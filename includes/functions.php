<?php
require_once __DIR__ . '/config.php';

function render_header($title = '') {
    $site_name = get_setting('site_name', SITE_NAME);
    if ($title) $full_title = $title . ' - ' . $site_name;
    else $full_title = $site_name . ' | البوابة الإخبارية الرسمية';
    ?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<title><?= htmlspecialchars($full_title) ?></title>
<link rel="icon" type="image/png" href="<?= htmlspecialchars(get_setting('favicon_url')) ?>">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&family=Tajawal:wght@400;500;700;800;900&display=swap" rel="stylesheet">
<style>
:root{--fonts:'Tajawal','Cairo',sans-serif;--red:#157039;--red-dark:#0f5a2d;--dark:#091e3a;--gray-light:#818b9d;--bg-gray:#f2f2f2;--bg-section:#f6f5f6;--white:#fff}*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:var(--fonts);font-size:16px;text-align:right;background:var(--white);font-weight:700;color:var(--dark);overflow-x:hidden}
a,a:hover{text-decoration:none;color:var(--dark)}.container{max-width:1300px}
.top-bar{background:#fefefe;padding:0}
.top-bar .top-links{display:flex;flex-wrap:wrap;align-items:center;justify-content:flex-end;gap:5px;padding:6px 0;font-size:13px;color:#7e7e7e;font-weight:400;font-family:'Cairo',sans-serif}
.top-bar .top-links a{color:#7e7e7e;font-size:13px;padding:2px 4px}
.top-bar .top-links .lang{color:var(--red)!important;font-weight:700}
.top-bar .top-links .divider{color:#ccc}
.top-bar .top-links .social-icon{display:inline-flex;align-items:center;justify-content:center;width:22px;height:22px;background:#989898;border-radius:50%;color:#fff;font-size:12px}
.top-bar .top-links .social-icon:hover{background:var(--red)}
.logo-text h1{font-size:28px;font-weight:900;color:var(--red);margin:0;line-height:1.2}
.logo-text small{font-size:13px;color:var(--gray-light);font-weight:500}
.nav-section{position:sticky;top:0;z-index:999999;background:var(--red)}
.nav-section .navbar{background:var(--red)!important;padding:0.3rem 0}
.nav-section .navbar-nav{}
.nav-section .navbar-nav .nav-link{color:var(--white);font-size:14px;font-weight:700;padding:6px 14px;border-radius:50px;transition:background .3s}
.nav-section .navbar-nav .nav-link:hover{background:rgba(255,255,255,0.2)}
.nav-section .navbar-toggler{border:none;color:var(--white);font-size:1.3rem}
.nav-section .navbar-toggler:focus{box-shadow:none}
.search-box{display:flex;align-items:center}
.search-box input{border:1px solid rgba(255,255,255,0.3);background:rgba(255,255,255,0.15);color:var(--white);padding:5px 12px;border-radius:0;font-size:14px;width:200px}
.search-box input::placeholder{color:rgba(255,255,255,0.7)}
.search-box input:focus{outline:none;background:rgba(255,255,255,0.25)}
.search-box button{background:var(--dark);color:var(--white);border:none;padding:5px 14px;font-size:13px;font-weight:700;cursor:pointer;transition:background 0.2s}
.search-box button:hover{background:#152c4e}
@keyframes fadeUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}
@keyframes pulse{0%,100%{opacity:1}50%{opacity:0.6}}
.hero-side-item,.news-card,.sidebar-box{animation:fadeUp 0.5s ease forwards;opacity:0}
.hero-side-item:nth-child(1){animation-delay:0.1s}
.hero-side-item:nth-child(2){animation-delay:0.2s}
.news-card{transition:transform 0.3s ease,box-shadow 0.3s ease}
.news-card:hover{transform:translateY(-5px);box-shadow:0 8px 25px rgba(0,0,0,0.1)}
.hero-side-item{transition:transform 0.3s ease}
.hero-side-item:hover{transform:scale(1.02);z-index:5}
.hero-side-item img{transition:transform 0.5s ease}
.hero-side-item:hover img{transform:scale(1.08)}
.footer{background:var(--dark);color:#ccc;padding:40px 0 0}
.footer h5{color:var(--white);font-size:18px;font-weight:800;margin-bottom:15px;padding-bottom:8px;border-bottom:2px solid var(--red)}
.footer ul{list-style:none;padding:0}
.footer ul li{margin-bottom:8px}
.footer ul li a{color:#aaa;font-size:14px;font-weight:400;transition:color 0.2s}
.footer ul li a:hover{color:var(--white)}
.footer-social{display:flex;gap:8px;margin-top:15px}
.footer-social a{display:flex;align-items:center;justify-content:center;width:36px;height:36px;background:rgba(255,255,255,0.1);color:var(--white);border-radius:50%;font-size:16px;transition:background 0.2s}
.footer-social a:hover{background:var(--red)}
.footer-bottom{background:rgba(0,0,0,0.3);padding:15px 0;margin-top:30px;text-align:center;font-size:13px;font-weight:400;color:#888}
.ad-banner{background:#f0f0f0;text-align:center;padding:15px;margin:15px 0;min-height:90px;display:flex;align-items:center;justify-content:center;font-size:13px;color:#999;border:1px dashed #ddd}
.ad-banner-img{max-width:100%;height:auto;margin:15px 0;display:block}
.top-bar .ad-banner{padding:0;margin:0;min-height:auto;font-size:11px;border:none;background:transparent;display:inline}
.top-bar .ad-banner-img{margin:0;max-width:none;height:auto;max-height:50px;display:inline;vertical-align:middle}
@media(max-width:991px){
.nav-section .navbar-nav .nav-link{font-size:13px;padding:5px 10px}
.logo-text h1{font-size:22px}
.search-box input{width:120px}
}
@media(max-width:767px){
.top-bar .top-links{justify-content:center;font-size:12px}
}
@media(max-width:480px){
.top-bar{padding:3px 0}
.nav-section .navbar-nav .nav-link{font-size:13px;text-align:center}
.search-box input{width:80px;font-size:12px}
.search-box button{font-size:12px;padding:4px 8px}
.article-title{font-size:18px}
}
.breadcrumb-section{background:#f8f8f8;padding:12px 0;border-bottom:1px solid #eee}
.breadcrumb-section .breadcrumb{margin:0;font-size:14px;font-weight:400;background:none}
.breadcrumb-section .breadcrumb a{color:var(--red)}
.breadcrumb-section .breadcrumb .active{color:#666}
.article-title{font-size:28px;font-weight:900;line-height:1.4;color:var(--dark);margin-bottom:15px;margin-top:20px}
.article-meta{padding:12px 0;border-top:1px solid #eee;border-bottom:1px solid #eee;margin-bottom:20px;display:flex;flex-wrap:wrap;align-items:center;gap:12px;font-size:14px;color:var(--gray-light);font-weight:400}
.article-meta .author-name{color:var(--dark);font-weight:700}
.article-meta .share-icons{display:flex;gap:6px;margin-right:auto}
.article-meta .share-icons a{display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:50%;color:#fff;font-size:14px;transition:opacity 0.2s}
.article-meta .share-icons a:hover{opacity:0.8}
.article-meta .share-icons .share-fb{background:#1877f2}
.article-meta .share-icons .share-tw{background:#1da1f2}
.article-meta .share-icons .share-in{background:#0a66c2}
.edit-article-btn{display:inline-flex;align-items:center;gap:4px;background:#f39c12;color:#fff;padding:4px 14px;border-radius:4px;font-size:13px;font-weight:700;transition:background .2s;text-decoration:none}
.edit-article-btn:hover{background:#d68910;color:#fff;text-decoration:none}
.article-img-wrap{margin-bottom:25px}
.article-img-wrap img{width:100%;max-height:600px;object-fit:scale-down;background:#f5f5f5;border-radius:4px}
.article-img-wrap .img-caption{font-size:13px;color:var(--gray-light);font-weight:400;padding:8px 0;font-family:'Cairo',sans-serif;border-bottom:1px solid #eee}
.article-cover{margin-bottom:20px}
.article-cover img{width:100%;max-height:600px;object-fit:scale-down;background:#f5f5f5;border-radius:6px}
.article-gallery{margin:30px 0;padding:20px;background:#fafafa;border-radius:8px;border:1px solid #eee}
.article-gallery .gallery-title{font-size:18px;font-weight:800;color:var(--dark);margin-bottom:15px}
.article-gallery .gallery-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:12px}
.article-gallery .gallery-item{border-radius:6px;overflow:hidden;background:#fff;box-shadow:0 1px 4px rgba(0,0,0,0.06)}
.article-gallery .gallery-item a{display:block}
.article-gallery .gallery-item img{width:100%;height:160px;object-fit:scale-down;background:#eee;transition:transform .3s;display:block;cursor:pointer}
.article-gallery .gallery-item img:hover{transform:scale(1.05)}
.article-gallery .gallery-item .gallery-desc{font-size:12px;color:#666;font-weight:400;padding:8px 10px;text-align:center}
.article-body p{font-size:16px;font-weight:400;line-height:1.8;color:#333;margin-bottom:18px}
.article-tags{margin:25px 0;padding:15px 0;border-top:1px solid #eee;border-bottom:1px solid #eee}
.article-tags .tags-label{font-size:14px;font-weight:700;color:var(--dark);margin-left:10px}
.article-tags .tag-btn{display:inline-block;background:#f0f0f0;color:#555;font-size:13px;font-weight:500;padding:5px 14px;margin:3px 4px;border-radius:20px;transition:all 0.2s}
.article-tags .tag-btn:hover{background:var(--red);color:var(--white)}
.article-share-bottom{display:flex;align-items:center;gap:10px;padding:15px 0;margin-bottom:20px}
.article-share-bottom span{font-size:15px;font-weight:700;color:var(--dark)}
.article-share-bottom .share-icons{display:flex;gap:6px}
.article-share-bottom .share-icons a{display:inline-flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:50%;color:#fff;font-size:15px;transition:opacity 0.2s}
.article-share-bottom .share-icons a:hover{opacity:0.8}
.article-share-bottom .share-icons .share-fb{background:#1877f2}
.article-share-bottom .share-icons .share-tw{background:#1da1f2}
.article-share-bottom .share-icons .share-in{background:#0a66c2}
.section-title-wrap{display:flex;align-items:center;justify-content:space-between;margin-bottom:15px;border-bottom:2px solid #e0e0e0;padding-bottom:8px}
.section-title{font-size:20px;font-weight:800;color:var(--dark);padding-right:12px;border-right:4px solid var(--red)}
.related-card{display:flex;gap:12px;margin-bottom:15px;padding-bottom:15px;border-bottom:1px solid #eee}
.related-card:last-child{border-bottom:none;margin-bottom:0;padding-bottom:0}
.related-card img{width:110px;height:75px;object-fit:cover;flex-shrink:0;border-radius:3px}
.related-card .related-body{flex:1}
.related-card .related-body h4{font-size:14px;font-weight:700;line-height:1.4;margin:0 0 4px}
.related-card .related-body h4 a{color:var(--dark)}
.related-card .related-body h4 a:hover{color:var(--red)}
.related-card .related-body .related-date{font-size:11px;color:var(--gray-light);font-weight:400}
.sidebar-box{background:#fafafa;border:1px solid #e8e8e8;margin-bottom:20px}
.sidebar-box .sidebar-header{background:var(--dark);color:var(--white);padding:10px 15px;font-size:17px;font-weight:800}
.sidebar-box .sidebar-body{padding:12px}
.sidebar-most-item{padding:10px 0;border-bottom:1px solid #eee;display:flex;gap:12px;align-items:flex-start}
.sidebar-most-item:last-child{border-bottom:none}
.sidebar-most-item .most-num{background:var(--red);color:var(--white);width:26px;height:26px;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:800;flex-shrink:0;margin-top:2px;border-radius:2px}
.sidebar-most-item h5{font-size:13px;font-weight:700;line-height:1.4;margin:0}
.sidebar-most-item h5 a{color:var(--dark)}
.sidebar-most-item h5 a:hover{color:var(--red)}
.sidebar-hour-item{padding:8px 0;border-bottom:1px dashed #eee;display:flex;gap:8px;align-items:flex-start}
.sidebar-hour-item:last-child{border-bottom:none}
.sidebar-hour-item .hour-dot{width:6px;height:6px;background:var(--red);border-radius:50%;flex-shrink:0;margin-top:6px}
.sidebar-hour-item h5{font-size:12px;font-weight:500;line-height:1.4;margin:0;color:#555}
.sidebar-hour-item h5 a{color:#555}
.sidebar-hour-item h5 a:hover{color:var(--red)}
.sticky-sidebar{position:sticky;top:80px}
.floating-share{position:fixed;right:15px;top:50%;transform:translateY(-50%);display:flex;flex-direction:column;gap:5px;z-index:999;opacity:0;visibility:hidden;transition:opacity 0.3s}
.floating-share.show{opacity:1;visibility:visible}
.floating-share a{display:flex;align-items:center;justify-content:center;width:40px;height:40px;border-radius:50%;color:#fff;font-size:16px;transition:transform 0.2s}
.floating-share a:hover{transform:scale(1.1)}
.floating-share .fl-fb{background:#1877f2}
.floating-share .fl-tw{background:#1da1f2}
.floating-share .fl-in{background:#0a66c2}
@media(max-width:991px){
.article-title{font-size:24px}.floating-share{display:none}
}
@media(max-width:767px){
.article-title{font-size:20px}.article-meta{flex-direction:column;align-items:flex-start}.article-meta .share-icons{margin-right:0}.related-card img{width:90px;height:80px}
}
#imageLightbox{display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.85);z-index:999999;align-items:center;justify-content:center;cursor:zoom-in}
#imageLightbox.show{display:flex}
#imageLightbox .lightbox-img{max-width:90%;max-height:90%;object-fit:scale-down;border-radius:4px;box-shadow:0 10px 40px rgba(0,0,0,0.5);transition:transform 0.3s ease;transform-origin:center center;user-select:none;-webkit-user-select:none}
#imageLightbox .lightbox-img.zoomed{cursor:grab;max-width:none;max-height:none;object-fit:contain}
#imageLightbox .lightbox-img.zoomed:active{cursor:grabbing}
#imageLightbox .lightbox-close{position:absolute;top:15px;left:25px;color:#fff;font-size:40px;font-weight:300;cursor:pointer;opacity:0.7;transition:opacity .2s;z-index:10}
#imageLightbox .lightbox-close:hover{opacity:1}
.article-img-wrap img,.article-cover img,.gallery-item img{cursor:zoom-in}
.article-img-wrap,.article-cover,.gallery-item{position:relative}
.article-img-wrap::after,.article-cover::after,.gallery-item::after{content:'🔍';position:absolute;bottom:8px;right:8px;font-size:16px;opacity:0;transition:opacity 0.3s;pointer-events:none;background:rgba(0,0,0,0.5);width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff}
.article-img-wrap:hover::after,.article-cover:hover::after,.gallery-item:hover::after{opacity:1}
</style>
</head>
<body>
<?php
}

function render_topbar() {
    ?>
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
<?php
}

function render_navbar() {
    $secs = get_sections();
    ?>
<section class="nav-section" style="position:sticky;top:0;z-index:999999">
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
                <i class="fas fa-bars"></i>
            </button>
            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="index.php">الرئيسية</a></li>
                    <?php foreach ($secs as $s): ?>
                    <li class="nav-item"><a class="nav-link" href="section.php?slug=<?= htmlspecialchars($s['slug']) ?>"><?= htmlspecialchars($s['name']) ?></a></li>
                    <?php endforeach; ?>
                </ul>
                <div class="search-box me-2">
                    <button type="submit" onclick="var q=this.nextElementSibling.value;if(q.trim())window.location='search.php?q='+encodeURIComponent(q.trim())"><i class="fas fa-search"></i></button>
                    <input type="text" class="searchInput" placeholder="بحث...">
                </div>
            </div>
        </div>
    </nav>
</section>
<?php
}

function render_footer() {
    $footerSecs = get_sections();
    ?>
<footer class="footer">
    <div class="container">
        <div class="row">
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="footer-logo" style="margin-bottom:10px;">
                    <a href="<?= SITE_URL ?>"><img src="<?= htmlspecialchars(get_setting('logo_url')) ?>" alt="<?= htmlspecialchars(get_setting('site_name')) ?>" style="height:170px;width:auto;"></a>
                </div>
                <h5>عن الجامعة</h5>
                <p style="font-size:14px;font-weight:400;line-height:1.7;color:#aaa;">جامعة المنوفية إحدى الجامعات المصرية الرائدة، تأسست عام 1976، وتضم 17 كلية ومعهداً، وتسعى دائماً نحو التميز في التعليم والبحث العلمي وخدمة المجتمع.</p>
                <div class="footer-social">
                    <a href="<?= htmlspecialchars(get_setting('facebook_url')) ?>" target="_blank"><i class="fab fa-facebook-f"></i></a>
                    <a href="<?= htmlspecialchars(get_setting('twitter_url')) ?>" target="_blank"><i class="fab fa-twitter"></i></a>
                    <a href="<?= htmlspecialchars(get_setting('youtube_url')) ?>" target="_blank"><i class="fab fa-youtube"></i></a>
                    <a href="<?= htmlspecialchars(get_setting('instagram_url')) ?>" target="_blank"><i class="fab fa-instagram"></i></a>
                    <a href="<?= htmlspecialchars(get_setting('linkedin_url')) ?>" target="_blank"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>
            <div class="col-lg-2 col-md-6 mb-4">
                <h5>أقسام رئيسية</h5>
                <ul>
                    <?php foreach ($footerSecs as $s): ?>
                    <li><a href="section.php?slug=<?= htmlspecialchars($s['slug']) ?>"><?= htmlspecialchars($s['name']) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="col-lg-6 col-md-6 mb-4">
                <h5>اتصل بنا</h5>
                <ul>
                    <li><a href="<?= htmlspecialchars(get_setting('maps_url')) ?>" target="_blank"><i class="fas fa-map-marker-alt ms-2"></i><?= htmlspecialchars(get_setting('address')) ?></a></li>
                    <li><a href="tel:<?= htmlspecialchars(get_setting('phone')) ?>"><i class="fas fa-phone ms-2"></i><?= htmlspecialchars(get_setting('phone')) ?></a></li>
                    <li><a href="#"><i class="fas fa-fax ms-2"></i><?= htmlspecialchars(get_setting('fax')) ?></a></li>
                    <li><a href="mailto:<?= htmlspecialchars(get_setting('email')) ?>"><i class="fas fa-envelope ms-2"></i><?= htmlspecialchars(get_setting('email')) ?></a></li>
                    <li style="margin-top:10px;"><a href="contact.php" style="color:var(--red);font-weight:700;"><i class="fas fa-arrow-left ms-1"></i> صفحة الاتصال</a></li>
                </ul>
                <div style="margin-top:10px;">
                    <span style="color:var(--red); font-weight:800; font-size:20px;"><?= htmlspecialchars(get_setting('site_name')) ?></span>
                </div>
            </div>
        </div>
    </div>
    <div class="footer-bottom">
        <div class="container">
            <?= get_setting('footer_text') ?>
            <div style="margin-top:6px;font-size:12px;opacity:0.6">Powered by Ziad Elfeky</div>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function(){
    var now = new Date();
    var days = ['الأحد','الإثنين','الثلاثاء','الأربعاء','الخميس','الجمعة','السبت'];
    var months = ['يناير','فبراير','مارس','أبريل','مايو','يونيو','يوليو','أغسطس','سبتمبر','أكتوبر','نوفمبر','ديسمبر'];
    var el = document.getElementById('headerDate');
    if (el) el.textContent = days[now.getDay()] + ' ' + now.getDate() + ' ' + months[now.getMonth()] + ' ' + now.getFullYear();

    document.querySelectorAll('.searchInput').forEach(function(inp){
        inp.addEventListener('keypress', function(e){
            if(e.key === 'Enter'){var q=this.value.trim();if(q)window.location='search.php?q='+encodeURIComponent(q);}
        });
    });
})();

(function(){
    var lightbox = document.getElementById('imageLightbox');
    if (!lightbox) {
        lightbox = document.createElement('div');
        lightbox.id = 'imageLightbox';
        lightbox.innerHTML = '<span class="lightbox-close">&times;</span><img class="lightbox-img" id="lightboxImg" src="">';
        document.body.appendChild(lightbox);

        var img = document.getElementById('lightboxImg');
        var zoomLevel = 1;
        var isZoomed = false;
        var panX = 0, panY = 0;

        function resetZoom() {
            zoomLevel = 1; isZoomed = false; panX = 0; panY = 0;
            img.className = 'lightbox-img'; img.style.transform = '';
            lightbox.style.cursor = 'zoom-in';
        }

        lightbox.addEventListener('click', function(e){
            if (e.target === lightbox || e.target.classList.contains('lightbox-close')) {
                lightbox.classList.remove('show');
                resetZoom();
            }
        });

        img.addEventListener('dblclick', function(e){
            e.stopPropagation();
            if (!isZoomed) {
                isZoomed = true; zoomLevel = 2.5;
                var rect = img.getBoundingClientRect();
                var x = (e.clientX - rect.left) / rect.width;
                var y = (e.clientY - rect.top) / rect.height;
                panX = (0.5 - x) * (zoomLevel - 1) * 100;
                panY = (0.5 - y) * (zoomLevel - 1) * 100;
                img.className = 'lightbox-img zoomed';
                img.style.transform = 'translate(' + panX + 'px, ' + panY + 'px) scale(' + zoomLevel + ')';
                lightbox.style.cursor = 'grab'; hint.style.opacity = '0';
            } else {
                resetZoom();
            }
        });

        lightbox.addEventListener('wheel', function(e){
            if (!img.src || img.src.indexOf('data:') === 0) return;
            e.preventDefault();
            var delta = e.deltaY > 0 ? -0.2 : 0.2;
            var newZoom = Math.max(1, Math.min(5, zoomLevel + delta));
            if (newZoom === 1 && zoomLevel > 1) { resetZoom(); return; }
            zoomLevel = newZoom; isZoomed = zoomLevel > 1;
            if (isZoomed) {
                var rect = img.getBoundingClientRect();
                var x = (e.clientX - rect.left) / rect.width;
                var y = (e.clientY - rect.top) / rect.height;
                panX = (0.5 - x) * (zoomLevel - 1) * 100;
                panY = (0.5 - y) * (zoomLevel - 1) * 100;
                img.className = 'lightbox-img zoomed';
                img.style.transform = 'translate(' + panX + 'px, ' + panY + 'px) scale(' + zoomLevel + ')';
                lightbox.style.cursor = 'grab'; hint.style.opacity = '0';
            } else {
                resetZoom();
            }
        }, { passive: false });

        var isDragging = false, startX, startY, currentPanX = 0, currentPanY = 0;
        img.addEventListener('mousedown', function(e){
            if (!isZoomed) return;
            isDragging = true; startX = e.clientX - panX; startY = e.clientY - panY;
            currentPanX = panX; currentPanY = panY;
            lightbox.style.cursor = 'grabbing';
        });
        document.addEventListener('mousemove', function(e){
            if (!isDragging) return;
            panX = e.clientX - startX; panY = e.clientY - startY;
            img.style.transform = 'translate(' + panX + 'px, ' + panY + 'px) scale(' + zoomLevel + ')';
        });
        document.addEventListener('mouseup', function(){
            if (isDragging) { isDragging = false; lightbox.style.cursor = 'grab'; }
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') { lightbox.classList.remove('show'); resetZoom(); }
        });
    }
    document.querySelectorAll('.article-img-wrap img, .article-cover img, .gallery-item img, .gallery-item a, .lightbox-trigger').forEach(function(el){
        el.addEventListener('click', function(e){
            e.preventDefault();
            e.stopPropagation();
            var src = this.tagName === 'A' ? (this.querySelector('img') ? this.querySelector('img').src : this.href) : this.src;
            document.getElementById('lightboxImg').src = src;
            lightbox.classList.add('show');
        });
    });
})();


</script>
</body>
</html>
<?php
}

function render_section_cards($articles_list) {
    foreach ($articles_list as $a) {
        ?>
        <div class="col-lg-3 col-md-6 col-sm-6 mb-4">
            <div class="news-card">
                <a href="article.php?id=<?= $a['id'] ?>">
                    <div class="card-img-wrap">
                        <img src="<?= htmlspecialchars($a['image']) ?>" alt="<?= htmlspecialchars($a['title']) ?>" onerror="this.src='data:image/svg+xml,<svg xmlns=\'http://www.w3.org/2000/svg\' width=\'300\' height=\'200\'><rect fill=\'%23f0f0f0\' width=\'300\' height=\'200\'/><text x=\'150\' y=\'105\' text-anchor=\'middle\' fill=\'%23ccc\' font-size=\'14\'>صورة</text></svg>'">
                    </div>
                    <div class="card-bodyy">
                        <div class="card-date"><?= htmlspecialchars($a['date']) ?></div>
                        <h4><?= htmlspecialchars($a['title']) ?></h4>
                        <div class="card-author"><i class="fas fa-user ms-1"></i><?= htmlspecialchars($a['author'] ?: 'بوابة الجامعة') ?></div>
                    </div>
                </a>
            </div>
        </div>
        <?php
    }
}

function render_articles_json_script() {
    global $articles;
    echo '<script>var articleData = ' . json_encode($articles, JSON_UNESCAPED_UNICODE) . ';</script>';
}


?>
