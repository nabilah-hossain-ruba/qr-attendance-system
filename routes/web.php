<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\LecturerController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\CourseSelectionController;
use App\Http\Controllers\CourseEnrollmentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Guest routes (unauthenticated)
Route::middleware('guest')->group(function () {
    // Login
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);

    // Registration
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);

    // Password Reset
    Route::get('/password/reset', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('/password/email', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
    Route::get('/password/reset/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('/password/reset', [ResetPasswordController::class, 'reset'])->name('password.update');
});

// Authenticated routes
Route::middleware('auth')->group(function () {
    // Logout
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    Route::get('/logout', function() {
        return redirect('/login');
    });
});

// Public Home Route - Redirect to login
Route::get('/', function () {
    return redirect()->route('login');
})->name('home.welcome');

// Main Home Route (after login)
Route::get('/home-main', function () {
    return view('home-main');
})->middleware(['auth'])->name('home.main');

// Authenticated routes (common)
Route::middleware(['auth'])->group(function () {
    // Home dashboard after login
    Route::get('/home', function () { return view('home'); })->name('home.dashboard');

    // Attendance CRUD routes (could also use Route::resource)
    Route::get('/attendances', [AttendanceController::class, 'index'])->name('attendances.index');
    Route::get('/attendances/create', [AttendanceController::class, 'create'])->name('attendances.create');
    Route::post('/attendances', [AttendanceController::class, 'store'])->name('attendances.store');
    
    // Course Enrollment routes (for both students and lecturers)
    Route::get('/add-courses', [CourseEnrollmentController::class, 'addCourses'])->name('add-courses');
    Route::post('/get-sections', [CourseEnrollmentController::class, 'getSections'])->name('get-sections');
    Route::post('/enroll-course', [CourseEnrollmentController::class, 'enrollInCourse'])->name('enroll-course');
    Route::post('/remove-course', [CourseEnrollmentController::class, 'removeFromCourse'])->name('remove-course');
});

// Student routes
Route::middleware(['auth', 'role:student'])->group(function () {
    Route::get('/student/dashboard', [StudentController::class, 'dashboard'])->name('student.dashboard');
    Route::get('/student/attendance/history', [AttendanceController::class, 'history'])->name('student.attendance.history');
    Route::get('/student/scan-qr', [StudentController::class, 'scanQr'])->name('student.scan-qr');
    Route::post('/attendance/mark', [AttendanceController::class, 'markAttendance'])->name('attendance.mark');
    
    // Course enrollment routes
    Route::get('/student/courses', [StudentController::class, 'showCourses'])->name('student.courses');
    Route::post('/student/courses/enroll', [StudentController::class, 'enrollCourses'])->name('student.courses.enroll');
    Route::post('/student/courses/unenroll', [StudentController::class, 'unenrollCourse'])->name('student.courses.unenroll');
});

// Lecturer routes
Route::middleware(['auth', 'role:lecturer'])->group(function () {
    Route::get('/lecturer/dashboard', [LecturerController::class, 'dashboard'])->name('lecturer.dashboard');
    Route::post('/lecturer/qr/generate', [LecturerController::class, 'generateQrCode'])->name('lecturer.qr.generate');
    Route::get('/lecturer/session/{session}', [LecturerController::class, 'showSession'])->name('lecturer.session.show');
    Route::get('/lecturer/session/{session}/qr', [LecturerController::class, 'showQrOnly'])->name('lecturer.session.qr');
    Route::post('/lecturer/session/{session}/close', [LecturerController::class, 'closeSession'])->name('lecturer.session.close');
    Route::get('/lecturer/session/{session}/export', [LecturerController::class, 'exportAttendance'])->name('lecturer.session.export');
    
    // Course assignment routes
    Route::get('/lecturer/courses', [LecturerController::class, 'showCourses'])->name('lecturer.courses');
    Route::post('/lecturer/courses/assign', [LecturerController::class, 'assignCourses'])->name('lecturer.courses.assign');
    Route::post('/lecturer/courses/unassign', [LecturerController::class, 'unassignCourse'])->name('lecturer.courses.unassign');
    
    // Student enrollment routes
    Route::get('/lecturer/enrollment', [LecturerController::class, 'showEnrollment'])->name('lecturer.enrollment');
    Route::post('/lecturer/enrollment/enroll', [LecturerController::class, 'enrollStudents'])->name('lecturer.enrollment.enroll');
    Route::post('/lecturer/enrollment/unenroll', [LecturerController::class, 'unenrollStudents'])->name('lecturer.enrollment.unenroll');
    Route::get('/lecturer/enrollment/students', [LecturerController::class, 'getEnrolledStudents'])->name('lecturer.enrollment.students');
});

// Admin routes
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/admin/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
    
    // User management routes
    Route::get('/admin/users', [AdminController::class, 'users'])->name('admin.users');
    Route::get('/admin/users/{id}/edit', [AdminController::class, 'editUser'])->name('admin.users.edit');
    Route::put('/admin/users/{id}', [AdminController::class, 'updateUser'])->name('admin.users.update');
    Route::delete('/admin/users/{id}', [AdminController::class, 'deleteUser'])->name('admin.users.delete');
    Route::post('/admin/users/reassign-courses', [AdminController::class, 'reassignCourses'])->name('admin.users.reassign-courses');
    
    // Course management routes
    Route::get('/admin/courses', [AdminController::class, 'courses'])->name('admin.courses');
    Route::post('/admin/courses/create', [AdminController::class, 'createCourse'])->name('admin.courses.create');
    Route::post('/admin/upload-schedule', [AdminController::class, 'uploadSchedule'])->name('admin.upload-schedule');
    
    // Session management routes
    Route::get('/admin/sessions', [AdminController::class, 'sessions'])->name('admin.sessions');
    Route::get('/admin/sessions/{id}/edit', [AdminController::class, 'editSession'])->name('admin.sessions.edit');
    Route::put('/admin/sessions/{id}', [AdminController::class, 'updateSession'])->name('admin.sessions.update');
    Route::delete('/admin/sessions/{id}', [AdminController::class, 'deleteSession'])->name('admin.sessions.delete');
    
    // Reports routes
    Route::get('/admin/reports', [AdminController::class, 'reports'])->name('admin.reports');
    Route::post('/admin/reports/generate', [AdminController::class, 'generateReport'])->name('admin.reports.generate');
    
    // CSV Import routes
    Route::get('/admin/import-courses', [AdminController::class, 'showImportForm'])->name('admin.import-courses');
    Route::post('/admin/preview-csv', [AdminController::class, 'previewCsvImport'])->name('admin.preview-csv');
    Route::post('/admin/import-csv', [AdminController::class, 'importCoursesFromCsv'])->name('admin.import-csv');
});
