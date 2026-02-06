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

$postId = (int)($_POST['post_id'] ?? 0);
if ($postId <= 0) {
  echo json_encode(['success' => false, 'message' => 'Invalid post_id.']);
  exit;
}

try {
  $database = new Database();
  $db = $database->getConnection();

  $stmt = $db->prepare("SELECT author_id FROM kb_posts WHERE post_id = ?");
  $stmt->execute([$postId]);
  $post = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$post) {
    echo json_encode(['success' => false, 'message' => 'Post not found.']);
    exit;
  }

  $isAuthor  = ((int)$post['author_id'] === $userId);
  $isManager = ($role === 'manager');

  if (!$isAuthor && !$isManager) {
    echo json_encode(['success' => false, 'message' => 'Permission denied.']);
    exit;
  }

  $db->beginTransaction();

  $stmt = $db->prepare("DELETE FROM kb_comments WHERE post_id = ?");
  $stmt->execute([$postId]);

  $stmt = $db->prepare("DELETE FROM kb_posts WHERE post_id = ?");
  $stmt->execute([$postId]);

  $db->commit();

  echo json_encode(['success' => true]);

} catch (Exception $e) {
  if ($db->inTransaction()) $db->rollBack();
  echo json_encode(['success' => false, 'message' => 'Server error deleting post.']);
}
