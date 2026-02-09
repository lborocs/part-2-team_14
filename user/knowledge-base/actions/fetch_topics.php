<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    // WHERE is_public = 1
    $sql = "
        SELECT
            topic_id,
            topic_name,
            description,
            icon,
            is_public,
            restricted_to_role
        FROM kb_topics
        ORDER BY (icon IS NOT NULL AND icon != '') DESC, topic_name ASC
    ";


    $stmt = $db->prepare($sql);
    $stmt->execute();

    $topics = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'topics' => $topics]);
    exit();

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error',
        'debug' => $e->getMessage()
    ]);
    exit();
}
?>