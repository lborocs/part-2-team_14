<?php
header('Content-Type: application/json');
session_start();
require_once('../../config/database.php');
$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    echo json_encode(['error' => 'Database connection failed!']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$managerId = (int) $_SESSION['user_id'];

try {
    $sql = "SELECT *
            FROM projects
            WHERE team_leader_id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->execute([$managerId]);

    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));

} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
