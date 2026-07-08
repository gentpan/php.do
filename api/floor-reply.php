<?php
require_once __DIR__ . '/../functions.php';
$u = require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect(pd_url_page('index.php'));

$thread_id = intval($_POST['thread_id']);
$post_id = intval($_POST['post_id']);
$content = clean_text(isset($_POST['content']) ? $_POST['content'] : '', 500);
if ($thread_id < 1 || $post_id < 1 || $content === '') {
    redirect(pd_url_page('thread.php', array('id' => $thread_id), 'replies'));
}

$post_rs = mysqli_query(db(), "SELECT p.*, t.title, t.user_id AS thread_user_id FROM pd_posts p LEFT JOIN pd_threads t ON p.thread_id=t.id WHERE p.id={$post_id} AND p.thread_id={$thread_id} AND p.is_deleted=0 LIMIT 1");
$post = $post_rs ? mysqli_fetch_assoc($post_rs) : null;
if (!$post) {
    redirect(pd_url_page('thread.php', array('id' => $thread_id), 'replies'));
}

$uid = intval($u['id']);
$ip = esc(client_ip());
$content_sql = esc($content);
mysqli_query(db(), "INSERT INTO pd_post_comments (thread_id,post_id,user_id,content,ip,is_deleted,created_at) VALUES ({$thread_id},{$post_id},{$uid},'{$content_sql}','{$ip}',0,NOW())");
$comment_id = intval(mysqli_insert_id(db()));
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
