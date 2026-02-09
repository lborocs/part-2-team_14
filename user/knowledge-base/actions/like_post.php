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

  // Try to insert a like record. If it already exists, rowCount will be 0 (or it will throw duplicate key).
  $insert = $db->prepare("INSERT IGNORE INTO kb_post_likes (post_id, user_id) VALUES (?, ?)");
  $insert->execute([$postId, $userId]);

  $likedNow = ($insert->rowCount() === 1);

  // Only increment if they liked for the first time
  if ($likedNow) {
    $upd = $db->prepare("UPDATE kb_posts SET view_count = view_count + 1 WHERE post_id = ?");
    $upd->execute([$postId]);
  }

  // Get updated count + whether this user has liked it
  $stmt = $db->prepare("SELECT view_count FROM kb_posts WHERE post_id = ?");
  $stmt->execute([$postId]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);

  $db->commit();

  echo json_encode([
    "success" => true,
    "post_id" => $postId,
    "view_count" => (int)($row["view_count"] ?? 0),
    "liked_now" => $likedNow
  ]);
} catch (Exception $e) {
  if ($db && $db->inTransaction()) $db->rollBack();
  echo json_encode(["success" => false, "message" => "Server error."]);
}
