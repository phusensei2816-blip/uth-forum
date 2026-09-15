<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/upload_helpers.php';
require_role(['teacher']);
$user = current_user();

$id = (int)($_GET['id'] ?? $_POST['class_id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM classes WHERE id = ? AND teacher_id = ?');
$stmt->execute([$id, $user['id']]);
$class = $stmt->fetch();
if (!$class) { http_response_code(403); die('Bạn không quản lý lớp học này.'); }

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (empty($_FILES['material']['name'])) {
        $errors[] = 'Vui lòng chọn tệp để tải lên.';
    } else {
        $up = secure_store_upload($_FILES['material'], 'mat');
        if ($up['ok']) {
            $ins = $pdo->prepare('INSERT INTO files (class_id, uploader_id, original_name, stored_name, filesize, mime_type)
                                   VALUES (?,?,?,?,?,?)');
            $ins->execute([$id, $user['id'], $_FILES['material']['name'], $up['stored'], $_FILES['material']['size'], $up['mime']]);
            flash('success', 'Đã tải lên tài liệu.');
            header('Location: view.php?id=' . $id);
            exit;
        } else {
            $errors[] = $up['error'];
        }
    }
}

$pageTitle = 'Tải lên tài liệu - UTH Forum';
require __DIR__ . '/../includes/header.php';
?>
<section class="row" style="justify-content:center;">
  <div class="card" style="max-width:600px;width:100%;">
    <h2>Tải lên tài liệu / bài tập cho "<?= e($class['name']) ?>"</h2>
    <?php foreach ($errors as $err): ?><div class="alert alert-error"><?= e($err) ?></div><?php endforeach; ?>
    <form method="post" enctype="multipart/form-data">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="class_id" value="<?= (int)$id ?>">
      <div class="form-group">
        <label>Chọn tệp (PDF, Word, Excel, PowerPoint, hình ảnh, nén ZIP...)</label>
        <input type="file" name="material" required>
      </div>
      <button type="submit" class="btn btn-red">Tải lên</button>
    </form>
  </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
