<?php
require_once __DIR__ . '/../functions.php';
$page_title = '排行榜 - ' . SITE_NAME;
qf_include_header();

$signin_rank = qf_signin_table_ready() ? mysqli_query(db(), "SELECT u.nickname, u.username, COUNT(s.id) AS total_days, MAX(s.continuous_days) AS best_streak
    FROM qf_users u
    LEFT JOIN qf_signins s ON s.user_id=u.id
    GROUP BY u.id
    HAVING total_days > 0
    ORDER BY total_days DESC, best_streak DESC, u.id ASC
    LIMIT 30") : false;
$coin_rank = qf_user_coins_ready() ? mysqli_query(db(), "SELECT nickname, username, coins FROM qf_users WHERE coins>0 ORDER BY coins DESC, id ASC LIMIT 30") : false;
?>
<?php if (!empty($_SESSION['flash'])) { ?>
    <div class="alert success"><?php echo nl2br(h($_SESSION['flash'])); unset($_SESSION['flash']); ?></div>
<?php } ?>
<section class="card">
    <h1>排行榜</h1>
    <p class="muted">查看用户签到总天数排名和金币排行榜。</p>
</section>
<div class="rank-grid">
    <section class="card">
        <h2>签到总天数排名</h2>
        <table class="forum-table">
            <tr><th>排名</th><th>用户</th><th>签到天数</th><th>最长连续</th></tr>
            <?php $i = 1; while ($signin_rank && $row = mysqli_fetch_assoc($signin_rank)) { ?>
                <tr>
                    <td><?php echo $i++; ?></td>
                    <td><?php echo h(qf_user_display_name($row)); ?></td>
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
                    <td><?php echo h(qf_user_display_name($row)); ?></td>
                    <td><?php echo intval($row['coins']); ?></td>
                </tr>
            <?php } ?>
            <?php if ($i === 1) { ?><tr><td colspan="3" class="muted">暂无金币记录。</td></tr><?php } ?>
        </table>
    </section>
</div>
<?php qf_include_footer(); ?>
