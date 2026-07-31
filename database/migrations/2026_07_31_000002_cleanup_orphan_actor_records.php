<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    private const USER_TYPES = ['Admin', 'InstituteAdmin', 'Teacher'];

    public function up(): void
    {
        $this->deleteOrphanCommunityActors();
        $this->deleteOrphanCommunitySources();
        $this->deleteMissingUserReferences();
    }

    public function down(): void
    {
        // Deleted orphan data cannot be restored safely.
    }

    private function deleteOrphanCommunityActors(): void
    {
        if (!Schema::hasTable('community_posts')) {
            return;
        }

        $postIds = collect();

        $postIds = $postIds->merge(
            DB::table('community_posts')
                ->where('author_type', 'Student')
                ->whereNotExists(function ($query) {
                    $query->selectRaw('1')
                        ->from('students')
                        ->whereColumn('students.id', 'community_posts.author_id');
                })
                ->pluck('id')
        );

        foreach (self::USER_TYPES as $role) {
            $postIds = $postIds->merge(
                DB::table('community_posts')
                    ->where('author_type', $role)
                    ->whereNotExists(function ($query) use ($role) {
                        $query->selectRaw('1')
                            ->from('users')
                            ->whereColumn('users.id', 'community_posts.author_id')
                            ->where('users.role', $role);
                    })
                    ->pluck('id')
            );
        }

        $postIds = $postIds->merge(
            DB::table('community_posts')
                ->whereNotIn('author_type', array_merge(self::USER_TYPES, ['Student']))
                ->pluck('id')
        )->unique()->values();

        $this->deleteCommunityPosts($postIds);

        $this->deleteOrphanActorsFromTable('community_post_likes', 'liker_type', 'liker_id');
        $this->deleteOrphanActorsFromTable('community_post_comments', 'commenter_type', 'commenter_id');
    }

    private function deleteOrphanCommunitySources(): void
    {
        if (
            !Schema::hasTable('community_posts') ||
            !Schema::hasColumn('community_posts', 'source_type') ||
            !Schema::hasColumn('community_posts', 'source_id')
        ) {
            return;
        }

        $sources = [
            'StudentAchievement' => 'student_achievements',
            'TeacherAchievement' => 'teacher_achievements',
            'MySpace' => 'my_spaces',
        ];

        foreach ($sources as $sourceType => $sourceTable) {
            if (!Schema::hasTable($sourceTable)) {
                continue;
            }

            $ids = DB::table('community_posts')
                ->where('source_type', $sourceType)
                ->whereNotExists(function ($query) use ($sourceTable) {
                    $query->selectRaw('1')
                        ->from($sourceTable)
                        ->whereColumn("{$sourceTable}.id", 'community_posts.source_id');
                })
                ->pluck('id');

            $this->deleteCommunityPosts($ids);
        }
    }

    private function deleteCommunityPosts($ids): void
    {
        $ids = collect($ids)->filter()->unique()->values();

        if ($ids->isEmpty()) {
            return;
        }

        DB::table('community_posts')
            ->whereIn('id', $ids)
            ->whereNull('source_type')
            ->select(['image_path', 'attachment_path'])
            ->orderBy('id')
            ->chunk(100, function ($posts) {
                foreach ($posts as $post) {
                    $this->deleteStoredFile($post->image_path);
                    $this->deleteStoredFile($post->attachment_path);
                }
            });

        if (Schema::hasTable('community_post_likes')) {
            DB::table('community_post_likes')->whereIn('community_post_id', $ids)->delete();
        }

        if (Schema::hasTable('community_post_comments')) {
            DB::table('community_post_comments')->whereIn('community_post_id', $ids)->delete();
        }

        DB::table('community_posts')->whereIn('id', $ids)->delete();
    }

    private function deleteOrphanActorsFromTable(string $table, string $typeColumn, string $idColumn): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        DB::table($table)
            ->where($typeColumn, 'Student')
            ->whereNotExists(function ($query) use ($table, $idColumn) {
                $query->selectRaw('1')
                    ->from('students')
                    ->whereColumn('students.id', "{$table}.{$idColumn}");
            })
            ->delete();

        foreach (self::USER_TYPES as $role) {
            DB::table($table)
                ->where($typeColumn, $role)
                ->whereNotExists(function ($query) use ($table, $idColumn, $role) {
                    $query->selectRaw('1')
                        ->from('users')
                        ->whereColumn('users.id', "{$table}.{$idColumn}")
                        ->where('users.role', $role);
                })
                ->delete();
        }

        DB::table($table)
            ->whereNotIn($typeColumn, array_merge(self::USER_TYPES, ['Student']))
            ->delete();
    }

    private function deleteMissingUserReferences(): void
    {
        if (Schema::hasTable('pending_password_changes')) {
            DB::table('pending_password_changes')
                ->whereNotExists(function ($query) {
                    $query->selectRaw('1')
                        ->from('users')
                        ->whereColumn('users.id', 'pending_password_changes.user_id');
                })
                ->delete();
        }

        if (Schema::hasTable('lms_notifications') && Schema::hasColumn('lms_notifications', 'created_by')) {
            DB::table('lms_notifications')
                ->whereNotNull('created_by')
                ->whereNotExists(function ($query) {
                    $query->selectRaw('1')
                        ->from('users')
                        ->whereColumn('users.id', 'lms_notifications.created_by');
                })
                ->update(['created_by' => null]);
        }

        if (Schema::hasTable('community_posts') && Schema::hasColumn('community_posts', 'approved_by')) {
            DB::table('community_posts')
                ->whereNotNull('approved_by')
                ->whereNotExists(function ($query) {
                    $query->selectRaw('1')
                        ->from('users')
                        ->whereColumn('users.id', 'community_posts.approved_by');
                })
                ->update(['approved_by' => null]);
        }
    }

    private function deleteStoredFile(?string $path): void
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
};
