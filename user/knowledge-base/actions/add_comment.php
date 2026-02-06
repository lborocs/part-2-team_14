<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../../config/database.php';

if (!isset($_SESSION['user_id'])) {
  echo json_encode(['success' => false, 'message' => 'Not logged in.']);
  exit;
}

$userId = (int)$_SESSION['user_id'];

$postId  = (int)($_POST['post_id'] ?? 0);
$content = trim($_POST['content'] ?? '');

if ($postId <= 0) {
  echo json_encode(['success' => false, 'message' => 'Invalid post_id.']);
  exit;
}
if ($content === '') {
  echo json_encode(['success' => false, 'message' => 'Reply cannot be empty.']);
  exit;
}

try {
  $database = new Database();
  $db = $database->getConnection();

  // Make sure post exists
  $check = $db->prepare("SELECT post_id FROM kb_posts WHERE post_id = ?");
  $check->execute([$postId]);
  if (!$check->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Post not found.']);
    exit;
  }

  // Insert comment
  $stmt = $db->prepare("
    INSERT INTO kb_comments (post_id, author_id, content, created_at)
    VALUES (?, ?, ?, NOW())
  ");
  $stmt->execute([$postId, $userId, $content]);

  // Recalculate cached counter safely
    $db->prepare("
    UPDATE kb_posts p
    SET p.comment_count = (
        SELECT COUNT(*) FROM kb_comments c WHERE c.post_id = ?
    )
    WHERE p.post_id = ?
    ")->execute([$postId, $postId]);


  echo json_encode([
    'success' => true,
    'message' => 'Reply added.',
    'comment_id' => $db->lastInsertId()
  ]);
} catch (Throwable $e) {
  echo json_encode(['success' => false, 'message' => 'Server error adding reply.']);
}
