<?php
require_once __DIR__ . '/../includes/functions.php';
require_role(['admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $fileId = (int)$_POST['file_id'];
    $stmt = $pdo->prepare('SELECT * FROM files WHERE id = ?');
    $stmt->execute([$fileId]);
    $file = $stmt->fetch();
    if ($file) {
        @unlink(UPLOAD_DIR . $file['stored_name']);
        $pdo->prepare('DELETE FROM files WHERE id = ?')->execute([$fileId]);
        flash('success', 'Đã xóa tệp.');
    }
    header('Location: files.php?page=' . (int)($_GET['page'] ?? 1));
    exit;
}

$total = (int)$pdo->query('SELECT COUNT(*) FROM files')->fetchColumn();
[$offset, $perPage, $page, $totalPages] = paginate($total, 15);
$files = $pdo->query("SELECT f.*, u.full_name, u.username, c.name AS class_name
                       FROM files f JOIN users u ON u.id=f.uploader_id
                       LEFT JOIN classes c ON c.id=f.class_id
                       ORDER BY f.created_at DESC LIMIT $perPage OFFSET $offset")->fetchAll();
$storageUsed = (int)$pdo->query('SELECT COALESCE(SUM(filesize),0) FROM files')->fetchColumn();

$pageTitle = 'Quản lý tệp tin - UTH Forum';
require __DIR__ . '/../includes/header.php';
?>
<div class="admin-layout">
<?php require __DIR__ . '/../includes/admin_nav.php'; ?>
<div class="admin-main">
<h2>Quản lý tệp tin</h2>
<p style="color:var(--muted);">Tổng dung lượng đã sử dụng: <strong><?= round($storageUsed/1024/1024, 1) ?> MB</strong> (<?= $total ?> tệp)</p>
<div class="box">
  <table>
    <tr><th>Tên tệp</th><th>Người tải lên</th><th>Lớp</th><th>Dung lượng</th><th>Ngày</th><th></th></tr>
    <?php foreach ($files as $f): ?>
      <tr>
        <td><a href="../<?= e(UPLOAD_URL . $f['stored_name']) ?>" target="_blank"><?= e($f['original_name']) ?></a></td>
        <td><?= e($f['full_name'] ?: $f['username']) ?></td>
        <td><?= e($f['class_name'] ?: '—') ?></td>
        <td><?= round($f['filesize']/1024) ?> KB</td>
        <td><?= e(date('d/m/Y', strtotime($f['created_at']))) ?></td>
        <td>
          <form method="post" onsubmit="return confirm('Xóa tệp này?')">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="file_id" value="<?= (int)$f['id'] ?>">
            <button type="submit" class="btn btn-danger btn-sm">Xóa</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$files): ?><tr><td colspan="6" style="color:var(--muted);">Chưa có tệp nào.</td></tr><?php endif; ?>
  </table>
  <?php pagination_links($page, $totalPages, 'files.php'); ?>
</div>
</div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
