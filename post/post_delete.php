<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();
$user = current_user();

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM posts WHERE id = ?');
$stmt->execute([$id]);
$post = $stmt->fetch();
if (!$post) { http_response_code(404); die('Không tìm thấy bài viết.'); }

if ($post['user_id'] != $user['id'] && $user['role'] !== 'admin') {
    http_response_code(403); die('Bạn không thể xóa bài viết này.');
}

$pdo->prepare('DELETE FROM posts WHERE id = ?')->execute([$id]);
flash('success', 'Đã xóa bài viết.');
header('Location: ' . ($user['role'] === 'admin' ? 'admin/moderation.php' : 'index.php'));
exit;
