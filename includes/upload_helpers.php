<?php
/**
 * Secure file upload helper.
 * Validates real file content (not just the client-supplied name/MIME),
 * enforces an extension whitelist, and writes the file under a random name.
 */

const UPLOAD_ALLOWED = [
    // ext => allowed real MIME type(s) as reported by fileinfo
    'pdf'  => ['application/pdf'],
    'doc'  => ['application/msword'],
    'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
    'xls'  => ['application/vnd.ms-excel'],
    'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'],
    'ppt'  => ['application/vnd.ms-powerpoint'],
    'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/zip'],
    'jpg'  => ['image/jpeg'],
    'jpeg' => ['image/jpeg'],
    'png'  => ['image/png'],
    'gif'  => ['image/gif'],
    'zip'  => ['application/zip', 'application/x-zip-compressed'],
];

/**
 * Validate + move an uploaded file ($_FILES[...] entry) into UPLOAD_DIR.
 *
 * @return array{ok:bool, stored?:string, error?:string}
 */
function secure_store_upload(array $file, string $prefix): array {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => 'Tải lên thất bại.'];
    }
    if ($file['size'] > MAX_UPLOAD_SIZE) {
        return ['ok' => false, 'error' => 'Tệp vượt quá dung lượng cho phép.'];
    }
    // Reject files that aren't genuinely uploaded via HTTP POST (defense in depth)
    if (!is_uploaded_file($file['tmp_name'])) {
        return ['ok' => false, 'error' => 'Tệp không hợp lệ.'];
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!isset(UPLOAD_ALLOWED[$ext])) {
        return ['ok' => false, 'error' => 'Định dạng tệp không được phép.'];
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $realMime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($realMime, UPLOAD_ALLOWED[$ext], true)) {
        return ['ok' => false, 'error' => 'Nội dung tệp không khớp với định dạng khai báo.'];
    }

    $stored = $prefix . '_' . bin2hex(random_bytes(16)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $stored)) {
        return ['ok' => false, 'error' => 'Không thể lưu tệp lên máy chủ.'];
    }
    // Belt-and-braces: strip any execute bit even if umask is loose
    @chmod(UPLOAD_DIR . $stored, 0644);

    return ['ok' => true, 'stored' => $stored, 'mime' => $realMime];
}
