<?php
require_once __DIR__ . '/../functions.php';
pd_ensure_thread_reaction_schema();
pd_ensure_post_vote_schema();
$id = pd_path_id();
mysqli_query(db(), "UPDATE pd_threads SET views=views+1 WHERE id={$id}");
$rs = mysqli_query(db(), "SELECT t.*, f.name AS forum_name, u.nickname, u.username, u.avatar, u.email, u.signature AS author_signature, u.points AS author_points, u.group_id AS author_group_id, u.is_admin AS author_is_admin, u.is_moderator AS author_is_moderator FROM pd_threads t
    LEFT JOIN pd_forums f ON t.forum_id=f.id
    LEFT JOIN pd_users u ON t.user_id=u.id
    WHERE t.id={$id} AND t.is_deleted=0 LIMIT 1");
$thread = $rs ? mysqli_fetch_assoc($rs) : null;
if (!$thread) exit('帖子不存在');
$content_page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$paged_content = pd_paginate_content($thread['content'], pd_thread_page_chars(), $content_page);
$page_title = $thread['title'] . ' - ' . SITE_NAME;
pd_include_header();
// 评论分页
$replies_per_page = pd_replies_per_page();
$total_replies = count_rows("SELECT COUNT(*) FROM pd_posts WHERE thread_id={$id} AND is_deleted=0");
$reply_pages = max(1, (int)ceil($total_replies / $replies_per_page));
$reply_page = isset($_GET['rp']) ? intval($_GET['rp']) : 1;
if ($reply_page < 1) $reply_page = 1;
if ($reply_page > $reply_pages) $reply_page = $reply_pages;
$reply_offset = ($reply_page - 1) * $replies_per_page;
$posts = mysqli_query(db(), "SELECT p.*, t.forum_id, u.nickname, u.username, u.avatar, u.email, u.signature, u.reply_count, u.points, u.group_id, u.is_admin AS author_is_admin, u.is_moderator AS author_is_moderator FROM pd_posts p LEFT JOIN pd_users u ON p.user_id=u.id LEFT JOIN pd_threads t ON p.thread_id=t.id
    WHERE p.thread_id={$id} AND p.is_deleted=0 ORDER BY p.id ASC LIMIT {$reply_offset}, {$replies_per_page}");
$attachments = mysqli_query(db(), "SELECT * FROM pd_attachments WHERE thread_id={$id} AND post_id=0 ORDER BY id ASC");
$guest_zip_download_blocked = !current_user() && !pd_guest_download_allowed();
$thread_avatar = pd_user_avatar($thread, 160);
$thread_author = pd_user_display_name($thread);
$me = current_user();
$user_post_votes = array();
if ($me) {
    $vote_rs = mysqli_query(db(), "SELECT pv.post_id, pv.vote FROM pd_post_votes pv INNER JOIN pd_posts p ON p.id=pv.post_id WHERE p.thread_id=" . intval($id) . " AND p.is_deleted=0 AND pv.user_id=" . intval($me['id']));
    while ($vote_rs && ($vote_row = mysqli_fetch_assoc($vote_rs))) {
        $user_post_votes[intval($vote_row['post_id'])] = intval($vote_row['vote']);
    }
}
?>
<?php if (!empty($_SESSION['flash'])) { ?>
    <div class="alert"><?php echo nl2br(h($_SESSION['flash'])); unset($_SESSION['flash']); ?></div>
<?php } ?>
<nav class="pd-thread-breadcrumb" aria-label="面包屑导航">
    <div class="pd-crumb-trail">
        <a href="<?php echo h(pd_url_page('index.php')); ?>"><i class="fa-solid fa-house" aria-hidden="true"></i><span>首页</span></a>
        <span class="pd-crumb-sep">›</span>
        <a href="<?php echo h(pd_url_forum($thread['forum_id'])); ?>"><?php echo h($thread['forum_name']); ?></a>
        <span class="pd-crumb-sep">›</span>
        <span class="pd-crumb-current" title="<?php echo h($thread['title']); ?>"><?php echo h($thread['title']); ?></span>
    </div>
    <?php $thread_tools_html = pd_thread_admin_tools_html($thread); ?>
    <?php if ($thread_tools_html !== '') { ?>
        <span class="admin-tools pd-crumb-tools" data-thread-tools data-thread-id="<?php echo intval($thread['id']); ?>"><?php echo $thread_tools_html; ?></span>
    <?php } ?>
</nav>
<article class="card post-title-card post-content-card pd-thread-title-card pd-thread-main-card">
    <div class="pd-thread-title-row">
        <img class="pd-author-avatar" src="<?php echo h($thread_avatar); ?>" alt="">
        <div>
            <h1<?php echo pd_thread_title_attr($thread); ?>><?php echo h($thread['title']); ?></h1>
            <?php $thread_author_points = intval(isset($thread['author_points']) ? $thread['author_points'] : 0); ?>
            <div class="post-meta pd-thread-title-meta">
                <span class="pd-meta-time"><?php echo pd_time_html($thread['created_at']); ?></span>
            </div>
        </div>
    </div>
    <div class="content post-content-box pd-md-body"><?php echo pd_render_content($paged_content['content']); ?></div>
    <?php if (intval($paged_content['total']) > 1) { ?>
        <div class="content-page-nav">
            <?php for ($cp = 1; $cp <= intval($paged_content['total']); $cp++) { ?>
                <a class="<?php echo $cp === intval($paged_content['page']) ? 'active' : ''; ?>" href="<?php echo h(pd_url_thread($id)); ?>?page=<?php echo $cp; ?>"><?php echo $cp; ?></a>
            <?php } ?>
        </div>
    <?php } ?>
    <div class="pd-thread-foot-stats">
        <a class="pd-author-link" href="<?php echo h(pd_url_user($thread['user_id'])); ?>"><?php echo h($thread_author); ?></a>
        <span class="pd-level">Lv.<?php echo intval(pd_user_level($thread_author_points)); ?></span>
        <?php echo pd_user_group_badge_html(array('group_id' => isset($thread['author_group_id']) ? $thread['author_group_id'] : 0, 'points' => $thread_author_points)); ?>
        <?php if (intval(isset($thread['author_is_moderator']) ? $thread['author_is_moderator'] : 0)) { ?><span class="moderator-badge">版主</span><?php } ?>
        <span class="pd-foot-nums"><?php echo pd_format_compact_number($thread['views']); ?> 浏览 · <?php echo pd_format_compact_number($thread['replies']); ?> 回复</span>
    </div>
    <?php echo pd_render_attachment_list($attachments, array('guest_zip_blocked' => $guest_zip_download_blocked)); ?>
    <?php
    $reaction_types = pd_reaction_types();
    $reaction_counts = pd_thread_reaction_counts($id);
    $user_reaction = $me ? pd_user_thread_reaction($id, intval($me['id'])) : '';
    ?>
    <div class="pd-reactions" data-reactions data-thread-id="<?php echo intval($id); ?>" data-logged-in="<?php echo $me ? '1' : '0'; ?>" data-login-url="<?php echo h(pd_url_page('login.php')); ?>" aria-label="表情反应">
        <?php foreach ($reaction_types as $rkey => $rinfo) { ?>
            <button type="button" class="pd-reaction<?php echo $user_reaction === $rkey ? ' is-active' : ''; ?>" data-reaction="<?php echo h($rkey); ?>" data-label="<?php echo h($rinfo['label']); ?>" aria-pressed="<?php echo $user_reaction === $rkey ? 'true' : 'false'; ?>" title="<?php echo h($rinfo['label']); ?>">
                <span class="pd-reaction-emoji"><?php echo $rinfo['emoji']; ?></span>
                <span class="pd-reaction-count" data-reaction-count="<?php echo h($rkey); ?>"><?php echo intval($reaction_counts[$rkey]) > 0 ? intval($reaction_counts[$rkey]) : ''; ?></span>
            </button>
        <?php } ?>
    </div>
    <?php $thread_signature = trim((string)(isset($thread['author_signature']) ? $thread['author_signature'] : '')); ?>
    <?php if ($thread_signature !== '') { ?>
        <div class="pd-signature">
            <span>SIGNATURE</span>
            <p><?php echo nl2br(h($thread_signature)); ?></p>
        </div>
    <?php } ?>
    <?php echo pd_render_ad('thread'); ?>
</article>
<section class="card replies" id="replies">
    <h2>回复 <?php echo pd_format_compact_number($thread['replies']); ?></h2>
    <?php $floor_no = $reply_offset; ?>
    <?php while ($posts && $p = mysqli_fetch_assoc($posts)) { ?>
        <?php $floor_no++; ?>
        <?php $reply_attachments = mysqli_query(db(), "SELECT * FROM pd_attachments WHERE post_id=" . intval($p['id']) . " ORDER BY id ASC"); ?>
        <?php
        $reply_avatar = pd_user_avatar($p, 96);
        $reply_author = pd_user_display_name($p);
        $reply_level = pd_user_level(intval(isset($p['points']) ? $p['points'] : 0));
        $reply_signature = trim((string)$p['signature']);
        $post_user_vote = isset($user_post_votes[intval($p['id'])]) ? $user_post_votes[intval($p['id'])] : 0;
        $post_upvotes = intval(isset($p['upvotes']) ? $p['upvotes'] : 0);
        $post_downvotes = intval(isset($p['downvotes']) ? $p['downvotes'] : 0);
        ?>
        <div class="reply">
            <img class="pd-reply-avatar" src="<?php echo h($reply_avatar); ?>" alt="">
            <div class="pd-reply-body">
                <div class="pd-reply-header">
                    <div class="post-meta">
                        <a class="pd-reply-author" href="<?php echo h(pd_url_user($p['user_id'])); ?>"><?php echo h($reply_author); ?></a>
                        <span class="pd-level">Lv.<?php echo intval($reply_level); ?></span>
                        <?php echo pd_user_group_badge_html($p); ?>
                        <?php if (intval(isset($p['author_is_moderator']) ? $p['author_is_moderator'] : 0)) { ?> <span class="moderator-badge">版主</span><?php } ?>
                        <span>发表于</span>
                        <span><?php echo pd_time_html($p['created_at']); ?></span>
                        <span class="pd-meta-sep"></span>
                        <a class="pd-only-author" href="<?php echo h(pd_url_page('search.php', array('q' => $reply_author))); ?>">只看Ta</a>
                    </div>
                    <span class="pd-floor-no"><?php echo intval($floor_no); ?>#</span>
                </div>
                <div class="content pd-md-body"><?php echo pd_render_content($p['content']); ?></div>
                <?php if ($reply_signature !== '') { ?>
                    <div class="pd-signature">
                        <span>SIGNATURE</span>
                        <p><?php echo h($reply_signature); ?></p>
                    </div>
                <?php } ?>
            <?php echo pd_render_attachment_list($reply_attachments, array('reply' => true, 'guest_zip_blocked' => $guest_zip_download_blocked)); ?>
            <?php $floor_replies = mysqli_query(db(), "SELECT c.*, u.nickname FROM pd_post_comments c LEFT JOIN pd_users u ON c.user_id=u.id WHERE c.post_id=" . intval($p['id']) . " AND c.is_deleted=0 ORDER BY c.id ASC LIMIT 50"); ?>
            <div class="floor-replies">
                <?php while ($floor_replies && $c = mysqli_fetch_assoc($floor_replies)) { ?>
                    <div class="floor-reply"><strong><?php echo h($c['nickname']); ?></strong>：<?php echo pd_render_content($c['content']); ?> <span><?php echo pd_time_html($c['created_at']); ?></span></div>
                <?php } ?>
                <?php if (current_user()) { ?>
                    <form id="floor-reply-form-<?php echo intval($p['id']); ?>" class="floor-reply-form" method="post" action="<?php echo h(pd_url_page('floor_reply.php')); ?>" style="display:none">
                        <input type="hidden" name="thread_id" value="<?php echo intval($id); ?>">
                        <input type="hidden" name="post_id" value="<?php echo intval($p['id']); ?>">
                        <input type="text" name="content" maxlength="500" placeholder="回复 <?php echo h($p['nickname']); ?>" required>
                        <button class="action-badge action-badge-reply floor-reply-submit" type="submit" title="回复" aria-label="回复" data-tooltip="回复"><i class="fa-solid fa-paper-plane" aria-hidden="true"></i><span>回复</span></button>
                    </form>
                <?php } ?>
            </div>
                <div class="pd-reply-actions">
                    <div class="pd-reply-actions-main">
                        <?php if (current_user()) { ?>
                            <button class="pd-vote-button pd-vote-button-sm floor-reply-toggle" type="button" data-reply-target="floor-reply-form-<?php echo intval($p['id']); ?>"><i class="fa-regular fa-comment-dots" aria-hidden="true"></i><span>回复</span></button>
                        <?php } else { ?>
                            <a class="pd-vote-button pd-vote-button-sm" href="<?php echo h(pd_url_page('login.php')); ?>"><i class="fa-regular fa-comment-dots" aria-hidden="true"></i><span>回复</span></a>
                        <?php } ?>
                        <div class="pd-post-votes" data-post-votes>
                            <?php if ($me) { ?>
                                <form method="post" action="<?php echo h(pd_url_page('react.php')); ?>" data-vote-form>
                                    <input type="hidden" name="thread_id" value="<?php echo intval($id); ?>">
                                    <input type="hidden" name="post_id" value="<?php echo intval($p['id']); ?>">
                                    <input type="hidden" name="vote" value="up">
                                    <button class="pd-vote-button pd-vote-button-sm<?php echo $post_user_vote === 1 ? ' active' : ''; ?>" type="submit" data-vote-button="up" aria-pressed="<?php echo $post_user_vote === 1 ? 'true' : 'false'; ?>">
                                        <i class="fa-solid fa-thumbs-up" aria-hidden="true"></i><span>顶</span><strong data-vote-count="up"><?php echo $post_upvotes; ?></strong>
                                    </button>
                                </form>
                                <form method="post" action="<?php echo h(pd_url_page('react.php')); ?>" data-vote-form>
                                    <input type="hidden" name="thread_id" value="<?php echo intval($id); ?>">
                                    <input type="hidden" name="post_id" value="<?php echo intval($p['id']); ?>">
                                    <input type="hidden" name="vote" value="down">
                                    <button class="pd-vote-button pd-vote-button-sm<?php echo $post_user_vote === -1 ? ' active' : ''; ?>" type="submit" data-vote-button="down" aria-pressed="<?php echo $post_user_vote === -1 ? 'true' : 'false'; ?>">
                                        <i class="fa-solid fa-thumbs-down" aria-hidden="true"></i><span>踩</span><strong data-vote-count="down"><?php echo $post_downvotes; ?></strong>
                                    </button>
                                </form>
                            <?php } else { ?>
                                <a class="pd-vote-button pd-vote-button-sm" href="<?php echo h(pd_url_page('login.php')); ?>"><i class="fa-solid fa-thumbs-up" aria-hidden="true"></i><span>顶</span><strong><?php echo $post_upvotes; ?></strong></a>
                                <a class="pd-vote-button pd-vote-button-sm" href="<?php echo h(pd_url_page('login.php')); ?>"><i class="fa-solid fa-thumbs-down" aria-hidden="true"></i><span>踩</span><strong><?php echo $post_downvotes; ?></strong></a>
                            <?php } ?>
                        </div>
                    </div>
                    <div>
                        <?php if (is_admin()) { ?><span class="admin-tools"><?php echo pd_ip_badge_html(isset($p['ip']) ? $p['ip'] : ''); ?><?php echo pd_action_badge(pd_url_page('admin/action.php', array('action' => 'del_post', 'id' => intval($p['id']), 'tid' => intval($id), 'token' => pd_action_token('del_post', $p['id'], intval($id)))), '删除', 'fa-solid fa-trash-can', 'action-badge-danger', 'data-confirm="确定删除？" data-ajax="1"'); ?></span><?php } ?>
                        <?php if (!is_admin() && pd_can_moderator_delete_post(current_user(), $p)) { ?><span class="admin-tools"><?php echo pd_action_badge(pd_url_page('moderator_action.php', array('action' => 'del_post', 'id' => intval($p['id']), 'tid' => intval($id), 'token' => pd_action_token('mod_del_post', $p['id'], intval($id)))), '版主删除', 'fa-solid fa-trash-can', 'action-badge-danger', 'data-confirm="确定删除该回复？" data-ajax="1"'); ?></span><?php } ?>
                        <span class="pd-reply-action pd-report" title="举报" aria-label="举报"><i class="fa-regular fa-flag" aria-hidden="true"></i></span>
                    </div>
                </div>
            </div>
        </div>
    <?php } ?>
    <?php if ($reply_pages > 1) { ?>
        <nav class="pd-reply-pagination" aria-label="回复分页">
            <?php if ($reply_page > 1) { ?>
                <a href="<?php echo h(pd_url_thread($id)); ?>?rp=<?php echo $reply_page - 1; ?>#replies">上一页</a>
            <?php } ?>
            <?php for ($rp = 1; $rp <= $reply_pages; $rp++) { ?>
                <a class="<?php echo $rp === $reply_page ? 'active' : ''; ?>" href="<?php echo h(pd_url_thread($id)); ?>?rp=<?php echo $rp; ?>#replies"><?php echo $rp; ?></a>
            <?php } ?>
            <?php if ($reply_page < $reply_pages) { ?>
                <a href="<?php echo h(pd_url_thread($id)); ?>?rp=<?php echo $reply_page + 1; ?>#replies">下一页</a>
            <?php } ?>
        </nav>
    <?php } ?>
</section>
<?php if (current_user()) { ?>
<section class="card">
    <h2>发表回复</h2>
    <form method="post" action="<?php echo h(pd_url_page('reply.php')); ?>" enctype="multipart/form-data">
        <input type="hidden" name="thread_id" value="<?php echo intval($id); ?>">
        <?php
        $editorId = 'reply-content-textarea';
        $editorName = 'content';
        $editorValue = '';
        $editorRows = 8;
        $editorRequired = true;
        $editorCompact = true;
        $editorMaxlength = intval(pd_reply_max_chars());
        $editorPlaceholder = '写下你的回复（Markdown）';
        include __DIR__ . '/../parts/markdown-editor.php';
        ?>
        <p class="muted">最多可输入 <?php echo intval(pd_reply_max_chars()); ?> 字。</p>
        <div class="upload-captcha-row">
            <div class="captcha-col"><?php if (pd_captcha_required('reply', current_user())) { echo pd_render_captcha(); } ?></div>
        </div>
        <button class="btn" type="submit">回帖</button>
    </form>
</section>
<?php } else { ?>
<section class="card"><a href="<?php echo h(pd_url_page('login.php')); ?>">登录后回复</a></section>
<?php } ?>
<script src="<?php echo h(pd_asset_js('admin')); ?>"></script>
<?php pd_include_footer(); ?>
