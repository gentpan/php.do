<?php
require_once __DIR__ . '/../functions.php';
require_admin();

$message = '';
$positions = array(
    'top' => '顶部广告',
    'sidebar' => '右侧板块上方广告',
    'footer' => '底部广告'
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $position = isset($_POST['position']) ? $_POST['position'] : '';
    if (!isset($positions[$position])) {
        $message = '广告位置无效。';
    } else {
        $title = clean_text($_POST['title'], 80);
        $link_url = clean_text($_POST['link_url'], 255);
        $width = clean_text($_POST['width'], 20);
        $height = clean_text($_POST['height'], 20);
        $is_enabled = !empty($_POST['is_enabled']) ? 1 : 0;

        $image_path = '';
        if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $allow = array('jpg', 'jpeg', 'png', 'gif', 'webp');
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allow)) {
                $dir = __DIR__ . '/uploads/ads';
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
                $name = 'ad_' . $position . '_' . date('YmdHis') . '_' . mt_rand(1000, 9999) . '.' . $ext;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $dir . '/' . $name)) {
                    $image_path = 'uploads/ads/' . $name;
                } else {
                    $message = '广告图片保存失败，请检查 uploads/ads 权限。';
                }
            } else {
                $message = '广告图片只支持 jpg、jpeg、png、gif、webp。';
            }
        }

        if ($message === '') {
            $pos_sql = esc($position);
            $title_sql = esc($title === '' ? $positions[$position] : $title);
            $link_sql = esc($link_url);
            $width_sql = esc($width);
            $height_sql = esc($height);

            if ($image_path !== '') {
                $image_sql = esc($image_path);
                mysqli_query(db(), "REPLACE INTO qf_ads (position,title,image_path,link_url,width,height,is_enabled,updated_at) VALUES ('{$pos_sql}','{$title_sql}','{$image_sql}','{$link_sql}','{$width_sql}','{$height_sql}',{$is_enabled},NOW())");
            } else {
                mysqli_query(db(), "INSERT INTO qf_ads (position,title,link_url,width,height,is_enabled,updated_at) VALUES ('{$pos_sql}','{$title_sql}','{$link_sql}','{$width_sql}','{$height_sql}',{$is_enabled},NOW()) ON DUPLICATE KEY UPDATE title='{$title_sql}', link_url='{$link_sql}', width='{$width_sql}', height='{$height_sql}', is_enabled={$is_enabled}, updated_at=NOW()");
            }
            $message = '广告设置已保存。';
        }
    }
}

$ads = array();
$rs = mysqli_query(db(), "SELECT * FROM qf_ads");
while ($rs && $row = mysqli_fetch_assoc($rs)) {
    $ads[$row['position']] = $row;
}

$page_title = '广告位置 - ' . SITE_NAME;
include __DIR__ . '/../header.php';
?>
<section class="card">
    <div class="admin-page-title">
        <h1>广告位置</h1>
    </div>
    <p class="admin-back-row"><a class="btn btn-light btn-small" href="<?php echo h(qf_url_page('admin.php')); ?>">返回后台</a></p>
    <?php if ($message !== '') { ?><div class="alert success"><?php echo h($message); ?></div><?php } ?>
    <p class="muted">可以分别管理顶部广告、右侧板块上方广告、底部广告。关闭后前台不显示。</p>
</section>

<?php foreach ($positions as $pos => $label) { $ad = isset($ads[$pos]) ? $ads[$pos] : array('title' => $label, 'image_path' => '', 'link_url' => '', 'width' => '', 'height' => '', 'is_enabled' => 0); ?>
<section class="card">
    <h2><?php echo h($label); ?></h2>
    <?php if (!empty($ad['image_path'])) { ?>
        <div class="ad-preview"><img src="<?php echo h($ad['image_path']); ?>" alt="<?php echo h($label); ?>"></div>
    <?php } ?>
    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="position" value="<?php echo h($pos); ?>">
        <label>广告标题</label>
        <input type="text" name="title" value="<?php echo h($ad['title']); ?>">

        <label>广告图片</label>
        <input type="file" name="image" accept=".jpg,.jpeg,.png,.gif,.webp">

        <label>广告链接</label>
        <input type="text" name="link_url" value="<?php echo h($ad['link_url']); ?>" placeholder="例如：https://example.com">

        <label>图片宽度</label>
        <input type="text" name="width" value="<?php echo h($ad['width']); ?>" placeholder="例如：100%、960px、300px">

        <label>图片高度</label>
        <input type="text" name="height" value="<?php echo h($ad['height']); ?>" placeholder="留空为自动高度，或填写 90px">

        <label><input class="inline-check" type="checkbox" name="is_enabled" value="1" <?php if (intval($ad['is_enabled'])) echo 'checked'; ?>> 开启显示</label>

        <button class="btn" type="submit">保存广告</button>
    </form>
</section>
<?php } ?>
<?php include __DIR__ . '/../footer.php'; ?>
