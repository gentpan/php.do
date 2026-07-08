<?php
require_once __DIR__ . '/../functions.php';
require_admin();
qf_ensure_points_schema();

$flash = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $op = isset($_POST['op']) ? $_POST['op'] : '';
    if ($op === 'save') {
        $id = intval($_POST['id']);
        $name = clean_text($_POST['name'], 60);
        $slug = preg_replace('/[^a-z0-9\-]+/', '', strtolower(trim((string)$_POST['slug'])));
        $color = trim((string)$_POST['color']);
        $min_points = max(0, intval($_POST['min_points']));
        $display_order = intval($_POST['display_order']);
        if ($name === '' || $slug === '') {
            $error = '用户组名称和标识不能为空。';
        } elseif ($color !== '' && !preg_match('/^#[0-9a-fA-F]{3,8}$/', $color)) {
            $error = '颜色请使用 #RGB 或 #RRGGBB。';
        } else {
            $name_sql = esc($name);
            $slug_sql = esc($slug);
            $color_sql = esc($color !== '' ? $color : '#505b93');
            if ($id > 0) {
                mysqli_query(db(), "UPDATE qf_user_groups SET name='{$name_sql}', slug='{$slug_sql}', color='{$color_sql}', min_points={$min_points}, display_order={$display_order} WHERE id={$id}");
                $flash = '用户组已更新。';
            } else {
                mysqli_query(db(), "INSERT INTO qf_user_groups (name,slug,color,min_points,is_system,display_order,created_at) VALUES ('{$name_sql}','{$slug_sql}','{$color_sql}',{$min_points},0,{$display_order},NOW())");
                $flash = '用户组已创建。';
            }
            // 重新按积分挂组
            mysqli_query(db(), "UPDATE qf_users u SET u.group_id = IFNULL((
                SELECT g2.id FROM qf_user_groups g2 WHERE g2.min_points <= u.points ORDER BY g2.min_points DESC, g2.display_order ASC, g2.id ASC LIMIT 1
            ), 0)");
        }
    } elseif ($op === 'delete') {
        $id = intval($_POST['id']);
        $rs = mysqli_query(db(), "SELECT * FROM qf_user_groups WHERE id={$id} LIMIT 1");
        $row = $rs ? mysqli_fetch_assoc($rs) : null;
        if (!$row) {
            $error = '用户组不存在。';
        } elseif (intval($row['is_system']) === 1) {
            $error = '系统用户组不能删除。';
        } else {
            mysqli_query(db(), "DELETE FROM qf_user_groups WHERE id={$id}");
            mysqli_query(db(), "UPDATE qf_users SET group_id=0 WHERE group_id={$id}");
            mysqli_query(db(), "UPDATE qf_users u SET u.group_id = IFNULL((
                SELECT g2.id FROM qf_user_groups g2 WHERE g2.min_points <= u.points ORDER BY g2.min_points DESC, g2.display_order ASC, g2.id ASC LIMIT 1
            ), 0) WHERE u.group_id=0");
            $flash = '用户组已删除。';
        }
    }
}

$groups = qf_list_user_groups();
$edit_id = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
$edit = null;
foreach ($groups as $g) {
    if (intval($g['id']) === $edit_id) {
        $edit = $g;
        break;
    }
}
$page_title = '用户组管理 - ' . SITE_NAME;
qf_include_header();
?>
<section class="card">
    <div class="admin-page-title"><h1>用户组管理</h1></div>
    <p class="admin-back-row">
        <a class="btn btn-light btn-small" href="<?php echo h(qf_url_page('admin/index.php')); ?>">返回后台</a>
        <a class="btn btn-light btn-small" href="<?php echo h(qf_url_page('admin/points.php')); ?>">积分与等级</a>
    </p>
    <?php if ($flash !== '') { ?><div class="alert success"><?php echo h($flash); ?></div><?php } ?>
    <?php if ($error !== '') { ?><div class="alert"><?php echo h($error); ?></div><?php } ?>
    <p class="muted">用户组按「所需最低积分」自动分配；用户达到更高组门槛后自动升级。系统组可改名/改门槛，但不可删除。</p>

    <h2><?php echo $edit ? '编辑用户组' : '新增用户组'; ?></h2>
    <form method="post" class="settings-form">
        <input type="hidden" name="op" value="save">
        <input type="hidden" name="id" value="<?php echo $edit ? intval($edit['id']) : 0; ?>">
        <label>名称</label>
        <input type="text" name="name" maxlength="60" required value="<?php echo h($edit ? $edit['name'] : ''); ?>">
        <label>标识（slug）</label>
        <input type="text" name="slug" maxlength="40" required value="<?php echo h($edit ? $edit['slug'] : ''); ?>" placeholder="例如 senior">
        <label>颜色</label>
        <input type="text" name="color" maxlength="20" value="<?php echo h($edit ? $edit['color'] : '#505b93'); ?>" placeholder="#505b93">
        <label>所需最低积分</label>
        <input type="number" name="min_points" min="0" max="100000000" value="<?php echo $edit ? intval($edit['min_points']) : 0; ?>">
        <label>排序</label>
        <input type="number" name="display_order" value="<?php echo $edit ? intval($edit['display_order']) : 100; ?>">
        <button class="btn" type="submit"><?php echo $edit ? '保存修改' : '创建用户组'; ?></button>
        <?php if ($edit) { ?><a class="btn btn-light" href="<?php echo h(qf_url_page('admin/groups.php')); ?>">取消编辑</a><?php } ?>
    </form>
</section>

<section class="card">
    <h2>现有用户组</h2>
    <table class="forum-table">
        <tr><th>ID</th><th>名称</th><th>标识</th><th>最低积分</th><th>排序</th><th>类型</th><th>操作</th></tr>
        <?php foreach ($groups as $g) { ?>
            <tr>
                <td><?php echo intval($g['id']); ?></td>
                <td><span class="phpdo-group-badge" style="--group-color:<?php echo h($g['color']); ?>"><?php echo h($g['name']); ?></span></td>
                <td><?php echo h($g['slug']); ?></td>
                <td><?php echo intval($g['min_points']); ?></td>
                <td><?php echo intval($g['display_order']); ?></td>
                <td><?php echo intval($g['is_system']) ? '系统' : '自定义'; ?></td>
                <td class="user-actions">
                    <a class="btn btn-small btn-light" href="<?php echo h(qf_url_page('admin/groups.php', array('edit' => intval($g['id'])))); ?>">编辑</a>
                    <?php if (!intval($g['is_system'])) { ?>
                        <form method="post" style="display:inline" data-confirm="确定删除该用户组？">
                            <input type="hidden" name="op" value="delete">
                            <input type="hidden" name="id" value="<?php echo intval($g['id']); ?>">
                            <button class="btn btn-small btn-danger" type="submit">删除</button>
                        </form>
                    <?php } ?>
                </td>
            </tr>
        <?php } ?>
    </table>
</section>
<?php qf_include_footer(); ?>
