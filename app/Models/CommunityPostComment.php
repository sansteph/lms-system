<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommunityPostComment extends Model
{
    protected $fillable = [
        'community_post_id',
        'commenter_type',
        'commenter_id',
        'body',
    ];

    public function post()
    {
        return $this->belongsTo(CommunityPost::class, 'community_post_id');
    }

    public function commenter()
    {
        if ($this->commenter_type === 'Student') {
            return Student::find($this->commenter_id);
        }

        return User::find($this->commenter_id);
    }

    public function commenterName(): string
    {
        return $this->commenter()?->name ?? 'Deleted User';
    }

    public function roleLabel(): string
    {
        return match ($this->commenter_type) {
            'Admin' => 'Admin',
            'InstituteAdmin' => 'Institute Admin',
            'Teacher' => 'STEM Engineer',
            'Student' => 'Student',
            default => $this->commenter_type,
        };
    }
}
