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
<script>window.CSRF = <?= json_encode(csrf_token()) ?>;</script>
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

      <div class="post-actions" style="margin-top:16px;">
        <button class="like-btn <?= $liked ? 'liked' : '' ?>" data-post-id="<?= (int)$post['id'] ?>" <?= $user ? '' : 'disabled' ?>>
          <i class="fa-solid fa-heart"></i> <span class="count"><?= $likeCount ?></span>
        </button>
        <span><i class="fa-solid fa-comment"></i> <?= count($comments) ?> bình luận</span>
        <?php if ($isOwner): ?>
          <a href="post_edit.php?id=<?= (int)$post['id'] ?>">Sửa</a>
          <a href="post_delete.php?id=<?= (int)$post['id'] ?>" onclick="return confirm('Xóa bài viết này?')">Xóa</a>
        <?php endif; ?>
      </div>
    </div>

    <div class="card" style="margin-top:16px;">
      <h3>Bình luận</h3>
      <?php foreach ($comments as $c): ?>
        <div class="comment">
          <div class="avatar" style="width:34px;height:34px;font-size:13px;"><?= e(mb_strtoupper(mb_substr($c['full_name'] ?: $c['username'], 0, 1))) ?></div>
          <div style="flex:1;">
            <div class="bubble">
              <strong><?= e($c['full_name'] ?: $c['username']) ?></strong>
              <span style="color:var(--muted);font-size:12px;"> · <?= time_ago($c['created_at']) ?></span>
              <p style="margin:4px 0 0;"><?= e($c['content']) ?></p>
            </div>
            <?php if ($user && ($user['id'] == $c['user_id'] || $isClassTeacher || $isAdmin)): ?>
              <a href="comment_delete.php?id=<?= (int)$c['id'] ?>&post_id=<?= (int)$post['id'] ?>"
                 style="font-size:12px;color:var(--red);" onclick="return confirm('Xóa bình luận này?')">Xóa</a>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>

      <?php if ($user): ?>
        <form method="post" action="comment_add.php" class="comment-form">
          <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="post_id" value="<?= (int)$post['id'] ?>">
          <textarea name="content" placeholder="Viết bình luận..." required></textarea>
          <button type="submit" class="btn btn-teal btn-sm">Gửi</button>
        </form>
      <?php else: ?>
        <p style="font-size:14px;"><a href="login.php" style="color:var(--teal);">Đăng nhập</a> để bình luận.</p>
      <?php endif; ?>
    </div>
  </section>
</div>
<script src="js/main.js"></script>
<?php require __DIR__ . '/includes/footer.php'; ?>
