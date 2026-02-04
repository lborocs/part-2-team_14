<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($email) || empty($password) || empty($confirm_password)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        exit();
    }
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !str_ends_with($email, '@make-it-all.co.uk')) {
        echo json_encode(['success' => false, 'message' => 'Email must end with @make-it-all.co.uk']);
        exit();
    }
    
    // Validate password (at least 8 chars, 1 letter, 3 numbers, 1 special char)
    if (!preg_match('/^(?=.*[A-Za-z])(?=.*[^A-Za-z0-9])(?=(?:.*\d){3,}).{8,}$/', $password)) {
        echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters with 1 letter, 3 numbers, and 1 special character']);
        exit();
    }

    // Confirm password match
    if ($password !== $confirm_password) {
        echo json_encode(['success' => false, 'message' => 'Passwords must match']);
        exit();
    }
    
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        // Check if email exists in database
        $query = "SELECT user_id, is_registered FROM users WHERE email = :email";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'Email not found in employee database. Contact HR.']);
            exit();
        }
        
        if ($user['is_registered']) {
            echo json_encode(['success' => false, 'message' => 'This email is already registered. Please login.']);
            exit();
        }
        
        // Hash password
        $password_hash = password_hash($password, PASSWORD_BCRYPT);
        
        // Update user with password and set registered flag
        $updateQuery = "UPDATE users 
                        SET password_hash = :password_hash, 
                            is_registered = 1 
                        WHERE user_id = :user_id";
        
        $updateStmt = $db->prepare($updateQuery);
        $updateStmt->bindParam(':password_hash', $password_hash);
        $updateStmt->bindParam(':user_id', $user['user_id']);
        
        if ($updateStmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Account created successfully! Please login.'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to create account']);
        }
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error occurred']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>