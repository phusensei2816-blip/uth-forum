<?php
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$postId = (int)($_GET['post_id'] ?? 0);

if ($postId <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'invalid_post']);
    exit;
}

$stmt = $pdo->prepare(
    'SELECT p.id, p.status, p.user_id, c.teacher_id
     FROM posts p
     LEFT JOIN classes c ON c.id = p.class_id
     WHERE p.id = ?'
);
$stmt->execute([$postId]);
$post = $stmt->fetch();

if (!$post) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'not_found']);
    exit;
}

$user = current_user();
$isOwner = $user && (int)$user['id'] === (int)$post['user_id'];
$isClassTeacher = $user && $post['teacher_id'] && (int)$user['id'] === (int)$post['teacher_id'];
$isAdmin = $user && ($user['role'] ?? '') === 'admin';

if ($post['status'] !== 'approved' && !$isOwner && !$isAdmin && !$isClassTeacher) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'forbidden']);
    exit;
}

// Like count + current user's Like state.
$likeStmt = $pdo->prepare('SELECT COUNT(*) FROM likes WHERE post_id = ?');
$likeStmt->execute([$postId]);
$likeCount = (int)$likeStmt->fetchColumn();

$liked = false;
if ($user) {
    $likedStmt = $pdo->prepare(
        'SELECT 1 FROM likes WHERE post_id = ? AND user_id = ? LIMIT 1'
    );
    $likedStmt->execute([$postId, $user['id']]);
    $liked = (bool)$likedStmt->fetchColumn();
}

// Always return the current comment list (not only new comments).
// This lets other browsers see both new comments and deleted comments.
$commentStmt = $pdo->prepare(
    'SELECT cm.id, cm.user_id, cm.content, cm.created_at,
            u.username, u.full_name, u.role
     FROM comments cm
     JOIN users u ON u.id = cm.user_id
     WHERE cm.post_id = ?
     ORDER BY cm.id ASC
     LIMIT 200'
);
$commentStmt->execute([$postId]);
$rows = $commentStmt->fetchAll();

$comments = [];
foreach ($rows as $c) {
    $canDelete = $user && (
        (int)$user['id'] === (int)$c['user_id'] ||
        $isClassTeacher ||
        $isAdmin
    );

    $c['can_delete'] = $canDelete;
    $comments[] = $c;
}

$commentCount = count($comments);

// A small version number helps the browser know that the response is fresh.
$latestCommentId = $commentCount ? (int)$comments[count($comments) - 1]['id'] : 0;

// Make the JSON deterministic so the browser can cheaply compare responses.
$version = md5(json_encode([
    'like_count' => $likeCount,
    'liked' => $liked,
    'comments' => $comments
]));

echo json_encode([
    'ok' => true,
    'version' => $version,
    'like_count' => $likeCount,
    'liked' => $liked,
    'comment_count' => $commentCount,
    'latest_comment_id' => $latestCommentId,
    'comments' => $comments
], JSON_UNESCAPED_UNICODE);
exit;
