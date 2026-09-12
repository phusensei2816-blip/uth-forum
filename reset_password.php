<?php
require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

$token = trim($_GET['token'] ?? $_POST['token'] ?? '');

if ($token === '') {
    $error = 'Liên kết đặt lại mật khẩu không hợp lệ.';
} else {
    $stmt = $pdo->prepare(
        'SELECT pr.*, u.email
         FROM password_resets pr
         INNER JOIN users u ON u.id = pr.user_id
         WHERE pr.token = ? AND pr.expires_at > NOW() AND u.is_active = 1'
    );
    $stmt->execute([$token]);
    $reset = $stmt->fetch();

    if (!$reset) {
        $error = 'Liên kết đặt lại mật khẩu không hợp lệ hoặc đã hết hạn.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
    csrf_check();

    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';

    if ($password === '') {
        $error = 'Vui lòng nhập mật khẩu mới.';
    } elseif (strlen($password) < 6) {
        $error = 'Mật khẩu phải có ít nhất 6 ký tự.';
    } elseif ($password !== $passwordConfirm) {
        $error = 'Mật khẩu xác nhận không khớp.';
    } else {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare(
            'UPDATE users SET password_hash = ? WHERE id = ?'
        );
        $stmt->execute([$passwordHash, $reset['user_id']]);

        $pdo->prepare('DELETE FROM password_resets WHERE user_id = ?')
            ->execute([$reset['user_id']]);

        $success = 'Đặt lại mật khẩu thành công.';
    }
}

$pageTitle = 'Đặt lại mật khẩu - UTH Forum';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/style_Login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>

<body class="login-page">

<main class="login-shell">
    <div class="login-card">

        <section class="login-panel">

            <div class="login-brand">
                <a href="index.php">
                    <img src="img/logo.png" alt="Logo UTH">
                </a>
                <p>HỘI NHẬP - SÁNG TẠO - KIẾN THỨC - KỸ NĂNG</p>
            </div>

            <div class="login-content">

                <div class="login-heading">
                    <h1>ĐẶT LẠI MẬT KHẨU</h1>
                </div>

                <?php if ($error): ?>
                    <div class="login-error">
                        <?= e($error) ?>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="login-success">
                        <?= e($success) ?>
                    </div>

                    <p class="register-text">
                        <a href="login.php">Quay lại đăng nhập</a>
                    </p>
                <?php elseif (!$error): ?>

                    <form method="post" class="login-form">

                        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="token" value="<?= e($token) ?>">

                        <div class="login-field">
                            <label for="password">Mật khẩu mới</label>

                            <div class="input-wrap">
                                <i class="fa-solid fa-lock"></i>
                                <input type="password" id="password" name="password" placeholder="Nhập mật khẩu mới" autocomplete="new-password" required>
                                <button type="button" class="password-toggle" id="passwordToggle" aria-label="Hiện mật khẩu">
                                    <i class="fa-regular fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="login-field">
                            <label for="password_confirm">Xác nhận mật khẩu</label>

                            <div class="input-wrap">
                                <i class="fa-solid fa-lock"></i>
                                <input type="password" id="password_confirm" name="password_confirm"  placeholder="Nhập lại mật khẩu mới" autocomplete="new-password" required>
                                <button type="button" class="password-toggle" id="passwordConfirmToggle" aria-label="Hiện mật khẩu">
                                    <i class="fa-regular fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <button type="submit" class="login-submit">
                            Đặt lại mật khẩu
                        </button>

                    </form>

                    <p class="register-text">
                        Nhớ mật khẩu?
                        <a href="login.php">Đăng nhập</a>
                    </p>

                <?php endif; ?>

            </div>
        </section>

        <section class="login-visual">

            <img class="campus-image" src="img/uth-campus.jpg" alt="Cơ sở UTH">

            <div class="visual-overlay"></div>

            <div class="visual-logo">
                <span>UTH</span>
                <small>UNIVERSITY OF TRANSPORT<br>HO CHI MINH CITY</small>
            </div>

            <div class="visual-content">
                <p class="visual-label">WELCOME TO</p>
                <h2 class="visual-label">FORUM UTH</h2>
                <h2>Tạo mật khẩu<br>mới cho tài khoản</h2>
                <p>
                    Bảo vệ tài khoản của bạn bằng một mật khẩu mới.
                    <br>Hãy chọn mật khẩu đủ an toàn và dễ nhớ.
                </p>
            </div>

            <div class="visual-features">

                <div class="feature-item">
                    <i class="fa-solid fa-shield-halved"></i>
                    <div class="feature-text">
                        <h4>Bảo mật</h4>
                        <p>Bảo vệ tài khoản</p>
                    </div>
                </div>

                <div class="feature-item">
                    <i class="fa-solid fa-key"></i>
                    <div class="feature-text">
                        <h4>Mật khẩu mới</h4>
                        <p>An toàn hơn</p>
                    </div>
                </div>

                <div class="feature-item">
                    <i class="fa-solid fa-circle-check"></i>
                    <div class="feature-text">
                        <h4>Hoàn tất</h4>
                        <p>Đăng nhập lại</p>
                    </div>
                </div>

            </div>

        </section>

    </div>
</main>

<script>
    // ================= SHOW / HIDE PASSWORD =================
    const passwordInput = document.getElementById('password');
    const passwordToggle = document.getElementById('passwordToggle');

    if (passwordToggle) {
        passwordToggle.addEventListener('click', () => {
            const isPassword = passwordInput.type === 'password';

            passwordInput.type = isPassword ? 'text' : 'password';
            passwordToggle.innerHTML = isPassword
                ? '<i class="fa-regular fa-eye-slash"></i>'
                : '<i class="fa-regular fa-eye"></i>';

            passwordToggle.setAttribute(
                'aria-label',
                isPassword ? 'Ẩn mật khẩu' : 'Hiện mật khẩu'
            );
        });
    }

    // ================= SHOW / HIDE CONFIRM PASSWORD =================
    const passwordConfirmInput = document.getElementById('password_confirm');
    const passwordConfirmToggle = document.getElementById('passwordConfirmToggle');

    if (passwordConfirmToggle) {
        passwordConfirmToggle.addEventListener('click', () => {
            const isPassword = passwordConfirmInput.type === 'password';

            passwordConfirmInput.type = isPassword ? 'text' : 'password';
            passwordConfirmToggle.innerHTML = isPassword
                ? '<i class="fa-regular fa-eye-slash"></i>'
                : '<i class="fa-regular fa-eye"></i>';

            passwordConfirmToggle.setAttribute(
                'aria-label',
                isPassword ? 'Ẩn mật khẩu' : 'Hiện mật khẩu'
            );
        });
    }
</script>

</body>
</html>