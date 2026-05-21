<?php
session_start();
require_once __DIR__ . '/../includes/config.php';

if (!isset($_SESSION['admin'])) {
    header('Location: index.php');
    exit;
}

$message = '';
$error = '';

$settings_file = DATA_PATH . '/settings.json';
$settings = [];
if (file_exists($settings_file)) {
    $settings = json_decode(file_get_contents($settings_file), true);
    if (json_last_error() !== JSON_ERROR_NONE) $settings = [];
}

$ad_positions = [
    'navbar'        => 'شريط الإعلانات العلوي (بجانب الشعار)',
    'after_hero'    => 'بعد القسم الرئيسي (الهيرو)',
    'between_sections' => 'بين الأقسام (بعد منشآت الجامعة)',
    'sidebar_top'   => 'الشريط الجانبي - أعلى',
    'sidebar_bottom' => 'الشريط الجانبي - أسفل',
    'section_page'  => 'صفحة القسم - أسفل المقالات',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ad_type = $_POST['ad_type'] ?? 'image';
    $position = $_POST['position'] ?? '';

    $ads = $settings['ads'] ?? [];

    if ($ad_type === 'image') {
        $image = trim($_POST['ad_image'] ?? '');
        $link = trim($_POST['ad_link'] ?? '');

        // Handle file upload
        if (isset($_FILES['ad_image_file']) && $_FILES['ad_image_file']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../uploads/ads/';
            $ext = strtolower(pathinfo($_FILES['ad_image_file']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','gif','webp','svg'];
            if (in_array($ext, $allowed)) {
                $filename = $position . '_' . time() . '.' . $ext;
                $destPath = $uploadDir . $filename;
                $destUrl = 'uploads/ads/' . $filename;
                if (move_uploaded_file($_FILES['ad_image_file']['tmp_name'], $destPath)) {
                    $image = $destUrl;
                } else {
                    $error = 'فشل رفع الصورة.';
                }
            } else {
                $error = 'امتداد الصورة غير مسموح به.';
            }
        }

        if ($image) {
            $ads[$position] = ['type' => 'image', 'image' => $image, 'link' => $link];
        } elseif (!$error) {
            unset($ads[$position]);
        }
    } else {
        $code = trim($_POST['ad_code'] ?? '');
        if ($code) {
            $ads[$position] = ['type' => 'code', 'code' => $code];
        } else {
            unset($ads[$position]);
        }
    }

    $settings['ads'] = $ads;

    if (save_settings($settings)) {
        $GLOBALS['settings'] = $settings;
        $message = 'تم حفظ الإعلان بنجاح';
    } else {
        $error = 'حدث خطأ أثناء حفظ الإعلان';
    }
}

// Handle delete
if (isset($_GET['delete']) && isset($settings['ads'][$_GET['delete']])) {
    unset($settings['ads'][$_GET['delete']]);
    $settings['ads'] = $settings['ads'];
    save_settings($settings);
    $GLOBALS['settings'] = $settings;
    $message = 'تم حذف الإعلان';
}

$ads = $settings['ads'] ?? [];

// Edit mode: load ad data to populate form
$edit_pos = $_GET['edit'] ?? '';
$edit_ad = null;
if ($edit_pos) {
    if (isset($ads[$edit_pos])) {
        $edit_ad = $ads[$edit_pos];
    }
    // Keep $edit_pos even if no ad exists yet (user wants to create a new one)
}

$admin = $_SESSION['admin'];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الإعلانات - <?php echo htmlspecialchars(get_setting('site_name', 'الأهرام')); ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .ad-card{background:#fff;border-radius:8px;box-shadow:0 1px 3px rgba(0,0,0,0.08);margin-bottom:20px;padding:20px}
        .ad-card .ad-preview{max-width:100%;max-height:120px;border-radius:4px;margin-top:10px}
        .ad-card .badge-image{display:inline-block;background:#3498db;color:#fff;padding:2px 10px;border-radius:3px;font-size:11px}
        .ad-card .badge-code{display:inline-block;background:#e67e22;color:#fff;padding:2px 10px;border-radius:3px;font-size:11px}
        .ad-card .ad-code-preview{background:#f8f8f8;border:1px solid #eee;border-radius:4px;padding:10px;font-size:12px;color:#666;font-family:monospace;max-height:80px;overflow:hidden;margin-top:8px;direction:ltr;text-align:left}
        .btn-edit{border:none;padding:5px 12px;border-radius:4px;font-size:12px;cursor:pointer;text-decoration:none;display:inline-block}
        .btn-edit-sm{background:#f39c12;color:#fff}
        .btn-edit-sm:hover{background:#d68910;color:#fff}
        .btn-danger-sm{background:#c0392b;color:#fff;border:none;padding:5px 12px;border-radius:4px;font-size:12px;cursor:pointer;text-decoration:none;display:inline-block}
        .btn-danger-sm:hover{background:#a93226;color:#fff}
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
            <a href="ads.php" class="nav-item active"><i class="fas fa-ad"></i> الإعلانات</a>
            <a href="ad-requests.php" class="nav-item"><i class="fas fa-envelope"></i> طلبات الإعلانات</a>
            <a href="sections.php" class="nav-item"><i class="fas fa-layer-group"></i> الأقسام</a>
            <a href="settings.php" class="nav-item"><i class="fas fa-cogs"></i> الإعدادات</a>
            <a href="logout.php" class="nav-item"><i class="fas fa-sign-out-alt"></i> تسجيل الخروج</a>
        </div>
        <div class="main-content">
            <div class="top-bar-admin">
                <div class="page-title">إدارة الإعلانات</div>
                <div class="user-info">مرحباً، <?php echo htmlspecialchars($admin['display_name'] ?? $admin['username']); ?></div>
            </div>

            <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <!-- ===== الإعلانات الحالية ===== -->
            <div class="card">
                <div class="card-header"><i class="fas fa-list"></i> الإعلانات الحالية</div>
                <div class="card-body">
                    <?php if (empty($ads)): ?>
                    <div class="empty-state"><i class="fas fa-ad"></i><p style="margin-top:10px">لا توجد إعلانات مضافة بعد.</p></div>
                    <?php else: ?>
                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:15px">
                        <?php foreach ($ads as $pos => $ad): ?>
                        <div class="ad-card">
                            <div style="display:flex;justify-content:space-between;align-items:flex-start">
                                <div>
                                    <strong style="font-size:15px"><?php echo htmlspecialchars($ad_positions[$pos] ?? $pos); ?></strong>
                                    <span class="<?php echo $ad['type'] === 'image' ? 'badge-image' : 'badge-code'; ?>" style="margin-right:8px">
                                        <?php echo $ad['type'] === 'image' ? 'صورة' : 'كود'; ?>
                                    </span>
                                </div>
                                <div style="display:flex;gap:5px">
                                    <a href="?edit=<?php echo urlencode($pos); ?>" class="btn-edit btn-edit-sm"><i class="fas fa-edit"></i></a>
                                    <a href="?delete=<?php echo urlencode($pos); ?>" class="btn-danger-sm" onclick="return confirm('حذف هذا الإعلان؟')"><i class="fas fa-trash"></i></a>
                                </div>
                            </div>
                            <?php if ($ad['type'] === 'image'): ?>
                                <?php if (!empty($ad['image'])): ?>
                                <img src="<?php echo htmlspecialchars($ad['image']); ?>" class="ad-preview" alt="إعلان">
                                <?php endif; ?>
                                <?php if (!empty($ad['link'])): ?>
                                <div style="margin-top:6px;font-size:12px;color:#888;font-weight:400">الرابط: <?php echo htmlspecialchars($ad['link']); ?></div>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="ad-code-preview"><?php echo htmlspecialchars($ad['code'] ?? ''); ?></div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ===== نشر إعلان في كل المواضع ===== -->
            <div class="card">
                <div class="card-header"><i class="fas fa-bullhorn"></i> نشر صورة في جميع المواضع</div>
                <div class="card-body">
                    <form method="post" enctype="multipart/form-data" action="?bulk=1">
                        <div class="form-group">
                            <label>اختر صورة لنشرها في كل أماكن الإعلانات</label>
                            <input type="file" name="bulk_ad_image" accept="image/*" required style="display:block;width:100%;padding:8px;border:1px solid #ddd;border-radius:4px;font-size:13px">
                        </div>
                        <div class="form-group">
                            <label>رابط النقر (سيحول إلى صفحة طلب الإعلان)</label>
                            <input type="text" name="bulk_ad_link" class="form-control" placeholder="اختياري" value="ad-request.php">
                        </div>
                        <button type="submit" class="btn btn-primary" style="background:#27ae60;border-color:#27ae60"><i class="fas fa-cloud-upload-alt"></i> نشر في الكل</button>
                        <div style="font-size:12px;color:#888;margin-top:5px;font-weight:400">سيتم استبدال جميع الإعلانات الحالية بهذه الصورة في كل المواضع (navbar, after_hero, between_sections, sidebar_top, sidebar_bottom, section_page).</div>
                    </form>
                    <?php
                    if (isset($_GET['bulk']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['bulk_ad_image']) && $_FILES['bulk_ad_image']['error'] === UPLOAD_ERR_OK) {
                        $uploadDir = __DIR__ . '/../uploads/ads/';
                        $ext = strtolower(pathinfo($_FILES['bulk_ad_image']['name'], PATHINFO_EXTENSION));
                        $allowed = ['jpg','jpeg','png','gif','webp','svg'];
                        if (in_array($ext, $allowed)) {
                            $filename = 'bulk_' . time() . '.' . $ext;
                            if (move_uploaded_file($_FILES['bulk_ad_image']['tmp_name'], $uploadDir . $filename)) {
                                $imgUrl = 'uploads/ads/' . $filename;
                                $ads = $settings['ads'] ?? [];
                                foreach (array_keys($ad_positions) as $pos) {
                                    $ads[$pos] = ['type' => 'image', 'image' => $imgUrl, 'link' => 'ad-request.php'];
                                }
                                $settings['ads'] = $ads;
                                if (save_settings($settings)) {
                                    echo '<div class="alert alert-success" style="margin-top:15px">تم نشر الإعلان في جميع المواضع بنجاح.</div>';
                                }
                            }
                        }
                    }
                    ?>
                </div>
            </div>

            <!-- ===== إضافة / تعديل إعلان ===== -->
            <div class="card">
                <div class="card-header"><i class="fas fa-<?php echo $edit_pos ? 'edit' : 'plus-circle'; ?>"></i> <?php echo $edit_pos ? 'تعديل الإعلان' : 'إضافة إعلان جديد'; ?></div>
                <div class="card-body">
                    <form method="post" enctype="multipart/form-data" id="adForm">
                        <div class="form-group">
                            <label>الموضع</label>
                            <select name="position" id="adPosition" class="form-control" style="width:100%;max-width:400px">
                                <option value="">-- اختر الموضع --</option>
                                <?php foreach ($ad_positions as $key => $label): ?>
                                <option value="<?php echo htmlspecialchars($key); ?>" <?php echo $edit_pos === $key ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($label); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <div style="font-size:12px;color:#888;margin-top:4px;font-weight:400">اختر الموضع ثم املأ البيانات.</div>
                        </div>

                        <div id="formFields" style="<?php echo $edit_pos !== '' ? '' : 'display:none'; ?>">
                            <div class="form-group">
                                <label>نوع الإعلان</label>
                                <select name="ad_type" id="adType" class="form-control" style="width:250px">
                                    <option value="image" <?php echo $edit_ad && $edit_ad['type'] === 'image' ? 'selected' : ''; ?>>صورة مع رابط</option>
                                    <option value="code" <?php echo $edit_ad && $edit_ad['type'] === 'code' ? 'selected' : ''; ?>>كود HTML / JavaScript</option>
                                </select>
                            </div>

                            <div id="imageFields" style="<?php echo !$edit_ad || $edit_ad['type'] === 'image' ? '' : 'display:none'; ?>">
                            <div class="form-group">
                                <label>رفع صورة الإعلان</label>
                                <input type="file" name="ad_image_file" accept="image/*" style="display:block;width:100%;padding:8px;border:1px solid #ddd;border-radius:4px;font-size:13px">
                                <div style="font-size:12px;color:#888;margin-top:3px;font-weight:400;display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                                    <span>أو أدخل رابط مباشر:</span>
                                    <input type="text" name="ad_image" class="form-control" placeholder="https://example.com/banner.jpg" id="adImage" value="<?php echo htmlspecialchars($edit_ad['image'] ?? ''); ?>" style="width:300px;font-size:13px">
                                </div>
                            </div>
                            <div class="form-group">
                                <label>رابط النقر</label>
                                <input type="text" name="ad_link" class="form-control" placeholder="https://example.com" id="adLink" value="<?php echo htmlspecialchars($edit_ad['link'] ?? ''); ?>">
                            </div>
                            </div>

                            <div id="codeFields" style="<?php echo $edit_ad && $edit_ad['type'] === 'code' ? '' : 'display:none'; ?>">
                                <div class="form-group">
                                    <label>كود الإعلان (HTML/JavaScript)</label>
                                    <textarea name="ad_code" class="form-control" id="adCode" placeholder="&lt;!-- كود الإعلان هنا --&gt;" style="height:120px;direction:ltr;text-align:left;font-family:monospace;font-size:13px"><?php echo htmlspecialchars($edit_ad['code'] ?? ''); ?></textarea>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> حفظ الإعلان</button>
                            <?php if ($edit_pos): ?>
                            <a href="ads.php" class="btn btn-secondary">إلغاء</a>
                            <?php endif; ?>
                        </div>
                    </form>

                    <script>
                    (function(){
                        var posSel = document.getElementById('adPosition');
                        var typeSel = document.getElementById('adType');

                        posSel.addEventListener('change', function(){
                            if (this.value) {
                                window.location.href = '?edit=' + encodeURIComponent(this.value);
                            }
                        });

                        typeSel.addEventListener('change', function(){
                            var showImage = this.value === 'image';
                            document.getElementById('imageFields').style.display = showImage ? 'block' : 'none';
                            document.getElementById('codeFields').style.display = showImage ? 'none' : 'block';
                        });
                    })();
                    </script>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
