<?php
require_once __DIR__ . '/../includes/functions.php';

require_login();
$user = current_user();

$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    http_response_code(400);
    die('Mã lớp học không hợp lệ.');
}

$stmt = $pdo->prepare(
    'SELECT c.*, u.full_name AS teacher_name
     FROM classes c
     JOIN users u ON u.id = c.teacher_id
     WHERE c.id = ?'
);
$stmt->execute([$id]);
$class = $stmt->fetch();

if (!$class) {
    http_response_code(404);
    die('Không tìm thấy lớp học.');
}

$isTeacher = (int) $user['id'] === (int) $class['teacher_id'];
$isAdmin = ($user['role'] ?? '') === 'admin';

// Kiểm tra thành viên của lớp.
$memberCheck = $pdo->prepare(
    'SELECT id
     FROM class_members
     WHERE class_id = ? AND user_id = ?'
);
$memberCheck->execute([$id, $user['id']]);
$isMember = (bool) $memberCheck->fetch();

if (!$isTeacher && !$isMember && !$isAdmin) {
    http_response_code(403);
    die('Bạn cần tham gia lớp bằng mã mời để xem nội dung này.');
}

// Lấy các bài viết đã được duyệt trong lớp.
$postStmt = $pdo->prepare(
    "SELECT p.*, u.full_name, u.username
     FROM posts p
     JOIN users u ON u.id = p.user_id
     WHERE p.class_id = ? AND p.status = 'approved'
     ORDER BY p.is_announcement DESC, p.created_at DESC"
);
$postStmt->execute([$id]);
$posts = $postStmt->fetchAll();

// Lấy tài liệu của lớp.
$materialStmt = $pdo->prepare(
    'SELECT f.*, u.full_name, u.username
     FROM files f
     JOIN users u ON u.id = f.uploader_id
     WHERE f.class_id = ?
     ORDER BY f.created_at DESC'
);
$materialStmt->execute([$id]);
$materials = $materialStmt->fetchAll();

// Lấy danh sách thành viên.
$memberStmt = $pdo->prepare(
    'SELECT u.id, u.full_name, u.username
     FROM class_members cm
     JOIN users u ON u.id = cm.user_id
     WHERE cm.class_id = ?
     ORDER BY u.full_name ASC'
);
$memberStmt->execute([$id]);
$members = $memberStmt->fetchAll();

$pageTitle = $class['name'] . ' - UTH Forum';
$pageCss = 'class.css';

require __DIR__ . '/../includes/header.php';
?>

<section class="class-detail-header">
    <h2><?= e($class['name']) ?></h2>

    <?php if (!empty($class['description'])): ?>
        <p><?= nl2br(e($class['description'])) ?></p>
    <?php endif; ?>

    <p class="class-detail-meta">
        Giảng viên: <?= e($class['teacher_name']) ?>
        · <?= count($members) ?> thành viên
    </p>

    <?php if ($isTeacher || $isAdmin): ?>
        <p class="class-invite-code">
            Mã mời lớp:
            <span class="class-code"><?= e($class['invite_code']) ?></span>
        </p>
    <?php endif; ?>
</section>

<div class="class-detail-layout">
    <section class="class-detail-main">
        <?php if ($isTeacher): ?>
            <div class="class-action-box">
                <a href="../post_create.php" class="btn btn-red btn-sm">
                    Đăng thông báo
                </a>

                <a href="upload_material.php?id=<?= (int) $class['id'] ?>" class="btn btn-teal btn-sm">
                    Tải lên tài liệu / bài tập
                </a>

                <a href="../chat/index.php?class_id=<?= (int) $class['id'] ?>" class="btn btn-outline btn-sm">
                    Chat nhóm lớp
                </a>
            </div>
        <?php elseif ($isMember): ?>
            <div class="box class-action-box">
                <a
                    href="../chat/index.php?class_id=<?= (int) $class['id'] ?>"
                    class="btn btn-outline btn-sm"
                >
                    Chat nhóm lớp
                </a>
            </div>
        <?php endif; ?>

        <div class="box class-content-box">
            <h3>Thông báo &amp; thảo luận</h3>

            <?php if (!$posts): ?>
                <p class="class-muted">Chưa có thông báo nào.</p>
            <?php else: ?>
                <?php foreach ($posts as $post): ?>
                    <article class="class-post-item">
                        <div class="class-post-avatar">
                            <?= e(mb_strtoupper(mb_substr(
                                $post['full_name'] ?: $post['username'],
                                0,
                                1
                            ))) ?>
                        </div>

                        <div class="class-post-content">
                            <div class="class-post-meta">
                                <strong>
                                    <?= e($post['full_name'] ?: $post['username']) ?>
                                </strong>

                                <span>
                                    · <?= e(time_ago($post['created_at'])) ?>
                                </span>

                                <?php if (!empty($post['is_announcement'])): ?>
                                    <span class="tag tag-urgent">Khẩn cấp</span>
                                <?php endif; ?>
                            </div>

                            <h3 class="class-post-title">
                                <a href="../post_view.php?id=<?= (int) $post['id'] ?>">
                                    <?= e($post['title']) ?>
                                </a>
                            </h3>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="box class-content-box class-materials-box">
            <h3>Tài liệu &amp; bài tập</h3>

            <?php if (!$materials): ?>
                <p class="class-muted">Chưa có tài liệu nào.</p>
            <?php else: ?>
                <?php foreach ($materials as $material): ?>
                    <div class="class-material-item">
                        <div class="class-material-info">
                            <strong>
                                <?= e($material['original_name']) ?>
                            </strong>

                            <span>
                                <?= (int) round($material['filesize'] / 1024) ?> KB
                                ·
                                <?= e($material['full_name'] ?: $material['username']) ?>
                            </span>
                        </div>

                        <a
                            class="btn btn-outline btn-sm"
                            href="../<?= e(UPLOAD_URL . $material['stored_name']) ?>"
                            download
                        >
                            Tải xuống
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

    <aside class="class-detail-sidebar">
        <div class="box class-members-box">
            <h3>Thành viên (<?= count($members) ?>)</h3>

            <?php if (!$members): ?>
                <p class="class-muted">Chưa có thành viên.</p>
            <?php else: ?>
                <?php foreach ($members as $member): ?>
                    <div class="class-member-item">
                        <div class="class-member-avatar">
                            <?= e(mb_strtoupper(mb_substr(
                                $member['full_name'] ?: $member['username'],
                                0,
                                1
                            ))) ?>
                        </div>

                        <span>
                            <?= e($member['full_name'] ?: $member['username']) ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </aside>
</div>