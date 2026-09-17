<?php
require_once __DIR__ . '/../includes/functions.php';

require_role(['student']);
$user = current_user();

$error = '';
$inviteCode = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $inviteCode = trim($_POST['invite_code'] ?? '');

    // Mã mời phải gồm đúng 12 chữ số.
    if (!preg_match('/^[0-9]{12}$/', $inviteCode)) {
        $error = 'Mã lớp phải gồm đúng 12 chữ số.';
    } else {
        $stmt = $pdo->prepare(
            'SELECT id, name FROM classes WHERE invite_code = ?'
        );
        $stmt->execute([$inviteCode]);
        $class = $stmt->fetch();

        if (!$class) {
            $error = 'Mã mời không hợp lệ.';
        } else {
            // Kiểm tra sinh viên đã tham gia lớp này chưa.
            $check = $pdo->prepare(
                'SELECT id
                 FROM class_members
                 WHERE class_id = ? AND user_id = ?'
            );
            $check->execute([$class['id'], $user['id']]);

            if ($check->fetch()) {
                flash(
                    'info',
                    'Bạn đã là thành viên của lớp "' . $class['name'] . '".'
                );
            } else {
                // Thêm sinh viên vào lớp.
                $insert = $pdo->prepare(
                    'INSERT INTO class_members (class_id, user_id)
                     VALUES (?, ?)'
                );
                $insert->execute([$class['id'], $user['id']]);

                flash(
                    'success',
                    'Đã tham gia lớp "' . $class['name'] . '".'
                );
            }

            header('Location: view.php?id=' . (int) $class['id']);
            exit;
        }
    }
}

// Lấy danh sách lớp sinh viên đã tham gia.
$stmt = $pdo->prepare(
    'SELECT
        c.id,
        c.name,
        u.full_name AS teacher_name,
        COUNT(cm2.id) AS members
     FROM classes c
     JOIN class_members cm
        ON cm.class_id = c.id AND cm.user_id = ?
     JOIN users u ON u.id = c.teacher_id
     LEFT JOIN class_members cm2 ON cm2.class_id = c.id
     GROUP BY c.id, c.name, u.full_name
     ORDER BY c.id DESC'
);

$stmt->execute([$user['id']]);
$myClasses = $stmt->fetchAll();

$pageTitle = 'Lớp học của tôi - UTH Forum';
$pageCss = 'class.css';

require __DIR__ . '/../includes/header.php';?>

<div class="class-page">
    <section class="class-list-section">
        <h2>Lớp học của tôi</h2>

        <?php if (!$myClasses): ?>
            <div class="box class-empty">
                <p>Bạn chưa tham gia lớp học nào.</p>
            </div>
        <?php else: ?>
            <?php foreach ($myClasses as $class): ?>
                <div class="box class-item">
                    <h3>
                        <a href="view.php?id=<?= (int) $class['id'] ?>">
                            <?= e($class['name']) ?>
                        </a>
                    </h3>

                    <p class="class-meta">
                        GV: <?= e($class['teacher_name']) ?>
                        · <?= (int) $class['members'] ?> thành viên
                    </p>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>

    <aside class="class-join-section">
        <div class="card class-join-card">
            <h3>Tham gia lớp bằng mã mời</h3>

            <?php if ($error !== ''): ?>
                <div class="alert alert-error">
                    <?= e($error) ?>
                </div>
            <?php endif; ?>

            <form method="post">
                <input type="hidden"  name="csrf" value="<?= e(csrf_token()) ?>">

                <div class="form-group">
                    <label for="invite-code">Mã mời lớp học</label>
                    <input
                        type="text"
                        id="invite-code"
                        name="invite_code"
                        value="<?= e($inviteCode) ?>"
                        placeholder="VD: 012012012012"
                        inputmode="numeric"
                        pattern="[0-9]{12}"
                        minlength="12"
                        maxlength="12"
                        autocomplete="off"
                        required>
                </div>

                <button type="submit" class="btn btn-teal class-join-button">
                    Tham gia
                </button>
            </form>
        </div>
    </aside>
</div>