<?php

namespace Tests\Feature;

use App\Models\Content;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\CourseContent;
use App\Models\Institute;
use App\Models\MySpace;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentAchievement;
use App\Models\User;
use App\Services\StudentCsvImportService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MobileWorkflowParityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (! in_array('sqlite', \PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('Run PHP with -d extension=pdo_sqlite.');
        }
        // Never run workflow tests against the developer's MySQL database.
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'database.connections.sqlite.url' => null]);
        DB::purge('sqlite');
        foreach ([
            'users' => ['user_id', 'name', 'email', 'password', 'role', 'institute', 'status'],
            'students' => ['student_id', 'name', 'email', 'password', 'institute', 'class', 'section', 'contact', 'guardian_name', 'is_robotics_club_member', 'status', 'profile_completed'],
            'institutes' => ['institute_name'], 'classes' => ['class_name', 'section', 'institute'],
            'my_spaces' => ['title', 'description', 'type', 'created_by_type', 'created_by_id', 'status', 'blueprint_pdf', 'repository_link'],
            'student_achievements' => ['student_id', 'title', 'achievement_type', 'organizer', 'description', 'achievement_date', 'position', 'certificate_file', 'verification_status'],
            'teacher_achievements' => ['user_id', 'title', 'achievement_type', 'organizer', 'description', 'achievement_date', 'position', 'certificate_file', 'verification_status'],
            'courses' => ['course_title', 'assigned_class', 'institute', 'is_template_source', 'description', 'target', 'price', 'availability_type', 'is_active', 'status', 'certificate_enabled'],
            'contents' => ['course_id', 'institute', 'content_title', 'description', 'lesson_order', 'content_type', 'assigned_class', 'section', 'file_path', 'preview_pdf_path', 'student_file_path', 'student_preview_pdf_path', 'original_file_name', 'uploaded_by', 'is_released', 'status'],
            'course_contents' => ['course_id', 'content_id', 'sort_order', 'status', 'created_by'],
            'community_posts' => ['source_type', 'source_id', 'post_type', 'title', 'body', 'author_type', 'author_id', 'institute', 'status', 'published_at', 'approved_by', 'approved_at', 'rejected_at', 'attachment_path', 'attachment_original_name'],
            'teaching_plans' => ['course_id', 'course_content_id', 'content_id'], 'teaching_plan_items' => ['course_content_id', 'content_id', 'teaching_plan_id'],
            'course_enrollments' => ['course_id'], 'certificates' => ['student_id', 'certificate_code', 'badge_count', 'final_score', 'final_grade', 'final_classification', 'issued_date', 'status', 'certificate_type', 'course_id'], 'class_timetables' => ['content_id'],
            'assessments' => ['content_id'], 'lesson_progress' => ['content_id'], 'class_content_sessions' => ['content_id'],
        ] as $name => $columns) {
            Schema::create($name, function (Blueprint $table) use ($columns) {
                $table->id();
                foreach ($columns as $column) {
                    $table->string($column)->nullable();
                }
                $table->timestamps();
            });
        }
        Storage::fake('local');
        Storage::fake('public');
        Institute::create(['institute_name' => 'Alpha']);
        Institute::create(['institute_name' => 'Beta']);
        SchoolClass::create(['class_name' => 'Class 10', 'section' => 'A', 'institute' => 'Alpha']);
        SchoolClass::create(['class_name' => 'Class 10', 'section' => 'A', 'institute' => 'Beta']);
    }

    private function student(string $institute = 'Alpha'): Student
    {
        return Student::create(['student_id' => uniqid('S'), 'name' => 'Learner', 'password' => Hash::make('password'), 'institute' => $institute]);
    }

    private function user(string $role = 'Admin', string $institute = 'Alpha'): User
    {
        return User::create(['name' => $role, 'email' => uniqid().'@example.com', 'password' => 'password', 'role' => $role, 'institute' => $institute]);
    }

    private function pdf(): UploadedFile
    {
        return UploadedFile::fake()->create('proof.pdf', 10, 'application/pdf');
    }

    public function test_student_submission_requires_pdf_and_is_owned_by_token(): void
    {
        $student = $this->student();
        Sanctum::actingAs($student);
        $body = ['title' => 'New idea', 'description' => 'A lab idea', 'type' => 'Idea'];
        $this->postJson('/api/student/my-space', $body)->assertUnprocessable()->assertJsonValidationErrors('blueprint_pdf');
        $response = $this->postJson('/api/student/my-space', $body + ['blueprint_pdf' => $this->pdf(), 'created_by_id' => 999])->assertCreated();
        $item = MySpace::firstOrFail();
        $this->assertEquals($student->id, $item->created_by_id);
        Storage::disk('public')->assertExists($item->blueprint_pdf);
        $url = $response->json('item.attachment_url');
        $this->get($url)->assertOk();
        $this->get(preg_replace('/signature=[^&]+/', 'signature=wrong', $url))->assertForbidden();
        $this->travel(6)->minutes();
        $this->get($url)->assertForbidden();
    }

    public function test_submission_lists_and_updates_are_isolated_by_owner_and_role(): void
    {
        $owner = $this->student();
        $item = MySpace::create(['title' => 'Private', 'type' => 'Project', 'description' => 'x', 'repository_link' => 'https://example.com/repo', 'created_by_type' => 'Student', 'created_by_id' => $owner->id, 'status' => 'Pending']);
        Sanctum::actingAs($this->student());
        $this->getJson('/api/student/my-space')->assertOk()->assertJsonCount(0, 'posts');
        $this->postJson('/api/student/my-space/'.$item->id, [])->assertNotFound();
        $this->deleteJson('/api/student/my-space/'.$item->id)->assertNotFound();
        $this->getJson('/api/engineer/my-space')->assertForbidden();
    }

    public function test_approved_my_space_is_locked_and_project_to_idea_requires_evidence(): void
    {
        $owner = $this->student();
        Sanctum::actingAs($owner);
        $item = MySpace::create(['title' => 'Project', 'type' => 'Project', 'description' => 'x', 'repository_link' => 'https://example.com/repo', 'created_by_type' => 'Student', 'created_by_id' => $owner->id, 'status' => 'Pending']);
        $body = ['title' => 'Idea', 'description' => 'x', 'type' => 'Idea'];
        $this->postJson('/api/student/my-space/'.$item->id, $body)->assertUnprocessable();
        $item->update(['status' => 'Approved']);
        $this->postJson('/api/student/my-space/'.$item->id, $body)->assertForbidden();
        $this->deleteJson('/api/student/my-space/'.$item->id)->assertForbidden();
    }

    public function test_student_achievement_upload_edit_and_approval_lock(): void
    {
        Sanctum::actingAs($this->student());
        $body = ['title' => 'Award', 'achievement_type' => 'Competition', 'achievement_date' => '2026-09-01'];
        $this->postJson('/api/student/achievements', $body)->assertUnprocessable();
        $this->postJson('/api/student/achievements', $body + ['certificate_file' => $this->pdf()])->assertCreated();
        $item = StudentAchievement::firstOrFail();
        $item->update(['verification_status' => 'Rejected']);
        $this->postJson('/api/student/achievements/'.$item->id, $body)->assertOk()->assertJsonPath('item.status', 'Pending');
        $this->getJson('/api/student/achievements')->assertOk()->assertJsonPath('achievements.0.achievement_date', '2026-09-01');
        $item->update(['verification_status' => 'Approved']);
        $this->postJson('/api/student/achievements/'.$item->id, $body)->assertForbidden();
    }

    public function test_student_achievements_include_approved_certificates(): void
    {
        $student = $this->student();
        Certificate::create([
            'student_id' => $student->id,
            'certificate_code' => 'CERT-READY',
            'status' => 'approved',
            'certificate_type' => 'Annual',
            'final_score' => 88,
            'final_grade' => 'A',
            'issued_date' => '2026-09-09',
        ]);
        Certificate::create([
            'student_id' => $student->id,
            'certificate_code' => 'CERT-PENDING',
            'status' => 'Pending Approval',
            'certificate_type' => 'Annual',
        ]);
        StudentAchievement::create([
            'student_id' => $student->id,
            'title' => 'Robotics Fair',
            'achievement_type' => 'Competition',
            'verification_status' => 'Approved',
        ]);

        Sanctum::actingAs($student);
        $response = $this->getJson('/api/student/achievements')->assertOk();

        $response->assertJsonCount(2, 'achievements')
            ->assertJsonFragment([
                'title' => 'Annual Certificate',
                'achievement_type' => 'Certificate',
                'can_edit' => false,
                'can_delete' => false,
                'description' => 'CERT-READY | Score: 88 | Grade: A',
            ])
            ->assertJsonFragment([
                'title' => 'Robotics Fair',
                'achievement_type' => 'Competition',
            ]);
    }

    public function test_submission_rejects_wrong_file_types_and_cleans_up_replaced_proof(): void
    {
        Sanctum::actingAs($this->student());
        $body = ['title' => 'Award', 'achievement_type' => 'Competition'];
        $this->postJson('/api/student/achievements', $body + ['certificate_file' => UploadedFile::fake()->create('script.php', 1, 'text/x-php')])->assertUnprocessable();
        $this->postJson('/api/student/achievements', $body + ['certificate_file' => UploadedFile::fake()->create('large.pdf', 5121, 'application/pdf')])->assertUnprocessable();
        $this->postJson('/api/student/achievements', $body + ['certificate_file' => $this->pdf()])->assertCreated();
        $item = StudentAchievement::firstOrFail();
        $old = $item->certificate_file;
        $this->postJson('/api/student/achievements/'.$item->id, $body + ['certificate_file' => $this->pdf()])->assertOk();
        Storage::disk('public')->assertMissing($old);
        Storage::disk('public')->assertExists($item->fresh()->certificate_file);
        $this->deleteJson('/api/student/achievements/'.$item->id)->assertOk();
        $this->assertCount(0, Storage::disk('public')->allFiles());
    }

    public function test_engineer_evidence_is_optional_and_student_role_cannot_impersonate_engineer(): void
    {
        Sanctum::actingAs($this->user('Teacher'));
        $this->postJson('/api/engineer/achievements', ['title' => 'Workshop', 'achievement_type' => 'Workshop'])->assertCreated();
        $this->getJson('/api/student/achievements')->assertForbidden();
        $this->getJson('/api/engineer/achievements')->assertOk()->assertJsonCount(1, 'achievements');
        $this->deleteJson('/api/engineer/achievements/1')->assertOk();
    }

    public function test_admin_can_review_evidence_and_approval_syncs_to_community(): void
    {
        $student = $this->student();
        $item = StudentAchievement::create(['student_id' => $student->id, 'title' => 'Award', 'achievement_type' => 'Competition', 'verification_status' => 'Pending']);
        $admin = $this->user('InstituteAdmin');
        Sanctum::actingAs($admin);
        $this->getJson('/api/admin/submissions/student/achievements')->assertOk()->assertJsonCount(1, 'achievements');
        $this->postJson('/api/admin/submissions/student/achievements/'.$item->id.'/approve')->assertOk();
        $this->assertDatabaseHas('community_posts', ['source_type' => 'StudentAchievement', 'source_id' => $item->id, 'approved_by' => $admin->id]);
        $this->postJson('/api/admin/submissions/student/achievements/'.$item->id.'/reject')->assertOk();
        $this->assertDatabaseCount('community_posts', 0);
        Sanctum::actingAs($this->user('InstituteAdmin', 'Beta'));
        $this->getJson('/api/admin/submissions/student/achievements?institute=Alpha')->assertOk()->assertJsonCount(0, 'achievements');
        $this->postJson('/api/admin/submissions/student/achievements/'.$item->id.'/approve')->assertNotFound();
    }

    public function test_import_template_alignment_and_partial_import_passwords(): void
    {
        Sanctum::actingAs($this->user('Teacher'));
        $template = $this->getJson('/api/students/import-template')->assertOk();
        $this->assertCount(count($template->json('columns')), $template->json('example'));
        $this->get($template->json('download_url'))->assertOk();
        $csv = implode(',', StudentCsvImportService::COLUMNS)."\n";
        $csv .= "S101,Student,Beta,Class 10,A,1234567890,,Parent,Yes,Correct123,Active\n";
        $csv .= "S102,Invalid,Beta,Unknown,A,1234567890,,Parent,No,Correct123,Active\n";
        $csv .= "S101,Duplicate,Beta,Class 10,A,1234567890,,Parent,No,Correct123,Active\n";
        $result = $this->postJson('/api/students/import', ['students_csv' => UploadedFile::fake()->createWithContent('students.csv', $csv)])->assertOk();
        $result->assertJsonPath('created', 1)->assertJsonPath('skipped', 2)->assertJsonCount(2, 'errors');
        $student = Student::where('student_id', 'S101')->firstOrFail();
        $this->assertSame('Alpha', $student->institute);
        $this->assertTrue(Hash::check('Correct123', $student->password));
    }

    public function test_import_rejects_bad_headers_and_unauthorised_role(): void
    {
        Sanctum::actingAs($this->user());
        $this->postJson('/api/students/import', ['students_csv' => UploadedFile::fake()->createWithContent('bad.csv', "name\nTest\n")])->assertUnprocessable();
        Sanctum::actingAs($this->student());
        $this->postJson('/api/students/import')->assertForbidden();
    }

    public function test_student_filter_options_include_empty_classes_and_scoped_sections(): void
    {
        SchoolClass::create(['class_name' => 'Class 11', 'section' => 'STEM-F', 'institute' => 'Alpha']);
        SchoolClass::create(['class_name' => 'Class 12', 'section' => 'Private', 'institute' => 'Beta']);
        Student::create(['student_id' => 'LEGACY1', 'name' => 'Legacy', 'institute' => 'Alpha', 'class' => 'Legacy Class', 'section' => 'Legacy Section']);

        $request = \Illuminate\Http\Request::create('/', 'GET', ['student_class' => 'Class 11', 'institute' => 'Beta']);
        $request->setUserResolver(fn () => $this->user('InstituteAdmin', 'Alpha'));

        $fields = collect(app(\App\Services\MobileManagementFilters::class)->fields($request, 'students'))->keyBy('name');

        $this->assertSame(['Class 10', 'Class 11', 'Legacy Class'], array_column($fields['student_class']['options'], 'value'));
        $this->assertSame(['STEM-F'], array_column($fields['student_section']['options'], 'value'));
    }

    private function course(): Course
    {
        return Course::create(['course_title' => 'Robotics', 'assigned_class' => 'Class 10', 'institute' => 'Alpha']);
    }

    private function lesson(int $order = 1): array
    {
        return ['title' => 'Circuit', 'content_type' => 'PDF', 'sort_order' => $order, 'assigned_class' => 'Class 10', 'section' => 'A', 'status' => 'active', 'file' => $this->pdf(), 'student_file' => $this->pdf()];
    }

    public function test_course_upload_updates_both_records_and_preview_is_signed(): void
    {
        $course = $this->course();
        Sanctum::actingAs($this->user('InstituteAdmin'));
        $path = '/api/admin/courses/'.$course->id.'/lessons';
        $this->postJson($path, ['lessons' => [$this->lesson()]])->assertOk();
        $link = CourseContent::firstOrFail();
        $this->getJson($path)->assertOk()->assertJsonPath('lessons.0.title', 'Circuit');
        $url = $this->getJson($path.'/'.$link->id.'/preview/student')->assertOk()->json('url');
        $this->get($url)->assertOk();
        $this->postJson($path.'/'.$link->id, ['lessons' => [array_diff_key($this->lesson(2), ['file' => true, 'student_file' => true])]])->assertOk();
        $this->assertEquals(2, $link->fresh()->sort_order);
        $this->assertEquals(2, Content::firstOrFail()->lesson_order);
    }

    public function test_course_upload_rejects_duplicate_orders_invalid_classes_and_cross_institute(): void
    {
        $course = $this->course();
        Sanctum::actingAs($this->user('InstituteAdmin'));
        $path = '/api/admin/courses/'.$course->id.'/lessons';
        $this->postJson($path, ['lessons' => [$this->lesson(), $this->lesson()]])->assertUnprocessable();
        $invalid = $this->lesson(2);
        $invalid['assigned_class'] = 'Unknown';
        $this->postJson($path, ['lessons' => [$this->lesson(), $invalid]])->assertUnprocessable();
        $this->assertDatabaseCount('contents', 0);
        $this->assertCount(0, Storage::disk('local')->allFiles());
        Sanctum::actingAs($this->user('InstituteAdmin', 'Beta'));
        $this->getJson($path)->assertForbidden();
        $this->postJson($path, ['lessons' => [$this->lesson()]])->assertForbidden();
        Sanctum::actingAs($this->student());
        $this->getJson($path)->assertForbidden();
    }

    public function test_course_upload_rejects_missing_selected_files_and_nonsequential_indices(): void
    {
        $course = $this->course();
        Sanctum::actingAs($this->user());
        $path = '/api/admin/courses/'.$course->id.'/lessons';
        $lesson = $this->lesson();
        unset($lesson['student_file']);
        $this->postJson($path, ['lessons' => [$lesson], 'expected_uploads' => ['lessons.0.file', 'lessons.0.student_file']])->assertUnprocessable();
        $this->postJson($path, ['lessons' => [2 => $this->lesson()]])->assertUnprocessable();
        $this->assertDatabaseCount('contents', 0);
        $this->postJson($path, ['lessons' => [$this->lesson()], 'expected_uploads' => ['lessons.0.file', 'lessons.0.student_file']])->assertOk();
    }

    public function test_course_create_template_permissions_and_delete_cleanup(): void
    {
        $data = ['course_title' => 'Robotics', 'assigned_class' => 'Class 10', 'target' => 'Both', 'price' => 0, 'availability_type' => 'Institute', 'is_active' => true, 'is_template_source' => true];
        Sanctum::actingAs($this->user('InstituteAdmin'));
        $this->postJson('/api/admin/courses', $data)->assertForbidden();
        Sanctum::actingAs($this->user());
        $id = $this->postJson('/api/admin/courses', $data)->assertCreated()->json('course.id');
        $this->getJson('/api/admin/courses/'.$id)->assertOk()->assertJsonPath('course.is_template_source', true);
        $path = '/api/admin/courses/'.$id.'/lessons';
        $this->postJson($path, ['lessons' => [$this->lesson()]])->assertOk();
        $this->deleteJson('/api/admin/courses/'.$id)->assertOk();
        $this->assertDatabaseCount('contents', 0);
        $this->assertDatabaseCount('course_contents', 0);
        $this->assertDatabaseCount('courses', 0);
        $this->assertCount(0, Storage::disk('local')->allFiles());
    }

    public function test_removing_one_lesson_keeps_other_lessons(): void
    {
        $course = $this->course();
        Sanctum::actingAs($this->user());
        $path = '/api/admin/courses/'.$course->id.'/lessons';
        $this->postJson($path, ['lessons' => [$this->lesson(), $this->lesson(2)]])->assertOk();
        $link = CourseContent::firstOrFail();
        $this->deleteJson($path.'/'.$link->id)->assertOk();
        $this->assertDatabaseCount('contents', 1);
        $this->assertDatabaseCount('course_contents', 1);
        $this->assertEquals(2, CourseContent::firstOrFail()->sort_order);
    }
}
