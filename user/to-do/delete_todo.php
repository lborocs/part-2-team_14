<?php
session_start();
require_once __DIR__ . "/../../config/database.php";
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$id = $data['todo_id'] ?? null;

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'ID missing']);
    exit;
}

try {
    $database = new Database();
    $pdo = $database->getConnection();
    $stmt = $pdo->prepare("DELETE FROM user_personal_tasks WHERE personal_task_id = ? AND user_id = ?");
    $stmt->execute([$id, $_SESSION['user_id']]);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}