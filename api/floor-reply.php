<?php
require_once __DIR__ . '/../functions.php';
$u = require_login();
if (ip_banned(client_ip())) {
    header('Content-Type: text/html; charset=utf-8', true, 403);
    exit('当前 IP 已被封禁');
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect(pd_url_page('index.php'));

$thread_id = intval($_POST['thread_id']);
$post_id = intval($_POST['post_id']);
$content = clean_text(isset($_POST['content']) ? $_POST['content'] : '', 500);
if ($thread_id < 1 || $post_id < 1 || $content === '') {
    redirect(pd_url_page('thread.php', array('id' => $thread_id), 'replies'));
}

$mute_message = pd_user_mute_message($u);
if ($mute_message !== '') {
    $_SESSION['flash'] = $mute_message;
    redirect(pd_url_page('thread.php', array('id' => $thread_id), 'replies'));
}
if (pd_captcha_required('reply', $u)) {
    $_SESSION['flash'] = '当前账号需要先通过普通回帖的验证码后才能使用楼中楼回复。';
    redirect(pd_url_page('thread.php', array('id' => $thread_id), 'replies'));
}
if (!empty($_SESSION['last_reply_at']) && time() - intval($_SESSION['last_reply_at']) < max(2, intval(POST_INTERVAL))) {
    $_SESSION['flash'] = '回复太快了，请稍后再试。';
    redirect(pd_url_page('thread.php', array('id' => $thread_id), 'replies'));
}

$conn = db();
mysqli_begin_transaction($conn);
$post_rs = mysqli_query($conn, "SELECT p.*, t.title, t.user_id AS thread_user_id FROM pd_posts p INNER JOIN pd_threads t ON p.thread_id=t.id WHERE p.id={$post_id} AND p.thread_id={$thread_id} AND p.is_deleted=0 AND t.is_deleted=0 LIMIT 1 FOR UPDATE");
$post = $post_rs ? mysqli_fetch_assoc($post_rs) : null;
if (!$post) {
    mysqli_rollback($conn);
    redirect(pd_url_page('thread.php', array('id' => $thread_id), 'replies'));
}

$uid = intval($u['id']);
$ip = esc(client_ip());
$content_sql = esc($content);
$inserted = mysqli_query($conn, "INSERT INTO pd_post_comments (thread_id,post_id,user_id,content,ip,is_deleted,created_at) VALUES ({$thread_id},{$post_id},{$uid},'{$content_sql}','{$ip}',0,NOW())");
$comment_id = $inserted ? intval(mysqli_insert_id($conn)) : 0;
if ($comment_id < 1) {
    mysqli_rollback($conn);
    $_SESSION['flash'] = '楼中楼回复失败，请稍后重试。';
    redirect(pd_url_page('thread.php', array('id' => $thread_id), 'replies'));
}
mysqli_commit($conn);
$_SESSION['last_reply_at'] = time();
$floor_pts = pd_points_for_floor_reply();
if ($floor_pts !== 0) {
    pd_add_user_points($uid, $floor_pts, 'floor_reply', 'comment', $comment_id);
}

if (intval($post['user_id']) !== $uid) {
    pd_notify_user(intval($post['user_id']), $thread_id, $post_id, '有人回复了你的楼层');
}
if (intval($post['thread_user_id']) > 0 && intval($post['thread_user_id']) !== $uid && intval($post['thread_user_id']) !== intval($post['user_id'])) {
    pd_notify_user(intval($post['thread_user_id']), $thread_id, $post_id, '你的帖子《' . $post['title'] . '》有新的楼中楼回复');
}

$_SESSION['flash'] = '楼中楼回复成功。';
redirect(pd_url_page('thread.php', array('id' => $thread_id), 'replies'));
?>
