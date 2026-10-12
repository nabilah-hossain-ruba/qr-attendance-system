<?php
/**
 * Course Enrollment API
 * Handles automatic enrollment of students in selected course sections
 */

header('Content-Type: application/json');
session_start();

// Database configuration
$config = [
    'host' => 'localhost',
    'dbname' => 'qr_attendance',
    'username' => 'your_username',
    'password' => 'your_password'
];

class EnrollmentAPI {
    private $pdo;
    private $currentUser;
    
    public function __construct($config) {
        try {
            $this->pdo = new PDO(
                "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8",
                $config['username'],
                $config['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );
            
            // Get current user from session
            if (!isset($_SESSION['user_id'])) {
                throw new Exception('User not authenticated');
            }
            
            $this->currentUser = $this->getCurrentUser($_SESSION['user_id']);
            if (!$this->currentUser) {
                throw new Exception('User not found');
            }
            
        } catch (PDOException $e) {
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }
    
    private function getCurrentUser($userId) {
        $stmt = $this->pdo->prepare("
            SELECT id, user_type, student_id, lecturer_id, first_name, last_name, email 
            FROM users 
            WHERE id = ? AND is_active = 1
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }
    
    public function enrollInCourses($sections) {
        $this->pdo->beginTransaction();
        
        try {
            $enrolledCount = 0;
            $errors = [];
            $currentSemester = $this->getCurrentSemester();
            $currentYear = $this->getCurrentAcademicYear();
            
            foreach ($sections as $section) {
                try {
                    $result = $this->enrollInSection(
                        $section['course_code'], 
                        $section['section_number'],
                        $currentSemester,
                        $currentYear
                    );
                    
                    if ($result['success']) {
                        $enrolledCount++;
                    } else {
                        $errors[] = "{$section['course_code']} Section {$section['section_number']}: {$result['message']}";
                    }
                    
                } catch (Exception $e) {
                    $errors[] = "{$section['course_code']} Section {$section['section_number']}: {$e->getMessage()}";
                }
            }
            
            if ($enrolledCount > 0) {
                $this->pdo->commit();
                
                return [
                    'success' => true,
                    'enrolled_count' => $enrolledCount,
                    'total_requested' => count($sections),
                    'errors' => $errors,
                    'message' => "Successfully enrolled in $enrolledCount out of " . count($sections) . " requested courses"
                ];
            } else {
                $this->pdo->rollBack();
                return [
                    'success' => false,
                    'enrolled_count' => 0,
                    'errors' => $errors,
                    'message' => 'No enrollments were successful: ' . implode('; ', $errors)
                ];
            }
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
    
    private function enrollInSection($courseCode, $sectionNumber, $semester, $academicYear) {
        // Validate user is a student
        if ($this->currentUser['user_type'] !== 'student') {
            return ['success' => false, 'message' => 'Only students can enroll in courses'];
        }
        
        $studentId = $this->currentUser['student_id'];
        if (!$studentId) {
            return ['success' => false, 'message' => 'Student ID not found'];
        }
        
        // Check if course and section exist
        $sectionInfo = $this->getSectionInfo($courseCode, $sectionNumber);
        if (!$sectionInfo) {
            return ['success' => false, 'message' => 'Course section not found'];
        }
        
        // Check if section is active
        if (!$sectionInfo['is_active']) {
            return ['success' => false, 'message' => 'Course section is not active'];
        }
        
        // Check capacity
        if ($sectionInfo['enrolled_count'] >= $sectionInfo['capacity']) {
            return ['success' => false, 'message' => 'Section is full'];
        }
        
        // Check if already enrolled
        if ($this->isAlreadyEnrolled($studentId, $courseCode, $sectionNumber, $semester, $academicYear)) {
            return ['success' => false, 'message' => 'Already enrolled in this section'];
        }
        
        // Check for schedule conflicts
        $conflicts = $this->checkScheduleConflicts($studentId, $courseCode, $sectionNumber, $semester, $academicYear);
        if (!empty($conflicts)) {
            return ['success' => false, 'message' => 'Schedule conflict with: ' . implode(', ', $conflicts)];
        }
        
        // Check prerequisites (if implemented)
        // $prereqCheck = $this->checkPrerequisites($studentId, $courseCode);
        
        // Perform enrollment
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO enrollments (
                    student_id, course_code, section_number, 
                    enrollment_status, semester, academic_year
                ) VALUES (?, ?, ?, 'enrolled', ?, ?)
            ");
            
            $stmt->execute([$studentId, $courseCode, $sectionNumber, $semester, $academicYear]);
            
            // Log enrollment activity
            $this->logEnrollmentActivity($studentId, $courseCode, $sectionNumber, 'enrolled');
            
            return ['success' => true, 'message' => 'Successfully enrolled'];
            
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) { // Duplicate entry
                return ['success' => false, 'message' => 'Already enrolled in this section'];
            }
            throw $e;
        }
    }
    
    private function getSectionInfo($courseCode, $sectionNumber) {
        $stmt = $this->pdo->prepare("
            SELECT cs.*, c.name as course_name
            FROM course_sections cs
            JOIN courses c ON cs.course_code = c.code
            WHERE cs.course_code = ? AND cs.section_number = ?
        ");
        $stmt->execute([$courseCode, $sectionNumber]);
        return $stmt->fetch();
    }
    
    private function isAlreadyEnrolled($studentId, $courseCode, $sectionNumber, $semester, $academicYear) {
        $stmt = $this->pdo->prepare("
            SELECT id FROM enrollments 
            WHERE student_id = ? AND course_code = ? AND section_number = ? 
            AND semester = ? AND academic_year = ?
            AND enrollment_status IN ('enrolled', 'waitlisted')
        ");
        $stmt->execute([$studentId, $courseCode, $sectionNumber, $semester, $academicYear]);
        return $stmt->fetch() !== false;
    }
    
    private function checkScheduleConflicts($studentId, $courseCode, $sectionNumber, $semester, $academicYear) {
        // Get schedule for the section being enrolled in
        $stmt = $this->pdo->prepare("
            SELECT day_of_week, start_time, end_time 
            FROM schedules 
            WHERE course_code = ? AND section_number = ?
        ");
        $stmt->execute([$courseCode, $sectionNumber]);
        $newSchedules = $stmt->fetchAll();
        
        if (empty($newSchedules)) {
            return []; // No schedule defined, no conflict
        }
        
        // Get student's current schedules
        $stmt = $this->pdo->prepare("
            SELECT DISTINCT s.day_of_week, s.start_time, s.end_time, s.course_code, s.section_number
            FROM enrollments e
            JOIN schedules s ON e.course_code = s.course_code AND e.section_number = s.section_number
            WHERE e.student_id = ? AND e.semester = ? AND e.academic_year = ?
            AND e.enrollment_status = 'enrolled'
        ");
        $stmt->execute([$studentId, $semester, $academicYear]);
        $existingSchedules = $stmt->fetchAll();
        
        $conflicts = [];
        
        foreach ($newSchedules as $newSchedule) {
            foreach ($existingSchedules as $existing) {
                if ($newSchedule['day_of_week'] === $existing['day_of_week']) {
                    // Check for time overlap
                    $newStart = strtotime($newSchedule['start_time']);
                    $newEnd = strtotime($newSchedule['end_time']);
                    $existingStart = strtotime($existing['start_time']);
                    $existingEnd = strtotime($existing['end_time']);
                    
                    if (($newStart < $existingEnd && $newEnd > $existingStart)) {
                        $conflicts[] = "{$existing['course_code']} Section {$existing['section_number']}";
                    }
                }
            }
        }
        
        return array_unique($conflicts);
    }
    
    private function getCurrentSemester() {
        $stmt = $this->pdo->prepare("
            SELECT setting_value FROM system_settings 
            WHERE setting_key = 'current_semester'
        ");
        $stmt->execute();
        $result = $stmt->fetch();
        return $result ? $result['setting_value'] : 'Fall 2025';
    }
    
    private function getCurrentAcademicYear() {
        $stmt = $this->pdo->prepare("
            SELECT setting_value FROM system_settings 
            WHERE setting_key = 'current_academic_year'
        ");
        $stmt->execute();
        $result = $stmt->fetch();
        return $result ? $result['setting_value'] : '2025-2026';
    }
    
    private function logEnrollmentActivity($studentId, $courseCode, $sectionNumber, $action) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO enrollment_logs (
                    student_id, course_code, section_number, action, 
                    performed_by, created_at
                ) VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$studentId, $courseCode, $sectionNumber, $action, $this->currentUser['id']]);
        } catch (Exception $e) {
            // Log errors but don't fail the enrollment
            error_log("Failed to log enrollment activity: " . $e->getMessage());
        }
    }
    
    public function dropCourse($courseCode, $sectionNumber) {
        if ($this->currentUser['user_type'] !== 'student') {
            throw new Exception('Only students can drop courses');
        }
        
        $studentId = $this->currentUser['student_id'];
        $currentSemester = $this->getCurrentSemester();
        $currentYear = $this->getCurrentAcademicYear();
        
        try {
            $this->pdo->beginTransaction();
            
            // Check if enrolled
            $stmt = $this->pdo->prepare("
                SELECT id FROM enrollments 
                WHERE student_id = ? AND course_code = ? AND section_number = ?
                AND semester = ? AND academic_year = ?
                AND enrollment_status = 'enrolled'
            ");
            $stmt->execute([$studentId, $courseCode, $sectionNumber, $currentSemester, $currentYear]);
            $enrollment = $stmt->fetch();
            
            if (!$enrollment) {
                throw new Exception('Not enrolled in this course section');
            }
            
            // Update enrollment status
            $stmt = $this->pdo->prepare("
                UPDATE enrollments 
                SET enrollment_status = 'dropped', drop_date = NOW(), updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$enrollment['id']]);
            
            // Log activity
            $this->logEnrollmentActivity($studentId, $courseCode, $sectionNumber, 'dropped');
            
            $this->pdo->commit();
            
            return [
                'success' => true,
                'message' => 'Successfully dropped course'
            ];
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}

// Handle API requests
try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Only POST method allowed');
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }
    
    $api = new EnrollmentAPI($config);
    
    if (isset($input['sections'])) {
        // Enrollment request
        if (empty($input['sections']) || !is_array($input['sections'])) {
            throw new Exception('Sections array is required');
        }
        
        $result = $api->enrollInCourses($input['sections']);
        echo json_encode($result);
        
    } elseif (isset($input['course_code']) && isset($input['section_number'])) {
        // Drop course request
        $result = $api->dropCourse($input['course_code'], $input['section_number']);
        echo json_encode($result);
        
    } else {
        throw new Exception('Invalid request parameters');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
