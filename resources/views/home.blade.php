@extends('layouts.app')

@section('content')
<!-- Hero Section -->
<div class="hero-section">
    <div class="container">
        <div class="row align-items-center min-vh-75">
            <div class="col-lg-6">
                <div class="hero-content">
                    <h1 class="hero-title">
                        <span class="text-gradient">BRAC University</span><br>
                        QR Attendance System
                    </h1>
                    
                    @auth
                        <div class="welcome-card">
                            <div class="welcome-header">
                                <div class="user-avatar">
                                    <i class="bi bi-person-circle"></i>
                                </div>
                                <div class="user-info">
                                    <h3>Welcome back, {{ Auth::user()->name }}!</h3>
                                    <span class="role-badge role-{{ Auth::user()->role }}">
                                        {{ ucfirst(Auth::user()->role) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="cta-buttons">
                            <a href="{{ route('login') }}" class="btn btn-primary btn-lg me-3">
                                <i class="bi bi-box-arrow-in-right"></i> Login
                            </a>
                            <a href="{{ route('register') }}" class="btn btn-outline-primary btn-lg">
                                <i class="bi bi-person-plus"></i> Register
                            </a>
                        </div>
                    @endauth
                </div>
            </div>
            <div class="col-lg-6">
                <div class="hero-visual">
                    <div class="qr-animation">
                        <div class="qr-code">
                            <div class="qr-grid">
                                @for($i = 0; $i < 25; $i++)
                                    <div class="qr-dot {{ $i % 3 == 0 ? 'active' : '' }}"></div>
                                @endfor
                            </div>
                        </div>
                        <div class="scan-line"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@if (session('status'))
    <div class="container mt-4">
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>
            {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div>
@endif

@auth
<!-- Dashboard Section -->
<div class="dashboard-section">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <h2 class="section-title text-center mb-5">Your Dashboard</h2>
            </div>
        </div>
        
        <div class="row g-4">
            <!-- Main Dashboard Card -->
            <div class="col-lg-8">
                <div class="dashboard-card main-card">
                    <div class="card-header">
                        <div class="card-icon">
                            @if(Auth::user()->isStudent())
                                <i class="bi bi-mortarboard"></i>
                            @elseif(Auth::user()->isLecturer())
                                <i class="bi bi-person-workspace"></i>
                            @else
                                <i class="bi bi-gear"></i>
                            @endif
                        </div>
                        <div class="card-title">
                            <h3>
                                @if(Auth::user()->isStudent())
                                    Student Dashboard
                                @elseif(Auth::user()->isLecturer())
                                    Lecturer Dashboard
                                @else
                                    Admin Dashboard
                                @endif
                            </h3>
                            <p>Access your personalized workspace</p>
                        </div>
                    </div>
                    <div class="card-body">
                        <p class="card-description">
                            @if(Auth::user()->isStudent())
                                View your courses, track attendance, and scan QR codes for class sessions.
                            @elseif(Auth::user()->isLecturer())
                                Manage your courses, generate QR codes, and monitor student attendance.
                            @else
                                Oversee system operations, manage users, and generate comprehensive reports.
                            @endif
                        </p>
                        <a href="{{ Auth::user()->isStudent() ? route('student.dashboard') : (Auth::user()->isLecturer() ? route('lecturer.dashboard') : route('admin.dashboard')) }}" 
                           class="btn btn-primary btn-lg">
                            <i class="bi bi-arrow-right"></i> Go to Dashboard
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Quick Stats -->
            <div class="col-lg-4">
                <div class="stats-grid">
                    @if(Auth::user()->isStudent())
                        @php
                            $enrolledCount = Auth::user()->courses->count();
                            $recentAttendance = Auth::user()->attendances()->latest()->limit(1)->first();
                        @endphp
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="bi bi-book"></i>
                            </div>
                            <div class="stat-content">
                                <h4>{{ $enrolledCount }}</h4>
                                <p>Enrolled Courses</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="bi bi-clock-history"></i>
                            </div>
                            <div class="stat-content">
                                <h4>
                                    @if($recentAttendance)
                                        {{ $recentAttendance->created_at->diffForHumans() }}
                                    @else
                                        No records
                                    @endif
                                </h4>
                                <p>Last Attendance</p>
                            </div>
                        </div>
                    @elseif(Auth::user()->isLecturer())
                        @php
                            $assignedCount = Auth::user()->courses->count();
                            $recentSession = \App\Models\Session::whereIn('course_id', Auth::user()->courses->pluck('id'))->latest()->limit(1)->first();
                        @endphp
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="bi bi-journal-bookmark"></i>
                            </div>
                            <div class="stat-content">
                                <h4>{{ $assignedCount }}</h4>
                                <p>Assigned Courses</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="bi bi-calendar-event"></i>
                            </div>
                            <div class="stat-content">
                                <h4>
                                    @if($recentSession)
                                        {{ $recentSession->created_at->diffForHumans() }}
                                    @else
                                        No sessions
                                    @endif
                                </h4>
                                <p>Recent Session</p>
                            </div>
                        </div>
                    @else
                        @php
                            $totalUsers = \App\Models\User::count();
                            $totalCourses = \App\Models\Course::count();
                        @endphp
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="bi bi-people"></i>
                            </div>
                            <div class="stat-content">
                                <h4>{{ $totalUsers }}</h4>
                                <p>Total Users</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="bi bi-collection"></i>
                            </div>
                            <div class="stat-content">
                                <h4>{{ $totalCourses }}</h4>
                                <p>Total Courses</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        
    </div>
</div>
@endauth

<!-- Features Section -->
<div class="features-section">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <h2 class="section-title text-center mb-5">Why Choose Our System?</h2>
            </div>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="bi bi-lightning-charge"></i>
                    </div>
                    <h4>Lightning Fast</h4>
                    <p>Quick and efficient QR code scanning for instant attendance marking</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="bi bi-shield-check"></i>
                    </div>
                    <h4>Secure & Reliable</h4>
                    <p>Advanced security measures to ensure accurate and tamper-proof attendance records</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="bi bi-graph-up-arrow"></i>
                    </div>
                    <h4>Real-time Analytics</h4>
                    <p>Comprehensive reports and analytics to track attendance patterns and trends</p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Lavender Color Palette */
:root {
    --lavender-primary: #967bb6;
    --lavender-secondary: #b19cd9;
    --lavender-light: #e6d7ff;
    --lavender-dark: #7b5b9a;
    --lavender-accent: #d4c5e8;
    --text-dark: #2d1b4e;
    --text-light: #6b5b95;
    --gradient-primary: linear-gradient(135deg, #967bb6 0%, #b19cd9 100%);
    --gradient-secondary: linear-gradient(135deg, #e6d7ff 0%, #d4c5e8 100%);
}

/* Hero Section */
.hero-section {
    background: var(--gradient-primary);
    color: white;
    position: relative;
    overflow: hidden;
}

.hero-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="75" cy="75" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="50" cy="10" r="0.5" fill="rgba(255,255,255,0.1)"/><circle cx="10" cy="60" r="0.5" fill="rgba(255,255,255,0.1)"/><circle cx="90" cy="40" r="0.5" fill="rgba(255,255,255,0.1)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
    opacity: 0.3;
}

.min-vh-75 {
    min-height: 60vh;
}

.hero-content {
    position: relative;
    z-index: 2;
}

.hero-title {
    font-size: 3.5rem;
    font-weight: 700;
    line-height: 1.2;
    margin-bottom: 1.5rem;
}

.text-gradient {
    background: linear-gradient(45deg, #fff, #e6d7ff);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.hero-subtitle {
    font-size: 1.25rem;
    line-height: 1.6;
    margin-bottom: 2rem;
    opacity: 0.9;
}

.cta-buttons .btn {
    padding: 12px 30px;
    font-weight: 600;
    border-radius: 50px;
    transition: all 0.3s ease;
}

.btn-primary {
    background: white;
    color: var(--lavender-primary);
    border: 2px solid white;
}

.btn-primary:hover {
    background: transparent;
    color: white;
    border-color: white;
    transform: translateY(-2px);
}

.btn-outline-primary {
    background: transparent;
    color: white;
    border: 2px solid white;
}

.btn-outline-primary:hover {
    background: white;
    color: var(--lavender-primary);
    transform: translateY(-2px);
}

/* Welcome Card */
.welcome-card {
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(10px);
    border-radius: 20px;
    padding: 2rem;
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.welcome-header {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.user-avatar i {
    font-size: 3rem;
    color: white;
}

.user-info h3 {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 600;
}

.role-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 500;
    margin-top: 0.5rem;
}

.role-student { background: rgba(255, 255, 255, 0.2); color: white; }
.role-lecturer { background: rgba(255, 255, 255, 0.2); color: white; }
.role-admin { background: rgba(255, 255, 255, 0.2); color: white; }

/* QR Animation */
.hero-visual {
    display: flex;
    justify-content: center;
    align-items: center;
    position: relative;
    z-index: 2;
}

.qr-animation {
    position: relative;
    width: 200px;
    height: 200px;
}

.qr-code {
    width: 100%;
    height: 100%;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(10px);
    border: 2px solid rgba(255, 255, 255, 0.2);
}

.qr-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 6px;
    width: 140px;
    height: 140px;
}

.qr-dot {
    width: 100%;
    height: 100%;
    background: rgba(255, 255, 255, 0.3);
    border-radius: 4px;
    transition: all 0.3s ease;
}

.qr-dot.active {
    background: white;
    animation: pulse 2s infinite;
}

.scan-line {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, transparent, white, transparent);
    animation: scan 3s infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

@keyframes scan {
    0% { transform: translateY(0); }
    100% { transform: translateY(200px); }
}

/* Dashboard Section */
.dashboard-section {
    padding: 3rem 0;
    background: var(--gradient-secondary);
}

.section-title {
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--text-dark);
    margin-bottom: 1rem;
}

.section-subtitle {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--text-light);
}

.dashboard-card {
    background: white;
    border-radius: 20px;
    padding: 2rem;
    box-shadow: 0 10px 30px rgba(150, 123, 182, 0.1);
    border: 1px solid rgba(150, 123, 182, 0.1);
    transition: all 0.3s ease;
    height: 100%;
}

.dashboard-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 20px 40px rgba(150, 123, 182, 0.15);
}

.main-card .card-header {
    display: flex;
    align-items: center;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}

.card-icon {
    width: 60px;
    height: 60px;
    background: var(--gradient-primary);
    border-radius: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
}

.card-title h3 {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--text-dark);
    margin: 0;
}

.card-title p {
    color: var(--text-light);
    margin: 0.5rem 0 0 0;
}

.card-description {
    color: var(--text-light);
    line-height: 1.6;
    margin-bottom: 1.5rem;
}

/* Stats Grid */
.stats-grid {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    height: 100%;
}

.stat-card {
    background: white;
    border-radius: 15px;
    padding: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    box-shadow: 0 5px 15px rgba(150, 123, 182, 0.1);
    border: 1px solid rgba(150, 123, 182, 0.1);
    transition: all 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(150, 123, 182, 0.15);
}

.stat-icon {
    width: 50px;
    height: 50px;
    background: var(--gradient-primary);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.25rem;
}

.stat-content h4 {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--text-dark);
    margin: 0;
}

.stat-content p {
    color: var(--text-light);
    margin: 0;
    font-size: 0.875rem;
}

/* Quick Actions */
.quick-actions {
    display: flex;
    justify-content: center;
    gap: 1rem;
    flex-wrap: wrap;
}

.quick-action-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    padding: 1.5rem;
    background: white;
    border-radius: 15px;
    text-decoration: none;
    color: var(--text-dark);
    box-shadow: 0 5px 15px rgba(150, 123, 182, 0.1);
    border: 1px solid rgba(150, 123, 182, 0.1);
    transition: all 0.3s ease;
    min-width: 120px;
}

.quick-action-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(150, 123, 182, 0.15);
    color: var(--lavender-primary);
}

.quick-action-btn i {
    font-size: 1.5rem;
}

.quick-action-btn span {
    font-weight: 500;
    font-size: 0.875rem;
}

/* Features Section */
.features-section {
    padding: 5rem 0;
    background: white;
}

.feature-card {
    text-align: center;
    padding: 2rem;
    background: var(--gradient-secondary);
    border-radius: 20px;
    height: 100%;
    transition: all 0.3s ease;
}

.feature-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 35px rgba(150, 123, 182, 0.1);
}

.feature-icon {
    width: 80px;
    height: 80px;
    background: var(--gradient-primary);
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1.5rem;
    color: white;
    font-size: 2rem;
}

.feature-card h4 {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--text-dark);
    margin-bottom: 1rem;
}

.feature-card p {
    color: var(--text-light);
    line-height: 1.6;
}

/* Responsive Design */
@media (max-width: 768px) {
    .hero-title {
        font-size: 2.5rem;
    }
    
    .hero-subtitle {
        font-size: 1.1rem;
    }
    
    .cta-buttons {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }
    
    .cta-buttons .btn {
        width: 100%;
    }
    
    .welcome-header {
        flex-direction: column;
        text-align: center;
    }
    
    .quick-actions {
        flex-direction: column;
        align-items: center;
    }
    
    .quick-action-btn {
        width: 100%;
        max-width: 200px;
    }
}

/* Custom Button Styles */
.btn-lg {
    padding: 12px 30px;
    font-size: 1.1rem;
    border-radius: 50px;
}

.btn-primary {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    border: none;
    color: white;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(40, 167, 69, 0.3);
    background: linear-gradient(135deg, #218838 0%, #1ea085 100%);
}

/* Alert Styles */
.alert {
    border-radius: 15px;
    border: none;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}

.alert-success {
    background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
    color: #155724;
}
</style>
@endsection
