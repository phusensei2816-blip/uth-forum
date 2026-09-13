<?php
require_once __DIR__ . '/includes/functions.php';
log_visit();

$q = trim($_GET['q'] ?? '');
$where = "p.status = 'approved'";
$params = [];
if ($q !== '') {
    $where .= ' AND (p.title LIKE ? OR p.content LIKE ?)';
    $params[] = "%$q%";
    $params[] = "%$q%";
}

$total = $pdo->prepare("SELECT COUNT(*) FROM posts p WHERE $where");
$total->execute($params);
$totalRows = (int)$total->fetchColumn();
[$offset, $perPage, $page, $totalPages] = paginate($totalRows, 8);

$sql = "SELECT p.*, u.username, u.full_name, u.role,
               (SELECT COUNT(*) FROM comments c WHERE c.post_id = p.id) AS comment_count,
               (SELECT COUNT(*) FROM likes l WHERE l.post_id = p.id) AS like_count
        FROM posts p JOIN users u ON u.id = p.user_id
        WHERE $where
        ORDER BY p.is_announcement DESC, p.created_at DESC
        LIMIT $perPage OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$posts = $stmt->fetchAll();

$user = current_user();

// ---------- Sidebar: nội dung khác nhau theo vai trò ----------
if ($user && $user['role'] === 'teacher') {
    // Lớp đang phụ trách (kèm mã mời)
    $myClasses = $pdo->prepare(
        "SELECT c.*, COUNT(cm.id) AS members
         FROM classes c LEFT JOIN class_members cm ON cm.class_id = c.id
         WHERE c.teacher_id = ? GROUP BY c.id ORDER BY c.created_at DESC LIMIT 5"
    );
    $myClasses->execute([$user['id']]);
    $myClasses = $myClasses->fetchAll();

    // Bài viết chờ duyệt trong các lớp của giáo viên này
    $pendingCount = $pdo->prepare(
        "SELECT COUNT(*) FROM posts p JOIN classes c ON c.id = p.class_id
         WHERE c.teacher_id = ? AND p.status = 'pending'"
    );
    $pendingCount->execute([$user['id']]);
    $pendingCount = (int)$pendingCount->fetchColumn();
} elseif ($user && $user['role'] === 'student') {
    // Lớp đã tham gia
    $myClasses = $pdo->prepare(
        "SELECT c.* FROM classes c
         JOIN class_members cm ON cm.class_id = c.id
         WHERE cm.user_id = ? ORDER BY cm.joined_at DESC LIMIT 5"
    );
    $myClasses->execute([$user['id']]);
    $myClasses = $myClasses->fetchAll();
} else {
    // Khách / admin: hiện lớp nổi bật chung
    $myClasses = $pdo->query(
        "SELECT c.id, c.name, COUNT(cm.id) AS members
         FROM classes c LEFT JOIN class_members cm ON cm.class_id = c.id
         GROUP BY c.id ORDER BY members DESC LIMIT 5"
    )->fetchAll();
}

$topContributors = $pdo->query("SELECT u.username, u.full_name, COUNT(p.id) AS posts
    FROM users u JOIN posts p ON p.user_id = u.id AND p.status='approved'
    GROUP BY u.id ORDER BY posts DESC LIMIT 5")->fetchAll();

$pageTitle = 'Diễn đàn UTH — Trang chủ';
require __DIR__ . '/includes/header.php';
?>

<div class="shell">
  <nav class="side-nav">
    <a class="item active" href="index.php">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"></path>
      </svg>
      Trang chủ
    </a>
    <?php if ($user && $user['role'] === 'teacher'): ?>
      <a class="item" href="teacher/dashboard.php">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="16" rx="2"></rect><line x1="3" y1="10" x2="21" y2="10"></line></svg>
        Lớp học
      </a>
    <?php else: ?>
      <a class="item" href="class/join.php">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="16" rx="2"></rect><line x1="3" y1="10" x2="21" y2="10"></line></svg>
        Lớp học
      </a>
    <?php endif; ?>
    <?php if ($user): ?>
      <a class="item" href="chat/index.php">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"></path></svg>
        Tin nhắn
      </a>
    <?php endif; ?>
  </nav>

  <section>
    <div class="feed-header">
      <h1><?= $q !== '' ? 'Tìm kiếm: "' . e($q) . '"' : 'Bảng tin' ?></h1>
      <span><?= ($user && $user['role'] === 'teacher') ? 'Hoạt động mới nhất từ các lớp bạn phụ trách' : 'Cập nhật mới nhất từ cộng đồng' ?></span>
    </div>

    <?php if ($user): ?>
      <div class="composer">
        <div class="avatar"><?= e(mb_strtoupper(mb_substr($user['full_name'] ?: $user['username'], 0, 1))) ?></div>
        <?php if ($user['role'] === 'teacher'): ?>
          <input type="text" placeholder="Đăng thông báo mới cho lớp?" readonly onclick="location.href='post_create.php'" style="cursor:pointer;">
          <button type="button" onclick="location.href='post_create.php'">Đăng thông báo</button>
        <?php else: ?>
          <input type="text" placeholder="Bạn đang nghĩ gì?" readonly onclick="location.href='post_create.php'" style="cursor:pointer;">
          <button type="button" onclick="location.href='post_create.php'">Đăng bài</button>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <?php foreach ($posts as $p): ?>
      <article class="post <?= $p['is_announcement'] ? 'urgent' : '' ?>">
        <div class="post-top">
          <div class="avatar"><?= e(mb_strtoupper(mb_substr($p['full_name'] ?: $p['username'], 0, 1))) ?></div>
          <div class="post-meta">
            <div class="name"><?= e($p['full_name'] ?: $p['username']) ?></div>
            <div class="role"><?= e(role_label($p['role'])) ?> · <?= e(time_ago($p['created_at'])) ?></div>
          </div>
          <?php if ($p['is_announcement']): ?>
            <span class="tag urgent">Thông báo khẩn</span>
          <?php else: ?>
            <span class="tag normal">Bài viết</span>
          <?php endif; ?>
        </div>
        <div class="post-body">
          <h3 style="font-size:16px; margin-bottom:6px;">
            <a href="post_view.php?id=<?= (int)$p['id'] ?>"><?= e($p['title']) ?></a>
          </h3>
          <p>
            <?= e(mb_substr(strip_tags($p['content']), 0, 180)) ?><?= mb_strlen(strip_tags($p['content'])) > 180 ? '...' : '' ?>
          </p>
        </div>
        <div class="post-actions">
          <div class="act">👍 <?= (int)$p['like_count'] ?> Thích</div>
          <div class="act">💬 <?= (int)$p['comment_count'] ?> Bình luận</div>
          <div class="act"><a href="post_view.php?id=<?= (int)$p['id'] ?>">Xem chi tiết →</a></div>
        </div>
      </article>
    <?php endforeach; ?>

    <?php if (empty($posts)): ?>
      <div class="empty-state">
        <h3>Chưa có bài viết nào</h3>
        <p>Hãy là người đầu tiên chia sẻ điều gì đó với cộng đồng.</p>
      </div>
    <?php endif; ?>

    <div style="margin-top:20px;">
      <?php pagination_links($page, $totalPages, 'index.php' . ($q !== '' ? '?q=' . urlencode($q) : '')); ?>
    </div>
  </section>

  <aside>
    <div class="widget">
      <h4>
        <?php if ($user && $user['role'] === 'teacher'): ?>
          Lớp đang phụ trách
        <?php elseif ($user && $user['role'] === 'student'): ?>
          Lớp học của tôi
        <?php else: ?>
          Lớp học nổi bật
        <?php endif; ?>
      </h4>

      <?php foreach ($myClasses as $c): ?>
        <div class="class-row">
          <span class="class-dot"></span>
          <div class="info">
            <div class="cname"><?= e($c['name']) ?></div>
            <div class="cmeta">
              <?php if ($user && $user['role'] === 'teacher'): ?>
                <?= (int)$c['members'] ?> thành viên · Mã: <?= e($c['invite_code']) ?>
              <?php else: ?>
                <?= isset($c['members']) ? (int)$c['members'] . ' thành viên' : e($c['description'] ?? '') ?>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>

      <?php if (!$myClasses): ?>
        <p style="font-size:13px;color:var(--ink-soft);">
          <?= ($user && $user['role'] === 'teacher') ? 'Bạn chưa tạo lớp nào.' : 'Chưa có lớp học nào.' ?>
        </p>
      <?php endif; ?>
    </div>

    <?php if ($user && $user['role'] === 'teacher'): ?>
      <div class="widget">
        <h4>Việc cần xử lý</h4>
        <?php if ($pendingCount > 0): ?>
          <div class="class-row">
            <span class="class-dot" style="background:var(--maroon-500);"></span>
            <div class="info">
              <div class="cname"><?= $pendingCount ?> bài viết chờ duyệt</div>
              <div class="cmeta">Trong các lớp bạn phụ trách</div>
            </div>
          </div>
        <?php else: ?>
          <p style="font-size:13px;color:var(--ink-soft);">Không có việc gì cần xử lý.</p>
        <?php endif; ?>
      </div>
    <?php else: ?>
      <div class="widget">
        <h4>Người đóng góp</h4>
        <?php foreach ($topContributors as $index => $c): ?>
          <div class="contrib-row">
            <span class="rank"><?= $index + 1 ?></span>
            <div>
              <div class="cname"><?= e($c['full_name'] ?: $c['username']) ?></div>
              <div class="cmeta"><?= (int)$c['posts'] ?> bài viết</div>
            </div>
          </div>
        <?php endforeach; ?>
        <?php if (!$topContributors): ?><p style="font-size:13px;color:var(--ink-soft);">Chưa có dữ liệu.</p><?php endif; ?>
      </div>
    <?php endif; ?>
  </aside>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>