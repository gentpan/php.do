</main>
<footer class="footer">
    <div class="wrap copyright">
        <?php echo h(qf_site_name()); ?> ·
        <?php if (qf_setting('icp_code', '') !== '') { ?><span class="icp-code"><?php echo qf_setting('icp_code', ''); ?></span> · <?php } ?>
        <span class="powered-text">Powered by <a class="powered-by" href="http://lume.0816y.com/" target="_blank" rel="noopener">Lume 1.0</a></span>
        <?php if (qf_friend_links_enabled()) { $friend_links = qf_friend_links(); if (!empty($friend_links)) { ?>
            <span class="footer-friend-links"> ·
                <?php foreach ($friend_links as $link) { ?>
                    <a href="<?php echo h($link['url']); ?>" target="_blank" rel="noopener"><?php echo h($link['name']); ?></a>
                <?php } ?>
            </span>
        <?php } } ?>
    </div>
</footer>
<script src="assets/litezoom.min.js"></script>
<script src="<?php echo h(qf_asset_js('app')); ?>"></script>
<?php echo qf_setting('stats_code', ''); ?>
</body>
</html>
