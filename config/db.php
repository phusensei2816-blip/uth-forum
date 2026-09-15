<?php
/**
 * Database connection (PDO + MySQL)
 * Edit the 4 constants below to match your environment.
 */
define('DB_HOST', 'localhost');
define('DB_NAME', 'uth_forum');
define('DB_USER', 'root');
define('DB_PASS', '');

// Base URL of the site, used for building links (no trailing slash)
define('BASE_URL', '/uth-forum');

// Where uploaded materials are stored on disk / served from
define('UPLOAD_DIR', __DIR__ . '/../uploads/materials/');
define('UPLOAD_URL', 'uploads/materials/');
define('MAX_UPLOAD_SIZE', 20 * 1024 * 1024); // 20 MB

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    error_log('[uth-forum] DB connection failed: ' . $e->getMessage());
    http_response_code(500);
    die('Hệ thống đang bảo trì, vui lòng thử lại sau.');
}
