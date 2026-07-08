<?php
require_once __DIR__ . '/../functions.php';
$slug = isset($_GET['slug']) ? preg_replace('/[^a-z0-9-]+/', '', strtolower((string)$_GET['slug'])) : '';
$page = pd_static_page($slug);
if (!$page) {
    http_response_code(404);
    exit('页面不存在');
}
$page_title = $page['title'] . ' - ' . SITE_NAME;
pd_include_header(true);
?>
<div class="pd-info pd-info-page">
    <div class="pd-breadcrumb">
        <a href="<?php echo h(pd_url_page('index.php')); ?>"><i class="fa-solid fa-house" aria-hidden="true"></i></a>
        <span>»</span>
        <strong><?php echo h($page['title']); ?></strong>
    </div>

    <?php if (!empty($page['view'])) {
        // 受信任的静态分部；分部自行输出 .pd-info-block 卡片（可一或多张）
        $view_file = __DIR__ . '/legal/' . preg_replace('/[^a-z0-9_-]+/', '', $page['view']) . '.php';
        if (is_file($view_file)) { include $view_file; }
    } else { ?>
        <section class="pd-info-block">
            <h1><?php echo h($page['title']); ?></h1>
            <?php foreach ($page['body'] as $paragraph) { ?>
                <p><?php echo h($paragraph); ?></p>
            <?php } ?>
        </section>
    <?php } ?>

    <nav class="pd-info-links" aria-label="信息页导航">
        <a href="<?php echo h(pd_url_page('about.php')); ?>">关于</a>
        <?php foreach (pd_static_pages() as $item_slug => $item) { ?>
            <a class="<?php echo $item_slug === $slug ? 'active' : ''; ?>" href="<?php echo h(pd_url_page('page.php', array('slug' => $item_slug))); ?>"><?php echo h($item['title']); ?></a>
        <?php } ?>
    </nav>
</div>
<?php pd_include_footer(); ?>
