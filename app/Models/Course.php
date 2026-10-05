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
        'bracu_section_id',
        'course_code',
        'course_name',
        'course_type',
        'section',
        'semester',
        'year',
        'lecturer_id',
        'faculty_initials',
        'schedule',
        'class_schedules',
        'class_start_date',
        'class_end_date',
        'room',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'year' => 'integer',
        'class_schedules' => 'array',
        'class_start_date' => 'date',
        'class_end_date' => 'date',
        'synced_at' => 'datetime',
    ];

    /**
     * Class slots ([day, startTime, endTime]) this section meets on the given date,
     * according to the synced BRACU timetable.
     *
     * @param  \Carbon\Carbon  $date
     * @return array
     */
    public function classesOn($date)
    {
        if (empty($this->class_schedules)) {
            return [];
        }

        // Compare calendar dates only, so timezones can't shift the semester by a day
        $dateString = $date->toDateString();

        if (($this->class_start_date && $dateString < $this->class_start_date->toDateString())
            || ($this->class_end_date && $dateString > $this->class_end_date->toDateString())) {
            return [];
        }

        $day = strtoupper($date->format('l'));

        return array_values(array_filter($this->class_schedules, function ($slot) use ($day) {
            return strtoupper($slot['day'] ?? '') === $day && !empty($slot['startTime']) && !empty($slot['endTime']);
        }));
    }

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
