<?php
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json');

if (!is_logged_in()) { echo json_encode(['ok' => false]); exit; }
if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) { echo json_encode(['ok' => false]); exit; }

$user = current_user();
$content = trim($_POST['content'] ?? '');
$receiverId = !empty($_POST['user_id']) ? (int)$_POST['user_id'] : null;
$classId = !empty($_POST['class_id']) ? (int)$_POST['class_id'] : null;

if ($content === '' || (!$receiverId && !$classId)) {
    echo json_encode(['ok' => false, 'error' => 'invalid']); exit;
}

// Authorization: for group chat, confirm membership; for 1-1, no extra check needed beyond login
if ($classId) {
    $isTeacher = $pdo->prepare('SELECT id FROM classes WHERE id=? AND teacher_id=?');
    $isTeacher->execute([$classId, $user['id']]);
    if (!$isTeacher->fetch()) {
        $chk = $pdo->prepare('SELECT id FROM class_members WHERE class_id=? AND user_id=?');
        $chk->execute([$classId, $user['id']]);
        if (!$chk->fetch()) { echo json_encode(['ok' => false, 'error' => 'forbidden']); exit; }
    }
}

$ins = $pdo->prepare('INSERT INTO messages (sender_id, receiver_id, class_id, content) VALUES (?,?,?,?)');
$ins->execute([$user['id'], $receiverId, $classId, $content]);

echo json_encode([
    'ok' => true,
    'message' => [
        'id' => $pdo->lastInsertId(),
        'sender_id' => $user['id'],
        'sender_name' => $user['full_name'] ?: $user['username'],
        'content' => $content,
        'created_at' => date('c'),
    ],
]);