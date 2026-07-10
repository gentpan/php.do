<?php
/* core/content.php — 由 functions.php 自动切分。集中 25 个定义。 */

function pd_forum_slug_map() {
    return array(
        '站务公告' => 'announcements',
        '技术问答' => 'qa',
        '框架生态' => 'frameworks',
        '程序发布' => 'release',
        '数据库与缓存' => 'database',
        '部署运维' => 'ops',
        '安全审计' => 'security',
        '综合闲聊' => 'chat',
    );
}

// 版块 → FontAwesome 图标类名映射（按 slug 匹配，未知版块用兜底图标）
function pd_forum_icon_map() {
    return array(
        'announcements' => 'fa-bullhorn',
        'qa'            => 'fa-circle-question',
        'frameworks'    => 'fa-cubes',
        'release'       => 'fa-rocket',
        'database'      => 'fa-database',
        'ops'           => 'fa-server',
        'security'      => 'fa-shield-halved',
        'chat'          => 'fa-comments',
    );
}

// 返回版块图标完整类名，如 "fa-solid fa-database"。$forum 可为版块数组或版块名。
function pd_forum_icon($forum) {
    $name = is_array($forum) ? (isset($forum['name']) ? (string)$forum['name'] : '') : (string)$forum;
    $slug_map = pd_forum_slug_map();
    $slug = isset($slug_map[$name]) ? $slug_map[$name] : '';
    $icons = pd_forum_icon_map();
    $icon = ($slug !== '' && isset($icons[$slug])) ? $icons[$slug] : 'fa-hashtag';
    return 'fa-solid ' . $icon;
}

function pd_forum_slug_by_id($id) {
    static $cache = array();
    $id = intval($id);
    if ($id < 1) {
        return '';
    }
    if (isset($cache[$id])) {
        return $cache[$id];
    }
    $rs = mysqli_query(db(), "SELECT name FROM pd_forums WHERE id={$id} LIMIT 1");
    $row = $rs ? mysqli_fetch_assoc($rs) : null;
    $map = pd_forum_slug_map();
    $cache[$id] = ($row && isset($map[$row['name']])) ? $map[$row['name']] : '';
    return $cache[$id];
}

function pd_forum_id_by_slug($slug) {
    static $cache = array();
    $slug = strtolower(trim((string)$slug, '/'));
    if ($slug === '') {
        return 0;
    }
    if (isset($cache[$slug])) {
        return $cache[$slug];
    }
    $name = '';
    foreach (pd_forum_slug_map() as $forum_name => $forum_slug) {
        if ($forum_slug === $slug) {
            $name = $forum_name;
            break;
        }
    }
    if ($name === '') {
        $cache[$slug] = 0;
        return 0;
    }
    $name_sql = esc($name);
    $rs = mysqli_query(db(), "SELECT id FROM pd_forums WHERE name='{$name_sql}' LIMIT 1");
    $row = $rs ? mysqli_fetch_assoc($rs) : null;
    $cache[$slug] = $row ? intval($row['id']) : 0;
    return $cache[$slug];
}

function pd_community_stats() {
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    $cache = array(
        'members'      => count_rows("SELECT COUNT(*) FROM pd_users"),
        'admins'       => count_rows("SELECT COUNT(*) FROM pd_users WHERE is_admin=1"),
        'moderators'   => count_rows("SELECT COUNT(*) FROM pd_users WHERE is_moderator=1 AND is_admin=0"),
        'topics_7d'    => count_rows("SELECT COUNT(*) FROM pd_threads WHERE is_deleted=0 AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"),
        'posts_today'  => count_rows("SELECT COUNT(*) FROM pd_posts WHERE is_deleted=0 AND created_at >= CURDATE()"),
        'active_7d'    => count_rows("SELECT COUNT(DISTINCT user_id) FROM (SELECT user_id, created_at FROM pd_threads WHERE is_deleted=0 UNION ALL SELECT user_id, created_at FROM pd_posts WHERE is_deleted=0) x WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"),
        'registers_7d' => count_rows("SELECT COUNT(*) FROM pd_users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"),
        'likes_total'  => count_rows("SELECT COALESCE(SUM(upvotes),0) FROM pd_threads WHERE is_deleted=0"),
        'posts_total'  => count_rows("SELECT COUNT(*) FROM pd_posts WHERE is_deleted=0"),
    );
    return $cache;
}

// 首页可选 banner 列表：扫描 assets/banner/ 下以数字命名（1,2,3…）的图，优先 webp。返回 [ key => 相对路径 ]。
function pd_home_banner_options() {
    $dir = 'assets/banner/';
    $base = PD_ROOT . '/' . $dir;
    $options = array();
    for ($i = 1; $i <= 30; $i++) {
        foreach (array($i . '.webp', $i . '.png', $i . '.jpg', $i . '.jpeg') as $f) {
            if (file_exists($base . $f)) {
                $options[(string)$i] = $dir . $f;
                break;
            }
        }
    }
    return $options;
}

// 首页当前选定的 banner 路径（后台可切换）。无匹配时回退第一张，全无则 ''。
function pd_home_banner_src() {
    $options = pd_home_banner_options();
    if (empty($options)) {
        return '';
    }
    $selected = (string)pd_setting('home_banner', '1');
    if (isset($options[$selected])) {
        return $options[$selected];
    }
    return reset($options);
}

// 帖子列表/详情：置顶与精华标题 class、徽章 HTML（全站 is_top=1 / 版块 is_top=2）
function pd_thread_title_classes($row) {
    $classes = array();
    $is_top = intval(isset($row['is_top']) ? $row['is_top'] : 0);
    if ($is_top === 1) {
        $classes[] = 'pd-title-top';
        $classes[] = 'pd-title-top-global';
    } elseif ($is_top === 2) {
        $classes[] = 'pd-title-top';
        $classes[] = 'pd-title-top-board';
    }
    if (intval(isset($row['is_good']) ? $row['is_good'] : 0)) {
        $classes[] = 'pd-title-good';
    }
    return implode(' ', $classes);
}

function pd_thread_title_attr($row, $base_classes = '') {
    $classes = trim((string)$base_classes);
    $extra = pd_thread_title_classes($row);
    if ($extra !== '') {
        $classes = $classes === '' ? $extra : $classes . ' ' . $extra;
    }
    return $classes === '' ? '' : ' class="' . h($classes) . '"';
}

function pd_thread_top_badge_html($row) {
    $is_top = intval(isset($row['is_top']) ? $row['is_top'] : 0);
    if ($is_top === 1) {
        return '<span class="pd-badge-sq pd-badge-top pd-badge-top-global" title="全站置顶" aria-label="全站置顶"><i class="fa-solid fa-up-long" aria-hidden="true"></i></span>';
    }
    if ($is_top === 2) {
        return '<span class="pd-badge-sq pd-badge-top pd-badge-top-board" title="版块置顶" aria-label="版块置顶"><i class="fa-solid fa-thumbtack" aria-hidden="true"></i></span>';
    }
    return '';
}

function pd_thread_good_badge_html($row) {
    if (!intval(isset($row['is_good']) ? $row['is_good'] : 0)) {
        return '';
    }
    return '<span class="pd-badge-sq pd-badge-good" title="精华" aria-label="精华"><i class="fa-solid fa-star" aria-hidden="true"></i></span>';
}

// 帖子表情反应：5 种类型（key => emoji + 标签）。每人每帖只能选 1 种。
function pd_reaction_types() {
    return array(
        'like'       => array('emoji' => '👍',   'label' => 'Like'),
        'cheer'      => array('emoji' => '👏🏻', 'label' => 'Cheer'),
        'celebrate'  => array('emoji' => '🎉',   'label' => 'Celebrate'),
        'appreciate' => array('emoji' => '✨',   'label' => 'Appreciate'),
        'smile'      => array('emoji' => '🙂',   'label' => 'Smile'),
    );
}

function pd_thread_reaction_counts($thread_id) {
    $thread_id = intval($thread_id);
    $counts = array();
    foreach (pd_reaction_types() as $key => $info) {
        $counts[$key] = 0;
    }
    $rs = mysqli_query(db(), "SELECT reaction, COUNT(*) AS c FROM pd_thread_reactions WHERE thread_id={$thread_id} GROUP BY reaction");
    while ($rs && ($row = mysqli_fetch_assoc($rs))) {
        $k = (string)$row['reaction'];
        if (array_key_exists($k, $counts)) {
            $counts[$k] = intval($row['c']);
        }
    }
    return $counts;
}

// 生成帖子面包屑右侧的管理/版主工具栏内层 HTML（供 thread.php 首次渲染与 AJAX 局部刷新复用）
function pd_thread_admin_tools_html($thread) {
    $tid = intval($thread['id']);
    $out = '';
    if (is_admin()) {
        $is_top = intval(isset($thread['is_top']) ? $thread['is_top'] : 0);
        $is_good = intval(isset($thread['is_good']) ? $thread['is_good'] : 0);
        $out .= pd_ip_badge_html(isset($thread['ip']) ? $thread['ip'] : '');
        $out .= pd_action_badge(pd_url_page('edit_thread.php', array('id' => $tid)), '编辑', 'fa-solid fa-pen-to-square', 'action-badge-edit');
        $out .= pd_action_badge(pd_url_page('move_thread.php', array('id' => $tid)), '移动', 'fa-solid fa-arrow-right-arrow-left', 'action-badge-move');
        $out .= pd_action_badge(pd_url_page('admin/action.php', array('action' => 'top_board', 'id' => $tid, 'token' => pd_action_token('top_board', $tid))), '本版块置顶', 'fa-solid fa-thumbtack', 'action-badge-pin' . ($is_top === 2 ? ' is-active' : ''), 'data-ajax="1"');
        $out .= pd_action_badge(pd_url_page('admin/action.php', array('action' => 'top_global', 'id' => $tid, 'token' => pd_action_token('top_global', $tid))), '全站置顶', 'fa-solid fa-up-long', 'action-badge-pin' . ($is_top === 1 ? ' is-active' : ''), 'data-ajax="1"');
        if ($is_top > 0) {
            $out .= pd_action_badge(pd_url_page('admin/action.php', array('action' => 'cancel_top', 'id' => $tid, 'token' => pd_action_token('cancel_top', $tid))), '取消置顶', 'fa-solid fa-ban', 'action-badge-muted', 'data-ajax="1"');
        }
        $out .= pd_action_badge(pd_url_page('admin/action.php', array('action' => 'good', 'id' => $tid, 'token' => pd_action_token('good', $tid))), $is_good ? '取消加精' : '加精', $is_good ? 'fa-solid fa-star' : 'fa-regular fa-star', 'action-badge-feature' . ($is_good ? ' is-active' : ''), 'data-ajax="1"');
        $out .= pd_action_badge(pd_url_page('admin/action.php', array('action' => 'del_thread', 'id' => $tid, 'token' => pd_action_token('del_thread', $tid))), '删除', 'fa-solid fa-trash-can', 'action-badge-danger', 'data-confirm="确定删除？" data-ajax="1"');
    } elseif (pd_can_moderator_delete_thread(current_user(), $thread)) {
        $out .= pd_action_badge(pd_url_page('moderator_action.php', array('action' => 'del_thread', 'id' => $tid, 'token' => pd_action_token('mod_del_thread', $tid))), '版主删除', 'fa-solid fa-trash-can', 'action-badge-danger', 'data-confirm="确定删除该主题？" data-ajax="1"');
    }
    return $out;
}

function pd_home_threads_limit() {
    return pd_setting_int('home_threads_per_page', 12, 1, 100);
}

function pd_forum_threads_limit() {
    return pd_setting_int('forum_threads_per_page', 60, 1, 200);
}

function pd_thread_page_chars() {
    return pd_setting_int('thread_page_chars', 4000, 500, 50000);
}

function pd_reply_max_chars() {
    return pd_setting_int('reply_max_chars', 1000, 100, 50000);
}

function pd_forum_info($forum_id) {
    static $cache = array();
    $forum_id = intval($forum_id);
    if ($forum_id < 1) {
        return null;
    }
    if (!isset($cache[$forum_id])) {
        $rs = mysqli_query(db(), "SELECT * FROM pd_forums WHERE id={$forum_id} LIMIT 1");
        $cache[$forum_id] = $rs ? mysqli_fetch_assoc($rs) : null;
    }
    return $cache[$forum_id];
}

function pd_topic_category_enabled($forum_id) {
    $forum = pd_forum_info($forum_id);
    return $forum && intval($forum['topic_category_enabled']) === 1;
}

function pd_topic_categories($forum_id) {
    $forum = pd_forum_info($forum_id);
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

function pd_forum_post_allowed($forum_id, $user_id) {
    $forum = pd_forum_info($forum_id);
    if (!$forum) {
        return false;
    }
    if (intval($forum['post_user_limit_enabled']) !== 1) {
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
