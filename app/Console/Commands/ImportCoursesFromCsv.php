<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Course;
use App\Models\User;

class ImportCoursesFromCsv extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'courses:import-csv {file_path? : Path to the CSV file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import courses from CSV file with course_code, section, and faculty_initial mapping';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $filePath = $this->argument('file_path') ?? base_path('course_schedule_improved.csv');
        
        if (!file_exists($filePath)) {
            $this->error("CSV file not found at: {$filePath}");
            return 1;
        }

        $this->info("Starting CSV import from: {$filePath}");

        try {
            $csvData = $this->readCsvFile($filePath);
            $result = $this->processAndImportCourses($csvData);
            
            $this->info("Import completed successfully!");
            $this->line("- Courses created: {$result['courses_created']}");
            $this->line("- Courses updated: {$result['courses_updated']}");
            $this->line("- Errors: {$result['errors']}");
            
            if (!empty($result['warnings'])) {
                $this->warn("Warnings:");
                foreach ($result['warnings'] as $warning) {
                    $this->line("  - {$warning}");
                }
            }

            return 0;
        } catch (\Exception $e) {
            $this->error("Import failed: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Read and parse CSV file
     *
     * @param string $filePath
     * @return array
     */
    private function readCsvFile($filePath)
    {
        $csvData = [];
        $header = [];

        if (($handle = fopen($filePath, 'r')) !== false) {
            // Read header row
            $header = fgetcsv($handle);
            
            // Read data rows
            while (($data = fgetcsv($handle)) !== false) {
                $csvData[] = array_combine($header, $data);
            }
            fclose($handle);
        }

        return $csvData;
    }

    /**
     * Process CSV data and import courses
     *
     * @param array $csvData
     * @return array
     */
    private function processAndImportCourses($csvData)
    {
        $coursesCreated = 0;
        $coursesUpdated = 0;
        $errors = 0;
        $warnings = [];
        $processedCourses = [];

        // Start database transaction
        DB::beginTransaction();

        try {
            foreach ($csvData as $row) {
                // Extract required fields
                $courseCode = trim($row['Course'] ?? '');
                $section = trim($row['Section'] ?? '');
                $facultyInitial = trim($row['Faculty'] ?? '');

                // Validate required fields
                if (empty($courseCode) || empty($section) || empty($facultyInitial)) {
                    $errors++;
                    continue;
                }

                // Create unique key for this course-section combination
                $courseKey = $courseCode . '_' . $section;

                // Skip if we already processed this course-section combination
                if (isset($processedCourses[$courseKey])) {
                    continue;
                }
                $processedCourses[$courseKey] = true;

                // Find or create lecturer based on faculty_initial
                $lecturer = $this->findOrCreateLecturer($facultyInitial);

                // Check if course already exists
                $existingCourse = Course::where('course_code', $courseCode)
                                      ->where('section', $section)
                                      ->first();

                if ($existingCourse) {
                    // Update existing course
                    $existingCourse->lecturer_id = $lecturer ? $lecturer->id : null;
                    $existingCourse->save();
                    $coursesUpdated++;
                } else {
                    // Create new course
                    Course::create([
                        'course_code' => $courseCode,
                        'course_name' => $this->generateCourseName($courseCode),
                        'section' => $section,
                        'semester' => 'Fall', // Default values
                        'year' => now()->year,
                        'lecturer_id' => $lecturer ? $lecturer->id : null,
                        'room' => null,
                        'schedule' => null,
                    ]);
                    $coursesCreated++;
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return [
            'courses_created' => $coursesCreated,
            'courses_updated' => $coursesUpdated,
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }

    /**
     * Find or create lecturer based on faculty initial
     *
     * @param string $facultyInitial
     * @return User|null
     */
    private function findOrCreateLecturer($facultyInitial)
    {
        // Try to find existing lecturer by lecturer_id (which matches faculty initial pattern)
        $lecturer = User::where('role', 'lecturer')
                       ->where('lecturer_id', $facultyInitial . '001')
                       ->first();

        if (!$lecturer) {
            // Try to find by name pattern
            $lecturer = User::where('role', 'lecturer')
                           ->where('name', 'like', '%' . $facultyInitial . '%')
                           ->first();
        }

        // If still not found, create new lecturer
        if (!$lecturer) {
            try {
                $lecturer = User::create([
                    'name' => 'Prof. ' . $facultyInitial,
                    'email' => strtolower($facultyInitial) . '@university.edu',
                    'password' => bcrypt('password'), // Default password
                    'role' => 'lecturer',
                    'lecturer_id' => $facultyInitial . '001',
                ]);
            } catch (\Exception $e) {
                // If creation fails (e.g., duplicate email), return null
                return null;
            }
        }

        return $lecturer;
    }

    /**
     * Generate course name based on course code
     *
     * @param string $courseCode
     * @return string
     */
    private function generateCourseName($courseCode)
    {
        // Simple mapping for common course codes
        $courseNames = [
            'ACT201' => 'Principles of Accounting I',
            'CSE101' => 'Introduction to Computer Science',
            'MAT101' => 'Calculus I',
            'ENG101' => 'English Composition',
            'PHY101' => 'Physics I',
            // Add more mappings as needed
        ];

        return $courseNames[$courseCode] ?? $courseCode . ' - Course';
    }
}
