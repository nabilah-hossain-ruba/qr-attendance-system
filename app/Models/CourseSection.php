<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseSection extends Model
{
    use HasFactory;

    protected $table = 'course_sections';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'course_code',
        'section_number',
        'lecturer_id',
        'room',
        'max_capacity',
        'current_enrollment',
        'semester',
        'academic_year',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_active' => 'boolean',
        'max_capacity' => 'integer',
        'current_enrollment' => 'integer',
    ];

    /**
     * Get the course that owns this section.
     */
    public function course()
    {
        return $this->belongsTo(Course::class, 'course_code', 'code');
    }

    /**
     * Get the lecturer assigned to this section.
     */
    public function lecturer()
    {
        return $this->belongsTo(User::class, 'lecturer_id', 'lecturer_id');
    }

    /**
     * Get the students enrolled in this section.
     */
    public function students()
    {
        return $this->belongsToMany(
            User::class,
            'enrollments',
            'course_code',
            'student_id',
            'course_code',
            'student_id'
        )
        ->wherePivot('section_number', $this->section_number)
        ->wherePivot('enrollment_status', 'enrolled');
    }

    /**
     * Get the enrollments for this section.
     */
    public function enrollments()
    {
        return $this->hasMany(Enrollment::class, 'course_code', 'course_code')
                    ->where('section_number', $this->section_number);
    }

    /**
     * Get the schedules for this section.
     */
    public function schedules()
    {
        return $this->hasMany(Schedule::class, 'course_code', 'course_code')
                    ->where('section_number', $this->section_number);
    }

    /**
     * Check if the section is full.
     */
    public function isFull()
    {
        return $this->current_enrollment >= $this->max_capacity;
    }

    /**
     * Check if the section is almost full (90% capacity).
     */
    public function isAlmostFull()
    {
        return $this->current_enrollment >= ($this->max_capacity * 0.9);
    }

    /**
     * Get available spots in this section.
     */
    public function getAvailableSpotsAttribute()
    {
        return $this->max_capacity - $this->current_enrollment;
    }

    /**
     * Get the full section identifier.
     */
    public function getFullSectionIdentifierAttribute()
    {
        return "{$this->course_code} - Section {$this->section_number}";
    }

    /**
     * Get enrollment status text.
     */
    public function getEnrollmentStatusTextAttribute()
    {
        if ($this->isFull()) {
            return 'Full';
        } elseif ($this->isAlmostFull()) {
            return 'Almost Full';
        } else {
            return 'Available';
        }
    }

    /**
     * Scope to get only active sections.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get sections with available spots.
     */
    public function scopeWithAvailableSpots($query)
    {
        return $query->whereRaw('current_enrollment < max_capacity');
    }

    /**
     * Scope to get sections by semester and academic year.
     */
    public function scopeBySemester($query, $semester, $academicYear = null)
    {
        $query = $query->where('semester', $semester);
        if ($academicYear) {
            $query = $query->where('academic_year', $academicYear);
        }
        return $query;
    }
}
