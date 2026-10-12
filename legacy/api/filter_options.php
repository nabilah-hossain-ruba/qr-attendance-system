<?php
/**
 * Filter Options API
 * Returns available filter options for course search
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
    
    // Get distinct departments
    $deptStmt = $pdo->query("
        SELECT DISTINCT department 
        FROM courses 
        WHERE department IS NOT NULL 
        AND department != '' 
        AND is_active = 1
        ORDER BY department
    ");
    $departments = $deptStmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Get distinct semesters from course sections
    $semStmt = $pdo->query("
        SELECT DISTINCT semester 
        FROM course_sections 
        WHERE semester IS NOT NULL 
        AND semester != '' 
        AND is_active = 1
        ORDER BY semester
    ");
    $semesters = $semStmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Get distinct academic years
    $yearStmt = $pdo->query("
        SELECT DISTINCT academic_year 
        FROM course_sections 
        WHERE academic_year IS NOT NULL 
        AND academic_year != '' 
        AND is_active = 1
        ORDER BY academic_year DESC
    ");
    $academicYears = $yearStmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Get credit hour options
    $creditStmt = $pdo->query("
        SELECT DISTINCT credits 
        FROM courses 
        WHERE credits IS NOT NULL 
        AND is_active = 1
        ORDER BY credits
    ");
    $credits = $creditStmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo json_encode([
        'departments' => $departments,
        'semesters' => $semesters,
        'academic_years' => $academicYears,
        'credits' => array_map('intval', $credits)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to load filter options',
        'message' => $e->getMessage()
    ]);
}
?>
