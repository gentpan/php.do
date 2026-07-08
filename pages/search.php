<?php
require_once __DIR__ . '/../functions.php';
$q = isset($_GET['q']) ? clean_text($_GET['q'], 60) : '';
$page_title = '搜索结果 - ' . SITE_NAME;
pd_include_header();
?>
<section class="card">
    <h1>搜索结果</h1>
    <form class="search" method="get">
        <input name="q" value="<?php echo h($q); ?>" placeholder="输入关键词">
        <button class="btn">搜索</button>
    </form>
</section>
<?php if ($q !== '') { 
    $qs = esc($q);
    $rs = mysqli_query(db(), "SELECT t.*, f.name AS forum_name, u.nickname, u.username, u.avatar, u.email
        FROM pd_threads t
        LEFT JOIN pd_forums f ON t.forum_id=f.id
        LEFT JOIN pd_users u ON t.user_id=u.id
        WHERE t.is_deleted=0 AND (t.title LIKE '%{$qs}%' OR t.content LIKE '%{$qs}%' OR t.topic_category LIKE '%{$qs}%')
        ORDER BY t.updated_at DESC LIMIT 50");
    $found = $rs ? mysqli_num_rows($rs) : 0;
?>
<section class="card thread-list">
    <?php if ($found === 0) { ?>
        <div class="pd-empty">没有找到相关帖子，可以换一个关键词再试。</div>
    <?php } ?>
    <?php while ($rs && $t = mysqli_fetch_assoc($rs)) { ?>
        <?php echo pd_render_thread_row($t, array('variant' => 'list', 'meta' => 'search')); ?>
    <?php } ?>
</section>
<?php } ?>
<?php pd_include_footer(); ?>
