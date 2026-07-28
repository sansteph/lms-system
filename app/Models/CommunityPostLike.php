<?php

namespace App\Models;

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
}
