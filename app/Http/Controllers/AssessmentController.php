<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Assessment;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use App\Models\Content;
use App\Models\TeachingPlanItem;
use App\Services\Ai\GeminiAiService;
use App\Services\Ai\PdfTextExtractionService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Support\DeletesAssessments;
use App\Support\BuildsInstituteSectionPager;

class AssessmentController extends Controller
{
    use BuildsInstituteSectionPager, DeletesAssessments;

    public function index(Request $request)
    {
        $search = $request->search;
        $selectedClass = $request->input('class');
        $teacherClassNames = $this->teacherInstituteClassNames();
        $sectionPager = null;
        $currentInstitute = null;

        if (session('user_role') == 'Admin') {
            ['currentInstitute' => $currentInstitute, 'sectionPager' => $sectionPager] =
                $this->buildInstituteSectionPager($request, $request->route()?->getName() ?: 'teacher.assessments');
        }

        $assessments = Assessment::with(['teacher', 'questionPaperReviewer'])
            ->when(session('user_role') == 'Teacher', function ($query) {
                $teacher = User::find(session('user_id'));

                $query->where('teacher_id', session('user_id'))
                    ->where('institute', $teacher?->institute);
            })
            ->when(session('user_role') == 'InstituteAdmin', function ($query) {
                $query->where('institute', session('user_institute'));
            })
            ->when(session('user_role') == 'Admin' && $currentInstitute, function ($query) use ($currentInstitute) {
                $query->where('institute', $currentInstitute);
            })
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('assessment_title', 'like', "%{$search}%")
                        ->orWhere('assessment_type', 'like', "%{$search}%")
                        ->orWhere('assigned_class', 'like', "%{$search}%");
                });
            })
            ->when($selectedClass, function ($query) use ($selectedClass) {
                $query->where('assigned_class', $selectedClass);
            })
            ->orderBy('assigned_class')
            ->latest()
            ->paginate(30)
            ->withQueryString();

        $teacher = User::find(session('user_id'));
        $availableContents = $this->teacherAssessmentContents($teacher);

        $classOptions = $teacherClassNames;

        return view('assessments', compact('assessments', 'classOptions', 'teacher', 'selectedClass', 'availableContents', 'sectionPager'));
    }

    public function store(Request $request)
    {
        if (session('user_role') != 'Teacher') {
            abort(403, 'Only STEM Engineers can create assessments.');
        }

        $request->validate([
            'assessment_title' => 'required|string|max:255',
            'assessment_type' => 'required|string',
            'assigned_class' => 'required|string',
            'assessment_category' => 'required|in:Monthly,Annual',
            'assessment_date' => 'required|date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
            'total_marks' => 'required|integer|min:1',
            'duration' => 'required|string|max:50',
            'file' => 'required|file|mimes:pdf|max:51200',
            'status' => 'required|boolean',
        ]);

        $teacher = User::findOrFail(session('user_id'));
        $teacherClassNames = $this->teacherInstituteClassNames();

        $this->authorizeTeacherAssessmentScope(
            $request->assigned_class,
            $teacherClassNames
        );

        [$filePath, $previewPath] = $this->storePrivateQuestionPaper($request->file('file'));

        Assessment::create([
            'institute' => $teacher->institute,
            'assessment_title' => $request->assessment_title,
            'assessment_type' => $request->assessment_type,
            'assigned_class' => $request->assigned_class,
            'assessment_category' => $request->assessment_category,
            'assessment_date' => $request->assessment_date,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'total_marks' => $request->total_marks,
            'duration' => $request->duration,
            'question_paper_type' => 'Uploaded Question Paper',
            'file_path' => $filePath,
            'question_paper_preview_path' => $previewPath,
            'question_paper_status' => 'Pending Approval',
            'question_paper_reviewed_by' => null,
            'question_paper_reviewed_at' => null,
            'question_paper_feedback' => null,
            'status' => $request->status,
            'content_id' => null,
            'teacher_id' => $teacher->id,
        ]);

        return redirect()->back()
            ->with('success', 'Assessment uploaded and sent for admin approval.');
    }

    public function generateAiQuestionPaper(
        Request $request,
        GeminiAiService $ai,
        PdfTextExtractionService $pdfTextExtractionService
    ) {
        if (session('user_role') != 'Teacher') {
            abort(403, 'Only STEM Engineers can create AI question papers.');
        }

        $request->validate([
            'assessment_title' => 'required|string|max:255',
            'assessment_type' => 'required|string',
            'assigned_class' => 'required|string',
            'assessment_category' => 'required|in:Monthly,Annual',
            'assessment_date' => 'required|date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
            'total_marks' => 'required|integer|min:1',
            'duration' => 'required|string|max:50',
            'content_ids' => 'required|array|min:1',
            'content_ids.*' => 'integer|exists:contents,id',
            'status' => 'required|boolean',
        ]);

        $teacher = User::findOrFail(session('user_id'));
        $teacherClassNames = $this->teacherInstituteClassNames();

        $this->authorizeTeacherAssessmentScope(
            $request->assigned_class,
            $teacherClassNames
        );

        $contents = $this->authorizedAssessmentContentsQuery($teacher, $request->assigned_class)
            ->whereIn('contents.id', $request->content_ids)
            ->get()
            ->unique('id')
            ->values();

        if ($contents->count() != count(array_unique($request->content_ids))) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'One or more selected contents are not available for this institute and class.');
        }

        try {
            $contentContext = $this->extractAssessmentContentContext($contents, $pdfTextExtractionService);

            $paper = $ai->generateAssessmentQuestionPaper([
                'assessment_title' => $request->assessment_title,
                'assessment_category' => $request->assessment_category,
                'assigned_class' => $request->assigned_class,
                'total_marks' => (int) $request->total_marks,
                'duration_minutes' => $request->duration,
                'content_titles' => $contents->pluck('content_title')->values()->all(),
                'content_text' => $contentContext,
            ]);

            [$filePath, $previewPath] = $this->storeAiQuestionPaperPdf($paper, [
                'assessment_title' => $request->assessment_title,
                'assessment_category' => $request->assessment_category,
                'assigned_class' => $request->assigned_class,
                'assessment_date' => $request->assessment_date,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'total_marks' => $request->total_marks,
                'duration' => $request->duration,
                'teacher_name' => $teacher->name,
                'institute' => $teacher->institute,
                'contents' => $contents,
            ]);
        } catch (\Throwable $exception) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'AI question paper could not be generated: ' . $exception->getMessage());
        }

        Assessment::create([
            'institute' => $teacher->institute,
            'assessment_title' => $request->assessment_title,
            'assessment_type' => $request->assessment_type,
            'assigned_class' => $request->assigned_class,
            'assessment_category' => $request->assessment_category,
            'assessment_date' => $request->assessment_date,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'total_marks' => $request->total_marks,
            'duration' => $request->duration,
            'question_paper_type' => 'AI Generated Question Paper',
            'ai_generated' => true,
            'ai_source_content_ids' => $contents->pluck('id')->values()->all(),
            'ai_generation_payload' => json_encode($paper, JSON_UNESCAPED_SLASHES),
            'file_path' => $filePath,
            'question_paper_preview_path' => $previewPath,
            'question_paper_status' => 'Pending Approval',
            'question_paper_reviewed_by' => null,
            'question_paper_reviewed_at' => null,
            'question_paper_feedback' => null,
            'status' => $request->status,
            'content_id' => $contents->first()?->id,
            'teacher_id' => $teacher->id,
        ]);

        return redirect()->back()
            ->with('success', 'AI question paper generated and sent for admin approval.');
    }

    public function update(Request $request, $id)
    {
        if (session('user_role') != 'Teacher') {
            abort(403, 'Only STEM Engineers can edit assessments.');
        }

        $request->validate([
            'assessment_title' => 'required|string|max:255',
            'assessment_type' => 'required|string',
            'assigned_class' => 'required|string',
            'assessment_category' => 'required|in:Monthly,Annual',
            'assessment_date' => 'required|date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
            'total_marks' => 'required|integer|min:1',
            'duration' => 'required|string|max:50',
            'file' => 'nullable|file|mimes:pdf|max:51200',
            'status' => 'required|boolean',
        ]);

        $teacher = User::findOrFail(session('user_id'));
        $assessment = Assessment::findOrFail($id);

        if ($assessment->teacher_id != $teacher->id) {
            abort(403, 'You can only edit assessments created by you.');
        }

        $teacherClassNames = $this->teacherInstituteClassNames();

        $this->authorizeTeacherAssessmentScope(
            $request->assigned_class,
            $teacherClassNames
        );

        $updates = [
            'institute' => $teacher->institute,
            'assessment_title' => $request->assessment_title,
            'assessment_type' => $request->assessment_type,
            'assigned_class' => $request->assigned_class,
            'assessment_category' => $request->assessment_category,
            'assessment_date' => $request->assessment_date,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'total_marks' => $request->total_marks,
            'duration' => $request->duration,
            'question_paper_type' => 'Uploaded Question Paper',
            'status' => $request->status,
            'content_id' => null,
        ];

        if ($request->hasFile('file')) {
            $this->deleteStoredFile($assessment->file_path);
            $this->deleteStoredFile($assessment->question_paper_preview_path);

            [$filePath, $previewPath] = $this->storePrivateQuestionPaper($request->file('file'));

            $updates = array_merge($updates, [
                'file_path' => $filePath,
                'question_paper_preview_path' => $previewPath,
                'question_paper_status' => 'Pending Approval',
                'question_paper_reviewed_by' => null,
                'question_paper_reviewed_at' => null,
                'question_paper_feedback' => null,
            ]);
        }

        $assessment->update($updates);

        return redirect()->back()
            ->with('success', $request->hasFile('file')
                ? 'Assessment updated and question paper sent for approval.'
                : 'Assessment updated successfully.');
    }

    public function delete($id)
    {
        if (session('user_role') != 'Teacher') {
            abort(403, 'Only STEM Engineers can delete assessments.');
        }

        $assessment = Assessment::findOrFail($id);

        if ($assessment->teacher_id != session('user_id')) {
            abort(403, 'You can only delete assessments created by you.');
        }

        DB::transaction(function () use ($assessment) {
            $this->deleteAssessmentCompletely($assessment);
        });

        return redirect()->back()
            ->with('success', 'Assessment and all related records deleted successfully.');
    }

    public function adminQuestionPapers()
    {
        $assessments = Assessment::with(['teacher', 'questionPaperReviewer'])
            ->whereNotNull('file_path')
            ->when(session('user_role') == 'InstituteAdmin', function ($query) {
                $query->where('institute', session('user_institute'));
            })
            ->orderBy('institute')
            ->orderBy('assigned_class')
            ->latest('created_at')
            ->get();

        return view('admin-question-papers', compact('assessments'));
    }

    public function approveQuestionPaper($id)
    {
        $assessment = Assessment::findOrFail($id);
        $this->authorizeAdminQuestionPaper($assessment);

        $assessment->update([
            'question_paper_status' => 'Approved',
            'question_paper_reviewed_by' => session('user_id'),
            'question_paper_reviewed_at' => now(),
            'question_paper_feedback' => null,
        ]);

        return redirect()->back()
            ->with('success', 'Question paper approved successfully.');
    }

    public function rejectQuestionPaper($id)
    {
        $assessment = Assessment::findOrFail($id);
        $this->authorizeAdminQuestionPaper($assessment);

        $assessment->update([
            'question_paper_status' => 'Rejected',
            'question_paper_reviewed_by' => session('user_id'),
            'question_paper_reviewed_at' => now(),
            'question_paper_feedback' => null,
        ]);

        return redirect()->back()
            ->with('success', 'Question paper rejected.');
    }

    public function showQuestionPaper(Assessment $assessment, $variant = 'file')
    {
        $fetchDestination = request()->headers->get('sec-fetch-dest');

        if (session('student_id') && $fetchDestination && $fetchDestination == 'document') {
            abort(403, 'Question papers must be viewed inside the assessment page.');
        }

        if (!$this->canViewQuestionPaper($assessment)) {
            abort(403, 'You are not authorized to view this question paper.');
        }

        if (!in_array($variant, ['file', 'preview'])) {
            abort(404);
        }

        $extension = strtolower(pathinfo($assessment->file_path ?? '', PATHINFO_EXTENSION));
        $storagePath = $variant == 'preview'
            ? $assessment->question_paper_preview_path
            : $assessment->file_path;

        if (!$storagePath || !Storage::disk('local')->exists($storagePath)) {
            abort(404);
        }

        $path = Storage::disk('local')->path($storagePath);
        $mimeType = mime_content_type($path) ?: 'application/octet-stream';
        $fileName = str_replace('"', '', basename($path));

        return response()->file($path, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline',
            'Cache-Control' => 'private, no-store, no-cache, must-revalidate',
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "frame-ancestors 'self'",
        ]);
    }

    private function teacherInstituteClassNames()
    {
        if (session('user_role') != 'Teacher') {
            return collect();
        }

        $teacher = User::find(session('user_id'));

        if (!$teacher) {
            return collect();
        }

        return SchoolClass::where('institute', $teacher->institute)
            ->get()
            ->map(function ($class) {
                return trim($class->class_name . ' ' . $class->section);
            })
            ->values();
    }

    private function authorizeTeacherAssessmentScope($assignedClass, $teacherClassNames)
    {
        if (!$teacherClassNames->contains($assignedClass)) {
            abort(403, 'You can only create assessments for classes in your institute.');
        }
    }

    private function authorizeAdminQuestionPaper(Assessment $assessment)
    {
        if (
            session('user_role') == 'InstituteAdmin' &&
            $assessment->institute != session('user_institute')
        ) {
            abort(403, 'Unauthorized action.');
        }
    }

    private function canViewQuestionPaper(Assessment $assessment)
    {
        if (session('user_role') == 'Admin') {
            return true;
        }

        if (session('user_role') == 'InstituteAdmin') {
            return $assessment->institute == session('user_institute');
        }

        if (session('user_role') == 'Teacher') {
            $teacher = User::find(session('user_id'));

            return $teacher &&
                $assessment->teacher_id == $teacher->id &&
                $assessment->institute == $teacher->institute;
        }

        if (session('student_id')) {
            $student = Student::find(session('student_id'));

            return $student && $this->studentCanViewQuestionPaper($student, $assessment);
        }

        return false;
    }

    private function studentCanViewQuestionPaper(Student $student, Assessment $assessment)
    {
        if (
            $assessment->status != 1 ||
            $assessment->institute != $student->institute ||
            $assessment->question_paper_status != 'Approved' ||
            !$assessment->file_path ||
            !$this->assessmentWindowIsOpen($assessment)
        ) {
            return false;
        }

        if (
            $assessment->assessment_date &&
            $assessment->assessment_date > today()->toDateString()
        ) {
            return false;
        }

        return $this->normalizedClassName($assessment->assigned_class) ==
            $this->normalizedClassName(trim($student->class . ' ' . $student->section));
    }

    private function normalizedClassName($className)
    {
        return preg_replace('/\s+/', ' ', trim((string) $className));
    }

    private function storePrivateQuestionPaper($file)
    {
        $fileName = time() . '_' . $file->getClientOriginalName();
        $filePath = $file->storeAs('assessment-papers', $fileName, 'local');
        $previewPath = strtolower($file->getClientOriginalExtension()) === 'pdf'
            ? $filePath
            : null;

        return [$filePath, $previewPath];
    }

    private function teacherAssessmentContents(?User $teacher)
    {
        if (!$teacher) {
            return collect();
        }

        return $this->authorizedAssessmentContentsQuery($teacher)
            ->orderBy('contents.assigned_class')
            ->orderBy('contents.lesson_order')
            ->orderBy('contents.content_title')
            ->get()
            ->unique('id')
            ->values();
    }

    private function authorizedAssessmentContentsQuery(User $teacher, ?string $assignedClass = null)
    {
        return Content::query()
            ->select('contents.*')
            ->selectRaw("REPLACE(TRIM(CONCAT(COALESCE(teaching_plans.class, ''), ' ', COALESCE(teaching_plans.section, ''))), '  ', ' ') as assessment_class_label")
            ->join('teaching_plan_items', 'teaching_plan_items.content_id', '=', 'contents.id')
            ->join('teaching_plans', 'teaching_plans.id', '=', 'teaching_plan_items.teaching_plan_id')
            ->where('contents.institute', $teacher->institute)
            ->where('contents.status', 1)
            ->whereNotNull('contents.file_path')
            ->where('teaching_plans.institute', $teacher->institute)
            ->where('teaching_plans.status', 'active')
            ->whereIn('teaching_plan_items.status', ['released', 'completed'])
            ->when($assignedClass, function ($query) use ($assignedClass) {
                $query->whereRaw(
                    "REPLACE(TRIM(CONCAT(COALESCE(teaching_plans.class, ''), ' ', COALESCE(teaching_plans.section, ''))), '  ', ' ') = ?",
                    [$this->normalizedClassName($assignedClass)]
                );
            });
    }

    private function extractAssessmentContentContext($contents, PdfTextExtractionService $pdfTextExtractionService): string
    {
        $parts = [];

        foreach ($contents as $content) {
            $path = $this->preferredAssessmentPdfPath($content);

            if (!$path || !Storage::disk('local')->exists($path)) {
                continue;
            }

            $text = $pdfTextExtractionService->extract(Storage::disk('local')->path($path));

            $parts[] = "Content: {$content->content_title}\n" . mb_substr($text, 0, 8000);
        }

        $combined = trim(implode("\n\n---\n\n", $parts));

        if (mb_strlen($combined) < 100) {
            throw new \RuntimeException('Selected contents do not have enough readable PDF text for AI question paper generation.');
        }

        return $combined;
    }

    private function preferredAssessmentPdfPath(Content $content): ?string
    {
        foreach ([
            $content->student_preview_pdf_path,
            $content->preview_pdf_path,
            $content->student_file_path,
            $content->file_path,
        ] as $path) {
            if ($path && strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'pdf') {
                return $path;
            }
        }

        return null;
    }

    private function storeAiQuestionPaperPdf(array $paper, array $meta): array
    {
        $pdf = Pdf::loadView('pdf.ai-question-paper', [
            'paper' => $paper,
            'meta' => $meta,
        ])->setPaper('a4', 'portrait');

        $fileName = 'ai_question_paper_' . now()->format('Ymd_His') . '_' . uniqid() . '.pdf';
        $filePath = 'assessment-papers/' . $fileName;

        Storage::disk('local')->put($filePath, $pdf->output());

        return [$filePath, $filePath];
    }

    private function assessmentWindowIsOpen(Assessment $assessment): bool
    {
        if ($assessment->assessment_date) {
            $today = today()->toDateString();

            if ($assessment->assessment_date > $today) {
                return false;
            }

            if (($assessment->start_time || $assessment->end_time) && $assessment->assessment_date < $today) {
                return false;
            }
        }

        if ($assessment->start_time && now()->format('H:i:s') < $assessment->start_time) {
            return false;
        }

        if ($assessment->end_time && now()->format('H:i:s') > $assessment->end_time) {
            return false;
        }

        return true;
    }
}
