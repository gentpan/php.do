<?php
require_once __DIR__ . '/../functions.php';

$u = current_user();
if (!$u) {
    qf_json_response(array('ok' => false, 'error' => '请先登录后再投票。'), 401);
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    qf_json_response(array('ok' => false, 'error' => '请求方式不正确。'), 405);
}

qf_ensure_thread_vote_schema();

$thread_id = isset($_POST['thread_id']) ? intval($_POST['thread_id']) : 0;
$vote_raw = isset($_POST['vote']) ? trim((string)$_POST['vote']) : '';
$vote = 0;
if ($vote_raw === 'up' || $vote_raw === '1') {
    $vote = 1;
} elseif ($vote_raw === 'down' || $vote_raw === '-1') {
    $vote = -1;
}
if ($thread_id <= 0 || $vote === 0) {
    qf_json_response(array('ok' => false, 'error' => '投票参数不正确。'), 400);
}

$thread_rs = mysqli_query(db(), "SELECT id FROM qf_threads WHERE id={$thread_id} AND is_deleted=0 LIMIT 1");
if (!$thread_rs || mysqli_num_rows($thread_rs) === 0) {
    qf_json_response(array('ok' => false, 'error' => '帖子不存在。'), 404);
}

$user_id = intval($u['id']);
$current = 0;
$vote_rs = mysqli_query(db(), "SELECT vote FROM qf_thread_votes WHERE thread_id={$thread_id} AND user_id={$user_id} LIMIT 1");
if ($vote_rs && ($row = mysqli_fetch_assoc($vote_rs))) {
    $current = intval($row['vote']);
}

$new_vote = $current === $vote ? 0 : $vote;
if ($current === 0 && $new_vote !== 0) {
    mysqli_query(db(), "INSERT INTO qf_thread_votes (thread_id,user_id,vote,created_at,updated_at) VALUES ({$thread_id},{$user_id},{$new_vote},NOW(),NOW())");
} elseif ($new_vote === 0) {
    mysqli_query(db(), "DELETE FROM qf_thread_votes WHERE thread_id={$thread_id} AND user_id={$user_id}");
} else {
    mysqli_query(db(), "UPDATE qf_thread_votes SET vote={$new_vote}, updated_at=NOW() WHERE thread_id={$thread_id} AND user_id={$user_id}");
}

$counts = qf_recount_thread_votes($thread_id);
$payload = array(
    'ok' => true,
    'vote' => $new_vote,
    'upvotes' => intval($counts['upvotes']),
    'downvotes' => intval($counts['downvotes']),
);

$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string)$_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
if (!$is_ajax) {
    $back = isset($_SERVER['HTTP_REFERER']) ? (string)$_SERVER['HTTP_REFERER'] : qf_url_thread($thread_id);
    header('Location: ' . $back);
    exit;
}
qf_json_response($payload);
