<?php
require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) { header('Location: index.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'student';
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    $maxAttempts = 5;
    $windowMinutes = 15;

    $cnt = $pdo->prepare('SELECT COUNT(*) FROM login_attempts
                           WHERE ip = ? AND login = ? AND attempted_at > (NOW() - INTERVAL ' . $windowMinutes . ' MINUTE)');
    $cnt->execute([$ip, $login]);
    $recentAttempts = (int)$cnt->fetchColumn();

    if ($recentAttempts >= $maxAttempts) {
        $error = 'Bạn đã nhập sai quá nhiều lần. Vui lòng thử lại sau ' . $windowMinutes . ' phút.';
    } else {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE (username = ? OR email = ?) AND role = ? AND is_active = 1');
        $stmt->execute([$login, $login, $role]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            // Successful login: clear this login's failed-attempt history
            $pdo->prepare('DELETE FROM login_attempts WHERE ip = ? AND login = ?')->execute([$ip, $login]);
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            header('Location: index.php');
            exit;
        }

        $pdo->prepare('INSERT INTO login_attempts (ip, login) VALUES (?, ?)')->execute([$ip, $login]);
        $error = 'Tên đăng nhập hoặc mật khẩu không đúng.';
    }
}

$pageTitle = 'Đăng nhập - UTH Forum';

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; font-src https://cdnjs.cloudflare.com");
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

<!-- ================= LEFT: LOGIN ================= -->
        <section class="login-panel">

            <div class="login-brand">
                <!-- Đổi tên file nếu logo UTH của bạn có tên khác -->
                <a href="index.php">
                  <img src="img/logo.png" alt="Logo UTH">
                </a>
                <p>HỘI NHẬP - SÁNG TẠO - KIẾN THỨC - KỸ NĂNG</p>
            </div>

            <div class="login-content">

                <div class="login-heading">
                    <h1>ĐĂNG NHẬP</h1>
                </div>

                <?php if ($error): ?>
                    <div class="login-error">
                        <?= e($error) ?>
                    </div>
                <?php endif; ?>

                <form method="post" class="login-form">
                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">

                    <!-- Chọn đối tượng -->
                    <div class="login-field">
                        <label>Chọn đối tượng</label>

                        <div class="role-list">
                            <button type="button" class="role-option active" data-role="student">
                                <i class="fa-solid fa-user-graduate"></i>
                                <span>Sinh viên</span>
                            </button>

                            <button type="button" class="role-option" data-role="teacher">
                                <i class="fa-solid fa-person-chalkboard"></i>
                                <span>Giảng viên</span>
                            </button>

                            <button type="button" class="role-option" data-role="admin">
                                <i class="fa-solid fa-user"></i>
                                <span>Admin</span>
                            </button>
                        </div>
                        <!-- Phục vụ giao diện trước; backend role sẽ xử lý ở bước sau -->    
                        
                        <input type="hidden" name="role" id="role" value="student">
                    </div>

                    <!-- Thông tin đăng nhập -->
                    <div class="login-field">
                        <label for="login">Thông tin đăng nhập</label>

                        <div class="input-wrap">
                            <i class="fa-regular fa-id-badge"></i>
                            <input type="text" id="login" name="login" placeholder="Nhập MSSV" autocomplete="username" required autofocus>
                        </div>
                        <!-- <p id="login-hint" class="login-hint">Sử dụng MSSV được cấp bởi nhà trường</p> -->

                    </div>

                    <!-- Password -->
                    <div class="login-field">
                        <div class="input-wrap">
                            <i class="fa-solid fa-unlock-keyhole"></i>
                            <input type="password" id="password" name="password" placeholder="Mật khẩu" autocomplete="current-password" required>
                            <button type="button" class="password-toggle" id="passwordToggle" aria-label="Hiện mật khẩu">
                                <i class="fa-regular fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Remember + forgot -->
                    <div class="login-options">
                        <label class="remember-me">
                            <input type="checkbox" name="remember">
                            <span>Ghi nhớ đăng nhập</span>
                        </label>

                        <a href="#" class="forgot-link">Quên mật khẩu?</a>
                    </div>

                    <button type="submit" class="login-submit">Đăng nhập</button>
                </form>

                <!-- Social -->
                <div class="social-divider">
                    <span></span>
                    <p>hoặc đăng nhập với</p>
                    <span></span>
                </div>

                <div class="social-list">
                    <button type="button" class="social-btn">
                        <img src="img/google.png" alt="Google" class="social-icon">
                        Google
                    </button>

                    <button type="button" class="social-btn">
                        <img src="img/microsoft.png" alt="microsoft" class="social-icon">
                        Microsoft
                    </button>
                </div>

                <p class="register-text">
                    Chưa có tài khoản?
                    <a href="register.php">Đăng ký ngay</a>
                </p>

            </div>
        </section>

        <!-- ================= RIGHT: CAMPUS ================= -->
        <section class="login-visual">

            <!-- Đổi tên file nếu ảnh trường của bạn có tên khác -->
            <img class="campus-image" src="img/uth-campus.jpg" alt="Cơ sở UTH">

            <div class="visual-overlay"></div>

            <div class="visual-logo">
                <span>UTH</span>
                <small>UNIVERSITY OF TRANSPORT<br>HO CHI MINH CITY</small>
            </div>

            <div class="visual-content">
                <p class="visual-label">WELCOME TO</p>
                <h2 class="visual-label">FORUM UTH</h2>
                <h2>Kết nối sinh viên<br>và giảng viên UTH</h2>
                <p>
                    Cùng chia sẻ kiến thức, tài liệu,thông tin học tập
                    <br>và xây dựng cộng đồng UTH năng động.
                </p>
            </div>

            <div class="visual-features">
                <div class="feature-item">
                    <i class="fa-solid fa-users"></i>
                    <div class="feature-text">
                        <h4>Hội nhập</h4>
                        <p>Mở rộng kết nối</p>
                    </div>
                </div>

                <div class="feature-item">
                    <i class="fa-solid fa-lightbulb"></i>
                    <div class="feature-text">
                        <h4>Sáng tạo</h4>
                        <p>Khơi nguồn ý tưởng</p>
                    </div>
                </div>

                <div class="feature-item">
                    <i class="fa-solid fa-book"></i>
                    <div class="feature-text">
                        <h4>Kiến thức - Kỹ năng</h4>
                        <p>Nâng tầm tương lai</p>
                    </div>
                </div>
            </div>

        </section>

    </div>
</main>

<script>
    // ================= ROLE SELECT =================
    const roleButtons = document.querySelectorAll('.role-option');
    const roleInput = document.getElementById('role');
    const loginInput = document.getElementById('login');
    // const loginHint = document.getElementById('login-hint');

    roleButtons.forEach(button => {
        button.addEventListener('click', () => {
            roleButtons.forEach(item => item.classList.remove('active'));
            button.classList.add('active');

            const role = button.dataset.role;
            roleInput.value = role;

            if (role === 'student') {
                loginInput.placeholder = 'Nhập MSSV';
                // loginHint.textContent = 'Sử dụng MSSV được cấp bởi nhà trường';
            }

            if (role === 'teacher') {
                loginInput.placeholder = 'Nhập MSGV';
                // loginHint.textContent = 'Sử dụng MSGV được cấp bởi nhà trường';
            }

            if (role === 'admin') {
                loginInput.placeholder = 'Tên đăng nhập Admin';
                // loginHint.textContent = 'Sử dụng tài khoản Admin được cấp bởi hệ thống';
            }
        });
    });

    // ================= SHOW / HIDE PASSWORD =================
    const passwordInput = document.getElementById('password');
    const passwordToggle = document.getElementById('passwordToggle');

    passwordToggle.addEventListener('click', () => {
        const isPassword = passwordInput.type === 'password';

        passwordInput.type = isPassword ? 'text' : 'password';
        passwordToggle.innerHTML = isPassword ? '<i class="fa-regular fa-eye-slash"></i>' : '<i class="fa-regular fa-eye"></i>';
        passwordToggle.setAttribute(
            'aria-label',
            isPassword ? 'Ẩn mật khẩu' : 'Hiện mật khẩu'
        );
    });
</script>

</body>
</html>
