<?php
require_once __DIR__ . '/../functions.php';
require_admin();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$rs = mysqli_query(db(), "SELECT t.*, f.name AS forum_name FROM pd_threads t LEFT JOIN pd_forums f ON t.forum_id=f.id WHERE t.id={$id} AND t.is_deleted=0 LIMIT 1");
$thread = $rs ? mysqli_fetch_assoc($rs) : null;
if (!$thread) {
    exit('帖子不存在');
}

$forums = mysqli_query(db(), "SELECT * FROM pd_forums ORDER BY display_order ASC, id ASC");
$page_title = '移动帖子 - ' . SITE_NAME;
pd_include_header();
?>
<section class="card narrow-card">
    <h1>移动帖子</h1>
    <p class="muted">当前帖子：<?php echo h($thread['title']); ?></p>
    <p class="muted">当前版块：<?php echo h($thread['forum_name']); ?></p>
    <form method="post" action="<?php echo h(pd_url_page('admin/action.php', array('action' => 'move_thread'))); ?>">
        <input type="hidden" name="thread_id" value="<?php echo intval($thread['id']); ?>">
        <label>移动到目标版块</label>
        <select name="new_forum_id" required>
            <?php while ($forums && $f = mysqli_fetch_assoc($forums)) { ?>
                <option value="<?php echo intval($f['id']); ?>" <?php if (intval($f['id']) === intval($thread['forum_id'])) echo 'selected'; ?>><?php echo h($f['name']); ?></option>
            <?php } ?>
        </select>
        <p class="muted">移动后会清空原主题分类，避免和目标版块分类不匹配。</p>
        <button class="btn" type="submit">确认移动</button>
        <a class="btn btn-light" href="<?php echo h(pd_url_thread($thread['id'])); ?>">返回帖子</a>
    </form>
</section>
<?php pd_include_footer(); ?>
