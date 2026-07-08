<?php
require_once __DIR__ . '/../functions.php';

$u = require_login();
pd_require_csrf();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(pd_url_page('index.php'));
}

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$redirect = isset($_POST['redirect']) ? $_POST['redirect'] : pd_url_page('index.php');
if ($redirect === '' || preg_match('/^https?:\/\//i', $redirect) || strpos($redirect, '//') === 0 || preg_match('/[\r\n:]/', $redirect)) {
    $redirect = pd_url_page('index.php');
}

$rs = mysqli_query(db(), "SELECT * FROM pd_attachments WHERE id={$id} LIMIT 1");
$att = $rs ? mysqli_fetch_assoc($rs) : null;
if (!$att) {
    $_SESSION['flash'] = '附件不存在或已被删除。';
    redirect($redirect);
}

if (!pd_can_delete_attachment($att, $u)) {
    header('Content-Type: text/html; charset=utf-8', true, 403);
    exit('你没有权限删除这个附件。');
}

$file_deleted = pd_delete_attachment_file($att['file_path']);
if (!$file_deleted) {
    $_SESSION['flash'] = '附件路径不安全，已停止删除。';
    redirect($redirect);
}

$tag_id = intval($att['id']);
$thread_rows = mysqli_query(db(), "SELECT id, content FROM pd_threads WHERE content LIKE '%download?id={$tag_id}%'");
while ($thread_rows && ($row = mysqli_fetch_assoc($thread_rows))) {
    $new_content = pd_remove_attachment_tag_from_content($row['content'], $tag_id);
    $content_sql = esc($new_content);
    $row_id = intval($row['id']);
    mysqli_query(db(), "UPDATE pd_threads SET content='{$content_sql}' WHERE id={$row_id}");
}

$post_rows = mysqli_query(db(), "SELECT id, content FROM pd_posts WHERE content LIKE '%download?id={$tag_id}%'");
while ($post_rows && ($row = mysqli_fetch_assoc($post_rows))) {
    $new_content = pd_remove_attachment_tag_from_content($row['content'], $tag_id);
    $content_sql = esc($new_content);
    $row_id = intval($row['id']);
    mysqli_query(db(), "UPDATE pd_posts SET content='{$content_sql}' WHERE id={$row_id}");
}

mysqli_query(db(), "DELETE FROM pd_attachments WHERE id={$tag_id}");
$_SESSION['flash'] = '附件已删除，服务器文件也已清理。';
redirect($redirect);
?>
