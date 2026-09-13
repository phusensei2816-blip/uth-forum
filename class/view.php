<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_login();

$user = current_user();
$classId = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare(
    'SELECT c.*, u.full_name AS teacher_name
     FROM classes c JOIN users u ON u.id = c.teacher_id
     WHERE c.id = ?'
);
$stmt->execute([$classId]);
$class = $stmt->fetch();

if (!$class) {
    http_response_code(404);
    die('Không tìm thấy lớp học.');
}

$isOwner = ($user['role'] === 'teacher' && (int)$class['teacher_id'] === (int)$user['id']);
$isAdmin = ($user['role'] === 'admin');
$isMember = false;

if ($user['role'] === 'student') {
    $check = $pdo->prepare('SELECT id FROM class_members WHERE class_id = ? AND user_id = ?');
    $check->execute([$classId, $user['id']]);
    $isMember = (bool)$check->fetch();
}

if (!$isOwner && !$isAdmin && !$isMember) {
    http_response_code(403);
    die('Bạn chưa tham gia lớp học này.');
}

$posts = $pdo->prepare(
    "SELECT p.*, u.full_name, u.username FROM posts p
     JOIN users u ON u.id = p.user_id
     WHERE p.class_id = ? AND p.status = 'approved'
     ORDER BY p.is_announcement DESC, p.created_at DESC"
);
$posts->execute([$classId]);
$posts = $posts->fetchAll();

$pageTitle = $class['name'];
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
  <div>
    <h1><?= e($class['name']) ?></h1>
    <p class="text-muted">Giảng viên: <?= e($class['teacher_name']) ?></p>
  </div>
  <?php if ($isOwner || $isAdmin): ?>
    <span class="role-badge">Mã lớp: <?= e($class['invite_code']) ?></span>
  <?php endif; ?>
</div>

<p><?= nl2br(e($class['description'])) ?></p>

<div class="page-header">
  <h2>Tài liệu</h2>
  <a href="<?= e(BASE_URL) ?>/class/materials.php?id=<?= $classId ?>" class="btn btn-sm">Xem tài liệu</a>
</div>

<h2>Bài viết &amp; thông báo</h2>

<?php if (empty($posts)): ?>
  <div class="empty-state">
    <h3>Chưa có bài viết nào trong lớp này</h3>
  </div>
<?php else: ?>
  <?php foreach ($posts as $post): ?>
    <div class="post <?= $post['is_announcement'] ? 'urgent' : '' ?>">
      <div class="post-top">
        <div class="post-meta">
          <div class="name"><?= e($post['full_name'] ?: $post['username']) ?></div>
          <div class="role"><?= date('d/m/Y H:i', strtotime($post['created_at'])) ?></div>
        </div>
        <?php if ($post['is_announcement']): ?>
          <span class="tag urgent">Thông báo khẩn</span>
        <?php endif; ?>
      </div>
      <h3><?= e($post['title']) ?></h3>
      <div class="post-body">
        <?= $post['content'] /* HTML từ rich text editor — đã được lọc/sanitize khi lưu vào DB */ ?>
      </div>
    </div>
  <?php endforeach; ?>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>