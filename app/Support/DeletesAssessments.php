<?php

namespace App\Support;

use App\Models\Assessment;
use App\Models\AssessmentAnswer;
use App\Models\AssessmentQuestion;
use App\Models\AssessmentResult;
use App\Models\AssessmentSession;
use App\Models\Certificate;
use App\Models\CertificateVerificationLog;
use App\Models\ClassContentSession;
use App\Models\CourseEnrollment;
use App\Models\IndependentLearner;
use App\Models\LessonProgress;
use App\Models\MySpace;
use App\Models\Student;
use App\Models\StudentAchievement;
use App\Models\TeacherAchievement;
use App\Models\User;
use App\Models\UserActivityLog;
use App\Models\UserSession;
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

    protected function deleteStudentCompletely(Student $student): void
    {
        $this->deleteAssessmentResultsForStudent($student->id);

        LessonProgress::where('student_id', $student->id)->delete();

        $this->deleteTrackingRecords('Student', $student->id);

        $this->deleteCertificatesForStudent($student->id);

        StudentAchievement::where('student_id', $student->id)
            ->get()
            ->each(function (StudentAchievement $achievement) {
                $this->deleteStoredFile($achievement->certificate_file);
                $achievement->delete();
            });

        $this->deleteMySpaceForSubmitter('Student', $student->id);

        $this->deleteStoredFile($student->profile_image);

        $student->delete();
    }

    protected function deleteTeacherCompletely(User $teacher): void
    {
        Assessment::where('teacher_id', $teacher->id)
            ->get()
            ->each(function (Assessment $assessment) {
                $this->deleteAssessmentCompletely($assessment);
            });

        AssessmentSession::where('user_type', 'Teacher')
            ->where('user_id', $teacher->id)
            ->delete();

        ClassContentSession::where('stem_engineer_id', $teacher->id)->delete();

        $this->deleteTrackingRecords('Teacher', $teacher->id);

        $this->deleteMySpaceForSubmitter('Teacher', $teacher->id);

        TeacherAchievement::where('user_id', $teacher->id)
            ->get()
            ->each(function (TeacherAchievement $achievement) {
                $this->deleteStoredFile($achievement->certificate_file);
                $achievement->delete();
            });

        $this->deleteStoredFile($teacher->profile_image);

        $teacher->delete();
    }

    protected function deleteIndependentLearnerCompletely(IndependentLearner $learner): void
    {
        CourseEnrollment::where('learner_id', $learner->id)->delete();

        LessonProgress::where('independent_learner_id', $learner->id)->delete();

        $this->deleteCertificatesForIndependentLearner($learner->id);

        $learner->delete();
    }

    protected function deleteCourseDependentRecords($courseId): void
    {
        CourseEnrollment::where('course_id', $courseId)->delete();

        $this->deleteCertificatesForCourse($courseId);
    }

    protected function deleteCertificatesForStudent($studentId): void
    {
        $certificates = Certificate::where('student_id', $studentId)->get();

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

    protected function deleteCertificatesForIndependentLearner($learnerId): void
    {
        $certificates = Certificate::where('independent_learner_id', $learnerId)->get();

        $this->deleteCertificateCollection($certificates);
    }

    protected function deleteCertificatesForCourse($courseId): void
    {
        $certificates = Certificate::where('course_id', $courseId)->get();

        $this->deleteCertificateCollection($certificates);
    }

    protected function deleteCertificateCollection($certificates): void
    {
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

    protected function deleteTrackingRecords(string $userType, $userId): void
    {
        $sessionIds = UserSession::where('user_type', $userType)
            ->where('user_id', $userId)
            ->pluck('id');

        UserActivityLog::where('user_type', $userType)
            ->where('user_id', $userId)
            ->delete();

        if ($sessionIds->isNotEmpty()) {
            UserActivityLog::whereIn('user_session_id', $sessionIds)->delete();
        }

        UserSession::where('user_type', $userType)
            ->where('user_id', $userId)
            ->delete();
    }

    protected function deleteMySpaceForSubmitter(string $submitterType, $submitterId): void
    {
        MySpace::where('created_by_type', $submitterType)
            ->where('created_by_id', $submitterId)
            ->get()
            ->each(function (MySpace $item) {
                $this->deleteStoredFile($item->blueprint_pdf);
                $item->delete();
            });
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
