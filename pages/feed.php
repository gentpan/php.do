<?php
require_once __DIR__ . '/../functions.php';

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = isset($_SERVER['HTTP_HOST']) ? preg_replace('/[^a-zA-Z0-9.\-:]/', '', $_SERVER['HTTP_HOST']) : 'php.do';
$base = $scheme . '://' . $host;

$rows = mysqli_query(db(), "SELECT t.id, t.title, t.content, t.created_at, u.nickname, u.username
    FROM qf_threads t
    LEFT JOIN qf_users u ON t.user_id=u.id
    WHERE t.is_deleted=0 AND t.is_top<>2
    ORDER BY t.created_at DESC
    LIMIT 30");

header('Content-Type: application/rss+xml; charset=utf-8');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
<channel>
<title><?php echo h(qf_site_name()); ?></title>
<link><?php echo h($base); ?>/</link>
<atom:link href="<?php echo h($base); ?>/feed" rel="self" type="application/rss+xml" />
<description><?php echo h(qf_site_desc()); ?></description>
<language>zh-CN</language>
<generator><?php echo h(qf_site_name()); ?></generator>
<?php while ($rows && ($r = mysqli_fetch_assoc($rows))) {
    $url = $base . '/' . ltrim(qf_url_thread(intval($r['id'])), '/');
    $author = qf_user_display_name($r);
    $text = (string)$r['content'];
    $text = preg_replace('/\[[^\]]{0,40}\]/u', '', $text);
    $text = trim(strip_tags($text));
    $excerpt = function_exists('mb_substr') ? mb_substr($text, 0, 200, 'UTF-8') : substr($text, 0, 200);
    $pub = date(DATE_RSS, strtotime($r['created_at']));
?>
<item>
<title><?php echo h($r['title']); ?></title>
<link><?php echo h($url); ?></link>
<guid isPermaLink="true"><?php echo h($url); ?></guid>
<?php if ($author !== '') { ?><author><?php echo h($author); ?></author><?php } ?>
<pubDate><?php echo h($pub); ?></pubDate>
<description><![CDATA[<?php echo $excerpt; ?>]]></description>
</item>
<?php } ?>
</channel>
</rss>
