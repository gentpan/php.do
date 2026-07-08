<?php
/* core/messaging.php — 由 functions.php 自动切分。集中 25 个定义。 */

function pd_notifications_ready() {
    $table = mysqli_query(db(), "SHOW TABLES LIKE 'pd_notifications'");
    return $table && mysqli_num_rows($table) > 0;
}

function pd_unread_notifications_count($user_id) {
    if (!$user_id || !pd_notifications_ready()) {
        return 0;
    }
    $user_id = intval($user_id);
    return count_rows("SELECT COUNT(*) FROM pd_notifications WHERE user_id={$user_id} AND is_read=0");
}

function pd_pm_ready() {
    static $ready = null;
    if ($ready !== null) {
        return $ready;
    }
    pd_ensure_pm_schema();
    $table = mysqli_query(db(), "SHOW TABLES LIKE 'pd_pm_threads'");
    $ready = $table && mysqli_num_rows($table) > 0;
    return $ready;
}

function pd_pm_pair_ids($user_a, $user_b) {
    $user_a = intval($user_a);
    $user_b = intval($user_b);
    return $user_a < $user_b ? array($user_a, $user_b) : array($user_b, $user_a);
}

function pd_pm_unread_count($user_id) {
    if (!$user_id || !pd_pm_ready()) {
        return 0;
    }
    $user_id = intval($user_id);
    return count_rows("SELECT COUNT(*) FROM pd_pm_messages WHERE recipient_id={$user_id} AND is_read=0");
}

function pd_pm_user_brief($user_id) {
    $user_id = intval($user_id);
    if ($user_id < 1) {
        return null;
    }
    $rs = mysqli_query(db(), "SELECT id,username,nickname,avatar FROM pd_users WHERE id={$user_id} AND status=1 LIMIT 1");
    return $rs ? mysqli_fetch_assoc($rs) : null;
}

function pd_pm_get_thread_row($thread_id) {
    $thread_id = intval($thread_id);
    if ($thread_id < 1 || !pd_pm_ready()) {
        return null;
    }
    $rs = mysqli_query(db(), "SELECT * FROM pd_pm_threads WHERE id={$thread_id} LIMIT 1");
    return $rs ? mysqli_fetch_assoc($rs) : null;
}

function pd_pm_thread_peer_id($thread, $user_id) {
    $user_id = intval($user_id);
    if (!$thread) {
        return 0;
    }
    if (intval($thread['user1_id']) === $user_id) {
        return intval($thread['user2_id']);
    }
    if (intval($thread['user2_id']) === $user_id) {
        return intval($thread['user1_id']);
    }
    return 0;
}

function pd_pm_user_in_thread($thread, $user_id) {
    return pd_pm_thread_peer_id($thread, $user_id) > 0;
}

function pd_pm_unhide_thread($thread_id, $user_id) {
    $thread = pd_pm_get_thread_row($thread_id);
    $user_id = intval($user_id);
    if (!$thread || !pd_pm_user_in_thread($thread, $user_id)) {
        return false;
    }
    if (intval($thread['user1_id']) === $user_id) {
        return mysqli_query(db(), "UPDATE pd_pm_threads SET user1_hidden=0 WHERE id=" . intval($thread_id));
    }
    return mysqli_query(db(), "UPDATE pd_pm_threads SET user2_hidden=0 WHERE id=" . intval($thread_id));
}

function pd_pm_get_or_create_thread($user_a, $user_b) {
    if (!pd_pm_ready()) {
        return 0;
    }
    $user_a = intval($user_a);
    $user_b = intval($user_b);
    if ($user_a < 1 || $user_b < 1 || $user_a === $user_b) {
        return 0;
    }
    list($low, $high) = pd_pm_pair_ids($user_a, $user_b);
    $rs = mysqli_query(db(), "SELECT id FROM pd_pm_threads WHERE user1_id={$low} AND user2_id={$high} LIMIT 1");
    $row = $rs ? mysqli_fetch_assoc($rs) : null;
    if ($row) {
        return intval($row['id']);
    }
    if (!pd_pm_user_brief($low) || !pd_pm_user_brief($high)) {
        return 0;
    }
    mysqli_query(db(), "INSERT INTO pd_pm_threads (user1_id,user2_id,last_message_id,updated_at,user1_hidden,user2_hidden) VALUES ({$low},{$high},0,NOW(),0,0)");
    return intval(mysqli_insert_id(db()));
}

function pd_pm_send_message($sender_id, $recipient_id, $body, $thread_id = 0) {
    if (!pd_pm_ready()) {
        return array('ok' => false, 'error' => '私信功能未就绪。');
    }
    $sender_id = intval($sender_id);
    $recipient_id = intval($recipient_id);
    $body = clean_text($body, 2000);
    if ($sender_id < 1 || $recipient_id < 1 || $sender_id === $recipient_id) {
        return array('ok' => false, 'error' => '无效的收件人。');
    }
    if ($body === '') {
        return array('ok' => false, 'error' => '消息不能为空。');
    }
    if (!pd_pm_user_brief($recipient_id)) {
        return array('ok' => false, 'error' => '用户不存在。');
    }
    if ($thread_id < 1) {
        $thread_id = pd_pm_get_or_create_thread($sender_id, $recipient_id);
    }
    $thread = pd_pm_get_thread_row($thread_id);
    if (!$thread || !pd_pm_user_in_thread($thread, $sender_id) || pd_pm_thread_peer_id($thread, $sender_id) !== $recipient_id) {
        return array('ok' => false, 'error' => '会话不存在。');
    }
    pd_pm_unhide_thread($thread_id, $sender_id);
    pd_pm_unhide_thread($thread_id, $recipient_id);
    $body_sql = esc($body);
    $thread_id = intval($thread_id);
    $ok = mysqli_query(db(), "INSERT INTO pd_pm_messages (thread_id,sender_id,recipient_id,body,is_read,created_at) VALUES ({$thread_id},{$sender_id},{$recipient_id},'{$body_sql}',0,NOW())");
    if (!$ok) {
        return array('ok' => false, 'error' => '发送失败，请稍后重试。');
    }
    $message_id = intval(mysqli_insert_id(db()));
    mysqli_query(db(), "UPDATE pd_pm_threads SET last_message_id={$message_id}, updated_at=NOW() WHERE id={$thread_id}");
    return array('ok' => true, 'thread_id' => $thread_id, 'message_id' => $message_id);
}

function pd_pm_mark_thread_read($thread_id, $user_id) {
    $thread_id = intval($thread_id);
    $user_id = intval($user_id);
    if ($thread_id < 1 || $user_id < 1 || !pd_pm_ready()) {
        return;
    }
    mysqli_query(db(), "UPDATE pd_pm_messages SET is_read=1 WHERE thread_id={$thread_id} AND recipient_id={$user_id} AND is_read=0");
}

function pd_pm_hide_thread($thread_id, $user_id) {
    $thread = pd_pm_get_thread_row($thread_id);
    $user_id = intval($user_id);
    if (!$thread || !pd_pm_user_in_thread($thread, $user_id)) {
        return false;
    }
    if (intval($thread['user1_id']) === $user_id) {
        return mysqli_query(db(), "UPDATE pd_pm_threads SET user1_hidden=1 WHERE id=" . intval($thread_id));
    }
    return mysqli_query(db(), "UPDATE pd_pm_threads SET user2_hidden=1 WHERE id=" . intval($thread_id));
}

function pd_pm_fetch_threads($user_id, $limit = 40) {
    if (!pd_pm_ready()) {
        return array();
    }
    $user_id = intval($user_id);
    $limit = max(1, min(100, intval($limit)));
    $sql = "SELECT t.*,
        m.body AS last_body,
        m.sender_id AS last_sender_id,
        m.created_at AS last_at,
        (SELECT COUNT(*) FROM pd_pm_messages um WHERE um.thread_id=t.id AND um.recipient_id={$user_id} AND um.is_read=0) AS unread_count
        FROM pd_pm_threads t
        LEFT JOIN pd_pm_messages m ON m.id=t.last_message_id
        WHERE (t.user1_id={$user_id} AND t.user1_hidden=0) OR (t.user2_id={$user_id} AND t.user2_hidden=0)
        ORDER BY t.updated_at DESC
        LIMIT {$limit}";
    $rs = mysqli_query(db(), $sql);
    $rows = array();
    while ($rs && ($row = mysqli_fetch_assoc($rs))) {
        $rows[] = $row;
    }
    return $rows;
}

function pd_pm_fetch_messages($thread_id, $user_id, $limit = 80) {
    $thread = pd_pm_get_thread_row($thread_id);
    if (!$thread || !pd_pm_user_in_thread($thread, $user_id)) {
        return array();
    }
    $thread_id = intval($thread_id);
    $limit = max(1, min(200, intval($limit)));
    $rs = mysqli_query(db(), "SELECT * FROM pd_pm_messages WHERE thread_id={$thread_id} ORDER BY id DESC LIMIT {$limit}");
    $rows = array();
    while ($rs && ($row = mysqli_fetch_assoc($rs))) {
        $rows[] = $row;
    }
    return array_reverse($rows);
}

function pd_pm_excerpt($text, $max = 42) {
    $text = trim(preg_replace('/\s+/u', ' ', strip_tags((string)$text)));
    if ($text === '') {
        return '';
    }
    if (function_exists('mb_strlen') && mb_strlen($text, 'UTF-8') > $max) {
        return mb_substr($text, 0, $max, 'UTF-8') . '…';
    }
    if (strlen($text) > $max * 2) {
        return substr($text, 0, $max) . '…';
    }
    return $text;
}

function pd_notification_sound_enabled($user) {
    if (!$user) {
        return false;
    }
    return intval(isset($user['notification_sound_enabled']) ? $user['notification_sound_enabled'] : 1) === 1;
}

function pd_online_window_seconds() {
    return 900; // 15 分钟内算在线
}

function pd_online_touch($force = false) {
    if (PHP_SAPI === 'cli') {
        return;
    }
    pd_ensure_online_schema();

    $now = time();
    $last = isset($_SESSION['pd_online_touched_at']) ? intval($_SESSION['pd_online_touched_at']) : 0;
    if (!$force && $last > 0 && ($now - $last) < 60) {
        return;
    }
    $_SESSION['pd_online_touched_at'] = $now;

    $sid = session_id();
    if ($sid === '') {
        return;
    }
    $sid_sql = esc(substr($sid, 0, 64));
    $user = current_user();
    $uid = $user ? intval($user['id']) : 0;
    $ip_sql = esc(substr((string)client_ip(), 0, 45));
    $ua = isset($_SERVER['HTTP_USER_AGENT']) ? (string)$_SERVER['HTTP_USER_AGENT'] : '';
    $ua_sql = esc(substr($ua, 0, 255));

    mysqli_query(db(), "INSERT INTO pd_online (session_id,user_id,ip,user_agent,last_seen)
        VALUES ('{$sid_sql}',{$uid},'{$ip_sql}','{$ua_sql}',NOW())
        ON DUPLICATE KEY UPDATE user_id={$uid}, ip='{$ip_sql}', user_agent='{$ua_sql}', last_seen=NOW()");

    // 偶尔清理过期会话，降低表膨胀
    if (mt_rand(1, 40) === 1) {
        $ttl = pd_online_window_seconds() * 4;
        mysqli_query(db(), "DELETE FROM pd_online WHERE last_seen < DATE_SUB(NOW(), INTERVAL {$ttl} SECOND)");
    }

    pd_online_record_daily_peak();
}

function pd_online_counts() {
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    pd_ensure_online_schema();
    $win = pd_online_window_seconds();
    $members = count_rows("SELECT COUNT(DISTINCT user_id) FROM pd_online WHERE user_id > 0 AND last_seen >= DATE_SUB(NOW(), INTERVAL {$win} SECOND)");
    $guests = count_rows("SELECT COUNT(*) FROM pd_online WHERE user_id = 0 AND last_seen >= DATE_SUB(NOW(), INTERVAL {$win} SECOND)");
    $cache = array(
        'members' => $members,
        'guests' => $guests,
        'total' => $members + $guests,
    );
    return $cache;
}

function pd_online_members($limit = 20) {
    pd_ensure_online_schema();
    $win = pd_online_window_seconds();
    $limit = max(1, min(50, intval($limit)));
    $rs = mysqli_query(db(), "SELECT u.id, u.nickname, u.username, MAX(o.last_seen) AS last_seen
        FROM pd_online o
        INNER JOIN pd_users u ON u.id = o.user_id
        WHERE o.user_id > 0 AND o.last_seen >= DATE_SUB(NOW(), INTERVAL {$win} SECOND) AND u.status=1
        GROUP BY u.id, u.nickname, u.username
        ORDER BY last_seen DESC
        LIMIT {$limit}");
    $rows = array();
    while ($rs && ($row = mysqli_fetch_assoc($rs))) {
        $rows[] = $row;
    }
    return $rows;
}

function pd_online_stat_day() {
    $timezone = 'Asia/Shanghai';
    try {
        return (new DateTimeImmutable('now', new DateTimeZone('UTC')))
            ->setTimezone(new DateTimeZone($timezone))
            ->format('Y-m-d');
    } catch (Exception $e) {
        return gmdate('Y-m-d');
    }
}

function pd_online_record_daily_peak() {
    $counts = pd_online_counts();
    $day = esc(pd_online_stat_day());
    $total = intval($counts['total']);
    $members = intval($counts['members']);
    $guests = intval($counts['guests']);
    mysqli_query(db(), "INSERT INTO pd_online_daily (day_date,peak_total,peak_members,peak_guests,peak_at,updated_at)
        VALUES ('{$day}',{$total},{$members},{$guests},NOW(),NOW())
        ON DUPLICATE KEY UPDATE
          peak_members = IF(VALUES(peak_total) > peak_total, VALUES(peak_members), peak_members),
          peak_guests = IF(VALUES(peak_total) > peak_total, VALUES(peak_guests), peak_guests),
          peak_at = IF(VALUES(peak_total) > peak_total, NOW(), peak_at),
          peak_total = GREATEST(peak_total, VALUES(peak_total)),
          updated_at = NOW()");
}

function pd_online_today_peak() {
    pd_ensure_online_schema();
    $day = esc(pd_online_stat_day());
    $rs = mysqli_query(db(), "SELECT * FROM pd_online_daily WHERE day_date='{$day}' LIMIT 1");
    $row = $rs ? mysqli_fetch_assoc($rs) : null;
    if ($row) {
        return $row;
    }
    $counts = pd_online_counts();
    return array(
        'day_date' => $day,
        'peak_total' => intval($counts['total']),
        'peak_members' => intval($counts['members']),
        'peak_guests' => intval($counts['guests']),
        'peak_at' => null,
    );
}
