<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Enrollment;
use App\Models\User;

class CourseEnrollmentController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware(['auth']);
    }

    /**
     * Show the course enrollment page for both students and lecturers.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function addCourses()
    {
        $user = Auth::user();
        
        // Get all active courses
        $courses = Course::active()
                        ->with(['sections' => function($query) {
                            $query->active();
                        }])
                        ->orderBy('code')
                        ->get();
        
        // Get user's current enrollments/assignments based on user type
        $userEnrollments = [];
        if ($user->user_type === 'student') {
            $enrollments = Enrollment::where('student_id', $user->student_id)
                                   ->where('enrollment_status', 'enrolled')
                                   ->get();
            foreach ($enrollments as $enrollment) {
                $userEnrollments[] = $enrollment->course_code . '_' . $enrollment->section_number;
            }
        } elseif ($user->user_type === 'lecturer') {
            $sections = CourseSection::where('lecturer_id', $user->lecturer_id)
                                   ->active()
                                   ->get();
            foreach ($sections as $section) {
                $userEnrollments[] = $section->course_code . '_' . $section->section_number;
            }
        }
        
        return view('course-enrollment.add-courses', compact('courses', 'userEnrollments', 'user'));
    }

    /**
     * Get sections for a specific course code via AJAX.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getSections(Request $request)
    {
        $request->validate([
            'course_code' => 'required|string'
        ]);

        $user = Auth::user();
        $courseCode = $request->course_code;
        
        $sections = CourseSection::where('course_code', $courseCode)
                                ->active()
                                ->with(['lecturer', 'course'])
                                ->get()
                                ->map(function ($section) use ($user) {
                                    $isAvailable = true;
                                    $status = 'available';
                                    $statusText = 'Available';
                                    
                                    if ($user->user_type === 'student') {
                                        // Check if student is already enrolled in this section
                                        $enrolled = Enrollment::where('student_id', $user->student_id)
                                                            ->where('course_code', $section->course_code)
                                                            ->where('section_number', $section->section_number)
                                                            ->where('enrollment_status', 'enrolled')
                                                            ->exists();
                                        
                                        if ($enrolled) {
                                            $isAvailable = false;
                                            $status = 'enrolled';
                                            $statusText = 'Already Enrolled';
                                        } elseif ($section->isFull()) {
                                            $isAvailable = false;
                                            $status = 'full';
                                            $statusText = 'Section Full';
                                        }
                                        
                                    } elseif ($user->user_type === 'lecturer') {
                                        // Check lecturer assignment status
                                        if ($section->lecturer_id === $user->lecturer_id) {
                                            $isAvailable = false;
                                            $status = 'assigned';
                                            $statusText = 'Already Assigned to You';
                                        } elseif ($section->lecturer_id) {
                                            $isAvailable = false;
                                            $status = 'taken';
                                            $statusText = 'Assigned to ' . ($section->lecturer ? $section->lecturer->first_name . ' ' . $section->lecturer->last_name : 'Another Lecturer');
                                        }
                                    }
                                    
                                    return [
                                        'id' => $section->id,
                                        'section_number' => $section->section_number,
                                        'room' => $section->room,
                                        'lecturer_name' => $section->lecturer ? $section->lecturer->first_name . ' ' . $section->lecturer->last_name : 'Not Assigned',
                                        'current_enrollment' => $section->current_enrollment,
                                        'max_capacity' => $section->max_capacity,
                                        'available_spots' => $section->available_spots,
                                        'semester' => $section->semester,
                                        'academic_year' => $section->academic_year,
                                        'is_available' => $isAvailable,
                                        'status' => $status,
                                        'status_text' => $statusText,
                                        'enrollment_status_text' => $section->enrollment_status_text
                                    ];
                                });

        return response()->json([
            'success' => true,
            'sections' => $sections
        ]);
    }

    /**
     * Enroll user in selected course section.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function enrollInCourse(Request $request)
    {
        $request->validate([
            'section_id' => 'required|exists:course_sections,id'
        ]);

        $user = Auth::user();
        $section = CourseSection::with('course')->findOrFail($request->section_id);
        
        // Get current semester and academic year from system settings
        $currentSemester = DB::table('system_settings')
                            ->where('setting_key', 'current_semester')
                            ->value('setting_value') ?? 'Fall 2025';
        $currentAcademicYear = DB::table('system_settings')
                                ->where('setting_key', 'current_academic_year')
                                ->value('setting_value') ?? '2025-2026';

        if ($user->user_type === 'student') {
            // Check if student is already enrolled in this section
            $existingEnrollment = Enrollment::where('student_id', $user->student_id)
                                          ->where('course_code', $section->course_code)
                                          ->where('section_number', $section->section_number)
                                          ->where('enrollment_status', 'enrolled')
                                          ->exists();
            
            if ($existingEnrollment) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are already enrolled in this course section.'
                ], 400);
            }
            
            // Check if section is full
            if ($section->isFull()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This section is full. Please select another section.'
                ], 400);
            }
            
            try {
                DB::beginTransaction();
                
                // Create enrollment record
                Enrollment::create([
                    'student_id' => $user->student_id,
                    'course_code' => $section->course_code,
                    'section_number' => $section->section_number,
                    'enrollment_status' => 'enrolled',
                    'enrollment_date' => now(),
                    'semester' => $currentSemester,
                    'academic_year' => $currentAcademicYear,
                ]);
                
                DB::commit();
                
                $message = "Successfully enrolled in {$section->course_code} - Section {$section->section_number}!";
                
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Error enrolling in course: ' . $e->getMessage()
                ], 500);
            }
            
        } elseif ($user->user_type === 'lecturer') {
            // Check if lecturer is already assigned to this section
            if ($section->lecturer_id === $user->lecturer_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are already assigned to this course section.'
                ], 400);
            }
            
            // Check if another lecturer is already assigned
            if ($section->lecturer_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'This course section is already assigned to another lecturer.'
                ], 400);
            }
            
            try {
                // Assign lecturer to section
                $section->lecturer_id = $user->lecturer_id;
                $section->save();
                
                $message = "Successfully assigned to {$section->course_code} - Section {$section->section_number}!";
                
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error assigning to course: ' . $e->getMessage()
                ], 500);
            }
            
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Invalid user role for course enrollment.'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'course' => [
                'code' => $section->course_code,
                'name' => $section->course->name,
                'section' => $section->section_number
            ]
        ]);
    }

    /**
     * Remove user from course section.
     *
     * @param  \Illuminate\\Http\\Request  $request
     * @return \Illuminate\\Http\\Response
     */
    public function removeFromCourse(Request $request)
    {
        $request->validate([
            'course_id' => 'required|exists:courses,id'
        ]);

        $user = Auth::user();
        $course = Course::findOrFail($request->course_id);

        if ($user->isStudent()) {
            if (!$user->courses->contains($course->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not enrolled in this course section.'
                ], 400);
            }

            $user->courses()->detach($course->id);
            $message = "Successfully unenrolled from {$course->course_code} - Section {$course->section}";
            
        } elseif ($user->isLecturer()) {
            if ($course->lecturer_id != $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not assigned to this course section.'
                ], 400);
            }

            $course->lecturer_id = null;
            $course->save();
            $message = "Successfully unassigned from {$course->course_code} - Section {$course->section}";
            
        }

        return response()->json([
            'success' => true,
            'message' => $message
        ]);
    }
}
