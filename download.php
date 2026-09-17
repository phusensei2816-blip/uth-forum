<?php
require_once __DIR__ . '/includes/functions.php';

// Kiểm tra đăng nhập
require_login();

$id = (int)($_GET['id'] ?? 0);

// Lấy thông tin tệp từ cơ sở dữ liệu
$stmt = $pdo->prepare('SELECT * FROM files WHERE id = ?');
$stmt->execute([$id]);
$file = $stmt->fetch();

if (!$file) {
    http_response_code(404);
    die('Tệp không tồn tại.');
}

$filePath = UPLOAD_DIR . $file['stored_name'];

if (!file_exists($filePath)) {
    http_response_code(404);
    die('Không tìm thấy tệp trên hệ thống.');
}

// Làm sạch bộ đệm để tránh dính ký tự thừa làm hỏng file
if (ob_get_level()) {
    ob_end_clean();
}

// Thiết lập Header ép tải xuống đúng tên và định dạng file gốc
header('Content-Description: File Transfer');
header('Content-Type: ' . ($file['mime_type'] ?: 'application/octet-stream'));
header('Content-Disposition: attachment; filename="' . basename($file['original_name']) . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($filePath));

// Đọc và xuất file
readfile($filePath);
exit;