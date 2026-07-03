<?php
require_once __DIR__ . '/db.php';
$u = require_login();
if (ip_banned(client_ip())) exit('当前 IP 已被封禁');
$fid = isset($_GET['fid']) ? intval($_GET['fid']) : 0;
$error = '';
$mute_message = qf_user_mute_message($u);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($mute_message !== '') {
        $error = $mute_message;
    } elseif (!empty($_SESSION['last_post_at']) && time() - intval($_SESSION['last_post_at']) < POST_INTERVAL) {
        $error = '发帖太快了，请稍后再试。';
    } else {
        $fid = intval($_POST['forum_id']);
        $topic_category = '';
        if (qf_topic_category_enabled($fid)) {
            $candidate = clean_text(isset($_POST['topic_category']) ? $_POST['topic_category'] : '', 40);
            if (in_array($candidate, qf_topic_categories($fid))) {
                $topic_category = $candidate;
            }
        }
        $title = clean_text($_POST['title'], 100);
        $content = clean_text($_POST['content'], 10000);
        if ($fid < 1 || $title === '' || $content === '') {
            $error = '请完整填写版块、标题和内容。';
        } elseif (qf_captcha_required('post', $u) && !qf_verify_captcha()) {
            $error = '验证码错误，请重新输入。';
        } elseif (!qf_forum_post_allowed($fid, intval($u['id']))) {
            $error = '只有指定用户ID可以在该版块发帖。';
        } else {
            $uid = intval($u['id']);
            $ip = esc(client_ip());
            $topic_category_sql = esc($topic_category);
            $title_sql = esc($title);
            $content_sql = esc($content);
            $ok = mysqli_query(db(), "INSERT INTO qf_threads (forum_id,user_id,topic_category,title,content,ip,created_at,updated_at) VALUES ({$fid},{$uid},'{$topic_category_sql}','{$title_sql}','{$content_sql}','{$ip}',NOW(),NOW())");
            if ($ok) {
                $thread_id = mysqli_insert_id(db());
                $upload_errors = array();
                $upload_saved = qf_upload_attachments($thread_id, 0, $uid, $upload_errors);
                if ($upload_saved > 0 && empty($upload_errors)) {
                    $_SESSION['flash'] = '发帖成功，附件/图片上传成功';
                } elseif ($upload_saved > 0 && !empty($upload_errors)) {
                    $_SESSION['flash'] = "发帖成功，附件/图片上传成功，部分文件上传失败：\n" . implode("\n", $upload_errors);
                } elseif (!empty($upload_errors)) {
                    $_SESSION['flash'] = "发帖成功，但附件/图片上传失败：\n" . implode("\n", $upload_errors);
                } else {
                    $_SESSION['flash'] = '发帖成功';
                }
                $_SESSION['last_post_at'] = time();
                redirect(qf_url_thread($thread_id));
            } else {
                $error = '发布失败：' . mysqli_error(db());
            }
        }
    }
}
$forums = mysqli_query(db(), "SELECT * FROM qf_forums ORDER BY display_order ASC, id ASC");
$forum_rows = array();
$forum_category_map = array();
while ($forums && $f = mysqli_fetch_assoc($forums)) {
    $forum_rows[] = $f;
    $forum_category_map[intval($f['id'])] = intval($f['topic_category_enabled']) ? qf_topic_categories(intval($f['id'])) : array();
}
$page_title = '发布新帖 - ' . SITE_NAME;
include __DIR__ . '/header.php';
?>
<section class="card post-form-card">
    <h1>发布新帖</h1>
    <?php if ($error) { ?><div class="alert"><?php echo h($error); ?></div><?php } ?>
    <form method="post" enctype="multipart/form-data">
        <label>选择版块</label>
        <select name="forum_id" required>
            <?php foreach ($forum_rows as $f) { ?>
                <option value="<?php echo intval($f['id']); ?>" <?php if ($fid == intval($f['id'])) echo 'selected'; ?>><?php echo h($f['name']); ?></option>
            <?php } ?>
        </select>
        <div id="topic-category-box" class="forum-category-map">
            <label>主题分类</label>
            <select name="topic_category" id="topic-category-select">
                <option value="">不选择分类</option>
                <?php foreach (qf_topic_categories($fid) as $cat) { ?>
                    <option value="<?php echo h($cat); ?>"><?php echo h($cat); ?></option>
                <?php } ?>
            </select>
        </div>
        <label>标题</label>
        <input type="text" name="title" maxlength="100" required>
        <label>内容</label>
        <div class="editor-toolbar">
            <button type="button" data-wrap="[font=宋体]" data-close="[/font]">字体</button>
            <button type="button" data-wrap="[size=18]" data-close="[/size]">字体大小</button>
            <button type="button" data-wrap="[b]" data-close="[/b]">加粗</button>
            <button type="button" data-link="1">超链接</button>
            <button type="button" data-remote-img="1">远程图片</button>
        </div>
        <textarea class="post-content-textarea" id="post-content-textarea" name="content" rows="16" required></textarea>
        <div class="upload-captcha-row">
            <div class="captcha-col"><?php if (qf_captcha_required('post', $u)) { echo qf_render_captcha(); } ?></div>
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
                    <input class="qf-instant-upload" data-target="post-content-textarea" data-status="post-upload-status" type="file" name="attachments[]" multiple accept=".jpg,.jpeg,.png,.gif,.webp,.zip,.rar">
                </label>
                <button class="upload-help" type="button">?</button>
                <p class="muted upload-tip">支持 <?php echo h(qf_upload_allowed_exts_label()); ?>，单个文件最大 <?php echo intval(qf_upload_max_mb()); ?>MB。</p>
                <p id="post-upload-status" class="muted upload-status"></p>
            </div>
        </div>
        <button class="btn" type="submit">发帖</button>
    </form>
</section>
<script>window.qfForumCategories = <?php echo json_encode($forum_category_map); ?>;</script>
<script src="<?php echo h(qf_asset_js('editor')); ?>"></script>
<?php include __DIR__ . '/footer.php'; ?>
