<?php
require_once __DIR__ . '/../includes/functions.php';

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare(
    'SELECT p.*, u.username, u.full_name, u.role,
            c.name AS class_name, c.teacher_id
     FROM posts p
     JOIN users u ON u.id = p.user_id
     LEFT JOIN classes c ON c.id = p.class_id
     WHERE p.id = ?'
);
$stmt->execute([$id]);
$post = $stmt->fetch();

if (!$post) {
    http_response_code(404);
    die('Không tìm thấy bài viết.');
}

$user = current_user();

$isOwner = $user && $user['id'] == $post['user_id'];
$isClassTeacher = $user
    && $post['teacher_id']
    && $user['id'] == $post['teacher_id'];
$isAdmin = $user && $user['role'] === 'admin';

// Bài chưa duyệt chỉ dành cho tác giả, quản trị viên
// hoặc giáo viên phụ trách lớp xem.
if (
    $post['status'] !== 'approved'
    && !$isOwner
    && !$isAdmin
    && !$isClassTeacher
) {
    http_response_code(403);
    die('Bài viết này chưa được duyệt.');
}

// Lấy tệp đính kèm
$fileStmt = $pdo->prepare('SELECT * FROM files WHERE post_id = ?');
$fileStmt->execute([$id]);
$attachments = $fileStmt->fetchAll();

// Đếm lượt thích
$likeStmt = $pdo->prepare('SELECT COUNT(*) FROM likes WHERE post_id = ?');
$likeStmt->execute([$id]);
$likeCount = (int) $likeStmt->fetchColumn();

// Kiểm tra người dùng đã thích bài viết chưa
$liked = false;

if ($user) {
    $likeCheck = $pdo->prepare(
        'SELECT 1 FROM likes WHERE post_id = ? AND user_id = ?'
    );
    $likeCheck->execute([$id, $user['id']]);
    $liked = (bool) $likeCheck->fetch();
}

// Lấy bình luận
$commentStmt = $pdo->prepare(
    'SELECT cm.*, u.username, u.full_name, u.role
     FROM comments cm
     JOIN users u ON u.id = cm.user_id
     WHERE cm.post_id = ?
     ORDER BY cm.created_at ASC'
);
$commentStmt->execute([$id]);
$comments = $commentStmt->fetchAll();

$pageTitle = $post['title'] . ' - UTH Forum';
$pageCss = 'post.css';

require __DIR__ . '/../includes/header.php';?>

<script>
    window.CSRF = <?= json_encode(
        csrf_token(),
        JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
    ) ?>;
</script>

<main class="post-view-page">
    <div class="post-view-layout">

        <!-- Nội dung bài viết -->
        <article class="post-view-card">

            <div class="post-view-meta">

              <span class="post-view-role">
                    <?= e(role_label($post['role'])) ?>
                </span>

                <strong class="post-view-author">
                    <?= e($post['full_name'] ?: $post['username']) ?>
                </strong>
                
                <span class="post-view-time">
                    <?= time_ago($post['created_at']) ?>
                </span>

                <?php if ($post['class_name']): ?>
                    <span class="post-view-class">
                        Lớp: <?= e($post['class_name']) ?>
                    </span>
                <?php endif; ?>

                <?php if ($post['is_announcement']): ?>
                    <span class="post-view-tag post-view-tag-urgent">
                        Khẩn cấp
                    </span>
                <?php endif; ?>

                <?php if ($post['status'] !== 'approved'): ?>
                    <span class="post-view-tag post-view-tag-status">
                        <?= $post['status'] === 'pending'
                            ? 'Chờ duyệt'
                            : 'Bị từ chối' ?>
                    </span>
                <?php endif; ?>
            </div>

            <h1 class="post-view-title">
                <?= e($post['title']) ?>
            </h1>

            <div class="post-view-content">
                <?= $post['content'] /* Nội dung đã được làm sạch khi lưu */ ?>
            </div>

            <!-- Tệp đính kèm -->
            <?php if ($attachments): ?>
                <div class="post-view-attachments">
                    <h3 class="post-view-section-title">Tệp đính kèm</h3>

                    <?php foreach ($attachments as $file): ?>
                        <div class="post-view-file">
                            <span class="post-view-file-name">
                                <i class="fa-solid fa-paperclip"></i>
                                <?= e($file['original_name']) ?>
                            </span>

                            <!-- Sửa dòng này trong post/post_view.php -->
                            <a class="post-view-download"
                                href="../download.php?id=<?= (int)$file['id'] ?>">
                                Tải xuống
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Thao tác bài viết -->
            <div class="post-view-actions">
                <button
                    type="button"
                    class="like-btn post-view-like <?= $liked ? 'liked' : '' ?>"
                    data-post-id="<?= (int) $post['id'] ?>"
                    <?= $user ? '' : 'disabled' ?>
                    aria-label="Thích bài viết"
                >
                    <i class="fa-solid fa-heart"></i>
                    <span class="count"><?= $likeCount ?></span>
                </button>

                <a class="post-view-comment-count" href="#comments">
                    <i class="fa-solid fa-comment"></i>
                    <?= count($comments) ?> bình luận
                </a>

                <?php if ($isOwner): ?>
                    <div class="post-view-owner-actions">
                        <a href="post_edit.php?id=<?= (int) $post['id'] ?>">
                            Sửa bài
                        </a>

                        <a class="post-view-delete"
                            href="post_delete.php?id=<?= (int) $post['id'] ?>"
                            onclick="return confirm('Xóa bài viết này?')">
                            Xóa bài
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </article>

        <!-- Khu vực bình luận -->
        <section class="post-view-card post-view-comments" id="comments">
            <h2 class="post-view-comments-title">
                Bình luận
                <span><?= count($comments) ?></span>
            </h2>

            <?php if ($comments): ?>
                <div class="post-view-comment-list">
                    <?php foreach ($comments as $comment): ?>
                        <?php
                        $commentName = $comment['full_name']
                            ?: $comment['username'];
                        ?>

                        <div class="post-view-comment">
                            <div class="post-view-comment-avatar">
                                <?= e(mb_strtoupper(mb_substr($commentName, 0, 1))) ?>
                            </div>

                            <div class="post-view-comment-body">
                                <div class="post-view-comment-bubble">
                                    <div class="post-view-comment-heading">
                                        <strong><?= e($commentName) ?></strong>
                                        <span><?= time_ago($comment['created_at']) ?></span>
                                    </div>

                                    <p><?= e($comment['content']) ?></p>
                                </div>

                                <?php
                                $canDeleteComment = $user && (
                                    $user['id'] == $comment['user_id']
                                    || $isClassTeacher
                                    || $isAdmin
                                );
                                ?>

                                <?php if ($canDeleteComment): ?>
                                    <a class="post-view-comment-delete"
                                        href="../comment_delete.php?id=<?= (int) $comment['id'] ?>&post_id=<?= (int) $post['id'] ?>"
                                        onclick="return confirm('Xóa bình luận này?')">
                                        Xóa
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="post-view-no-comments">
                    Chưa có bình luận nào. Hãy là người bình luận đầu tiên!
                </p>
            <?php endif; ?>

            <?php if ($user): ?>
                <form method="post" action="../comment_add.php" class="post-view-comment-form">
                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="post_id" value="<?= (int) $post['id'] ?>">
                    <textarea name="content" placeholder="Viết bình luận..." required></textarea>
                    <button type="submit">Gửi bình luận</button>
                </form>
            <?php else: ?>
                <p class="post-view-login-note">
                    <a href="../login.php">Đăng nhập</a>
                    để viết bình luận.
                </p>
            <?php endif; ?>
        </section>

    </div>
</main>

<script src="../js/main.js"></script>