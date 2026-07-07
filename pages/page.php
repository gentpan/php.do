<?php
require_once __DIR__ . '/../functions.php';
$slug = isset($_GET['slug']) ? preg_replace('/[^a-z0-9-]+/', '', strtolower((string)$_GET['slug'])) : '';
$page = qf_static_page($slug);
if (!$page) {
    http_response_code(404);
    exit('页面不存在');
}
$page_title = $page['title'] . ' - ' . SITE_NAME;
qf_include_header();
?>
<section class="card phpdo-page-head">
    <div>
        <h1><?php echo h($page['title']); ?></h1>
        <p><a class="back-home" href="<?php echo h(qf_url_page('index.php')); ?>">返回首页</a></p>
    </div>
</section>
<section class="card phpdo-static-page">
    <?php foreach ($page['body'] as $paragraph) { ?>
        <p><?php echo h($paragraph); ?></p>
    <?php } ?>
    <div class="phpdo-static-links">
        <?php foreach (qf_static_pages() as $item_slug => $item) { ?>
            <a class="<?php echo $item_slug === $slug ? 'active' : ''; ?>" href="<?php echo h(qf_url_page('page.php', array('slug' => $item_slug))); ?>"><?php echo h($item['title']); ?></a>
        <?php } ?>
    </div>
</section>
<?php qf_include_footer(); ?>
