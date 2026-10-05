@extends('layouts.app')

@section('content')
<div class="container mx-auto px-6 py-12">
    <div class="max-w-7xl mx-auto bg-white shadow-xl rounded-2xl overflow-hidden border border-gray-200">
        <!-- Header -->
        <div class="bg-[#7D1935] text-white px-8 py-6 flex justify-between items-center">
            <h1 class="text-2xl font-semibold tracking-wide">Student Enrollment Management</h1>
            <a href="{{ route('lecturer.dashboard') }}" class="bg-white bg-opacity-20 hover:bg-opacity-30 text-white px-4 py-2 rounded-lg transition duration-200">
                <i class="bi bi-arrow-left"></i> Back to Dashboard
            </a>
        </div>

        <div class="p-8 space-y-8">
            @if (session('success'))
                <div class="rounded-lg bg-green-50 border border-green-200 text-green-800 px-4 py-3">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="rounded-lg bg-red-50 border border-red-200 text-red-800 px-4 py-3">
                    {{ session('error') }}
                </div>
            @endif

            @if($courses->count() > 0)
                <!-- Course Selection -->
                <div class="bg-gray-50 rounded-lg shadow p-6">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">Select Course</h2>
                    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($courses as $course)
                            <div class="course-card p-4 border rounded-lg cursor-pointer hover:shadow-md transition duration-200" 
                                 data-course-id="{{ $course->id }}" 
                                 data-course-code="{{ $course->course_code }}"
                                 data-course-name="{{ $course->course_name }}"
                                 data-section="{{ $course->section }}">
                                <h3 class="font-semibold text-[#7D1935]">{{ $course->course_code }}</h3>
                                <p class="text-gray-600">{{ $course->course_name }}</p>
                                <p class="text-sm text-gray-500">Section {{ $course->section }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Enrollment Forms -->
                <div id="enrollmentSection" class="hidden">
                    <div class="grid md:grid-cols-2 gap-8">
                        <!-- Enroll Students -->
                        <div class="bg-green-50 rounded-lg shadow p-6">
                            <h3 class="text-lg font-semibold text-green-800 mb-4">
                                <i class="bi bi-person-plus"></i> Enroll Students
                            </h3>
                            <form id="enrollForm" action="{{ route('lecturer.enrollment.enroll') }}" method="POST">
                                @csrf
                                <input type="hidden" name="course_id" id="enrollCourseId">
                                
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Select Students to Enroll</label>
                                    <div class="max-h-64 overflow-y-auto border border-gray-300 rounded-lg p-3">
                                        @foreach($students as $student)
                                            <div class="flex items-center mb-2">
                                                <input type="checkbox" 
                                                       name="student_ids[]" 
                                                       value="{{ $student->id }}" 
                                                       id="enroll_student_{{ $student->id }}"
                                                       class="enroll-checkbox rounded border-gray-300 text-green-600 focus:ring-green-500">
                                                <label for="enroll_student_{{ $student->id }}" class="ml-2 text-sm text-gray-700">
                                                    {{ $student->name }} ({{ $student->student_id }})
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                                
                                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition duration-200">
                                    <i class="bi bi-check-circle"></i> Enroll Selected Students
                                </button>
                            </form>
                        </div>

                        <!-- Unenroll Students -->
                        <div class="bg-red-50 rounded-lg shadow p-6">
                            <h3 class="text-lg font-semibold text-red-800 mb-4">
                                <i class="bi bi-person-dash"></i> Unenroll Students
                            </h3>
                            <form id="unenrollForm" action="{{ route('lecturer.enrollment.unenroll') }}" method="POST">
                                @csrf
                                <input type="hidden" name="course_id" id="unenrollCourseId">
                                
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Select Students to Unenroll</label>
                                    <div id="enrolledStudents" class="max-h-64 overflow-y-auto border border-gray-300 rounded-lg p-3">
                                        <p class="text-gray-500 text-center py-4">Select a course first to view enrolled students</p>
                                    </div>
                                </div>
                                
                                <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg transition duration-200">
                                    <i class="bi bi-x-circle"></i> Unenroll Selected Students
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Current Semester Info -->
                    <div class="mt-6 bg-blue-50 rounded-lg p-4">
                        <p class="text-sm text-blue-800">
                            <i class="bi bi-info-circle"></i> 
                            Current Semester: <strong>{{ $currentSemester }}</strong> | 
                            Academic Year: <strong>{{ $currentAcademicYear }}</strong>
                        </p>
                    </div>
                </div>
            @else
                <div class="text-center py-12">
                    <i class="bi bi-journal-x text-6xl text-gray-400 mb-4"></i>
                    <h3 class="text-xl font-semibold text-gray-600 mb-2">No Courses Assigned</h3>
                    <p class="text-gray-500 mb-4">You need to be assigned to courses before you can manage student enrollment.</p>
                    <a href="{{ route('lecturer.courses') }}" class="bg-[#7D1935] hover:bg-[#5A0F26] text-white px-6 py-3 rounded-lg transition duration-200">
                        <i class="bi bi-plus-circle"></i> Assign Courses
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>

<style>
.course-card {
    transition: all 0.2s ease-in-out;
    border: 2px solid transparent;
}

.course-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.course-card.selected {
    border-color: #7D1935;
    background-color: #f8f4f5;
}

.enroll-checkbox:checked + label {
    font-weight: 600;
    color: #059669;
}

.unenroll-checkbox:checked + label {
    font-weight: 600;
    color: #dc2626;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const courseCards = document.querySelectorAll('.course-card');
    const enrollmentSection = document.getElementById('enrollmentSection');
    const enrollForm = document.getElementById('enrollForm');
    const unenrollForm = document.getElementById('unenrollForm');
    const enrollCourseId = document.getElementById('enrollCourseId');
    const unenrollCourseId = document.getElementById('unenrollCourseId');
    const enrolledStudentsDiv = document.getElementById('enrolledStudents');
    
    let selectedCourseId = null;
    let selectedCourseCode = null;
    let selectedCourseName = null;
    let selectedSection = null;

    // Course selection
    courseCards.forEach(card => {
        card.addEventListener('click', function() {
            // Remove previous selection
            courseCards.forEach(c => c.classList.remove('selected'));
            
            // Add selection to clicked card
            this.classList.add('selected');
            
            // Get course data
            selectedCourseId = this.dataset.courseId;
            selectedCourseCode = this.dataset.courseCode;
            selectedCourseName = this.dataset.courseName;
            selectedSection = this.dataset.section;
            
            // Show enrollment section
            enrollmentSection.classList.remove('hidden');
            
            // Update forms
            enrollCourseId.value = selectedCourseId;
            unenrollCourseId.value = selectedCourseId;
            
            // Load enrolled students
            loadEnrolledStudents();
        });
    });

    // Load enrolled students for selected course
    function loadEnrolledStudents() {
        if (!selectedCourseId) return;
        
        fetch(`{{ route('lecturer.enrollment.students') }}?course_id=${selectedCourseId}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    enrolledStudentsDiv.innerHTML = '<p class="text-red-500 text-center py-4">Error loading students</p>';
                    return;
                }
                
                if (data.length === 0) {
                    enrolledStudentsDiv.innerHTML = '<p class="text-gray-500 text-center py-4">No students enrolled in this course</p>';
                    return;
                }
                
                enrolledStudentsDiv.innerHTML = data.map(student => `
                    <div class="flex items-center mb-2">
                        <input type="checkbox" 
                               name="student_ids[]" 
                               value="${student.id}" 
                               id="unenroll_student_${student.id}"
                               class="unenroll-checkbox rounded border-gray-300 text-red-600 focus:ring-red-500">
                        <label for="unenroll_student_${student.id}" class="ml-2 text-sm text-gray-700">
                            ${student.name} (${student.student_id})
                        </label>
                    </div>
                `).join('');
            })
            .catch(error => {
                console.error('Error:', error);
                enrolledStudentsDiv.innerHTML = '<p class="text-red-500 text-center py-4">Error loading students</p>';
            });
    }

    // Form submission handlers
    enrollForm.addEventListener('submit', function(e) {
        const checkedBoxes = this.querySelectorAll('input[name="student_ids[]"]:checked');
        if (checkedBoxes.length === 0) {
            e.preventDefault();
            alert('Please select at least one student to enroll.');
        }
    });

    unenrollForm.addEventListener('submit', function(e) {
        const checkedBoxes = this.querySelectorAll('input[name="student_ids[]"]:checked');
        if (checkedBoxes.length === 0) {
            e.preventDefault();
            alert('Please select at least one student to unenroll.');
        }
    });
});
</script>
@endsection
