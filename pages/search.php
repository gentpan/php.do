<?php
require_once __DIR__ . '/../functions.php';
$q = isset($_GET['q']) ? clean_text($_GET['q'], 60) : '';
$page_title = ($q !== '' ? $q . ' - ' : '') . '搜索 - ' . SITE_NAME;

$rs = null;
$found = 0;
if ($q !== '') {
    $qs = esc($q);
    $rs = mysqli_query(db(), "SELECT t.*, f.name AS forum_name, u.nickname, u.username, u.avatar, u.email
        FROM pd_threads t
        LEFT JOIN pd_forums f ON t.forum_id=f.id
        LEFT JOIN pd_users u ON t.user_id=u.id
        WHERE t.is_deleted=0 AND (t.title LIKE '%{$qs}%' OR t.content LIKE '%{$qs}%' OR t.topic_category LIKE '%{$qs}%')
        ORDER BY t.updated_at DESC LIMIT 50");
    $found = $rs ? mysqli_num_rows($rs) : 0;
}

pd_include_header();
?>
<div class="pd-search">
    <?php if ($q !== '') { ?>
    <p class="pd-search-summary">找到 <b><?php echo intval($found); ?></b> 条与 “<span><?php echo h($q); ?></span>” 相关的结果</p>
    <section class="pd-search-results thread-list">
        <?php if ($found === 0) { ?>
            <div class="pd-empty">没有找到相关帖子，可以换一个关键词再试。</div>
        <?php } ?>
        <?php while ($rs && $t = mysqli_fetch_assoc($rs)) { ?>
            <?php echo pd_render_thread_row($t, array('variant' => 'list', 'meta' => 'search')); ?>
        <?php } ?>
    </section>
    <?php } else { ?>
    <div class="pd-search-empty">
        <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
        <p>请使用顶部导航的搜索框输入关键词进行搜索。</p>
    </div>
    <?php } ?>
</div>
<?php pd_include_footer(); ?>
