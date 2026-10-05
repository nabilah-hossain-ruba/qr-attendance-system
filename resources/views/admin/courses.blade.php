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
                            <i class="bi bi-cloud-download"></i> Sync from BRACU
                        </a>
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

@endsection
