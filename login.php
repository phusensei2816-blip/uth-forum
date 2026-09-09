<?php
require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) { header('Location: index.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare('SELECT * FROM users WHERE (username = ? OR email = ?) AND is_active = 1');
    $stmt->execute([$login, $login]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        header('Location: index.php');
        exit;
    }
    $error = 'Tên đăng nhập hoặc mật khẩu không đúng.';
}

$pageTitle = 'Đăng nhập - UTH Forum';
require __DIR__ . '/includes/header.php';
?>
<section class="row" style="justify-content:center;">
  <div class="card" style="max-width:420px;width:100%;">
    <h2>Đăng nhập</h2>
    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
    <form method="post">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <div class="form-group">
        <label>Tên đăng nhập hoặc email</label>
        <input type="text" name="login" required autofocus>
      </div>
      <div class="form-group">
        <label>Mật khẩu</label>
        <input type="password" name="password" required>
      </div>
      <button type="submit" class="btn btn-red" style="width:100%;">Đăng nhập</button>
    </form>
    <p style="margin-top:14px;font-size:14px;">Chưa có tài khoản? <a href="register.php" style="color:var(--teal);font-weight:600;">Đăng ký ngay</a></p>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
