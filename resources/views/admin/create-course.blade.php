@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>{{ __('Create New Course') }}</h4>
                    <a href="{{ route('admin.courses') }}" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Back to Courses
                    </a>
                </div>

                <div class="card-body">
                    @if (session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('admin.courses.store') }}">
                        @csrf

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="code" class="form-label">{{ __('Course Code') }} <span class="text-danger">*</span></label>
                                    <input id="code" type="text" class="form-control @error('code') is-invalid @enderror" 
                                           name="code" value="{{ old('code') }}" required autocomplete="code" autofocus
                                           placeholder="e.g., CSE110">
                                    @error('code')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                    <small class="form-text text-muted">Unique course identifier (e.g., CSE110, MAT101)</small>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="credits" class="form-label">{{ __('Credits') }} <span class="text-danger">*</span></label>
                                    <select id="credits" class="form-control @error('credits') is-invalid @enderror" name="credits" required>
                                        <option value="">Select Credits</option>
                                        @for($i = 1; $i <= 6; $i++)
                                            <option value="{{ $i }}" {{ old('credits') == $i ? 'selected' : '' }}>{{ $i }}</option>
                                        @endfor
                                    </select>
                                    @error('credits')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <label for="name" class="form-label">{{ __('Course Name') }} <span class="text-danger">*</span></label>
                            <input id="name" type="text" class="form-control @error('name') is-invalid @enderror" 
                                   name="name" value="{{ old('name') }}" required
                                   placeholder="e.g., Introduction to Computer Science">
                            @error('name')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="form-group mb-3">
                            <label for="description" class="form-label">{{ __('Course Description') }}</label>
                            <textarea id="description" class="form-control @error('description') is-invalid @enderror" 
                                      name="description" rows="3" 
                                      placeholder="Brief description of the course content and objectives">{{ old('description') }}</textarea>
                            @error('description')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="department" class="form-label">{{ __('Department') }} <span class="text-danger">*</span></label>
                                    <input id="department" type="text" class="form-control @error('department') is-invalid @enderror" 
                                           name="department" value="{{ old('department') }}" required
                                           placeholder="e.g., Computer Science and Engineering">
                                    @error('department')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="faculty" class="form-label">{{ __('Faculty') }}</label>
                                    <input id="faculty" type="text" class="form-control @error('faculty') is-invalid @enderror" 
                                           name="faculty" value="{{ old('faculty') }}"
                                           placeholder="e.g., Faculty of Engineering">
                                    @error('faculty')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">
                        <h5>Section Configuration</h5>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label for="number_of_sections" class="form-label">{{ __('Number of Sections') }} <span class="text-danger">*</span></label>
                                    <input id="number_of_sections" type="number" min="1" max="20" 
                                           class="form-control @error('number_of_sections') is-invalid @enderror" 
                                           name="number_of_sections" value="{{ old('number_of_sections', 1) }}" required>
                                    @error('number_of_sections')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                    <small class="form-text text-muted">How many sections to create for this course</small>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label for="section_capacity" class="form-label">{{ __('Section Capacity') }} <span class="text-danger">*</span></label>
                                    <input id="section_capacity" type="number" min="5" max="200" 
                                           class="form-control @error('section_capacity') is-invalid @enderror" 
                                           name="section_capacity" value="{{ old('section_capacity', 40) }}" required>
                                    @error('section_capacity')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                    <small class="form-text text-muted">Maximum students per section</small>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label for="semester" class="form-label">{{ __('Semester') }} <span class="text-danger">*</span></label>
                                    <select id="semester" class="form-control @error('semester') is-invalid @enderror" name="semester" required>
                                        <option value="">Select Semester</option>
                                        <option value="Fall" {{ old('semester') == 'Fall' ? 'selected' : '' }}>Fall</option>
                                        <option value="Spring" {{ old('semester') == 'Spring' ? 'selected' : '' }}>Spring</option>
                                        <option value="Summer" {{ old('semester') == 'Summer' ? 'selected' : '' }}>Summer</option>
                                    </select>
                                    @error('semester')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <label for="academic_year" class="form-label">{{ __('Academic Year') }} <span class="text-danger">*</span></label>
                            <select id="academic_year" class="form-control @error('academic_year') is-invalid @enderror" name="academic_year" required>
                                <option value="">Select Academic Year</option>
                                @php
                                    $currentYear = date('Y');
                                    $startYear = $currentYear - 1;
                                    $endYear = $currentYear + 2;
                                @endphp
                                @for($year = $startYear; $year <= $endYear; $year++)
                                    @php $academicYear = $year . '-' . ($year + 1); @endphp
                                    <option value="{{ $academicYear }}" {{ old('academic_year') == $academicYear || $academicYear == '2025-2026' ? 'selected' : '' }}>
                                        {{ $academicYear }}
                                    </option>
                                @endfor
                            </select>
                            @error('academic_year')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="alert alert-info">
                            <h6><i class="fas fa-info-circle"></i> What will happen:</h6>
                            <ul class="mb-0">
                                <li>The course <strong id="preview-code">{{ old('code', '[COURSE_CODE]') }}</strong> will be created</li>
                                <li><span id="preview-sections">{{ old('number_of_sections', '1') }}</span> section(s) will be created (01, 02, 03, etc.)</li>
                                <li>Each section will have a capacity of <span id="preview-capacity">{{ old('section_capacity', '40') }}</span> students</li>
                                <li>Sections will be available for lecturer assignment and student enrollment</li>
                            </ul>
                        </div>

                        <div class="form-group mb-0 text-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-plus"></i> {{ __('Create Course & Sections') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Update preview when values change
    const codeInput = document.getElementById('code');
    const sectionsInput = document.getElementById('number_of_sections');
    const capacityInput = document.getElementById('section_capacity');
    
    function updatePreview() {
        document.getElementById('preview-code').textContent = codeInput.value || '[COURSE_CODE]';
        document.getElementById('preview-sections').textContent = sectionsInput.value || '1';
        document.getElementById('preview-capacity').textContent = capacityInput.value || '40';
    }
    
    if (codeInput) codeInput.addEventListener('input', updatePreview);
    if (sectionsInput) sectionsInput.addEventListener('input', updatePreview);
    if (capacityInput) capacityInput.addEventListener('input', updatePreview);
});
</script>
@endsection
