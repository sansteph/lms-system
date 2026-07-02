<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Assessment;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Support\DeletesAssessments;

class AssessmentController extends Controller
{
    use DeletesAssessments;

    public function index(Request $request)
    {
        $search = $request->search;
        $teacherClassNames = $this->teacherAssignedClassNames();

        $assessments = Assessment::with(['teacher', 'questionPaperReviewer'])
            ->when(session('user_role') == 'Teacher', function ($query) use ($teacherClassNames) {
                $query->where('teacher_id', session('user_id'))
                    ->whereIn('assigned_class', $teacherClassNames);
            })
            ->when(session('user_role') == 'InstituteAdmin', function ($query) {
                $query->where('institute', session('user_institute'));
            })
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('assessment_title', 'like', "%{$search}%")
                        ->orWhere('assessment_type', 'like', "%{$search}%")
                        ->orWhere('assigned_class', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->get();

        $teacher = User::find(session('user_id'));

        $classOptions = $teacherClassNames;

        return view('assessments', compact('assessments', 'classOptions', 'teacher'));
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
            'total_marks' => 'required|integer|min:1',
            'duration' => 'required|string|max:50',
            'file' => 'required|file|mimes:pdf,ppt,pptx,doc,docx,jpg,jpeg,png,webp|max:51200',
            'status' => 'required|boolean',
        ]);

        $teacher = User::findOrFail(session('user_id'));
        $teacherClassNames = $this->teacherAssignedClassNames();

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
            'total_marks' => 'required|integer|min:1',
            'duration' => 'required|string|max:50',
            'file' => 'nullable|file|mimes:pdf,ppt,pptx,doc,docx,jpg,jpeg,png,webp|max:51200',
            'status' => 'required|boolean',
        ]);

        $teacher = User::findOrFail(session('user_id'));
        $assessment = Assessment::findOrFail($id);

        if ($assessment->teacher_id != $teacher->id) {
            abort(403, 'You can only edit assessments created by you.');
        }

        $teacherClassNames = $this->teacherAssignedClassNames();

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
            ->latest()
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
        $previewOnlyExtensions = ['ppt', 'pptx', 'doc', 'docx'];

        if (in_array($extension, $previewOnlyExtensions)) {
            if (!$assessment->question_paper_preview_path) {
                abort(404);
            }

            $variant = 'preview';
        }

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

    private function teacherAssignedClassNames()
    {
        if (session('user_role') != 'Teacher') {
            return collect();
        }

        $teacher = User::find(session('user_id'));

        if (!$teacher) {
            return collect();
        }

        return SchoolClass::where('institute', $teacher->institute)
            ->where('class_teacher', $teacher->name)
            ->get()
            ->map(function ($class) {
                return trim($class->class_name . ' ' . $class->section);
            })
            ->values();
    }

    private function authorizeTeacherAssessmentScope($assignedClass, $teacherClassNames)
    {
        if (!$teacherClassNames->contains($assignedClass)) {
            abort(403, 'You can only create assessments for your assigned classes.');
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
            !$assessment->file_path
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
        $previewPath = $this->createQuestionPaperPreviewPdf(
            $filePath,
            $fileName,
            strtolower($file->getClientOriginalExtension())
        );

        return [$filePath, $previewPath];
    }

    private function createQuestionPaperPreviewPdf($filePath, $fileName, $extension)
    {
        if (!in_array($extension, ['ppt', 'pptx', 'doc', 'docx'])) {
            return null;
        }

        $inputPath = Storage::disk('local')->path($filePath);
        $outputDir = Storage::disk('local')->path('assessment-paper-previews');

        if (!file_exists($outputDir)) {
            mkdir($outputDir, 0775, true);
        }

        $libreOfficePath = '"C:\\Program Files\\LibreOffice\\program\\soffice.exe"';

        $command = $libreOfficePath
            . ' --headless'
            . ' --convert-to pdf'
            . ' --outdir ' . escapeshellarg($outputDir)
            . ' ' . escapeshellarg($inputPath);

        exec($command, $output, $resultCode);

        $pdfFileName = pathinfo($fileName, PATHINFO_FILENAME) . '.pdf';
        $convertedPdfPath = $outputDir . DIRECTORY_SEPARATOR . $pdfFileName;

        if ($resultCode === 0 && file_exists($convertedPdfPath)) {
            return 'assessment-paper-previews/' . $pdfFileName;
        }

        return null;
    }
}
