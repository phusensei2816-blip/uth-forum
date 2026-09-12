<?php
require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) { header('Location: index.php'); exit; }

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $username = trim($_POST['username'] ?? '');
    $userCode = trim($_POST['user_code'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    // $confirmPassword = $_POST['confirm_password'] ?? '';
    $role     = in_array($_POST['role'] ?? '', ['student', 'teacher'], true) ? $_POST['role'] : 'student';
    $agree    = isset($_POST['terms']);

    if ($username === '' || !preg_match('/^[a-zA-Z0-9_.]{3,30}$/', $username)) {
        $errors[] = 'Tên đăng nhập phải từ 3-30 ký tự (chữ, số, dấu chấm, gạch dưới).';
    }
    if ($userCode === '') {
        $errors[] = 'Vui lòng nhập MSSV / MSGV.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email không hợp lệ.';
    }
    if (strlen($password) < 6) {
        $errors[] = 'Mật khẩu phải có ít nhất 6 ký tự.';
    }
    // if ($password !== $confirmPassword) {
    //     $errors[] = 'Mật khẩu xác nhận không khớp.';
    // }
    if (!$agree) {
        $errors[] = 'Bạn cần đồng ý với điều khoản sử dụng.';
    }

    if (!$errors) {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? OR user_code = ? OR email = ?');
        $stmt->execute([$username, $userCode, $email]);
        if ($stmt->fetch()) {
            $errors[] = 'Tên đăng nhập, MSSV / MSGV hoặc email đã được sử dụng.';
        }
    }

    if (!$errors) {
        $stmt = $pdo->prepare('INSERT INTO users (username, user_code, email, password_hash, role) VALUES (?,?,?,?,?)');
        $stmt->execute([$username, $userCode, $email, password_hash($password, PASSWORD_DEFAULT), $role]);
        flash('success', 'Đăng ký thành công! Vui lòng đăng nhập.');
        header('Location: login.php');
        exit;
    }
}

$pageTitle = 'Đăng ký - UTH Forum';
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

        <!-- ================= LEFT: REGISTER ================= -->
        <section class="login-panel">

            <div class="login-brand">
                <a href="index.php">
                    <img src="img/logo.png" alt="Logo UTH">
                </a>
                <p>HỘI NHẬP - SÁNG TẠO - KIẾN THỨC - KỸ NĂNG</p>
            </div>

            <div class="login-content">

                <div class="login-heading">
                    <h1>ĐĂNG KÝ</h1>
                </div>

                <?php foreach ($errors as $err): ?>
                    <div class="login-error">
                        <?= e($err) ?>
                    </div>
                <?php endforeach; ?>

                <form method="post" class="login-form">
                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">

                    <!-- Tên đăng nhập -->
                    <div class="login-field">
                        <label for="username">Tên đăng nhập</label>

                        <div class="input-wrap">
                            <i class="fa-regular fa-user"></i>
                            <input type="text" id="username" name="username" placeholder="Nhập tên đăng nhập"
                                value="<?= e($_POST['username'] ?? '') ?>"
                                autocomplete="username"
                                required>
                        </div>
                    </div>

                    <div class="register-row">
                      <!-- MSSV / MSGV -->
                      <div class="login-field">
                          <label for="user_code">MSSV / MSGV</label>

                          <div class="input-wrap">
                              <i class="fa-regular fa-id-badge"></i>
                              <input type="text" id="user_code" name="user_code" placeholder="Nhập MSSV"
                                  value="<?= e($_POST['user_code'] ?? '') ?>"
                                  required>
                          </div>
                      </div>

                      <!-- Email -->
                      <div class="login-field">
                          <label for="email">Email</label>

                          <div class="input-wrap">
                              <i class="fa-regular fa-envelope"></i>
                              <input type="email" id="email" name="email" placeholder="Nhập email"
                                  value="<?= e($_POST['email'] ?? '') ?>"
                                  autocomplete="email"
                                  required>
                          </div>
                      </div>
                    </div>

                    <!-- Mật khẩu -->
                    <div class="login-field">
                        <label for="password">Mật khẩu</label>

                        <div class="input-wrap">
                            <i class="fa-solid fa-unlock-keyhole"></i>
                            <input type="password" id="password" name="password" placeholder="Nhập mật khẩu" autocomplete="new-password" required>

                            <button
                                type="button"
                                class="password-toggle"
                                id="passwordToggle"
                                aria-label="Hiện mật khẩu">
                                <i class="fa-regular fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Xác nhận mật khẩu
                    <div class="login-field">
                        <label for="confirm_password">Xác nhận mật khẩu</label>

                        <div class="input-wrap">
                            <i class="fa-solid fa-lock"></i>
                            <input type="password" id="confirm_password" name="confirm_password" placeholder="Nhập lại mật khẩu" autocomplete="new-password" required>

                            <button type="button" class="password-toggle" id="confirmPasswordToggle" aria-label="Hiện mật khẩu">
                                <i class="fa-regular fa-eye"></i>
                            </button>
                        </div>
                    </div> -->

                    <!-- Vai trò -->
                    <div class="login-field">
                        <label for="role">Đối tượng</label>

                        <div class="input-wrap">
                            <select name="role" id="role">
                                <option value="student" <?= ($_POST['role'] ?? 'student') === 'student' ? 'selected' : '' ?>>
                                    Sinh viên
                                </option>
                                <option value="teacher" <?= ($_POST['role'] ?? '') === 'teacher' ? 'selected' : '' ?>>
                                    Giảng viên
                                </option>
                            </select>
                        </div>
                    </div>

                    <!-- Điều khoản -->
                    <div class="login-options register-terms">
                        <label class="remember-me">
                            <input type="checkbox" name="terms" id="terms" <?= isset($_POST['terms']) ? 'checked' : '' ?>>
                            <span>Tôi đồng ý với điều khoản sử dụng và chính sách bảo mật.</span>
                        </label>
                    </div>

                    <button type="submit" class="login-submit">Đăng ký</button>

                </form>

                <p class="register-text">
                    Đã có tài khoản?
                    <a href="login.php">Đăng nhập ngay</a>
                </p>

            </div>
        </section>


        <!-- ================= RIGHT: CAMPUS ================= -->
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
    // ================= SHOW / HIDE PASSWORD =================

    const passwordInput = document.getElementById('password');
    const passwordToggle = document.getElementById('passwordToggle');

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


    // ================= SHOW / HIDE CONFIRM PASSWORD =================

    const confirmPasswordInput = document.getElementById('confirm_password');
    const confirmPasswordToggle = document.getElementById('confirmPasswordToggle');

    confirmPasswordToggle.addEventListener('click', () => {
        const isPassword = confirmPasswordInput.type === 'password';

        confirmPasswordInput.type = isPassword ? 'text' : 'password';

        confirmPasswordToggle.innerHTML = isPassword
            ? '<i class="fa-regular fa-eye-slash"></i>'
            : '<i class="fa-regular fa-eye"></i>';

        confirmPasswordToggle.setAttribute(
            'aria-label',
            isPassword ? 'Ẩn mật khẩu' : 'Hiện mật khẩu'
        );
    });
</script>

</body>
</html>