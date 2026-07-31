<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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

    public function scopeWithExistingActor(Builder $query): Builder
    {
        return $query->where(function (Builder $actors) {
            $actors->where(function (Builder $students) {
                $students->where('commenter_type', 'Student')
                    ->whereExists(function ($exists) {
                        $exists->selectRaw('1')
                            ->from('students')
                            ->whereColumn('students.id', 'community_post_comments.commenter_id');
                    });
            });

            foreach (['Admin', 'InstituteAdmin', 'Teacher'] as $role) {
                $actors->orWhere(function (Builder $users) use ($role) {
                    $users->where('commenter_type', $role)
                        ->whereExists(function ($exists) use ($role) {
                            $exists->selectRaw('1')
                                ->from('users')
                                ->whereColumn('users.id', 'community_post_comments.commenter_id')
                                ->where('users.role', $role);
                        });
                });
            }
        });
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
