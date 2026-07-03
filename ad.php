<?php
require_once __DIR__ . '/db.php';
$position = isset($_GET['position']) ? clean_text($_GET['position'], 30) : '';
echo qf_render_ad($position);
?>
