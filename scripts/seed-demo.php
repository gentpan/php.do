<?php
require_once __DIR__ . '/../functions.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit('404 Not Found');
}

$avatar_dir = __DIR__ . '/../assets/avatars';
if (!is_dir($avatar_dir)) {
    mkdir($avatar_dir, 0755, true);
}

function seed_pick($items, $index) {
    return $items[$index % count($items)];
}

function seed_sql($value) {
    return esc($value);
}

function seed_make_avatar($path, $seed, $label) {
    $palettes = array(
        array('#0052D9', '#7fb2ff', '#ffffff'),
        array('#e65a4c', '#ffd6c9', '#ffffff'),
        array('#111827', '#8db7ff', '#ffffff'),
        array('#0f766e', '#99f6e4', '#ffffff'),
        array('#7c3aed', '#ddd6fe', '#ffffff'),
        array('#ca8a04', '#fef3c7', '#ffffff'),
    );
    $palette = $palettes[$seed % count($palettes)];
    $face_x = 36 + ($seed * 7) % 56;
    $face_y = 34 + ($seed * 11) % 44;
    $eye = 3 + ($seed % 3);
    $initial = function_exists('mb_substr') ? mb_substr($label, 0, 1, 'UTF-8') : substr($label, 0, 1);
    $svg = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
        . '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 128 128">'
        . '<rect width="128" height="128" rx="34" fill="' . $palette[0] . '"/>'
        . '<circle cx="' . $face_x . '" cy="' . $face_y . '" r="38" fill="' . $palette[1] . '" opacity=".9"/>'
        . '<circle cx="' . (128 - $face_x) . '" cy="' . (126 - $face_y) . '" r="42" fill="' . $palette[2] . '" opacity=".22"/>'
        . '<circle cx="48" cy="57" r="' . $eye . '" fill="#10234a"/>'
        . '<circle cx="80" cy="57" r="' . $eye . '" fill="#10234a"/>'
        . '<path d="M45 80c9 10 29 10 38 0" fill="none" stroke="#10234a" stroke-width="6" stroke-linecap="round"/>'
        . '<text x="64" y="112" text-anchor="middle" font-size="28" font-family="Arial, sans-serif" font-weight="800" fill="#fff">' . h($initial) . '</text>'
        . '</svg>';
    file_put_contents($path, $svg);
}

$forum_names = array(
    array('站务公告', '网站公告、规则和反馈'),
    array('闲聊灌水', '轻松交流，分享日常'),
    array('本地生活', '同城信息、吃喝玩乐和生活交流'),
    array('数码设备', '手机、电脑、外设和折腾记录'),
    array('开发技术', '代码、服务器、数据库和工程实践'),
    array('设计灵感', '界面、字体、配色和产品体验'),
    array('资源分享', '工具、素材、书单和链接收藏'),
    array('影音书房', '电影、音乐、播客和阅读'),
    array('游戏天地', '主机、手游、PC 和开黑交流'),
    array('AI 交流', '模型、提示词、自动化和新工具'),
    array('创业运营', '产品、增长、内容和商业化'),
    array('旅行见闻', '路线、攻略、照片和城市记忆'),
    array('美食厨房', '餐厅、菜谱、咖啡和甜点'),
    array('运动健康', '跑步、健身、睡眠和身体状态'),
    array('二手交易', '闲置发布、求购和避坑提醒'),
    array('问答互助', '问题求助、经验答疑和快速反馈'),
);

$nick_prefixes = array('蓝桥', '小满', '星河', '云起', '松间', '南风', '白露', '青柠', '阿澈', '林深', '海盐', '北岛');
$nick_suffixes = array('同学', '旅人', '编辑', '观察员', '工程师', '设计师', '店长', '玩家', '船长', '茶客', '站友', '记录者');
$title_starts = array('今天发现一个', '关于', '有没有人试过', '记录一下', '分享一个', '想问问大家', '最近在看', '我把');
$title_topics = array('蓝色主题的细节', 'R2 上传配置', '手机拍照体验', '本地咖啡店', '周末短途路线', '轻量论坛设计', '中文字体搭配', 'AI 工作流', '服务器优化', '小众工具');
$content_parts = array(
    '这个帖子是演示数据，用来观察首页列表、版块页和详情页的真实排版。',
    '内容尽量模拟正常用户交流，有标题、有段落，也会带一点日常语气。',
    '如果你在后台清理数据，可以按用户名 blue_demo_ 或标题里的演示内容来识别。',
    '欢迎继续调整版块比例、字体和卡片间距，让站点看起来更接近真实社区。',
);
$reply_parts = array('赞同这个方向。', '这个信息很有用，收藏了。', '我也遇到过类似情况。', '期待后续更新。', '这个版块终于热闹起来了。', '可以再补充一点截图或步骤。');

$forums = array();
foreach ($forum_names as $i => $forum) {
    $name = seed_sql($forum[0]);
    $rs = mysqli_query(db(), "SELECT id FROM qf_forums WHERE name='{$name}' LIMIT 1");
    $row = $rs ? mysqli_fetch_assoc($rs) : null;
    if ($row) {
        $forums[] = intval($row['id']);
        continue;
    }
    $desc = seed_sql($forum[1]);
    $order = ($i + 1) * 10;
    mysqli_query(db(), "INSERT INTO qf_forums (name,description,display_order,created_at) VALUES ('{$name}','{$desc}',{$order},NOW())");
    $forums[] = intval(mysqli_insert_id(db()));
}

$users = array();
for ($i = 1; $i <= 48; $i++) {
    $username = 'blue_demo_' . str_pad((string)$i, 3, '0', STR_PAD_LEFT);
    $nickname = seed_pick($nick_prefixes, $i) . seed_pick($nick_suffixes, $i * 3);
    $avatar_file = 'demo-' . str_pad((string)$i, 2, '0', STR_PAD_LEFT) . '.svg';
    seed_make_avatar($avatar_dir . '/' . $avatar_file, $i, $nickname);
    $avatar = 'assets/avatars/' . $avatar_file;
    $username_sql = seed_sql($username);
    $rs = mysqli_query(db(), "SELECT id FROM qf_users WHERE username='{$username_sql}' LIMIT 1");
    $row = $rs ? mysqli_fetch_assoc($rs) : null;
    if (!$row) {
        $nickname_sql = seed_sql($nickname);
        $avatar_sql = seed_sql($avatar);
        $password_sql = seed_sql(qf_password_hash('demo123456'));
        $ip = '10.20.' . intval($i % 255) . '.' . intval(20 + $i);
        mysqli_query(db(), "INSERT INTO qf_users (username,password,nickname,avatar,signature,status,coins,reply_count,ip,created_at) VALUES ('{$username_sql}','{$password_sql}','{$nickname_sql}','{$avatar_sql}','这里是 Blue 演示用户',1," . mt_rand(0, 280) . ",0,'{$ip}',DATE_SUB(NOW(), INTERVAL " . mt_rand(3, 80) . " DAY))");
        $users[] = intval(mysqli_insert_id(db()));
    } else {
        $users[] = intval($row['id']);
    }
}

for ($i = 1; $i <= 160; $i++) {
    $title = seed_pick($title_starts, $i) . seed_pick($title_topics, $i * 5) . ' #' . str_pad((string)$i, 3, '0', STR_PAD_LEFT);
    $title_sql = seed_sql($title);
    $rs = mysqli_query(db(), "SELECT id FROM qf_threads WHERE title='{$title_sql}' LIMIT 1");
    $thread = $rs ? mysqli_fetch_assoc($rs) : null;
    if ($thread) {
        continue;
    }
    $forum_id = seed_pick($forums, $i);
    $user_id = seed_pick($users, $i * 7);
    $content = implode("\n\n", array(
        '<!--demo-seed-->',
        seed_pick($content_parts, $i),
        seed_pick($content_parts, $i + 1),
        '关键词：' . seed_pick($title_topics, $i * 2) . '，版块：' . seed_pick($forum_names, $i)[0] . '。'
    ));
    $content_sql = seed_sql($content);
    $views = mt_rand(18, 1800);
    $is_good = $i % 11 === 0 ? 1 : 0;
    $is_top = $i % 53 === 0 ? 1 : 0;
    $days = mt_rand(0, 45);
    $ip = '172.16.' . intval($i % 255) . '.' . intval(30 + ($i % 200));
    mysqli_query(db(), "INSERT INTO qf_threads (forum_id,user_id,title,content,views,replies,is_top,is_good,is_deleted,ip,created_at,updated_at) VALUES ({$forum_id},{$user_id},'{$title_sql}','{$content_sql}',{$views},0,{$is_top},{$is_good},0,'{$ip}',DATE_SUB(NOW(), INTERVAL {$days} DAY),DATE_SUB(NOW(), INTERVAL " . max(0, $days - mt_rand(0, 3)) . " DAY))");
    $thread_id = intval(mysqli_insert_id(db()));
    $reply_count = mt_rand(0, 8);
    for ($r = 1; $r <= $reply_count; $r++) {
        $reply_user = seed_pick($users, $i + $r * 9);
        $reply = seed_pick($reply_parts, $i + $r) . ' 演示回复 ' . $r . '。';
        $reply_sql = seed_sql($reply);
        mysqli_query(db(), "INSERT INTO qf_posts (thread_id,user_id,content,is_deleted,ip,created_at) VALUES ({$thread_id},{$reply_user},'{$reply_sql}',0,'{$ip}',DATE_SUB(NOW(), INTERVAL " . mt_rand(0, max(1, $days)) . " DAY))");
    }
    mysqli_query(db(), "UPDATE qf_threads SET replies={$reply_count} WHERE id={$thread_id}");
}

mysqli_query(db(), "UPDATE qf_users u SET reply_count=(SELECT COUNT(*) FROM qf_posts p WHERE p.user_id=u.id AND p.is_deleted=0) WHERE u.username LIKE 'blue_demo_%'");

$counts = array();
foreach (array('qf_forums', 'qf_users', 'qf_threads', 'qf_posts') as $table) {
    $rs = mysqli_query(db(), "SELECT COUNT(*) AS c FROM {$table}");
    $row = $rs ? mysqli_fetch_assoc($rs) : array('c' => 0);
    $counts[$table] = intval($row['c']);
}

echo "Demo seed complete\n";
echo "forums={$counts['qf_forums']} users={$counts['qf_users']} threads={$counts['qf_threads']} posts={$counts['qf_posts']}\n";
