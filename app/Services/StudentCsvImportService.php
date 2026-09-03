<?php

namespace App\Services;

use App\Models\SchoolClass;
use App\Models\Student;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StudentCsvImportService
{
    public const COLUMNS = ['student_id', 'name', 'institute', 'class', 'section', 'contact', 'email', 'guardian_name', 'is_robotics_club_member', 'password', 'status'];

    public function import(UploadedFile $file, ?string $managedInstitute): array
    {
        $handle = fopen($file->getRealPath(), 'r');
        if (! $handle) {
            throw ValidationException::withMessages(['students_csv' => 'Unable to read the CSV.']);
        }
        $created = 0;
        $skipped = 0;
        $errors = [];
        try {
            $header = fgetcsv($handle);
            if (! $header) {
                throw ValidationException::withMessages(['students_csv' => 'The CSV is empty.']);
            }
            $header = array_map(fn ($value) => strtolower(trim((string) $value, " \t\n\r\0\x0B\xEF\xBB\xBF")), $header);
            $required = ['student_id', 'name', 'class', 'section', 'contact', 'password'];
            if ($managedInstitute === null) {
                $required[] = 'institute';
            }
            $missing = array_diff($required, $header);
            if ($missing || count(array_unique($header)) !== count($header)) {
                throw ValidationException::withMessages(['students_csv' => $missing ? 'Missing columns: '.implode(', ', $missing) : 'CSV column names must be unique.']);
            }
            $line = 1;
            while (($row = fgetcsv($handle)) !== false) {
                $line++;
                if (! array_filter($row, fn ($value) => trim((string) $value) !== '')) {
                    continue;
                }
                if (count($row) !== count($header)) {
                    $skipped++;
                    $errors[] = "Row {$line}: column count does not match the header.";

                    continue;
                }
                $data = array_combine($header, array_map(fn ($value) => trim((string) $value), $row));
                $data['institute'] = $managedInstitute ?? ($data['institute'] ?? '');
                $validator = Validator::make($data, [
                    'student_id' => ['required', 'string', 'max:255', Rule::unique('students', 'student_id')->where('institute', $data['institute'])],
                    'name' => 'required|string|max:255', 'institute' => 'required|exists:institutes,institute_name',
                    'class' => 'required|string|max:255', 'section' => 'required|string|max:50',
                    'contact' => 'required|string|max:30', 'password' => 'required|string|min:6|max:255',
                    'email' => 'nullable|email|max:255', 'guardian_name' => 'nullable|string|max:255',
                    'is_robotics_club_member' => ['nullable', Rule::in(['', 'Yes', 'No', 'yes', 'no', '1', '0', 'true', 'false', 'y', 'n'])],
                    'status' => ['nullable', Rule::in(['', 'Active', 'Inactive', 'active', 'inactive', '1', '0'])],
                ]);
                $validator->after(function ($validator) use ($data) {
                    if (! SchoolClass::where('institute', $data['institute'])->where('class_name', $data['class'] ?? '')->where('section', $data['section'] ?? '')->exists()) {
                        $validator->errors()->add('class', 'Class and section do not exist in this institute.');
                    }
                });
                if ($validator->fails()) {
                    $skipped++;
                    $errors[] = "Row {$line}: ".implode(' ', $validator->errors()->all());

                    continue;
                }
                Student::create([
                    'student_id' => $data['student_id'], 'name' => $data['name'], 'institute' => $data['institute'],
                    'class' => $data['class'], 'section' => $data['section'], 'contact' => $data['contact'],
                    'email' => ($data['email'] ?? '') ?: null, 'guardian_name' => ($data['guardian_name'] ?? '') ?: null,
                    'is_robotics_club_member' => in_array(strtolower($data['is_robotics_club_member'] ?? ''), ['yes', 'y', '1', 'true'], true),
                    'password' => Hash::make($data['password']),
                    'status' => ! in_array(strtolower($data['status'] ?? 'Active'), ['inactive', '0'], true), 'profile_completed' => true,
                ]);
                $created++;
            }
        } finally {
            fclose($handle);
        }

        return ['created' => $created, 'skipped' => $skipped, 'errors' => $errors,
            'message' => "Bulk upload completed. Created {$created} student(s), skipped {$skipped} row(s)."];
    }
}
