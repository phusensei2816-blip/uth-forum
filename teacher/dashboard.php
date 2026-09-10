<?php
require_once __DIR__ . '/../includes/functions.php';
require_role(['teacher']);
$user = current_user();

$stmt = $pdo->prepare('SELECT c.*, COUNT(cm.id) AS members
                        FROM classes c LEFT JOIN class_members cm ON cm.class_id = c.id
                        WHERE c.teacher_id = ? GROUP BY c.id ORDER BY c.created_at DESC');
$stmt->execute([$user['id']]);
$classes = $stmt->fetchAll();

$pageTitle = 'Lớp học của tôi - UTH Forum';
require __DIR__ . '/../includes/header.php';
?>
<div class="row" style="justify-content:space-between;align-items:center;">
  <h2>Lớp học của tôi</h2>
  <a href="../class/create.php" class="btn btn-red">+ Tạo lớp mới</a>
</div>
<div class="row">
  <?php if (!$classes): ?>
    <div class="box"><p style="color:var(--muted);">Bạn chưa tạo lớp học nào.</p></div>
  <?php endif; ?>
  <?php foreach ($classes as $c): ?>
    <div class="box" style="flex:1;min-width:260px;">
      <h3><a href="../class/view.php?id=<?= (int)$c['id'] ?>"><?= e($c['name']) ?></a></h3>
      <p style="color:var(--muted);font-size:14px;"><?= (int)$c['members'] ?> thành viên</p>
      <p style="font-size:13px;">Mã mời: <strong><?= e($c['invite_code']) ?></strong></p>
    </div>
  <?php endforeach; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
