<?php
require_once __DIR__ . '/../functions.php';
$id = qf_path_id();
mysqli_query(db(), "UPDATE qf_threads SET views=views+1 WHERE id={$id}");
$rs = mysqli_query(db(), "SELECT t.*, f.name AS forum_name, u.nickname, u.username, u.is_admin AS author_is_admin, u.is_moderator AS author_is_moderator FROM qf_threads t
    LEFT JOIN qf_forums f ON t.forum_id=f.id
    LEFT JOIN qf_users u ON t.user_id=u.id
    WHERE t.id={$id} AND t.is_deleted=0 LIMIT 1");
$thread = $rs ? mysqli_fetch_assoc($rs) : null;
if (!$thread) exit('帖子不存在');
$content_page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$paged_content = qf_paginate_content($thread['content'], qf_thread_page_chars(), $content_page);
$page_title = $thread['title'] . ' - ' . SITE_NAME;
qf_include_header();
$posts = mysqli_query(db(), "SELECT p.*, t.forum_id, u.nickname, u.is_admin AS author_is_admin, u.is_moderator AS author_is_moderator FROM qf_posts p LEFT JOIN qf_users u ON p.user_id=u.id LEFT JOIN qf_threads t ON p.thread_id=t.id
    WHERE p.thread_id={$id} AND p.is_deleted=0 ORDER BY p.id ASC LIMIT 200");
$attachments = mysqli_query(db(), "SELECT * FROM qf_attachments WHERE thread_id={$id} AND post_id=0 ORDER BY id ASC");
$guest_zip_download_blocked = !current_user() && !qf_guest_download_allowed();
$compressed_exts = array('zip', 'rar');
?>
<?php if (!empty($_SESSION['flash'])) { ?>
    <div class="alert"><?php echo nl2br(h($_SESSION['flash'])); unset($_SESSION['flash']); ?></div>
<?php } ?>
<section class="card post-title-card">
    <div class="post-meta">
        <a href="<?php echo h(qf_url_page('index.php')); ?>">首页</a>
        · <a href="<?php echo h(qf_url_forum($thread['forum_id'])); ?>"><?php echo h($thread['forum_name']); ?></a>
        · <?php echo h($thread['nickname']); ?><?php if (intval(isset($thread['author_is_moderator']) ? $thread['author_is_moderator'] : 0)) { ?> <span class="moderator-badge">版主</span><?php } ?> · <?php echo format_time($thread['created_at']); ?>
        <?php if (is_admin()) { ?>
            <span class="admin-tools">
                IP: <?php echo h($thread['ip']); ?>
                <a href="<?php echo h(qf_url_page('edit_thread.php', array('id' => intval($thread['id'])))); ?>">编辑</a>
                <a href="<?php echo h(qf_url_page('move_thread.php', array('id' => intval($thread['id'])))); ?>">移动</a>
                <a href="<?php echo h(qf_url_page('admin/action.php', array('action' => 'top_board', 'id' => intval($thread['id']), 'token' => qf_action_token('top_board', $thread['id'])))); ?>">本版块置顶</a>
                <a href="<?php echo h(qf_url_page('admin/action.php', array('action' => 'top_global', 'id' => intval($thread['id']), 'token' => qf_action_token('top_global', $thread['id'])))); ?>">全站置顶</a>
                <?php if (intval($thread['is_top']) > 0) { ?><a href="<?php echo h(qf_url_page('admin/action.php', array('action' => 'cancel_top', 'id' => intval($thread['id']), 'token' => qf_action_token('cancel_top', $thread['id'])))); ?>">取消置顶</a><?php } ?>
                <a href="<?php echo h(qf_url_page('admin/action.php', array('action' => 'good', 'id' => intval($thread['id']), 'token' => qf_action_token('good', $thread['id'])))); ?>"><?php echo intval($thread['is_good']) ? '取消加精' : '加精'; ?></a>
                <a data-confirm="确定删除？" href="<?php echo h(qf_url_page('admin/action.php', array('action' => 'del_thread', 'id' => intval($thread['id']), 'token' => qf_action_token('del_thread', $thread['id'])))); ?>">删除</a>
            </span>
        <?php } elseif (qf_can_moderator_delete_thread(current_user(), $thread)) { ?>
            <span class="admin-tools">
                <a data-confirm="确定删除该主题？" href="<?php echo h(qf_url_page('moderator_action.php', array('action' => 'del_thread', 'id' => intval($thread['id']), 'token' => qf_action_token('mod_del_thread', $thread['id'])))); ?>">版主删除</a>
            </span>
        <?php } ?>
    </div>
    <h1><?php if ($thread['topic_category'] !== '') { ?><span class="category-tag"><?php echo h($thread['topic_category']); ?></span><?php } ?><?php echo h($thread['title']); ?></h1>
</section>
<article class="card post-content-card">
    <div class="content post-content-box"><?php echo qf_render_content($paged_content['content']); ?></div>
    <?php if (intval($paged_content['total']) > 1) { ?>
        <div class="content-page-nav">
            <?php for ($cp = 1; $cp <= intval($paged_content['total']); $cp++) { ?>
                <a class="<?php echo $cp === intval($paged_content['page']) ? 'active' : ''; ?>" href="<?php echo h(qf_url_thread($id)); ?>?page=<?php echo $cp; ?>"><?php echo $cp; ?></a>
            <?php } ?>
        </div>
    <?php } ?>
    <?php if ($attachments && mysqli_num_rows($attachments) > 0) { ?>
        <div class="attachment-list">
            <h3>附件</h3>
            <?php while ($att = mysqli_fetch_assoc($attachments)) { ?>
                <?php if (in_array(strtolower($att['file_ext']), array('jpg', 'jpeg', 'png', 'gif', 'webp'))) { ?>
                    <a href="<?php echo h(qf_attachment_url($att['id'])); ?>" target="_blank">
                        <img class="attachment-img" src="<?php echo h(qf_attachment_url($att['id'])); ?>" alt="<?php echo h($att['original_name']); ?>">
                    </a>
                    <?php echo qf_attachment_delete_form($att); ?>
                <?php } else { ?>
                    <?php $zip_blocked = $guest_zip_download_blocked && in_array(strtolower($att['file_ext']), $compressed_exts); ?>
                    <a class="attachment-file" href="<?php echo h($zip_blocked ? qf_url_page('register.php') : qf_attachment_url($att['id'])); ?>" target="_blank" <?php if ($zip_blocked) echo qf_guest_download_confirm_onclick(); ?>>
                        <?php echo h($att['original_name']); ?> · <?php echo h(strtoupper($att['file_ext'])); ?> · <?php echo round(intval($att['file_size']) / 1024, 1); ?>KB · 下载次数 <?php echo intval(isset($att['download_count']) ? $att['download_count'] : 0); ?>
                    </a>
                    <?php echo qf_attachment_delete_form($att); ?>
                <?php } ?>
            <?php } ?>
        </div>
    <?php } ?>
</article>
<section class="card replies" id="replies">
    <h2>回复 <?php echo intval($thread['replies']); ?></h2>
    <?php $floor_no = 0; ?>
    <?php while ($posts && $p = mysqli_fetch_assoc($posts)) { ?>
        <?php $floor_no++; ?>
        <?php $reply_attachments = mysqli_query(db(), "SELECT * FROM qf_attachments WHERE post_id=" . intval($p['id']) . " ORDER BY id ASC"); ?>
        <div class="reply">
            <div class="post-meta"><?php echo h($p['nickname']); ?> <span class="floor-label"><?php echo h(qf_floor_name($floor_no)); ?><?php if (qf_floor_icon($floor_no) !== '') { ?> <span class="floor-icon"><?php echo h(qf_floor_icon($floor_no)); ?></span><?php } ?></span><?php if (intval(isset($p['author_is_moderator']) ? $p['author_is_moderator'] : 0)) { ?> <span class="moderator-badge">版主</span><?php } ?> · <?php echo format_time($p['created_at']); ?>
                <?php if (is_admin()) { ?><span class="admin-tools">IP: <?php echo h($p['ip']); ?> <a data-confirm="确定删除？" href="<?php echo h(qf_url_page('admin/action.php', array('action' => 'del_post', 'id' => intval($p['id']), 'tid' => intval($id), 'token' => qf_action_token('del_post', $p['id'], intval($id))))); ?>">删除</a></span><?php } ?>
                <?php if (!is_admin() && qf_can_moderator_delete_post(current_user(), $p)) { ?><span class="admin-tools"><a data-confirm="确定删除该回复？" href="<?php echo h(qf_url_page('moderator_action.php', array('action' => 'del_post', 'id' => intval($p['id']), 'tid' => intval($id), 'token' => qf_action_token('mod_del_post', $p['id'], intval($id))))); ?>">版主删除</a></span><?php } ?>
                <?php if (current_user()) { ?><span class="floor-reply-actions"><button class="btn btn-small btn-light floor-reply-toggle" type="button" data-reply-target="floor-reply-form-<?php echo intval($p['id']); ?>">回复</button></span><?php } ?>
            </div>
            <div class="content"><?php echo qf_render_content($p['content']); ?></div>
            <?php if ($reply_attachments && mysqli_num_rows($reply_attachments) > 0) { ?>
                <div class="attachment-list reply-attachments">
                    <?php while ($att = mysqli_fetch_assoc($reply_attachments)) { ?>
                        <?php if (in_array(strtolower($att['file_ext']), array('jpg', 'jpeg', 'png', 'gif', 'webp'))) { ?>
                            <a href="<?php echo h(qf_attachment_url($att['id'])); ?>" target="_blank">
                                <img class="attachment-img" src="<?php echo h(qf_attachment_url($att['id'])); ?>" alt="<?php echo h($att['original_name']); ?>">
                            </a>
                            <?php echo qf_attachment_delete_form($att); ?>
                        <?php } else { ?>
                            <?php $zip_blocked = $guest_zip_download_blocked && in_array(strtolower($att['file_ext']), $compressed_exts); ?>
                            <a class="attachment-file" href="<?php echo h($zip_blocked ? qf_url_page('register.php') : qf_attachment_url($att['id'])); ?>" target="_blank" <?php if ($zip_blocked) echo qf_guest_download_confirm_onclick(); ?>><?php echo h($att['original_name']); ?> · <?php echo h(strtoupper($att['file_ext'])); ?> · <?php echo round(intval($att['file_size']) / 1024, 1); ?>KB · 下载次数 <?php echo intval(isset($att['download_count']) ? $att['download_count'] : 0); ?></a>
                            <?php echo qf_attachment_delete_form($att); ?>
                        <?php } ?>
                    <?php } ?>
                </div>
            <?php } ?>
            <?php $floor_replies = mysqli_query(db(), "SELECT c.*, u.nickname FROM qf_post_comments c LEFT JOIN qf_users u ON c.user_id=u.id WHERE c.post_id=" . intval($p['id']) . " AND c.is_deleted=0 ORDER BY c.id ASC LIMIT 50"); ?>
            <div class="floor-replies">
                <?php while ($floor_replies && $c = mysqli_fetch_assoc($floor_replies)) { ?>
                    <div class="floor-reply"><strong><?php echo h($c['nickname']); ?></strong>：<?php echo qf_render_content($c['content']); ?> <span><?php echo format_time($c['created_at']); ?></span></div>
                <?php } ?>
                <?php if (current_user()) { ?>
                    <form id="floor-reply-form-<?php echo intval($p['id']); ?>" class="floor-reply-form" method="post" action="<?php echo h(qf_url_page('floor_reply.php')); ?>" style="display:none">
                        <input type="hidden" name="thread_id" value="<?php echo intval($id); ?>">
                        <input type="hidden" name="post_id" value="<?php echo intval($p['id']); ?>">
                        <input type="text" name="content" maxlength="500" placeholder="回复 <?php echo h($p['nickname']); ?>" required>
                        <button class="btn btn-small" type="submit">回复</button>
                    </form>
                <?php } ?>
            </div>
        </div>
    <?php } ?>
</section>
<?php if (current_user()) { ?>
<section class="card">
    <h2>发表回复</h2>
    <form method="post" action="<?php echo h(qf_url_page('reply.php')); ?>" enctype="multipart/form-data">
        <input type="hidden" name="thread_id" value="<?php echo intval($id); ?>">
        <div class="editor-toolbar">
            <button type="button" data-wrap="[font=宋体]" data-close="[/font]">字体</button>
            <button type="button" data-wrap="[size=18]" data-close="[/size]">字体大小</button>
            <button type="button" data-wrap="[b]" data-close="[/b]">加粗</button>
            <button type="button" data-link="1">超链接</button>
            <button type="button" data-remote-img="1">远程图片</button>
        </div>
        <textarea id="reply-content-textarea" name="content" rows="5" maxlength="<?php echo intval(qf_reply_max_chars()); ?>" required placeholder="写下你的回复"></textarea>
        <p class="muted">最多可输入 <?php echo intval(qf_reply_max_chars()); ?> 字。</p>
        <div class="upload-captcha-row">
            <div class="captcha-col"><?php if (qf_captcha_required('reply', current_user())) { echo qf_render_captcha(); } ?></div>
            <div class="upload-col">
                <label class="upload-icon-box">
                    <span class="upload-icon" aria-hidden="true">
                        <svg viewBox="0 0 48 38" width="42" height="34">
                            <rect x="4" y="4" width="40" height="30" rx="2"></rect>
                            <circle cx="15" cy="13" r="4"></circle>
                            <path d="M8 31 L20 20 L28 27 L34 18 L42 31"></path>
                        </svg>
                    </span>
                    <span class="upload-text">图片/附件</span>
                    <input class="qf-instant-upload" data-target="reply-content-textarea" data-status="reply-upload-status" type="file" name="attachments[]" multiple accept=".jpg,.jpeg,.png,.gif,.webp,.zip,.rar">
                </label>
                <button class="upload-help" type="button">?</button>
                <p class="muted upload-tip">支持 <?php echo h(qf_upload_allowed_exts_label()); ?>，单个文件最大 <?php echo intval(qf_upload_max_mb()); ?>MB。</p>
                <p id="reply-upload-status" class="muted upload-status"></p>
            </div>
        </div>
        <button class="btn" type="submit">回帖</button>
    </form>
</section>
<?php } else { ?>
<section class="card"><a href="<?php echo h(qf_url_page('login.php')); ?>">登录后回复</a></section>
<?php } ?>
<script src="<?php echo h(qf_asset_js('editor')); ?>"></script>
<?php qf_include_footer(); ?>
