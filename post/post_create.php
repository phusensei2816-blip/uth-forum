<?php
require_once __DIR__ . '/../includes/functions.php';

require_role(['student', 'teacher']);
$user = current_user();

/* Lấy danh sách lớp học người dùng có thể đăng bài */
if ($user['role'] === 'teacher') {
    $stmt = $pdo->prepare(
        'SELECT id, name FROM classes WHERE teacher_id = ?'
    );
    $stmt->execute([$user['id']]);
} else {
    $stmt = $pdo->prepare(
        'SELECT c.id, c.name
         FROM classes c
         JOIN class_members cm ON cm.class_id = c.id
         WHERE cm.user_id = ?'
    );
    $stmt->execute([$user['id']]);
}

$classes = $stmt->fetchAll();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $title = trim($_POST['title'] ?? '');
    $content = sanitize_html($_POST['content'] ?? '');

    $classId = isset($_POST['class_id']) && $_POST['class_id'] !== ''
        ? (int) $_POST['class_id']
        : null;

    $isAnnouncement = (
        $user['role'] === 'teacher'
        && isset($_POST['is_announcement'])
    );

    if ($title === '') {
        $errors[] = 'Vui lòng nhập tiêu đề.';
    }

    if (trim(strip_tags($content)) === '') {
        $errors[] = 'Nội dung không được để trống.';
    }

    if (!$errors) {
        // Giáo viên đăng bài được duyệt tự động.
        // Bài viết của sinh viên cần quản trị viên phê duyệt.
        $status = $user['role'] === 'teacher'
            ? 'approved'
            : 'pending';

        $stmt = $pdo->prepare(
            'INSERT INTO posts
                (user_id, class_id, title, content, is_announcement, status)
             VALUES (?, ?, ?, ?, ?, ?)'
        );

        $stmt->execute([
            $user['id'],
            $classId,
            $title,
            $content,
            $isAnnouncement ? 1 : 0,
            $status
        ]);

        $postId = $pdo->lastInsertId();

        /* Tệp đính kèm không bắt buộc */
        if (
            isset($_FILES['attachment'])
            && $_FILES['attachment']['name'] !== ''
        ) {
            $file = $_FILES['attachment'];

            if (
                $file['error'] === UPLOAD_ERR_OK
                && $file['size'] <= MAX_UPLOAD_SIZE
            ) {
                $extension = pathinfo(
                    $file['name'],
                    PATHINFO_EXTENSION
                );

                $extension = preg_replace(
                    '/[^a-zA-Z0-9]/',
                    '',
                    $extension
                );

                $storedName = uniqid('post_') . '.' . $extension;

                if (
                    move_uploaded_file(
                        $file['tmp_name'],
                        UPLOAD_DIR . $storedName
                    )
                ) {
                    $stmt = $pdo->prepare(
                        'INSERT INTO files
                            (post_id, uploader_id, original_name,
                             stored_name, filesize, mime_type)
                         VALUES (?, ?, ?, ?, ?, ?)'
                    );

                    $stmt->execute([
                        $postId,
                        $user['id'],
                        $file['name'],
                        $storedName,
                        $file['size'],
                        $file['type']
                    ]);
                }
            }
        }

        flash(
            'success',
            $status === 'approved'
                ? 'Đã đăng bài thành công.'
                : 'Bài viết đã được gửi và đang chờ quản trị viên duyệt.'
        );

        header('Location: post_view.php?id=' . $postId);
        exit;
    }
}

$pageTitle = ( $user['role'] === 'teacher' ? 'Đăng thông báo' : 'Đăng bài') . ' - UTH Forum';
$pageCss = 'post.css';

require __DIR__ . '/../includes/header.php';?>

<section class="post-create-page">
    <div class="post-create-card">

        <div class="post-create-header">
            <h2>
                <?= $user['role'] === 'teacher'
                    ? 'Đăng thông báo / tài liệu'
                    : 'Tạo bài viết mới' ?>
            </h2>
        </div>

        <?php if ($errors): ?>
            <div class="post-create-alert post-create-alert-error">
                <?php foreach ($errors as $error): ?>
                    <p><?= e($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($user['role'] === 'student'): ?>
            <div class="post-create-alert post-create-alert-info">
                Bài viết của bạn sẽ hiển thị công khai sau khi
                quản trị viên phê duyệt.
            </div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data" class="post-create-form">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">

            <div class="post-form-group">
                <label for="post-title">Tiêu đề bài viết</label>
                <input type="text" id="post-title" name="title" placeholder="Nhập tiêu đề bài viết..." required value="<?= e($_POST['title'] ?? '') ?>">
            </div>

            <?php if ($classes): ?>
                <div class="post-form-group">

                    <label for="post-class"> Đăng vào lớp học
                        <span>(không bắt buộc)</span>
                    </label>

                    <select id="post-class" name="class_id">
                        <option value="">Bảng tin chung</option>

                        <?php foreach ($classes as $class): ?>
                            <option value="<?= (int) $class['id'] ?>" <?=
                              (
                                  ($_POST['class_id'] ?? '')
                                  == $class['id']
                              ) ? 'selected' : '' ?>><?= e($class['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <small> Chọn một lớp học hoặc đăng lên bảng tin chung.</small>

                </div>
            <?php endif; ?>

            <?php if ($user['role'] === 'teacher'): ?>
                <div class="post-announcement-option">
                    <input type="checkbox" name="is_announcement" id="is_announcement"
                        <?= isset($_POST['is_announcement']) ? 'checked' : '' ?>>

                    <label for="is_announcement"> Gắn cờ là thông báo khẩn cấp</label>

                </div>
            <?php endif; ?>

            <div class="post-form-group">
                <label for="editor">Nội dung bài viết</label>

                <div class="editor-toolbar" data-target="editor">
                    <button type="button" data-cmd="bold" title="In đậm">
                        <b>B</b>
                    </button>

                    <button type="button" data-cmd="italic" title="In nghiêng">
                        <i>I</i>
                    </button>

                    <button type="button" data-cmd="underline" title="Gạch chân">
                        <u>U</u>
                    </button>

                    <button type="button" data-cmd="insertUnorderedList" title="Danh sách">
                        •
                    </button>

                    <button type="button" data-cmd="insertOrderedList" title="Danh sách đánh số">
                        1.
                    </button>

                    <button type="button" data-cmd="createLink" title="Chèn liên kết">
                        <i class="fa-solid fa-link"></i>
                    </button>
                </div>

                <div id="editor" class="editor-content" contenteditable="true" data-placeholder="Viết nội dung bài viết của bạn...">
                  <?= sanitize_html($_POST['content'] ?? '') ?>
                </div>

                <textarea id="editor_hidden" name="content" hidden>
                  <?= e($_POST['content'] ?? '') ?>
                </textarea>

                <small> Bạn có thể định dạng văn bản bằng thanh công cụ phía trên.</small>
            </div>

            <div class="post-form-group">

                <label for="post-attachment"> Tệp đính kèm
                    <span>(không bắt buộc)</span>
                </label>

                <input type="file" id="post-attachment" name="attachment">
                <small>Chọn tệp nếu bạn muốn đính kèm tài liệu vào bài viết.</small>

            </div>

            <div class="post-create-actions">
                <a href="../index.php" class="post-btn-cancel"> Hủy</a>
                <button type="submit" class="post-btn-submit"> Đăng bài</button>
            </div>

        </form>
    </div>
</section>

<script src="../js/main.js"></script>