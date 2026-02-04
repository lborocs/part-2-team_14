<?php
header('Content-Type: application/json');
require_once('../../config/database.php');
$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    echo json_encode(['error' => 'Database connection failed!']);
    exit;
}

$managerId = isset($_GET['team_leader_id']) ? intval($_GET['team_leader_id']) : 0;

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