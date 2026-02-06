<?php
ini_set('display_errors', 0);
error_reporting(0);

session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../../config/database.php';

if (!isset($_SESSION['user_id'])) {
  echo json_encode(['success' => false, 'message' => 'Not logged in.']);
  exit;
}

$role = strtolower($_SESSION['role'] ?? '');
if ($role !== 'manager') {
  echo json_encode(['success' => false, 'message' => 'Not allowed.']);
  exit;
}

$postId = (int)($_POST['post_id'] ?? 0);
if ($postId <= 0) {
  echo json_encode(['success' => false, 'message' => 'Invalid post_id.']);
  exit;
}

try {
  $database = new Database();
  $db = $database->getConnection();

  // Ensure post exists
  $check = $db->prepare("SELECT post_id, is_solved FROM kb_posts WHERE post_id = ?");
  $check->execute([$postId]);
  $row = $check->fetch(PDO::FETCH_ASSOC);

  if (!$row) {
    echo json_encode(['success' => false, 'message' => 'Post not found.']);
    exit;
  }

  // Mark solved
  $upd = $db->prepare("UPDATE kb_posts SET is_solved = 1 WHERE post_id = ?");
  $upd->execute([$postId]);

  echo json_encode([
    'success' => true,
    'message' => 'Post marked as solved.',
    'post_id' => $postId,
    'is_solved' => 1
  ]);
  exit;

} catch (Throwable $e) {
  echo json_encode(['success' => false, 'message' => 'Server error marking solved.']);
  exit;
}
