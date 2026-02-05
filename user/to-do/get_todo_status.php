<?php
session_start();
require_once __DIR__ . "/../../config/database.php";

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$todoId = $_GET['todo_id'] ?? 0;
$userId = $_SESSION['user_id'];

try {
    $sql = "SELECT personal_task_id, task_name, is_completed 
            FROM personal_tasks 
            WHERE personal_task_id = ? AND user_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$todoId, $userId]);
    
    $todo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($todo) {
        echo json_encode($todo);
    } else {
        echo json_encode(['error' => 'Todo not found']);
    }
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>