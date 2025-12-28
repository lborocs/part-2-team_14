<?php
header('Content-Type: application/json');
require_once('../../config/database.php');
$database = new Database();
$conn = $database->getConnection();

if (!$conn) {
    echo json_encode(['error' => 'Database connection failed!']);
    exit;
}

try {
    $sql = "SELECT 
            DISTINCT priority
            FROM projects
            WHERE priority IN ('high', 'medium')";

    $stmt = $conn->prepare($sql);
    $stmt->execute();

    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));

} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>