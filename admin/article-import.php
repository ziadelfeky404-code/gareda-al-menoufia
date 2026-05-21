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

// ─── Parse Functions ───────────────────────────────────────────

function parseHTML($html) {
    $doc = new DOMDocument();
    $doc->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'), LIBXML_NOWARNING | LIBXML_NOERROR);
    $xpath = new DOMXPath($doc);

    // Title: <title> or first <h1>
    $title = '';
    $nodes = $xpath->query('//title');
    if ($nodes->length) $title = trim($nodes->item(0)->textContent);
    if (!$title) {
        $nodes = $xpath->query('//h1');
        if ($nodes->length) $title = trim($nodes->item(0)->textContent);
    }

    // Paragraphs: all <p> tags with meaningful content
    $paragraphs = [];
    $nodes = $xpath->query('//p');
    foreach ($nodes as $node) {
        $text = trim($node->textContent);
        if (mb_strlen($text) > 20) $paragraphs[] = $text;
    }

    // First image
    $image = '';
    $nodes = $xpath->query('//img');
    foreach ($nodes as $node) {
        $src = $node->getAttribute('src');
        if ($src && !str_starts_with($src, 'data:')) { $image = $src; break; }
    }

    return ['title' => $title, 'paragraphs' => $paragraphs, 'image' => $image];
}

function parseDOCX($path) {
    if (!class_exists('ZipArchive')) {
        return ['error' => 'إضافة Zip غير مفعلة في PHP. شغّل PHP بـ: php -d extension_dir=...\ext -d extension=php_zip.dll'];
    }
    $zip = new ZipArchive();
    if ($zip->open($path) !== true) {
        return ['error' => 'لا يمكن فتح ملف DOCX'];
    }
    // Read word/document.xml
    $content = $zip->getFromName('word/document.xml');
    $zip->close();
    if (!$content) return ['error' => 'ملف DOCX تالف'];

    $xml = simplexml_load_string($content);
    if (!$xml) return ['error' => 'لا يمكن قراءة محتوى DOCX'];

    $namespaces = $xml->getNamespaces(true);
    $body = $xml->body ?? $xml->children($namespaces['w'])->body ?? null;
    if (!$body) return ['error' => 'لا يوجد محتوى نصي في الملف'];

    $paragraphs = [];
    $title = '';

    // Register w namespace
    $w = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';
    $body->registerXPathNamespace('w', $w);

    $pNodes = $body->xpath('//w:p');
    if (!$pNodes) $pNodes = $body->children($w)->p ?? [];

    $first = true;
    foreach ($pNodes as $p) {
        $textParts = [];
        $runs = $p->xpath('.//w:t');
        if (!$runs) $runs = $p->children($w)->r ?? [];

        foreach ($runs as $r) {
            $tNodes = $r->xpath('.//w:t');
            if (!$tNodes) $tNodes = [$r];
            foreach ($tNodes as $t) {
                $textParts[] = (string)$t;
            }
        }
        $text = trim(implode('', $textParts));
        if (!$text) continue;

        if ($first && mb_strlen($text) < 100) {
            $title = $text;
            $first = false;
        } else {
            if (mb_strlen($text) > 20) $paragraphs[] = $text;
        }
    }

    return ['title' => $title, 'paragraphs' => $paragraphs, 'image' => ''];
}

function parsePDF($path) {
    $text = file_get_contents($path);
    // Basic PDF text extraction: find text between parentheses (simple approach)
    preg_match_all('/\(([^)]*)\)/', $text, $matches);
    $lines = [];
    foreach ($matches[1] as $m) {
        $decoded = preg_replace_callback('/\\\\([0-7]{3})/', function($m) { return chr(octdec($m[1])); }, $m);
        $decoded = str_replace(['\\(', '\\)', '\\n'], ['(', ')', "\n"], $decoded);
        $lines[] = trim($decoded);
    }
    $allText = implode("\n", $lines);

    // Split into paragraphs by double newlines or long lines
    $rawParagraphs = preg_split('/\n\s*\n/', $allText);
    $paragraphs = [];
    $title = '';
    $first = true;
    foreach ($rawParagraphs as $p) {
        $p = trim($p);
        if (mb_strlen($p) < 10) continue;
        if ($first) { $title = mb_substr($p, 0, 150); $first = false; }
        else $paragraphs[] = $p;
    }

    return ['title' => $title, 'paragraphs' => $paragraphs, 'image' => ''];
}

// ─── Handle Upload ─────────────────────────────────────────────

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['article_file'])) {
    $section = $_POST['section'] ?? '';
    $author = trim($_POST['author'] ?? '');
    $date = trim($_POST['date'] ?? date('Y-m-d'));

    if (!in_array($section, $sections)) {
        $error = 'الرجاء اختيار قسم صحيح';
    } else {
        $file = $_FILES['article_file'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $error = 'خطأ في رفع الملف: ' . $file['error'];
        } else {
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $result = null;

            if ($ext === 'html' || $ext === 'htm') {
                $html = file_get_contents($file['tmp_name']);
                $result = parseHTML($html);
            } elseif ($ext === 'docx') {
                $result = parseDOCX($file['tmp_name']);
            } elseif ($ext === 'pdf') {
                $result = parsePDF($file['tmp_name']);
            } else {
                $error = 'الصيغة غير مدعومة. الصيغ المدعومة: HTML, DOCX, PDF';
            }

            if ($result && isset($result['error'])) {
                $error = $result['error'];
            } elseif ($result) {
                // Save extracted image URL if found
                $image = $result['image'] ?? '';

                // If no image found in file, try the manual image URL from form
                $manualImage = trim($_POST['image'] ?? '');
                if (!$image && $manualImage) $image = $manualImage;

                $title = $result['title'];
                $paragraphs = $result['paragraphs'];

                // Auto-generate tags from section name
                $tags = [$section];

                // Validate we got enough content
                if (!$title && empty($paragraphs)) {
                    $error = 'لم نتمكن من استخراج محتوى من الملف. تأكد من أن الملف يحتوي على نصوص.';
                } else {
                    if (!$title) $title = 'مقال بدون عنوان - ' . date('Y-m-d');

                    // Save to articles.json
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
                        'cover_image' => '',
                        'image_desc' => '',
                        'date' => $date,
                        'author' => $author,
                        'tags' => $tags,
                        'paragraphs' => $paragraphs,
                        'images' => [],
                    ];

                    $articles[] = $article;
                    $GLOBALS['articles'] = $articles;
                    save_articles($articles);

                    $message = " تم استيراد المقال بنجاح! (ID: $new_id)";
                    $message .= "<br><small>العنوان: " . htmlspecialchars($title) . "</small>";
                    $message .= "<br><small>عدد الفقرات: " . count($paragraphs) . "</small>";
                }
            }
        }
    }
}

$admin = $_SESSION['admin'];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>استيراد مقال - <?php echo htmlspecialchars(get_setting('site_name', 'الأهرام')); ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .upload-zone{border:3px dashed #ddd;border-radius:8px;padding:40px 20px;text-align:center;cursor:pointer;transition:all .3s;margin-bottom:15px}
        .upload-zone:hover,.upload-zone.dragover{border-color:#157039;background:#f0faf4}
        .upload-zone i{font-size:48px;color:#157039;display:block;margin-bottom:10px}
        .upload-zone p{font-size:14px;color:#666;font-weight:400}
        .upload-zone .ext-badge{display:inline-block;background:#157039;color:#fff;padding:2px 10px;border-radius:3px;font-size:12px;margin:2px}
        .preview-box{background:#fafafa;border:1px solid #eee;border-radius:4px;padding:12px;margin-top:10px;max-height:200px;overflow-y:auto;font-size:12px;color:#555;font-weight:400;line-height:1.6}
        .preview-box strong{color:#333}
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
            <a href="article-add.php" class="nav-item"><i class="fas fa-plus-circle"></i> إضافة مقال</a>
            <a href="article-import.php" class="nav-item active"><i class="fas fa-upload"></i> استيراد مقال</a>
            <a href="ads.php" class="nav-item"><i class="fas fa-ad"></i> الإعلانات</a>
            <a href="sections.php" class="nav-item"><i class="fas fa-layer-group"></i> الأقسام</a>
            <a href="settings.php" class="nav-item"><i class="fas fa-cogs"></i> الإعدادات</a>
            <a href="logout.php" class="nav-item"><i class="fas fa-sign-out-alt"></i> تسجيل الخروج</a>
        </div>
        <div class="main-content">
            <div class="top-bar-admin">
                <div class="page-title">استيراد مقال من ملف</div>
                <div class="user-info">مرحباً، <?php echo htmlspecialchars($admin['display_name'] ?? $admin['username']); ?></div>
            </div>

            <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header"><i class="fas fa-file-upload"></i> رفع ملف واستيراده</div>
                <div class="card-body">
                    <form method="post" enctype="multipart/form-data" id="importForm">
                        <div class="form-group">
                            <label>القسم</label>
                            <select name="section" class="form-control" required>
                                <option value="">اختر القسم</option>
                                <?php foreach ($sections as $s): ?>
                                <option value="<?php echo htmlspecialchars($s); ?>"><?php echo htmlspecialchars($s); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="upload-zone" id="uploadZone" onclick="document.getElementById('fileInput').click()">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <p>اسحب الملف هنا أو اضغط للاختيار</p>
                            <div>
                                <span class="ext-badge">HTML</span>
                                <span class="ext-badge">DOCX</span>
                                <span class="ext-badge">PDF</span>
                            </div>
                            <input type="file" name="article_file" id="fileInput" accept=".html,.htm,.docx,.pdf" style="display:none" required>
                            <div id="fileName" style="margin-top:10px;font-weight:700;color:#157039;"></div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>المؤلف (اختياري)</label>
                                    <input type="text" name="author" class="form-control" placeholder="سيظهر في المقال">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>التاريخ (اختياري)</label>
                                    <input type="text" name="date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>رابط صورة (اختياري — إذا لم يستخرج من الملف)</label>
                            <input type="text" name="image" class="form-control" placeholder="https://example.com/image.jpg">
                        </div>

                        <button type="submit" class="btn btn-primary"><i class="fas fa-file-import"></i> استيراد المقال</button>
                        <a href="articles.php" class="btn btn-secondary">عرض كل المقالات</a>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><i class="fas fa-info-circle"></i> كيف تعمل الميزة</div>
                <div class="card-body" style="font-size:14px;font-weight:400;line-height:1.8;color:#555">
                    <ul>
                        <li><strong>HTML</strong> — يستخرج العنوان من <code>&lt;title&gt;</code> أو <code>&lt;h1&gt;</code>، والفقرات من <code>&lt;p&gt;</code>، وأول صورة من <code>&lt;img&gt;</code>.</li>
                        <li><strong>DOCX (Word)</strong> — يستخرج النصوص من ملف Word. أول فقرة قصيرة تعتبر عنوانًا. <span style="color:#c0392b">* يتطلب تفعيل Zip</span></li>
                        <li><strong>PDF</strong> — يستخرج النصوص من ملف PDF. <span style="color:#c0392b">* الاستخراج أساسي وقد لا يكون دقيقًا</span></li>
                    </ul>
                    <p style="margin-top:10px;color:#888;">💡 نصيحة: أفضل صيغة للاستيراد هي <strong>HTML</strong> — احفظ ملف Word كـ "صفحة ويب" ثم ارفعه.</p>
                </div>
            </div>
        </div>
    </div>

    <script>
    document.getElementById('fileInput').addEventListener('change', function(e) {
        var name = this.files.length ? this.files[0].name : '';
        document.getElementById('fileName').textContent = name;
    });
    var zone = document.getElementById('uploadZone');
    zone.addEventListener('dragover', function(e) { e.preventDefault(); this.classList.add('dragover'); });
    zone.addEventListener('dragleave', function() { this.classList.remove('dragover'); });
    zone.addEventListener('drop', function(e) {
        e.preventDefault();
        this.classList.remove('dragover');
        var files = e.dataTransfer.files;
        if (files.length) {
            document.getElementById('fileInput').files = files;
            document.getElementById('fileName').textContent = files[0].name;
        }
    });
    </script>
</body>
</html>
