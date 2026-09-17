<?php
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json');

if (!is_logged_in()) { echo json_encode(['ok' => false]); exit; }
$user = current_user();

$lastId = (int)($_GET['last_id'] ?? 0);
$receiverId = !empty($_GET['user_id']) ? (int)$_GET['user_id'] : null;
$classId = !empty($_GET['class_id']) ? (int)$_GET['class_id'] : null;

if ($receiverId) {
    $stmt = $pdo->prepare("SELECT m.*, u.full_name, u.username FROM messages m JOIN users u ON u.id=m.sender_id
        WHERE m.id > ? AND ((m.sender_id=? AND m.receiver_id=?) OR (m.sender_id=? AND m.receiver_id=?))
        ORDER BY m.id ASC");
    $stmt->execute([$lastId, $user['id'], $receiverId, $receiverId, $user['id']]);
} elseif ($classId) {
    $stmt = $pdo->prepare("SELECT m.*, u.full_name, u.username FROM messages m JOIN users u ON u.id=m.sender_id
        WHERE m.id > ? AND m.class_id = ? ORDER BY m.id ASC");
    $stmt->execute([$lastId, $classId]);
} else {
    echo json_encode(['ok' => false]); exit;
}

$rows = $stmt->fetchAll();
$out = array_map(fn($m) => [
    'id' => $m['id'],
    'sender_id' => $m['sender_id'],
    'sender_name' => $m['full_name'] ?: $m['username'],
    'content' => $m['content'],
    'created_at' => $m['created_at'],
], $rows);

echo json_encode(['ok' => true, 'messages' => $out]);