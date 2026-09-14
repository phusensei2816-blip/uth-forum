<?php
require_once __DIR__ . '/../includes/functions.php';
require_role(['admin']);

$totalUsers   = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$totalStudents= (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='student'")->fetchColumn();
$totalTeachers= (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='teacher'")->fetchColumn();
$totalPosts   = (int)$pdo->query('SELECT COUNT(*) FROM posts')->fetchColumn();
$pendingPosts = (int)$pdo->query("SELECT COUNT(*) FROM posts WHERE status='pending'")->fetchColumn();
$totalClasses = (int)$pdo->query('SELECT COUNT(*) FROM classes')->fetchColumn();
$totalFiles   = (int)$pdo->query('SELECT COUNT(*) FROM files')->fetchColumn();
$storageUsed  = (int)$pdo->query('SELECT COALESCE(SUM(filesize),0) FROM files')->fetchColumn();
$todayVisits  = (int)$pdo->query("SELECT COALESCE(hits,0) FROM visits WHERE visited_at = CURDATE()")->fetchColumn();
$totalVisits  = (int)$pdo->query('SELECT COALESCE(SUM(hits),0) FROM visits')->fetchColumn();

$recentVisits = $pdo->query('SELECT * FROM visits ORDER BY visited_at DESC LIMIT 7')->fetchAll();

$pageTitle = 'Bảng điều khiển quản trị - UTH Forum';
require __DIR__ . '/../includes/header.php';
?>
<div class="admin-layout">
<?php require __DIR__ . '/../includes/admin_nav.php'; ?>
<div class="admin-main">
<h2>Bảng điều khiển quản trị</h2>
<div class="stat-grid">
  <div class="stat-box"><i class="fa-solid fa-users stat-icon"></i><div class="num"><?= $totalUsers ?></div><div class="label">Tổng người dùng</div></div>
  <div class="stat-box"><i class="fa-solid fa-user-graduate stat-icon"></i><div class="num"><?= $totalStudents ?></div><div class="label">Sinh viên</div></div>
  <div class="stat-box"><i class="fa-solid fa-chalkboard-user stat-icon"></i><div class="num"><?= $totalTeachers ?></div><div class="label">Giảng viên</div></div>
  <div class="stat-box"><i class="fa-solid fa-school stat-icon"></i><div class="num"><?= $totalClasses ?></div><div class="label">Lớp học</div></div>
  <div class="stat-box"><i class="fa-solid fa-file-lines stat-icon"></i><div class="num"><?= $totalPosts ?></div><div class="label">Tổng bài viết</div></div>
  <div class="stat-box"><i class="fa-solid fa-flag stat-icon"></i><div class="num" style="color:var(--red);"><?= $pendingPosts ?></div><div class="label">Chờ duyệt</div></div>
  <div class="stat-box"><i class="fa-solid fa-folder-open stat-icon"></i><div class="num"><?= $totalFiles ?></div><div class="label">Tệp đã tải lên</div></div>
  <div class="stat-box"><i class="fa-solid fa-database stat-icon"></i><div class="num"><?= round($storageUsed/1024/1024, 1) ?> MB</div><div class="label">Dung lượng lưu trữ</div></div>
  <div class="stat-box"><i class="fa-solid fa-eye stat-icon"></i><div class="num"><?= $todayVisits ?></div><div class="label">Lượt truy cập hôm nay</div></div>
  <div class="stat-box"><i class="fa-solid fa-chart-line stat-icon"></i><div class="num"><?= $totalVisits ?></div><div class="label">Tổng lượt truy cập</div></div>
</div>

<div class="row">
  <div class="box" style="flex:1;min-width:280px;">
    <h3>Truy cập 7 ngày gần nhất</h3>
    <table>
      <tr><th>Ngày</th><th>Lượt truy cập</th></tr>
      <?php foreach ($recentVisits as $v): ?>
        <tr><td><?= e(date('d/m/Y', strtotime($v['visited_at']))) ?></td><td><?= (int)$v['hits'] ?></td></tr>
      <?php endforeach; ?>
      <?php if (!$recentVisits): ?><tr><td colspan="2" style="color:var(--muted);">Chưa có dữ liệu.</td></tr><?php endif; ?>
    </table>
  </div>
  <div class="box" style="flex:1;min-width:280px;">
    <h3>Thao tác nhanh</h3>
    <p><a href="moderation.php" class="btn btn-red btn-sm">Duyệt bài viết (<?= $pendingPosts ?>)</a></p>
    <p><a href="users.php" class="btn btn-teal btn-sm">Quản lý người dùng</a></p>
    <p><a href="files.php" class="btn btn-outline btn-sm">Quản lý tệp tin</a></p>
  </div>
</div>
</div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
