@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header">{{ __('Lecturer Dashboard') }}</div>

                <div class="card-body">
                    @if (session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger" role="alert">
                            {{ session('error') }}
                        </div>
                    @endif

                    <h4>Welcome back, {{ Auth::user()->name }}!</h4>
                    <p>Lecturer Dashboard</p>

                    
                    <div class="row mt-4">
                        <!-- LEFT HALF: Manage Courses -->
                        <div class="col-md-6">
                            <div class="card mb-4">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <span>Manage Courses</span>
                                </div>
                                <div class="card-body text-center">
                                    <p>Manage your course assignments and enrollment.</p>
                                    <a href="{{ route('lecturer.courses') }}" class="btn btn-primary">
                                        <i class="bi bi-gear"></i> Manage Courses
                                    </a>
                                </div>
                            </div>
                        </div>

                        
                        <!-- RIGHT HALF: Generate QR Code -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">Generate QR Code for Attendance</div>
                                <div class="card-body">
                                    @if(count(Auth::user()->courses) > 0)
                                        <form method="POST" action="{{ route('lecturer.qr.generate') }}">
                                            @csrf
                                            <div class="mb-3">
                                                <label for="course_id" class="form-label">
                                                    <i class="bi bi-book"></i> Select Course
                                                </label>
                                                <select id="course_id" name="course_id" required class="form-select">
                                                    <option value="">Choose a course...</option>
                                                    @foreach(Auth::user()->courses as $course)
                                                        <option value="{{ $course->id }}">{{ $course->course_code }} - Section {{ $course->section }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label for="expiry_minutes" class="form-label">
                                                    <i class="bi bi-clock"></i> QR Code Expiry (minutes)
                                                </label>
                                                <input type="number" id="expiry_minutes" name="expiry_minutes" value="15" min="5" max="60" required class="form-control">
                                                <div class="form-text">Recommended: 15-30 minutes for class sessions</div>
                                            </div>
                                            <button type="submit" class="btn btn-primary w-100">
                                                <i class="bi bi-qr-code"></i> Generate QR Code
                                            </button>
                                        </form>
                                    @else
                                        <div class="text-center">
                                            <p class="text-muted mb-3">Assign yourself to courses first to generate QR codes</p>
                                            <a href="{{ route('lecturer.courses') }}" class="btn btn-primary">
                                                <i class="bi bi-plus-circle"></i> Assign Courses
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <span>Recent Sessions</span>
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
                                                    <th>Attendance</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($sessions ?? [] as $session)
                                                    <tr>
                                                        <td>
                                                            <strong>{{ $session->course->course_code }}</strong><br>
                                                            <small class="text-muted">Section {{ $session->course->section }}</small>
                                                        </td>
                                                        <td>{{ $session->date }}</td>
                                                        <td>
                                                            @if(isset($session->start_time) && isset($session->end_time))
                                                                {{ $session->start_time }} - {{ $session->end_time }}
                                                            @else
                                                                {{ $session->created_at->format('H:i:s') }}
                                                            @endif
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-
                                                                @if($session->status === 'active') success
                                                                @elseif($session->status === 'completed') primary
                                                                @elseif($session->status === 'cancelled') danger
                                                                @else secondary
                                                                @endif">
                                                                {{ ucfirst($session->status) }}
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <i class="bi bi-people-fill"></i> {{ $session->attendances->count() ?? 0 }} students
                                                        </td>
                                                        <td>
                                                            <a href="{{ route('lecturer.session.show', $session->id) }}" class="btn btn-sm btn-outline-primary">
                                                                <i class="bi bi-eye"></i> View
                                                            </a>
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="6" class="text-center text-muted">
                                                            No recent sessions found. Generate QR codes to start tracking attendance.
                                                        </td>
                                                    </tr>
                                                @endforelse
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