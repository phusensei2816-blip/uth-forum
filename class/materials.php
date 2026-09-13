<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();

$user = current_user();
$classId = (int)($_GET['id'] ?? 0);

if ($classId <= 0) {
    http_response_code(404);
    die('Không tìm thấy lớp học.');
}

/*
|--------------------------------------------------------------------------
| Lấy thông tin lớp
|--------------------------------------------------------------------------
*/
$stmt = $pdo->prepare(
    'SELECT c.*, u.full_name AS teacher_name
     FROM classes c
     JOIN users u ON u.id = c.teacher_id
     WHERE c.id = ?'
);
$stmt->execute([$classId]);
$class = $stmt->fetch();

if (!$class) {
    http_response_code(404);
    die('Không tìm thấy lớp học.');
}

/*
|--------------------------------------------------------------------------
| Kiểm tra quyền truy cập lớp
|--------------------------------------------------------------------------
*/
$isOwner = (
    $user['role'] === 'teacher'
    && (int)$class['teacher_id'] === (int)$user['id']
);

$isAdmin = ($user['role'] === 'admin');

$isMember = false;

if ($user['role'] === 'student') {
    $check = $pdo->prepare(
        'SELECT id
         FROM class_members
         WHERE class_id = ? AND user_id = ?'
    );

    $check->execute([
        $classId,
        $user['id']
    ]);

    $isMember = (bool)$check->fetch();
}

if (!$isOwner && !$isAdmin && !$isMember) {
    http_response_code(403);
    die('Bạn chưa tham gia lớp học này.');
}

/*
|--------------------------------------------------------------------------
| Lấy danh sách tài liệu
|--------------------------------------------------------------------------
*/
$stmt = $pdo->prepare(
    'SELECT f.*, u.full_name AS uploader_name, u.username
     FROM files f
     JOIN users u ON u.id = f.uploader_id
     WHERE f.class_id = ?
     ORDER BY f.created_at DESC'
);

$stmt->execute([$classId]);

$materials = $stmt->fetchAll();

/*
|--------------------------------------------------------------------------
| Hàm format dung lượng
|--------------------------------------------------------------------------
*/
function format_filesize(int $bytes): string
{
    if ($bytes < 1024) {
        return $bytes . ' B';
    }

    if ($bytes < 1024 * 1024) {
        return round($bytes / 1024, 1) . ' KB';
    }

    if ($bytes < 1024 * 1024 * 1024) {
        return round($bytes / (1024 * 1024), 1) . ' MB';
    }

    return round($bytes / (1024 * 1024 * 1024), 1) . ' GB';
}

/*
|--------------------------------------------------------------------------
| Icon theo loại file
|--------------------------------------------------------------------------
*/
function material_icon(string $filename): string
{
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

    switch ($ext) {
        case 'pdf':
            return 'fa-file-pdf';

        case 'doc':
        case 'docx':
            return 'fa-file-word';

        case 'xls':
        case 'xlsx':
            return 'fa-file-excel';

        case 'ppt':
        case 'pptx':
            return 'fa-file-powerpoint';

        case 'zip':
        case 'rar':
        case '7z':
            return 'fa-file-zipper';

        case 'jpg':
        case 'jpeg':
        case 'png':
        case 'gif':
        case 'webp':
            return 'fa-file-image';

        case 'txt':
            return 'fa-file-lines';

        default:
            return 'fa-file';
    }
}

$pageTitle = 'Tài liệu - ' . $class['name'];

require __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1>Tài liệu lớp</h1>

        <p class="text-muted">
            <?= e($class['name']) ?>
        </p>
    </div>

    <div style="display:flex; gap:10px; align-items:center;">

        <a
            href="<?= e(BASE_URL) ?>/class/view.php?id=<?= $classId ?>"
            class="btn btn-outline btn-sm"
        >
            Quay lại lớp
        </a>

        <?php if ($isOwner): ?>

            <a
                href="<?= e(BASE_URL) ?>/class/upload_material.php?id=<?= $classId ?>"
                class="btn btn-red btn-sm"
            >
                <i class="fa-solid fa-upload"></i>
                Tải tài liệu
            </a>

        <?php endif; ?>

    </div>
</div>


<?php if (empty($materials)): ?>

    <div class="empty-state">

        <i
            class="fa-solid fa-folder-open"
            style="font-size:40px; margin-bottom:15px;"
        ></i>

        <h3>Chưa có tài liệu</h3>

        <p class="text-muted">
            Hiện tại lớp này chưa có tài liệu nào được tải lên.
        </p>

        <?php if ($isOwner): ?>

            <a
                href="<?= e(BASE_URL) ?>/class/upload_material.php?id=<?= $classId ?>"
                class="btn btn-red"
            >
                Tải tài liệu đầu tiên
            </a>

        <?php endif; ?>

    </div>

<?php else: ?>

    <div class="card">

        <div
            style="
                display:flex;
                justify-content:space-between;
                align-items:center;
                margin-bottom:20px;
            "
        >

            <div>
                <h2 style="margin:0;">
                    Danh sách tài liệu
                </h2>

                <p class="text-muted" style="margin:5px 0 0;">
                    <?= count($materials) ?> tài liệu
                </p>
            </div>

        </div>


        <div style="display:flex; flex-direction:column; gap:12px;">

            <?php foreach ($materials as $material): ?>

                <div
                    class="card"
                    style="
                        margin:0;
                        padding:15px;
                        display:flex;
                        align-items:center;
                        justify-content:space-between;
                        gap:15px;
                    "
                >

                    <div
                        style="
                            display:flex;
                            align-items:center;
                            gap:15px;
                            min-width:0;
                        "
                    >

                        <div
                            style="
                                width:45px;
                                height:45px;
                                border-radius:10px;
                                display:flex;
                                align-items:center;
                                justify-content:center;
                                background:#f3f4f6;
                                flex-shrink:0;
                            "
                        >
                            <i
                                class="fa-solid <?= e(material_icon($material['original_name'])) ?>"
                                style="font-size:22px;"
                            ></i>
                        </div>


                        <div style="min-width:0;">

                            <div
                                style="
                                    font-weight:600;
                                    overflow:hidden;
                                    text-overflow:ellipsis;
                                    white-space:nowrap;
                                "
                            >
                                <?= e($material['original_name']) ?>
                            </div>

                            <div
                                class="text-muted"
                                style="font-size:13px; margin-top:4px;"
                            >
                                <?= format_filesize((int)$material['filesize']) ?>

                                ·

                                <?= e($material['uploader_name'] ?: $material['username']) ?>

                                ·

                                <?= date(
                                    'd/m/Y H:i',
                                    strtotime($material['created_at'])
                                ) ?>
                            </div>

                        </div>

                    </div>


                    <a
                        href="<?= e(BASE_URL) ?>/uploads/materials/<?= e($material['stored_name']) ?>"
                        class="btn btn-outline btn-sm"
                        download
                    >
                        <i class="fa-solid fa-download"></i>
                        Tải xuống
                    </a>

                </div>

            <?php endforeach; ?>

        </div>

    </div>

<?php endif; ?>


<?php require __DIR__ . '/../includes/footer.php'; ?>