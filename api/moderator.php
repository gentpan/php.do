<?php
require_once __DIR__ . '/../functions.php';
$u = require_login();
$action = isset($_GET['action']) ? $_GET['action'] : '';

if (intval(isset($u['is_moderator']) ? $u['is_moderator'] : 0) !== 1 || intval($u['is_admin']) === 1) {
    $_SESSION['flash'] = '你没有版主权限。';
    redirect(pd_url_page('index.php'));
}

if ($action === 'del_thread') {
    $id = intval($_GET['id']);
    pd_require_action_token('mod_del_thread', $id);
    $rs = mysqli_query(db(), "SELECT t.*, u.is_admin AS author_is_admin FROM pd_threads t LEFT JOIN pd_users u ON t.user_id=u.id WHERE t.id={$id} AND t.is_deleted=0 LIMIT 1");
    $thread = $rs ? mysqli_fetch_assoc($rs) : null;
    if (!$thread) {
        if (pd_is_ajax_request()) {
            pd_json_response(array('ok' => 0, 'msg' => '帖子不存在。'));
        }
        $_SESSION['flash'] = '帖子不存在。';
        redirect(pd_url_page('index.php'));
    }
    if (!pd_can_moderator_delete_thread($u, $thread)) {
        if (pd_is_ajax_request()) {
            pd_json_response(array('ok' => 0, 'msg' => '只能删除你任命板块内的帖子，不能删除管理员帖子，或今天删除次数已达上限。'));
        }
        $_SESSION['flash'] = '只能删除你任命板块内的帖子，不能删除管理员帖子，或今天删除次数已达上限。';
        redirect(pd_url_thread($id));
    }
    mysqli_query(db(), "UPDATE pd_threads SET is_deleted=1 WHERE id={$id}");
    $author_id = intval(isset($thread['user_id']) ? $thread['user_id'] : 0);
    if ($author_id > 0) {
        $delta = -pd_points_for_thread();
        if (intval(isset($thread['is_good']) ? $thread['is_good'] : 0) === 1) {
            $delta -= pd_points_for_good();
        }
        if ($delta !== 0) {
            pd_add_user_points($author_id, $delta, 'del_thread', 'thread', $id);
        }
    }
    pd_log_moderator_delete(intval($u['id']), 'thread', $id);
    if (pd_is_ajax_request()) {
        pd_json_response(array('ok' => 1, 'redirect' => pd_url_page('index.php'), 'msg' => '版主已删除该主题。'));
    }
    $_SESSION['flash'] = '版主已删除该主题。';
    redirect(pd_url_page('index.php'));
}

if ($action === 'del_post') {
    $id = intval($_GET['id']);
    $tid = intval($_GET['tid']);
    pd_require_action_token('mod_del_post', $id, $tid);
    $rs = mysqli_query(db(), "SELECT p.*, t.forum_id, u.is_admin AS author_is_admin FROM pd_posts p LEFT JOIN pd_threads t ON p.thread_id=t.id LEFT JOIN pd_users u ON p.user_id=u.id WHERE p.id={$id} AND p.is_deleted=0 LIMIT 1");
    $post = $rs ? mysqli_fetch_assoc($rs) : null;
    if (!$post) {
        if (pd_is_ajax_request()) {
            pd_json_response(array('ok' => 0, 'msg' => '回复不存在。'));
        }
        $_SESSION['flash'] = '回复不存在。';
        redirect(pd_url_thread($tid));
    }
    $tid = intval($post['thread_id']);
    if (!pd_can_moderator_delete_post($u, $post)) {
        if (pd_is_ajax_request()) {
            pd_json_response(array('ok' => 0, 'msg' => '只能删除你任命板块内的回复，不能删除管理员回复，或今天删除次数已达上限。'));
        }
        $_SESSION['flash'] = '只能删除你任命板块内的回复，不能删除管理员回复，或今天删除次数已达上限。';
        redirect(pd_url_thread($tid));
    }
    pd_soft_delete_post($id, $tid);
    pd_log_moderator_delete(intval($u['id']), 'post', $id);
    if (pd_is_ajax_request()) {
        pd_json_response(array('ok' => 1, 'removed' => 1, 'post_id' => $id, 'msg' => '版主已删除该回复。'));
    }
    $_SESSION['flash'] = '版主已删除该回复。';
    redirect(pd_url_thread($tid));
}

redirect(pd_url_page('index.php'));
?>
