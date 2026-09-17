<?php
require_once __DIR__ . '/auth.php';

date_default_timezone_set('Asia/Ho_Chi_Minh');

if (!defined('UPLOAD_DIR')) {
    define('UPLOAD_DIR', dirname(__DIR__) . '/uploads/');
}


function e(?string $s): string {
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

/** Store a one-time flash message */
function flash(string $type, string $msg): void {
    $_SESSION['flash'][] = ['type' => $type, 'msg' => $msg];
}

/** Render + clear flash messages */
function render_flashes(): void {
    if (empty($_SESSION['flash'])) return;
    foreach ($_SESSION['flash'] as $f) {
        echo '<div class="alert alert-' . e($f['type']) . '">' . e($f['msg']) . '</div>';
    }
    unset($_SESSION['flash']);
}

/** Very small allow-list HTML sanitizer for rich-text post content */
function sanitize_html(string $html): string {
    $allowed = '<p><br><b><strong><i><em><u><ul><ol><li><a><h1><h2><h3><blockquote><img><span><code><pre>';
    $clean = strip_tags($html, $allowed);
    // strip inline event handlers / javascript: links
    $clean = preg_replace('/on\w+\s*=\s*"[^"]*"/i', '', $clean);
    $clean = preg_replace('/on\w+\s*=\s*\'[^\']*\'/i', '', $clean);
    $clean = preg_replace('/href\s*=\s*"javascript:[^"]*"/i', 'href="#"', $clean);
    return $clean;
}

/** Pagination helper: returns [offset, limit, page, totalPages] */
function paginate(int $totalRows, int $perPage = 10): array {
    $page = max(1, (int)($_GET['page'] ?? 1));
    $totalPages = max(1, (int)ceil($totalRows / $perPage));
    $page = min($page, $totalPages);
    $offset = ($page - 1) * $perPage;
    return [$offset, $perPage, $page, $totalPages];
}

function pagination_links(int $page, int $totalPages, string $baseUrl): void {
    if ($totalPages <= 1) return;
    echo '<div class="pagination">';
    for ($i = 1; $i <= $totalPages; $i++) {
        $active = $i === $page ? ' active' : '';
        $sep = strpos($baseUrl, '?') !== false ? '&' : '?';
        echo '<a class="page' . $active . '" href="' . e($baseUrl . $sep . 'page=' . $i) . '">' . $i . '</a>';
    }
    echo '</div>';
}

function role_label(string $role): string {
    return ['student' => 'Sinh viên', 'teacher' => 'Giảng viên', 'admin' => 'Quản trị viên'][$role] ?? $role;
}

function time_ago(string $datetime): string {
    $diff = time() - strtotime($datetime);
    if ($diff < 60) return 'vừa xong';
    if ($diff < 3600) return floor($diff / 60) . ' phút trước';
    if ($diff < 86400) return floor($diff / 3600) . ' giờ trước';
    if ($diff < 2592000) return floor($diff / 86400) . ' ngày trước';
    return date('d/m/Y', strtotime($datetime));
}

function generate_invite_code(): string {
    return strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
}

/** Log one visit per calendar day (bumps a counter row) */
function log_visit(): void {
    global $pdo;
    $today = date('Y-m-d');
    $stmt = $pdo->prepare('INSERT INTO visits (visited_at, hits) VALUES (?, 1)
                            ON DUPLICATE KEY UPDATE hits = hits + 1');
    $stmt->execute([$today]);
}
