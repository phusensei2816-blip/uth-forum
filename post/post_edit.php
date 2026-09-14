<?php
require_once __DIR__ . '/includes/functions.php';
require_login();
$user = current_user();

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM posts WHERE id = ?');
$stmt->execute([$id]);
$post = $stmt->fetch();
if (!$post) { http_response_code(404); die('Không tìm thấy bài viết.'); }
if ($post['user_id'] != $user['id']) { http_response_code(403); die('Bạn không thể sửa bài viết này.'); }

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $title = trim($_POST['title'] ?? '');
    $content = sanitize_html($_POST['content'] ?? '');
    if ($title === '') $errors[] = 'Vui lòng nhập tiêu đề.';
    if (trim(strip_tags($content)) === '') $errors[] = 'Nội dung không được để trống.';

    if (!$errors) {
        // Editing a student post sends it back to moderation; teacher edits stay approved
        $status = $user['role'] === 'teacher' ? 'approved' : 'pending';
        $upd = $pdo->prepare('UPDATE posts SET title=?, content=?, status=? WHERE id=?');
        $upd->execute([$title, $content, $status, $id]);
        flash('success', 'Đã cập nhật bài viết.');
        header('Location: post_view.php?id=' . $id);
        exit;
    }
}

$pageTitle = 'Sửa bài viết - UTH Forum';
require __DIR__ . '/includes/header.php';
?>
<section class="row" style="justify-content:center;">
  <div class="card" style="max-width:700px;width:100%;">
    <h2>Sửa bài viết</h2>
    <?php foreach ($errors as $err): ?><div class="alert alert-error"><?= e($err) ?></div><?php endforeach; ?>
    <form method="post">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <div class="form-group">
        <label>Tiêu đề</label>
        <input type="text" name="title" required value="<?= e($post['title']) ?>">
      </div>
      <div class="form-group">
        <label>Nội dung</label>
        <div class="editor-toolbar" data-target="editor">
          <button data-cmd="bold"><b>B</b></button>
          <button data-cmd="italic"><i>I</i></button>
          <button data-cmd="underline"><u>U</u></button>
          <button data-cmd="insertUnorderedList">&#8226;</button>
          <button data-cmd="insertOrderedList">1.</button>
          <button data-cmd="createLink">&#128279;</button>
        </div>
        <div id="editor" class="editor-content" contenteditable="true"><?= $post['content'] ?></div>
        <textarea id="editor_hidden" name="content" style="display:none;"></textarea>
      </div>
      <button type="submit" class="btn btn-red">Lưu thay đổi</button>
    </form>
  </div>
</section>
<script src="js/main.js"></script>
<?php require __DIR__ . '/includes/footer.php'; ?>
