<?php
/* core/render.php — 由 functions.php 自动切分。集中 11 个定义。 */

function pd_include_header($mode = false) {
    global $page_title;
    // 页头模式：
    //   false / 'full' —— 完整论坛头（banner+logo+论坛导航）
    //   true  / 'lite' —— 精简头：仅 banner+logo，无导航（登录/注册/关于等）
    //   'info'         —— 精简头 + 信息区子导航（信息页 page.php）
    if ($mode === true) { $mode = 'lite'; }
    if ($mode === false || $mode === '') { $mode = 'full'; }
    $pd_header_mode = (string) $mode;
    // lite 与 info 都走精简布局（加载 standalone.css、隐藏论坛导航、body.pd-standalone）
    $pd_lite_layout = ($pd_header_mode !== 'full');
    include pd_theme_file('header.php');
}

function pd_include_footer() {
    include pd_theme_file('footer.php');
}

function pd_asset_js($name, $base = 'assets/js/') {
    $name = trim((string)$name, '/');
    $base = rtrim((string)$base, '/') . '/';
    $source = $base . $name . '.js';
    $min = $base . $name . '.min.js';
    $host = strtolower(isset($_SERVER['HTTP_HOST']) ? preg_replace('/:.*/', '', $_SERVER['HTTP_HOST']) : '');
    $local_hosts = array('', 'localhost', '127.0.0.1', '::1', 'lume.test');
    $path = (!in_array($host, $local_hosts, true) && file_exists(PD_ROOT . '/' . $min)) ? $min : $source;
    $file = PD_ROOT . '/' . $path;
    $version = file_exists($file) ? filemtime($file) : time();
    return $path . '?v=' . $version;
}

/** 帖子列表行：variant=feed 首页；variant=list 版块/搜索/用户页 */
function pd_render_thread_row($t, $opts = array()) {
    $variant = isset($opts['variant']) ? $opts['variant'] : 'feed';
    $avatar = pd_user_avatar($t, 80);
    $author = pd_user_display_name($t);
    $is_new = pd_parse_utc_timestamp($t['created_at']) >= time() - 86400 * 7;
    $has_image = intval(isset($t['has_image']) ? $t['has_image'] : 0);
    $has_attachment = !empty($t['has_attachment']);

    if ($variant === 'feed') {
        ob_start();
        ?>
        <article class="pd-thread-row">
            <a class="pd-avatar" href="<?php echo h(pd_url_thread($t['id'])); ?>" aria-hidden="true" tabindex="-1">
                <img src="<?php echo h($avatar); ?>" alt="">
            </a>
            <div class="pd-thread-main">
                <h2>
                    <?php echo pd_thread_top_badge_html($t); ?>
                    <?php echo pd_thread_good_badge_html($t); ?>
                    <a href="<?php echo h(pd_url_thread($t['id'])); ?>"<?php echo pd_thread_title_attr($t); ?>><?php echo h($t['title']); ?></a>
                    <?php if ($has_image) { ?><i class="fa-regular fa-image pd-image-icon" aria-hidden="true"></i><?php } ?>
                    <?php if ($has_attachment) { ?><i class="fa-solid fa-paperclip pd-attach-icon" title="含附件" aria-label="含附件"></i><?php } ?>
                    <?php if ($is_new) { ?><i class="fa-solid fa-rectangle-new pd-new" title="新帖" aria-label="新帖"></i><?php } ?>
                </h2>
                <div class="pd-thread-meta">
                    <p>
                        <a class="pd-author-link" href="<?php echo h(pd_url_user($t['user_id'])); ?>"><?php echo h($author); ?></a>
                        <?php echo pd_time_html($t['created_at']); ?>
                        <?php if (!empty($t['forum_name'])) { ?><a class="pd-forum-tag pd-forum-tag-<?php echo intval($t['forum_id']) % 8; ?>" href="<?php echo h(pd_url_forum(intval($t['forum_id']))); ?>"><?php echo h($t['forum_name']); ?></a><?php } ?>
                    </p>
                    <div class="pd-thread-stats" aria-label="帖子统计">
                        <span><i class="fa-regular fa-eye" aria-hidden="true"></i><?php echo pd_format_compact_number($t['views']); ?></span>
                        <span><i class="fa-regular fa-comment-dots" aria-hidden="true"></i><?php echo pd_format_compact_number($t['replies']); ?></span>
                    </div>
                </div>
            </div>
        </article>
        <?php
        return ob_get_clean();
    }

    $meta = isset($opts['meta']) ? $opts['meta'] : 'forum';
    $show_counts = !isset($opts['counts']) || $opts['counts'];
    $avatar_tag = isset($opts['avatar_link']) && $opts['avatar_link'] === false
        ? '<span class="pd-avatar" aria-hidden="true"><img src="' . h($avatar) . '" alt=""></span>'
        : '<a class="pd-avatar" href="' . h(pd_url_thread($t['id'])) . '" aria-hidden="true" tabindex="-1"><img src="' . h($avatar) . '" alt=""></a>';

    ob_start();
    ?>
    <div class="thread-row">
        <?php echo $avatar_tag; ?>
        <div class="thread-main">
            <a<?php echo pd_thread_title_attr($t, 'thread-title'); ?> href="<?php echo h(pd_url_thread($t['id'])); ?>">
                <?php echo pd_thread_top_badge_html($t); ?>
                <?php echo pd_thread_good_badge_html($t); ?>
                <?php echo h($t['title']); ?>
            </a>
            <p>
                <?php if ($meta === 'search') { ?>
                    <a class="pd-author-link" href="<?php echo h(pd_url_user($t['user_id'])); ?>"><?php echo h($author); ?></a>
                    <span><?php echo h($t['forum_name']); ?></span>
                    <span><?php echo pd_time_html($t['updated_at']); ?></span>
                <?php } elseif ($meta === 'user') { ?>
                    <?php echo h($t['forum_name']); ?> · <?php echo pd_time_html($t['updated_at']); ?>
                <?php } else { ?>
                    <a class="pd-author-link" href="<?php echo h(pd_url_user($t['user_id'])); ?>"><?php echo h($author); ?></a> · 发表于 <?php echo pd_time_html($t['created_at']); ?> · 最后更新 <?php echo pd_time_html($t['updated_at']); ?>
                <?php } ?>
            </p>
        </div>
        <?php if ($show_counts) { ?>
        <div class="thread-count">
            <span><i class="fa-regular fa-comment-dots" aria-hidden="true"></i><?php echo pd_format_compact_number($t['replies']); ?></span>
            <span><i class="fa-regular fa-eye" aria-hidden="true"></i><?php echo pd_format_compact_number($t['views']); ?></span>
        </div>
        <?php } ?>
    </div>
    <?php
    return ob_get_clean();
}

/** 渲染附件列表（主楼/回复共用） */
function pd_render_attachment_list($attachments, $opts = array()) {
    if (!$attachments) {
        return '';
    }
    $rows = array();
    if ($attachments instanceof mysqli_result) {
        while ($row = mysqli_fetch_assoc($attachments)) {
            $rows[] = $row;
        }
    } elseif (is_array($attachments)) {
        $rows = $attachments;
    }
    if (empty($rows)) {
        return '';
    }
    $reply_class = !empty($opts['reply']) ? ' reply-attachments' : '';
    $show_heading = empty($opts['reply']);
    $guest_zip_blocked = !empty($opts['guest_zip_blocked']);
    $compressed_exts = array('zip', 'rar');
    $image_exts = array('jpg', 'jpeg', 'png', 'gif', 'webp');
    $dl_cost = pd_download_points_cost();
    $dl_viewer = current_user();
    $dl_viewer_id = $dl_viewer ? intval($dl_viewer['id']) : 0;
    $dl_viewer_admin = $dl_viewer && !empty($dl_viewer['is_admin']);
    ob_start();
    ?>
    <div class="attachment-list<?php echo h($reply_class); ?>">
        <?php if ($show_heading) { ?><h3>附件</h3><?php } ?>
        <?php foreach ($rows as $att) {
            $ext = strtolower($att['file_ext']);
            if (in_array($ext, $image_exts, true)) { ?>
                <a href="<?php echo h(pd_attachment_url($att['id'])); ?>" target="_blank">
                    <img class="attachment-img" src="<?php echo h(pd_attachment_url($att['id'])); ?>" alt="<?php echo h($att['original_name']); ?>">
                </a>
                <?php echo pd_attachment_delete_form($att); ?>
            <?php } else {
                $zip_blocked = $guest_zip_blocked && in_array($ext, $compressed_exts, true); ?>
                <a class="attachment-file" href="<?php echo h($zip_blocked ? pd_url_page('register.php') : pd_attachment_url($att['id'])); ?>" target="_blank" <?php if ($zip_blocked) echo pd_guest_download_confirm_onclick(); ?>>
                    <?php echo h($att['original_name']); ?> · <?php echo h(strtoupper($att['file_ext'])); ?> · <?php echo round(intval($att['file_size']) / 1024, 1); ?>KB · 下载次数 <?php echo intval(isset($att['download_count']) ? $att['download_count'] : 0); ?><?php if ($dl_cost > 0 && !$dl_viewer_admin && $dl_viewer_id !== intval($att['user_id'])) { ?> · <span class="attachment-price">首次下载需 <?php echo intval($dl_cost); ?> 积分</span><?php } ?>
                </a>
                <?php echo pd_attachment_delete_form($att);
            }
        } ?>
    </div>
    <?php
    return ob_get_clean();
}

function pd_render_captcha() {
    pd_prepare_form_guard();
    $hp = $_SESSION['pd_hp_field'];
    return '<div class="hp-field"><label>网址</label><input type="text" name="' . h($hp) . '" value=""></div>'
        . '<div class="captcha-box"><label>验证码</label><div class="captcha-row">'
        . '<input type="text" name="captcha_code" maxlength="4" autocomplete="off" required placeholder="4位字符">'
        . '<img src="api/captcha?t=' . time() . '" alt="验证码" data-captcha-refresh title="点击刷新">'
        . '</div></div>';
}

function pd_browser_title($page_title) {
    $site_name = pd_site_name();
    if ($page_title === SITE_NAME) {
        // 首页：站点名称 · 站点副标题（slogan）
        $subtitle = trim((string) pd_site_slogan());
        return $subtitle !== '' ? $site_name . ' · ' . $subtitle : $site_name;
    }
    return str_replace(SITE_NAME, $site_name, $page_title);
}

function pd_render_bbcode_content($content) {
    $html = nl2br(h($content));
    $html = preg_replace_callback('/\[file url=&quot;([^&]+)&quot; name=&quot;([^&]*)&quot; desc=&quot;([^&]*)&quot;\](.*?)\[\/file\]/is', function($m) {
        return pd_render_file_tag($m[1], $m[2], $m[3]);
    }, $html);
    $html = preg_replace_callback('/\[file url=&quot;([^&]+)&quot; name=&quot;([^&]*)&quot;\](.*?)\[\/file\]/is', function($m) {
        return pd_render_file_tag($m[1], $m[2], $m[3]);
    }, $html);
    $html = preg_replace_callback('/\[img\]((?:https?:\/\/|\/|uploads\/)[^\]\s]+)\[\/img\]/i', function($m) {
        $url = h($m[1]);
        return '<img class="remote-img" src="' . $url . '" alt="远程图片">';
    }, $html);
    $html = preg_replace_callback('/\[url=(&quot;)?((?:https?:\/\/)[^&\]\s]+)(&quot;)?\](.*?)\[\/url\]/is', function($m) {
        return pd_render_url_tag($m[2], $m[4]);
    }, $html);
    $html = preg_replace('/\[b\](.*?)\[\/b\]/is', '<strong>$1</strong>', $html);
    $html = preg_replace('/\[size=([0-9]{1,2})\](.*?)\[\/size\]/is', '<span style="font-size:$1px">$2</span>', $html);
    $html = preg_replace('/\[font=([^\]]{1,20})\](.*?)\[\/font\]/is', '<span style="font-family:$1">$2</span>', $html);
    return $html;
}

function pd_render_content($content) {
    if (pd_content_looks_like_bbcode($content)) {
        return pd_render_bbcode_content($content);
    }
    return pd_markdown($content);
}

function pd_render_url_tag($url, $text) {
    $safe_url = h(html_entity_decode($url, ENT_QUOTES, 'UTF-8'));
    if (!preg_match('/^https?:\/\//i', $safe_url)) {
        return h($text);
    }
    $safe_text = trim($text) !== '' ? $text : $safe_url;
    return '<a class="content-link" href="' . $safe_url . '" target="_blank" rel="nofollow noopener">' . $safe_text . '</a>';
}

function pd_render_file_tag($url, $name, $description) {
    $safe_url = h(html_entity_decode($url, ENT_QUOTES, 'UTF-8'));
    $safe_name = h(html_entity_decode($name, ENT_QUOTES, 'UTF-8'));
    $safe_description = h(trim(html_entity_decode($description, ENT_QUOTES, 'UTF-8')));
    $title = $safe_description !== '' ? $safe_description : $safe_name;
    $download_count = 0;
    $delete_form = '';
    $link_attr = '';
    $raw_url = html_entity_decode($url, ENT_QUOTES, 'UTF-8');
    $att = pd_resolve_attachment_from_url($raw_url);
    if ($att) {
        $safe_url = h(pd_attachment_url($att['id']));
        $download_count = intval($att['download_count']);
        $delete_form = pd_attachment_delete_form($att);
        if (!current_user() && !pd_guest_download_allowed() && in_array(strtolower($att['file_ext']), array('zip', 'rar'))) {
            $safe_url = h(pd_url_page('register.php'));
            $link_attr = ' ' . pd_guest_download_confirm_onclick();
        }
    } elseif (preg_match('/^\/?uploads\/.*\.(zip|rar)$/i', $raw_url) && !current_user() && !pd_guest_download_allowed()) {
        $safe_url = h(pd_url_page('register.php'));
        $link_attr = ' ' . pd_guest_download_confirm_onclick();
    }
    return '<div class="attachment-inline-card">'
        . '<a class="attachment-inline-link" href="' . $safe_url . '" target="_blank" rel="noopener"' . $link_attr . '>'
        . '<strong>' . $title . '</strong>'
        . '<span>' . $safe_name . ' · 已下载 ' . $download_count . ' 次</span>'
        . '</a>'
        . $delete_form
        . '</div>';
}
