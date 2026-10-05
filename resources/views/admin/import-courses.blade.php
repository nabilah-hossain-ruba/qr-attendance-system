@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4><i class="bi bi-cloud-download"></i> Sync Courses from BRACU</h4>
                    <a href="{{ route('admin.courses') }}" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Courses
                    </a>
                </div>

                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <div class="card mb-4 border-primary">
                        <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
                            <div>
                                <h6 class="mb-1"><i class="bi bi-arrow-repeat"></i> Sync from BRACU Connect</h6>
                                <small class="text-muted">
                                    Imports every section's course, class days/times, room and faculty initials for the current semester.
                                    Runs automatically every 6 hours.
                                </small>
                            </div>
                            <form method="POST" action="{{ route('admin.sync-bracu') }}"
                                  onsubmit="this.querySelector('button').disabled = true; this.querySelector('button').textContent = 'Syncing...';">
                                @csrf
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-arrow-repeat"></i> Sync Now
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="alert alert-info mb-0">
                        <h6><i class="bi bi-info-circle"></i> How lecturers are assigned</h6>
                        <ul class="mb-0">
                            <li>BRACU lists each section's faculty by initial (e.g. <strong>RBR</strong>).</li>
                            <li>Set a lecturer's <strong>Faculty Initial</strong> on their
                                <a href="{{ route('admin.users') }}">user profile</a> and their sections are assigned to them straight away.</li>
                            <li>Sections still marked <strong>TBA</strong> keep any existing assignment until BRACU names a faculty.</li>
                            <li>No lecturer accounts are created automatically &mdash; lecturers register themselves.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
