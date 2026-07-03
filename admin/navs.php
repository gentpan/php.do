<?php
require_once __DIR__ . '/../functions.php';
require_admin();

$saved = false;
$error = '';

if (!qf_nav_table_ready()) {
    $error = '主导航表不存在，请先访问 install/upgrade.php 升级数据库。';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $error === '') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    if ($action === 'add') {
        $title = clean_text(isset($_POST['title']) ? $_POST['title'] : '', 40);
        $url = trim((string)(isset($_POST['url']) ? $_POST['url'] : ''));
        $display_order = intval(isset($_POST['display_order']) ? $_POST['display_order'] : 10);
        $is_enabled = !empty($_POST['is_enabled']) ? 1 : 0;
        if ($title === '') {
            $error = '导航名称不能为空。';
        } elseif (!qf_valid_nav_url($url)) {
            $error = '导航链接格式不正确，请填写站内链接或 http/https 链接。';
        } else {
            $title_sql = esc($title);
            $url_sql = esc($url);
            mysqli_query(db(), "INSERT INTO qf_navs (title,url,display_order,is_enabled,created_at) VALUES ('{$title_sql}','{$url_sql}',{$display_order},{$is_enabled},NOW())");
            $saved = true;
        }
    } elseif ($action === 'save') {
        if (!empty($_POST['delete_navs']) && is_array($_POST['delete_navs'])) {
            foreach ($_POST['delete_navs'] as $delete_id) {
                $delete_id = intval($delete_id);
                if ($delete_id > 0) {
                    mysqli_query(db(), "DELETE FROM qf_navs WHERE id={$delete_id}");
                }
            }
        }
        if (!empty($_POST['navs']) && is_array($_POST['navs'])) {
            foreach ($_POST['navs'] as $id => $nav) {
                $id = intval($id);
                if ($id < 1 || (!empty($_POST['delete_navs']) && in_array((string)$id, $_POST['delete_navs']))) {
                    continue;
                }
                $title = clean_text(isset($nav['title']) ? $nav['title'] : '', 40);
                $url = trim((string)(isset($nav['url']) ? $nav['url'] : ''));
                $display_order = intval(isset($nav['display_order']) ? $nav['display_order'] : 10);
                $is_enabled = !empty($nav['is_enabled']) ? 1 : 0;
                if ($title === '' || !qf_valid_nav_url($url)) {
                    continue;
                }
                $title_sql = esc($title);
                $url_sql = esc($url);
                mysqli_query(db(), "UPDATE qf_navs SET title='{$title_sql}', url='{$url_sql}', display_order={$display_order}, is_enabled={$is_enabled} WHERE id={$id}");
            }
        }
        $saved = true;
    }
}

$navs = qf_nav_table_ready() ? mysqli_query(db(), "SELECT * FROM qf_navs ORDER BY display_order ASC, id ASC") : false;
$page_title = '主导航设置 - ' . SITE_NAME;
qf_include_header();
?>
<section class="card">
    <div class="admin-page-title">
        <h1>主导航设置</h1>
    </div>
    <p class="admin-back-row"><a class="btn btn-light btn-small" href="<?php echo h(qf_url_page('admin/index.php')); ?>">返回后台</a></p>
    <?php if ($saved) { ?><div class="alert success">主导航已保存。</div><?php } ?>
    <?php if ($error) { ?><div class="alert"><?php echo h($error); ?></div><?php } ?>

    <h2>新增主导航</h2>
    <form method="post">
        <input type="hidden" name="action" value="add">
        <div class="add-forum-grid">
            <div>
                <label>导航名称</label>
                <input type="text" name="title" maxlength="40" placeholder="例如：影视" required>
            </div>
            <div>
                <label>导航链接</label>
                <input type="text" name="url" maxlength="255" placeholder="例如：forum.php?id=3 或 https://example.com" required>
            </div>
            <div>
                <label>排序</label>
                <input class="forum-order-input" type="number" name="display_order" value="10">
            </div>
        </div>
        <label><input class="inline-check" type="checkbox" name="is_enabled" value="1" checked> 启用</label>
        <p class="muted">添加后会显示在前台“首页、搜索”后面。排序数字越小越靠前。</p>
        <button class="btn" type="submit">添加主导航</button>
    </form>

    <h2>现有主导航</h2>
    <form method="post">
        <input type="hidden" name="action" value="save">
        <table class="forum-table forum-admin-table">
            <tr><th>名称</th><th>链接</th><th>排序</th><th>启用</th><th>删除</th></tr>
            <?php $count = 0; while ($navs && ($nav = mysqli_fetch_assoc($navs))) { $count++; ?>
                <tr>
                    <td><input type="text" name="navs[<?php echo intval($nav['id']); ?>][title]" value="<?php echo h($nav['title']); ?>" maxlength="40" required></td>
                    <td><input type="text" name="navs[<?php echo intval($nav['id']); ?>][url]" value="<?php echo h($nav['url']); ?>" maxlength="255" required></td>
                    <td><input class="forum-order-input" type="number" name="navs[<?php echo intval($nav['id']); ?>][display_order]" value="<?php echo intval($nav['display_order']); ?>"></td>
                    <td><label><input class="inline-check" type="checkbox" name="navs[<?php echo intval($nav['id']); ?>][is_enabled]" value="1" <?php if (intval($nav['is_enabled'])) echo 'checked'; ?>> 启用</label></td>
                    <td><label><input class="inline-check" type="checkbox" name="delete_navs[]" value="<?php echo intval($nav['id']); ?>"> 删除</label></td>
                </tr>
            <?php } ?>
            <?php if ($count === 0) { ?><tr><td colspan="5">暂无自定义主导航。</td></tr><?php } ?>
        </table>
        <button class="btn" type="submit" data-confirm="确定保存主导航设置？勾选删除的导航将被删除。">保存全部主导航</button>
    </form>
</section>
<?php qf_include_footer(); ?>
