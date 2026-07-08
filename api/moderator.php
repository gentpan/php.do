<?php
require_once __DIR__ . '/../functions.php';
$u = require_login();
$action = isset($_GET['action']) ? $_GET['action'] : '';

if (intval(isset($u['is_moderator']) ? $u['is_moderator'] : 0) !== 1 || intval($u['is_admin']) === 1) {
    $_SESSION['flash'] = '你没有版主权限。';
    redirect(qf_url_page('index.php'));
}

if ($action === 'del_thread') {
    $id = intval($_GET['id']);
    qf_require_action_token('mod_del_thread', $id);
    $rs = mysqli_query(db(), "SELECT t.*, u.is_admin AS author_is_admin FROM qf_threads t LEFT JOIN qf_users u ON t.user_id=u.id WHERE t.id={$id} AND t.is_deleted=0 LIMIT 1");
    $thread = $rs ? mysqli_fetch_assoc($rs) : null;
    if (!$thread) {
        if (qf_is_ajax_request()) {
            qf_json_response(array('ok' => 0, 'msg' => '帖子不存在。'));
        }
        $_SESSION['flash'] = '帖子不存在。';
        redirect(qf_url_page('index.php'));
    }
    if (!qf_can_moderator_delete_thread($u, $thread)) {
        if (qf_is_ajax_request()) {
            qf_json_response(array('ok' => 0, 'msg' => '只能删除你任命板块内的帖子，不能删除管理员帖子，或今天删除次数已达上限。'));
        }
        $_SESSION['flash'] = '只能删除你任命板块内的帖子，不能删除管理员帖子，或今天删除次数已达上限。';
        redirect(qf_url_thread($id));
    }
    mysqli_query(db(), "UPDATE qf_threads SET is_deleted=1 WHERE id={$id}");
    qf_log_moderator_delete(intval($u['id']), 'thread', $id);
    if (qf_is_ajax_request()) {
        qf_json_response(array('ok' => 1, 'redirect' => qf_url_page('index.php'), 'msg' => '版主已删除该主题。'));
    }
    $_SESSION['flash'] = '版主已删除该主题。';
    redirect(qf_url_page('index.php'));
}

if ($action === 'del_post') {
    $id = intval($_GET['id']);
    $tid = intval($_GET['tid']);
    qf_require_action_token('mod_del_post', $id, $tid);
    $rs = mysqli_query(db(), "SELECT p.*, t.forum_id, u.is_admin AS author_is_admin FROM qf_posts p LEFT JOIN qf_threads t ON p.thread_id=t.id LEFT JOIN qf_users u ON p.user_id=u.id WHERE p.id={$id} AND p.is_deleted=0 LIMIT 1");
    $post = $rs ? mysqli_fetch_assoc($rs) : null;
    if (!$post) {
        if (qf_is_ajax_request()) {
            qf_json_response(array('ok' => 0, 'msg' => '回复不存在。'));
        }
        $_SESSION['flash'] = '回复不存在。';
        redirect(qf_url_thread($tid));
    }
    $tid = intval($post['thread_id']);
    if (!qf_can_moderator_delete_post($u, $post)) {
        if (qf_is_ajax_request()) {
            qf_json_response(array('ok' => 0, 'msg' => '只能删除你任命板块内的回复，不能删除管理员回复，或今天删除次数已达上限。'));
        }
        $_SESSION['flash'] = '只能删除你任命板块内的回复，不能删除管理员回复，或今天删除次数已达上限。';
        redirect(qf_url_thread($tid));
    }
    qf_soft_delete_post($id, $tid);
    qf_log_moderator_delete(intval($u['id']), 'post', $id);
    if (qf_is_ajax_request()) {
        qf_json_response(array('ok' => 1, 'removed' => 1, 'post_id' => $id, 'msg' => '版主已删除该回复。'));
    }
    $_SESSION['flash'] = '版主已删除该回复。';
    redirect(qf_url_thread($tid));
}

redirect(qf_url_page('index.php'));
?>
