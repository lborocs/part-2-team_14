<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Email and password are required']);
        exit();
    }
    
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        // Check if user exists and is registered
        $query = "SELECT user_id, email, first_name, last_name, role, password_hash, is_registered 
                  FROM users 
                  WHERE email = :email AND is_active = 1";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'Invalid email']);
            exit();
        }
        
        // Check if user is registered
        if (!$user['is_registered']) {
            echo json_encode(['success' => false, 'message' => 'Account not registered. Please sign up first.']);
            exit();
        }
        
        // Verify password
        if (!password_verify($password, $user['password_hash'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid password']);
            exit();
        }
        
        // Update last login
        $updateQuery = "UPDATE users SET last_login = NOW() WHERE user_id = :user_id";
        $updateStmt = $db->prepare($updateQuery);
        $updateStmt->bindParam(':user_id', $user['user_id']);
        $updateStmt->execute();
        
        // Set session variables
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['name'] = $user['first_name'] . ' ' . $user['last_name'];
        $_SESSION['role'] = $user['role'];
        
        // Determine redirect based on role
        $redirect = ($user['role'] === 'manager' || $user['role'] === 'team_leader')
            ? 'user/home/home.php?user=' . urlencode($user['email'])
            : 'user/project/projects-overview.php';
        
        echo json_encode([
            'success' => true,
            'message' => 'Login successful',
            'redirect' => $redirect
        ]);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error occurred']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
