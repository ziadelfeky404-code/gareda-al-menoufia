<?php
session_start();
require_once 'includes/functions.php';

$isAdmin = isset($_SESSION['admin']);

$sections = ['أخبار المنوفية', 'منشآت الجامعة', 'ندوات ومؤتمرات', 'تكريم ومسابقات', 'الفن والمسابقات'];
$sectionArticles = [];
foreach ($sections as $sec) {
    $arts = get_articles_by_section($sec);
    $arts = array_reverse($arts);
    $sectionArticles[$sec] = array_slice($arts, 0, 8);
}
$akhbarArts  = $sectionArticles['أخبار المنوفية'] ?? [];
$monshatArts = $sectionArticles['منشآت الجامعة'] ?? [];
$nadawatArts = $sectionArticles['ندوات ومؤتمرات'] ?? [];
$takreemArts = $sectionArticles['تكريم ومسابقات'] ?? [];
$fannArts    = $sectionArticles['الفن والمسابقات'] ?? [];

$tickerIds = get_setting('ticker_news_ids', [2,5,8,17,28]);
$tickerArticles = [];
foreach ($tickerIds as $tid) {
    $a = get_article($tid);
    if ($a) $tickerArticles[] = $a;
}

// Hero articles with pin support
$heroPins = get_setting('hero_pins', []);
$heroArts = [];
foreach ($heroPins as $pid) {
    $a = get_article($pid);
    if ($a) $heroArts[] = $a;
}
// Fill empty slots with latest articles
$allArts = $articles;
$allArts = array_reverse($allArts);
foreach ($allArts as $aa) {
    if (count($heroArts) >= 6) break;
    $found = false;
    foreach ($heroArts as $h) { if ($h['id'] == $aa['id']) { $found = true; break; } }
    if (!$found) $heroArts[] = $aa;
}
while (count($heroArts) < 6) { $heroArts[] = null; }
// Extra articles for slideshow
$heroSlidePins = get_setting('hero_slide_pins', []);
$heroSlides = [];
foreach ($heroSlidePins as $sid) {
    $a = get_article($sid);
    if ($a) $heroSlides[] = $a;
}
// Fill slideshow with latest unique articles
foreach ($allArts as $aa) {
    if (count($heroSlides) >= 7) break;
    $found = false;
    foreach ($heroSlides as $h) { if ($h['id'] == $aa['id']) { $found = true; break; } }
    if (!$found) $heroSlides[] = $aa;
}
if (empty($heroSlides)) { $heroSlides[] = get_latest_article(); }

$mostReadIds = [1, 2, 5, 7];
$mostReadArticles = [];
foreach ($mostReadIds as $id) {
    $a = get_article($id);
    if ($a) $mostReadArticles[] = $a;
}

$urgentIds = [22, 23, 29, 28];
$urgentArticles = [];
foreach ($urgentIds as $id) {
    $a = get_article($id);
    if ($a) $urgentArticles[] = $a;
}
?><!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<title>بوابة جامعة المنوفية الإخبارية</title>
<link rel="icon" type="image/png" href="<?= htmlspecialchars(get_setting('favicon_url')) ?>">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&family=Tajawal:wght@400;500;700;800;900&display=swap" rel="stylesheet">
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
    color: var(--white); font-size: 14px; font-weight: 700;
    padding: 6px 14px; border-radius: 50px; transition: background .3s;
}
.nav-section .navbar-nav .nav-link:hover { background: rgba(255,255,255,0.2); }
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
.top-bar .ad-banner { padding:0; margin:0; min-height:auto; font-size:11px; border:none; background:transparent; display:inline; }
.top-bar .ad-banner-img { margin:0; max-width:none; height:auto; max-height:50px; display:inline; vertical-align:middle; }
.ticker-section { background: var(--gray); color: var(--white); margin-top: 5px; margin-bottom: 10px; }
.ticker-label { background: var(--red); padding: 8px 20px; font-weight: 800; font-size: 16px; white-space: nowrap; }
.ticker-content { overflow: hidden; padding: 8px 10px; }
.ticker-content marquee a { color: var(--white); font-size: 15px; font-weight: 500; }
.ticker-content marquee a:hover { text-decoration: underline; }
.ticker-dot { margin: 0 12px; }
.hero-section { margin-top: 15px; }
.hero-overlay {
    position: absolute; bottom: 0; left: 0; right: 0; padding: 30px;
    background: linear-gradient(0deg, rgba(0,0,0,0.95) 0%, rgba(0,0,0,0.7) 40%, rgba(0,0,0,0) 100%);
}
.hero-overlay .hero-category { font-size: 13px; font-weight: 400; color: var(--white); font-family: 'Cairo', sans-serif; margin-bottom: 5px; }
.hero-overlay .hero-category::after { content: ''; display: block; width: 40px; height: 3px; background: var(--red); margin-top: 4px; }
.hero-overlay h2 { color: var(--white); font-size: 26px; font-weight: 800; line-height: 1.3; margin-top: 8px; }
.hero-overlay h2:hover { color: #ffcccc; }
.hero-side { display: flex; flex-direction: column; flex: 1; }
.hero-side-item { position: relative; flex: 1; overflow: hidden; }
.hero-side-item img { width: 100%; height: 100%; object-fit: cover; }
.hero-side-item .hero-overlay { padding: 15px; }
.hero-side-item .hero-overlay h2 { font-size: 15px; margin-top: 3px; }
.hero-side-item .hero-overlay .hero-category::after { width: 25px; }
.lg-hero { min-height: 180px; }
.sm-hero { min-height: 170px; }
.hero-main { position: relative; overflow: hidden; flex: 1; min-height: 380px; transition:transform 0.3s ease; }
.hero-main:hover { transform:scale(1.01); }
.hero-main img { width: 100%; height: 100%; object-fit: cover; transition:transform 0.5s ease; }
.hero-main:hover img { transform:scale(1.05); }
.hero-main .hero-overlay { padding: 30px; }
.hero-main .hero-overlay h2 { font-size: 26px; }
.col-lg-6 .hero-side-item { min-height: 230px; }
.section-title-wrap { display: flex; align-items: center; justify-content: space-between; margin-bottom: 15px; border-bottom: 2px solid #e0e0e0; padding-bottom: 8px; }
.section-title { font-size: 22px; font-weight: 800; color: var(--dark); padding-right: 12px; border-right: 4px solid var(--red); }
.section-title a { color: var(--dark); }
.section-title a:hover { color: var(--red); }
.section-more { font-size: 14px; color: var(--red); font-weight: 600; }
.section-more:hover { text-decoration: underline; }
.news-card { margin-bottom: 20px; transition: transform 0.2s; }
.news-card:hover { transform: translateY(-2px); }
.news-card .card-img { position: relative; overflow: hidden; }
.news-card .card-img img { width: 100%; height: 180px; object-fit: cover; transition: transform 0.3s; }
.news-card:hover .card-img img { transform: scale(1.05); }
.news-card .card-badge { position: absolute; top: 10px; right: 10px; background: var(--red); color: var(--white); font-size: 12px; font-weight: 600; padding: 3px 10px; font-family: 'Cairo', sans-serif; }
.news-card .card-body { padding: 10px 0; }
.news-card .card-body h3 { font-size: 16px; font-weight: 800; line-height: 1.4; margin-bottom: 5px; }
.news-card .card-body h3 a { color: var(--dark); }
.news-card .card-body h3 a:hover { color: var(--red); }
.news-card .card-body p { font-size: 13px; color: var(--gray-light); font-weight: 400; margin-bottom: 0; }
.list-news-item { display: flex; gap: 12px; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid #eee; }
.list-news-item:last-child { border-bottom: none; }
.list-news-item img { width: 120px; height: 80px; object-fit: cover; flex-shrink: 0; }
.list-news-item h4 { font-size: 14px; font-weight: 700; line-height: 1.4; margin: 0; }
.list-news-item h4 a { color: var(--dark); }
.list-news-item h4 a:hover { color: var(--red); }
.list-news-item .list-date { font-size: 11px; color: var(--gray-light); font-weight: 400; margin-top: 4px; }
.sidebar-box { background: #fafafa; border: 1px solid #e8e8e8; margin-bottom: 20px; }
.sidebar-box .sidebar-header { background: var(--dark); color: var(--white); padding: 10px 15px; font-size: 17px; font-weight: 800; }
.sidebar-box .sidebar-body { padding: 12px; }
.urgent-item { padding: 10px 0; border-bottom: 1px solid #eee; display: flex; gap: 10px; align-items: flex-start; }
.urgent-item:last-child { border-bottom: none; }
.urgent-item .urgent-num { background: var(--red); color: var(--white); width: 26px; height: 26px; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 800; flex-shrink: 0; margin-top: 2px; }
.urgent-item h5 { font-size: 13px; font-weight: 700; line-height: 1.4; margin: 0; }
.urgent-item h5 a { color: var(--dark); }
.urgent-item h5 a:hover { color: var(--red); }

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
.ad-banner { background: #f0f0f0; text-align: center; padding: 15px; margin: 15px 0; min-height: 90px; display: flex; align-items: center; justify-content: center; font-size: 13px; color: #999; border: 1px dashed #ddd; }
.ad-banner-img { max-width: 100%; height: auto; margin: 15px 0; display: block; }
.btn-add-article{display:inline-flex;align-items:center;justify-content:center;width:34px;height:34px;background:#157039;color:#fff;border-radius:50%;font-size:15px;transition:all .2s;margin-right:8px;text-decoration:none;vertical-align:middle}
.btn-add-article:hover{background:#0f5a2d;color:#fff;transform:scale(1.1);text-decoration:none}
.admin-bar{background:#091e3a;color:#ccc;padding:6px 0;font-size:12px;text-align:center;font-weight:400}
.admin-bar a{color:#fff;font-weight:700;margin:0 8px}
.admin-bar a:hover{text-decoration:underline}
@media (max-width: 991px) {
    .nav-section .navbar-nav .nav-link { font-size: 14px; padding: 8px 10px; }
    .hero-main { min-height: 280px; }
    .hero-overlay h2 { font-size: 20px; }
    .logo-text h1 { font-size: 22px; }
    .search-box input { width: 100px; }
}
@media (max-width: 767px) {
    :root { --bg-title: 18px; --title-size: 16px; }
    .top-bar .top-links { justify-content: center; font-size: 12px; }
    .office-info span, .office-info p { font-size: 13px; }
    .hero-main { min-height: 250px; }
    .hero-overlay { padding: 15px; }
    .hero-overlay h2 { font-size: 16px; }
    .hero-side-item { min-height: 150px !important; }
    .news-card .card-img img { height: 160px; }
    .section-title { font-size: 18px; }
    .writer-card { margin-bottom: 15px; }
    .ticker-label { font-size: 14px; padding: 6px 12px; }
}
@media (max-width: 480px) {
    .top-bar { padding: 3px 0; }
    .hero-main { min-height: 200px; }
    .hero-overlay h2 { font-size: 14px; }
    .hero-side-item { min-height: 120px !important; }
    .nav-section .navbar-nav .nav-link { font-size: 13px; text-align: center; }
    .search-box input { width: 80px; font-size: 12px; }
    .search-box button { font-size: 12px; padding: 4px 8px; }
    .footer { padding: 25px 0 0; }
}
@keyframes fadeUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}
@keyframes pulse{0%,100%{opacity:1}50%{opacity:0.6}}
@keyframes shimmer{0%{background-position:-200% 0}100%{background-position:200% 0}}
.hero-side-item,.news-card,.urgent-item,.list-news-item,.sidebar-box{animation:fadeUp 0.5s ease forwards;opacity:0}
.hero-side-item:nth-child(1){animation-delay:0.1s}
.hero-side-item:nth-child(2){animation-delay:0.2s}
.news-card{animation-delay:0.15s}
.urgent-item{animation-delay:0.1s}
.ticker-label{animation:pulse 1.5s ease-in-out infinite}
.news-card{transition:transform 0.3s ease,box-shadow 0.3s ease}
.news-card:hover{transform:translateY(-5px);box-shadow:0 8px 25px rgba(0,0,0,0.1)}
.section-title{border-right:4px solid var(--red);transition:border-color 0.3s}
.section-title-wrap:hover .section-title{border-right-color:var(--red-dark)}
.hero-side-item{transition:transform 0.3s ease}
.hero-side-item:hover{transform:scale(1.02);z-index:5}
.hero-side-item img{transition:transform 0.5s ease}
.hero-side-item:hover img{transform:scale(1.08)}
.slide-dot{transition:all 0.3s ease}
.slide-dot:hover{transform:scale(1.3)}
</style>
</head>
<body>

<!-- ===== TOP BAR ===== -->
<section class="top-bar">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="top-links">
                    <span id="headerDate" class="ms-1"></span>
                    <span class="divider">|</span>
                    <a href="contact.php">اتصل بنا</a>
                    <a href="<?= htmlspecialchars(get_setting('facebook_url')) ?>" class="social-icon" target="_blank"><i class="fab fa-facebook-f"></i></a>
                    <a href="<?= htmlspecialchars(get_setting('twitter_url')) ?>" class="social-icon" target="_blank"><i class="fab fa-twitter"></i></a>
                    <a href="<?= htmlspecialchars(get_setting('youtube_url')) ?>" class="social-icon" target="_blank"><i class="fab fa-youtube"></i></a>
                    <a href="<?= htmlspecialchars(get_setting('instagram_url')) ?>" class="social-icon" target="_blank"><i class="fab fa-instagram"></i></a>
                </div>
            </div>
        </div>
        <div class="row align-items-center">
            <div class="col-auto">
                <div class="logo-area">
                    <a href="index.php">
                        <img src="<?= htmlspecialchars(get_setting('logo_url')) ?>" alt="<?= htmlspecialchars(get_setting('site_name')) ?>" style="height:120px;width:auto;">
                    </a>
                </div>
            </div>
            <div class="col text-center d-none d-md-block">
                <div class="office-info">
                    <span><?= htmlspecialchars(get_setting('editor_name', 'رئيس التحرير')) ?></span>
                    <p><?= htmlspecialchars(get_setting('editor_title', 'أ.د. أحمد القاصد')) ?></p>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- ===== NAVBAR ===== -->
<section class="nav-section" style="position:sticky;top:0;z-index:999999">
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
                <i class="fas fa-bars"></i>
            </button>
            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="index.php">الرئيسية</a></li>
                    <?php $navSecs = get_sections(); foreach ($navSecs as $ns): ?>
                    <li class="nav-item"><a class="nav-link" href="section.php?slug=<?= htmlspecialchars($ns['slug']) ?>"><?= htmlspecialchars($ns['name']) ?></a></li>
                    <?php endforeach; ?>
                </ul>
                <div class="search-box me-2">
                    <button type="button" onclick="var q=this.nextElementSibling.value;if(q.trim())window.location='search.php?q='+encodeURIComponent(q.trim())"><i class="fas fa-search"></i></button>
                    <input type="text" class="searchInput" placeholder="بحث...">
                </div>
            </div>
        </div>
    </nav>
</section>

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

<!-- ===== BREAKING TICKER ===== -->
<?php if (!empty($tickerArticles)): ?>
<section class="ticker-section">
    <div class="container">
        <div class="row">
            <div class="col-12 d-flex">
                <div class="ticker-label d-none d-md-block">عاجل</div>
                <div class="ticker-content flex-grow-1">
                    <marquee behavior="scroll" direction="right" scrollamount="4" scrolldelay="60" onmouseover="this.stop();" onmouseout="this.start();">
                        <?php foreach ($tickerArticles as $i => $ta): ?>
                        <?php if ($i > 0): ?><span class="ticker-dot">|</span><?php endif; ?>
                        <a href="article.php?id=<?= $ta['id'] ?>"><?= htmlspecialchars($ta['title']) ?></a>
                        <?php endforeach; ?>
                    </marquee>
                </div>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ===== HERO SECTION ===== -->
<section class="hero-section">
    <div class="container">
        <div class="row g-2">
<!-- Left column (slideshow - big hero) -->
<div class="col-lg-6 mb-2 mb-lg-0 d-flex flex-column" id="heroSlideshow">
    <?php foreach ($heroSlides as $si => $slide): ?>
    <div class="hero-main" data-slide="<?= $si ?>" data-pin-pos="hero_slide_<?= $si ?>" style="<?= $si > 0 ? 'display:none' : '' ?>">
        <?php if ($slide): ?>
        <img src="<?= htmlspecialchars($slide['image'] ?? '') ?>" alt="<?= htmlspecialchars($slide['title'] ?? '') ?>">
        <div class="hero-overlay">
            <div class="hero-category"><?= htmlspecialchars($slide['section'] ?? '') ?></div>
            <h2><a href="article.php?id=<?= $slide['id'] ?? 0 ?>" style="color:inherit;"><?= htmlspecialchars($slide['title'] ?? '') ?></a></h2>
        </div>
        <?php if ($isAdmin): ?>
        <div style="position:absolute;top:4px;left:4px;background:#e74c3c;color:#fff;border-radius:4px;padding:1px 6px;font-size:10px;z-index:20;font-weight:700">#<?= $slide['id'] ?? '?' ?></div>
        <div onclick="editHeroPin(this.parentElement, 'hero_slide_<?= $si ?>')" style="position:absolute;top:4px;right:4px;background:rgba(0,0,0,0.7);color:#fff;border-radius:4px;padding:2px 8px;font-size:11px;cursor:pointer;opacity:0;z-index:20;transition:opacity 0.3s" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0'" class="hero-edit-btn"><i class="fas fa-pen"></i> تغيير</div>
        <?php endif; ?>
        <?php else: ?>
        <div style="width:100%;height:380px;background:#eee;display:flex;align-items:center;justify-content:center;color:#ccc;font-weight:400">لا يوجد مقال</div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
    <div style="display:flex;justify-content:center;gap:6px;margin-top:8px">
        <?php for ($di = 0; $di < count($heroSlides); $di++): ?>
        <span class="slide-dot" data-idx="<?= $di ?>" style="width:10px;height:10px;border-radius:50%;background:<?= $di == 0 ? '#e74c3c' : 'rgba(0,0,0,0.2)' ?>;cursor:pointer;transition:all 0.3s"></span>
        <?php endfor; ?>
    </div>
</div>
<!-- Middle column (static) -->
<div class="col-lg-3 col-md-6 mb-2 mb-lg-0 d-flex"><div class="hero-side">
    <?php for ($hi = 0; $hi < 2; $hi++): $ha = $heroArts[$hi]; ?>
    <div class="hero-side-item lg-hero<?= $hi == 0 ? ' mb-2' : '' ?>" data-pin-pos="hero_<?= $hi ?>">
        <?php if ($ha): ?>
        <img src="<?= htmlspecialchars($ha['image'] ?? '') ?>" alt="<?= htmlspecialchars($ha['title'] ?? '') ?>">
        <div class="hero-overlay">
            <div class="hero-category"><?= htmlspecialchars($ha['section'] ?? '') ?></div>
            <h2><a href="article.php?id=<?= $ha['id'] ?? 0 ?>" style="color:inherit;"><?= htmlspecialchars($ha['title'] ?? '') ?></a></h2>
        </div>
        <?php if ($isAdmin): ?>
        <div style="position:absolute;top:4px;left:4px;background:#e74c3c;color:#fff;border-radius:4px;padding:1px 6px;font-size:10px;z-index:20;font-weight:700">#<?= $ha['id'] ?? '?' ?></div>
        <div onclick="editHeroPin(this.parentElement, 'hero_<?= $hi ?>')" style="position:absolute;top:4px;right:4px;background:rgba(0,0,0,0.7);color:#fff;border-radius:4px;padding:2px 8px;font-size:11px;cursor:pointer;opacity:0;z-index:20;transition:opacity 0.3s" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0'" class="hero-edit-btn"><i class="fas fa-pen"></i> تغيير</div>
        <?php endif; ?>
        <?php else: ?>
        <div style="width:100%;height:100%;min-height:180px;background:#eee;display:flex;align-items:center;justify-content:center;color:#ccc;font-weight:400">لا يوجد مقال</div>
        <?php endif; ?>
    </div>
    <?php endfor; ?>
</div></div>
<!-- Right column (static) -->
<div class="col-lg-3 col-md-6 d-flex"><div class="hero-side">
    <?php for ($hi = 2; $hi < 4; $hi++): $ha = $heroArts[$hi]; ?>
    <div class="hero-side-item lg-hero<?= $hi == 2 ? ' mb-2' : '' ?>" data-pin-pos="hero_<?= $hi ?>">
        <?php if ($ha): ?>
        <img src="<?= htmlspecialchars($ha['image'] ?? '') ?>" alt="<?= htmlspecialchars($ha['title'] ?? '') ?>">
        <div class="hero-overlay">
            <div class="hero-category"><?= htmlspecialchars($ha['section'] ?? '') ?></div>
            <h2><a href="article.php?id=<?= $ha['id'] ?? 0 ?>" style="color:inherit;"><?= htmlspecialchars($ha['title'] ?? '') ?></a></h2>
        </div>
        <?php if ($isAdmin): ?>
        <div style="position:absolute;top:4px;left:4px;background:#e74c3c;color:#fff;border-radius:4px;padding:1px 6px;font-size:10px;z-index:20;font-weight:700">#<?= $ha['id'] ?? '?' ?></div>
        <div onclick="editHeroPin(this.parentElement, 'hero_<?= $hi ?>')" style="position:absolute;top:4px;right:4px;background:rgba(0,0,0,0.7);color:#fff;border-radius:4px;padding:2px 8px;font-size:11px;cursor:pointer;opacity:0;z-index:20;transition:opacity 0.3s" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0'" class="hero-edit-btn"><i class="fas fa-pen"></i> تغيير</div>
        <?php endif; ?>
        <?php else: ?>
        <div style="width:100%;height:100%;min-height:180px;background:#eee;display:flex;align-items:center;justify-content:center;color:#ccc;font-weight:400">لا يوجد مقال</div>
        <?php endif; ?>
    </div>
    <?php endfor; ?>
</div></div>
        </div>
    </div>
</section>

<!-- ===== أخبار المنوفية ===== -->
<section class="py-3" id="section-akhbar">
    <div class="container">
        <div class="section-title-wrap">
            <h2 class="section-title"><a href="section.php?slug=<?= 'akhbar' ?>">أخبار المنوفية</a><?php if ($isAdmin): ?><a href="admin/quick-add.php?section=<?= urlencode('أخبار المنوفية') ?>" class="btn-add-article" title="إضافة مقال"><i class="fas fa-plus"></i></a><?php endif; ?></h2>
            <a href="section.php?slug=<?= 'akhbar' ?>" class="section-more">المزيد <i class="fas fa-arrow-left me-1"></i></a>
        </div>
        <div class="row">
            <div class="col-lg-8">
                <div class="row">
                    <?php $a0 = $akhbarArts[0] ?? null; if ($a0): ?>
                    <div class="col-md-4">
                        <div class="news-card">
                            <div class="card-img">
                                <span class="card-badge"><?= htmlspecialchars($a0['tags'][0] ?? '') ?></span>
                                <img src="<?= htmlspecialchars($a0['image'] ?? '') ?>" alt="<?= htmlspecialchars($a0['title'] ?? '') ?>">
                            </div>
                            <div class="card-body">
                                <h3><a href="article.php?id=<?= $a0['id'] ?>"><?= htmlspecialchars($a0['title']) ?></a></h3>
                                <p><?= htmlspecialchars(mb_substr($a0['paragraphs'][0] ?? '', 0, 100)) ?>...</p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php $a1 = $akhbarArts[1] ?? null; if ($a1): ?>
                    <div class="col-md-4">
                        <div class="news-card">
                            <div class="card-img">
                                <span class="card-badge"><?= htmlspecialchars($a1['tags'][0] ?? '') ?></span>
                                <img src="<?= htmlspecialchars($a1['image']) ?>" alt="<?= htmlspecialchars($a1['title']) ?>">
                            </div>
                            <div class="card-body">
                                <h3><a href="article.php?id=<?= $a1['id'] ?>"><?= htmlspecialchars($a1['title']) ?></a></h3>
                                <p><?= htmlspecialchars(mb_substr($a1['paragraphs'][0] ?? '', 0, 100)) ?>...</p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php $a2 = $akhbarArts[2] ?? null; if ($a2): ?>
                    <div class="col-md-4">
                        <div class="news-card">
                            <div class="card-img">
                                <span class="card-badge"><?= htmlspecialchars($a2['tags'][0] ?? '') ?></span>
                                <img src="<?= htmlspecialchars($a2['image']) ?>" alt="<?= htmlspecialchars($a2['title']) ?>">
                            </div>
                            <div class="card-body">
                                <h3><a href="article.php?id=<?= $a2['id'] ?>"><?= htmlspecialchars($a2['title']) ?></a></h3>
                                <p><?= htmlspecialchars(mb_substr($a2['paragraphs'][0] ?? '', 0, 100)) ?>...</p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="row mt-2">
                    <?php $a3 = $akhbarArts[3] ?? null; if ($a3): ?>
                    <div class="col-md-6">
                        <div class="list-news-item">
                            <img src="<?= htmlspecialchars($a3['image']) ?>" alt="<?= htmlspecialchars($a3['title']) ?>">
                            <div>
                                <h4><a href="article.php?id=<?= $a3['id'] ?>"><?= htmlspecialchars($a3['title']) ?></a></h4>
                                <div class="list-date"><?= htmlspecialchars($a3['date']) ?></div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php $a4 = $akhbarArts[4] ?? null; if ($a4): ?>
                    <div class="col-md-6">
                        <div class="list-news-item">
                            <img src="<?= htmlspecialchars($a4['image']) ?>" alt="<?= htmlspecialchars($a4['title']) ?>">
                            <div>
                                <h4><a href="article.php?id=<?= $a4['id'] ?>"><?= htmlspecialchars($a4['title']) ?></a></h4>
                                <div class="list-date"><?= htmlspecialchars($a4['date']) ?></div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="sidebar-box">
                    <div class="sidebar-header">الأكثر قراءة</div>
                    <div class="sidebar-body">
                        <?php $num = 1; foreach ($mostReadArticles as $mra): ?>
                        <div class="urgent-item">
                            <span class="urgent-num"><?= $num++ ?></span>
                            <h5><a href="article.php?id=<?= $mra['id'] ?>"><?= htmlspecialchars($mra['title']) ?></a></h5>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===== منشآت الجامعة ===== -->
<section class="py-3" id="section-monshat" style="background:var(--bg-section);">
    <div class="container">
        <div class="section-title-wrap">
            <h2 class="section-title"><a href="section.php?slug=<?= 'monshat' ?>">منشآت الجامعة</a><?php if ($isAdmin): ?><a href="admin/quick-add.php?section=<?= urlencode('منشآت الجامعة') ?>" class="btn-add-article" title="إضافة مقال"><i class="fas fa-plus"></i></a><?php endif; ?></h2>
            <a href="section.php?slug=<?= 'monshat' ?>" class="section-more">المزيد <i class="fas fa-arrow-left me-1"></i></a>
        </div>
        <div class="row">
            <?php for ($i = 0; $i < 4; $i++): $a = $monshatArts[$i] ?? null; if (!$a) continue; ?>
            <div class="col-lg-3 col-md-6">
                <div class="news-card">
                    <div class="card-img">
                        <span class="card-badge"><?php $t0 = is_array($a['tags']) ? ($a['tags'][0] ?? '') : (is_string($a['tags']) ? $a['tags'] : ''); echo htmlspecialchars($t0); ?></span>
                        <img src="<?= htmlspecialchars($a['image']) ?>" alt="<?= htmlspecialchars($a['title']) ?>">
                    </div>
                    <div class="card-body">
                        <h3><a href="article.php?id=<?= $a['id'] ?>"><?= htmlspecialchars($a['title']) ?></a></h3>
                    </div>
                </div>
            </div>
            <?php endfor; ?>
        </div>
    </div>
</section>

<section class="pb-5">
    <div class="container">
        <div class="section-title">
            <div class="title-icon"><i class="fas fa-trophy"></i></div>
            <h2>تكريم ومسابقات</h2>
            <a href="section.php?slug=takreem">المزيد <i class="fas fa-arrow-left"></i></a>
        </div>
        <div class="row">
            <?php for ($i = 0; $i < 4; $i++): $a = $takreemArts[$i] ?? null; if (!$a) continue; ?>
            <div class="col-lg-3 col-md-6">
                <div class="news-card">
                    <div class="card-img">
                        <span class="card-badge"><?php $t0 = is_array($a['tags']) ? ($a['tags'][0] ?? '') : (is_string($a['tags']) ? $a['tags'] : ''); echo htmlspecialchars($t0); ?></span>
                        <img src="<?= htmlspecialchars($a['image']) ?>" alt="<?= htmlspecialchars($a['title']) ?>">
                    </div>
                    <div class="card-body">
                        <h3><a href="article.php?id=<?= $a['id'] ?>"><?= htmlspecialchars($a['title']) ?></a></h3>
                    </div>
                </div>
            </div>
            <?php endfor; ?>
        </div>
        <div class="row mt-2">
            <?php for ($i = 4; $i < 6; $i++): $a = $takreemArts[$i] ?? null; if (!$a) continue; ?>
            <div class="col-md-6">
                <div class="list-news-item">
                    <img src="<?= htmlspecialchars($a['image']) ?>" alt="<?= htmlspecialchars($a['title']) ?>">
                    <div>
                        <h4><a href="article.php?id=<?= $a['id'] ?>"><?= htmlspecialchars($a['title']) ?></a></h4>
                    </div>
                </div>
            </div>
            <?php endfor; ?>
        </div>
    </div>
</section>



<!-- ===== الفن والمسابقات ===== -->
<section class="py-4" id="section-fann">
    <div class="container">
        <div class="section-title-wrap">
            <h2 class="section-title"><a href="section.php?slug=<?= 'fann' ?>">الفن والمسابقات</a><?php if ($isAdmin): ?><a href="admin/quick-add.php?section=<?= urlencode('الفن والمسابقات') ?>" class="btn-add-article" title="إضافة مقال"><i class="fas fa-plus"></i></a><?php endif; ?></h2>
            <a href="section.php?slug=<?= 'fann' ?>" class="section-more">المزيد <i class="fas fa-arrow-left me-1"></i></a>
        </div>
        <div class="row">
            <?php for ($col = 0; $col < 3; $col++): 
                $cardIdx = $col * 2;
                $listIdx = $col * 2 + 1;
                $cardArt = $fannArts[$cardIdx] ?? null;
                $listArt = $fannArts[$listIdx] ?? null;
            ?>
            <div class="col-md-4">
                <?php if ($cardArt): ?>
                <div class="news-card">
                    <div class="card-img">
                        <span class="card-badge"><?= htmlspecialchars($cardArt['tags'][0] ?? '') ?></span>
                        <img src="<?= htmlspecialchars($cardArt['image']) ?>" alt="<?= htmlspecialchars($cardArt['title']) ?>">
                    </div>
                    <div class="card-body">
                        <h3><a href="article.php?id=<?= $cardArt['id'] ?>"><?= htmlspecialchars($cardArt['title']) ?></a></h3>
                    </div>
                </div>
                <?php endif; ?>
                <?php if ($listArt): ?>
                <div class="list-news-item">
                    <img src="<?= htmlspecialchars($listArt['image']) ?>" alt="<?= htmlspecialchars($listArt['title']) ?>">
                    <div>
                        <h4><a href="article.php?id=<?= $listArt['id'] ?>"><?= htmlspecialchars($listArt['title']) ?></a></h4>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endfor; ?>
        </div>
    </div>
</section>

<?php if ($isAdmin): ?>
<style>.news-card,.list-news-item{position:relative}.news-card .art-id,.list-news-item .art-id{position:absolute;top:4px;left:4px;background:#e74c3c;color:#fff;border-radius:4px;padding:1px 6px;font-size:10px;z-index:20;font-weight:700}</style>
<script>
document.querySelectorAll('.news-card a[href*="id="],.list-news-item a[href*="id="]').forEach(function(a){
    var m = a.href.match(/id=(\d+)/); if(m){
        var d=document.createElement('span'); d.className='art-id'; d.textContent='#'+m[1];
        a.closest('.news-card,.list-news-item').appendChild(d);
    }
});
</script>
<?php endif; ?>
<script>
(function(){
    var slides = document.querySelectorAll('#heroSlideshow .hero-main');
    var dots = document.querySelectorAll('#heroSlideshow .slide-dot');
    var currentIdx = 0;
    var slideInterval = setInterval(nextSlide, 5000);
    function nextSlide() {
        var next = (currentIdx + 1) % slides.length;
        slides[currentIdx].style.display = 'none';
        slides[next].style.display = 'block';
        if (dots[currentIdx]) dots[currentIdx].style.background = 'rgba(0,0,0,0.2)';
        if (dots[next]) dots[next].style.background = '#e74c3c';
        currentIdx = next;
    }
    dots.forEach(function(d, i){
        d.addEventListener('click', function(){
            clearInterval(slideInterval);
            slides[currentIdx].style.display = 'none';
            if (dots[currentIdx]) dots[currentIdx].style.background = 'rgba(0,0,0,0.2)';
            slides[i].style.display = 'block';
            dots[i].style.background = '#e74c3c';
            currentIdx = i;
            slideInterval = setInterval(nextSlide, 5000);
        });
    });

})();

function editHeroPin(container, pos) {
    var id = prompt('أدخل رقم المقال للموضع ' + pos + ':');
    if (id === null || id === '' || isNaN(id) || id < 1) return;
    var btn = container.querySelector('.hero-edit-btn');
    if (btn) btn.textContent = '...جاري';
    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'admin/ajax-save-pin.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function() {
        if (xhr.status === 200) { location.reload(); }
        else { alert('خطأ: ' + xhr.status + ' - ' + xhr.responseText); if (btn) btn.textContent = ' تغيير'; }
    };
    xhr.onerror = function() { alert('فشل الاتصال'); if (btn) btn.textContent = ' تغيير'; };
    xhr.send('position=' + encodeURIComponent(pos) + '&article_id=' + encodeURIComponent(id));
}
</script>
<?php render_footer(); ?>
