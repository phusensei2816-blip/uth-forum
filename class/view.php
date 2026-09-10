<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();
$user = current_user();

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT c.*, u.full_name AS teacher_name FROM classes c JOIN users u ON u.id = c.teacher_id WHERE c.id = ?');
$stmt->execute([$id]);
$class = $stmt->fetch();
if (!$class) { http_response_code(404); die('Không tìm thấy lớp học.'); }

$isTeacher = $user['id'] == $class['teacher_id'];
$isAdmin = $user['role'] === 'admin';

$memberChk = $pdo->prepare('SELECT id FROM class_members WHERE class_id=? AND user_id=?');
$memberChk->execute([$id, $user['id']]);
$isMember = (bool)$memberChk->fetch();

if (!$isTeacher && !$isMember && !$isAdmin) {
    http_response_code(403);
    die('Bạn cần tham gia lớp bằng mã mời để xem nội dung này.');
}

$posts = $pdo->prepare("SELECT p.*, u.full_name, u.username FROM posts p JOIN users u ON u.id=p.user_id
                         WHERE p.class_id = ? AND p.status='approved' ORDER BY p.is_announcement DESC, p.created_at DESC");
$posts->execute([$id]);
$posts = $posts->fetchAll();

$materials = $pdo->prepare('SELECT f.*, u.full_name, u.username FROM files f JOIN users u ON u.id=f.uploader_id
                             WHERE f.class_id = ? ORDER BY f.created_at DESC');
$materials->execute([$id]);
$materials = $materials->fetchAll();

$members = $pdo->prepare('SELECT u.* FROM class_members cm JOIN users u ON u.id=cm.user_id WHERE cm.class_id=?');
$members->execute([$id]);
$members = $members->fetchAll();

$pageTitle = $class['name'] . ' - UTH Forum';
require __DIR__ . '/../includes/header.php';
?>
<div class="class-header">
  <h2 style="color:#fff;"><?= e($class['name']) ?></h2>
  <p><?= e($class['description']) ?></p>
  <p>Giảng viên: <?= e($class['teacher_name']) ?> · <?= count($members) ?> thành viên</p>
  <?php if ($isTeacher || $isAdmin): ?>
    <p style="margin-top:10px;">Mã mời lớp: <span class="code"><?= e($class['invite_code']) ?></span></p>
  <?php endif; ?>
</div>

<div class="row">
  <section style="flex:2;min-width:0;">
    <?php if ($isTeacher): ?>
      <div class="box" style="margin-bottom:16px;display:flex;gap:10px;flex-wrap:wrap;">
        <a href="../post_create.php" class="btn btn-red btn-sm">Đăng thông báo</a>
        <a href="upload_material.php?id=<?= (int)$class['id'] ?>" class="btn btn-teal btn-sm">Tải lên tài liệu / bài tập</a>
        <a href="../chat/index.php?class_id=<?= (int)$class['id'] ?>" class="btn btn-outline btn-sm">Chat nhóm lớp</a>
      </div>
    <?php elseif ($isMember): ?>
      <div class="box" style="margin-bottom:16px;">
        <a href="../chat/index.php?class_id=<?= (int)$class['id'] ?>" class="btn btn-outline btn-sm">Chat nhóm lớp</a>
      </div>
    <?php endif; ?>

    <div class="box">
      <h3>Thông báo & thảo luận</h3>
      <?php if (!$posts): ?><p style="color:var(--muted);">Chưa có thông báo nào.</p><?php endif; ?>
      <?php foreach ($posts as $p): ?>
        <div class="post-card">
          <div class="avatar"><?= e(mb_strtoupper(mb_substr($p['full_name'] ?: $p['username'], 0, 1))) ?></div>
          <div style="flex:1;">
            <div class="post-meta">
              <strong><?= e($p['full_name'] ?: $p['username']) ?></strong>
              <span>· <?= time_ago($p['created_at']) ?></span>
              <?php if ($p['is_announcement']): ?><span class="tag tag-urgent">Khẩn cấp</span><?php endif; ?>
            </div>
            <h3 class="post-title"><a href="../post_view.php?id=<?= (int)$p['id'] ?>"><?= e($p['title']) ?></a></h3>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="box" style="margin-top:16px;">
      <h3>Tài liệu & bài tập</h3>
      <?php if (!$materials): ?><p style="color:var(--muted);">Chưa có tài liệu nào.</p><?php endif; ?>
      <?php foreach ($materials as $m): ?>
        <div class="material-item">
          <span><i class="fa-solid fa-file"></i> <?= e($m['original_name']) ?>
            <span style="color:var(--muted);font-size:12px;"> — <?= round($m['filesize']/1024) ?> KB, <?= e($m['full_name'] ?: $m['username']) ?></span>
          </span>
          <a class="btn btn-outline btn-sm" href="../<?= e(UPLOAD_URL . $m['stored_name']) ?>" download>Tải xuống</a>
        </div>
      <?php endforeach; ?>
    </div>
  </section>

  <aside style="flex:1;min-width:240px;">
    <div class="box">
      <h3>Thành viên (<?= count($members) ?>)</h3>
      <?php foreach ($members as $m): ?>
        <div class="inner-box">
          <div class="avatar-sm"><?= e(mb_strtoupper(mb_substr($m['full_name'] ?: $m['username'], 0, 1))) ?></div>
          <div style="font-size:13px;"><?= e($m['full_name'] ?: $m['username']) ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  </aside>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
