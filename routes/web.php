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
use App\Http\Controllers\StudentProfileController;
use App\Http\Controllers\MySpaceController;
use App\Http\Controllers\TeacherStudentProfileController;
use App\Http\Controllers\IndependentLearnerController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\CommunityFeedController;
use App\Http\Controllers\AiContentController;
use App\Http\Controllers\AiChatController;
use App\Http\Controllers\LmsNotificationController;
use App\Http\Controllers\NewsroomController;
use Illuminate\Support\Facades\Mail;
use App\Http\Controllers\TeachingPlanController;


//Public Routes
Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/portal', [PageController::class, 'portal'])->name('portal');
Route::post('/access-request/store', [PageController::class, 'storeAccessRequest'])->name('access.request.store');
Route::get('/logout', [UserController::class, 'logout'])->name('logout');
Route::get('/password-change/confirm/{token}', [UserController::class, 'confirmPasswordChange'])->name('password-change.confirm');
Route::get('/verify-certificate', [PageController::class, 'verifyCertificate'])->name('certificate.verify');
Route::post('/verify-certificate', [PageController::class, 'verifyCertificateSubmit'])->name('certificate.verify.submit');
Route::get('/content-preview/{content}/for/{audience}', [ContentController::class, 'showPreview'])->name('content.preview');
Route::get('/content-preview-stream/{content}/for/{audience}', [ContentController::class, 'streamPreview'])->name('content.preview.stream');
Route::get('/content-files/{content}/for/{audience}/{variant?}', [ContentController::class, 'showFile'])->name('content.file.audience');
Route::get('/content-files/{content}/{variant?}', [ContentController::class, 'showFile'])->name('content.file');
Route::get('/assessment-paper/{assessment}/{variant?}', [AssessmentController::class, 'showQuestionPaper'])->name('assessment.paper');
Route::get('/assessment-answer-file/{result}', [AssessmentResultController::class, 'showAnswerFile'])->name('assessment.answer.file');
Route::post('/ai-chat/ask', [AiChatController::class, 'ask'])->middleware('throttle:20,1')->name('ai-chat.ask');
Route::get('/newsroom', [NewsroomController::class, 'index'])->name('newsroom');
Route::get('/blogs/login', [CommunityFeedController::class, 'blogsLogin'])->name('blogs.login');
Route::post('/blogs/login', [CommunityFeedController::class, 'blogsLoginSubmit'])->name('blogs.login.submit');
Route::get('/blogs', [CommunityFeedController::class, 'blogsEntry'])->name('blogs.community-feed');
Route::post('/blogs', [CommunityFeedController::class, 'store'])->name('blogs.community-feed.store');
Route::post('/blogs/{id}/like', [CommunityFeedController::class, 'toggleLike'])->name('blogs.community-feed.like');
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
Route::get('/admin/institute-register', [UserController::class, 'instituteRegister'])->name('admin.institute.register');
Route::post('/admin/institute-register', [UserController::class, 'instituteRegisterSubmit'])->name('admin.institute.register.submit');

//Admin + InstituteAdmin Shared Routes
Route::middleware(['admin.auth', 'track.activity'])->group(function () {

    Route::get('/admin-dashboard', [PageController::class, 'adminDashboard'])->name('admin.dashboard');

    Route::get('/admin/change-password', [UserController::class, 'changePassword'])->name('admin.change.password');
    Route::post('/admin/change-password', [UserController::class, 'changePasswordSubmit'])->name('admin.change.password.submit');

    Route::get('/courses', [CourseController::class, 'index'])->name('courses');
    Route::post('/courses/store', [CourseController::class, 'store'])->name('courses.store');
    Route::post('/courses/update/{id}',[CourseController::class, 'update'])->name('courses.update');
    Route::post('/courses/{id}/upload-content', [CourseController::class, 'uploadCourseContent'])->name('courses.upload-content');
    Route::post('/courses/{id}/contents/{courseContent}/order', [CourseController::class, 'updateContentOrder'])->name('courses.contents.order');
    Route::post('/courses/{id}/contents/{courseContent}/detach', [CourseController::class, 'detachContent'])->name('courses.contents.detach');
    Route::get('/courses/delete/{id}', [CourseController::class, 'delete'])->name('courses.delete');

    Route::get('/students', [PageController::class, 'students'])->name('students');
    Route::post('/students/store', [PageController::class, 'storeStudent'])->name('students.store');
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
    Route::post('/content/{content}/ai-summary', [AiContentController::class, 'generateSummary'])->name('content.ai-summary.generate');
    Route::get('/content/delete/{id}', [ContentController::class, 'delete'])->name('content.delete');

    Route::get('/reports', [ReportController::class, 'index'])->name('reports');
    Route::get('/reports/export', [ReportController::class, 'exportCsv'])->name('reports.export');
    Route::post('/reports/ai-insights', [ReportController::class, 'generateAiInsights'])->name('reports.ai-insights');
    Route::post('/reports/ai-insights/download', [ReportController::class, 'downloadAiInsights'])->name('reports.ai-insights.download');

    Route::get('/admin/certificates', [PageController::class, 'adminCertificates'])->name('admin.certificates');
    Route::post('/admin/certificates/approve/{id}', [PageController::class, 'approveCertificate'])->name('admin.certificates.approve');
    Route::post('/admin/certificates/reject/{id}', [PageController::class, 'rejectCertificate'])->name('admin.certificates.reject');
    Route::post('/admin/certificates/revoke/{id}', [PageController::class, 'revokeCertificate'])->name('admin.certificates.revoke');
    Route::post('/admin/certificates/reissue/{id}', [PageController::class, 'reissueCertificate'])->name('admin.certificates.reissue');

    Route::get('/admin/analytics', [PageController::class, 'adminAnalytics'])->name('admin.analytics');
    Route::post('/admin/analytics/ai-insights', [PageController::class, 'generateAdminAnalyticsAiInsights'])->name('admin.analytics.ai-insights');
    Route::post('/admin/analytics/ai-insights/download', [PageController::class, 'downloadAdminAnalyticsAiInsights'])->name('admin.analytics.ai-insights.download');

    Route::get('/admin/achievements', [StudentAchievementController::class, 'adminIndex'])->name('admin.achievements');
    Route::post('/admin/achievements/{id}/approve', [StudentAchievementController::class, 'approve'])->name('admin.achievements.approve');
    Route::post('/admin/achievements/{id}/reject', [StudentAchievementController::class, 'reject'])->name('admin.achievements.reject');

    Route::get('/admin/my-space', [MySpaceController::class, 'adminIndex'])->name('admin.my-space');
    Route::post('/admin/my-space/{id}/approve', [MySpaceController::class, 'approve'])->name('admin.my-space.approve');
    Route::post('/admin/my-space/{id}/reject', [MySpaceController::class, 'reject'])->name('admin.my-space.reject');
    Route::post('/admin/my-space/{id}/feature', [MySpaceController::class, 'feature'])->name('admin.my-space.feature');
    Route::get('/admin/my-space/{id}', [MySpaceController::class, 'show'])->name('admin.my-space.show');

    Route::get('/admin/assessment-monitoring', [PageController::class, 'assessmentMonitoring'])->name('admin.assessment.monitoring');

    Route::get('/admin/class-session-report', [PageController::class, 'classSessionReport'])->name('admin.class-session.report');
    Route::get('/admin/class-session-report/export', [PageController::class, 'exportClassSessionReport'])->name('admin.class-session.report.export');

    Route::get('/notifications', [LmsNotificationController::class, 'index'])->name('notifications');
    Route::post('/notifications/store', [LmsNotificationController::class, 'store'])->name('notifications.store');
    Route::post('/notifications/delete/{id}', [LmsNotificationController::class, 'delete'])->name('notifications.delete');

    Route::get('/admin/community-feed', [CommunityFeedController::class, 'index'])->name('admin.community-feed');
    Route::post('/admin/community-feed', [CommunityFeedController::class, 'store'])->name('admin.community-feed.store');
    Route::post('/admin/community-feed/{id}/like', [CommunityFeedController::class, 'toggleLike'])->name('admin.community-feed.like');
    Route::post('/admin/community-feed/{id}/approve', [CommunityFeedController::class, 'approve'])->name('admin.community-feed.approve');
    Route::post('/admin/community-feed/{id}/reject', [CommunityFeedController::class, 'reject'])->name('admin.community-feed.reject');
    Route::post('/admin/community-feed/{id}/delete', [CommunityFeedController::class, 'delete'])->name('admin.community-feed.delete');

    Route::get('/teaching-plans', [TeachingPlanController::class, 'index'])->name('teaching-plans');
    Route::post('/teaching-plans/store', [TeachingPlanController::class, 'store'])->name('teaching-plans.store');
    Route::post('/teaching-plan-templates/store', [TeachingPlanController::class, 'storeTemplate'])->name('teaching-plan-templates.store');
    Route::post('/teaching-plan-templates/deploy', [TeachingPlanController::class, 'deployTemplates'])->name('teaching-plan-templates.deploy');
    Route::post('/teaching-plans/update/{id}', [TeachingPlanController::class, 'update'])->name('teaching-plans.update');
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
});

//Super Admin Only Routes
Route::middleware(['admin.auth', 'super.admin'])->group(function () {

    Route::get('/institutes', [InstituteController::class, 'index'])->name('institutes');
    Route::post('/institutes/store', [InstituteController::class, 'store'])->name('institutes.store');
    Route::post('/institutes/update/{id}', [InstituteController::class, 'update'])->name('institutes.update');
    Route::get('/institutes/delete/{id}', [InstituteController::class, 'delete'])->name('institutes.delete');

    Route::get('/admin/institute-requests', [UserController::class, 'instituteRequests'])->name('admin.institute.requests');
    Route::post('/admin/institute-requests/{id}/approve', [UserController::class, 'approveInstituteRequest']) ->name('admin.institute.requests.approve');
    Route::post('/admin/institute-requests/{id}/reject', [UserController::class, 'rejectInstituteRequest']) ->name('admin.institute.requests.reject');

    Route::get('/admin/independent-learners', [IndependentLearnerController::class, 'adminIndex'])->name('admin.independent.learners');
    Route::post('/admin/independent-learners/{id}/toggle-status', [IndependentLearnerController::class, 'toggleStatus'])->name('admin.independent.learners.toggle-status');
    Route::get('/admin/independent-learners/{id}', [IndependentLearnerController::class, 'showLearner'])->name('admin.independent.learners.show');

    Route::get('/admin/activity-monitoring', [PageController::class, 'activityMonitoring'])->name('admin.activity.monitoring');
    Route::get('/admin/export-activity-report', [PageController::class, 'exportActivityReport'])->name('admin.export.activity');

});


// Teacher public routes
Route::get('/teacher-login', [PageController::class, 'teacherLogin'])->name('teacher.login');
Route::post('/teacher-login', [UserController::class, 'teacherLogin'])->name('teacher.login.submit');

// Teacher protected routes
Route::middleware(['teacher.auth','track.activity'])->group(function () {

    Route::get('/teacher-dashboard', [PageController::class, 'teacherDashboard'])->name('teacher.dashboard');

    Route::get('/teacher/my-classes', [PageController::class, 'teacherClasses'])->name('teacher.classes');
    Route::get('/teacher/pending-sessions', [PageController::class, 'teacherPendingSessions'])->name('teacher.pending-sessions');
    Route::get('/teacher/session-completion-video/{fileName}', [PageController::class, 'streamSessionCompletionVideo'])
        ->where('fileName', '[A-Za-z0-9._ -]+')
        ->name('teacher.session-completion-video');

    Route::get('/teacher/content', [PageController::class, 'teacherContent'])->name('teacher.content');
    Route::get('/teacher/content/{id}/ai-prep', [PageController::class, 'teacherAiPrep'])->name('teacher.ai-prep');
    Route::get('/teacher/content/{id}/ai-prep/quiz', [PageController::class, 'teacherAiPrepQuiz'])->name('teacher.ai-prep.quiz');
    Route::post('/teacher/content/{id}/ai-prep/quiz', [PageController::class, 'submitTeacherAiPrep'])->name('teacher.ai-prep.submit');

    Route::get('/teacher/reports', [PageController::class, 'teacherReports'])->name('teacher.reports');
    Route::get('/teacher/reports/export', [PageController::class, 'exportTeacherReports'])->name('teacher.reports.export');

    Route::get('/teacher/certificates', [PageController::class, 'teacherCertificates'])->name('teacher.certificates');
    Route::post('/teacher/certificates/approve/{id}', [PageController::class, 'approveCertificate'])->name('teacher.certificates.approve');

    Route::get('/teacher/profile', [PageController::class, 'teacherProfile'])->name('teacher.profile');
    Route::post('/teacher/profile', [PageController::class, 'updateTeacherProfile'])->name('teacher.profile.update');
    Route::get('/teacher/change-password', [UserController::class, 'teacherChangePassword'])->name('teacher.change.password');
    Route::post('/teacher/change-password', [UserController::class, 'teacherChangePasswordSubmit'])->name('teacher.change.password.submit');
    Route::get('/teacher/feedback', [FeedbackController::class, 'teacherCreate'])->name('teacher.feedback');
    Route::post('/teacher/feedback', [FeedbackController::class, 'teacherStore'])->name('teacher.feedback.store');
    Route::get('/teacher/notifications', [LmsNotificationController::class, 'teacherIndex'])->name('teacher.notifications');
    Route::get('/teacher/community-feed', [CommunityFeedController::class, 'index'])->name('teacher.community-feed');
    Route::post('/teacher/community-feed', [CommunityFeedController::class, 'store'])->name('teacher.community-feed.store');
    Route::post('/teacher/community-feed/{id}/like', [CommunityFeedController::class, 'toggleLike'])->name('teacher.community-feed.like');
    Route::post('/teacher/community-feed/{id}/approve', [CommunityFeedController::class, 'approve'])->name('teacher.community-feed.approve');
    Route::post('/teacher/community-feed/{id}/reject', [CommunityFeedController::class, 'reject'])->name('teacher.community-feed.reject');
    Route::post('/teacher/community-feed/{id}/delete', [CommunityFeedController::class, 'delete'])->name('teacher.community-feed.delete');
    Route::get('/teacher/achievements', [PageController::class, 'teacherAchievements'])->name('teacher.achievements');
    Route::post('/teacher/achievements', [PageController::class, 'storeTeacherAchievement'])->name('teacher.achievements.store');
    Route::post('/teacher/achievements/{id}/delete', [PageController::class, 'deleteTeacherAchievement'])->name('teacher.achievements.delete');

    Route::get('/teacher-results', [PageController::class, 'teacherResults'])->name('teacher.results');
    Route::post('/teacher-results/ai-insights', [PageController::class, 'generateTeacherResultsAiInsights'])->name('teacher.results.ai-insights');
    Route::post('/teacher-results/ai-insights/download', [PageController::class, 'downloadTeacherResultsAiInsights'])->name('teacher.results.ai-insights.download');
    Route::delete('/teacher/results/disqualify/{id}', [PageController::class, 'disqualifyResult'])->name('teacher.results.disqualify');
    Route::get('/results/export', [PageController::class, 'exportResults'])->name('results.export');

    Route::get('/teacher/student-profiles', [TeacherStudentProfileController::class, 'index'])->name('teacher.student.profiles');
    Route::get('/teacher/student-details/export', [TeacherStudentProfileController::class, 'export'])->name('teacher.student.profiles.export');

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
    Route::post('/teacher/assessments/update/{id}', [AssessmentController::class, 'update'])->name('teacher.assessments.update');
    Route::get('/teacher/assessments/delete/{id}', [AssessmentController::class, 'delete'])->name('teacher.assessments.delete');


});


// Student public routes
Route::get('/student-login', [PageController::class, 'studentLogin'])->name('student.login');
Route::post('/student-login', [PageController::class, 'studentLoginSubmit'])->name('student.login.submit');

// Student protected routes
Route::middleware(['student.auth','track.activity'])->group(function () {

    Route::get('/student-dashboard',[PageController::class, 'studentDashboard'])->middleware('student.profile.completed')->name('student.dashboard');

    Route::get('/student/take-assessment',[PageController::class, 'studentTakeAssessment'])->name('student.assessment');
    Route::get('/student/take-assessment/{assessment}/start',[PageController::class, 'studentAssessmentTaking'])->name('student.assessment.take');
    Route::get('/student/history',[PageController::class, 'studentHistory'])->name('student.history');

    Route::get('/student/badges',[PageController::class, 'studentBadges'])->name('student.badges');

    Route::get('/student/profile',[PageController::class, 'studentProfile'])->name('student.profile');
    Route::post('/student/profile',[PageController::class, 'updateStudentProfile'])->name('student.profile.update');
    Route::post('/student/profile/remove-image',[PageController::class, 'removeStudentProfileImage'])->name('student.profile.remove-image');
    Route::get('/student/feedback', [FeedbackController::class, 'studentCreate'])->name('student.feedback');
    Route::post('/student/feedback', [FeedbackController::class, 'studentStore'])->name('student.feedback.store');
    Route::get('/student/notifications', [LmsNotificationController::class, 'studentIndex'])->name('student.notifications');
    Route::get('/student/community-feed', [CommunityFeedController::class, 'index'])->name('student.community-feed');
    Route::post('/student/community-feed', [CommunityFeedController::class, 'store'])->name('student.community-feed.store');
    Route::post('/student/community-feed/{id}/like', [CommunityFeedController::class, 'toggleLike'])->name('student.community-feed.like');
    Route::post('/student/community-feed/{id}/delete', [CommunityFeedController::class, 'delete'])->name('student.community-feed.delete');

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

    Route::get('/student/basic-details', [StudentProfileController::class, 'create'])->name('student.basic-details');
    Route::post('/student/basic-details', [StudentProfileController::class, 'store'])->name('student.basic-details.store');
    
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
