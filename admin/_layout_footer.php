        </main>
        <footer class="admin-footer">
            <span>&copy; <?php echo date('Y'); ?> <?php echo h(qf_site_name()); ?> · Admin</span>
            <span class="admin-footer-meta"><?php echo number_format(qf_perf_seconds(), 3); ?>s · SQL <?php echo intval(qf_perf_sql_count()); ?></span>
        </footer>
    </div>
</div>
<div class="admin-sidebar-backdrop" data-admin-backdrop hidden></div>

<div class="admin-confirm" id="admin-confirm" hidden>
    <div class="admin-confirm-panel" role="dialog" aria-modal="true" aria-labelledby="admin-confirm-title">
        <h3 id="admin-confirm-title">请确认</h3>
        <p data-admin-confirm-msg></p>
        <div class="admin-confirm-actions">
            <button type="button" class="btn btn-light" data-admin-confirm-cancel>取消</button>
            <button type="button" class="btn btn-danger" data-admin-confirm-ok>确定</button>
        </div>
    </div>
</div>

<script src="assets/js/admin-shell.js?v=<?php echo file_exists(__DIR__ . '/../assets/js/admin-shell.js') ? filemtime(__DIR__ . '/../assets/js/admin-shell.js') : time(); ?>"></script>
<?php if (!empty($admin_extra_js)) { foreach ((array)$admin_extra_js as $js) { ?>
<script src="<?php echo h($js); ?>"></script>
<?php } } ?>
</body>
</html>
