<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ClassController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\InstituteController;
use App\Http\Controllers\ContentController;
use App\Http\Controllers\AssessmentController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\AssessmentResultController;
use App\Http\Controllers\StudentAchievementController;
use App\Http\Controllers\LessonProgressController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\MySpaceController;
use App\Http\Controllers\TeacherStudentProfileController;
use App\Http\Controllers\IndependentLearnerController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\CommunityFeedController;
use App\Http\Controllers\AiChatController;
use App\Http\Controllers\LmsNotificationController;
use App\Http\Controllers\NewsroomController;
use App\Http\Controllers\PrincipalController;
use Illuminate\Support\Facades\Mail;
use App\Http\Controllers\TeachingPlanController;


//Public Routes
Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/portal', [PageController::class, 'portal'])->name('portal');
Route::get('/logout', [UserController::class, 'logout'])->name('logout');
Route::get('/password-change/confirm/{token}', [UserController::class, 'confirmPasswordChange'])->name('password-change.confirm');
Route::post('/password-change/reset/{token}', [UserController::class, 'submitPasswordReset'])->name('password-change.reset.submit');
Route::get('/verify-certificate', [PageController::class, 'verifyCertificate'])->name('certificate.verify');
Route::post('/verify-certificate', [PageController::class, 'verifyCertificateSubmit'])->name('certificate.verify.submit');
Route::get('/content-preview/{content}/for/{audience}', [ContentController::class, 'showPreview'])->middleware('track.activity')->name('content.preview');
Route::get('/content-preview-stream/{content}/for/{audience}', [ContentController::class, 'streamPreview'])->name('content.preview.stream');
Route::get('/content-files/{content}/for/{audience}/{variant?}', [ContentController::class, 'showFile'])->name('content.file.audience');
Route::get('/content-files/{content}/{variant?}', [ContentController::class, 'showFile'])->name('content.file');
Route::get('/assessment-paper/{assessment}/{variant?}', [AssessmentController::class, 'showQuestionPaper'])->name('assessment.paper');
Route::get('/assessment-answer-file/{result}', [AssessmentResultController::class, 'showAnswerFile'])->name('assessment.answer.file');
Route::post('/ai-chat/ask', [AiChatController::class, 'ask'])->middleware('throttle:20,1')->name('ai-chat.ask');
Route::post('/activity-monitoring/end-current', [PageController::class, 'finishCurrentActivityLog'])->name('activity-monitoring.end-current');
Route::get('/newsroom', [NewsroomController::class, 'index'])->name('newsroom');
Route::get('/blogs/login', [CommunityFeedController::class, 'blogsLogin'])->name('blogs.login');
Route::post('/blogs/login', [CommunityFeedController::class, 'blogsLoginSubmit'])->name('blogs.login.submit');
Route::get('/blogs', [CommunityFeedController::class, 'blogsEntry'])->name('blogs.community-feed');
Route::post('/blogs', [CommunityFeedController::class, 'store'])->name('blogs.community-feed.store');
Route::post('/blogs/{id}/like', [CommunityFeedController::class, 'toggleLike'])->name('blogs.community-feed.like');
Route::post('/blogs/{id}/comments', [CommunityFeedController::class, 'storeComment'])->name('blogs.community-feed.comments.store');
Route::post('/blogs/comments/{id}/update', [CommunityFeedController::class, 'updateComment'])->name('blogs.community-feed.comments.update');
Route::post('/blogs/comments/{id}/delete', [CommunityFeedController::class, 'deleteComment'])->name('blogs.community-feed.comments.delete');
Route::post('/blogs/{id}/approve', [CommunityFeedController::class, 'approve'])->name('blogs.community-feed.approve');
Route::post('/blogs/{id}/reject', [CommunityFeedController::class, 'reject'])->name('blogs.community-feed.reject');
Route::post('/blogs/{id}/delete', [CommunityFeedController::class, 'delete'])->name('blogs.community-feed.delete');


//Hybrid-Learners Public Routes
Route::view('/coming-soon', 'coming-soon')->name('coming.soon');
Route::get('/independent/register', [IndependentLearnerController::class, 'register'])->name('independent.register');
Route::post('/independent/register', [IndependentLearnerController::class, 'registerSubmit'])->name('independent.register.submit');
Route::get('/independent/login', [IndependentLearnerController::class, 'login'])->name('independent.login');
Route::post('/independent/login', [IndependentLearnerController::class, 'loginSubmit'])->name('independent.login.submit');
Route::get('/independent/courses', [IndependentLearnerController::class, 'courses'])->name('independent.courses');
Route::get('/independent/courses/{id}', [IndependentLearnerController::class, 'courseDetails'])->name('independent.courses.show');

//Hybrid-Learners
Route::middleware(['independent.auth'])->group(function () {
    Route::get('/independent-dashboard', [IndependentLearnerController::class, 'dashboard'])->name('independent.dashboard');
    Route::post('/independent/courses/{id}/enroll', [IndependentLearnerController::class, 'enroll'])->name('independent.courses.enroll');
    Route::get('/independent/my-enrollments', [IndependentLearnerController::class, 'myEnrollments'])->name('independent.enrollments');
    Route::get('/independent/courses/{id}/learn', [IndependentLearnerController::class, 'learnCourse'])->name('independent.courses.learn');
    Route::post('/independent/lesson/{contentId}/complete', [IndependentLearnerController::class, 'markLessonComplete'])->name('independent.lesson.complete');
    Route::get('/independent/certificates', [IndependentLearnerController::class, 'certificates'])->name('independent.certificates');
});


//Admin + InstituteAdmin Shared Public Routes
Route::get('/admin-login', [PageController::class, 'adminLogin'])->name('admin.login');
Route::post('/admin-login', [UserController::class, 'adminLogin'])->name('admin.login.submit');
Route::get('/mfa/verify', [UserController::class, 'showMfaVerify'])->name('mfa.verify');
Route::post('/mfa/verify', [UserController::class, 'verifyMfa'])->name('mfa.verify.submit');
Route::post('/mfa/resend', [UserController::class, 'resendMfa'])->name('mfa.resend');
Route::get('/admin/forgot-password', [UserController::class, 'forgotPassword'])->defaults('role', 'admin')->name('admin.forgot.password');
Route::post('/admin/forgot-password', [UserController::class, 'forgotPasswordSubmit'])->defaults('role', 'admin')->name('admin.forgot.password.submit');

//Admin + InstituteAdmin Shared Routes
Route::middleware(['admin.auth', 'track.activity'])->group(function () {

    Route::get('/admin-dashboard', [PageController::class, 'adminDashboard'])->name('admin.dashboard');

    Route::get('/admin/change-password', [UserController::class, 'changePassword'])->name('admin.change.password');
    Route::post('/admin/change-password', [UserController::class, 'changePasswordSubmit'])->name('admin.change.password.submit');
    Route::get('/admin/two-factor', [UserController::class, 'mfaSettings'])->name('admin.mfa.settings');
    Route::post('/admin/two-factor/enable', [UserController::class, 'beginMfaSetup'])->name('admin.mfa.enable');
    Route::post('/admin/two-factor/verify', [UserController::class, 'confirmMfaSetup'])->name('admin.mfa.verify');
    Route::post('/admin/two-factor/disable', [UserController::class, 'disableMfa'])->name('admin.mfa.disable');

    Route::get('/admin/management', [PageController::class, 'adminManagementHub'])->name('admin.management');
    Route::get('/admin/reports', [PageController::class, 'adminReportsHub'])->name('admin.reports.hub');
    Route::get('/admin/approvals', [PageController::class, 'adminApprovalsHub'])->name('admin.approvals');
    Route::get('/admin/monitoring', [PageController::class, 'adminMonitoringHub'])->name('admin.monitoring');

    Route::get('/courses', [CourseController::class, 'index'])->name('courses');
    Route::post('/courses/store', [CourseController::class, 'store'])->name('courses.store');
    Route::post('/courses/update/{id}',[CourseController::class, 'update'])->name('courses.update');
    Route::post('/courses/{id}/upload-content', [CourseController::class, 'uploadCourseContent'])->name('courses.upload-content');
    Route::post('/courses/{id}/contents/{courseContent}/order', [CourseController::class, 'updateContentOrder'])->name('courses.contents.order');
    Route::post('/courses/{id}/contents/{courseContent}/detach', [CourseController::class, 'detachContent'])->name('courses.contents.detach');
    Route::get('/courses/delete/{id}', [CourseController::class, 'delete'])->name('courses.delete');

    Route::get('/students', [PageController::class, 'students'])->name('students');
    Route::post('/students/store', [PageController::class, 'storeStudent'])->name('students.store');
    Route::post('/students/bulk-upload', [PageController::class, 'bulkUploadStudents'])->name('students.bulk-upload');
    Route::get('/students/bulk-template', [PageController::class, 'downloadStudentBulkTemplate'])->name('students.bulk-template');
    Route::post('/students/update/{id}', [PageController::class, 'updateStudent'])->name('students.update');
    Route::get('/students/delete/{id}', [PageController::class, 'deleteStudent'])->name('students.delete');

    Route::get('/classes', [ClassController::class, 'index'])->name('classes');
    Route::post('/classes/store', [ClassController::class, 'store'])->name('classes.store');
    Route::post('/classes/update/{id}', [ClassController::class, 'update'])->name('classes.update');
    Route::get('/classes/delete/{id}', [ClassController::class, 'delete'])->name('classes.delete');

    Route::get('/users', [UserController::class, 'index'])->name('users');
    Route::post('/users/store', [UserController::class, 'store'])->name('users.store');
    Route::post('/users/update/{id}', [UserController::class, 'update'])->name('users.update');
    Route::get('/users/delete/{id}', [UserController::class, 'delete'])->name('users.delete');

    Route::get('/content', function () {
        return redirect()->route('courses');
    })->name('content');
    Route::post('/content/bulk-store', [ContentController::class, 'bulkStore'])->name('content.bulk-store');
    Route::post('/content/course-content/{courseContent}/order', [ContentController::class, 'updateCourseContentOrder'])->name('content.course-content.order');
    Route::post('/content/course-content/{courseContent}/detach', [ContentController::class, 'detachCourseContent'])->name('content.course-content.detach');
    Route::post('/content/update/{id}', [ContentController::class, 'update'])->name('content.update');
    Route::get('/content/delete/{id}', [ContentController::class, 'delete'])->name('content.delete');

    Route::get('/reports/student-ai-review', [ReportController::class, 'index'])
        ->defaults('reportMode', 'student-ai-review')
        ->name('reports.student-ai-review');
    Route::get('/reports/student-ai-review/download', [ReportController::class, 'downloadPdf'])
        ->defaults('reportMode', 'student-ai-review')
        ->name('reports.student-ai-review.download.get');
    Route::post('/reports/student-ai-review/download', [ReportController::class, 'downloadPdf'])
        ->defaults('reportMode', 'student-ai-review')
        ->name('reports.student-ai-review.download');
    Route::get('/reports/stem-engineer-prep', [ReportController::class, 'index'])
        ->defaults('reportMode', 'stem-engineer-prep')
        ->name('reports.stem-engineer-prep');
    Route::get('/reports/stem-engineer-prep/download', [ReportController::class, 'downloadPdf'])
        ->defaults('reportMode', 'stem-engineer-prep')
        ->name('reports.stem-engineer-prep.download.get');
    Route::post('/reports/stem-engineer-prep/download', [ReportController::class, 'downloadPdf'])
        ->defaults('reportMode', 'stem-engineer-prep')
        ->name('reports.stem-engineer-prep.download');
    Route::get('/reports/student-performance/daily', [ReportController::class, 'index'])
        ->defaults('reportMode', 'daily-student-performance')
        ->name('reports.student-performance.daily');
    Route::get('/reports/student-performance/daily/download', [ReportController::class, 'downloadPdf'])
        ->defaults('reportMode', 'daily-student-performance')
        ->name('reports.student-performance.daily.download.get');
    Route::post('/reports/student-performance/daily/download', [ReportController::class, 'downloadPdf'])
        ->defaults('reportMode', 'daily-student-performance')
        ->name('reports.student-performance.daily.download');
    Route::get('/reports/student-performance/weekly', [ReportController::class, 'index'])
        ->defaults('reportMode', 'weekly-student-performance')
        ->name('reports.student-performance.weekly');
    Route::get('/reports/student-performance/weekly/download', [ReportController::class, 'downloadPdf'])
        ->defaults('reportMode', 'weekly-student-performance')
        ->name('reports.student-performance.weekly.download.get');
    Route::post('/reports/student-performance/weekly/download', [ReportController::class, 'downloadPdf'])
        ->defaults('reportMode', 'weekly-student-performance')
        ->name('reports.student-performance.weekly.download');
    Route::get('/reports/student-performance/monthly', [ReportController::class, 'index'])
        ->defaults('reportMode', 'monthly-student-performance')
        ->name('reports.student-performance.monthly');
    Route::get('/reports/student-performance/monthly/download', [ReportController::class, 'downloadPdf'])
        ->defaults('reportMode', 'monthly-student-performance')
        ->name('reports.student-performance.monthly.download.get');
    Route::post('/reports/student-performance/monthly/download', [ReportController::class, 'downloadPdf'])
        ->defaults('reportMode', 'monthly-student-performance')
        ->name('reports.student-performance.monthly.download');
    Route::get('/reports/stem-engineer-performance/weekly', [ReportController::class, 'index'])
        ->defaults('reportMode', 'weekly-stem-engineer-performance')
        ->name('reports.stem-engineer-performance.weekly');
    Route::get('/reports/stem-engineer-performance/weekly/download', [ReportController::class, 'downloadPdf'])
        ->defaults('reportMode', 'weekly-stem-engineer-performance')
        ->name('reports.stem-engineer-performance.weekly.download.get');
    Route::post('/reports/stem-engineer-performance/weekly/download', [ReportController::class, 'downloadPdf'])
        ->defaults('reportMode', 'weekly-stem-engineer-performance')
        ->name('reports.stem-engineer-performance.weekly.download');
    Route::get('/reports/stem-engineer-performance/monthly', [ReportController::class, 'index'])
        ->defaults('reportMode', 'monthly-stem-engineer-performance')
        ->name('reports.stem-engineer-performance.monthly');
    Route::get('/reports/stem-engineer-performance/monthly/download', [ReportController::class, 'downloadPdf'])
        ->defaults('reportMode', 'monthly-stem-engineer-performance')
        ->name('reports.stem-engineer-performance.monthly.download.get');
    Route::post('/reports/stem-engineer-performance/monthly/download', [ReportController::class, 'downloadPdf'])
        ->defaults('reportMode', 'monthly-stem-engineer-performance')
        ->name('reports.stem-engineer-performance.monthly.download');

    Route::get('/admin/certificates', [PageController::class, 'adminCertificates'])->name('admin.certificates');
    Route::post('/admin/certificates/approve/{id}', [PageController::class, 'approveCertificate'])->name('admin.certificates.approve');
    Route::post('/admin/certificates/reject/{id}', [PageController::class, 'rejectCertificate'])->name('admin.certificates.reject');
    Route::post('/admin/certificates/revoke/{id}', [PageController::class, 'revokeCertificate'])->name('admin.certificates.revoke');
    Route::post('/admin/certificates/reissue/{id}', [PageController::class, 'reissueCertificate'])->name('admin.certificates.reissue');

    Route::get('/admin/student-achievements', [StudentAchievementController::class, 'adminIndex'])->name('admin.achievements');
    Route::post('/admin/achievements/{id}/approve', [StudentAchievementController::class, 'approve'])->name('admin.achievements.approve');
    Route::post('/admin/achievements/{id}/reject', [StudentAchievementController::class, 'reject'])->name('admin.achievements.reject');
    Route::get('/admin/teacher-achievements', [PageController::class, 'adminTeacherAchievements'])->name('admin.teacher-achievements');
    Route::post('/admin/teacher-achievements/{id}/approve', [PageController::class, 'approveTeacherAchievement'])->name('admin.teacher-achievements.approve');
    Route::post('/admin/teacher-achievements/{id}/reject', [PageController::class, 'rejectTeacherAchievement'])->name('admin.teacher-achievements.reject');

    Route::get('/admin/my-space', [MySpaceController::class, 'adminIndex'])->name('admin.my-space');
    Route::get('/admin/my-space/stem-engineers', [MySpaceController::class, 'adminIndex'])
        ->defaults('submitterType', 'Teacher')
        ->name('admin.my-space.teachers');
    Route::get('/admin/my-space/students', [MySpaceController::class, 'adminIndex'])
        ->defaults('submitterType', 'Student')
        ->name('admin.my-space.students');
    Route::post('/admin/my-space/{id}/approve', [MySpaceController::class, 'approve'])->name('admin.my-space.approve');
    Route::post('/admin/my-space/{id}/reject', [MySpaceController::class, 'reject'])->name('admin.my-space.reject');
    Route::post('/admin/my-space/{id}/feature', [MySpaceController::class, 'feature'])->name('admin.my-space.feature');
    Route::get('/admin/my-space/{id}', [MySpaceController::class, 'show'])->name('admin.my-space.show');

    Route::get('/admin/assessment-monitoring', [PageController::class, 'assessmentMonitoring'])->name('admin.assessment.monitoring');

    Route::get('/admin/class-session-report', [PageController::class, 'classSessionReport'])->name('admin.class-session.report');
    Route::get('/admin/class-session-report/daily', [PageController::class, 'classSessionReport'])
        ->defaults('reportType', 'daily')
        ->name('admin.class-session.report.daily');
    Route::get('/admin/class-session-report/daily/download', [PageController::class, 'downloadClassSessionReportPdf'])
        ->defaults('reportType', 'daily')
        ->name('admin.class-session.report.daily.download.get');
    Route::post('/admin/class-session-report/daily/download', [PageController::class, 'downloadClassSessionReportPdf'])
        ->defaults('reportType', 'daily')
        ->name('admin.class-session.report.daily.download');
    Route::get('/admin/class-session-report/weekly', [PageController::class, 'classSessionReport'])
        ->defaults('reportType', 'weekly')
        ->name('admin.class-session.report.weekly');
    Route::get('/admin/class-session-report/weekly/download', [PageController::class, 'downloadClassSessionReportPdf'])
        ->defaults('reportType', 'weekly')
        ->name('admin.class-session.report.weekly.download.get');
    Route::post('/admin/class-session-report/weekly/download', [PageController::class, 'downloadClassSessionReportPdf'])
        ->defaults('reportType', 'weekly')
        ->name('admin.class-session.report.weekly.download');
    Route::get('/admin/class-session-report/monthly', [PageController::class, 'classSessionReport'])
        ->defaults('reportType', 'monthly')
        ->name('admin.class-session.report.monthly');
    Route::get('/admin/class-session-report/monthly/download', [PageController::class, 'downloadClassSessionReportPdf'])
        ->defaults('reportType', 'monthly')
        ->name('admin.class-session.report.monthly.download.get');
    Route::post('/admin/class-session-report/monthly/download', [PageController::class, 'downloadClassSessionReportPdf'])
        ->defaults('reportType', 'monthly')
        ->name('admin.class-session.report.monthly.download');

    Route::get('/notifications', [LmsNotificationController::class, 'index'])->name('notifications');
    Route::post('/notifications/store', [LmsNotificationController::class, 'store'])->name('notifications.store');
    Route::post('/notifications/delete/{id}', [LmsNotificationController::class, 'delete'])->name('notifications.delete');

    Route::get('/teaching-plans', [TeachingPlanController::class, 'index'])->name('teaching-plans');
    Route::post('/teaching-plans/store', [TeachingPlanController::class, 'store'])->name('teaching-plans.store');
    Route::post('/teaching-plan-templates/store', [TeachingPlanController::class, 'storeTemplate'])->name('teaching-plan-templates.store');
    Route::post('/teaching-plan-templates/deploy', [TeachingPlanController::class, 'deployTemplates'])->name('teaching-plan-templates.deploy');
    Route::post('/teaching-plans/update/{id}', [TeachingPlanController::class, 'update'])->name('teaching-plans.update');
    Route::post('/teaching-plans/{id}/ai-training', [TeachingPlanController::class, 'deployAiTraining'])->name('teaching-plans.ai-training.deploy');
    Route::post('/teaching-plans/{id}/release-next', [TeachingPlanController::class, 'releaseNext'])->name('teaching-plans.release-next');
    Route::post('/teaching-plans/{id}/lagged-content', [TeachingPlanController::class, 'storeLaggedContent'])->name('teaching-plans.lagged-content.store');
    Route::post('/teaching-plans/{id}/weeks/{week}', [TeachingPlanController::class, 'updateWeek'])->name('teaching-plans.weeks.update');
    Route::post('/teaching-plans/run-release-check', [TeachingPlanController::class, 'runReleaseCheck'])->name('teaching-plans.run-release-check');
    Route::get('/teaching-plans/delete/{id}', [TeachingPlanController::class, 'delete'])->name('teaching-plans.delete');

    Route::get('/admin/assessment-review-monitoring',[PageController::class, 'assessmentReviewMonitoring'])->name('admin.assessment.review.monitoring');

    Route::get('/admin/question-papers', [AssessmentController::class, 'adminQuestionPapers'])->name('admin.question-papers');
    Route::post('/admin/question-papers/{id}/approve', [AssessmentController::class, 'approveQuestionPaper'])->name('admin.question-papers.approve');
    Route::post('/admin/question-papers/{id}/reject', [AssessmentController::class, 'rejectQuestionPaper'])->name('admin.question-papers.reject');
    Route::get('/admin/assessment-review', [AssessmentResultController::class, 'reviewResults'])->name('admin.assessment.review');
    Route::post('/admin/assessment-review/{id}', [AssessmentResultController::class, 'reviewAnswer'])->name('admin.assessment.review.submit');
    Route::post('/admin/topic-complete/{contentId}', [UserController::class, 'markTopicComplete'])->name('admin.complete-topic');
});

//Manager Routes
Route::middleware(['manager.auth', 'track.activity'])->group(function () {
    Route::get('/manager-dashboard', [PageController::class, 'managerDashboard'])->name('manager.dashboard');
    Route::get('/manager/reports', [PageController::class, 'managerReportsHub'])->name('manager.reports.hub');
    Route::get('/manager/feedback', [FeedbackController::class, 'panelCreate'])->defaults('audience', 'manager')->name('manager.feedback');
    Route::post('/manager/feedback', [FeedbackController::class, 'panelStore'])->defaults('audience', 'manager')->name('manager.feedback.store');

    Route::get('/manager/class-session-report/daily', [PageController::class, 'classSessionReport'])
        ->defaults('reportType', 'daily')
        ->name('manager.class-session.report.daily');
    Route::post('/manager/class-session-report/daily/download', [PageController::class, 'downloadClassSessionReportPdf'])
        ->defaults('reportType', 'daily')
        ->name('manager.class-session.report.daily.download');
    Route::get('/manager/class-session-report/weekly', [PageController::class, 'classSessionReport'])
        ->defaults('reportType', 'weekly')
        ->name('manager.class-session.report.weekly');
    Route::post('/manager/class-session-report/weekly/download', [PageController::class, 'downloadClassSessionReportPdf'])
        ->defaults('reportType', 'weekly')
        ->name('manager.class-session.report.weekly.download');
    Route::get('/manager/class-session-report/monthly', [PageController::class, 'classSessionReport'])
        ->defaults('reportType', 'monthly')
        ->name('manager.class-session.report.monthly');
    Route::post('/manager/class-session-report/monthly/download', [PageController::class, 'downloadClassSessionReportPdf'])
        ->defaults('reportType', 'monthly')
        ->name('manager.class-session.report.monthly.download');

    Route::get('/manager/reports/student-performance/daily', [ReportController::class, 'index'])
        ->defaults('reportMode', 'daily-student-performance')
        ->name('manager.reports.student-performance.daily');
    Route::post('/manager/reports/student-performance/daily/download', [ReportController::class, 'downloadPdf'])
        ->defaults('reportMode', 'daily-student-performance')
        ->name('manager.reports.student-performance.daily.download');
    Route::get('/manager/reports/student-performance/weekly', [ReportController::class, 'index'])
        ->defaults('reportMode', 'weekly-student-performance')
        ->name('manager.reports.student-performance.weekly');
    Route::post('/manager/reports/student-performance/weekly/download', [ReportController::class, 'downloadPdf'])
        ->defaults('reportMode', 'weekly-student-performance')
        ->name('manager.reports.student-performance.weekly.download');
    Route::get('/manager/reports/student-performance/monthly', [ReportController::class, 'index'])
        ->defaults('reportMode', 'monthly-student-performance')
        ->name('manager.reports.student-performance.monthly');
    Route::post('/manager/reports/student-performance/monthly/download', [ReportController::class, 'downloadPdf'])
        ->defaults('reportMode', 'monthly-student-performance')
        ->name('manager.reports.student-performance.monthly.download');

    Route::get('/manager/reports/stem-engineer-performance/weekly', [ReportController::class, 'index'])
        ->defaults('reportMode', 'weekly-stem-engineer-performance')
        ->name('manager.reports.stem-engineer-performance.weekly');
    Route::post('/manager/reports/stem-engineer-performance/weekly/download', [ReportController::class, 'downloadPdf'])
        ->defaults('reportMode', 'weekly-stem-engineer-performance')
        ->name('manager.reports.stem-engineer-performance.weekly.download');
    Route::get('/manager/reports/stem-engineer-performance/monthly', [ReportController::class, 'index'])
        ->defaults('reportMode', 'monthly-stem-engineer-performance')
        ->name('manager.reports.stem-engineer-performance.monthly');
    Route::post('/manager/reports/stem-engineer-performance/monthly/download', [ReportController::class, 'downloadPdf'])
        ->defaults('reportMode', 'monthly-stem-engineer-performance')
        ->name('manager.reports.stem-engineer-performance.monthly.download');
});

//Principal Routes
Route::middleware(['principal.auth', 'track.activity'])->group(function () {
    Route::get('/principal-dashboard', [PageController::class, 'principalDashboard'])->name('principal.dashboard');
    Route::get('/principal/reports', [PageController::class, 'principalReportsHub'])->name('principal.reports.hub');
    Route::get('/principal/change-password', [UserController::class, 'changePassword'])->name('principal.change.password');
    Route::post('/principal/change-password', [UserController::class, 'changePasswordSubmit'])->name('principal.change.password.submit');
    Route::get('/principal/two-factor', [UserController::class, 'mfaSettings'])->name('principal.mfa.settings');
    Route::post('/principal/two-factor/enable', [UserController::class, 'beginMfaSetup'])->name('principal.mfa.enable');
    Route::post('/principal/two-factor/verify', [UserController::class, 'confirmMfaSetup'])->name('principal.mfa.verify');
    Route::post('/principal/two-factor/disable', [UserController::class, 'disableMfa'])->name('principal.mfa.disable');
    Route::get('/principal/feedback', [FeedbackController::class, 'panelCreate'])->defaults('audience', 'principal')->name('principal.feedback');
    Route::post('/principal/feedback', [FeedbackController::class, 'panelStore'])->defaults('audience', 'principal')->name('principal.feedback.store');

    Route::get('/principal/class-session-report/daily', [PageController::class, 'classSessionReport'])
        ->defaults('reportType', 'daily')
        ->name('principal.class-session.report.daily');
    Route::post('/principal/class-session-report/daily/download', [PageController::class, 'downloadClassSessionReportPdf'])
        ->defaults('reportType', 'daily')
        ->name('principal.class-session.report.daily.download');
    Route::get('/principal/class-session-report/weekly', [PageController::class, 'classSessionReport'])
        ->defaults('reportType', 'weekly')
        ->name('principal.class-session.report.weekly');
    Route::post('/principal/class-session-report/weekly/download', [PageController::class, 'downloadClassSessionReportPdf'])
        ->defaults('reportType', 'weekly')
        ->name('principal.class-session.report.weekly.download');
    Route::get('/principal/class-session-report/monthly', [PageController::class, 'classSessionReport'])
        ->defaults('reportType', 'monthly')
        ->name('principal.class-session.report.monthly');
    Route::post('/principal/class-session-report/monthly/download', [PageController::class, 'downloadClassSessionReportPdf'])
        ->defaults('reportType', 'monthly')
        ->name('principal.class-session.report.monthly.download');

    Route::get('/principal/reports/student-performance/daily', [ReportController::class, 'index'])
        ->defaults('reportMode', 'daily-student-performance')
        ->name('principal.reports.student-performance.daily');
    Route::post('/principal/reports/student-performance/daily/download', [ReportController::class, 'downloadPdf'])
        ->defaults('reportMode', 'daily-student-performance')
        ->name('principal.reports.student-performance.daily.download');
    Route::get('/principal/reports/student-performance/weekly', [ReportController::class, 'index'])
        ->defaults('reportMode', 'weekly-student-performance')
        ->name('principal.reports.student-performance.weekly');
    Route::post('/principal/reports/student-performance/weekly/download', [ReportController::class, 'downloadPdf'])
        ->defaults('reportMode', 'weekly-student-performance')
        ->name('principal.reports.student-performance.weekly.download');
    Route::get('/principal/reports/student-performance/monthly', [ReportController::class, 'index'])
        ->defaults('reportMode', 'monthly-student-performance')
        ->name('principal.reports.student-performance.monthly');
    Route::post('/principal/reports/student-performance/monthly/download', [ReportController::class, 'downloadPdf'])
        ->defaults('reportMode', 'monthly-student-performance')
        ->name('principal.reports.student-performance.monthly.download');
});

//Super Admin Only Routes
Route::middleware(['admin.auth', 'super.admin'])->group(function () {

    Route::get('/institutes', [InstituteController::class, 'index'])->name('institutes');
    Route::post('/institutes/store', [InstituteController::class, 'store'])->name('institutes.store');
    Route::post('/institutes/update/{id}', [InstituteController::class, 'update'])->name('institutes.update');
    Route::get('/institutes/delete/{id}', [InstituteController::class, 'delete'])->name('institutes.delete');

    Route::get('/admin/independent-learners', [IndependentLearnerController::class, 'adminIndex'])->name('admin.independent.learners');
    Route::post('/admin/independent-learners/{id}/toggle-status', [IndependentLearnerController::class, 'toggleStatus'])->name('admin.independent.learners.toggle-status');
    Route::get('/admin/independent-learners/{id}', [IndependentLearnerController::class, 'showLearner'])->name('admin.independent.learners.show');

    Route::get('/admin/activity-monitoring', [PageController::class, 'activityMonitoring'])->name('admin.activity.monitoring');

    Route::get('/principals', [PrincipalController::class, 'index'])->name('principals');
    Route::post('/principals/store', [PrincipalController::class, 'store'])->name('principals.store');
    Route::post('/principals/update/{id}', [PrincipalController::class, 'update'])->name('principals.update');
    Route::get('/principals/delete/{id}', [PrincipalController::class, 'delete'])->name('principals.delete');

});


// Teacher public routes
Route::get('/teacher-login', [PageController::class, 'teacherLogin'])->name('teacher.login');
Route::post('/teacher-login', [UserController::class, 'teacherLogin'])->name('teacher.login.submit');
Route::get('/teacher/forgot-password', [UserController::class, 'forgotPassword'])->defaults('role', 'teacher')->name('teacher.forgot.password');
Route::post('/teacher/forgot-password', [UserController::class, 'forgotPasswordSubmit'])->defaults('role', 'teacher')->name('teacher.forgot.password.submit');

// Teacher protected routes
Route::middleware(['teacher.auth','track.activity'])->group(function () {

    Route::get('/teacher-dashboard', [PageController::class, 'teacherDashboard'])->name('teacher.dashboard');
    Route::get('/teacher/sessions', [PageController::class, 'teacherSessionsHub'])->name('teacher.sessions');
    Route::get('/teacher/assessments-hub', [PageController::class, 'teacherAssessmentsHub'])->name('teacher.assessments.hub');
    Route::get('/teacher/students', [PageController::class, 'teacherStudentsHub'])->name('teacher.students.hub');

    Route::get('/teacher/my-classes', [PageController::class, 'teacherClasses'])->name('teacher.classes');
    Route::get('/teacher/pending-sessions', [PageController::class, 'teacherPendingSessions'])->name('teacher.pending-sessions');
    Route::get('/teacher/session-completion-video/{fileName}', [PageController::class, 'streamSessionCompletionVideo'])
        ->where('fileName', '[A-Za-z0-9._ -]+')
        ->name('teacher.session-completion-video');

    Route::get('/teacher/content', [PageController::class, 'teacherContent'])->name('teacher.content');
    Route::get('/teacher/content/{id}/ai-prep', [PageController::class, 'teacherAiPrep'])->name('teacher.ai-prep');
    Route::get('/teacher/content/{id}/ai-prep/quiz', [PageController::class, 'teacherAiPrepQuiz'])->name('teacher.ai-prep.quiz');
    Route::post('/teacher/content/{id}/ai-prep/quiz', [PageController::class, 'submitTeacherAiPrep'])->name('teacher.ai-prep.submit');

    Route::get('/teacher/certificates', [PageController::class, 'teacherCertificates'])->name('teacher.certificates');
    Route::post('/teacher/certificates/approve/{id}', [PageController::class, 'approveCertificate'])->name('teacher.certificates.approve');

    Route::get('/teacher/profile', [PageController::class, 'teacherProfile'])->name('teacher.profile');
    Route::post('/teacher/profile', [PageController::class, 'updateTeacherProfile'])->name('teacher.profile.update');
    Route::get('/teacher/change-password', [UserController::class, 'teacherChangePassword'])->name('teacher.change.password');
    Route::post('/teacher/change-password', [UserController::class, 'teacherChangePasswordSubmit'])->name('teacher.change.password.submit');
    Route::get('/teacher/two-factor', [UserController::class, 'mfaSettings'])->name('teacher.mfa.settings');
    Route::post('/teacher/two-factor/enable', [UserController::class, 'beginMfaSetup'])->name('teacher.mfa.enable');
    Route::post('/teacher/two-factor/verify', [UserController::class, 'confirmMfaSetup'])->name('teacher.mfa.verify');
    Route::post('/teacher/two-factor/disable', [UserController::class, 'disableMfa'])->name('teacher.mfa.disable');
    Route::get('/teacher/feedback', [FeedbackController::class, 'teacherCreate'])->name('teacher.feedback');
    Route::post('/teacher/feedback', [FeedbackController::class, 'teacherStore'])->name('teacher.feedback.store');
    Route::get('/teacher/notifications', [LmsNotificationController::class, 'teacherIndex'])->name('teacher.notifications');
    Route::get('/teacher/achievements', [PageController::class, 'teacherAchievements'])->name('teacher.achievements');
    Route::post('/teacher/achievements', [PageController::class, 'storeTeacherAchievement'])->name('teacher.achievements.store');
    Route::post('/teacher/achievements/{id}/delete', [PageController::class, 'deleteTeacherAchievement'])->name('teacher.achievements.delete');

    Route::get('/teacher-results', [PageController::class, 'teacherResults'])->name('teacher.results');
    Route::post('/teacher-results/ai-insights', [PageController::class, 'generateTeacherResultsAiInsights'])->name('teacher.results.ai-insights');
    Route::post('/teacher-results/ai-insights/download', [PageController::class, 'downloadTeacherResultsAiInsights'])->name('teacher.results.ai-insights.download');
    Route::delete('/teacher/results/disqualify/{id}', [PageController::class, 'disqualifyResult'])->name('teacher.results.disqualify');

    Route::get('/teacher/student-profiles', [TeacherStudentProfileController::class, 'index'])->name('teacher.student.profiles');
    Route::get('/teacher/student-details/export', [TeacherStudentProfileController::class, 'export'])->name('teacher.student.profiles.export');
    Route::get('/teacher/student-management', [PageController::class, 'teacherStudentManagement'])->name('teacher.student-management');
    Route::post('/teacher/student-management/store', [PageController::class, 'storeStudent'])->name('teacher.students.store');
    Route::post('/teacher/student-management/bulk-upload', [PageController::class, 'bulkUploadStudents'])->name('teacher.students.bulk-upload');
    Route::get('/teacher/student-management/bulk-template', [PageController::class, 'downloadStudentBulkTemplate'])->name('teacher.students.bulk-template');
    Route::post('/teacher/student-management/update/{id}', [PageController::class, 'updateStudent'])->name('teacher.students.update');
    Route::get('/teacher/student-management/delete/{id}', [PageController::class, 'deleteStudent'])->name('teacher.students.delete');

    Route::get('/teacher/my-space', [MySpaceController::class, 'index'])->name('teacher.my-space');
    Route::get('/teacher/my-space/create', [MySpaceController::class, 'create'])->name('teacher.my-space.create');
    Route::post('/teacher/my-space/store', [MySpaceController::class, 'store'])->name('teacher.my-space.store');
    Route::get('/teacher/my-space/{id}/edit', [MySpaceController::class, 'edit'])->name('teacher.my-space.edit');
    Route::post('/teacher/my-space/{id}/update', [MySpaceController::class, 'update'])->name('teacher.my-space.update');
    Route::post('/teacher/my-space/{id}/delete', [MySpaceController::class, 'delete'])->name('teacher.my-space.delete');
    Route::get('/teacher/my-space/{id}', [MySpaceController::class, 'show'])->name('teacher.my-space.show');

    Route::post('/assessment-session/start/{assessmentId}', [PageController::class, 'startAssessmentSession'])->name('assessment.session.start');
    Route::post('/assessment-session/violation/{sessionId}', [PageController::class, 'recordAssessmentViolation'])->name('assessment.session.violation');
    Route::post('/assessment-session/submit/{sessionId}', [PageController::class, 'submitAssessmentSession'])->name('assessment.session.submit');

    Route::post('/teacher/class-session/start',[PageController::class, 'startClassSession'])->name('teacher.class-session.start');
    Route::post('/teacher/class-session/end/{sessionId}',[PageController::class, 'endClassSession'])->name('teacher.class-session.end');

    Route::get('/assessment-review', [AssessmentResultController::class, 'reviewResults'])->name('assessment.review');
    Route::post('/assessment-review/{id}', [AssessmentResultController::class, 'reviewAnswer'])->name('assessment.review.submit');

    Route::post('/teacher/topic-complete/{contentId}',[UserController::class, 'markTopicComplete'])->name('teacher.complete-topic');
    Route::get('/teacher/session-content/{contentId}', [PageController::class, 'teacherSessionContent'])->name('teacher.session.content');

    Route::get('/teacher/assessments', [AssessmentController::class, 'index'])->name('teacher.assessments');
    Route::post('/teacher/assessments/store', [AssessmentController::class, 'store'])->name('teacher.assessments.store');
    Route::post('/teacher/assessments/ai-generate', [AssessmentController::class, 'generateAiQuestionPaper'])->name('teacher.assessments.ai-generate');
    Route::post('/teacher/assessments/update/{id}', [AssessmentController::class, 'update'])->name('teacher.assessments.update');
    Route::get('/teacher/assessments/delete/{id}', [AssessmentController::class, 'delete'])->name('teacher.assessments.delete');


});


// Student public routes
Route::get('/student-login', [PageController::class, 'studentLogin'])->name('student.login');
Route::post('/student-login', [PageController::class, 'studentLoginSubmit'])->name('student.login.submit');
Route::get('/student-mfa', [PageController::class, 'studentMfa'])->name('student.mfa');
Route::post('/student-mfa/verify', [PageController::class, 'verifyStudentMfa'])->name('student.mfa.verify');

// Student protected routes
Route::middleware(['student.auth','track.activity'])->group(function () {

    Route::get('/student-dashboard',[PageController::class, 'studentDashboard'])->name('student.dashboard');

    Route::get('/student/take-assessment',[PageController::class, 'studentTakeAssessment'])->name('student.assessment');
    Route::get('/student/component-mastery',[PageController::class, 'studentComponentMastery'])->name('student.component-mastery');
    Route::post('/student/component-assessments/{componentKey}/generate',[PageController::class, 'generateStudentComponentAssessment'])->name('student.component-assessments.generate');
    Route::get('/student/take-assessment/{assessment}/start',[PageController::class, 'studentAssessmentTaking'])->name('student.assessment.take');
    Route::get('/student/history',[PageController::class, 'studentHistory'])->name('student.history');

    Route::get('/student/badges',[PageController::class, 'studentBadges'])->name('student.badges');

    Route::get('/student/profile',[PageController::class, 'studentProfile'])->name('student.profile');
    Route::post('/student/profile',[PageController::class, 'updateStudentProfile'])->name('student.profile.update');
    Route::post('/student/profile/remove-image',[PageController::class, 'removeStudentProfileImage'])->name('student.profile.remove-image');
    Route::get('/student/feedback', [FeedbackController::class, 'studentCreate'])->name('student.feedback');
    Route::post('/student/feedback', [FeedbackController::class, 'studentStore'])->name('student.feedback.store');
    Route::get('/student/notifications', [LmsNotificationController::class, 'studentIndex'])->name('student.notifications');
    Route::post('/assessment-results/store',[AssessmentResultController::class, 'store'])->name('assessment-results.store');
    Route::get('/student/certificate/download', [PageController::class, 'downloadStudentCertificate'])->name('student.certificate.download');

    Route::get('/student/achievements/{id}/edit', [StudentAchievementController::class, 'edit'])->name('student.achievements.edit');
    Route::post('/student/achievements/{id}/update', [StudentAchievementController::class, 'update'])->name('student.achievements.update');
    Route::post('/student/achievements/{id}/delete', [StudentAchievementController::class, 'delete'])->name('student.achievements.delete');
    Route::get('/student/achievements/create',[StudentAchievementController::class, 'create'])->name('student.achievements.create');
    Route::post('/student/achievements/store',[StudentAchievementController::class, 'store'])->name('student.achievements.store');

    Route::get('/student/content',[PageController::class, 'studentContent'])->name('student.content');
    Route::get('/student/content/{id}/ai-review',[PageController::class, 'studentAiReview'])->name('student.content.ai-review');
    Route::get('/student/content/{id}/ai-review/quiz',[PageController::class, 'studentAiReviewQuiz'])->name('student.content.ai-review.quiz');
    Route::post('/student/content/{id}/ai-review/quiz',[PageController::class, 'submitStudentAiReview'])->name('student.content.ai-review.submit');
    Route::post('/student/lesson/{id}/complete',[PageController::class, 'completeLesson'])->name('student.lesson.complete');

    Route::get('/student/my-space', [MySpaceController::class, 'index'])->name('student.my-space');
    Route::get('/student/my-space/create', [MySpaceController::class, 'create'])->name('student.my-space.create');
    Route::post('/student/my-space/store', [MySpaceController::class, 'store'])->name('student.my-space.store');
    Route::get('/student/my-space/{id}/edit', [MySpaceController::class, 'edit'])->name('student.my-space.edit');
    Route::post('/student/my-space/{id}/update', [MySpaceController::class, 'update'])->name('student.my-space.update');
    Route::post('/student/my-space/{id}/delete', [MySpaceController::class, 'delete'])->name('student.my-space.delete');
    Route::get('/student/my-space/{id}', [MySpaceController::class, 'show'])->name('student.my-space.show');
    
    Route::post('/assessment-session/start/{assessmentId}', [PageController::class, 'startAssessmentSession'])->name('assessment.session.start');
    Route::post('/assessment-session/violation/{sessionId}', [PageController::class, 'recordAssessmentViolation'])->name('assessment.session.violation');
    Route::post('/assessment-session/submit/{sessionId}', [PageController::class, 'submitAssessmentSession'])->name('assessment.session.submit');
});
