<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attendance extends Model
{
    protected $fillable = [
        'user_id',
        'date',
        'total_time',
        'total_break_time',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    /**
     * Get the user that owns the attendance.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the attendance logs for the attendance.
     */
    public function logs(): HasMany
    {
        return $this->hasMany(AttendanceLog::class);
    }
}
