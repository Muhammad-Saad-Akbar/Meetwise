<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Recording extends Model
{
    protected $fillable = [
        'meeting_id',
        'file_path',
        'mime_type',
        'status',
        'chunk_count',
        'size',
        'duration',
        'started_at',
        'ended_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at'   => 'datetime',
    ];

    // Relationship: Recording belongs to a Meeting
    public function meeting()
    {
        return $this->belongsTo(Meeting::class);
    }
}
