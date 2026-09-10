<?php

namespace Tests\Feature;

use App\Models\{User, Student, Assessment, AssessmentSession, AssessmentResult, Certificate, TeachingPlan, TeachingPlanWeek, TeachingPlanItem, Content, ClassContentSession, SchoolClass, Course, Institute, LessonProgress, UserActivityLog, UserSession};
use App\Services\{MobileAssessmentService, MobileContentAccess, TeachingPlanReleaseService, LmsNotificationService};
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema, Cache, Hash, Storage};
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MobileSecurityParityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (!in_array('sqlite', \PDO::getAvailableDrivers(), true)) $this->markTestSkipped('SQLite required.');
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'database.connections.sqlite.url' => null, 'cache.default' => 'array', 'session.driver' => 'array']);
        DB::purge('sqlite');
        foreach ([
            'users' => ['user_id','name','email','password','role','institute','status','qualification','designation','joined_on','linkedin_url','profile_image','phone'],
            'students' => ['student_id','name','email','password','institute','class','section','status','contact','guardian_name','is_robotics_club_member','profile_completed','linkedin_url','profile_image'],
            'institutes' => ['institute_name','status','location','institute_id','contact_person','email','phone'], 'classes' => ['class_name','section','institute','status','academic_year','class_teacher'],
            'courses' => ['course_title','institute','status','is_template_source','assigned_class'],
            'contents' => ['content_title','institute','course_id','status','is_released','assigned_class','lesson_order','preview_pdf_path','student_preview_pdf_path','student_file_path','file_path'],
            'course_contents' => ['course_id','content_id','sort_order','status','source_template_content_id'],
            'teaching_plans' => ['title','course_id','institute','class','section','status','is_template','start_date','release_day','release_policy','ai_training_start_date','remarks'],
            'teaching_plan_weeks' => ['teaching_plan_id','week_number','status','release_date','week_start_date','week_end_date','release_reason','released_at','completed_at'],
            'teaching_plan_items' => ['teaching_plan_id','teaching_plan_week_id','content_id','status','released_at','completed_at','sort_order','completed_by','completed_by_role','course_id'],
            'ai_content_summaries' => ['content_id','summary','status','key_points','teacher_quiz','student_quiz','provider','model','extracted_text'],
            'ai_quizzes' => ['content_id','audience','grade_level','status','provider','model','title','instructions','total_marks','passing_marks'],
            'ai_quiz_questions' => ['ai_quiz_id','question_order','question_type','question_text','options','expected_answer','marks'],
            'ai_quiz_attempts' => ['ai_quiz_id','content_id','attempt_type','grade_level','teacher_id','student_id','status','started_at','submitted_at','score','percentage','feedback','evaluated_at'],
            'ai_quiz_answers' => ['ai_quiz_attempt_id','ai_quiz_question_id','answer_text','score','feedback'],
            'ai_component_content_profiles' => ['content_id','component_key','component_label','is_practical','confidence','evidence','provider','model','analyzed_at'],
            'assessments' => ['assessment_title','assessment_type','institute','assigned_class','assessment_category','assessment_date','start_time','end_time','duration','total_marks','teacher_id','status','question_paper_status','file_path','question_paper_preview_path','question_paper_reviewed_by','question_paper_reviewed_at','question_paper_feedback','question_paper_type','ai_generated','ai_source_content_ids','ai_generation_payload','content_id','component_key','component_label','certificate_eligible'],
            'assessment_sessions' => ['assessment_id','user_id','user_type','started_at','submitted_at','status','violation_count','last_violation_at'],
            'assessment_results' => ['assessment_id','student_id','score','total_marks','status','percentage','answer_text','answer_file_path','badge','feedback','passed','evaluated_by','evaluated_at'],
            'certificates' => ['student_id','certificate_code','badge_count','final_score','final_grade','final_classification','issued_date','status','approved_by','approved_at','rejection_reason','certificate_type','course_id'],
            'class_content_sessions' => ['teaching_plan_item_id','teaching_plan_id','teaching_plan_week_id','course_id','stem_engineer_id','institute','content_id','started_at','ended_at','end_time','status','planned_topic','delivered_topic','delivered_content_id','duration_seconds','remarks','session_date','class','section','class_id'],
            'class_timetables' => ['class_id','content_id'],
            'lesson_progress' => ['student_id','content_id','is_completed','completed_at'],
            'user_sessions' => ['user_type','user_id','login_time','logout_time','total_duration_seconds','ip_address','browser'],
            'user_activity_logs' => ['user_session_id','user_type','user_id','section_name','route_name','page_url','started_at','ended_at','duration_seconds'],
            'community_posts' => ['title','body','post_type','image_path','attachment_path','attachment_original_name','author_type','author_id','institute','status','published_at','approved_by','approved_at','rejected_at','source_type','source_id'],
            'community_post_comments' => ['community_post_id','commenter_type','commenter_id','body'],
            'community_post_likes' => ['community_post_id','liker_type','liker_id'],
            'lms_notifications' => ['title','message','target','institute','starts_at','expires_at','status','created_by','notification_type','login_display_limit'],
            'lms_notification_login_views' => ['notification_id','user_id','viewer_role','institute','display_count','last_displayed_at'],
            'certificate_verification_logs' => ['verifier_name','verifier_email','verification_reason','certificate_code','verification_status','certificate_id','ip_address'],
            'my_spaces' => ['title','description','created_by_type','created_by_id','type','status','blueprint_pdf'],
            'student_achievements' => ['student_id','title','achievement_type','position','description','achievement_date','organizer','verification_status','certificate_file'],
            'teacher_achievements' => ['user_id','title','description','verification_status','certificate_file'],
            'assessment_answers' => ['assessment_result_id','assessment_id','student_id'],
            'assessment_questions' => ['assessment_id'],
            'pending_password_changes' => ['user_id'],
        ] as $name => $columns) {
            Schema::create($name, function (Blueprint $t) use ($columns) {
                $t->id(); foreach ($columns as $c) $t->string($c)->nullable(); $t->timestamps();
            });
        }
        $this->mock(LmsNotificationService::class)->shouldReceive('notifyTeachersOfReleasedWeek')->zeroOrMoreTimes();
    }

    private function student(): Student
    {
        return Student::create(['student_id' => uniqid('S'), 'name' => 'Student', 'password' => 'Correct123', 'institute' => 'Alpha', 'class' => 'Class 10', 'section' => 'A', 'status' => 1]);
    }

    public function test_gap_login_notices_share_web_limits_and_institute_scope(): void
    {
        $this->app->forgetInstance(LmsNotificationService::class);
        $teacher = $this->user(); $student = $this->student();
        foreach ([['Topic', 'Alpha', 'teachers', 'admin_topic_complete'], ['Other', 'Beta', 'teachers', null], ['Students', 'Alpha', 'students', null]] as [$title, $institute, $target, $type]) {
            \App\Models\LmsNotification::create(['title' => $title, 'message' => 'Notice', 'institute' => $institute, 'target' => $target, 'status' => 'active', 'notification_type' => $type, 'login_display_limit' => 2]);
        }
        $service = app(LmsNotificationService::class);
        $first = $service->mobileLoginNotifications($teacher);
        $this->assertSame(['Topic'], array_column($first, 'title'));
        $second = $service->mobileLoginNotifications($teacher);
        $this->assertNotSame($first[0]['signature'], $second[0]['signature']);
        $this->assertSame([], $service->mobileLoginNotifications($teacher));
        $this->assertSame(['Students'], array_column($service->mobileLoginNotifications($student), 'title'));
        $this->assertEquals(2, DB::table('lms_notification_login_views')->value('display_count'));
    }

    public function test_gap_feedback_matches_web_template_validation_and_recipients(): void
    {
        \Illuminate\Support\Facades\Mail::shouldReceive('send')->twice()->andReturnUsing(function ($view, $data, $callback) {
            $this->assertSame('emails.feedback-submitted', $view);
            $this->assertStringContainsString('Broken lesson', view($view, $data)->render());
            $message = new \Illuminate\Mail\Message(new \Symfony\Component\Mime\Email());
            $callback($message);
            $this->assertSame('tinkedgemain@gmail.com', $message->getSymfonyMessage()->getTo()[0]->getAddress());
            $this->assertCount(2, $message->getSymfonyMessage()->getCc());
        });
        foreach ([$this->user('STEM Engineer'), $this->student()] as $actor) {
            Sanctum::actingAs($actor);
            $this->postJson('/api/feedback', ['category' => 'Invalid', 'subject' => 'Broken lesson', 'message' => 'Details'])->assertUnprocessable();
            $this->postJson('/api/feedback', ['category' => 'Learning Content', 'subject' => 'Broken lesson', 'message' => 'Details'])->assertOk();
        }
    }

    public function test_gap_results_insights_filters_and_export_links_are_scoped(): void
    {
        $teacher = $this->user(); $student = $this->student();
        SchoolClass::create(['institute' => 'Alpha', 'class_name' => 'Class 10', 'section' => 'A']);
        $paper = Assessment::create(['assessment_title' => 'Exam', 'teacher_id' => $teacher->id, 'institute' => 'Alpha']);
        AssessmentResult::create(['assessment_id' => $paper->id, 'student_id' => $student->id, 'status' => 'Completed', 'percentage' => 85]);
        $other = Assessment::create(['assessment_title' => 'Other exam', 'teacher_id' => $this->user()->id, 'institute' => 'Alpha']);
        AssessmentResult::create(['assessment_id' => $other->id, 'student_id' => $student->id, 'status' => 'Completed', 'percentage' => 10]);
        $this->mock(\App\Services\Ai\GeminiAiService::class)->shouldReceive('generateReportInsights')->once()
            ->with('STEM Engineer Student Results', \Mockery::on(fn ($m) => $m['total_results'] === 1 && (float) $m['average_percentage'] === 85.0))->andReturn(['summary' => 'Improved']);
        Sanctum::actingAs($teacher);
        $this->postJson('/api/workflows/results/insights', ['student_class' => 'Class 10', 'student_section' => 'A'])->assertOk()->assertJsonPath('insights.summary', 'Improved');
        $this->getJson('/api/workflows/results?student_section=B')->assertOk()->assertJsonCount(0, 'records');
        $this->getJson('/api/workflows/results')->assertOk()->assertJsonCount(1, 'records');
        $url = $this->getJson('/api/workflows/students/export?student_class=Class+10&search=Student')->assertOk()->json('download_url');
        $csv = $this->get($url)->assertOk()->streamedContent();
        $this->assertStringContainsString('Student', $csv);
        $this->get($url.'x')->assertForbidden();
        $teacher->update(['status' => 0]); $this->get($url)->assertForbidden();
        Sanctum::actingAs($student);
        $this->getJson('/api/workflows/students/export')->assertForbidden();
    }

    public function test_gap_results_export_produces_web_pdf_with_filtered_metrics(): void
    {
        $teacher = $this->user(); $student = $this->student();
        SchoolClass::create(['institute' => 'Alpha', 'class_name' => 'Class 10', 'section' => 'A']);
        $paper = Assessment::create(['assessment_title' => 'Exam', 'teacher_id' => $teacher->id, 'institute' => 'Alpha']);
        AssessmentResult::create(['assessment_id' => $paper->id, 'student_id' => $student->id, 'status' => 'Completed', 'percentage' => 85, 'badge' => 'Gold']);
        $outsider = $this->student(); $outsider->update(['institute' => 'Beta']);
        AssessmentResult::create(['assessment_id' => $paper->id, 'student_id' => $outsider->id, 'status' => 'Completed', 'percentage' => 10]);
        $this->mock(\App\Services\Ai\GeminiAiService::class)->shouldReceive('generateReportInsights')->once()
            ->with('STEM Engineer Student Results', \Mockery::on(fn ($m) => $m['total_results'] === 1 && $m['section_filter'] === 'A'))->andReturn(['summary' => 'Improved']);
        Sanctum::actingAs($teacher);
        $this->getJson('/api/workflows/results?search=Gold')->assertOk()->assertJsonCount(1, 'records');
        $this->getJson('/api/workflows/results')->assertOk()->assertJsonCount(1, 'records');
        $url = $this->getJson('/api/workflows/results/export?student_class=Class+10&student_section=A')->assertOk()->json('download_url');
        $pdf = $this->get($url)->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $pdf->getContent());
        $this->travel(6)->minutes();
        $this->get($url)->assertForbidden();
    }

    public function test_gap_ai_question_paper_uses_real_services_and_web_pdf_approval_flow(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('lesson.pdf', '%PDF-lesson');
        $teacher = $this->user();
        SchoolClass::create(['institute' => 'Alpha', 'class_name' => 'Class 10', 'section' => 'A', 'status' => 1]);
        $content = Content::create(['content_title' => 'Circuits', 'institute' => 'Alpha', 'status' => 1, 'file_path' => 'lesson.pdf']);
        $plan = TeachingPlan::create(['institute' => 'Alpha', 'class' => 'Class 10', 'section' => 'A', 'status' => 'active']);
        \App\Models\TeachingPlanItem::create(['teaching_plan_id' => $plan->id, 'content_id' => $content->id, 'status' => 'released']);
        $this->mock(\App\Services\Ai\PdfTextExtractionService::class)->shouldReceive('extract')->once()->andReturn(str_repeat('A closed circuit carries electric current. ', 10));
        $this->mock(\App\Services\Ai\GeminiAiService::class)->shouldReceive('generateAssessmentQuestionPaper')->once()
            ->with(\Mockery::on(fn ($p) => $p['assigned_class'] === 'Class 10 A' && $p['content_titles'] === ['Circuits'] && str_contains($p['content_text'], 'closed circuit')))
            ->andReturn(['title' => 'Circuits quiz', 'sections' => [['heading' => 'Circuits', 'questions' => [['number' => 1, 'question' => 'Explain current.', 'marks' => 10]]]]]);
        Sanctum::actingAs($teacher);
        $input = ['assessment_title' => 'Circuits quiz', 'assessment_type' => 'Theory', 'assigned_class' => 'Class 10 A', 'assessment_category' => 'Monthly', 'assessment_date' => today()->toDateString(), 'total_marks' => 10, 'duration' => '30', 'content_ids' => [$content->id], 'status' => true];
        $this->postJson('/api/workflows/assessments/ai-create', $input)->assertOk();
        $paper = Assessment::firstOrFail();
        $this->assertSame('Pending Approval', $paper->question_paper_status);
        $this->assertEquals($teacher->id, $paper->teacher_id);
        $this->assertStringStartsWith('%PDF-', Storage::disk('local')->get($paper->file_path));
        Sanctum::actingAs($this->student());
        $this->postJson('/api/workflows/assessments/ai-create', $input)->assertForbidden();
    }

    public function test_gap_legacy_pagination_does_not_drop_old_records_or_mix_institutes(): void
    {
        for ($i = 0; $i < 28; $i++) $this->student();
        $this->student()->update(['institute' => 'Beta']);
        Sanctum::actingAs($this->user());
        $this->getJson('/api/engineer/students')->assertOk()->assertJsonCount(25, 'details')->assertJsonPath('pagination.details.total', 28);
        $this->getJson('/api/engineer/students?details_page=2')->assertOk()->assertJsonCount(3, 'details');
    }

    public function test_gap_dashboard_uses_web_counts_and_roster(): void
    {
        $teacher = $this->user(); $student = $this->student();
        SchoolClass::create(['class_name' => 'Class 10', 'section' => 'A', 'institute' => 'Alpha', 'status' => 1]);
        Assessment::create(['teacher_id' => $teacher->id, 'institute' => 'Alpha', 'assessment_category' => 'Monthly']);
        Assessment::create(['teacher_id' => $this->user()->id, 'institute' => 'Alpha', 'assessment_category' => 'Monthly']);
        Sanctum::actingAs($teacher);
        $data = $this->getJson('/api/dashboard/summary')->assertOk()->json();
        $metrics = collect($data['metrics'])->pluck('value', 'label');
        $this->assertSame('1', $metrics['Assessments']);
        $this->assertSame('1', $metrics['Students']);
        $this->assertEquals(1, $data['class_roster'][0]['students']);
        Sanctum::actingAs($student);
        $this->getJson('/api/dashboard/summary')->assertOk()->assertJsonStructure(['upcoming_assessments', 'metrics']);
    }

    public function test_gap_public_verification_logs_and_hides_unissued_details(): void
    {
        $student = $this->student();
        $cert = Certificate::create(['student_id' => $student->id, 'certificate_code' => 'CHECK-1', 'status' => 'Issued', 'issued_date' => today()]);
        $input = ['verifier_name' => 'Verifier', 'verifier_email' => 'verifier@example.com', 'verification_reason' => 'Application', 'certificate_code' => 'CHECK-1'];
        $this->postJson('/api/public/verify-certificate', [])->assertUnprocessable();
        $this->postJson('/api/public/verify-certificate', $input)->assertOk()->assertJsonPath('status', 'verified')->assertJsonPath('certificate.name', 'Student');
        $cert->update(['status' => 'Revoked']);
        $this->postJson('/api/public/verify-certificate', $input)->assertOk()->assertJsonPath('status', 'revoked')->assertJsonPath('certificate', null);
        $this->assertDatabaseCount('certificate_verification_logs', 2);
    }

    public function test_gap_newsroom_reuses_cached_web_feed(): void
    {
        $this->mock(\App\Services\Newsroom\NewsroomFeedService::class)->shouldReceive('feed')->once()->with(false)->andReturn(['items' => [['title' => 'STEM']], 'digest' => 'Updates']);
        $this->getJson('/api/public/newsroom?refresh=1')->assertOk()->assertJsonPath('items.0.title', 'STEM');
    }

    public function test_gap_mobile_activity_stays_active_until_stop_and_detects_disconnect(): void
    {
        $teacher = $this->user(); Sanctum::actingAs($teacher);
        $id = $this->postJson('/api/activity', ['action' => 'start', 'section' => 'Learning Content'])->assertOk()->json('id');
        $this->travel(30)->seconds();
        $this->postJson('/api/activity', ['action' => 'heartbeat', 'id' => $id])->assertOk();
        $this->assertSame('Active', UserActivityLog::find($id)->activity_status);
        $this->assertNull(UserActivityLog::find($id)->ended_at);
        $this->travel(91)->seconds();
        $this->assertSame('Disconnected', UserActivityLog::find($id)->activity_status);
        $this->postJson('/api/activity', ['action' => 'stop', 'id' => $id])->assertOk();
        $this->assertSame('Completed', UserActivityLog::find($id)->activity_status);
        $this->assertLessThanOrEqual(75, (int) UserActivityLog::find($id)->duration_seconds);
    }

    public function test_gap_admin_student_delete_removes_files_results_and_tracking(): void
    {
        Storage::fake('local'); Storage::fake('public');
        $student = $this->student(); $other = $this->student();
        Storage::disk('local')->put('answers/one.pdf', 'answer');
        AssessmentResult::create(['student_id' => $student->id, 'answer_file_path' => 'answers/one.pdf']);
        LessonProgress::create(['student_id' => $student->id, 'content_id' => 1]);
        UserSession::create(['user_id' => $student->id, 'user_type' => 'Student']);
        Sanctum::actingAs($this->user('Admin'));
        $this->deleteJson('/api/admin/students/'.$student->id)->assertOk();
        $this->assertDatabaseMissing('students', ['id' => $student->id]);
        $this->assertDatabaseHas('students', ['id' => $other->id]);
        $this->assertDatabaseCount('assessment_results', 0);
        $this->assertDatabaseCount('lesson_progress', 0);
        $this->assertDatabaseCount('user_sessions', 0);
        Storage::disk('local')->assertMissing('answers/one.pdf');
    }

    public function test_gap_ai_namespace_and_removed_dead_route_are_valid(): void
    {
        foreach (app('router')->getRoutes() as $route) {
            if (!str_starts_with($route->uri(), 'api/')) continue;
            $action = $route->getActionName();
            if (!str_contains($action, '@')) continue;
            [$class, $method] = explode('@', $action);
            $this->assertTrue(method_exists($class, $method), $action);
        }
        $controller = new \ReflectionClass(\App\Http\Controllers\Api\MobileApiController::class);
        $this->assertSame('student', $controller->getConstant('AI_STUDENT_ATTEMPT_TYPE'));
        $this->assertSame(50.0, $controller->getMethod('teacherAiPassingPercentage')->invoke($controller->newInstance()));
        $this->assertSame(60.0, $controller->getMethod('studentAiPassingPercentage')->invoke($controller->newInstance()));
        Sanctum::actingAs($this->user());
        $this->postJson('/api/engineer/assessments/1/start')->assertNotFound();
    }

    public function test_gap_admin_class_and_teacher_deletions_use_web_cleanup(): void
    {
        Storage::fake('local'); Storage::fake('public');
        $student = $this->student(); $other = $this->student(); $other->update(['section' => 'B']);
        $class = SchoolClass::create(['class_name' => 'Class 10', 'section' => 'A', 'institute' => 'Alpha']);
        DB::table('class_timetables')->insert(['class_id' => $class->id]);
        ClassContentSession::create(['class_id' => $class->id, 'institute' => 'Alpha', 'class' => 'Class 10', 'section' => 'A']);
        Sanctum::actingAs($this->user('Admin'));
        $this->deleteJson('/api/admin/classes/'.$class->id)->assertOk();
        $this->assertDatabaseMissing('students', ['id' => $student->id]);
        $this->assertDatabaseHas('students', ['id' => $other->id]);
        $this->assertDatabaseCount('class_timetables', 0);
        $this->assertDatabaseCount('class_content_sessions', 0);

        $teacher = $this->user();
        $assigned = SchoolClass::create(['institute' => 'Alpha', 'class_name' => 'Class 10', 'section' => 'B', 'class_teacher' => $teacher->name]);
        Storage::disk('local')->put('paper.pdf', 'paper');
        $paper = Assessment::create(['teacher_id' => $teacher->id, 'institute' => 'Alpha', 'file_path' => 'paper.pdf']);
        UserSession::create(['user_type' => 'Teacher', 'user_id' => $teacher->id]);
        $this->deleteJson('/api/admin/teachers/'.$teacher->id)->assertOk();
        $this->assertDatabaseMissing('users', ['id' => $teacher->id]);
        $this->assertDatabaseMissing('assessments', ['id' => $paper->id]);
        $this->assertDatabaseCount('user_sessions', 0);
        $this->assertNull($assigned->fresh()->class_teacher);
        Storage::disk('local')->assertMissing('paper.pdf');
    }

    public function test_gap_institute_delete_preserves_other_institutes_and_shared_content_files(): void
    {
        Storage::fake('local'); Storage::fake('public');
        $alpha = Institute::create(['institute_name' => 'Alpha']);
        $beta = Institute::create(['institute_name' => 'Beta']);
        Storage::disk('local')->put('shared.pdf', 'shared');
        Storage::disk('local')->put('alpha.pdf', 'private');
        Content::create(['institute' => 'Alpha', 'file_path' => 'shared.pdf']);
        Content::create(['institute' => 'Alpha', 'file_path' => 'alpha.pdf']);
        $shared = Content::create(['institute' => 'Beta', 'file_path' => 'shared.pdf']);
        $student = $this->student(); $teacher = $this->user();
        Sanctum::actingAs($this->user('InstituteAdmin'));
        $this->deleteJson('/api/admin/institutes/'.$alpha->id)->assertForbidden();
        Sanctum::actingAs($this->user('Admin'));
        $this->deleteJson('/api/admin/institutes/'.$alpha->id)->assertOk();
        $this->assertDatabaseMissing('institutes', ['id' => $alpha->id]);
        $this->assertDatabaseMissing('students', ['id' => $student->id]);
        $this->assertDatabaseMissing('users', ['id' => $teacher->id]);
        $this->assertDatabaseHas('institutes', ['id' => $beta->id]);
        $this->assertDatabaseHas('contents', ['id' => $shared->id]);
        Storage::disk('local')->assertExists('shared.pdf');
        Storage::disk('local')->assertMissing('alpha.pdf');
    }

    public function test_admin_can_update_institute_admin_details_with_institute(): void
    {
        $institute = Institute::create([
            'institute_id' => 'INS001',
            'institute_name' => 'Alpha',
            'location' => 'City',
            'contact_person' => 'Old Contact',
            'email' => 'alpha@example.com',
            'phone' => '111',
            'status' => 1,
        ]);
        $admin = User::create([
            'user_id' => 'ADM001',
            'name' => 'Old Admin',
            'email' => 'old-admin@example.com',
            'password' => Hash::make('OldPass123'),
            'role' => 'InstituteAdmin',
            'institute' => 'Alpha',
            'status' => 1,
        ]);

        Sanctum::actingAs($this->user('Admin'));
        $this->putJson('/api/admin/institutes/'.$institute->id, [
            'institute_id' => 'INS002',
            'institute_name' => 'Alpha Prime',
            'location' => 'New City',
            'contact_person' => 'New Contact',
            'email' => 'alpha-prime@example.com',
            'phone' => '222',
            'status' => false,
            'admin_name' => 'New Admin',
            'admin_email' => 'new-admin@example.com',
            'admin_password' => 'NewPass123',
        ])->assertOk();

        $this->assertDatabaseHas('institutes', [
            'id' => $institute->id,
            'institute_name' => 'Alpha Prime',
            'phone' => '222',
        ]);
        $admin->refresh();
        $this->assertSame('New Admin', $admin->name);
        $this->assertSame('new-admin@example.com', $admin->email);
        $this->assertSame('Alpha Prime', $admin->institute);
        $this->assertSame('0', (string) $admin->status);
        $this->assertTrue(Hash::check('NewPass123', $admin->password));
    }

    public function test_mobile_principal_management_matches_web_rules(): void
    {
        Institute::create(['institute_name' => 'Alpha', 'status' => 1]);
        Institute::create(['institute_name' => 'Beta', 'status' => 1]);
        $existing = User::create([
            'user_id' => 'PR0001',
            'name' => 'Alpha Principal',
            'email' => 'principal.alpha@example.com',
            'password' => Hash::make('Secret123'),
            'role' => 'Principal',
            'institute' => 'Alpha',
            'phone' => '111',
            'status' => 1,
        ]);

        Sanctum::actingAs($this->user('InstituteAdmin'));
        $this->getJson('/api/admin/principals')->assertForbidden();

        Sanctum::actingAs($this->user('Admin'));
        $this->getJson('/api/admin/principals?institute=Alpha')
            ->assertOk()
            ->assertJsonCount(1, 'principals')
            ->assertJsonPath('principals.0.email', 'principal.alpha@example.com')
            ->assertJsonPath('institutes.0', 'Alpha');

        $this->postJson('/api/admin/principals', [
            'name' => 'Duplicate Principal',
            'institute' => 'Alpha',
            'phone' => '222',
            'email' => 'duplicate@example.com',
            'password' => 'Secret123',
        ])->assertStatus(422);

        $createdId = $this->postJson('/api/admin/principals', [
            'name' => 'Beta Principal',
            'institute' => 'Beta',
            'phone' => '333',
            'email' => 'principal.beta@example.com',
            'password' => 'Secret123',
        ])->assertOk()->json('principal.id');

        $this->putJson('/api/admin/principals/'.$existing->id, [
            'name' => 'Alpha Principal Updated',
            'institute' => 'Alpha',
            'phone' => '444',
            'email' => 'principal.alpha.updated@example.com',
            'password' => '',
            'status' => false,
        ])->assertOk();

        $existing->refresh();
        $this->assertSame('Alpha Principal Updated', $existing->name);
        $this->assertSame('444', $existing->phone);
        $this->assertSame('principal.alpha.updated@example.com', $existing->email);
        $this->assertSame('0', (string) $existing->status);
        $this->assertTrue(Hash::check('Secret123', $existing->password));

        $this->deleteJson('/api/admin/principals/'.$createdId)->assertOk();
        $this->assertDatabaseMissing('users', ['id' => $createdId]);
    }

    public function test_gap_management_filter_controls_and_queries_match_selected_scope(): void
    {
        Institute::create(['institute_name' => 'Alpha', 'location' => 'City', 'status' => 1]);
        Institute::create(['institute_name' => 'Beta', 'location' => 'Town', 'status' => 1]);
        SchoolClass::create(['institute' => 'Alpha', 'class_name' => 'Class 10', 'section' => 'A', 'status' => 1]);
        SchoolClass::create(['institute' => 'Beta', 'class_name' => 'Class 9', 'section' => 'B', 'status' => 1]);
        $student = $this->student();
        $this->student()->update(['class' => 'Class 9', 'section' => 'B']);
        $this->user();
        Course::create(['course_title' => 'Circuits', 'institute' => 'Alpha', 'status' => 1]);
        Sanctum::actingAs($this->user('Admin'));
        foreach (['institutes', 'teachers', 'students', 'classes', 'courses'] as $area) {
            $this->getJson('/api/admin/management-filters/'.$area.'?institute=Alpha')->assertOk()->assertJsonStructure(['filters']);
        }
        $this->getJson('/api/admin/students?student_class=Class+10&student_section=A')->assertOk()->assertJsonCount(1, 'students')->assertJsonPath('students.0.id', $student->id);
        $this->getJson('/api/admin/classes?class_name=Class+10&section_name=A')->assertOk()->assertJsonCount(1, 'classes');
        $this->getJson('/api/admin/institutes?location=Town')->assertOk()->assertJsonCount(1, 'institutes');
        $this->getJson('/api/admin/courses?course_title=Circuits')->assertOk()->assertJsonCount(1, 'courses');
        $this->getJson('/api/admin/teachers?search=Alpha')->assertOk()->assertJsonCount(1, 'teachers');
        $this->getJson('/api/admin/students?search=Class+9')->assertOk()->assertJsonCount(1, 'students');
        $this->getJson('/api/admin/classes?search=Class+10')->assertOk()->assertJsonCount(1, 'classes');
        Sanctum::actingAs($this->user('InstituteAdmin'));
        $this->getJson('/api/admin/management-filters/students?institute=Beta')->assertOk()->assertJsonPath('filters.0.options.0.value', 'Class 10');
        $this->getJson('/api/admin/management-filters/institutes')->assertOk()->assertJsonCount(1, 'filters.0.options')->assertJsonPath('filters.0.options.0.value', 'City');
    }

    public function test_gap_student_export_all_and_selected_filters_match_the_mobile_roster(): void
    {
        SchoolClass::create(['institute' => 'Alpha', 'class_name' => 'Class 10', 'section' => 'A']);
        SchoolClass::create(['institute' => 'Alpha', 'class_name' => 'Class 9', 'section' => 'B']);
        $first = $this->student(); $first->update(['name' => 'First']);
        $second = $this->student(); $second->update(['name' => 'Second', 'class' => 'Class 9', 'section' => 'B']);
        Sanctum::actingAs($this->user());
        $url = $this->getJson('/api/workflows/students/export')->assertOk()->json('download_url');
        $csv = $this->get($url)->assertOk()->streamedContent();
        $this->assertStringContainsString('First', $csv);
        $this->assertStringContainsString('Second', $csv);
        $this->getJson('/api/workflows/students?search=Class+9')->assertOk()->assertJsonCount(1, 'records');
        $url = $this->getJson('/api/workflows/students/export?search=Class+9')->assertOk()->json('download_url');
        $csv = $this->get($url)->assertOk()->streamedContent();
        $this->assertStringNotContainsString('First', $csv);
        $this->assertStringContainsString('Second', $csv);
    }

    public function test_gap_signed_exports_stop_working_when_the_issuing_token_is_revoked(): void
    {
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id(); $table->morphs('tokenable'); $table->string('name');
            $table->string('token', 64)->unique(); $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable(); $table->timestamp('expires_at')->nullable(); $table->timestamps();
        });
        $teacher = $this->user();
        $token = $teacher->createToken('export-test')->accessToken;
        Sanctum::actingAs($teacher);
        $teacher->withAccessToken($token);
        $url = $this->getJson('/api/workflows/students/export')->assertOk()->json('download_url');
        $this->get($url)->assertOk();
        $token->delete();
        $this->get($url)->assertForbidden();
    }

    public function test_gap_admin_completes_only_the_requested_released_topic(): void
    {
        $this->app->forgetInstance(LmsNotificationService::class);
        $course = Course::create(['course_title' => 'Circuits', 'institute' => 'Alpha']);
        $content = Content::create(['course_id' => $course->id, 'content_title' => 'Circuit', 'file_path' => 'lesson.pdf', 'institute' => 'Alpha']);
        $plan = TeachingPlan::create(['course_id' => $course->id, 'institute' => 'Alpha', 'status' => 'active', 'is_template' => false]);
        $week = TeachingPlanWeek::create(['teaching_plan_id' => $plan->id, 'week_number' => 1, 'status' => 'released']);
        $first = \App\Models\TeachingPlanItem::create(['teaching_plan_id' => $plan->id, 'teaching_plan_week_id' => $week->id, 'content_id' => $content->id, 'status' => 'completed']);
        $item = \App\Models\TeachingPlanItem::create(['teaching_plan_id' => $plan->id, 'teaching_plan_week_id' => $week->id, 'content_id' => $content->id, 'status' => 'released']);
        ClassContentSession::create(['content_id' => $content->id, 'teaching_plan_item_id' => $first->id, 'status' => 'completed']);
        Sanctum::actingAs($this->user('InstituteAdmin'));
        $this->postJson('/api/admin/content/'.$content->id.'/complete', ['plan_item_id' => $item->id])->assertForbidden();
        $admin = $this->user('Admin'); $admin->update(['institute' => 'Head Office']); Sanctum::actingAs($admin);
        $this->postJson('/api/admin/content/'.$content->id.'/complete', ['plan_item_id' => $item->id])->assertOk();
        $this->assertSame('completed', $item->fresh()->status);
        $this->assertSame('Admin', $item->fresh()->completed_by_role);
        $this->assertTrue((bool) $content->fresh()->is_released);
        $week->update(['status' => 'locked']); $item->update(['status' => 'locked']);
        $this->postJson('/api/admin/content/'.$content->id.'/complete', ['plan_item_id' => $item->id])->assertForbidden();
    }

    public function test_gap_ai_prep_generates_quiz_and_student_review_obeys_assignment(): void
    {
        $teacher = $this->user(); $student = $this->student();
        $plan = TeachingPlan::create(['institute' => 'Alpha', 'class' => 'Class 10', 'section' => 'A', 'status' => 'active', 'is_template' => false, 'ai_training_start_date' => today()]);
        $content = Content::create(['content_title' => 'Circuits', 'assigned_class' => 'Class 10', 'institute' => 'Alpha', 'status' => 1]);
        $week = TeachingPlanWeek::create(['teaching_plan_id' => $plan->id, 'status' => 'released', 'release_date' => today()]);
        $item = \App\Models\TeachingPlanItem::create(['teaching_plan_id' => $plan->id, 'teaching_plan_week_id' => $week->id, 'content_id' => $content->id, 'status' => 'released']);
        \App\Models\AiContentSummary::create(['content_id' => $content->id, 'status' => 'generated', 'summary' => 'Electric circuits need a closed path.', 'key_points' => ['Current flows in a closed circuit.']]);
        Sanctum::actingAs($teacher);
        $this->getJson('/api/engineer/learning-content/'.$content->id.'/ai-prep')->assertOk()->assertJsonPath('passing_percentage', 50)->assertJsonStructure(['quiz' => ['questions']]);
        Sanctum::actingAs($student);
        $this->getJson('/api/student/content/'.$content->id.'/ai-review')->assertForbidden();
        $item->update(['status' => 'completed']);
        Cache::put('student_ai_review_unlock_'.$student->id.'_'.$content->id, true);
        $review = $this->getJson('/api/student/content/'.$content->id.'/ai-review')->assertOk();
        $answers = \App\Models\AiQuizQuestion::where('ai_quiz_id', $review->json('quiz.id'))->pluck('expected_answer', 'id')->all();
        $this->postJson('/api/student/content/'.$content->id.'/ai-review/quiz', ['answers' => $answers])->assertOk()->assertJsonPath('status', 'passed');
    }

    public function test_ai_prep_and_student_ai_quiz_enforce_preview_and_assessment_style_restrictions(): void
    {
        $teacher = $this->user(); $student = $this->student();
        $content = Content::create(['content_title' => 'Robotics', 'assigned_class' => 'Class 10', 'institute' => 'Alpha', 'status' => 1]);
        \App\Models\AiContentSummary::create(['content_id' => $content->id, 'status' => 'generated', 'summary' => 'Robots combine sensing, control, and movement.', 'key_points' => ['Sensors detect input.']]);

        Sanctum::actingAs($teacher);
        $this->getJson('/api/engineer/learning-content/'.$content->id.'/ai-prep')->assertForbidden();

        $plan = TeachingPlan::create(['institute' => 'Alpha', 'class' => 'Class 10', 'section' => 'A', 'status' => 'active', 'is_template' => false, 'ai_training_start_date' => today()]);
        $week = TeachingPlanWeek::create(['teaching_plan_id' => $plan->id, 'status' => 'released', 'release_date' => today()]);
        $item = TeachingPlanItem::create(['teaching_plan_id' => $plan->id, 'teaching_plan_week_id' => $week->id, 'content_id' => $content->id, 'status' => 'released']);

        $prep = $this->getJson('/api/engineer/learning-content/'.$content->id.'/ai-prep?item_id='.$item->id)->assertOk();
        $this->postJson('/api/engineer/learning-content/'.$content->id.'/ai-prep/quiz', [
            'item_id' => $item->id,
            'answers' => [],
        ])->assertUnprocessable();
        $this->postJson('/api/engineer/learning-content/'.$content->id.'/ai-prep/quiz', [
            'item_id' => $item->id,
            'answers' => [],
            'auto_submitted' => true,
        ])->assertOk()->assertJsonPath('status', 'failed')->assertJsonPath('auto_submitted', true);

        $item->update(['status' => 'completed']);
        Cache::put('student_ai_review_unlock_'.$student->id.'_'.$content->id, true);
        Sanctum::actingAs($student);
        $this->getJson('/api/student/content/'.$content->id.'/ai-review')->assertOk();
        $this->postJson('/api/student/content/'.$content->id.'/ai-review/quiz', [
            'answers' => [],
        ])->assertUnprocessable();
        $this->postJson('/api/student/content/'.$content->id.'/ai-review/quiz', [
            'answers' => [],
            'auto_submitted' => true,
        ])->assertOk()->assertJsonPath('status', 'failed')->assertJsonPath('auto_submitted', true);
    }

    public function test_mobile_component_mastery_matches_web_card_states(): void
    {
        $student = $this->student();
        $plan = TeachingPlan::create(['institute' => 'Alpha', 'class' => 'Class 10', 'section' => 'A', 'status' => 'active', 'is_template' => false]);
        $week = TeachingPlanWeek::create(['teaching_plan_id' => $plan->id, 'status' => 'released', 'release_date' => today()]);
        $contents = collect(range(1, 5))->map(function ($i) use ($student, $plan, $week) {
            $content = Content::create(['content_title' => "Robot build $i", 'assigned_class' => 'Class 10', 'institute' => 'Alpha', 'status' => 1]);
            TeachingPlanItem::create(['teaching_plan_id' => $plan->id, 'teaching_plan_week_id' => $week->id, 'content_id' => $content->id, 'status' => 'completed']);
            \App\Models\AiContentSummary::create(['content_id' => $content->id, 'status' => 'generated', 'summary' => 'Robot project with motor sensors and control.']);
            \App\Models\AiComponentContentProfile::create(['content_id' => $content->id, 'component_key' => 'robotics', 'component_label' => 'Robotics', 'is_practical' => 1, 'confidence' => 90]);
            \App\Models\AiQuizAttempt::create(['content_id' => $content->id, 'student_id' => $student->id, 'attempt_type' => 'student', 'status' => 'passed', 'percentage' => 80]);
            return $content;
        });

        $assessment = Assessment::create(['assessment_title' => 'Robotics Mastery', 'institute' => 'Alpha', 'assigned_class' => 'Class 10 A', 'assessment_category' => 'Component Mastery', 'component_key' => 'robotics', 'component_label' => 'Robotics', 'status' => 1, 'question_paper_status' => 'Approved', 'file_path' => 'paper.pdf', 'assessment_date' => today()]);
        Sanctum::actingAs($student);
        $this->getJson('/api/student/component-mastery')
            ->assertOk()
            ->assertJsonPath('assessments.0.component_label', 'Robotics')
            ->assertJsonPath('assessments.0.completed_practical_topics', 5)
            ->assertJsonPath('assessments.0.content_titles.0', $contents->first()->content_title)
            ->assertJsonPath('assessments.0.assessment_id', $assessment->id)
            ->assertJsonPath('assessments.0.can_take', true)
            ->assertJsonPath('assessments.0.can_prepare', false);

        AssessmentResult::create(['student_id' => $student->id, 'assessment_id' => $assessment->id, 'status' => 'Completed', 'percentage' => 88, 'passed' => 1]);
        $this->getJson('/api/student/component-mastery')
            ->assertOk()
            ->assertJsonPath('assessments.0.can_take', false)
            ->assertJsonPath('assessments.0.can_view_result', true)
            ->assertJsonPath('assessments.0.percentage', 88);
    }

    public function test_gap_session_completion_returns_celebration_only_for_manual_completion(): void
    {
        $teacher = $this->user(); Sanctum::actingAs($teacher);
        $session = ClassContentSession::create(['stem_engineer_id' => $teacher->id, 'institute' => 'Alpha', 'status' => 'in_progress', 'started_at' => now()->subMinutes(35)]);
        $this->postJson('/api/engineer/sessions/end/'.$session->id, ['status' => 'partially_completed'])->assertOk()->assertJsonPath('celebrate', true);
        $auto = ClassContentSession::create(['stem_engineer_id' => $teacher->id, 'institute' => 'Alpha', 'status' => 'in_progress', 'started_at' => now()->subMinutes(51)]);
        $this->postJson('/api/engineer/sessions/end/'.$auto->id, ['auto_ended' => true])->assertOk()->assertJsonPath('celebrate', false)->assertJsonPath('celebration_video_url', null);
        $this->get('/api/session-completion-video/clip.mp4')->assertForbidden();
    }
    private function user(string $role = 'Teacher'): User
    {
        return User::create(['name' => $role, 'email' => uniqid().'@example.com', 'password' => 'Correct123', 'role' => $role, 'institute' => 'Alpha', 'status' => 1]);
    }
    private function assessmentSession(Student $student, int $minutesAgo = 0): AssessmentSession
    {
        $assessment = Assessment::create(['assessment_title' => 'Exam', 'duration' => 30, 'total_marks' => 100, 'assessment_date' => today(), 'assigned_class' => 'Class 10 A', 'institute' => 'Alpha', 'status' => 1, 'question_paper_status' => 'Approved', 'file_path' => 'paper.pdf']);
        return AssessmentSession::create(['assessment_id' => $assessment->id, 'user_id' => $student->id, 'user_type' => 'Student', 'status' => 'Started', 'started_at' => now()->subMinutes($minutesAgo), 'violation_count' => 0]);
    }

    public function test_engineer_endpoints_reject_student_and_disabled_tokens(): void
    {
        $s = $this->student(); Sanctum::actingAs($s);
        foreach (['students','assessments','sessions','learning-content','profile'] as $path) $this->getJson('/api/engineer/'.$path)->assertForbidden();
        $this->getJson('/api/workflows/students')->assertForbidden();
        $s->update(['status' => 0]); Sanctum::actingAs($s->fresh());
        $this->getJson('/api/student/profile')->assertForbidden();
    }
    public function test_profile_uses_authenticated_student_not_shared_or_empty_email(): void
    {
        $other = $this->student(); $own = $this->student(); Sanctum::actingAs($own);
        $this->putJson('/api/student/profile', ['linkedin_url' => 'https://example.com/own'])->assertOk();
        $this->assertNull($other->fresh()->linkedin_url);
        $this->assertSame('https://example.com/own', $own->fresh()->linkedin_url);
    }
    public function test_institute_admin_role_is_preserved_and_hybrid_controls_denied(): void
    {
        Sanctum::actingAs($this->user('InstituteAdmin'));
        $this->getJson('/api/profile')->assertOk()->assertJsonPath('role', 'InstituteAdmin');
        $this->getJson('/api/workflows/hybrid-learners')->assertForbidden();
    }
    public function test_certificate_approval_uses_web_eligibility_and_issuance_rules(): void
    {
        $s = $this->student(); $admin = $this->user('Admin'); Sanctum::actingAs($admin);
        $c = Certificate::create(['student_id' => $s->id, 'status' => 'Pending', 'final_score' => 39]);
        $this->postJson('/api/admin/approvals/certificate/'.$c->id.'/approve')->assertUnprocessable();
        $c->update(['final_score' => 85]);
        $this->postJson('/api/admin/approvals/certificate/'.$c->id.'/approve')->assertOk();
        $this->assertSame('approved', $c->fresh()->status);
        $this->assertNotNull($c->fresh()->issued_date);
        $this->assertSame('A', $c->fresh()->final_grade);
        $c->update(['status' => 'Revoked']);
        $this->postJson('/api/workflows/certificates/approve/'.$c->id)->assertUnprocessable();
        $s->update(['institute' => 'Beta']); Sanctum::actingAs($this->user());
        $this->postJson('/api/workflows/certificates/approve/'.$c->id)->assertForbidden();
    }

    public function test_question_paper_workflow_paginates_filters_and_enforces_scope(): void
    {
        $teacher = $this->user();
        Institute::create(['institute_name' => 'Alpha', 'status' => 1]);
        Institute::create(['institute_name' => 'Beta', 'status' => 1]);
        SchoolClass::create(['institute' => 'Alpha', 'class_name' => 'Class 10', 'section' => 'A']);
        SchoolClass::create(['institute' => 'Beta', 'class_name' => 'Class 9', 'section' => 'B']);
        for ($i = 1; $i <= 26; $i++) {
            Assessment::create(['assessment_title' => "Paper $i", 'institute' => 'Alpha', 'assigned_class' => 'Class 10 A', 'teacher_id' => $teacher->id,
                'question_paper_status' => 'Pending Approval', 'file_path' => 'paper.pdf']);
        }
        $other = Assessment::create(['assessment_title' => 'Other paper', 'institute' => 'Beta', 'assigned_class' => 'Class 9 B', 'question_paper_status' => 'Approved', 'file_path' => 'other.pdf']);
        Assessment::create(['assessment_title' => 'No paper yet', 'institute' => 'Alpha']);
        Sanctum::actingAs($this->user('InstituteAdmin'));
        $response = $this->getJson('/api/workflows/question-papers?institute=Beta')->assertOk()->assertJsonCount(25, 'records')->assertJsonPath('pagination.total', 26);
        $this->assertSame(['question_class', 'question_status'], array_column($response->json('filters'), 'name'));
        $this->assertSame([['value' => 'Class 10 A', 'label' => 'Class 10 A']], $response->json('filters.0.options'));
        $this->getJson('/api/workflows/question-papers?page=2')->assertOk()->assertJsonCount(1, 'records');
        $this->getJson('/api/workflows/question-papers?question_status=Approved')->assertOk()->assertJsonCount(0, 'records');
        $this->getJson('/api/workflows/question-papers?question_class=Class+9+B')->assertOk()->assertJsonCount(0, 'records');
        $this->postJson('/api/workflows/question-papers/approve/'.$other->id)->assertForbidden();
        Sanctum::actingAs($this->user('Admin'));
        $this->getJson('/api/workflows/question-papers?institute=Beta&question_status=Approved')->assertOk()->assertJsonCount(1, 'records')->assertJsonPath('records.0.id', $other->id);
        Sanctum::actingAs($teacher);
        $this->getJson('/api/workflows/question-papers')->assertForbidden();
        $this->postJson('/api/workflows/question-papers/approve/1')->assertForbidden();
        Sanctum::actingAs($this->student());
        $this->getJson('/api/workflows/question-papers')->assertForbidden();
    }

    public function test_question_paper_preview_and_decisions_use_web_authorization_and_reviewer(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('paper.pdf', "%PDF-1.4\n%%EOF");
        $paper = Assessment::create(['assessment_title' => 'Circuit', 'institute' => 'Alpha', 'question_paper_status' => 'Pending Approval', 'file_path' => 'paper.pdf']);
        $admin = $this->user('InstituteAdmin');
        Sanctum::actingAs($admin);
        $this->get('/api/workflows/question-papers/'.$paper->id.'/document')->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->postJson('/api/workflows/question-papers/approve/'.$paper->id)->assertOk()->assertJsonPath('success', true);
        $this->assertSame('Approved', $paper->fresh()->question_paper_status);
        $this->assertEquals($admin->id, $paper->fresh()->question_paper_reviewed_by);
        $this->postJson('/api/workflows/question-papers/reject/'.$paper->id)->assertOk();
        $this->assertSame('Rejected', $paper->fresh()->question_paper_status);
        $paper->update(['institute' => 'Beta']);
        $this->getJson('/api/workflows/question-papers/'.$paper->id.'/document')->assertForbidden();
        Sanctum::actingAs($this->student());
        $this->getJson('/api/workflows/question-papers/'.$paper->id.'/document')->assertForbidden();
    }

    public function test_certificate_workflow_matches_web_filters_statuses_and_actions(): void
    {
        $student = $this->student();
        $other = $this->student();
        $other->update(['institute' => 'Beta', 'class' => 'Class 9', 'section' => 'B']);
        for ($i = 0; $i < 26; $i++) Certificate::create(['student_id' => $student->id, 'certificate_code' => "CERT-$i", 'status' => 'Pending Approval', 'final_score' => 85]);
        $legacy = Certificate::create(['student_id' => $student->id, 'certificate_code' => 'LEGACY', 'status' => 'pending_admin_approval', 'final_score' => 85]);
        Certificate::create(['student_id' => $other->id, 'certificate_code' => 'OTHER', 'status' => 'approved', 'final_score' => 85]);
        Sanctum::actingAs($this->user('InstituteAdmin'));
        $page = $this->getJson('/api/workflows/certificates?institute=Beta&certificate_status=Pending+Approval')->assertOk()->assertJsonPath('pagination.total', 26)->assertJsonCount(25, 'records');
        $this->assertSame(['student_class', 'student_section', 'certificate_status'], array_column($page->json('filters'), 'name'));
        $this->assertSame(['approve', 'revoke'], array_column($page->json('records.0.actions'), 'id'));
        $this->getJson('/api/workflows/certificates?certificate_status=Pending+Approval&page=2')->assertOk()->assertJsonCount(1, 'records');
        $this->getJson('/api/workflows/certificates?student_class=Class+9')->assertOk()->assertJsonCount(0, 'records');
        $this->getJson('/api/workflows/certificates?student_section=B')->assertOk()->assertJsonCount(0, 'records');
        $this->getJson('/api/workflows/certificates?certificate_status=pending_admin_approval')->assertOk()->assertJsonPath('records.0.id', $legacy->id);
        $this->postJson('/api/workflows/certificates/approve/'.$legacy->id)->assertOk();
        $approved = $this->getJson('/api/workflows/certificates?certificate_status=approved')->assertOk()->assertJsonCount(1, 'records');
        $this->assertSame(['revoke'], array_column($approved->json('records.0.actions'), 'id'));
        $this->postJson('/api/workflows/certificates/revoke/'.$legacy->id)->assertOk();
        $revoked = $this->getJson('/api/workflows/certificates?certificate_status=Revoked')->assertOk();
        $this->assertSame(['reissue'], array_column($revoked->json('records.0.actions'), 'id'));
        Sanctum::actingAs($this->user('Admin'));
        $this->getJson('/api/workflows/certificates?institute=Beta&student_class=Class+9&student_section=B')->assertOk()->assertJsonCount(1, 'records')->assertJsonPath('records.0.title', $other->name);
        Sanctum::actingAs($student);
        $this->getJson('/api/workflows/certificates')->assertForbidden();
    }
    public function test_releases_respect_start_date_and_previous_week_policy(): void
    {
        $p = TeachingPlan::create(['status' => 'active', 'is_template' => false, 'start_date' => today()->addDay(), 'release_policy' => 'release_next_only_if_previous_completed']);
        $first = TeachingPlanWeek::create(['teaching_plan_id' => $p->id, 'week_number' => 1, 'status' => 'locked', 'release_date' => today()]);
        $second = TeachingPlanWeek::create(['teaching_plan_id' => $p->id, 'week_number' => 2, 'status' => 'locked', 'release_date' => today()]);
        $service = app(TeachingPlanReleaseService::class);
        $this->assertFalse($service->releaseWeek($first));
        $p->update(['start_date' => today()]);
        $this->assertFalse($service->releaseWeek($second));
        $first->update(['status' => 'completed']);
        $this->assertTrue($service->releaseWeek($second));
    }

    public function test_mobile_session_reports_use_session_metrics_and_web_date_boundaries(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-09-09 12:00:00'));
        Sanctum::actingAs($this->user('InstituteAdmin'));
        foreach (['completed', 'partially_completed', 'cancelled', 'in_progress'] as $status) {
            ClassContentSession::create(['institute' => 'Alpha', 'class' => 'Class 10', 'section' => 'A',
                'session_date' => '2026-09-09', 'planned_topic' => 'Robotics', 'status' => $status,
                'duration_seconds' => 1800, 'ended_at' => $status === 'in_progress' ? null : now()]);
        }
        ClassContentSession::create(['institute' => 'Alpha', 'class' => 'Class 10', 'session_date' => '2026-08-01', 'status' => 'completed', 'ended_at' => now()]);
        ClassContentSession::create(['institute' => 'Beta', 'session_date' => '2026-09-09', 'status' => 'completed']);
        $daily = $this->getJson('/api/admin/reports?report_mode=daily-session&institute=Beta')
            ->assertOk()->assertJsonPath('report_schema_version', 2)->assertJsonPath('report_mode', 'daily-session')
            ->assertJsonPath('title', 'Daily Session Report')->assertJsonPath('scope', 'Alpha')
            ->assertJsonPath('metrics.total_sessions', 4)->assertJsonPath('metrics.completed_sessions', 1)
            ->assertJsonPath('metrics.partially_completed_sessions', 1)->assertJsonPath('metrics.cancelled_or_skipped_sessions', 1)
            ->assertJsonPath('metrics.unfinished_sessions', 1)->assertJsonPath('metrics.teaching_hours', 2)
            ->assertJsonCount(4, 'table_rows')->assertJsonPath('table_headers.3', 'Planned Content');
        $this->assertArrayNotHasKey('assessment_results', $daily->json('metrics'));
        $this->getJson('/api/admin/reports?report_mode=weekly-session')->assertOk()->assertJsonPath('metrics.total_sessions', 5);
        $this->getJson('/api/admin/reports?report_mode=monthly-session&report_month=2026-09')->assertOk()->assertJsonPath('metrics.total_sessions', 4);
        $this->getJson('/api/admin/reports?report_mode=weekly-session&from_date=2026-09-01')->assertOk()->assertJsonPath('metrics.total_sessions', 4);
        $this->getJson('/api/admin/reports?report_mode=weekly-session&from_date=2026-09-09&to_date=2026-09-01')->assertUnprocessable();
    }

    public function test_mobile_report_filters_and_panel_roles_keep_the_correct_scope(): void
    {
        $student = $this->student();
        $other = $this->student();
        $other->update(['institute' => 'Beta', 'class' => 'Class 99']);
        Sanctum::actingAs($this->user('InstituteAdmin'));
        $this->getJson('/api/admin/reports?filters_only=1&institute=Beta')
            ->assertOk()->assertJsonCount(1, 'classes')->assertJsonPath('classes.0.institute', 'Alpha');
        ClassContentSession::create(['institute' => 'Alpha', 'session_date' => today(), 'status' => 'completed']);
        ClassContentSession::create(['institute' => 'Beta', 'session_date' => today(), 'status' => 'completed']);
        Sanctum::actingAs($this->user('Principal'));
        $this->getJson('/api/principal/reports?report_mode=daily-session&institute=Beta')
            ->assertOk()->assertJsonPath('scope', 'Alpha')->assertJsonPath('metrics.total_sessions', 1);
        $this->getJson('/api/principal/reports?report_mode=weekly-stem-engineer-performance')->assertUnprocessable();
        Sanctum::actingAs($this->user('Manager'));
        $this->getJson('/api/manager/reports?report_mode=daily-session&institute=Beta')
            ->assertOk()->assertJsonPath('scope', 'Beta')->assertJsonPath('metrics.total_sessions', 1);
        $this->getJson('/api/manager/reports?report_mode=daily-student-performance')->assertUnprocessable();
    }

    public function test_mobile_reports_select_metrics_and_rows_for_each_report_family(): void
    {
        Sanctum::actingAs($this->user('InstituteAdmin'));
        $student = $this->student();
        SchoolClass::create(['institute' => 'Alpha', 'class_name' => $student->class, 'section' => $student->section]);
        $teacher = $this->user('STEM Engineer');
        ClassContentSession::create(['institute' => 'Alpha', 'class' => $student->class, 'section' => $student->section,
            'stem_engineer_id' => $teacher->id, 'session_date' => today(), 'status' => 'partially_completed', 'duration_seconds' => 1800]);
        TeachingPlan::create(['institute' => 'Alpha', 'class' => $student->class, 'section' => $student->section, 'status' => 'active']);
        DB::table('ai_quiz_attempts')->insert(['student_id' => $student->id, 'attempt_type' => 'student', 'status' => 'passed', 'percentage' => 80, 'submitted_at' => now()]);
        DB::table('ai_quiz_attempts')->insert(['teacher_id' => $teacher->id, 'attempt_type' => 'teacher_prep', 'status' => 'failed', 'percentage' => 40, 'submitted_at' => now()]);
        foreach (['student-ai-review', 'daily-student-performance', 'weekly-student-performance', 'monthly-student-performance'] as $mode) {
            $response = $this->getJson('/api/admin/reports?report_mode='.$mode)->assertOk()
                ->assertJsonPath('metrics.student_ai_attempts', 1)->assertJsonPath('metrics.student_ai_passed', 1)
                ->assertJsonPath('table_headers.3', 'Sessions')->assertJsonPath('table_rows.0.3', 1)
                ->assertJsonPath('table_rows.0.4', 1);
            $this->assertArrayNotHasKey('stem_engineer_prep_attempts', $response->json('metrics'));
        }
        foreach (['stem-engineer-prep', 'weekly-stem-engineer-performance', 'monthly-stem-engineer-performance'] as $mode) {
            $response = $this->getJson('/api/admin/reports?report_mode='.$mode)->assertOk()
                ->assertJsonPath('metrics.stem_engineer_prep_attempts', 1)->assertJsonPath('table_headers.4', 'Partial');
            $this->assertArrayNotHasKey('student_ai_attempts', $response->json('metrics'));
        }
    }

    public function test_admin_week_actions_override_schedule_and_keep_items_in_sync(): void
    {
        $admin = $this->user('InstituteAdmin');
        Sanctum::actingAs($admin);
        $plan = TeachingPlan::create(['institute' => 'Alpha', 'status' => 'active', 'is_template' => false,
            'start_date' => today()->addMonth(), 'release_policy' => 'release_next_only_if_previous_completed']);
        TeachingPlanWeek::create(['teaching_plan_id' => $plan->id, 'week_number' => 1, 'status' => 'locked']);
        $week = TeachingPlanWeek::create(['teaching_plan_id' => $plan->id, 'week_number' => 2, 'status' => 'locked', 'release_date' => today()]);
        $content = Content::create(['content_title' => 'Override lesson', 'status' => 1, 'is_released' => false]);
        $item = TeachingPlanItem::create(['teaching_plan_id' => $plan->id, 'teaching_plan_week_id' => $week->id,
            'content_id' => $content->id, 'status' => 'locked']);
        $path = '/api/admin/teaching-plans/'.$plan->id.'/weeks/'.$week->id;

        foreach (['released', 'completed', 'released', 'skipped', 'locked'] as $status) {
            $this->putJson($path, ['status' => $status])->assertOk();
            $this->assertSame($status, $week->fresh()->status);
            $this->assertSame($status, $item->fresh()->status);
            $this->assertSame($status === 'completed', (bool) $content->fresh()->is_released);
        }
        $plan->update(['start_date' => today(), 'release_policy' => 'scheduled_weekly_release']);
        app(TeachingPlanReleaseService::class)->releaseDueWeek($plan);
        $this->assertSame('locked', $week->fresh()->status);

        $outsider = $this->user('InstituteAdmin');
        $outsider->update(['institute' => 'Beta']);
        Sanctum::actingAs($outsider);
        $this->putJson($path, ['status' => 'released'])->assertForbidden();
        $this->assertSame('locked', $week->fresh()->status);
    }

    public function test_engineer_learning_content_shows_manually_released_next_week(): void
    {
        $teacher = $this->user('STEM Engineer');
        $course = Course::create(['course_title' => 'STEM Course', 'institute' => 'Alpha', 'status' => 1]);
        $plan = TeachingPlan::create([
            'course_id' => $course->id,
            'institute' => 'Alpha',
            'class' => 'Class 10',
            'section' => 'A',
            'status' => 'active',
            'is_template' => false,
            'start_date' => today(),
        ]);
        $week = TeachingPlanWeek::create([
            'teaching_plan_id' => $plan->id,
            'week_number' => 2,
            'status' => 'locked',
            'release_date' => today()->addWeek(),
            'week_start_date' => today()->addWeek(),
            'week_end_date' => today()->addWeek()->addDays(6),
        ]);
        $content = Content::create([
            'course_id' => $course->id,
            'content_title' => 'Next Week Robotics',
            'institute' => 'Alpha',
            'status' => 1,
            'file_path' => 'robotics.pdf',
        ]);
        TeachingPlanItem::create([
            'teaching_plan_id' => $plan->id,
            'teaching_plan_week_id' => $week->id,
            'content_id' => $content->id,
            'status' => 'locked',
            'sort_order' => 1,
        ]);

        $this->assertNotNull(app(TeachingPlanReleaseService::class)->releaseNextWeek($plan));

        Sanctum::actingAs($teacher);
        $this->getJson('/api/engineer/learning-content')
            ->assertOk()
            ->assertJsonPath('lessons.0.content_id', (string) $content->id)
            ->assertJsonPath('lessons.0.title', 'Next Week Robotics')
            ->assertJsonPath('lessons.0.status', 'released');
    }

    public function test_engineer_sessions_content_includes_multiple_released_weeks(): void
    {
        Sanctum::actingAs($this->user('STEM Engineer'));
        $class = SchoolClass::create(['class_name' => 'Class 10', 'section' => 'A', 'institute' => 'Alpha', 'status' => 1]);
        $course = Course::create(['course_title' => 'Robotics', 'institute' => 'Alpha', 'status' => 1]);
        $plan = TeachingPlan::create([
            'course_id' => $course->id, 'institute' => 'Alpha', 'class' => 'Class 10',
            'section' => 'A', 'status' => 'active', 'is_template' => false,
            'start_date' => today(), 'release_policy' => 'scheduled_weekly_release',
        ]);
        $expectedIds = [];
        for ($number = 1; $number <= 4; $number++) {
            $start = today()->startOfWeek()->addWeeks($number - 1);
            $week = TeachingPlanWeek::create([
                'teaching_plan_id' => $plan->id, 'week_number' => $number, 'status' => 'locked',
                'release_date' => $start, 'week_start_date' => $start, 'week_end_date' => $start->copy()->addDays(6),
            ]);
            for ($order = 1; $order <= 2; $order++) {
                $content = Content::create(['course_id' => $course->id, 'content_title' => "Week $number Lesson $order", 'status' => 1]);
                TeachingPlanItem::create([
                    'teaching_plan_id' => $plan->id, 'teaching_plan_week_id' => $week->id,
                    'content_id' => $content->id, 'status' => 'locked', 'sort_order' => $order,
                ]);
                if ($number <= 3) $expectedIds[] = $content->id;
            }
        }
        for ($number = 1; $number <= 3; $number++) {
            $this->assertNotNull(app(TeachingPlanReleaseService::class)->releaseNextWeek($plan));
        }

        $response = $this->getJson('/api/engineer/sessions?class_id='.$class->id)
            ->assertOk()->assertJsonCount(6, 'learning_content')
            ->assertJsonPath('pagination.learning_content.total', 6);
        $this->assertEquals($expectedIds, array_column($response->json('learning_content'), 'content_id'));

        $otherClass = SchoolClass::create(['class_name' => 'Class 9', 'section' => 'A', 'institute' => 'Alpha', 'status' => 1]);
        $this->getJson('/api/engineer/sessions?class_id='.$otherClass->id)
            ->assertOk()->assertJsonCount(0, 'learning_content');
    }

    public function test_student_pdf_does_not_fall_back_to_teacher_version_when_student_pdf_exists(): void
    {
        $content = new Content(['preview_pdf_path' => 'teacher.pdf', 'student_file_path' => 'student.pdf']);
        $this->assertSame('student.pdf', app(MobileContentAccess::class)->pdfPath($content, 'student'));
        $this->assertSame('teacher.pdf', app(MobileContentAccess::class)->pdfPath($content, 'teacher'));
    }

    public function test_mobile_media_previews_support_teacher_formats_and_byte_ranges(): void
    {
        Storage::fake('local');
        $teacher = $this->user();
        Sanctum::actingAs($teacher);
        $plan = TeachingPlan::create(['institute' => 'Alpha', 'status' => 'active', 'is_template' => false]);
        foreach (['png' => 'image', 'mp4' => 'video', 'mp3' => 'audio', 'pdf' => 'document'] as $extension => $kind) {
            $path = 'lessons/lesson.'.$extension;
            Storage::disk('local')->put($path, '0123456789');
            $content = Content::create(['content_title' => 'Lesson', 'institute' => 'Alpha', 'status' => 1, 'file_path' => $path]);
            \App\Models\TeachingPlanItem::create(['teaching_plan_id' => $plan->id, 'content_id' => $content->id, 'status' => 'released']);
            $url = $this->getJson('/api/engineer/learning-content/'.$content->id.'/preview')
                ->assertOk()->assertJsonPath('preview_kind', $kind)->json('preview_url');
            $this->get($url, ['Range' => 'bytes=2-5'])->assertStatus(206)
                ->assertHeader('Content-Range', 'bytes 2-5/10')->assertHeader('X-Content-Type-Options', 'nosniff');
        }
    }

    public function test_student_media_uses_student_material_and_rejects_unreleased_content(): void
    {
        Storage::fake('local');
        $student = $this->student();
        Sanctum::actingAs($student);
        $course = Course::create(['course_title' => 'Course', 'institute' => 'Alpha', 'assigned_class' => 'Class 10 A']);
        $content = Content::create(['content_title' => 'Lesson', 'institute' => 'Alpha', 'course_id' => $course->id, 'status' => 1, 'is_released' => true,
            'preview_pdf_path' => 'teacher.pdf', 'file_path' => 'teacher.pdf', 'student_file_path' => 'student.mp4']);
        Storage::disk('local')->put('student.mp4', '0123456789');
        $url = $this->getJson('/api/student/content/'.$content->id.'/preview')->assertOk()->assertJsonPath('extension', 'mp4')->json('preview_url');
        $this->get($url)->assertOk()->assertHeader('Content-Type', 'video/mp4');
        $content->update(['is_released' => false]);
        $this->get($url)->assertForbidden();
        $this->getJson('/api/student/content/'.$content->id.'/preview')->assertForbidden();
    }

    public function test_media_links_expire_and_cannot_be_reused_for_another_account_or_file(): void
    {
        Storage::fake('local');
        $teacher = $this->user(); Sanctum::actingAs($teacher);
        $plan = TeachingPlan::create(['institute' => 'Alpha', 'status' => 'active', 'is_template' => false]);
        $content = Content::create(['content_title' => 'Lesson', 'institute' => 'Alpha', 'status' => 1, 'file_path' => 'lesson.mp4']);
        Storage::disk('local')->put('lesson.mp4', 'video');
        \App\Models\TeachingPlanItem::create(['teaching_plan_id' => $plan->id, 'content_id' => $content->id, 'status' => 'released']);
        $url = $this->getJson('/api/engineer/learning-content/'.$content->id.'/preview')->assertOk()->json('preview_url');
        $this->get($url.'&account_id=999')->assertForbidden();
        $teacher->update(['status' => 0]);
        $this->get($url)->assertForbidden();
        $teacher->update(['status' => 1]);
        $this->travel(11)->minutes();
        $this->get($url)->assertForbidden();
    }

    public function test_teaching_plan_list_pages_filters_and_keeps_institute_scope(): void
    {
        $admin = $this->user('InstituteAdmin'); Sanctum::actingAs($admin);
        Institute::create(['institute_name' => 'Alpha', 'status' => 1]);
        Institute::create(['institute_name' => 'Beta', 'status' => 1]);
        for ($i = 0; $i < 51; $i++) TeachingPlan::create(['title' => 'Plan '.$i, 'institute' => 'Alpha', 'class' => 'Class 10', 'section' => 'A', 'status' => 'active', 'is_template' => false]);
        TeachingPlan::create(['title' => 'Other institute', 'institute' => 'Beta', 'class' => 'Class 9', 'status' => 'active']);
        TeachingPlan::create(['title' => 'Inactive', 'institute' => 'Alpha', 'class' => 'Class 8', 'status' => 'inactive']);
        $this->getJson('/api/admin/teaching-plans?status=active&plan_class=Class%2010&plan_section=A&page=3')
            ->assertOk()->assertJsonCount(1, 'plans')->assertJsonPath('pagination.total', 51)->assertJsonPath('pagination.last_page', 3)
            ->assertJsonPath('institutes', ['Alpha']);
        $this->getJson('/api/admin/teaching-plans?institute=Beta')->assertOk()->assertJsonCount(0, 'plans')->assertJsonCount(0, 'classes');
        $this->getJson('/api/admin/teaching-plans?search=missing')->assertOk()->assertJsonPath('pagination.total', 0);
    }
    public function test_exam_owner_draft_deadline_and_idempotent_submission(): void
    {
        $s = $this->student(); $session = $this->assessmentSession($s); Sanctum::actingAs($s);
        $this->postJson('/api/student/assessment-sessions/'.$session->id.'/draft', ['answer_text' => 'Saved before deadline'])->assertOk();
        $this->getJson('/api/student/assessment-sessions/'.$session->id)->assertOk()->assertJsonPath('answer_text', 'Saved before deadline');
        Sanctum::actingAs($this->student());
        $this->postJson('/api/student/assessment-sessions/'.$session->id.'/violation')->assertNotFound();
        Sanctum::actingAs($s); $this->travel(31)->minutes();
        $service = app(MobileAssessmentService::class);
        $one = $service->submit($session, 'Late changed answer');
        $two = $service->submit($session, 'Another answer');
        $this->assertSame($one->id, $two->id);
        $this->assertSame('Saved before deadline', $one->answer_text);
        $this->assertSame('AutoSubmitted', $session->fresh()->status);
    }
    public function test_three_violations_persist_a_result_not_only_session_status(): void
    {
        $s = $this->student(); $session = $this->assessmentSession($s); Sanctum::actingAs($s);
        for ($i = 1; $i <= 3; $i++) $this->postJson('/api/student/assessment-sessions/'.$session->id.'/violation', ['answer_text' => 'Current answer'])->assertOk()->assertJsonPath('violation_count', $i)->assertJsonPath('auto_submit', $i === 3);
        $this->assertSame('Current answer', AssessmentResult::firstOrFail()->answer_text);
        $this->assertSame('AutoSubmitted', $session->fresh()->status);
    }
    public function test_engineer_roster_paginates_and_never_exposes_passwords(): void
    {
        Sanctum::actingAs($this->user());
        for ($i = 0; $i < 30; $i++) $this->student();
        $other = $this->student(); $other->update(['institute' => 'Beta']);
        $this->getJson('/api/workflows/students')->assertOk()->assertJsonCount(25, 'records')->assertJsonPath('pagination.total', 30)->assertJsonMissing(['password' => 'Correct123']);
        $this->getJson('/api/workflows/students?page=2')->assertOk()->assertJsonCount(5, 'records');
    }
    public function test_engineer_session_restores_and_expires_after_fifty_minutes(): void
    {
        $teacher = $this->user(); Sanctum::actingAs($teacher);
        $s = ClassContentSession::create(['stem_engineer_id' => $teacher->id, 'institute' => 'Alpha', 'teaching_plan_item_id' => 1, 'status' => 'in_progress', 'started_at' => now()->subMinutes(20)]);
        $this->getJson('/api/engineer/sessions/state?item_id=1')->assertOk()->assertJsonPath('active.session_id', $s->id);
        $this->travel(31)->minutes();
        $this->getJson('/api/engineer/sessions/state?item_id=1')->assertOk()->assertJsonPath('active', null);
        $this->assertSame('partially_completed', $s->fresh()->status);
        $this->assertEquals(3000, $s->fresh()->duration_seconds);
    }

    public function test_workflow_forms_and_filters_are_scoped_and_return_edit_values(): void
    {
        $teacher = $this->user(); Sanctum::actingAs($teacher);
        $student = $this->student();
        SchoolClass::create(['institute' => 'Alpha', 'class_name' => 'Class 10', 'section' => 'A', 'status' => 1]);
        $session = $this->assessmentSession($student);
        $session->assessment->update(['teacher_id' => $teacher->id, 'assessment_category' => 'Monthly']);
        $this->getJson('/api/workflows/assessments')->assertOk()->assertJsonCount(1, 'records')->assertJsonPath('records.0.actions.0.values.assessment_type', null);
        $this->getJson('/api/workflows/assessments?search=missing')->assertOk()->assertJsonCount(0, 'records');
        $this->getJson('/api/workflows/assessments?assessment_category=Annual')->assertOk()->assertJsonCount(0, 'records');
        $this->getJson('/api/workflows/students?status=0')->assertOk()->assertJsonCount(0, 'records');
        AssessmentResult::create(['assessment_id' => $session->assessment_id, 'student_id' => $student->id, 'badge' => 'Gold', 'status' => 'Completed']);
        $this->getJson('/api/workflows/results')->assertOk()->assertJsonCount(1, 'records');
        $this->getJson('/api/workflows/results?badge=Silver')->assertOk()->assertJsonCount(0, 'records');
        $this->getJson('/api/workflows/profile')->assertOk()->assertJsonPath('records.0.id', $teacher->id);
        Sanctum::actingAs($this->user('InstituteAdmin'));
        Course::create(['course_title' => 'Alpha course', 'institute' => 'Alpha', 'status' => 1]);
        Course::create(['course_title' => 'Beta course', 'institute' => 'Beta', 'status' => 1]);
        $plan = TeachingPlan::create([
            'title' => 'Alpha plan',
            'institute' => 'Alpha',
            'status' => 'active',
            'is_template' => 0,
            'start_date' => '2026-09-11',
            'release_day' => 'Friday',
        ]);
        TeachingPlan::create(['title' => 'Beta plan', 'institute' => 'Beta', 'status' => 'active', 'is_template' => 0]);
        $this->getJson('/api/workflows/teaching-plans')->assertOk()->assertJsonCount(1, 'records')->assertJsonPath('records.0.title', 'Alpha plan')->assertJsonCount(1, 'actions.0.fields.0.options')->assertJsonPath('records.0.actions.0.values.start_date', '2026-09-11')->assertJsonPath('records.0.actions.0.values.release_day', 'Friday');
        $this->getJson('/api/admin/teaching-plans/'.$plan->id)->assertOk()->assertJsonPath('plan.plan_title', 'Alpha plan')->assertJsonPath('plan.start_date', '2026-09-11')->assertJsonPath('plan.release_day', 'Friday');
        Sanctum::actingAs($student);
        $this->getJson('/api/workflows/badges')->assertOk()->assertJsonCount(1, 'records')->assertJsonPath('records.0.status', 'Gold Badge');
        Sanctum::actingAs($this->student());
        $this->getJson('/api/workflows/badges')->assertOk()->assertJsonCount(0, 'records');
    }

    public function test_monitoring_bounds_elapsed_time_and_stopped_logs_cannot_accrue_time(): void
    {
        $student = $this->student(); Sanctum::actingAs($student);
        $id = $this->postJson('/api/activity', ['action' => 'start', 'section' => 'Learning Content Preview'])->assertOk()->json('id');
        $this->travel(30)->seconds();
        $this->postJson('/api/activity', ['action' => 'heartbeat', 'id' => $id])->assertOk();
        $this->assertEquals(30, UserActivityLog::findOrFail($id)->duration_seconds);
        $this->travel(5)->minutes();
        $this->postJson('/api/activity', ['action' => 'stop', 'id' => $id])->assertOk();
        $this->assertEquals(75, UserActivityLog::findOrFail($id)->duration_seconds);
        $this->travel(30)->seconds();
        $this->postJson('/api/activity', ['action' => 'heartbeat', 'id' => $id])->assertOk()->assertJsonPath('active', false);
        $this->assertEquals(75, UserSession::firstOrFail()->total_duration_seconds);
        Sanctum::actingAs($this->student());
        $this->postJson('/api/activity', ['action' => 'heartbeat', 'id' => $id])->assertNotFound();
    }

    public function test_content_scope_and_sequence_apply_to_ai_context_and_previews(): void
    {
        $student = $this->student();
        $course = Course::create(['course_title' => 'Lessons', 'institute' => 'Alpha', 'assigned_class' => 'Class 10 A', 'status' => 1]);
        $first = Content::create(['content_title' => 'First', 'course_id' => $course->id, 'lesson_order' => 1, 'status' => 1, 'is_released' => true]);
        $second = Content::create(['content_title' => 'Second', 'course_id' => $course->id, 'lesson_order' => 4, 'status' => 1, 'is_released' => true]);
        $hidden = Content::create(['content_title' => 'Unreleased', 'course_id' => $course->id, 'lesson_order' => 5, 'status' => 1, 'is_released' => false]);
        $access = app(MobileContentAccess::class);
        $this->assertEquals([$first->id], $access->unlockedIds($student)->all());
        LessonProgress::create(['student_id' => $student->id, 'content_id' => $first->id, 'is_completed' => true]);
        $this->assertEquals([$first->id, $second->id], $access->unlockedIds($student)->all());
        $this->assertFalse($access->availableIds($student)->contains($hidden->id));
        $student->update(['section' => 'B']);
        $this->assertTrue($access->availableIds($student)->isEmpty());
    }

    public function test_web_assessment_updates_cannot_target_another_student(): void
    {
        $own = $this->student(); $other = $this->student(); $session = $this->assessmentSession($other);
        $this->withSession(['student_id' => $own->id])->postJson('/assessment-session/violation/'.$session->id)->assertNotFound();
        $this->withSession(['student_id' => $own->id])->postJson('/assessment-session/submit/'.$session->id)->assertNotFound();
        $this->assertEquals(0, $session->fresh()->violation_count);
        $this->assertSame('Started', $session->fresh()->status);
    }

    public function test_daily_release_check_does_not_release_future_weeks(): void
    {
        $plan = TeachingPlan::create(['institute' => 'Alpha', 'status' => 'active', 'is_template' => 0, 'start_date' => today(), 'release_policy' => 'scheduled_weekly_release']);
        $due = TeachingPlanWeek::create(['teaching_plan_id' => $plan->id, 'week_number' => 1, 'status' => 'locked', 'release_date' => today()]);
        $future = TeachingPlanWeek::create(['teaching_plan_id' => $plan->id, 'week_number' => 2, 'status' => 'locked', 'release_date' => today()->addDays(3)]);
        $this->assertEquals(1, app(TeachingPlanReleaseService::class)->runFridayRelease(now()));
        $this->assertSame('released', $due->fresh()->status);
        $this->assertSame('locked', $future->fresh()->status);
    }

    public function test_community_submission_moderation_comments_and_search_use_token_identity(): void
    {
        $student = $this->student(); Sanctum::actingAs($student);
        session()->put(['user_role' => 'Admin', 'user_id' => 999]);
        $this->postJson('/api/workflows/community/create', ['title' => 'Lab project', 'body' => 'Progress update', 'post_type' => 'Project Update'])->assertOk();
        $post = \App\Models\CommunityPost::firstOrFail();
        $this->assertSame('Pending', $post->status);
        $this->assertSame('Student', $post->author_type);
        $this->assertEquals($student->id, $post->author_id);
        $this->assertSame('Admin', session('user_role'));
        $this->getJson('/api/workflows/community?tab=profile')->assertOk()->assertJsonCount(1, 'records');
        $this->postJson('/api/workflows/community/approve/'.$post->id)->assertForbidden();
        Sanctum::actingAs($this->user());
        $this->postJson('/api/workflows/community/approve/'.$post->id)->assertOk();
        $this->getJson('/api/workflows/community?search=missing')->assertOk()->assertJsonCount(0, 'records');
        Sanctum::actingAs($student);
        $this->postJson('/api/workflows/community/comment/'.$post->id, ['body' => 'Thanks'])->assertOk();
        $this->postJson('/api/workflows/community/like/'.$post->id)->assertOk();
        $this->getJson('/api/workflows/community')->assertOk()->assertJsonCount(1, 'records.0.comments')->assertJsonPath('records.0.details.likes', 1);
        $other = $this->user(); $other->update(['institute' => 'Beta']); Sanctum::actingAs($other);
        $this->getJson('/api/workflows/community')->assertOk()->assertJsonCount(0, 'records');
        $this->postJson('/api/workflows/community/reject/'.$post->id)->assertForbidden();
    }

    public function test_notification_targeting_is_not_taken_from_a_stale_web_session(): void
    {
        $admin = $this->user('InstituteAdmin'); Sanctum::actingAs($admin);
        session()->put(['user_role' => 'Admin', 'user_institute' => 'Beta']);
        $body = ['title' => 'Notice', 'message' => 'Class update', 'target' => 'students', 'status' => 'active'];
        $this->postJson('/api/admin/notifications', $body + ['institute' => 'Beta'])->assertUnprocessable();
        $this->postJson('/api/admin/notifications', $body)->assertOk()->assertJsonPath('notification.institute', 'Alpha');
        Sanctum::actingAs($this->user('Admin'));
        $this->postJson('/api/admin/notifications', $body)->assertOk()->assertJsonPath('notification.institute', 'All Institutes');
    }

    public function test_teaching_plan_course_and_class_must_belong_to_same_institute(): void
    {
        Sanctum::actingAs($this->user('Admin'));
        $course = Course::create(['course_title' => 'Alpha course', 'institute' => 'Alpha', 'status' => 1]);
        $class = SchoolClass::create(['class_name' => 'Class 10', 'institute' => 'Beta', 'status' => 1]);
        $this->postJson('/api/workflows/teaching-plans/create', ['course_id' => $course->id, 'class_id' => $class->id])->assertNotFound();
        $this->assertSame(0, TeachingPlan::count());
    }

    public function test_mobile_activity_is_visible_in_monitoring_including_older_mobile_logs(): void
    {
        $student = $this->student(); Sanctum::actingAs($student);
        $id = $this->postJson('/api/activity', ['action' => 'start', 'section' => 'Learning Content Preview'])->assertOk()->json('id');
        $this->assertSame('content.preview', UserActivityLog::findOrFail($id)->route_name);
        for ($i = 0; $i < 51; $i++) UserActivityLog::create(['user_id' => $student->id, 'user_type' => 'Student', 'route_name' => 'mobile', 'section_name' => 'Learning Content Preview', 'started_at' => now(), 'duration_seconds' => 10]);
        UserActivityLog::create(['user_id' => $student->id, 'user_type' => 'Student', 'route_name' => 'mobile', 'section_name' => 'Dashboard', 'started_at' => now()]);
        Sanctum::actingAs($this->user('Admin'));
        $this->getJson('/api/admin/monitoring/activity')->assertOk()->assertJsonCount(50, 'items')->assertJsonPath('pagination.total', 52);
        $this->getJson('/api/admin/monitoring/activity?page=2')->assertOk()->assertJsonCount(2, 'items');
        $this->getJson('/api/admin/monitoring/activity?viewer_type=Teacher')->assertOk()->assertJsonCount(0, 'items');
        $this->assertSame(52, UserActivityLog::learningContent()->count());
    }

    public function test_monitoring_filters_apply_without_selecting_an_institute(): void
    {
        $student = $this->student(); $session = $this->assessmentSession($student);
        AssessmentResult::create(['student_id' => $student->id, 'assessment_id' => $session->assessment_id, 'status' => 'Pending Review']);
        Sanctum::actingAs($this->user('Admin'));
        $this->getJson('/api/admin/monitoring/assessments')->assertOk()->assertJsonPath('summary.2.value', '1');
        $this->getJson('/api/admin/monitoring/assessments?student_section=B')->assertOk()->assertJsonCount(0, 'items');
        $this->getJson('/api/admin/monitoring/reviews?search=missing')->assertOk()->assertJsonCount(0, 'items');
        $this->getJson('/api/admin/monitoring/reviews?student_class=Class%209')->assertOk()->assertJsonCount(0, 'items');
        $this->getJson('/api/admin/monitoring/reviews?student_class=Class%2010')->assertOk()->assertJsonCount(1, 'items');
        $this->getJson('/api/admin/monitoring/reviews?status=Pending%20Review')->assertOk()->assertJsonCount(1, 'items');
        $this->getJson('/api/admin/monitoring/reviews?status=Completed')->assertOk()->assertJsonCount(0, 'items');
    }

    public function test_admin_rosters_and_notifications_are_paginated_with_open_date_ranges(): void
    {
        for ($i = 0; $i < 51; $i++) $this->student();
        $other = $this->student(); $other->update(['institute' => 'Beta']);
        Sanctum::actingAs($this->user('InstituteAdmin'));
        $this->getJson('/api/admin/students')->assertOk()->assertJsonCount(50, 'students')->assertJsonPath('pagination.total', 51);
        $this->getJson('/api/admin/students?page=2')->assertOk()->assertJsonCount(1, 'students');
        for ($i = 0; $i < 51; $i++) $this->user();
        $this->getJson('/api/admin/teachers?page=2')->assertOk()->assertJsonCount(1, 'teachers')->assertJsonPath('pagination.total', 51);
        for ($i = 0; $i < 51; $i++) \App\Models\LmsNotification::create(['title' => 'Notice', 'message' => 'Update', 'target' => 'students', 'status' => 'active', 'institute' => 'Alpha']);
        $today = today()->format('Y-m-d');
        $this->getJson('/api/admin/notifications?from_date='.$today)->assertOk()->assertJsonPath('pagination.total', 51)->assertJsonCount(50, 'notifications');
        $this->getJson('/api/admin/notifications?to_date='.$today.'&page=2')->assertOk()->assertJsonCount(1, 'notifications');
        $this->getJson('/api/admin/notifications?from_date=2026-09-02&to_date=2026-09-01')->assertUnprocessable();
    }

    public function test_locked_lessons_do_not_expose_ai_summary_or_allow_preview(): void
    {
        $student = $this->student(); Sanctum::actingAs($student);
        $course = Course::create(['course_title' => 'Lessons', 'institute' => 'Alpha', 'assigned_class' => 'Class 10 A', 'status' => 1]);
        Content::create(['content_title' => 'First', 'course_id' => $course->id, 'lesson_order' => 1, 'status' => 1, 'is_released' => true]);
        $locked = Content::create(['content_title' => 'Second', 'course_id' => $course->id, 'lesson_order' => 2, 'status' => 1, 'is_released' => true]);
        DB::table('ai_content_summaries')->insert(['content_id' => $locked->id, 'summary' => 'Hidden lesson answer', 'status' => 'generated']);
        $this->getJson('/api/student/learning-content')->assertOk()->assertJsonPath('lessons.1.status', 'Locked')->assertJsonPath('lessons.1.ai_summary', null);
        $this->getJson('/api/student/content/'.$locked->id.'/preview')->assertForbidden();
    }

    public function test_pending_sessions_keep_one_actionable_row_and_include_overdue_plan_items(): void
    {
        $teacher = $this->user(); Sanctum::actingAs($teacher);
        $class = SchoolClass::create(['class_name' => 'Class 10', 'section' => 'A', 'institute' => 'Alpha', 'status' => 1]);
        $course = Course::create(['course_title' => 'STEM', 'institute' => 'Alpha', 'status' => 1]);
        $content = Content::create(['content_title' => 'Robotics', 'course_id' => $course->id, 'institute' => 'Alpha', 'status' => 1, 'file_path' => 'robotics.pdf']);
        $missedContent = Content::create(['content_title' => 'Circuits', 'course_id' => $course->id, 'institute' => 'Alpha', 'status' => 1, 'file_path' => 'circuits.pdf']);
        $plan = TeachingPlan::create(['title' => 'Class 10 Plan', 'course_id' => $course->id, 'institute' => 'Alpha', 'class' => 'Class 10', 'section' => 'A', 'status' => 'active', 'is_template' => false]);
        $week = TeachingPlanWeek::create(['teaching_plan_id' => $plan->id, 'week_number' => 1, 'status' => 'released', 'week_start_date' => today()->subDays(8)->toDateString(), 'week_end_date' => today()->subDay()->toDateString()]);
        $attemptedItem = TeachingPlanItem::create(['teaching_plan_id' => $plan->id, 'teaching_plan_week_id' => $week->id, 'course_id' => $course->id, 'content_id' => $content->id, 'status' => 'released', 'sort_order' => 1]);
        $missedItem = TeachingPlanItem::create(['teaching_plan_id' => $plan->id, 'teaching_plan_week_id' => $week->id, 'course_id' => $course->id, 'content_id' => $missedContent->id, 'status' => 'released', 'sort_order' => 2]);
        for ($i = 0; $i < 3; $i++) ClassContentSession::create(['stem_engineer_id' => $teacher->id, 'institute' => 'Alpha', 'class' => 'Class 10', 'section' => 'A', 'teaching_plan_item_id' => $attemptedItem->id, 'content_id' => $content->id, 'status' => 'partially_completed', 'session_date' => today()]);
        ClassContentSession::create(['stem_engineer_id' => $teacher->id, 'institute' => 'Alpha', 'class' => 'Class 9', 'section' => 'A', 'teaching_plan_item_id' => 999, 'status' => 'cancelled', 'session_date' => today()]);
        ClassContentSession::create(['stem_engineer_id' => $teacher->id, 'institute' => 'Alpha', 'class' => 'Class 10', 'section' => 'A', 'teaching_plan_item_id' => $attemptedItem->id, 'content_id' => $content->id, 'status' => 'completed', 'session_date' => today()]);
        $this->getJson('/api/engineer/sessions?class_id='.$class->id)
            ->assertOk()
            ->assertJsonCount(1, 'pending_sessions')
            ->assertJsonPath('pagination.pending_sessions.total', 1)
            ->assertJsonPath('pending_sessions.0.item_id', $missedItem->id)
            ->assertJsonPath('pending_sessions.0.status', 'catch_up')
            ->assertJsonCount(1, 'today_sessions');
        $this->getJson('/api/engineer/sessions?class_id='.$class->id.'&page=2')->assertOk()->assertJsonCount(0, 'pending_sessions');
        $class->update(['institute' => 'Beta']);
        $this->getJson('/api/engineer/sessions?class_id='.$class->id)->assertNotFound();
    }

    public function test_student_assessment_windows_are_filtered_before_pagination(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-09-02 10:00:00'));
        $student = $this->student(); Sanctum::actingAs($student);
        $attributes = ['assessment_title' => 'Open exam', 'duration' => 30, 'assessment_date' => today(), 'assigned_class' => 'Class 10 A', 'institute' => 'Alpha', 'status' => 1, 'question_paper_status' => 'Approved', 'file_path' => 'paper.pdf', 'start_time' => '09:00:00', 'end_time' => '11:00:00'];
        for ($i = 0; $i < 31; $i++) Assessment::create($attributes);
        for ($i = 0; $i < 31; $i++) Assessment::create(array_replace($attributes, ['assessment_title' => 'Closed exam', 'end_time' => '09:30:00']));
        Assessment::create(array_replace($attributes, ['start_time' => '10:30:00']));
        Assessment::create(array_replace($attributes, ['assessment_date' => today()->addDay()]));
        Assessment::create(array_replace($attributes, ['assessment_date' => today()->subDay()]));
        Assessment::create(array_replace($attributes, ['institute' => 'Beta']));
        $this->getJson('/api/student/assessments')->assertOk()->assertJsonCount(30, 'assessments')->assertJsonPath('pagination.total', 31)->assertJsonMissing(['title' => 'Closed exam']);
        $this->getJson('/api/student/assessments?page=2')->assertOk()->assertJsonCount(1, 'assessments');
    }

    public function test_exam_start_preserves_timer_and_caps_deadline_at_window_end(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-09-02 10:00:00'));
        $student = $this->student(); Sanctum::actingAs($student);
        $session = $this->assessmentSession($student);
        $session->assessment->update(['end_time' => '10:15:00']);
        $path = '/api/student/assessments/'.$session->assessment_id.'/start';
        $expectedDeadline = now()->addMinutes(15)->toIso8601String();
        $this->postJson($path)->assertOk()->assertJsonPath('session_id', $session->id)->assertJsonPath('assessment_date', '2026-09-02')->assertJsonPath('deadline', $expectedDeadline);
        $this->travel(5)->minutes();
        $this->postJson($path)->assertOk()->assertJsonPath('session_id', $session->id)->assertJsonPath('deadline', $expectedDeadline);
        $this->getJson('/api/student/assessment-sessions/'.$session->id)->assertOk()->assertJsonPath('deadline', $expectedDeadline);
        $this->assertSame(1, AssessmentSession::count());
    }

    public function test_expired_exam_cannot_restart_and_submits_saved_draft(): void
    {
        $student = $this->student(); Sanctum::actingAs($student);
        $session = $this->assessmentSession($student);
        $this->postJson('/api/student/assessment-sessions/'.$session->id.'/draft', ['answer_text' => 'My saved answer'])->assertOk();
        $this->travel(31)->minutes();
        $this->postJson('/api/student/assessments/'.$session->assessment_id.'/start')->assertStatus(409);
        $this->assertSame(1, AssessmentSession::count());
        $this->assertSame('AutoSubmitted', $session->fresh()->status);
        $this->assertSame('My saved answer', AssessmentResult::firstOrFail()->answer_text);
    }

    public function test_student_result_history_is_paginated_and_handles_evaluation_dates(): void
    {
        $student = $this->student(); Sanctum::actingAs($student);
        $session = $this->assessmentSession($student);
        for ($i = 0; $i < 31; $i++) AssessmentResult::create([
            'student_id' => $student->id, 'assessment_id' => $session->assessment_id,
            'status' => 'Completed', 'score' => 85, 'total_marks' => 100,
            'evaluated_at' => now(),
        ]);
        AssessmentResult::create(['student_id' => $this->student()->id, 'assessment_id' => $session->assessment_id, 'status' => 'Completed']);
        $this->getJson('/api/student/assessment-results')->assertOk()->assertJsonCount(30, 'results')->assertJsonPath('pagination.total', 31)->assertJsonPath('results.0.result_date', today()->format('Y-m-d'));
        $this->getJson('/api/student/assessment-results?page=2')->assertOk()->assertJsonCount(1, 'results');
    }
}
