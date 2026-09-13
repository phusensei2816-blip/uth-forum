<?php
require_once __DIR__ . '/../includes/functions.php';

require_role(['teacher']);
$user = current_user();

$stmt = $pdo->prepare(
    'SELECT c.*, COUNT(cm.id) AS members
     FROM classes c
     LEFT JOIN class_members cm ON cm.class_id = c.id
     WHERE c.teacher_id = ?
     GROUP BY c.id
     ORDER BY c.created_at DESC'
);
$stmt->execute([$user['id']]);
$classes = $stmt->fetchAll();

$pageTitle = 'Lớp học của tôi - UTH Forum';
$pageCss = 'class.css';

require __DIR__ . '/../includes/header.php';
?>

<section class="teacher-dashboard">
    <div class="row class-page-heading">
        <div>
            <h1>Lớp học của tôi</h1>
            <p class="class-meta">
                Quản lý các lớp học mà bạn đang phụ trách.
            </p>
        </div>

        <a href="../class/create.php" class="btn btn-teal">
            + Tạo lớp mới
        </a>
    </div>

    <?php if (!$classes): ?>
        <div class="box class-empty">
            <h3>Bạn chưa tạo lớp học nào</h3>
            <p> Hãy tạo lớp học mới để sinh viên có thể tham gia bằng mã mời. </p>

            <a href="../class/create.php" class="btn btn-teal">
                Tạo lớp học
            </a>
        </div>
    <?php else: ?>
        <div class="class-list">
            <?php foreach ($classes as $c): ?>
                <article class="box class-item">
                    <div class="class-item-content">
                        <h3>
                            <a href="../class/view.php?id=<?= (int) $c['id'] ?>">
                                <?= e($c['name']) ?>
                            </a>
                        </h3>

                        <p class="class-meta">
                            <?= (int) $c['members'] ?> thành viên
                        </p>

                        <p class="class-invite-code">
                            Mã mời:
                            <strong><?= e($c['invite_code']) ?></strong>
                        </p>
                    </div>

                    <a href="../class/view.php?id=<?= (int) $c['id'] ?>" class="btn btn-teal btn-sm">
                        Xem lớp học
                    </a>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>