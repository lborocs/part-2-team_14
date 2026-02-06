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
$title   = trim($_POST['title'] ?? '');
$content = trim($_POST['content'] ?? '');

if ($postId <= 0 || $title === '' || $content === '') {
  echo json_encode(['success' => false, 'message' => 'Invalid input.']);
  exit;
}

try {
  $database = new Database();
  $db = $database->getConnection();

  // Author check
  $stmt = $db->prepare("SELECT author_id FROM kb_posts WHERE post_id = ?");
  $stmt->execute([$postId]);
  $post = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$post) {
    echo json_encode(['success' => false, 'message' => 'Post not found.']);
    exit;
  }

  if ((int)$post['author_id'] !== $userId) {
    echo json_encode(['success' => false, 'message' => 'Only the author can edit this post.']);
    exit;
  }

  // Update post
  $stmt = $db->prepare("
    UPDATE kb_posts
    SET title = ?, content = ?, updated_at = NOW()
    WHERE post_id = ?
  ");
  $stmt->execute([$title, $content, $postId]);

  // Return updated_at
  $stmt = $db->prepare("SELECT updated_at FROM kb_posts WHERE post_id = ?");
  $stmt->execute([$postId]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);

  echo json_encode([
    'success' => true,
    'updated_at' => $row['updated_at'] ?? null
  ]);

} catch (Exception $e) {
  echo json_encode(['success' => false, 'message' => 'Server error updating post.']);
}
