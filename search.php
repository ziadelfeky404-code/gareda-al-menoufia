<?php
require_once 'includes/functions.php';

function normalizeArabic($s) {
    $s = preg_replace('/\s+/', ' ', $s);
    $s = str_replace(['أ','إ','آ','ٱ'], 'ا', $s);
    $s = str_replace(['ى','ئ'], 'ي', $s);
    $s = str_replace('ة', 'ه', $s);
    $s = preg_replace('/[\x{064B}-\x{065F}\x{0670}]/u', '', $s); // tashkeel
    return trim($s);
}

$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$sectionFilter = isset($_GET['section']) ? trim($_GET['section']) : '';
$authorFilter = isset($_GET['author']) ? trim($_GET['author']) : '';
$fromDate = isset($_GET['from']) ? trim($_GET['from']) : '';
$toDate = isset($_GET['to']) ? trim($_GET['to']) : '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 6;

$arabicMonths = ['يناير'=>'01','فبراير'=>'02','مارس'=>'03','أبريل'=>'04','مايو'=>'05','يونيو'=>'06','يوليو'=>'07','أغسطس'=>'08','سبتمبر'=>'09','أكتوبر'=>'10','نوفمبر'=>'11','ديسمبر'=>'12'];

function dateToKey($d) {
    global $arabicMonths;
    $d = trim($d);
    if (!$d) return '';
    $parts = explode(' ', $d);
    if (count($parts) < 3) return $d;
    $day = str_pad($parts[0], 2, '0', STR_PAD_LEFT);
    $month = $arabicMonths[$parts[1]] ?? '00';
    $year = $parts[2] ?? '0000';
    return $year . '-' . $month . '-' . $day;
}

$results = [];
$hasFilters = ($query !== '' || $sectionFilter !== '' || $authorFilter !== '' || $fromDate !== '' || $toDate !== '');

if ($hasFilters) {
    $q = $query !== '' ? normalizeArabic($query) : '';
    if ($q === '') $q = $query;
    $words = $q ? explode(' ', $q) : [];
    $fromKey = $fromDate ? dateToKey($fromDate) : '';
    $toKey = $toDate ? dateToKey($toDate) : '';
    foreach ($GLOBALS['articles'] as $a) {
        if ($sectionFilter !== '' && $a['section'] !== $sectionFilter) continue;
        if ($authorFilter !== '' && strpos(normalizeArabic($a['author'] ?? ''), normalizeArabic($authorFilter)) === false) continue;
        if ($fromKey !== '' || $toKey !== '') {
            $aDateKey = dateToKey($a['date'] ?? '');
            if ($aDateKey === '') continue;
            if ($fromKey !== '' && $aDateKey < $fromKey) continue;
            if ($toKey !== '' && $aDateKey > $toKey) continue;
        }
        if ($query === '') { $results[] = ['article' => $a, 'score' => 0]; continue; }
        $score = 0;
        $wordCount = count($words);
        $titleNorm = normalizeArabic($a['title']);
        $authorNorm = normalizeArabic($a['author'] ?? '');
        $secNorm = normalizeArabic($a['section']);
        $hitFields = 0;
        if ($q !== '') {
            $exactTitle = strpos($titleNorm, $q) !== false;
            if ($exactTitle) { $score += 100 * $wordCount; $hitFields++; }
            elseif ($words) {
                $mc = 0; foreach ($words as $w) { if (strpos($titleNorm, $w) !== false) $mc++; }
                if ($mc === $wordCount) { $score += 60; $hitFields++; }
                elseif ($mc > 0) $score += $mc * 15;
            }
            $exactAuthor = strpos($authorNorm, $q) !== false;
            if ($exactAuthor) { $score += 40 * $wordCount; $hitFields++; }
            elseif ($words) {
                foreach ($words as $w) { if (strpos($authorNorm, $w) !== false) { $score += 20; $hitFields++; break; } }
            }
            $tags = is_array($a['tags']) ? $a['tags'] : ($a['tags'] ? explode(',', $a['tags']) : []);
            foreach ($tags as $tag) {
                $tagNorm = normalizeArabic($tag);
                if (strpos($tagNorm, $q) !== false) { $score += 30 * $wordCount; $hitFields++; break; }
                foreach ($words as $w) { if (strpos($tagNorm, $w) !== false) { $score += 15; $hitFields++; break 2; } }
            }
            $exactSec = strpos($secNorm, $q) !== false;
            if ($exactSec) { $score += 20 * $wordCount; $hitFields++; }
            elseif ($words) {
                foreach ($words as $w) { if (strpos($secNorm, $w) !== false) { $score += 10; $hitFields++; break; } }
            }
            $paraExact = false; $paraAll = false; $paraAny = false;
            foreach ($a['paragraphs'] as $p) {
                $pNorm = normalizeArabic($p);
                if (strpos($pNorm, $q) !== false) { $paraExact = true; break; }
                if ($words) {
                    $mc = 0; foreach ($words as $w) { if (strpos($pNorm, $w) !== false) $mc++; }
                    if ($mc === $wordCount) { $paraAll = true; }
                    elseif ($mc > 0) { $paraAny = true; }
                }
            }
            if ($paraExact) { $score += 15 * $wordCount; $hitFields++; }
            elseif ($paraAll) { $score += 8; $hitFields++; }
            elseif ($paraAny) { $score += 3; }
            if ($hitFields >= 3) $score += 40;
            elseif ($hitFields >= 2) $score += 15;
        }
        if ($score > 0) $results[] = ['article' => $a, 'score' => $score];
    }
    if ($query !== '') usort($results, function($x, $y) { return $y['score'] - $x['score']; });
}

$totalResults = count($results);
$totalPages = max(1, ceil($totalResults / $perPage));
$offset = ($page - 1) * $perPage;
$pageResults = array_slice($results, $offset, $perPage);

$allSections = get_sections();

render_header('بحث');
render_topbar();
render_navbar();
?>
<style>
.page-header{background:linear-gradient(135deg,var(--dark),#1a3a6a);color:var(--white);padding:30px 0;margin-bottom:25px}
.page-header h2{font-size:26px;font-weight:800;margin:0}
.page-header p{font-size:14px;font-weight:400;color:rgba(255,255,255,0.7);margin:5px 0 0}
.breadcrumb-custom{font-size:13px;color:rgba(255,255,255,0.6);font-weight:400;font-family:'Cairo',sans-serif;margin-bottom:8px}
.breadcrumb-custom a{color:rgba(255,255,255,0.6)}
.breadcrumb-custom a:hover{color:var(--white)}
.search-filters{background:var(--bg-section);padding:15px;border-radius:6px;margin-bottom:20px}
.search-filters .filter-row{display:flex;flex-wrap:wrap;gap:10px;align-items:end}
.search-filters .filter-group{flex:1;min-width:140px}
.search-filters .filter-group label{display:block;font-size:12px;font-weight:700;color:#555;margin-bottom:3px;font-family:'Cairo',sans-serif}
.search-filters .filter-group input,.search-filters .filter-group select{width:100%;padding:7px 10px;border:2px solid #ddd;border-radius:4px;font-size:13px;font-family:var(--fonts);background:#fff}
.search-filters .filter-group input:focus,.search-filters .filter-group select:focus{outline:none;border-color:var(--red)}
.search-filters .filter-actions{display:flex;gap:6px;align-items:end}
.search-filters .filter-actions button,.search-filters .filter-actions a{padding:7px 18px;border-radius:4px;font-size:13px;font-weight:700;cursor:pointer;border:none;white-space:nowrap}
.search-filters .filter-actions .btn-filter{background:var(--red);color:#fff}
.search-filters .filter-actions .btn-filter:hover{background:var(--red-dark)}
.search-filters .filter-actions .btn-reset{background:#f0f0f0;color:#555;text-decoration:none;display:inline-flex;align-items:center}
.search-filters .filter-actions .btn-reset:hover{background:#ddd}
.search-again{background:#fff;border:2px solid #eee;padding:15px;border-radius:6px;margin-bottom:15px;display:flex;gap:10px;align-items:center}
.search-again input{flex:1;padding:8px 12px;border:2px solid #ddd;border-radius:4px;font-size:14px;font-family:var(--fonts)}
.search-again input:focus{outline:none;border-color:var(--red)}
.search-again button{background:var(--red);color:var(--white);border:none;padding:8px 25px;border-radius:4px;font-weight:700;font-size:14px;cursor:pointer}
.search-again button:hover{background:var(--red-dark)}
.result-count{color:var(--gray-light);font-size:14px;font-weight:400;margin-bottom:20px;padding:10px 15px;background:#fafafa;border:1px solid #eee;border-radius:4px}
.result-item{display:flex;gap:15px;padding:18px 0;border-bottom:1px solid #eee;transition:background 0.2s}
.result-item:hover{background:#fafafa}
.result-item img{width:200px;height:140px;object-fit:cover;flex-shrink:0;border-radius:4px}
.result-item .result-body{flex:1}
.result-item .result-body .result-tag{display:inline-block;background:var(--red);color:var(--white);font-size:11px;font-weight:600;padding:2px 10px;font-family:'Cairo',sans-serif;margin-bottom:5px}
.result-item .result-body h3{font-size:17px;font-weight:800;line-height:1.4;margin-bottom:5px}
.result-item .result-body h3 a{color:var(--dark)}
.result-item .result-body h3 a:hover{color:var(--red)}
.result-item .result-body p{font-size:13px;color:var(--gray-light);font-weight:400;margin-bottom:5px;line-height:1.6}
.result-item .result-body .result-date{font-size:11px;color:var(--gray-light);font-weight:400}
.no-results{text-align:center;padding:60px 20px;color:#999}
.no-results i{font-size:48px;margin-bottom:15px;color:#ddd}
.no-results h4{font-size:18px;font-weight:700;color:#666}
.pagination-wrap{display:flex;justify-content:center;gap:5px;margin:30px 0}
.pagination-wrap a{display:inline-flex;align-items:center;justify-content:center;min-width:36px;height:36px;padding:0 10px;border:1px solid #ddd;font-size:14px;font-weight:600;color:#555;border-radius:4px;transition:all 0.2s}
.pagination-wrap a:hover,.pagination-wrap a.active{background:var(--red);color:var(--white);border-color:var(--red)}
@media(max-width:767px){
.result-item{flex-direction:column}
.result-item img{width:100%;height:180px}
}
</style>

<section class="page-header">
    <div class="container">
        <div class="breadcrumb-custom">
            <a href="index.php">الرئيسية</a> / <span>بحث</span>
        </div>
        <h2><?= $query ? 'نتائج البحث عن : ' . htmlspecialchars($query) : ($hasFilters ? 'نتائج التصفية' : 'البحث') ?></h2>
        <p><?= $hasFilters ? 'عدد النتائج: ' . $totalResults : '' ?></p>
    </div>
</section>

<section class="pb-5">
    <div class="container">
        <form class="search-filters" method="get" action="search.php">
            <div class="filter-row">
                <div class="filter-group" style="flex:2">
                    <label>كلمة البحث</label>
                    <input type="text" name="q" placeholder="ابحث..." value="<?= htmlspecialchars($query) ?>">
                </div>
                <div class="filter-group">
                    <label>القسم</label>
                    <select name="section">
                        <option value="">الكل</option>
                        <?php foreach ($allSections as $s): ?>
                        <option value="<?= htmlspecialchars($s['name']) ?>" <?= $sectionFilter === $s['name'] ? 'selected' : '' ?>><?= htmlspecialchars($s['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label>المؤلف</label>
                    <input type="text" name="author" placeholder="اسم المؤلف" value="<?= htmlspecialchars($authorFilter) ?>">
                </div>
                <div class="filter-group">
                    <label>من تاريخ</label>
                    <input type="text" name="from" placeholder="مثال: 1 يناير 2026" value="<?= htmlspecialchars($fromDate) ?>">
                </div>
                <div class="filter-group">
                    <label>إلى تاريخ</label>
                    <input type="text" name="to" placeholder="مثال: 31 ديسمبر 2026" value="<?= htmlspecialchars($toDate) ?>">
                </div>
                <div class="filter-actions">
                    <button type="submit" class="btn-filter"><i class="fas fa-search"></i> بحث</button>
                    <a href="search.php" class="btn-reset"><i class="fas fa-times"></i></a>
                </div>
            </div>
        </form>

        <?php if (!$hasFilters): ?>
        <div class="no-results"><i class="fas fa-search"></i><h4>أدخل كلمة للبحث أو استخدم الفلتر</h4></div>
        <?php elseif ($totalResults === 0): ?>
        <div class="no-results"><i class="far fa-frown"></i><h4>عذراً، لا توجد نتائج تطابق بحثك</h4><p style="color:#999;font-size:14px;font-weight:400;">حاول بكلمات أخرى أو غير الفلتر</p></div>
        <?php else: ?>
        <div class="result-count">عدد النتائج: <?= $totalResults ?></div>
        <?php foreach ($pageResults as $r): $a = $r['article']; ?>
        <div class="result-item">
            <img src="<?= htmlspecialchars($a['image']) ?>" alt="<?= htmlspecialchars($a['title']) ?>" onerror="this.src='data:image/svg+xml,<svg xmlns=\'http://www.w3.org/2000/svg\' width=\'200\' height=\'140\'><rect fill=\'%23f0f0f0\' width=\'200\' height=\'140\'/><text x=\'100\' y=\'75\' text-anchor=\'middle\' fill=\'%23ccc\' font-size=\'14\'>صورة</text></svg>'">
            <div class="result-body">
                <span class="result-tag"><?= htmlspecialchars($a['section']) ?></span>
                <h3><a href="article.php?id=<?= $a['id'] ?>"><?= htmlspecialchars($a['title']) ?></a></h3>
                <p><?= htmlspecialchars(mb_substr($a['paragraphs'][0] ?? '', 0, 200)) ?>...</p>
                <span class="result-date"><i class="far fa-clock ms-1"></i><?= htmlspecialchars($a['date']) ?></span>
            </div>
        </div>
        <?php endforeach; ?>
         <?php if ($totalPages > 1):
            $qs = http_build_query(array_filter(['q'=>$query,'section'=>$sectionFilter,'author'=>$authorFilter,'from'=>$fromDate,'to'=>$toDate]));
        ?>
        <div class="pagination-wrap">
            <?php if ($page > 1): ?>
            <a href="?<?= $qs ?>&page=<?= $page - 1 ?>"><i class="fas fa-chevron-right"></i></a>
            <?php endif; ?>
            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
            <a href="?<?= $qs ?>&page=<?= $p ?>" <?= $p === $page ? 'class="active"' : '' ?>><?= $p ?></a>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?>
            <a href="?<?= $qs ?>&page=<?= $page + 1 ?>"><i class="fas fa-chevron-left"></i></a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</section>

<?php render_articles_json_script(); ?>
<?php render_footer(); ?>
