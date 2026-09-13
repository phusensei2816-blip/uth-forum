<?php
require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) { header('Location: index.php'); exit; }

$fieldErrors = [];
$old = ['full_name' => '', 'username' => '', 'email' => '', 'role' => 'student'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $old['full_name'] = trim($_POST['full_name'] ?? '');
    $old['username']  = trim($_POST['username'] ?? '');
    $old['email']     = trim($_POST['email'] ?? '');
    $old['role']      = in_array($_POST['role'] ?? '', ['student', 'teacher'], true) ? $_POST['role'] : 'student';
    $password         = $_POST['password'] ?? '';
    $agree            = isset($_POST['terms']);

    if ($old['username'] === '') {
        $fieldErrors['username'] = 'Vui lòng nhập tên đăng nhập.';
    } elseif (!preg_match('/^[a-zA-Z0-9_.]{3,30}$/', $old['username'])) {
        $fieldErrors['username'] = 'Từ 3-30 ký tự: chữ, số, dấu chấm, gạch dưới.';
    }

    if ($old['email'] === '') {
        $fieldErrors['email'] = 'Vui lòng nhập email.';
    } elseif (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $fieldErrors['email'] = 'Email không hợp lệ.';
    }

    if (strlen($password) < 6) {
        $fieldErrors['password'] = 'Mật khẩu phải có ít nhất 6 ký tự.';
    }

    if (!$agree) {
        $fieldErrors['terms'] = 'Bạn cần đồng ý với điều khoản sử dụng.';
    }

    if (!$fieldErrors) {
        $stmt = $pdo->prepare('SELECT username, email FROM users WHERE username = ? OR email = ?');
        $stmt->execute([$old['username'], $old['email']]);
        $existing = $stmt->fetch();
        if ($existing) {
            if ($existing['username'] === $old['username']) {
                $fieldErrors['username'] = 'Tên đăng nhập này đã được sử dụng.';
            } else {
                $fieldErrors['email'] = 'Email này đã được sử dụng.';
            }
        }
    }

    if (!$fieldErrors) {
        $stmt = $pdo->prepare('INSERT INTO users (username, email, password_hash, full_name, role) VALUES (?,?,?,?,?)');
        $stmt->execute([$old['username'], $old['email'], password_hash($password, PASSWORD_DEFAULT), $old['full_name'], $old['role']]);
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
                <a href="index.php"><img src="img/logo.png" alt="Logo UTH"></a>
                <p>HỘI NHẬP - SÁNG TẠO - KIẾN THỨC - KỸ NĂNG</p>
            </div>

            <div class="login-content">

                <div class="login-heading">
                    <h1>ĐĂNG KÝ</h1>
                </div>

                <form method="post" class="login-form">
                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">

                    <!-- Vai trò -->
                    <div class="login-field">
                        <label>Bạn là</label>
                        <div class="role-list">
                            <button type="button" class="role-option <?= $old['role'] === 'student' ? 'active' : '' ?>" data-role="student">
                                <i class="fa-solid fa-user-graduate"></i>
                                <span>Sinh viên</span>
                            </button>
                            <button type="button" class="role-option <?= $old['role'] === 'teacher' ? 'active' : '' ?>" data-role="teacher">
                                <i class="fa-solid fa-person-chalkboard"></i>
                                <span>Giảng viên</span>
                            </button>
                        </div>
                        <input type="hidden" name="role" id="role" value="<?= e($old['role']) ?>">
                    </div>

                    <!-- Họ tên -->
                    <div class="login-field">
                        <label for="full_name">Họ và tên</label>
                        <div class="input-wrap">
                            <i class="fa-regular fa-id-card"></i>
                            <input type="text" id="full_name" name="full_name" placeholder="Nguyễn Văn A"
                                   value="<?= e($old['full_name']) ?>" autocomplete="name">
                        </div>
                    </div>

                    <!-- Tên đăng nhập -->
                    <div class="login-field <?= isset($fieldErrors['username']) ? 'has-error' : '' ?>">
                        <label for="username">Tên đăng nhập</label>
                        <div class="input-wrap <?= isset($fieldErrors['username']) ? 'has-error' : '' ?>">
                            <i class="fa-regular fa-id-badge"></i>
                            <input type="text" id="username" name="username" placeholder="vd: nguyenvana"
                                   value="<?= e($old['username']) ?>" autocomplete="username" required>
                        </div>
                        <?php if (isset($fieldErrors['username'])): ?>
                            <div class="field-error"><?= e($fieldErrors['username']) ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Email -->
                    <div class="login-field <?= isset($fieldErrors['email']) ? 'has-error' : '' ?>">
                        <label for="email">Email</label>
                        <div class="input-wrap <?= isset($fieldErrors['email']) ? 'has-error' : '' ?>">
                            <i class="fa-regular fa-envelope"></i>
                            <input type="email" id="email" name="email" placeholder="ten@sv.uth.edu.vn"
                                   value="<?= e($old['email']) ?>" autocomplete="email" required>
                        </div>
                        <?php if (isset($fieldErrors['email'])): ?>
                            <div class="field-error"><?= e($fieldErrors['email']) ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Mật khẩu -->
                    <div class="login-field <?= isset($fieldErrors['password']) ? 'has-error' : '' ?>">
                        <label for="password">Mật khẩu</label>
                        <div class="input-wrap <?= isset($fieldErrors['password']) ? 'has-error' : '' ?>">
                            <i class="fa-solid fa-unlock-keyhole"></i>
                            <input type="password" id="password" name="password" placeholder="Ít nhất 6 ký tự"
                                   autocomplete="new-password" required>
                            <button type="button" class="password-toggle" id="passwordToggle" aria-label="Hiện mật khẩu">
                                <i class="fa-regular fa-eye"></i>
                            </button>
                        </div>
                        <?php if (isset($fieldErrors['password'])): ?>
                            <div class="field-error"><?= e($fieldErrors['password']) ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Điều khoản -->
                    <div class="login-field <?= isset($fieldErrors['terms']) ? 'has-error' : '' ?>" style="margin-bottom:16px;">
                        <label class="remember-me" style="font-size:13.5px;">
                            <input type="checkbox" name="terms" id="terms">
                            <span>Tôi đồng ý với điều khoản sử dụng và chính sách bảo mật.</span>
                        </label>
                        <?php if (isset($fieldErrors['terms'])): ?>
                            <div class="field-error"><?= e($fieldErrors['terms']) ?></div>
                        <?php endif; ?>
                    </div>

                    <button type="submit" class="login-submit">Đăng ký</button>
                </form>

                <p class="register-text">
                    Đã có tài khoản?
                    <a href="login.php">Đăng nhập</a>
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
                <p class="visual-label">JOIN US</p>
                <h2 class="visual-label">FORUM UTH</h2>
                <h2>Tham gia cộng đồng<br>sinh viên và giảng viên UTH</h2>
                <p>
                    Tạo tài khoản để đăng bài, tham gia lớp học
                    <br>và kết nối với cộng đồng UTH ngay hôm nay.
                </p>
            </div>
        </section>

    </div>
</main>

<script>
    // ================= ROLE SELECT =================
    const roleButtons = document.querySelectorAll('.role-option');
    const roleInput = document.getElementById('role');
    roleButtons.forEach(button => {
        button.addEventListener('click', () => {
            roleButtons.forEach(item => item.classList.remove('active'));
            button.classList.add('active');
            roleInput.value = button.dataset.role;
        });
    });

    // ================= SHOW / HIDE PASSWORD =================
    const passwordInput = document.getElementById('password');
    const passwordToggle = document.getElementById('passwordToggle');
    passwordToggle.addEventListener('click', () => {
        const isPassword = passwordInput.type === 'password';
        passwordInput.type = isPassword ? 'text' : 'password';
        passwordToggle.innerHTML = isPassword ? '<i class="fa-regular fa-eye-slash"></i>' : '<i class="fa-regular fa-eye"></i>';
        passwordToggle.setAttribute('aria-label', isPassword ? 'Ẩn mật khẩu' : 'Hiện mật khẩu');
    });
</script>

</body>
</html>