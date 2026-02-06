<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$title    = trim($_POST['title'] ?? '');
$content  = trim($_POST['content'] ?? '');
$topic_id = (int)($_POST['topic_id'] ?? 0);

$tagsRaw = trim($_POST['tags'] ?? '');
$tagsArr = [];

if ($tagsRaw !== '') {
    $maybeJson = json_decode($tagsRaw, true);
    if (is_array($maybeJson)) {
        $tagsArr = $maybeJson;
    } else {
        $parts = array_map('trim', explode(',', $tagsRaw));
        $tagsArr = array_values(array_filter($parts, fn($t) => $t !== ''));
    }
}

if ($title === '' || $content === '' || $topic_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Title, topic, and details are required']);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();

    $sql = "
        INSERT INTO kb_posts
            (title, content, topic_id, author_id, tags, view_count, comment_count, is_solved, created_at, updated_at)
        VALUES
            (:title, :content, :topic_id, :author_id, :tags, 0, 0, 0, NOW(), NOW())
    ";

    $stmt = $db->prepare($sql);
    $stmt->bindValue(':title', $title, PDO::PARAM_STR);
    $stmt->bindValue(':content', $content, PDO::PARAM_STR);
    $stmt->bindValue(':topic_id', $topic_id, PDO::PARAM_INT);
    $stmt->bindValue(':author_id', (int)$_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->bindValue(':tags', json_encode($tagsArr), PDO::PARAM_STR);
    $stmt->execute();

    echo json_encode(['success' => true, 'post_id' => (int)$db->lastInsertId()]);
    exit();

} catch (PDOException $e) {
    http_response_code(500);

    // For debugging only.
    echo json_encode([
        'success' => false,
        'message' => 'Database error',
        'debug' => $e->getMessage()
    ]);
    exit();
}
?>