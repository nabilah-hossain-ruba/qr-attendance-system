<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'course_code',
        'course_name',
        'section',
        'semester',
        'year',
        'lecturer_id',
        'schedule',
        'room',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'year' => 'integer',
    ];

    /**
     * Get the lecturer assigned to this course section.
     */
    public function lecturer()
    {
        return $this->belongsTo(User::class, 'lecturer_id');
    }

    /**
     * Get the sessions for this course section.
     */
    public function sessions()
    {
        return $this->hasMany(Session::class, 'course_id');
    }

    /**
     * Get the attendance records for this course section.
     */
    public function attendances()
    {
        return $this->hasManyThrough(
            Attendance::class,
            Session::class,
            'course_id',
            'session_id',
            'id',
            'id'
        );
    }

    /**
     * Get the students enrolled in this course section.
     */
    public function students()
    {
        return $this->belongsToMany(User::class, 'course_student', 'course_id', 'user_id');
    }

    /**
     * Scope to get courses by semester.
     */
    public function scopeBySemester($query, $semester)
    {
        return $query->where('semester', $semester);
    }

    /**
     * Scope to get courses by year.
     */
    public function scopeByYear($query, $year)
    {
        return $query->where('year', $year);
    }

    /**
     * Scope to get courses by course code.
     */
    public function scopeByCode($query, $code)
    {
        return $query->where('course_code', $code);
    }
}
