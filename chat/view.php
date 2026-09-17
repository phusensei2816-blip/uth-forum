<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();
$user = current_user();

$otherId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
$classId = isset($_GET['class_id']) ? (int)$_GET['class_id'] : null;

$otherUser = null;
$classInfo = null;

if ($otherId) {
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$otherId]);
    $otherUser = $stmt->fetch();
    if (!$otherUser) { http_response_code(404); die('Không tìm thấy người dùng.'); }
} elseif ($classId) {
    $stmt = $pdo->prepare('SELECT * FROM classes WHERE id = ?');
    $stmt->execute([$classId]);
    $classInfo = $stmt->fetch();
    if (!$classInfo) { http_response_code(404); die('Không tìm thấy lớp học.'); }
    $isTeacher = $user['id'] == $classInfo['teacher_id'];
    if (!$isTeacher) {
        $chk = $pdo->prepare('SELECT id FROM class_members WHERE class_id=? AND user_id=?');
        $chk->execute([$classId, $user['id']]);
        if (!$chk->fetch()) { http_response_code(403); die('Bạn không phải thành viên lớp này.'); }
    }
} else {
    header('Location: index.php'); exit;
}

// Reuse the same sidebar lists as index.php
$contacts = $pdo->prepare("
    SELECT DISTINCT u.id, u.username, u.full_name, u.role
    FROM users u
    WHERE u.id != ? AND u.id IN (
        SELECT cm2.user_id FROM class_members cm1
        JOIN class_members cm2 ON cm2.class_id = cm1.class_id
        WHERE cm1.user_id = ?
        UNION
        SELECT c.teacher_id FROM classes c JOIN class_members cm ON cm.class_id=c.id WHERE cm.user_id = ?
        UNION
        SELECT cm.user_id FROM classes c JOIN class_members cm ON cm.class_id=c.id WHERE c.teacher_id = ?
    ) ORDER BY u.full_name");
$contacts->execute([$user['id'], $user['id'], $user['id'], $user['id']]);
$contacts = $contacts->fetchAll();

$groups = $pdo->prepare("SELECT c.id, c.name FROM classes c WHERE c.teacher_id = ?
    UNION SELECT c.id, c.name FROM classes c JOIN class_members cm ON cm.class_id=c.id WHERE cm.user_id = ?");
$groups->execute([$user['id'], $user['id']]);
$groups = $groups->fetchAll();

// Initial message load
if ($otherUser) {
    $msgs = $pdo->prepare("SELECT m.*, u.full_name, u.username FROM messages m JOIN users u ON u.id=m.sender_id
        WHERE (m.sender_id=? AND m.receiver_id=?) OR (m.sender_id=? AND m.receiver_id=?)
        ORDER BY m.created_at ASC LIMIT 200");
    $msgs->execute([$user['id'], $otherId, $otherId, $user['id']]);
} else {
    $msgs = $pdo->prepare("SELECT m.*, u.full_name, u.username FROM messages m JOIN users u ON u.id=m.sender_id
        WHERE m.class_id = ? ORDER BY m.created_at ASC LIMIT 200");
    $msgs->execute([$classId]);
}
$msgs = $msgs->fetchAll();
$lastId = $msgs ? end($msgs)['id'] : 0;

$pageTitle = 'Trò chuyện - UTH Forum';
$pageCss = 'chat.css';
require __DIR__ . '/../includes/header.php';
?>
<script>
  window.CSRF = <?= json_encode(csrf_token()) ?>;
  window.CURRENT_USER_ID = <?= (int)$user['id'] ?>;
  window.CHAT_TARGET = <?= json_encode($otherUser ? ['user_id' => $otherId] : ['class_id' => $classId]) ?>;
  window.LAST_ID = <?= (int)$lastId ?>;
</script>
<h2>Trò chuyện</h2>
<div class="chat-wrap">
  <div class="chat-list">
    <?php if ($groups): ?>
      <div style="padding:10px 16px;font-size:12px;color:var(--muted);font-weight:600;">NHÓM LỚP</div>
      <div class="chat-groups-list">
      <?php foreach ($groups as $g): ?>
        <div class="conv <?= $classId === (int)$g['id'] ? 'active' : '' ?>" onclick="location.href='view.php?class_id=<?= (int)$g['id'] ?>'">
          <div class="avatar-sm"><i class="fa-solid fa-people-group"></i></div><div><?= e($g['name']) ?></div>
        </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <?php if ($contacts): ?>
      <div style="padding:10px 16px;font-size:12px;color:var(--muted);font-weight:600;">LIÊN HỆ</div>
      <?php foreach ($contacts as $c): ?>
        <div class="conv <?= $otherId === (int)$c['id'] ? 'active' : '' ?>" onclick="location.href='view.php?user_id=<?= (int)$c['id'] ?>'">
          <div class="avatar-sm"><?= e(mb_strtoupper(mb_substr($c['full_name'] ?: $c['username'], 0, 1))) ?></div>
          <div><?= e($c['full_name'] ?: $c['username']) ?></div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
  <div class="chat-main">
    <div style="padding:12px 16px;border-bottom:1px solid var(--line);font-weight:600;">
      <?= $otherUser ? e($otherUser['full_name'] ?: $otherUser['username']) : e($classInfo['name']) ?>
    </div>
    <div class="chat-messages" id="chat-messages">
      <?php foreach ($msgs as $m): ?>
        <div class="msg <?= $m['sender_id'] == $user['id'] ? 'mine' : 'theirs' ?>">
          <?php if (!$otherUser): ?><strong style="display:block;font-size:11px;opacity:.8;"><?= e($m['full_name'] ?: $m['username']) ?></strong><?php endif; ?>
          <?= e($m['content']) ?>
        </div>
      <?php endforeach; ?>
    </div>
    <form class="chat-input" id="chat-form">
      <textarea id="chat-text" placeholder="Nhập tin nhắn..." required></textarea>
      <button type="submit" class="btn btn-red">Gửi</button>
    </form>
  </div>
</div>
<script src="../js/chat.js"></script>
