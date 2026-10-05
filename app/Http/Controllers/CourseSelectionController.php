<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Course;
use App\Models\User;

class CourseSelectionController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth']);
    }

    /**
     * Show course selection page
     */
    public function index()
    {
        $user = Auth::user();
        $availableCourses = Course::with('lecturer')->get();
        $userCourses = $user->courses;
        
        return view('course-selection', compact('availableCourses', 'userCourses', 'user'));
    }

    /**
     * Enroll in or assign courses
     */
    public function store(Request $request)
    {
        $request->validate([
            'courses' => 'required|array',
            'courses.*' => 'exists:courses,id'
        ]);

        $user = Auth::user();
        
        if ($user->isStudent()) {
            // For students: enroll in courses
            $user->courses()->sync($request->courses);
            $message = 'Successfully enrolled in selected courses!';
            $redirect = route('student.dashboard');
        } elseif ($user->isLecturer()) {
            // For lecturers: assign as lecturer to courses
            Course::whereIn('id', $request->courses)
                  ->update(['lecturer_id' => $user->id]);
            $message = 'Successfully assigned to selected courses!';
            $redirect = route('lecturer.dashboard');
        } else {
            return redirect()->back()->with('error', 'Invalid user role');
        }

        return redirect($redirect)->with('status', $message);
    }
}
