@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>{{ __('Course Management') }}</h4>
                    <div>
                        <a href="{{ route('admin.import-courses') }}" class="btn btn-primary me-2">
                            <i class="bi bi-file-earmark-spreadsheet"></i> Import Courses from CSV
                        </a>
                        <button class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#uploadScheduleModal">
                            <i class="bi bi-upload"></i> Upload Schedule CSV
                        </button>
                        <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary">Back to Dashboard</a>
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

                    @if (session('success'))
                        <div class="alert alert-success" role="alert">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if (session('warning'))
                        <div class="alert alert-warning" role="alert">
                            {{ session('warning') }}
                            @if (session('import_warnings') && count(session('import_warnings')) > 0)
                                <hr>
                                <small>
                                    <strong>Import Warnings:</strong>
                                    <ul class="mb-0 mt-2">
                                        @foreach(session('import_warnings') as $warning)
                                            <li>{{ $warning }}</li>
                                        @endforeach
                                    </ul>
                                </small>
                            @endif
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Course Code</th>
                                    <th>Course Name</th>
                                    <th>Section</th>
                                    <th>Semester</th>
                                    <th>Year</th>
                                    <th>Lecturer</th>
                                    <th>Schedule</th>
                                    <th>Room</th>
                                    <th>Students</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if(isset($courses))
                                    @foreach($courses as $course)
                                        <tr>
                                            <td><strong>{{ $course->course_code }}</strong></td>
                                            <td>{{ $course->course_name }}</td>
                                            <td>{{ $course->section }}</td>
                                            <td>{{ $course->semester }}</td>
                                            <td>{{ $course->year }}</td>
                                            <td>{{ $course->lecturer->name ?? 'Not Assigned' }}</td>
                                            <td>{{ $course->schedule ?? 'TBD' }}</td>
                                            <td>{{ $course->room ?? 'TBD' }}</td>
                                            <td>{{ $course->students ? $course->students->count() : 0 }}</td>
                                            <td>
                                                <button class="btn btn-sm btn-primary" title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger" title="Delete">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="10" class="text-center">No courses found. Upload a schedule CSV to get started.</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>

                    @if(isset($courses) && method_exists($courses, 'links'))
                        {{ $courses->links() }}
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Upload Schedule Modal -->
<div class="modal fade" id="uploadScheduleModal" tabindex="-1" aria-labelledby="uploadScheduleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="uploadScheduleModalLabel">Upload Class Schedule CSV</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('admin.upload-schedule') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="semester" class="form-label">Semester</label>
                        <select class="form-control" id="semester" name="semester" required>
                            <option value="">Select Semester</option>
                            <option value="Spring">Spring</option>
                            <option value="Summer">Summer</option>
                            <option value="Fall">Fall</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="year" class="form-label">Year</label>
                        <input type="number" class="form-control" id="year" name="year" value="{{ date('Y') }}" min="2020" max="2030" required>
                    </div>
                    <div class="mb-3">
                        <label for="csv_file" class="form-label">CSV File</label>
                        <input type="file" class="form-control" id="csv_file" name="csv_file" accept=".csv" required>
                        <div class="form-text">
                            CSV should contain columns: course_code, course_name, section, lecturer_name, lecturer_email, schedule, room
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Upload Schedule</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
