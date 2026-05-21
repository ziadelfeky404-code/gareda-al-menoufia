<?php
session_start();
require_once __DIR__ . '/../includes/config.php';

if (!isset($_SESSION['admin'])) {
    header('Location: index.php');
    exit;
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
];

$presetSection = isset($_GET['section']) && in_array($_GET['section'], $sections) ? $_GET['section'] : '';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $section = $_POST['section'] ?? '';
    $image = trim($_POST['image'] ?? '');
    $cover_image = trim($_POST['cover_image'] ?? '');
    $date = trim($_POST['date'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $image_desc = trim($_POST['image_desc'] ?? '');
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
                if ($u) {
                    $gallery[] = ['url' => $u, 'desc' => trim($descs[$i] ?? '')];
                }
            }
        }

        $articles_file = DATA_PATH . '/articles.json';
        $articles = [];
        if (file_exists($articles_file)) {
            $articles = json_decode(file_get_contents($articles_file), true);
            if (json_last_error() !== JSON_ERROR_NONE) $articles = [];
        }

        $GLOBALS['articles'] = $articles;
        $new_id = get_next_id();

        $article = [
            'id' => $new_id,
            'section' => $section,
            'sectionFile' => $section_file_map[$section] ?? '',
            'title' => $title,
            'image' => $image,
            'cover_image' => $cover_image,
            'image_desc' => $image_desc,
            'date' => $date,
            'author' => $author,
            'tags' => $tags,
            'paragraphs' => $paragraphs,
            'images' => $gallery,
        ];

        $articles[] = $article;
        $GLOBALS['articles'] = $articles;
        save_articles($articles);

        $message = 'تم إضافة المقال بنجاح';

        $redirect = trim($_POST['redirect'] ?? '');
        if ($redirect) {
            header('Location: ' . $redirect);
            exit;
        }
    }
}

$admin = $_SESSION['admin'];
$today = (new DateTime('now', new DateTimeZone('Africa/Cairo')))->format('Y-m-d');
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إضافة سريعة - <?php echo htmlspecialchars(get_setting('site_name', 'الأهرام')); ?></title>
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
        .img-upload-area{border:2px dashed #ddd;border-radius:6px;padding:20px;text-align:center;cursor:pointer;transition:all .2s;background:#fafafa}
        .img-upload-area:hover{border-color:#157039;background:#f0faf4}
        .img-upload-area i{font-size:32px;color:#157039;display:block;margin-bottom:6px}
        .img-upload-area p{font-size:12px;color:#888;font-weight:400}
        .img-preview{max-width:100%;max-height:180px;border-radius:6px;margin-top:10px;display:none}
        .img-url-row{display:flex;gap:10px;align-items:center;margin-top:10px}
        .img-url-row .form-control{flex:1}
        .img-url-row .or-divider{font-size:12px;color:#aaa;font-weight:700;white-space:nowrap}
        .field-hint{font-size:11px;color:#aaa;font-weight:400;margin-top:3px}
        .action-row{display:flex;gap:10px;align-items:center;margin-top:5px}
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
    <h2><i class="fas fa-plus-circle"></i> إضافة مقال سريع</h2>
    <div>
        <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> لوحة التحكم</a>
        <span style="margin:0 8px;opacity:0.5">|</span>
        <a href="../index.php"><i class="fas fa-home"></i> العودة للموقع</a>
    </div>
</div>

<div class="wrap">

    <?php if ($message): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
        <div style="margin-top:8px">
            <a href="quick-add.php?section=<?php echo urlencode($section); ?>" class="btn btn-primary" style="font-size:12px;padding:6px 16px"><i class="fas fa-plus"></i> إضافة آخر</a>
            <a href="../section.php?slug=<?php echo section_slug($section); ?>" class="btn btn-secondary" style="font-size:12px;padding:6px 16px"><i class="fas fa-eye"></i> عرض القسم</a>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if (!$message): ?>
    <div class="card">
        <div class="card-header"><i class="fas fa-pen"></i> بيانات المقال</div>
        <form method="post" id="quickForm">
            <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($_POST['redirect'] ?? $_SERVER['HTTP_REFERER'] ?? '../index.php'); ?>">

            <div class="row">
                <div class="col">
                    <div class="form-group">
                        <label>القسم</label>
                        <select name="section" class="form-control" required>
                            <option value="">-- اختر القسم --</option>
                            <?php foreach ($sections as $s): ?>
                            <option value="<?php echo htmlspecialchars($s); ?>" <?php echo ($presetSection === $s || (isset($_POST['section']) && $_POST['section'] === $s)) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($s); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col">
                    <div class="form-group">
                        <label>التاريخ</label>
                        <input type="text" name="date" class="form-control" value="<?php echo htmlspecialchars($_POST['date'] ?? $today); ?>" placeholder="<?php echo $today; ?>">
                    </div>
                </div>
                <div class="col">
                    <div class="form-group">
                        <label>المؤلف</label>
                        <input type="text" name="author" class="form-control" value="<?php echo htmlspecialchars($_POST['author'] ?? ''); ?>" placeholder="اختياري">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label>عنوان المقال</label>
                <input type="text" name="title" class="form-control" required value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" placeholder="اكتب عنوان المقال هنا" style="font-size:18px;font-weight:800">
            </div>

            <div class="form-group">
                <label>رابط صورة المقال (مصغرة)</label>
                <input type="text" name="image" class="form-control" placeholder="https://example.com/photo.jpg" value="<?php echo htmlspecialchars($_POST['image'] ?? ''); ?>">
                <div class="field-hint">تظهر في قوائم المقالات والبطاقات</div>
            </div>

            <div class="form-group">
                <label>رابط صورة الغلاف</label>
                <input type="text" name="cover_image" class="form-control" placeholder="https://example.com/cover.jpg" value="<?php echo htmlspecialchars($_POST['cover_image'] ?? ''); ?>">
                <div class="field-hint">صورة كبيرة في أعلى المقال (اختياري)</div>
            </div>

            <div class="form-group">
                <label>وصف صورة المقال</label>
                <input type="text" name="image_desc" class="form-control" value="<?php echo htmlspecialchars($_POST['image_desc'] ?? ''); ?>" placeholder="وصف مختصر للصورة (اختياري)" style="font-size:14px;font-weight:400">
            </div>

            <div class="form-group">
                <label>معرض الصور (روابط — رابط واحد في كل سطر)</label>
                <textarea name="gallery_urls" class="form-control" style="min-height:70px" placeholder="https://example.com/photo1.jpg&#10;https://example.com/photo2.jpg"><?php echo htmlspecialchars($_POST['gallery_urls'] ?? ''); ?></textarea>
                <div class="field-hint">اختياري — كل سطر = صورة في المعرض</div>
            </div>
            <div class="form-group">
                <label>وصف كل صورة في المعرض (سطر لكل صورة)</label>
                <textarea name="gallery_descs" class="form-control" style="min-height:50px" placeholder="وصف الصورة الأولى&#10;وصف الصورة الثانية"><?php echo htmlspecialchars($_POST['gallery_descs'] ?? ''); ?></textarea>
            </div>

            <div class="form-group">
                <label>نص المقال</label>
                <textarea name="paragraphs" class="form-control" placeholder="الصق نص المقال هنا...
كل سطر = فقرة جديدة

يمكنك نسخ النص من Word أو أي مصدر ولصقه مباشرة."><?php echo htmlspecialchars($_POST['paragraphs'] ?? ''); ?></textarea>
                <div class="field-hint">كل سطر يصبح فقرة منفصلة. يمكنك الضغط على Enter لفصل الفقرات.</div>
            </div>

            <div class="form-group">
                <label>الوسوم (مفصولة بفواصل)</label>
                <input type="text" name="tags" class="form-control" value="<?php echo htmlspecialchars($_POST['tags'] ?? ''); ?>" placeholder="مثال: جامعة, طلاب, أنشطة, مؤتمر">
                <div class="field-hint">اختياري — تساعد في تصنيف المقال</div>
            </div>

            <div class="action-row">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> نشر المقال</button>
                <a href="../index.php" class="btn btn-outline"><i class="fas fa-times"></i> إلغاء</a>
            </div>
        </form>
    </div>
    <?php endif; ?>
</div>



</body>
</html>
