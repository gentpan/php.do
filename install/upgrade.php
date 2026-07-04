<?php
require_once __DIR__ . '/../functions.php';

$sql = "CREATE TABLE IF NOT EXISTS qf_attachments (
  id int(11) NOT NULL AUTO_INCREMENT,
  thread_id int(11) NOT NULL DEFAULT '0',
  post_id int(11) NOT NULL DEFAULT '0',
  user_id int(11) NOT NULL DEFAULT '0',
  file_path varchar(255) NOT NULL DEFAULT '',
  original_name varchar(255) NOT NULL DEFAULT '',
  file_ext varchar(20) NOT NULL DEFAULT '',
  file_size int(11) NOT NULL DEFAULT '0',
  created_at datetime NOT NULL,
  PRIMARY KEY (id),
  KEY thread_id (thread_id),
  KEY post_id (post_id),
  KEY user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

$ok = mysqli_query(db(), $sql);
if ($ok) {
    $check = mysqli_query(db(), "SHOW COLUMNS FROM qf_users LIKE 'reply_count'");
    if ($check && mysqli_num_rows($check) == 0) {
        mysqli_query(db(), "ALTER TABLE qf_users ADD reply_count int(11) NOT NULL DEFAULT '0' AFTER status");
    }
    $check = mysqli_query(db(), "SHOW COLUMNS FROM qf_users LIKE 'mute_until'");
    if ($check && mysqli_num_rows($check) == 0) {
        mysqli_query(db(), "ALTER TABLE qf_users ADD mute_until datetime DEFAULT NULL AFTER status");
    }
    $check = mysqli_query(db(), "SHOW COLUMNS FROM qf_users LIKE 'coins'");
    if ($check && mysqli_num_rows($check) == 0) {
        mysqli_query(db(), "ALTER TABLE qf_users ADD coins int(11) NOT NULL DEFAULT '0' AFTER mute_until");
    }
    $check = mysqli_query(db(), "SHOW COLUMNS FROM qf_users LIKE 'is_moderator'");
    if ($check && mysqli_num_rows($check) == 0) {
        mysqli_query(db(), "ALTER TABLE qf_users ADD is_moderator tinyint(1) NOT NULL DEFAULT '0' AFTER is_admin");
    }
    $check = mysqli_query(db(), "SHOW COLUMNS FROM qf_users LIKE 'moderator_delete_limit'");
    if ($check && mysqli_num_rows($check) == 0) {
        mysqli_query(db(), "ALTER TABLE qf_users ADD moderator_delete_limit int(11) NOT NULL DEFAULT '0' AFTER is_moderator");
    }
    $check = mysqli_query(db(), "SHOW COLUMNS FROM qf_users LIKE 'signature'");
    if ($check && mysqli_num_rows($check) == 0) {
        mysqli_query(db(), "ALTER TABLE qf_users ADD signature varchar(255) NOT NULL DEFAULT '' AFTER avatar");
    }
    $check = mysqli_query(db(), "SHOW COLUMNS FROM qf_users LIKE 'email'");
    if ($check && mysqli_num_rows($check) == 0) {
        mysqli_query(db(), "ALTER TABLE qf_users ADD email varchar(190) NOT NULL DEFAULT '' AFTER nickname");
    }
    $check = mysqli_query(db(), "SHOW COLUMNS FROM qf_users LIKE 'email_bound_at'");
    if ($check && mysqli_num_rows($check) == 0) {
        mysqli_query(db(), "ALTER TABLE qf_users ADD email_bound_at datetime DEFAULT NULL AFTER email");
    }
    $check = mysqli_query(db(), "SHOW COLUMNS FROM qf_users LIKE 'gender'");
    if ($check && mysqli_num_rows($check) == 0) {
        mysqli_query(db(), "ALTER TABLE qf_users ADD gender varchar(10) NOT NULL DEFAULT '' AFTER signature");
    }
    $check = mysqli_query(db(), "SHOW COLUMNS FROM qf_users LIKE 'custom_field'");
    if ($check && mysqli_num_rows($check) == 0) {
        mysqli_query(db(), "ALTER TABLE qf_users ADD custom_field varchar(255) NOT NULL DEFAULT '' AFTER gender");
    }
    $check = mysqli_query(db(), "SHOW COLUMNS FROM qf_users LIKE 'notification_sound_enabled'");
    if ($check && mysqli_num_rows($check) == 0) {
        mysqli_query(db(), "ALTER TABLE qf_users ADD notification_sound_enabled tinyint(1) NOT NULL DEFAULT '1' AFTER reply_count");
    }
}

if ($ok) {
    $check = mysqli_query(db(), "SHOW COLUMNS FROM qf_attachments LIKE 'post_id'");
    if ($check && mysqli_num_rows($check) == 0) {
        mysqli_query(db(), "ALTER TABLE qf_attachments ADD post_id int(11) NOT NULL DEFAULT '0' AFTER thread_id, ADD KEY post_id (post_id)");
    }
    $check = mysqli_query(db(), "SHOW COLUMNS FROM qf_attachments LIKE 'download_count'");
    if ($check && mysqli_num_rows($check) == 0) {
        mysqli_query(db(), "ALTER TABLE qf_attachments ADD download_count int(11) NOT NULL DEFAULT '0' AFTER file_size");
    }
}

if ($ok) {
    $check = mysqli_query(db(), "SHOW COLUMNS FROM qf_threads LIKE 'topic_category'");
    if ($check && mysqli_num_rows($check) == 0) {
        mysqli_query(db(), "ALTER TABLE qf_threads ADD topic_category varchar(40) NOT NULL DEFAULT '' AFTER user_id");
    }
}

if ($ok) {
    $check = mysqli_query(db(), "SHOW COLUMNS FROM qf_forums LIKE 'topic_category_enabled'");
    if ($check && mysqli_num_rows($check) == 0) {
        mysqli_query(db(), "ALTER TABLE qf_forums ADD topic_category_enabled tinyint(1) NOT NULL DEFAULT '0' AFTER description");
    }
    $check = mysqli_query(db(), "SHOW COLUMNS FROM qf_forums LIKE 'topic_categories'");
    if ($check && mysqli_num_rows($check) == 0) {
        mysqli_query(db(), "ALTER TABLE qf_forums ADD topic_categories varchar(255) NOT NULL DEFAULT '' AFTER topic_category_enabled");
    }
    $check = mysqli_query(db(), "SHOW COLUMNS FROM qf_forums LIKE 'post_user_limit_enabled'");
    if ($check && mysqli_num_rows($check) == 0) {
        mysqli_query(db(), "ALTER TABLE qf_forums ADD post_user_limit_enabled tinyint(1) NOT NULL DEFAULT '0' AFTER topic_categories");
    }
    $check = mysqli_query(db(), "SHOW COLUMNS FROM qf_forums LIKE 'post_user_ids'");
    if ($check && mysqli_num_rows($check) == 0) {
        mysqli_query(db(), "ALTER TABLE qf_forums ADD post_user_ids varchar(255) NOT NULL DEFAULT '' AFTER post_user_limit_enabled");
    }
}

$bans_sql = "CREATE TABLE IF NOT EXISTS qf_bans (
  id int(11) NOT NULL AUTO_INCREMENT,
  ip varchar(45) NOT NULL DEFAULT '',
  reason varchar(255) NOT NULL DEFAULT '',
  expires_at datetime DEFAULT NULL,
  created_at datetime NOT NULL,
  PRIMARY KEY (id),
  KEY ip (ip)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($ok) {
    $ok = mysqli_query(db(), $bans_sql);
}

$security_logs_sql = "CREATE TABLE IF NOT EXISTS qf_security_logs (
  id int(11) NOT NULL AUTO_INCREMENT,
  ip varchar(45) NOT NULL DEFAULT '',
  uri varchar(255) NOT NULL DEFAULT '',
  created_at datetime NOT NULL,
  PRIMARY KEY (id),
  KEY ip_created (ip, created_at),
  KEY created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($ok) {
    $ok = mysqli_query(db(), $security_logs_sql);
}

$moderator_logs_sql = "CREATE TABLE IF NOT EXISTS qf_moderator_logs (
  id int(11) NOT NULL AUTO_INCREMENT,
  moderator_id int(11) NOT NULL DEFAULT '0',
  target_type varchar(20) NOT NULL DEFAULT '',
  target_id int(11) NOT NULL DEFAULT '0',
  created_at datetime NOT NULL,
  PRIMARY KEY (id),
  KEY moderator_created (moderator_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($ok) {
    $ok = mysqli_query(db(), $moderator_logs_sql);
}

$moderator_forums_sql = "CREATE TABLE IF NOT EXISTS qf_moderator_forums (
  id int(11) NOT NULL AUTO_INCREMENT,
  user_id int(11) NOT NULL DEFAULT '0',
  forum_id int(11) NOT NULL DEFAULT '0',
  created_at datetime NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY user_forum (user_id,forum_id),
  KEY forum_id (forum_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($ok) {
    $ok = mysqli_query(db(), $moderator_forums_sql);
}

$settings_sql = "CREATE TABLE IF NOT EXISTS qf_settings (
  setting_key varchar(60) NOT NULL DEFAULT '',
  setting_value text NOT NULL,
  PRIMARY KEY (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($ok) {
    $ok = mysqli_query(db(), $settings_sql);
}

$passkeys_sql = "CREATE TABLE IF NOT EXISTS qf_passkeys (
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

if ($ok) {
    $ok = mysqli_query(db(), $passkeys_sql);
}

$signins_sql = "CREATE TABLE IF NOT EXISTS qf_signins (
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

if ($ok) {
    $ok = mysqli_query(db(), $signins_sql);
}

$post_comments_sql = "CREATE TABLE IF NOT EXISTS qf_post_comments (
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

if ($ok) {
    $ok = mysqli_query(db(), $post_comments_sql);
}

$notifications_sql = "CREATE TABLE IF NOT EXISTS qf_notifications (
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

if ($ok) {
    $ok = mysqli_query(db(), $notifications_sql);
}

if ($ok) {
    $settings = array(
        'site_title' => SITE_NAME,
        'site_name' => SITE_NAME,
        'site_desc' => SITE_DESC,
        'site_keywords' => '',
        'theme_name' => 'php',
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
        'rewrite_nginx_rules' => qf_default_nginx_rewrite_rules()
    );
    foreach ($settings as $k => $v) {
        $k_sql = esc($k);
        $v_sql = esc($v);
        mysqli_query(db(), "INSERT IGNORE INTO qf_settings (setting_key, setting_value) VALUES ('{$k_sql}', '{$v_sql}')");
    }
    mysqli_query(db(), "UPDATE qf_settings SET setting_value='4000' WHERE setting_key='thread_page_chars' AND setting_value='3000'");
    mysqli_query(db(), "UPDATE qf_settings SET setting_value='1000' WHERE setting_key='reply_max_chars' AND setting_value='8000'");
    $rewrite_rs = mysqli_query(db(), "SELECT setting_value FROM qf_settings WHERE setting_key='rewrite_nginx_rules' LIMIT 1");
    $rewrite_row = $rewrite_rs ? mysqli_fetch_assoc($rewrite_rs) : null;
    if ($rewrite_row && (
        strpos($rewrite_row['setting_value'], '/thread.php/$1') !== false
        || strpos($rewrite_row['setting_value'], '/forum.php/$1') !== false
        || strpos($rewrite_row['setting_value'], '/thread.php?id=$1') !== false
        || strpos($rewrite_row['setting_value'], '/forum.php?id=$1') !== false
        || strpos($rewrite_row['setting_value'], '/download.php?id=$1') !== false
        || strpos($rewrite_row['setting_value'], 'rewrite ^/thread/([0-9]+)$') !== false
        || strpos($rewrite_row['setting_value'], 'rewrite ^/forum/([0-9]+)$') !== false
    )) {
        qf_update_setting('rewrite_nginx_rules', qf_default_nginx_rewrite_rules());
    }
}

$ads_sql = "CREATE TABLE IF NOT EXISTS qf_ads (
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

if ($ok) {
    $ok = mysqli_query(db(), $ads_sql);
}

$navs_sql = "CREATE TABLE IF NOT EXISTS qf_navs (
  id int(11) NOT NULL AUTO_INCREMENT,
  title varchar(40) NOT NULL DEFAULT '',
  url varchar(255) NOT NULL DEFAULT '',
  display_order int(11) NOT NULL DEFAULT '0',
  is_enabled tinyint(1) NOT NULL DEFAULT '1',
  created_at datetime NOT NULL,
  PRIMARY KEY (id),
  KEY enabled_order (is_enabled, display_order, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($ok) {
    $ok = mysqli_query(db(), $navs_sql);
}

if ($ok) {
    $ads = array('top' => '顶部广告', 'sidebar' => '右侧板块上方广告', 'footer' => '底部广告');
    foreach ($ads as $pos => $title) {
        $pos_sql = esc($pos);
        $title_sql = esc($title);
        mysqli_query(db(), "INSERT IGNORE INTO qf_ads (position,title,updated_at) VALUES ('{$pos_sql}','{$title_sql}',NOW())");
    }
}
?>
<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <title>升级数据库</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<main class="wrap narrow">
    <section class="card">
        <h1>升级结果</h1>
        <?php if ($ok) { ?>
            <p class="success">升级成功，附件表、站点设置表、广告表、用户管理字段和资料字段已准备好。</p>
            <p><a class="btn" href="../">返回首页</a></p>
            <p class="muted">升级完成后建议删除 install/upgrade.php。</p>
        <?php } else { ?>
            <p class="danger">升级失败：<?php echo h(mysqli_error(db())); ?></p>
        <?php } ?>
    </section>
</main>
</body>
</html>
