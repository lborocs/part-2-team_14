<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$topic_name   = trim($_POST['topic_name'] ?? '');
$description  = trim($_POST['description'] ?? '');
$icon         = trim($_POST['icon'] ?? 'tag'); // default feather icon
$is_public    = isset($_POST['is_public']) ? (int)$_POST['is_public'] : 1;
$restricted   = trim($_POST['restricted_to_role'] ?? 'all'); // 'all' or role name

if ($topic_name === '') {
    echo json_encode(['success' => false, 'message' => 'Topic name is required']);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // prevent duplicates (case-insensitive)
    $check = $db->prepare("SELECT topic_id FROM kb_topics WHERE LOWER(topic_name) = LOWER(:name) LIMIT 1");
    $check->bindValue(':name', $topic_name, PDO::PARAM_STR);
    $check->execute();

    if ($check->fetch()) {
        echo json_encode(['success' => false, 'message' => 'That topic already exists']);
        exit();
    }

    $sql = "
        INSERT INTO kb_topics (topic_name, description, icon, created_by, is_public, restricted_to_role)
        VALUES (:topic_name, :description, :icon, :created_by, :is_public, :restricted_to_role)
    ";
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':topic_name', $topic_name, PDO::PARAM_STR);
    $stmt->bindValue(':description', $description, PDO::PARAM_STR);
    $stmt->bindValue(':icon', $icon, PDO::PARAM_STR);
    $stmt->bindValue(':created_by', (int)$_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->bindValue(':is_public', $is_public, PDO::PARAM_INT);
    $stmt->bindValue(':restricted_to_role', $restricted, PDO::PARAM_STR);
    $stmt->execute();

    echo json_encode(['success' => true, 'topic_id' => (int)$db->lastInsertId()]);
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