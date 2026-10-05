@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4><i class="bi bi-journal-bookmark"></i> Course Assignment</h4>
                    <a href="{{ route('lecturer.dashboard') }}" class="btn btn-secondary">
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
                        <form action="{{ route('lecturer.courses.assign') }}" method="POST" id="assignmentForm">
                            @csrf
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <h5 class="text-primary">Available Courses for Assignment</h5>
                                    <p class="text-muted">Select courses you want to be assigned as lecturer:</p>
                                </div>
                            </div>

                            <div class="row">
                                @foreach($availableCourses as $course)
                                    <div class="col-md-6 col-lg-4 mb-3">
                                        <div class="card course-card h-100 {{ $course->lecturer_id && $course->lecturer_id != auth()->id() ? 'border-warning' : '' }}">
                                            <div class="card-body">
                                                <div class="form-check mb-3">
                                                    <input class="form-check-input" type="checkbox" name="courses[]" 
                                                           value="{{ $course->id }}" id="course{{ $course->id }}"
                                                           {{ $course->lecturer_id && $course->lecturer_id != auth()->id() ? 'disabled' : '' }}>
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
                                                            @if($course->lecturer && $course->lecturer_id != auth()->id())
                                                                <i class="bi bi-person-fill text-warning"></i> <span class="text-warning">{{ $course->lecturer->name }}</span><br>
                                                            @elseif($course->lecturer_id == auth()->id())
                                                                <i class="bi bi-person-check text-success"></i> <span class="text-success">You</span><br>
                                                            @else
                                                                <i class="bi bi-person-dash text-muted"></i> <span class="text-muted">No lecturer assigned</span><br>
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
                                                
                                                @if($course->lecturer_id && $course->lecturer_id != auth()->id())
                                                    <span class="badge bg-warning text-dark">
                                                        <i class="bi bi-exclamation-triangle"></i> Already assigned
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div class="row mt-4">
                                <div class="col-md-12">
                                    <button type="submit" class="btn btn-success" id="assignBtn" disabled>
                                        <i class="bi bi-check-circle"></i> Assign to Selected Courses
                                    </button>
                                    <span class="text-muted ms-2" id="selectedCount">No courses selected</span>
                                </div>
                            </div>
                        </form>
                    @else
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> No courses available for assignment at this time.
                        </div>
                    @endif

                    <!-- Show currently assigned courses -->
                    @if(auth()->user()->courses->count() > 0)
                        <hr class="my-4">
                        <div class="row">
                            <div class="col-md-12">
                                <h5 class="text-success">Currently Assigned Courses</h5>
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
                                                        @if($course->room)
                                                            <br><i class="bi bi-door-open"></i> {{ $course->room }}
                                                        @endif
                                                    </small>
                                                    <div class="mt-2">
                                                        <form action="{{ route('lecturer.courses.unassign') }}" method="POST" 
                                                              onsubmit="return confirm('Are you sure you want to unassign yourself from this course?')" 
                                                              style="display: inline;">
                                                            @csrf
                                                            <input type="hidden" name="course_id" value="{{ $course->id }}">
                                                            <button type="submit" class="btn btn-outline-danger btn-sm">
                                                                <i class="bi bi-x-circle"></i> Unassign
                                                            </button>
                                                        </form>
                                                        <a href="{{ route('lecturer.dashboard') }}" class="btn btn-outline-primary btn-sm ms-1">
                                                            <i class="bi bi-eye"></i> View Sessions
                                                        </a>
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

.course-card.border-warning {
    background-color: #fff8e6;
}

.form-check-input:checked ~ .form-check-label {
    color: #198754;
    font-weight: bold;
}

.form-check-input:disabled {
    opacity: 0.5;
}

.form-check-input:disabled ~ .form-check-label {
    opacity: 0.5;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('input[name="courses[]"]:not([disabled])');
    const assignBtn = document.getElementById('assignBtn');
    const selectedCount = document.getElementById('selectedCount');
    
    function updateAssignButton() {
        const checkedCount = document.querySelectorAll('input[name="courses[]"]:checked:not([disabled])').length;
        
        if (checkedCount > 0) {
            assignBtn.disabled = false;
            selectedCount.textContent = checkedCount === 1 ? '1 course selected' : checkedCount + ' courses selected';
        } else {
            assignBtn.disabled = true;
            selectedCount.textContent = 'No courses selected';
        }
    }
    
    checkboxes.forEach(function(checkbox) {
        checkbox.addEventListener('change', updateAssignButton);
    });
});
</script>
@endsection
