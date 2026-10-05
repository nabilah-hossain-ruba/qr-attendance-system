@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <!-- Card Header -->
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Session Details</span>
                    <a href="{{ route('lecturer.dashboard') }}" class="btn btn-sm btn-secondary">Back to Dashboard</a>
                </div>

                <div class="card-body">
                    <!-- Flash Status -->
                    @if (session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif

                    <div class="row mb-4">
                        <!-- Session Information -->
                        <div class="col-md-6">
                            <h5>Session Information</h5>
                            <table class="table table-bordered">
                                <tr>
                                    <th>Course</th>
                                    <td>{{ $session->course->course_code }} - {{ $session->course->course_name }}</td>
                                </tr>
                                <tr>
                                    <th>Date</th>
                                    <td>{{ $session->date->format('M d, Y') }}</td>
                                </tr>
                                <tr>
                                    <th>Status</th>
                                    <td>
                                        <span class="badge {{ $session->status == 'active' ? 'bg-success' : 'bg-secondary' }}">
                                            {{ ucfirst($session->status) }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>QR Code Expiry</th>
                                    <td>
                                        @if($session->qr_expiry > now())
                                            <span class="text-success">{{ $session->qr_expiry->format('h:i A') }}</span>
                                            (Expires in {{ now()->diffInMinutes($session->qr_expiry) }} minutes)
                                        @else
                                            <span class="text-danger">Expired</span>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <!-- QR Code Display -->
                        <div class="col-md-6 text-center">
                            <h5>QR Code</h5>
                            @if($session->qr_expiry > now() && $session->status == 'active')
                                <div class="p-3 border rounded bg-light">
                                    <div id="qrcode" class="d-inline-block"></div>
                                    <div class="mt-2">
                                        <small class="text-muted">Show this QR code to students for attendance</small>
                                        <div class="mt-2">
                                            <a href="{{ route('lecturer.session.qr', $session->id) }}" class="btn btn-sm btn-info" target="_blank">
                                                <i class="bi bi-fullscreen"></i> View QR Only
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="alert alert-warning">
                                    QR code has expired. Generate a new session from the dashboard.
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Attendance List -->
                    <h5>Attendance List</h5>
                    @if($session->attendances->isEmpty())
                        <div class="alert alert-info">
                            No students have marked attendance for this session yet.
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Student ID</th>
                                        <th>Name</th>
                                        <th>Time</th>
                                        <th>IP Address</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($session->attendances as $attendance)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ $attendance->user->student_id }}</td>
                                            <td>{{ $attendance->user->name }}</td>
                                            <td>{{ $attendance->marked_at->format('h:i:s A') }}</td>
                                            <td>{{ $attendance->ip_address }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                    <!-- Actions -->
                    <div class="mt-4">
                        <div class="d-flex justify-content-between">
                            @if($session->status == 'active')
                                <form action="{{ route('lecturer.session.close', $session->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-warning">Close Session</button>
                                </form>
                            @else
                                <span class="text-muted">This session is closed</span>
                            @endif

                            <a href="{{ route('lecturer.session.export', $session->id) }}" class="btn btn-success">
                                <i class="fas fa-file-excel"></i> Export Attendance
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const sessionStatus = "{{ $session->status }}";
    const qrExpiry = new Date("{{ $session->qr_expiry }}");
    const qrCodeValue = "{{ $session->qr_code }}";
    const now = new Date();

    if(sessionStatus === 'active' && qrExpiry > now) {
        const qrCodeContainer = document.getElementById('qrcode');
        QRCode.toCanvas(qrCodeContainer, qrCodeValue, {
            width: 200,
            margin: 2,
            color: {
                dark: '#000000',
                light: '#ffffff'
            }
        }, function(error) {
            if (error) {
                console.error('QR Code generation failed:', error);
                qrCodeContainer.innerHTML = '<div class="alert alert-danger">Failed to generate QR code</div>';
            }
        });
    }
});
</script>
@endsection
