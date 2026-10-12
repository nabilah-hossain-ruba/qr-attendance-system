<?php
/**
 * Enrolled Courses API
 * Returns list of courses the current user is enrolled in
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
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('User not authenticated');
    }
    
    $pdo = new PDO(
        "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8",
        $config['username'],
        $config['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    
    // Get current user
    $userStmt = $pdo->prepare("
        SELECT user_type, student_id, lecturer_id 
        FROM users 
        WHERE id = ? AND is_active = 1
    ");
    $userStmt->execute([$_SESSION['user_id']]);
    $user = $userStmt->fetch();
    
    if (!$user) {
        throw new Exception('User not found');
    }
    
    if ($user['user_type'] === 'student') {
        // Get enrolled courses for student
        $stmt = $pdo->prepare("
            SELECT 
                e.course_code,
                e.section_number,
                c.name as course_name,
                c.credits,
                e.enrollment_status,
                e.enrollment_date,
                cs.room,
                CONCAT(u.first_name, ' ', u.last_name) as lecturer_name
            FROM enrollments e
            JOIN courses c ON e.course_code = c.code
            JOIN course_sections cs ON e.course_code = cs.course_code AND e.section_number = cs.section_number
            LEFT JOIN users u ON cs.lecturer_id = u.lecturer_id
            WHERE e.student_id = ? 
            AND e.enrollment_status = 'enrolled'
            ORDER BY e.course_code, e.section_number
        ");
        $stmt->execute([$user['student_id']]);
        
    } elseif ($user['user_type'] === 'lecturer') {
        // Get assigned courses for lecturer
        $stmt = $pdo->prepare("
            SELECT 
                la.course_code,
                la.section_number,
                c.name as course_name,
                c.credits,
                'teaching' as enrollment_status,
                la.assigned_at as enrollment_date,
                cs.room,
                CONCAT(u.first_name, ' ', u.last_name) as lecturer_name,
                cs.enrolled_count,
                cs.capacity
            FROM lecturer_assignments la
            JOIN courses c ON la.course_code = c.code
            JOIN course_sections cs ON la.course_code = cs.course_code AND la.section_number = cs.section_number
            LEFT JOIN users u ON la.lecturer_id = u.lecturer_id
            WHERE la.lecturer_id = ? 
            AND la.is_active = 1
            ORDER BY la.course_code, la.section_number
        ");
        $stmt->execute([$user['lecturer_id']]);
        
    } else {
        // Admin or other user type
        echo json_encode([]);
        exit;
    }
    
    $enrolledCourses = $stmt->fetchAll();
    
    // Get schedule information for each course
    foreach ($enrolledCourses as &$course) {
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
        
        $scheduleStmt->execute([$course['course_code'], $course['section_number']]);
        $schedule = $scheduleStmt->fetchAll();
        
        $course['schedule'] = array_map(function($s) {
            return [
                'day' => ucfirst(strtolower($s['day_of_week'])),
                'start_time' => date('g:i A', strtotime($s['start_time'])),
                'end_time' => date('g:i A', strtotime($s['end_time'])),
                'room' => $s['room']
            ];
        }, $schedule);
        
        // Format enrollment date
        $course['enrollment_date'] = date('M j, Y', strtotime($course['enrollment_date']));
    }
    
    echo json_encode($enrolledCourses);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to load enrolled courses',
        'message' => $e->getMessage()
    ]);
}
?>
