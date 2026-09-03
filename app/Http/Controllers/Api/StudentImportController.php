<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\StudentCsvImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class StudentImportController extends Controller
{
    private function institute(Request $request): ?string
    {
        $user = $request->user();
        abort_unless($user instanceof User && in_array($user->role, ['Admin', 'InstituteAdmin', 'Teacher', 'STEM Engineer'], true), 403);
        if ($user->role === 'Admin') {
            return null;
        }
        abort_unless(filled($user->institute), 403, 'An institute must be assigned first.');

        return $user->institute;
    }

    public function store(Request $request, StudentCsvImportService $importer)
    {
        $institute = $this->institute($request);
        $request->validate(['students_csv' => 'required|file|mimes:csv,txt|max:5120']);

        return response()->json($importer->import($request->file('students_csv'), $institute));
    }

    public function template(Request $request)
    {
        $institute = $this->institute($request);

        return response()->json(['columns' => StudentCsvImportService::COLUMNS, 'institute' => $institute,
            'download_url' => URL::temporarySignedRoute('mobile.student-import-template', now()->addMinutes(5), ['accountId' => $request->user()->id]),
            'example' => ['STU001', 'Student Name', $institute ?: 'Institute Name', 'Class 10', 'A', '9876543210', 'student@example.com', 'Guardian Name', 'No', 'Student@123', 'Active']]);
    }

    public function download(int $accountId)
    {
        $user = User::findOrFail($accountId);
        abort_unless(in_array($user->role, ['Admin', 'InstituteAdmin', 'Teacher', 'STEM Engineer'], true), 403);

        return response()->streamDownload(function () use ($user) {
            $file = fopen('php://output', 'w');
            fputcsv($file, StudentCsvImportService::COLUMNS);
            fputcsv($file, ['STU001', 'Student Name', $user->role === 'Admin' ? 'Institute Name' : $user->institute, 'Class 10', 'A', '9876543210', 'student@example.com', 'Guardian Name', 'No', 'Student@123', 'Active']);
            fclose($file);
        }, 'student_bulk_upload_template.csv', ['Content-Type' => 'text/csv', 'Cache-Control' => 'private, no-store']);
    }
}
