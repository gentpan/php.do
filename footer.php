</main>
<?php
$footer_icp_code = qf_setting('icp_code', '');
$footer_friend_links = array();
if (qf_friend_links_enabled()) {
    $footer_friend_links = qf_friend_links();
}
?>
<?php if ($footer_icp_code !== '' || !empty($footer_friend_links)) { ?>
<footer class="footer">
    <div class="wrap footer-links">
        <?php if ($footer_icp_code !== '') { ?><span class="icp-code"><?php echo h($footer_icp_code); ?></span><?php } ?>
        <?php if (!empty($footer_friend_links)) { ?>
            <span class="footer-friend-links">
                <?php foreach ($footer_friend_links as $link) { ?>
                    <a href="<?php echo h($link['url']); ?>" target="_blank" rel="noopener"><?php echo h($link['name']); ?></a>
                <?php } ?>
            </span>
        <?php } ?>
    </div>
</footer>
<?php } ?>
<script src="assets/litezoom.min.js"></script>
<script src="<?php echo h(qf_asset_js('app')); ?>"></script>
<?php echo qf_setting('stats_code', ''); ?>
</body>
</html>
