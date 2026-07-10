<?php
/* core/settings.php — 由 functions.php 自动切分。集中 28 个定义。 */

function pd_default_avatar_dir() {
    return PD_ROOT . '/assets/avatars';
}

function pd_default_avatar_public_path($user_id) {
    return 'assets/avatars/user-' . intval($user_id) . '.svg';
}

function pd_setting($key, $default = '') {
    static $cache = null;
    if ($key === null) {
        $cache = null;
        return $default;
    }
    if ($cache === null) {
        $cache = array();
        $table = mysqli_query(db(), "SHOW TABLES LIKE 'pd_settings'");
        if ($table && mysqli_num_rows($table) > 0) {
            $rs = mysqli_query(db(), "SELECT setting_key, setting_value FROM pd_settings");
            while ($rs && $row = mysqli_fetch_assoc($rs)) {
                $cache[$row['setting_key']] = $row['setting_value'];
            }
        }
    }
    return isset($cache[$key]) ? $cache[$key] : $default;
}

function pd_site_name() {
    return pd_setting('site_name', SITE_NAME);
}

function pd_site_desc() {
    return pd_setting('site_desc', SITE_DESC);
}

function pd_site_keywords() {
    return pd_setting('site_keywords', '');
}

function pd_default_level_thresholds() {
    return array(1 => 0, 2 => 30, 3 => 100, 4 => 250, 5 => 500, 6 => 1000, 7 => 2000, 8 => 3500, 9 => 6000, 10 => 10000);
}

function pd_default_nginx_rewrite_rules() {
    return "rewrite ^/thread/([0-9]+)\\.html$ /pages/thread.php?id=$1 last;\n"
        . "rewrite ^/download/([0-9]+)$ /api/download.php?id=$1 last;\n"
        . "rewrite ^/api/([a-z-]+)$ /api/$1.php last;\n"
        . "rewrite ^/admin/([a-z-]+)$ /admin/$1.php last;\n"
        . "try_files \$uri \$uri/ /index.php?\$query_string;";
}

function pd_theme_file($file) {
    return PD_ROOT . '/' . ltrim($file, '/');
}

function pd_static_pages() {
    // 内容分部在 pages/legal/<view>.php（分部自行输出 .pd-info-block 卡片）
    return array(
        'help' => array('title' => '使用帮助', 'view' => 'help'),
        'rules' => array('title' => '规则', 'view' => 'rules'),
        'privacy' => array('title' => '隐私政策', 'view' => 'privacy'),
    );
}

function pd_static_page($slug) {
    $slug = strtolower(trim((string)$slug, '/'));
    $pages = pd_static_pages();
    return isset($pages[$slug]) ? $pages[$slug] : null;
}

// ===== 社区“关于”页数据 =====
function pd_site_slogan() {
    return pd_setting('site_slogan', 'where possible begins · 让分享回到互联网');
}

function pd_site_about_text() {
    $default = "行色匆匆的旅人啊，你是否还记得十多年前互联网的模样？\n那时候的人们乐于分享自己的见识，不以有钱为成功标准。\n来这里，拓一方净土，重现互联网精神，这里什么都可能。";
    return pd_setting('site_about', $default);
}

function pd_site_founded_text() {
    $set = trim((string)pd_setting('site_founded', ''));
    $ts = 0;
    if ($set !== '' && ($t = strtotime($set)) !== false) {
        $ts = $t;
    } else {
        $r = mysqli_query(db(), "SELECT MIN(created_at) AS m FROM pd_users");
        $row = $r ? mysqli_fetch_assoc($r) : null;
        if ($row && $row['m']) {
            $ts = strtotime($row['m']);
        }
    }
    if ($ts <= 0) {
        return '—';
    }
    $diff = time() - $ts;
    $years = (int)floor($diff / (86400 * 365));
    $months = (int)floor($diff / (86400 * 30));
    if ($years >= 1) {
        return $years . ' 年前';
    }
    if ($months >= 1) {
        return $months . ' 个月前';
    }
    return '不到 1 个月';
}

function pd_nav_table_ready() {
    $table = mysqli_query(db(), "SHOW TABLES LIKE 'pd_navs'");
    return $table && mysqli_num_rows($table) > 0;
}

function pd_nav_target($url) {
    return preg_match('/^https?:\/\//i', (string)$url) ? ' target="_blank" rel="nofollow noopener"' : '';
}

function pd_main_navs() {
    if (!pd_nav_table_ready()) {
        return array();
    }
    $rs = mysqli_query(db(), "SELECT * FROM pd_navs WHERE is_enabled=1 ORDER BY display_order ASC, id ASC");
    $rows = array();
    while ($rs && ($row = mysqli_fetch_assoc($rs))) {
        $rows[] = $row;
    }
    return $rows;
}

function pd_header_nav_forums() {
    $rs = mysqli_query(db(), "SELECT id, name FROM pd_forums WHERE show_in_nav=1 ORDER BY display_order ASC, id ASC");
    $rows = array();
    while ($rs && ($row = mysqli_fetch_assoc($rs))) {
        $rows[] = $row;
    }
    return $rows;
}

function pd_footer_nav_forums() {
    $rs = mysqli_query(db(), "SELECT id, name FROM pd_forums WHERE show_in_nav=0 ORDER BY display_order ASC, id ASC");
    $rows = array();
    while ($rs && ($row = mysqli_fetch_assoc($rs))) {
        $rows[] = $row;
    }
    return $rows;
}

function pd_nav_icon_html($nav) {
    $type = isset($nav['icon_type']) ? $nav['icon_type'] : '';
    $value = isset($nav['icon_value']) ? trim((string)$nav['icon_value']) : '';
    if ($type === 'img' && $value !== '') {
        return '<img class="pd-cat-icon pd-cat-icon-img" src="' . h($value) . '" alt="" aria-hidden="true">';
    }
    if ($type === 'svg') {
        $clean = pd_sanitize_nav_svg($value);
        if ($clean !== '') {
            return '<span class="pd-cat-icon pd-cat-icon-svg" aria-hidden="true">' . $clean . '</span>';
        }
    }
    if ($type === 'fa' && $value !== '') {
        $cls = trim(preg_replace('/[^a-z0-9 _-]/i', '', $value));
        if ($cls !== '') {
            return '<i class="pd-cat-icon ' . h($cls) . '" aria-hidden="true"></i>';
        }
    }
    return '<i class="pd-cat-icon fa-regular fa-compass" aria-hidden="true"></i>';
}

/** 读取整型站点设置并限制在 [min, max] */
function pd_setting_int($key, $default, $min = null, $max = null) {
    $value = intval(pd_setting($key, (string)$default));
    if ($min !== null && $value < $min) {
        $value = $min;
    }
    if ($max !== null && $value > $max) {
        $value = $max;
    }
    return $value;
}
