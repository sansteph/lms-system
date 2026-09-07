<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiComponentContentProfile extends Model
{
    protected $fillable = [
        'content_id',
        'component_key',
        'component_label',
        'is_practical',
        'confidence',
        'evidence',
        'provider',
        'model',
        'analyzed_at',
    ];

    protected $casts = [
        'is_practical' => 'boolean',
        'evidence' => 'array',
        'analyzed_at' => 'datetime',
    ];

    public function content()
    {
        return $this->belongsTo(Content::class);
    }
}
