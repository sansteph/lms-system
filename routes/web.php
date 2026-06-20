<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ClassController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\InstituteController;
use App\Http\Controllers\ContentController;
use App\Http\Controllers\AssessmentController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\AssessmentResultController;
use App\Http\Controllers\AssessmentQuestionController;
use App\Http\Controllers\AIController;
use App\Http\Controllers\StudentAchievementController;
use App\Http\Controllers\LessonProgressController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\StudentProfileController;
use App\Http\Controllers\MySpaceController;
use App\Http\Controllers\TeacherStudentProfileController;
use App\Http\Controllers\IndependentLearnerController;
use Illuminate\Support\Facades\Mail;
use App\Http\Controllers\ClassTimetableController;


//Public Routes

Route::get('/', [PageController::class, 'home'])->name('home');

Route::get('/portal', [PageController::class, 'portal'])->name('portal');

Route::post('/access-request/store', [PageController::class, 'storeAccessRequest'])
    ->name('access.request.store');

Route::get('/logout', [UserController::class, 'logout'])->name('logout');

Route::get('/admin-login', [PageController::class, 'adminLogin'])->name('admin.login');
Route::post('/admin-login', [UserController::class, 'adminLogin'])->name('admin.login.submit');

Route::get('/admin/institute-register', [UserController::class, 'instituteRegister'])
    ->name('admin.institute.register');

Route::post('/admin/institute-register', [UserController::class, 'instituteRegisterSubmit'])
    ->name('admin.institute.register.submit');

Route::get('/teacher-login', [PageController::class, 'teacherLogin'])->name('teacher.login');
Route::post('/teacher-login', [UserController::class, 'teacherLogin'])->name('teacher.login.submit');

Route::get('/student-login', [PageController::class, 'studentLogin'])->name('student.login');
Route::post('/student-login', [PageController::class, 'studentLoginSubmit'])->name('student.login.submit');

Route::get('/verify-certificate', [PageController::class, 'verifyCertificate'])->name('certificate.verify');
Route::post('/verify-certificate', [PageController::class, 'verifyCertificateSubmit'])->name('certificate.verify.submit');

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



//Admin + InstituteAdmin Shared Routes

Route::middleware(['admin.auth'])->group(function () {

    Route::get('/admin-dashboard', [PageController::class, 'adminDashboard'])->name('admin.dashboard');

    Route::get('/admin/change-password', [UserController::class, 'changePassword'])->name('admin.change.password');
    Route::post('/admin/change-password', [UserController::class, 'changePasswordSubmit'])->name('admin.change.password.submit');

    Route::get('/courses', [CourseController::class, 'index'])->name('courses');
    Route::post('/courses/store', [CourseController::class, 'store'])->name('courses.store');
    Route::post('/courses/update/{id}',[CourseController::class, 'update'])->name('courses.update');
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

    Route::get('/content', [ContentController::class, 'index'])->name('content');
    Route::post('/content/store', [ContentController::class, 'store'])->name('content.store');
    Route::post('/content/update/{id}', [ContentController::class, 'update'])->name('content.update');
    Route::get('/content/delete/{id}', [ContentController::class, 'delete'])->name('content.delete');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications');
    Route::post('/notifications/store', [NotificationController::class, 'store'])->name('notifications.store');
    Route::post('/notifications/update/{id}', [NotificationController::class, 'update'])->name('notifications.update');
    Route::get('/notifications/delete/{id}', [NotificationController::class, 'delete'])->name('notifications.delete');

    Route::get('/reports', [ReportController::class, 'index'])->name('reports');

    Route::get('/admin/certificates', [PageController::class, 'adminCertificates'])->name('admin.certificates');
    Route::post('/admin/certificates/revoke/{id}', [PageController::class, 'revokeCertificate'])->name('admin.certificates.revoke');
    Route::post('/admin/certificates/reissue/{id}', [PageController::class, 'reissueCertificate'])->name('admin.certificates.reissue');

    Route::get('/results/export', [PageController::class, 'exportResults'])->name('results.export');

    Route::get('/admin/analytics', [PageController::class, 'adminAnalytics'])->name('admin.analytics');

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

    Route::get('/class-timetable',[ClassTimetableController::class, 'index'])->name('timetable');
    Route::post('/class-timetable/store',[ClassTimetableController::class, 'store'])->name('timetable.store');
    Route::get('/class-timetable/copy-week',[ClassTimetableController::class, 'copyWeekToNext'])->name('timetable.copy.week');
    Route::post('/class-timetable/update/{id}', [ClassTimetableController::class, 'update'])->name('timetable.update');
    Route::get('/class-timetable/delete/{id}',[ClassTimetableController::class, 'delete'])->name('timetable.delete');

    Route::get('/admin/assessment-review-monitoring',[PageController::class, 'assessmentReviewMonitoring'])->name('admin.assessment.review.monitoring');

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


// Teacher protected routes
Route::middleware(['teacher.auth','track.activity'])->group(function () {

    Route::get('/teacher-dashboard', [PageController::class, 'teacherDashboard'])->name('teacher.dashboard');

    Route::get('/teacher/my-classes', [PageController::class, 'teacherClasses'])->name('teacher.classes');

    Route::get('/teacher/content', [PageController::class, 'teacherContent'])->name('teacher.content');

    Route::get('/teacher/assessments', [PageController::class, 'teacherAssessments'])->name('teacher.assessments');

    Route::get('/teacher/reports', [PageController::class, 'teacherReports'])->name('teacher.reports');

    Route::get('/teacher/certificates', [PageController::class, 'teacherCertificates'])->name('teacher.certificates');

    Route::get('/teacher/notifications', [PageController::class, 'teacherNotifications'])->name('teacher.notifications');

    Route::get('/teacher/profile', [PageController::class, 'teacherProfile'])->name('teacher.profile');

    Route::get('/teacher-results', [PageController::class, 'teacherResults'])->name('teacher.results');

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

    Route::post('/teacher/class-session/start/{timetableId}',[PageController::class, 'startClassSession'])->name('teacher.class-session.start');

    Route::post('/teacher/class-session/end/{sessionId}',[PageController::class, 'endClassSession'])->name('teacher.class-session.end');

    Route::get('/assessment-review', [AssessmentResultController::class, 'reviewResults'])->name('assessment.review');

    Route::post('/assessment-review/{id}', [AssessmentResultController::class, 'reviewAnswer'])->name('assessment.review.submit');

    Route::post('/teacher/topic-complete/{contentId}',[UserController::class, 'markTopicComplete'])->name('teacher.complete-topic');

    Route::get('/teacher/session-content/{contentId}', [PageController::class, 'teacherSessionContent'])->name('teacher.session.content');

   Route::get('/teacher/assessments', [AssessmentController::class, 'index'])
    ->name('teacher.assessments');

    Route::post('/teacher/assessments/store', [AssessmentController::class, 'store'])
        ->name('teacher.assessments.store');

    Route::post('/teacher/assessments/update/{id}', [AssessmentController::class, 'update'])
        ->name('teacher.assessments.update');

    Route::get('/teacher/assessments/delete/{id}', [AssessmentController::class, 'delete'])
        ->name('teacher.assessments.delete');

    Route::get('/teacher/assessment-questions', [AssessmentQuestionController::class, 'index'])
        ->name('teacher.assessment.questions');

    Route::post('/teacher/assessment-questions/store', [AssessmentQuestionController::class, 'store'])
        ->name('teacher.assessment.questions.store');

    Route::post('/teacher/assessment-questions/update/{id}', [AssessmentQuestionController::class, 'update'])
        ->name('teacher.assessment.questions.update');

    Route::get('/teacher/assessment-questions/delete/{id}', [AssessmentQuestionController::class, 'delete'])
        ->name('teacher.assessment.questions.delete');
});


// Student protected routes
Route::middleware(['student.auth','track.activity'])->group(function () {

    Route::get('/student-dashboard',[PageController::class, 'studentDashboard'])->middleware('student.profile.completed')->name('student.dashboard');
    Route::get('/student/take-assessment',[PageController::class, 'studentTakeAssessment'])->name('student.assessment');
    Route::get('/student/history',[PageController::class, 'studentHistory'])->name('student.history');
    Route::get('/student/badges',[PageController::class, 'studentBadges'])->name('student.badges');
    Route::get('/student/notifications',[PageController::class, 'studentNotifications'])->name('student.notifications');
    Route::get('/student/profile',[PageController::class, 'studentProfile'])->name('student.profile');
    Route::post('/assessment-results/store',[AssessmentResultController::class, 'store'])->name('assessment-results.store');
    Route::get('/student/certificate',[PageController::class, 'studentCertificate'])->name('student.certificate');
    Route::get('/student/certificate/download', [PageController::class, 'downloadStudentCertificate'])->name('student.certificate.download');
    Route::get('/student/achievements/{id}/edit', [StudentAchievementController::class, 'edit'])->name('student.achievements.edit');
    Route::post('/student/achievements/{id}/update', [StudentAchievementController::class, 'update'])->name('student.achievements.update');
    Route::post('/student/achievements/{id}/delete', [StudentAchievementController::class, 'delete'])->name('student.achievements.delete');
    Route::get('/student/achievements/create',[StudentAchievementController::class, 'create'])->name('student.achievements.create');
    Route::post('/student/achievements/store',[StudentAchievementController::class, 'store'])->name('student.achievements.store');
    Route::get('/student/content',[PageController::class, 'studentContent'])->name('student.content');
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


