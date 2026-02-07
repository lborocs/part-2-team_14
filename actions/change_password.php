<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$currentPassword = $input['current_password'] ?? '';
$newPassword     = $input['new_password'] ?? '';
$confirmPassword = $input['confirm_password'] ?? '';

if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit();
}

if (!preg_match('/^(?=.*[A-Za-z])(?=.*[^A-Za-z0-9])(?=(?:.*\d){3,}).{8,}$/', $newPassword)) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters with 1 letter, 3 numbers, and 1 special character']);
    exit();
}

if ($newPassword !== $confirmPassword) {
    echo json_encode(['success' => false, 'message' => 'New passwords do not match']);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // Get current password hash
    $stmt = $db->prepare("SELECT password_hash FROM users WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
        echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
        exit;
    }

    // Check the new password is different
    if (password_verify($newPassword, $user['password_hash'])) {
    echo json_encode([
        'success' => false,
        'message' => 'New password must be different from your current password'
    ]);
    exit;
}

    // Hash new password
    $newHash = password_hash($newPassword, PASSWORD_BCRYPT);

    // Update password
    $update = $db->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
    if ($update->execute([$newHash, $_SESSION['user_id']])) {
        echo json_encode(['success' => true, 'message' => 'Password updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update password']);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>