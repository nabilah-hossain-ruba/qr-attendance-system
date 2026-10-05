@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>
                        @if($user->isStudent())
                            Course Enrollment
                        @elseif($user->isLecturer())
                            Course Assignment
                        @endif
                    </h4>
                    <div>
                        @if($user->isStudent() && $userCourses->count() > 0)
                            <a href="{{ route('student.dashboard') }}" class="btn btn-secondary">Go to Dashboard</a>
                        @elseif($user->isLecturer() && $userCourses->count() > 0)
                            <a href="{{ route('lecturer.dashboard') }}" class="btn btn-secondary">Go to Dashboard</a>
                        @endif
                    </div>
                </div>

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

                    <div class="row">
                        <!-- Available Courses -->
                        <div class="col-md-8">
                            <h5>Available Courses</h5>
                            @if($availableCourses->count() > 0)
                                <form method="POST" action="{{ route('course-selection.store') }}">
                                    @csrf
                                    <div class="mb-3">
                                        <div class="form-text mb-3">
                                            @if($user->isStudent())
                                                Select the courses you want to enroll in this semester.
                                            @else
                                                Select the courses you will be teaching this semester.
                                            @endif
                                        </div>
                                        
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th width="50">Select</th>
                                                        <th>Course Code</th>
                                                        <th>Course Name</th>
                                                        <th>Section</th>
                                                        <th>Semester</th>
                                                        <th>Schedule</th>
                                                        <th>Room</th>
                                                        @if($user->isStudent())
                                                            <th>Lecturer</th>
                                                        @else
                                                            <th>Current Lecturer</th>
                                                        @endif
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($availableCourses as $course)
                                                        <tr>
                                                            <td>
                                                                <div class="form-check">
                                                                    <input class="form-check-input" type="checkbox" 
                                                                           name="courses[]" value="{{ $course->id }}" 
                                                                           id="course_{{ $course->id }}"
                                                                           @if($userCourses->contains($course->id)) checked @endif>
                                                                </div>
                                                            </td>
                                                            <td><strong>{{ $course->course_code }}</strong></td>
                                                            <td>{{ $course->course_name }}</td>
                                                            <td>{{ $course->section }}</td>
                                                            <td>{{ $course->semester }} {{ $course->year }}</td>
                                                            <td>
                                                                <small class="text-muted">{{ $course->schedule ?? 'TBD' }}</small>
                                                            </td>
                                                            <td>
                                                                <small class="text-muted">{{ $course->room ?? 'TBD' }}</small>
                                                            </td>
                                                            <td>
                                                                @if($course->lecturer)
                                                                    <small class="text-success">{{ $course->lecturer->name }}</small>
                                                                @else
                                                                    <small class="text-warning">Not Assigned</small>
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="selectAll()">Select All</button>
                                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="selectNone()">Select None</button>
                                        </div>
                                        <button type="submit" class="btn btn-primary">
                                            @if($user->isStudent())
                                                <i class="bi bi-person-plus"></i> Enroll in Selected Courses
                                            @else
                                                <i class="bi bi-briefcase"></i> Assign to Selected Courses
                                            @endif
                                        </button>
                                    </div>
                                </form>
                            @else
                                <div class="alert alert-info">
                                    <h6><i class="bi bi-info-circle"></i> No Courses Available</h6>
                                    <p class="mb-0">No courses have been uploaded yet. Please ask the administrator to upload the class schedule.</p>
                                </div>
                            @endif
                        </div>

                        <!-- Current Enrollments/Assignments -->
                        <div class="col-md-4">
                            <h5>
                                @if($user->isStudent())
                                    My Enrolled Courses
                                @else
                                    My Assigned Courses
                                @endif
                            </h5>
                            
                            @if($userCourses->count() > 0)
                                <div class="list-group">
                                    @foreach($userCourses as $course)
                                        <div class="list-group-item">
                                            <div class="d-flex w-100 justify-content-between">
                                                <h6 class="mb-1">{{ $course->course_code }}</h6>
                                                <small class="text-muted">Section {{ $course->section }}</small>
                                            </div>
                                            <p class="mb-1">{{ $course->course_name }}</p>
                                            <small class="text-muted">{{ $course->semester }} {{ $course->year }}</small>
                                        </div>
                                    @endforeach
                                </div>
                                
                                <div class="mt-3 text-center">
                                    @if($user->isStudent())
                                        <a href="{{ route('student.dashboard') }}" class="btn btn-success">
                                            <i class="bi bi-speedometer2"></i> Go to Student Dashboard
                                        </a>
                                    @else
                                        <a href="{{ route('lecturer.dashboard') }}" class="btn btn-success">
                                            <i class="bi bi-speedometer2"></i> Go to Lecturer Dashboard
                                        </a>
                                    @endif
                                </div>
                            @else
                                <div class="alert alert-warning">
                                    <small>
                                        @if($user->isStudent())
                                            You haven't enrolled in any courses yet.
                                        @else
                                            You haven't been assigned to any courses yet.
                                        @endif
                                    </small>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function selectAll() {
    const checkboxes = document.querySelectorAll('input[name="courses[]"]');
    checkboxes.forEach(checkbox => checkbox.checked = true);
}

function selectNone() {
    const checkboxes = document.querySelectorAll('input[name="courses[]"]');
    checkboxes.forEach(checkbox => checkbox.checked = false);
}
</script>
@endsection
