@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header">{{ __('Lecturer Dashboard') }}</div>

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

                    @if ($errors->any())
                        <div class="alert alert-danger" role="alert">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    <h4>Welcome back, {{ Auth::user()->name }}!</h4>
                    <p>Lecturer Dashboard</p>


                    <div id="location-error" class="alert alert-danger d-none mt-3"></div>

                    <!-- Today's classes from the BRACU timetable -->
                    <div class="card mt-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-calendar-day"></i> Today's Classes</span>
                            <small class="text-muted">{{ now(config('bracu.timezone'))->format('l, M d') }}</small>
                        </div>
                        <div class="card-body">
                            @if($todaysClasses->isEmpty())
                                <p class="text-muted text-center mb-0">No classes scheduled for you today.</p>
                            @else
                                <div class="list-group">
                                    @foreach($todaysClasses as $class)
                                        <div class="list-group-item d-flex flex-wrap justify-content-between align-items-center gap-2 {{ $class['status'] === 'now' ? 'list-group-item-primary' : '' }}">
                                            <div>
                                                <strong>{{ $class['course']->course_code }}</strong>
                                                <span class="text-muted">Section {{ $class['course']->section }}</span>
                                                @if($class['status'] === 'now')
                                                    <span class="badge bg-primary ms-1">Now</span>
                                                @elseif($class['status'] === 'ended')
                                                    <span class="badge bg-secondary ms-1">Ended</span>
                                                @endif
                                                <br>
                                                <small class="text-muted">
                                                    <i class="bi bi-clock"></i> {{ $class['start']->format('g:i A') }} - {{ $class['end']->format('g:i A') }}
                                                    @if($class['course']->room)
                                                        &middot; <i class="bi bi-door-open"></i> {{ $class['course']->room }}
                                                    @endif
                                                    @if($class['session'])
                                                        &middot; <i class="bi bi-people-fill"></i> {{ $class['session']->attendances()->count() }} present
                                                    @endif
                                                </small>
                                            </div>
                                            <div class="d-flex gap-2">
                                                @if($class['qr_active'])
                                                    <a href="{{ route('lecturer.session.qr', $class['session']->id) }}" class="btn btn-success">
                                                        <i class="bi bi-qr-code"></i> Show QR
                                                    </a>
                                                @else
                                                    @if($class['session'])
                                                        <a href="{{ route('lecturer.session.show', $class['session']->id) }}" class="btn btn-outline-secondary">
                                                            <i class="bi bi-eye"></i> Session
                                                        </a>
                                                    @endif
                                                    <form method="POST" action="{{ route('lecturer.qr.generate') }}" class="js-generate-qr">
                                                        @csrf
                                                        <input type="hidden" name="course_id" value="{{ $class['course']->id }}">
                                                        <input type="hidden" name="latitude">
                                                        <input type="hidden" name="longitude">
                                                        <button type="submit" class="btn {{ $class['status'] === 'now' ? 'btn-primary' : 'btn-outline-primary' }}">
                                                            <i class="bi bi-qr-code"></i> {{ $class['session'] ? 'Generate Again' : 'Generate QR' }}
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                <small class="form-text text-muted d-block mt-2">
                                    <i class="bi bi-geo-alt"></i> Generate from the classroom &mdash; the QR code is valid for {{ config('attendance.qr_valid_minutes') }} minutes and students must be within {{ config('attendance.radius_meters') }}m of you.
                                </small>
                            @endif
                        </div>
                    </div>

                    <div class="row mt-4">
                        <!-- LEFT HALF: Manage Courses -->
                        <div class="col-md-6">
                            <div class="card mb-4">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <span>Manage Courses</span>
                                </div>
                                <div class="card-body text-center">
                                    <p>Manage your course assignments and enrollment.</p>
                                    <a href="{{ route('lecturer.courses') }}" class="btn btn-primary">
                                        <i class="bi bi-gear"></i> Manage Courses
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- RIGHT HALF: Generate QR Code for any course (e.g. make-up classes) -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">Generate QR Code for Another Class</div>
                                <div class="card-body">
                                    @if(count($courses) > 0)
                                        <form method="POST" action="{{ route('lecturer.qr.generate') }}" class="js-generate-qr">
                                            @csrf
                                            <input type="hidden" name="latitude">
                                            <input type="hidden" name="longitude">
                                            <div class="mb-3">
                                                <label for="course_id" class="form-label">
                                                    <i class="bi bi-book"></i> Select Course
                                                </label>
                                                <select id="course_id" name="course_id" required class="form-select">
                                                    <option value="">Choose a course...</option>
                                                    @foreach($courses as $course)
                                                        <option value="{{ $course->id }}">{{ $course->course_code }} - Section {{ $course->section }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="mb-3 form-text">
                                                <i class="bi bi-clock"></i> The QR code is valid for {{ config('attendance.qr_valid_minutes') }} minutes.
                                                <br>
                                                <i class="bi bi-geo-alt"></i> Generate it from the classroom &mdash; students must be within {{ config('attendance.radius_meters') }}m of your current location.
                                            </div>
                                            <button type="submit" class="btn btn-primary w-100">
                                                <i class="bi bi-qr-code"></i> Generate QR Code
                                            </button>
                                        </form>
                                    @else
                                        <div class="text-center">
                                            <p class="text-muted mb-3">No courses assigned to you yet. Ask an admin to set your faculty initial so your BRACU courses are assigned automatically.</p>
                                            <a href="{{ route('lecturer.courses') }}" class="btn btn-primary">
                                                <i class="bi bi-plus-circle"></i> Assign Courses
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <span>Recent Sessions</span>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Course</th>
                                                    <th>Date</th>
                                                    <th>Time</th>
                                                    <th>Status</th>
                                                    <th>Attendance</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($sessions ?? [] as $session)
                                                    <tr>
                                                        <td>
                                                            <strong>{{ $session->course->course_code }}</strong><br>
                                                            <small class="text-muted">Section {{ $session->course->section }}</small>
                                                        </td>
                                                        <td>{{ $session->date }}</td>
                                                        <td>
                                                            @if(isset($session->start_time) && isset($session->end_time))
                                                                {{ $session->start_time }} - {{ $session->end_time }}
                                                            @else
                                                                {{ $session->created_at->format('H:i:s') }}
                                                            @endif
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-
                                                                @if($session->status === 'active') success
                                                                @elseif($session->status === 'completed') primary
                                                                @elseif($session->status === 'cancelled') danger
                                                                @else secondary
                                                                @endif">
                                                                {{ ucfirst($session->status) }}
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <i class="bi bi-people-fill"></i> {{ $session->attendances->count() ?? 0 }} students
                                                        </td>
                                                        <td>
                                                            <a href="{{ route('lecturer.session.show', $session->id) }}" class="btn btn-sm btn-outline-primary">
                                                                <i class="bi bi-eye"></i> View
                                                            </a>
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="6" class="text-center text-muted">
                                                            No recent sessions found. Generate QR codes to start tracking attendance.
                                                        </td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Every "Generate" form sends the lecturer's current location as the classroom location
    document.querySelectorAll('form.js-generate-qr').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (form.dataset.located) {
                return;
            }
            e.preventDefault();

            const button = form.querySelector('button[type="submit"]');
            const buttonHtml = button.innerHTML;
            const errorBox = document.getElementById('location-error');

            if (!form.reportValidity()) {
                return;
            }

            if (!navigator.geolocation) {
                errorBox.textContent = 'Your browser does not support location, which is required to generate a QR code.';
                errorBox.classList.remove('d-none');
                return;
            }

            button.disabled = true;
            button.textContent = 'Getting your location...';
            errorBox.classList.add('d-none');

            navigator.geolocation.getCurrentPosition(function (position) {
                form.querySelector('input[name="latitude"]').value = position.coords.latitude;
                form.querySelector('input[name="longitude"]').value = position.coords.longitude;
                form.dataset.located = '1';
                form.submit();
            }, function (error) {
                button.disabled = false;
                button.innerHTML = buttonHtml;
                errorBox.textContent = 'Could not get your location: ' + error.message + '. Please allow location access and try again.';
                errorBox.classList.remove('d-none');
                errorBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }, { enableHighAccuracy: true, timeout: 20000, maximumAge: 0 });
        });
    });
</script>
@endsection
