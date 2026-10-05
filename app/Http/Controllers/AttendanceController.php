<?php

namespace App\Http\Controllers;

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Session;
use App\Models\Attendance;
use App\Models\User;

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
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'accuracy' => 'nullable|numeric|min:0',
        ], [
            'latitude.required' => 'Your location is required to mark attendance. Please allow location access.',
            'longitude.required' => 'Your location is required to mark attendance. Please allow location access.',
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
        
        // Check the student is using their registered device
        $deviceId = $request->cookie(config('attendance.device_cookie'));

        if (!$deviceId) {
            return response()->json([
                'success' => false,
                'message' => 'Device not recognised. Please reload the scan page and try again.'
            ], 403);
        }

        $deviceError = $this->checkDevice($user, $deviceId);

        if ($deviceError) {
            return response()->json([
                'success' => false,
                'message' => $deviceError
            ], 403);
        }

        // Check the student is near the classroom
        $distance = null;

        if ($session->hasLocation()) {
            $maxAccuracy = config('attendance.max_accuracy_meters');

            if ($request->filled('accuracy') && $request->accuracy > $maxAccuracy) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your location is not accurate enough (±' . round($request->accuracy) . 'm). Turn on GPS / precise location and try again.'
                ], 403);
            }

            $distance = $session->distanceTo((float) $request->latitude, (float) $request->longitude);
            $radius = $session->radius_meters ?? config('attendance.radius_meters');

            if ($distance > $radius) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are too far from the classroom (' . round($distance) . 'm away). You must be within ' . $radius . 'm to mark attendance.'
                ], 403);
            }
        }

        // Mark attendance (the unique index on session_id + user_id prevents duplicates)
        $attendance = new Attendance();
        $attendance->session_id = $session->id;
        $attendance->user_id = $user->id;
        $attendance->status = 'present';
        $attendance->marked_at = now();
        $attendance->ip_address = $request->ip();
        $attendance->device_info = $request->userAgent();
        $attendance->device_id = $deviceId;
        $attendance->latitude = $request->latitude;
        $attendance->longitude = $request->longitude;
        $attendance->distance_meters = $distance !== null ? (int) round($distance) : null;

        try {
            $attendance->save();
        } catch (UniqueConstraintViolationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Attendance already marked for this session.'
            ], 400);
        }

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
     * Bind the student to this device on first use, or check it matches the bound device.
     * Returns an error message, or null if the device is allowed.
     *
     * @param  \App\Models\User  $user
     * @param  string  $deviceId
     * @return string|null
     */
    private function checkDevice(User $user, $deviceId)
    {
        if ($user->device_id) {
            return $user->device_id === $deviceId
                ? null
                : 'Your account is registered to another device. Use that device or ask an admin to reset it.';
        }

        if (User::where('device_id', $deviceId)->where('id', '!=', $user->id)->exists()) {
            return 'This device is already registered to another student.';
        }

        try {
            $bound = User::where('id', $user->id)
                         ->whereNull('device_id')
                         ->update(['device_id' => $deviceId, 'device_bound_at' => now()]);
        } catch (UniqueConstraintViolationException $e) {
            return 'This device is already registered to another student.';
        }

        // Another request bound a different device first
        if (!$bound && $user->fresh()->device_id !== $deviceId) {
            return 'Your account is registered to another device. Use that device or ask an admin to reset it.';
        }

        return null;
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
