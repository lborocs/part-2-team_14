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

$commentId = (int)($_POST['comment_id'] ?? 0);
$content   = trim($_POST['content'] ?? '');

if ($commentId <= 0) {
  echo json_encode(['success' => false, 'message' => 'Invalid comment_id']);
  exit();
}

if ($content === '') {
  echo json_encode(['success' => false, 'message' => 'Content cannot be empty']);
  exit();
}

try {
  $database = new Database();
  $db = $database->getConnection();

  // Load comment author
  $stmt = $db->prepare("SELECT author_id FROM kb_comments WHERE comment_id = ?");
  $stmt->execute([$commentId]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$row) {
    echo json_encode(['success' => false, 'message' => 'Comment not found']);
    exit();
  }

  // Author-only edit
  $currentUserId = (int)$_SESSION['user_id'];
  if ((int)$row['author_id'] !== $currentUserId) {
    echo json_encode(['success' => false, 'message' => 'Not allowed']);
    exit();
  }

  //  Update + timestamp
  $upd = $db->prepare("
    UPDATE kb_comments
    SET content = :content,
        is_edited = 1,
        last_edited_at = NOW()
    WHERE comment_id = :id
  ");
  $upd->bindValue(':content', $content, PDO::PARAM_STR);
  $upd->bindValue(':id', $commentId, PDO::PARAM_INT);
  $upd->execute();

  // Return the timestamp so UI can update
  $ts = $db->query("SELECT last_edited_at FROM kb_comments WHERE comment_id = " . (int)$commentId)
           ->fetchColumn();

  echo json_encode(['success' => true, 'last_edited_at' => $ts]);
  exit();

} catch (PDOException $e) {
  http_response_code(500);
  echo json_encode(['success' => false, 'message' => 'Database error', 'debug' => $e->getMessage()]);
  exit();
}
?>
