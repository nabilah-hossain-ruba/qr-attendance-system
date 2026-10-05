<?php
/**
 * Optimized CSV Upload Handler
 * Processes large CSV files in chunks to avoid timeout issues
 */

ini_set('max_execution_time', 300); // Increase to 5 minutes
ini_set('memory_limit', '512M'); // Increase memory limit

class OptimizedCSVProcessor {
    private $pdo;
    private $chunkSize = 100; // Process 100 records at a time
    
    public function __construct($database_config) {
        try {
            $this->pdo = new PDO(
                "mysql:host={$database_config['host']};dbname={$database_config['dbname']};charset=utf8",
                $database_config['username'],
                $database_config['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_PERSISTENT => false
                ]
            );
        } catch (PDOException $e) {
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }
    
    public function processScheduleCSV($csvFile) {
        $results = [
            'success' => false,
            'processed' => 0,
            'errors' => [],
            'message' => ''
        ];
        
        try {
            // Validate file
            if (!file_exists($csvFile) || !is_readable($csvFile)) {
                throw new Exception("CSV file not found or not readable");
            }
            
            $handle = fopen($csvFile, 'r');
            if (!$handle) {
                throw new Exception("Could not open CSV file");
            }
            
            // Read header
            $header = fgetcsv($handle);
            if (!$header) {
                throw new Exception("Invalid CSV format - no header found");
            }
            
            // Validate required columns
            $requiredColumns = ['Course', 'Faculty', 'Section', 'Day', 'Start_Time', 'End_Time', 'Room'];
            $missingColumns = array_diff($requiredColumns, $header);
            if (!empty($missingColumns)) {
                throw new Exception("Missing required columns: " . implode(', ', $missingColumns));
            }
            
            $this->pdo->beginTransaction();
            
            // Prepare statements for batch processing
            $insertScheduleStmt = $this->prepareScheduleInsert();
            $insertCourseStmt = $this->prepareCourseInsert();
            $insertSectionStmt = $this->prepareSectionInsert();
            
            $batch = [];
            $processed = 0;
            $courses = [];
            $sections = [];
            
            while (($row = fgetcsv($handle)) !== FALSE) {
                if (count($row) !== count($header)) {
                    $results['errors'][] = "Row " . ($processed + 2) . ": Column count mismatch";
                    continue;
                }
                
                $data = array_combine($header, $row);
                
                // Validate data
                if (empty($data['Course']) || empty($data['Section'])) {
                    $results['errors'][] = "Row " . ($processed + 2) . ": Missing course or section";
                    continue;
                }
                
                // Collect unique courses and sections
                $courseKey = $data['Course'];
                $sectionKey = $data['Course'] . '_' . $data['Section'];
                
                if (!isset($courses[$courseKey])) {
                    $courses[$courseKey] = [
                        'code' => $data['Course'],
                        'name' => $data['Course'], // You can enhance this
                        'faculty' => $data['Faculty'] ?? ''
                    ];
                }
                
                if (!isset($sections[$sectionKey])) {
                    $sections[$sectionKey] = [
                        'course_code' => $data['Course'],
                        'section_number' => $data['Section'],
                        'faculty' => $data['Faculty'] ?? '',
                        'room' => $data['Room'] ?? ''
                    ];
                }
                
                $batch[] = $this->prepareScheduleData($data);
                $processed++;
                
                // Process in chunks
                if (count($batch) >= $this->chunkSize) {
                    $this->processBatch($batch, $insertScheduleStmt);
                    $batch = [];
                    
                    // Clear memory and avoid timeout
                    if ($processed % 500 === 0) {
                        $this->pdo->commit();
                        $this->pdo->beginTransaction();
                    }
                }
            }
            
            // Process remaining batch
            if (!empty($batch)) {
                $this->processBatch($batch, $insertScheduleStmt);
            }
            
            // Insert courses and sections
            $this->insertCoursesAndSections($courses, $sections, $insertCourseStmt, $insertSectionStmt);
            
            $this->pdo->commit();
            fclose($handle);
            
            $results['success'] = true;
            $results['processed'] = $processed;
            $results['message'] = "Successfully processed $processed records";
            
        } catch (Exception $e) {
            if (isset($handle)) fclose($handle);
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            $results['errors'][] = $e->getMessage();
            $results['message'] = "Processing failed: " . $e->getMessage();
        }
        
        return $results;
    }
    
    private function prepareScheduleInsert() {
        $sql = "INSERT INTO schedules (
            course_code, faculty, section_number, day_of_week, start_time, 
            end_time, room, session_id, status, created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'scheduled', NOW(), NOW())
        ON DUPLICATE KEY UPDATE updated_at = NOW()";
        
        return $this->pdo->prepare($sql);
    }
    
    private function prepareCourseInsert() {
        $sql = "INSERT INTO courses (code, name, faculty, created_at, updated_at) 
                VALUES (?, ?, ?, NOW(), NOW()) 
                ON DUPLICATE KEY UPDATE updated_at = NOW()";
        return $this->pdo->prepare($sql);
    }
    
    private function prepareSectionInsert() {
        $sql = "INSERT INTO course_sections (course_code, section_number, faculty, room, created_at, updated_at) 
                VALUES (?, ?, ?, ?, NOW(), NOW()) 
                ON DUPLICATE KEY UPDATE updated_at = NOW()";
        return $this->pdo->prepare($sql);
    }
    
    private function prepareScheduleData($data) {
        return [
            'course_code' => $data['Course'],
            'faculty' => $data['Faculty'] ?? '',
            'section_number' => $data['Section'],
            'day_of_week' => strtoupper($data['Day']),
            'start_time' => $this->convertTime($data['Start_Time']),
            'end_time' => $this->convertTime($data['End_Time']),
            'room' => $data['Room'] ?? '',
            'session_id' => $this->generateSessionId($data)
        ];
    }
    
    private function processBatch($batch, $stmt) {
        foreach ($batch as $record) {
            $stmt->execute([
                $record['course_code'],
                $record['faculty'],
                $record['section_number'],
                $record['day_of_week'],
                $record['start_time'],
                $record['end_time'],
                $record['room'],
                $record['session_id']
            ]);
        }
    }
    
    private function insertCoursesAndSections($courses, $sections, $courseStmt, $sectionStmt) {
        foreach ($courses as $course) {
            $courseStmt->execute([$course['code'], $course['name'], $course['faculty']]);
        }
        
        foreach ($sections as $section) {
            $sectionStmt->execute([
                $section['course_code'],
                $section['section_number'],
                $section['faculty'],
                $section['room']
            ]);
        }
    }
    
    private function convertTime($timeString) {
        try {
            $time = DateTime::createFromFormat('g:i A', trim($timeString));
            return $time ? $time->format('H:i:s') : null;
        } catch (Exception $e) {
            return null;
        }
    }
    
    private function generateSessionId($data) {
        return strtoupper($data['Course']) . '_' . 
               strtoupper($data['Faculty']) . '_' . 
               $data['Section'] . '_' . 
               substr(md5($data['Day'] . $data['Start_Time']), 0, 6);
    }
}

// Usage example
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $config = [
        'host' => 'localhost',
        'dbname' => 'qr_attendance',
        'username' => 'your_username',
        'password' => 'your_password'
    ];
    
    try {
        $processor = new OptimizedCSVProcessor($config);
        $uploadedFile = $_FILES['csv_file']['tmp_name'];
        $result = $processor->processScheduleCSV($uploadedFile);
        
        header('Content-Type: application/json');
        echo json_encode($result);
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}
?>
