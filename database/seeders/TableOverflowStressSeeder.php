<?php

namespace Database\Seeders;

use App\Models\Assessment;
use App\Models\AssessmentResult;
use App\Models\Certificate;
use App\Models\ClassContentSession;
use App\Models\Content;
use App\Models\Course;
use App\Models\IndependentLearner;
use App\Models\Institute;
use App\Models\LmsNotification;
use App\Models\MySpace;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentAchievement;
use App\Models\TeacherAchievement;
use App\Models\TeachingPlan;
use App\Models\TeachingPlanItem;
use App\Models\TeachingPlanWeek;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TableOverflowStressSeeder extends Seeder
{
    public function run(): void
    {
        if (!app()->environment(['local', 'development'])) {
            $this->command?->warn('TableOverflowStressSeeder is intended for local/development use only.');

            return;
        }

        $institute = Institute::updateOrCreate(
            ['institute_id' => 'STRESS-001'],
            [
                'institute_name' => 'InnovatEdge Integrated STEM Research and Applied Robotics Laboratory Campus',
                'location' => 'Bengaluru, Karnataka, India',
                'contact_person' => 'Dr. Anantha Subramanian - Academic Operations Lead',
                'email' => 'stress-demo@innovatedge.test',
                'phone' => '9000000001',
                'status' => 1,
            ]
        );

        $admin = User::updateOrCreate(
            ['email' => 'stress.admin@innovatedge.test'],
            [
                'user_id' => 'ADM-STRESS-001',
                'name' => 'Stress Test Super Admin',
                'role' => 'Admin',
                'password' => Hash::make('password'),
                'status' => 1,
            ]
        );

        $teacher = User::updateOrCreate(
            ['email' => 'stress.teacher@innovatedge.test'],
            [
                'user_id' => 'TEE-STRESS-001',
                'name' => 'Stress Test STEM Engineer',
                'role' => 'Teacher',
                'password' => Hash::make('password'),
                'status' => 1,
                'institute' => $institute->institute_name,
            ]
        );

        $studentNames = [
            'Aarav Prakash Suryanarayanan',
            'Diya Nandini Krishnamurthy',
            'Rohan Venkatesh Bhat',
            'Sahana Lakshmi Iyer',
            'Kavin Mahadevan Subramaniam',
        ];

        $classEntries = [
            ['class_name' => '1st A', 'section' => 'A'],
            ['class_name' => '1st B', 'section' => 'B'],
            ['class_name' => '2nd Combined', 'section' => 'Combined'],
            ['class_name' => '3rd A', 'section' => 'A'],
            ['class_name' => '4th B', 'section' => 'B'],
        ];

        $classModels = [];

        foreach ($classEntries as $index => $classEntry) {
            $classModels[] = SchoolClass::updateOrCreate(
                [
                    'class_name' => $classEntry['class_name'],
                    'section' => $classEntry['section'],
                    'institute' => $institute->institute_name,
                ],
                [
                    'class_teacher' => $teacher->name,
                    'academic_year' => '2026-2027',
                    'status' => 1,
                ]
            );
        }

        $students = [];
        foreach ($classModels as $index => $classModel) {
            foreach ([1, 2, 3, 4] as $slot) {
                $name = $studentNames[($index + $slot - 1) % count($studentNames)] . ' - Portfolio Stress Variant ' . $slot;
                $students[] = Student::updateOrCreate(
                    [
                        'student_id' => 'STRESS-' . str_pad((string) (($index * 4) + $slot), 3, '0', STR_PAD_LEFT),
                    ],
                    [
                        'name' => $name,
                        'institute' => $institute->institute_name,
                        'class' => $classModel->class_name,
                        'section' => $classModel->section,
                        'contact' => '987654' . str_pad((string) (($index * 4) + $slot), 4, '0', STR_PAD_LEFT),
                        'email' => 'student' . (($index * 4) + $slot) . '@innovatedge.test',
                        'guardian_name' => 'Guardian ' . Str::of($name)->before(' -')->toString(),
                        'status' => 1,
                        'profile_completed' => true,
                        'password' => Hash::make('password'),
                    ]
                );
            }
        }

        $courseA = Course::updateOrCreate(
            ['course_title' => 'Robotics, AI and Electronics Integrated Learning Pathway for Demonstration Purposes'],
            [
                'description' => Str::repeat('This course record is intentionally verbose to test table wrapping and scrolling behaviour in the LMS screens. ', 4),
                'target' => 'Student',
                'assigned_class' => '1st A',
                'certificate_enabled' => true,
                'status' => 1,
            ]
        );

        $courseB = Course::updateOrCreate(
            ['course_title' => 'Advanced STEM Problem Solving and Engineering Design Sprint for Long-Text Layout Validation'],
            [
                'description' => Str::repeat('A second demo course used to confirm that long titles, badges and action buttons remain inside the frame. ', 4),
                'target' => 'Student',
                'assigned_class' => '2nd Combined',
                'certificate_enabled' => true,
                'status' => 1,
            ]
        );

        $contents = collect([
            [
                'content_title' => 'Robotics Navigation and Sensor Fusion in a Compact Classroom Demo Block',
                'content_type' => 'PPT',
                'assigned_class' => '1st A',
                'section' => 'A',
                'course_id' => $courseA->id,
                'lesson_order' => 1,
            ],
            [
                'content_title' => 'AI-enabled Decision Making and Project Showcase With Extended Metadata',
                'content_type' => 'PDF',
                'assigned_class' => '1st B',
                'section' => 'B',
                'course_id' => $courseA->id,
                'lesson_order' => 2,
            ],
            [
                'content_title' => 'Electronics Lab Safety, Component Identification and Application Matrix',
                'content_type' => 'PPT',
                'assigned_class' => '2nd Combined',
                'section' => 'Combined',
                'course_id' => $courseB->id,
                'lesson_order' => 1,
            ],
            [
                'content_title' => 'Advanced Prototyping, Iteration and Presentation Skills for STEM Content Frames',
                'content_type' => 'PDF',
                'assigned_class' => '3rd A',
                'section' => 'A',
                'course_id' => $courseB->id,
                'lesson_order' => 2,
            ],
        ])->map(function (array $contentData, int $index) use ($teacher, $institute) {
            return Content::updateOrCreate(
                ['content_title' => $contentData['content_title']],
                [
                    'description' => Str::repeat('This content record is intentionally long so the content tables can be checked for wrap and scroll behaviour. ', 3),
                    'course_id' => $contentData['course_id'],
                    'lesson_order' => $contentData['lesson_order'],
                    'content_type' => $contentData['content_type'],
                    'assigned_class' => $contentData['assigned_class'],
                    'section' => $contentData['section'],
                    'institute' => $institute->institute_name,
                    'file_path' => 'dummy/content-' . ($index + 1) . '.pdf',
                    'preview_pdf_path' => 'dummy/content-' . ($index + 1) . '-preview.pdf',
                    'student_file_path' => null,
                    'student_preview_pdf_path' => null,
                    'original_file_name' => 'Demo Content ' . ($index + 1) . '.pdf',
                    'uploaded_by' => $teacher->id,
                    'is_released' => true,
                    'status' => 1,
                ]
            );
        });

        $plan = TeachingPlan::updateOrCreate(
            [
                'title' => 'Stress Test Teaching Plan for Table Layout Verification',
                'institute_id' => $institute->id,
                'class' => '1st A',
                'section' => 'A',
                'course_id' => $courseA->id,
                'is_template' => false,
            ],
            [
                'institute' => $institute->institute_name,
                'course_content_id' => null,
                'content_id' => $contents->first()->id,
                'start_date' => now()->toDateString(),
                'release_day' => 'Friday',
                'contents_per_week' => 2,
                'release_policy' => 'release_next_only_if_previous_completed',
                'current_batch' => 1,
                'status' => 'approved',
                'approved_by' => $admin->id,
                'approved_at' => now(),
                'remarks' => 'This is a local dummy record for layout stress testing only.',
            ]
        );

        $weeks = collect(range(1, 3))->map(function (int $weekNumber) use ($plan) {
            return TeachingPlanWeek::updateOrCreate(
                [
                    'teaching_plan_id' => $plan->id,
                    'week_number' => $weekNumber,
                ],
                [
                    'week_start_date' => now()->copy()->addWeeks($weekNumber - 1)->startOfWeek()->toDateString(),
                    'week_end_date' => now()->copy()->addWeeks($weekNumber - 1)->endOfWeek()->toDateString(),
                    'release_date' => now()->copy()->addWeeks($weekNumber - 1)->addDay(4)->toDateString(),
                    'status' => $weekNumber === 1 ? 'completed' : 'released',
                    'released_at' => now()->copy()->addWeeks($weekNumber - 1),
                    'completed_at' => $weekNumber === 1 ? now() : null,
                ]
            );
        });

        foreach ($weeks as $weekIndex => $week) {
            foreach ([1, 2] as $sortOrder) {
                TeachingPlanItem::updateOrCreate(
                    [
                        'teaching_plan_id' => $plan->id,
                        'teaching_plan_week_id' => $week->id,
                        'sort_order' => $sortOrder,
                    ],
                    [
                        'course_id' => $courseA->id,
                        'course_content_id' => null,
                        'content_id' => $contents[$sortOrder - 1]->id,
                        'status' => $weekIndex === 0 ? 'released' : 'locked',
                        'released_at' => $weekIndex === 0 ? now() : null,
                        'completed_at' => $weekIndex === 0 && $sortOrder === 1 ? now() : null,
                    ]
                );
            }
        }

        $firstPlanItem = TeachingPlanItem::where('teaching_plan_id', $plan->id)->orderBy('sort_order')->first();

        ClassContentSession::updateOrCreate(
            [
                'teaching_plan_item_id' => $firstPlanItem->id,
                'session_date' => now()->toDateString(),
                'start_time' => now()->format('H:i:s'),
            ],
            [
                'institute' => $institute->institute_name,
                'class_id' => $classModels[0]->id,
                'course_id' => $courseA->id,
                'teaching_plan_id' => $plan->id,
                'teaching_plan_week_id' => $firstPlanItem->teaching_plan_week_id,
                'course_content_id' => null,
                'content_id' => $firstPlanItem->content_id,
                'delivered_content_id' => $firstPlanItem->content_id,
                'stem_engineer_id' => $teacher->id,
                'class' => $classModels[0]->class_name,
                'section' => $classModels[0]->section,
                'session_day' => now()->format('l'),
                'end_time' => null,
                'started_at' => now(),
                'ended_at' => null,
                'duration_seconds' => 0,
                'status' => 'in_progress',
                'planned_topic' => $firstPlanItem->content->content_title ?? 'Demo Planned Topic',
                'delivered_topic' => null,
                'remarks' => 'Local stress-test session.',
            ]
        );

        $assessments = collect([
            'Assessment 1 - Long Title for Table Width and Multi-Line Wrapping Validation',
            'Assessment 2 - Another Long and Detailed Monthly Review Paper for UI Stress Testing',
        ])->map(function (string $title, int $index) use ($classModels) {
            return Assessment::updateOrCreate(
                ['assessment_title' => $title],
                [
                    'assessment_type' => $index === 0 ? 'Monthly' : 'Annual',
                    'assigned_class' => $classModels[$index]->class_name,
                    'total_marks' => 100,
                    'duration' => '60 mins',
                    'question_paper_type' => 'PDF',
                    'file_path' => 'dummy/assessment-' . ($index + 1) . '.pdf',
                    'status' => 1,
                ]
            );
        });

        foreach ($assessments as $index => $assessment) {
            $student = $students[$index];

            AssessmentResult::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'assessment_id' => $assessment->id,
                ],
                [
                    'score' => 84 - ($index * 7),
                    'total_marks' => 100,
                    'status' => 'Completed',
                    'badge' => $index === 0 ? 'Gold' : 'Silver',
                ]
            );
        }

        foreach ($students->take(2) as $index => $student) {
            Certificate::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'certificate_code' => 'CERT-STRESS-' . str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
                ],
                [
                    'badge_count' => 5 + $index,
                    'issued_date' => now()->toDateString(),
                    'status' => $index === 0 ? 'Issued' : 'Pending Approval',
                ]
            );
        }

        foreach ($students->take(3) as $index => $student) {
            MySpace::updateOrCreate(
                [
                    'title' => 'Demo Idea ' . ($index + 1) . ' for Scroll Validation in My Space',
                    'created_by_type' => 'Student',
                    'created_by_id' => $student->id,
                ],
                [
                    'description' => Str::repeat('This is a dummy My Space submission used to test the action buttons and table overflow behavior. ', 3),
                    'type' => $index % 2 === 0 ? 'Idea' : 'Project',
                    'status' => 'Published',
                    'blueprint_pdf' => null,
                    'repository_link' => 'https://example.com/demo/' . ($index + 1),
                ]
            );
        }

        StudentAchievement::updateOrCreate(
            [
                'student_id' => $students[0]->id,
                'title' => 'National Level STEM Showcase and Robotics Expo Participation',
            ],
            [
                'achievement_type' => 'Competition',
                'organizer' => 'InnovatEdge Test Consortium',
                'description' => Str::repeat('This student achievement row is intentionally lengthy for height and wrapping validation. ', 3),
                'achievement_date' => now()->subDays(12)->toDateString(),
                'position' => 'First Runner Up',
                'certificate_file' => 'dummy/student-achievement.pdf',
                'verification_status' => 'Approved',
            ]
        );

        TeacherAchievement::updateOrCreate(
            [
                'user_id' => $teacher->id,
                'title' => 'Teacher Led STEM Integration Workshop for Table Stress Testing',
            ],
            [
                'achievement_type' => 'Workshop',
                'organizer' => 'InnovatEdge Professional Development Cell',
                'description' => Str::repeat('This teacher achievement row is intentionally long to check wrapping and row height balance. ', 3),
                'achievement_date' => now()->subDays(10)->toDateString(),
                'position' => 'Resource Person',
                'certificate_file' => 'dummy/teacher-achievement.pdf',
                'verification_status' => 'Approved',
            ]
        );

        LmsNotification::updateOrCreate(
            [
                'title' => 'Stress Test Notice for Table Overflow Validation',
            ],
            [
                'message' => Str::repeat('This notification is intentionally long so the notifications table can be checked for overflow and wrapping. ', 3),
                'target' => 'all',
                'institute' => $institute->institute_name,
                'starts_at' => now()->toDateString(),
                'expires_at' => now()->addDays(7)->toDateString(),
                'status' => 'active',
                'created_by' => $admin->id,
            ]
        );

        IndependentLearner::updateOrCreate(
            ['email' => 'stress.learner@innovatedge.test'],
            [
                'name' => 'Independent Learner for Overflow Stress Validation',
                'phone' => '9888800000',
                'password' => Hash::make('password'),
                'status' => 1,
            ]
        );

        $this->command?->info('Local table stress data is ready.');
    }
}
