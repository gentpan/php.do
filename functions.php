<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/compat.php';
if (PHP_SAPI !== 'cli') {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params(array(
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $https,
        'httponly' => true,
        'samesite' => 'Lax'
    ));
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('X-XSS-Protection: 0');
}
session_start();

function db() {
    static $conn = null;
    if ($conn) {
        return $conn;
    }
    $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if (!$conn) {
        header('Content-Type: text/html; charset=utf-8');
        exit('数据库连接失败：' . mysqli_connect_error());
    }
    mysqli_set_charset($conn, DB_CHARSET);
    return $conn;
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

function qf_password_hash($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

function qf_password_verify($password, $hash) {
    return password_verify($password, $hash);
}

function qf_setting($key, $default = '') {
    static $cache = null;
    if ($key === null) {
        $cache = null;
        return $default;
    }
    if ($cache === null) {
        $cache = array();
        $table = mysqli_query(db(), "SHOW TABLES LIKE 'qf_settings'");
        if ($table && mysqli_num_rows($table) > 0) {
            $rs = mysqli_query(db(), "SELECT setting_key, setting_value FROM qf_settings");
            while ($rs && $row = mysqli_fetch_assoc($rs)) {
                $cache[$row['setting_key']] = $row['setting_value'];
            }
        }
    }
    return isset($cache[$key]) ? $cache[$key] : $default;
}

function qf_update_setting($key, $value) {
    $key_sql = esc($key);
    $value_sql = esc($value);
    $ok = mysqli_query(db(), "REPLACE INTO qf_settings (setting_key, setting_value) VALUES ('{$key_sql}', '{$value_sql}')");
    if ($ok) {
        qf_setting(null);
    }
    return $ok;
}

function qf_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function qf_csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . h(qf_csrf_token()) . '">';
}

function qf_verify_csrf() {
    $sent = isset($_POST['csrf_token']) ? (string)$_POST['csrf_token'] : '';
    return $sent !== '' && hash_equals(qf_csrf_token(), $sent);
}

function qf_require_csrf() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !qf_verify_csrf()) {
        header('Content-Type: text/html; charset=utf-8', true, 403);
        exit('请求已过期或来源不正确，请返回上一页刷新后重试。');
    }
}

function qf_action_token($action, $id, $extra = '') {
    return hash_hmac('sha256', $action . '|' . intval($id) . '|' . $extra, qf_csrf_token());
}

function qf_require_action_token($action, $id, $extra = '') {
    $sent = isset($_GET['token']) ? (string)$_GET['token'] : '';
    if ($sent === '' || !hash_equals(qf_action_token($action, $id, $extra), $sent)) {
        header('Content-Type: text/html; charset=utf-8', true, 403);
        exit('操作链接已过期或来源不正确，请返回上一页刷新后重试。');
    }
}

function qf_inject_csrf_fields($html) {
    if (stripos($html, '<form') === false || stripos($html, 'method="post"') === false) {
        return $html;
    }
    return preg_replace('/(<form\b[^>]*method=["\']post["\'][^>]*>)/i', '$1' . qf_csrf_field(), $html);
}

function qf_ensure_upload_protection() {
    $dir = __DIR__ . '/uploads';
    if (!is_dir($dir)) {
        return;
    }
    $htaccess = $dir . '/.htaccess';
    $rules = "Options -Indexes\nphp_flag engine off\nRemoveHandler .php .phtml .php3 .php4 .php5 .php7 .phar\n<FilesMatch \"\\.(php|phtml|php3|php4|php5|php7|phar)$\">\nRequire all denied\n</FilesMatch>\n<FilesMatch \"\\.(zip|rar)$\">\nRequire all denied\n</FilesMatch>\n";
    $current = file_exists($htaccess) ? (string)file_get_contents($htaccess) : '';
    if ($current === '' || strpos($current, 'RemoveHandler .php') === false || strpos($current, 'zip|rar') === false) {
        @file_put_contents($htaccess, $rules);
    }
    $index = $dir . '/index.html';
    if (!file_exists($index)) {
        @file_put_contents($index, '');
    }
}

function qf_site_name() {
    return qf_setting('site_name', SITE_NAME);
}

function qf_site_desc() {
    return qf_setting('site_desc', SITE_DESC);
}

function qf_site_keywords() {
    return qf_setting('site_keywords', '');
}

function qf_theme_options() {
    return array(
        'light-blue' => '白色蓝色',
        'litenote' => 'LiteNote 淡黄',
        'dark' => '黑色款',
    );
}

function qf_theme() {
    $theme = qf_setting('theme_name', 'light-blue');
    $options = qf_theme_options();
    return isset($options[$theme]) ? $theme : 'light-blue';
}

function qf_font_options() {
    return array(
        'system' => array(
            'label' => '系统默认',
            'family' => '"Microsoft YaHei", "PingFang SC", Arial, sans-serif',
            'url' => '',
        ),
        'tencentsans' => array(
            'label' => '腾讯体',
            'family' => '"TencentSans W7", "Microsoft YaHei", "PingFang SC", sans-serif',
            'url' => 'https://static.bluecdn.com/fonts/tencentsans.css',
        ),
        'kuaikanshijieti' => array(
            'label' => '快看世界体',
            'family' => '"快看世界体", "Microsoft YaHei", "PingFang SC", sans-serif',
            'url' => 'https://static.bluecdn.com/fonts/kuaikanshijieti.css',
        ),
        'luo' => array(
            'label' => 'Luo',
            'family' => '"Luo", "Microsoft YaHei", "PingFang SC", sans-serif',
            'url' => 'https://static.bluecdn.com/fonts/luo.css',
        ),
        'source-han-serif-cn' => array(
            'label' => '思源宋体',
            'family' => '"Source Han Serif CN", "Noto Serif CJK SC", serif',
            'url' => 'https://static.bluecdn.com/fonts/source-han-serif-cn.css',
        ),
        'lxgw-wenkai' => array(
            'label' => '霞鹜文楷',
            'family' => '"LXGW WenKai", "Microsoft YaHei", "PingFang SC", sans-serif',
            'url' => 'https://static.bluecdn.com/fonts/lxgw-wenkai.css',
        ),
    );
}

function qf_font_key($setting_key, $default = 'system') {
    $options = qf_font_options();
    $font_key = qf_setting($setting_key, $default);
    if (!isset($options[$font_key])) {
        $font_key = isset($options[$default]) ? $default : 'system';
    }
    return $font_key;
}

function qf_font_family($setting_key, $default = 'system') {
    $options = qf_font_options();
    $font_key = qf_font_key($setting_key, $default);
    return $options[$font_key]['family'];
}

function qf_selected_font_urls() {
    $options = qf_font_options();
    $urls = array();
    foreach (array('title_font', 'content_font') as $setting_key) {
        $font_key = qf_font_key($setting_key);
        $url = isset($options[$font_key]['url']) ? $options[$font_key]['url'] : '';
        if ($url !== '') {
            $urls[$url] = $url;
        }
    }
    return array_values($urls);
}

function qf_default_nginx_rewrite_rules() {
    return "rewrite ^/thread/([0-9]+)\\.html$ /pages/thread.php?id=$1 last;\n"
        . "rewrite ^/forum/([0-9]+)\\.html$ /pages/forum.php?id=$1 last;\n"
        . "rewrite ^/download/([0-9]+)$ /pages/download.php?id=$1 last;\n"
        . "try_files $uri $uri.php $uri/ /index.php?$query_string;";
}

function qf_base_href() {
    $script_name = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';
    $dir = str_replace('\\', '/', dirname($script_name));
    if (in_array(basename($dir), array('admin', 'api', 'pages'), true)) {
        $dir = dirname($dir);
    }
    if ($dir === '/' || $dir === '\\' || $dir === '.' || $dir === '') {
        return '/';
    }
    return rtrim($dir, '/') . '/';
}

function qf_theme_file($file) {
    return __DIR__ . '/themes/' . ltrim($file, '/');
}

function qf_include_header() {
    include qf_theme_file('header.php');
}

function qf_include_footer() {
    include qf_theme_file('footer.php');
}

function qf_asset_js($name) {
    $name = trim((string)$name, '/');
    $source = 'assets/js/' . $name . '.js';
    $min = 'assets/js/' . $name . '.min.js';
    $host = strtolower(isset($_SERVER['HTTP_HOST']) ? preg_replace('/:.*/', '', $_SERVER['HTTP_HOST']) : '');
    $local_hosts = array('', 'localhost', '127.0.0.1', '::1', 'lume.test');
    $path = (!in_array($host, $local_hosts, true) && file_exists(__DIR__ . '/' . $min)) ? $min : $source;
    $file = __DIR__ . '/' . $path;
    $version = file_exists($file) ? filemtime($file) : time();
    return $path . '?v=' . $version;
}

function qf_rewrite_enabled() {
    return intval(qf_setting('rewrite_enabled', '0')) === 1;
}

function qf_append_url_parts($path, $params = array(), $fragment = '') {
    $query = '';
    if (!empty($params)) {
        $query = http_build_query($params);
    }
    return $path . ($query !== '' ? '?' . $query : '') . ($fragment !== '' ? '#' . ltrim($fragment, '#') : '');
}

function qf_route_script($script, &$params = array()) {
    $map = array(
        'ad.php' => 'api/ad.php',
        'captcha.php' => 'api/captcha.php',
        'ajax_upload_image.php' => 'api/upload-image.php',
        'ajax_upload_attachment.php' => 'api/upload-attachment.php',
        'delete_attachment.php' => 'api/delete-attachment.php',
        'floor_reply.php' => 'api/floor-reply.php',
        'moderator_action.php' => 'api/moderator.php',
        'reply.php' => 'api/reply.php',
        'signin.php' => 'api/signin.php',
        'login.php' => 'api/auth.php',
        'logout.php' => 'api/auth.php',
        'register.php' => 'api/auth.php',
        'download.php' => 'pages/download.php',
        'edit_thread.php' => 'pages/edit-thread.php',
        'forum.php' => 'pages/forum.php',
        'move_thread.php' => 'pages/move-thread.php',
        'notifications.php' => 'pages/notifications.php',
        'post.php' => 'pages/post.php',
        'profile.php' => 'pages/profile.php',
        'rankings.php' => 'pages/rankings.php',
        'search.php' => 'pages/search.php',
        'thread.php' => 'pages/thread.php',
    );
    if ($script === 'login.php' && !isset($params['action'])) {
        $params['action'] = 'login';
    } elseif ($script === 'logout.php' && !isset($params['action'])) {
        $params['action'] = 'logout';
    } elseif ($script === 'register.php' && !isset($params['action'])) {
        $params['action'] = 'register';
    }
    return isset($map[$script]) ? $map[$script] : $script;
}

function qf_clean_route_path($script) {
    $map = array(
        'pages/download.php' => 'download',
        'pages/edit-thread.php' => 'edit-thread',
        'pages/forum.php' => 'forum',
        'pages/move-thread.php' => 'move-thread',
        'pages/notifications.php' => 'notifications',
        'pages/post.php' => 'post',
        'pages/profile.php' => 'profile',
        'pages/rankings.php' => 'rankings',
        'pages/search.php' => 'search',
        'pages/thread.php' => 'thread',
        'api/ad.php' => 'api/ad',
        'api/captcha.php' => 'api/captcha',
        'api/upload-attachment.php' => 'api/upload-attachment',
        'api/upload-image.php' => 'api/upload-image',
        'api/auth.php' => 'api/auth',
        'api/delete-attachment.php' => 'api/delete-attachment',
        'api/floor-reply.php' => 'api/floor-reply',
        'api/moderator.php' => 'api/moderator',
        'api/reply.php' => 'api/reply',
        'api/signin.php' => 'api/signin',
    );
    return isset($map[$script]) ? $map[$script] : $script;
}

function qf_url_page($script, $params = array(), $fragment = '') {
    $script = ltrim((string)$script, '/');
    $params = is_array($params) ? $params : array();
    $logical_script = $script;
    $script = qf_route_script($script, $params);
    if (!qf_rewrite_enabled()) {
        return qf_append_url_parts($script, $params, $fragment);
    }
    if ($script === 'index.php' || $script === '') {
        return qf_append_url_parts('/', $params, $fragment);
    }
    if (($logical_script === 'thread.php' || $script === 'pages/thread.php') && isset($params['id'])) {
        $id = intval($params['id']);
        unset($params['id']);
        return qf_append_url_parts('/thread/' . $id . '.html', $params, $fragment);
    }
    if (($logical_script === 'forum.php' || $script === 'pages/forum.php') && isset($params['id'])) {
        $id = intval($params['id']);
        unset($params['id']);
        return qf_append_url_parts('/forum/' . $id . '.html', $params, $fragment);
    }
    if (($logical_script === 'download.php' || $script === 'pages/download.php') && isset($params['id'])) {
        $id = intval($params['id']);
        unset($params['id']);
        return qf_append_url_parts('/download/' . $id, $params, $fragment);
    }
    if ($script === 'admin/index.php') {
        return qf_append_url_parts('/admin/index', $params, $fragment);
    }
    $clean = qf_clean_route_path($script);
    if ($clean !== $script) {
        return qf_append_url_parts('/' . $clean, $params, $fragment);
    }
    if (substr($script, -4) === '.php') {
        $script = substr($script, 0, -4);
    }
    return qf_append_url_parts('/' . $script, $params, $fragment);
}

function qf_url_nav($url) {
    $url = trim((string)$url);
    if ($url === '' || preg_match('/^[a-z][a-z0-9+\-.]*:\/\//i', $url) || strpos($url, '//') === 0 || strpos($url, '#') === 0) {
        return $url;
    }
    $parts = parse_url($url);
    if (!$parts || !isset($parts['path'])) {
        return $url;
    }
    $path = ltrim($parts['path'], '/');
    if (substr($path, -4) !== '.php') {
        return $url;
    }
    $params = array();
    if (!empty($parts['query'])) {
        parse_str($parts['query'], $params);
    }
    $fragment = isset($parts['fragment']) ? $parts['fragment'] : '';
    return qf_url_page($path, $params, $fragment);
}

function qf_url_thread($id) {
    $id = intval($id);
    return qf_rewrite_enabled() ? '/thread/' . $id . '.html' : qf_url_page('thread.php', array('id' => $id));
}

function qf_url_forum($id) {
    $id = intval($id);
    return qf_rewrite_enabled() ? '/forum/' . $id . '.html' : qf_url_page('forum.php', array('id' => $id));
}

function qf_path_id() {
    if (isset($_GET['id'])) {
        return intval($_GET['id']);
    }
    $path = isset($_SERVER['PATH_INFO']) ? trim($_SERVER['PATH_INFO'], '/') : '';
    if ($path !== '' && preg_match('/^([0-9]+)/', $path, $m)) {
        return intval($m[1]);
    }
    $uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
    if (preg_match('/\/(?:thread|forum)\/([0-9]+)/', $uri, $m)) {
        return intval($m[1]);
    }
    return 0;
}

function qf_attachment_url($id) {
    return qf_url_page('download.php', array('id' => intval($id)));
}

function qf_protected_attachment_dir() {
    return __DIR__ . '/uploads/protected';
}

function qf_protected_attachment_path($ext = 'dat') {
    $dir = qf_protected_attachment_dir();
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

function qf_store_uploaded_attachment_file($tmp_name, $ext, &$file_path) {
    if (in_array(strtolower($ext), array('zip', 'rar'))) {
        list($target, $relative) = qf_protected_attachment_path($ext);
        if (!move_uploaded_file($tmp_name, $target)) {
            return false;
        }
        $file_path = $relative;
        return true;
    }
    $upload_dir = __DIR__ . '/uploads';
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

function qf_migrate_attachment_to_protected_storage($att) {
    if (!$att || !in_array(strtolower($att['file_ext']), array('zip', 'rar'))) {
        return $att;
    }
    $path = (string)$att['file_path'];
    if (strpos($path, 'uploads/protected/') === 0 || preg_match('/^https?:\/\//i', $path)) {
        return $att;
    }
    $base_dir = realpath(__DIR__ . '/uploads');
    $old_file = realpath(__DIR__ . '/' . ltrim($path, '/'));
    if (!$base_dir || !$old_file || strpos($old_file, $base_dir . DIRECTORY_SEPARATOR) !== 0 || !is_file($old_file)) {
        return $att;
    }
    list($target, $relative) = qf_protected_attachment_path($att['file_ext']);
    if (!rename($old_file, $target)) {
        if (!copy($old_file, $target)) {
            return $att;
        }
        @unlink($old_file);
    }
    $relative_sql = esc($relative);
    mysqli_query(db(), "UPDATE qf_attachments SET file_path='{$relative_sql}' WHERE id=" . intval($att['id']));
    $att['file_path'] = $relative;
    return $att;
}

function qf_resolve_attachment_from_url($url) {
    $raw_url = html_entity_decode((string)$url, ENT_QUOTES, 'UTF-8');
    if (preg_match('/download\.php\?id=([0-9]+)/i', $raw_url, $m)) {
        $aid = intval($m[1]);
        $rs = mysqli_query(db(), "SELECT * FROM qf_attachments WHERE id={$aid} LIMIT 1");
        return $rs ? qf_migrate_attachment_to_protected_storage(mysqli_fetch_assoc($rs)) : null;
    }
    if (preg_match('/^\/?uploads\/[^?#]+/i', $raw_url, $m)) {
        $path = ltrim($m[0], '/');
        $path_sql = esc($path);
        $rs = mysqli_query(db(), "SELECT * FROM qf_attachments WHERE file_path='{$path_sql}' LIMIT 1");
        if ($rs && ($att = mysqli_fetch_assoc($rs))) {
            return qf_migrate_attachment_to_protected_storage($att);
        }
        $full_path = realpath(__DIR__ . '/' . $path);
        $base_dir = realpath(__DIR__ . '/uploads');
        if ($base_dir && $full_path && strpos($full_path, $base_dir . DIRECTORY_SEPARATOR) === 0 && is_file($full_path)) {
            $original = basename($path);
            $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
            if (in_array($ext, array('zip', 'rar'))) {
                $original_sql = esc($original);
                $ext_sql = esc($ext);
                $size = intval(filesize($full_path));
                list($target, $relative) = qf_protected_attachment_path($ext);
                if (rename($full_path, $target) || (copy($full_path, $target) && @unlink($full_path))) {
                    $path_sql = esc($relative);
                }
                mysqli_query(db(), "INSERT INTO qf_attachments (thread_id,post_id,user_id,file_path,original_name,file_ext,file_size,created_at) VALUES (0,0,0,'{$path_sql}','{$original_sql}','{$ext_sql}',{$size},NOW())");
                $new_id = intval(mysqli_insert_id(db()));
                if ($new_id > 0) {
                    $new_rs = mysqli_query(db(), "SELECT * FROM qf_attachments WHERE id={$new_id} LIMIT 1");
                    return $new_rs ? mysqli_fetch_assoc($new_rs) : null;
                }
            }
        }
    }
    return null;
}

function qf_can_delete_attachment($att, $user = null) {
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

function qf_attachment_delete_form($att, $label = '删除附件') {
    if (!qf_can_delete_attachment($att)) {
        return '';
    }
    $redirect = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : qf_url_page('index.php');
    return '<form class="attachment-delete-form" method="post" action="' . h(qf_url_page('delete_attachment.php')) . '" data-confirm="确定删除这个附件？删除后服务器文件也会被删除。">'
        . qf_csrf_field()
        . '<input type="hidden" name="id" value="' . intval($att['id']) . '">'
        . '<input type="hidden" name="redirect" value="' . h($redirect) . '">'
        . '<button class="attachment-delete-btn" type="submit">' . h($label) . '</button>'
        . '</form>';
}

function qf_guest_download_confirm_onclick() {
    return 'data-login-required="1" data-login-url="' . h(qf_url_page('register.php')) . '"';
}

function qf_remove_attachment_tag_from_content($content, $attachment_id) {
    $needle = preg_quote(qf_attachment_url($attachment_id), '/');
    $content = preg_replace('/\s*\[file\s+url=(["\'])' . $needle . '\1\s+name=(["\']).*?\2\].*?\[\/file\]\s*/is', "\n", $content);
    return trim($content);
}

function qf_nav_table_ready() {
    $table = mysqli_query(db(), "SHOW TABLES LIKE 'qf_navs'");
    return $table && mysqli_num_rows($table) > 0;
}

function qf_valid_nav_url($url) {
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

function qf_nav_target($url) {
    return preg_match('/^https?:\/\//i', (string)$url) ? ' target="_blank" rel="nofollow noopener"' : '';
}

function qf_main_navs() {
    if (!qf_nav_table_ready()) {
        return array();
    }
    $rs = mysqli_query(db(), "SELECT * FROM qf_navs WHERE is_enabled=1 ORDER BY display_order ASC, id ASC");
    $rows = array();
    while ($rs && ($row = mysqli_fetch_assoc($rs))) {
        $rows[] = $row;
    }
    return $rows;
}

function qf_delete_attachment_file($path) {
    if ($path === '' || preg_match('/^https?:\/\//i', $path)) {
        return true;
    }
    $base_dir = realpath(__DIR__ . '/uploads');
    $file = realpath(__DIR__ . '/' . ltrim($path, '/'));
    if (!$base_dir || !$file || strpos($file, $base_dir . DIRECTORY_SEPARATOR) !== 0) {
        return false;
    }
    if (is_file($file)) {
        return unlink($file);
    }
    return true;
}

function qf_notifications_ready() {
    $table = mysqli_query(db(), "SHOW TABLES LIKE 'qf_notifications'");
    return $table && mysqli_num_rows($table) > 0;
}

function qf_post_comments_ready() {
    $table = mysqli_query(db(), "SHOW TABLES LIKE 'qf_post_comments'");
    return $table && mysqli_num_rows($table) > 0;
}

function qf_unread_notifications_count($user_id) {
    if (!$user_id || !qf_notifications_ready()) {
        return 0;
    }
    $user_id = intval($user_id);
    return count_rows("SELECT COUNT(*) FROM qf_notifications WHERE user_id={$user_id} AND is_read=0");
}

function qf_notify_user($user_id, $thread_id, $post_id, $message) {
    $user_id = intval($user_id);
    $thread_id = intval($thread_id);
    $post_id = intval($post_id);
    if ($user_id < 1 || !qf_notifications_ready()) {
        return false;
    }
    $message_sql = esc(clean_text($message, 180));
    return mysqli_query(db(), "INSERT INTO qf_notifications (user_id,thread_id,post_id,message,is_read,created_at) VALUES ({$user_id},{$thread_id},{$post_id},'{$message_sql}',0,NOW())");
}

function qf_floor_name($floor) {
    $floor = intval($floor);
    if ($floor === 1) return '沙发';
    if ($floor === 2) return '椅子';
    if ($floor === 3) return '板凳';
    return $floor . '楼';
}

function qf_floor_icon($floor) {
    $floor = intval($floor);
    if ($floor === 1) return '🛋';
    if ($floor === 2) return '🪑';
    if ($floor === 3) return '▰';
    return '';
}

function qf_notification_sound_enabled($user) {
    if (!$user) {
        return false;
    }
    return intval(isset($user['notification_sound_enabled']) ? $user['notification_sound_enabled'] : 1) === 1;
}

function qf_guest_download_allowed() {
    return intval(qf_setting('guest_download_enabled', '0')) === 1;
}

function qf_home_threads_limit() {
    $limit = intval(qf_setting('home_threads_per_page', '12'));
    if ($limit < 1) {
        $limit = 12;
    }
    if ($limit > 100) {
        $limit = 100;
    }
    return $limit;
}

function qf_forum_threads_limit() {
    $limit = intval(qf_setting('forum_threads_per_page', '60'));
    if ($limit < 1) {
        $limit = 60;
    }
    if ($limit > 200) {
        $limit = 200;
    }
    return $limit;
}

function qf_signin_base_coins() {
    $coins = intval(qf_setting('signin_base_coins', '5'));
    if ($coins < 0) {
        $coins = 0;
    }
    if ($coins > 100000) {
        $coins = 100000;
    }
    return $coins;
}

function qf_signin_streak_bonus() {
    $coins = intval(qf_setting('signin_streak_bonus', '2'));
    if ($coins < 0) {
        $coins = 0;
    }
    if ($coins > 100000) {
        $coins = 100000;
    }
    return $coins;
}

function qf_moderator_daily_delete_limit() {
    $limit = 20;
    if ($limit < 0) {
        $limit = 0;
    }
    if ($limit > 10000) {
        $limit = 10000;
    }
    return $limit;
}

function qf_thread_page_chars() {
    $limit = intval(qf_setting('thread_page_chars', '4000'));
    if ($limit < 500) {
        $limit = 500;
    }
    if ($limit > 50000) {
        $limit = 50000;
    }
    return $limit;
}

function qf_reply_max_chars() {
    $limit = intval(qf_setting('reply_max_chars', '1000'));
    if ($limit < 100) {
        $limit = 100;
    }
    if ($limit > 50000) {
        $limit = 50000;
    }
    return $limit;
}

function qf_friend_links_enabled() {
    return intval(qf_setting('friend_links_enabled', '0')) === 1;
}

function qf_friend_links() {
    $raw = qf_setting('friend_links', '');
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

function qf_captcha_enabled() {
    return intval(qf_setting('captcha_enabled', '1')) === 1;
}

function qf_captcha_free_count() {
    $count = intval(qf_setting('captcha_reply_free_count', '10'));
    if ($count < 0) {
        $count = 0;
    }
    if ($count > 10000) {
        $count = 10000;
    }
    return $count;
}

function qf_captcha_required($scene, $user = null) {
    if (!qf_captcha_enabled()) {
        return false;
    }
    if ($scene === 'register') {
        return true;
    }
    $free_count = qf_captcha_free_count();
    if ($user && intval($user['reply_count']) >= $free_count) {
        return false;
    }
    return true;
}

function qf_prepare_form_guard() {
    if (empty($_SESSION['qf_form_started_at'])) {
        $_SESSION['qf_form_started_at'] = time();
    }
    if (empty($_SESSION['qf_hp_field'])) {
        $_SESSION['qf_hp_field'] = 'website_' . mt_rand(1000, 9999);
    }
}

function qf_render_captcha() {
    qf_prepare_form_guard();
    $hp = $_SESSION['qf_hp_field'];
    return '<div class="hp-field"><label>网址</label><input type="text" name="' . h($hp) . '" value=""></div>'
        . '<div class="captcha-box"><label>验证码</label><div class="captcha-row">'
        . '<input type="text" name="captcha_code" maxlength="6" autocomplete="off" required placeholder="输入图片中的字符">'
        . '<img src="api/captcha?t=' . time() . '" alt="验证码" data-captcha-refresh title="点击刷新">'
        . '</div><p class="muted">看不清可点击图片刷新。</p></div>';
}

function qf_verify_captcha() {
    qf_prepare_form_guard();
    $hp = $_SESSION['qf_hp_field'];
    if (!empty($_POST[$hp])) {
        return false;
    }
    if (empty($_SESSION['qf_form_started_at']) || time() - intval($_SESSION['qf_form_started_at']) < 2) {
        return false;
    }
    $input = strtoupper(trim((string)(isset($_POST['captcha_code']) ? $_POST['captcha_code'] : '')));
    $answer = strtoupper((string)(isset($_SESSION['qf_captcha_answer']) ? $_SESSION['qf_captcha_answer'] : ''));
    unset($_SESSION['qf_captcha_answer']);
    unset($_SESSION['qf_form_started_at']);
    unset($_SESSION['qf_hp_field']);
    return $input !== '' && $answer !== '' && hash_equals($answer, $input);
}

function qf_forum_info($forum_id) {
    static $cache = array();
    $forum_id = intval($forum_id);
    if ($forum_id < 1) {
        return null;
    }
    if (!isset($cache[$forum_id])) {
        $rs = mysqli_query(db(), "SELECT * FROM qf_forums WHERE id={$forum_id} LIMIT 1");
        $cache[$forum_id] = $rs ? mysqli_fetch_assoc($rs) : null;
    }
    return $cache[$forum_id];
}

function qf_topic_category_enabled($forum_id) {
    $forum = qf_forum_info($forum_id);
    return $forum && intval($forum['topic_category_enabled']) === 1;
}

function qf_topic_categories($forum_id) {
    $forum = qf_forum_info($forum_id);
    $raw = $forum ? $forum['topic_categories'] : '';
    $parts = preg_split('/[\r\n,，、|]+/', $raw);
    $items = array();
    foreach ($parts as $item) {
        $item = clean_text($item, 40);
        if ($item !== '') {
            $items[] = $item;
        }
    }
    return array_values(array_unique($items));
}

function qf_forum_post_allowed($forum_id, $user_id) {
    $forum = qf_forum_info($forum_id);
    if (!$forum || intval($forum['post_user_limit_enabled']) !== 1) {
        return true;
    }
    $ids = preg_split('/[\s,，、|]+/', $forum['post_user_ids']);
    $allowed = array();
    foreach ($ids as $id) {
        $id = intval($id);
        if ($id > 0) {
            $allowed[] = $id;
        }
    }
    return in_array(intval($user_id), $allowed);
}

function qf_upload_max_mb() {
    $max_mb = intval(qf_setting('upload_max_mb', '5'));
    if ($max_mb < 1) {
        $max_mb = 5;
    }
    if ($max_mb > 50) {
        $max_mb = 50;
    }
    return $max_mb;
}

function qf_upload_allowed_exts() {
    $raw = strtolower(qf_setting('upload_allowed_exts', 'jpg,jpeg,png,gif,webp,zip,rar'));
    $parts = preg_split('/[\s,，|]+/', $raw);
    $exts = array();
    foreach ($parts as $ext) {
        $ext = trim($ext);
        $ext = ltrim($ext, '.');
        if ($ext !== '' && preg_match('/^[a-z0-9]+$/', $ext)) {
            $exts[] = $ext;
        }
    }
    $exts = array_values(array_unique($exts));
    if (empty($exts)) {
        $exts = array('jpg', 'jpeg', 'png', 'gif', 'webp', 'zip', 'rar');
    }
    return $exts;
}

function qf_upload_allowed_exts_label() {
    return implode('、', qf_upload_allowed_exts());
}

function qf_qiniu_enabled() {
    return intval(qf_setting('qiniu_enabled', '0')) === 1;
}

function qf_s3_enabled() {
    return intval(qf_setting('s3_enabled', '0')) === 1;
}

function qf_base64_urlsafe($data) {
    return str_replace(array('+', '/'), array('-', '_'), base64_encode($data));
}

function qf_qiniu_upload_token($key) {
    $access_key = qf_setting('qiniu_access_key', '');
    $secret_key = qf_setting('qiniu_secret_key', '');
    $bucket = qf_setting('qiniu_bucket', '');
    if ($access_key === '' || $secret_key === '' || $bucket === '') {
        return '';
    }
    $policy = array(
        'scope' => $bucket . ':' . $key,
        'deadline' => time() + 3600
    );
    $encoded_policy = qf_base64_urlsafe(json_encode($policy));
    $sign = hash_hmac('sha1', $encoded_policy, $secret_key, true);
    return $access_key . ':' . qf_base64_urlsafe($sign) . ':' . $encoded_policy;
}

function qf_qiniu_upload_file($tmp_name, $key, &$error) {
    if (!function_exists('curl_init') || !function_exists('curl_file_create')) {
        $error = '服务器未开启 PHP cURL，无法上传到七牛云。';
        return '';
    }
    $domain = rtrim(qf_setting('qiniu_domain', ''), '/');
    $upload_host = rtrim(qf_setting('qiniu_upload_host', 'https://upload.qiniup.com'), '/');
    if ($domain === '' || $upload_host === '') {
        $error = '七牛云配置不完整，请填写绑定域名和上传地址。';
        return '';
    }
    $token = qf_qiniu_upload_token($key);
    if ($token === '') {
        $error = '七牛云配置不完整，请填写 AccessKey、SecretKey 和 Bucket。';
        return '';
    }
    $ch = curl_init($upload_host);
    $post = array(
        'token' => $token,
        'key' => $key,
        'file' => curl_file_create($tmp_name)
    );
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $body = curl_exec($ch);
    $http_code = intval(curl_getinfo($ch, CURLINFO_HTTP_CODE));
    $curl_error = curl_error($ch);
    curl_close($ch);
    if ($body === false || $http_code < 200 || $http_code >= 300) {
        $error = '七牛云上传失败：' . ($curl_error !== '' ? $curl_error : $body);
        return '';
    }
    return $domain . '/' . ltrim($key, '/');
}

function qf_s3_setting($key, $default = '') {
    return trim((string)qf_setting($key, $default));
}

function qf_s3_key($safe_name) {
    $prefix = trim(qf_s3_setting('s3_path_prefix', 'lume'), "/ \t\n\r\0\x0B");
    $prefix = preg_replace('/[^a-zA-Z0-9_\-\/]/', '', $prefix);
    $date_path = date('Y/m/d');
    return ($prefix !== '' ? $prefix . '/' : '') . $date_path . '/' . ltrim($safe_name, '/');
}

function qf_s3_public_url($key) {
    $cdn = rtrim(qf_s3_setting('s3_cdn_domain', ''), '/');
    if ($cdn !== '') {
        return $cdn . '/' . ltrim($key, '/');
    }
    $endpoint = rtrim(qf_s3_setting('s3_endpoint', ''), '/');
    $bucket = qf_s3_setting('s3_bucket', '');
    return $endpoint . '/' . rawurlencode($bucket) . '/' . str_replace('%2F', '/', rawurlencode(ltrim($key, '/')));
}

function qf_s3_upload_bytes($body, $key, $content_type, &$error) {
    if (!function_exists('curl_init')) {
        $error = '服务器未开启 PHP cURL，无法上传到 S3/R2。';
        return '';
    }
    $endpoint = rtrim(qf_s3_setting('s3_endpoint', ''), '/');
    $bucket = qf_s3_setting('s3_bucket', '');
    $region = qf_s3_setting('s3_region', 'auto');
    $access_key = qf_s3_setting('s3_access_key', '');
    $secret_key = qf_s3_setting('s3_secret_key', '');
    if ($endpoint === '' || $bucket === '' || $region === '' || $access_key === '' || $secret_key === '') {
        $error = 'S3/R2 配置不完整，请填写 Endpoint、Bucket、Region、Access Key 和 Secret Key。';
        return '';
    }
    if (!preg_match('/^https?:\/\//i', $endpoint)) {
        $error = 'S3/R2 Endpoint 必须以 http:// 或 https:// 开头。';
        return '';
    }
    $url = $endpoint . '/' . rawurlencode($bucket) . '/' . str_replace('%2F', '/', rawurlencode(ltrim($key, '/')));
    $url_parts = parse_url($url);
    if (empty($url_parts['host']) || empty($url_parts['path'])) {
        $error = 'S3/R2 Endpoint 无效。';
        return '';
    }
    $host = $url_parts['host'];
    if (!empty($url_parts['port'])) {
        $host .= ':' . $url_parts['port'];
    }
    $canonical_uri = $url_parts['path'];
    $amz_date = gmdate('Ymd\THis\Z');
    $date_stamp = gmdate('Ymd');
    $payload_hash = hash('sha256', $body);
    $service = 's3';
    $credential_scope = $date_stamp . '/' . $region . '/' . $service . '/aws4_request';
    $canonical_headers = 'content-type:' . $content_type . "\n"
        . 'host:' . $host . "\n"
        . 'x-amz-content-sha256:' . $payload_hash . "\n"
        . 'x-amz-date:' . $amz_date . "\n";
    $signed_headers = 'content-type;host;x-amz-content-sha256;x-amz-date';
    $canonical_request = "PUT\n" . $canonical_uri . "\n\n" . $canonical_headers . "\n" . $signed_headers . "\n" . $payload_hash;
    $string_to_sign = "AWS4-HMAC-SHA256\n" . $amz_date . "\n" . $credential_scope . "\n" . hash('sha256', $canonical_request);
    $k_date = hash_hmac('sha256', $date_stamp, 'AWS4' . $secret_key, true);
    $k_region = hash_hmac('sha256', $region, $k_date, true);
    $k_service = hash_hmac('sha256', $service, $k_region, true);
    $k_signing = hash_hmac('sha256', 'aws4_request', $k_service, true);
    $signature = hash_hmac('sha256', $string_to_sign, $k_signing);
    $authorization = 'AWS4-HMAC-SHA256 Credential=' . $access_key . '/' . $credential_scope . ', SignedHeaders=' . $signed_headers . ', Signature=' . $signature;

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Authorization: ' . $authorization,
        'Content-Type: ' . $content_type,
        'Host: ' . $host,
        'X-Amz-Content-Sha256: ' . $payload_hash,
        'X-Amz-Date: ' . $amz_date
    ));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 45);
    $response = curl_exec($ch);
    $http_code = intval(curl_getinfo($ch, CURLINFO_HTTP_CODE));
    $curl_error = curl_error($ch);
    curl_close($ch);
    if ($response === false || $http_code < 200 || $http_code >= 300) {
        $error = 'S3/R2 上传失败：' . ($curl_error !== '' ? $curl_error : ('HTTP ' . $http_code . ' ' . $response));
        return '';
    }
    return qf_s3_public_url($key);
}

function qf_s3_upload_file($tmp_name, $key, $content_type, &$error) {
    $body = @file_get_contents($tmp_name);
    if ($body === false) {
        $error = '读取上传文件失败。';
        return '';
    }
    return qf_s3_upload_bytes($body, $key, $content_type, $error);
}

function qf_s3_test(&$message) {
    $key = qf_s3_key('test_' . date('YmdHis') . '_' . mt_rand(1000, 9999) . '.txt');
    $error = '';
    $url = qf_s3_upload_bytes("Lume S3/R2 test " . date('c') . "\n", $key, 'text/plain; charset=utf-8', $error);
    if ($url === '') {
        $message = $error;
        return false;
    }
    $message = 'S3/R2 测试上传成功：' . $url;
    return true;
}

function qf_remote_upload_file($tmp_name, $safe_name, $content_type, &$error) {
    if (qf_s3_enabled()) {
        return qf_s3_upload_file($tmp_name, qf_s3_key($safe_name), $content_type, $error);
    }
    if (qf_qiniu_enabled()) {
        return qf_qiniu_upload_file($tmp_name, 'lume/' . date('Y/m/d') . '/' . $safe_name, $error);
    }
    return '';
}

function qf_browser_title($page_title) {
    $site_title = qf_setting('site_title', SITE_NAME);
    if ($page_title === SITE_NAME) {
        return $site_title;
    }
    return str_replace(SITE_NAME, $site_title, $page_title);
}

function qf_render_ad($position) {
    $table = mysqli_query(db(), "SHOW TABLES LIKE 'qf_ads'");
    if (!$table || mysqli_num_rows($table) == 0) {
        return '';
    }
    $pos = esc($position);
    $rs = mysqli_query(db(), "SELECT * FROM qf_ads WHERE position='{$pos}' AND is_enabled=1 LIMIT 1");
    $ad = $rs ? mysqli_fetch_assoc($rs) : null;
    if (!$ad || $ad['image_path'] === '') {
        return '';
    }
    $style = '';
    if ($ad['width'] !== '') {
        $style .= 'width:' . h($ad['width']) . ';';
    }
    if ($ad['height'] !== '') {
        $style .= 'height:' . h($ad['height']) . ';';
    }
    $img = '<img src="' . h($ad['image_path']) . '" alt="' . h($ad['title']) . '" style="' . $style . '">';
    if ($ad['link_url'] !== '') {
        $img = '<a href="' . h($ad['link_url']) . '" target="_blank" rel="noopener">' . $img . '</a>';
    }
    return '<div class="ad-box ad-' . h($position) . '">' . $img . '</div>';
}

function current_user() {
    if (empty($_SESSION['qf_uid'])) {
        return null;
    }
    $uid = intval($_SESSION['qf_uid']);
    $rs = mysqli_query(db(), "SELECT * FROM qf_users WHERE id={$uid} LIMIT 1");
    return $rs ? mysqli_fetch_assoc($rs) : null;
}

function qf_signin_table_ready() {
    $table = mysqli_query(db(), "SHOW TABLES LIKE 'qf_signins'");
    return $table && mysqli_num_rows($table) > 0;
}

function qf_user_coins_ready() {
    $column = mysqli_query(db(), "SHOW COLUMNS FROM qf_users LIKE 'coins'");
    return $column && mysqli_num_rows($column) > 0;
}

function qf_user_signed_today($user_id) {
    if (!$user_id || !qf_signin_table_ready()) {
        return false;
    }
    $user_id = intval($user_id);
    $rs = mysqli_query(db(), "SELECT id FROM qf_signins WHERE user_id={$user_id} AND signin_date=CURDATE() LIMIT 1");
    return $rs && mysqli_num_rows($rs) > 0;
}

function qf_signin_reward($user_id, &$message) {
    $user_id = intval($user_id);
    if ($user_id < 1) {
        $message = '请先登录后再签到。';
        return false;
    }
    if (!qf_signin_table_ready()) {
        $message = '签到表不存在，请先访问 install/upgrade.php 升级数据库。';
        return false;
    }
    if (!qf_user_coins_ready()) {
        $message = '金币字段不存在，请先访问 install/upgrade.php 升级数据库。';
        return false;
    }
    if (qf_user_signed_today($user_id)) {
        $message = '今天已经签到过了。';
        return false;
    }
    $continuous_days = 1;
    $yesterday = mysqli_query(db(), "SELECT continuous_days FROM qf_signins WHERE user_id={$user_id} AND signin_date=DATE_SUB(CURDATE(), INTERVAL 1 DAY) LIMIT 1");
    if ($yesterday && ($row = mysqli_fetch_assoc($yesterday))) {
        $continuous_days = intval($row['continuous_days']) + 1;
    }
    $reward = qf_signin_base_coins();
    if ($continuous_days > 1) {
        $reward += qf_signin_streak_bonus();
    }
    mysqli_query(db(), "INSERT INTO qf_signins (user_id, signin_date, continuous_days, reward_coins, created_at) VALUES ({$user_id}, CURDATE(), {$continuous_days}, {$reward}, NOW())");
    mysqli_query(db(), "UPDATE qf_users SET coins=coins+{$reward} WHERE id={$user_id}");
    $message = '签到成功，获得 ' . $reward . ' 金币，连续签到 ' . $continuous_days . ' 天。';
    return true;
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

function require_login() {
    $u = current_user();
    if (!$u) {
        header('Location: ' . qf_url_page('login.php'));
        exit;
    }
    return $u;
}

function qf_handle_login() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        redirect(qf_url_page('index.php', array('auth' => 'login')));
    }
    $username_raw = clean_text(isset($_POST['username']) ? $_POST['username'] : '', 30);
    $username = esc($username_raw);
    $password = (string)(isset($_POST['password']) ? $_POST['password'] : '');
    $rs = mysqli_query(db(), "SELECT * FROM qf_users WHERE username='{$username}' AND status=1 LIMIT 1");
    $u = $rs ? mysqli_fetch_assoc($rs) : null;
    if ($u && qf_password_verify($password, $u['password'])) {
        session_regenerate_id(true);
        $_SESSION['qf_uid'] = intval($u['id']);
        redirect(qf_url_page('index.php'));
    }
    $_SESSION['auth_modal'] = 'login';
    $_SESSION['auth_error'] = '用户名或密码错误。';
    $_SESSION['auth_login_username'] = $username_raw;
    redirect(qf_url_page('index.php'));
}

function qf_handle_register() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        redirect(qf_url_page('index.php', array('auth' => 'register')));
    }
    $username = clean_text(isset($_POST['username']) ? $_POST['username'] : '', 30);
    $nickname = clean_text(isset($_POST['nickname']) ? $_POST['nickname'] : '', 30);
    $password = (string)(isset($_POST['password']) ? $_POST['password'] : '');
    $error = '';
    if (qf_captcha_required('register') && !qf_verify_captcha()) {
        $error = '验证码错误，请重新输入。';
    } elseif (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
        $error = '用户名只能使用字母、数字、下划线，长度 3-30。';
    } elseif ($nickname === '' || strlen($password) < 6) {
        $error = '昵称不能为空，密码至少 6 位。';
    } else {
        $daily_limit = intval(qf_setting('register_ip_daily_limit', '5'));
        if ($daily_limit < 1) {
            $daily_limit = 5;
        }
        $ip_raw = client_ip();
        $ip_check = esc($ip_raw);
        $today_count = count_rows("SELECT COUNT(*) FROM qf_users WHERE ip='{$ip_check}' AND created_at >= CURDATE()");
        if ($today_count >= $daily_limit) {
            $error = '当前 IP 今天注册次数已达到上限。';
        } else {
            $u = esc($username);
            $n = esc($nickname);
            $p = qf_password_hash($password);
            $ip = esc($ip_raw);
            if (mysqli_query(db(), "INSERT INTO qf_users (username,password,nickname,ip,created_at) VALUES ('{$u}','{$p}','{$n}','{$ip}',NOW())")) {
                session_regenerate_id(true);
                $_SESSION['qf_uid'] = mysqli_insert_id(db());
                redirect(qf_url_page('index.php'));
            }
            $error = '注册失败，用户名可能已存在。';
        }
    }
    $_SESSION['auth_modal'] = 'register';
    $_SESSION['auth_error'] = $error;
    $_SESSION['auth_register_username'] = $username;
    $_SESSION['auth_register_nickname'] = $nickname;
    redirect(qf_url_page('index.php'));
}

function qf_handle_logout() {
    unset($_SESSION['qf_uid']);
    session_regenerate_id(true);
    redirect(qf_url_page('index.php'));
}

function qf_handle_auth_action() {
    $action = isset($_GET['action']) ? clean_text($_GET['action'], 20) : '';
    if ($action === '' && isset($_POST['action'])) {
        $action = clean_text($_POST['action'], 20);
    }
    if ($action === 'login') {
        qf_handle_login();
    } elseif ($action === 'register') {
        qf_handle_register();
    } elseif ($action === 'logout') {
        qf_handle_logout();
    }
    redirect(qf_url_page('index.php'));
}

function require_admin() {
    if (!is_admin()) {
        header('Location: ' . qf_url_page('index.php'));
        exit;
    }
}

function client_ip() {
    return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
}

function ip_banned($ip) {
    $ip = esc($ip);
    $rs = mysqli_query(db(), "SELECT id FROM qf_bans WHERE ip='{$ip}' AND (expires_at IS NULL OR expires_at > NOW()) LIMIT 1");
    return $rs && mysqli_num_rows($rs) > 0;
}

function qf_moderator_logs_ready() {
    $table = mysqli_query(db(), "SHOW TABLES LIKE 'qf_moderator_logs'");
    return $table && mysqli_num_rows($table) > 0;
}

function qf_moderator_forums_ready() {
    $table = mysqli_query(db(), "SHOW TABLES LIKE 'qf_moderator_forums'");
    return $table && mysqli_num_rows($table) > 0;
}

function qf_moderator_forum_ids($user_id) {
    if (!qf_moderator_forums_ready()) {
        return array();
    }
    $user_id = intval($user_id);
    $rs = mysqli_query(db(), "SELECT forum_id FROM qf_moderator_forums WHERE user_id={$user_id}");
    $ids = array();
    while ($rs && $row = mysqli_fetch_assoc($rs)) {
        $ids[] = intval($row['forum_id']);
    }
    return $ids;
}

function qf_moderator_assigned_to_forum($user_id, $forum_id) {
    if (!qf_moderator_forums_ready()) {
        return false;
    }
    $user_id = intval($user_id);
    $forum_id = intval($forum_id);
    $rs = mysqli_query(db(), "SELECT id FROM qf_moderator_forums WHERE user_id={$user_id} AND forum_id={$forum_id} LIMIT 1");
    return $rs && mysqli_num_rows($rs) > 0;
}

function qf_moderator_delete_count_today($moderator_id) {
    if (!qf_moderator_logs_ready()) {
        return 0;
    }
    $moderator_id = intval($moderator_id);
    return count_rows("SELECT COUNT(*) FROM qf_moderator_logs WHERE moderator_id={$moderator_id} AND created_at >= CURDATE()");
}

function qf_moderator_delete_limit_for_user($moderator) {
    $limit = intval(isset($moderator['moderator_delete_limit']) ? $moderator['moderator_delete_limit'] : 0);
    return $limit > 0 ? $limit : qf_moderator_daily_delete_limit();
}

function qf_moderator_delete_allowed($moderator) {
    $moderator_id = intval(is_array($moderator) ? $moderator['id'] : $moderator);
    $limit = is_array($moderator) ? qf_moderator_delete_limit_for_user($moderator) : qf_moderator_daily_delete_limit();
    return qf_moderator_delete_count_today($moderator_id) < $limit;
}

function qf_log_moderator_delete($moderator_id, $target_type, $target_id) {
    if (!qf_moderator_logs_ready()) {
        return;
    }
    $moderator_id = intval($moderator_id);
    $target_type = esc($target_type);
    $target_id = intval($target_id);
    mysqli_query(db(), "INSERT INTO qf_moderator_logs (moderator_id,target_type,target_id,created_at) VALUES ({$moderator_id},'{$target_type}',{$target_id},NOW())");
}

function qf_can_moderator_delete_thread($moderator, $thread) {
    if (!$moderator || !$thread || intval(isset($moderator['is_moderator']) ? $moderator['is_moderator'] : 0) !== 1 || intval($moderator['is_admin']) === 1) {
        return false;
    }
    if (intval(isset($thread['author_is_admin']) ? $thread['author_is_admin'] : 0) === 1) {
        return false;
    }
    if (!qf_moderator_assigned_to_forum(intval($moderator['id']), intval($thread['forum_id']))) {
        return false;
    }
    return qf_moderator_delete_allowed($moderator);
}

function qf_can_moderator_delete_post($moderator, $post) {
    if (!$moderator || !$post || intval(isset($moderator['is_moderator']) ? $moderator['is_moderator'] : 0) !== 1 || intval($moderator['is_admin']) === 1) {
        return false;
    }
    if (intval(isset($post['author_is_admin']) ? $post['author_is_admin'] : 0) === 1) {
        return false;
    }
    if (!qf_moderator_assigned_to_forum(intval($moderator['id']), intval($post['forum_id']))) {
        return false;
    }
    return qf_moderator_delete_allowed($moderator);
}

function qf_security_logs_ready() {
    $table = mysqli_query(db(), "SHOW TABLES LIKE 'qf_security_logs'");
    return $table && mysqli_num_rows($table) > 0;
}

function qf_security_guard() {
    if (PHP_SAPI === 'cli') {
        return;
    }
    $ip = client_ip();
    if ($ip === '' || intval(qf_setting('cc_enabled', '0')) !== 1) {
        return;
    }
    if (!qf_security_logs_ready()) {
        return;
    }
    if (ip_banned($ip)) {
        header('Content-Type: text/html; charset=utf-8', true, 403);
        exit('当前 IP 已被封禁，请稍后再访问。');
    }
    $window = intval(qf_setting('cc_window_seconds', '60'));
    $limit = intval(qf_setting('cc_limit_count', '60'));
    $ban_hours = intval(qf_setting('cc_ban_hours', '2'));
    if ($window < 10) $window = 60;
    if ($limit < 5) $limit = 60;
    if ($ban_hours < 1) $ban_hours = 2;
    $ip_sql = esc($ip);
    $uri = isset($_SERVER['REQUEST_URI']) ? clean_text($_SERVER['REQUEST_URI'], 255) : '';
    $uri_sql = esc($uri);
    mysqli_query(db(), "INSERT INTO qf_security_logs (ip, uri, created_at) VALUES ('{$ip_sql}', '{$uri_sql}', NOW())");
    mysqli_query(db(), "DELETE FROM qf_security_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 DAY)");
    $count = count_rows("SELECT COUNT(*) FROM qf_security_logs WHERE ip='{$ip_sql}' AND created_at >= DATE_SUB(NOW(), INTERVAL {$window} SECOND)");
    if ($count > $limit) {
        $reason = esc('防CC自动封禁：' . $window . '秒内访问' . $count . '次');
        mysqli_query(db(), "INSERT INTO qf_bans (ip, reason, expires_at, created_at) VALUES ('{$ip_sql}', '{$reason}', DATE_ADD(NOW(), INTERVAL {$ban_hours} HOUR), NOW())");
        header('Content-Type: text/html; charset=utf-8', true, 429);
        exit('访问过于频繁，当前 IP 已被临时封禁。');
    }
}

function qf_user_mute_message($user) {
    if (!$user || empty($user['mute_until'])) {
        return '';
    }
    $until = strtotime($user['mute_until']);
    if ($until && $until > time()) {
        return '你已被禁止发言，到期时间：' . date('Y-m-d H:i', $until);
    }
    return '';
}

function format_time($time) {
    $ts = strtotime($time);
    if (!$ts) {
        return '';
    }
    $diff = time() - $ts;
    if ($diff < 60) return $diff . '秒前';
    if ($diff < 3600) return floor($diff / 60) . '分钟前';
    if ($diff < 86400) return floor($diff / 3600) . '小时前';
    return date('Y-m-d H:i', $ts);
}

function qf_render_content($content) {
    $html = nl2br(h($content));
    $html = preg_replace_callback('/\[file url=&quot;([^&]+)&quot; name=&quot;([^&]*)&quot; desc=&quot;([^&]*)&quot;\](.*?)\[\/file\]/is', function($m) {
        return qf_render_file_tag($m[1], $m[2], $m[3]);
    }, $html);
    $html = preg_replace_callback('/\[file url=&quot;([^&]+)&quot; name=&quot;([^&]*)&quot;\](.*?)\[\/file\]/is', function($m) {
        return qf_render_file_tag($m[1], $m[2], $m[3]);
    }, $html);
    $html = preg_replace_callback('/\[img\]((?:https?:\/\/|\/|uploads\/)[^\]\s]+)\[\/img\]/i', function($m) {
        $url = h($m[1]);
        return '<img class="remote-img" src="' . $url . '" alt="远程图片">';
    }, $html);
    $html = preg_replace_callback('/\[url=(&quot;)?((?:https?:\/\/)[^&\]\s]+)(&quot;)?\](.*?)\[\/url\]/is', function($m) {
        return qf_render_url_tag($m[2], $m[4]);
    }, $html);
    $html = preg_replace('/\[b\](.*?)\[\/b\]/is', '<strong>$1</strong>', $html);
    $html = preg_replace('/\[size=([0-9]{1,2})\](.*?)\[\/size\]/is', '<span style="font-size:$1px">$2</span>', $html);
    $html = preg_replace('/\[font=([^\]]{1,20})\](.*?)\[\/font\]/is', '<span style="font-family:$1">$2</span>', $html);
    return $html;
}

function qf_render_url_tag($url, $text) {
    $safe_url = h(html_entity_decode($url, ENT_QUOTES, 'UTF-8'));
    if (!preg_match('/^https?:\/\//i', $safe_url)) {
        return h($text);
    }
    $safe_text = trim($text) !== '' ? $text : $safe_url;
    return '<a class="content-link" href="' . $safe_url . '" target="_blank" rel="nofollow noopener">' . $safe_text . '</a>';
}

function qf_paginate_content($content, $page_chars, $page) {
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

function qf_render_file_tag($url, $name, $description) {
    $safe_url = h(html_entity_decode($url, ENT_QUOTES, 'UTF-8'));
    $safe_name = h(html_entity_decode($name, ENT_QUOTES, 'UTF-8'));
    $safe_description = h(trim(html_entity_decode($description, ENT_QUOTES, 'UTF-8')));
    $title = $safe_description !== '' ? $safe_description : $safe_name;
    $download_count = 0;
    $delete_form = '';
    $link_attr = '';
    $raw_url = html_entity_decode($url, ENT_QUOTES, 'UTF-8');
    $att = qf_resolve_attachment_from_url($raw_url);
    if ($att) {
        $safe_url = h(qf_attachment_url($att['id']));
        $download_count = intval($att['download_count']);
        $delete_form = qf_attachment_delete_form($att);
        if (!current_user() && !qf_guest_download_allowed() && in_array(strtolower($att['file_ext']), array('zip', 'rar'))) {
            $safe_url = h(qf_url_page('register.php'));
            $link_attr = ' ' . qf_guest_download_confirm_onclick();
        }
    } elseif (preg_match('/^\/?uploads\/.*\.(zip|rar)$/i', $raw_url) && !current_user() && !qf_guest_download_allowed()) {
        $safe_url = h(qf_url_page('register.php'));
        $link_attr = ' ' . qf_guest_download_confirm_onclick();
    }
    return '<div class="attachment-inline-card">'
        . '<a class="attachment-inline-link" href="' . $safe_url . '" target="_blank" rel="noopener"' . $link_attr . '>'
        . '<strong>' . $title . '</strong>'
        . '<span>' . $safe_name . ' · 已下载 ' . $download_count . ' 次</span>'
        . '</a>'
        . $delete_form
        . '</div>';
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

function qf_check_copyright() {
    $script = isset($_SERVER['SCRIPT_NAME']) ? basename($_SERVER['SCRIPT_NAME']) : '';
    if (in_array($script, array('install.php', 'upgrade.php', 'copyright.php', 'install/install.php', 'install/upgrade.php'))) {
        return;
    }

    $message = '请勿修改版权信息，本站正在维护升级中~';
    $footer_file = qf_theme_file('footer.php');
    $css_file = __DIR__ . '/assets/style.css';
    $required_link = 'http://lume.0816y.com/';
    $required_text = 'Lume 1.0';
    $required_class = 'powered-by';

    $ok = true;
    if (!file_exists($footer_file)) {
        $ok = false;
    } else {
        $footer = file_get_contents($footer_file);
        if (strpos($footer, $required_link) === false || strpos($footer, $required_text) === false || strpos($footer, $required_class) === false) {
            $ok = false;
        }
    }

    if ($ok && file_exists($css_file)) {
        $css = strtolower(preg_replace('/\s+/', '', file_get_contents($css_file)));
        $bad_rules = array(
            '.powered-by{display:none',
            '.powered-by{visibility:hidden',
            '.powered-by{opacity:0',
            '.copyright{display:none',
            '.copyright{visibility:hidden',
            '.copyright{opacity:0',
            '.footer{display:none',
            '.footer{visibility:hidden',
            '.footer{opacity:0'
        );
        foreach ($bad_rules as $rule) {
            if (strpos($css, $rule) !== false) {
                $ok = false;
                break;
            }
        }
    }

    if (!$ok) {
        $_SESSION['copyright_error'] = $message;
        header('Location: ' . qf_url_page('copyright.php'));
        exit;
    }
}

function qf_upload_attachments($thread_id, $post_id, $user_id, &$errors) {
    if (empty($_FILES['attachments']) || !is_array($_FILES['attachments']['name'])) {
        return 0;
    }

    $has_file = false;
    for ($i = 0; $i < count($_FILES['attachments']['name']); $i++) {
        if ($_FILES['attachments']['name'][$i] !== '') {
            $has_file = true;
            break;
        }
    }
    if (!$has_file) {
        return 0;
    }

    $table = mysqli_query(db(), "SHOW TABLES LIKE 'qf_attachments'");
    if (!$table || mysqli_num_rows($table) == 0) {
        $errors[] = '附件表不存在，请先访问 install/upgrade.php 升级数据库。';
        return 0;
    }

    $allow_exts = qf_upload_allowed_exts();
    $use_remote = qf_s3_enabled() || qf_qiniu_enabled();
    $upload_dir = __DIR__ . '/uploads';
    if (!$use_remote) {
        if (!is_dir($upload_dir) && !mkdir($upload_dir, 0755, true)) {
            $errors[] = 'uploads 目录创建失败，请检查目录权限。';
            return 0;
        }
        if (!is_writable($upload_dir)) {
            $errors[] = 'uploads 目录不可写，请把目录权限设置为可写。';
            return 0;
        }
    }

    $saved = 0;
    for ($i = 0; $i < count($_FILES['attachments']['name']); $i++) {
        if ($_FILES['attachments']['name'][$i] === '') {
            continue;
        }
        if ($_FILES['attachments']['error'][$i] !== UPLOAD_ERR_OK) {
            $errors[] = $_FILES['attachments']['name'][$i] . ' 上传失败，错误码：' . intval($_FILES['attachments']['error'][$i]);
            continue;
        }
        $max_mb = qf_upload_max_mb();
        if ($_FILES['attachments']['size'][$i] > $max_mb * 1024 * 1024) {
            $errors[] = $_FILES['attachments']['name'][$i] . ' 超过 ' . $max_mb . 'MB，已跳过。';
            continue;
        }
        $original = $_FILES['attachments']['name'][$i];
        $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
        if (!in_array($ext, $allow_exts)) {
            $errors[] = $original . ' 格式不支持。';
            continue;
        }
        if (in_array($ext, array('jpg', 'jpeg', 'png', 'gif', 'webp')) && @getimagesize($_FILES['attachments']['tmp_name'][$i]) === false) {
            $errors[] = $original . ' 不是有效图片文件。';
            continue;
        }
        $safe_name = date('YmdHis') . '_' . mt_rand(1000, 9999) . '.' . $ext;
        if ($use_remote) {
            $remote_error = '';
            $file_path = qf_remote_upload_file($_FILES['attachments']['tmp_name'][$i], $safe_name, 'application/octet-stream', $remote_error);
            if ($file_path === '') {
                $errors[] = $original . ' ' . $remote_error;
                continue;
            }
        } else {
            qf_ensure_upload_protection();
            if (!qf_store_uploaded_attachment_file($_FILES['attachments']['tmp_name'][$i], $ext, $file_path)) {
                $errors[] = $original . ' 保存失败，请检查 uploads 权限。';
                continue;
            }
        }
        $path_sql = esc($file_path);
        $original_sql = esc($original);
        $ext_sql = esc($ext);
        $size = intval($_FILES['attachments']['size'][$i]);
        $thread_id = intval($thread_id);
        $post_id = intval($post_id);
        $user_id = intval($user_id);
        $ok = mysqli_query(db(), "INSERT INTO qf_attachments (thread_id,post_id,user_id,file_path,original_name,file_ext,file_size,created_at) VALUES ({$thread_id},{$post_id},{$user_id},'{$path_sql}','{$original_sql}','{$ext_sql}',{$size},NOW())");
        if ($ok) {
            $saved++;
        } else {
            $errors[] = $original . ' 数据保存失败：' . mysqli_error(db());
        }
    }
    return $saved;
}
if (PHP_SAPI !== 'cli') {
    ob_start('qf_inject_csrf_fields');
    qf_ensure_upload_protection();
    qf_require_csrf();
}
qf_security_guard();
?>
