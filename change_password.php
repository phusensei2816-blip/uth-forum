<?php
require_once __DIR__ . '/includes/functions.php';
require_login();

$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($current === '' || $new === '' || $confirm === '') {
        flash('error', 'Vui lòng nhập đầy đủ các trường.');
    } elseif (!password_verify($current, $user['password_hash'])) {
        flash('error', 'Mật khẩu hiện tại không đúng.');
    } elseif (strlen($new) < 6) {
        flash('error', 'Mật khẩu mới phải có ít nhất 6 ký tự.');
    } elseif ($new !== $confirm) {
        flash('error', 'Mật khẩu xác nhận không khớp.');
    } elseif (password_verify($new, $user['password_hash'])) {
        flash('error', 'Mật khẩu mới phải khác mật khẩu hiện tại.');
    } else {
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        $stmt->execute([$hash, $user['id']]);
        flash('success', 'Đổi mật khẩu thành công.');
        header('Location: ' . BASE_URL . '/profile.php');
        exit;
    }

    header('Location: ' . BASE_URL . '/change_password.php');
    exit;
}

$pageTitle = 'Đổi mật khẩu';
require __DIR__ . '/includes/header.php';
?>

<div class="security-page">
  <section class="profile-card password-card">
    <div class="password-hero">
      <div class="password-icon"><i class="fa-solid fa-key"></i></div>
      <div>
        <div class="profile-role-pill"><i class="fa-solid fa-lock"></i> Bảo mật</div>
        <h1>Đổi mật khẩu</h1>
        <p>Cập nhật mật khẩu để bảo vệ tài khoản <?= e($user['username']) ?>.</p>
      </div>
    </div>

    <form method="post" class="password-form">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">

      <div class="form-group">
        <label>Mật khẩu hiện tại</label>
        <input type="password" name="current_password" autocomplete="current-password" required>
      </div>
      <div class="form-group">
        <label>Mật khẩu mới</label>
        <input type="password" name="new_password" minlength="6" autocomplete="new-password" required>
        <small>Ít nhất 6 ký tự.</small>
      </div>
      <div class="form-group">
        <label>Xác nhận mật khẩu mới</label>
        <input type="password" name="confirm_password" minlength="6" autocomplete="new-password" required>
      </div>

      <div class="password-actions">
        <a class="btn btn-outline" href="<?= e(BASE_URL) ?>/profile.php">Quay lại hồ sơ</a>
        <button class="btn btn-teal" type="submit"><i class="fa-solid fa-check"></i> Cập nhật mật khẩu</button>
      </div>
    </form>
  </section>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
