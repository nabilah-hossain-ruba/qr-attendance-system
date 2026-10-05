<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware(['auth', 'role:student']);
    }

    /**
     * Show the student dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function dashboard()
    {
        $user = Auth::user();
        $courses = $user->courses;
        $attendances = $user->attendances()->with('session.course')->latest()->take(10)->get();
        
        return view('student.dashboard', compact('courses', 'attendances'));
    }
    
    /**
     * Show the QR code scanner page.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function scanQr()
    {
        return view('student.scan-qr');
    }
    
    /**
     * Show available courses for enrollment.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function showCourses()
    {
        $user = Auth::user();
        $enrolledCourses = $user->courses->pluck('id')->toArray();
        $availableCourses = \App\Models\Course::with('lecturer')
            ->whereNotIn('id', $enrolledCourses)
            ->get();
        
        return view('student.courses', compact('availableCourses', 'enrolledCourses'));
    }
    
    /**
     * Enroll student in selected courses.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function enrollCourses(Request $request)
    {
        $request->validate([
            'courses' => 'required|array',
            'courses.*' => 'exists:courses,id'
        ]);

        $user = Auth::user();
        
        // Get currently enrolled course IDs
        $currentCourses = $user->courses->pluck('id')->toArray();
        
        // Merge with new selections (prevent duplicates)
        $allCourses = array_unique(array_merge($currentCourses, $request->courses));
        
        // Sync the courses
        $user->courses()->sync($allCourses);
        
        $enrolledCount = count($request->courses);
        $message = "Successfully enrolled in {$enrolledCount} course(s)!";
        
        return redirect()->route('student.dashboard')->with('success', $message);
    }
    
    /**
     * Unenroll from a course.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function unenrollCourse(Request $request)
    {
        $request->validate([
            'course_id' => 'required|exists:courses,id'
        ]);

        $user = Auth::user();
        $course = \App\Models\Course::findOrFail($request->course_id);
        
        // Check if student is enrolled
        if ($user->courses->contains($course->id)) {
            $user->courses()->detach($course->id);
            $message = "Successfully unenrolled from {$course->course_code} - {$course->course_name}";
            return redirect()->back()->with('success', $message);
        } else {
            return redirect()->back()->with('error', 'You are not enrolled in this course.');
        }
    }
}
