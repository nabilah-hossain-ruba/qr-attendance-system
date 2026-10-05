<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Session extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'course_id',
        'date',
        'start_time',
        'end_time',
        'qr_code',
        'qr_expiry',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date' => 'date',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'qr_expiry' => 'datetime',
    ];

    /**
     * Get the course that owns the session.
     */
    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Get the attendance records for the session.
     */
    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * Check if the QR code has expired.
     */
    public function isQrExpired()
    {
        return now()->gt($this->qr_expiry);
    }

    /**
     * Generate a new QR code for the session.
     */
    public function generateQrCode($expiryMinutes = 15)
    {
        // Generate a unique token for the QR code
        $token = encrypt($this->id . '_' . time());
        
        // Set the QR code and expiry time
        $this->qr_code = $token;
        $this->qr_expiry = now()->addMinutes($expiryMinutes);
        $this->save();
        
        return $token;
    }

    /**
     * Get the attendance percentage for the session.
     */
    public function getAttendancePercentage()
    {
        $totalStudents = $this->course->students()->count();
        if ($totalStudents === 0) {
            return 0;
        }
        
        $attendedStudents = $this->attendances()->count();
        return ($attendedStudents / $totalStudents) * 100;
    }
}