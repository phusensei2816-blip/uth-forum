<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role(['student']);

$user = current_user();
$errors = [];
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $code = strtoupper(trim($_POST['invite_code'] ?? ''));

    if ($code === '') {
        $errors[] = 'Vui lòng nhập mã lớp.';
    } else {
        $stmt = $pdo->prepare('SELECT id, name FROM classes WHERE invite_code = ?');
        $stmt->execute([$code]);
        $class = $stmt->fetch();

        if (!$class) {
            $errors[] = 'Mã lớp không hợp lệ.';
        } else {
            $check = $pdo->prepare('SELECT id FROM class_members WHERE class_id = ? AND user_id = ?');
            $check->execute([$class['id'], $user['id']]);

            if ($check->fetch()) {
                $errors[] = 'Bạn đã tham gia lớp này rồi.';
            } else {
                $insert = $pdo->prepare('INSERT INTO class_members (class_id, user_id) VALUES (?, ?)');
                $insert->execute([$class['id'], $user['id']]);
                $success = 'Đã tham gia lớp "' . $class['name'] . '" thành công.';
            }
        }
    }
}

$stmt = $pdo->prepare(
    'SELECT c.* FROM classes c
     JOIN class_members cm ON cm.class_id = c.id
     WHERE cm.user_id = ?
     ORDER BY cm.joined_at DESC'
);
$stmt->execute([$user['id']]);
$myClasses = $stmt->fetchAll();

$pageTitle = 'Lớp của tôi';
include __DIR__ . '/../includes/header.php';
?>

<div class="form-card">
  <h1>Tham gia lớp học</h1>

  <?php foreach ($errors as $err): ?>
    <div class="alert alert-error"><?= e($err) ?></div>
  <?php endforeach; ?>
  <?php if ($success): ?>
    <div class="alert alert-success"><?= e($success) ?></div>
  <?php endif; ?>

  <form method="post" class="join-form">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <div class="form-group">
      <label for="invite_code">Mã lớp (do giảng viên cung cấp)</label>
      <input type="text" id="invite_code" name="invite_code" placeholder="VD: A1B2C3" required>
    </div>
    <button type="submit" class="btn btn-red">Tham gia</button>
  </form>
</div>

<div class="page-header">
  <h1>Lớp đã tham gia</h1>
</div>

<?php if (empty($myClasses)): ?>
  <div class="empty-state">
    <h3>Bạn chưa tham gia lớp nào</h3>
    <p>Nhập mã lớp ở trên để bắt đầu.</p>
  </div>
<?php else: ?>
  <div class="class-grid">
    <?php foreach ($myClasses as $class): ?>
      <a href="<?= e(BASE_URL) ?>/class/view.php?id=<?= (int)$class['id'] ?>" class="class-card">
        <h3><?= e($class['name']) ?></h3>
        <p><?= e($class['description']) ?></p>
      </a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>