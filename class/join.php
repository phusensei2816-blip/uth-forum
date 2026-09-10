<?php
require_once __DIR__ . '/../includes/functions.php';
require_role(['student']);
$user = current_user();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $code = strtoupper(trim($_POST['invite_code'] ?? ''));
    $stmt = $pdo->prepare('SELECT id, name FROM classes WHERE invite_code = ?');
    $stmt->execute([$code]);
    $class = $stmt->fetch();

    if (!$class) {
        $error = 'Mã mời không hợp lệ.';
    } else {
        $chk = $pdo->prepare('SELECT id FROM class_members WHERE class_id=? AND user_id=?');
        $chk->execute([$class['id'], $user['id']]);
        if ($chk->fetch()) {
            flash('info', 'Bạn đã là thành viên của lớp "' . $class['name'] . '".');
        } else {
            $pdo->prepare('INSERT INTO class_members (class_id, user_id) VALUES (?,?)')->execute([$class['id'], $user['id']]);
            flash('success', 'Đã tham gia lớp "' . $class['name'] . '".');
        }
        header('Location: view.php?id=' . $class['id']);
        exit;
    }
}

$stmt = $pdo->prepare('SELECT c.*, u.full_name AS teacher_name, COUNT(cm2.id) AS members
                        FROM classes c
                        JOIN class_members cm ON cm.class_id = c.id AND cm.user_id = ?
                        JOIN users u ON u.id = c.teacher_id
                        LEFT JOIN class_members cm2 ON cm2.class_id = c.id
                        GROUP BY c.id');
$stmt->execute([$user['id']]);
$myClasses = $stmt->fetchAll();

$pageTitle = 'Lớp học của tôi - UTH Forum';
require __DIR__ . '/../includes/header.php';
?>
<div class="row">
  <section style="flex:2;min-width:0;">
    <h2>Lớp học của tôi</h2>
    <?php if (!$myClasses): ?>
      <div class="box"><p style="color:var(--muted);">Bạn chưa tham gia lớp học nào.</p></div>
    <?php endif; ?>
    <?php foreach ($myClasses as $c): ?>
      <div class="box" style="margin-bottom:12px;">
        <h3><a href="view.php?id=<?= (int)$c['id'] ?>"><?= e($c['name']) ?></a></h3>
        <p style="color:var(--muted);font-size:14px;">GV: <?= e($c['teacher_name']) ?> · <?= (int)$c['members'] ?> thành viên</p>
      </div>
    <?php endforeach; ?>
  </section>
  <aside style="flex:1;min-width:260px;">
    <div class="card">
      <h3>Tham gia lớp bằng mã mời</h3>
      <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
      <form method="post">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <div class="form-group">
          <input type="text" name="invite_code" placeholder="Nhập mã mời (VD: A1B2C3)" style="text-transform:uppercase;" required>
        </div>
        <button type="submit" class="btn btn-teal" style="width:100%;">Tham gia</button>
      </form>
    </div>
  </aside>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
