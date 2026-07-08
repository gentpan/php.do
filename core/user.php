<?php
/* core/user.php — 由 functions.php 自动切分。集中 48 个定义。 */

function pd_avatar_initial($nickname, $username) {
    $label = trim((string)$nickname);
    if ($label === '') {
        $label = trim((string)$username);
    }
    if ($label === '') {
        return 'B';
    }
    return function_exists('mb_substr') ? mb_substr($label, 0, 1, 'UTF-8') : strtoupper(substr($label, 0, 1));
}

function pd_avatar_gravatar_enabled() {
    return intval(pd_setting('avatar_gravatar_enabled', '1')) === 1;
}

function pd_avatar_upload_enabled() {
    return intval(pd_setting('avatar_upload_enabled', '1')) === 1;
}

function pd_avatar_cartoon_enabled() {
    return intval(pd_setting('avatar_cartoon_enabled', '1')) === 1;
}

/**
 * 统一解析用户头像的优先级：
 * 1) 自定义上传（avatar 为非生成路径）
 * 2) 用户主动选择的随机卡通（pick- 前缀，优先于 Gravatar）
 * 3) 绑定了邮箱且全局开启 Gravatar
 * 4) 注册时自动生成的默认卡通（user-/demo- 前缀）
 * 5) 兜底默认图
 * 传入的数组需含 avatar，可选 email。
 */
function pd_user_avatar($user, $size = 160) {
    $avatar = isset($user['avatar']) ? (string)$user['avatar'] : '';
    if ($avatar !== '' && !pd_is_generated_avatar_path($avatar)) {
        return $avatar;
    }
    if (pd_is_chosen_cartoon_path($avatar)) {
        return $avatar;
    }
    $email = '';
    if (isset($user['email'])) {
        $email = trim((string)$user['email']);
    } elseif (isset($user['user_email'])) {
        $email = trim((string)$user['user_email']);
    }
    if ($email !== '' && pd_avatar_gravatar_enabled()) {
        return pd_gravatar_url($email, $size);
    }
    return $avatar !== '' ? $avatar : 'assets/avatar-default.svg';
}

function pd_points_for_thread() { return pd_setting_int('points_thread', 10, 0, 100000); }

function pd_points_for_reply() { return pd_setting_int('points_reply', 3, 0, 100000); }

function pd_points_for_floor_reply() { return pd_setting_int('points_floor_reply', 1, 0, 100000); }

function pd_points_for_good() { return pd_setting_int('points_good_bonus', 20, 0, 100000); }

function pd_level_thresholds() {
    $raw = trim((string)pd_setting('level_thresholds', ''));
    $out = array();
    if ($raw !== '') {
        foreach (preg_split('/\r\n|\r|\n/', $raw) as $line) {
            $line = trim($line);
            if ($line === '' || strpos($line, ':') === false) continue;
            list($lv, $need) = array_map('trim', explode(':', $line, 2));
            $lv = intval($lv);
            $need = intval($need);
            if ($lv >= 1 && $lv <= 50) $out[$lv] = max(0, $need);
        }
    }
    if (empty($out)) $out = pd_default_level_thresholds();
    ksort($out, SORT_NUMERIC);
    return $out;
}

function pd_level_names() {
    $raw = trim((string)pd_setting('level_names', ''));
    $out = array();
    if ($raw === '') return $out;
    foreach (preg_split('/\r\n|\r|\n/', $raw) as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, ':') === false) continue;
        list($lv, $name) = array_map('trim', explode(':', $line, 2));
        $lv = intval($lv);
        $name = clean_text($name, 20);
        if ($lv >= 1 && $name !== '') $out[$lv] = $name;
    }
    return $out;
}

function pd_level_name($level) {
    $names = pd_level_names();
    $level = intval($level);
    return isset($names[$level]) ? $names[$level] : ('Lv.' . $level);
}

function pd_user_level($points) {
    $points = intval($points);
    $level = 1;
    foreach (pd_level_thresholds() as $lv => $need) {
        if ($points >= $need) $level = $lv;
        else break;
    }
    return $level;
}

function pd_points_to_next_level($points) {
    $points = intval($points);
    $thresholds = pd_level_thresholds();
    $current = pd_user_level($points);
    if (!isset($thresholds[$current + 1])) return 0;
    return max(0, intval($thresholds[$current + 1]) - $points);
}

function pd_level_progress($points) {
    $points = intval($points);
    $thresholds = pd_level_thresholds();
    $level = pd_user_level($points);
    $curr_need = isset($thresholds[$level]) ? intval($thresholds[$level]) : 0;
    if (!isset($thresholds[$level + 1])) {
        return array('level' => $level, 'current' => $points, 'start' => $curr_need, 'next' => 0, 'remain' => 0, 'percent' => 100, 'max' => true);
    }
    $next_need = intval($thresholds[$level + 1]);
    $span = max(1, $next_need - $curr_need);
    $got = max(0, $points - $curr_need);
    return array(
        'level' => $level,
        'current' => $points,
        'start' => $curr_need,
        'next' => $next_need,
        'remain' => max(0, $next_need - $points),
        'percent' => (int)min(100, floor($got * 100 / $span)),
        'max' => false,
    );
}

function pd_points_reason_label($reason) {
    $map = array(
        'thread' => '发布主题', 'reply' => '发表回复', 'floor_reply' => '楼中楼回复',
        'del_thread' => '主题被删除', 'del_post' => '回复被删除',
        'good_on' => '主题加精奖励', 'good_off' => '取消加精',
        'admin' => '管理员调整', 'recalc' => '积分重算', 'clear' => '清除内容',
    );
    return isset($map[$reason]) ? $map[$reason] : $reason;
}

function pd_user_group($user_or_id) {
    pd_ensure_points_schema();
    if (is_array($user_or_id)) {
        $gid = intval(isset($user_or_id['group_id']) ? $user_or_id['group_id'] : 0);
        $points = intval(isset($user_or_id['points']) ? $user_or_id['points'] : 0);
    } else {
        $uid = intval($user_or_id);
        if ($uid <= 0) return null;
        $rs = mysqli_query(db(), "SELECT points, group_id FROM pd_users WHERE id={$uid} LIMIT 1");
        $row = $rs ? mysqli_fetch_assoc($rs) : null;
        if (!$row) return null;
        $gid = intval($row['group_id']);
        $points = intval($row['points']);
    }
    if ($gid > 0) {
        $gr = mysqli_query(db(), "SELECT * FROM pd_user_groups WHERE id={$gid} LIMIT 1");
        $group = $gr ? mysqli_fetch_assoc($gr) : null;
        if ($group) return $group;
    }
    $gr = mysqli_query(db(), "SELECT * FROM pd_user_groups WHERE min_points <= {$points} ORDER BY min_points DESC, display_order ASC, id ASC LIMIT 1");
    return $gr ? mysqli_fetch_assoc($gr) : null;
}

function pd_user_group_badge_html($user_or_id) {
    $group = pd_user_group($user_or_id);
    if (!$group) return '';
    $color = preg_match('/^#[0-9a-fA-F]{3,8}$/', $group['color']) ? $group['color'] : '#505b93';
    return '<span class="pd-group-badge" style="--group-color:' . h($color) . '">' . h($group['name']) . '</span>';
}

function pd_level_badge_html($points, $show_name = false) {
    $level = pd_user_level($points);
    $html = '<span class="pd-level">Lv.' . intval($level) . '</span>';
    if ($show_name) $html .= ' <span class="pd-level-name">' . h(pd_level_name($level)) . '</span>';
    return $html;
}

function pd_staff_list($role) {
    $where = ($role === 'admin') ? 'is_admin=1' : 'is_moderator=1 AND is_admin=0';
    $rs = mysqli_query(db(), "SELECT id, username, nickname, avatar, email, signature FROM pd_users WHERE {$where} ORDER BY id ASC LIMIT 60");
    $out = array();
    while ($rs && ($r = mysqli_fetch_assoc($rs))) {
        $out[] = $r;
    }
    return $out;
}

function pd_user_display_name($row) {
    $nick = isset($row['nickname']) ? $row['nickname'] : '';
    if ($nick !== null && $nick !== '') {
        return $nick;
    }
    return isset($row['username']) ? $row['username'] : '';
}

function pd_user_thread_reaction($thread_id, $user_id) {
    $thread_id = intval($thread_id);
    $user_id = intval($user_id);
    if ($user_id <= 0) {
        return '';
    }
    $rs = mysqli_query(db(), "SELECT reaction FROM pd_thread_reactions WHERE thread_id={$thread_id} AND user_id={$user_id} LIMIT 1");
    if ($rs && ($row = mysqli_fetch_assoc($rs))) {
        return (string)$row['reaction'];
    }
    return '';
}

function pd_signin_base_coins() {
    return pd_setting_int('signin_base_coins', 5, 0, 100000);
}

function pd_signin_streak_bonus() {
    return pd_setting_int('signin_streak_bonus', 2, 0, 100000);
}

function pd_moderator_daily_delete_limit() {
    return pd_setting_int('moderator_daily_delete_limit', 20, 0, 10000);
}

function current_user() {
    if (empty($_SESSION['pd_uid'])) {
        return null;
    }
    $uid = intval($_SESSION['pd_uid']);
    $rs = mysqli_query(db(), "SELECT * FROM pd_users WHERE id={$uid} LIMIT 1");
    return $rs ? mysqli_fetch_assoc($rs) : null;
}

function pd_signin_table_ready() {
    $table = mysqli_query(db(), "SHOW TABLES LIKE 'pd_signins'");
    return $table && mysqli_num_rows($table) > 0;
}

function pd_user_coins_ready() {
    $column = mysqli_query(db(), "SHOW COLUMNS FROM pd_users LIKE 'coins'");
    return $column && mysqli_num_rows($column) > 0;
}

function pd_user_signed_today($user_id) {
    if (!$user_id || !pd_signin_table_ready()) {
        return false;
    }
    $user_id = intval($user_id);
    $today = esc(pd_user_today_ymd($user_id));
    $rs = mysqli_query(db(), "SELECT id FROM pd_signins WHERE user_id={$user_id} AND signin_date='{$today}' LIMIT 1");
    return $rs && mysqli_num_rows($rs) > 0;
}

function pd_signin_reward($user_id, &$message) {
    $user_id = intval($user_id);
    if ($user_id < 1) {
        $message = '请先登录后再签到。';
        return false;
    }
    if (!pd_signin_table_ready()) {
        $message = '签到表不存在，请先访问 install/upgrade.php 升级数据库。';
        return false;
    }
    if (!pd_user_coins_ready()) {
        $message = '金币字段不存在，请先访问 install/upgrade.php 升级数据库。';
        return false;
    }
    if (pd_user_signed_today($user_id)) {
        $message = '今天已经签到过了。';
        return false;
    }
    $continuous_days = 1;
    $today = esc(pd_user_today_ymd($user_id));
    try {
        $yesterday_ymd = esc((new DateTimeImmutable($today, new DateTimeZone('UTC')))->sub(new DateInterval('P1D'))->format('Y-m-d'));
    } catch (Exception $e) {
        $yesterday_ymd = esc(gmdate('Y-m-d', strtotime($today . ' -1 day')));
    }
    $yesterday = mysqli_query(db(), "SELECT continuous_days FROM pd_signins WHERE user_id={$user_id} AND signin_date='{$yesterday_ymd}' LIMIT 1");
    if ($yesterday && ($row = mysqli_fetch_assoc($yesterday))) {
        $continuous_days = intval($row['continuous_days']) + 1;
    }
    $reward = pd_signin_base_coins();
    if ($continuous_days > 1) {
        $reward += pd_signin_streak_bonus();
    }
    mysqli_query(db(), "INSERT INTO pd_signins (user_id, signin_date, continuous_days, reward_coins, created_at) VALUES ({$user_id}, '{$today}', {$continuous_days}, {$reward}, NOW())");
    mysqli_query(db(), "UPDATE pd_users SET coins=coins+{$reward} WHERE id={$user_id}");
    $message = '签到成功，获得 ' . $reward . ' 金币，连续签到 ' . $continuous_days . ' 天。';
    return true;
}

function pd_invite_table_ready() {
    $t = mysqli_query(db(), "SHOW TABLES LIKE 'pd_invites'");
    return $t && mysqli_num_rows($t) > 0;
}

function pd_invite_valid($code) {
    if (!pd_invite_table_ready()) {
        return null;
    }
    $code = trim((string)$code);
    if ($code === '') {
        return null;
    }
    $code_sql = esc($code);
    $rs = mysqli_query(db(), "SELECT * FROM pd_invites WHERE code='{$code_sql}' AND used_by=0 AND (expires_at IS NULL OR expires_at > NOW()) LIMIT 1");
    return $rs ? mysqli_fetch_assoc($rs) : null;
}

function pd_oauth_table_ready() {
    $t = mysqli_query(db(), "SHOW TABLES LIKE 'pd_oauth'");
    return $t && mysqli_num_rows($t) > 0;
}

function pd_oauth_providers() {
    return array(
        'github' => array(
            'label' => 'GitHub',
            'authorize' => 'https://github.com/login/oauth/authorize',
            'token' => 'https://github.com/login/oauth/access_token',
            'scope' => 'read:user user:email',
            'icon' => 'fa-brands fa-github',
            'logo' => 'assets/logos/github.svg',
        ),
        'google' => array(
            'label' => 'Google',
            'authorize' => 'https://accounts.google.com/o/oauth2/v2/auth',
            'token' => 'https://oauth2.googleapis.com/token',
            'scope' => 'openid email profile',
            'icon' => 'fa-brands fa-google',
            'logo' => 'assets/logos/google.svg',
        ),
        'x' => array(
            'label' => 'X',
            'authorize' => 'https://twitter.com/i/oauth2/authorize',
            'token' => 'https://api.twitter.com/2/oauth2/token',
            'scope' => 'users.read tweet.read',
            'icon' => 'fa-brands fa-x-twitter',
            'logo' => 'assets/logos/x.svg',
        ),
        'discord' => array(
            'label' => 'Discord',
            'authorize' => 'https://discord.com/oauth2/authorize',
            'token' => 'https://discord.com/api/oauth2/token',
            'scope' => 'identify email',
            'icon' => 'fa-brands fa-discord',
            'logo' => 'assets/logos/discord.svg',
        ),
    );
}

function pd_oauth_enabled($provider) {
    $providers = pd_oauth_providers();
    if (!isset($providers[$provider])) {
        return false;
    }
    return intval(pd_setting('oauth_' . $provider . '_enabled', '0')) === 1
        && trim(pd_setting('oauth_' . $provider . '_client_id', '')) !== ''
        && trim(pd_setting('oauth_' . $provider . '_client_secret', '')) !== '';
}

function pd_oauth_any_enabled() {
    foreach (array_keys(pd_oauth_providers()) as $p) {
        if (pd_oauth_enabled($p)) {
            return true;
        }
    }
    return false;
}

function pd_oauth_redirect_uri($provider) {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');
    $scheme = $https ? 'https' : 'http';
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'php.do';
    return $scheme . '://' . $host . pd_url_page('api/oauth.php', array('provider' => $provider, 'action' => 'callback'));
}

function pd_oauth_login_or_register($provider, $provider_uid, $login, $name, $email) {
    if (!pd_oauth_table_ready() || $provider_uid === '') {
        return 0;
    }
    $p_sql = esc($provider);
    $uid_sql = esc($provider_uid);
    $rs = mysqli_query(db(), "SELECT user_id FROM pd_oauth WHERE provider='{$p_sql}' AND provider_uid='{$uid_sql}' LIMIT 1");
    if ($rs && ($row = mysqli_fetch_assoc($rs))) {
        $uid = intval($row['user_id']);
        $ur = mysqli_query(db(), "SELECT id FROM pd_users WHERE id={$uid} AND status=1 LIMIT 1");
        return ($ur && mysqli_num_rows($ur) > 0) ? $uid : 0;
    }
    $base = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$login);
    if (strlen($base) < 3) {
        $base = $provider . preg_replace('/[^0-9a-zA-Z]/', '', $provider_uid);
    }
    $base = substr($base, 0, 24);
    $username = $base;
    $n = 0;
    while (true) {
        $u_sql = esc($username);
        $chk = mysqli_query(db(), "SELECT id FROM pd_users WHERE username='{$u_sql}' LIMIT 1");
        if (!$chk || mysqli_num_rows($chk) === 0) {
            break;
        }
        $n++;
        if ($n > 9999) {
            return 0;
        }
        $username = substr($base, 0, 20) . $n;
    }
    $nickname = clean_text($name !== '' ? $name : $username, 30);
    if ($nickname === '') {
        $nickname = $username;
    }
    $u_sql = esc($username);
    $n_sql = esc($nickname);
    $ip = esc(client_ip());
    $random_pw = pd_password_hash(bin2hex(random_bytes(18)));
    if (pd_table_has_column('pd_users', 'email') && $email !== '') {
        $email_sql = esc(clean_text($email, 190));
        $ok = mysqli_query(db(), "INSERT INTO pd_users (username,password,nickname,email,ip,created_at) VALUES ('{$u_sql}','{$random_pw}','{$n_sql}','{$email_sql}','{$ip}',NOW())");
    } else {
        $ok = mysqli_query(db(), "INSERT INTO pd_users (username,password,nickname,ip,created_at) VALUES ('{$u_sql}','{$random_pw}','{$n_sql}','{$ip}',NOW())");
    }
    if (!$ok) {
        return 0;
    }
    $new_id = intval(mysqli_insert_id(db()));
    $avatar = pd_generate_default_avatar($new_id, $username, $nickname);
    if ($avatar !== '') {
        $a_sql = esc($avatar);
        mysqli_query(db(), "UPDATE pd_users SET avatar='{$a_sql}' WHERE id={$new_id}");
    }
    mysqli_query(db(), "INSERT INTO pd_oauth (user_id,provider,provider_uid,created_at) VALUES ({$new_id},'{$p_sql}','{$uid_sql}',NOW())");
    return $new_id;
}

function pd_moderator_logs_ready() {
    $table = mysqli_query(db(), "SHOW TABLES LIKE 'pd_moderator_logs'");
    return $table && mysqli_num_rows($table) > 0;
}

function pd_moderator_forums_ready() {
    $table = mysqli_query(db(), "SHOW TABLES LIKE 'pd_moderator_forums'");
    return $table && mysqli_num_rows($table) > 0;
}

function pd_moderator_forum_ids($user_id) {
    if (!pd_moderator_forums_ready()) {
        return array();
    }
    $user_id = intval($user_id);
    $rs = mysqli_query(db(), "SELECT forum_id FROM pd_moderator_forums WHERE user_id={$user_id}");
    $ids = array();
    while ($rs && $row = mysqli_fetch_assoc($rs)) {
        $ids[] = intval($row['forum_id']);
    }
    return $ids;
}

function pd_moderator_assigned_to_forum($user_id, $forum_id) {
    if (!pd_moderator_forums_ready()) {
        return false;
    }
    $user_id = intval($user_id);
    $forum_id = intval($forum_id);
    $rs = mysqli_query(db(), "SELECT id FROM pd_moderator_forums WHERE user_id={$user_id} AND forum_id={$forum_id} LIMIT 1");
    return $rs && mysqli_num_rows($rs) > 0;
}

function pd_moderator_delete_count_today($moderator_id) {
    if (!pd_moderator_logs_ready()) {
        return 0;
    }
    $moderator_id = intval($moderator_id);
    return count_rows("SELECT COUNT(*) FROM pd_moderator_logs WHERE moderator_id={$moderator_id} AND created_at >= CURDATE()");
}

function pd_moderator_delete_limit_for_user($moderator) {
    $limit = intval(isset($moderator['moderator_delete_limit']) ? $moderator['moderator_delete_limit'] : 0);
    return $limit > 0 ? $limit : pd_moderator_daily_delete_limit();
}

function pd_moderator_delete_allowed($moderator) {
    $moderator_id = intval(is_array($moderator) ? $moderator['id'] : $moderator);
    $limit = is_array($moderator) ? pd_moderator_delete_limit_for_user($moderator) : pd_moderator_daily_delete_limit();
    return pd_moderator_delete_count_today($moderator_id) < $limit;
}

function pd_user_mute_message($user) {
    if (!$user || empty($user['mute_until'])) {
        return '';
    }
    $until = pd_parse_utc_timestamp($user['mute_until']);
    if ($until && $until > time()) {
        return '你已被禁止发言，到期时间：' . pd_format_absolute($user['mute_until']);
    }
    return '';
}

function pd_user_timezone($user = null) {
    if ($user === null) {
        $user = current_user();
    }
    if (!$user) {
        return '';
    }
    $timezone = isset($user['timezone']) ? trim((string)$user['timezone']) : '';
    return pd_valid_timezone($timezone) ? $timezone : '';
}

function pd_user_today_ymd($user = null) {
    if (is_int($user) || (is_string($user) && $user !== '' && ctype_digit($user))) {
        $rs = mysqli_query(db(), 'SELECT timezone FROM pd_users WHERE id=' . intval($user) . ' LIMIT 1');
        $user = $rs ? mysqli_fetch_assoc($rs) : null;
    }
    $timezone = pd_user_timezone($user);
    if ($timezone === '') {
        $timezone = 'Asia/Shanghai';
    }
    try {
        return (new DateTimeImmutable('now', new DateTimeZone('UTC')))
            ->setTimezone(new DateTimeZone($timezone))
            ->format('Y-m-d');
    } catch (Exception $e) {
        return gmdate('Y-m-d');
    }
}
