<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Enrollment extends Model
{
    use HasFactory;

    protected $table = 'enrollments';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'student_id',
        'course_code',
        'section_number',
        'enrollment_status',
        'enrollment_date',
        'drop_date',
        'grade',
        'semester',
        'academic_year',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'enrollment_date' => 'datetime',
        'drop_date' => 'datetime',
    ];

    /**
     * Get the student that owns this enrollment.
     */
    public function student()
    {
        return $this->belongsTo(User::class, 'student_id', 'student_id');
    }

    /**
     * Get the course for this enrollment.
     */
    public function course()
    {
        return $this->belongsTo(Course::class, 'course_code', 'code');
    }

    /**
     * Get the course section for this enrollment.
     */
    public function courseSection()
    {
        return $this->hasOne(CourseSection::class, 'course_code', 'course_code')
                    ->where('section_number', $this->section_number);
    }

    /**
     * Scope to get only enrolled students.
     */
    public function scopeEnrolled($query)
    {
        return $query->where('enrollment_status', 'enrolled');
    }

    /**
     * Scope to get enrollments by semester.
     */
    public function scopeBySemester($query, $semester, $academicYear = null)
    {
        $query = $query->where('semester', $semester);
        if ($academicYear) {
            $query = $query->where('academic_year', $academicYear);
        }
        return $query;
    }

    /**
     * Get the full course section identifier.
     */
    public function getFullCourseIdentifierAttribute()
    {
        return "{$this->course_code} - Section {$this->section_number}";
    }
}
