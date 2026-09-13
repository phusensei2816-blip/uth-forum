<?php
require_once __DIR__ . '/includes/functions.php';
require_login();

$user = current_user();

if (!$user) {
    header('Location: ' . BASE_URL . '/logout.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| XỬ LÝ CẬP NHẬT PROFILE
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    csrf_check();

    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');

    $studentCode = trim($_POST['student_code'] ?? '');
    $programType = trim($_POST['program_type'] ?? '');
    $teacherCode = trim($_POST['teacher_code'] ?? '');
    $major = trim($_POST['major'] ?? '');
    $specialization = trim($_POST['specialization'] ?? '');

    /*
    |--------------------------------------------------------------------------
    | VALIDATE THÔNG TIN CƠ BẢN
    |--------------------------------------------------------------------------
    */

    if ($fullName === '') {

        flash('error', 'Vui lòng nhập họ và tên.');

    } elseif (mb_strlen($fullName) > 120) {

        flash('error', 'Họ và tên không được vượt quá 120 ký tự.');

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        flash('error', 'Email không hợp lệ.');

    } elseif (mb_strlen($email) > 120) {

        flash('error', 'Email không được vượt quá 120 ký tự.');

    } else {

        /*
        |--------------------------------------------------------------------------
        | KIỂM TRA EMAIL TRÙNG
        |--------------------------------------------------------------------------
        */

        $check = $pdo->prepare(
            'SELECT id
             FROM users
             WHERE email = ?
             AND id <> ?
             LIMIT 1'
        );

        $check->execute([
            $email,
            $user['id']
        ]);

        if ($check->fetch()) {

            flash(
                'error',
                'Email này đã được sử dụng bởi tài khoản khác.'
            );

        } else {

            /*
            |--------------------------------------------------------------------------
            | XỬ LÝ AVATAR
            |--------------------------------------------------------------------------
            */

            $avatarPath = $user['avatar'] ?? null;
            $newAvatarUploaded = false;

            if (!empty($_FILES['avatar']['name'])) {

                $file = $_FILES['avatar'];

                $maxSize = 2 * 1024 * 1024;

                $allowed = [
                    'image/jpeg' => 'jpg',
                    'image/png'  => 'png',
                    'image/webp' => 'webp',
                    'image/gif'  => 'gif',
                ];

                if ($file['error'] !== UPLOAD_ERR_OK) {

                    flash(
                        'error',
                        'Không thể tải ảnh đại diện lên.'
                    );

                } elseif ($file['size'] > $maxSize) {

                    flash(
                        'error',
                        'Ảnh đại diện tối đa 2MB.'
                    );

                } else {

                    $finfo = new finfo(FILEINFO_MIME_TYPE);
                    $mime = $finfo->file($file['tmp_name']);

                    if (!isset($allowed[$mime])) {

                        flash(
                            'error',
                            'Chỉ chấp nhận ảnh JPG, PNG, WEBP hoặc GIF.'
                        );

                    } else {

                        $dir = __DIR__ . '/uploads/avatars';

                        if (!is_dir($dir)) {
                            mkdir($dir, 0755, true);
                        }

                        $filename =
                            'avatar_' .
                            $user['id'] .
                            '_' .
                            bin2hex(random_bytes(8)) .
                            '.' .
                            $allowed[$mime];

                        $destination = $dir . '/' . $filename;

                        if (!move_uploaded_file(
                            $file['tmp_name'],
                            $destination
                        )) {

                            flash(
                                'error',
                                'Không thể lưu ảnh đại diện.'
                            );

                        } else {

                            $avatarPath =
                                'uploads/avatars/' . $filename;

                            $newAvatarUploaded = true;
                        }
                    }
                }
            }

            /*
            |--------------------------------------------------------------------------
            | KIỂM TRA CÓ LỖI UPLOAD HAY KHÔNG
            |--------------------------------------------------------------------------
            */

            $hasError = false;

            foreach ($_SESSION['flash'] ?? [] as $f) {

                if (($f['type'] ?? '') === 'error') {

                    $hasError = true;
                    break;
                }
            }

            /*
            |--------------------------------------------------------------------------
            | CẬP NHẬT DATABASE
            |--------------------------------------------------------------------------
            */

            if (!$hasError) {

                $upd = $pdo->prepare(
                    "UPDATE users
                     SET full_name = ?,
                         student_code = ?,
                         program_type = ?,
                         teacher_code = ?,
                         major = ?,
                         specialization = ?,
                         email = ?,
                         avatar = ?
                     WHERE id = ?"
                );

                $upd->execute([
                    $fullName,

                    $studentCode !== ''
                        ? $studentCode
                        : null,

                    $programType !== ''
                        ? $programType
                        : null,

                    $teacherCode !== ''
                        ? $teacherCode
                        : null,

                    $major !== ''
                        ? $major
                        : null,

                    $specialization !== ''
                        ? $specialization
                        : null,

                    $email,

                    $avatarPath,

                    $user['id']
                ]);

                /*
                |--------------------------------------------------------------------------
                | XÓA AVATAR CŨ
                |--------------------------------------------------------------------------
                */

                if (
                    $newAvatarUploaded &&
                    !empty($user['avatar'])
                ) {

                    $old =
                        __DIR__ .
                        '/' .
                        ltrim($user['avatar'], '/');

                    $avatarDir =
                        realpath(
                            __DIR__ . '/uploads/avatars'
                        );

                    $oldRealPath =
                        realpath($old);

                    if (
                        is_file($old) &&
                        $avatarDir &&
                        $oldRealPath &&
                        strpos(
                            $oldRealPath,
                            $avatarDir
                        ) === 0
                    ) {

                        @unlink($old);
                    }
                }

                flash(
                    'success',
                    'Cập nhật thông tin cá nhân thành công.'
                );
            }
        }
    }

    header(
        'Location: ' .
        BASE_URL .
        '/profile.php'
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| LẤY USER MỚI NHẤT
|--------------------------------------------------------------------------
*/

$user = current_user();


/*
|--------------------------------------------------------------------------
| THỐNG KÊ BÀI VIẾT
|--------------------------------------------------------------------------
*/

$postStmt = $pdo->prepare(
    "SELECT COUNT(*)
     FROM posts
     WHERE user_id = ?
     AND status = 'approved'"
);

$postStmt->execute([
    $user['id']
]);

$postCount = (int)$postStmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| THỐNG KÊ BÌNH LUẬN
|--------------------------------------------------------------------------
*/

$commentStmt = $pdo->prepare(
    'SELECT COUNT(*)
     FROM comments
     WHERE user_id = ?'
);

$commentStmt->execute([
    $user['id']
]);

$commentCount = (int)$commentStmt->fetchColumn();


/*
|--------------------------------------------------------------------------
| LỚP HỌC
|--------------------------------------------------------------------------
*/

$classes = [];
$pendingCount = 0;


/*
|--------------------------------------------------------------------------
| GIẢNG VIÊN
|--------------------------------------------------------------------------
*/

if ($user['role'] === 'teacher') {

    $stmt = $pdo->prepare(
        "SELECT c.*,
                COUNT(cm.id) AS members
         FROM classes c
         LEFT JOIN class_members cm
             ON cm.class_id = c.id
         WHERE c.teacher_id = ?
         GROUP BY c.id
         ORDER BY c.created_at DESC"
    );

    $stmt->execute([
        $user['id']
    ]);

    $classes = $stmt->fetchAll();


    /*
    |--------------------------------------------------------------------------
    | BÀI ĐANG CHỜ DUYỆT
    |--------------------------------------------------------------------------
    */

    $pending = $pdo->prepare(
        "SELECT COUNT(*)
         FROM posts p
         JOIN classes c
             ON c.id = p.class_id
         WHERE c.teacher_id = ?
         AND p.status = 'pending'"
    );

    $pending->execute([
        $user['id']
    ]);

    $pendingCount =
        (int)$pending->fetchColumn();
}


/*
|--------------------------------------------------------------------------
| SINH VIÊN
|--------------------------------------------------------------------------
*/

elseif ($user['role'] === 'student') {

    $stmt = $pdo->prepare(
        "SELECT c.*,
                u.full_name AS teacher_name,
                u.username AS teacher_username
         FROM classes c
         JOIN class_members cm
             ON cm.class_id = c.id
         JOIN users u
             ON u.id = c.teacher_id
         WHERE cm.user_id = ?
         ORDER BY c.created_at DESC"
    );

    $stmt->execute([
        $user['id']
    ]);

    $classes = $stmt->fetchAll();
}


/*
|--------------------------------------------------------------------------
| LẤY DANH SÁCH CHƯƠNG TRÌNH / NGÀNH
|--------------------------------------------------------------------------
*/

$majorRows = $pdo->query(
    "SELECT program_type,
            major_name,
            specialization
     FROM uth_majors
     ORDER BY program_type, major_name"
)->fetchAll();


/*
|--------------------------------------------------------------------------
| TIÊU ĐỀ TRANG
|--------------------------------------------------------------------------
*/

$pageTitle =
    $user['role'] === 'teacher'
        ? 'Hồ sơ giảng viên'
        : (
            $user['role'] === 'student'
                ? 'Hồ sơ sinh viên'
                : 'Thông tin cá nhân'
        );


require __DIR__ . '/includes/header.php';
?>


<div class="profile-page">

    <div class="profile-cover"></div>


    <!-- =========================================================
         PROFILE HEADER
    ========================================================== -->

    <section class="profile-card profile-main-card">

        <div class="profile-main-top">

            <div class="profile-large-avatar">

                <?php if (!empty($user['avatar'])): ?>

                    <img
                        src="<?= e(
                            BASE_URL .
                            '/' .
                            ltrim($user['avatar'], '/')
                        ) ?>"
                        alt="Ảnh đại diện"
                    >

                <?php else: ?>

                    <?= e(
                        mb_strtoupper(
                            mb_substr(
                                $user['full_name']
                                    ?: $user['username'],
                                0,
                                1
                            )
                        )
                    ) ?>

                <?php endif; ?>

            </div>


            <div class="profile-heading">

                <div class="profile-role-pill">

                    <i class="fa-solid fa-circle-check"></i>

                    <?= e(
                        role_label($user['role'])
                    ) ?>

                </div>

                <h1>
                    <?= e(
                        $user['full_name']
                            ?: $user['username']
                    ) ?>
                </h1>

                <p>
                    @<?= e($user['username']) ?>

                    · Tham gia từ

                    <?= e(
                        date(
                            'd/m/Y',
                            strtotime(
                                $user['created_at']
                            )
                        )
                    ) ?>
                </p>

            </div>

        </div>


        <!-- =====================================================
             STATS
        ====================================================== -->

        <div class="profile-stats">

            <div>
                <strong><?= $postCount ?></strong>
                <span>Bài viết</span>
            </div>

            <div>
                <strong><?= $commentCount ?></strong>
                <span>Bình luận</span>
            </div>

            <div>
                <strong><?= count($classes) ?></strong>

                <span>
                    <?= $user['role'] === 'teacher'
                        ? 'Lớp phụ trách'
                        : 'Lớp tham gia'
                    ?>
                </span>
            </div>

            <?php if ($user['role'] === 'teacher'): ?>

                <div>
                    <strong><?= $pendingCount ?></strong>
                    <span>Bài chờ duyệt</span>
                </div>

            <?php endif; ?>

        </div>

    </section>


    <!-- =========================================================
         PROFILE GRID
    ========================================================== -->

    <div class="profile-grid">


        <!-- =====================================================
             FORM
        ====================================================== -->

        <section class="profile-card">

            <div class="profile-section-title">

                <div>

                    <h2>
                        Thông tin cá nhân
                    </h2>

                    <p>
                        Cập nhật thông tin hiển thị trên diễn đàn
                    </p>

                </div>

            </div>


            <form
                method="post"
                enctype="multipart/form-data"
                class="profile-form"
            >

                <input
                    type="hidden"
                    name="csrf"
                    value="<?= e(csrf_token()) ?>"
                >


                <!-- =================================================
                     AVATAR
                ================================================== -->

                <div class="profile-avatar-upload">

                    <div class="profile-form-avatar">

                        <?php if (!empty($user['avatar'])): ?>

                            <img
                                src="<?= e(
                                    BASE_URL .
                                    '/' .
                                    ltrim(
                                        $user['avatar'],
                                        '/'
                                    )
                                ) ?>"
                                alt="Ảnh đại diện"
                            >

                        <?php else: ?>

                            <?= e(
                                mb_strtoupper(
                                    mb_substr(
                                        $user['full_name']
                                            ?: $user['username'],
                                        0,
                                        1
                                    )
                                )
                            ) ?>

                        <?php endif; ?>

                    </div>


                    <div>

                        <label
                            class="profile-upload-btn"
                            for="avatarInput"
                        >

                            <i class="fa-solid fa-camera"></i>

                            Đổi ảnh đại diện

                        </label>

                        <input
                            id="avatarInput"
                            type="file"
                            name="avatar"
                            accept="image/jpeg,image/png,image/webp,image/gif"
                            hidden
                        >

                        <small>
                            JPG, PNG, WEBP hoặc GIF · tối đa 2MB
                        </small>

                    </div>

                </div>


                <!-- =================================================
                     FORM GRID
                ================================================== -->

                <div class="profile-form-grid">


                    <!-- HỌ TÊN -->

                    <div class="form-group">

                        <label>
                            Họ và tên
                        </label>

                        <input
                            type="text"
                            name="full_name"
                            value="<?= e(
                                $user['full_name'] ?? ''
                            ) ?>"
                            maxlength="120"
                            required
                        >

                    </div>


                    <!-- EMAIL -->

                    <div class="form-group">

                        <label>
                            Email
                        </label>

                        <input
                            type="email"
                            name="email"
                            value="<?= e(
                                $user['email']
                            ) ?>"
                            maxlength="120"
                            required
                        >

                    </div>


                    <!-- USERNAME -->

                    <div class="form-group">

                        <label>
                            Tên đăng nhập
                        </label>

                        <input
                            type="text"
                            value="<?= e(
                                $user['username']
                            ) ?>"
                            disabled
                        >

                    </div>


                    <!-- ROLE -->

                    <div class="form-group">

                        <label>
                            Vai trò
                        </label>

                        <input
                            type="text"
                            value="<?= e(
                                role_label(
                                    $user['role']
                                )
                            ) ?>"
                            disabled
                        >

                    </div>


                    <!-- =================================================
                         SINH VIÊN
                    ================================================== -->

                    <?php if ($user['role'] === 'student'): ?>


                        <!-- MSSV -->

                        <div class="form-group">

                            <label>
                                Mã số sinh viên
                            </label>

                            <input
                                type="text"
                                name="student_code"
                                value="<?= e(
                                    $user['student_code'] ?? ''
                                ) ?>"
                                maxlength="30"
                                placeholder="Nhập mã số sinh viên"
                            >

                        </div>


                        <!-- CHƯƠNG TRÌNH -->

                        <div class="form-group">

                            <label>
                                Chương trình
                            </label>

                            <select
                                name="program_type"
                                id="programType"
                                required
                            >

                                <option value="">
                                    -- Chọn chương trình --
                                </option>


                                <?php

                                $programs = [];

                                foreach ($majorRows as $row) {

                                    if (
                                        !in_array(
                                            $row['program_type'],
                                            $programs,
                                            true
                                        )
                                    ) {

                                        $programs[] =
                                            $row['program_type'];
                                    }
                                }

                                foreach ($programs as $program):

                                ?>

                                    <option
                                        value="<?= e($program) ?>"
                                        <?= (
                                            ($user['program_type'] ?? '')
                                            === $program
                                        )
                                            ? 'selected'
                                            : ''
                                        ?>
                                    >

                                        <?= e($program) ?>

                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>


                        <!-- NGÀNH -->

                        <div class="form-group">

                            <label>
                                Ngành
                            </label>

                            <select
                                name="major"
                                id="majorSelect"
                                required
                            >

                                <option value="">
                                    -- Chọn ngành --
                                </option>

                            </select>

                        </div>


                        <!-- CHUYÊN NGÀNH -->

                        <div class="form-group">

                            <label>
                                Chuyên ngành
                            </label>

                            <select
                                name="specialization"
                                id="specializationSelect"
                            >

                                <option value="">
                                    -- Chọn chuyên ngành --
                                </option>

                            </select>

                        </div>


                    <!-- =================================================
                         GIẢNG VIÊN
                    ================================================== -->

                    <?php elseif ($user['role'] === 'teacher'): ?>


                        <div class="form-group">

                            <label>
                                Mã số giáo viên
                            </label>

                            <input
                                type="text"
                                name="teacher_code"
                                value="<?= e(
                                    $user['teacher_code'] ?? ''
                                ) ?>"
                                maxlength="30"
                                placeholder="Nhập mã số giáo viên"
                            >

                        </div>


                    <?php endif; ?>


                </div>


                <!-- =================================================
                     BUTTON
                ================================================== -->

                <div class="profile-form-actions">

                    <a
                        class="btn btn-outline"
                        href="<?= e(
                            BASE_URL
                        ) ?>/index.php"
                    >
                        Hủy
                    </a>

                    <button
                        class="btn btn-teal"
                        type="submit"
                    >

                        <i class="fa-solid fa-floppy-disk"></i>

                        Lưu thay đổi

                    </button>

                </div>

            </form>

        </section>


        <!-- =========================================================
             ROLE CARD
        ========================================================== -->

        <aside>

            <section class="profile-card profile-role-card">

                <h2>
                    <?= $user['role'] === 'teacher'
                        ? 'Hồ sơ giảng viên'
                        : (
                            $user['role'] === 'student'
                                ? 'Hồ sơ sinh viên'
                                : 'Tài khoản'
                        )
                    ?>
                </h2>


                <?php if ($user['role'] === 'teacher'): ?>

                    <p class="profile-role-desc">
                        Quản lý lớp học, đăng thông báo và duyệt bài viết của sinh viên.
                    </p>

                    <div class="profile-info-row">

                        <span>
                            <i class="fa-solid fa-chalkboard-user"></i>
                            Lớp phụ trách
                        </span>

                        <strong>
                            <?= count($classes) ?>
                        </strong>

                    </div>

                    <div class="profile-info-row">

                        <span>
                            <i class="fa-solid fa-clock"></i>
                            Bài chờ duyệt
                        </span>

                        <strong>
                            <?= $pendingCount ?>
                        </strong>

                    </div>


                <?php elseif ($user['role'] === 'student'): ?>

                    <p class="profile-role-desc">
                        Tham gia lớp học, trao đổi bài viết, bình luận và theo dõi thông báo từ giảng viên.
                    </p>

                    <div class="profile-info-row">

                        <span>
                            <i class="fa-solid fa-users"></i>
                            Lớp đã tham gia
                        </span>

                        <strong>
                            <?= count($classes) ?>
                        </strong>

                    </div>

                    <div class="profile-info-row">

                        <span>
                            <i class="fa-solid fa-pen"></i>
                            Bài viết
                        </span>

                        <strong>
                            <?= $postCount ?>
                        </strong>

                    </div>


                <?php else: ?>

                    <p class="profile-role-desc">
                        Tài khoản quản trị diễn đàn.
                    </p>

                <?php endif; ?>

            </section>


            <!-- =====================================================
                 SECURITY
            ====================================================== -->

            <section class="profile-card profile-security-card">

                <div class="profile-security-icon">

                    <i class="fa-solid fa-shield-halved"></i>

                </div>

                <div>

                    <h3>
                        Bảo mật tài khoản
                    </h3>

                    <p>
                        Đổi mật khẩu định kỳ để bảo vệ tài khoản của bạn.
                    </p>

                    <a
                        href="<?= e(
                            BASE_URL
                        ) ?>/change_password.php"
                    >
                        Đổi mật khẩu →
                    </a>

                </div>

            </section>

        </aside>

    </div>


    <!-- =========================================================
         CLASS LIST
    ========================================================== -->

    <section class="profile-card profile-classes-card">

        <div class="profile-section-title">

            <div>

                <h2>
                    <?= $user['role'] === 'teacher'
                        ? 'Các lớp đang phụ trách'
                        : 'Các lớp đã tham gia'
                    ?>
                </h2>

                <p>
                    <?= $user['role'] === 'teacher'
                        ? 'Danh sách các lớp bạn đang quản lý.'
                        : 'Danh sách các lớp học của bạn.'
                    ?>
                </p>

            </div>

        </div>


        <?php if ($classes): ?>

            <div class="profile-class-list">

                <?php foreach ($classes as $class): ?>

                    <a
                        class="profile-class-item"
                        href="<?= e(
                            BASE_URL
                        ) ?>/class/view.php?id=<?= (int)$class['id'] ?>"
                    >

                        <div class="profile-class-icon">

                            <i class="fa-solid fa-book-open"></i>

                        </div>


                        <div class="profile-class-info">

                            <strong>
                                <?= e(
                                    $class['name']
                                ) ?>
                            </strong>


                            <span>

                                <?php if ($user['role'] === 'teacher'): ?>

                                    <?= (int)(
                                        $class['members'] ?? 0
                                    ) ?>

                                    thành viên

                                    · Mã lớp:

                                    <?= e(
                                        $class['invite_code']
                                    ) ?>


                                <?php else: ?>

                                    Giảng viên:

                                    <?= e(
                                        $class['teacher_name']
                                            ?: $class['teacher_username']
                                    ) ?>

                                <?php endif; ?>

                            </span>

                        </div>


                        <i class="fa-solid fa-chevron-right"></i>

                    </a>

                <?php endforeach; ?>

            </div>


        <?php else: ?>

            <div class="profile-empty">

                <i class="fa-regular fa-folder-open"></i>

                <strong>

                    <?= $user['role'] === 'teacher'
                        ? 'Chưa có lớp phụ trách'
                        : 'Chưa tham gia lớp nào'
                    ?>

                </strong>

                <p>

                    <?= $user['role'] === 'teacher'
                        ? 'Tạo lớp đầu tiên để bắt đầu quản lý sinh viên.'
                        : 'Tham gia một lớp để xem tài liệu và trao đổi với giảng viên.'
                    ?>

                </p>

            </div>

        <?php endif; ?>

    </section>

</div>


<!-- =============================================================
     AVATAR JAVASCRIPT
============================================================= -->

<script>

document
    .getElementById('avatarInput')
    ?.addEventListener('change', function () {

        const file = this.files?.[0];

        if (
            file &&
            file.size > 2 * 1024 * 1024
        ) {

            alert(
                'Ảnh đại diện tối đa 2MB.'
            );

            this.value = '';
        }

    });

</script>


<!-- =============================================================
     PROGRAM / MAJOR / SPECIALIZATION JAVASCRIPT
============================================================= -->

<script>

const majorData = <?= json_encode(
    $majorRows,
    JSON_UNESCAPED_UNICODE |
    JSON_UNESCAPED_SLASHES
) ?>;


const programType =
    document.getElementById('programType');

const majorSelect =
    document.getElementById('majorSelect');

const specializationSelect =
    document.getElementById(
        'specializationSelect'
    );


const currentMajor = <?= json_encode(
    $user['major'] ?? '',
    JSON_UNESCAPED_UNICODE
) ?>;


const currentSpecialization = <?= json_encode(
    $user['specialization'] ?? '',
    JSON_UNESCAPED_UNICODE
) ?>;


/*
|--------------------------------------------------------------------------
| LOAD NGÀNH
|--------------------------------------------------------------------------
*/

function loadMajors() {

    if (
        !programType ||
        !majorSelect ||
        !specializationSelect
    ) {
        return;
    }


    const selectedProgram =
        programType.value;


    majorSelect.innerHTML =
        '<option value="">-- Chọn ngành --</option>';


    specializationSelect.innerHTML =
        '<option value="">-- Chọn chuyên ngành --</option>';


    if (!selectedProgram) {
        return;
    }


    const majors =
        majorData.filter(function (row) {

            return row.program_type === selectedProgram;

        });


    const uniqueMajors = [];


    majors.forEach(function (row) {

        if (
            !uniqueMajors.includes(
                row.major_name
            )
        ) {

            uniqueMajors.push(
                row.major_name
            );
        }

    });


    uniqueMajors.forEach(function (major) {

        const option =
            document.createElement('option');


        option.value = major;

        option.textContent = major;


        if (major === currentMajor) {

            option.selected = true;

        }


        majorSelect.appendChild(
            option
        );

    });


    loadSpecializations();

}


/*
|--------------------------------------------------------------------------
| LOAD CHUYÊN NGÀNH
|--------------------------------------------------------------------------
*/

function loadSpecializations() {

    if (
        !programType ||
        !majorSelect ||
        !specializationSelect
    ) {
        return;
    }


    const selectedProgram =
        programType.value;


    const selectedMajor =
        majorSelect.value;


    specializationSelect.innerHTML =
        '<option value="">-- Chọn chuyên ngành --</option>';


    if (
        !selectedProgram ||
        !selectedMajor
    ) {
        return;
    }


    const row =
        majorData.find(function (item) {

            return (
                item.program_type === selectedProgram &&
                item.major_name === selectedMajor
            );

        });


    if (
        !row ||
        !row.specialization
    ) {
        return;
    }


    const specializations =
        row.specialization
            .split(';')
            .map(function (item) {

                return item.trim();

            })
            .filter(function (item) {

                return item !== '';

            });


    specializations.forEach(
        function (specialization) {

            const option =
                document.createElement('option');


            option.value =
                specialization;

            option.textContent =
                specialization;


            if (
                specialization ===
                currentSpecialization
            ) {

                option.selected = true;

            }


            specializationSelect.appendChild(
                option
            );

        }
    );

}


/*
|--------------------------------------------------------------------------
| EVENT
|--------------------------------------------------------------------------
*/

if (programType) {

    programType.addEventListener(
        'change',
        function () {

            loadMajors();

        }
    );

}


if (majorSelect) {

    majorSelect.addEventListener(
        'change',
        function () {

            loadSpecializations();

        }
    );

}


/*
|--------------------------------------------------------------------------
| LOAD DỮ LIỆU ĐÃ LƯU
|--------------------------------------------------------------------------
*/

if (
    programType &&
    programType.value
) {

    loadMajors();

}

</script>


<?php require __DIR__ . '/includes/footer.php'; ?>