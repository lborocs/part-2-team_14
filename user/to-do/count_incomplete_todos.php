<?php
session_start();
require_once __DIR__ . "/../../config/database.php";

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['count' => 0]);
    exit;
}

$userId = $_SESSION['user_id'];

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    $sql = "SELECT COUNT(*) as count
            FROM user_personal_tasks
            WHERE user_id = ? AND is_completed = 0";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId]);
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode(['count' => $result['count']]);
} catch (PDOException $e) {
    echo json_encode(['count' => 0]);
}
?>