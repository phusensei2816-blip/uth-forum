<?php
require_once __DIR__ . '/functions.php';

$user = current_user();
$currentPage = basename($_SERVER['PHP_SELF']);

function nav_active(string $page, string $currentPage): string
{
    return $page === $currentPage ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="<?= e(BASE_URL) ?>/css/style.css">

    <?php if (!empty($pageCss)): ?>
        <link rel="stylesheet" href="<?= e(BASE_URL) ?>/css/<?= e($pageCss) ?>">
    <?php endif; ?>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <title><?= e($pageTitle ?? 'Diễn đàn UTH') ?></title>
</head>
<body>

<header class="header">
    <div class="container header-content">

        <div class="logo">
            <a href="<?= e(BASE_URL) ?>/index.php">
                <img src="<?= e(BASE_URL) ?>/img/logo.png" alt="UTH Forum">
            </a>
        </div>

        <div class="nav-search">
            <div class="form-group">
                <form action="<?= e(BASE_URL) ?>/index.php" method="get">
                    <input type="text" name="q" placeholder="Tìm kiếm bài viết..." value="<?= e($_GET['q'] ?? '') ?>">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </form>
            </div>
        </div>

        <nav class="nav-group">
            <ul>
                <?php if ($user): ?>

                    <li>
                      <a href="<?= e(BASE_URL) ?>/chat/index.php"
                          class="<?= nav_active('index.php', $currentPage) ?>">
                          Trò chuyện
                      </a>
                    </li>

                    <?php if ($user['role'] === 'student'): ?>
                        <li>
                            <a href="<?= e(BASE_URL) ?>/class/join.php"
                              class="<?= nav_active('join.php', $currentPage) ?>">
                                Lớp của tôi
                            </a>
                        </li>

                        <li>
                            <a href="<?= e(BASE_URL) ?>/post/post_create.php"
                                class="<?= nav_active('/postpost_create.php', $currentPage) ?>">
                                Đăng bài
                            </a>
                        </li>

                    <?php elseif ($user['role'] === 'teacher'): ?>
                        <li>
                            <a href="<?= e(BASE_URL) ?>/teacher/dashboard.php"
                                class="<?= nav_active('dashboard.php', $currentPage) ?>">
                                Lớp học
                            </a>
                        </li>

                        <li>
                            <a href="<?= e(BASE_URL) ?>/post/post_create.php"
                                class="<?= nav_active('/postpost_create.php', $currentPage) ?>">
                                Đăng thông báo
                            </a>
                        </li>

                    <?php elseif ($user['role'] === 'admin'): ?>
                        <li>
                            <a href="<?= e(BASE_URL) ?>/admin/dashboard.php"
                                class="<?= nav_active('dashboard.php', $currentPage) ?>">
                                Quản trị
                            </a>
                        </li>
                    <?php endif; ?>

                    <li class="nav-account">
                        <a href="<?= e(BASE_URL) ?>/logout.php" class="logout-button">
                            Đăng xuất
                        </a>

                        <span class="account-info">
                            <?= e(role_label($user['role'])) ?>:
                            <?= e($user['username']) ?>
                        </span>
                    </li>

                <?php else: ?>

                    <li>
                        <a href="<?= e(BASE_URL) ?>/login.php"
                            class="<?= nav_active('login.php', $currentPage) ?>">
                            Đăng nhập
                        </a>
                    </li>

                    <li>
                        <a href="<?= e(BASE_URL) ?>/register.php"
                            class="btn btn-red btn-sm <?= nav_active('register.php', $currentPage) ?>">
                            Đăng ký
                        </a>
                    </li>

                <?php endif; ?>
            </ul>
        </nav>

    </div>
</header>

<main>
    <div class="container">
        <?php render_flashes(); ?>