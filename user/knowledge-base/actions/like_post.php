<?php
session_start();
header("Content-Type: application/json");

require_once __DIR__ . "/../../../config/database.php";

if (!isset($_SESSION["user_id"])) {
  echo json_encode(["success" => false, "message" => "Not logged in."]);
  exit;
}

$userId = (int)$_SESSION["user_id"];
$postId = (int)($_POST["post_id"] ?? 0);

if ($postId <= 0) {
  echo json_encode(["success" => false, "message" => "Invalid post_id."]);
  exit;
}

try {
  $database = new Database();
  $db = $database->getConnection();
  $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  $db->beginTransaction();

  // Check if user already liked this post
  $check = $db->prepare("SELECT like_id FROM kb_post_likes WHERE post_id = ? AND user_id = ?");
  $check->execute([$postId, $userId]);
  $existing = $check->fetch(PDO::FETCH_ASSOC);

  if ($existing) {
    // Unlike: remove the like
    $del = $db->prepare("DELETE FROM kb_post_likes WHERE like_id = ?");
    $del->execute([$existing['like_id']]);

    $upd = $db->prepare("UPDATE kb_posts SET like_count = GREATEST(like_count - 1, 0) WHERE post_id = ?");
    $upd->execute([$postId]);

    $liked = false;
  } else {
    // Like: insert new like
    $ins = $db->prepare("INSERT INTO kb_post_likes (post_id, user_id) VALUES (?, ?)");
    $ins->execute([$postId, $userId]);

    $upd = $db->prepare("UPDATE kb_posts SET like_count = like_count + 1 WHERE post_id = ?");
    $upd->execute([$postId]);

    $liked = true;
  }

  // Get updated count
  $stmt = $db->prepare("SELECT like_count FROM kb_posts WHERE post_id = ?");
  $stmt->execute([$postId]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);

  $db->commit();

  echo json_encode([
    "success"    => true,
    "post_id"    => $postId,
    "like_count" => (int)($row["like_count"] ?? 0),
    "liked"      => $liked
  ]);
} catch (Exception $e) {
  if (isset($db) && $db->inTransaction()) $db->rollBack();
  echo json_encode(["success" => false, "message" => "Server error."]);
}
