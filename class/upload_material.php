<?php
require_once __DIR__ . '/../includes/functions.php';

require_role(['teacher']);
$user = current_user();

$id = (int) ($_GET['id'] ?? $_POST['class_id'] ?? 0);

if ($id <= 0) {
    http_response_code(400);
    die('Mã lớp học không hợp lệ.');
}

// Chỉ giảng viên quản lý lớp mới được tải tài liệu lên.
$stmt = $pdo->prepare(
    'SELECT id, name
     FROM classes
     WHERE id = ? AND teacher_id = ?'
);
$stmt->execute([$id, $user['id']]);
$class = $stmt->fetch();

if (!$class) {
    http_response_code(403);
    die('Bạn không quản lý lớp học này.');
}

$errors = [];

// Các định dạng tệp được cho phép.
$allowedExtensions = [
    'pdf', 'doc', 'docx',
    'xls', 'xlsx',
    'ppt', 'pptx',
    'jpg', 'jpeg', 'png', 'gif', 'webp',
    'zip', 'rar', '7z'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    if (
        !isset($_FILES['material']) ||
        !is_array($_FILES['material']) ||
        $_FILES['material']['error'] === UPLOAD_ERR_NO_FILE
    ) {
        $errors[] = 'Vui lòng chọn tệp để tải lên.';
    } else {
        $file = $_FILES['material'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Tải lên thất bại. Vui lòng thử lại.';
        } elseif ($file['size'] <= 0) {
            $errors[] = 'Tệp tải lên không hợp lệ hoặc đang trống.';
        } elseif ($file['size'] > MAX_UPLOAD_SIZE) {
            $errors[] = 'Tệp vượt quá dung lượng cho phép (20MB).';
        } else {
            $originalName = basename($file['name']);
            $extension = strtolower(
                pathinfo($originalName, PATHINFO_EXTENSION)
            );

            if (!in_array($extension, $allowedExtensions, true)) {
                $errors[] = 'Định dạng tệp này chưa được hỗ trợ.';
            } else {
                // Tạo tên lưu trữ riêng, không dùng trực tiếp tên tệp gốc.
                $storedName = bin2hex(random_bytes(16)) . '.' . $extension;
                $uploadPath = rtrim(UPLOAD_DIR, '/\\') . DIRECTORY_SEPARATOR . $storedName;

                if (!is_dir(UPLOAD_DIR) || !is_writable(UPLOAD_DIR)) {
                    $errors[] = 'Thư mục tải lên chưa tồn tại hoặc không có quyền ghi.';
                } elseif (!is_uploaded_file($file['tmp_name'])) {
                    $errors[] = 'Tệp tải lên không hợp lệ.';
                } elseif (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                    try {
                        $mimeType = 'application/octet-stream';

                        if (function_exists('finfo_open')) {
                            $finfo = finfo_open(FILEINFO_MIME_TYPE);

                            if ($finfo) {
                                $detectedMime = finfo_file($finfo, $uploadPath);

                                if (is_string($detectedMime) && $detectedMime !== '') {
                                    $mimeType = $detectedMime;
                                }

                                finfo_close($finfo);
                            }
                        }

                        $insert = $pdo->prepare(
                            'INSERT INTO files
                                (class_id, uploader_id, original_name, stored_name, filesize, mime_type)
                             VALUES (?, ?, ?, ?, ?, ?)'
                        );

                        $insert->execute([
                            $id,
                            $user['id'],
                            $originalName,
                            $storedName,
                            (int) $file['size'],
                            $mimeType
                        ]);

                        flash('success', 'Đã tải lên tài liệu.');

                        header('Location: view.php?id=' . $id);
                        exit;
                    } catch (Throwable $exception) {
                        // Xóa tệp nếu không lưu được thông tin vào cơ sở dữ liệu.
                        if (is_file($uploadPath)) {
                            unlink($uploadPath);
                        }

                        $errors[] = 'Không thể lưu thông tin tài liệu. Vui lòng thử lại.';
                    }
                } else {
                    $errors[] = 'Không thể lưu tệp lên máy chủ.';
                }
            }
        }
    }
}

$pageTitle = 'Tải lên tài liệu - UTH Forum';
$pageCss = 'class.css';

require __DIR__ . '/../includes/header.php';?>

<section class="class-upload-page">
    <div class="card class-upload-card">
        <h1>
            Tải lên tài liệu / bài tập cho
            “<?= e($class['name']) ?>”
        </h1>

        <?php foreach ($errors as $error): ?>
            <div class="alert alert-error">
                <?= e($error) ?>
            </div>
        <?php endforeach; ?>

        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">

            <input type="hidden" name="class_id" value="<?= (int) $id ?>">

            <div class="form-group">
                <label for="material-file">
                    Chọn tệp (PDF, Word, Excel, PowerPoint, hình ảnh hoặc tệp nén)
                </label>

                <input type="file" id="material-file" name="material"
                    accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.gif,.webp,.zip,.rar,.7z"
                    required>
            </div>

            <p class="class-upload-note">
                Dung lượng tối đa: 20MB.
            </p>

            <button type="submit" class="btn btn-teal class-upload-button">
                Tải lên
            </button>
        </form>
    </div>
</section>