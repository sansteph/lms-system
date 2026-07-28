<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('community_posts')) {
            return;
        }

        Schema::table('community_posts', function (Blueprint $table) {
            if (!Schema::hasColumn('community_posts', 'source_type')) {
                $table->string('source_type')->nullable()->after('rejected_at');
            }

            if (!Schema::hasColumn('community_posts', 'source_id')) {
                $table->unsignedBigInteger('source_id')->nullable()->after('source_type');
            }
        });

        Schema::table('community_posts', function (Blueprint $table) {
            $table->unique(['source_type', 'source_id'], 'community_posts_source_unique');
        });

        $this->backfillStudentAchievements();
        $this->backfillTeacherAchievements();
        $this->backfillMySpace();
    }

    public function down(): void
    {
        if (!Schema::hasTable('community_posts')) {
            return;
        }

        Schema::table('community_posts', function (Blueprint $table) {
            $table->dropUnique('community_posts_source_unique');
        });

        Schema::table('community_posts', function (Blueprint $table) {
            foreach (['source_type', 'source_id'] as $column) {
                if (Schema::hasColumn('community_posts', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    private function backfillStudentAchievements(): void
    {
        if (!Schema::hasTable('student_achievements') || !Schema::hasTable('students')) {
            return;
        }

        DB::table('student_achievements')
            ->join('students', 'students.id', '=', 'student_achievements.student_id')
            ->where('student_achievements.verification_status', 'Approved')
            ->select([
                'student_achievements.*',
                'students.institute as student_institute',
            ])
            ->orderBy('student_achievements.id')
            ->chunkById(100, function ($achievements) {
                foreach ($achievements as $achievement) {
                    DB::table('community_posts')->updateOrInsert(
                        [
                            'source_type' => 'StudentAchievement',
                            'source_id' => $achievement->id,
                        ],
                        [
                            'post_type' => 'Achievement',
                            'title' => $achievement->title,
                            'body' => $this->achievementBody(
                                $achievement->description,
                                $achievement->achievement_type,
                                $achievement->organizer,
                                $achievement->position
                            ),
                            'author_type' => 'Student',
                            'author_id' => $achievement->student_id,
                            'institute' => $achievement->student_institute,
                            'status' => 'Approved',
                            'published_at' => $achievement->updated_at ?? now(),
                            'approved_at' => $achievement->updated_at ?? now(),
                            'attachment_path' => $achievement->certificate_file,
                            'attachment_original_name' => $achievement->certificate_file ? 'Achievement Proof' : null,
                            'created_at' => $achievement->created_at ?? now(),
                            'updated_at' => now(),
                        ]
                    );
                }
            }, 'student_achievements.id', 'id');
    }

    private function backfillTeacherAchievements(): void
    {
        if (!Schema::hasTable('teacher_achievements') || !Schema::hasTable('users')) {
            return;
        }

        DB::table('teacher_achievements')
            ->join('users', 'users.id', '=', 'teacher_achievements.user_id')
            ->where('teacher_achievements.verification_status', 'Approved')
            ->select([
                'teacher_achievements.*',
                'users.institute as teacher_institute',
            ])
            ->orderBy('teacher_achievements.id')
            ->chunkById(100, function ($achievements) {
                foreach ($achievements as $achievement) {
                    DB::table('community_posts')->updateOrInsert(
                        [
                            'source_type' => 'TeacherAchievement',
                            'source_id' => $achievement->id,
                        ],
                        [
                            'post_type' => 'Achievement',
                            'title' => $achievement->title,
                            'body' => $this->achievementBody(
                                $achievement->description,
                                $achievement->achievement_type,
                                $achievement->organizer,
                                $achievement->position
                            ),
                            'author_type' => 'Teacher',
                            'author_id' => $achievement->user_id,
                            'institute' => $achievement->teacher_institute,
                            'status' => 'Approved',
                            'published_at' => $achievement->updated_at ?? now(),
                            'approved_at' => $achievement->updated_at ?? now(),
                            'attachment_path' => $achievement->certificate_file,
                            'attachment_original_name' => $achievement->certificate_file ? 'Achievement Proof' : null,
                            'created_at' => $achievement->created_at ?? now(),
                            'updated_at' => now(),
                        ]
                    );
                }
            }, 'teacher_achievements.id', 'id');
    }

    private function backfillMySpace(): void
    {
        if (!Schema::hasTable('my_spaces')) {
            return;
        }

        DB::table('my_spaces')
            ->whereIn('status', ['Approved', 'Featured'])
            ->orderBy('id')
            ->chunkById(100, function ($items) {
                foreach ($items as $item) {
                    $submitter = $item->created_by_type === 'Student'
                        ? DB::table('students')->where('id', $item->created_by_id)->first()
                        : DB::table('users')->where('id', $item->created_by_id)->first();

                    if (!$submitter) {
                        continue;
                    }

                    DB::table('community_posts')->updateOrInsert(
                        [
                            'source_type' => 'MySpace',
                            'source_id' => $item->id,
                        ],
                        [
                            'post_type' => $item->status === 'Featured'
                                ? 'Featured My Space'
                                : 'My Space ' . $item->type,
                            'title' => $item->title,
                            'body' => trim($item->description . ($item->repository_link ? "\n\nProject Link: {$item->repository_link}" : '')),
                            'author_type' => $item->created_by_type,
                            'author_id' => $item->created_by_id,
                            'institute' => $submitter->institute ?? null,
                            'status' => 'Approved',
                            'published_at' => $item->updated_at ?? now(),
                            'approved_at' => $item->updated_at ?? now(),
                            'attachment_path' => $item->blueprint_pdf,
                            'attachment_original_name' => $item->blueprint_pdf ? 'Idea Blueprint' : null,
                            'created_at' => $item->created_at ?? now(),
                            'updated_at' => now(),
                        ]
                    );
                }
            });
    }

    private function achievementBody(?string $description, ?string $type, ?string $organizer, ?string $position): string
    {
        return implode("\n", array_filter([
            $description,
            $type ? 'Type: ' . $type : null,
            $organizer ? 'Organizer: ' . $organizer : null,
            $position ? 'Position: ' . $position : null,
        ])) ?: 'Achievement approved and shared with the InnovatEdge community.';
    }
};
