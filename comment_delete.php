<?php
require_once __DIR__ . '/includes/functions.php';
require_login();
$user = current_user();

$id = (int)($_GET['id'] ?? 0);
$postId = (int)($_GET['post_id'] ?? 0);

$stmt = $pdo->prepare('SELECT cm.*, p.class_id, c.teacher_id
                        FROM comments cm
                        JOIN posts p ON p.id = cm.post_id
                        LEFT JOIN classes c ON c.id = p.class_id
                        WHERE cm.id = ?');
$stmt->execute([$id]);
$comment = $stmt->fetch();
if (!$comment) { http_response_code(404); die('Không tìm thấy bình luận.'); }

$canDelete = $user['id'] == $comment['user_id']
    || $user['role'] === 'admin'
    || ($user['role'] === 'teacher' && $comment['teacher_id'] == $user['id']);

if (!$canDelete) { http_response_code(403); die('Bạn không có quyền xóa bình luận này.'); }

$pdo->prepare('DELETE FROM comments WHERE id = ?')->execute([$id]);
header('Location: post/post_view.php?id=' . $postId . '#comments');
exit;
