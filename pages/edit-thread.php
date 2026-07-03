<?php
require_once __DIR__ . '/../functions.php';
require_admin();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$rs = mysqli_query(db(), "SELECT * FROM qf_threads WHERE id={$id} AND is_deleted=0 LIMIT 1");
$thread = $rs ? mysqli_fetch_assoc($rs) : null;
if (!$thread) {
    exit('帖子不存在');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = clean_text($_POST['title'], 100);
    $topic_category = '';
    if (qf_topic_category_enabled(intval($thread['forum_id']))) {
        $candidate = clean_text(isset($_POST['topic_category']) ? $_POST['topic_category'] : '', 40);
        if (in_array($candidate, qf_topic_categories(intval($thread['forum_id'])))) {
            $topic_category = $candidate;
        }
    }
    $content = clean_text($_POST['content'], 10000);
    if ($title === '' || $content === '') {
        $error = '标题和内容不能为空。';
    } else {
        $title_sql = esc($title);
        $topic_category_sql = esc($topic_category);
        $content_sql = esc($content);
        mysqli_query(db(), "UPDATE qf_threads SET topic_category='{$topic_category_sql}', title='{$title_sql}', content='{$content_sql}', updated_at=NOW() WHERE id={$id}");

        $upload_errors = array();
        $me = current_user();
        qf_upload_attachments($id, 0, intval($me['id']), $upload_errors);
        if (!empty($upload_errors)) {
            $_SESSION['flash'] = implode("\n", $upload_errors);
        }
        redirect(qf_url_thread($id));
    }
}

$attachments = mysqli_query(db(), "SELECT * FROM qf_attachments WHERE thread_id={$id} AND post_id=0 ORDER BY id ASC");
$page_title = '编辑帖子 - ' . SITE_NAME;
qf_include_header();
?>
<section class="card post-form-card">
    <h1>编辑帖子</h1>
    <?php if ($error) { ?><div class="alert"><?php echo h($error); ?></div><?php } ?>
    <form method="post" enctype="multipart/form-data">
        <label>标题</label>
        <input type="text" name="title" maxlength="100" value="<?php echo h($thread['title']); ?>" required>

        <?php if (qf_topic_category_enabled(intval($thread['forum_id']))) { ?>
            <label>主题分类</label>
            <select name="topic_category">
                <option value="">不选择分类</option>
                <?php foreach (qf_topic_categories(intval($thread['forum_id'])) as $cat) { ?>
                    <option value="<?php echo h($cat); ?>" <?php if ($thread['topic_category'] === $cat) echo 'selected'; ?>><?php echo h($cat); ?></option>
                <?php } ?>
            </select>
        <?php } ?>

        <label>内容</label>
        <div class="editor-toolbar">
            <button type="button" data-wrap="[font=宋体]" data-close="[/font]">字体</button>
            <button type="button" data-wrap="[size=18]" data-close="[/size]">字体大小</button>
            <button type="button" data-wrap="[b]" data-close="[/b]">加粗</button>
            <button type="button" data-link="1">超链接</button>
            <button type="button" data-remote-img="1">远程图片</button>
        </div>
        <textarea class="post-content-textarea" name="content" rows="16" required><?php echo h($thread['content']); ?></textarea>

        <?php if ($attachments && mysqli_num_rows($attachments) > 0) { ?>
            <div class="attachment-list">
                <h3>已有附件</h3>
                <?php while ($att = mysqli_fetch_assoc($attachments)) { ?>
                    <a class="attachment-file" href="<?php echo h(qf_attachment_url($att['id'])); ?>" target="_blank"><?php echo h($att['original_name']); ?></a>
                <?php } ?>
            </div>
        <?php } ?>

        <div class="upload-captcha-row">
            <div class="captcha-col"></div>
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
                    <input type="file" name="attachments[]" multiple accept=".jpg,.jpeg,.png,.gif,.webp,.zip,.rar">
                </label>
                <button class="upload-help" type="button">?</button>
                <p class="muted upload-tip">支持 <?php echo h(qf_upload_allowed_exts_label()); ?>，单个文件最大 <?php echo intval(qf_upload_max_mb()); ?>MB。</p>
            </div>
        </div>

        <button class="btn" type="submit">保存修改</button>
        <a class="btn btn-light" href="<?php echo h(qf_url_thread($id)); ?>">返回帖子</a>
    </form>
</section>
<script src="<?php echo h(qf_asset_js('editor')); ?>"></script>
<?php qf_include_footer(); ?>
