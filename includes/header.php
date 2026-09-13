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
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <title><?= e($pageTitle ?? 'Diễn đàn UTH') ?></title>
</head>
<body>

<header>
  <a class="logo" href="<?= e(BASE_URL) ?>/index.php">
    <div class="logo-mark">U</div>
    <span>Diễn đàn UTH</span>
  </a>

  <form class="search-wrap" method="get" action="<?= e(BASE_URL) ?>/index.php">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
      <circle cx="11" cy="11" r="7"></circle>
      <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
    </svg>
    <input type="text" name="q" value="<?= e($_GET['q'] ?? '') ?>" placeholder="Tìm bài viết, lớp học, thành viên...">
  </form>

  <div class="nav-icons">
    <?php if ($user): ?>
      <a class="icon-btn" href="<?= e(BASE_URL) ?>/chat/index.php" title="Tin nhắn">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
          <path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"></path>
        </svg>
      </a>

      <?php if ($user['role'] === 'student'): ?>
        <a href="<?= e(BASE_URL) ?>/class/join.php" class="btn btn-outline btn-sm">Lớp của tôi</a>
        <a href="<?= e(BASE_URL) ?>/post_create.php" class="btn btn-red btn-sm">Đăng bài</a>
      <?php elseif ($user['role'] === 'teacher'): ?>
        <a href="<?= e(BASE_URL) ?>/teacher/dashboard.php" class="btn btn-outline btn-sm">Lớp học</a>
        <a href="<?= e(BASE_URL) ?>/post_create.php" class="btn btn-red btn-sm">Đăng thông báo</a>
      <?php elseif ($user['role'] === 'admin'): ?>
        <a href="<?= e(BASE_URL) ?>/admin/dashboard.php" class="btn btn-outline btn-sm">Quản trị</a>
      <?php endif; ?>

      <span class="role-badge"><?= e(role_label($user['role'])) ?></span>

      <div class="profile-menu-wrap">
        <button type="button" class="avatar profile-avatar-btn" id="profileMenuBtn" aria-expanded="false" aria-controls="profileMenu" title="Tài khoản">
          <?php if (!empty($user['avatar'])): ?>
            <img src="<?= e(BASE_URL . '/' . ltrim($user['avatar'], '/')) ?>" alt="Ảnh đại diện">
          <?php else: ?>
            <?= e(mb_strtoupper(mb_substr($user['full_name'] ?: $user['username'], 0, 1))) ?>
          <?php endif; ?>
        </button>

        <div class="profile-dropdown" id="profileMenu" hidden>
          <div class="profile-dropdown-head">
            <div class="avatar profile-dropdown-avatar">
              <?php if (!empty($user['avatar'])): ?>
                <img src="<?= e(BASE_URL . '/' . ltrim($user['avatar'], '/')) ?>" alt="Ảnh đại diện">
              <?php else: ?>
                <?= e(mb_strtoupper(mb_substr($user['full_name'] ?: $user['username'], 0, 1))) ?>
              <?php endif; ?>
            </div>
            <div class="profile-dropdown-info">
              <strong><?= e($user['full_name'] ?: $user['username']) ?></strong>
              <span><?= e(role_label($user['role'])) ?></span>
            </div>
          </div>
          <div class="profile-dropdown-divider"></div>
          <a class="profile-menu-item" href="<?= e(BASE_URL) ?>/profile.php">
            <span class="profile-menu-icon"><i class="fa-regular fa-user"></i></span>
            <span>Thông tin cá nhân</span>
          </a>
          <a class="profile-menu-item" href="<?= e(BASE_URL) ?>/change_password.php">
            <span class="profile-menu-icon"><i class="fa-solid fa-key"></i></span>
            <span>Đổi mật khẩu</span>
          </a>
          <div class="profile-dropdown-divider"></div>
          <a class="profile-menu-item danger" href="<?= e(BASE_URL) ?>/logout.php" onclick="return confirm('Bạn có chắc muốn đăng xuất không?')">
            <span class="profile-menu-icon"><i class="fa-solid fa-right-from-bracket"></i></span>
            <span>Đăng xuất</span>
          </a>
        </div>
      </div>
    <?php else: ?>
      <a href="<?= e(BASE_URL) ?>/login.php" class="btn btn-outline btn-sm">Đăng nhập</a>
      <a href="<?= e(BASE_URL) ?>/register.php" class="btn btn-red btn-sm">Đăng ký</a>
    <?php endif; ?>
  </div>
</header>

<main>
  <div class="container">
    <?php render_flashes(); ?>