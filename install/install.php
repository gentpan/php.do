<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../compat.php';
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS);
if (!$conn) {
    exit('数据库连接失败：' . mysqli_connect_error());
}
mysqli_query($conn, "CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
mysqli_select_db($conn, DB_NAME);
mysqli_set_charset($conn, DB_CHARSET);

$sqls = array();
$sqls[] = "CREATE TABLE IF NOT EXISTS qf_users (
  id int(11) NOT NULL AUTO_INCREMENT,
  username varchar(32) NOT NULL DEFAULT '',
  password varchar(255) NOT NULL DEFAULT '',
  nickname varchar(32) NOT NULL DEFAULT '',
  email varchar(190) NOT NULL DEFAULT '',
  email_bound_at datetime DEFAULT NULL,
  avatar varchar(255) NOT NULL DEFAULT '',
  signature varchar(255) NOT NULL DEFAULT '',
  gender varchar(10) NOT NULL DEFAULT '',
  custom_field varchar(255) NOT NULL DEFAULT '',
  is_admin tinyint(1) NOT NULL DEFAULT '0',
  is_moderator tinyint(1) NOT NULL DEFAULT '0',
  moderator_delete_limit int(11) NOT NULL DEFAULT '0',
  status tinyint(1) NOT NULL DEFAULT '1',
  mute_until datetime DEFAULT NULL,
  coins int(11) NOT NULL DEFAULT '0',
  reply_count int(11) NOT NULL DEFAULT '0',
  notification_sound_enabled tinyint(1) NOT NULL DEFAULT '1',
  ip varchar(45) NOT NULL DEFAULT '',
  created_at datetime NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

$sqls[] = "CREATE TABLE IF NOT EXISTS qf_passkeys (
  id int(11) NOT NULL AUTO_INCREMENT,
  user_id int(11) NOT NULL DEFAULT '0',
  credential_id varchar(255) NOT NULL DEFAULT '',
  public_key_cose text NOT NULL,
  sign_count bigint(20) NOT NULL DEFAULT '0',
  label varchar(80) NOT NULL DEFAULT '',
  transports varchar(120) NOT NULL DEFAULT '',
  created_at datetime NOT NULL,
  last_used_at datetime DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY credential_id (credential_id),
  KEY user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

$sqls[] = "CREATE TABLE IF NOT EXISTS qf_forums (
  id int(11) NOT NULL AUTO_INCREMENT,
  name varchar(60) NOT NULL DEFAULT '',
  description varchar(255) NOT NULL DEFAULT '',
  topic_category_enabled tinyint(1) NOT NULL DEFAULT '0',
  topic_categories varchar(255) NOT NULL DEFAULT '',
  post_user_limit_enabled tinyint(1) NOT NULL DEFAULT '0',
  post_user_ids varchar(255) NOT NULL DEFAULT '',
  display_order int(11) NOT NULL DEFAULT '0',
  created_at datetime NOT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

$sqls[] = "CREATE TABLE IF NOT EXISTS qf_threads (
  id int(11) NOT NULL AUTO_INCREMENT,
  forum_id int(11) NOT NULL DEFAULT '0',
  user_id int(11) NOT NULL DEFAULT '0',
  topic_category varchar(40) NOT NULL DEFAULT '',
  title varchar(120) NOT NULL DEFAULT '',
  content mediumtext NOT NULL,
  views int(11) NOT NULL DEFAULT '0',
  replies int(11) NOT NULL DEFAULT '0',
  is_top tinyint(1) NOT NULL DEFAULT '0',
  is_good tinyint(1) NOT NULL DEFAULT '0',
  is_deleted tinyint(1) NOT NULL DEFAULT '0',
  ip varchar(45) NOT NULL DEFAULT '',
  created_at datetime NOT NULL,
  updated_at datetime NOT NULL,
  PRIMARY KEY (id),
  KEY forum_id (forum_id),
  KEY updated_at (updated_at),
  KEY is_top (is_top)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

$sqls[] = "CREATE TABLE IF NOT EXISTS qf_posts (
  id int(11) NOT NULL AUTO_INCREMENT,
  thread_id int(11) NOT NULL DEFAULT '0',
  user_id int(11) NOT NULL DEFAULT '0',
  content mediumtext NOT NULL,
  is_deleted tinyint(1) NOT NULL DEFAULT '0',
  ip varchar(45) NOT NULL DEFAULT '',
  created_at datetime NOT NULL,
  PRIMARY KEY (id),
  KEY thread_id (thread_id),
  KEY created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

$sqls[] = "CREATE TABLE IF NOT EXISTS qf_bans (
  id int(11) NOT NULL AUTO_INCREMENT,
  ip varchar(45) NOT NULL DEFAULT '',
  reason varchar(255) NOT NULL DEFAULT '',
  expires_at datetime DEFAULT NULL,
  created_at datetime NOT NULL,
  PRIMARY KEY (id),
  KEY ip (ip)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

$sqls[] = "CREATE TABLE IF NOT EXISTS qf_security_logs (
  id int(11) NOT NULL AUTO_INCREMENT,
  ip varchar(45) NOT NULL DEFAULT '',
  uri varchar(255) NOT NULL DEFAULT '',
  created_at datetime NOT NULL,
  PRIMARY KEY (id),
  KEY ip_created (ip, created_at),
  KEY created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

$sqls[] = "CREATE TABLE IF NOT EXISTS qf_moderator_logs (
  id int(11) NOT NULL AUTO_INCREMENT,
  moderator_id int(11) NOT NULL DEFAULT '0',
  target_type varchar(20) NOT NULL DEFAULT '',
  target_id int(11) NOT NULL DEFAULT '0',
  created_at datetime NOT NULL,
  PRIMARY KEY (id),
  KEY moderator_created (moderator_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

$sqls[] = "CREATE TABLE IF NOT EXISTS qf_moderator_forums (
  id int(11) NOT NULL AUTO_INCREMENT,
  user_id int(11) NOT NULL DEFAULT '0',
  forum_id int(11) NOT NULL DEFAULT '0',
  created_at datetime NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY user_forum (user_id,forum_id),
  KEY forum_id (forum_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

$sqls[] = "CREATE TABLE IF NOT EXISTS qf_attachments (
  id int(11) NOT NULL AUTO_INCREMENT,
  thread_id int(11) NOT NULL DEFAULT '0',
  post_id int(11) NOT NULL DEFAULT '0',
  user_id int(11) NOT NULL DEFAULT '0',
  file_path varchar(255) NOT NULL DEFAULT '',
  original_name varchar(255) NOT NULL DEFAULT '',
  file_ext varchar(20) NOT NULL DEFAULT '',
  file_size int(11) NOT NULL DEFAULT '0',
  download_count int(11) NOT NULL DEFAULT '0',
  created_at datetime NOT NULL,
  PRIMARY KEY (id),
  KEY thread_id (thread_id),
  KEY post_id (post_id),
  KEY user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

$sqls[] = "CREATE TABLE IF NOT EXISTS qf_post_comments (
  id int(11) NOT NULL AUTO_INCREMENT,
  thread_id int(11) NOT NULL DEFAULT '0',
  post_id int(11) NOT NULL DEFAULT '0',
  user_id int(11) NOT NULL DEFAULT '0',
  content text NOT NULL,
  ip varchar(45) NOT NULL DEFAULT '',
  is_deleted tinyint(1) NOT NULL DEFAULT '0',
  created_at datetime NOT NULL,
  PRIMARY KEY (id),
  KEY post_id (post_id),
  KEY thread_id (thread_id),
  KEY created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

$sqls[] = "CREATE TABLE IF NOT EXISTS qf_notifications (
  id int(11) NOT NULL AUTO_INCREMENT,
  user_id int(11) NOT NULL DEFAULT '0',
  thread_id int(11) NOT NULL DEFAULT '0',
  post_id int(11) NOT NULL DEFAULT '0',
  message varchar(180) NOT NULL DEFAULT '',
  is_read tinyint(1) NOT NULL DEFAULT '0',
  created_at datetime NOT NULL,
  PRIMARY KEY (id),
  KEY user_read (user_id, is_read),
  KEY created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

$sqls[] = "CREATE TABLE IF NOT EXISTS qf_settings (
  setting_key varchar(60) NOT NULL DEFAULT '',
  setting_value text NOT NULL,
  PRIMARY KEY (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

$sqls[] = "CREATE TABLE IF NOT EXISTS qf_signins (
  id int(11) NOT NULL AUTO_INCREMENT,
  user_id int(11) NOT NULL DEFAULT '0',
  signin_date date NOT NULL,
  continuous_days int(11) NOT NULL DEFAULT '1',
  reward_coins int(11) NOT NULL DEFAULT '0',
  created_at datetime NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY user_date (user_id, signin_date),
  KEY user_id (user_id),
  KEY signin_date (signin_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

$sqls[] = "CREATE TABLE IF NOT EXISTS qf_ads (
  id int(11) NOT NULL AUTO_INCREMENT,
  position varchar(30) NOT NULL DEFAULT '',
  title varchar(80) NOT NULL DEFAULT '',
  image_path varchar(255) NOT NULL DEFAULT '',
  link_url varchar(255) NOT NULL DEFAULT '',
  width varchar(20) NOT NULL DEFAULT '',
  height varchar(20) NOT NULL DEFAULT '',
  is_enabled tinyint(1) NOT NULL DEFAULT '0',
  updated_at datetime NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY position (position)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

$sqls[] = "CREATE TABLE IF NOT EXISTS qf_navs (
  id int(11) NOT NULL AUTO_INCREMENT,
  title varchar(40) NOT NULL DEFAULT '',
  url varchar(255) NOT NULL DEFAULT '',
  display_order int(11) NOT NULL DEFAULT '0',
  is_enabled tinyint(1) NOT NULL DEFAULT '1',
  created_at datetime NOT NULL,
  PRIMARY KEY (id),
  KEY enabled_order (is_enabled, display_order, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

$ok = true;
$errors = array();
foreach ($sqls as $sql) {
    if (!mysqli_query($conn, $sql)) {
        $ok = false;
        $errors[] = mysqli_error($conn);
    }
}

if ($ok) {
    require_once __DIR__ . '/functions.php';
    $forum_count = 0;
    $rs = mysqli_query($conn, "SELECT COUNT(*) FROM qf_forums");
    if ($rs) {
        $row = mysqli_fetch_row($rs);
        $forum_count = intval($row[0]);
    }
    if ($forum_count === 0) {
        mysqli_query($conn, "INSERT INTO qf_forums (name, description, topic_category_enabled, topic_categories, post_user_limit_enabled, post_user_ids, display_order, created_at) VALUES
            ('站务公告', '网站公告、规则和反馈', 0, '', 0, '', 1, NOW()),
            ('闲聊灌水', '轻松交流，分享日常', 0, '', 0, '', 2, NOW()),
            ('本地生活', '同城信息、吃喝玩乐和生活交流', 0, '', 0, '', 3, NOW())");
    }
    $admin_count = 0;
    $rs = mysqli_query($conn, "SELECT COUNT(*) FROM qf_users WHERE is_admin=1");
    if ($rs) {
        $row = mysqli_fetch_row($rs);
        $admin_count = intval($row[0]);
    }
    if ($admin_count === 0) {
        $pass = qf_password_hash('admin123');
        mysqli_query($conn, "INSERT INTO qf_users (username, password, nickname, is_admin, ip, created_at) VALUES ('admin', '{$pass}', '管理员', 1, '', NOW())");
    }
    $settings = array(
        'site_title' => SITE_NAME,
        'site_name' => SITE_NAME,
        'site_desc' => SITE_DESC,
        'site_keywords' => '',
        'theme_name' => 'light-blue',
        'title_font' => 'system',
        'content_font' => 'system',
        'icp_code' => '',
        'stats_code' => '',
        'upload_max_mb' => '5',
        'upload_allowed_exts' => 'jpg,jpeg,png,gif,webp,zip,rar',
        'guest_download_enabled' => '0',
        'cc_enabled' => '0',
        'cc_window_seconds' => '60',
        'cc_limit_count' => '60',
        'cc_ban_hours' => '2',
        'home_threads_per_page' => '12',
        'forum_threads_per_page' => '60',
        'thread_page_chars' => '4000',
        'reply_max_chars' => '1000',
        'signin_base_coins' => '5',
        'signin_streak_bonus' => '2',
        'moderator_daily_delete_limit' => '20',
        'register_ip_daily_limit' => '5',
        'captcha_enabled' => '1',
        'captcha_reply_free_count' => '10',
        's3_enabled' => '0',
        's3_endpoint' => '',
        's3_region' => 'auto',
        's3_bucket' => '',
        's3_access_key' => '',
        's3_secret_key' => '',
        's3_cdn_domain' => '',
        's3_path_prefix' => 'lume',
        'friend_links_enabled' => '0',
        'friend_links' => '',
        'rewrite_enabled' => '1',
        'rewrite_nginx_rules' => 'rewrite ^/thread/([0-9]+)\\.html$ /pages/thread.php?id=$1 last;
rewrite ^/forum/([0-9]+)\\.html$ /pages/forum.php?id=$1 last;
rewrite ^/download/([0-9]+)$ /pages/download.php?id=$1 last;
try_files $uri $uri.php $uri/ /index.php?$query_string;'
    );
    foreach ($settings as $k => $v) {
        $k_sql = mysqli_real_escape_string($conn, $k);
        $v_sql = mysqli_real_escape_string($conn, $v);
        mysqli_query($conn, "INSERT IGNORE INTO qf_settings (setting_key, setting_value) VALUES ('{$k_sql}', '{$v_sql}')");
    }
    $ads = array('top' => '顶部广告', 'sidebar' => '右侧板块上方广告', 'footer' => '底部广告');
    foreach ($ads as $pos => $title) {
        $pos_sql = mysqli_real_escape_string($conn, $pos);
        $title_sql = mysqli_real_escape_string($conn, $title);
        mysqli_query($conn, "INSERT IGNORE INTO qf_ads (position,title,updated_at) VALUES ('{$pos_sql}','{$title_sql}',NOW())");
    }
}
?>
<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <title>论坛安装</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<main class="wrap narrow">
    <section class="card">
        <h1>安装结果</h1>
        <?php if ($ok) { ?>
            <p class="success">安装成功。</p>
            <p>默认管理员：<strong>admin</strong></p>
            <p>默认密码：<strong>admin123</strong></p>
            <p><a class="btn" href="../">进入论坛</a></p>
            <p class="muted">测试完成后请删除 install/install.php，并登录后台修改管理员密码。</p>
        <?php } else { ?>
            <p class="danger">安装失败：</p>
            <pre><?php echo htmlspecialchars(implode("\n", $errors), ENT_QUOTES, 'UTF-8'); ?></pre>
        <?php } ?>
    </section>
</main>
</body>
</html>
