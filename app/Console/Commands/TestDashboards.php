<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Course;
use App\Models\Session;
use App\Models\Attendance;
use Illuminate\Support\Facades\Route;

class TestDashboards extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dashboards:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test all dashboard routes and functionality';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing Dashboard Routes and Functionality...');
        
        // Test route registration
        $this->info('\n1. Testing Route Registration:');
        
        $routes = [
            'admin.dashboard' => 'Admin Dashboard',
            'lecturer.dashboard' => 'Lecturer Dashboard', 
            'student.dashboard' => 'Student Dashboard',
            'student.scan-qr' => 'Student QR Scanner',
            'attendance.mark' => 'Mark Attendance',
            'lecturer.qr.generate' => 'Generate QR Code'
        ];
        
        foreach ($routes as $routeName => $description) {
            if (Route::has($routeName)) {
                $this->info("✓ {$description} route exists");
            } else {
                $this->error("✗ {$description} route missing");
            }
        }
        
        // Test user roles
        $this->info('\n2. Testing User Roles:');
        $adminCount = User::where('role', 'admin')->count();
        $lecturerCount = User::where('role', 'lecturer')->count();
        $studentCount = User::where('role', 'student')->count();
        
        $this->info("✓ Admins: {$adminCount}");
        $this->info("✓ Lecturers: {$lecturerCount}");
        $this->info("✓ Students: {$studentCount}");
        
        // Test data relationships
        $this->info('\n3. Testing Data Relationships:');
        
        $courseCount = Course::count();
        $sessionCount = Session::count();
        $attendanceCount = Attendance::count();
        
        $this->info("✓ Courses: {$courseCount}");
        $this->info("✓ Sessions: {$sessionCount}");
        $this->info("✓ Attendances: {$attendanceCount}");
        
        // Test active sessions
        $activeSessions = Session::where('status', 'active')->count();
        $this->info("✓ Active Sessions: {$activeSessions}");
        
        // Test course enrollments
        $enrollments = Course::withCount('students')->get();
        $this->info('\n4. Course Enrollments:');
        foreach ($enrollments as $course) {
            $this->info("✓ {$course->course_code}: {$course->students_count} students enrolled");
        }
        
        $this->info('\n5. Dashboard Requirements Check:');
        
        // Check if views exist
        $views = [
            'admin.dashboard',
            'lecturer.dashboard', 
            'student.dashboard',
            'student.scan-qr',
            'lecturer.session'
        ];
        
        foreach ($views as $view) {
            $viewPath = resource_path("views/" . str_replace('.', '/', $view) . ".blade.php");
            if (file_exists($viewPath)) {
                $this->info("✓ View {$view} exists");
            } else {
                $this->error("✗ View {$view} missing");
            }
        }
        
        $this->info('\n✅ Dashboard testing completed!');
        $this->info('\nTo test the dashboards manually:');
        $this->info('1. Run: php artisan serve');
        $this->info('2. Visit http://localhost:8000');
        $this->info('3. Login with:');
        $this->info('   - Admin: admin@g.bracu.ac.bd / password');
        $this->info('   - Lecturer: lecturer.demo@g.bracu.ac.bd / password');
        $this->info('   - Student: student.demo@g.bracu.ac.bd / password');
        
        return 0;
    }
}
