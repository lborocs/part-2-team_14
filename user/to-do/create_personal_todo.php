<?php
session_start();
require_once __DIR__ . "/../../config/database.php";

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$taskName = $data['task_name'] ?? '';
$deadline = $data['deadline'] ?? null;
$userId = $_SESSION['user_id'];

if (empty($taskName)) {
    echo json_encode(['success' => false, 'message' => 'Task name is required']);
    exit;
}

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    $sql = "INSERT INTO user_personal_tasks (task_name, user_id, deadline, is_completed) 
            VALUES (?, ?, ?, 0)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$taskName, $userId, $deadline]);
    
    echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>