<?php
require_once __DIR__ . '/../functions.php';
require_admin();

$saved = false;
$error = '';
$icons_ready = qf_nav_icon_columns_ready();
$icon_types = qf_nav_icon_types();

if (!qf_nav_table_ready()) {
    $error = '主导航表不存在，请先访问 install/upgrade.php 升级数据库。';
}

/**
 * 保存分类图标上传，返回站内相对路径；失败返回空字符串并写入 $error。
 */
function qf_nav_save_icon_upload($file_key, $row_id) {
    global $error;
    if (empty($_FILES[$file_key]['name'])) {
        return '';
    }
    $file = $_FILES[$file_key];
    if (is_array($file['name'])) {
        // 批量表单：$_FILES['x']['name'][id]
        if (empty($file['name'][$row_id])) {
            return '';
        }
        if ($file['error'][$row_id] !== UPLOAD_ERR_OK) {
            $error = '分类图片上传失败。';
            return '';
        }
        $name_raw = $file['name'][$row_id];
        $tmp = $file['tmp_name'][$row_id];
    } else {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $error = '分类图片上传失败。';
            return '';
        }
        $name_raw = $file['name'];
        $tmp = $file['tmp_name'];
    }
    $allow = array('jpg', 'jpeg', 'png', 'gif', 'webp', 'svg');
    $ext = strtolower(pathinfo($name_raw, PATHINFO_EXTENSION));
    if (!in_array($ext, $allow, true)) {
        $error = '分类图片只支持 jpg、jpeg、png、gif、webp、svg。';
        return '';
    }
    $dir = __DIR__ . '/../uploads/navs';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $name = 'nav_' . date('YmdHis') . '_' . mt_rand(1000, 9999) . '.' . $ext;
    if (!move_uploaded_file($tmp, $dir . '/' . $name)) {
        $error = '分类图片保存失败，请检查 uploads/navs 目录权限。';
        return '';
    }
    return 'uploads/navs/' . $name;
}

/**
 * 依据提交的图标类型解析出 icon_value。
 */
function qf_nav_resolve_icon($icon_type, $fa, $svg, $uploaded_path, $existing_value) {
    if ($icon_type === 'img') {
        return $uploaded_path !== '' ? $uploaded_path : $existing_value;
    }
    if ($icon_type === 'svg') {
        return qf_sanitize_nav_svg($svg);
    }
    if ($icon_type === 'fa') {
        return trim(preg_replace('/[^a-z0-9 _-]/i', '', (string)$fa));
    }
    return '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $error === '') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    if ($action === 'add') {
        $title = clean_text(isset($_POST['title']) ? $_POST['title'] : '', 40);
        $url = trim((string)(isset($_POST['url']) ? $_POST['url'] : ''));
        $display_order = intval(isset($_POST['display_order']) ? $_POST['display_order'] : 10);
        $is_enabled = !empty($_POST['is_enabled']) ? 1 : 0;
        $icon_type = isset($_POST['icon_type']) && isset($icon_types[$_POST['icon_type']]) ? $_POST['icon_type'] : '';
        $icon_upload = ($icons_ready && $icon_type === 'img') ? qf_nav_save_icon_upload('icon_image', 0) : '';
        $icon_value = $icons_ready ? qf_nav_resolve_icon(
            $icon_type,
            isset($_POST['icon_fa']) ? $_POST['icon_fa'] : '',
            isset($_POST['icon_svg']) ? $_POST['icon_svg'] : '',
            $icon_upload,
            ''
        ) : '';
        if ($title === '') {
            $error = '导航名称不能为空。';
        } elseif (!qf_valid_nav_url($url)) {
            $error = '导航链接格式不正确，请填写站内链接或 http/https 链接。';
        } elseif ($error === '') {
            $title_sql = esc($title);
            $url_sql = esc($url);
            if ($icons_ready) {
                $type_sql = esc($icon_type);
                $value_sql = esc($icon_value);
                mysqli_query(db(), "INSERT INTO qf_navs (title,url,icon_type,icon_value,display_order,is_enabled,created_at) VALUES ('{$title_sql}','{$url_sql}','{$type_sql}','{$value_sql}',{$display_order},{$is_enabled},NOW())");
            } else {
                mysqli_query(db(), "INSERT INTO qf_navs (title,url,display_order,is_enabled,created_at) VALUES ('{$title_sql}','{$url_sql}',{$display_order},{$is_enabled},NOW())");
            }
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
                if ($icons_ready) {
                    $icon_type = isset($nav['icon_type']) && isset($icon_types[$nav['icon_type']]) ? $nav['icon_type'] : '';
                    $existing_value = '';
                    if ($icon_type === 'img') {
                        $existing_rs = mysqli_query(db(), "SELECT icon_value FROM qf_navs WHERE id={$id}");
                        $existing_row = $existing_rs ? mysqli_fetch_assoc($existing_rs) : null;
                        $existing_value = $existing_row && $existing_row['icon_value'] !== null ? $existing_row['icon_value'] : '';
                    }
                    $icon_upload = $icon_type === 'img' ? qf_nav_save_icon_upload('icon_image', $id) : '';
                    $icon_value = qf_nav_resolve_icon(
                        $icon_type,
                        isset($nav['icon_fa']) ? $nav['icon_fa'] : '',
                        isset($nav['icon_svg']) ? $nav['icon_svg'] : '',
                        $icon_upload,
                        $existing_value
                    );
                    $type_sql = esc($icon_type);
                    $value_sql = esc($icon_value);
                    mysqli_query(db(), "UPDATE qf_navs SET title='{$title_sql}', url='{$url_sql}', icon_type='{$type_sql}', icon_value='{$value_sql}', display_order={$display_order}, is_enabled={$is_enabled} WHERE id={$id}");
                } else {
                    mysqli_query(db(), "UPDATE qf_navs SET title='{$title_sql}', url='{$url_sql}', display_order={$display_order}, is_enabled={$is_enabled} WHERE id={$id}");
                }
            }
        }
        $saved = true;
    }
}

$navs = qf_nav_table_ready() ? mysqli_query(db(), "SELECT * FROM qf_navs ORDER BY display_order ASC, id ASC") : false;
$page_title = '主导航设置 - ' . SITE_NAME;
qf_include_admin_header();
?>
<section class="card">
    <div class="admin-page-title">
        <h1>主导航设置</h1>
    </div>
<?php if ($saved) { ?><div class="alert success">主导航已保存。</div><?php } ?>
    <?php if ($error) { ?><div class="alert"><?php echo h($error); ?></div><?php } ?>

    <?php if (!$icons_ready) { ?><div class="alert">分类图标字段尚未创建，请访问 <code>install/upgrade.php</code> 升级数据库后即可为每个分类设置图标。</div><?php } ?>

    <h2>新增主导航</h2>
    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="action" value="add">
        <div class="add-forum-grid">
            <div>
                <label>导航名称</label>
                <input type="text" name="title" maxlength="40" placeholder="例如：影视" required>
            </div>
            <div>
                <label>导航链接</label>
                <input type="text" name="url" maxlength="255" placeholder="例如：/develop 或 https://example.com" required>
            </div>
            <div>
                <label>排序</label>
                <input class="forum-order-input" type="number" name="display_order" value="10">
            </div>
        </div>
        <?php if ($icons_ready) { ?>
        <div class="add-forum-grid nav-icon-grid" data-nav-icon>
            <div>
                <label>图标类型</label>
                <select name="icon_type" data-nav-icon-type>
                    <option value="">不显示图标</option>
                    <option value="fa">Font Awesome 类名</option>
                    <option value="svg">SVG 代码</option>
                    <option value="img">上传图片</option>
                </select>
            </div>
            <div data-nav-icon-field="fa" hidden>
                <label>Font Awesome 类名</label>
                <input type="text" name="icon_fa" maxlength="120" placeholder="例如：fa-solid fa-film">
            </div>
            <div data-nav-icon-field="svg" hidden>
                <label>SVG 代码</label>
                <textarea name="icon_svg" rows="2" placeholder="粘贴 &lt;svg ...&gt;&lt;/svg&gt;"></textarea>
            </div>
            <div data-nav-icon-field="img" hidden>
                <label>上传图片</label>
                <input type="file" name="icon_image" accept=".jpg,.jpeg,.png,.gif,.webp,.svg">
            </div>
        </div>
        <?php } ?>
        <label><input class="inline-check" type="checkbox" name="is_enabled" value="1" checked> 启用</label>
        <p class="muted">添加后会显示在前台顶部菜单下方的“分类导航”一行。排序数字越小越靠前。</p>
        <button class="btn" type="submit">添加主导航</button>
    </form>

    <h2>现有主导航</h2>
    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="action" value="save">
        <table class="forum-table forum-admin-table">
            <tr><th>名称</th><th>链接</th><?php if ($icons_ready) { ?><th>图标</th><?php } ?><th>排序</th><th>启用</th><th>删除</th></tr>
            <?php $count = 0; while ($navs && ($nav = mysqli_fetch_assoc($navs))) { $count++; $nid = intval($nav['id']); $cur_type = isset($nav['icon_type']) ? $nav['icon_type'] : ''; $cur_value = isset($nav['icon_value']) ? (string)$nav['icon_value'] : ''; ?>
                <tr>
                    <td><input type="text" name="navs[<?php echo $nid; ?>][title]" value="<?php echo h($nav['title']); ?>" maxlength="40" required></td>
                    <td><input type="text" name="navs[<?php echo $nid; ?>][url]" value="<?php echo h($nav['url']); ?>" maxlength="255" required></td>
                    <?php if ($icons_ready) { ?>
                    <td class="nav-icon-cell" data-nav-icon>
                        <div class="nav-icon-preview"><?php echo qf_nav_icon_html($nav); ?></div>
                        <select name="navs[<?php echo $nid; ?>][icon_type]" data-nav-icon-type>
                            <option value="" <?php if ($cur_type === '') echo 'selected'; ?>>不显示</option>
                            <option value="fa" <?php if ($cur_type === 'fa') echo 'selected'; ?>>FA 类名</option>
                            <option value="svg" <?php if ($cur_type === 'svg') echo 'selected'; ?>>SVG</option>
                            <option value="img" <?php if ($cur_type === 'img') echo 'selected'; ?>>图片</option>
                        </select>
                        <div data-nav-icon-field="fa"<?php if ($cur_type !== 'fa') echo ' hidden'; ?>>
                            <input type="text" name="navs[<?php echo $nid; ?>][icon_fa]" value="<?php echo $cur_type === 'fa' ? h($cur_value) : ''; ?>" maxlength="120" placeholder="fa-solid fa-film">
                        </div>
                        <div data-nav-icon-field="svg"<?php if ($cur_type !== 'svg') echo ' hidden'; ?>>
                            <textarea name="navs[<?php echo $nid; ?>][icon_svg]" rows="2" placeholder="&lt;svg&gt;"><?php echo $cur_type === 'svg' ? h($cur_value) : ''; ?></textarea>
                        </div>
                        <div data-nav-icon-field="img"<?php if ($cur_type !== 'img') echo ' hidden'; ?>>
                            <input type="file" name="navs[<?php echo $nid; ?>][icon_image]" accept=".jpg,.jpeg,.png,.gif,.webp,.svg">
                            <?php if ($cur_type === 'img' && $cur_value !== '') { ?><span class="muted">保留原图，选文件可替换</span><?php } ?>
                        </div>
                    </td>
                    <?php } ?>
                    <td><input class="forum-order-input" type="number" name="navs[<?php echo $nid; ?>][display_order]" value="<?php echo intval($nav['display_order']); ?>"></td>
                    <td><label><input class="inline-check" type="checkbox" name="navs[<?php echo $nid; ?>][is_enabled]" value="1" <?php if (intval($nav['is_enabled'])) echo 'checked'; ?>> 启用</label></td>
                    <td><label><input class="inline-check" type="checkbox" name="delete_navs[]" value="<?php echo $nid; ?>"> 删除</label></td>
                </tr>
            <?php } ?>
            <?php if ($count === 0) { ?><tr><td colspan="<?php echo $icons_ready ? 6 : 5; ?>">暂无自定义主导航。</td></tr><?php } ?>
        </table>
        <button class="btn" type="submit" data-confirm="确定保存主导航设置？勾选删除的导航将被删除。">保存全部主导航</button>
    </form>
</section>
<script>
(function () {
    function bind(root) {
        var select = root.querySelector('[data-nav-icon-type]');
        if (!select) return;
        function sync() {
            var val = select.value;
            var fields = root.querySelectorAll('[data-nav-icon-field]');
            for (var i = 0; i < fields.length; i++) {
                fields[i].hidden = fields[i].getAttribute('data-nav-icon-field') !== val;
            }
        }
        select.addEventListener('change', sync);
        sync();
    }
    var roots = document.querySelectorAll('[data-nav-icon]');
    for (var i = 0; i < roots.length; i++) bind(roots[i]);
})();
</script>
<?php qf_include_admin_footer(); ?>
