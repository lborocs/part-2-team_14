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

  $currentUserId = (int)($_SESSION['user_id'] ?? 0);

  $sql = "
    SELECT
      p.post_id, p.title, p.content, p.topic_id, p.author_id, p.tags,
      p.view_count, p.like_count, p.comment_count, p.is_solved,
      p.created_at, p.updated_at,
      t.topic_name,
      u.first_name, u.last_name, u.profile_picture,
      (SELECT COUNT(*) FROM kb_post_likes l WHERE l.post_id = p.post_id AND l.user_id = :uid) AS user_has_liked
    FROM kb_posts p
    LEFT JOIN kb_topics t ON t.topic_id = p.topic_id
    LEFT JOIN users u ON u.user_id = p.author_id
    WHERE p.post_id = :post_id
    LIMIT 1
  ";

  $stmt = $db->prepare($sql);
  $stmt->bindValue(':post_id', $postId, PDO::PARAM_INT);
  $stmt->bindValue(':uid', $currentUserId, PDO::PARAM_INT);
  $stmt->execute();

  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$row) {
    http_response_code(404);
    echo json_encode(['success'=>false,'message'=>'Post not found']);
    exit();
  }

  $tags = [];
  if (!empty($row['tags'])) {
    $decoded = json_decode($row['tags'], true);
    if (is_array($decoded)) $tags = $decoded;
  }

  $authorName = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
  if ($authorName === '') $authorName = 'Unknown';

  echo json_encode([
    'success' => true,
    'post' => [
      'post_id' => (int)$row['post_id'],
      'title' => $row['title'] ?? '',
      'content' => $row['content'] ?? '',
      'topic_id' => (int)($row['topic_id'] ?? 0),
      'topic_name' => $row['topic_name'] ?? null,
      'author_id' => (int)($row['author_id'] ?? 0),
      'author_name' => $authorName,
      'tags' => $tags,
      'view_count' => (int)($row['view_count'] ?? 0),
      'like_count' => (int)($row['like_count'] ?? 0),
      'user_has_liked' => (int)($row['user_has_liked'] ?? 0) > 0,
      'comment_count' => (int)($row['comment_count'] ?? 0),
      'is_solved' => (int)($row['is_solved'] ?? 0),
      'created_at' => $row['created_at'] ?? null,
      'updated_at' => $row['updated_at'] ?? null,
      'profile_picture' => $row['profile_picture'] ?? null,
    ]
  ]);
  exit();

} catch (PDOException $e) {
  http_response_code(500);
  echo json_encode(['success'=>false,'message'=>'Database error','debug'=>$e->getMessage()]);
  exit();
}
?>
