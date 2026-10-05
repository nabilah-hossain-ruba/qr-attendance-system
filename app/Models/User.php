<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'name',
        'email',
        'password',
        'role',
        'user_type',
        'student_id',
        'lecturer_id',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'password' => 'hashed',
        'is_active' => 'boolean',
    ];
    
    /**
     * Check if user is a lecturer
     *
     * @return bool
     */
    public function isLecturer()
    {
        return $this->role === 'lecturer';
    }
    
    /**
     * Check if user is a student
     *
     * @return bool
     */
    public function isStudent()
    {
        return $this->role === 'student';
    }
    
    /**
     * Check if user is an admin
     *
     * @return bool
     */
    public function isAdmin()
    {
        return $this->role === 'admin';
    }
    
    /**
     * Get the courses that the user is enrolled in or teaching
     */
    public function courses()
    {
        if ($this->isStudent()) {
            return $this->belongsToMany(Course::class, 'course_student');
        }
        
        return $this->hasMany(Course::class, 'lecturer_id');
    }
    
    /**
     * Get the attendance records for the user
     */
    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }
    
}
