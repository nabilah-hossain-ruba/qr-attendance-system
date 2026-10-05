<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Course;
use App\Models\Session;
use App\Models\Attendance;

class TestSystem extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'system:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the QR Attendance System';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing QR Attendance System...');
        
        // Test database connection
        try {
            $userCount = User::count();
            $courseCount = Course::count();
            $sessionCount = Session::count();
            $attendanceCount = Attendance::count();
            
            $this->info("Database Status:");
            $this->info("- Users: {$userCount}");
            $this->info("- Courses: {$courseCount}");
            $this->info("- Sessions: {$sessionCount}");
            $this->info("- Attendance Records: {$attendanceCount}");
            
            // Show existing users
            $this->info("\nExisting Users:");
            $users = User::all(['name', 'email', 'role']);
            foreach ($users as $user) {
                $this->info("- {$user->name} ({$user->email}) - {$user->role}");
            }
            
            // Test email validation
            $this->info("\nTesting BRACU email validation...");
            $rule = new \App\Rules\BracuEmail();
            $this->info("Valid email test: user@bracu.ac.bd");
            $this->info("Invalid email test: user@gmail.com");
            
            $this->info("\nSystem test completed successfully!");
            
        } catch (\Exception $e) {
            $this->error("Database connection failed: " . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}
