<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/upload_helpers.php';
require_role(['student', 'teacher']);
$user = current_user();

// Classes the user can attach the post to
if ($user['role'] === 'teacher') {
    $stmt = $pdo->prepare('SELECT id, name FROM classes WHERE teacher_id = ?');
    $stmt->execute([$user['id']]);
} else {
    $stmt = $pdo->prepare('SELECT c.id, c.name FROM classes c
                            JOIN class_members cm ON cm.class_id = c.id
                            WHERE cm.user_id = ?');
    $stmt->execute([$user['id']]);
}
$classes = $stmt->fetchAll();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $title = trim($_POST['title'] ?? '');
    $content = sanitize_html($_POST['content'] ?? '');
    $classId = $_POST['class_id'] !== '' ? (int)$_POST['class_id'] : null;
    $isAnnouncement = $user['role'] === 'teacher' && isset($_POST['is_announcement']);

    if ($title === '') $errors[] = 'Vui lòng nhập tiêu đề.';
    if (trim(strip_tags($content)) === '') $errors[] = 'Nội dung không được để trống.';

    if (!$errors) {
        // Teacher posts (and especially announcements) are auto-approved; student posts go to moderation queue
        $status = $user['role'] === 'teacher' ? 'approved' : 'pending';
        $stmt = $pdo->prepare('INSERT INTO posts (user_id, class_id, title, content, is_announcement, status)
                                VALUES (?,?,?,?,?,?)');
        $stmt->execute([$user['id'], $classId, $title, $content, $isAnnouncement ? 1 : 0, $status]);
        $postId = $pdo->lastInsertId();

        // Optional file attachment
        if (!empty($_FILES['attachment']['name'])) {
            $up = secure_store_upload($_FILES['attachment'], 'post');
            if ($up['ok']) {
                $ins = $pdo->prepare('INSERT INTO files (post_id, uploader_id, original_name, stored_name, filesize, mime_type)
                                       VALUES (?,?,?,?,?,?)');
                $ins->execute([$postId, $user['id'], $_FILES['attachment']['name'], $up['stored'], $_FILES['attachment']['size'], $up['mime']]);
            } else {
                flash('error', 'Đính kèm không được lưu: ' . $up['error']);
            }
        }

        flash('success', $status === 'approved'
            ? 'Đã đăng bài thành công.'
            : 'Bài viết đã được gửi và đang chờ quản trị viên duyệt.');
        header('Location: post_view.php?id=' . $postId);
        exit;
    }
}

$pageTitle = ($user['role'] === 'teacher' ? 'Đăng thông báo' : 'Đăng bài') . ' - UTH Forum';
require __DIR__ . '/includes/header.php';
?>
<section class="row" style="justify-content:center;">
  <div class="card" style="max-width:700px;width:100%;">
    <h2><?= $user['role'] === 'teacher' ? 'Đăng thông báo / tài liệu' : 'Tạo bài viết mới' ?></h2>
    <?php if (!$errors === false): foreach ($errors as $err): ?>
      <div class="alert alert-error"><?= e($err) ?></div>
    <?php endforeach; endif; ?>
    <?php if ($user['role'] === 'student'): ?>
      <div class="alert alert-info">Bài viết của bạn sẽ hiển thị công khai sau khi quản trị viên phê duyệt.</div>
    <?php endif; ?>
    <form method="post" enctype="multipart/form-data">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <div class="form-group">
        <label>Tiêu đề</label>
        <input type="text" name="title" required value="<?= e($_POST['title'] ?? '') ?>">
      </div>
      <?php if ($classes): ?>
        <div class="form-group">
          <label>Đăng vào lớp học (không bắt buộc)</label>
          <select name="class_id">
            <option value="">-- Bảng tin chung --</option>
            <?php foreach ($classes as $c): ?>
              <option value="<?= (int)$c['id'] ?>"><?= e($c['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      <?php endif; ?>
      <?php if ($user['role'] === 'teacher'): ?>
        <div class="form-group check">
          <input type="checkbox" name="is_announcement" id="is_announcement">
          <label for="is_announcement" style="margin:0;">Gắn cờ là thông báo khẩn cấp</label>
        </div>
      <?php endif; ?>
      <div class="form-group">
        <label>Nội dung</label>
        <div class="editor-toolbar" data-target="editor">
          <button data-cmd="bold" title="Đậm"><b>B</b></button>
          <button data-cmd="italic" title="Nghiêng"><i>I</i></button>
          <button data-cmd="underline" title="Gạch chân"><u>U</u></button>
          <button data-cmd="insertUnorderedList" title="Danh sách">&#8226;</button>
          <button data-cmd="insertOrderedList" title="Danh sách số">1.</button>
          <button data-cmd="createLink" title="Chèn liên kết">&#128279;</button>
        </div>
        <div id="editor" class="editor-content" contenteditable="true"></div>
        <textarea id="editor_hidden" name="content" style="display:none;"></textarea>
      </div>
      <div class="form-group">
        <label>Tệp đính kèm (không bắt buộc)</label>
        <input type="file" name="attachment">
      </div>
      <button type="submit" class="btn btn-red">Đăng bài</button>
    </form>
  </div>
</section>
<script src="js/main.js"></script>
<?php require __DIR__ . '/includes/footer.php'; ?>
