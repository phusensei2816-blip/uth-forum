<?php
require_once __DIR__ . '/../includes/functions.php';
require_role(['teacher']);
$user = current_user();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $name = trim($_POST['name'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    if ($name === '') $errors[] = 'Vui lòng nhập tên lớp học.';

    if (!$errors) {
        do {
            $code = generate_invite_code();
            $chk = $pdo->prepare('SELECT id FROM classes WHERE invite_code = ?');
            $chk->execute([$code]);
        } while ($chk->fetch());

        $stmt = $pdo->prepare('INSERT INTO classes (name, description, teacher_id, invite_code) VALUES (?,?,?,?)');
        $stmt->execute([$name, $desc, $user['id'], $code]);
        flash('success', 'Đã tạo lớp học. Mã mời: ' . $code);
        header('Location: view.php?id=' . $pdo->lastInsertId());
        exit;
    }
}

$pageTitle = 'Tạo lớp học - UTH Forum';
require __DIR__ . '/../includes/header.php';
?>
<section class="row" style="justify-content:center;">
  <div class="card" style="max-width:600px;width:100%;">
    <h2>Tạo lớp học mới</h2>
    <?php foreach ($errors as $err): ?><div class="alert alert-error"><?= e($err) ?></div><?php endforeach; ?>
    <form method="post">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <div class="form-group">
        <label>Tên lớp học</label>
        <input type="text" name="name" required placeholder="VD: Kỹ thuật ô tô K21">
      </div>
      <div class="form-group">
        <label>Mô tả</label>
        <textarea name="description" rows="3"></textarea>
      </div>
      <button type="submit" class="btn btn-red">Tạo lớp — hệ thống sẽ tự sinh mã mời</button>
    </form>
  </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
