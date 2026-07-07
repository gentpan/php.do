<?php
require_once __DIR__ . '/../functions.php';

$u = current_user();
if (!$u) {
    qf_json_response(array('ok' => false, 'error' => '请先登录后再表态。'), 401);
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    qf_json_response(array('ok' => false, 'error' => '请求方式不正确。'), 405);
}

qf_ensure_thread_reaction_schema();

$thread_id = isset($_POST['thread_id']) ? intval($_POST['thread_id']) : 0;
$reaction = isset($_POST['reaction']) ? trim((string)$_POST['reaction']) : '';
$types = qf_reaction_types();
if ($thread_id <= 0 || !isset($types[$reaction])) {
    qf_json_response(array('ok' => false, 'error' => '表态参数不正确。'), 400);
}

$thread_rs = mysqli_query(db(), "SELECT id FROM qf_threads WHERE id={$thread_id} AND is_deleted=0 LIMIT 1");
if (!$thread_rs || mysqli_num_rows($thread_rs) === 0) {
    qf_json_response(array('ok' => false, 'error' => '帖子不存在。'), 404);
}

$user_id = intval($u['id']);
$reaction_sql = mysqli_real_escape_string(db(), $reaction);
$current = qf_user_thread_reaction($thread_id, $user_id);

if ($current === $reaction) {
    // 再次点击同一个表情 => 取消
    mysqli_query(db(), "DELETE FROM qf_thread_reactions WHERE thread_id={$thread_id} AND user_id={$user_id}");
    $active = '';
} elseif ($current === '') {
    mysqli_query(db(), "INSERT INTO qf_thread_reactions (thread_id,user_id,reaction,created_at,updated_at) VALUES ({$thread_id},{$user_id},'{$reaction_sql}',NOW(),NOW())");
    $active = $reaction;
} else {
    // 切换到另一种表情
    mysqli_query(db(), "UPDATE qf_thread_reactions SET reaction='{$reaction_sql}', updated_at=NOW() WHERE thread_id={$thread_id} AND user_id={$user_id}");
    $active = $reaction;
}

$counts = qf_thread_reaction_counts($thread_id);
$payload = array('ok' => true, 'active' => $active, 'counts' => $counts);

$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string)$_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
if (!$is_ajax) {
    $back = isset($_SERVER['HTTP_REFERER']) ? (string)$_SERVER['HTTP_REFERER'] : qf_url_thread($thread_id);
    header('Location: ' . $back);
    exit;
}
qf_json_response($payload);
