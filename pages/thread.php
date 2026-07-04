<?php
require_once __DIR__ . '/../functions.php';
qf_ensure_thread_vote_schema();
$id = qf_path_id();
mysqli_query(db(), "UPDATE qf_threads SET views=views+1 WHERE id={$id}");
$rs = mysqli_query(db(), "SELECT t.*, f.name AS forum_name, u.nickname, u.username, u.avatar, u.is_admin AS author_is_admin, u.is_moderator AS author_is_moderator FROM qf_threads t
    LEFT JOIN qf_forums f ON t.forum_id=f.id
    LEFT JOIN qf_users u ON t.user_id=u.id
    WHERE t.id={$id} AND t.is_deleted=0 LIMIT 1");
$thread = $rs ? mysqli_fetch_assoc($rs) : null;
if (!$thread) exit('帖子不存在');
$content_page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$paged_content = qf_paginate_content($thread['content'], qf_thread_page_chars(), $content_page);
$page_title = $thread['title'] . ' - ' . SITE_NAME;
qf_include_header();
$posts = mysqli_query(db(), "SELECT p.*, t.forum_id, u.nickname, u.username, u.avatar, u.signature, u.reply_count, u.is_admin AS author_is_admin, u.is_moderator AS author_is_moderator FROM qf_posts p LEFT JOIN qf_users u ON p.user_id=u.id LEFT JOIN qf_threads t ON p.thread_id=t.id
    WHERE p.thread_id={$id} AND p.is_deleted=0 ORDER BY p.id ASC LIMIT 200");
$attachments = mysqli_query(db(), "SELECT * FROM qf_attachments WHERE thread_id={$id} AND post_id=0 ORDER BY id ASC");
$guest_zip_download_blocked = !current_user() && !qf_guest_download_allowed();
$compressed_exts = array('zip', 'rar');
$thread_avatar = trim((string)$thread['avatar']);
if ($thread_avatar === '') {
    $thread_avatar = 'assets/avatar-default.svg';
}
$thread_author = $thread['nickname'] !== '' ? $thread['nickname'] : $thread['username'];
$me = current_user();
$user_vote = 0;
if ($me) {
    $vote_rs = mysqli_query(db(), "SELECT vote FROM qf_thread_votes WHERE thread_id=" . intval($id) . " AND user_id=" . intval($me['id']) . " LIMIT 1");
    if ($vote_rs && ($vote_row = mysqli_fetch_assoc($vote_rs))) {
        $user_vote = intval($vote_row['vote']);
    }
}
?>
<?php if (!empty($_SESSION['flash'])) { ?>
    <div class="alert"><?php echo nl2br(h($_SESSION['flash'])); unset($_SESSION['flash']); ?></div>
<?php } ?>
<section class="card post-title-card phpdo-thread-title-card">
    <div class="phpdo-thread-breadcrumb">
        <a href="<?php echo h(qf_url_page('index.php')); ?>">首页</a>
        <span>›</span>
        <a href="<?php echo h(qf_url_forum($thread['forum_id'])); ?>"><?php echo h($thread['forum_name']); ?></a>
    </div>
    <div class="phpdo-thread-title-row">
        <img class="phpdo-author-avatar" src="<?php echo h($thread_avatar); ?>" alt="">
        <div>
            <h1><?php if ($thread['topic_category'] !== '') { ?><a class="category-tag" href="<?php echo h(qf_url_tag($thread['topic_category'])); ?>"><?php echo h($thread['topic_category']); ?></a><?php } ?><?php echo h($thread['title']); ?></h1>
            <div class="post-meta">
                <a class="phpdo-author-link" href="<?php echo h(qf_url_user($thread['user_id'])); ?>"><?php echo h($thread_author); ?></a><?php if (intval(isset($thread['author_is_moderator']) ? $thread['author_is_moderator'] : 0)) { ?> <span class="moderator-badge">版主</span><?php } ?> · <?php echo format_time($thread['created_at']); ?> · <?php echo intval($thread['views']); ?> 浏览 · <?php echo intval($thread['replies']); ?> 回复
                <?php if (is_admin()) { ?>
                    <span class="admin-tools">
                        <span class="action-badge action-badge-static"><i class="fa-solid fa-network-wired" aria-hidden="true"></i><span>IP: <?php echo h($thread['ip']); ?></span></span>
                        <?php echo qf_action_badge(qf_url_page('edit_thread.php', array('id' => intval($thread['id']))), '编辑', 'fa-solid fa-pen-to-square', 'action-badge-edit'); ?>
                        <?php echo qf_action_badge(qf_url_page('move_thread.php', array('id' => intval($thread['id']))), '移动', 'fa-solid fa-arrow-right-arrow-left', 'action-badge-move'); ?>
                        <?php echo qf_action_badge(qf_url_page('admin/action.php', array('action' => 'top_board', 'id' => intval($thread['id']), 'token' => qf_action_token('top_board', $thread['id']))), '本版块置顶', 'fa-solid fa-thumbtack', 'action-badge-pin'); ?>
                        <?php echo qf_action_badge(qf_url_page('admin/action.php', array('action' => 'top_global', 'id' => intval($thread['id']), 'token' => qf_action_token('top_global', $thread['id']))), '全站置顶', 'fa-solid fa-up-long', 'action-badge-pin'); ?>
                        <?php if (intval($thread['is_top']) > 0) { ?><?php echo qf_action_badge(qf_url_page('admin/action.php', array('action' => 'cancel_top', 'id' => intval($thread['id']), 'token' => qf_action_token('cancel_top', $thread['id']))), '取消置顶', 'fa-solid fa-ban', 'action-badge-muted'); ?><?php } ?>
                        <?php echo qf_action_badge(qf_url_page('admin/action.php', array('action' => 'good', 'id' => intval($thread['id']), 'token' => qf_action_token('good', $thread['id']))), intval($thread['is_good']) ? '取消加精' : '加精', 'fa-solid fa-star', 'action-badge-feature'); ?>
                        <?php echo qf_action_badge(qf_url_page('admin/action.php', array('action' => 'del_thread', 'id' => intval($thread['id']), 'token' => qf_action_token('del_thread', $thread['id']))), '删除', 'fa-solid fa-trash-can', 'action-badge-danger', 'data-confirm="确定删除？"'); ?>
                    </span>
                <?php } elseif (qf_can_moderator_delete_thread(current_user(), $thread)) { ?>
                    <span class="admin-tools">
                        <?php echo qf_action_badge(qf_url_page('moderator_action.php', array('action' => 'del_thread', 'id' => intval($thread['id']), 'token' => qf_action_token('mod_del_thread', $thread['id']))), '版主删除', 'fa-solid fa-trash-can', 'action-badge-danger', 'data-confirm="确定删除该主题？"'); ?>
                    </span>
                <?php } ?>
            </div>
            <div class="phpdo-thread-votes" data-thread-votes>
                <?php if ($me) { ?>
                    <form method="post" action="<?php echo h(qf_url_page('vote.php')); ?>" data-vote-form>
                        <input type="hidden" name="thread_id" value="<?php echo intval($id); ?>">
                        <input type="hidden" name="vote" value="up">
                        <button class="phpdo-vote-button<?php echo $user_vote === 1 ? ' active' : ''; ?>" type="submit" data-vote-button="up" aria-pressed="<?php echo $user_vote === 1 ? 'true' : 'false'; ?>">
                            <i class="fa-solid fa-thumbs-up" aria-hidden="true"></i><span>顶</span><strong data-vote-count="up"><?php echo intval(isset($thread['upvotes']) ? $thread['upvotes'] : 0); ?></strong>
                        </button>
                    </form>
                    <form method="post" action="<?php echo h(qf_url_page('vote.php')); ?>" data-vote-form>
                        <input type="hidden" name="thread_id" value="<?php echo intval($id); ?>">
                        <input type="hidden" name="vote" value="down">
                        <button class="phpdo-vote-button<?php echo $user_vote === -1 ? ' active' : ''; ?>" type="submit" data-vote-button="down" aria-pressed="<?php echo $user_vote === -1 ? 'true' : 'false'; ?>">
                            <i class="fa-solid fa-thumbs-down" aria-hidden="true"></i><span>踩</span><strong data-vote-count="down"><?php echo intval(isset($thread['downvotes']) ? $thread['downvotes'] : 0); ?></strong>
                        </button>
                    </form>
                <?php } else { ?>
                    <a class="phpdo-vote-button" href="<?php echo h(qf_url_page('login.php')); ?>"><i class="fa-solid fa-thumbs-up" aria-hidden="true"></i><span>顶</span><strong><?php echo intval(isset($thread['upvotes']) ? $thread['upvotes'] : 0); ?></strong></a>
                    <a class="phpdo-vote-button" href="<?php echo h(qf_url_page('login.php')); ?>"><i class="fa-solid fa-thumbs-down" aria-hidden="true"></i><span>踩</span><strong><?php echo intval(isset($thread['downvotes']) ? $thread['downvotes'] : 0); ?></strong></a>
                <?php } ?>
            </div>
        </div>
    </div>
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
        <?php
        $reply_avatar = trim((string)$p['avatar']);
        if ($reply_avatar === '') {
            $reply_avatar = 'assets/avatar-default.svg';
        }
        $reply_author = $p['nickname'] !== '' ? $p['nickname'] : $p['username'];
        $reply_level = max(1, min(9, intval(floor(intval($p['reply_count']) / 20)) + 1));
        $reply_signature = trim((string)$p['signature']);
        ?>
        <div class="reply">
            <img class="phpdo-reply-avatar" src="<?php echo h($reply_avatar); ?>" alt="">
            <div class="phpdo-reply-body">
                <div class="phpdo-reply-header">
                    <div class="post-meta">
                        <a class="phpdo-reply-author" href="<?php echo h(qf_url_user($p['user_id'])); ?>"><?php echo h($reply_author); ?></a>
                        <span class="phpdo-level">Lv.<?php echo intval($reply_level); ?></span>
                        <?php if (intval(isset($p['author_is_moderator']) ? $p['author_is_moderator'] : 0)) { ?> <span class="moderator-badge">版主</span><?php } ?>
                        <span>发表于</span>
                        <span><?php echo format_time($p['created_at']); ?></span>
                        <span class="phpdo-meta-sep"></span>
                        <a class="phpdo-only-author" href="<?php echo h(qf_url_page('search.php', array('q' => $reply_author))); ?>">只看Ta</a>
                    </div>
                    <span class="phpdo-floor-no"><?php echo intval($floor_no); ?>#</span>
                </div>
                <div class="content"><?php echo qf_render_content($p['content']); ?></div>
                <?php if ($reply_signature !== '') { ?>
                    <div class="phpdo-signature">
                        <span>SIGNATURE</span>
                        <p><?php echo h($reply_signature); ?></p>
                    </div>
                <?php } ?>
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
                        <button class="action-badge action-badge-reply floor-reply-submit" type="submit" title="回复" aria-label="回复" data-tooltip="回复"><i class="fa-solid fa-paper-plane" aria-hidden="true"></i><span>回复</span></button>
                    </form>
                <?php } ?>
            </div>
                <div class="phpdo-reply-actions">
                    <div>
                        <?php if (current_user()) { ?>
                            <button class="phpdo-reply-action floor-reply-toggle" type="button" data-reply-target="floor-reply-form-<?php echo intval($p['id']); ?>"><i class="fa-regular fa-comment-dots" aria-hidden="true"></i><span>回复</span></button>
                        <?php } else { ?>
                            <a class="phpdo-reply-action" href="<?php echo h(qf_url_page('login.php')); ?>"><i class="fa-regular fa-comment-dots" aria-hidden="true"></i><span>回复</span></a>
                        <?php } ?>
                    </div>
                    <div>
                        <?php if (is_admin()) { ?><span class="admin-tools"><span class="action-badge action-badge-static"><i class="fa-solid fa-network-wired" aria-hidden="true"></i><span>IP: <?php echo h($p['ip']); ?></span></span><?php echo qf_action_badge(qf_url_page('admin/action.php', array('action' => 'del_post', 'id' => intval($p['id']), 'tid' => intval($id), 'token' => qf_action_token('del_post', $p['id'], intval($id)))), '删除', 'fa-solid fa-trash-can', 'action-badge-danger', 'data-confirm="确定删除？"'); ?></span><?php } ?>
                        <?php if (!is_admin() && qf_can_moderator_delete_post(current_user(), $p)) { ?><span class="admin-tools"><?php echo qf_action_badge(qf_url_page('moderator_action.php', array('action' => 'del_post', 'id' => intval($p['id']), 'tid' => intval($id), 'token' => qf_action_token('mod_del_post', $p['id'], intval($id)))), '版主删除', 'fa-solid fa-trash-can', 'action-badge-danger', 'data-confirm="确定删除该回复？"'); ?></span><?php } ?>
                        <span class="phpdo-reply-action phpdo-report"><i class="fa-regular fa-flag" aria-hidden="true"></i><span>举报</span></span>
                    </div>
                </div>
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
            <label class="editor-upload-button" title="上传图片/附件">
                <i class="fa-solid fa-cloud-arrow-up" aria-hidden="true"></i><span>上传</span>
                <input class="qf-instant-upload" data-target="reply-content-textarea" data-status="reply-upload-status" type="file" name="attachments[]" multiple accept=".jpg,.jpeg,.png,.gif,.webp,.zip,.rar">
            </label>
            <button class="upload-help editor-help-button" type="button">?</button>
        </div>
        <p class="muted upload-tip editor-upload-tip">支持 <?php echo h(qf_upload_allowed_exts_label()); ?>，单个文件最大 <?php echo intval(qf_upload_max_mb()); ?>MB。</p>
        <p id="reply-upload-status" class="muted upload-status"></p>
        <textarea id="reply-content-textarea" name="content" rows="5" maxlength="<?php echo intval(qf_reply_max_chars()); ?>" required placeholder="写下你的回复"></textarea>
        <p class="muted">最多可输入 <?php echo intval(qf_reply_max_chars()); ?> 字。</p>
        <div class="upload-captcha-row">
            <div class="captcha-col"><?php if (qf_captcha_required('reply', current_user())) { echo qf_render_captcha(); } ?></div>
        </div>
        <button class="btn" type="submit">回帖</button>
    </form>
</section>
<?php } else { ?>
<section class="card"><a href="<?php echo h(qf_url_page('login.php')); ?>">登录后回复</a></section>
<?php } ?>
<script src="<?php echo h(qf_asset_js('editor')); ?>"></script>
<?php qf_include_footer(); ?>
