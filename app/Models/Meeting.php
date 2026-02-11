<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Meeting extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'host_id',
        'meeting_code',
        'type',
        'scheduled_at',
        'status',
    ];

    // Relationship: Meeting belongs to a User (host)
    public function host()
    {
        return $this->belongsTo(User::class, 'host_id');
    }
}
