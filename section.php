<?php
session_start();
require_once 'includes/functions.php';

$isAdmin = isset($_SESSION['admin']);

$allSecs = get_sections();
$slugMap = [];
foreach ($allSecs as $s) {
    $slugMap[$s['slug']] = $s['name'];
}
$slugReverse = array_flip($slugMap);

$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';
$section = isset($slugMap[$slug]) ? $slugMap[$slug] : '';

if (!$section) {
    render_header('قسم غير موجود');
    render_topbar();
    render_navbar();
    echo '<section class="pb-5"><div class="container"><div class="no-results" style="text-align:center;padding:80px 20px;color:#999;"><i class="far fa-frown" style="font-size:48px;margin-bottom:15px;color:#ddd;display:block;"></i><h4 style="font-size:18px;font-weight:700;color:#666;">عذراً، القسم المطلوب غير موجود</h4><p style="color:#999;font-size:14px;font-weight:400;">' . htmlspecialchars($slug) . '</p></div></div></section>';
    render_footer();
    exit;
}

$sectionArticles = array_reverse(get_articles_by_section($section));
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 12;
$total = count($sectionArticles);
$totalPages = max(1, ceil($total / $perPage));
$offset = ($page - 1) * $perPage;
$pageArticles = array_slice($sectionArticles, $offset, $perPage);

render_header($section);
render_topbar();
render_navbar();
?>
<style>
:root {
    --fonts: 'Tajawal', 'Cairo', sans-serif;
    --red: #157039;
    --red-dark: #0f5a2d;
    --red-light: #1a8a45;
    --dark: #091e3a;
    --gray: #4e4e4e;
    --gray-light: #818b9d;
    --bg-gray: #f2f2f2;
    --bg-section: #f6f5f6;
    --white: #fff;
    --title-size: 19px;
    --sm-title: 14px;
    --bg-title: 29px;
}
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: var(--fonts);
    font-size: var(--title-size);
    text-align: right;
    background: var(--white);
    font-weight: 700;
    color: var(--dark);
    overflow-x: hidden;
}
a, a:hover { text-decoration: none; color: var(--dark); }
.container { max-width: 1300px; }
.top-bar {
    background: #fefefe;
    position: sticky;
    top: 0;
    z-index: 99999;
    padding: 0;
}
.top-bar .top-links {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: flex-end;
    gap: 5px;
    padding: 6px 0;
    font-size: var(--sm-title);
    color: #7e7e7e;
    font-weight: 400;
    font-family: 'Cairo', sans-serif;
}
.top-bar .top-links a { color: #7e7e7e; font-size: var(--sm-title); padding: 2px 4px; }
.top-bar .top-links .lang { color: var(--red) !important; font-weight: 700; }
.top-bar .top-links .divider { color: #ccc; }
.top-bar .top-links .social-icon {
    display: inline-flex; align-items: center; justify-content: center;
    width: 22px; height: 22px; background: #989898; border-radius: 50%;
    color: #fff; font-size: 12px;
}
.top-bar .top-links .social-icon:hover { background: var(--red); }
.logo-area { display: flex; align-items: center; flex-wrap: wrap; }
.logo-text h1 { font-size: 28px; font-weight: 900; color: var(--red); margin: 0; line-height: 1.2; }
.logo-text small { font-size: 13px; color: var(--gray-light); font-weight: 500; }
.office-info { text-align: center; margin-top: 10px; }
.office-info span { color: var(--red); font-size: 15px; font-family: 'Cairo', sans-serif; font-weight: 400; }
.office-info p { color: var(--dark); font-size: 15px; font-weight: 800; margin-bottom: 0; }
.nav-section { position: sticky; top: 0; z-index: 999999; background: var(--red); }
.nav-section .navbar { background: var(--red) !important; padding: 0.3rem 0; }
.nav-section .navbar-nav { }
.nav-section .navbar-nav .nav-link {
    color: var(--white); font-size: 16px; font-weight: 700;
    padding: 10px 14px; transition: background 0.2s;
}
.nav-section .navbar-nav .nav-link:hover { background: rgba(255,255,255,0.15); }
.nav-section .navbar-nav .nav-link.active { background: rgba(255,255,255,0.2); }
.nav-section .navbar-toggler { border: none; color: var(--white); font-size: 1.3rem; }
.nav-section .navbar-toggler:focus { box-shadow: none; }
.search-box { display: flex; align-items: center; }
.search-box input {
    border: 1px solid rgba(255,255,255,0.3); background: rgba(255,255,255,0.15);
    color: var(--white); padding: 5px 12px; border-radius: 0; font-size: 14px; width: 140px;
}
.search-box input::placeholder { color: rgba(255,255,255,0.7); }
.search-box input:focus { outline: none; background: rgba(255,255,255,0.25); }
.search-box button { background: var(--dark); color: var(--white); border: none; padding: 5px 14px; font-size: 13px; font-weight: 700; cursor: pointer; }
.page-header {
    background: var(--dark);
    padding: 25px 0;
    margin-bottom: 30px;
    position: relative;
}
.page-header h1 {
    color: var(--white);
    font-size: 32px;
    font-weight: 900;
    margin: 0;
}
.page-header .breadcrumb-custom {
    color: rgba(255,255,255,0.6);
    font-size: 14px;
    font-weight: 400;
    margin-top: 5px;
}
.page-header .breadcrumb-custom a { color: rgba(255,255,255,0.8); }
.page-header .breadcrumb-custom a:hover { color: var(--white); }
.page-header .breadcrumb-custom .sep { margin: 0 8px; }
.section-title-wrap { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; border-bottom: 2px solid #e0e0e0; padding-bottom: 8px; }
.section-title { font-size: 22px; font-weight: 800; color: var(--dark); padding-right: 12px; border-right: 4px solid var(--red); }
.section-title a { color: var(--dark); }
.section-title a:hover { color: var(--red); }
.section-more { font-size: 14px; color: var(--red); font-weight: 600; }
.section-more:hover { text-decoration: underline; }
.news-card {
    margin-bottom: 25px;
    border: 1px solid #eee;
    border-radius: 0;
    overflow: hidden;
    transition: all 0.3s ease;
    background: var(--white);
}
.news-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
}
.news-card .card-img { position: relative; overflow: hidden; }
.news-card .card-img img { width: 100%; height: 200px; object-fit: cover; transition: transform 0.3s; }
.news-card:hover .card-img img { transform: scale(1.05); }
.news-card .card-badge { position: absolute; top: 10px; right: 10px; background: var(--red); color: var(--white); font-size: 12px; font-weight: 600; padding: 3px 10px; font-family: 'Cairo', sans-serif; }
.news-card .card-body { padding: 15px; }
.news-card .card-body h3 { font-size: 16px; font-weight: 800; line-height: 1.4; margin-bottom: 8px; }
.news-card .card-body h3 a { color: var(--dark); }
.news-card .card-body h3 a:hover { color: var(--red); }
.news-card .card-body .card-text { font-size: 13px; color: var(--gray-light); font-weight: 400; margin-bottom: 10px; line-height: 1.6; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }
.news-card .card-body .card-date { font-size: 12px; color: #aaa; font-weight: 400; font-family: 'Cairo', sans-serif; }
.news-card .card-body .card-date i { margin-left: 4px; }
.news-card .card-body .card-author { font-size: 12px; color: var(--gray-light); font-weight: 400; font-family: 'Cairo', sans-serif; margin-top: 6px; }
.news-card .card-body .card-author i { margin-left: 4px; }
.pagination-wrap { display: flex; justify-content: center; gap: 5px; margin: 30px 0 20px; }
.pagination-wrap a { display: inline-flex; align-items: center; justify-content: center; min-width: 36px; height: 36px; padding: 0 10px; border: 1px solid #ddd; font-size: 14px; font-weight: 600; color: #555; border-radius: 4px; transition: all 0.2s; }
.pagination-wrap a:hover, .pagination-wrap a.active { background: var(--red); color: var(--white); border-color: var(--red); }
.ad-banner { background: #f0f0f0; text-align: center; padding: 15px; margin: 15px 0; min-height: 90px; display: flex; align-items: center; justify-content: center; font-size: 13px; color: #999; border: 1px dashed #ddd; }
.ad-banner-img { max-width: 100%; height: auto; margin: 15px 0; display: block; }
.btn-add-article{display:inline-flex;align-items:center;justify-content:center;width:34px;height:34px;background:#157039;color:#fff;border-radius:50%;font-size:15px;transition:all .2s;margin-right:8px;text-decoration:none;vertical-align:middle}
.btn-add-article:hover{background:#0f5a2d;color:#fff;transform:scale(1.1);text-decoration:none}
.admin-bar{background:#091e3a;color:#ccc;padding:6px 0;font-size:12px;text-align:center;font-weight:400}
.admin-bar a{color:#fff;font-weight:700;margin:0 8px}
.admin-bar a:hover{text-decoration:underline}
.footer { background: var(--dark); color: #ccc; padding: 40px 0 0; }
.footer h5 { color: var(--white); font-size: 18px; font-weight: 800; margin-bottom: 15px; padding-bottom: 8px; border-bottom: 2px solid var(--red); }
.footer ul { list-style: none; padding: 0; }
.footer ul li { margin-bottom: 8px; }
.footer ul li a { color: #aaa; font-size: 14px; font-weight: 400; transition: color 0.2s; }
.footer ul li a:hover { color: var(--white); }
.footer-social { display: flex; gap: 8px; margin-top: 15px; }
.footer-social a { display: flex; align-items: center; justify-content: center; width: 36px; height: 36px; background: rgba(255,255,255,0.1); color: var(--white); border-radius: 50%; font-size: 16px; transition: background 0.2s; }
.footer-social a:hover { background: var(--red); }
.footer-bottom { background: rgba(0,0,0,0.3); padding: 15px 0; margin-top: 30px; text-align: center; font-size: 13px; font-weight: 400; color: #888; }
@media (max-width: 991px) {
    .nav-section .navbar-nav .nav-link { font-size: 14px; padding: 8px 10px; }
    .logo-text h1 { font-size: 22px; }
    .search-box input { width: 100px; }
    .page-header h1 { font-size: 26px; }
}
@media (max-width: 767px) {
    :root { --bg-title: 18px; --title-size: 16px; }
    .top-bar .top-links { justify-content: center; font-size: 12px; }
    .office-info span, .office-info p { font-size: 13px; }
    .news-card .card-img img { height: 170px; }
    .section-title { font-size: 18px; }
    .page-header { padding: 18px 0; }
    .page-header h1 { font-size: 22px; }
}
@media (max-width: 480px) {
    .top-bar { padding: 3px 0; }
    .nav-section .navbar-nav .nav-link { font-size: 13px; text-align: center; }
    .search-box input { width: 80px; font-size: 12px; }
    .search-box button { font-size: 12px; padding: 4px 8px; }
    .footer { padding: 25px 0 0; }
}
</style>

<?php if ($isAdmin): ?>
<div class="admin-bar">
    <i class="fas fa-user-shield"></i> أنت مسجل كمدير —
    <a href="admin/quick-add.php"><i class="fas fa-plus-circle"></i> إضافة مقال</a>
    <span style="opacity:0.3;margin:0 4px">|</span>
    <a href="admin/dashboard.php"><i class="fas fa-tachometer-alt"></i> لوحة التحكم</a>
    <span style="opacity:0.3;margin:0 4px">|</span>
    <a href="admin/logout.php"><i class="fas fa-sign-out-alt"></i> تسجيل الخروج</a>
</div>
<?php endif; ?>

<section class="page-header">
    <div class="container">
        <h1><?= htmlspecialchars($section) ?><?php if ($isAdmin): ?><a href="admin/quick-add.php?section=<?= urlencode($section) ?>" class="btn-add-article" title="إضافة مقال"><i class="fas fa-plus"></i></a><?php endif; ?></h1>
        <div class="breadcrumb-custom">
            <a href="index.php">الرئيسية</a>
            <span class="sep">/</span>
            <span><?= htmlspecialchars($section) ?></span>
        </div>
    </div>
</section>

<section class="pb-4">
    <div class="container">
        <div class="section-title-wrap">
            <h2 class="section-title">جميع أخبار <?= htmlspecialchars($section) ?></h2>
            <span class="section-more">آخر الأخبار</span>
        </div>

        <?php if (empty($sectionArticles)): ?>
        <div class="no-results" style="text-align:center;padding:60px 20px;color:#999;">
            <i class="far fa-frown" style="font-size:48px;margin-bottom:15px;color:#ddd;display:block;"></i>
            <h4 style="font-size:18px;font-weight:700;color:#666;">لا توجد مقالات في هذا القسم</h4>
        </div>
        <?php else: ?>
        <div class="row g-4">
            <?php foreach ($pageArticles as $a): ?>
            <div class="col-lg-4 col-md-6">
                <div class="news-card card">
                    <div class="card-img">
                        <?php if (!empty($a['badge'])): ?>
                        <span class="card-badge"><?= htmlspecialchars($a['badge']) ?></span>
                        <?php endif; ?>
                        <img src="<?= htmlspecialchars($a['image']) ?>" alt="<?= htmlspecialchars($a['title']) ?>" onerror="this.src='data:image/svg+xml,<svg xmlns=\'http://www.w3.org/2000/svg\' width=\'300\' height=\'200\'><rect fill=\'%23f0f0f0\' width=\'300\' height=\'200\'/><text x=\'150\' y=\'105\' text-anchor=\'middle\' fill=\'%23ccc\' font-size=\'14\'>صورة</text></svg>'">
                    </div>
                    <div class="card-body">
                        <h3><a href="article.php?id=<?= $a['id'] ?>"><?= htmlspecialchars($a['title']) ?></a></h3>
                        <p class="card-text"><?= htmlspecialchars(mb_substr(implode(' ', $a['paragraphs']), 0, 150)) ?>...</p>
<div class="card-date"><i class="far fa-calendar-alt"></i> <?= htmlspecialchars($a['date'] ?? '') ?></div>
<div class="card-author"><i class="fas fa-user"></i> <?= htmlspecialchars($a['author'] ?: 'بوابة الجامعة') ?></div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if ($totalPages > 1): ?>
        <div class="pagination-wrap">
            <?php if ($page > 1): ?>
            <a href="?slug=<?= $slugReverse[$section] ?>&page=<?= $page - 1 ?>"><i class="fas fa-chevron-right"></i></a>
            <?php endif; ?>
            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
            <a href="?slug=<?= $slugReverse[$section] ?>&page=<?= $p ?>" <?= $p === $page ? 'class="active"' : '' ?>><?= $p ?></a>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?>
            <a href="?slug=<?= $slugReverse[$section] ?>&page=<?= $page + 1 ?>"><i class="fas fa-chevron-left"></i></a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</section>

<?php render_articles_json_script(); ?>
<?php render_footer(); ?>
