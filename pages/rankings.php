<?php
require_once __DIR__ . '/../functions.php';
$page_title = '排行榜 - ' . SITE_NAME;
pd_include_header();

$points_rank = mysqli_query(db(), "SELECT u.id, u.nickname, u.username, u.points, u.group_id, g.name AS group_name, g.color AS group_color
    FROM pd_users u
    LEFT JOIN pd_user_groups g ON g.id=u.group_id
    WHERE u.status=1 AND u.points>0
    ORDER BY u.points DESC, u.id ASC
    LIMIT 30");
$signin_rank = pd_signin_table_ready() ? mysqli_query(db(), "SELECT u.nickname, u.username, COUNT(s.id) AS total_days, MAX(s.continuous_days) AS best_streak
    FROM pd_users u
    LEFT JOIN pd_signins s ON s.user_id=u.id
    GROUP BY u.id
    HAVING total_days > 0
    ORDER BY total_days DESC, best_streak DESC, u.id ASC
    LIMIT 30") : false;
$coin_rank = pd_user_coins_ready() ? mysqli_query(db(), "SELECT nickname, username, coins FROM pd_users WHERE coins>0 ORDER BY coins DESC, id ASC LIMIT 30") : false;
?>
<?php if (!empty($_SESSION['flash'])) { ?>
    <div class="alert success"><?php echo nl2br(h($_SESSION['flash'])); unset($_SESSION['flash']); ?></div>
<?php } ?>
<section class="card">
    <h1>排行榜</h1>
    <p class="muted">积分排行、签到天数与金币排行。</p>
</section>
<div class="rank-grid">
    <section class="card">
        <h2>积分排行榜</h2>
        <table class="forum-table">
            <tr><th>排名</th><th>用户</th><th>等级</th><th>用户组</th><th>积分</th></tr>
            <?php $i = 1; while ($points_rank && $row = mysqli_fetch_assoc($points_rank)) { ?>
                <tr>
                    <td><?php echo $i++; ?></td>
                    <td><a href="<?php echo h(pd_url_user($row['id'])); ?>"><?php echo h(pd_user_display_name($row)); ?></a></td>
                    <td><span class="pd-level">Lv.<?php echo intval(pd_user_level($row['points'])); ?></span></td>
                    <td><?php echo !empty($row['group_name']) ? '<span class="pd-group-badge" style="--group-color:' . h($row['group_color'] ? $row['group_color'] : '#505b93') . '">' . h($row['group_name']) . '</span>' : '—'; ?></td>
                    <td><?php echo intval($row['points']); ?></td>
                </tr>
            <?php } ?>
            <?php if ($i === 1) { ?><tr><td colspan="5" class="muted">暂无积分记录。</td></tr><?php } ?>
        </table>
    </section>
    <section class="card">
        <h2>签到总天数排名</h2>
        <table class="forum-table">
            <tr><th>排名</th><th>用户</th><th>签到天数</th><th>最长连续</th></tr>
            <?php $i = 1; while ($signin_rank && $row = mysqli_fetch_assoc($signin_rank)) { ?>
                <tr>
                    <td><?php echo $i++; ?></td>
                    <td><?php echo h(pd_user_display_name($row)); ?></td>
                    <td><?php echo intval($row['total_days']); ?></td>
                    <td><?php echo intval($row['best_streak']); ?></td>
                </tr>
            <?php } ?>
            <?php if ($i === 1) { ?><tr><td colspan="4" class="muted">暂无签到记录。</td></tr><?php } ?>
        </table>
    </section>
    <section class="card">
        <h2>金币排行榜</h2>
        <table class="forum-table">
            <tr><th>排名</th><th>用户</th><th>金币</th></tr>
            <?php $i = 1; while ($coin_rank && $row = mysqli_fetch_assoc($coin_rank)) { ?>
                <tr>
                    <td><?php echo $i++; ?></td>
                    <td><?php echo h(pd_user_display_name($row)); ?></td>
                    <td><?php echo intval($row['coins']); ?></td>
                </tr>
            <?php } ?>
            <?php if ($i === 1) { ?><tr><td colspan="3" class="muted">暂无金币记录。</td></tr><?php } ?>
        </table>
    </section>
</div>
<?php pd_include_footer(); ?>
