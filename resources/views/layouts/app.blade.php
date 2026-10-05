<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'QR Attendance') }}</title>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=Nunito:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Scripts -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <style>
        /* Lavender Theme Variables */
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

        /* Global Styles */
        body {
            font-family: 'Nunito', sans-serif;
            background-color: #f8f9fa;
        }

        /* Navigation Styles */
        .navbar {
            background: var(--gradient-primary) !important;
            box-shadow: 0 2px 20px rgba(150, 123, 182, 0.15);
            border: none;
            padding: 1rem 0;
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            color: white !important;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .navbar-brand:hover {
            color: var(--lavender-light) !important;
        }


        .navbar-nav .nav-link {
            color: white !important;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            transition: all 0.3s ease;
        }

        .navbar-nav .nav-link:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white !important;
        }

        .navbar-nav .btn {
            border-radius: 25px;
            padding: 0.5rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }

        .navbar-nav .btn:hover {
            background: rgba(255, 255, 255, 0.2);
            border-color: rgba(255, 255, 255, 0.5);
            transform: translateY(-2px);
        }

        .navbar-toggler {
            border: none;
            color: white;
        }

        .navbar-toggler:focus {
            box-shadow: none;
        }

        .dropdown-menu {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(150, 123, 182, 0.15);
            background: white;
            margin-top: 0.5rem;
        }

        .dropdown-item {
            color: var(--text-dark);
            font-weight: 500;
            padding: 0.75rem 1.5rem;
            transition: all 0.3s ease;
        }

        .dropdown-item:hover {
            background: var(--lavender-light);
            color: var(--lavender-dark);
        }

        .dropdown-item:active {
            background: var(--lavender-primary);
            color: white;
        }

        /* Main Content */
        main {
            min-height: calc(100vh - 80px);
        }

        /* Custom Button Styles */
        .btn-primary {
            background: var(--gradient-primary);
            border: none;
            color: white;
            font-weight: 600;
            border-radius: 25px;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: var(--lavender-dark);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(150, 123, 182, 0.3);
        }

        .btn-success {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border: none;
            color: white;
            font-weight: 600;
            border-radius: 25px;
            transition: all 0.3s ease;
        }

        .btn-success:hover {
            background: #1e7e34;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
        }

        .btn-danger {
            background: linear-gradient(135deg, #dc3545 0%, #fd7e14 100%);
            border: none;
            color: white;
            font-weight: 600;
            border-radius: 25px;
            transition: all 0.3s ease;
        }

        .btn-danger:hover {
            background: #c82333;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.3);
        }

        /* Card Styles */
        .card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 5px 20px rgba(150, 123, 182, 0.1);
            transition: all 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(150, 123, 182, 0.15);
        }

        .card-header {
            background: var(--gradient-secondary);
            border-bottom: 1px solid rgba(150, 123, 182, 0.1);
            border-radius: 20px 20px 0 0 !important;
            color: var(--text-dark);
            font-weight: 600;
        }

        /* Form Styles */
        .form-control {
            border: 2px solid var(--lavender-light);
            border-radius: 15px;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--lavender-primary);
            box-shadow: 0 0 0 0.2rem rgba(150, 123, 182, 0.25);
        }

        /* Alert Styles */
        .alert {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .alert-success {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
        }

        .alert-danger {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
        }

        .alert-warning {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            color: #856404;
        }

        .alert-info {
            background: linear-gradient(135deg, #d1ecf1 0%, #bee5eb 100%);
            color: #0c5460;
        }

        /* Table Styles */
        .table {
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(150, 123, 182, 0.1);
        }

        .table thead th {
            background: var(--gradient-primary);
            color: white;
            border: none;
            font-weight: 600;
            padding: 1rem;
        }

        .table tbody td {
            border-color: var(--lavender-light);
            padding: 1rem;
        }

        /* Badge Styles */
        .badge {
            border-radius: 20px;
            padding: 0.5rem 1rem;
            font-weight: 500;
        }

        .badge.bg-primary {
            background: var(--gradient-primary) !important;
        }

        .badge.bg-success {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%) !important;
        }

        .badge.bg-danger {
            background: linear-gradient(135deg, #dc3545 0%, #fd7e14 100%) !important;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .navbar-brand {
                font-size: 1.25rem;
            }
            
            .navbar-nav .btn {
                margin: 0.25rem 0;
                width: 100%;
            }
        }

        /* Animation Classes */
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .slide-in-left {
            animation: slideInLeft 0.5s ease-out;
        }

        @keyframes slideInLeft {
            from { opacity: 0; transform: translateX(-30px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .slide-in-right {
            animation: slideInRight 0.5s ease-out;
        }

        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(30px); }
            to { opacity: 1; transform: translateX(0); }
        }
    </style>
</head>
<body>
    <div id="app">
        <nav class="navbar navbar-expand-md navbar-light shadow-sm">
            <div class="container">
                <a class="navbar-brand" href="{{ url('/') }}">
                    {{ config('app.name', 'QR Attendance') }}
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="{{ __('Toggle navigation') }}">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <!-- Left Side Of Navbar -->
                    <ul class="navbar-nav me-auto">
                        @auth
                            <li class="nav-item">
                                @if(Auth::user()->isStudent())
                                    <a class="nav-link btn btn-primary text-white me-2" href="{{ route('student.dashboard') }}">
                                        <i class="bi bi-speedometer2"></i> Dashboard
                                    </a>
                                @elseif(Auth::user()->isLecturer())
                                    <a class="nav-link btn btn-success text-white me-2" href="{{ route('lecturer.dashboard') }}">
                                        <i class="bi bi-speedometer2"></i> Dashboard
                                    </a>
                                @elseif(Auth::user()->isAdmin())
                                    <a class="nav-link btn btn-danger text-white me-2" href="{{ route('admin.dashboard') }}">
                                        <i class="bi bi-speedometer2"></i> Dashboard
                                    </a>
                                @endif
                            </li>
                        @endauth
                    </ul>

                    <!-- Right Side Of Navbar -->
                    <ul class="navbar-nav ms-auto">
                        <!-- Authentication Links -->
                        @guest
                            @if (Route::has('login'))
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('login') }}">{{ __('Login') }}</a>
                                </li>
                            @endif

                            @if (Route::has('register'))
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('register') }}">{{ __('Register') }}</a>
                                </li>
                            @endif
                        @else
                            <li class="nav-item dropdown">
                                <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                    <i class="bi bi-person-circle me-1"></i>{{ Auth::user()->name }}
                                </a>

                                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                    <div class="dropdown-header">
                                        <strong>{{ Auth::user()->name }}</strong>
                                        <br>
                                        <small class="text-muted">{{ ucfirst(Auth::user()->role) }}</small>
                                    </div>
                                    <div class="dropdown-divider"></div>
                                    
                                    @if(Auth::user()->isStudent())
                                        <a class="dropdown-item" href="{{ route('student.dashboard') }}">
                                            <i class="bi bi-speedometer2 me-2"></i>{{ __('Dashboard') }}
                                        </a>
                                    @elseif(Auth::user()->isLecturer())
                                        <a class="dropdown-item" href="{{ route('lecturer.dashboard') }}">
                                            <i class="bi bi-speedometer2 me-2"></i>{{ __('Dashboard') }}
                                        </a>
                                    @elseif(Auth::user()->isAdmin())
                                        <a class="dropdown-item" href="{{ route('admin.dashboard') }}">
                                            <i class="bi bi-speedometer2 me-2"></i>{{ __('Dashboard') }}
                                        </a>
                                    @endif
                                    
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item text-danger" href="#"
                                       onclick="event.preventDefault(); logout();">
                                        <i class="bi bi-box-arrow-right me-2"></i>{{ __('Logout') }}
                                    </a>

                                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                        @csrf
                                    </form>
                                </div>
                            </li>
                        @endguest
                    </ul>
                </div>
            </div>
        </nav>

        <main class="py-4">
            @yield('content')
        </main>
    </div>

    <script>
        function logout() {
            if (confirm('Are you sure you want to logout?')) {
                document.getElementById('logout-form').submit();
            }
        }
    </script>
</body>
</html>