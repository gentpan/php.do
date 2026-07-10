<?php
require_once __DIR__ . '/../functions.php';
$u = require_login();
if (ip_banned(client_ip())) exit('当前 IP 已被封禁');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect(pd_url_page('index.php'));
$tid = intval($_POST['thread_id']);
$content = clean_text($_POST['content'], pd_reply_max_chars() + 1000);
if ($tid < 1 || $content === '') redirect(pd_url_thread($tid));
$reply_len = function_exists('mb_strlen') ? mb_strlen($content, 'UTF-8') : strlen($content);
if ($reply_len > pd_reply_max_chars()) {
    $_SESSION['flash'] = '回复内容不能超过 ' . pd_reply_max_chars() . ' 字。';
    redirect(pd_url_thread($tid));
}
$mute_message = pd_user_mute_message($u);
if ($mute_message !== '') {
    $_SESSION['flash'] = $mute_message;
    redirect(pd_url_thread($tid));
}
if (!empty($_SESSION['last_reply_at']) && time() - intval($_SESSION['last_reply_at']) < max(2, intval(POST_INTERVAL))) {
    $_SESSION['flash'] = '回复太快了，请稍后再试。';
    redirect(pd_url_thread($tid));
}
if (pd_captcha_required('reply', $u) && !pd_verify_captcha()) {
    $_SESSION['flash'] = '验证码错误，请重新输入。';
    redirect(pd_url_thread($tid));
}
$uid = intval($u['id']);
$ip = esc(client_ip());
$content_sql = esc($content);
$conn = db();
mysqli_begin_transaction($conn);
$thread_rs = mysqli_query($conn, "SELECT title,user_id FROM pd_threads WHERE id={$tid} AND is_deleted=0 LIMIT 1 FOR UPDATE");
$thread_info = $thread_rs ? mysqli_fetch_assoc($thread_rs) : null;
if (!$thread_info) {
    mysqli_rollback($conn);
    $_SESSION['flash'] = '帖子不存在或已被删除。';
    redirect(pd_url_page('index.php'));
}
$inserted = mysqli_query($conn, "INSERT INTO pd_posts (thread_id,user_id,content,ip,created_at) VALUES ({$tid},{$uid},'{$content_sql}','{$ip}',NOW())");
$post_id = $inserted ? intval(mysqli_insert_id($conn)) : 0;
if ($post_id < 1
    || !mysqli_query($conn, "UPDATE pd_threads SET replies=replies+1, updated_at=NOW() WHERE id={$tid} AND is_deleted=0")
    || !mysqli_query($conn, "UPDATE pd_users SET reply_count=reply_count+1 WHERE id={$uid} AND status=1")) {
    mysqli_rollback($conn);
    $_SESSION['flash'] = '回帖失败，请稍后重试。';
    redirect(pd_url_thread($tid));
}
mysqli_commit($conn);
$_SESSION['last_reply_at'] = time();
pd_bind_content_attachments($tid, $post_id, $uid, $content);
$upload_errors = array();
$upload_saved = pd_upload_attachments($tid, $post_id, $uid, $upload_errors);
if ($upload_saved > 0 && empty($upload_errors)) {
    $_SESSION['flash'] = '回帖成功，附件/图片上传成功';
} elseif ($upload_saved > 0 && !empty($upload_errors)) {
    $_SESSION['flash'] = "回帖成功，附件/图片上传成功，部分文件上传失败：\n" . implode("\n", $upload_errors);
} elseif (!empty($upload_errors)) {
    $_SESSION['flash'] = "回帖成功，但附件/图片上传失败：\n" . implode("\n", $upload_errors);
} else {
    $_SESSION['flash'] = '回帖成功';
}
pd_add_user_points($uid, pd_points_for_reply(), 'reply', 'post', $post_id);
if ($thread_info && intval($thread_info['user_id']) !== $uid) {
    pd_notify_user(intval($thread_info['user_id']), $tid, $post_id, '你的帖子《' . $thread_info['title'] . '》有新的回帖');
}
redirect(pd_url_page('thread.php', array('id' => $tid), 'replies'));
?>
