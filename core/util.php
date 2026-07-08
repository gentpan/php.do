<?php
/* core/util.php — 由 functions.php 自动切分。集中 90 个定义。 */

function db() {
    static $conn = null;
    if ($conn) {
        return $conn;
    }
    $port = defined('DB_PORT') ? (int) DB_PORT : 3306;
    $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME, $port);
    if (!$conn) {
        header('Content-Type: text/html; charset=utf-8');
        exit('数据库连接失败：' . mysqli_connect_error());
    }
    pd_assert_mysql_runtime($conn);
    mysqli_set_charset($conn, DB_CHARSET);
    // 统一数据库会话时区为 UTC，保证 NOW() 与 PHP 时区一致（全站按 UTC 存储）
    mysqli_query($conn, "SET time_zone = '+00:00'");
    return $conn;
}

// 页面渲染耗时（秒），用于页脚性能徽章
function pd_perf_seconds() {
    return defined('PD_START') ? (microtime(true) - PD_START) : 0.0;
}

// 本次请求执行的 SQL 语句数（基于连接会话状态 Questions）
function pd_perf_sql_count() {
    $rs = @mysqli_query(db(), "SHOW SESSION STATUS LIKE 'Questions'");
    if ($rs && ($row = mysqli_fetch_assoc($rs))) {
        return max(0, intval($row['Value']) - 1); // 扣除这条 SHOW 自身
    }
    return 0;
}

function h($str) {
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

function clean_text($str, $max) {
    $str = trim((string)$str);
    $str = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $str);
    if (function_exists('mb_substr')) {
        return mb_substr($str, 0, $max, 'UTF-8');
    }
    return substr($str, 0, $max * 3);
}

function esc($str) {
    return mysqli_real_escape_string(db(), (string)$str);
}

function pd_password_hash($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

function pd_password_verify($password, $hash) {
    return password_verify($password, $hash);
}

function pd_is_generated_avatar_path($avatar) {
    $avatar = (string)$avatar;
    return preg_match('#^assets/avatars/(user|demo|pick)-[a-zA-Z0-9_-]+\.svg$#', $avatar) === 1;
}

// 用户主动选择的“随机卡通头像”，用 pick- 前缀区别于注册时自动生成的 user- 默认头像。
function pd_is_chosen_cartoon_path($avatar) {
    return preg_match('#^assets/avatars/pick-[a-zA-Z0-9_-]+\.svg$#', (string)$avatar) === 1;
}

function pd_gravatar_url($email, $size = 160) {
    $hash = md5(strtolower(trim((string)$email)));
    return 'https://gravatar.bluecdn.com/avatar/' . $hash . '?s=' . intval($size) . '&d=identicon';
}

function pd_pick_avatar_part($items, $hash, $shift = 0) {
    return $items[($hash >> $shift) % count($items)];
}

function pd_cartoon_default_avatar_svg($user_id, $username, $nickname, $seed = '') {
    $hash = crc32($user_id . '|' . $username . '|' . $nickname . '|' . $seed);
    $backgrounds = array('#ff4f9a', '#5d29f0', '#bfaaff', '#d9d1ff', '#ffd34f', '#ff8da3', '#cbbcff', '#b990ff');
    $skins = array('#ffd2ba', '#f2b893', '#e9a978', '#ffe0c9', '#d8986d');
    $hair_colors = array('#1f2430', '#5d29f0', '#ffe67a', '#a868ff', '#f05b6a', '#6b3c23');
    $shirt_colors = array('#f5d7e9', '#ffd7c5', '#e4dcff', '#ffe47a', '#e4dcff', '#e9ddff');
    $bg = pd_pick_avatar_part($backgrounds, $hash, 0);
    $skin = pd_pick_avatar_part($skins, $hash, 3);
    $hair = pd_pick_avatar_part($hair_colors, $hash, 6);
    $shirt = pd_pick_avatar_part($shirt_colors, $hash, 9);
    $hair_style = ($hash >> 12) % 6;
    $glasses = (($hash >> 16) % 100) < 64;
    $mouth = ($hash >> 18) % 4;
    $brows = ($hash >> 20) % 3;
    $earring = (($hash >> 22) % 100) < 28;
    $tilt = (($hash >> 24) % 7) - 3;
    $hair_paths = array(
        '<path d="M34 54c2-23 18-36 39-34 19 2 30 16 31 35-17-12-42-12-70-1z" fill="' . $hair . '"/>',
        '<path d="M31 58c3-25 19-39 41-38 16 1 27 10 32 26-15-5-28-12-43-5-10 5-18 11-30 17z" fill="' . $hair . '"/>',
        '<path d="M35 52c4-22 20-34 41-30 15 3 24 15 27 30-22-13-43-14-68 0z" fill="' . $hair . '"/><path d="M48 24c9-10 24-10 34 0-12-2-22-1-34 0z" fill="' . $hair . '"/>',
        '<path d="M32 62c0-28 17-45 40-45 25 0 39 17 38 49-8-17-18-25-32-28-18-4-31 3-46 24z" fill="' . $hair . '"/>',
        '<path d="M36 50c7-20 22-29 42-27 17 2 25 10 27 23-16-9-32-8-48-3-8 2-15 5-21 7z" fill="' . $hair . '"/><path d="M32 55c7-7 15-11 25-13-8 8-15 15-22 25z" fill="' . $hair . '"/>',
        '<path d="M37 52c2-21 16-34 34-34 20 0 34 12 37 34-16-7-33-13-52-8-8 2-14 5-19 8z" fill="' . $hair . '"/><path d="M48 23l15-8 13 8-14 8z" fill="' . $hair . '"/>',
    );
    $mouth_paths = array(
        '<path d="M58 82c6 5 15 5 21 0" fill="none" stroke="#3b1f1f" stroke-width="4" stroke-linecap="round"/>',
        '<path d="M58 82c7 8 17 8 23 0" fill="#fff" stroke="#3b1f1f" stroke-width="3" stroke-linejoin="round"/>',
        '<circle cx="69" cy="84" r="5" fill="#3b1f1f"/>',
        '<path d="M60 85c6-4 13-4 19 0" fill="none" stroke="#3b1f1f" stroke-width="4" stroke-linecap="round"/>',
    );
    $brow_paths = array(
        '<path d="M46 62l13-3M78 59l13 3" stroke="#2b211d" stroke-width="4" stroke-linecap="round"/>',
        '<path d="M46 58l13 3M78 62l13-3" stroke="#2b211d" stroke-width="4" stroke-linecap="round"/>',
        '<path d="M46 60h13M78 60h13" stroke="#2b211d" stroke-width="4" stroke-linecap="round"/>',
    );
    return '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
        . '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 128 128">'
        . '<rect width="128" height="128" rx="14" fill="' . $bg . '"/>'
        . '<g transform="rotate(' . $tilt . ' 64 70)">'
        . '<path d="M38 123c4-20 16-31 31-31s27 11 31 31z" fill="' . $shirt . '" stroke="#191919" stroke-width="1.6"/>'
        . '<path d="M57 91h23v20c0 6-23 6-23 0z" fill="' . $skin . '" stroke="#191919" stroke-width="1.5"/>'
        . '<circle cx="39" cy="69" r="7" fill="' . $skin . '" stroke="#191919" stroke-width="1.5"/>'
        . '<circle cx="98" cy="69" r="7" fill="' . $skin . '" stroke="#191919" stroke-width="1.5"/>'
        . '<path d="M37 60c0-23 13-38 32-38s33 15 33 38v18c0 19-14 30-33 30S37 97 37 78z" fill="' . $skin . '" stroke="#191919" stroke-width="1.8"/>'
        . $hair_paths[$hair_style]
        . $brow_paths[$brows]
        . ($glasses
            ? '<circle cx="54" cy="69" r="9" fill="none" stroke="#171717" stroke-width="2.2"/><circle cx="84" cy="69" r="9" fill="none" stroke="#171717" stroke-width="2.2"/><path d="M63 69h12" stroke="#171717" stroke-width="2.2" stroke-linecap="round"/>'
            : '<circle cx="54" cy="69" r="3" fill="#171717"/><circle cx="84" cy="69" r="3" fill="#171717"/>')
        . '<path d="M68 71c-1 5-3 9-5 12 3 2 7 2 10 0" fill="none" stroke="#9a5b42" stroke-width="2" stroke-linecap="round"/>'
        . $mouth_paths[$mouth]
        . ($earring ? '<circle cx="100" cy="78" r="3" fill="#ffe36d" stroke="#191919" stroke-width="1"/>' : '')
        . '</g>'
        . '</svg>';
}

function pd_generate_default_avatar($user_id, $username, $nickname) {
    $user_id = intval($user_id);
    if ($user_id < 1) {
        return '';
    }
    $dir = pd_default_avatar_dir();
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
        return '';
    }
    $svg = pd_cartoon_default_avatar_svg($user_id, $username, $nickname);
    $path = $dir . '/user-' . $user_id . '.svg';
    if (file_put_contents($path, $svg) === false) {
        return '';
    }
    return pd_default_avatar_public_path($user_id);
}

// 保存用户主动选择的随机卡通头像（pick- 前缀），$seed 决定长相，返回公开路径或 ''。
function pd_save_chosen_cartoon($user_id, $username, $nickname, $seed = '') {
    $user_id = intval($user_id);
    if ($user_id < 1) {
        return '';
    }
    $dir = pd_default_avatar_dir();
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
        return '';
    }
    $svg = pd_cartoon_default_avatar_svg($user_id, $username, $nickname, (string)$seed);
    $path = $dir . '/pick-' . $user_id . '.svg';
    if (file_put_contents($path, $svg) === false) {
        return '';
    }
    return 'assets/avatars/pick-' . $user_id . '.svg';
}

function pd_update_setting($key, $value) {
    $key_sql = esc($key);
    $value_sql = esc($value);
    $ok = mysqli_query(db(), "REPLACE INTO pd_settings (setting_key, setting_value) VALUES ('{$key_sql}', '{$value_sql}')");
    if ($ok) {
        pd_setting(null);
    }
    return $ok;
}

function pd_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function pd_csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . h(pd_csrf_token()) . '">';
}

function pd_verify_csrf() {
    $sent = isset($_POST['csrf_token']) ? (string)$_POST['csrf_token'] : '';
    if ($sent === '' && isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
        $sent = (string)$_SERVER['HTTP_X_CSRF_TOKEN'];
    }
    return $sent !== '' && hash_equals(pd_csrf_token(), $sent);
}

function pd_action_token($action, $id, $extra = '') {
    return hash_hmac('sha256', $action . '|' . intval($id) . '|' . $extra, pd_csrf_token());
}

function pd_inject_csrf_fields($html) {
    if (stripos($html, '<form') === false || stripos($html, 'method="post"') === false) {
        return $html;
    }
    return preg_replace('/(<form\b[^>]*method=["\']post["\'][^>]*>)/i', '$1' . pd_csrf_field(), $html);
}

function pd_sync_user_group($user_id) {
    $user_id = intval($user_id);
    if ($user_id <= 0) return 0;
    $rs = mysqli_query(db(), "SELECT points FROM pd_users WHERE id={$user_id} LIMIT 1");
    $row = $rs ? mysqli_fetch_assoc($rs) : null;
    if (!$row) return 0;
    $points = intval($row['points']);
    $g = mysqli_query(db(), "SELECT id FROM pd_user_groups WHERE min_points <= {$points} ORDER BY min_points DESC, display_order ASC, id ASC LIMIT 1");
    $group = $g ? mysqli_fetch_assoc($g) : null;
    $gid = $group ? intval($group['id']) : 0;
    mysqli_query(db(), "UPDATE pd_users SET group_id={$gid} WHERE id={$user_id}");
    return $gid;
}

function pd_recalc_user_points($user_id) {
    $user_id = intval($user_id);
    if ($user_id <= 0) return 0;
    $thread_pts = pd_points_for_thread();
    $reply_pts = pd_points_for_reply();
    $floor_pts = pd_points_for_floor_reply();
    $good_pts = pd_points_for_good();
    $tr = mysqli_query(db(), "SELECT COUNT(*) AS c FROM pd_threads WHERE user_id={$user_id} AND is_deleted=0");
    $trow = $tr ? mysqli_fetch_assoc($tr) : null;
    $pr = mysqli_query(db(), "SELECT COUNT(*) AS c FROM pd_posts WHERE user_id={$user_id} AND is_deleted=0");
    $prow = $pr ? mysqli_fetch_assoc($pr) : null;
    $fr = mysqli_query(db(), "SELECT COUNT(*) AS c FROM pd_post_comments WHERE user_id={$user_id} AND is_deleted=0");
    $frow = $fr ? mysqli_fetch_assoc($fr) : null;
    $gr = mysqli_query(db(), "SELECT COUNT(*) AS c FROM pd_threads WHERE user_id={$user_id} AND is_deleted=0 AND is_good=1");
    $grow = $gr ? mysqli_fetch_assoc($gr) : null;
    $total = intval($trow['c']) * $thread_pts + intval($prow['c']) * $reply_pts + intval($frow['c']) * $floor_pts + intval($grow['c']) * $good_pts;
    $cur = mysqli_query(db(), "SELECT points FROM pd_users WHERE id={$user_id} LIMIT 1");
    $crow = $cur ? mysqli_fetch_assoc($cur) : null;
    $old = $crow ? intval($crow['points']) : 0;
    $delta = $total - $old;
    if ($delta !== 0) pd_add_user_points($user_id, $delta, 'recalc', 'user', $user_id, '按发帖回复重算');
    else pd_sync_user_group($user_id);
    return $total;
}

function pd_list_user_groups() {
    pd_ensure_points_schema();
    $rs = mysqli_query(db(), "SELECT * FROM pd_user_groups ORDER BY min_points ASC, display_order ASC, id ASC");
    $rows = array();
    while ($rs && $row = mysqli_fetch_assoc($rs)) $rows[] = $row;
    return $rows;
}

function pd_selected_font_urls() {
    $options = pd_font_options();
    $urls = array();
    foreach (array('title_font', 'content_font') as $setting_key) {
        $font_key = pd_font_key($setting_key);
        $url = isset($options[$font_key]['url']) ? $options[$font_key]['url'] : '';
        if ($url !== '') {
            $urls[$url] = $url;
        }
    }
    return array_values($urls);
}

function pd_include_admin_header() {
    include pd_theme_file('admin/_layout_header.php');
}

function pd_include_admin_footer() {
    include pd_theme_file('admin/_layout_footer.php');
}

function pd_append_url_parts($path, $params = array(), $fragment = '') {
    $query = '';
    if (!empty($params)) {
        $query = http_build_query($params);
    }
    return $path . ($query !== '' ? '?' . $query : '') . ($fragment !== '' ? '#' . ltrim($fragment, '#') : '');
}

function pd_clean_route_path($script) {
    $map = array(
        'pages/download.php' => 'download.php',
        'pages/edit-thread.php' => 'edit-thread.php',
        'pages/forum.php' => 'forum.php',
        'pages/move-thread.php' => 'move-thread.php',
        'pages/notifications.php' => 'notifications.php',
        'pages/messages.php' => 'messages.php',
        'pages/login.php' => 'login.php',
        'pages/post.php' => 'post.php',
        'pages/profile.php' => 'settings.php',
        'pages/rankings.php' => 'rankings.php',
        'pages/register.php' => 'register.php',
        'pages/search.php' => 'search.php',
        'pages/thread.php' => 'thread.php',
        'pages/user.php' => 'user.php',
        'pages/page.php' => 'pages.php',
        'api/ad.php' => 'api/ad',
        'api/captcha.php' => 'api/captcha',
        'api/upload-attachment.php' => 'api/upload-attachment',
        'api/upload-image.php' => 'api/upload-image',
        'api/markdown-preview.php' => 'api/markdown-preview',
        'api/auth.php' => 'api/auth',
        'api/delete-attachment.php' => 'api/delete-attachment',
        'api/floor-reply.php' => 'api/floor-reply',
        'api/moderator.php' => 'api/moderator',
        'api/passkey.php' => 'api/passkey',
        'api/reply.php' => 'api/reply',
        'api/signin.php' => 'api/signin',
        'api/react.php' => 'api/react',
        'api/messages.php' => 'api/messages',
        'api/geoip.php' => 'api/geoip',
    );
    return isset($map[$script]) ? $map[$script] : $script;
}

function pd_format_compact_number($number) {
    $number = max(0, intval($number));
    if ($number < 1000) {
        return (string)$number;
    }
    $value = $number / 1000;
    $formatted = $value >= 10 ? number_format($value, 0, '.', '') : number_format($value, 1, '.', '');
    if (strpos($formatted, '.') !== false) {
        $formatted = rtrim(rtrim($formatted, '0'), '.');
    }
    return $formatted . 'k';
}

function pd_path_id() {
    if (isset($_GET['id'])) {
        return intval($_GET['id']);
    }
    $path = isset($_SERVER['PATH_INFO']) ? trim($_SERVER['PATH_INFO'], '/') : '';
    if ($path !== '' && preg_match('/^([0-9]+)/', $path, $m)) {
        return intval($m[1]);
    }
    $uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
    if (preg_match('/\/(?:thread|user)\/([0-9]+)/', $uri, $m)) {
        return intval($m[1]);
    }
    return 0;
}

function pd_contact_email() {
    $e = trim((string)pd_setting('contact_email', ''));
    if ($e !== '') {
        return $e;
    }
    $r = mysqli_query(db(), "SELECT email FROM pd_users WHERE is_admin=1 AND email<>'' ORDER BY id ASC LIMIT 1");
    $row = $r ? mysqli_fetch_assoc($r) : null;
    return ($row && $row['email']) ? $row['email'] : '';
}

// 头部 banner：每个页面固定用对应图，首页用后台选定的 banner；优先 webp。返回本地路径或 ''。
function pd_header_banner_src($script, $slug = '') {
    $dir = 'assets/banner/';
    $base = PD_ROOT . '/' . $dir;
    $first_existing = function ($files) use ($dir, $base) {
        foreach ($files as $f) {
            if (file_exists($base . $f)) {
                return $dir . $f;
            }
        }
        return '';
    };
    if ($script === 'about.php') {
        return $first_existing(array('aboutpd.webp', 'aboutpd.png', 'aboutphpdo.webp', 'aboutphpdo.png'));
    }
    if ($script === 'page.php' && $slug === 'rules') {
        return $first_existing(array('rulespd.webp', 'rulespd.png', 'rulesphpdo.webp', 'rulesphpdo.png'));
    }
    if ($script === 'page.php' && $slug === 'help') {
        return $first_existing(array('helppd.webp', 'helppd.png', 'helpphpdo.webp', 'helpphpdo.png'));
    }
    if ($script === 'index.php') {
        return pd_home_banner_src();
    }
    return '';
}

function pd_member_noun() {
    $n = trim((string)pd_setting('member_noun', ''));
    return $n !== '' ? $n : '成员';
}

function pd_latest_users($limit = 8) {
    $limit = max(1, min(24, intval($limit)));
    $rs = mysqli_query(db(), "SELECT id, username, nickname, avatar, email FROM pd_users ORDER BY id DESC LIMIT {$limit}");
    $out = array();
    while ($rs && ($r = mysqli_fetch_assoc($rs))) {
        $out[] = $r;
    }
    return $out;
}

function pd_recount_thread_votes($thread_id) {
    $thread_id = intval($thread_id);
    pd_ensure_thread_vote_schema();
    $up = count_rows("SELECT COUNT(*) FROM pd_thread_votes WHERE thread_id={$thread_id} AND vote=1");
    $down = count_rows("SELECT COUNT(*) FROM pd_thread_votes WHERE thread_id={$thread_id} AND vote=-1");
    mysqli_query(db(), "UPDATE pd_threads SET upvotes={$up}, downvotes={$down} WHERE id={$thread_id}");
    return array('upvotes' => $up, 'downvotes' => $down);
}

function pd_recount_post_votes($post_id) {
    $post_id = intval($post_id);
    pd_ensure_post_vote_schema();
    $up = count_rows("SELECT COUNT(*) FROM pd_post_votes WHERE post_id={$post_id} AND vote=1");
    $down = count_rows("SELECT COUNT(*) FROM pd_post_votes WHERE post_id={$post_id} AND vote=-1");
    mysqli_query(db(), "UPDATE pd_posts SET upvotes={$up}, downvotes={$down} WHERE id={$post_id}");
    return array('upvotes' => $up, 'downvotes' => $down);
}

function pd_protected_attachment_dir() {
    return PD_ROOT . '/uploads/protected';
}

function pd_protected_attachment_path($ext = 'dat') {
    $dir = pd_protected_attachment_dir();
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    $htaccess = $dir . '/.htaccess';
    if (!file_exists($htaccess)) {
        @file_put_contents($htaccess, "Require all denied\n");
    }
    $index = $dir . '/index.html';
    if (!file_exists($index)) {
        @file_put_contents($index, '');
    }
    $safe_ext = preg_replace('/[^a-z0-9]/i', '', (string)$ext);
    if ($safe_ext === '') {
        $safe_ext = 'dat';
    }
    $name = date('YmdHis') . '_' . mt_rand(1000, 9999) . '_' . mt_rand(1000, 9999) . '.dat';
    return array($dir . '/' . $name, 'uploads/protected/' . $name);
}

function pd_store_uploaded_attachment_file($tmp_name, $ext, &$file_path) {
    if (in_array(strtolower($ext), array('zip', 'rar'))) {
        list($target, $relative) = pd_protected_attachment_path($ext);
        if (!move_uploaded_file($tmp_name, $target)) {
            return false;
        }
        $file_path = $relative;
        return true;
    }
    $upload_dir = PD_ROOT . '/uploads';
    if (!is_dir($upload_dir) && !mkdir($upload_dir, 0755, true)) {
        return false;
    }
    $safe_name = date('YmdHis') . '_' . mt_rand(1000, 9999) . '.' . strtolower($ext);
    $target = $upload_dir . '/' . $safe_name;
    if (!move_uploaded_file($tmp_name, $target)) {
        return false;
    }
    $file_path = 'uploads/' . $safe_name;
    return true;
}

function pd_resolve_attachment_from_url($url) {
    $raw_url = html_entity_decode((string)$url, ENT_QUOTES, 'UTF-8');
    if (preg_match('/download\.php\?id=([0-9]+)/i', $raw_url, $m)) {
        $aid = intval($m[1]);
        $rs = mysqli_query(db(), "SELECT * FROM pd_attachments WHERE id={$aid} LIMIT 1");
        return $rs ? pd_migrate_attachment_to_protected_storage(mysqli_fetch_assoc($rs)) : null;
    }
    if (preg_match('/^\/?uploads\/[^?#]+/i', $raw_url, $m)) {
        $path = ltrim($m[0], '/');
        $path_sql = esc($path);
        $rs = mysqli_query(db(), "SELECT * FROM pd_attachments WHERE file_path='{$path_sql}' LIMIT 1");
        if ($rs && ($att = mysqli_fetch_assoc($rs))) {
            return pd_migrate_attachment_to_protected_storage($att);
        }
        $full_path = realpath(PD_ROOT . '/' . $path);
        $base_dir = realpath(PD_ROOT . '/uploads');
        if ($base_dir && $full_path && strpos($full_path, $base_dir . DIRECTORY_SEPARATOR) === 0 && is_file($full_path)) {
            $original = basename($path);
            $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
            if (in_array($ext, array('zip', 'rar'))) {
                $original_sql = esc($original);
                $ext_sql = esc($ext);
                $size = intval(filesize($full_path));
                list($target, $relative) = pd_protected_attachment_path($ext);
                if (rename($full_path, $target) || (copy($full_path, $target) && @unlink($full_path))) {
                    $path_sql = esc($relative);
                }
                mysqli_query(db(), "INSERT INTO pd_attachments (thread_id,post_id,user_id,file_path,original_name,file_ext,file_size,created_at) VALUES (0,0,0,'{$path_sql}','{$original_sql}','{$ext_sql}',{$size},NOW())");
                $new_id = intval(mysqli_insert_id(db()));
                if ($new_id > 0) {
                    $new_rs = mysqli_query(db(), "SELECT * FROM pd_attachments WHERE id={$new_id} LIMIT 1");
                    return $new_rs ? mysqli_fetch_assoc($new_rs) : null;
                }
            }
        }
    }
    return null;
}

function pd_can_delete_attachment($att, $user = null) {
    if (!$att) {
        return false;
    }
    if ($user === null) {
        $user = current_user();
    }
    if (!$user) {
        return false;
    }
    return intval($user['is_admin']) === 1 || intval($att['user_id']) === intval($user['id']);
}

function pd_soft_delete_post($post_id, $thread_id) {
    $post_id = intval($post_id);
    $thread_id = intval($thread_id);
    if ($post_id <= 0 || $thread_id <= 0) {
        return false;
    }
    $rs = mysqli_query(db(), "SELECT user_id, is_deleted FROM pd_posts WHERE id={$post_id} LIMIT 1");
    $row = $rs ? mysqli_fetch_assoc($rs) : null;
    mysqli_query(db(), "UPDATE pd_posts SET is_deleted=1 WHERE id={$post_id}");
    mysqli_query(db(), "UPDATE pd_threads SET replies=GREATEST(replies-1,0) WHERE id={$thread_id}");
    if ($row && intval($row['is_deleted']) === 0 && intval($row['user_id']) > 0) {
        $delta = -pd_points_for_reply();
        if ($delta !== 0) {
            pd_add_user_points(intval($row['user_id']), $delta, 'del_post', 'post', $post_id);
        }
        mysqli_query(db(), "UPDATE pd_users SET reply_count=GREATEST(reply_count-1,0) WHERE id=" . intval($row['user_id']));
    }
    return true;
}

function pd_action_badge($href, $label, $icon, $extra_class = '', $attrs = '') {
    $class = trim('action-badge ' . $extra_class);
    return '<a class="' . h($class) . '" href="' . h($href) . '" title="' . h($label) . '" aria-label="' . h($label) . '" data-tooltip="' . h($label) . '" ' . trim($attrs) . '><i class="' . h($icon) . '" aria-hidden="true"></i><span>' . h($label) . '</span></a>';
}

function pd_is_ajax_request() {
    if (isset($_GET['ajax']) && $_GET['ajax'] !== '') {
        return true;
    }
    if (isset($_POST['ajax']) && $_POST['ajax'] !== '') {
        return true;
    }
    return isset($_SERVER['HTTP_X_REQUESTED_WITH'])
        && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

function pd_guest_download_confirm_onclick() {
    return 'data-login-required="1" data-login-url="' . h(pd_url_page('register.php')) . '"';
}

function pd_remove_attachment_tag_from_content($content, $attachment_id) {
    $needle = preg_quote(pd_attachment_url($attachment_id), '/');
    $content = preg_replace('/\s*\[file\s+url=(["\'])' . $needle . '\1\s+name=(["\']).*?\2\].*?\[\/file\]\s*/is', "\n", $content);
    return trim($content);
}

function pd_valid_nav_url($url) {
    $url = trim((string)$url);
    if ($url === '') {
        return false;
    }
    if (preg_match('/^https?:\/\//i', $url)) {
        return true;
    }
    if (preg_match('/^[a-z0-9_\/\.\-\?=&%#]+$/i', $url) && strpos($url, ':') === false) {
        return true;
    }
    return false;
}

function pd_sanitize_nav_svg($svg) {
    $svg = trim((string)$svg);
    if ($svg === '' || stripos($svg, '<svg') === false) {
        return '';
    }
    // Admin-only input, but strip the obviously dangerous bits.
    $svg = preg_replace('#<script[\s\S]*?</script>#i', '', $svg);
    $svg = preg_replace('#\son[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)#i', '', $svg);
    return $svg;
}

function pd_delete_attachment_file($path) {
    if ($path === '' || preg_match('/^https?:\/\//i', $path)) {
        return true;
    }
    $base_dir = realpath(PD_ROOT . '/uploads');
    $file = realpath(PD_ROOT . '/' . ltrim($path, '/'));
    if (!$base_dir || !$file || strpos($file, $base_dir . DIRECTORY_SEPARATOR) !== 0) {
        return false;
    }
    if (is_file($file)) {
        return unlink($file);
    }
    return true;
}

function pd_notify_user($user_id, $thread_id, $post_id, $message) {
    $user_id = intval($user_id);
    $thread_id = intval($thread_id);
    $post_id = intval($post_id);
    if ($user_id < 1 || !pd_notifications_ready()) {
        return false;
    }
    $message_sql = esc(clean_text($message, 180));
    return mysqli_query(db(), "INSERT INTO pd_notifications (user_id,thread_id,post_id,message,is_read,created_at) VALUES ({$user_id},{$thread_id},{$post_id},'{$message_sql}',0,NOW())");
}

function pd_floor_name($floor) {
    $floor = intval($floor);
    if ($floor === 1) return '沙发';
    if ($floor === 2) return '椅子';
    if ($floor === 3) return '板凳';
    return $floor . '楼';
}

function pd_floor_icon($floor) {
    $floor = intval($floor);
    if ($floor === 1) return '🛋';
    if ($floor === 2) return '🪑';
    if ($floor === 3) return '▰';
    return '';
}

function pd_guest_download_allowed() {
    return intval(pd_setting('guest_download_enabled', '0')) === 1;
}

function pd_replies_per_page() {
    return pd_setting_int('replies_per_page', 20, 5, 100);
}

function pd_friend_links_enabled() {
    return intval(pd_setting('friend_links_enabled', '0')) === 1;
}

function pd_friend_links() {
    $raw = pd_setting('friend_links', '');
    $lines = preg_split('/\r\n|\r|\n/', $raw);
    $items = array();
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        $parts = explode('|', $line, 2);
        if (count($parts) !== 2) {
            continue;
        }
        $name = clean_text($parts[0], 60);
        $url = trim($parts[1]);
        if ($name !== '' && preg_match('/^https?:\/\//i', $url)) {
            $items[] = array('name' => $name, 'url' => $url);
        }
    }
    return $items;
}

function pd_prepare_form_guard() {
    if (empty($_SESSION['pd_form_started_at'])) {
        $_SESSION['pd_form_started_at'] = time();
    }
    if (empty($_SESSION['pd_hp_field'])) {
        $_SESSION['pd_hp_field'] = 'website_' . mt_rand(1000, 9999);
    }
}

function pd_verify_captcha() {
    pd_prepare_form_guard();
    $hp = $_SESSION['pd_hp_field'];
    if (!empty($_POST[$hp])) {
        return false;
    }
    if (empty($_SESSION['pd_form_started_at']) || time() - intval($_SESSION['pd_form_started_at']) < 2) {
        return false;
    }
    $input = strtoupper(trim((string)(isset($_POST['captcha_code']) ? $_POST['captcha_code'] : '')));
    $answer = strtoupper((string)(isset($_SESSION['pd_captcha_answer']) ? $_SESSION['pd_captcha_answer'] : ''));
    unset($_SESSION['pd_captcha_answer']);
    unset($_SESSION['pd_form_started_at']);
    unset($_SESSION['pd_hp_field']);
    return $input !== '' && $answer !== '' && hash_equals($answer, $input);
}

function pd_remote_upload_file($tmp_name, $safe_name, $content_type, &$error) {
    if (pd_s3_enabled()) {
        return pd_s3_upload_file($tmp_name, pd_s3_key($safe_name), $content_type, $error);
    }
    return '';
}

function is_admin() {
    $u = current_user();
    return $u && intval($u['is_admin']) === 1;
}

function is_moderator_user($user = null) {
    if ($user === null) {
        $user = current_user();
    }
    return $user && (intval($user['is_admin']) === 1 || intval(isset($user['is_moderator']) ? $user['is_moderator'] : 0) === 1);
}

function pd_b64url_encode($data) {
    return rtrim(strtr(base64_encode((string)$data), '+/', '-_'), '=');
}

function pd_b64url_decode($data) {
    $data = strtr((string)$data, '-_', '+/');
    $pad = strlen($data) % 4;
    if ($pad) {
        $data .= str_repeat('=', 4 - $pad);
    }
    return base64_decode($data, true);
}

function pd_json_response($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function pd_json_input() {
    $raw = file_get_contents('php://input');
    $json = json_decode($raw ? $raw : '{}', true);
    return is_array($json) ? $json : array();
}

function pd_cbor_decode($data) {
    $reader = new QfCborReader((string)$data);
    return $reader->read();
}

function require_login() {
    $u = current_user();
    if (!$u) {
        header('Location: ' . pd_url_page('login.php'));
        exit;
    }
    return $u;
}

function pd_generate_invite_code() {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $max = strlen($chars) - 1;
    $code = '';
    for ($i = 0; $i < 10; $i++) {
        $code .= $chars[random_int(0, $max)];
    }
    return $code;
}

function pd_consume_invite($code, $user_id) {
    if (!pd_invite_table_ready()) {
        return false;
    }
    $code_sql = esc(trim((string)$code));
    $uid = intval($user_id);
    mysqli_query(db(), "UPDATE pd_invites SET used_by={$uid}, used_at=NOW() WHERE code='{$code_sql}' AND used_by=0 AND (expires_at IS NULL OR expires_at > NOW())");
    return mysqli_affected_rows(db()) > 0;
}

function pd_http_request($method, $url, $data = null, $headers = array()) {
    $method = strtoupper($method);
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($data) ? http_build_query($data) : $data);
        }
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        $body = curl_exec($ch);
        $code = intval(curl_getinfo($ch, CURLINFO_HTTP_CODE));
        curl_close($ch);
        return array('code' => $code, 'body' => $body === false ? '' : $body);
    }
    $opts = array('http' => array('method' => $method, 'timeout' => 15, 'ignore_errors' => true));
    if (!empty($headers)) {
        $opts['http']['header'] = implode("\r\n", $headers);
    }
    if ($data !== null) {
        $opts['http']['content'] = is_array($data) ? http_build_query($data) : $data;
    }
    $body = @file_get_contents($url, false, stream_context_create($opts));
    return array('code' => 200, 'body' => $body === false ? '' : $body);
}

function require_admin() {
    if (!is_admin()) {
        header('Location: ' . pd_url_page('index.php'));
        exit;
    }
}

function client_ip() {
    $remote = isset($_SERVER['REMOTE_ADDR']) ? trim((string)$_SERVER['REMOTE_ADDR']) : '';
    $candidates = array();
    // Cloudflare / 反代：优先取真实访客 IP（仅当直连地址为私网或本机时采纳，防伪造）
    if (pd_ip_is_private_or_local($remote)) {
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            $candidates[] = trim((string)$_SERVER['HTTP_CF_CONNECTING_IP']);
        }
        if (!empty($_SERVER['HTTP_TRUE_CLIENT_IP'])) {
            $candidates[] = trim((string)$_SERVER['HTTP_TRUE_CLIENT_IP']);
        }
        if (!empty($_SERVER['HTTP_X_REAL_IP'])) {
            $candidates[] = trim((string)$_SERVER['HTTP_X_REAL_IP']);
        }
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            foreach (explode(',', (string)$_SERVER['HTTP_X_FORWARDED_FOR']) as $part) {
                $candidates[] = trim($part);
            }
        }
    }
    foreach ($candidates as $ip) {
        if ($ip !== '' && filter_var($ip, FILTER_VALIDATE_IP) && !pd_ip_is_private_or_local($ip)) {
            return $ip;
        }
    }
    return $remote;
}

function ip_banned($ip) {
    $ip = esc($ip);
    $rs = mysqli_query(db(), "SELECT id FROM pd_bans WHERE ip='{$ip}' AND (expires_at IS NULL OR expires_at > NOW()) LIMIT 1");
    return $rs && mysqli_num_rows($rs) > 0;
}

function pd_log_moderator_delete($moderator_id, $target_type, $target_id) {
    if (!pd_moderator_logs_ready()) {
        return;
    }
    $moderator_id = intval($moderator_id);
    $target_type = esc($target_type);
    $target_id = intval($target_id);
    mysqli_query(db(), "INSERT INTO pd_moderator_logs (moderator_id,target_type,target_id,created_at) VALUES ({$moderator_id},'{$target_type}',{$target_id},NOW())");
}

function pd_can_moderator_delete_thread($moderator, $thread) {
    if (!$moderator || !$thread || intval(isset($moderator['is_moderator']) ? $moderator['is_moderator'] : 0) !== 1 || intval($moderator['is_admin']) === 1) {
        return false;
    }
    if (intval(isset($thread['author_is_admin']) ? $thread['author_is_admin'] : 0) === 1) {
        return false;
    }
    if (!pd_moderator_assigned_to_forum(intval($moderator['id']), intval($thread['forum_id']))) {
        return false;
    }
    return pd_moderator_delete_allowed($moderator);
}

function pd_can_moderator_delete_post($moderator, $post) {
    if (!$moderator || !$post || intval(isset($moderator['is_moderator']) ? $moderator['is_moderator'] : 0) !== 1 || intval($moderator['is_admin']) === 1) {
        return false;
    }
    if (intval(isset($post['author_is_admin']) ? $post['author_is_admin'] : 0) === 1) {
        return false;
    }
    if (!pd_moderator_assigned_to_forum(intval($moderator['id']), intval($post['forum_id']))) {
        return false;
    }
    return pd_moderator_delete_allowed($moderator);
}

function pd_timezone_choices() {
    return array(
        '' => '跟随浏览器（自动）',
        'Asia/Shanghai' => '中国（北京时间 UTC+8）',
        'Asia/Hong_Kong' => '香港',
        'Asia/Taipei' => '台北',
        'Asia/Tokyo' => '日本（东京）',
        'Asia/Singapore' => '新加坡',
        'Europe/London' => '英国（伦敦）',
        'Europe/Paris' => '中欧',
        'America/New_York' => '美国东部',
        'America/Los_Angeles' => '美国太平洋',
        'UTC' => 'UTC（协调世界时）',
    );
}

function pd_valid_timezone($timezone) {
    $timezone = trim((string)$timezone);
    if ($timezone === '') {
        return true;
    }
    try {
        new DateTimeZone($timezone);
        return true;
    } catch (Exception $e) {
        return false;
    }
}

function pd_parse_utc_timestamp($dt) {
    $dt = trim((string)$dt);
    if ($dt === '' || strpos($dt, '0000-00-00') === 0) {
        return false;
    }
    try {
        return (new DateTimeImmutable($dt, new DateTimeZone('UTC')))->getTimestamp();
    } catch (Exception $e) {
        return false;
    }
}

function pd_iso8601($dt) {
    $ts = pd_parse_utc_timestamp($dt);
    if ($ts === false) {
        return '';
    }
    return gmdate('Y-m-d\TH:i:s\Z', $ts);
}

function pd_time_ago($dt) {
    $ts = pd_parse_utc_timestamp($dt);
    if ($ts === false) {
        return '';
    }
    $diff = time() - $ts;
    if ($diff < 0) {
        $diff = 0;
    }
    if ($diff < 60) return '刚刚';
    if ($diff < 3600) return floor($diff / 60) . ' 分钟前';
    if ($diff < 86400) return floor($diff / 3600) . ' 小时前';
    if ($diff < 2592000) return floor($diff / 86400) . ' 天前';
    if ($diff < 31536000) return floor($diff / 2592000) . ' 个月前';
    return floor($diff / 31536000) . ' 年前';
}

function pd_format_absolute($dt, $timezone = null) {
    $dt = trim((string)$dt);
    if ($dt === '' || strpos($dt, '0000-00-00') === 0) {
        return '';
    }
    try {
        $utc = new DateTimeImmutable($dt, new DateTimeZone('UTC'));
        if ($timezone === null) {
            $timezone = pd_user_timezone();
        }
        if ($timezone === '') {
            return $utc->format('Y-m-d H:i') . ' UTC';
        }
        return $utc->setTimezone(new DateTimeZone($timezone))->format('Y-m-d H:i');
    } catch (Exception $e) {
        return '';
    }
}

function pd_time_html($dt, $attrs = array()) {
    $iso = pd_iso8601($dt);
    if ($iso === '') {
        return '';
    }
    $class = isset($attrs['class']) ? (string)$attrs['class'] : 'pd-time';
    $extra = '';
    foreach ($attrs as $key => $value) {
        if ($key === 'class') {
            continue;
        }
        $extra .= ' ' . h($key) . '="' . h((string)$value) . '"';
    }
    return '<time class="' . h($class) . '" datetime="' . h($iso) . '" title="' . h(pd_format_absolute($dt)) . '"' . $extra . '>' . h(pd_time_ago($dt)) . '</time>';
}

function format_time($time) {
    return pd_time_ago($time);
}

function pd_content_looks_like_bbcode($content) {
    return (bool)preg_match('/\[(?:b|img|url|size|font|file)\b/i', (string)$content);
}

function pd_markdown($text) {
    $text = (string)$text;
    if (trim($text) === '') {
        return '';
    }
    static $loaded = false;
    if (!$loaded) {
        $path = PD_ROOT . '/core/vendor/Parsedown.php';
        if (is_file($path)) {
            require_once $path;
        }
        $loaded = true;
    }
    if (!class_exists('Parsedown', false)) {
        return nl2br(h($text));
    }
    $parser = new Parsedown();
    if (method_exists($parser, 'setSafeMode')) {
        $parser->setSafeMode(true);
    }
    if (method_exists($parser, 'setBreaksEnabled')) {
        $parser->setBreaksEnabled(true);
    }
    if (method_exists($parser, 'setMarkupEscaped')) {
        $parser->setMarkupEscaped(true);
    }
    $html = $parser->text($text);
    // 附件/外链图片统一加 class，便于缩放与样式
    $html = preg_replace('/<img\b([^>]*?)>/i', '<img class="remote-img"$1>', $html);
    $html = preg_replace('/<a\b([^>]*?)>/i', '<a class="content-link"$1>', $html);
    return $html;
}

function pd_paginate_content($content, $page_chars, $page) {
    $plain_len = function_exists('mb_strlen') ? mb_strlen($content, 'UTF-8') : strlen($content);
    $page_chars = intval($page_chars);
    if ($plain_len <= $page_chars) {
        return array('content' => $content, 'page' => 1, 'total' => 1);
    }
    $total = intval(ceil($plain_len / $page_chars));
    $page = intval($page);
    if ($page < 1) {
        $page = 1;
    }
    if ($page > $total) {
        $page = $total;
    }
    $start = ($page - 1) * $page_chars;
    $slice = function_exists('mb_substr') ? mb_substr($content, $start, $page_chars, 'UTF-8') : substr($content, $start, $page_chars);
    return array('content' => $slice, 'page' => $page, 'total' => $total);
}

function count_rows($sql) {
    $rs = mysqli_query(db(), $sql);
    if (!$rs) return 0;
    $row = mysqli_fetch_row($rs);
    return intval($row[0]);
}

function redirect($url) {
    header('Location: ' . $url);
    exit;
}
