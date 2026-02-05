<?php
session_start();
require_once __DIR__ . "/../../config/database.php";

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit;
}

$userId = $_SESSION['user_id'];

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    $sql = "SELECT personal_task_id, task_name, deadline, is_completed 
            FROM user_personal_tasks 
            WHERE user_id = ? 
            ORDER BY is_completed ASC, deadline ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId]);
    
    $todos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($todos);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>