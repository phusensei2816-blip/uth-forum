<?php
require_once __DIR__ . '/includes/functions.php';
header('Content-Type: application/json');

if (!is_logged_in()) { echo json_encode(['ok' => false, 'error' => 'not_logged_in']); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['ok' => false]); exit; }
if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
    echo json_encode(['ok' => false, 'error' => 'csrf']); exit;
}

$user = current_user();
$postId = (int)($_POST['post_id'] ?? 0);

$stmt = $pdo->prepare('SELECT id FROM likes WHERE post_id = ? AND user_id = ?');
$stmt->execute([$postId, $user['id']]);
$existing = $stmt->fetch();

if ($existing) {
    $pdo->prepare('DELETE FROM likes WHERE id = ?')->execute([$existing['id']]);
    $liked = false;
} else {
    $pdo->prepare('INSERT INTO likes (post_id, user_id) VALUES (?,?)')->execute([$postId, $user['id']]);
    $liked = true;
}

$count = $pdo->prepare('SELECT COUNT(*) FROM likes WHERE post_id = ?');
$count->execute([$postId]);
echo json_encode(['ok' => true, 'liked' => $liked, 'count' => (int)$count->fetchColumn()]);
