<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'session_id',
        'user_id',
        'status',
        'marked_at',
        'ip_address',
        'device_info',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'marked_at' => 'datetime',
    ];

    /**
     * Get the session that owns the attendance record.
     */
    public function session()
    {
        return $this->belongsTo(Session::class);
    }

    /**
     * Get the user that owns the attendance record.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include attendance records for a specific course.
     */
    public function scopeCourse($query, $courseId)
    {
        return $query->whereHas('session', function ($q) use ($courseId) {
            $q->where('course_id', $courseId);
        });
    }

    /**
     * Scope a query to only include attendance records for a specific date.
     */
    public function scopeDate($query, $date)
    {
        return $query->whereHas('session', function ($q) use ($date) {
            $q->whereDate('date', $date);
        });
    }

    /**
     * Check if the attendance was marked within the valid time window.
     */
    public function isMarkedOnTime()
    {
        return $this->marked_at->lt($this->session->qr_expiry);
    }
}
