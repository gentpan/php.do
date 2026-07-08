<?php
require_once __DIR__ . '/../functions.php';
qf_ensure_pm_schema();
$u = require_login();
$uid = intval($u['id']);
$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!qf_verify_csrf()) {
        qf_json_response(array('ok' => 0, 'error' => 'CSRF 校验失败，请刷新页面后重试。'), 403);
    }
    $body = isset($_POST['body']) ? $_POST['body'] : '';
    $thread_id = intval(isset($_POST['thread_id']) ? $_POST['thread_id'] : 0);
    $to_id = intval(isset($_POST['to_id']) ? $_POST['to_id'] : 0);
    if ($thread_id > 0) {
        $thread = qf_pm_get_thread_row($thread_id);
        $to_id = qf_pm_thread_peer_id($thread, $uid);
    }
    $result = qf_pm_send_message($uid, $to_id, $body, $thread_id);
    if (empty($result['ok'])) {
        qf_json_response(array('ok' => 0, 'error' => isset($result['error']) ? $result['error'] : '发送失败。'), 400);
    }
    $message_id = intval($result['message_id']);
    $rs = mysqli_query(db(), "SELECT * FROM qf_pm_messages WHERE id={$message_id} LIMIT 1");
    $msg = $rs ? mysqli_fetch_assoc($rs) : null;
    qf_json_response(array(
        'ok' => 1,
        'thread_id' => intval($result['thread_id']),
        'message' => $msg ? array(
            'id' => intval($msg['id']),
            'body' => $msg['body'],
            'sender_id' => intval($msg['sender_id']),
            'created_at' => $msg['created_at'],
            'time_html' => qf_time_html($msg['created_at']),
        ) : null,
        'redirect' => qf_url_messages(intval($result['thread_id'])),
    ));
}

if (!$is_ajax) {
    redirect(qf_url_messages());
}

qf_json_response(array('ok' => 0, 'error' => '不支持的请求。'), 405);
