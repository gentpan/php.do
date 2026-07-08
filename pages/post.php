<?php
require_once __DIR__ . '/../functions.php';
$u = require_login();
if (ip_banned(client_ip())) exit('当前 IP 已被封禁');
$fid = isset($_GET['fid']) ? intval($_GET['fid']) : 0;
$error = '';
$mute_message = pd_user_mute_message($u);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($mute_message !== '') {
        $error = $mute_message;
    } elseif (!empty($_SESSION['last_post_at']) && time() - intval($_SESSION['last_post_at']) < POST_INTERVAL) {
        $error = '发帖太快了，请稍后再试。';
    } else {
        $fid = intval($_POST['forum_id']);
        $topic_category = '';
        if (pd_topic_category_enabled($fid)) {
            $candidate = clean_text(isset($_POST['topic_category']) ? $_POST['topic_category'] : '', 40);
            if (in_array($candidate, pd_topic_categories($fid))) {
                $topic_category = $candidate;
            }
        }
        $title = clean_text($_POST['title'], 100);
        $content = clean_text($_POST['content'], 10000);
        if ($fid < 1 || $title === '' || $content === '') {
            $error = '请完整填写版块、标题和内容。';
        } elseif (pd_captcha_required('post', $u) && !pd_verify_captcha()) {
            $error = '验证码错误，请重新输入。';
        } elseif (!pd_forum_post_allowed($fid, intval($u['id']))) {
            $error = '只有指定用户ID可以在该版块发帖。';
        } else {
            $uid = intval($u['id']);
            $ip = esc(client_ip());
            $topic_category_sql = esc($topic_category);
            $title_sql = esc($title);
            $content_sql = esc($content);
            $ok = mysqli_query(db(), "INSERT INTO pd_threads (forum_id,user_id,topic_category,title,content,ip,created_at,updated_at) VALUES ({$fid},{$uid},'{$topic_category_sql}','{$title_sql}','{$content_sql}','{$ip}',NOW(),NOW())");
            if ($ok) {
                $thread_id = mysqli_insert_id(db());
                pd_add_user_points($uid, pd_points_for_thread(), 'thread', 'thread', $thread_id);
                $upload_errors = array();
                $upload_saved = pd_upload_attachments($thread_id, 0, $uid, $upload_errors);
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
                redirect(pd_url_thread($thread_id));
            } else {
                $error = '发布失败：' . mysqli_error(db());
            }
        }
    }
}
$forums = mysqli_query(db(), "SELECT * FROM pd_forums ORDER BY display_order ASC, id ASC");
$forum_rows = array();
$forum_category_map = array();
while ($forums && $f = mysqli_fetch_assoc($forums)) {
    $forum_rows[] = $f;
    $forum_category_map[intval($f['id'])] = intval($f['topic_category_enabled']) ? pd_topic_categories(intval($f['id'])) : array();
}
$page_title = '发布新帖 - ' . SITE_NAME;
pd_include_header();
?>
<nav class="pd-thread-breadcrumb" aria-label="面包屑导航">
    <div class="pd-crumb-trail">
        <a href="<?php echo h(pd_url_page('index.php')); ?>"><i class="fa-solid fa-house" aria-hidden="true"></i><span>首页</span></a>
        <span class="pd-crumb-sep">›</span>
        <span class="pd-crumb-current">发布新帖</span>
    </div>
</nav>
<section class="pd-post-page">
    <?php if ($error) { ?><div class="alert"><?php echo h($error); ?></div><?php } ?>
    <form class="pd-post-form" method="post" enctype="multipart/form-data">
        <div class="pd-post-meta">
            <div class="pd-post-field pd-post-field-title">
                <label for="post-title">标题</label>
                <input id="post-title" type="text" name="title" maxlength="100" required placeholder="一句话说清主题">
            </div>
            <div class="pd-post-field pd-post-field-forum">
                <label for="post-forum-id">版块</label>
                <select id="post-forum-id" name="forum_id" required>
                    <?php foreach ($forum_rows as $f) { ?>
                        <option value="<?php echo intval($f['id']); ?>" <?php if ($fid == intval($f['id'])) echo 'selected'; ?>><?php echo h($f['name']); ?></option>
                    <?php } ?>
                </select>
            </div>
            <div id="topic-category-box" class="pd-post-field pd-post-field-category forum-category-map">
                <label for="topic-category-select">主题分类</label>
                <select name="topic_category" id="topic-category-select">
                    <option value="">不选择分类</option>
                    <?php foreach (pd_topic_categories($fid) as $cat) { ?>
                        <option value="<?php echo h($cat); ?>"><?php echo h($cat); ?></option>
                    <?php } ?>
                </select>
            </div>
        </div>
        <div class="pd-post-editor">
            <label for="post-content-textarea">内容</label>
            <?php
            $editorId = 'post-content-textarea';
            $editorName = 'content';
            $editorValue = '';
            $editorRows = 22;
            $editorRequired = true;
            $editorCompact = false;
            include __DIR__ . '/../parts/markdown-editor.php';
            ?>
        </div>
        <div class="pd-post-actions">
            <div class="upload-captcha-row">
                <div class="captcha-col"><?php if (pd_captcha_required('post', $u)) { echo pd_render_captcha(); } ?></div>
            </div>
            <button class="btn" type="submit">发布帖子</button>
        </div>
    </form>
</section>
<script>window.pdForumCategories = <?php echo json_encode($forum_category_map); ?>;</script>
<script src="<?php echo h(pd_asset_js('admin')); ?>"></script>
<?php pd_include_footer(); ?>
