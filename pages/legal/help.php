<?php
/* “使用帮助”分部：4 个内容板块；由 pages/page.php include，自行输出卡片。 */
$help_contact_email = function_exists('pd_contact_email') ? trim((string) pd_contact_email()) : '';
?>
<section class="pd-info-block">
    <h2>使用指南</h2>
    <p>欢迎来到 php.do。这里是围绕 PHP 与相关技术交流分享的社区，几步即可开始：</p>
    <ul>
        <li>浏览顶部版块导航，进入感兴趣的版块查看主题；首页汇总最新与热门讨论。</li>
        <li>注册并登录后，点击“我要发帖”即可创建主题，支持 Markdown、代码块、图片与附件。</li>
        <li>版块内可开启分类筛选，点击分类标签会跳转到对应版块的分类列表。</li>
        <li>对帖子和回复可以点赞、投票与添加表情；有价值的内容欢迎积极互动。</li>
    </ul>
</section>

<section class="pd-info-block">
    <h2>帮助文档</h2>
    <h3>账号与个人中心</h3>
    <p>个人主页展示你的公开资料、最近主题和回复；个人设置页用于管理头像、邮箱、签名、密码以及 Passkey（无密码登录）。</p>
    <h3>发帖与内容</h3>
    <p>发帖请尽量补充 PHP 版本、运行环境、错误日志、最小复现代码和已尝试过的方案，便于他人快速定位问题。请勿公开真实密钥、Token、个人隐私或生产库信息。</p>
    <h3>签到与积分</h3>
    <p>每日签到可获得金币，发帖、回帖与被加精都会带来积分；积分与等级、排行榜挂钩。</p>
    <h3>通知与私信</h3>
    <p>被回复、被提及或收到私信时会产生系统通知；可在设置中控制通知提醒方式。</p>
</section>

<section class="pd-info-block">
    <h2>常见问题</h2>
    <h3>忘记密码怎么办？</h3>
    <p>在登录页通过绑定邮箱找回密码；若邮箱也无法访问，请通过下方邮箱联系管理员。</p>
    <h3>为什么发帖或回复需要验证码？</h3>
    <p>为了减少垃圾内容，新账号或频繁操作时可能触发验证码，正常使用一段时间后会减少。</p>
    <h3>可以上传哪些附件？</h3>
    <p>支持在帖子中上传图片与常见附件，具体大小与格式限制以发帖页提示为准。</p>
    <h3>如何删除自己的帖子或账号？</h3>
    <p>你可以删除自己发布的内容；如需注销账号，请通过下方邮箱联系我们处理。</p>
</section>

<section class="pd-info-block">
    <h2>联系</h2>
    <p>如果你在使用过程中遇到影响站点的关键问题或紧急事项，欢迎通过下方邮箱与我们取得联系，我们会尽快回复；如发现任何不当内容或需要举报，也可以登录后直接与版主、管理员联系。</p>
    <div class="pd-info-contact">
        <?php if ($help_contact_email !== '') { ?>
            <a class="pd-info-mail" href="mailto:<?php echo h($help_contact_email); ?>"><i class="fa-solid fa-envelope" aria-hidden="true"></i><span><?php echo h($help_contact_email); ?></span></a>
        <?php } else { ?>
            <p class="muted">暂未设置公开联系邮箱，请登录后与管理员联系。</p>
        <?php } ?>
    </div>
</section>
