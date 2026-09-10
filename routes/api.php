<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MobileApiController;
use App\Http\Controllers\Api\SubmissionController;
use App\Http\Controllers\Api\StudentImportController;
use App\Http\Controllers\Api\CourseAuthoringController;
use App\Http\Controllers\CourseController;

// API Version 1 Group
Route::prefix('v1')->group(function () {

    // Public Routes
    Route::post('/auth/login', [AuthController::class, 'login']);

    // Protected Routes (Require Bearer Token)
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/auth/profile', [AuthController::class, 'profile']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        // Future student, teacher, and community routes go here...
    });
});

Route::post('/login', [MobileApiController::class, 'login']);
Route::post('/student-mfa/verify', [MobileApiController::class, 'verifyStudentMfa']);
Route::post('/mfa/verify', [MobileApiController::class, 'verifyMobileMfa']);
Route::get('/public/newsroom', [\App\Http\Controllers\Api\MobilePublicController::class, 'newsroom'])->middleware('throttle:20,1');
Route::get('/public/community', [\App\Http\Controllers\Api\MobilePublicController::class, 'community'])->middleware('throttle:20,1');
Route::post('/public/verify-certificate', [\App\Http\Controllers\Api\MobilePublicController::class, 'verifyCertificate'])->middleware('throttle:10,1');
Route::get('/session-completion-video/{fileName}', [\App\Http\Controllers\PageController::class, 'streamSessionCompletionVideo'])
    ->middleware(['signed', 'throttle:30,1'])->name('mobile.session-completion-video');
Route::get('/mobile-certificates/{certificate}/download', [MobileApiController::class, 'downloadMobileCertificate'])
    ->middleware(['signed', 'throttle:30,1'])->name('mobile.certificate-download');
Route::get('/mobile-status', \App\Http\Controllers\Api\MobileStatusController::class);
Route::get('/workflow-exports/{ticket}', [\App\Http\Controllers\Api\MobileWorkflowController::class, 'downloadExport'])
    ->middleware(['signed', 'throttle:20,1'])->name('mobile.workflow-export');
Route::post('/forgot-password', [MobileApiController::class, 'forgotPassword']);
Route::post('/hybrid/register', [MobileApiController::class, 'registerHybridLearner']);
Route::middleware('auth:sanctum')->post('/change-password', [MobileApiController::class, 'changePassword']);

Route::middleware(['auth:sanctum', \App\Http\Middleware\MobileRoleAccess::class])->group(function () {
    Route::post('/activity', [\App\Http\Controllers\Api\MobileActivityController::class, 'update'])->middleware('throttle:120,1');
    Route::get('/workflows/{area}', [\App\Http\Controllers\Api\MobileWorkflowController::class, 'index']);
    Route::get('/workflows/{area}/export', [\App\Http\Controllers\Api\MobileWorkflowController::class, 'export']);
    Route::post('/workflows/{area}/{action}/{id?}', [\App\Http\Controllers\Api\MobileWorkflowController::class, 'act'])->whereNumber('id');
    Route::get('/workflows/{area}/{id}/document', [\App\Http\Controllers\Api\MobileWorkflowController::class, 'document'])->whereNumber('id');
    Route::get('/students/import-template', [StudentImportController::class, 'template']);
    Route::post('/students/import', [StudentImportController::class, 'store']);
    Route::get('/profile', [MobileApiController::class, 'profile']);
    Route::post('/logout', [MobileApiController::class, 'logout']);
    Route::post('/feedback', [MobileApiController::class, 'submitFeedback']);
    Route::post('/push-token', [MobileApiController::class, 'storePushToken']);
    Route::delete('/push-token', [MobileApiController::class, 'deletePushToken']);
    Route::get('/mfa/status', [MobileApiController::class, 'mobileMfaStatus']);
    Route::post('/mfa/enable', [MobileApiController::class, 'beginMobileMfaSetup']);
    Route::post('/mfa/verify-enable', [MobileApiController::class, 'verifyMobileMfaSetup']);
    Route::post('/mfa/disable', [MobileApiController::class, 'disableMobileMfa']);
    Route::post('/ai-chat/ask', [MobileApiController::class, 'aiChatAsk'])->middleware('throttle:20,1');
    Route::post('/ai/report-insights', [MobileApiController::class, 'aiReportInsights']);
    Route::post('/ai/assessment-question-paper', [MobileApiController::class, 'aiAssessmentQuestionPaper']);

    Route::get('/dashboard/summary', [MobileApiController::class, 'dashboardSummary']);

    Route::get('/engineer/sessions', [MobileApiController::class, 'engineerSessions']);
    Route::get('/engineer/sessions/state', [\App\Http\Controllers\Api\MobileSessionController::class, 'engineerState']);
    Route::post('/engineer/sessions/start', [MobileApiController::class, 'startEngineerSession']);
    Route::post('/engineer/sessions/end/{sessionId}', [MobileApiController::class, 'endEngineerSession']);
    Route::get('/engineer/learning-content', [MobileApiController::class, 'learningContent']);
    Route::get('/engineer/learning-content/{contentId}/ai-prep', [MobileApiController::class, 'engineerAiPrep']);
    Route::post('/engineer/learning-content/{contentId}/ai-prep/quiz', [MobileApiController::class, 'submitEngineerAiPrep']);
    Route::get('/engineer/learning-content/{contentId}/preview', [MobileApiController::class, 'engineerContentPreview']);
    Route::get('/engineer/students', [MobileApiController::class, 'engineerStudents']);
    Route::get('/engineer/assessments', [MobileApiController::class, 'engineerAssessments']);
    Route::get('/engineer/assessment-results/{resultId}', [MobileApiController::class, 'engineerAssessmentResult']);
    Route::post('/engineer/content/{contentId}/complete', [MobileApiController::class, 'completeTeacherTopic']);
    Route::post('/admin/content/{contentId}/complete', [MobileApiController::class, 'completeTeacherTopic']);
    Route::get('/engineer/profile', [MobileApiController::class, 'engineerProfile']);
    Route::put('/engineer/profile', [MobileApiController::class, 'updateEngineerProfile']);
    Route::get('/engineer/notifications', [MobileApiController::class, 'engineerNotifications']);

    Route::get('/student/assessments', [MobileApiController::class, 'studentAssessments']);
    Route::get('/student/component-mastery', [MobileApiController::class, 'studentComponentMastery']);
    Route::post('/student/component-mastery/{componentKey}/generate', [MobileApiController::class, 'generateStudentComponentMastery']);
    Route::get('/student/assessment-results', [MobileApiController::class, 'studentAssessmentResults']);
    Route::get('/student/learning-content', [MobileApiController::class, 'learningContent']);
    Route::get('/student/content/{contentId}/preview', [MobileApiController::class, 'studentContentPreview']);
    Route::post('/student/lessons/{contentId}/complete', [MobileApiController::class, 'completeStudentLesson']);
    Route::get('/student/content/{contentId}/ai-review', [MobileApiController::class, 'studentAiReview']);
    Route::post('/student/content/{contentId}/ai-review/quiz', [MobileApiController::class, 'submitStudentAiReview']);
    Route::post('/student/assessments/{assessmentId}/start', [MobileApiController::class, 'startStudentAssessment']);
    Route::post('/student/assessment-sessions/{sessionId}/submit', [MobileApiController::class, 'submitStudentAssessment']);
    Route::get('/student/assessment-sessions/{id}', [\App\Http\Controllers\Api\MobileSessionController::class, 'assessmentState']);
    Route::post('/student/assessment-sessions/{id}/draft', [\App\Http\Controllers\Api\MobileSessionController::class, 'saveDraft']);
    Route::post('/student/assessment-sessions/{id}/violation', [\App\Http\Controllers\Api\MobileSessionController::class, 'violation']);
    Route::get('/student/profile', [MobileApiController::class, 'studentProfile']);
    Route::put('/student/profile', [MobileApiController::class, 'updateStudentProfile']);
    Route::post('/student/profile', [MobileApiController::class, 'updateStudentProfile']);
    Route::get('/{audience}/{kind}', [SubmissionController::class, 'index'])->where(['audience' => 'student|engineer', 'kind' => 'my-space|achievements']);
    Route::post('/{audience}/{kind}', [SubmissionController::class, 'save'])->where(['audience' => 'student|engineer', 'kind' => 'my-space|achievements']);
    Route::post('/{audience}/{kind}/{id}', [SubmissionController::class, 'save'])->where(['audience' => 'student|engineer', 'kind' => 'my-space|achievements', 'id' => '[0-9]+']);
    Route::delete('/{audience}/{kind}/{id}', [SubmissionController::class, 'destroy'])->where(['audience' => 'student|engineer', 'kind' => 'my-space|achievements', 'id' => '[0-9]+']);
    Route::get('/student/notifications', [MobileApiController::class, 'studentNotifications']);

    Route::get('/hybrid/courses', [MobileApiController::class, 'hybridLearnerCourses']);
    Route::post('/hybrid/courses/{courseId}/enroll', [MobileApiController::class, 'hybridLearnerEnroll']);
    Route::get('/hybrid/enrollments', [MobileApiController::class, 'hybridLearnerEnrollments']);
    Route::get('/hybrid/courses/{courseId}/lessons', [MobileApiController::class, 'hybridLearnerCourseLessons']);
    Route::post('/hybrid/lessons/{contentId}/complete', [MobileApiController::class, 'completeHybridLearnerLesson']);
    Route::get('/hybrid/lessons/{contentId}/preview', [MobileApiController::class, 'hybridLearnerLessonPreview']);
    Route::get('/hybrid/certificates', [MobileApiController::class, 'hybridLearnerCertificates']);

    Route::get('/admin/workspace', [MobileApiController::class, 'adminWorkspace']);
    Route::get('/admin/management-filters/{area}', [MobileApiController::class, 'managementFilters']);
    Route::get('/admin/reports', [MobileApiController::class, 'adminReports']);
    Route::get('/admin/reports/export', [MobileApiController::class, 'adminReportExportUrl']);
    Route::get('/manager/reports', [MobileApiController::class, 'managerReports']);
    Route::get('/principal/reports', [MobileApiController::class, 'principalReports']);
    Route::get('/panel/reports/export-url', [MobileApiController::class, 'panelReportExportUrl']);
    Route::get('/manager/approvals', [MobileApiController::class, 'adminApprovals']);
    Route::get('/panel/notifications', [MobileApiController::class, 'panelNotifications']);
    Route::post('/panel/feedback', [MobileApiController::class, 'submitPanelFeedback']);
    Route::get('/admin/monitoring', [MobileApiController::class, 'adminMonitoring']);
    Route::get('/admin/monitoring/{type}', [MobileApiController::class, 'adminMonitoringDetails']);
    Route::get('/admin/notifications', [MobileApiController::class, 'adminNotifications']);
    Route::post('/admin/notifications', [MobileApiController::class, 'storeAdminNotification']);
    Route::delete('/admin/notifications/{id}', [MobileApiController::class, 'deleteAdminNotification']);
    Route::get('/admin/approvals', [MobileApiController::class, 'adminApprovals']);
    Route::get('/admin/submissions/{audience}/{kind}', [SubmissionController::class, 'reviewIndex'])->where(['audience' => 'student|engineer', 'kind' => 'my-space|achievements']);
    Route::post('/admin/submissions/{audience}/{kind}/{id}/{decision}', [SubmissionController::class, 'decide'])->where(['audience' => 'student|engineer', 'kind' => 'my-space|achievements', 'id' => '[0-9]+']);
    Route::post('/admin/approvals/{type}/{id}/{decision}', [MobileApiController::class, 'updateAdminApproval']);
    Route::get('/admin/independent-learners', [MobileApiController::class, 'adminIndependentLearners']);
    Route::get('/admin/teachers', [MobileApiController::class, 'adminTeachers']);
    Route::get('/admin/teachers/{id}', [MobileApiController::class, 'adminTeacher']);
    Route::post('/admin/teachers', [MobileApiController::class, 'storeAdminTeacher']);
    Route::put('/admin/teachers/{id}', [MobileApiController::class, 'updateAdminTeacher']);
    Route::delete('/admin/teachers/{id}', [MobileApiController::class, 'deleteAdminTeacher']);
    Route::get('/admin/students', [MobileApiController::class, 'adminStudents']);
    Route::get('/admin/students/{id}', [MobileApiController::class, 'adminStudent']);
    Route::post('/admin/students', [MobileApiController::class, 'storeAdminStudent']);
    Route::put('/admin/students/{id}', [MobileApiController::class, 'updateAdminStudent']);
    Route::delete('/admin/students/{id}', [MobileApiController::class, 'deleteAdminStudent']);
    Route::get('/admin/institutes', [MobileApiController::class, 'adminInstitutes']);
    Route::get('/admin/institutes/{id}', [MobileApiController::class, 'adminInstitute']);
    Route::post('/admin/institutes', [MobileApiController::class, 'storeAdminInstitute']);
    Route::put('/admin/institutes/{id}', [MobileApiController::class, 'updateAdminInstitute']);
    Route::delete('/admin/institutes/{id}', [MobileApiController::class, 'deleteAdminInstitute']);
    Route::get('/admin/principals', [MobileApiController::class, 'adminPrincipals']);
    Route::get('/admin/principals/{id}', [MobileApiController::class, 'adminPrincipal']);
    Route::post('/admin/principals', [MobileApiController::class, 'storeAdminPrincipal']);
    Route::put('/admin/principals/{id}', [MobileApiController::class, 'updateAdminPrincipal']);
    Route::delete('/admin/principals/{id}', [MobileApiController::class, 'deleteAdminPrincipal']);
    Route::get('/admin/classes', [MobileApiController::class, 'adminClasses']);
    Route::get('/admin/classes/{id}', [MobileApiController::class, 'adminClass']);
    Route::post('/admin/classes', [MobileApiController::class, 'storeAdminClass']);
    Route::put('/admin/classes/{id}', [MobileApiController::class, 'updateAdminClass']);
    Route::delete('/admin/classes/{id}', [MobileApiController::class, 'deleteAdminClass']);
    Route::get('/admin/courses', [MobileApiController::class, 'adminCourses']);
    Route::get('/admin/courses/{id}/lessons', [CourseAuthoringController::class, 'index']);
    Route::post('/admin/courses/{id}/lessons', [CourseAuthoringController::class, 'save']);
    Route::post('/admin/courses/{id}/lessons/{lessonId}', [CourseAuthoringController::class, 'save']);
    Route::delete('/admin/courses/{id}/lessons/{courseContent}', [CourseController::class, 'detachContent']);
    Route::get('/admin/courses/{id}/lessons/{lessonId}/preview/{audience}', [CourseAuthoringController::class, 'preview']);
    Route::get('/admin/courses/{id}', [MobileApiController::class, 'adminCourse']);
    Route::post('/admin/courses', [MobileApiController::class, 'storeAdminCourse']);
    Route::put('/admin/courses/{id}', [MobileApiController::class, 'updateAdminCourse']);
    Route::delete('/admin/courses/{id}', [MobileApiController::class, 'deleteAdminCourse']);
    Route::get('/admin/teaching-plans', [MobileApiController::class, 'adminTeachingPlans']);
    Route::get('/admin/teaching-plans/{id}', [MobileApiController::class, 'adminTeachingPlan']);
    Route::put('/admin/teaching-plans/{id}', [MobileApiController::class, 'updateAdminTeachingPlan']);
    Route::post('/admin/teaching-plans/{id}/release-next', [MobileApiController::class, 'releaseNextAdminTeachingPlan']);
    Route::put('/admin/teaching-plans/{id}/weeks/{weekId}', [MobileApiController::class, 'updateAdminTeachingPlanWeek']);
});

// A short-lived signed preview link lets the mobile OS render a protected PDF
// without exposing a Sanctum token to the browser or a third-party viewer.
Route::get('/mobile-preview/engineer/{contentId}', [MobileApiController::class, 'serveEngineerContentPreview'])
    ->middleware('signed')
    ->name('mobile.engineer.content-preview');

Route::get('/mobile-preview/student/{studentId}/{contentId}', [MobileApiController::class, 'serveStudentContentPreview'])
    ->middleware('signed')
    ->name('mobile.student.content-preview');

Route::get('/mobile-preview/hybrid/{learnerId}/{contentId}', [MobileApiController::class, 'serveHybridLearnerLessonPreview'])
    ->middleware('signed')
    ->name('mobile.hybrid.content-preview');

Route::get('/mobile-reports/{accountId}/{reportMode}', [MobileApiController::class, 'serveAdminReportExport'])
    ->middleware('signed')
    ->name('mobile.admin.report-export');

Route::get('/submission-file/{kind}/{id}', [SubmissionController::class, 'file'])
    ->middleware('signed')->name('mobile.submission-file');

Route::get('/authoring-file/{accountId}/{lessonId}/{audience}', [CourseAuthoringController::class, 'file'])
    ->middleware('signed')->name('mobile.authoring-preview');

Route::get('/student-import-template/{accountId}', [StudentImportController::class, 'download'])
    ->middleware('signed')->name('mobile.student-import-template');
