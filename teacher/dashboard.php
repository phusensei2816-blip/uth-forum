<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role(['teacher']);

$user = current_user();
$teacherId = $user['id'];

// ---------- Thẻ thống kê ----------
$classCount = $pdo->prepare('SELECT COUNT(*) FROM classes WHERE teacher_id = ?');
$classCount->execute([$teacherId]);
$classCount = (int)$classCount->fetchColumn();

$studentCount = $pdo->prepare(
    "SELECT COUNT(DISTINCT cm.user_id) FROM class_members cm
     JOIN classes c ON c.id = cm.class_id
     WHERE c.teacher_id = ?"
);
$studentCount->execute([$teacherId]);
$studentCount = (int)$studentCount->fetchColumn();

$materialCount = $pdo->prepare(
    "SELECT COUNT(*) FROM files f
     JOIN classes c ON c.id = f.class_id
     WHERE c.teacher_id = ?"
);
$materialCount->execute([$teacherId]);
$materialCount = (int)$materialCount->fetchColumn();

$postCount = $pdo->prepare(
    "SELECT COUNT(*) FROM posts p
     JOIN classes c ON c.id = p.class_id
     WHERE c.teacher_id = ?"
);
$postCount->execute([$teacherId]);
$postCount = (int)$postCount->fetchColumn();

$pendingCount = $pdo->prepare(
    "SELECT COUNT(*) FROM posts p
     JOIN classes c ON c.id = p.class_id
     WHERE c.teacher_id = ? AND p.status = 'pending'"
);
$pendingCount->execute([$teacherId]);
$pendingCount = (int)$pendingCount->fetchColumn();

// ---------- Danh sách lớp + số liệu từng lớp ----------
$classes = $pdo->prepare(
    "SELECT c.*,
        (SELECT COUNT(*) FROM class_members cm WHERE cm.class_id = c.id) AS member_count,
        (SELECT COUNT(*) FROM files f WHERE f.class_id = c.id) AS file_count,
        (SELECT COUNT(*) FROM posts p WHERE p.class_id = c.id) AS post_count
     FROM classes c
     WHERE c.teacher_id = ?
     ORDER BY c.created_at DESC"
);
$classes->execute([$teacherId]);
$classes = $classes->fetchAll();

$pageTitle = 'Dashboard giáo viên';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <h1>Xin chào, <?= e($user['full_name'] ?: $user['username']) ?></h1>
  <a href="<?= e(BASE_URL) ?>/teacher/create_class.php" class="btn btn-red">+ Tạo lớp mới</a>
</div>

<div class="stat-grid">
  <div class="stat-box">
    <i class="fa-solid fa-chalkboard" style="color:var(--teal);font-size:20px;margin-bottom:8px;"></i>
    <div class="num"><?= $classCount ?></div>
    <div class="label">Lớp đang quản lý</div>
  </div>
  <div class="stat-box">
    <i class="fa-solid fa-user-graduate" style="color:var(--teal);font-size:20px;margin-bottom:8px;"></i>
    <div class="num"><?= $studentCount ?></div>
    <div class="label">Sinh viên</div>
  </div>
  <div class="stat-box">
    <i class="fa-solid fa-file-lines" style="color:var(--gold-500,#d99c33);font-size:20px;margin-bottom:8px;"></i>
    <div class="num"><?= $materialCount ?></div>
    <div class="label">Tài liệu đã tải lên</div>
  </div>
  <div class="stat-box">
    <i class="fa-solid fa-comments" style="color:var(--gold-500,#d99c33);font-size:20px;margin-bottom:8px;"></i>
    <div class="num"><?= $postCount ?></div>
    <div class="label">Bài viết trong lớp</div>
  </div>
</div>

<?php if ($pendingCount > 0): ?>
  <div class="alert alert-info" style="display:flex;justify-content:space-between;align-items:center;">
    <span><i class="fa-solid fa-circle-exclamation"></i> Có <strong><?= $pendingCount ?></strong> bài viết đang chờ bạn duyệt.</span>
    <a href="<?= e(BASE_URL) ?>/teacher/moderate_posts.php" class="btn btn-teal btn-sm">Duyệt ngay</a>
  </div>
<?php endif; ?>

<h2 style="margin-top:24px;">Lớp học của tôi</h2>

<?php if (empty($classes)): ?>
  <div class="empty-state">
    <h3>Chưa có lớp học nào</h3>
    <p>Tạo lớp đầu tiên để bắt đầu chia sẻ tài liệu và thông báo với sinh viên.</p>
  </div>
<?php else: ?>
  <?php foreach ($classes as $class): ?>
    <div class="box" style="margin-bottom:16px;">
      <div style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:12px;">
        <div>
          <h3 style="margin-bottom:4px;"><?= e($class['name']) ?></h3>
          <p style="color:var(--muted);font-size:14px;margin:0;"><?= e($class['description']) ?></p>
        </div>
        <span class="tag" style="background:var(--navy-100,#dff3f3);color:var(--teal-dark);align-self:flex-start;">
          Mã mời: <?= e($class['invite_code']) ?>
        </span>
      </div>

      <div style="display:flex;gap:20px;margin:12px 0;font-size:13px;color:var(--muted);">
        <span><i class="fa-solid fa-user-graduate"></i> <?= (int)$class['member_count'] ?> thành viên</span>
        <span><i class="fa-solid fa-file-lines"></i> <?= (int)$class['file_count'] ?> tài liệu</span>
        <span><i class="fa-solid fa-comments"></i> <?= (int)$class['post_count'] ?> bài viết</span>
      </div>

      <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <a href="<?= e(BASE_URL) ?>/class/view.php?id=<?= (int)$class['id'] ?>" class="btn btn-teal btn-sm">Quản lý lớp</a>
        <a href="<?= e(BASE_URL) ?>/class/view.php?id=<?= (int)$class['id'] ?>#members" class="btn btn-outline btn-sm">Thành viên</a>
        <a href="<?= e(BASE_URL) ?>/class/upload_material.php?id=<?= (int)$class['id'] ?>" class="btn btn-outline btn-sm">Tải tài liệu</a>
        <a href="<?= e(BASE_URL) ?>/teacher/moderate_posts.php?class_id=<?= (int)$class['id'] ?>" class="btn btn-outline btn-sm">Quản lý bài viết</a>
      </div>
    </div>
  <?php endforeach; ?>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>