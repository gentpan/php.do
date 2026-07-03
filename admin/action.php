<?php
require_once __DIR__ . '/../functions.php';
require_admin();
$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($action === 'add_forum' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = esc(clean_text($_POST['name'], 60));
    $desc = esc(clean_text($_POST['description'], 255));
    $new_topic_category_enabled = !empty($_POST['new_topic_category_enabled']) ? 1 : 0;
    $new_topic_categories = esc(clean_text($_POST['new_topic_categories'], 255));
    $new_post_user_limit_enabled = !empty($_POST['new_post_user_limit_enabled']) ? 1 : 0;
    $new_post_user_ids = esc(clean_text($_POST['new_post_user_ids'], 255));
    $order = intval($_POST['display_order']);
    if ($name !== '') {
        mysqli_query(db(), "INSERT INTO qf_forums (name, description, topic_category_enabled, topic_categories, post_user_limit_enabled, post_user_ids, display_order, created_at) VALUES ('{$name}', '{$desc}', {$new_topic_category_enabled}, '{$new_topic_categories}', {$new_post_user_limit_enabled}, '{$new_post_user_ids}', {$order}, NOW())");
    }
    redirect(qf_url_page('admin/index.php'));
}

if ($action === 'edit_forum' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $forum_id = intval($_POST['forum_id']);
    $name = esc(clean_text($_POST['name'], 60));
    $desc = esc(clean_text($_POST['description'], 255));
    $order = intval($_POST['display_order']);
    if ($forum_id > 0 && $name !== '') {
        mysqli_query(db(), "UPDATE qf_forums SET name='{$name}', description='{$desc}', display_order={$order} WHERE id={$forum_id}");
    }
    redirect(qf_url_page('admin/index.php'));
}

if ($action === 'save_forums' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $delete_forums = isset($_POST['delete_forums']) && is_array($_POST['delete_forums']) ? $_POST['delete_forums'] : array();
    foreach ($delete_forums as $forum_id) {
        $forum_id = intval($forum_id);
        if ($forum_id > 0) {
            mysqli_query(db(), "UPDATE qf_threads SET is_deleted=1 WHERE forum_id={$forum_id}");
            mysqli_query(db(), "DELETE FROM qf_forums WHERE id={$forum_id}");
        }
    }

    if (!empty($_POST['forums']) && is_array($_POST['forums'])) {
        foreach ($_POST['forums'] as $forum_id => $data) {
            $forum_id = intval($forum_id);
            if ($forum_id < 1 || in_array((string)$forum_id, $delete_forums)) {
                continue;
            }
            $name = esc(clean_text($data['name'], 60));
            $desc = esc(clean_text($data['description'], 255));
            $topic_category_enabled = !empty($data['topic_category_enabled']) ? 1 : 0;
            $topic_categories = esc(clean_text($data['topic_categories'], 255));
            $post_user_limit_enabled = !empty($data['post_user_limit_enabled']) ? 1 : 0;
            $post_user_ids = esc(clean_text($data['post_user_ids'], 255));
            $order = intval($data['display_order']);
            if ($name !== '') {
                mysqli_query(db(), "UPDATE qf_forums SET name='{$name}', description='{$desc}', topic_category_enabled={$topic_category_enabled}, topic_categories='{$topic_categories}', post_user_limit_enabled={$post_user_limit_enabled}, post_user_ids='{$post_user_ids}', display_order={$order} WHERE id={$forum_id}");
            }
        }
    }
    redirect(qf_url_page('admin/index.php'));
}

if ($action === 'add_ban' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $ip = esc(clean_text($_POST['ip'], 45));
    $reason = esc(clean_text($_POST['reason'], 255));
    $days = intval($_POST['days']);
    if ($days < 1) {
        $days = 1;
    }
    if ($days > 3650) {
        $days = 3650;
    }
    if ($ip !== '') {
        mysqli_query(db(), "INSERT INTO qf_bans (ip, reason, expires_at, created_at) VALUES ('{$ip}', '{$reason}', DATE_ADD(NOW(), INTERVAL {$days} DAY), NOW())");
    }
    redirect(qf_url_page('admin/index.php'));
}

if ($action === 'del_ban') {
    $id = intval($_GET['id']);
    qf_require_action_token('del_ban', $id);
    mysqli_query(db(), "DELETE FROM qf_bans WHERE id={$id}");
    redirect(qf_url_page('admin/index.php'));
}

if ($action === 'mute_user' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = intval($_POST['user_id']);
    $days = intval($_POST['days']);
    if ($days < 1) {
        $days = 1;
    }
    if ($days > 3650) {
        $days = 3650;
    }
    if ($user_id > 0) {
        mysqli_query(db(), "UPDATE qf_users SET mute_until=DATE_ADD(NOW(), INTERVAL {$days} DAY) WHERE id={$user_id} AND is_admin=0");
        $_SESSION['flash'] = '已禁止该用户发言 ' . $days . ' 天。';
    }
    redirect(qf_url_page('admin/users.php'));
}

if ($action === 'set_moderator' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = intval($_POST['user_id']);
    $forum_id = intval($_POST['forum_id']);
    $moderator_delete_limit = intval($_POST['moderator_delete_limit']);
    if ($moderator_delete_limit < 0) {
        $moderator_delete_limit = 0;
    }
    if ($moderator_delete_limit > 10000) {
        $moderator_delete_limit = 10000;
    }
    if ($user_id > 0) {
        mysqli_query(db(), "DELETE FROM qf_moderator_forums WHERE user_id={$user_id}");
        if ($forum_id > 0) {
            mysqli_query(db(), "UPDATE qf_users SET is_moderator=1, moderator_delete_limit={$moderator_delete_limit} WHERE id={$user_id} AND is_admin=0");
            mysqli_query(db(), "INSERT INTO qf_moderator_forums (user_id,forum_id,created_at) VALUES ({$user_id},{$forum_id},NOW())");
            $_SESSION['flash'] = '版主权限已更新，已任命到具体板块。';
        } else {
            mysqli_query(db(), "UPDATE qf_users SET is_moderator=0, moderator_delete_limit=0 WHERE id={$user_id} AND is_admin=0");
            $_SESSION['flash'] = '已取消版主权限。';
        }
    }
    redirect(qf_url_page('admin/users.php'));
}

if ($action === 'change_user_password' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = intval($_POST['user_id']);
    $password = (string)$_POST['password'];
    if ($user_id > 0 && strlen($password) >= 6) {
        $password_sql = esc(qf_password_hash($password));
        mysqli_query(db(), "UPDATE qf_users SET password='{$password_sql}' WHERE id={$user_id} AND is_admin=0");
        $_SESSION['flash'] = '用户密码已修改。';
    } else {
        $_SESSION['flash'] = '密码修改失败，新密码至少 6 位。';
    }
    redirect(qf_url_page('admin/users.php'));
}

if ($action === 'clear_user_content' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = intval($_POST['user_id']);
    if ($user_id > 0) {
        mysqli_query(db(), "UPDATE qf_threads SET is_deleted=1 WHERE user_id={$user_id}");
        mysqli_query(db(), "UPDATE qf_posts SET is_deleted=1 WHERE user_id={$user_id}");
        mysqli_query(db(), "UPDATE qf_threads t SET replies=(SELECT COUNT(*) FROM qf_posts p WHERE p.thread_id=t.id AND p.is_deleted=0) WHERE t.id IN (SELECT thread_id FROM qf_posts WHERE user_id={$user_id})");
        mysqli_query(db(), "UPDATE qf_users SET reply_count=0 WHERE id={$user_id} AND is_admin=0");
        $_SESSION['flash'] = '已清除该用户全部发帖和回帖。';
    }
    redirect(qf_url_page('admin/users.php'));
}

if ($action === 'top_board') {
    $id = intval($_GET['id']);
    qf_require_action_token('top_board', $id);
    mysqli_query(db(), "UPDATE qf_threads SET is_top=2 WHERE id={$id}");
    redirect(qf_url_thread($id));
}

if ($action === 'top_global') {
    $id = intval($_GET['id']);
    qf_require_action_token('top_global', $id);
    mysqli_query(db(), "UPDATE qf_threads SET is_top=1 WHERE id={$id}");
    redirect(qf_url_thread($id));
}

if ($action === 'cancel_top') {
    $id = intval($_GET['id']);
    qf_require_action_token('cancel_top', $id);
    mysqli_query(db(), "UPDATE qf_threads SET is_top=0 WHERE id={$id}");
    redirect(qf_url_thread($id));
}

if ($action === 'move_thread' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['thread_id']);
    $new_forum_id = intval($_POST['new_forum_id']);
    if ($id > 0 && $new_forum_id > 0) {
        mysqli_query(db(), "UPDATE qf_threads SET forum_id={$new_forum_id}, topic_category='' WHERE id={$id}");
    }
    redirect(qf_url_thread($id));
}

if ($action === 'good') {
    $id = intval($_GET['id']);
    qf_require_action_token('good', $id);
    mysqli_query(db(), "UPDATE qf_threads SET is_good=IF(is_good=1,0,1) WHERE id={$id}");
    redirect(qf_url_thread($id));
}

if ($action === 'del_thread') {
    $id = intval($_GET['id']);
    qf_require_action_token('del_thread', $id);
    mysqli_query(db(), "UPDATE qf_threads SET is_deleted=1 WHERE id={$id}");
    redirect(qf_url_page('index.php'));
}

if ($action === 'del_post') {
    $id = intval($_GET['id']);
    $tid = intval($_GET['tid']);
    qf_require_action_token('del_post', $id, $tid);
    mysqli_query(db(), "UPDATE qf_posts SET is_deleted=1 WHERE id={$id}");
    mysqli_query(db(), "UPDATE qf_threads SET replies=GREATEST(replies-1,0) WHERE id={$tid}");
    redirect(qf_url_thread($tid));
}

redirect(qf_url_page('admin/index.php'));
?>
