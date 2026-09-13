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
  <?php if (!empty($pageCss)): ?>
    <link rel="stylesheet" href="<?= e(BASE_URL) ?>/css/<?= e($pageCss) ?>" />
  <?php endif; ?>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
  <title><?= e($pageTitle ?? 'Diễn đàn UTH') ?></title>
</head>
<body>

<header class="header">
  <div class="container header-content">
    <div class="logo">
      <a href="<?= e(BASE_URL) ?>/index.php"><img src="<?= e(BASE_URL) ?>/img/logo.png" alt="UTH Forum" /></a>
    </div>

    <div class="nav-search">
      <div class="form-group">
        <form action="<?= e(BASE_URL) ?>/index.php" method="get">
          <input type="text" name="q" placeholder="Tìm kiếm bài viết..." value="<?= e($_GET['q'] ?? '') ?>" />
          <i class="fa-solid fa-magnifying-glass"></i>
        </form>
      </div>
    </div>

    <div class="nav-group">
      <ul>
        <?php if ($user): ?>
          <li><a href="<?= e(BASE_URL) ?>/chat/index.php">Trò chuyện</a></li>

          <?php if ($user['role'] === 'student'): ?>
            <li><a href="<?= e(BASE_URL) ?>/class/join.php">Lớp của tôi</a></li>
            <li><a href="<?= e(BASE_URL) ?>/post_create.php">Đăng bài</a></li>

          <?php elseif ($user['role'] === 'teacher'): ?>
            <li><a href="<?= e(BASE_URL) ?>/teacher/dashboard.php">Lớp học</a></li>
            <li><a href="<?= e(BASE_URL) ?>/post_create.php">Đăng thông báo</a></li>

          <?php elseif ($user['role'] === 'admin'): ?>
            <li><a href="<?= e(BASE_URL) ?>/admin/dashboard.php">Quản trị</a></li>
          <?php endif; ?>

          <li><span class="role-badge"><?= e(role_label($user['role'])) ?></span></li>
          <li><a href="<?= e(BASE_URL) ?>/logout.php">Đăng xuất (<?= e($user['username']) ?>)</a></li>

        <?php else: ?>
          <li><a href="<?= e(BASE_URL) ?>/login.php">Đăng nhập</a></li>
          <li><a href="<?= e(BASE_URL) ?>/register.php" class="btn btn-red btn-sm">Đăng ký</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</header>
<main>
  <div class="container">
    <?php render_flashes(); ?>