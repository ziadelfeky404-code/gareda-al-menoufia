<?php
session_start();
require_once __DIR__ . '/../includes/config.php';

if (!isset($_SESSION['admin'])) {
    header('Location: index.php');
    exit;
}

$sections = ['أخبار المنوفية', 'منشآت الجامعة', 'ندوات ومؤتمرات', 'تكريم ومسابقات', 'الفن والمسابقات'];
$section_file_map = [
    'أخبار المنوفية' => 'اخبار المنوفية.htm',
    'منشآت الجامعة' => 'منشات الجامعه.htm',
    'ندوات ومؤتمرات' => 'ندوات ومؤتمرات.htm',
    'تكريم ومسابقات' => 'تكريم ومسابقات.htm',
    'الفن والمسابقات' => 'صفحه الفن والمسابقات اخير.htm',
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
        if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
            $url = v_upload_image($_FILES['image_file']['tmp_name'], $_FILES['image_file']['name']);
            if ($url) $image = $url;
        }
        if (isset($_FILES['cover_image_file']) && $_FILES['cover_image_file']['error'] === UPLOAD_ERR_OK) {
            $url = v_upload_image($_FILES['cover_image_file']['tmp_name'], $_FILES['cover_image_file']['name']);
            if ($url) $cover_image = $url;
        }

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
        if (isset($_FILES['gallery_files'])) {
            foreach ($_FILES['gallery_files']['error'] as $i => $err) {
                if ($err === UPLOAD_ERR_OK) {
                    $url = v_upload_image($_FILES['gallery_files']['tmp_name'][$i], $_FILES['gallery_files']['name'][$i]);
                    if ($url) $gallery[] = ['url' => $url, 'desc' => ''];
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
    }
}

$admin = $_SESSION['admin'];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إضافة مقال - <?php echo htmlspecialchars(get_setting('site_name', 'الأهرام')); ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .form-group{margin-bottom:18px}
        .form-group label{display:block;font-size:13px;font-weight:700;color:#555;margin-bottom:5px}
        .form-control{width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:6px;font-size:14px;font-family:inherit}
        .form-control:focus{outline:none;border-color:#157039}
        textarea.form-control{min-height:150px;resize:vertical;line-height:1.7}
        .img-upload-area{border:2px dashed #ddd;border-radius:6px;padding:20px;text-align:center;cursor:pointer;transition:all .2s;background:#fafafa;margin-top:4px}
        .img-upload-area:hover{border-color:#157039;background:#f0faf4}
        .img-upload-area i{font-size:32px;color:#157039;display:block;margin-bottom:6px}
        .img-upload-area p{font-size:12px;color:#888;font-weight:400}
        .img-preview{max-width:100%;max-height:180px;border-radius:6px;margin-top:10px;display:none}
        .img-url-row{display:flex;gap:10px;align-items:center;margin-top:10px}
        .img-url-row .form-control{flex:1}
        .img-url-row .or-divider{font-size:12px;color:#aaa;font-weight:700;white-space:nowrap}
        .field-hint{font-size:11px;color:#aaa;font-weight:400;margin-top:3px}
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="sidebar">
            <div class="sidebar-header">
                <img src="<?php echo htmlspecialchars(get_setting('logo_url', '')); ?>" alt="شعار">
                <h5>لوحة التحكم</h5>
            </div>
            <a href="dashboard.php" class="nav-item"><i class="fas fa-tachometer-alt"></i> الرئيسية</a>
            <a href="articles.php" class="nav-item"><i class="fas fa-newspaper"></i> المقالات</a>
            <a href="article-add.php" class="nav-item active"><i class="fas fa-plus-circle"></i> إضافة مقال</a>
            
            <a href="ads.php" class="nav-item"><i class="fas fa-ad"></i> الإعلانات</a>
            <a href="sections.php" class="nav-item"><i class="fas fa-layer-group"></i> الأقسام</a>
            <a href="settings.php" class="nav-item"><i class="fas fa-cogs"></i> الإعدادات</a>
            <a href="logout.php" class="nav-item"><i class="fas fa-sign-out-alt"></i> تسجيل الخروج</a>
        </div>
        <div class="main-content">
            <div class="top-bar-admin">
                <div class="page-title">إضافة مقال جديد</div>
                <div class="user-info">مرحباً، <?php echo htmlspecialchars($admin['display_name'] ?? $admin['username']); ?></div>
            </div>

            <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">بيانات المقال</div>
                <div class="card-body">
                    <form method="post" enctype="multipart/form-data">
                        <div class="form-group">
                            <label>عنوان المقال</label>
                            <input type="text" name="title" class="form-control" required value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>">
                        </div>
                        <div class="row" style="display:flex;gap:15px;flex-wrap:wrap">
                            <div class="col" style="flex:1;min-width:200px">
                                <div class="form-group">
                                    <label>القسم</label>
                                    <select name="section" class="form-control" required>
                                        <option value="">اختر القسم</option>
                                        <?php foreach ($sections as $s): ?>
                                        <option value="<?php echo htmlspecialchars($s); ?>" <?php echo isset($_POST['section']) && $_POST['section'] === $s ? 'selected' : ''; ?>><?php echo htmlspecialchars($s); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col" style="flex:1;min-width:200px">
                                <div class="form-group">
                                    <label>التاريخ</label>
                                    <input type="text" name="date" class="form-control" value="<?php echo htmlspecialchars($_POST['date'] ?? ''); ?>" placeholder="مثال: 19 مايو 2026">
                                </div>
                            </div>
                            <div class="col" style="flex:1;min-width:200px">
                                <div class="form-group">
                                    <label>المؤلف</label>
                                    <input type="text" name="author" class="form-control" value="<?php echo htmlspecialchars($_POST['author'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>صورة المقال (مصغرة)</label>
                            <div class="img-upload-area" onclick="document.getElementById('imageFile').click()">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <p>اضغط لرفع صورة</p>
                                <input type="file" name="image_file" id="imageFile" accept="image/*" style="display:none">
                                <div id="imgFileName" style="font-size:12px;color:#157039;font-weight:700;margin-top:5px"></div>
                            </div>
                            <img id="imgPreview" class="img-preview">
                            <div class="img-url-row">
                                <span class="or-divider">— أو أدخل رابط الصورة —</span>
                                <input type="text" name="image" class="form-control" placeholder="https://example.com/photo.jpg" value="<?php echo htmlspecialchars($_POST['image'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label>صورة الغلاف</label>
                            <div class="img-upload-area" onclick="document.getElementById('coverImageFile').click()">
                                <i class="fas fa-image"></i>
                                <p>اضغط لرفع صورة غلاف</p>
                                <input type="file" name="cover_image_file" id="coverImageFile" accept="image/*" style="display:none">
                                <div id="coverFileName" style="font-size:12px;color:#157039;font-weight:700;margin-top:5px"></div>
                            </div>
                            <img id="coverPreview" class="img-preview">
                            <div class="img-url-row">
                                <span class="or-divider">— أو أدخل رابط الصورة —</span>
                                <input type="text" name="cover_image" class="form-control" placeholder="https://example.com/cover.jpg" value="<?php echo htmlspecialchars($_POST['cover_image'] ?? ''); ?>">
                            </div>
                            <div class="field-hint">صورة كبيرة في أعلى المقال (اختياري)</div>
                        </div>

                        <div class="form-group">
                            <label>وصف صورة المقال</label>
                            <input type="text" name="image_desc" class="form-control" value="<?php echo htmlspecialchars($_POST['image_desc'] ?? ''); ?>" placeholder="اختياري — يظهر أسفل الصورة">
                        </div>

                        <div class="form-group">
                            <label>معرض الصور</label>
                            <div class="img-upload-area" onclick="document.getElementById('galleryFiles').click()">
                                <i class="fas fa-images"></i>
                                <p>اختر عدة صور من الجهاز</p>
                                <input type="file" name="gallery_files[]" id="galleryFiles" accept="image/*" multiple style="display:none">
                                <div id="galleryFileNames" style="font-size:12px;color:#157039;font-weight:700;margin-top:5px"></div>
                            </div>
                            <div style="margin-top:10px;border-top:1px dashed #eee;padding-top:10px">
                                <label style="font-size:12px;color:#888;font-weight:400">أو أدخل روابط الصور (رابط في كل سطر)</label>
                                <textarea name="gallery_urls" class="form-control" style="min-height:60px;margin-top:4px" placeholder="https://example.com/photo1.jpg"></textarea>
                            </div>
                            <div style="margin-top:8px">
                                <label style="font-size:12px;color:#888;font-weight:400">وصف كل صورة (سطر لكل صورة — اختياري)</label>
                                <textarea name="gallery_descs" class="form-control" style="min-height:40px;margin-top:4px" placeholder="الصورة الأولى"></textarea>
                            </div>
                            <div class="field-hint">صور إضافية تظهر داخل المقال (اختياري)</div>
                        </div>

                        <div class="form-group">
                            <label>الفقرات (كل فقرة في سطر منفصل)</label>
                            <textarea name="paragraphs" class="form-control" style="min-height:200px"><?php echo htmlspecialchars($_POST['paragraphs'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label>الوسوم (مفصولة بفواصل)</label>
                            <input type="text" name="tags" class="form-control" value="<?php echo htmlspecialchars($_POST['tags'] ?? ''); ?>" placeholder="مثال: جامعة, طلاب, أنشطة">
                        </div>

                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> حفظ المقال</button>
                        <a href="articles.php" class="btn btn-secondary">إلغاء</a>
                    </form>
                </div>
            </div>
        </div>
    </div>

<script>
(function(){
    function setupPreview(inputId, previewId, nameId) {
        var input = document.getElementById(inputId);
        if (!input) return;
        input.addEventListener('change', function(){
            if (this.files && this.files[0]) {
                var nameEl = document.getElementById(nameId);
                if (nameEl) nameEl.textContent = this.files[0].name;
                var preview = document.getElementById(previewId);
                if (preview) {
                    var reader = new FileReader();
                    reader.onload = function(e){ preview.src = e.target.result; preview.style.display = 'block'; };
                    reader.readAsDataURL(this.files[0]);
                }
            }
        });
    }
    setupPreview('imageFile', 'imgPreview', 'imgFileName');
    setupPreview('coverImageFile', 'coverPreview', 'coverFileName');
    var gf = document.getElementById('galleryFiles');
    if (gf) {
        gf.addEventListener('change', function(){
            if (this.files && this.files.length) {
                var names = [];
                for (var i = 0; i < this.files.length; i++) names.push(this.files[i].name);
                document.getElementById('galleryFileNames').textContent = names.join(' — ');
            }
        });
    }
})();
</script>
</body>
</html>
