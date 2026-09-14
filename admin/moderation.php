<?php
require_once __DIR__ . '/../includes/functions.php';
require_role(['admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $postId = (int)$_POST['post_id'];
    $action = $_POST['action'] ?? '';
    if ($action === 'approve') {
        $pdo->prepare("UPDATE posts SET status='approved', reject_reason=NULL WHERE id=?")->execute([$postId]);
        flash('success', 'Đã phê duyệt bài viết.');
    } elseif ($action === 'reject') {
        $reason = trim($_POST['reason'] ?? '');
        $pdo->prepare("UPDATE posts SET status='rejected', reject_reason=? WHERE id=?")->execute([$reason, $postId]);
        flash('success', 'Đã từ chối bài viết.');
    }
    header('Location: moderation.php');
    exit;
}

$total = (int)$pdo->query("SELECT COUNT(*) FROM posts WHERE status='pending'")->fetchColumn();
[$offset, $perPage, $page, $totalPages] = paginate($total, 10);
$posts = $pdo->query("SELECT p.*, u.full_name, u.username FROM posts p JOIN users u ON u.id=p.user_id
                       WHERE p.status='pending' ORDER BY p.created_at ASC LIMIT $perPage OFFSET $offset")->fetchAll();

$pageTitle = 'Hàng đợi duyệt bài - UTH Forum';
require __DIR__ . '/../includes/header.php';
?>
<div class="admin-layout">
<?php require __DIR__ . '/../includes/admin_nav.php'; ?>
<div class="admin-main">
<h2>Hàng đợi duyệt bài (<?= $total ?>)</h2>
<div class="box">
  <?php if (!$posts): ?><p style="color:var(--muted);">Không có bài viết nào đang chờ duyệt.</p><?php endif; ?>
  <?php foreach ($posts as $p): ?>
    <div class="post-card">
      <div class="avatar"><?= e(mb_strtoupper(mb_substr($p['full_name'] ?: $p['username'], 0, 1))) ?></div>
      <div style="flex:1;">
        <div class="post-meta">
          <strong><?= e($p['full_name'] ?: $p['username']) ?></strong>
          <span>· <?= time_ago($p['created_at']) ?></span>
        </div>
        <h3 class="post-title"><a href="../post_view.php?id=<?= (int)$p['id'] ?>" target="_blank"><?= e($p['title']) ?></a></h3>
        <div style="font-size:14px;color:var(--muted);max-height:60px;overflow:hidden;">
          <?= strip_tags($p['content']) ?>
        </div>
        <div style="display:flex;gap:8px;margin-top:10px;">
          <form method="post">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="post_id" value="<?= (int)$p['id'] ?>">
            <input type="hidden" name="action" value="approve">
            <button type="submit" class="btn btn-teal btn-sm">Phê duyệt</button>
          </form>
          <form method="post" onsubmit="return fillReason(this)">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="post_id" value="<?= (int)$p['id'] ?>">
            <input type="hidden" name="action" value="reject">
            <input type="hidden" name="reason" class="reason-field">
            <button type="submit" class="btn btn-danger btn-sm">Từ chối</button>
          </form>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
  <?php pagination_links($page, $totalPages, 'moderation.php'); ?>
</div>
</div>
</div>
<script>
function fillReason(form) {
  const reason = prompt('Lý do từ chối (không bắt buộc):', '');
  form.querySelector('.reason-field').value = reason || '';
  return true;
}
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
