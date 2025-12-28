<?php
header('Content-Type: application/json');
require_once('../../config/database.php');

$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

$managerId = isset($_GET['assigned_by']) ? intval($_GET['assigned_by']) : 0;

try {
    $sql = "SELECT
                u.user_id,
                CONCAT(u.first_name, ' ', u.last_name) AS full_name,
                u.profile_picture,
                COALESCE(COUNT(ta.task_id),0) AS total_tasks,
                COALESCE(SUM(CASE WHEN t.status = 'completed' THEN 1 ELSE 0 END),0) AS completed_tasks,
                COALESCE(SUM(CASE WHEN t.deadline < NOW() AND t.status != 'completed' THEN 1 ELSE 0 END),0) AS overdue_tasks,
                GROUP_CONCAT(DISTINCT p.project_id SEPARATOR ',') AS project_ids,
                GROUP_CONCAT(DISTINCT p.project_name SEPARATOR ', ') AS projects
            FROM users u
            LEFT JOIN task_assignments ta ON u.user_id = ta.user_id
            LEFT JOIN tasks t ON ta.task_id = t.task_id
            LEFT JOIN projects p ON t.project_id = p.project_id
            WHERE ta.assigned_by = ?
            GROUP BY u.user_id
            /*get employees who have 3 overdue tasks*/
            /*HAVING SUM(CASE WHEN t.deadline < NOW() AND t.status != 'completed' THEN 1 ELSE 0 END) >= 3*/
            ORDER BY overdue_tasks DESC";

    $stmt = $conn->prepare($sql);
    $stmt->execute([$managerId]);
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($employees);

} catch(PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
