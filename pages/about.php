<?php
require_once __DIR__ . '/../functions.php';

$page_title = '关于 - ' . SITE_NAME;
$stats = pd_community_stats();
$admins = pd_staff_list('admin');
$mods = pd_staff_list('moderator');
$contact = pd_contact_email();

function pd_staff_card($u) {
    $name = pd_user_display_name($u);
    $title = trim((string)(isset($u['signature']) ? $u['signature'] : ''));
    ob_start();
    ?>
    <a class="pd-staff-card" href="<?php echo h(pd_url_user($u['id'])); ?>">
        <img class="pd-staff-avatar" src="<?php echo h(pd_user_avatar($u, 96)); ?>" alt="" loading="lazy">
        <span class="pd-staff-meta">
            <span class="pd-staff-name"><?php echo h($name); ?></span>
            <?php if ($title !== '') { ?><span class="pd-staff-title"><?php echo h($title); ?></span><?php } ?>
        </span>
    </a>
    <?php
    return ob_get_clean();
}

pd_include_header(true);
?>
<div class="pd-about pd-info">
    <div class="pd-breadcrumb">
        <a href="<?php echo h(pd_url_page('index.php')); ?>"><i class="fa-solid fa-house" aria-hidden="true"></i></a>
        <span>»</span>
        <strong>关于</strong>
    </div>

    <section class="pd-about-hero">
        <h1><?php echo h(pd_site_name()); ?></h1>
        <p class="pd-about-slogan"><?php echo h(pd_site_slogan()); ?></p>
        <div class="pd-about-topstats">
            <div class="pd-about-topstat"><b><?php echo pd_format_compact_number($stats['members']); ?></b><span>位成员</span></div>
            <div class="pd-about-topstat"><b><?php echo intval($stats['admins']); ?></b><span>位管理员</span></div>
            <div class="pd-about-topstat"><b><?php echo intval($stats['moderators']); ?></b><span>位版主</span></div>
            <div class="pd-about-topstat"><b><?php echo h(pd_site_founded_text()); ?></b><span>创建</span></div>
        </div>
    </section>

    <section class="pd-about-block">
        <h2>关于</h2>
        <div class="pd-about-text"><?php echo nl2br(h(pd_site_about_text())); ?></div>
    </section>

    <?php if (!empty($admins)) { ?>
    <section class="pd-about-block">
        <h2>我们的管理员</h2>
        <div class="pd-staff-grid">
            <?php foreach ($admins as $u) { echo pd_staff_card($u); } ?>
        </div>
    </section>
    <?php } ?>

    <?php if (!empty($mods)) { ?>
    <section class="pd-about-block">
        <h2>我们的版主</h2>
        <div class="pd-staff-grid">
            <?php foreach ($mods as $u) { echo pd_staff_card($u); } ?>
        </div>
    </section>
    <?php } ?>

    <section class="pd-about-block">
        <h2>联系我们</h2>
        <div class="pd-about-contact">
            <p>如果出现影响本站的关键问题或紧急事项，请联系 <?php if ($contact !== '') { ?><a href="mailto:<?php echo h($contact); ?>"><?php echo h($contact); ?></a><?php } else { ?>站点管理员<?php } ?>。</p>
            <p class="muted">如果您发现任何不当内容，请登录后与我们的版主和管理员联系。</p>
        </div>
    </section>

    <section class="pd-about-block">
        <h2>网站活动</h2>
        <div class="pd-about-activity">
            <div class="pd-about-metric"><b><?php echo pd_format_compact_number($stats['topics_7d']); ?></b><span>个话题</span><em>在过去 7 天</em></div>
            <div class="pd-about-metric"><b><?php echo pd_format_compact_number($stats['posts_today']); ?></b><span>个帖子</span><em>今天</em></div>
            <div class="pd-about-metric"><b><?php echo pd_format_compact_number($stats['active_7d']); ?></b><span>位活跃用户</span><em>在过去 7 天</em></div>
            <div class="pd-about-metric"><b><?php echo pd_format_compact_number($stats['registers_7d']); ?></b><span>个注册</span><em>在过去 7 天</em></div>
            <div class="pd-about-metric"><b><?php echo pd_format_compact_number($stats['likes_total']); ?></b><span>个赞</span><em>所有时间</em></div>
            <div class="pd-about-metric"><b><?php echo pd_format_compact_number($stats['posts_total']); ?></b><span>个帖子</span><em>累计</em></div>
        </div>
    </section>
</div>
<?php pd_include_footer(); ?>
