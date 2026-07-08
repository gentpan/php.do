<?php
require_once __DIR__ . '/../functions.php';
$u = require_login();
if (ip_banned(client_ip())) exit('当前 IP 已被封禁');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect(qf_url_page('index.php'));
$tid = intval($_POST['thread_id']);
$content = clean_text($_POST['content'], qf_reply_max_chars() + 1000);
if ($tid < 1 || $content === '') redirect(qf_url_thread($tid));
$reply_len = function_exists('mb_strlen') ? mb_strlen($content, 'UTF-8') : strlen($content);
if ($reply_len > qf_reply_max_chars()) {
    $_SESSION['flash'] = '回复内容不能超过 ' . qf_reply_max_chars() . ' 字。';
    redirect(qf_url_thread($tid));
}
$thread_rs = mysqli_query(db(), "SELECT title,user_id FROM qf_threads WHERE id={$tid} AND is_deleted=0 LIMIT 1");
$thread_info = $thread_rs ? mysqli_fetch_assoc($thread_rs) : null;
$mute_message = qf_user_mute_message($u);
if ($mute_message !== '') {
    $_SESSION['flash'] = $mute_message;
    redirect(qf_url_thread($tid));
}
if (qf_captcha_required('reply', $u) && !qf_verify_captcha()) {
    $_SESSION['flash'] = '验证码错误，请重新输入。';
    redirect(qf_url_thread($tid));
}
$uid = intval($u['id']);
$ip = esc(client_ip());
$content_sql = esc($content);
mysqli_query(db(), "INSERT INTO qf_posts (thread_id,user_id,content,ip,created_at) VALUES ({$tid},{$uid},'{$content_sql}','{$ip}',NOW())");
$post_id = mysqli_insert_id(db());
$upload_errors = array();
$upload_saved = qf_upload_attachments($tid, $post_id, $uid, $upload_errors);
if ($upload_saved > 0 && empty($upload_errors)) {
    $_SESSION['flash'] = '回帖成功，附件/图片上传成功';
} elseif ($upload_saved > 0 && !empty($upload_errors)) {
    $_SESSION['flash'] = "回帖成功，附件/图片上传成功，部分文件上传失败：\n" . implode("\n", $upload_errors);
} elseif (!empty($upload_errors)) {
    $_SESSION['flash'] = "回帖成功，但附件/图片上传失败：\n" . implode("\n", $upload_errors);
} else {
    $_SESSION['flash'] = '回帖成功';
}
mysqli_query(db(), "UPDATE qf_threads SET replies=replies+1, updated_at=NOW() WHERE id={$tid}");
mysqli_query(db(), "UPDATE qf_users SET reply_count=reply_count+1 WHERE id={$uid}");
qf_add_user_points($uid, qf_points_for_reply(), 'reply', 'post', $post_id);
if ($thread_info && intval($thread_info['user_id']) !== $uid) {
    qf_notify_user(intval($thread_info['user_id']), $tid, $post_id, '你的帖子《' . $thread_info['title'] . '》有新的回帖');
}
redirect(qf_url_page('thread.php', array('id' => $tid), 'replies'));
?>
