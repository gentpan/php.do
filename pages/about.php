<?php
require_once __DIR__ . '/../functions.php';

$page_title = '关于 - ' . SITE_NAME;
$stats = qf_community_stats();
$admins = qf_staff_list('admin');
$mods = qf_staff_list('moderator');
$contact = qf_contact_email();

function phpdo_staff_card($u) {
    $name = ($u['nickname'] !== null && $u['nickname'] !== '') ? $u['nickname'] : $u['username'];
    $title = trim((string)(isset($u['signature']) ? $u['signature'] : ''));
    ob_start();
    ?>
    <a class="phpdo-staff-card" href="<?php echo h(qf_url_user($u['id'])); ?>">
        <img class="phpdo-staff-avatar" src="<?php echo h(qf_user_avatar($u, 96)); ?>" alt="" loading="lazy">
        <span class="phpdo-staff-meta">
            <span class="phpdo-staff-name"><?php echo h($name); ?></span>
            <?php if ($title !== '') { ?><span class="phpdo-staff-title"><?php echo h($title); ?></span><?php } ?>
        </span>
    </a>
    <?php
    return ob_get_clean();
}

qf_include_header();
?>
<div class="phpdo-about">
    <?php qf_render_page_banner('about', qf_site_name(), qf_site_slogan()); ?>
    <div class="phpdo-breadcrumb">
        <a href="<?php echo h(qf_url_page('index.php')); ?>"><i class="fa-solid fa-house" aria-hidden="true"></i></a>
        <span>»</span>
        <strong>关于</strong>
    </div>

    <section class="phpdo-about-hero">
        <div class="phpdo-about-topstats">
            <div class="phpdo-about-topstat"><b><?php echo qf_format_compact_number($stats['members']); ?></b><span>位成员</span></div>
            <div class="phpdo-about-topstat"><b><?php echo intval($stats['admins']); ?></b><span>位管理员</span></div>
            <div class="phpdo-about-topstat"><b><?php echo intval($stats['moderators']); ?></b><span>位版主</span></div>
            <div class="phpdo-about-topstat"><b><?php echo h(qf_site_founded_text()); ?></b><span>创建</span></div>
        </div>
    </section>

    <section class="phpdo-about-block">
        <h2>关于</h2>
        <div class="phpdo-about-text"><?php echo nl2br(h(qf_site_about_text())); ?></div>
    </section>

    <?php if (!empty($admins)) { ?>
    <section class="phpdo-about-block">
        <h2>我们的管理员</h2>
        <div class="phpdo-staff-grid">
            <?php foreach ($admins as $u) { echo phpdo_staff_card($u); } ?>
        </div>
    </section>
    <?php } ?>

    <?php if (!empty($mods)) { ?>
    <section class="phpdo-about-block">
        <h2>我们的版主</h2>
        <div class="phpdo-staff-grid">
            <?php foreach ($mods as $u) { echo phpdo_staff_card($u); } ?>
        </div>
    </section>
    <?php } ?>

    <section class="phpdo-about-block">
        <h2>联系我们</h2>
        <div class="phpdo-about-contact">
            <p>如果出现影响本站的关键问题或紧急事项，请联系 <?php if ($contact !== '') { ?><a href="mailto:<?php echo h($contact); ?>"><?php echo h($contact); ?></a><?php } else { ?>站点管理员<?php } ?>。</p>
            <p class="muted">如果您发现任何不当内容，请登录后与我们的版主和管理员联系。</p>
        </div>
    </section>

    <section class="phpdo-about-block">
        <h2>网站活动</h2>
        <div class="phpdo-about-activity">
            <div class="phpdo-about-metric"><b><?php echo qf_format_compact_number($stats['topics_7d']); ?></b><span>个话题</span><em>在过去 7 天</em></div>
            <div class="phpdo-about-metric"><b><?php echo qf_format_compact_number($stats['posts_today']); ?></b><span>个帖子</span><em>今天</em></div>
            <div class="phpdo-about-metric"><b><?php echo qf_format_compact_number($stats['active_7d']); ?></b><span>位活跃用户</span><em>在过去 7 天</em></div>
            <div class="phpdo-about-metric"><b><?php echo qf_format_compact_number($stats['registers_7d']); ?></b><span>个注册</span><em>在过去 7 天</em></div>
            <div class="phpdo-about-metric"><b><?php echo qf_format_compact_number($stats['likes_total']); ?></b><span>个赞</span><em>所有时间</em></div>
            <div class="phpdo-about-metric"><b><?php echo qf_format_compact_number($stats['posts_total']); ?></b><span>个帖子</span><em>累计</em></div>
        </div>
    </section>
</div>
<?php qf_include_footer(); ?>
