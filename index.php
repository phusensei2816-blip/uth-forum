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

// Sidebar data
$topClasses = $pdo->query("SELECT c.id, c.name, COUNT(cm.id) AS members
    FROM classes c LEFT JOIN class_members cm ON cm.class_id = c.id
    GROUP BY c.id ORDER BY members DESC LIMIT 5")->fetchAll();

$topContributors = $pdo->query("SELECT u.username, u.full_name, COUNT(p.id) AS posts
    FROM users u JOIN posts p ON p.user_id = u.id AND p.status='approved'
    GROUP BY u.id ORDER BY posts DESC LIMIT 5")->fetchAll();

// Featured posts for the homepage hero grid: pinned/urgent first, then most recent
$featuredPosts = $pdo->query("SELECT p.*, u.username, u.full_name
    FROM posts p JOIN users u ON u.id = p.user_id
    WHERE p.status = 'approved'
    ORDER BY p.is_announcement DESC, p.created_at DESC
    LIMIT 3")->fetchAll();

$pageTitle = 'Diễn đàn UTHer';
require __DIR__ . '/includes/header.php';
?>
</div>
<?php if ($q === ''): ?>
<section class="hero">
  <div class="hero-slides" id="heroSlides">
    <div class="hero-slide active" data-slide="0">
      <h1>Kết Nối. Chia Sẻ. <em>Bứt Phá.</em></h1>
      <p>Diễn đàn dành riêng cho sinh viên và giảng viên Trường Đại học Giao thông vận tải TP.HCM (UTH).</p>
    </div>
    <div class="hero-slide" data-slide="1">
      <h1>Tài Liệu Học Tập <em>Đầy Đủ</em></h1>
      <p>Giáo trình, slide bài giảng, bài tập được giảng viên cập nhật mỗi ngày, tải về chỉ với một cú click.</p>
    </div>
    <div class="hero-slide" data-slide="2">
      <h1>Thông Báo <em>Tức Thời</em></h1>
      <p>Không bỏ lỡ lịch thi, hạn nộp bài hay sự kiện quan trọng của lớp mình.</p>
    </div>
  </div>
  <div class="hero-dots" id="heroDots"></div>

  <form class="search-bar" action="<?= e(BASE_URL) ?>/index.php" method="get">
    <select disabled>
      <option>Tất cả lớp học</option>
      <?php foreach ($topClasses as $c): ?>
        <option><?= e($c['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <select disabled>
      <option>Thông báo</option>
      <option>Thảo luận</option>
      <option>Tài liệu</option>
    </select>
    <input type="text" name="q" placeholder="Tìm kiếm bài viết, tên lớp, giảng viên...">
    <button type="submit">Tìm kiếm</button>
  </form>
</section>

<section class="value-props">
  <div class="vp"><div class="icon">🔔</div><h3>Thông báo tức thời</h3><p>Cập nhật lịch thi, deadline ngay khi giảng viên đăng bài.</p></div>
  <div class="vp"><div class="icon">📚</div><h3>Tài liệu đầy đủ</h3><p>Kho giáo trình, slide, bài tập theo từng lớp học phần.</p></div>
  <div class="vp"><div class="icon">💬</div><h3>Trao đổi dễ dàng</h3><p>Bình luận, thảo luận trực tiếp với bạn học và giảng viên.</p></div>
</section>

<section class="featured" id="featured">
  <h2>Bài Viết <span>Nổi Bật</span></h2>
  <div class="featured-grid">
    <?php if (!$featuredPosts): ?>
      <p style="color:var(--muted);">Chưa có bài viết nào.</p>
    <?php endif; ?>
    <?php foreach ($featuredPosts as $fp): ?>
      <div class="fcard <?= $fp['is_announcement'] ? 'urgent' : '' ?>">
        <div class="band"></div>
        <div class="fcard-body">
          <span class="badge"><?= $fp['is_announcement'] ? 'Khẩn cấp' : 'Nổi bật' ?></span>
          <h3><a href="post_view.php?id=<?= (int)$fp['id'] ?>"><?= e($fp['title']) ?></a></h3>
          <p><?= e(mb_substr(strip_tags($fp['content']), 0, 90)) ?><?= mb_strlen(strip_tags($fp['content'])) > 90 ? '…' : '' ?></p>
          <div class="meta">
            <span><?= e($fp['full_name'] ?: $fp['username']) ?></span>
            <span><i class="fa-solid fa-heart"></i> <?= (int)$pdo->query('SELECT COUNT(*) FROM likes WHERE post_id=' . (int)$fp['id'])->fetchColumn() ?></span>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</section>
<script>
(function () {
  var slides = document.querySelectorAll('.hero-slide');
  var dotsWrap = document.getElementById('heroDots');
  if (!slides.length || !dotsWrap) return;
  var slideIndex = 0, timer;
  slides.forEach(function (s, i) {
    var dot = document.createElement('button');
    dot.type = 'button';
    if (i === 0) dot.classList.add('active');
    dot.addEventListener('click', function () { goToSlide(i); resetTimer(); });
    dotsWrap.appendChild(dot);
  });
  var dots = dotsWrap.querySelectorAll('button');
  function goToSlide(i) {
    slides[slideIndex].classList.remove('active');
    dots[slideIndex].classList.remove('active');
    slideIndex = i;
    slides[slideIndex].classList.add('active');
    dots[slideIndex].classList.add('active');
  }
  function nextSlide() { goToSlide((slideIndex + 1) % slides.length); }
  function resetTimer() { clearInterval(timer); timer = setInterval(nextSlide, 5000); }
  resetTimer();
  var heroEl = document.getElementById('heroSlides');
  heroEl.addEventListener('mouseenter', function () { clearInterval(timer); });
  heroEl.addEventListener('mouseleave', resetTimer);
})();
</script>
<?php endif; ?>

<div class="container">
<div class="row">
  <section class="left" style="flex:3;min-width:0;">
    <h2><?= $q !== '' ? 'Kết quả tìm kiếm: "' . e($q) . '"' : 'Bảng tin cộng đồng' ?></h2>
    <div class="box">
      <?php if (!$posts): ?>
        <p style="color:var(--muted);">Chưa có bài viết nào.</p>
      <?php endif; ?>
      <?php foreach ($posts as $p): ?>
        <div class="post-card">
          <div class="avatar"><?= e(mb_strtoupper(mb_substr($p['full_name'] ?: $p['username'], 0, 1))) ?></div>
          <div style="flex:1;min-width:0;">
            <div class="post-meta">
              <strong><?= e($p['full_name'] ?: $p['username']) ?></strong>
              <span><?= e(role_label($p['role'])) ?></span>
              <span>· <?= time_ago($p['created_at']) ?></span>
              <?php if ($p['is_announcement']): ?><span class="tag tag-urgent">Khẩn cấp</span><?php endif; ?>
            </div>
            <h3 class="post-title"><a href="post_view.php?id=<?= (int)$p['id'] ?>"><?= e($p['title']) ?></a></h3>
            <div class="post-actions">
              <span><i class="fa-solid fa-heart"></i> <?= (int)$p['like_count'] ?></span>
              <span><i class="fa-solid fa-comment"></i> <?= (int)$p['comment_count'] ?></span>
              <a href="post_view.php?id=<?= (int)$p['id'] ?>">Xem chi tiết</a>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
      <?php pagination_links($page, $totalPages, 'index.php' . ($q !== '' ? '?q=' . urlencode($q) : '')); ?>
    </div>
  </section>

  <aside class="sidebar" style="flex:1;min-width:260px;">
    <div class="box">
      <h3>Lớp học nổi bật</h3>
      <?php foreach ($topClasses as $c): ?>
        <div class="inner-box">
          <div class="avatar-sm"><?= e(mb_strtoupper(mb_substr($c['name'], 0, 1))) ?></div>
          <div class="details" style="font-size:13px;">
            <div><?= e($c['name']) ?></div>
            <span style="color:var(--muted);"><?= (int)$c['members'] ?> thành viên</span>
          </div>
        </div>
      <?php endforeach; ?>
      <?php if (!$topClasses): ?><p style="color:var(--muted);font-size:13px;">Chưa có lớp học nào.</p><?php endif; ?>
    </div>
    <div class="box">
      <h3>Đóng góp nhiều nhất</h3>
      <?php foreach ($topContributors as $c): ?>
        <div class="inner-box">
          <div class="avatar-sm"><?= e(mb_strtoupper(mb_substr($c['full_name'] ?: $c['username'], 0, 1))) ?></div>
          <div class="details" style="font-size:13px;">
            <div><?= e($c['full_name'] ?: $c['username']) ?></div>
            <span style="color:var(--muted);"><?= (int)$c['posts'] ?> bài viết</span>
          </div>
        </div>
      <?php endforeach; ?>
      <?php if (!$topContributors): ?><p style="color:var(--muted);font-size:13px;">Chưa có dữ liệu.</p><?php endif; ?>
    </div>
  </aside>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
