<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../../config/database.php';

if (!isset($_SESSION['user_id'])) {
  echo json_encode(['success' => false, 'message' => 'Not logged in.']);
  exit;
}

$userId = (int)$_SESSION['user_id'];
$role   = strtolower($_SESSION['role'] ?? '');

$commentId = (int)($_POST['comment_id'] ?? 0);
if ($commentId <= 0) {
  echo json_encode(['success' => false, 'message' => 'Invalid comment_id.']);
  exit;
}

try {
  $database = new Database();
  $db = $database->getConnection();

  $stmt = $db->prepare("SELECT author_id, post_id FROM kb_comments WHERE comment_id = ?");
  $stmt->execute([$commentId]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$row) {
    echo json_encode(['success' => false, 'message' => 'Comment not found.']);
    exit;
  }

  $isAuthor  = ((int)$row['author_id'] === $userId);
  $isManager = ($role === 'manager');

  if (!$isAuthor && !$isManager) {
    echo json_encode(['success' => false, 'message' => 'Not allowed.']);
    exit;
  }

  $del = $db->prepare("DELETE FROM kb_comments WHERE comment_id = ?");
  $del->execute([$commentId]);

// Recalculate cached counter safely (prevents drift)
    $db->prepare("
    UPDATE kb_posts p
    SET p.comment_count = (
        SELECT COUNT(*) FROM kb_comments c WHERE c.post_id = ?
    )
    WHERE p.post_id = ?
    ")->execute([(int)$row['post_id'], (int)$row['post_id']]);


  echo json_encode(['success' => true, 'message' => 'Comment deleted.']);
} catch (Throwable $e) {
  echo json_encode(['success' => false, 'message' => 'Server error deleting comment.']);
}
