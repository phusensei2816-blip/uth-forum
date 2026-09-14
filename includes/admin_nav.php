<?php
/**
 * Thanh điều hướng bên trái dùng chung cho toàn bộ khu vực Admin.
 * Include ngay sau khi mở <div class="admin-layout"> trong mỗi file admin/*.php
 */
$__adminCurrent = basename($_SERVER['SCRIPT_NAME']);
$__adminPending = (int)$pdo->query("SELECT COUNT(*) FROM posts WHERE status='pending'")->fetchColumn();
$__adminNav = [
    'dashboard.php'  => ['icon' => 'fa-gauge-high',  'label' => 'Bảng điều khiển'],
    'users.php'      => ['icon' => 'fa-users',       'label' => 'Người dùng'],
    'moderation.php' => ['icon' => 'fa-flag',        'label' => 'Duyệt bài', 'badge' => $__adminPending],
    'files.php'      => ['icon' => 'fa-folder-open', 'label' => 'Tệp tin'],
];
?>
<aside class="admin-sidebar">
  <?php foreach ($__adminNav as $__file => $__item): ?>
    <a href="<?= e($__file) ?>" class="<?= $__adminCurrent === $__file ? 'active' : '' ?>">
      <i class="fa-solid <?= e($__item['icon']) ?>"></i>
      <span><?= e($__item['label']) ?></span>
      <?php if (!empty($__item['badge'])): ?><span class="badge"><?= (int)$__item['badge'] ?></span><?php endif; ?>
    </a>
  <?php endforeach; ?>
</aside>
