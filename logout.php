<?php
require_once __DIR__ . '/functions.php';
unset($_SESSION['qf_uid']);
session_regenerate_id(true);
redirect(qf_url_page('index.php'));
?>
