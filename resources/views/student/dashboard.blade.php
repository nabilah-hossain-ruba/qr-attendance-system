@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header">{{ __('Student Dashboard') }}</div>

                <div class="card-body">
                    @if (session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif

                    <h4>Welcome, {{ Auth::user()->name }}!</h4>
                    <p>Student ID: {{ Auth::user()->student_id }}</p>
                    
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="card mb-4">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <span>My Courses</span>
                                    <a href="{{ route('student.courses') }}" class="btn btn-sm btn-success">
                                        <i class="bi bi-plus-circle"></i> Enroll in Courses
                                    </a>
                                </div>
                                <div class="card-body">
                                    @if(count($courses) > 0)
                                        <ul class="list-group">
                                            @foreach($courses as $course)
                                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <strong>{{ $course->course_code }}</strong><br>
                                                        <small>{{ $course->course_name }}</small>
                                                    </div>
                                                    <div class="text-end">
                                                        <span class="badge bg-primary rounded-pill">Section {{ $course->section }}</span><br>
                                                        <small class="text-muted">{{ $course->semester }} {{ $course->year }}</small>
                                                    </div>
                                                </li>
                                            @endforeach
                                        </ul>
                                        <div class="mt-3">
                                            <a href="{{ route('student.courses') }}" class="btn btn-outline-primary btn-sm">
                                                <i class="bi bi-gear"></i> Manage Course Enrollment
                                            </a>
                                        </div>
                                    @else
                                        <p class="text-muted">You are not enrolled in any courses yet.</p>
                                        <a href="{{ route('student.courses') }}" class="btn btn-primary">
                                            <i class="bi bi-book"></i> Browse Available Courses
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">Scan QR Code for Attendance</div>
                                <div class="card-body text-center">
                                    <p>Scan a QR code to mark your attendance for a session.</p>
                                    <a href="{{ route('student.scan-qr') }}" class="btn btn-primary">Scan QR Code</a>
                                    <div id="qrScanner" class="mt-3 d-none">
                                        <!-- QR scanner will be initialized here -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <span>Recent Attendance</span>
                                    <a href="{{ route('student.attendance.history') }}" class="btn btn-sm btn-outline-primary">View All</a>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Course</th>
                                                    <th>Date</th>
                                                    <th>Time</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @if(count($attendances) > 0)
                                                    @foreach($attendances as $attendance)
                                                        <tr>
                                                            <td>
                                                                <strong>{{ $attendance->session->course->course_code }}</strong><br>
                                                                <small class="text-muted">{{ $attendance->session->course->course_name }}</small>
                                                            </td>
                                                            <td>{{ $attendance->session->date }}</td>
                                                            <td>{{ $attendance->marked_at->format('H:i:s') }}</td>
                                                            <td>
                                                                <span class="badge bg-success">{{ ucfirst($attendance->status) }}</span>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                @else
                                                    <tr>
                                                        <td colspan="4" class="text-center text-muted">No recent attendance records found.</td>
                                                    </tr>
                                                @endif
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection