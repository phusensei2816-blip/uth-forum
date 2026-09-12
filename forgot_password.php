<?php
require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $email = trim($_POST['email'] ?? '');

    if ($email === '') {
        $error = 'Vui lòng nhập email.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email không hợp lệ.';
    } else {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? AND is_active = 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            $pdo->prepare('DELETE FROM password_resets WHERE user_id = ?')
                ->execute([$user['id']]);

            $token = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', time() + 3600);

            $stmt = $pdo->prepare(
                'INSERT INTO password_resets (user_id, token, expires_at)
                 VALUES (?, ?, ?)'
            );
            $stmt->execute([$user['id'], $token, $expiresAt]);

            $resetLink = 'http://' . $_SERVER['HTTP_HOST']
                . '/uth-forum-main/reset_password.php?token=' . $token;

            $success = 'Yêu cầu đặt lại mật khẩu đã được tạo.';

            // Hiển thị link để kiểm tra chức năng trên máy local.
            // Sau này sẽ thay bằng gửi email thật.
            $success .= '<br><br><a href="' . e($resetLink) . '" class="reset-link">'
                . 'Nhấn vào đây để đặt lại mật khẩu</a>';
        } else {
            $error = 'Email không tồn tại hoặc tài khoản đã bị khóa.';
        }
    }
}

$pageTitle = 'Quên mật khẩu - UTH Forum';
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
                    <h1>QUÊN MẬT KHẨU</h1>
                </div>

                <?php if ($error): ?>
                    <div class="login-error">
                        <?= e($error) ?>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="login-success">
                        <?= $success ?>
                    </div>
                <?php endif; ?>

                <form method="post" class="login-form">

                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">

                    <div class="login-field">
                        <label for="email">Email</label>

                        <div class="input-wrap">
                            <i class="fa-regular fa-envelope"></i>
                            <input type="email" id="email" name="email" placeholder="Nhập email của bạn" autocomplete="email" required autofocus>
                        </div>
                    </div>

                    <button type="submit" class="login-submit">
                        Gửi yêu cầu đặt lại mật khẩu
                    </button>

                </form>

                <p class="register-text">
                    Nhớ mật khẩu?
                    <a href="login.php">Đăng nhập</a>
                </p>

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
                <h2>Khôi phục tài khoản<br>của bạn</h2>
                <p>
                    Đừng lo nếu bạn quên mật khẩu.
                    <br>Chúng tôi sẽ giúp bạn lấy lại quyền truy cập.
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
                        <h4>Khôi phục</h4>
                        <p>Đặt lại mật khẩu</p>
                    </div>
                </div>

                <div class="feature-item">
                    <i class="fa-solid fa-circle-check"></i>
                    <div class="feature-text">
                        <h4>Đơn giản</h4>
                        <p>Thao tác nhanh chóng</p>
                    </div>
                </div>

            </div>

        </section>

    </div>
</main>

</body>
</html>