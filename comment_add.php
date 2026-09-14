<?php
require_once __DIR__ . '/includes/functions.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST'){
    header('Location: index.php');
    exit;
}

csrf_check();

$user = current_user();
$postId = (int)($_POST['post_id'] ?? 0);
$content = trim($_POST['content'] ?? '');

// Kiểm tra bài viết có tồn tại không
$stmt = $pdo->prepare('SELECT id FROM posts WHERE id = ?');
$stmt->execute([$postId]);

if (!$stmt->fetch()) {
    http_response_code(404);
    die('Không tìm thấy bài viết.');
}

// Thêm bình luận nếu nội dung không rỗng
if ($content !== '') {
    $ins = $pdo->prepare(
        'INSERT INTO comments (post_id, user_id, content) VALUES (?, ?, ?)'
    );

    $ins->execute([$postId, $user['id'], $content]);
}

// Quay lại trang chi tiết bài viết
header('Location: post/post_view.php?id=' . $postId . '#comments');
exit;
