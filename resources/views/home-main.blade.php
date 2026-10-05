@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header text-center">
                    <h3><i class="bi bi-mortarboard"></i> QR Attendance System</h3>
                </div>

                <div class="card-body text-center">
                    @if (session('status'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('status') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <div class="welcome-section mb-4">
                        <h4>Welcome back, {{ Auth::user()->name }}!</h4>
                        <p class="text-muted">
                            Role: <span class="badge bg-{{ Auth::user()->isStudent() ? 'primary' : (Auth::user()->isLecturer() ? 'success' : 'danger') }}">
                                {{ ucfirst(Auth::user()->role) }}
                            </span>
                            @if(Auth::user()->student_id)
                                | Student ID: <strong>{{ Auth::user()->student_id }}</strong>
                            @endif
                            @if(Auth::user()->lecturer_id)
                                | Lecturer ID: <strong>{{ Auth::user()->lecturer_id }}</strong>
                            @endif
                        </p>
                    </div>

                    <!-- Main Navigation Buttons -->
                    <div class="row justify-content-center">
                        <div class="col-md-5 mb-3">
                            <div class="card navigation-card h-100" style="cursor: pointer;" onclick="location.href='{{ Auth::user()->isStudent() ? route('student.dashboard') : (Auth::user()->isLecturer() ? route('lecturer.dashboard') : route('admin.dashboard')) }}'">
                                <div class="card-body d-flex flex-column align-items-center justify-content-center p-4">
                                    <i class="bi bi-speedometer2" style="font-size: 3rem; color: #0d6efd;"></i>
                                    <h5 class="card-title mt-3 mb-2">Dashboard</h5>
                                    <p class="card-text text-muted text-center">
                                        @if(Auth::user()->isStudent())
                                            View your courses, attendance history, and scan QR codes
                                        @elseif(Auth::user()->isLecturer())
                                            Manage sessions, generate QR codes, and view attendance
                                        @else
                                            System administration and reports
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-5 mb-3">
                            <div class="card navigation-card h-100" style="cursor: pointer;" onclick="location.href='{{ route('add-courses') }}'">
                                <div class="card-body d-flex flex-column align-items-center justify-content-center p-4">
                                    <i class="bi bi-book-half" style="font-size: 3rem; color: #198754;"></i>
                                    <h5 class="card-title mt-3 mb-2">Add Courses</h5>
                                    <p class="card-text text-muted text-center">
                                        @if(Auth::user()->isStudent())
                                            Browse and enroll in available courses
                                        @elseif(Auth::user()->isLecturer())
                                            Browse and assign yourself to courses
                                        @else
                                            Manage course assignments
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Stats -->
                    <div class="row mt-4">
                        @if(Auth::user()->isStudent())
                            @php
                                $enrolledCount = Auth::user()->courses->count();
                                $recentAttendance = Auth::user()->attendances()->latest()->limit(1)->first();
                            @endphp
                            <div class="col-md-6">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h6 class="card-title">Enrolled Courses</h6>
                                        <h4 class="text-primary">{{ $enrolledCount }}</h4>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h6 class="card-title">Last Attendance</h6>
                                        <p class="text-success mb-0">
                                            @if($recentAttendance)
                                                {{ $recentAttendance->created_at->diffForHumans() }}
                                            @else
                                                No records yet
                                            @endif
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @elseif(Auth::user()->isLecturer())
                            @php
                                $assignedCount = Auth::user()->courses->count();
                                $recentSession = \App\Models\Session::whereIn('course_id', Auth::user()->courses->pluck('id'))->latest()->limit(1)->first();
                            @endphp
                            <div class="col-md-6">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h6 class="card-title">Assigned Courses</h6>
                                        <h4 class="text-success">{{ $assignedCount }}</h4>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h6 class="card-title">Recent Session</h6>
                                        <p class="text-info mb-0">
                                            @if($recentSession)
                                                {{ $recentSession->created_at->diffForHumans() }}
                                            @else
                                                No sessions yet
                                            @endif
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @else
                            @php
                                $totalUsers = \App\Models\User::count();
                                $totalCourses = \App\Models\Course::count();
                            @endphp
                            <div class="col-md-6">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h6 class="card-title">Total Users</h6>
                                        <h4 class="text-danger">{{ $totalUsers }}</h4>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h6 class="card-title">Total Courses</h6>
                                        <h4 class="text-warning">{{ $totalCourses }}</h4>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Quick Links -->
                    <div class="mt-4 pt-3 border-top">
                        <h6 class="text-muted">Quick Actions</h6>
                        <div class="btn-group" role="group">
                            @if(Auth::user()->isStudent())
                                <a href="{{ route('student.scan-qr') }}" class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-qr-code-scan"></i> Scan QR
                                </a>
                                <a href="{{ route('student.attendance.history') }}" class="btn btn-outline-info btn-sm">
                                    <i class="bi bi-clock-history"></i> Attendance History
                                </a>
                            @elseif(Auth::user()->isLecturer())
                                @if(Auth::user()->courses->count() > 0)
                                    <a href="{{ route('lecturer.dashboard') }}" class="btn btn-outline-success btn-sm">
                                        <i class="bi bi-qr-code"></i> Generate QR
                                    </a>
                                @endif
                            @else
                                <a href="{{ route('admin.users') }}" class="btn btn-outline-danger btn-sm">
                                    <i class="bi bi-people"></i> Manage Users
                                </a>
                                <a href="{{ route('admin.reports') }}" class="btn btn-outline-warning btn-sm">
                                    <i class="bi bi-graph-up"></i> Reports
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.navigation-card {
    transition: all 0.3s ease;
    border: 2px solid transparent;
}

.navigation-card:hover {
    transform: translateY(-5px);
    border-color: #0d6efd;
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.welcome-section {
    padding: 20px 0;
}

.card-body i {
    transition: transform 0.3s ease;
}

.navigation-card:hover i {
    transform: scale(1.1);
}

.btn-group .btn {
    margin: 0 2px;
}
</style>
@endsection
