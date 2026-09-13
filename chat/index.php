
<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();

$user = current_user();

// Người dùng có thể trò chuyện với người cùng lớp hoặc giáo viên/học sinh liên quan.
$contacts = $pdo->prepare("
    SELECT DISTINCT u.id, u.username, u.full_name, u.role
    FROM users u
    WHERE u.id != ? AND u.id IN (
        SELECT cm2.user_id
        FROM class_members cm1
        JOIN class_members cm2 ON cm2.class_id = cm1.class_id
        WHERE cm1.user_id = ?

        UNION

        SELECT c.teacher_id
        FROM classes c
        JOIN class_members cm ON cm.class_id = c.id
        WHERE cm.user_id = ?

        UNION

        SELECT cm.user_id
        FROM classes c
        JOIN class_members cm ON cm.class_id = c.id
        WHERE c.teacher_id = ?
    )
    ORDER BY u.full_name
");
$contacts->execute([
    $user['id'],
    $user['id'],
    $user['id'],
    $user['id']
]);
$contacts = $contacts->fetchAll();

// Các nhóm lớp mà người dùng là giáo viên hoặc thành viên.
$groups = $pdo->prepare("
    SELECT c.id, c.name
    FROM classes c
    WHERE c.teacher_id = ?

    UNION

    SELECT c.id, c.name
    FROM classes c
    JOIN class_members cm ON cm.class_id = c.id
    WHERE cm.user_id = ?
");
$groups->execute([$user['id'], $user['id']]);
$groups = $groups->fetchAll();

$activeUserId = isset($_GET['user_id']) ? (int) $_GET['user_id'] : null;
$activeClassId = isset($_GET['class_id']) ? (int) $_GET['class_id'] : null;

$pageTitle = 'Trò chuyện - UTH Forum';
$pageCss = 'chat.css';
require __DIR__ . '/../includes/header.php';
?>

<script>
    window.CSRF = <?= json_encode(csrf_token()) ?>;
    window.CURRENT_USER_ID = <?= (int) $user['id'] ?>;
</script>

<h2>Trò chuyện</h2>

<div class="chat-wrap">

    <div class="chat-list">

        <?php if ($groups): ?>
            <div class="chat-section-title">NHÓM LỚP</div>

            <?php foreach ($groups as $g): ?>
                <div
                    class="conv <?= $activeClassId === (int) $g['id'] ? 'active' : '' ?>"
                    onclick="location.href='view.php?class_id=<?= (int) $g['id'] ?>'"
                >
                    <div class="avatar-sm">
                        <i class="fa-solid fa-people-group"></i>
                    </div>

                    <div class="chat-contact-name">
                        <?= e($g['name']) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>


        <?php if ($contacts): ?>
            <div class="chat-section-title">LIÊN HỆ</div>

            <?php foreach ($contacts as $c): ?>
                <div
                    class="conv <?= $activeUserId === (int) $c['id'] ? 'active' : '' ?>"
                    onclick="location.href='view.php?user_id=<?= (int) $c['id'] ?>'"
                >
                    <div class="avatar-sm">
                        <?= e(mb_strtoupper(mb_substr($c['full_name'] ?: $c['username'], 0, 1))) ?>
                    </div>

                    <div class="chat-contact-info">
                        <div class="chat-contact-name">
                            <?= e($c['full_name'] ?: $c['username']) ?>
                        </div>

                        <div class="chat-contact-role">
                            <?= e(role_label($c['role'])) ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>


        <?php if (!$groups && !$contacts): ?>
          <div class="chat-empty-list">
              <div class="chat-empty-icon">
                <i class="fa-solid fa-plus"></i>
              </div>

              <p>Tham gia một lớp để bắt đầu trò chuyện</p>
            </div>
        <?php endif; ?>

    </div>


    <div class="chat-main">
        <div class="chat-empty-state">
            <div class="chat-empty-icon">
                <i class="fa-regular fa-comments"></i>
            </div>

            <p>Chọn một cuộc trò chuyện để bắt đầu</p>
        </div>
    </div>

</div>