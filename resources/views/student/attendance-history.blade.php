@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>My Attendance History</span>
                    <a href="{{ route('student.dashboard') }}" class="btn btn-sm btn-secondary">Back to Dashboard</a>
                </div>

                <div class="card-body">
                    @if (session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif

                    @if($attendances->isEmpty())
                        <div class="alert alert-info">
                            You have no attendance records yet.
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Course</th>
                                        <th>Session</th>
                                        <th>Status</th>
                                        <th>Marked At</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($attendances as $attendance)
                                        <tr>
                                            <td>{{ $attendance->session->date->format('M d, Y') }}</td>
                                            <td>{{ $attendance->session->course->course_code }} - {{ $attendance->session->course->course_name }}</td>
                                            <td>{{ $attendance->session->title ?? 'Regular Class' }}</td>
                                            <td>
                                                <span class="badge {{ $attendance->status == 'present' ? 'bg-success' : 'bg-danger' }}">
                                                    {{ ucfirst($attendance->status) }}
                                                </span>
                                            </td>
                                            <td>{{ $attendance->marked_at->format('M d, Y h:i A') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="d-flex justify-content-center mt-4">
                            {{ $attendances->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection