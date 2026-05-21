<?php
session_start();
require_once __DIR__ . '/../includes/config.php';

if (!isset($_SESSION['admin'])) {
    header('Location: index.php');
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$article = get_article($id);

if (!$article) {
    header('Location: articles.php');
    exit;
}

// Handle delete
$deleteError = '';
$deleteSuccess = '';
if (isset($_GET['delete']) && $_GET['delete'] === '1') {
    $articles_file = DATA_PATH . '/articles.json';
    $all_articles = [];
    if (file_exists($articles_file)) {
        $all_articles = json_decode(file_get_contents($articles_file), true);
    }
    $found = false;
    foreach ($all_articles as $i => $a) {
        if ($a['id'] == $id) {
            array_splice($all_articles, $i, 1);
            $found = true;
            break;
        }
    }
    if ($found) {
        save_articles($all_articles);
        $redirect = $_GET['redirect'] ?? '';
        if ($redirect && strpos($redirect, 'article-edit.php') === false) {
            $sep = (strpos($redirect, '?') !== false) ? '&' : '?';
            header('Location: ' . $redirect . $sep . 'deleted=1');
        } else {
            header('Location: articles.php?deleted=1');
        }
        exit;
    } else {
        $deleteError = 'لم يتم العثور على المقال';
    }
}

$sections = [
    'أخبار المنوفية', 'منشآت الجامعة', 'ندوات ومؤتمرات',
    'تكريم ومسابقات', 'الفن والمسابقات',
    'رياضة ومسابقات', 'قيادات جامعية', 'تقارير'
];
$section_file_map = [
    'أخبار المنوفية' => 'اخبار المنوفية.htm',
    'منشآت الجامعة' => 'منشات الجامعه.htm',
    'ندوات ومؤتمرات' => 'ندوات ومؤتمرات.htm',
    'تكريم ومسابقات' => 'تكريم ومسابقات.htm',
    'الفن والمسابقات' => 'صفحه الفن والمسابقات اخير.htm',
    'رياضة ومسابقات' => '',
    'قيادات جامعية' => '',
    'تقارير' => '',
];

$message = '';
$error = '';

$upload_dir = __DIR__ . '/../uploads';
if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $section = $_POST['section'] ?? '';
    $image = trim($_POST['image'] ?? '');
    $cover_image = trim($_POST['cover_image'] ?? '');
    $image_desc = trim($_POST['image_desc'] ?? '');
    $date = trim($_POST['date'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $tags_raw = trim($_POST['tags'] ?? '');
    $paragraphs_raw = trim($_POST['paragraphs'] ?? '');

    if (!$title) $error = 'عنوان المقال مطلوب';
    elseif (!in_array($section, $sections)) $error = 'القسم غير صحيح';
    else {
        $tags = array_map('trim', explode(',', $tags_raw));
        $tags = array_filter($tags, function($t) { return $t !== ''; });
        $tags = array_values($tags);

        $paragraphs = array_map('trim', explode("\n", $paragraphs_raw));
        $paragraphs = array_filter($paragraphs, function($p) { return $p !== ''; });
        $paragraphs = array_values($paragraphs);

        $gallery = [];
        $galleryUrlsRaw = trim($_POST['gallery_urls'] ?? '');
        $galleryDescsRaw = trim($_POST['gallery_descs'] ?? '');
        if ($galleryUrlsRaw) {
            $urls = explode("\n", $galleryUrlsRaw);
            $descs = $galleryDescsRaw ? explode("\n", $galleryDescsRaw) : [];
            foreach ($urls as $i => $u) {
                $u = trim($u);
                if ($u) $gallery[] = ['url' => $u, 'desc' => trim($descs[$i] ?? '')];
            }
        }

        $articles_file = DATA_PATH . '/articles.json';
        $all_articles = [];
        if (file_exists($articles_file)) {
            $all_articles = json_decode(file_get_contents($articles_file), true);
            if (json_last_error() !== JSON_ERROR_NONE) $all_articles = [];
        }

        foreach ($all_articles as $i => $a) {
            if ($a['id'] == $id) {
                $all_articles[$i]['section'] = $section;
                $all_articles[$i]['sectionFile'] = $section_file_map[$section] ?? '';
                $all_articles[$i]['title'] = $title;
                $all_articles[$i]['image'] = $image;
                $all_articles[$i]['cover_image'] = $cover_image;
                $all_articles[$i]['image_desc'] = $image_desc;
                $all_articles[$i]['date'] = $date;
                $all_articles[$i]['author'] = $author;
                $all_articles[$i]['tags'] = $tags;
                $all_articles[$i]['paragraphs'] = $paragraphs;
                $all_articles[$i]['images'] = $gallery;
                break;
            }
        }

        save_articles($all_articles);
        $message = 'تم تحديث المقال بنجاح';
    }
}

$admin = $_SESSION['admin'];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تعديل مقال - <?php echo htmlspecialchars(get_setting('site_name', 'الأهرام')); ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        *{box-sizing:border-box;margin:0;padding:0}
        body{font-family:'Tajawal','Cairo',sans-serif;background:#f0f2f5;color:#333;font-weight:400;direction:rtl}
        .topbar{background:#157039;color:#fff;padding:12px 20px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:100}
        .topbar h2{font-size:18px;font-weight:800}
        .topbar a{color:#fff;text-decoration:none;font-size:13px;font-weight:700}
        .topbar a:hover{text-decoration:underline}
        .wrap{max-width:800px;margin:20px auto;padding:0 15px}
        .card{background:#fff;border-radius:8px;box-shadow:0 1px 4px rgba(0,0,0,0.08);padding:25px;margin-bottom:20px}
        .card-header{font-size:16px;font-weight:800;color:#157039;margin-bottom:18px;padding-bottom:10px;border-bottom:2px solid #f0f0f0}
        .form-group{margin-bottom:18px}
        .form-group label{display:block;font-size:13px;font-weight:700;color:#555;margin-bottom:5px}
        .form-control{width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:6px;font-size:14px;font-family:inherit;transition:border-color .2s}
        .form-control:focus{outline:none;border-color:#157039;box-shadow:0 0 0 2px rgba(21,112,57,0.1)}
        textarea.form-control{min-height:200px;resize:vertical;line-height:1.7}
        select.form-control{cursor:pointer}
        .row{display:flex;gap:15px;flex-wrap:wrap}
        .row .col{flex:1;min-width:200px}
        .btn{padding:10px 28px;border:none;border-radius:6px;font-size:14px;font-weight:800;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:6px;transition:all .2s}
        .btn-primary{background:#157039;color:#fff}
        .btn-primary:hover{background:#0f5a2d}
        .btn-secondary{background:#6c757d;color:#fff}
        .btn-secondary:hover{background:#5a6268}
        .btn-outline{background:transparent;border:1px solid #ddd;color:#666;font-weight:600}
        .btn-outline:hover{background:#f5f5f5}
        .alert{padding:12px 16px;border-radius:6px;font-size:13px;font-weight:700;margin-bottom:18px}
        .alert-success{background:#d4edda;color:#155724;border:1px solid #c3e6cb}
        .alert-danger{background:#f8d7da;color:#721c24;border:1px solid #f5c6cb}
        .current-img{max-width:100%;max-height:120px;border-radius:6px;margin-bottom:8px;display:block}
        .field-hint{font-size:11px;color:#aaa;font-weight:400;margin-top:3px}
        .action-row{display:flex;gap:10px;align-items:center;margin-top:5px}
        .current-img{max-width:200px;max-height:120px;border-radius:4px;margin-top:8px;display:block}
        @media(max-width:600px){
            .row .col{min-width:100%}
            .topbar h2{font-size:15px}
            .wrap{padding:0 10px}
            .card{padding:15px}
        }
    </style>
</head>
<body>

<div class="topbar">
    <h2><i class="fas fa-edit"></i> تعديل المقال</h2>
    <div>
        <a href="../article.php?id=<?= $id ?>"><i class="fas fa-eye"></i> عرض المقال</a>
        <span style="margin:0 8px;opacity:0.5">|</span>
        <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> لوحة التحكم</a>
    </div>
</div>

<div class="wrap">

    <?php if ($message): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
        <div style="margin-top:8px">
            <a href="../article.php?id=<?= $id ?>" class="btn btn-primary" style="font-size:12px;padding:6px 16px"><i class="fas fa-eye"></i> عرض المقال</a>
            <a href="articles.php" class="btn btn-secondary" style="font-size:12px;padding:6px 16px"><i class="fas fa-list"></i> كل المقالات</a>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if (!$message): ?>
    <div class="card">
        <div class="card-header"><i class="fas fa-pen"></i> تعديل: <?php echo htmlspecialchars($article['title']); ?></div>
        <form method="post" id="editForm">

            <div class="row">
                <div class="col">
                    <div class="form-group">
                        <label>القسم</label>
                        <select name="section" class="form-control" required>
                            <?php foreach ($sections as $s): ?>
                            <option value="<?php echo htmlspecialchars($s); ?>" <?php echo ($article['section'] === $s) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($s); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col">
                    <div class="form-group">
                        <label>التاريخ</label>
                        <input type="text" name="date" class="form-control" value="<?php echo htmlspecialchars($article['date']); ?>">
                    </div>
                </div>
                <div class="col">
                    <div class="form-group">
                        <label>المؤلف</label>
                        <input type="text" name="author" class="form-control" value="<?php echo htmlspecialchars($article['author']); ?>" placeholder="اختياري">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label>عنوان المقال</label>
                <input type="text" name="title" class="form-control" required value="<?php echo htmlspecialchars($article['title']); ?>" style="font-size:18px;font-weight:800">
            </div>

            <div class="form-group">
                <label>رابط صورة المقال (مصغرة)</label>
                <?php if ($article['image']): ?>
                <img src="../<?php echo htmlspecialchars($article['image']); ?>" class="current-img" alt="الصورة الحالية">
                <?php endif; ?>
                <input type="text" name="image" class="form-control" placeholder="https://example.com/photo.jpg" value="<?php echo htmlspecialchars($article['image']); ?>">
                <div class="field-hint">تظهر في قوائم المقالات والبطاقات</div>
            </div>

            <div class="form-group">
                <label>رابط صورة الغلاف</label>
                <?php if (!empty($article['cover_image'])): ?>
                <img src="../<?php echo htmlspecialchars($article['cover_image']); ?>" class="current-img" alt="الغلاف الحالي">
                <?php endif; ?>
                <input type="text" name="cover_image" class="form-control" placeholder="https://example.com/cover.jpg" value="<?php echo htmlspecialchars($article['cover_image'] ?? ''); ?>">
                <div class="field-hint">صورة كبيرة في أعلى المقال (اختياري)</div>
            </div>

            <div class="form-group">
                <label>وصف صورة المقال</label>
                <input type="text" name="image_desc" class="form-control" value="<?php echo htmlspecialchars($article['image_desc'] ?? ''); ?>" placeholder="وصف مختصر للصورة (اختياري)" style="font-size:14px;font-weight:400">
            </div>

            <div class="form-group">
                <label>معرض الصور (روابط — رابط في كل سطر)</label>
                <textarea name="gallery_urls" class="form-control" style="min-height:70px" placeholder="https://example.com/photo1.jpg"><?php
                    $existingUrls = '';
                    if (!empty($article['images'])) {
                        $parts = [];
                        foreach ($article['images'] as $gImg) {
                            $parts[] = $gImg['url'];
                        }
                        $existingUrls = implode("\n", $parts);
                    }
                    echo htmlspecialchars($existingUrls);
                ?></textarea>
                <div class="field-hint">اختياري — كل سطر = صورة في المعرض</div>
            </div>
            <div class="form-group">
                <label>وصف كل صورة في المعرض (سطر لكل صورة — اختياري)</label>
                <textarea name="gallery_descs" class="form-control" style="min-height:50px" placeholder="وصف الصورة الأولى"><?php
                    $existingDescs = '';
                    if (!empty($article['images'])) {
                        $parts = [];
                        foreach ($article['images'] as $gImg) {
                            $parts[] = $gImg['desc'] ?? '';
                        }
                        $existingDescs = implode("\n", $parts);
                    }
                    echo htmlspecialchars($existingDescs);
                ?></textarea>
            </div>

            <div class="form-group">
                <label>نص المقال</label>
                <textarea name="paragraphs" class="form-control" placeholder="كل سطر = فقرة جديدة"><?php echo htmlspecialchars(implode("\n", $article['paragraphs'])); ?></textarea>
                <div class="field-hint">كل سطر يصبح فقرة منفصلة</div>
            </div>

            <div class="form-group">
                <label>الوسوم (مفصولة بفواصل)</label>
                <input type="text" name="tags" class="form-control" value="<?php $tagsVal = is_array($article['tags']) ? implode(', ', $article['tags']) : (string)$article['tags']; echo htmlspecialchars($tagsVal); ?>" placeholder="مثال: جامعة, طلاب, أنشطة">
            </div>

            <div class="action-row">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> حفظ التعديلات</button>
                <a href="../article.php?id=<?= $id ?>" class="btn btn-outline"><i class="fas fa-times"></i> إلغاء</a>
                <button type="button" onclick="confirmDelete(<?= $id ?>)" style="background:transparent;border:1px solid #e74c3c;color:#e74c3c;padding:10px 20px;border-radius:6px;font-size:14px;font-weight:800;cursor:pointer;margin-right:auto;transition:all .2s" onmouseover="this.style.background='#fde8e8'" onmouseout="this.style.background='transparent'">
                    <i class="fas fa-trash"></i> حذف المقال
                </button>
            </div>
        </form>
    </div>
    <?php endif; ?>
</div>

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
        <p id="modalArticleTitle" style="font-size:14px;font-weight:800;color:#c0392b;margin-bottom:15px;max-height:40px;overflow:hidden"></p>
        <p style="font-size:12px;color:#999;font-weight:400;margin-bottom:15px;background:#fafafa;padding:8px 12px;border-radius:6px">
            <i class="fas fa-info-circle"></i> هذا الإجراء لا يمكن التراجع عنه
        </p>

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
            <button onclick="closeModal()" style="background:#f0f0f0;color:#555;border:none;padding:10px 24px;border-radius:6px;font-size:14px;font-weight:700;cursor:pointer;transition:background .2s">
                <i class="fas fa-times"></i> إلغاء
            </button>
        </div>
    </div>
</div>

<script>
var deleteId = 0;
var countdownInterval = null;

function confirmDelete(id) {
    deleteId = id;
    document.getElementById('modalArticleTitle').textContent = '"<?php echo htmlspecialchars(addslashes($article['title'])); ?>"';
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
        window.location.href = 'article-edit.php?id=' + deleteId + '&delete=1&redirect=' + encodeURIComponent(window.location.href);
    }
});

function closeModal() {
    document.getElementById('deleteModal').style.display = 'none';
    if (countdownInterval) {
        clearInterval(countdownInterval);
        countdownInterval = null;
    }
}

document.getElementById('deleteModal').addEventListener('click', function(e){
    if (e.target === this) closeModal();
});

document.addEventListener('keydown', function(e){
    if (e.key === 'Escape') closeModal();
});
</script>

</body>
</html>
