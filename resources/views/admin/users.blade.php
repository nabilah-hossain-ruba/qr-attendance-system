@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>{{ __('User Management') }}</h4>
                    <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary">Back to Dashboard</a>
                </div>

                <div class="card-body">
                    @if (session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Student ID</th>
                                    <th>Verified</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if(isset($users))
                                    @foreach($users as $user)
                                        <tr>
                                            <td>{{ $user->name }}</td>
                                            <td>{{ $user->email }}</td>
                                            <td>
                                                <span class="badge bg-{{ $user->role == 'admin' ? 'danger' : ($user->role == 'lecturer' ? 'warning' : 'primary') }}">
                                                    {{ ucfirst($user->role) }}
                                                </span>
                                            </td>
                                            <td>{{ $user->student_id ?? 'N/A' }}</td>
                                            <td>
                                                @if($user->email_verified_at)
                                                    <span class="badge bg-success">Verified</span>
                                                @else
                                                    <span class="badge bg-warning">Not Verified</span>
                                                @endif
                                            </td>
                                            <td>{{ $user->created_at->format('M d, Y') }}</td>
                                            <td>
                                                <a href="{{ route('admin.users.edit', $user->id) }}" class="btn btn-sm btn-primary" title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                @if($user->role === 'lecturer' && $user->courses->count() > 0)
                                                    <button class="btn btn-sm btn-warning" title="Has {{ $user->courses->count() }} assigned course(s)" disabled>
                                                        <i class="bi bi-exclamation-triangle"></i>
                                                    </button>
                                                @endif
                                                <form action="{{ route('admin.users.delete', $user->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this user?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger" title="Delete" 
                                                            @if($user->role === 'lecturer' && $user->courses->count() > 0) disabled @endif>
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="7" class="text-center">No users found.</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>

                    @if(isset($users) && method_exists($users, 'links'))
                        {{ $users->links() }}
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
