<?php
require_once __DIR__ . '/../functions.php';
$q = isset($_GET['q']) ? clean_text($_GET['q'], 60) : '';
$page_title = '搜索结果 - ' . SITE_NAME;
qf_include_header();
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
    $rs = mysqli_query(db(), "SELECT t.*, u.nickname FROM qf_threads t LEFT JOIN qf_users u ON t.user_id=u.id WHERE t.is_deleted=0 AND (t.title LIKE '%{$qs}%' OR t.content LIKE '%{$qs}%') ORDER BY t.updated_at DESC LIMIT 50");
?>
<section class="card thread-list">
    <?php while ($rs && $t = mysqli_fetch_assoc($rs)) { ?>
        <div class="thread-row">
            <div class="thread-main"><a class="thread-title" href="<?php echo h(qf_url_thread($t['id'])); ?>"><?php echo h($t['title']); ?></a><p><?php echo h($t['nickname']); ?> · <?php echo format_time($t['updated_at']); ?></p></div>
            <div class="thread-count"><?php echo intval($t['replies']); ?> 回复</div>
        </div>
    <?php } ?>
</section>
<?php } ?>
<?php qf_include_footer(); ?>
