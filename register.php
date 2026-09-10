<?php
require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) { header('Location: index.php'); exit; }

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $fullName = trim($_POST['full_name'] ?? '');
    $role     = in_array($_POST['role'] ?? '', ['student', 'teacher'], true) ? $_POST['role'] : 'student';
    $agree    = isset($_POST['terms']);

    if ($username === '' || !preg_match('/^[a-zA-Z0-9_.]{3,30}$/', $username)) {
        $errors[] = 'Tên đăng nhập phải từ 3-30 ký tự (chữ, số, dấu chấm, gạch dưới).';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email không hợp lệ.';
    }
    if (strlen($password) < 6) {
        $errors[] = 'Mật khẩu phải có ít nhất 6 ký tự.';
    }
    if (!$agree) {
        $errors[] = 'Bạn cần đồng ý với điều khoản sử dụng.';
    }

    if (!$errors) {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? OR email = ?');
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $errors[] = 'Tên đăng nhập hoặc email đã được sử dụng.';
        }
    }

    if (!$errors) {
        $stmt = $pdo->prepare('INSERT INTO users (username, email, password_hash, full_name, role) VALUES (?,?,?,?,?)');
        $stmt->execute([$username, $email, password_hash($password, PASSWORD_DEFAULT), $fullName, $role]);
        flash('success', 'Đăng ký thành công! Vui lòng đăng nhập.');
        header('Location: login.php');
        exit;
    }
}

$pageTitle = 'Đăng ký - UTH Forum';
require __DIR__ . '/includes/header.php';
?>
<section class="row" style="justify-content:center;">
  <div class="card" style="max-width:480px;width:100%;">
    <h2>Tạo tài khoản</h2>
    <?php foreach ($errors as $err): ?>
      <div class="alert alert-error"><?= e($err) ?></div>
    <?php endforeach; ?>
    <form method="post">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <div class="form-group">
        <label>Họ và tên</label>
        <input type="text" name="full_name" value="<?= e($_POST['full_name'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label>Tên đăng nhập</label>
        <input type="text" name="username" value="<?= e($_POST['username'] ?? '') ?>" required>
      </div>
      <div class="form-group">
        <label>Email</label>
        <input type="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" required>
      </div>
      <div class="form-group">
        <label>Mật khẩu</label>
        <input type="password" name="password" required>
      </div>
      <div class="form-group">
        <label>Vai trò</label>
        <select name="role">
          <option value="student">Sinh viên</option>
          <option value="teacher">Giảng viên</option>
        </select>
      </div>
      <div class="form-group check">
        <input type="checkbox" name="terms" id="terms">
        <label for="terms" style="margin:0;">Tôi đồng ý với điều khoản sử dụng và chính sách bảo mật.</label>
      </div>
      <button type="submit" class="btn btn-red" style="width:100%;">Đăng ký</button>
    </form>
    <p style="margin-top:14px;font-size:14px;">Đã có tài khoản? <a href="login.php" style="color:var(--teal);font-weight:600;">Đăng nhập</a></p>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
