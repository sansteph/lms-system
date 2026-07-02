<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Content;
use App\Models\Course;
use App\Models\User;
use App\Models\Student;
use App\Models\SchoolClass;
use App\Models\CourseEnrollment;
use Illuminate\Support\Facades\Storage;
use App\Models\Assessment;
use App\Models\AssessmentResult;
use App\Models\LessonProgress;
use App\Models\ClassContentSession;
use App\Models\ClassTimetable;
use Illuminate\Support\Facades\DB;
use App\Support\DeletesAssessments;

class ContentController extends Controller
{
    use DeletesAssessments;
    public function index(Request $request)
    {
        $search = $request->search;

        $courses = Course::where('status', 1)
            ->when(session('user_role') == 'InstituteAdmin', function ($query) {
                $query->where(function ($q) {
                    $q->where('availability_type', 'Institute')
                      ->orWhere('availability_type', 'Both');
                });
            })
            ->get();

        $contents = Content::when(session('user_role') == 'InstituteAdmin', function ($query) {
                $query->where('institute', session('user_institute'));
            })
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('content_title', 'like', "%{$search}%")
                      ->orWhere('content_type', 'like', "%{$search}%")
                      ->orWhere('assigned_class', 'like', "%{$search}%");
                });
            })
            ->get();

        return view('content', compact('contents', 'courses'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'content_title' => 'required|string|max:255',
            'course_id' => 'required|exists:courses,id',
            'lesson_order' => 'required|integer|min:1',
            'assigned_class' => 'required|string|max:255',
            'file' => 'required|file|mimes:ppt,pptx|max:51200',
            'student_file' => 'required|file|mimes:doc,docx|max:51200',
            'status' => 'required|boolean',
            'institute' => session('user_role') == 'Admin'
                ? 'required|string|max:255'
                : 'nullable|string|max:255',
        ]);

        [$filePath, $previewPdfPath] = $this->storePrivateContentFile($request->file('file'));
        [$studentFilePath, $studentPreviewPdfPath] = $this->storePrivateContentFile($request->file('student_file'));

        Content::create([
            'institute' => session('user_role') == 'InstituteAdmin'
                ? session('user_institute')
                : $request->institute,

            'content_title' => $request->content_title,
            'course_id' => $request->course_id,
            'lesson_order' => $request->lesson_order,
            'content_type' => 'PPT',
            'assigned_class' => $request->assigned_class,
            'file_path' => $filePath,
            'preview_pdf_path' => $previewPdfPath,
            'student_file_path' => $studentFilePath,
            'student_preview_pdf_path' => $studentPreviewPdfPath,
            'status' => $request->status,
        ]);

        return redirect()->back()
            ->with('success', 'Content uploaded successfully');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'content_title' => 'required|string|max:255',
            'course_id' => 'required|exists:courses,id',
            'lesson_order' => 'required|integer|min:1',
            'assigned_class' => 'required|string|max:255',
            'file' => 'nullable|file|mimes:ppt,pptx|max:51200',
            'student_file' => 'nullable|file|mimes:doc,docx|max:51200',
            'status' => 'required|boolean',
            'institute' => session('user_role') == 'Admin'
                ? 'required|string|max:255'
                : 'nullable|string|max:255',
        ]);

        $content = Content::findOrFail($id);

        if (
            session('user_role') == 'InstituteAdmin' &&
            $content->institute != session('user_institute')
        ) {
            abort(403, 'Unauthorized action.');
        }

        $filePath = $content->file_path;
        $previewPdfPath = $content->preview_pdf_path;
        $studentFilePath = $content->student_file_path;
        $studentPreviewPdfPath = $content->student_preview_pdf_path;

        if ($request->hasFile('file')) {
            $this->deleteContentStoragePath($content->file_path);
            $this->deleteContentStoragePath($content->preview_pdf_path);

            [$filePath, $previewPdfPath] = $this->storePrivateContentFile($request->file('file'));
        }

        if ($request->hasFile('student_file')) {
            $this->deleteContentStoragePath($content->student_file_path);
            $this->deleteContentStoragePath($content->student_preview_pdf_path);

            [$studentFilePath, $studentPreviewPdfPath] = $this->storePrivateContentFile($request->file('student_file'));
        }

        $content->update([
            'institute' => session('user_role') == 'InstituteAdmin'
                ? session('user_institute')
                : $request->institute,
            'content_title' => $request->content_title,
            'course_id' => $request->course_id,
            'lesson_order' => $request->lesson_order,
            'content_type' => 'PPT',
            'assigned_class' => $request->assigned_class,
            'file_path' => $filePath,
            'preview_pdf_path' => $previewPdfPath,
            'student_file_path' => $studentFilePath,
            'student_preview_pdf_path' => $studentPreviewPdfPath,
            'status' => $request->status,
        ]);

        return redirect()->back()
            ->with('success', 'Content updated successfully');
    }

    public function showPreview(Request $request, Content $content, $audience = 'auto')
    {
        if (!in_array($audience, ['auto', 'teacher', 'student'])) {
            abort(404);
        }

        $audience = $this->resolveContentAudience($audience);

        if (!$this->canViewContent($content, $audience)) {
            abort(403, 'You are not authorized to view this content.');
        }

        $paths = $this->contentPathsForAudience($content, $audience);

        if (!$paths['file']) {
            abort(404);
        }

        $extension = strtolower(pathinfo($paths['file'], PATHINFO_EXTENSION));
        $previewExtensions = ['ppt', 'pptx', 'doc', 'docx'];

        if (in_array($extension, $previewExtensions) && !$paths['preview']) {
            abort(404);
        }

        $sourceUrl = route('content.preview.stream', [$content->id, $audience]);
        $allowFullscreen = $audience == 'student';

        return view('content.secure-preview', compact(
            'content',
            'audience',
            'extension',
            'sourceUrl',
            'allowFullscreen'
        ));
    }

    public function streamPreview(Request $request, Content $content, $audience = 'auto')
    {
        $fetchDestination = $request->headers->get('sec-fetch-dest');

        if ($fetchDestination && !in_array($fetchDestination, ['iframe', 'embed', 'object'])) {
            abort(403, 'Content previews must be viewed inside the LMS.');
        }

        if (!in_array($audience, ['auto', 'teacher', 'student'])) {
            abort(404);
        }

        $audience = $this->resolveContentAudience($audience);

        if (!$this->canViewContent($content, $audience)) {
            abort(403, 'You are not authorized to view this content.');
        }

        $paths = $this->contentPathsForAudience($content, $audience);

        if (!$paths['file']) {
            abort(404);
        }

        $extension = strtolower(pathinfo($paths['file'], PATHINFO_EXTENSION));
        $previewExtensions = ['ppt', 'pptx', 'doc', 'docx'];
        $storagePath = in_array($extension, $previewExtensions)
            ? $paths['preview']
            : $paths['file'];

        if (!$storagePath) {
            abort(404);
        }

        $resolved = $this->resolveContentStoragePath($storagePath);

        if (!$resolved) {
            abort(404);
        }

        $mimeType = mime_content_type($resolved['path']) ?: 'application/octet-stream';

        return response()->file($resolved['path'], [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline',
            'Cache-Control' => 'private, no-store, no-cache, must-revalidate',
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "frame-ancestors 'self'",
        ]);
    }

    public function showFile(Request $request, Content $content, $audience = 'auto', $variant = 'file')
    {
        $fetchDestination = $request->headers->get('sec-fetch-dest');

        if ($fetchDestination && $fetchDestination == 'document') {
            abort(403, 'Content files must be viewed inside the LMS.');
        }

        if (in_array($audience, ['file', 'preview'])) {
            $variant = $audience;
            $audience = 'auto';
        }

        if (!in_array($audience, ['auto', 'teacher', 'student'])) {
            abort(404);
        }

        if (!in_array($variant, ['file', 'preview'])) {
            abort(404);
        }

        $audience = $this->resolveContentAudience($audience);

        if (!$this->canViewContent($content, $audience)) {
            abort(403, 'You are not authorized to view this content.');
        }

        $paths = $this->contentPathsForAudience($content, $audience);

        if (!$paths['file']) {
            abort(404);
        }

        $originalExtension = strtolower(pathinfo($paths['file'], PATHINFO_EXTENSION));
        $previewOnlyExtensions = ['ppt', 'pptx', 'doc', 'docx'];

        if (in_array($originalExtension, $previewOnlyExtensions)) {
            abort(404);
        }

        if ($variant == 'preview') {
            abort(404);
        }

        $storagePath = $variant == 'preview'
            ? $paths['preview']
            : $paths['file'];

        if (!$storagePath) {
            abort(404);
        }

        $resolved = $this->resolveContentStoragePath($storagePath);

        if (!$resolved) {
            abort(404);
        }

        $mimeType = mime_content_type($resolved['path']) ?: 'application/octet-stream';
        $fileName = basename($resolved['path']);
        $safeFileName = str_replace('"', '', $fileName);

        return response()->file($resolved['path'], [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . $safeFileName . '"',
            'Cache-Control' => 'private, no-store, no-cache, must-revalidate',
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "frame-ancestors 'self'",
        ]);
    }

    public function delete($id)
    {
        $content = Content::findOrFail($id);

        if (
            session('user_role') == 'InstituteAdmin' &&
            $content->institute != session('user_institute')
        ) {
            abort(403, 'Unauthorized action.');
        }

        DB::transaction(function () use ($content, $id) {

            Assessment::where('content_id', $id)
                ->update(['content_id' => null]);

            LessonProgress::where('content_id', $id)->delete();

            ClassContentSession::where('content_id', $id)->delete();

            ClassTimetable::where('content_id', $id)->delete();

            $this->deleteContentStoragePath($content->file_path);
            $this->deleteContentStoragePath($content->preview_pdf_path);
            $this->deleteContentStoragePath($content->student_file_path);
            $this->deleteContentStoragePath($content->student_preview_pdf_path);

            $content->delete();
        });

        return redirect()->back()
            ->with('success', 'Content and all related records deleted successfully.');
    }

    private function storePrivateContentFile($file)
    {
        $fileName = time() . '_' . uniqid() . '_' . $file->getClientOriginalName();
        $filePath = $file->storeAs('contents', $fileName, 'local');
        $previewPdfPath = $this->createPrivatePreviewPdf(
            $filePath,
            $fileName,
            strtolower($file->getClientOriginalExtension())
        );

        return [$filePath, $previewPdfPath];
    }

    private function createPrivatePreviewPdf($filePath, $fileName, $extension)
    {
        if (!in_array($extension, ['ppt', 'pptx', 'doc', 'docx'])) {
            return null;
        }

        $inputPath = Storage::disk('local')->path($filePath);
        $outputDir = Storage::disk('local')->path('content-previews');

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
            return 'content-previews/' . $pdfFileName;
        }

        return null;
    }

    private function resolveContentAudience($audience)
    {
        if ($audience != 'auto') {
            return $audience;
        }

        if (session('student_id') || session('independent_learner_id')) {
            return 'student';
        }

        return 'teacher';
    }

    private function contentPathsForAudience(Content $content, $audience)
    {
        if ($audience == 'student') {
            return [
                'file' => $content->student_file_path,
                'preview' => $content->student_preview_pdf_path,
            ];
        }

        return [
            'file' => $content->file_path,
            'preview' => $content->preview_pdf_path,
        ];
    }

    private function canViewContent(Content $content, $audience)
    {
        if (session('user_role') == 'Admin') {
            return true;
        }

        if (session('user_role') == 'InstituteAdmin') {
            return $content->institute == session('user_institute');
        }

        if (session('user_role') == 'Teacher') {
            if ($audience != 'teacher') {
                return false;
            }

            $teacher = User::find(session('user_id'));

            return $teacher && $this->teacherCanViewContent($teacher, $content);
        }

        if (session('student_id')) {
            if ($audience != 'student') {
                return false;
            }

            $student = Student::find(session('student_id'));

            return $student && $this->studentCanViewContent($student, $content);
        }

        if (session('independent_learner_id')) {
            if ($audience != 'student') {
                return false;
            }

            return $content->status == 1 &&
                $content->is_released &&
                $content->student_file_path &&
                CourseEnrollment::where('learner_id', session('independent_learner_id'))
                ->where('course_id', $content->course_id)
                ->exists();
        }

        return false;
    }

    private function teacherCanViewContent(User $teacher, Content $content)
    {
        if ($content->institute != $teacher->institute || $content->status != 1) {
            return false;
        }

        $assignedClasses = SchoolClass::where('institute', $teacher->institute)
            ->where('class_teacher', $teacher->name)
            ->get()
            ->map(function ($class) {
                return trim($class->class_name . ' ' . $class->section);
            });

        $contentClass = $content->course
            ? $content->course->assigned_class
            : $content->assigned_class;

        return $assignedClasses->contains($contentClass);
    }

    private function studentCanViewContent(Student $student, Content $content)
    {
        $course = $this->studentAssignedCourse($student);

        if (
            !$course ||
            $content->course_id != $course->id ||
            $content->status != 1 ||
            !$content->is_released ||
            !$content->student_file_path
        ) {
            return false;
        }

        $previousLesson = Content::where('course_id', $course->id)
            ->where('lesson_order', $content->lesson_order - 1)
            ->first();

        if (!$previousLesson) {
            return true;
        }

        return LessonProgress::where('student_id', $student->id)
            ->where('content_id', $previousLesson->id)
            ->where('is_completed', true)
            ->exists();
    }

    private function studentAssignedCourse(Student $student)
    {
        $assignedClass = trim($student->class . ' ' . $student->section);

        return Course::where('institute', $student->institute)
            ->whereRaw(
                "REPLACE(TRIM(assigned_class), '  ', ' ') = ?",
                [$assignedClass]
            )
            ->first();
    }

    private function resolveContentStoragePath($path)
    {
        if (!Storage::disk('local')->exists($path)) {
            return null;
        }

        return [
            'disk' => 'local',
            'path' => Storage::disk('local')->path($path),
        ];
    }

    private function deleteContentStoragePath($path)
    {
        if (!$path) {
            return;
        }

        foreach (['local', 'public'] as $disk) {
            if (Storage::disk($disk)->exists($path)) {
                Storage::disk($disk)->delete($path);
            }
        }
    }
}
