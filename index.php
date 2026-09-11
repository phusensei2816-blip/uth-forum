<?php
require_once __DIR__ . '/includes/functions.php';
log_visit();

$q = trim($_GET['q'] ?? '');
$where = "p.status = 'approved'";
$params = [];
if ($q !== '') {
    $where .= ' AND (p.title LIKE ? OR p.content LIKE ?)';
    $params[] = "%$q%";
    $params[] = "%$q%";
}

$total = $pdo->prepare("SELECT COUNT(*) FROM posts p WHERE $where");
$total->execute($params);
$totalRows = (int)$total->fetchColumn();
[$offset, $perPage, $page, $totalPages] = paginate($totalRows, 8);

$sql = "SELECT p.*, u.username, u.full_name, u.role,
               (SELECT COUNT(*) FROM comments c WHERE c.post_id = p.id) AS comment_count,
               (SELECT COUNT(*) FROM likes l WHERE l.post_id = p.id) AS like_count
        FROM posts p JOIN users u ON u.id = p.user_id
        WHERE $where
        ORDER BY p.is_announcement DESC, p.created_at DESC
        LIMIT $perPage OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$posts = $stmt->fetchAll();

// Sidebar data
$topClasses = $pdo->query("SELECT c.id, c.name, COUNT(cm.id) AS members
    FROM classes c LEFT JOIN class_members cm ON cm.class_id = c.id
    GROUP BY c.id ORDER BY members DESC LIMIT 5")->fetchAll();

$topContributors = $pdo->query("SELECT u.username, u.full_name, COUNT(p.id) AS posts
    FROM users u JOIN posts p ON p.user_id = u.id AND p.status='approved'
    GROUP BY u.id ORDER BY posts DESC LIMIT 5")->fetchAll();

$pageTitle = 'Diễn đàn UTHer';
require __DIR__ . '/includes/header.php';
?>
<div class="row">
  <section class="left" style="flex:3;min-width:0;">
    <h2><?= $q !== '' ? 'Kết quả tìm kiếm: "' . e($q) . '"' : 'Bảng tin cộng đồng' ?></h2>
    <div class="box">
      <?php if (!$posts): ?>
        <p style="color:var(--muted);">Chưa có bài viết nào.</p>
      <?php endif; ?>
      <?php foreach ($posts as $p): ?>
        <div class="post-card">
          <div class="avatar"><?= e(mb_strtoupper(mb_substr($p['full_name'] ?: $p['username'], 0, 1))) ?></div>
          <div style="flex:1;min-width:0;">
            <div class="post-meta">
              <strong><?= e($p['full_name'] ?: $p['username']) ?></strong>
              <span><?= e(role_label($p['role'])) ?></span>
              <span>· <?= time_ago($p['created_at']) ?></span>
              <?php if ($p['is_announcement']): ?><span class="tag tag-urgent">Khẩn cấp</span><?php endif; ?>
            </div>
            <h3 class="post-title"><a href="post_view.php?id=<?= (int)$p['id'] ?>"><?= e($p['title']) ?></a></h3>
            <div class="post-actions">
              <span><i class="fa-solid fa-heart"></i> <?= (int)$p['like_count'] ?></span>
              <span><i class="fa-solid fa-comment"></i> <?= (int)$p['comment_count'] ?></span>
              <a href="post_view.php?id=<?= (int)$p['id'] ?>">Xem chi tiết</a>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
      <?php pagination_links($page, $totalPages, 'index.php' . ($q !== '' ? '?q=' . urlencode($q) : '')); ?>
    </div>
  </section>

  <aside class="sidebar" style="flex:1;min-width:260px;">
    <div class="box">
      <h3>Lớp học nổi bật</h3>
      <?php foreach ($topClasses as $c): ?>
        <div class="inner-box">
          <div class="avatar-sm"><?= e(mb_strtoupper(mb_substr($c['name'], 0, 1))) ?></div>
          <div class="details" style="font-size:13px;">
            <div><?= e($c['name']) ?></div>
            <span style="color:var(--muted);"><?= (int)$c['members'] ?> thành viên</span>
          </div>
        </div>
      <?php endforeach; ?>
      <?php if (!$topClasses): ?><p style="color:var(--muted);font-size:13px;">Chưa có lớp học nào.</p><?php endif; ?>
    </div>
    <div class="box">
      <h3>Đóng góp nhiều nhất</h3>
      <?php foreach ($topContributors as $c): ?>
        <div class="inner-box">
          <div class="avatar-sm"><?= e(mb_strtoupper(mb_substr($c['full_name'] ?: $c['username'], 0, 1))) ?></div>
          <div class="details" style="font-size:13px;">
            <div><?= e($c['full_name'] ?: $c['username']) ?></div>
            <span style="color:var(--muted);"><?= (int)$c['posts'] ?> bài viết</span>
          </div>
        </div>
      <?php endforeach; ?>
      <?php if (!$topContributors): ?><p style="color:var(--muted);font-size:13px;">Chưa có dữ liệu.</p><?php endif; ?>
    </div>
  </aside>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
