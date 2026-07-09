<?php

namespace App\Support;

use App\Models\Assessment;
use App\Models\AssessmentAnswer;
use App\Models\AssessmentQuestion;
use App\Models\AssessmentResult;
use App\Models\AssessmentSession;
use App\Models\Certificate;
use App\Models\CertificateVerificationLog;
use Illuminate\Support\Facades\Storage;

trait DeletesAssessments
{
    protected function deleteAssessmentCompletely(Assessment $assessment): void
    {
        $studentIds = AssessmentResult::where('assessment_id', $assessment->id)
            ->pluck('student_id')
            ->filter()
            ->unique()
            ->values();

        $this->deleteAssessmentLinkedCertificates($assessment, $studentIds);

        $this->deleteAssessmentResultsForAssessment($assessment);

        AssessmentSession::where('assessment_id', $assessment->id)->delete();
        AssessmentQuestion::where('assessment_id', $assessment->id)->delete();

        $this->deleteStoredFile($assessment->file_path);
        $this->deleteStoredFile($assessment->question_paper_preview_path);

        $assessment->delete();
    }

    protected function deleteAssessmentResultsForAssessment(Assessment $assessment): void
    {
        AssessmentResult::where('assessment_id', $assessment->id)
            ->get()
            ->each(function (AssessmentResult $result) {
                $this->deleteAssessmentResultCompletely($result);
            });

        AssessmentAnswer::where('assessment_id', $assessment->id)->delete();
    }

    protected function deleteAssessmentResultsForStudent($studentId): void
    {
        AssessmentResult::where('student_id', $studentId)
            ->get()
            ->each(function (AssessmentResult $result) {
                $this->deleteAssessmentResultCompletely($result);
            });

        AssessmentAnswer::where('student_id', $studentId)->delete();

        AssessmentSession::where('user_type', 'Student')
            ->where('user_id', $studentId)
            ->delete();
    }

    protected function deleteAssessmentResultCompletely(AssessmentResult $result): void
    {
        $answerQuery = AssessmentAnswer::where('assessment_result_id', $result->id);

        if ($result->assessment_id && $result->student_id) {
            $answerQuery->orWhere(function ($query) use ($result) {
                $query->where('assessment_id', $result->assessment_id)
                    ->where('student_id', $result->student_id);
            });
        }

        $answerQuery->delete();

        $this->deleteStoredFile($result->answer_file_path);

        $result->delete();
    }

    protected function deleteAssessmentLinkedCertificates(Assessment $assessment, $studentIds): void
    {
        if ($studentIds->isEmpty()) {
            return;
        }

        $certificates = Certificate::whereIn('student_id', $studentIds)
            ->when($assessment->assessment_category == 'Annual', function ($query) {
                $query->where('certificate_type', 'Annual');
            }, function ($query) {
                $query->whereRaw('1 = 0');
            })
            ->get();

        if ($certificates->isEmpty()) {
            return;
        }

        $certificateIds = $certificates->pluck('id');
        $certificateCodes = $certificates->pluck('certificate_code')->filter();

        foreach ($certificates as $certificate) {
            $this->deleteCertificateFiles($certificate);
        }

        CertificateVerificationLog::whereIn('certificate_id', $certificateIds)
            ->when($certificateCodes->isNotEmpty(), function ($query) use ($certificateCodes) {
                $query->orWhereIn('certificate_code', $certificateCodes);
            })
            ->delete();

        Certificate::whereIn('id', $certificateIds)->delete();
    }

    protected function deleteCertificateFiles(Certificate $certificate): void
    {
        foreach (['file_path', 'certificate_file', 'pdf_path', 'download_path'] as $field) {
            $this->deleteStoredFile($certificate->getAttribute($field));
        }
    }

    protected function deleteStoredFile($path): void
    {
        if (!$path) {
            return;
        }

        foreach (['public', 'local'] as $disk) {
            if (Storage::disk($disk)->exists($path)) {
                Storage::disk($disk)->delete($path);
            }
        }
    }
}
