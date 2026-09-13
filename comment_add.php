<?php
require_once __DIR__ . '/includes/functions.php';
require_login();

$isAjax = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'error' => 'method']);
        exit;
    }
    header('Location: index.php');
    exit;
}

try {
    csrf_check();
} catch (Throwable $e) {
    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'csrf']);
        exit;
    }
    throw $e;
}

$user = current_user();
$postId = (int)($_POST['post_id'] ?? 0);
$content = trim($_POST['content'] ?? '');

$stmt = $pdo->prepare('SELECT id, status FROM posts WHERE id = ?');
$stmt->execute([$postId]);
$post = $stmt->fetch();

if (!$post) {
    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'not_found']);
        exit;
    }
    http_response_code(404);
    die('Không tìm thấy bài viết.');
}

if ($content === '') {
    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'error' => 'empty']);
        exit;
    }
    header('Location: post_view.php?id=' . $postId . '#comments');
    exit;
}

$ins = $pdo->prepare('INSERT INTO comments (post_id, user_id, content) VALUES (?,?,?)');
$ins->execute([$postId, $user['id'], $content]);
$commentId = (int)$pdo->lastInsertId();

if ($isAjax) {
    $q = $pdo->prepare('SELECT cm.id, cm.user_id, cm.content, cm.created_at,
                               u.username, u.full_name
                        FROM comments cm
                        JOIN users u ON u.id = cm.user_id
                        WHERE cm.id = ?');
    $q->execute([$commentId]);
    $comment = $q->fetch();

    $count = $pdo->prepare('SELECT COUNT(*) FROM comments WHERE post_id = ?');
    $count->execute([$postId]);

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'ok' => true,
        'comment' => $comment,
        'count' => (int)$count->fetchColumn()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

header('Location: post_view.php?id=' . $postId . '#comments');
exit;
