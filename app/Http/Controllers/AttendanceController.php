<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Session;
use App\Models\Attendance;

class AttendanceController extends Controller
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
     * Display a listing of attendances.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $attendances = Attendance::with(['session.course', 'user'])->latest()->paginate(15);
        return view('attendances.index', compact('attendances'));
    }

    /**
     * Show the form for creating a new attendance record (legacy).
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('attendances.create');
    }

    /**
     * Store a newly created attendance record (legacy).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'user_name' => 'required',
            'date' => 'required|date',
            'check_in' => 'nullable',
            'check_out' => 'nullable'
        ]);

        Attendance::create($request->all());
        return redirect('/')->with('success', 'Attendance added!');
    }
    
    /**
     * Mark attendance using QR code.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function markAttendance(Request $request)
    {
        $request->validate([
            'qr_code' => 'required|string',
        ]);
        
        // Find the session by QR code
        $session = Session::where('qr_code', $request->qr_code)
                         ->where('status', 'active')
                         ->where('qr_expiry', '>', now())
                         ->first();
        
        if (!$session) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired QR code.'
            ], 400);
        }
        
        // Check if the student is enrolled in the course
        $user = Auth::user();
        
        if ($user->isStudent()) {
            $enrolled = $user->courses()->where('course_id', $session->course_id)->exists();
            
            if (!$enrolled) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not enrolled in this course.'
                ], 403);
            }
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Only students can mark attendance.'
            ], 403);
        }
        
        // Check if attendance already marked
        $existingAttendance = Attendance::where('session_id', $session->id)
                                      ->where('user_id', $user->id)
                                      ->first();
        
        if ($existingAttendance) {
            return response()->json([
                'success' => false,
                'message' => 'Attendance already marked for this session.'
            ], 400);
        }
        
        // Mark attendance
        $attendance = new Attendance();
        $attendance->session_id = $session->id;
        $attendance->user_id = $user->id;
        $attendance->status = 'present';
        $attendance->marked_at = now();
        $attendance->ip_address = $request->ip();
        $attendance->device_info = $request->userAgent();
        $attendance->save();
        
        return response()->json([
            'success' => true,
            'message' => 'Attendance marked successfully!',
            'data' => [
                'course' => $session->course->course_code . ' - ' . $session->course->course_name,
                'date' => $session->date,
                'time' => now()->format('H:i:s'),
            ]
        ]);
    }
    
    /**
     * Get attendance history for the authenticated student.
     *
     * @return \Illuminate\Http\Response
     */
    public function history()
    {
        $user = Auth::user();
        
        if (!$user->isStudent()) {
            return redirect()->back()->with('error', 'Only students can view attendance history.');
        }
        
        $attendances = $user->attendances()
                           ->with('session.course')
                           ->latest('marked_at')
                           ->paginate(15);
        
        return view('student.attendance-history', compact('attendances'));
    }
}
