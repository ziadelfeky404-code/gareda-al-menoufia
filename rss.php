<?php
require_once 'includes/config.php';

$site_name = get_setting('site_name', 'جامعة المنوفية');
$site_url = get_setting('site_url', '');
if (!$site_url) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $site_url = $protocol . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost:8080');
}

function parse_article_date($date_str) {
    $ar_months = ['يناير','فبراير','مارس','أبريل','مايو','يونيو','يوليو','أغسطس','سبتمبر','أكتوبر','نوفمبر','ديسمبر'];
    $en_months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
    $date_str = str_replace($ar_months, $en_months, $date_str);
    $ts = strtotime($date_str);
    return $ts ? date('r', $ts) : date('r');
}

header('Content-Type: application/rss+xml; charset=utf-8');
echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
<channel>
    <title><?= htmlspecialchars($site_name) ?> - البوابة الإخبارية</title>
    <link><?= htmlspecialchars($site_url) ?></link>
    <description>آخر الأخبار من <?= htmlspecialchars($site_name) ?></description>
    <language>ar</language>
    <lastBuildDate><?= date('r') ?></lastBuildDate>
    <atom:link href="<?= htmlspecialchars($site_url) ?>/rss.php" rel="self" type="application/rss+xml"/>
    <?php
    $articles = $GLOBALS['articles'] ?? [];
    $latest = array_slice(array_reverse($articles), 0, 20);
    foreach ($latest as $a):
        $url = $site_url . '/article.php?id=' . $a['id'];
        $description = htmlspecialchars(mb_substr($a['paragraphs'][0] ?? '', 0, 300));
        $pubDate = parse_article_date($a['date']);
    ?>
    <item>
        <title><?= htmlspecialchars($a['title']) ?></title>
        <link><?= htmlspecialchars($url) ?></link>
        <guid isPermaLink="true"><?= htmlspecialchars($url) ?></guid>
        <description><?= $description ?></description>
        <category><?= htmlspecialchars($a['section']) ?></category>
        <pubDate><?= $pubDate ?></pubDate>
        <?php if (!empty($a['image'])): ?>
        <enclosure url="<?= htmlspecialchars($site_url . '/' . $a['image']) ?>" type="image/jpeg"/>
        <?php endif; ?>
    </item>
    <?php endforeach; ?>
</channel>
</rss>
