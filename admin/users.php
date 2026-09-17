<?php
require_once __DIR__ . '/../includes/functions.php';
require_role(['admin']);
$admin = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $uid = (int)$_POST['user_id'];
    $action = $_POST['action'] ?? '';
    if ($uid !== $admin['id']) {
        if ($action === 'toggle_active') {
            $pdo->prepare('UPDATE users SET is_active = 1 - is_active WHERE id = ?')->execute([$uid]);
        } elseif ($action === 'set_role' && in_array($_POST['role'] ?? '', ['student','teacher','admin'], true)) {
            $pdo->prepare('UPDATE users SET role = ? WHERE id = ?')->execute([$_POST['role'], $uid]);
        }
        flash('success', 'Đã cập nhật người dùng.');
    }
    header('Location: users.php?page=' . (int)($_GET['page'] ?? 1));
    exit;
}

$total = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
[$offset, $perPage, $page, $totalPages] = paginate($total, 15);
$users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC LIMIT $perPage OFFSET $offset")->fetchAll();

$pageTitle = 'Quản lý người dùng - UTH Forum';
require __DIR__ . '/../includes/header.php';
?>
<h2>Quản lý người dùng (<?= $total ?>)</h2>
<div class="box">
  <table>
    <tr><th>Tên</th><th>Email</th><th>Vai trò</th><th>Trạng thái</th><th>Ngày tạo</th><th>Hành động</th></tr>
    <?php foreach ($users as $u): ?>
      <tr>
        <td><?= e($u['full_name'] ?: $u['username']) ?></td>
        <td><?= e($u['email']) ?></td>
        <td>
          <form method="post" style="display:flex;gap:6px;">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
            <input type="hidden" name="action" value="set_role">
            <select name="role" onchange="this.form.submit()" <?= $u['id']==$admin['id']?'disabled':'' ?>>
              <option value="student" <?= $u['role']==='student'?'selected':'' ?>>Sinh viên</option>
              <option value="teacher" <?= $u['role']==='teacher'?'selected':'' ?>>Giảng viên</option>
              <option value="admin" <?= $u['role']==='admin'?'selected':'' ?>>Quản trị viên</option>
            </select>
          </form>
        </td>
        <td><span class="tag <?= $u['is_active'] ? 'tag-approved' : 'tag-rejected' ?>"><?= $u['is_active'] ? 'Hoạt động' : 'Bị khóa' ?></span></td>
        <td><?= e(date('d/m/Y', strtotime($u['created_at']))) ?></td>
        <td>
          <?php if ($u['id'] != $admin['id']): ?>
            <form method="post" style="display:inline;">
              <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
              <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
              <input type="hidden" name="action" value="toggle_active">
              <button type="submit" class="btn btn-sm <?= $u['is_active'] ? 'btn-danger' : 'btn-teal' ?>">
                <?= $u['is_active'] ? 'Khóa' : 'Mở khóa' ?>
              </button>
            </form>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
  <?php pagination_links($page, $totalPages, 'users.php'); ?>
</div>
