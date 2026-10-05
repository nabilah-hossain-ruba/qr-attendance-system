@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header">{{ __('Admin Dashboard') }}</div>

                <div class="card-body">
                    @if (session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif

                    <h4>Welcome, {{ Auth::user()->name }}!</h4>
                    
                    <div class="row mt-4">
                        <div class="col-md-4">
                            <div class="card mb-4">
                                <div class="card-header">System Stats</div>
                                <div class="card-body">
                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            Total Users
                                            <span class="badge bg-primary rounded-pill">{{ $stats['users'] }}</span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            Students
                                            <span class="badge bg-success rounded-pill">{{ $stats['students'] }}</span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            Lecturers
                                            <span class="badge bg-info rounded-pill">{{ $stats['lecturers'] }}</span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            Total Courses
                                            <span class="badge bg-warning rounded-pill">{{ $stats['courses'] }}</span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            Total Sessions
                                            <span class="badge bg-secondary rounded-pill">{{ $stats['sessions'] }}</span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            Total Attendances
                                            <span class="badge bg-danger rounded-pill">{{ $stats['attendances'] }}</span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-8">
                            <div class="card mb-4">
                                <div class="card-header">Quick Actions</div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <a href="{{ route('admin.users') }}" class="btn btn-primary w-100">
                                                <i class="bi bi-people"></i> Manage Users
                                            </a>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <a href="{{ route('admin.courses') }}" class="btn btn-success w-100">
                                                <i class="bi bi-book"></i> Manage Courses
                                            </a>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <a href="{{ route('admin.sessions') }}" class="btn btn-info w-100">
                                                <i class="bi bi-calendar-event"></i> View Sessions
                                            </a>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <a href="{{ route('admin.reports') }}" class="btn btn-warning w-100">
                                                <i class="bi bi-file-earmark-text"></i> Generate Reports
                                            </a>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <button type="button" class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#createCourseModal">
                                                <i class="bi bi-plus-circle"></i> Create Courses
                                            </button>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <a href="{{ route('admin.import-courses') }}" class="btn btn-info w-100">
                                                <i class="bi bi-upload"></i> Import Courses
                                            </a>
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
</div>

<!-- Create Course Modal -->
<div class="modal fade" id="createCourseModal" tabindex="-1" aria-labelledby="createCourseModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createCourseModalLabel">Create New Course</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('admin.courses.create') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="course_code" class="form-label">Course Code <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('course_code') is-invalid @enderror" 
                               id="course_code" name="course_code" 
                               value="{{ old('course_code') }}" 
                               placeholder="e.g., CSE370" required>
                        @error('course_code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="mb-3">
                        <label for="number_of_sections" class="form-label">Number of Sections <span class="text-danger">*</span></label>
                        <input type="number" class="form-control @error('number_of_sections') is-invalid @enderror" 
                               id="number_of_sections" name="number_of_sections" 
                               value="{{ old('number_of_sections', 1) }}" 
                               min="1" max="20" required>
                        @error('number_of_sections')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Enter the number of sections to create for this course (1-20).</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Course</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection