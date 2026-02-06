<?php
session_start();
require_once __DIR__ . "/../../config/database.php";

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$todoId = $data['todo_id'] ?? 0;
$isCompleted = $data['is_completed'] ?? 0;
$userId = $_SESSION['user_id'];

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Verify user owns this todo
    $checkSql = "SELECT personal_task_id FROM user_personal_tasks WHERE personal_task_id = ? AND user_id = ?";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute([$todoId, $userId]);

    if ($checkStmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'Todo not found or unauthorized']);
        exit;
    }

    // Update the todo
    $updateSql = "UPDATE user_personal_tasks
                  SET is_completed = :is_completed
                  WHERE personal_task_id = :todo_id";
    $updateStmt = $pdo->prepare($updateSql);
    $updateStmt->execute([
        ':is_completed' => $isCompleted,
        ':todo_id' => $todoId
    ]);
    
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>