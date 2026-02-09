<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../../config/database.php';

$type   = $_GET['type'] ?? 'popular'; 
$limit  = (int)($_GET['limit'] ?? 20);
$search = trim($_GET['search'] ?? '');   

if ($limit < 1) $limit = 20;
if ($limit > 50) $limit = 50;

try {
    $database = new Database();
    $db = $database->getConnection();

    $currentUserId = (int)($_SESSION['user_id'] ?? 0);

    $orderBy = ($type === 'new')
        ? "p.created_at DESC"
        : "p.like_count DESC, p.comment_count DESC, p.created_at DESC";

    // optional WHERE when searching
    $where = "";
    if ($search !== "") {
        $where = "WHERE (
            p.title LIKE :q
            OR p.content LIKE :q
            OR CONCAT(IFNULL(u.first_name,''), ' ', IFNULL(u.last_name,'')) LIKE :q
        )";
    }

    $sql = "
        SELECT
            p.post_id,
            p.title,
            p.content,
            p.topic_id,
            t.topic_name,
            p.author_id,
            p.tags,
            p.view_count,
            p.like_count,
            p.comment_count,
            p.is_solved,
            p.created_at,
            u.first_name,
            u.last_name,
            u.profile_picture,
            (SELECT COUNT(*) FROM kb_post_likes l WHERE l.post_id = p.post_id AND l.user_id = :uid) AS user_has_liked
        FROM kb_posts p
        LEFT JOIN kb_topics t ON t.topic_id = p.topic_id
        LEFT JOIN users u ON u.user_id = p.author_id
        $where
        ORDER BY $orderBy
        LIMIT :limit
    ";

    $stmt = $db->prepare($sql);

    // bind :q only if search is used
    if ($search !== "") {
        $stmt->bindValue(':q', '%' . $search . '%', PDO::PARAM_STR);
    }

    $stmt->bindValue(':uid', $currentUserId, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    $posts = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $tags = [];
        if (!empty($row['tags'])) {
            $decoded = json_decode($row['tags'], true);
            if (is_array($decoded)) $tags = $decoded;
        }

        $authorName = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
        if ($authorName === '') $authorName = 'Unknown';

        $snippet = trim($row['content'] ?? '');
        if (mb_strlen($snippet) > 140) {
            $snippet = mb_substr($snippet, 0, 140) . '...';
        }

        $posts[] = [
            'post_id'        => (int)$row['post_id'],
            'title'          => $row['title'] ?? '',
            'snippet'        => $snippet,
            'content'        => $row['content'] ?? '',
            'topic_id'       => (int)($row['topic_id'] ?? 0),
            'topic_name'     => $row['topic_name'] ?? null,
            'author_id'      => (int)($row['author_id'] ?? 0),
            'author_name'    => $authorName,
            'tags'           => $tags,
            'view_count'     => (int)($row['view_count'] ?? 0),
            'like_count'     => (int)($row['like_count'] ?? 0),
            'user_has_liked' => (int)($row['user_has_liked'] ?? 0) > 0,
            'comment_count'  => (int)($row['comment_count'] ?? 0),
            'is_solved'      => (int)($row['is_solved'] ?? 0),
            'created_at'     => $row['created_at'] ?? null,
            'profile_picture'=> $row['profile_picture'] ?? null,
        ];
    }

    echo json_encode(['success' => true, 'posts' => $posts]);
    exit();

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error',
        'debug' => $e->getMessage()
    ]);
    exit();
}
?>
