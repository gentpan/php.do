<?php
// 帖子表态统一入口：同时处理「顶/踩投票」与「表情反应」。
// - 传 reaction=like|cheer|celebrate|appreciate|smile => 表情反应（每人每帖单选，可切换/取消）
// - 传 vote=up|down|1|-1                                => 顶/踩投票
require_once __DIR__ . '/../functions.php';

$u = current_user();
if (!$u) {
    qf_json_response(array('ok' => false, 'error' => '请先登录后再操作。'), 401);
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    qf_json_response(array('ok' => false, 'error' => '请求方式不正确。'), 405);
}

$thread_id = isset($_POST['thread_id']) ? intval($_POST['thread_id']) : 0;
$post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
if ($thread_id <= 0 && $post_id <= 0) {
    qf_json_response(array('ok' => false, 'error' => '参数不正确。'), 400);
}

if ($post_id > 0) {
    qf_ensure_post_vote_schema();
    $post_rs = mysqli_query(db(), "SELECT id, thread_id FROM qf_posts WHERE id={$post_id} AND is_deleted=0 LIMIT 1");
    if (!$post_rs || mysqli_num_rows($post_rs) === 0) {
        qf_json_response(array('ok' => false, 'error' => '评论不存在。'), 404);
    }
    $post_row = mysqli_fetch_assoc($post_rs);
    if ($thread_id <= 0) {
        $thread_id = intval($post_row['thread_id']);
    } elseif (intval($post_row['thread_id']) !== $thread_id) {
        qf_json_response(array('ok' => false, 'error' => '参数不正确。'), 400);
    }
} else {
    $thread_rs = mysqli_query(db(), "SELECT id FROM qf_threads WHERE id={$thread_id} AND is_deleted=0 LIMIT 1");
    if (!$thread_rs || mysqli_num_rows($thread_rs) === 0) {
        qf_json_response(array('ok' => false, 'error' => '帖子不存在。'), 404);
    }
}

$user_id = intval($u['id']);
$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string)$_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

function qf_react_redirect_back($thread_id) {
    $back = isset($_SERVER['HTTP_REFERER']) ? (string)$_SERVER['HTTP_REFERER'] : qf_url_thread($thread_id);
    header('Location: ' . $back);
    exit;
}

$reaction_raw = isset($_POST['reaction']) ? trim((string)$_POST['reaction']) : '';
$vote_raw = isset($_POST['vote']) ? trim((string)$_POST['vote']) : '';

// ===== 表情反应 =====
if ($reaction_raw !== '') {
    qf_ensure_thread_reaction_schema();
    $types = qf_reaction_types();
    if (!isset($types[$reaction_raw])) {
        qf_json_response(array('ok' => false, 'error' => '表态参数不正确。'), 400);
    }
    $reaction_sql = mysqli_real_escape_string(db(), $reaction_raw);
    $current = qf_user_thread_reaction($thread_id, $user_id);
    if ($current === $reaction_raw) {
        // 再次点击同一表情 => 取消
        mysqli_query(db(), "DELETE FROM qf_thread_reactions WHERE thread_id={$thread_id} AND user_id={$user_id}");
        $active = '';
    } elseif ($current === '') {
        mysqli_query(db(), "INSERT INTO qf_thread_reactions (thread_id,user_id,reaction,created_at,updated_at) VALUES ({$thread_id},{$user_id},'{$reaction_sql}',NOW(),NOW())");
        $active = $reaction_raw;
    } else {
        // 切换到另一种表情
        mysqli_query(db(), "UPDATE qf_thread_reactions SET reaction='{$reaction_sql}', updated_at=NOW() WHERE thread_id={$thread_id} AND user_id={$user_id}");
        $active = $reaction_raw;
    }
    $counts = qf_thread_reaction_counts($thread_id);
    if (!$is_ajax) {
        qf_react_redirect_back($thread_id);
    }
    qf_json_response(array('ok' => true, 'active' => $active, 'counts' => $counts));
}

// ===== 顶/踩投票（评论 post_id 优先；无 post_id 时兼容旧帖级投票）=====
$vote = 0;
if ($vote_raw === 'up' || $vote_raw === '1') {
    $vote = 1;
} elseif ($vote_raw === 'down' || $vote_raw === '-1') {
    $vote = -1;
}
if ($vote === 0) {
    qf_json_response(array('ok' => false, 'error' => '参数不正确。'), 400);
}

if ($post_id > 0) {
    qf_ensure_post_vote_schema();
    $current = 0;
    $vote_rs = mysqli_query(db(), "SELECT vote FROM qf_post_votes WHERE post_id={$post_id} AND user_id={$user_id} LIMIT 1");
    if ($vote_rs && ($row = mysqli_fetch_assoc($vote_rs))) {
        $current = intval($row['vote']);
    }
    $new_vote = $current === $vote ? 0 : $vote;
    if ($current === 0 && $new_vote !== 0) {
        mysqli_query(db(), "INSERT INTO qf_post_votes (post_id,user_id,vote,created_at,updated_at) VALUES ({$post_id},{$user_id},{$new_vote},NOW(),NOW())");
    } elseif ($new_vote === 0) {
        mysqli_query(db(), "DELETE FROM qf_post_votes WHERE post_id={$post_id} AND user_id={$user_id}");
    } else {
        mysqli_query(db(), "UPDATE qf_post_votes SET vote={$new_vote}, updated_at=NOW() WHERE post_id={$post_id} AND user_id={$user_id}");
    }
    $counts = qf_recount_post_votes($post_id);
    if (!$is_ajax) {
        qf_react_redirect_back($thread_id);
    }
    qf_json_response(array(
        'ok' => true,
        'vote' => $new_vote,
        'upvotes' => intval($counts['upvotes']),
        'downvotes' => intval($counts['downvotes']),
    ));
}

qf_ensure_thread_vote_schema();
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
if (!$is_ajax) {
    qf_react_redirect_back($thread_id);
}
qf_json_response(array(
    'ok' => true,
    'vote' => $new_vote,
    'upvotes' => intval($counts['upvotes']),
    'downvotes' => intval($counts['downvotes']),
));
