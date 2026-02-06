<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../../config/database.php';

$postId = isset($_GET['post_id']) ? (int)$_GET['post_id'] : 0;
if ($postId <= 0) {
  http_response_code(400);
  echo json_encode(['success'=>false,'message'=>'Missing post_id']);
  exit();
}

try {
  $database = new Database();
  $db = $database->getConnection();

  $sql = "
    SELECT c.comment_id, c.content, c.post_id, c.author_id,
           c.is_edited, c.last_edited_at, c.created_at,
           u.first_name, u.last_name
    FROM kb_comments c
    LEFT JOIN users u ON u.user_id = c.author_id
    WHERE c.post_id = :post_id
    ORDER BY c.created_at ASC
  ";

  $stmt = $db->prepare($sql);
  $stmt->bindValue(':post_id', $postId, PDO::PARAM_INT);
  $stmt->execute();

  $comments = [];
  while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $authorName = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
    if ($authorName === '') $authorName = 'Unknown';

    $comments[] = [
      'comment_id' => (int)$row['comment_id'],
      'post_id' => (int)$row['post_id'],
      'content' => $row['content'] ?? '',
      'author_id' => (int)($row['author_id'] ?? 0),
      'author_name' => $authorName,
      'is_edited' => (int)($row['is_edited'] ?? 0),
      'last_edited_at' => $row['last_edited_at'] ?? null,
      'created_at' => $row['created_at'] ?? null,
    ];
  }

  echo json_encode(['success'=>true,'comments'=>$comments]);
  exit();

} catch (PDOException $e) {
  http_response_code(500);
  echo json_encode(['success'=>false,'message'=>'Database error','debug'=>$e->getMessage()]);
  exit();
}
?>
