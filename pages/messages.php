<?php
require_once __DIR__ . '/../functions.php';
qf_ensure_pm_schema();
$u = require_login();
$uid = intval($u['id']);
$error = '';
$notice = '';
$active_thread_id = isset($_GET['thread']) ? intval($_GET['thread']) : 0;
$compose_to_id = isset($_GET['to']) ? intval($_GET['to']) : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['hide_thread'])) {
        $hide_id = intval($_POST['thread_id']);
        if ($hide_id > 0 && qf_pm_hide_thread($hide_id, $uid)) {
            redirect(qf_url_messages());
        }
    }
    if (isset($_POST['send_pm'])) {
        $body = isset($_POST['body']) ? $_POST['body'] : '';
        $thread_id = intval(isset($_POST['thread_id']) ? $_POST['thread_id'] : 0);
        $to_id = intval(isset($_POST['to_id']) ? $_POST['to_id'] : 0);
        if ($thread_id < 1 && $to_id < 1 && $compose_to_id > 0) {
            $to_id = $compose_to_id;
        }
        if ($thread_id > 0) {
            $thread = qf_pm_get_thread_row($thread_id);
            $to_id = qf_pm_thread_peer_id($thread, $uid);
        }
        $result = qf_pm_send_message($uid, $to_id, $body, $thread_id);
        if (!empty($result['ok'])) {
            redirect(qf_url_messages(intval($result['thread_id'])));
        }
        $error = isset($result['error']) ? $result['error'] : '发送失败。';
        if ($thread_id > 0) {
            $active_thread_id = $thread_id;
        } elseif ($to_id > 0) {
            $compose_to_id = $to_id;
        }
    }
}

if ($compose_to_id > 0 && $compose_to_id !== $uid) {
    $opened = qf_pm_get_or_create_thread($uid, $compose_to_id);
    if ($opened > 0) {
        $active_thread_id = $opened;
        $compose_to_id = 0;
    }
}

$threads = qf_pm_fetch_threads($uid);
$active_thread = null;
$active_peer = null;
$messages = array();

if ($active_thread_id > 0) {
    $active_thread = qf_pm_get_thread_row($active_thread_id);
    if ($active_thread && qf_pm_user_in_thread($active_thread, $uid)) {
        $peer_id = qf_pm_thread_peer_id($active_thread, $uid);
        $active_peer = qf_pm_user_brief($peer_id);
        qf_pm_mark_thread_read($active_thread_id, $uid);
        $messages = qf_pm_fetch_messages($active_thread_id, $uid);
    } else {
        $active_thread_id = 0;
        $error = $error !== '' ? $error : '会话不存在或无权查看。';
    }
}

$page_title = '私信 - ' . SITE_NAME;
qf_include_header();
?>
<section class="card phpdo-messages-card">
    <div class="phpdo-messages" data-pm-root<?php echo $active_thread_id > 0 ? ' data-pm-open="1"' : ''; ?>>
        <aside class="phpdo-messages-sidebar" aria-label="会话列表">
            <header class="phpdo-messages-sidebar-head">
                <h1>私信</h1>
                <?php $unread_total = qf_pm_unread_count($uid); ?>
                <?php if ($unread_total > 0) { ?>
                    <span class="phpdo-messages-unread-total"><?php echo intval($unread_total); ?> 条未读</span>
                <?php } ?>
            </header>
            <div class="phpdo-messages-thread-list">
                <?php if (!empty($threads)) { ?>
                    <?php foreach ($threads as $thread) {
                        $peer_id = qf_pm_thread_peer_id($thread, $uid);
                        $peer = qf_pm_user_brief($peer_id);
                        if (!$peer) {
                            continue;
                        }
                        $is_active = ($active_thread_id === intval($thread['id']));
                        $preview = qf_pm_excerpt(isset($thread['last_body']) ? $thread['last_body'] : '');
                        if ($preview === '') {
                            $preview = '暂无消息';
                        }
                        if (intval($thread['last_sender_id']) === $uid && $preview !== '暂无消息') {
                            $preview = '我：' . $preview;
                        }
                        ?>
                        <a class="phpdo-messages-thread<?php echo $is_active ? ' is-active' : ''; ?><?php echo intval($thread['unread_count']) > 0 ? ' is-unread' : ''; ?>" href="<?php echo h(qf_url_messages(intval($thread['id']))); ?>">
                            <img class="phpdo-messages-thread-avatar" src="<?php echo h(qf_user_avatar($peer, 96)); ?>" alt="" width="44" height="44" loading="lazy">
                            <span class="phpdo-messages-thread-main">
                                <span class="phpdo-messages-thread-top">
                                    <strong><?php echo h(qf_user_display_name($peer)); ?></strong>
                                    <time><?php echo h(qf_time_ago(isset($thread['last_at']) ? $thread['last_at'] : $thread['updated_at'])); ?></time>
                                </span>
                                <span class="phpdo-messages-thread-preview"><?php echo h($preview); ?></span>
                            </span>
                            <?php if (intval($thread['unread_count']) > 0) { ?>
                                <span class="phpdo-messages-thread-badge"><?php echo intval($thread['unread_count']) > 99 ? '99+' : intval($thread['unread_count']); ?></span>
                            <?php } ?>
                        </a>
                    <?php } ?>
                <?php } else { ?>
                    <div class="phpdo-messages-empty-list">
                        <p>还没有私信会话</p>
                        <span>在用户主页点击「发私信」开始聊天</span>
                    </div>
                <?php } ?>
            </div>
        </aside>

        <div class="phpdo-messages-main">
            <?php if ($active_thread && $active_peer) { ?>
                <header class="phpdo-messages-main-head">
                    <a class="phpdo-messages-back sm:hidden" href="<?php echo h(qf_url_messages()); ?>" aria-label="返回会话列表">
                        <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
                    </a>
                    <img class="phpdo-messages-peer-avatar" src="<?php echo h(qf_user_avatar($active_peer, 96)); ?>" alt="" width="40" height="40" loading="lazy">
                    <div class="phpdo-messages-peer-meta">
                        <strong><?php echo h(qf_user_display_name($active_peer)); ?></strong>
                        <a href="<?php echo h(qf_url_user(intval($active_peer['id']))); ?>">查看主页</a>
                    </div>
                    <form method="post" class="phpdo-messages-hide-form" data-confirm="确定删除这个会话？仅对你隐藏，对方仍可见历史记录。">
                        <?php echo qf_csrf_field(); ?>
                        <input type="hidden" name="thread_id" value="<?php echo intval($active_thread_id); ?>">
                        <button type="submit" name="hide_thread" value="1" class="phpdo-messages-hide-btn" aria-label="删除会话" title="删除会话">
                            <i class="fa-regular fa-trash-can" aria-hidden="true"></i>
                        </button>
                    </form>
                </header>

                <div class="phpdo-messages-stream" data-pm-stream data-thread-id="<?php echo intval($active_thread_id); ?>">
                    <?php if (!empty($messages)) { ?>
                        <?php foreach ($messages as $msg) {
                            $mine = intval($msg['sender_id']) === $uid;
                            ?>
                            <article class="phpdo-msg-row<?php echo $mine ? ' is-mine' : ' is-peer'; ?>" data-message-id="<?php echo intval($msg['id']); ?>">
                                <?php if (!$mine) { ?>
                                    <img class="phpdo-msg-avatar" src="<?php echo h(qf_user_avatar($active_peer, 64)); ?>" alt="" width="32" height="32" loading="lazy">
                                <?php } ?>
                                <div class="phpdo-msg-bubble">
                                    <p><?php echo nl2br(h($msg['body'])); ?></p>
                                    <time><?php echo qf_time_html($msg['created_at']); ?></time>
                                </div>
                            </article>
                        <?php } ?>
                    <?php } else { ?>
                        <div class="phpdo-messages-empty-chat">
                            <p>开始和 <?php echo h(qf_user_display_name($active_peer)); ?> 的对话吧</p>
                        </div>
                    <?php } ?>
                </div>

                <?php if ($error !== '') { ?><div class="alert error phpdo-messages-alert"><?php echo h($error); ?></div><?php } ?>

                <form class="phpdo-messages-compose" method="post" data-pm-form data-pm-api="<?php echo h(qf_url_page('api/messages.php')); ?>">
                    <?php echo qf_csrf_field(); ?>
                    <input type="hidden" name="thread_id" value="<?php echo intval($active_thread_id); ?>">
                    <input type="hidden" name="to_id" value="<?php echo intval($active_peer['id']); ?>">
                    <label class="sr-only" for="pm-body">输入私信内容</label>
                    <textarea id="pm-body" name="body" rows="3" maxlength="2000" placeholder="输入消息，Enter 发送，Shift+Enter 换行" required data-pm-input></textarea>
                    <div class="phpdo-messages-compose-actions">
                        <span class="phpdo-messages-compose-hint">最多 2000 字</span>
                        <button type="submit" name="send_pm" value="1" class="btn btn-solid">发送</button>
                    </div>
                </form>
            <?php } else { ?>
                <div class="phpdo-messages-placeholder">
                    <div class="phpdo-messages-placeholder-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                        </svg>
                    </div>
                    <h2>选择左侧会话</h2>
                    <p>或前往用户主页发起私信</p>
                    <?php if ($error !== '') { ?><div class="alert error"><?php echo h($error); ?></div><?php } ?>
                </div>
            <?php } ?>
        </div>
    </div>
</section>
<?php qf_include_footer(); ?>
