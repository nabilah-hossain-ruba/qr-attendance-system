@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>
                        <i class="bi bi-book-half"></i> 
                        @if($user->isStudent())
                            Course Enrollment
                        @elseif($user->isLecturer())
                            Course Assignment
                        @else
                            Course Management
                        @endif
                    </h4>
                    <a href="{{ route('home.main') }}" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Home
                    </a>
                </div>

                <div class="card-body">
                    <!-- Success/Error Messages -->
                    <div id="alertContainer"></div>

                    <!-- Instructions -->
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i>
                        <strong>Instructions:</strong>
                        @if($user->user_type === 'student')
                            Select a course from the dropdown, then choose a section to enroll in. You can only enroll in sections that are not full.
                        @elseif($user->user_type === 'lecturer')
                            Select a course from the dropdown, then choose a section to assign yourself as lecturer. You can only assign yourself to sections without an existing lecturer.
                        @endif
                    </div>

                    <!-- Course Selection Form -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6><i class="bi bi-search"></i> Select Course & Section</h6>
                                </div>
                                <div class="card-body">
                                    <form id="courseSelectionForm">
                                        @csrf
                                        <div class="mb-3">
                                            <label for="courseSelect" class="form-label">Course</label>
                                            <select class="form-control" id="courseSelect" required>
                                                <option value="">Choose a course...</option>
                                                @foreach($courses as $course)
                                                    <option value="{{ $course->code }}">
                                                        {{ $course->code }} - {{ $course->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label for="sectionSelect" class="form-label">Section</label>
                                            <select class="form-control" id="sectionSelect" disabled required>
                                                <option value="">Select course first...</option>
                                            </select>
                                            <div class="form-text">Available sections will appear after selecting a course.</div>
                                        </div>

                                        <div class="d-grid">
                                            <button type="submit" class="btn btn-success" id="enrollBtn" disabled>
                                                <i class="bi bi-check-circle"></i>
                                                @if($user->user_type === 'student')
                                                    Enroll in Course
                                                @else
                                                    Assign to Course
                                                @endif
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <!-- Selected Section Details -->
                            <div class="card" id="sectionDetailsCard" style="display: none;">
                                <div class="card-header">
                                    <h6><i class="bi bi-info-circle"></i> Section Details</h6>
                                </div>
                                <div class="card-body" id="sectionDetailsContent">
                                    <!-- Details will be populated via JavaScript -->
                                </div>
                            </div>

                            <!-- Loading indicator -->
                            <div class="text-center" id="loadingIndicator" style="display: none;">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="mt-2">Loading sections...</p>
                            </div>
                        </div>
                    </div>

                    <!-- Current Enrollments/Assignments -->
                    <hr class="my-4">
                    <div class="row">
                        <div class="col-md-12">
                            <h5>
                                @if($user->user_type === 'student')
                                    <i class="bi bi-journal-bookmark"></i> Your Current Enrollments
                                @else
                                    <i class="bi bi-journal-check"></i> Your Current Assignments
                                @endif
                            </h5>
                            
                            @php
                                // Get current enrollments/assignments based on user type
                                $currentItems = [];
                                if ($user->user_type === 'student') {
                                    $enrollments = \App\Models\Enrollment::where('student_id', $user->student_id)
                                                    ->where('enrollment_status', 'enrolled')
                                                    ->with(['course', 'courseSection'])
                                                    ->get();
                                    foreach ($enrollments as $enrollment) {
                                        $currentItems[] = (object) [
                                            'id' => $enrollment->id,
                                            'course_code' => $enrollment->course_code,
                                            'course_name' => $enrollment->course->name ?? 'Unknown Course',
                                            'section_number' => $enrollment->section_number,
                                            'semester' => $enrollment->semester,
                                            'academic_year' => $enrollment->academic_year,
                                        ];
                                    }
                                } elseif ($user->user_type === 'lecturer') {
                                    $sections = \App\Models\CourseSection::where('lecturer_id', $user->lecturer_id)
                                                 ->active()
                                                 ->with('course')
                                                 ->get();
                                    foreach ($sections as $section) {
                                        $currentItems[] = (object) [
                                            'id' => $section->id,
                                            'course_code' => $section->course_code,
                                            'course_name' => $section->course->name ?? 'Unknown Course',
                                            'section_number' => $section->section_number,
                                            'semester' => $section->semester,
                                            'academic_year' => $section->academic_year,
                                            'enrolled_count' => $section->current_enrollment,
                                        ];
                                    }
                                }
                            @endphp
                            
                            @if(count($currentItems) > 0)
                                <div class="row" id="currentEnrollments">
                                    @foreach($user->courses as $course)
                                        <div class="col-md-4 mb-3">
                                            <div class="card border-primary">
                                                <div class="card-body">
                                                    <h6 class="card-title">
                                                        <span class="badge bg-primary">{{ $course->course_code }}</span>
                                                    </h6>
                                                    <p class="card-text">{{ $course->course_name }}</p>
                                                    <small class="text-muted">
                                                        Section {{ $course->section }} | {{ $course->semester }} {{ $course->year }}
                                                    </small>
                                                    <div class="mt-2">
                                                        <button class="btn btn-outline-danger btn-sm remove-course-btn" 
                                                                data-course-id="{{ $course->id }}"
                                                                data-course-code="{{ $course->course_code }}"
                                                                data-section="{{ $course->section }}">
                                                            <i class="bi bi-x-circle"></i> Unenroll
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @elseif($user->isLecturer() && $user->courses->count() > 0)
                                <div class="row" id="currentAssignments">
                                    @foreach($user->courses as $course)
                                        <div class="col-md-4 mb-3">
                                            <div class="card border-success">
                                                <div class="card-body">
                                                    <h6 class="card-title">
                                                        <span class="badge bg-success">{{ $course->course_code }}</span>
                                                    </h6>
                                                    <p class="card-text">{{ $course->course_name }}</p>
                                                    <small class="text-muted">
                                                        Section {{ $course->section }} | {{ $course->semester }} {{ $course->year }}
                                                        <br>Students: {{ $course->students->count() }}
                                                    </small>
                                                    <div class="mt-2">
                                                        <button class="btn btn-outline-danger btn-sm remove-course-btn" 
                                                                data-course-id="{{ $course->id }}"
                                                                data-course-code="{{ $course->course_code }}"
                                                                data-section="{{ $course->section }}">
                                                            <i class="bi bi-x-circle"></i> Unassign
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="alert alert-light text-center">
                                    <i class="bi bi-inbox"></i>
                                    @if($user->isStudent())
                                        You are not enrolled in any courses yet. Use the form above to enroll in courses.
                                    @else
                                        You are not assigned to any courses yet. Use the form above to assign yourself to courses.
                                    @endif
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
document.addEventListener('DOMContentLoaded', function() {
    const courseSelect = document.getElementById('courseSelect');
    const sectionSelect = document.getElementById('sectionSelect');
    const enrollBtn = document.getElementById('enrollBtn');
    const sectionDetailsCard = document.getElementById('sectionDetailsCard');
    const sectionDetailsContent = document.getElementById('sectionDetailsContent');
    const loadingIndicator = document.getElementById('loadingIndicator');
    const alertContainer = document.getElementById('alertContainer');
    const courseSelectionForm = document.getElementById('courseSelectionForm');

    let selectedSectionData = null;

    // Course selection change handler
    courseSelect.addEventListener('change', function() {
        const courseCode = this.value;
        if (courseCode) {
            loadSections(courseCode);
        } else {
            resetSectionSelect();
        }
    });

    // Section selection change handler
    sectionSelect.addEventListener('change', function() {
        const sectionId = this.value;
        if (sectionId) {
            const section = selectedSectionData.find(s => s.id == sectionId);
            if (section) {
                displaySectionDetails(section);
                enrollBtn.disabled = !section.is_available;
            }
        } else {
            hideSectionDetails();
            enrollBtn.disabled = true;
        }
    });

    // Form submission handler
    courseSelectionForm.addEventListener('submit', function(e) {
        e.preventDefault();
        if (sectionSelect.value) {
            enrollInCourse(sectionSelect.value);
        }
    });

    // Remove course buttons
    document.addEventListener('click', function(e) {
        if (e.target.closest('.remove-course-btn')) {
            const btn = e.target.closest('.remove-course-btn');
            const courseId = btn.dataset.courseId;
            const courseCode = btn.dataset.courseCode;
            const section = btn.dataset.section;
            
            if (confirm(`Are you sure you want to ${@json($user->isStudent() ? 'unenroll from' : 'unassign yourself from')} ${courseCode} Section ${section}?`)) {
                removeFromCourse(courseId);
            }
        }
    });

    function loadSections(courseCode) {
        loadingIndicator.style.display = 'block';
        sectionSelect.disabled = true;
        resetSectionSelect();
        hideSectionDetails();

        fetch('{{ route("get-sections") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ course_code: courseCode })
        })
        .then(response => response.json())
        .then(data => {
            loadingIndicator.style.display = 'none';
            if (data.success) {
                selectedSectionData = data.sections;
                populateSectionSelect(data.sections);
            } else {
                showAlert('Error loading sections', 'danger');
            }
        })
        .catch(error => {
            loadingIndicator.style.display = 'none';
            showAlert('Error loading sections: ' + error.message, 'danger');
        });
    }

    function populateSectionSelect(sections) {
        sectionSelect.innerHTML = '<option value="">Choose a section...</option>';
        
        sections.forEach(section => {
            const option = document.createElement('option');
            option.value = section.id;
            option.textContent = `Section ${section.section} - ${section.status_text}`;
            if (!section.is_available) {
                option.disabled = true;
            }
            sectionSelect.appendChild(option);
        });
        
        sectionSelect.disabled = false;
    }

    function displaySectionDetails(section) {
        const statusClass = section.status === 'available' ? 'success' : 
                           section.status === 'enrolled' || section.status === 'assigned' ? 'primary' : 'warning';

        sectionDetailsContent.innerHTML = `
            <h6 class="mb-3">${courseSelect.options[courseSelect.selectedIndex].text}</h6>
            <div class="row">
                <div class="col-sm-6"><strong>Section:</strong></div>
                <div class="col-sm-6">${section.section}</div>
            </div>
            <div class="row">
                <div class="col-sm-6"><strong>Semester:</strong></div>
                <div class="col-sm-6">${section.semester} ${section.year}</div>
            </div>
            <div class="row">
                <div class="col-sm-6"><strong>Students:</strong></div>
                <div class="col-sm-6">${section.enrolled_students}</div>
            </div>
            ${section.room ? `
            <div class="row">
                <div class="col-sm-6"><strong>Room:</strong></div>
                <div class="col-sm-6">${section.room}</div>
            </div>
            ` : ''}
            ${section.schedule ? `
            <div class="row">
                <div class="col-sm-6"><strong>Schedule:</strong></div>
                <div class="col-sm-6">${section.schedule}</div>
            </div>
            ` : ''}
            <div class="row mt-2">
                <div class="col-12">
                    <span class="badge bg-${statusClass}">${section.status_text}</span>
                </div>
            </div>
        `;
        
        sectionDetailsCard.style.display = 'block';
    }

    function hideSectionDetails() {
        sectionDetailsCard.style.display = 'none';
    }

    function resetSectionSelect() {
        sectionSelect.innerHTML = '<option value="">Select course first...</option>';
        sectionSelect.disabled = true;
        enrollBtn.disabled = true;
        hideSectionDetails();
    }

    function enrollInCourse(courseId) {
        enrollBtn.disabled = true;
        enrollBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Processing...';

        fetch('{{ route("enroll-course") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ section_id: courseId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert(data.message, 'success');
                setTimeout(() => location.reload(), 2000);
            } else {
                showAlert(data.message, 'danger');
                enrollBtn.disabled = false;
                enrollBtn.innerHTML = '<i class="bi bi-check-circle"></i> {{ $user->isStudent() ? "Enroll in Course" : "Assign to Course" }}';
            }
        })
        .catch(error => {
            showAlert('Error: ' + error.message, 'danger');
            enrollBtn.disabled = false;
            enrollBtn.innerHTML = '<i class="bi bi-check-circle"></i> {{ $user->isStudent() ? "Enroll in Course" : "Assign to Course" }}';
        });
    }

    function removeFromCourse(courseId) {
        fetch('{{ route("remove-course") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ course_id: courseId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert(data.message, 'success');
                setTimeout(() => location.reload(), 2000);
            } else {
                showAlert(data.message, 'danger');
            }
        })
        .catch(error => {
            showAlert('Error: ' + error.message, 'danger');
        });
    }

    function showAlert(message, type) {
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        alertContainer.innerHTML = alertHtml;
    }
});
</script>

<style>
.card {
    transition: box-shadow 0.3s ease;
}

.card:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.remove-course-btn:hover {
    transform: scale(1.05);
}

#sectionDetailsCard {
    animation: fadeIn 0.3s ease-in-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
@endsection
