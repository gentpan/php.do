<?php
require_once __DIR__ . '/../functions.php';
require_admin();

$saved = false;
$s3_test_message = '';
$s3_test_ok = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 's3_test') {
        $s3_enabled = !empty($_POST['s3_enabled']) ? '1' : '0';
        $s3_endpoint = rtrim(trim((string)(isset($_POST['s3_endpoint']) ? $_POST['s3_endpoint'] : '')), '/');
        $s3_region = clean_text(isset($_POST['s3_region']) ? $_POST['s3_region'] : 'auto', 80);
        $s3_bucket = clean_text(isset($_POST['s3_bucket']) ? $_POST['s3_bucket'] : '', 120);
        $s3_access_key = clean_text(isset($_POST['s3_access_key']) ? $_POST['s3_access_key'] : '', 180);
        $s3_secret_key = clean_text(isset($_POST['s3_secret_key']) ? $_POST['s3_secret_key'] : '', 220);
        $s3_cdn_domain = rtrim(trim((string)(isset($_POST['s3_cdn_domain']) ? $_POST['s3_cdn_domain'] : '')), '/');
        $s3_path_prefix = trim((string)(isset($_POST['s3_path_prefix']) ? $_POST['s3_path_prefix'] : 'lume'));
        if ($s3_region === '') {
            $s3_region = 'auto';
        }
        $s3_path_prefix = preg_replace('/[^a-zA-Z0-9_\-\/]/', '', $s3_path_prefix);
        if ($s3_path_prefix === '') {
            $s3_path_prefix = 'lume';
        }
        qf_update_setting('s3_enabled', $s3_enabled);
        qf_update_setting('s3_endpoint', $s3_endpoint);
        qf_update_setting('s3_region', $s3_region);
        qf_update_setting('s3_bucket', $s3_bucket);
        qf_update_setting('s3_access_key', $s3_access_key);
        qf_update_setting('s3_secret_key', $s3_secret_key);
        qf_update_setting('s3_cdn_domain', $s3_cdn_domain);
        qf_update_setting('s3_path_prefix', $s3_path_prefix);
        $s3_test_ok = qf_s3_test($s3_test_message);
    } else {
    $site_title = clean_text($_POST['site_title'], 80);
    $site_name = clean_text($_POST['site_name'], 80);
    $site_desc = clean_text($_POST['site_desc'], 180);
    $site_keywords = clean_text($_POST['site_keywords'], 160);
    $theme_name = clean_text(isset($_POST['theme_name']) ? $_POST['theme_name'] : 'light-blue', 40);
    $title_font = clean_text(isset($_POST['title_font']) ? $_POST['title_font'] : 'system', 60);
    $content_font = clean_text(isset($_POST['content_font']) ? $_POST['content_font'] : 'system', 60);
    $icp_code = trim((string)$_POST['icp_code']);
    $stats_code = trim((string)$_POST['stats_code']);
    $upload_max_mb = intval($_POST['upload_max_mb']);
    $upload_allowed_exts = strtolower(trim((string)$_POST['upload_allowed_exts']));
    $guest_download_enabled = !empty($_POST['guest_download_enabled']) ? '1' : '0';
    $home_threads_per_page = intval($_POST['home_threads_per_page']);
    $forum_threads_per_page = intval($_POST['forum_threads_per_page']);
    $thread_page_chars = intval($_POST['thread_page_chars']);
    $reply_max_chars = intval($_POST['reply_max_chars']);
    $signin_base_coins = intval($_POST['signin_base_coins']);
    $signin_streak_bonus = intval($_POST['signin_streak_bonus']);
    $register_ip_daily_limit = intval($_POST['register_ip_daily_limit']);
    $captcha_enabled = !empty($_POST['captcha_enabled']) ? '1' : '0';
    $captcha_reply_free_count = intval($_POST['captcha_reply_free_count']);
    $qiniu_enabled = !empty($_POST['qiniu_enabled']) ? '1' : '0';
    $qiniu_access_key = clean_text($_POST['qiniu_access_key'], 120);
    $qiniu_secret_key = clean_text($_POST['qiniu_secret_key'], 120);
    $qiniu_bucket = clean_text($_POST['qiniu_bucket'], 120);
    $qiniu_domain = rtrim(trim((string)$_POST['qiniu_domain']), '/');
    $qiniu_upload_host = rtrim(trim((string)$_POST['qiniu_upload_host']), '/');
    $s3_enabled = !empty($_POST['s3_enabled']) ? '1' : '0';
    $s3_endpoint = rtrim(trim((string)$_POST['s3_endpoint']), '/');
    $s3_region = clean_text(isset($_POST['s3_region']) ? $_POST['s3_region'] : 'auto', 80);
    $s3_bucket = clean_text(isset($_POST['s3_bucket']) ? $_POST['s3_bucket'] : '', 120);
    $s3_access_key = clean_text(isset($_POST['s3_access_key']) ? $_POST['s3_access_key'] : '', 180);
    $s3_secret_key = clean_text(isset($_POST['s3_secret_key']) ? $_POST['s3_secret_key'] : '', 220);
    $s3_cdn_domain = rtrim(trim((string)(isset($_POST['s3_cdn_domain']) ? $_POST['s3_cdn_domain'] : '')), '/');
    $s3_path_prefix = trim((string)(isset($_POST['s3_path_prefix']) ? $_POST['s3_path_prefix'] : 'lume'));
    $friend_links_enabled = !empty($_POST['friend_links_enabled']) ? '1' : '0';
    $friend_links = trim((string)$_POST['friend_links']);
    $rewrite_enabled = !empty($_POST['rewrite_enabled']) ? '1' : '0';
    $rewrite_nginx_rules = trim((string)$_POST['rewrite_nginx_rules']);

    if ($site_title === '') {
        $site_title = SITE_NAME;
    }
    if ($site_name === '') {
        $site_name = SITE_NAME;
    }
    if ($site_desc === '') {
        $site_desc = SITE_DESC;
    }
    $theme_options = qf_theme_options();
    if (!isset($theme_options[$theme_name])) {
        $theme_name = 'light-blue';
    }
    $font_options = qf_font_options();
    if (!isset($font_options[$title_font])) {
        $title_font = 'system';
    }
    if (!isset($font_options[$content_font])) {
        $content_font = 'system';
    }
    $upload_allowed_exts = preg_replace('/[^a-z0-9,，\.\s|]/', '', $upload_allowed_exts);
    if ($upload_allowed_exts === '') {
        $upload_allowed_exts = 'jpg,jpeg,png,gif,webp,zip,rar';
    }
    if ($upload_max_mb < 1) {
        $upload_max_mb = 5;
    }
    if ($upload_max_mb > 50) {
        $upload_max_mb = 50;
    }
    if ($home_threads_per_page < 1) {
        $home_threads_per_page = 12;
    }
    if ($home_threads_per_page > 100) {
        $home_threads_per_page = 100;
    }
    if ($forum_threads_per_page < 1) {
        $forum_threads_per_page = 60;
    }
    if ($forum_threads_per_page > 200) {
        $forum_threads_per_page = 200;
    }
    if ($thread_page_chars < 500) {
        $thread_page_chars = 4000;
    }
    if ($thread_page_chars > 50000) {
        $thread_page_chars = 50000;
    }
    if ($reply_max_chars < 100) {
        $reply_max_chars = 1000;
    }
    if ($reply_max_chars > 50000) {
        $reply_max_chars = 50000;
    }
    if ($signin_base_coins < 0) {
        $signin_base_coins = 0;
    }
    if ($signin_streak_bonus < 0) {
        $signin_streak_bonus = 0;
    }
    if ($register_ip_daily_limit < 1) {
        $register_ip_daily_limit = 5;
    }
    if ($register_ip_daily_limit > 100) {
        $register_ip_daily_limit = 100;
    }
    if ($captcha_reply_free_count < 0) {
        $captcha_reply_free_count = 0;
    }
    if ($captcha_reply_free_count > 10000) {
        $captcha_reply_free_count = 10000;
    }
    if ($qiniu_upload_host === '') {
        $qiniu_upload_host = 'https://upload.qiniup.com';
    }
    if ($s3_region === '') {
        $s3_region = 'auto';
    }
    $s3_path_prefix = preg_replace('/[^a-zA-Z0-9_\-\/]/', '', $s3_path_prefix);
    if ($s3_path_prefix === '') {
        $s3_path_prefix = 'lume';
    }
    if ($rewrite_nginx_rules === '') {
        $rewrite_nginx_rules = qf_default_nginx_rewrite_rules();
    }

    qf_update_setting('site_title', $site_title);
    qf_update_setting('site_name', $site_name);
    qf_update_setting('site_desc', $site_desc);
    qf_update_setting('site_keywords', $site_keywords);
    qf_update_setting('theme_name', $theme_name);
    qf_update_setting('title_font', $title_font);
    qf_update_setting('content_font', $content_font);
    qf_update_setting('icp_code', $icp_code);
    qf_update_setting('stats_code', $stats_code);
    qf_update_setting('upload_max_mb', strval($upload_max_mb));
    qf_update_setting('upload_allowed_exts', $upload_allowed_exts);
    qf_update_setting('guest_download_enabled', $guest_download_enabled);
    qf_update_setting('home_threads_per_page', strval($home_threads_per_page));
    qf_update_setting('forum_threads_per_page', strval($forum_threads_per_page));
    qf_update_setting('thread_page_chars', strval($thread_page_chars));
    qf_update_setting('reply_max_chars', strval($reply_max_chars));
    qf_update_setting('signin_base_coins', strval($signin_base_coins));
    qf_update_setting('signin_streak_bonus', strval($signin_streak_bonus));
    qf_update_setting('register_ip_daily_limit', strval($register_ip_daily_limit));
    qf_update_setting('captcha_enabled', $captcha_enabled);
    qf_update_setting('captcha_reply_free_count', strval($captcha_reply_free_count));
    qf_update_setting('qiniu_enabled', $qiniu_enabled);
    qf_update_setting('qiniu_access_key', $qiniu_access_key);
    qf_update_setting('qiniu_secret_key', $qiniu_secret_key);
    qf_update_setting('qiniu_bucket', $qiniu_bucket);
    qf_update_setting('qiniu_domain', $qiniu_domain);
    qf_update_setting('qiniu_upload_host', $qiniu_upload_host);
    qf_update_setting('s3_enabled', $s3_enabled);
    qf_update_setting('s3_endpoint', $s3_endpoint);
    qf_update_setting('s3_region', $s3_region);
    qf_update_setting('s3_bucket', $s3_bucket);
    qf_update_setting('s3_access_key', $s3_access_key);
    qf_update_setting('s3_secret_key', $s3_secret_key);
    qf_update_setting('s3_cdn_domain', $s3_cdn_domain);
    qf_update_setting('s3_path_prefix', $s3_path_prefix);
    qf_update_setting('friend_links_enabled', $friend_links_enabled);
    qf_update_setting('friend_links', $friend_links);
    qf_update_setting('rewrite_enabled', $rewrite_enabled);
    qf_update_setting('rewrite_nginx_rules', $rewrite_nginx_rules);
    $saved = true;
    }
}

$page_title = '站点设置 - ' . SITE_NAME;
include __DIR__ . '/../header.php';
?>
<section class="card">
    <div class="admin-page-title">
        <h1>站点设置</h1>
    </div>
    <p class="admin-back-row"><a class="btn btn-light btn-small" href="<?php echo h(qf_url_page('admin/index.php')); ?>">返回后台</a></p>
    <?php if ($saved) { ?><div class="alert success">设置已保存。</div><?php } ?>
    <?php if ($s3_test_message !== '') { ?><div class="alert <?php echo $s3_test_ok ? 'success' : ''; ?>"><?php echo h($s3_test_message); ?></div><?php } ?>
    <form method="post">
        <label>站点名称（浏览器里显示的）</label>
        <input type="text" name="site_title" value="<?php echo h(qf_setting('site_title', SITE_NAME)); ?>" maxlength="80">

        <label>网站名称（网站内部的）</label>
        <input type="text" name="site_name" value="<?php echo h(qf_setting('site_name', SITE_NAME)); ?>" maxlength="80">

        <label>网站简介</label>
        <textarea name="site_desc" rows="3" maxlength="180"><?php echo h(qf_setting('site_desc', SITE_DESC)); ?></textarea>
        <p class="muted">显示在首页顶部网站名称下面，也会作为页面描述。</p>

        <label>关键词（KeyWords）</label>
        <input type="text" name="site_keywords" value="<?php echo h(qf_setting('site_keywords', '')); ?>" maxlength="160" placeholder="例如：Lume,轻论坛,本地论坛,同城交流">
        <p class="muted">多个关键词建议用英文逗号分隔，例如：Lume,轻论坛,本地论坛。</p>

        <label>网站配色风格</label>
        <select name="theme_name">
            <?php foreach (qf_theme_options() as $theme_key => $theme_label) { ?>
                <option value="<?php echo h($theme_key); ?>" <?php if (qf_theme() === $theme_key) echo 'selected'; ?>><?php echo h($theme_label); ?></option>
            <?php } ?>
        </select>
        <p class="muted">包含白色蓝色、LiteNote 淡黄、黑色款三种纯色风格。</p>

        <label>标题字体</label>
        <select name="title_font">
            <?php foreach (qf_font_options() as $font_key => $font_item) { ?>
                <option value="<?php echo h($font_key); ?>" <?php if (qf_font_key('title_font') === $font_key) echo 'selected'; ?>><?php echo h($font_item['label']); ?></option>
            <?php } ?>
        </select>
        <p class="muted">用于站点名、标题、卡片标题等。只加载当前选择的字体。</p>

        <label>内容字体</label>
        <select name="content_font">
            <?php foreach (qf_font_options() as $font_key => $font_item) { ?>
                <option value="<?php echo h($font_key); ?>" <?php if (qf_font_key('content_font') === $font_key) echo 'selected'; ?>><?php echo h($font_item['label']); ?></option>
            <?php } ?>
        </select>
        <p class="muted">用于正文、表单、列表内容等。标题和内容相同字体时只加载一次。</p>

        <label>网站备案信息代码</label>
        <textarea name="icp_code" rows="3" placeholder="例如：蜀ICP备xxxx号"><?php echo h(qf_setting('icp_code', '')); ?></textarea>
        <p class="muted">会显示在底部版权信息前面，可填写文字或备案链接代码。</p>

        <label>第三方统计代码</label>
        <textarea name="stats_code" rows="6" placeholder="例如百度统计、CNZZ等统计代码"><?php echo h(qf_setting('stats_code', '')); ?></textarea>
        <p class="muted">会输出在页面底部，保存前请确认代码来源可信。</p>

        <label>附件上传大小设置（MB）</label>
        <input type="number" name="upload_max_mb" min="1" max="50" value="<?php echo h(qf_setting('upload_max_mb', '5')); ?>">

        <label>允许上传附件后缀</label>
        <input type="text" name="upload_allowed_exts" value="<?php echo h(qf_setting('upload_allowed_exts', 'jpg,jpeg,png,gif,webp,zip,rar')); ?>">
        <p class="muted">多个后缀用英文逗号分隔，例如：jpg,png,zip,rar。不要填写点号也可以。</p>

        <label>是否允许游客下载附件</label>
        <label><input class="inline-check" type="checkbox" name="guest_download_enabled" value="1" <?php if (qf_guest_download_allowed()) echo 'checked'; ?>> 允许游客下载 zip、rar 压缩附件</label>
        <p class="muted">关闭后，图片仍可直接查看；未登录游客点击 zip、rar 压缩附件时会提示“需要登录才能进行该操作”。</p>

        <label>首页每页显示帖子数</label>
        <input type="number" name="home_threads_per_page" min="1" max="100" value="<?php echo h(qf_setting('home_threads_per_page', '12')); ?>">
        <p class="muted">控制首页“最新帖子”列表显示多少条。</p>

        <label>板块每页显示帖子数</label>
        <input type="number" name="forum_threads_per_page" min="1" max="200" value="<?php echo h(qf_setting('forum_threads_per_page', '60')); ?>">
        <p class="muted">控制每个板块页显示多少条主题帖。</p>

        <label>帖子内容分页字数</label>
        <input type="number" name="thread_page_chars" min="500" max="50000" value="<?php echo h(qf_setting('thread_page_chars', '4000')); ?>">
        <p class="muted">例如设置 4000，主题内容超过 4000 字会显示第 2 页、第 3 页。</p>

        <label>回帖字数限制</label>
        <input type="number" name="reply_max_chars" min="100" max="50000" value="<?php echo h(qf_setting('reply_max_chars', '1000')); ?>">
        <p class="muted">用户每次回帖最多允许输入的字数。</p>

        <label>签到一次获得金币</label>
        <input type="number" name="signin_base_coins" min="0" max="100000" value="<?php echo h(qf_setting('signin_base_coins', '5')); ?>">

        <label>连续签到额外奖励金币</label>
        <input type="number" name="signin_streak_bonus" min="0" max="100000" value="<?php echo h(qf_setting('signin_streak_bonus', '2')); ?>">
        <p class="muted">用户连续签到第 2 天起，除了基础金币外，再额外奖励这里设置的金币。</p>

        <label>单个IP一天内注册次数设置</label>
        <input type="number" name="register_ip_daily_limit" min="1" max="100" value="<?php echo h(qf_setting('register_ip_daily_limit', '5')); ?>">

        <label>验证码</label>
        <label><input class="inline-check" type="checkbox" name="captcha_enabled" value="1" <?php if (qf_captcha_enabled()) echo 'checked'; ?>> 开启验证码</label>
        <p class="muted">开启后，注册、发帖、回帖页面会显示高级图片验证码，并启用蜜罐和提交时间校验。</p>

        <label>回帖满多少次后免验证码</label>
        <input type="number" name="captcha_reply_free_count" min="0" max="10000" value="<?php echo h(qf_setting('captcha_reply_free_count', '10')); ?>">
        <p class="muted">例如填 10，用户累计回帖满 10 次后，发帖和回帖不再需要验证码；注册仍需要验证码。</p>

        <div class="storage-settings-tabs" data-storage-tabs>
            <div class="storage-tabs-head" role="tablist" aria-label="对象存储配置">
                <button class="storage-tab-btn<?php echo $s3_test_message === '' ? ' active' : ''; ?>" type="button" data-storage-tab="qiniu" role="tab" aria-selected="<?php echo $s3_test_message === '' ? 'true' : 'false'; ?>">七牛云</button>
                <button class="storage-tab-btn<?php echo $s3_test_message !== '' ? ' active' : ''; ?>" type="button" data-storage-tab="s3" role="tab" aria-selected="<?php echo $s3_test_message !== '' ? 'true' : 'false'; ?>">S3 / R2</button>
            </div>

            <div class="storage-tab-panel<?php echo $s3_test_message === '' ? ' active' : ''; ?>" data-storage-panel="qiniu" role="tabpanel">
                <h2>七牛云对象存储</h2>
                <label><input class="inline-check" type="checkbox" name="qiniu_enabled" value="1" <?php if (qf_qiniu_enabled()) echo 'checked'; ?>> 开启七牛云上传</label>
                <p class="muted">开启后，发帖、回帖和编辑帖子上传的图片/附件会上传到七牛云；未开启时继续保存到本地 uploads 目录。</p>

                <label>七牛 AccessKey</label>
                <input type="text" name="qiniu_access_key" value="<?php echo h(qf_setting('qiniu_access_key', '')); ?>">

                <label>七牛 SecretKey</label>
                <input type="password" name="qiniu_secret_key" value="<?php echo h(qf_setting('qiniu_secret_key', '')); ?>">

                <label>七牛空间名称 Bucket</label>
                <input type="text" name="qiniu_bucket" value="<?php echo h(qf_setting('qiniu_bucket', '')); ?>">

                <label>七牛绑定域名</label>
                <input type="text" name="qiniu_domain" value="<?php echo h(qf_setting('qiniu_domain', '')); ?>" placeholder="例如：https://img.example.com">
                <p class="muted">请填写带 http:// 或 https:// 的访问域名，用于前台显示图片和附件。</p>

                <label>七牛上传地址</label>
                <input type="text" name="qiniu_upload_host" value="<?php echo h(qf_setting('qiniu_upload_host', 'https://upload.qiniup.com')); ?>">
                <p class="muted">华东常用：https://upload.qiniup.com。其他区域可按七牛云空间所在区域修改。</p>
            </div>

            <div class="storage-tab-panel<?php echo $s3_test_message !== '' ? ' active' : ''; ?>" data-storage-panel="s3" role="tabpanel">
                <h2>S3 / Cloudflare R2 对象存储</h2>
                <label><input class="inline-check" type="checkbox" name="s3_enabled" value="1" <?php if (qf_s3_enabled()) echo 'checked'; ?>> 开启 S3/R2 上传</label>
                <p class="muted">开启后优先上传到 S3/R2；未开启时会继续使用七牛或本地 uploads。R2 Endpoint 示例：https://账号ID.r2.cloudflarestorage.com。</p>

                <label>S3/R2 Endpoint</label>
                <input type="text" name="s3_endpoint" value="<?php echo h(qf_setting('s3_endpoint', '')); ?>" placeholder="例如：https://xxxx.r2.cloudflarestorage.com">

                <label>S3/R2 Region</label>
                <input type="text" name="s3_region" value="<?php echo h(qf_setting('s3_region', 'auto')); ?>" placeholder="R2 通常填写 auto">

                <label>S3/R2 Bucket</label>
                <input type="text" name="s3_bucket" value="<?php echo h(qf_setting('s3_bucket', '')); ?>">

                <label>S3/R2 Access Key ID</label>
                <input type="text" name="s3_access_key" value="<?php echo h(qf_setting('s3_access_key', '')); ?>">

                <label>S3/R2 Secret Access Key</label>
                <input type="password" name="s3_secret_key" value="<?php echo h(qf_setting('s3_secret_key', '')); ?>">

                <label>CDN 访问域名</label>
                <input type="text" name="s3_cdn_domain" value="<?php echo h(qf_setting('s3_cdn_domain', '')); ?>" placeholder="例如：https://cdn.example.com">
                <p class="muted">前台图片和附件会使用这个域名生成访问地址。留空时使用 Endpoint/Bucket 拼接地址。</p>

                <label>存储路径前缀</label>
                <input type="text" name="s3_path_prefix" value="<?php echo h(qf_setting('s3_path_prefix', 'lume')); ?>" placeholder="例如：lume">
                <p class="muted">实际对象路径会形如：lume/年/月/日/文件名。</p>
                <p><button class="btn btn-light btn-small" type="submit" name="action" value="s3_test">测试 S3/R2 上传</button></p>
            </div>
        </div>

        <label>友情链接</label>
        <label><input class="inline-check" type="checkbox" name="friend_links_enabled" value="1" <?php if (qf_friend_links_enabled()) echo 'checked'; ?>> 开启友情链接</label>
        <textarea name="friend_links" rows="5" placeholder="例如：Lume官网|https://lume.0816y.com"><?php echo h(qf_setting('friend_links', '')); ?></textarea>
        <p class="muted">每行一个友情链接，格式：网站名称|网站地址。例如：朋友论坛|https://example.com。</p>

        <label>伪静态</label>
        <label><input class="inline-check" type="checkbox" name="rewrite_enabled" value="1" <?php if (qf_rewrite_enabled()) echo 'checked'; ?>> 开启伪静态链接</label>
        <p class="muted">开启后，前台帖子和版块链接会生成类似 thread/1.html、forum/1.html。请先把下面 Nginx 规则复制到虚拟空间的伪静态配置里。</p>

        <label>Nginx伪静态默认配置</label>
        <textarea name="rewrite_nginx_rules" rows="8"><?php echo h(qf_setting('rewrite_nginx_rules', qf_default_nginx_rewrite_rules())); ?></textarea>
        <p class="muted">先保存上面的规则，确认 thread/1.html 和 forum/1.html 可访问后，再开启伪静态链接。</p>

        <button class="btn" type="submit">保存设置</button>
    </form>
</section>
<script src="<?php echo h(qf_asset_js('admin-settings')); ?>"></script>
<?php include __DIR__ . '/../footer.php'; ?>
