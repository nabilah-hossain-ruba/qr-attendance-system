<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\DB;
use App\Models\Session;
use App\Models\Course;
use App\Models\User;
use App\Models\Enrollment;

class LecturerController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware(['auth', 'role:lecturer']);
    }

    /**
     * Show the lecturer dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function dashboard()
    {
        $user = Auth::user();
        $courses = $user->courses;
        $sessions = Session::whereIn('course_id', $courses->pluck('id'))
                          ->with('course')
                          ->latest()
                          ->take(10)
                          ->get();
        $todaysClasses = $this->todaysClasses($courses);
        
        return view('lecturer.dashboard', compact('courses', 'sessions', 'todaysClasses'));
    }

    /**
     * The lecturer's classes scheduled for today (BRACU timetable), in start-time order,
     * each with the latest attendance session generated for it today.
     *
     * @param  \Illuminate\Support\Collection  $courses
     * @return \Illuminate\Support\Collection
     */
    private function todaysClasses($courses)
    {
        $timezone = config('bracu.timezone');
        $now = now($timezone);
        $today = $now->copy()->startOfDay();

        $classes = $courses->flatMap(function ($course) use ($today, $timezone) {
            return collect($course->classesOn($today))->map(function ($slot) use ($course, $today, $timezone) {
                return [
                    'course' => $course,
                    'start' => Carbon::parse($today->toDateString() . ' ' . $slot['startTime'], $timezone),
                    'end' => Carbon::parse($today->toDateString() . ' ' . $slot['endTime'], $timezone),
                ];
            });
        });

        // Sessions generated since local midnight (timestamps are stored in the app timezone)
        $sessionsToday = Session::whereIn('course_id', $classes->pluck('course.id')->unique())
                                ->where('created_at', '>=', $today->copy()->setTimezone(config('app.timezone')))
                                ->latest()
                                ->get()
                                ->groupBy('course_id');

        return $classes->map(function ($class) use ($now, $sessionsToday) {
            $session = optional($sessionsToday->get($class['course']->id))->first();

            if ($now->lt($class['start']->copy()->subMinutes(15))) {
                $class['status'] = 'upcoming';
            } elseif ($now->lte($class['end'])) {
                $class['status'] = 'now';
            } else {
                $class['status'] = 'ended';
            }

            $class['session'] = $session;
            $class['qr_active'] = $session && $session->status === 'active' && !$session->isQrExpired();

            return $class;
        })->sortBy('start')->values();
    }
    
    /**
     * Generate QR code for a session.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function generateQrCode(Request $request)
    {
        $request->validate([
            'course_id' => 'required|exists:courses,id',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ], [
            'latitude.required' => 'Your location is required to generate a QR code. Please allow location access.',
            'longitude.required' => 'Your location is required to generate a QR code. Please allow location access.',
        ]);
        
        $course = Course::findOrFail($request->course_id);
        
        // Check if the lecturer is assigned to this course
        if ($course->lecturer_id !== Auth::id()) {
            return back()->with('error', 'You are not authorized to generate QR codes for this course.');
        }
        
        // Create a new session
        $session = new Session();
        $session->course_id = $request->course_id;
        $session->date = now()->toDateString();
        $session->start_time = now()->toTimeString();
        $session->end_time = now()->addHours(1)->toTimeString(); // Default 1 hour session
        $session->qr_code = 'ATTENDANCE_' . $request->course_id . '_' . time() . '_' . bin2hex(random_bytes(8)); // Generate a more readable QR code
        $session->qr_expiry = now()->addMinutes(config('attendance.qr_valid_minutes'));
        $session->latitude = $request->latitude;
        $session->longitude = $request->longitude;
        $session->radius_meters = config('attendance.radius_meters');
        $session->status = 'active';
        $session->save();
        
        return redirect()->route('lecturer.session.show', $session->id)
                         ->with('status', 'QR code generated successfully!');
    }
    
    /**
     * Show session details with QR code.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function showSession($id)
    {
        $session = Session::with(['course', 'attendances.user'])->findOrFail($id);
        
        // Check if the lecturer is assigned to this course
        if ($session->course->lecturer_id !== Auth::id()) {
            return redirect()->route('lecturer.dashboard')
                             ->with('error', 'You are not authorized to view this session.');
        }
        
        return view('lecturer.session', compact('session'));
    }
    
    /**
     * Show QR code only display.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function showQrOnly($id)
    {
        $session = Session::with(['course', 'attendances'])->findOrFail($id);
        
        // Check if the lecturer is assigned to this course
        if ($session->course->lecturer_id !== Auth::id()) {
            return redirect()->route('lecturer.dashboard')
                             ->with('error', 'You are not authorized to view this session.');
        }
        
        return view('lecturer.qr-display', compact('session'));
    }
    
    /**
     * Close a session.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function closeSession($id)
    {
        $session = Session::findOrFail($id);
        
        // Check if the lecturer is assigned to this course
        if ($session->course->lecturer_id !== Auth::id()) {
            return redirect()->route('lecturer.dashboard')
                             ->with('error', 'You are not authorized to close this session.');
        }
        
        $session->status = 'completed';
        $session->save();
        
        return redirect()->route('lecturer.session.show', $session->id)
                         ->with('status', 'Session closed successfully!');
    }
    
    /**
     * Export attendance as CSV.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function exportAttendance($id)
    {
        $session = Session::with(['course', 'attendances.user'])->findOrFail($id);
        
        // Check if the lecturer is assigned to this course
        if ($session->course->lecturer_id !== Auth::id()) {
            return redirect()->route('lecturer.dashboard')
                             ->with('error', 'You are not authorized to export this session.');
        }
        
        // Create CSV content
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="attendance-' . $session->id . '.csv"',
        ];
        
        $callback = function() use ($session) {
            $file = fopen('php://output', 'w');
            
            // Add header row
            fputcsv($file, [
                'Student ID',
                'Name',
                'Email',
                'Time',
                'IP Address',
                'Status'
            ]);
            
            // Add data rows
            foreach ($session->attendances as $attendance) {
                fputcsv($file, [
                    $attendance->user->student_id,
                    $attendance->user->name,
                    $attendance->user->email,
                    $attendance->marked_at->format('Y-m-d H:i:s'),
                    $attendance->ip_address,
                    $attendance->status
                ]);
            }
            
            fclose($file);
        };
        
        return Response::stream($callback, 200, $headers);
    }
    
    /**
     * Show available courses for assignment.
     *
     * @return \Illuminate\Http\Response
     */
    public function showCourses()
    {
        $user = Auth::user();
        $assignedCourses = $user->courses->pluck('id')->toArray();
        $availableCourses = Course::whereNotIn('id', $assignedCourses)
            ->orWhere('lecturer_id', '!=', $user->id)
            ->orWhereNull('lecturer_id')
            ->get();
        
        return view('lecturer.courses', compact('availableCourses', 'assignedCourses'));
    }
    
    /**
     * Assign lecturer to selected courses.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function assignCourses(Request $request)
    {
        $request->validate([
            'courses' => 'required|array',
            'courses.*' => 'exists:courses,id'
        ]);

        $user = Auth::user();
        $assignedCount = 0;
        
        foreach ($request->courses as $courseId) {
            $course = Course::find($courseId);
            
            // Only assign if not already assigned to another lecturer
            if (!$course->lecturer_id || $course->lecturer_id == $user->id) {
                $course->lecturer_id = $user->id;
                $course->save();
                $assignedCount++;
            }
        }
        
        $message = "Successfully assigned to {$assignedCount} course(s)!";
        if ($assignedCount < count($request->courses)) {
            $skipped = count($request->courses) - $assignedCount;
            $message .= " ({$skipped} course(s) were already assigned to other lecturers)";
        }
        
        return redirect()->route('lecturer.dashboard')->with('success', $message);
    }
    
    /**
     * Unassign lecturer from a course.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function unassignCourse(Request $request)
    {
        $request->validate([
            'course_id' => 'required|exists:courses,id'
        ]);

        $user = Auth::user();
        $course = Course::findOrFail($request->course_id);
        
        // Check if lecturer is assigned to this course
        if ($course->lecturer_id == $user->id) {
            $course->lecturer_id = null;
            $course->save();
            $message = "Successfully unassigned from {$course->course_code} - {$course->course_name}";
            return redirect()->back()->with('success', $message);
        } else {
            return redirect()->back()->with('error', 'You are not assigned to this course.');
        }
    }
    
    /**
     * Show student enrollment page for lecturer's courses.
     *
     * @return \Illuminate\Http\Response
     */
    public function showEnrollment()
    {
        $user = Auth::user();
        $courses = $user->courses;
        
        // Get all students
        $students = User::where('user_type', 'student')->orderBy('name')->get();
        
        // Get current semester and academic year
        $currentSemester = DB::table('system_settings')
                            ->where('setting_key', 'current_semester')
                            ->value('setting_value') ?? 'Fall 2025';
        $currentAcademicYear = DB::table('system_settings')
                                ->where('setting_key', 'current_academic_year')
                                ->value('setting_value') ?? '2025-2026';
        
        return view('lecturer.enrollment', compact('courses', 'students', 'currentSemester', 'currentAcademicYear'));
    }
    
    /**
     * Enroll students in lecturer's courses.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function enrollStudents(Request $request)
    {
        $request->validate([
            'course_id' => 'required|exists:courses,id',
            'student_ids' => 'required|array',
            'student_ids.*' => 'exists:users,id'
        ]);
        
        $user = Auth::user();
        $course = Course::findOrFail($request->course_id);
        
        // Check if lecturer is assigned to this course
        if ($course->lecturer_id !== $user->id) {
            return redirect()->back()->with('error', 'You are not authorized to enroll students in this course.');
        }
        
        $currentSemester = DB::table('system_settings')
                            ->where('setting_key', 'current_semester')
                            ->value('setting_value') ?? 'Fall 2025';
        $currentAcademicYear = DB::table('system_settings')
                                ->where('setting_key', 'current_academic_year')
                                ->value('setting_value') ?? '2025-2026';
        
        $enrolledCount = 0;
        $errors = [];
        
        try {
            DB::beginTransaction();
            
            foreach ($request->student_ids as $studentId) {
                $student = User::findOrFail($studentId);
                
                // Check if student is already enrolled
                $existingEnrollment = Enrollment::where('student_id', $student->student_id)
                                              ->where('course_code', $course->course_code)
                                              ->where('section_number', $course->section)
                                              ->where('enrollment_status', 'enrolled')
                                              ->exists();
                
                if (!$existingEnrollment) {
                    Enrollment::create([
                        'student_id' => $student->student_id,
                        'course_code' => $course->course_code,
                        'section_number' => $course->section,
                        'enrollment_status' => 'enrolled',
                        'enrollment_date' => now(),
                        'semester' => $currentSemester,
                        'academic_year' => $currentAcademicYear,
                    ]);
                    $enrolledCount++;
                } else {
                    $errors[] = "Student {$student->name} is already enrolled in this course.";
                }
            }
            
            DB::commit();
            
            $message = "Successfully enrolled {$enrolledCount} student(s) in {$course->course_code} - {$course->course_name}!";
            if (!empty($errors)) {
                $message .= " " . implode(' ', $errors);
            }
            
            return redirect()->route('lecturer.enrollment')->with('success', $message);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error enrolling students: ' . $e->getMessage());
        }
    }
    
    /**
     * Unenroll students from lecturer's courses.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function unenrollStudents(Request $request)
    {
        $request->validate([
            'course_id' => 'required|exists:courses,id',
            'student_ids' => 'required|array',
            'student_ids.*' => 'exists:users,id'
        ]);
        
        $user = Auth::user();
        $course = Course::findOrFail($request->course_id);
        
        // Check if lecturer is assigned to this course
        if ($course->lecturer_id !== $user->id) {
            return redirect()->back()->with('error', 'You are not authorized to unenroll students from this course.');
        }
        
        $unenrolledCount = 0;
        
        try {
            DB::beginTransaction();
            
            foreach ($request->student_ids as $studentId) {
                $student = User::findOrFail($studentId);
                
                $enrollment = Enrollment::where('student_id', $student->student_id)
                                      ->where('course_code', $course->course_code)
                                      ->where('section_number', $course->section)
                                      ->where('enrollment_status', 'enrolled')
                                      ->first();
                
                if ($enrollment) {
                    $enrollment->update(['enrollment_status' => 'dropped']);
                    $unenrolledCount++;
                }
            }
            
            DB::commit();
            
            $message = "Successfully unenrolled {$unenrolledCount} student(s) from {$course->course_code} - {$course->course_name}!";
            return redirect()->route('lecturer.enrollment')->with('success', $message);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error unenrolling students: ' . $e->getMessage());
        }
    }
    
    /**
     * Get enrolled students for a specific course.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getEnrolledStudents(Request $request)
    {
        $request->validate([
            'course_id' => 'required|exists:courses,id'
        ]);
        
        $user = Auth::user();
        $course = Course::findOrFail($request->course_id);
        
        // Check if lecturer is assigned to this course
        if ($course->lecturer_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        $enrolledStudents = Enrollment::where('course_code', $course->course_code)
                                    ->where('section_number', $course->section)
                                    ->where('enrollment_status', 'enrolled')
                                    ->with('user')
                                    ->get()
                                    ->map(function($enrollment) {
                                        return [
                                            'id' => $enrollment->user->id,
                                            'student_id' => $enrollment->student_id,
                                            'name' => $enrollment->user->name,
                                            'email' => $enrollment->user->email,
                                            'enrollment_date' => $enrollment->enrollment_date
                                        ];
                                    });
        
        return response()->json($enrolledStudents);
    }
}
