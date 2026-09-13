<?php
require_once __DIR__ . '/includes/functions.php';
require_role(['student', 'teacher']);
$user = current_user();

// Classes the user can attach the post to
if ($user['role'] === 'teacher') {
    $stmt = $pdo->prepare('SELECT id, name FROM classes WHERE teacher_id = ?');
    $stmt->execute([$user['id']]);
} else {
    $stmt = $pdo->prepare('
        SELECT c.id, c.name
        FROM classes c
        JOIN class_members cm ON cm.class_id = c.id
        WHERE cm.user_id = ?
    ');
    $stmt->execute([$user['id']]);
}

$classes = $stmt->fetchAll();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $title = trim($_POST['title'] ?? '');
    $content = sanitize_html($_POST['content'] ?? '');

    // class_id có thể không tồn tại nếu người dùng chưa tham gia lớp
    $classId = isset($_POST['class_id']) && $_POST['class_id'] !== ''
        ? (int) $_POST['class_id']
        : null;

    // Chỉ giáo viên mới có quyền đăng thông báo khẩn
    $isAnnouncement = (
        $user['role'] === 'teacher' &&
        isset($_POST['is_announcement'])
    );

    // Validate tiêu đề
    if ($title === '') {
        $errors[] = 'Vui lòng nhập tiêu đề.';
    }

    // Validate nội dung
    if (trim(strip_tags($content)) === '') {
        $errors[] = 'Nội dung không được để trống.';
    }

    // Kiểm tra quyền truy cập lớp
    if ($classId !== null) {

        if ($user['role'] === 'teacher') {

            // Giáo viên chỉ được đăng vào lớp mình quản lý
            $stmt = $pdo->prepare(
                'SELECT id
                 FROM classes
                 WHERE id = ? AND teacher_id = ?'
            );

            $stmt->execute([
                $classId,
                $user['id']
            ]);

        } else {

            // Sinh viên chỉ được đăng vào lớp mình đã tham gia
            $stmt = $pdo->prepare(
                'SELECT c.id
                 FROM classes c
                 JOIN class_members cm
                    ON cm.class_id = c.id
                 WHERE c.id = ?
                   AND cm.user_id = ?'
            );

            $stmt->execute([
                $classId,
                $user['id']
            ]);
        }

        // Không có quyền đăng vào lớp này
        if (!$stmt->fetch()) {
            $errors[] = 'Bạn không có quyền đăng bài vào lớp này.';
            $classId = null;
        }
    }

    // Nếu không có lỗi thì tiến hành tạo bài viết
    if (!$errors) {

        // Giáo viên: tự động duyệt
        // Sinh viên: chờ admin duyệt
        $status = $user['role'] === 'teacher'
            ? 'approved'
            : 'pending';

        $stmt = $pdo->prepare(
            'INSERT INTO posts
                (user_id, class_id, title, content, is_announcement, status)
             VALUES
                (?, ?, ?, ?, ?, ?)'
        );

        $stmt->execute([
            $user['id'],
            $classId,
            $title,
            $content,
            $isAnnouncement ? 1 : 0,
            $status
        ]);

        $postId = $pdo->lastInsertId();

        // =========================
        // File đính kèm
        // =========================
        if (!empty($_FILES['attachment']['name'])) {

            $f = $_FILES['attachment'];

            if (
                $f['error'] === UPLOAD_ERR_OK &&
                $f['size'] <= MAX_UPLOAD_SIZE
            ) {

                $ext = strtolower(
                    pathinfo($f['name'], PATHINFO_EXTENSION)
                );

                // Chỉ cho phép một số định dạng file
                $allowedExtensions = [
                    'pdf',
                    'doc',
                    'docx',
                    'xls',
                    'xlsx',
                    'ppt',
                    'pptx',
                    'jpg',
                    'jpeg',
                    'png',
                    'gif',
                    'zip'
                ];

                if (in_array($ext, $allowedExtensions, true)) {

                    $safeExt = preg_replace(
                        '/[^a-zA-Z0-9]/',
                        '',
                        $ext
                    );

                    $stored = uniqid('post_', true) . '.' . $safeExt;

                    if (
                        move_uploaded_file(
                            $f['tmp_name'],
                            UPLOAD_DIR . $stored
                        )
                    ) {

                        $ins = $pdo->prepare(
                            'INSERT INTO files
                                (
                                    post_id,
                                    uploader_id,
                                    original_name,
                                    stored_name,
                                    filesize,
                                    mime_type
                                )
                             VALUES
                                (?, ?, ?, ?, ?, ?)'
                        );

                        $ins->execute([
                            $postId,
                            $user['id'],
                            $f['name'],
                            $stored,
                            $f['size'],
                            $f['type']
                        ]);
                    }

                } else {
                    $errors[] = 'Định dạng file không được hỗ trợ.';
                }
            }
        }

        // Thông báo
        flash(
            'success',
            $status === 'approved'
                ? 'Đã đăng bài thành công.'
                : 'Bài viết đã được gửi và đang chờ quản trị viên duyệt.'
        );

        header('Location: post_view.php?id=' . $postId);
        exit;
    }
}

$pageTitle = (
    $user['role'] === 'teacher'
        ? 'Đăng thông báo'
        : 'Đăng bài'
) . ' - UTH Forum';

require __DIR__ . '/includes/header.php';
?>

<section class="row" style="justify-content:center;">
    <div class="card" style="max-width:700px;width:100%;">

        <h2>
            <?= $user['role'] === 'teacher'
                ? 'Đăng thông báo / tài liệu'
                : 'Tạo bài viết mới'
            ?>
        </h2>

        <?php if ($errors): ?>
            <?php foreach ($errors as $err): ?>
                <div class="alert alert-error">
                    <?= e($err) ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php if ($user['role'] === 'student'): ?>
            <div class="alert alert-info">
                Bài viết của bạn sẽ hiển thị công khai sau khi quản trị viên phê duyệt.
            </div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data">

            <input
                type="hidden"
                name="csrf"
                value="<?= e(csrf_token()) ?>"
            >

            <!-- Tiêu đề -->
            <div class="form-group">
                <label>Tiêu đề</label>

                <input
                    type="text"
                    name="title"
                    required
                    value="<?= e($_POST['title'] ?? '') ?>"
                >
            </div>

            <!-- Chọn lớp -->
            <div class="form-group">

                <label>
                    Đăng vào lớp học
                    <span>(không bắt buộc)</span>
                </label>

                <?php if ($classes): ?>

                    <select name="class_id">

                        <option value="">
                            -- Bảng tin chung --
                        </option>

                        <?php foreach ($classes as $c): ?>

                            <option
                                value="<?= (int)$c['id'] ?>"
                                <?= (
                                    ($_POST['class_id'] ?? '') == $c['id']
                                ) ? 'selected' : '' ?>
                            >
                                <?= e($c['name']) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                <?php else: ?>

                    <div class="empty-field">
                        Bạn chưa tham gia lớp học nào.

                        <a href="class/join.php">
                            Tham gia lớp
                        </a>
                    </div>

                <?php endif; ?>

            </div>

            <!-- Thông báo khẩn dành cho giáo viên -->
            <?php if ($user['role'] === 'teacher'): ?>

                <div class="form-group check">

                    <input
                        type="checkbox"
                        name="is_announcement"
                        id="is_announcement"
                    >

                    <label
                        for="is_announcement"
                        style="margin:0;"
                    >
                        Gắn cờ là thông báo khẩn cấp
                    </label>

                </div>

            <?php endif; ?>

            <!-- Nội dung -->
            <div class="form-group">

                <label>Nội dung</label>

                <div
                    class="editor-toolbar"
                    data-target="editor"
                >

                    <button
                        type="button"
                        data-cmd="bold"
                        title="Đậm"
                    >
                        <b>B</b>
                    </button>

                    <button
                        type="button"
                        data-cmd="italic"
                        title="Nghiêng"
                    >
                        <i>I</i>
                    </button>

                    <button
                        type="button"
                        data-cmd="underline"
                        title="Gạch chân"
                    >
                        <u>U</u>
                    </button>

                    <button
                        type="button"
                        data-cmd="insertUnorderedList"
                        title="Danh sách"
                    >
                        &#8226;
                    </button>

                    <button
                        type="button"
                        data-cmd="insertOrderedList"
                        title="Danh sách số"
                    >
                        1.
                    </button>

                    <button
                        type="button"
                        data-cmd="createLink"
                        title="Chèn liên kết"
                    >
                        &#128279;
                    </button>

                </div>

                <div
                    id="editor"
                    class="editor-content"
                    contenteditable="true"
                ></div>

                <textarea
                    id="editor_hidden"
                    name="content"
                    style="display:none;"
                ></textarea>

            </div>

            <!-- File -->
            <div class="form-group">

                <label>
                    Tệp đính kèm
                    <span>(không bắt buộc)</span>
                </label>

                <input
                    type="file"
                    name="attachment"
                >

            </div>

            <button
                type="submit"
                class="btn btn-red"
            >
                Đăng bài
            </button>

        </form>

    </div>
</section>

<script src="js/main.js"></script>

<?php require __DIR__ . '/includes/footer.php'; ?>