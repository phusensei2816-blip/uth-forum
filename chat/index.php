<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();
$user = current_user();

// People the user can message: anyone they share a class with (classmates/teacher), plus teachers can message their students
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
    )
    ORDER BY u.full_name
");
$contacts->execute([$user['id'], $user['id'], $user['id'], $user['id']]);
$contacts = $contacts->fetchAll();

// Class groups the user belongs to (member or teacher)
$groups = $pdo->prepare("
    SELECT c.id, c.name FROM classes c WHERE c.teacher_id = ?
    UNION
    SELECT c.id, c.name FROM classes c JOIN class_members cm ON cm.class_id=c.id WHERE cm.user_id = ?
");
$groups->execute([$user['id'], $user['id']]);
$groups = $groups->fetchAll();

$activeUserId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
$activeClassId = isset($_GET['class_id']) ? (int)$_GET['class_id'] : null;

$pageTitle = 'Trò chuyện - UTH Forum';
require __DIR__ . '/../includes/header.php';
?>
<script>window.CSRF = <?= json_encode(csrf_token()) ?>; window.CURRENT_USER_ID = <?= (int)$user['id'] ?>;</script>
<h2>Trò chuyện</h2>
<div class="chat-wrap">
  <div class="chat-list">
    <?php if ($groups): ?>
      <div style="padding:10px 16px;font-size:12px;color:var(--muted);font-weight:600;">NHÓM LỚP</div>
      <?php foreach ($groups as $g): ?>
        <div class="conv <?= $activeClassId === (int)$g['id'] ? 'active' : '' ?>" onclick="location.href='view.php?class_id=<?= (int)$g['id'] ?>'">
          <div class="avatar-sm"><i class="fa-solid fa-people-group"></i></div>
          <div><?= e($g['name']) ?></div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
    <?php if ($contacts): ?>
      <div style="padding:10px 16px;font-size:12px;color:var(--muted);font-weight:600;">LIÊN HỆ</div>
      <?php foreach ($contacts as $c): ?>
        <div class="conv <?= $activeUserId === (int)$c['id'] ? 'active' : '' ?>" onclick="location.href='view.php?user_id=<?= (int)$c['id'] ?>'">
          <div class="avatar-sm"><?= e(mb_strtoupper(mb_substr($c['full_name'] ?: $c['username'], 0, 1))) ?></div>
          <div>
            <div><?= e($c['full_name'] ?: $c['username']) ?></div>
            <div style="font-size:11px;color:var(--muted);"><?= e(role_label($c['role'])) ?></div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
    <?php if (!$groups && !$contacts): ?>
      <p style="padding:16px;color:var(--muted);font-size:13px;">Tham gia một lớp học để bắt đầu trò chuyện.</p>
    <?php endif; ?>
  </div>
  <div class="chat-main">
    <div style="display:flex;align-items:center;justify-content:center;height:100%;color:var(--muted);">
      Chọn một cuộc trò chuyện để bắt đầu
    </div>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
