<?php
/**
 * User Info API
 * Returns current user information
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
    
    $stmt = $pdo->prepare("
        SELECT 
            id, user_type, username, email, first_name, last_name, 
            student_id, lecturer_id, phone, created_at
        FROM users 
        WHERE id = ? AND is_active = 1
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if (!$user) {
        throw new Exception('User not found');
    }
    
    // Remove sensitive information and format response
    unset($user['id']); // Don't expose internal ID
    $user['created_at'] = date('M j, Y', strtotime($user['created_at']));
    
    echo json_encode($user);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to load user information',
        'message' => $e->getMessage()
    ]);
}
?>
