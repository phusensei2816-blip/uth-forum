<?php
require_once __DIR__ . '/../includes/functions.php';
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
        $f = $_FILES['material'];
        if ($f['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Tải lên thất bại.';
        } elseif ($f['size'] > MAX_UPLOAD_SIZE) {
            $errors[] = 'Tệp vượt quá dung lượng cho phép (20MB).';
        } else {
            $ext = pathinfo($f['name'], PATHINFO_EXTENSION);
            $stored = uniqid('mat_') . '.' . preg_replace('/[^a-zA-Z0-9]/', '', $ext);
            if (move_uploaded_file($f['tmp_name'], UPLOAD_DIR . $stored)) {
                $ins = $pdo->prepare('INSERT INTO files (class_id, uploader_id, original_name, stored_name, filesize, mime_type)
                                       VALUES (?,?,?,?,?,?)');
                $ins->execute([$id, $user['id'], $f['name'], $stored, $f['size'], $f['type']]);
                flash('success', 'Đã tải lên tài liệu.');
                header('Location: view.php?id=' . $id);
                exit;
            } else {
                $errors[] = 'Không thể lưu tệp lên máy chủ.';
            }
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
