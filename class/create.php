<?php
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
    }

    if (mb_strlen($name) > 150) {
        $errors[] = 'Tên lớp học không được vượt quá 150 ký tự.';
    }

    if (mb_strlen($description) > 2000) {
        $errors[] = 'Mô tả không được vượt quá 2000 ký tự.';
    }

    if (!$errors) {
        // Tạo mã mời gồm đúng 12 chữ số và kiểm tra trùng mã.
        do {
            $inviteCode = (string) random_int(100000000000, 999999999999);

            $check = $pdo->prepare(
                'SELECT id FROM classes WHERE invite_code = ?'
            );
            $check->execute([$inviteCode]);
        } while ($check->fetch());

        // Tạo lớp học.
        $stmt = $pdo->prepare(
            'INSERT INTO classes (name, description, teacher_id, invite_code)
             VALUES (?, ?, ?, ?)'
        );

        $stmt->execute([ $name, $description, $user['id'], $inviteCode]);
        $classId = (int) $pdo->lastInsertId();

        flash('success', 'Đã tạo lớp học. Mã mời: ' . $inviteCode);
        header('Location: view.php?id=' . $classId);

        exit;
    }
}

$pageTitle = 'Tạo lớp học - UTH Forum';
$pageCss = 'class.css';

require __DIR__ . '/../includes/header.php';?>

<section class="class-create-page">
    <div class="card class-create-card">
        <h1>Tạo lớp học mới</h1>

        <p class="class-create-description">
            Tạo lớp học để sinh viên có thể tham gia bằng mã mời.
        </p>

        <?php foreach ($errors as $error): ?>
            <div class="alert alert-error">
                <?= e($error) ?>
            </div>
        <?php endforeach; ?>

        <form method="post">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">

            <div class="form-group">
                <label for="class-name">Tên lớp học</label>
                <input type="text" id="class-name" name="name" maxlength="150" value="<?= e($name) ?>" placeholder="VD: Kỹ thuật ô tô K21" required>
            </div>

            <div class="form-group">
                <label for="class-description">Mô tả</label>
                <textarea id="class-description" name="description" rows="4" maxlength="2000" placeholder="Nhập mô tả lớp học (không bắt buộc)"><?= e($description) ?></textarea>
            </div>

            <button type="submit" class="btn btn-teal class-create-button">
                Tạo lớp học
            </button>

            <p class="class-create-note">
                Mã mời gồm 12 chữ số sẽ được hệ thống tự động tạo sau khi tạo lớp.
            </p>
        </form>
    </div>
</section>