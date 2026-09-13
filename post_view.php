<?php
require_once __DIR__ . '/includes/functions.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT p.*, u.username, u.full_name, u.role, c.name AS class_name, c.teacher_id
                        FROM posts p JOIN users u ON u.id = p.user_id
                        LEFT JOIN classes c ON c.id = p.class_id
                        WHERE p.id = ?');
$stmt->execute([$id]);
$post = $stmt->fetch();
if (!$post) { http_response_code(404); die('Không tìm thấy bài viết.'); }

$user = current_user();
$isOwner = $user && $user['id'] == $post['user_id'];
$isClassTeacher = $user && $post['teacher_id'] && $user['id'] == $post['teacher_id'];
$isAdmin = $user && $user['role'] === 'admin';

// Non-approved posts are only visible to their author, the admin, or the class teacher
if ($post['status'] !== 'approved' && !$isOwner && !$isAdmin && !$isClassTeacher) {
    http_response_code(403);
    die('Bài viết này chưa được duyệt.');
}

$fileStmt = $pdo->prepare('SELECT * FROM files WHERE post_id = ?');
$fileStmt->execute([$id]);
$attachments = $fileStmt->fetchAll();

$likeCount = $pdo->prepare('SELECT COUNT(*) FROM likes WHERE post_id = ?');
$likeCount->execute([$id]);
$likeCount = (int)$likeCount->fetchColumn();

$liked = false;
if ($user) {
    $lk = $pdo->prepare('SELECT 1 FROM likes WHERE post_id = ? AND user_id = ?');
    $lk->execute([$id, $user['id']]);
    $liked = (bool)$lk->fetch();
}

$commentStmt = $pdo->prepare('SELECT cm.*, u.username, u.full_name, u.role
                               FROM comments cm JOIN users u ON u.id = cm.user_id
                               WHERE cm.post_id = ? ORDER BY cm.created_at ASC');
$commentStmt->execute([$id]);
$comments = $commentStmt->fetchAll();

$pageTitle = $post['title'] . ' - UTH Forum';
require __DIR__ . '/includes/header.php';
?>
<script>window.CSRF = <?= json_encode(csrf_token()) ?>; window.POST_ID = <?= (int)$post['id'] ?>;</script>
<div class="row">
  <section style="flex:3;min-width:0;">
    <div class="card">
      <div class="post-meta">
        <strong><?= e($post['full_name'] ?: $post['username']) ?></strong>
        <span><?= e(role_label($post['role'])) ?></span>
        <span>· <?= time_ago($post['created_at']) ?></span>
        <?php if ($post['class_name']): ?><span>· Lớp: <?= e($post['class_name']) ?></span><?php endif; ?>
        <?php if ($post['is_announcement']): ?><span class="tag tag-urgent">Khẩn cấp</span><?php endif; ?>
        <?php if ($post['status'] !== 'approved'): ?>
          <span class="tag tag-<?= e($post['status']) ?>"><?= $post['status'] === 'pending' ? 'Chờ duyệt' : 'Bị từ chối' ?></span>
        <?php endif; ?>
      </div>
      <h2><?= e($post['title']) ?></h2>
      <div class="post-content"><?= $post['content'] /* already sanitized on save */ ?></div>

      <?php if ($attachments): ?>
        <div style="margin-top:14px;">
          <?php foreach ($attachments as $f): ?>
            <div class="material-item">
              <span><i class="fa-solid fa-paperclip"></i> <?= e($f['original_name']) ?></span>
              <a class="btn btn-outline btn-sm" href="<?= e(UPLOAD_URL . $f['stored_name']) ?>" download><?= e($f['original_name']) ?></a>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <div class="fb-post-stats">
    <div class="fb-like-summary">
        <span class="fb-like-icon">
            <i class="fa-solid fa-thumbs-up"></i>
        </span>

        <span class="like-count">
            <?= $likeCount ?>
        </span>
    </div>

    <span class="comment-count">
        <?= count($comments) ?> bình luận
    </span>
</div>

<div class="fb-post-actions">

    <?php if ($user): ?>

        <button
            type="button"
            class="fb-action like-btn <?= $liked ? 'liked' : '' ?>"
            data-post-id="<?= (int)$post['id'] ?>"
        >
            <i class="fa-solid fa-thumbs-up"></i>

            <span class="like-text">
                <?= $liked ? 'Đã thích' : 'Thích' ?>
            </span>
        </button>

    <?php else: ?>

        <button
            type="button"
            class="fb-action"
            onclick="location.href='login.php'"
        >
            <i class="fa-regular fa-thumbs-up"></i>
            <span>Thích</span>
        </button>

    <?php endif; ?>


    <button
        type="button"
        class="fb-action comment-scroll-btn"
    >
        <i class="fa-regular fa-comment"></i>
        <span>Bình luận</span>
    </button>


    <?php if ($isOwner): ?>

        <a
            href="post_edit.php?id=<?= (int)$post['id'] ?>"
            class="fb-action"
        >
            <i class="fa-solid fa-pen"></i>
            <span>Sửa</span>
        </a>

        <a
            href="post_delete.php?id=<?= (int)$post['id'] ?>"
            class="fb-action danger"
            onclick="return confirm('Xóa bài viết này?')"
        >
            <i class="fa-solid fa-trash"></i>
            <span>Xóa</span>
        </a>

    <?php endif; ?>

</div>
    </div>

    <div class="card fb-comments-card" id="comments" data-post-id="<?= (int)$post['id'] ?>">
      <div class="fb-comments-header">
        <h3>Bình luận</h3>
        <span class="fb-comment-total"><?= count($comments) ?></span>
      </div>

      <div class="fb-comments-list">
        <?php if (!$comments): ?>
          <div class="no-comments">Chưa có bình luận nào. Hãy là người đầu tiên bình luận!</div>
        <?php else: ?>
          <?php foreach ($comments as $c): ?>
            <div class="fb-comment" data-comment-id="<?= (int)$c['id'] ?>">
              <div class="avatar fb-comment-avatar">
                <?= e(mb_strtoupper(mb_substr($c['full_name'] ?: $c['username'], 0, 1))) ?>
              </div>

              <div class="fb-comment-content">
                <div class="fb-comment-bubble">
                  <div class="fb-comment-name">
                    <?= e($c['full_name'] ?: $c['username']) ?>
                  </div>
                  <div class="fb-comment-text">
                    <?= e($c['content']) ?>
                  </div>
                </div>

                <div class="fb-comment-meta">
                  <span><?= e(time_ago($c['created_at'])) ?></span>
                  <?php if ($user && ($user['id'] == $c['user_id'] || $isClassTeacher || $isAdmin)): ?>
                    <a href="comment_delete.php?id=<?= (int)$c['id'] ?>&post_id=<?= (int)$post['id'] ?>"
                       onclick="return confirm('Xóa bình luận này?')">Xóa</a>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <?php if ($user): ?>
        <form method="post" action="comment_add.php" class="fb-comment-form" id="commentForm">
          <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="post_id" value="<?= (int)$post['id'] ?>">

          <div class="avatar fb-input-avatar">
            <?= e(mb_strtoupper(mb_substr($user['full_name'] ?: $user['username'], 0, 1))) ?>
          </div>

          <div class="fb-comment-input-wrap">
            <textarea id="commentInput" name="content" rows="1"
                      placeholder="Viết bình luận..." required></textarea>
            <button type="submit" class="fb-send-comment" title="Gửi bình luận">
              <i class="fa-solid fa-paper-plane"></i>
            </button>
          </div>
        </form>
      <?php else: ?>
        <p class="fb-login-comment">
          <a href="login.php">Đăng nhập</a> để bình luận.
        </p>
      <?php endif; ?>
    </div>
  </section>
</div>
<script src="js/main.js"></script>
<?php require __DIR__ . '/includes/footer.php'; ?>
