<?php
require_once __DIR__ . '/db.php';
require_admin();
$page_title = '后台管理 - ' . SITE_NAME;
include __DIR__ . '/header.php';
$forums = mysqli_query(db(), "SELECT * FROM qf_forums ORDER BY display_order ASC, id ASC");
$bans = mysqli_query(db(), "SELECT * FROM qf_bans WHERE expires_at IS NULL OR expires_at > NOW() ORDER BY id DESC");
?>
<section class="card">
    <h1>后台管理</h1>
    <p class="muted">管理版块、帖子、置顶、加精和删除。</p>
    <p>
        <a class="btn btn-light" href="<?php echo h(qf_url_page('admin_settings.php')); ?>">站点设置</a>
        <a class="btn btn-light" href="<?php echo h(qf_url_page('admin_users.php')); ?>">用户管理</a>
        <a class="btn btn-light" href="<?php echo h(qf_url_page('admin_navs.php')); ?>">主导航设置</a>
        <a class="btn btn-light" href="<?php echo h(qf_url_page('admin_ads.php')); ?>">广告位置</a>
        <a class="btn btn-light" href="<?php echo h(qf_url_page('admin_security.php')); ?>">安全相关</a>
        <a class="btn btn-light" href="<?php echo h(qf_url_page('admin_cache.php')); ?>">清理缓存</a>
    </p>
</section>
<div class="admin-stack">
    <section class="card">
        <h2>新增版块</h2>
        <form method="post" action="<?php echo h(qf_url_page('admin_action.php', array('action' => 'add_forum'))); ?>">
            <div class="add-forum-grid">
                <div>
                    <label>版块名称</label><input name="name" required>
                </div>
                <div>
                    <label>版块说明</label><input name="description">
                </div>
                <div>
                    <label>排序</label><input class="forum-order-input" type="number" name="display_order" value="10">
                </div>
            </div>
            <label>主题分类</label>
            <div class="category-cell">
                <label class="category-inline"><input class="inline-check" type="checkbox" name="new_topic_category_enabled" value="1"> 开启</label>
                <input type="text" name="new_topic_categories" placeholder="例如：意见,BUG">
            </div>
            <label>指定用户ID发帖</label>
            <div class="limit-cell">
                <label class="category-inline"><input class="inline-check" type="checkbox" name="new_post_user_limit_enabled" value="1"> 开启</label>
                <input type="text" name="new_post_user_ids" placeholder="例如：1,2,3">
            </div>
            <button class="btn">保存</button>
        </form>
        <h2>现有版块</h2>
        <form method="post" action="<?php echo h(qf_url_page('admin_action.php', array('action' => 'save_forums'))); ?>">
            <table class="forum-table forum-admin-table">
                <tr><th class="forum-name-col">名称</th><th class="forum-desc-col">简介</th><th class="forum-category-col">主题分类</th><th class="forum-limit-col">指定用户ID发帖</th><th class="forum-order-col">排序</th><th class="forum-del-col">删除</th></tr>
                <?php while ($forums && $f = mysqli_fetch_assoc($forums)) { ?>
                    <tr>
                        <td><input type="text" name="forums[<?php echo intval($f['id']); ?>][name]" value="<?php echo h($f['name']); ?>" required></td>
                        <td><input type="text" name="forums[<?php echo intval($f['id']); ?>][description]" value="<?php echo h($f['description']); ?>"></td>
                        <td class="category-cell forum-category-cell">
                            <label class="category-inline"><input class="inline-check" type="checkbox" name="forums[<?php echo intval($f['id']); ?>][topic_category_enabled]" value="1" <?php if (intval($f['topic_category_enabled'])) echo 'checked'; ?>> 开启</label>
                            <input type="text" name="forums[<?php echo intval($f['id']); ?>][topic_categories]" value="<?php echo h($f['topic_categories']); ?>" placeholder="例如：意见,BUG">
                        </td>
                        <td class="limit-cell forum-limit-cell">
                            <label class="category-inline"><input class="inline-check" type="checkbox" name="forums[<?php echo intval($f['id']); ?>][post_user_limit_enabled]" value="1" <?php if (intval($f['post_user_limit_enabled'])) echo 'checked'; ?>> 开启</label>
                            <input type="text" name="forums[<?php echo intval($f['id']); ?>][post_user_ids]" value="<?php echo h($f['post_user_ids']); ?>" placeholder="例如：1,2,3">
                        </td>
                        <td><input class="forum-order-input" type="number" name="forums[<?php echo intval($f['id']); ?>][display_order]" value="<?php echo intval($f['display_order']); ?>"></td>
                        <td><label><input class="inline-check" type="checkbox" name="delete_forums[]" value="<?php echo intval($f['id']); ?>"> 删除</label></td>
                    </tr>
                <?php } ?>
            </table>
            <p class="muted">主题分类用于发帖时显示在标题前面，例如：程序发布版块可设置“意见,BUG”。指定用户ID发帖只限制发布主题帖，不影响回帖。删除版块会同时把该版块下的帖子标记为删除。</p>
            <button class="btn" type="submit" data-confirm="确定保存全部版块修改？勾选删除的版块将被删除。">保存全部版块</button>
        </form>
    </section>
    <section class="card">
        <h2>禁封IP</h2>
        <form class="ban-form" method="post" action="<?php echo h(qf_url_page('admin_action.php', array('action' => 'add_ban'))); ?>">
            <label>IP地址</label>
            <input type="text" name="ip" placeholder="例如：192.168.1.100" required>
            <label>封禁天数</label>
            <input type="number" name="days" min="1" max="3650" value="7" required>
            <label>封禁原因</label>
            <input type="text" name="reason" placeholder="例如：广告、灌水、恶意注册">
            <button class="btn" type="submit">添加禁封</button>
        </form>
        <h3>当前禁封</h3>
        <?php if ($bans && mysqli_num_rows($bans) > 0) { ?>
            <table class="forum-table">
                <tr><th>IP</th><th>原因</th><th>到期时间</th><th>操作</th></tr>
                <?php while ($ban = mysqli_fetch_assoc($bans)) { ?>
                    <tr>
                        <td><?php echo h($ban['ip']); ?></td>
                        <td><?php echo h($ban['reason']); ?></td>
                        <td><?php echo $ban['expires_at'] ? h($ban['expires_at']) : '永久'; ?></td>
                        <td><a class="danger-link" data-confirm="确定解除该 IP 禁封？" href="<?php echo h(qf_url_page('admin_action.php', array('action' => 'del_ban', 'id' => intval($ban['id']), 'token' => qf_action_token('del_ban', $ban['id'])))); ?>">解除</a></td>
                    </tr>
                <?php } ?>
            </table>
        <?php } else { ?>
            <p class="muted">暂无禁封 IP。</p>
        <?php } ?>
    </section>
</div>
<?php include __DIR__ . '/footer.php'; ?>
