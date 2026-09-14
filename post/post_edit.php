<?php
require_once __DIR__ . '/../includes/functions.php';

require_login();

$user = current_user();
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

// Lấy bài viết cần sửa
$stmt = $pdo->prepare('SELECT * FROM posts WHERE id = ?');
$stmt->execute([$id]);
$post = $stmt->fetch();

if (!$post) {
    http_response_code(404);
    die('Không tìm thấy bài viết.');
}

// Chỉ chủ bài viết mới được sửa
if ($post['user_id'] != $user['id']) {
    http_response_code(403);
    die('Bạn không thể sửa bài viết này.');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $title = trim($_POST['title'] ?? '');
    $content = sanitize_html($_POST['content'] ?? '');

    if ($title === '') {
        $errors[] = 'Vui lòng nhập tiêu đề.';
    }

    if (trim(strip_tags($content)) === '') {
        $errors[] = 'Nội dung không được để trống.';
    }

    if (!$errors) {
        // Bài viết của sinh viên cần duyệt lại.
        // Bài viết của giáo viên vẫn được duyệt.
        $status = $user['role'] === 'teacher'
            ? 'approved'
            : 'pending';

        $upd = $pdo->prepare(
            'UPDATE posts
             SET title = ?, content = ?, status = ?
             WHERE id = ?'
        );

        $upd->execute([
            $title,
            $content,
            $status,
            $id
        ]);

        flash('success', 'Đã cập nhật bài viết.');

        header('Location: post_view.php?id=' . $id);
        exit;
    }
}

$pageTitle = 'Sửa bài viết - UTH Forum';
$pageCss = 'post.css';

require __DIR__ . '/../includes/header.php';?>

<section class="post-edit-page">
    <div class="post-edit-card">

        <div class="post-edit-header">
            <h2>Sửa bài viết</h2>
        </div>

        <?php if ($errors): ?>
            <div class="post-edit-alert post-edit-alert-error">
                <?php foreach ($errors as $error): ?>
                    <p><?= e($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="post" class="post-edit-form">

            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">

            <input type="hidden" name="id" value="<?= (int) $post['id'] ?>">

            <div class="post-edit-group">
                <label for="post-title">Tiêu đề bài viết</label>

                <input type="text" id="post-title" name="title"
                    placeholder="Nhập tiêu đề bài viết..."
                    required
                    value="<?= e($_POST['title'] ?? $post['title']) ?>">
            </div>

            <div class="post-edit-group">
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

                <div id="editor" class="editor-content" contenteditable="true" data-placeholder="Viết nội dung bài viết...">
                  <?= sanitize_html($_POST['content'] ?? $post['content']) ?>
                </div>

                <textarea id="editor_hidden" name="content" hidden></textarea>

            </div>

            <div class="post-edit-actions">
                <a href="post_view.php?id=<?= (int) $post['id'] ?>" class="post-edit-btn-cancel">
                    Hủy
                </a>

                <button type="submit" class="post-edit-btn-submit">
                    Lưu thay đổi
                </button>
            </div>

        </form>
    </div>
</section>

<script src="../js/main.js"></script>