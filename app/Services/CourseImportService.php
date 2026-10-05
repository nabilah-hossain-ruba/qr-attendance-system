<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Course;
use App\Models\User;

class CourseImportService
{
    /**
     * Import courses from CSV data
     *
     * @param array $csvData
     * @return array
     */
    public function importFromCsvData($csvData)
    {
        $coursesCreated = 0;
        $coursesUpdated = 0;
        $errors = 0;
        $warnings = [];
        $processedCourses = [];

        DB::beginTransaction();

        try {
            foreach ($csvData as $row) {
                // Extract only the required fields
                $courseCode = trim($row['Course'] ?? '');
                $section = trim($row['Section'] ?? '');
                $facultyInitial = trim($row['Faculty'] ?? '');

                // Validate required fields
                if (empty($courseCode) || empty($section) || empty($facultyInitial)) {
                    $errors++;
                    $warnings[] = "Skipped row with missing data: Course={$courseCode}, Section={$section}, Faculty={$facultyInitial}";
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
                if (!$lecturer) {
                    $warnings[] = "Could not create/find lecturer for faculty initial: {$facultyInitial}";
                }

                // Check if course already exists
                $existingCourse = Course::where('course_code', $courseCode)
                                      ->where('section', $section)
                                      ->first();

                if ($existingCourse) {
                    // Update existing course
                    $existingCourse->lecturer_id = $lecturer ? $lecturer->id : null;
                    $existingCourse->save();
                    $coursesUpdated++;
                    Log::info("Updated course: {$courseCode} Section {$section}");
                } else {
                    // Create new course
                    Course::create([
                        'course_code' => $courseCode,
                        'course_name' => $this->generateCourseName($courseCode),
                        'section' => $section,
                        'semester' => 'Fall', // Default semester
                        'year' => now()->year,
                        'lecturer_id' => $lecturer ? $lecturer->id : null,
                        'room' => null,
                        'schedule' => null,
                    ]);
                    $coursesCreated++;
                    Log::info("Created course: {$courseCode} Section {$section}");
                }
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Course import failed: " . $e->getMessage());
            throw $e;
        }

        return [
            'courses_created' => $coursesCreated,
            'courses_updated' => $coursesUpdated,
            'errors' => $errors,
            'warnings' => $warnings,
            'total_processed' => count($processedCourses)
        ];
    }

    /**
     * Process uploaded CSV file
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @return array
     */
    public function importFromUploadedFile($file)
    {
        $csvData = $this->parseCsvFile($file->getPathname());
        return $this->importFromCsvData($csvData);
    }

    /**
     * Parse CSV file and return data array
     *
     * @param string $filePath
     * @return array
     */
    private function parseCsvFile($filePath)
    {
        $csvData = [];
        $header = [];

        if (($handle = fopen($filePath, 'r')) !== false) {
            // Read header row
            $header = fgetcsv($handle);
            
            // Read data rows
            while (($data = fgetcsv($handle)) !== false) {
                if (count($data) === count($header)) {
                    $csvData[] = array_combine($header, $data);
                }
            }
            fclose($handle);
        }

        return $csvData;
    }

    /**
     * Find or create lecturer based on faculty initial
     *
     * @param string $facultyInitial
     * @return User|null
     */
    private function findOrCreateLecturer($facultyInitial)
    {
        // Try to find existing lecturer by lecturer_id
        $lecturer = User::where('role', 'lecturer')
                       ->where('lecturer_id', $facultyInitial . '001')
                       ->first();

        if (!$lecturer) {
            // Try to find by name containing the faculty initial
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
                    'password' => bcrypt('password123'), // Default password
                    'role' => 'lecturer',
                    'lecturer_id' => $facultyInitial . '001',
                ]);
                Log::info("Created new lecturer: Prof. {$facultyInitial}");
            } catch (\Exception $e) {
                // If creation fails (e.g., duplicate email), try with a different email
                try {
                    $lecturer = User::create([
                        'name' => 'Prof. ' . $facultyInitial,
                        'email' => strtolower($facultyInitial) . '_' . time() . '@university.edu',
                        'password' => bcrypt('password123'),
                        'role' => 'lecturer',
                        'lecturer_id' => $facultyInitial . '001',
                    ]);
                    Log::info("Created new lecturer with alternative email: Prof. {$facultyInitial}");
                } catch (\Exception $e2) {
                    Log::error("Failed to create lecturer {$facultyInitial}: " . $e2->getMessage());
                    return null;
                }
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
        // Course code to name mapping
        $courseNames = [
            'ACT201' => 'Principles of Accounting I',
            'ACT202' => 'Principles of Accounting II',
            'CSE101' => 'Introduction to Computer Science',
            'CSE102' => 'Programming Fundamentals',
            'CSE201' => 'Data Structures',
            'CSE202' => 'Algorithms',
            'MAT101' => 'Calculus I',
            'MAT102' => 'Calculus II',
            'ENG101' => 'English Composition',
            'ENG102' => 'Advanced English',
            'PHY101' => 'Physics I',
            'PHY102' => 'Physics II',
            'BUS101' => 'Introduction to Business',
            'ECO101' => 'Microeconomics',
            'ECO102' => 'Macroeconomics',
            // Add more mappings as needed
        ];

        return $courseNames[$courseCode] ?? $courseCode . ' - Course';
    }

    /**
     * Get preview of CSV data without importing
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @return array
     */
    public function previewCsvFile($file, $maxRows = 10)
    {
        $csvData = $this->parseCsvFile($file->getPathname());
        
        $preview = [];
        $rowCount = 0;
        
        foreach ($csvData as $row) {
            if ($rowCount >= $maxRows) break;
            
            $courseCode = trim($row['Course'] ?? '');
            $section = trim($row['Section'] ?? '');
            $facultyInitial = trim($row['Faculty'] ?? '');
            
            if (!empty($courseCode) && !empty($section) && !empty($facultyInitial)) {
                $preview[] = [
                    'course_code' => $courseCode,
                    'section' => $section,
                    'faculty_initial' => $facultyInitial,
                    'generated_course_name' => $this->generateCourseName($courseCode)
                ];
                $rowCount++;
            }
        }
        
        return [
            'preview' => $preview,
            'total_rows' => count($csvData),
            'valid_rows' => $rowCount
        ];
    }
}
