<?php
header('Content-Type: application/json');
session_start();
require_once('../../config/database.php');

$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$managerId = (int) $_SESSION['user_id'];

try {
    $sql = "SELECT
                u.user_id,
                CONCAT(u.first_name, ' ', u.last_name) AS full_name,
                u.profile_picture,
                COALESCE(COUNT(DISTINCT ta.task_id),0) AS total_tasks,
                COALESCE(SUM(CASE WHEN t.status = 'completed' THEN 1 ELSE 0 END),0) AS completed_tasks,
                COALESCE(SUM(CASE WHEN t.deadline < NOW() AND t.status != 'completed' THEN 1 ELSE 0 END),0) AS overdue_tasks,
                GROUP_CONCAT(DISTINCT p.project_id SEPARATOR ',') AS project_ids,
                GROUP_CONCAT(DISTINCT p.project_name SEPARATOR ', ') AS projects
            FROM projects p
            INNER JOIN tasks t ON p.project_id = t.project_id
            INNER JOIN task_assignments ta ON ta.task_id = t.task_id
            INNER JOIN users u ON u.user_id = ta.user_id
            WHERE (p.created_by = :managerId OR p.team_leader_id = :managerId)
            GROUP BY u.user_id
            HAVING SUM(CASE WHEN t.deadline < NOW() AND t.status != 'completed' THEN 1 ELSE 0 END) >= 3
            ORDER BY overdue_tasks DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':managerId', $managerId, PDO::PARAM_INT);
    $stmt->execute();
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($employees);

} catch(PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
