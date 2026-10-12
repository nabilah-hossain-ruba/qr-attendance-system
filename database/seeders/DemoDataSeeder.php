<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Course;
use App\Models\Session;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get or create Admin User
        $admin = User::firstOrCreate(
            ['email' => env('SEED_ADMIN_EMAIL', 'admin@g.bracu.ac.bd')],
            [
                'name' => 'Demo Admin',
                'email_verified_at' => now(),
                'password' => Hash::make(env('SEED_ADMIN_PASSWORD', 'password')),
                'role' => 'admin',
            ]
        );

        // Create Sample Lecturers
        $lecturer1 = User::firstOrCreate(
            ['email' => 'lecturer.demo@g.bracu.ac.bd'],
            [
                'name' => 'Demo Lecturer',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'role' => 'lecturer',
            ]
        );

        // Create Sample Students
        $student1 = User::firstOrCreate(
            ['email' => 'student.demo@g.bracu.ac.bd'],
            [
                'name' => 'Demo Student',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'role' => 'student',
                'student_id' => '20000001',
            ]
        );

        // Create Sample Courses
        $course1 = Course::firstOrCreate(
            [
                'course_code' => 'CSE370',
                'section' => '1',
                'semester' => 'Summer',
                'year' => 2025,
            ],
            [
                'course_name' => 'Database Systems',
                'lecturer_id' => $lecturer1->id,
                'schedule' => 'ST 10:00-11:30',
                'room' => 'UB40301',
            ]
        );

        // Enroll students in courses
        $course1->students()->syncWithoutDetaching([$student1->id]);

        // Create Sample Session
        Session::firstOrCreate(
            [
                'course_id' => $course1->id,
                'date' => now()->toDateString(),
            ],
            [
                'start_time' => '10:00:00',
                'end_time' => '11:30:00',
                'qr_code' => 'TEST_QR_CODE_123',
                'qr_expiry' => now()->addHours(2),
                'status' => 'active',
            ]
        );

        $this->command->info('Demo data created!');
        $this->command->info('Admin: ' . env('SEED_ADMIN_EMAIL', 'admin@g.bracu.ac.bd') . ' / (SEED_ADMIN_PASSWORD, default "password")');
        $this->command->info('Lecturer: lecturer.demo@g.bracu.ac.bd / password');
        $this->command->info('Student: student.demo@g.bracu.ac.bd / password');
    }
}
