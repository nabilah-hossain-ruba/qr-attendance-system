<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Course;
use App\Models\Session;
use App\Models\Attendance;
use Illuminate\Support\Facades\DB;
use App\Services\BracuScheduleSync;
use App\Models\CourseSection;
use App\Models\Enrollment;

class AdminController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware(['auth', 'role:admin']);
    }

    /**
     * Show the admin dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function dashboard()
    {
        $stats = [
            'users' => User::count(),
            'courses' => Course::distinct('course_code')->count(),
            'sessions' => Session::count(),
            'attendances' => Attendance::count(),
            'students' => User::where('role', 'student')->count(),
            'lecturers' => User::where('role', 'lecturer')->count(),
        ];
        
        return view('admin.dashboard', compact('stats'));
    }
    
    /**
     * Show the users management page.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function users()
    {
        $users = User::with('courses')->paginate(15);
        return view('admin.users', compact('users'));
    }
    
    /**
     * Show the form for editing a user.
     *
     * @param  int  $id
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function editUser($id)
    {
        $user = User::findOrFail($id);
        return view('admin.edit-user', compact('user'));
    }
    
    /**
     * Update the specified user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function updateUser(Request $request, $id)
    {
        $user = User::findOrFail($id);
        
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $id,
            'role' => 'required|in:admin,lecturer,student',
            'student_id' => 'nullable|string|max:255|unique:users,student_id,' . $id,
            'faculty_initial' => 'nullable|alpha|max:10|unique:users,faculty_initial,' . $id,
        ]);
        
        $user->name = $request->name;
        $user->email = $request->email;
        $user->role = $request->role;
        $user->student_id = $request->student_id;
        $user->faculty_initial = $user->role === 'lecturer' && $request->filled('faculty_initial')
            ? strtoupper($request->faculty_initial)
            : null;
        
        if ($request->filled('password')) {
            $request->validate([
                'password' => 'required|string|min:8|confirmed',
            ]);
            $user->password = Hash::make($request->password);
        }
        
        $initialChanged = $user->isDirty('faculty_initial');
        $user->save();

        $message = 'User updated successfully!';

        if ($initialChanged) {
            $assigned = BracuScheduleSync::assignCoursesToLecturer($user);
            $message .= $user->faculty_initial ? " {$assigned} course(s) assigned from the BRACU schedule." : '';
        }
        
        return redirect()->route('admin.users')
                        ->with('status', $message);
    }
    
    /**
     * Unlink the user's registered attendance device so they can use a new one.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function resetDevice($id)
    {
        $user = User::findOrFail($id);
        $user->device_id = null;
        $user->device_bound_at = null;
        $user->save();

        return redirect()->route('admin.users.edit', $user->id)
                        ->with('status', 'Device reset. The user can now mark attendance from a new device.');
    }

    /**
     * Remove the specified user.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function deleteUser($id)
    {
        $user = User::findOrFail($id);
        
        // Prevent deleting the current admin user
        if ($user->id === Auth::id()) {
            return redirect()->route('admin.users')
                            ->with('error', 'You cannot delete your own account!');
        }
        
        // Check if lecturer is assigned to any courses
        if ($user->role === 'lecturer') {
            $assignedCourses = Course::where('lecturer_id', $user->id)->count();
            if ($assignedCourses > 0) {
                return redirect()->route('admin.users')
                                ->with('error', "Cannot delete lecturer. They are assigned to {$assignedCourses} course(s). Please reassign or remove the courses first.");
            }
        }
        
        // Delete related data
        $user->attendances()->delete();
        
        $user->delete();
        
        return redirect()->route('admin.users')
                        ->with('status', 'User deleted successfully!');
    }
    
    /**
     * Reassign courses from one lecturer to another.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function reassignCourses(Request $request)
    {
        $request->validate([
            'from_lecturer_id' => 'required|exists:users,id',
            'to_lecturer_id' => 'required|exists:users,id',
            'course_ids' => 'required|array',
            'course_ids.*' => 'exists:courses,id'
        ]);
        
        $fromLecturer = User::findOrFail($request->from_lecturer_id);
        $toLecturer = User::findOrFail($request->to_lecturer_id);
        
        // Verify both are lecturers
        if ($fromLecturer->role !== 'lecturer' || $toLecturer->role !== 'lecturer') {
            return redirect()->back()
                            ->with('error', 'Both users must be lecturers.');
        }
        
        // Reassign courses
        $reassignedCount = Course::where('lecturer_id', $fromLecturer->id)
                                ->whereIn('id', $request->course_ids)
                                ->update(['lecturer_id' => $toLecturer->id]);
        
        return redirect()->route('admin.users')
                        ->with('status', "Successfully reassigned {$reassignedCount} course(s) from {$fromLecturer->name} to {$toLecturer->name}.");
    }
    
    /**
     * Show the courses management page.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function courses()
    {
        $courses = Course::with(['lecturer', 'students'])->orderBy('course_code')->orderBy('section')->paginate(15);
        
        return view('admin.courses', compact('courses'));
    }
    
    /**
     * Create a new course with multiple sections.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function createCourse(Request $request)
    {
        $request->validate([
            'course_code' => 'required|string|max:20|unique:courses,course_code',
            'number_of_sections' => 'required|integer|min:1|max:20',
        ]);
        
        try {
            DB::beginTransaction();
            
            $createdSections = [];
            $currentYear = date('Y');
            $currentMonth = date('n');
            
            // Determine current semester based on month
            if ($currentMonth >= 1 && $currentMonth <= 4) {
                $semester = 'Spring';
            } elseif ($currentMonth >= 5 && $currentMonth <= 8) {
                $semester = 'Summer';
            } else {
                $semester = 'Fall';
            }
            
            // Create the specified number of sections
            for ($i = 1; $i <= $request->number_of_sections; $i++) {
                $sectionNumber = str_pad($i, 2, '0', STR_PAD_LEFT); // 01, 02, 03, etc.
                
                $course = Course::create([
                    'course_code' => strtoupper($request->course_code),
                    'course_name' => $request->course_code . ' - Course', // Default course name
                    'section' => $sectionNumber,
                    'semester' => $semester,
                    'year' => $currentYear,
                    'lecturer_id' => null, // Will be assigned later
                    'schedule' => null, // Will be set later
                    'room' => null, // Will be set later
                ]);
                
                $createdSections[] = $sectionNumber;
            }
            
            DB::commit();
            
            $sectionsList = implode(', ', $createdSections);
            return redirect()->route('admin.dashboard')
                           ->with('success', "Course {$request->course_code} created successfully with sections: {$sectionsList}!");
                           
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                           ->withInput()
                           ->with('error', 'Error creating course: ' . $e->getMessage());
        }
    }
    
    
    /**
     * Store a newly created course with multiple sections.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function storeCourse(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:20|unique:courses,code',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'credits' => 'required|integer|min:1|max:6',
            'department' => 'required|string|max:100',
            'faculty' => 'nullable|string|max:100',
            'number_of_sections' => 'required|integer|min:1|max:20',
            'section_capacity' => 'required|integer|min:5|max:200',
            'semester' => 'required|string|max:20',
            'academic_year' => 'required|string|max:10',
        ]);
        
        try {
            DB::beginTransaction();
            
            // Create the course
            $course = Course::create([
                'code' => strtoupper($request->code),
                'name' => $request->name,
                'description' => $request->description,
                'credits' => $request->credits,
                'department' => $request->department,
                'faculty' => $request->faculty,
                'is_active' => true,
            ]);
            
            // Create the specified number of sections
            for ($i = 1; $i <= $request->number_of_sections; $i++) {
                CourseSection::create([
                    'course_code' => $course->code,
                    'section_number' => str_pad($i, 2, '0', STR_PAD_LEFT), // 01, 02, 03, etc.
                    'max_capacity' => $request->section_capacity,
                    'current_enrollment' => 0,
                    'semester' => $request->semester,
                    'academic_year' => $request->academic_year,
                    'is_active' => true,
                ]);
            }
            
            DB::commit();
            
            return redirect()->route('admin.courses')
                           ->with('success', "Course {$course->code} created successfully with {$request->number_of_sections} sections!");
                           
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                           ->withInput()
                           ->with('error', 'Error creating course: ' . $e->getMessage());
        }
    }
    
    /**
     * Show sections for a specific course.
     *
     * @param  string  $courseCode
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function courseSections($courseCode)
    {
        $course = Course::with(['sections' => function($query) {
            $query->with(['lecturer', 'enrollments' => function($enrollmentQuery) {
                $enrollmentQuery->where('enrollment_status', 'enrolled')->with('student');
            }]);
        }])->where('code', $courseCode)->firstOrFail();
        
        $lecturers = User::where('user_type', 'lecturer')->get();
        
        return view('admin.course-sections', compact('course', 'lecturers'));
    }
    
    /**
     * Add additional sections to a course.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $courseCode
     * @return \Illuminate\Http\Response
     */
    public function addSections(Request $request, $courseCode)
    {
        $course = Course::where('code', $courseCode)->firstOrFail();
        
        $request->validate([
            'number_of_sections' => 'required|integer|min:1|max:10',
            'section_capacity' => 'required|integer|min:5|max:200',
            'semester' => 'required|string|max:20',
            'academic_year' => 'required|string|max:10',
        ]);
        
        // Find the highest existing section number
        $maxSection = CourseSection::where('course_code', $courseCode)
                                  ->max('section_number');
        $startSection = $maxSection ? ((int)$maxSection) + 1 : 1;
        
        try {
            DB::beginTransaction();
            
            $createdSections = [];
            for ($i = 0; $i < $request->number_of_sections; $i++) {
                $sectionNumber = str_pad($startSection + $i, 2, '0', STR_PAD_LEFT);
                
                $section = CourseSection::create([
                    'course_code' => $courseCode,
                    'section_number' => $sectionNumber,
                    'max_capacity' => $request->section_capacity,
                    'current_enrollment' => 0,
                    'semester' => $request->semester,
                    'academic_year' => $request->academic_year,
                    'is_active' => true,
                ]);
                
                $createdSections[] = $sectionNumber;
            }
            
            DB::commit();
            
            $sectionsList = implode(', ', $createdSections);
            return redirect()->route('admin.course.sections', $courseCode)
                           ->with('success', "Added sections {$sectionsList} to course {$courseCode}!");
                           
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                           ->withInput()
                           ->with('error', 'Error adding sections: ' . $e->getMessage());
        }
    }
    
    /**
     * Update section details.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $sectionId
     * @return \Illuminate\Http\Response
     */
    public function updateSection(Request $request, $sectionId)
    {
        $section = CourseSection::findOrFail($sectionId);
        
        $request->validate([
            'lecturer_id' => 'nullable|exists:users,lecturer_id',
            'room' => 'nullable|string|max:50',
            'max_capacity' => 'required|integer|min:5|max:200',
        ]);
        
        // Validate that new capacity is not less than current enrollment
        if ($request->max_capacity < $section->current_enrollment) {
            return redirect()->back()
                           ->with('error', "Cannot set capacity ({$request->max_capacity}) lower than current enrollment ({$section->current_enrollment})!");
        }
        
        $section->update([
            'lecturer_id' => $request->lecturer_id,
            'room' => $request->room,
            'max_capacity' => $request->max_capacity,
        ]);
        
        return redirect()->back()
                       ->with('success', "Section {$section->section_number} updated successfully!");
    }
    
    /**
     * Show the sessions management page.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function sessions()
    {
        $sessions = Session::with(['course', 'attendances'])->paginate(15);
        return view('admin.sessions', compact('sessions'));
    }
    
    /**
     * Show the form for editing a session.
     *
     * @param  int  $id
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function editSession($id)
    {
        $session = Session::findOrFail($id);
        $courses = Course::all();
        return view('admin.edit-session', compact('session', 'courses'));
    }
    
    /**
     * Update the specified session.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function updateSession(Request $request, $id)
    {
        $session = Session::findOrFail($id);
        
        $request->validate([
            'course_id' => 'required|exists:courses,id',
            'date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required|after:start_time',
            'status' => 'required|in:scheduled,active,completed,cancelled',
            'qr_expiry' => 'nullable|date|after:now',
        ]);
        
        $session->course_id = $request->course_id;
        $session->date = $request->date;
        $session->start_time = $request->start_time;
        $session->end_time = $request->end_time;
        $session->status = $request->status;
        
        if ($request->filled('qr_expiry')) {
            $session->qr_expiry = $request->qr_expiry;
        }
        
        $session->save();
        
        return redirect()->route('admin.sessions')
                        ->with('status', 'Session updated successfully!');
    }
    
    /**
     * Remove the specified session.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function deleteSession($id)
    {
        $session = Session::findOrFail($id);
        
        // Delete related attendances
        $session->attendances()->delete();
        
        $session->delete();
        
        return redirect()->route('admin.sessions')
                        ->with('status', 'Session deleted successfully!');
    }
    
    /**
     * Show the reports generation page.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function reports()
    {
        $courses = Course::all();
        return view('admin.reports', compact('courses'));
    }
    
    /**
     * Generate attendance report for a specific course.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function generateReport(Request $request)
    {
        $request->validate([
            'course_id' => 'required|exists:courses,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);
        
        $course = Course::with(['sessions' => function($query) use ($request) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        }])->findOrFail($request->course_id);
        
        $students = $course->students;
        $sessions = $course->sessions;
        
        // Generate report data
        $reportData = [];
        foreach ($students as $student) {
            $attendanceData = [];
            $totalPresent = 0;
            
            foreach ($sessions as $session) {
                $attendance = Attendance::where('session_id', $session->id)
                                      ->where('user_id', $student->id)
                                      ->first();
                
                $status = $attendance ? $attendance->status : 'absent';
                $attendanceData[$session->id] = $status;
                
                if ($status === 'present') {
                    $totalPresent++;
                }
            }
            
            $reportData[$student->id] = [
                'student' => $student,
                'attendances' => $attendanceData,
                'total_present' => $totalPresent,
                'percentage' => $sessions->count() > 0 ? ($totalPresent / $sessions->count()) * 100 : 0,
            ];
        }
        
        return view('admin.report-result', compact('course', 'sessions', 'reportData'));
    }
    
    /**
     * Show the BRACU schedule sync page.
     *
     * @return \Illuminate\Http\Response
     */
    public function showImportForm()
    {
        return view('admin.import-courses');
    }

    /**
     * Pull course sections, schedules and lecturers from BRACU Connect now.
     *
     * @param  \App\Services\BracuScheduleSync  $sync
     * @return \Illuminate\Http\Response
     */
    public function syncBracuSchedule(BracuScheduleSync $sync)
    {
        try {
            $stats = $sync->sync();
        } catch (\Throwable $e) {
            return redirect()->route('admin.import-courses')
                           ->with('error', 'BRACU sync failed: ' . $e->getMessage());
        }

        return redirect()->route('admin.import-courses')
                       ->with('success', "BRACU sync completed! Created: {$stats['created']}, Updated: {$stats['updated']}, "
                           . "Assigned to a lecturer: {$stats['with_lecturer']}, Skipped: {$stats['skipped']}");
    }
}
