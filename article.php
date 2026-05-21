<?php
session_start();
require_once 'includes/functions.php';

$isAdmin = isset($_SESSION['admin']);

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$article = get_article($id);

if (!$article) {
    header('Location: index.php');
    exit;
}

$related = [];
$sameSection = get_articles_by_section($article['section']);
shuffle($sameSection);
foreach ($sameSection as $ra) {
    if ($ra['id'] != $id) $related[] = $ra;
    if (count($related) >= 4) break;
}

$mostRead = $GLOBALS['articles'];
shuffle($mostRead);
$mostRead = array_slice($mostRead, 0, 6);

$hourlyNews = $GLOBALS['articles'];
shuffle($hourlyNews);
$hourlyNews = array_slice($hourlyNews, 0, 5);

$currentUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/article.php?id=' . $id;
$articleTitle = htmlspecialchars($article['title']);
$shareFb = 'https://www.facebook.com/sharer/sharer.php?u=' . urlencode($currentUrl);
$shareTw = 'https://twitter.com/intent/tweet?text=' . urlencode($articleTitle . ' ' . $currentUrl);
$shareLi = 'https://www.linkedin.com/sharing/share-offsite/?url=' . urlencode($currentUrl);

render_header($article['title']);
render_topbar();
render_navbar();
?>

<!-- ===== BREADCRUMB ===== -->
<section class="breadcrumb-section">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php"><i class="fas fa-home ms-1"></i>الرئيسية</a></li>
                <li class="breadcrumb-item"><a href="section.php?slug=<?= section_slug($article['section']) ?>"><?= htmlspecialchars($article['section']) ?></a></li>
                <li class="breadcrumb-item active"><?= htmlspecialchars(mb_substr($article['title'], 0, 60)) ?>...</li>
            </ol>
        </nav>
    </div>
</section>

<!-- ===== FLOATING SHARE ===== -->
<div class="floating-share" id="floatingShare">
    <a class="fl-fb" href="<?= $shareFb ?>" target="_blank" rel="noopener"><i class="fab fa-facebook-f"></i></a>
    <a class="fl-tw" href="<?= $shareTw ?>" target="_blank" rel="noopener"><i class="fab fa-twitter"></i></a>
    <a class="fl-in" href="<?= $shareLi ?>" target="_blank" rel="noopener"><i class="fab fa-linkedin-in"></i></a>
</div>

<!-- ===== MAIN CONTENT ===== -->
<section class="pb-4">
    <div class="container">
        <div class="row">
            <div class="col-lg-8">
                <?php if (!empty($article['cover_image'])): ?>
                <div class="article-cover">
                    <img src="<?= htmlspecialchars($article['cover_image'] ?? '') ?>" alt="<?= htmlspecialchars($article['title'] ?? '') ?>">
                </div>
                <?php endif; ?>

                <!-- Article Title -->
                <h1 class="article-title"><?= htmlspecialchars($article['title']) ?></h1>

                <!-- Article Meta -->
                <div class="article-meta">
                    <span><i class="far fa-calendar-alt ms-1"></i> <span><?= htmlspecialchars($article['date'] ?? '') ?></span></span>
                    <span><i class="far fa-user ms-1"></i> <span class="author-name"><?= htmlspecialchars($article['author'] ?: 'بوابة الجامعة') ?></span></span>
                    <span><i class="far fa-folder ms-1"></i> <span><?= htmlspecialchars($article['section']) ?></span></span>
                    <?php if ($isAdmin): ?>
                    <span><a href="admin/article-edit.php?id=<?= $article['id'] ?>" class="edit-article-btn"><i class="fas fa-edit"></i> تعديل</a></span>
                    <span><button onclick="confirmDelete(<?= $article['id'] ?>)" style="background:none;border:none;color:#e74c3c;font-size:13px;font-weight:700;cursor:pointer;padding:0"><i class="fas fa-trash"></i> حذف</button></span>
                    <?php endif; ?>
                    <div class="share-icons">
                        <a class="share-fb" href="<?= $shareFb ?>" target="_blank" rel="noopener"><i class="fab fa-facebook-f"></i></a>
                        <a class="share-tw" href="<?= $shareTw ?>" target="_blank" rel="noopener"><i class="fab fa-twitter"></i></a>
                        <a class="share-in" href="<?= $shareLi ?>" target="_blank" rel="noopener"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>

                <!-- Article Image -->
                <div class="article-img-wrap">
                    <img src="<?= htmlspecialchars($article['image']) ?>" alt="<?= htmlspecialchars($article['title']) ?>">
                    <div class="img-caption"><?= htmlspecialchars($article['title']) ?></div>
                </div>

                <!-- Font Size Controls -->
                <div class="fs-controls">
                    <span class="fs-label">حجم الخط:</span>
                    <button onclick="changeFontSize(-1)" class="fs-btn" title="تصغير الخط">أ-</button>
                    <button onclick="changeFontSize(1)" class="fs-btn" title="تكبير الخط">أ+</button>
                    <button onclick="resetFontSize()" class="fs-btn" title="الحجم الافتراضي">إعادة</button>
                </div>

                <!-- Article Body -->
                <div class="article-body" id="articleBody">
                    <?php foreach ($article['paragraphs'] as $p): ?>
                    <p><?= nl2br(htmlspecialchars($p)) ?></p>
                    <?php endforeach; ?>
                </div>

                <?php if (!empty($article['images'])): ?>
                <div class="article-gallery">
                    <h3 class="gallery-title"><i class="fas fa-images ms-1"></i> معرض الصور</h3>
                    <div class="gallery-grid">
                        <?php foreach ($article['images'] as $img): ?>
                        <div class="gallery-item">
                            <a href="<?= htmlspecialchars($img['url']) ?>">
                                <img src="<?= htmlspecialchars($img['url']) ?>" alt="<?= htmlspecialchars($img['desc'] ?? '') ?>" class="lightbox-trigger">
                            </a>
                            <?php if (!empty($img['desc'])): ?>
                            <div class="gallery-desc"><?= htmlspecialchars($img['desc'] ?? '') ?></div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Tags -->
                <?php $artTags = is_array($article['tags']) ? $article['tags'] : []; ?>
                <?php if (!empty($artTags)): ?>
                <div class="article-tags">
                    <span class="tags-label"><i class="fas fa-tags ms-1"></i>كلمات البحث:</span>
                    <?php foreach ($artTags as $tag): ?>
                    <a href="search.php?q=<?= urlencode($tag) ?>" class="tag-btn"><?= htmlspecialchars($tag) ?></a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- Share Bottom -->
                <div class="article-share-bottom">
                    <span><i class="fas fa-share-alt ms-1"></i> مشاركة</span>
                    <div class="share-icons">
                        <a class="share-fb" href="<?= $shareFb ?>" target="_blank" rel="noopener"><i class="fab fa-facebook-f"></i></a>
                        <a class="share-tw" href="<?= $shareTw ?>" target="_blank" rel="noopener"><i class="fab fa-twitter"></i></a>
                        <a class="share-in" href="<?= $shareLi ?>" target="_blank" rel="noopener"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>

                <!-- Related Articles -->
                <?php if ($related): ?>
                <div class="section-title-wrap">
                    <h2 class="section-title">اقرأ أيضًا</h2>
                </div>
                <?php foreach ($related as $ra): ?>
                <div class="related-card">
                    <img src="<?= htmlspecialchars($ra['image']) ?>" alt="<?= htmlspecialchars($ra['title']) ?>">
                    <div class="related-body">
                        <h4><a href="article.php?id=<?= $ra['id'] ?>"><?= htmlspecialchars($ra['title']) ?></a></h4>
                        <div class="related-date"><i class="far fa-clock ms-1"></i><?= htmlspecialchars($ra['date']) ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- ===== SIDEBAR ===== -->
            <div class="col-lg-4">
                <div class="sticky-sidebar">
                    <!-- Most Read -->
                    <div class="sidebar-box">
                        <div class="sidebar-header"><i class="fas fa-fire ms-1"></i> الأكثر قراءة</div>
                        <div class="sidebar-body">
                            <?php foreach ($mostRead as $i => $mr): ?>
                            <div class="sidebar-most-item">
                                <span class="most-num"><?= $i + 1 ?></span>
                                <h5><a href="article.php?id=<?= $mr['id'] ?>"><?= htmlspecialchars($mr['title']) ?></a></h5>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Hourly News -->
                    <div class="sidebar-box">
                        <div class="sidebar-header"><i class="far fa-clock ms-1"></i> أخبار الساعة</div>
                        <div class="sidebar-body">
                            <?php foreach ($hourlyNews as $hn): ?>
                            <div class="sidebar-hour-item">
                                <span class="hour-dot"></span>
                                <h5><a href="article.php?id=<?= $hn['id'] ?>"><?= htmlspecialchars($hn['title']) ?></a></h5>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Google News -->
                </div>
            </div>
        </div>
    </div>
</section>

<style>
.fs-controls{display:flex;align-items:center;gap:8px;margin-bottom:15px;padding:10px 15px;background:#f8f8f8;border-radius:50px;border:1px solid #eee;width:fit-content}
.fs-label{font-size:13px;color:#666;font-weight:500;margin-left:5px}
.fs-btn{background:var(--red);color:#fff;border:none;padding:6px 14px;border-radius:50px;font-size:14px;font-weight:800;cursor:pointer;transition:all .2s;font-family:var(--fonts)}
.fs-btn:hover{background:var(--red-dark);transform:scale(1.05)}
.fs-btn:last-child{background:#999}
.fs-btn:last-child:hover{background:#777}
</style>
<script>
(function(){
    var fs = document.getElementById('floatingShare');
    if (fs) {
        window.addEventListener('scroll', function(){
            if (window.scrollY > 400) fs.classList.add('show');
            else fs.classList.remove('show');
        });
    }
})();
</script>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);z-index:99999;align-items:center;justify-content:center">
    <div style="background:#fff;border-radius:12px;padding:30px;max-width:440px;width:90%;box-shadow:0 20px 60px rgba(0,0,0,0.3);text-align:center;position:relative;overflow:hidden">
        <div style="margin:15px 0 10px">
            <div style="width:60px;height:60px;border-radius:50%;background:#fde8e8;display:flex;align-items:center;justify-content:center;margin:0 auto">
                <i class="fas fa-exclamation-triangle" style="font-size:28px;color:#e74c3c"></i>
            </div>
        </div>
        <h3 style="font-size:18px;font-weight:800;color:#333;margin-bottom:6px">حذف المقال</h3>
        <p style="font-size:14px;color:#666;font-weight:400;margin-bottom:4px">هل أنت متأكد من حذف المقال</p>
        <p id="modalArticleTitle" style="font-size:14px;font-weight:800;color:#c0392b;margin-bottom:15px;max-height:40px;overflow:hidden">"<?= htmlspecialchars(addslashes($article['title'])) ?>"</p>
        <p style="font-size:12px;color:#999;font-weight:400;margin-bottom:15px;background:#fafafa;padding:8px 12px;border-radius:6px"><i class="fas fa-info-circle"></i> هذا الإجراء لا يمكن التراجع عنه</p>
        <div style="margin:15px 0;text-align:center">
            <div style="font-size:11px;color:#888;font-weight:400;margin-bottom:4px">انتظر <span id="countdownNum">10</span> ثوانٍ لتأكيد الحذف</div>
            <div style="background:#eee;border-radius:10px;height:10px;overflow:hidden;width:80%;margin:0 auto">
                <div id="countdownBar" style="height:100%;width:0%;background:#e74c3c;border-radius:10px;transition:width 1s linear"></div>
            </div>
        </div>
        <div style="display:flex;gap:10px;justify-content:center;margin-top:18px">
            <button id="confirmDeleteBtn" disabled style="background:#ccc;color:#fff;border:none;padding:10px 30px;border-radius:6px;font-size:14px;font-weight:800;cursor:not-allowed;transition:all .2s;min-width:100px">
                <i class="fas fa-trash"></i> <span id="confirmBtnText">حذف (10)</span>
            </button>
            <button onclick="closeModal()" style="background:#f0f0f0;color:#555;border:none;padding:10px 24px;border-radius:6px;font-size:14px;font-weight:700;cursor:pointer">
                <i class="fas fa-times"></i> إلغاء
            </button>
        </div>
    </div>
</div>

<script>
var deleteId = <?= $article['id'] ?>;
var countdownInterval = null;

function confirmDelete(id) {
    deleteId = id;
    document.getElementById('deleteModal').style.display = 'flex';

    var sec = 10;
    var btn = document.getElementById('confirmDeleteBtn');
    var bar = document.getElementById('countdownBar');
    var num = document.getElementById('countdownNum');
    var txt = document.getElementById('confirmBtnText');

    btn.disabled = true;
    btn.style.background = '#ccc';
    btn.style.cursor = 'not-allowed';
    bar.style.width = '0%';
    num.textContent = sec;
    txt.textContent = 'حذف (' + sec + ')';

    if (countdownInterval) clearInterval(countdownInterval);

    countdownInterval = setInterval(function(){
        sec--;
        var pct = ((10 - sec) / 10) * 100;
        bar.style.width = pct + '%';
        num.textContent = sec;
        txt.textContent = 'حذف (' + sec + ')';

        if (sec <= 0) {
            clearInterval(countdownInterval);
            countdownInterval = null;
            btn.disabled = false;
            btn.style.background = '#c0392b';
            btn.style.cursor = 'pointer';
            txt.textContent = 'تأكيد الحذف';
        }
    }, 1000);
}

document.getElementById('confirmDeleteBtn').addEventListener('click', function(){
    if (this.disabled) return;
    if (deleteId > 0) {
        var ref = document.referrer || '';
        var sameOrigin = ref.indexOf(location.protocol + '//' + location.host) === 0;
        var redirectTo = (sameOrigin && ref.indexOf('article.php') === -1) ? ref : '<?= urlencode('section.php?slug=' . section_slug($article['section'] ?? '')) ?>';
        window.location.href = 'admin/article-delete.php?id=' + deleteId + '&redirect=' + encodeURIComponent(redirectTo);
    }
});

function closeModal() {
    document.getElementById('deleteModal').style.display = 'none';
    if (countdownInterval) {
        clearInterval(countdownInterval);
        countdownInterval = null;
    }
}

// Font size controls
(function(){
    var body = document.getElementById('articleBody');
    if (!body) return;
    var size = parseInt(localStorage.getItem('articleFontSize')) || 100;
    function applySize() {
        [body, ...body.querySelectorAll('p, li, span, div')].forEach(function(el){
            el.style.fontSize = size + '%';
        });
    }
    applySize();
    window.changeFontSize = function(d) {
        size = Math.min(180, Math.max(60, size + d * 10));
        applySize();
        localStorage.setItem('articleFontSize', size);
    };
    window.resetFontSize = function() {
        size = 100;
        applySize();
        localStorage.setItem('articleFontSize', 100);
    };
})();
</script>

<?php render_footer(); ?>
