@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>{{ __('Edit Session') }}</h4>
                    <a href="{{ route('admin.sessions') }}" class="btn btn-secondary">Back to Sessions</a>
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

                    <form method="POST" action="{{ route('admin.sessions.update', $session->id) }}">
                        @csrf
                        @method('PUT')

                        <div class="row mb-3">
                            <label for="course_id" class="col-md-4 col-form-label text-md-end">{{ __('Course') }}</label>
                            <div class="col-md-6">
                                <select id="course_id" class="form-control @error('course_id') is-invalid @enderror" name="course_id" required>
                                    <option value="">Select Course</option>
                                    @foreach($courses as $course)
                                        <option value="{{ $course->id }}" {{ old('course_id', $session->course_id) == $course->id ? 'selected' : '' }}>
                                            {{ $course->course_code }} - {{ $course->course_name }} (Section {{ $course->section }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('course_id')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="date" class="col-md-4 col-form-label text-md-end">{{ __('Date') }}</label>
                            <div class="col-md-6">
                                <input id="date" type="date" class="form-control @error('date') is-invalid @enderror" name="date" value="{{ old('date', $session->date) }}" required>
                                @error('date')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="start_time" class="col-md-4 col-form-label text-md-end">{{ __('Start Time') }}</label>
                            <div class="col-md-6">
                                <input id="start_time" type="time" class="form-control @error('start_time') is-invalid @enderror" name="start_time" value="{{ old('start_time', $session->start_time) }}" required>
                                @error('start_time')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="end_time" class="col-md-4 col-form-label text-md-end">{{ __('End Time') }}</label>
                            <div class="col-md-6">
                                <input id="end_time" type="time" class="form-control @error('end_time') is-invalid @enderror" name="end_time" value="{{ old('end_time', $session->end_time) }}" required>
                                @error('end_time')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="status" class="col-md-4 col-form-label text-md-end">{{ __('Status') }}</label>
                            <div class="col-md-6">
                                <select id="status" class="form-control @error('status') is-invalid @enderror" name="status" required>
                                    <option value="scheduled" {{ old('status', $session->status) == 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                                    <option value="active" {{ old('status', $session->status) == 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="completed" {{ old('status', $session->status) == 'completed' ? 'selected' : '' }}>Completed</option>
                                    <option value="cancelled" {{ old('status', $session->status) == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                </select>
                                @error('status')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="qr_expiry" class="col-md-4 col-form-label text-md-end">{{ __('QR Code Expiry') }}</label>
                            <div class="col-md-6">
                                <input id="qr_expiry" type="datetime-local" class="form-control @error('qr_expiry') is-invalid @enderror" name="qr_expiry" value="{{ old('qr_expiry', $session->qr_expiry ? $session->qr_expiry->format('Y-m-d\TH:i') : '') }}">
                                @error('qr_expiry')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                                <small class="form-text text-muted">Leave empty if no QR code expiry</small>
                            </div>
                        </div>

                        <div class="row mb-0">
                            <div class="col-md-6 offset-md-4">
                                <button type="submit" class="btn btn-primary">
                                    {{ __('Update Session') }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
