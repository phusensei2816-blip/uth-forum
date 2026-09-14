<?php
require_once __DIR__ . '/functions.php';
$user = current_user();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="<?= e(BASE_URL) ?>/css/style.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
  <title><?= e($pageTitle ?? 'Diễn đàn UTH') ?></title>
</head>
<body>
<header class="header site-nav">
  <div class="container header-content">
    <a class="logo-text" href="<?= e(BASE_URL) ?>/index.php">UTH<span>FORUM</span></a>

    <ul class="site-nav-pills">
      <li><a href="<?= e(BASE_URL) ?>/index.php" class="<?= basename($_SERVER['SCRIPT_NAME']) === 'index.php' ? 'active' : '' ?>">Thông tin chung</a></li>
      <?php if ($user && $user['role'] === 'teacher'): ?>
        <li><a href="<?= e(BASE_URL) ?>/teacher/dashboard.php">Học tập</a></li>
      <?php elseif ($user && $user['role'] === 'student'): ?>
        <li><a href="<?= e(BASE_URL) ?>/class/join.php">Học tập</a></li>
      <?php else: ?>
        <li><a href="<?= e(BASE_URL) ?>/login.php">Học tập</a></li>
      <?php endif; ?>
      <li><a href="<?= e(BASE_URL) ?>/cong-no.php" class="<?= basename($_SERVER['SCRIPT_NAME']) === 'cong-no.php' ? 'active' : '' ?>">Công nợ</a></li>
      <li><a href="<?= e(BASE_URL) ?>/ho-tro.php" class="<?= basename($_SERVER['SCRIPT_NAME']) === 'ho-tro.php' ? 'active' : '' ?>">Hỗ trợ</a></li>
    </ul>

    <div class="site-nav-right">
      <?php if ($user): ?>
        <a class="nav-user-link" href="<?= e(BASE_URL) ?>/chat/index.php">Hoạt động sinh viên</a>
        <div class="nav-wrap" style="position:relative;">
          <button type="button" class="nav-avatar" id="navAvatarBtn" aria-label="Tài khoản"><?= e(mb_strtoupper(mb_substr($user['full_name'] ?: $user['username'], 0, 1))) ?></button>
          <div class="nav-panel" id="navAvatarPanel" style="display:none;position:absolute;top:calc(100% + 8px);right:0;min-width:200px;background:var(--card);border:1px solid var(--line);border-radius:10px;box-shadow:0 10px 24px rgba(0,0,0,.12);overflow:hidden;z-index:60;">
            <div style="padding:10px 14px;border-bottom:1px solid var(--line);font-size:13px;color:var(--muted);"><?= e($user['full_name'] ?: $user['username']) ?> · <?= e(role_label($user['role'])) ?></div>
            <?php if ($user['role'] === 'student'): ?>
              <a href="<?= e(BASE_URL) ?>/post_create.php" style="display:block;padding:10px 14px;font-size:14px;color:var(--ink);">Đăng bài</a>
            <?php elseif ($user['role'] === 'teacher'): ?>
              <a href="<?= e(BASE_URL) ?>/post_create.php" style="display:block;padding:10px 14px;font-size:14px;color:var(--ink);">Đăng thông báo</a>
            <?php elseif ($user['role'] === 'admin'): ?>
              <a href="<?= e(BASE_URL) ?>/admin/dashboard.php" style="display:block;padding:10px 14px;font-size:14px;color:var(--ink);">Quản trị</a>
            <?php endif; ?>
            <a href="<?= e(BASE_URL) ?>/logout.php" style="display:block;padding:10px 14px;font-size:14px;color:var(--red-dark, var(--muted));border-top:1px solid var(--line);">Đăng xuất</a>
          </div>
        </div>
      <?php else: ?>
        <a class="nav-user-link" href="<?= e(BASE_URL) ?>/login.php">Đăng nhập</a>
        <a href="<?= e(BASE_URL) ?>/register.php" class="btn btn-teal btn-sm">Đăng ký</a>
      <?php endif; ?>
    </div>
  </div>
</header>
<script>
(function () {
  var btn = document.getElementById('navAvatarBtn');
  var panel = document.getElementById('navAvatarPanel');
  if (!btn || !panel) return;
  btn.addEventListener('click', function (e) {
    e.stopPropagation();
    panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
  });
  document.addEventListener('click', function () { panel.style.display = 'none'; });
})();
</script>
<main>
  <div class="container">
    <?php render_flashes(); ?>
