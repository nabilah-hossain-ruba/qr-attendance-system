@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>{{ __('Session Management') }}</h4>
                    <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary">Back to Dashboard</a>
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

                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Course</th>
                                    <th>Date</th>
                                    <th>Start Time</th>
                                    <th>End Time</th>
                                    <th>Status</th>
                                    <th>QR Code</th>
                                    <th>QR Expiry</th>
                                    <th>Attendance</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if(isset($sessions))
                                    @foreach($sessions as $session)
                                        <tr>
                                            <td>
                                                <strong>{{ $session->course->course_code }}</strong><br>
                                                <small class="text-muted">{{ $session->course->course_name }}</small>
                                            </td>
                                            <td>{{ $session->date }}</td>
                                            <td>{{ $session->start_time }}</td>
                                            <td>{{ $session->end_time }}</td>
                                            <td>
                                                <span class="badge bg-{{ $session->status == 'active' ? 'success' : ($session->status == 'completed' ? 'primary' : 'secondary') }}">
                                                    {{ ucfirst($session->status) }}
                                                </span>
                                            </td>
                                            <td>
                                                @if($session->qr_code)
                                                    <code class="small">{{ substr($session->qr_code, 0, 20) }}...</code>
                                                @else
                                                    <span class="text-muted">N/A</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($session->qr_expiry)
                                                    {{ $session->qr_expiry->format('M d, Y H:i') }}
                                                @else
                                                    <span class="text-muted">N/A</span>
                                                @endif
                                            </td>
                                            <td>{{ $session->attendances->count() }} students</td>
                                            <td>
                                                <a href="{{ route('admin.sessions.edit', $session->id) }}" class="btn btn-sm btn-primary" title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <form action="{{ route('admin.sessions.delete', $session->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this session?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="9" class="text-center">No sessions found.</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>

                    @if(isset($sessions) && method_exists($sessions, 'links'))
                        {{ $sessions->links() }}
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
