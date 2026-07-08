<?php
/* core/schema.php — 由 functions.php 自动切分。集中 18 个定义。 */

function pd_require_csrf() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !pd_verify_csrf()) {
        header('Content-Type: text/html; charset=utf-8', true, 403);
        exit('请求已过期或来源不正确，请返回上一页刷新后重试。');
    }
}

function pd_require_action_token($action, $id, $extra = '') {
    $sent = isset($_GET['token']) ? (string)$_GET['token'] : '';
    if ($sent === '' || !hash_equals(pd_action_token($action, $id, $extra), $sent)) {
        header('Content-Type: text/html; charset=utf-8', true, 403);
        exit('操作链接已过期或来源不正确，请返回上一页刷新后重试。');
    }
}

function pd_ensure_upload_protection() {
    $dir = PD_ROOT . '/uploads';
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

// ===== 用户积分、等级与用户组 =====
function pd_ensure_attachment_download_schema() {
    static $done = false;
    if ($done) {
        return true;
    }
    $done = true;
    mysqli_query(db(), "CREATE TABLE IF NOT EXISTS pd_attachment_downloads (
      id int(11) NOT NULL AUTO_INCREMENT,
      attachment_id int(11) NOT NULL DEFAULT '0',
      user_id int(11) NOT NULL DEFAULT '0',
      cost int(11) NOT NULL DEFAULT '0',
      created_at datetime NOT NULL,
      PRIMARY KEY (id),
      UNIQUE KEY uniq_att_user (attachment_id, user_id),
      KEY user_id (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    return true;
}

function pd_ensure_points_schema() {
    static $done = false;
    if ($done) {
        return true;
    }
    $done = true;

    mysqli_query(db(), "CREATE TABLE IF NOT EXISTS pd_user_groups (
      id int(11) NOT NULL AUTO_INCREMENT,
      name varchar(60) NOT NULL DEFAULT '',
      slug varchar(40) NOT NULL DEFAULT '',
      color varchar(20) NOT NULL DEFAULT '',
      min_points int(11) NOT NULL DEFAULT '0',
      is_system tinyint(1) NOT NULL DEFAULT '0',
      display_order int(11) NOT NULL DEFAULT '0',
      created_at datetime NOT NULL,
      PRIMARY KEY (id),
      UNIQUE KEY slug (slug),
      KEY min_points (min_points)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    mysqli_query(db(), "CREATE TABLE IF NOT EXISTS pd_points_log (
      id int(11) NOT NULL AUTO_INCREMENT,
      user_id int(11) NOT NULL DEFAULT '0',
      delta int(11) NOT NULL DEFAULT '0',
      balance int(11) NOT NULL DEFAULT '0',
      reason varchar(40) NOT NULL DEFAULT '',
      ref_type varchar(20) NOT NULL DEFAULT '',
      ref_id int(11) NOT NULL DEFAULT '0',
      note varchar(255) NOT NULL DEFAULT '',
      operator_id int(11) NOT NULL DEFAULT '0',
      created_at datetime NOT NULL,
      PRIMARY KEY (id),
      KEY user_id (user_id),
      KEY created_at (created_at),
      KEY reason (reason)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    if (!pd_table_has_column('pd_users', 'points')) {
        mysqli_query(db(), "ALTER TABLE pd_users ADD points int(11) NOT NULL DEFAULT '0' AFTER reply_count");
        mysqli_query(db(), "UPDATE pd_users u SET u.points = u.reply_count * 3 + IFNULL((SELECT COUNT(*) FROM pd_threads t WHERE t.user_id = u.id AND t.is_deleted = 0), 0) * 10");
    }
    if (!pd_table_has_column('pd_users', 'group_id')) {
        mysqli_query(db(), "ALTER TABLE pd_users ADD group_id int(11) NOT NULL DEFAULT '0' AFTER points");
    }

    $cnt_rs = mysqli_query(db(), "SELECT COUNT(*) AS c FROM pd_user_groups");
    $cnt_row = $cnt_rs ? mysqli_fetch_assoc($cnt_rs) : null;
    if (!$cnt_row || intval($cnt_row['c']) === 0) {
        $defaults = array(
            array('name' => '新手', 'slug' => 'newbie', 'color' => '#8a9099', 'min_points' => 0, 'order' => 10),
            array('name' => '活跃会员', 'slug' => 'member', 'color' => '#505b93', 'min_points' => 100, 'order' => 20),
            array('name' => '资深会员', 'slug' => 'senior', 'color' => '#2f7d5a', 'min_points' => 500, 'order' => 30),
            array('name' => '核心贡献者', 'slug' => 'core', 'color' => '#c26a00', 'min_points' => 2000, 'order' => 40),
            array('name' => '社区元老', 'slug' => 'elder', 'color' => '#b54747', 'min_points' => 6000, 'order' => 50),
        );
        foreach ($defaults as $g) {
            $name = esc($g['name']);
            $slug = esc($g['slug']);
            $color = esc($g['color']);
            $min = intval($g['min_points']);
            $order = intval($g['order']);
            mysqli_query(db(), "INSERT INTO pd_user_groups (name,slug,color,min_points,is_system,display_order,created_at) VALUES ('{$name}','{$slug}','{$color}',{$min},1,{$order},NOW())");
        }
    }

    if (pd_setting('points_thread', '') === '') pd_update_setting('points_thread', '10');
    if (pd_setting('points_reply', '') === '') pd_update_setting('points_reply', '3');
    if (pd_setting('points_floor_reply', '') === '') pd_update_setting('points_floor_reply', '1');
    if (pd_setting('points_good_bonus', '') === '') pd_update_setting('points_good_bonus', '20');
    if (pd_setting('level_thresholds', '') === '') {
        pd_update_setting('level_thresholds', "1:0\n2:30\n3:100\n4:250\n5:500\n6:1000\n7:2000\n8:3500\n9:6000\n10:10000");
    }
    if (pd_setting('level_names', '') === '') {
        pd_update_setting('level_names', "1:新手\n2:入门\n3:熟练\n4:进阶\n5:老手\n6:达人\n7:精英\n8:大神\n9:宗师\n10:传奇");
    }

    mysqli_query(db(), "UPDATE pd_users u
        LEFT JOIN pd_user_groups g ON g.id = (
            SELECT g2.id FROM pd_user_groups g2 WHERE g2.min_points <= u.points ORDER BY g2.min_points DESC, g2.display_order ASC, g2.id ASC LIMIT 1
        )
        SET u.group_id = IFNULL(g.id, 0)
        WHERE u.group_id = 0");
    return true;
}

function pd_migrate_schema_prefix_from_qf() {
    static $ran = false;
    if ($ran) {
        return true;
    }
    $ran = true;
    if (pd_setting('schema_prefix_pd') === '1') {
        return true;
    }
    $conn = db();
    $tables = array(
        'users', 'passkeys', 'forums', 'threads', 'thread_votes', 'thread_reactions',
        'posts', 'post_votes', 'bans', 'security_logs', 'moderator_logs', 'moderator_forums',
        'attachments', 'post_comments', 'notifications', 'settings', 'signins', 'ads', 'navs',
        'invites', 'oauth', 'user_groups', 'points_log', 'pm_threads', 'pm_messages',
        'online', 'online_daily',
    );
    $renamed = false;
    foreach ($tables as $name) {
        $from = 'qf_' . $name;
        $to = 'pd_' . $name;
        $rs = mysqli_query($conn, "SHOW TABLES LIKE '{$from}'");
        if (!$rs || mysqli_num_rows($rs) === 0) {
            continue;
        }
        $rs2 = mysqli_query($conn, "SHOW TABLES LIKE '{$to}'");
        if ($rs2 && mysqli_num_rows($rs2) > 0) {
            continue;
        }
        if (mysqli_query($conn, "RENAME TABLE `{$from}` TO `{$to}`")) {
            $renamed = true;
        }
    }
    if ($renamed || mysqli_num_rows(mysqli_query($conn, "SHOW TABLES LIKE 'pd_settings'")) > 0) {
        pd_update_setting('schema_prefix_pd', '1');
    }
    return true;
}

function pd_migrate_forum_nav_plan_a() {
    static $ran = false;
    if ($ran) {
        return true;
    }
    $ran = true;
    if (pd_setting('forum_nav_plan_a') === '1') {
        return true;
    }
    $table = mysqli_query(db(), "SHOW TABLES LIKE 'pd_forums'");
    if (!$table || mysqli_num_rows($table) === 0) {
        return false;
    }

    $forum_id = function ($name) {
        $name_sql = esc($name);
        $rs = mysqli_query(db(), "SELECT id FROM pd_forums WHERE name='{$name_sql}' LIMIT 1");
        $row = $rs ? mysqli_fetch_assoc($rs) : null;
        return $row ? intval($row['id']) : 0;
    };

    $move_threads = function ($from_id, $to_id) {
        $from_id = intval($from_id);
        $to_id = intval($to_id);
        if ($from_id < 1 || $to_id < 1 || $from_id === $to_id) {
            return;
        }
        mysqli_query(db(), "UPDATE pd_threads SET forum_id={$to_id} WHERE forum_id={$from_id} AND is_deleted=0");
    };

    $delete_forum = function ($id) {
        $id = intval($id);
        if ($id < 1) {
            return;
        }
        mysqli_query(db(), "DELETE FROM pd_moderator_forums WHERE forum_id={$id}");
        mysqli_query(db(), "DELETE FROM pd_forums WHERE id={$id}");
    };

    $perf_id = $forum_id('性能优化');
    if ($perf_id > 0) {
        mysqli_query(db(), "UPDATE pd_forums SET name='数据库与缓存', description='MySQL、MariaDB、PostgreSQL、Redis、队列和缓存设计。', display_order=40 WHERE id={$perf_id}");
    }

    $chat_id = $forum_id('综合交流');
    if ($chat_id < 1) {
        $chat_id = $forum_id('灌水闲聊');
    }
    if ($chat_id > 0) {
        mysqli_query(db(), "UPDATE pd_forums SET name='综合闲聊', description='日常灌水、闲聊与技术之外的话题讨论。', display_order=70 WHERE id={$chat_id}");
    }

    $release_id = $forum_id('程序发布');
    $other_chat_id = $forum_id('灌水闲聊');
    if ($other_chat_id > 0 && $other_chat_id !== $chat_id) {
        if ($chat_id > 0) {
            $move_threads($other_chat_id, $chat_id);
        }
        $delete_forum($other_chat_id);
    }

    foreach (array('作品展示', '个站展示') as $name) {
        $fid = $forum_id($name);
        if ($fid > 0) {
            if ($release_id > 0) {
                $move_threads($fid, $release_id);
            }
            $delete_forum($fid);
        }
    }

    $orders = array(
        '技术问答' => 10,
        '框架生态' => 20,
        '程序发布' => 30,
        '数据库与缓存' => 40,
        '部署运维' => 50,
        '安全审计' => 60,
        '综合闲聊' => 70,
        '站务公告' => 200,
    );
    foreach ($orders as $name => $order) {
        $fid = $forum_id($name);
        if ($fid > 0) {
            mysqli_query(db(), "UPDATE pd_forums SET display_order=" . intval($order) . " WHERE id={$fid}");
        }
    }

    if ($release_id > 0) {
        $cats = esc("开源项目\n商业程序\n插件扩展\n个站展示\n版本更新");
        mysqli_query(db(), "UPDATE pd_forums SET topic_category_enabled=1, topic_categories='{$cats}' WHERE id={$release_id}");
    }

    $announce_id = $forum_id('站务公告');
    if ($announce_id > 0) {
        pd_update_setting('nav_hidden_forums', (string)$announce_id);
    }

    pd_update_setting('forum_nav_plan_a', '1');
    return true;
}

function pd_ensure_thread_vote_schema() {
    $threads = mysqli_query(db(), "SHOW TABLES LIKE 'pd_threads'");
    if (!$threads || mysqli_num_rows($threads) == 0) {
        return;
    }
    $check = mysqli_query(db(), "SHOW COLUMNS FROM pd_threads LIKE 'upvotes'");
    if ($check && mysqli_num_rows($check) == 0) {
        mysqli_query(db(), "ALTER TABLE pd_threads ADD upvotes int(11) NOT NULL DEFAULT '0' AFTER replies");
    }
    $check = mysqli_query(db(), "SHOW COLUMNS FROM pd_threads LIKE 'downvotes'");
    if ($check && mysqli_num_rows($check) == 0) {
        mysqli_query(db(), "ALTER TABLE pd_threads ADD downvotes int(11) NOT NULL DEFAULT '0' AFTER upvotes");
    }
    mysqli_query(db(), "CREATE TABLE IF NOT EXISTS pd_thread_votes (
      id int(11) NOT NULL AUTO_INCREMENT,
      thread_id int(11) NOT NULL DEFAULT '0',
      user_id int(11) NOT NULL DEFAULT '0',
      vote tinyint(1) NOT NULL DEFAULT '0',
      created_at datetime NOT NULL,
      updated_at datetime NOT NULL,
      PRIMARY KEY (id),
      UNIQUE KEY thread_user (thread_id,user_id),
      KEY thread_vote (thread_id,vote),
      KEY user_id (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

// 评论（楼层回复）顶/踩：pd_posts 增加 upvotes/downvotes 列 + pd_post_votes 记录每人每评论投票。
function pd_ensure_post_vote_schema() {
    $posts = mysqli_query(db(), "SHOW TABLES LIKE 'pd_posts'");
    if (!$posts || mysqli_num_rows($posts) == 0) {
        return;
    }
    $check = mysqli_query(db(), "SHOW COLUMNS FROM pd_posts LIKE 'upvotes'");
    if ($check && mysqli_num_rows($check) == 0) {
        mysqli_query(db(), "ALTER TABLE pd_posts ADD upvotes int(11) NOT NULL DEFAULT '0'");
    }
    $check = mysqli_query(db(), "SHOW COLUMNS FROM pd_posts LIKE 'downvotes'");
    if ($check && mysqli_num_rows($check) == 0) {
        mysqli_query(db(), "ALTER TABLE pd_posts ADD downvotes int(11) NOT NULL DEFAULT '0'");
    }
    mysqli_query(db(), "CREATE TABLE IF NOT EXISTS pd_post_votes (
      id int(11) NOT NULL AUTO_INCREMENT,
      post_id int(11) NOT NULL DEFAULT '0',
      user_id int(11) NOT NULL DEFAULT '0',
      vote tinyint(1) NOT NULL DEFAULT '0',
      created_at datetime NOT NULL,
      updated_at datetime NOT NULL,
      PRIMARY KEY (id),
      UNIQUE KEY post_user (post_id,user_id),
      KEY post_vote (post_id,vote),
      KEY user_id (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function pd_ensure_thread_reaction_schema() {
    $threads = mysqli_query(db(), "SHOW TABLES LIKE 'pd_threads'");
    if (!$threads || mysqli_num_rows($threads) == 0) {
        return;
    }
    mysqli_query(db(), "CREATE TABLE IF NOT EXISTS pd_thread_reactions (
      id int(11) NOT NULL AUTO_INCREMENT,
      thread_id int(11) NOT NULL DEFAULT '0',
      user_id int(11) NOT NULL DEFAULT '0',
      reaction varchar(20) NOT NULL DEFAULT '',
      created_at datetime NOT NULL,
      updated_at datetime NOT NULL,
      PRIMARY KEY (id),
      UNIQUE KEY thread_user (thread_id,user_id),
      KEY thread_reaction (thread_id,reaction),
      KEY user_id (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function pd_migrate_attachment_to_protected_storage($att) {
    if (!$att || !in_array(strtolower($att['file_ext']), array('zip', 'rar'))) {
        return $att;
    }
    $path = (string)$att['file_path'];
    if (strpos($path, 'uploads/protected/') === 0 || preg_match('/^https?:\/\//i', $path)) {
        return $att;
    }
    $base_dir = realpath(PD_ROOT . '/uploads');
    $old_file = realpath(PD_ROOT . '/' . ltrim($path, '/'));
    if (!$base_dir || !$old_file || strpos($old_file, $base_dir . DIRECTORY_SEPARATOR) !== 0 || !is_file($old_file)) {
        return $att;
    }
    list($target, $relative) = pd_protected_attachment_path($att['file_ext']);
    if (!rename($old_file, $target)) {
        if (!copy($old_file, $target)) {
            return $att;
        }
        @unlink($old_file);
    }
    $relative_sql = esc($relative);
    mysqli_query(db(), "UPDATE pd_attachments SET file_path='{$relative_sql}' WHERE id=" . intval($att['id']));
    $att['file_path'] = $relative;
    return $att;
}

function pd_ensure_forum_nav_schema() {
    static $done = false;
    if ($done) {
        return true;
    }
    $done = true;
    if (!pd_table_has_column('pd_forums', 'show_in_nav')) {
        mysqli_query(db(), "ALTER TABLE pd_forums ADD show_in_nav tinyint(1) NOT NULL DEFAULT 1 AFTER display_order");
        $hidden = array_filter(array_map('intval', explode(',', pd_setting('nav_hidden_forums', ''))));
        if (!empty($hidden)) {
            $ids = implode(',', $hidden);
            mysqli_query(db(), "UPDATE pd_forums SET show_in_nav=0 WHERE id IN ({$ids})");
        }
    }
    return true;
}

function pd_table_has_column($table, $column) {
    $table = preg_replace('/[^a-z0-9_]/i', '', (string)$table);
    $column_sql = esc((string)$column);
    $rs = mysqli_query(db(), "SHOW COLUMNS FROM {$table} LIKE '{$column_sql}'");
    return $rs && mysqli_num_rows($rs) > 0;
}

function pd_ensure_pm_schema() {
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;
    mysqli_query(db(), "CREATE TABLE IF NOT EXISTS pd_pm_threads (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        user1_id INT UNSIGNED NOT NULL,
        user2_id INT UNSIGNED NOT NULL,
        last_message_id INT UNSIGNED NOT NULL DEFAULT 0,
        updated_at DATETIME NOT NULL,
        user1_hidden TINYINT(1) NOT NULL DEFAULT 0,
        user2_hidden TINYINT(1) NOT NULL DEFAULT 0,
        PRIMARY KEY (id),
        UNIQUE KEY uk_users (user1_id, user2_id),
        KEY idx_user1_updated (user1_id, updated_at),
        KEY idx_user2_updated (user2_id, updated_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    mysqli_query(db(), "CREATE TABLE IF NOT EXISTS pd_pm_messages (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        thread_id INT UNSIGNED NOT NULL,
        sender_id INT UNSIGNED NOT NULL,
        recipient_id INT UNSIGNED NOT NULL,
        body TEXT NOT NULL,
        is_read TINYINT(1) NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL,
        PRIMARY KEY (id),
        KEY idx_thread_id (thread_id, id),
        KEY idx_recipient_unread (recipient_id, is_read)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function pd_ensure_account_auth_schema() {
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;
    $check = mysqli_query(db(), "SHOW COLUMNS FROM pd_users LIKE 'email'");
    if ($check && mysqli_num_rows($check) == 0) {
        mysqli_query(db(), "ALTER TABLE pd_users ADD email varchar(190) NOT NULL DEFAULT '' AFTER nickname");
    }
    $check = mysqli_query(db(), "SHOW COLUMNS FROM pd_users LIKE 'email_bound_at'");
    if ($check && mysqli_num_rows($check) == 0) {
        mysqli_query(db(), "ALTER TABLE pd_users ADD email_bound_at datetime DEFAULT NULL AFTER email");
    }
    $check = mysqli_query(db(), "SHOW COLUMNS FROM pd_users LIKE 'timezone'");
    if ($check && mysqli_num_rows($check) == 0) {
        mysqli_query(db(), "ALTER TABLE pd_users ADD timezone varchar(64) NOT NULL DEFAULT ''");
    }
    mysqli_query(db(), "CREATE TABLE IF NOT EXISTS pd_passkeys (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function pd_require_invite() {
    return pd_invite_table_ready() && intval(pd_setting('require_invite', '0')) === 1;
}

function pd_ensure_online_schema() {
    static $done = false;
    if ($done) {
        return true;
    }
    $done = true;

    mysqli_query(db(), "CREATE TABLE IF NOT EXISTS pd_online (
      session_id varchar(64) NOT NULL DEFAULT '',
      user_id int(11) NOT NULL DEFAULT '0',
      ip varchar(45) NOT NULL DEFAULT '',
      user_agent varchar(255) NOT NULL DEFAULT '',
      last_seen datetime NOT NULL,
      PRIMARY KEY (session_id),
      KEY last_seen (last_seen),
      KEY user_id (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    mysqli_query(db(), "CREATE TABLE IF NOT EXISTS pd_online_daily (
      day_date date NOT NULL,
      peak_total int(11) NOT NULL DEFAULT '0',
      peak_members int(11) NOT NULL DEFAULT '0',
      peak_guests int(11) NOT NULL DEFAULT '0',
      peak_at datetime DEFAULT NULL,
      updated_at datetime NOT NULL,
      PRIMARY KEY (day_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    return true;
}

function pd_ensure_timezone_schema() {
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;
    pd_ensure_account_auth_schema();
    pd_ensure_points_schema();
    pd_ensure_online_schema();
    pd_ensure_pm_schema();
    pd_migrate_storage_to_utc();
}

function pd_migrate_storage_to_utc() {
    if (intval(pd_setting('data_timezone_utc_migrated', '0')) === 1) {
        return;
    }
    $migrations = array(
        'pd_users' => array('created_at', 'mute_until', 'email_bound_at'),
        'pd_passkeys' => array('created_at', 'last_used_at'),
        'pd_forums' => array('created_at'),
        'pd_threads' => array('created_at', 'updated_at'),
        'pd_thread_votes' => array('created_at', 'updated_at'),
        'pd_thread_reactions' => array('created_at', 'updated_at'),
        'pd_posts' => array('created_at'),
        'pd_post_votes' => array('created_at', 'updated_at'),
        'pd_bans' => array('expires_at', 'created_at'),
        'pd_security_logs' => array('created_at'),
        'pd_moderator_logs' => array('created_at'),
        'pd_moderator_forums' => array('created_at'),
        'pd_attachments' => array('created_at'),
        'pd_post_comments' => array('created_at'),
        'pd_notifications' => array('created_at'),
        'pd_signins' => array('created_at'),
        'pd_ads' => array('updated_at'),
        'pd_navs' => array('created_at'),
        'pd_invites' => array('used_at', 'expires_at', 'created_at'),
        'pd_oauth' => array('created_at'),
    );
    foreach ($migrations as $table => $cols) {
        $table_check = mysqli_query(db(), "SHOW TABLES LIKE '" . esc($table) . "'");
        if (!$table_check || mysqli_num_rows($table_check) === 0) {
            continue;
        }
        foreach ($cols as $col) {
            $col_check = mysqli_query(db(), "SHOW COLUMNS FROM `{$table}` LIKE '" . esc($col) . "'");
            if (!$col_check || mysqli_num_rows($col_check) === 0) {
                continue;
            }
            mysqli_query(db(), "UPDATE `{$table}` SET `{$col}` = DATE_SUB(`{$col}`, INTERVAL 8 HOUR) WHERE `{$col}` IS NOT NULL AND `{$col}` > '0000-00-00 00:00:00'");
        }
    }
    pd_update_setting('data_timezone_utc_migrated', '1');
}
