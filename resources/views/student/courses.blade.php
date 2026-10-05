@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4><i class="bi bi-book"></i> Course Enrollment</h4>
                    <a href="{{ route('student.dashboard') }}" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Dashboard
                    </a>
                </div>

                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if($availableCourses->count() > 0)
                        <form action="{{ route('student.courses.enroll') }}" method="POST" id="enrollmentForm">
                            @csrf
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <h5 class="text-primary">Available Courses</h5>
                                    <p class="text-muted">Select courses you want to enroll in:</p>
                                </div>
                            </div>

                            <div class="row">
                                @foreach($availableCourses as $course)
                                    <div class="col-md-6 col-lg-4 mb-3">
                                        <div class="card course-card h-100">
                                            <div class="card-body">
                                                <div class="form-check mb-3">
                                                    <input class="form-check-input" type="checkbox" name="courses[]" 
                                                           value="{{ $course->id }}" id="course{{ $course->id }}">
                                                    <label class="form-check-label" for="course{{ $course->id }}">
                                                        <strong>{{ $course->course_code }}</strong>
                                                    </label>
                                                </div>
                                                
                                                <h6 class="card-title">{{ $course->course_name }}</h6>
                                                <div class="course-details">
                                                    <p class="card-text">
                                                        <small class="text-muted">
                                                            <i class="bi bi-calendar3"></i> {{ $course->semester }} {{ $course->year }}<br>
                                                            <i class="bi bi-geo-alt"></i> Section {{ $course->section }}<br>
                                                            @if($course->lecturer)
                                                                <i class="bi bi-person"></i> {{ $course->lecturer->name }}<br>
                                                            @endif
                                                            @if($course->room)
                                                                <i class="bi bi-door-open"></i> {{ $course->room }}<br>
                                                            @endif
                                                            @if($course->schedule)
                                                                <i class="bi bi-clock"></i> {{ $course->schedule }}
                                                            @endif
                                                        </small>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div class="row mt-4">
                                <div class="col-md-12">
                                    <button type="submit" class="btn btn-primary" id="enrollBtn" disabled>
                                        <i class="bi bi-check-circle"></i> Enroll in Selected Courses
                                    </button>
                                    <span class="text-muted ms-2" id="selectedCount">No courses selected</span>
                                </div>
                            </div>
                        </form>
                    @else
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> No courses available for enrollment at this time.
                        </div>
                    @endif

                    <!-- Show currently enrolled courses -->
                    @if(auth()->user()->courses->count() > 0)
                        <hr class="my-4">
                        <div class="row">
                            <div class="col-md-12">
                                <h5 class="text-success">Currently Enrolled Courses</h5>
                                <div class="row">
                                    @foreach(auth()->user()->courses as $course)
                                        <div class="col-md-6 col-lg-4 mb-3">
                                            <div class="card border-success">
                                                <div class="card-body">
                                                    <h6 class="card-title">
                                                        <span class="badge bg-success">{{ $course->course_code }}</span>
                                                    </h6>
                                                    <p class="card-text">{{ $course->course_name }}</p>
                                                    <small class="text-muted">
                                                        {{ $course->semester }} {{ $course->year }} - Section {{ $course->section }}
                                                    </small>
                                                    <div class="mt-2">
                                                        <form action="{{ route('student.courses.unenroll') }}" method="POST" 
                                                              onsubmit="return confirm('Are you sure you want to unenroll from this course?')" 
                                                              style="display: inline;">
                                                            @csrf
                                                            <input type="hidden" name="course_id" value="{{ $course->id }}">
                                                            <button type="submit" class="btn btn-outline-danger btn-sm">
                                                                <i class="bi bi-x-circle"></i> Unenroll
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.course-card {
    transition: transform 0.2s ease-in-out;
    border: 1px solid #dee2e6;
}

.course-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.form-check-input:checked ~ .form-check-label {
    color: #0d6efd;
    font-weight: bold;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('input[name="courses[]"]');
    const enrollBtn = document.getElementById('enrollBtn');
    const selectedCount = document.getElementById('selectedCount');
    
    function updateEnrollButton() {
        const checkedCount = document.querySelectorAll('input[name="courses[]"]:checked').length;
        
        if (checkedCount > 0) {
            enrollBtn.disabled = false;
            selectedCount.textContent = checkedCount === 1 ? '1 course selected' : checkedCount + ' courses selected';
        } else {
            enrollBtn.disabled = true;
            selectedCount.textContent = 'No courses selected';
        }
    }
    
    checkboxes.forEach(function(checkbox) {
        checkbox.addEventListener('change', updateEnrollButton);
    });
});
</script>
@endsection
