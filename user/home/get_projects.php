<?php
header('Content-Type: application/json');
require_once('../../config/database.php');
$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    echo json_encode(['error' => 'Database connection failed!']);
    exit;
}

$managerId = isset($_GET['created_by']) ? intval($_GET['created_by']) : 0;

try {
    $sql = "SELECT 
                p.project_id,
                p.project_name,
                p.priority,
                p.deadline,
                COUNT(t.task_id) AS total_tasks,
                SUM(CASE WHEN t.status = 'completed' THEN 1 ELSE 0 END) AS completed_tasks,
                SUM(CASE WHEN t.deadline < NOW() AND t.status != 'completed' THEN 1 ELSE 0 END) AS overdue_tasks,
                CASE
                    WHEN SUM(CASE WHEN t.deadline < NOW() AND t.status != 'completed' THEN 1 ELSE 0 END) > 3 THEN 'under_resourced'
                    WHEN SUM(CASE WHEN t.deadline < NOW() AND t.status != 'completed' THEN 1 ELSE 0 END) BETWEEN 1 AND 3 THEN 'tight'
                    ELSE 'sufficient'
                END AS resource_level
            FROM projects p
            LEFT JOIN tasks t ON p.project_id = t.project_id
            WHERE p.status = 'active'
            AND p.priority IN ('medium', 'high')
            AND p.created_by = :managerId
            GROUP BY p.project_id, p.project_name, p.priority, p.deadline
            ORDER BY resource_level DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':managerId', $managerId, PDO::PARAM_INT);
    $stmt->execute();

    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($projects);

} catch(PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
   