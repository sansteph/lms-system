<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiContentSummary extends Model
{
    protected $fillable = [
        'content_id',
        'provider',
        'model',
        'source_file_path',
        'source_hash',
        'extracted_text',
        'summary',
        'key_points',
        'quiz_seed',
        'status',
        'error_message',
        'generated_by',
        'generated_at',
    ];

    protected $casts = [
        'key_points' => 'array',
        'quiz_seed' => 'array',
        'generated_at' => 'datetime',
    ];

    public function content()
    {
        return $this->belongsTo(Content::class);
    }
}
