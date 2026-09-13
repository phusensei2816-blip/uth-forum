<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role(['teacher']);

$user = current_user();
$errors = [];

$name = '';
$description = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($name === '') {
        $errors[] = 'Vui lòng nhập tên lớp học.';
    } elseif (mb_strlen($name) > 150) {
        $errors[] = 'Tên lớp học không được vượt quá 150 ký tự.';
    }

    if (mb_strlen($description) > 5000) {
        $errors[] = 'Mô tả lớp học quá dài.';
    }

    if (!$errors) {
        // Tạo mã mời 6 ký tự và kiểm tra trùng trong database.
        do {
            $inviteCode = generate_invite_code();

            $check = $pdo->prepare(
                'SELECT id FROM classes WHERE invite_code = ?'
            );
            $check->execute([$inviteCode]);
        } while ($check->fetch());

        $stmt = $pdo->prepare(
            'INSERT INTO classes
                (name, description, teacher_id, invite_code)
             VALUES
                (?, ?, ?, ?)'
        );

        $stmt->execute([
            $name,
            $description,
            $user['id'],
            $inviteCode
        ]);

        $classId = (int)$pdo->lastInsertId();

        flash(
            'success',
            'Đã tạo lớp "' . $name . '" thành công. Mã mời: ' . $inviteCode
        );

        header(
            'Location: ' . BASE_URL . '/class/view.php?id=' . $classId
        );
        exit;
    }
}

$pageTitle = 'Tạo lớp học - UTH Forum';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1>Tạo lớp học mới</h1>
        <p class="text-muted">
            Tạo lớp để quản lý sinh viên, bài viết và tài liệu.
        </p>
    </div>

    <a
        href="<?= e(BASE_URL) ?>/teacher/dashboard.php"
        class="btn btn-outline"
    >
        Quay lại Dashboard
    </a>
</div>

<section class="row" style="justify-content:center;">
    <div class="card" style="max-width:650px;width:100%;">

        <h2>Thông tin lớp học</h2>

        <?php foreach ($errors as $err): ?>
            <div class="alert alert-error">
                <?= e($err) ?>
            </div>
        <?php endforeach; ?>

        <form method="post">

            <input
                type="hidden"
                name="csrf"
                value="<?= e(csrf_token()) ?>"
            >

            <div class="form-group">
                <label for="name">Tên lớp học</label>

                <input
                    type="text"
                    id="name"
                    name="name"
                    maxlength="150"
                    required
                    placeholder="VD: Kỹ thuật ô tô K21"
                    value="<?= e($name) ?>"
                >
            </div>

            <div class="form-group">
                <label for="description">Mô tả lớp học</label>

                <textarea
                    id="description"
                    name="description"
                    rows="5"
                    maxlength="5000"
                    placeholder="VD: Lớp học phần Kỹ thuật ô tô - học kỳ 1..."
                ><?= e($description) ?></textarea>
            </div>

            <div class="alert alert-info">
                <strong>Lưu ý:</strong>
                Sau khi tạo lớp, hệ thống sẽ tự động sinh
                <strong>mã mời 6 ký tự</strong>.
                Bạn có thể gửi mã này cho sinh viên để họ tham gia lớp.
            </div>

            <button
                type="submit"
                class="btn btn-red"
            >
                + Tạo lớp học
            </button>

        </form>
    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
