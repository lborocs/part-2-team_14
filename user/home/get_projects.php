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
    
    // Fetch projects for this manager
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
            AND (p.created_by = :managerId OR p.team_leader_id = :managerId)
            GROUP BY p.project_id, p.project_name, p.priority, p.deadline
            ORDER BY 
                CASE 
                    WHEN SUM(CASE WHEN t.deadline < NOW() AND t.status != 'completed' THEN 1 ELSE 0 END) > 3 THEN 1
                    WHEN SUM(CASE WHEN t.deadline < NOW() AND t.status != 'completed' THEN 1 ELSE 0 END) BETWEEN 1 AND 3 THEN 2
                    ELSE 3
                END ASC";

    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':managerId', $managerId, PDO::PARAM_INT);
    $stmt->execute();

    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($projects);

} catch(PDOException $e) {
    error_log($e->getMessage());
    echo json_encode(['error' => 'Database query error']);
}
?>

   
