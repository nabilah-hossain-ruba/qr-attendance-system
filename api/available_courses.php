<?php
/**
 * Available Courses API
 * Returns list of available courses with sections for enrollment
 */

header('Content-Type: application/json');
session_start();

$config = [
    'host' => 'localhost',
    'dbname' => 'qr_attendance',
    'username' => 'your_username',
    'password' => 'your_password'
];

try {
    $pdo = new PDO(
        "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8",
        $config['username'],
        $config['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    
    // Get current semester and academic year
    $currentSemester = 'Summer 2025'; // Default fallback
    $currentYear = '2025'; // Default fallback
    
    $semesterStmt = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'current_semester'");
    if ($semesterResult = $semesterStmt->fetch()) {
        $currentSemester = $semesterResult['setting_value'];
    }
    
    $yearStmt = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'current_academic_year'");
    if ($yearResult = $yearStmt->fetch()) {
        $currentYear = $yearResult['setting_value'];
    }
    
    // Get available courses with sections
    $coursesStmt = $pdo->prepare("
        SELECT DISTINCT 
            c.code,
            c.name,
            c.description,
            c.credits,
            c.department,
            c.faculty
        FROM courses c
        JOIN course_sections cs ON c.code = cs.course_code
        WHERE c.is_active = 1 
        AND cs.is_active = 1
        AND cs.semester = ?
        AND cs.academic_year = ?
        ORDER BY c.code
    ");
    
    $coursesStmt->execute([$currentSemester, $currentYear]);
    $courses = $coursesStmt->fetchAll();
    
    $result = [];
    
    foreach ($courses as $course) {
        // Get sections for this course
        $sectionsStmt = $pdo->prepare("
            SELECT 
                cs.section_number,
                cs.capacity,
                cs.enrolled_count,
                cs.room,
                u.first_name as lecturer_first_name,
                u.last_name as lecturer_last_name,
                CONCAT(u.first_name, ' ', u.last_name) as lecturer_name
            FROM course_sections cs
            LEFT JOIN users u ON cs.lecturer_id = u.lecturer_id AND u.user_type = 'lecturer'
            WHERE cs.course_code = ?
            AND cs.semester = ?
            AND cs.academic_year = ?
            AND cs.is_active = 1
            ORDER BY cs.section_number
        ");
        
        $sectionsStmt->execute([$course['code'], $currentSemester, $currentYear]);
        $sections = $sectionsStmt->fetchAll();
        
        // Get schedule for each section
        foreach ($sections as &$section) {
            $scheduleStmt = $pdo->prepare("
                SELECT day_of_week, start_time, end_time, room
                FROM schedules
                WHERE course_code = ? AND section_number = ?
                ORDER BY 
                    CASE day_of_week
                        WHEN 'MONDAY' THEN 1
                        WHEN 'TUESDAY' THEN 2
                        WHEN 'WEDNESDAY' THEN 3
                        WHEN 'THURSDAY' THEN 4
                        WHEN 'FRIDAY' THEN 5
                        WHEN 'SATURDAY' THEN 6
                        WHEN 'SUNDAY' THEN 7
                    END,
                    start_time
            ");
            
            $scheduleStmt->execute([$course['code'], $section['section_number']]);
            $schedule = $scheduleStmt->fetchAll();
            
            // Format schedule
            $section['schedule'] = array_map(function($s) {
                return [
                    'day' => ucfirst(strtolower($s['day_of_week'])),
                    'start_time' => date('g:i A', strtotime($s['start_time'])),
                    'end_time' => date('g:i A', strtotime($s['end_time'])),
                    'room' => $s['room']
                ];
            }, $schedule);
        }
        
        // Calculate available capacity
        $totalCapacity = array_sum(array_column($sections, 'capacity'));
        $totalEnrolled = array_sum(array_column($sections, 'enrolled_count'));
        $availableCapacity = $totalCapacity - $totalEnrolled;
        
        $result[] = [
            'code' => $course['code'],
            'name' => $course['name'],
            'description' => $course['description'],
            'credits' => (int)$course['credits'],
            'department' => $course['department'],
            'faculty' => $course['faculty'],
            'total_capacity' => $totalCapacity,
            'total_enrolled' => $totalEnrolled,
            'available_capacity' => $availableCapacity,
            'semester' => $currentSemester,
            'academic_year' => $currentYear,
            'sections' => $sections
        ];
    }
    
    echo json_encode($result);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to load available courses',
        'message' => $e->getMessage()
    ]);
}
?>
