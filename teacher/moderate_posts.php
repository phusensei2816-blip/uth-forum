<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role(['teacher']);

$user = current_user();
$teacherId = (int)$user['id'];

/*
|--------------------------------------------------------------------------
| XỬ LÝ DUYỆT / TỪ CHỐI BÀI VIẾT
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';
    $postId = (int)($_POST['post_id'] ?? 0);

    if ($postId <= 0) {
        flash('error', 'Bài viết không hợp lệ.');
        header('Location: ' . BASE_URL . '/teacher/moderate_posts.php');
        exit;
    }

    /*
     * Kiểm tra bài viết có thuộc lớp mà giáo viên này quản lý không
     */
    $stmt = $pdo->prepare(
        "SELECT p.id, p.title, p.status, p.class_id
         FROM posts p
         JOIN classes c ON c.id = p.class_id
         WHERE p.id = ?
           AND c.teacher_id = ?"
    );

    $stmt->execute([
        $postId,
        $teacherId
    ]);

    $post = $stmt->fetch();

    if (!$post) {
        flash('error', 'Bạn không có quyền xử lý bài viết này.');
        header('Location: ' . BASE_URL . '/teacher/moderate_posts.php');
        exit;
    }

    /*
     * DUYỆT BÀI
     */
    if ($action === 'approve') {

        $stmt = $pdo->prepare(
            "UPDATE posts
             SET status = 'approved',
                 reject_reason = NULL
             WHERE id = ?"
        );

        $stmt->execute([$postId]);

        flash(
            'success',
            'Đã duyệt bài viết "' . $post['title'] . '".'
        );
    }

    /*
     * TỪ CHỐI BÀI
     */
    elseif ($action === 'reject') {

        $reason = trim($_POST['reject_reason'] ?? '');

        if ($reason === '') {
            $reason = 'Bài viết chưa phù hợp với nội dung của lớp.';
        }

        $reason = mb_substr($reason, 0, 255);

        $stmt = $pdo->prepare(
            "UPDATE posts
             SET status = 'rejected',
                 reject_reason = ?
             WHERE id = ?"
        );

        $stmt->execute([
            $reason,
            $postId
        ]);

        flash(
            'success',
            'Đã từ chối bài viết "' . $post['title'] . '".'
        );
    }

    else {
        flash('error', 'Thao tác không hợp lệ.');
    }

    /*
     * Quay lại trang duyệt bài
     */
    $redirect = BASE_URL . '/teacher/moderate_posts.php';

    if (!empty($_POST['class_id'])) {
        $redirect .= '?class_id=' . (int)$_POST['class_id'];
    }

    header('Location: ' . $redirect);
    exit;
}


/*
|--------------------------------------------------------------------------
| LỌC THEO LỚP
|--------------------------------------------------------------------------
*/

$classId = (int)($_GET['class_id'] ?? 0);

if ($classId > 0) {

    /*
     * Kiểm tra giáo viên có quản lý lớp này không
     */
    $stmt = $pdo->prepare(
        "SELECT id, name
         FROM classes
         WHERE id = ?
           AND teacher_id = ?"
    );

    $stmt->execute([
        $classId,
        $teacherId
    ]);

    $selectedClass = $stmt->fetch();

    if (!$selectedClass) {
        flash('error', 'Bạn không có quyền truy cập lớp này.');

        header(
            'Location: ' . BASE_URL . '/teacher/moderate_posts.php'
        );

        exit;
    }

    /*
     * Lấy bài chờ duyệt của lớp
     */
    $stmt = $pdo->prepare(
        "SELECT
            p.*,
            u.username,
            u.full_name,
            c.name AS class_name
         FROM posts p
         JOIN users u ON u.id = p.user_id
         JOIN classes c ON c.id = p.class_id
         WHERE p.status = 'pending'
           AND p.class_id = ?
           AND c.teacher_id = ?
         ORDER BY p.created_at ASC"
    );

    $stmt->execute([
        $classId,
        $teacherId
    ]);

} else {

    $selectedClass = null;

    /*
     * Lấy tất cả bài chờ duyệt
     * thuộc các lớp giáo viên đang quản lý
     */
    $stmt = $pdo->prepare(
        "SELECT
            p.*,
            u.username,
            u.full_name,
            c.name AS class_name
         FROM posts p
         JOIN users u ON u.id = p.user_id
         JOIN classes c ON c.id = p.class_id
         WHERE p.status = 'pending'
           AND c.teacher_id = ?
         ORDER BY p.created_at ASC"
    );

    $stmt->execute([
        $teacherId
    ]);
}

$pendingPosts = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| LẤY DANH SÁCH LỚP
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(
    "SELECT id, name
     FROM classes
     WHERE teacher_id = ?
     ORDER BY name ASC"
);

$stmt->execute([
    $teacherId
]);

$classes = $stmt->fetchAll();


$pageTitle = 'Duyệt bài viết';

require __DIR__ . '/../includes/header.php';
?>


<div class="page-header">

    <div>
        <h1>Duyệt bài viết</h1>

        <p class="text-muted">
            Kiểm tra và duyệt các bài viết của sinh viên trước khi hiển thị.
        </p>
    </div>

    <a
        href="<?= e(BASE_URL) ?>/teacher/dashboard.php"
        class="btn btn-outline btn-sm"
    >
        Quay lại Dashboard
    </a>

</div>


<?php render_flashes(); ?>


<!-- BỘ LỌC LỚP -->

<div class="box" style="margin-bottom:20px;">

    <form method="get">

        <div
            style="
                display:flex;
                gap:12px;
                align-items:end;
                flex-wrap:wrap;
            "
        >

            <div style="flex:1;min-width:220px;">

                <label for="class_id">
                    Lọc theo lớp
                </label>

                <select
                    name="class_id"
                    id="class_id"
                    class="form-control"
                >

                    <option value="0">
                        Tất cả lớp
                    </option>

                    <?php foreach ($classes as $class): ?>

                        <option
                            value="<?= (int)$class['id'] ?>"
                            <?= $classId === (int)$class['id'] ? 'selected' : '' ?>
                        >
                            <?= e($class['name']) ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <button
                type="submit"
                class="btn btn-teal"
            >
                Lọc bài viết
            </button>

        </div>

    </form>

</div>


<!-- SỐ LƯỢNG -->

<div
    class="alert alert-info"
    style="margin-bottom:20px;"
>

    <i class="fa-solid fa-circle-exclamation"></i>

    Có
    <strong><?= count($pendingPosts) ?></strong>
    bài viết đang chờ duyệt.

</div>


<?php if (empty($pendingPosts)): ?>

    <div class="empty-state">

        <i
            class="fa-solid fa-circle-check"
            style="font-size:42px;margin-bottom:15px;"
        ></i>

        <h3>Không có bài viết chờ duyệt</h3>

        <p class="text-muted">
            Hiện tại không có bài viết nào cần bạn xử lý.
        </p>

    </div>

<?php else: ?>


    <div style="display:flex;flex-direction:column;gap:16px;">

        <?php foreach ($pendingPosts as $post): ?>

            <div class="box">

                <!-- THÔNG TIN BÀI -->

                <div
                    style="
                        display:flex;
                        justify-content:space-between;
                        gap:15px;
                        flex-wrap:wrap;
                    "
                >

                    <div>

                        <h3 style="margin:0 0 8px 0;">
                            <?= e($post['title']) ?>
                        </h3>

                        <div
                            class="text-muted"
                            style="font-size:13px;"
                        >

                            <i class="fa-solid fa-user"></i>

                            <?= e(
                                $post['full_name']
                                ?: $post['username']
                            ) ?>

                            &nbsp; · &nbsp;

                            <i class="fa-solid fa-chalkboard"></i>

                            <?= e($post['class_name']) ?>

                            &nbsp; · &nbsp;

                            <?= date(
                                'd/m/Y H:i',
                                strtotime($post['created_at'])
                            ) ?>

                        </div>

                    </div>


                    <span class="tag tag-pending">
                        Chờ duyệt
                    </span>

                </div>


                <!-- NỘI DUNG -->

                <div
                    style="
                        margin-top:16px;
                        padding:15px;
                        background:#f8fafc;
                        border-radius:8px;
                        line-height:1.7;
                    "
                >

                    <?= $post['content'] ?>

                </div>


                <!-- NÚT XEM -->

                <div
                    style="
                        display:flex;
                        gap:8px;
                        margin-top:16px;
                        flex-wrap:wrap;
                    "
                >

                    <a
                        href="<?= e(BASE_URL) ?>/post_view.php?id=<?= (int)$post['id'] ?>"
                        class="btn btn-outline btn-sm"
                        target="_blank"
                    >
                        <i class="fa-solid fa-eye"></i>
                        Xem bài
                    </a>


                    <!-- DUYỆT -->

                    <form
                        method="post"
                        style="display:inline;"
                    >

                        <input
                            type="hidden"
                            name="post_id"
                            value="<?= (int)$post['id'] ?>"
                        >

                        <input
                            type="hidden"
                            name="class_id"
                            value="<?= (int)$post['class_id'] ?>"
                        >

                        <input
                            type="hidden"
                            name="action"
                            value="approve"
                        >

                        <button
                            type="submit"
                            class="btn btn-teal btn-sm"
                            onclick="return confirm('Bạn có chắc muốn duyệt bài viết này?')"
                        >
                            <i class="fa-solid fa-check"></i>
                            Duyệt bài
                        </button>

                    </form>


                    <!-- TỪ CHỐI -->

                    <form
                        method="post"
                        style="
                            display:flex;
                            gap:8px;
                            flex:1;
                            min-width:300px;
                        "
                    >

                        <input
                            type="hidden"
                            name="post_id"
                            value="<?= (int)$post['id'] ?>"
                        >

                        <input
                            type="hidden"
                            name="class_id"
                            value="<?= (int)$post['class_id'] ?>"
                        >

                        <input
                            type="hidden"
                            name="action"
                            value="reject"
                        >

                        <input
                            type="text"
                            name="reject_reason"
                            class="form-control"
                            placeholder="Lý do từ chối..."
                            maxlength="255"
                        >

                        <button
                            type="submit"
                            class="btn btn-outline btn-sm"
                            onclick="return confirm('Bạn có chắc muốn từ chối bài viết này?')"
                        >
                            <i class="fa-solid fa-xmark"></i>
                            Từ chối
                        </button>

                    </form>

                </div>

            </div>

        <?php endforeach; ?>

    </div>

<?php endif; ?>


<?php require __DIR__ . '/../includes/footer.php'; ?>