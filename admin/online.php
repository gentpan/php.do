<?php
require_once __DIR__ . '/../functions.php';
require_admin();
qf_ensure_online_schema();
qf_online_touch(true);

$online = qf_online_counts();
$today = qf_online_today_peak();
$members = qf_online_members(50);
$history = mysqli_query(db(), "SELECT * FROM qf_online_daily ORDER BY day_date DESC LIMIT 60");

$page_title = '在线统计 - ' . SITE_NAME;
qf_include_header();
?>
<section class="card">
    <div class="admin-page-title"><h1>在线统计</h1></div>
    <p class="admin-back-row">
        <a class="btn btn-light btn-small" href="<?php echo h(qf_url_page('admin/index.php')); ?>">返回后台</a>
    </p>
    <p class="muted">15 分钟内有活动计为在线；访客按会话计数，会员按用户去重。每日自动记录当天峰值。</p>

    <div class="admin-points-grid">
        <div>
            <label>当前在线</label>
            <p style="font-size:28px;font-weight:800;margin:6px 0 0"><?php echo intval($online['total']); ?></p>
        </div>
        <div>
            <label>会员</label>
            <p style="font-size:28px;font-weight:800;margin:6px 0 0"><?php echo intval($online['members']); ?></p>
        </div>
        <div>
            <label>访客</label>
            <p style="font-size:28px;font-weight:800;margin:6px 0 0"><?php echo intval($online['guests']); ?></p>
        </div>
        <div>
            <label>今日峰值</label>
            <p style="font-size:28px;font-weight:800;margin:6px 0 0"><?php echo intval($today['peak_total']); ?></p>
            <p class="muted" style="margin:4px 0 0">会员 <?php echo intval($today['peak_members']); ?> · 访客 <?php echo intval($today['peak_guests']); ?><?php if (!empty($today['peak_at'])) { ?> · <?php echo qf_time_html($today['peak_at']); ?><?php } ?></p>
        </div>
    </div>
</section>

<section class="card">
    <h2>当前在线会员</h2>
    <?php if (empty($members)) { ?>
        <p class="muted">暂无登录会员在线。</p>
    <?php } else { ?>
        <table class="forum-table">
            <tr><th>用户</th><th>最近活动</th></tr>
            <?php foreach ($members as $m) { ?>
                <tr>
                    <td><a href="<?php echo h(qf_url_user($m['id'])); ?>"><?php echo h(qf_user_display_name($m)); ?></a></td>
                    <td><?php echo qf_time_html($m['last_seen']); ?></td>
                </tr>
            <?php } ?>
        </table>
    <?php } ?>
</section>

<section class="card">
    <h2>每日在线峰值（最近 60 天）</h2>
    <table class="forum-table">
        <tr><th>日期</th><th>峰值总数</th><th>峰值会员</th><th>峰值访客</th><th>峰值时间</th></tr>
        <?php $n = 0; while ($history && ($row = mysqli_fetch_assoc($history))) { $n++; ?>
            <tr>
                <td><?php echo h($row['day_date']); ?></td>
                <td><?php echo intval($row['peak_total']); ?></td>
                <td><?php echo intval($row['peak_members']); ?></td>
                <td><?php echo intval($row['peak_guests']); ?></td>
                <td><?php echo !empty($row['peak_at']) ? qf_time_html($row['peak_at']) : '—'; ?></td>
            </tr>
        <?php } ?>
        <?php if ($n === 0) { ?><tr><td colspan="5" class="muted">暂无历史记录，访问站点后会自动开始统计。</td></tr><?php } ?>
    </table>
</section>
<?php qf_include_footer(); ?>
