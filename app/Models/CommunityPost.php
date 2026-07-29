<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommunityPost extends Model
{
    protected $fillable = [
        'title',
        'body',
        'post_type',
        'image_path',
        'attachment_path',
        'attachment_original_name',
        'author_type',
        'author_id',
        'institute',
        'status',
        'published_at',
        'approved_by',
        'approved_at',
        'rejected_at',
        'source_type',
        'source_id',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function likes()
    {
        return $this->hasMany(CommunityPostLike::class);
    }

    public function comments()
    {
        return $this->hasMany(CommunityPostComment::class);
    }

    public function author()
    {
        if ($this->author_type === 'Student') {
            return Student::find($this->author_id);
        }

        return User::find($this->author_id);
    }

    public function authorName(): string
    {
        return $this->author()?->name ?? 'Deleted User';
    }

    public function roleLabel(): string
    {
        return match ($this->author_type) {
            'Admin' => 'Admin',
            'InstituteAdmin' => 'Institute Admin',
            'Teacher' => 'STEM Engineer',
            'Student' => 'Student',
            default => $this->author_type,
        };
    }

    public function authorProfileImage(): ?string
    {
        if ($this->author_type === 'Student') {
            return null;
        }

        return $this->author()?->profile_image;
    }

    public function authorMeta(): string
    {
        if ($this->author_type === 'Student') {
            return $this->institute ?: 'Student';
        }

        return trim($this->roleLabel() . ($this->institute ? ' | ' . $this->institute : ''));
    }

    public function isSystemSynced(): bool
    {
        return $this->source_type !== null && $this->source_id !== null;
    }

    public function isLikedBy(string $type, int $id): bool
    {
        return $this->likes->contains(function ($like) use ($type, $id) {
            return $like->liker_type === $type && (int) $like->liker_id === $id;
        });
    }
}
