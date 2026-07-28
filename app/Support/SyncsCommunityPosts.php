<?php

namespace App\Support;

use App\Models\CommunityPost;
use App\Models\MySpace;
use App\Models\StudentAchievement;
use App\Models\TeacherAchievement;

trait SyncsCommunityPosts
{
    protected function syncStudentAchievementToCommunity(StudentAchievement $achievement): void
    {
        $student = $achievement->student;

        if ($achievement->verification_status !== 'Approved' || !$student) {
            $this->deleteCommunitySource('StudentAchievement', $achievement->id);
            return;
        }

        CommunityPost::updateOrCreate(
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
                'author_id' => $student->id,
                'institute' => $student->institute,
                'status' => 'Approved',
                'published_at' => now(),
                'approved_by' => session('user_id'),
                'approved_at' => now(),
                'rejected_at' => null,
                'attachment_path' => $achievement->certificate_file,
                'attachment_original_name' => $achievement->certificate_file ? 'Achievement Proof' : null,
            ]
        );
    }

    protected function syncTeacherAchievementToCommunity(TeacherAchievement $achievement): void
    {
        $teacher = $achievement->teacher;

        if ($achievement->verification_status !== 'Approved' || !$teacher) {
            $this->deleteCommunitySource('TeacherAchievement', $achievement->id);
            return;
        }

        CommunityPost::updateOrCreate(
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
                'author_id' => $teacher->id,
                'institute' => $teacher->institute,
                'status' => 'Approved',
                'published_at' => now(),
                'approved_by' => session('user_id'),
                'approved_at' => now(),
                'rejected_at' => null,
                'attachment_path' => $achievement->certificate_file,
                'attachment_original_name' => $achievement->certificate_file ? 'Achievement Proof' : null,
            ]
        );
    }

    protected function syncMySpaceToCommunity(MySpace $item): void
    {
        $submitter = $item->submitter();

        if (!in_array($item->status, ['Approved', 'Featured'], true) || !$submitter) {
            $this->deleteCommunitySource('MySpace', $item->id);
            return;
        }

        $body = trim($item->description . ($item->repository_link ? "\n\nProject Link: {$item->repository_link}" : ''));

        CommunityPost::updateOrCreate(
            [
                'source_type' => 'MySpace',
                'source_id' => $item->id,
            ],
            [
                'post_type' => $item->status === 'Featured'
                    ? 'Featured My Space'
                    : 'My Space ' . $item->type,
                'title' => $item->title,
                'body' => $body,
                'author_type' => $item->created_by_type,
                'author_id' => $item->created_by_id,
                'institute' => $submitter->institute ?? null,
                'status' => 'Approved',
                'published_at' => now(),
                'approved_by' => session('user_id'),
                'approved_at' => now(),
                'rejected_at' => null,
                'attachment_path' => $item->blueprint_pdf,
                'attachment_original_name' => $item->blueprint_pdf ? 'Idea Blueprint' : null,
            ]
        );
    }

    protected function deleteCommunitySource(string $sourceType, int $sourceId): void
    {
        CommunityPost::where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->delete();
    }

    private function achievementBody(?string $description, ?string $type, ?string $organizer, ?string $position): string
    {
        $details = array_filter([
            $description,
            $type ? 'Type: ' . $type : null,
            $organizer ? 'Organizer: ' . $organizer : null,
            $position ? 'Position: ' . $position : null,
        ]);

        return implode("\n", $details) ?: 'Achievement approved and shared with the InnovatEdge community.';
    }
}
