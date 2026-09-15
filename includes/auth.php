<?php
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        // Set to true once the site is served over HTTPS (recommended for production).
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
require_once __DIR__ . '/../config/db.php';

/** Is a user currently logged in? */
function is_logged_in(): bool {
    return isset($_SESSION['user_id']);
}

/** Current user's role, or null if not logged in */
function current_role(): ?string {
    return $_SESSION['role'] ?? null;
}

/** Fetch the full current user row (cached per request) */
function current_user(): ?array {
    static $user = null;
    if (!is_logged_in()) return null;
    if ($user === null) {
        global $pdo;
        $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch() ?: null;
    }
    return $user;
}

/** Redirect to login if not authenticated */
function require_login(): void {
    if (!is_logged_in()) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

/** Redirect home if the current user doesn't have one of the allowed roles */
function require_role(array $roles): void {
    require_login();
    if (!in_array(current_role(), $roles, true)) {
        http_response_code(403);
        die('Bạn không có quyền truy cập trang này.');
    }
}

/** Simple CSRF token helpers */
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}
function csrf_check(): void {
    $token = $_POST['csrf'] ?? '';
    if (!hash_equals($_SESSION['csrf'] ?? '', $token)) {
        http_response_code(400);
        die('Phiên làm việc không hợp lệ, vui lòng tải lại trang.');
    }
}
