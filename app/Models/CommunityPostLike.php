<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CommunityPostLike extends Model
{
    protected $fillable = [
        'community_post_id',
        'liker_type',
        'liker_id',
    ];

    public function post()
    {
        return $this->belongsTo(CommunityPost::class, 'community_post_id');
    }

    public function scopeWithExistingActor(Builder $query): Builder
    {
        return $query->where(function (Builder $actors) {
            $actors->where(function (Builder $students) {
                $students->where('liker_type', 'Student')
                    ->whereExists(function ($exists) {
                        $exists->selectRaw('1')
                            ->from('students')
                            ->whereColumn('students.id', 'community_post_likes.liker_id');
                    });
            });

            foreach (['Admin', 'InstituteAdmin', 'Teacher'] as $role) {
                $actors->orWhere(function (Builder $users) use ($role) {
                    $users->where('liker_type', $role)
                        ->whereExists(function ($exists) use ($role) {
                            $exists->selectRaw('1')
                                ->from('users')
                                ->whereColumn('users.id', 'community_post_likes.liker_id')
                                ->where('users.role', $role);
                        });
                });
            }
        });
    }
}
