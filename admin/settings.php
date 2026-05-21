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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = [
        'site_name', 'site_subtitle', 'site_url',
        'logo_url', 'favicon_url',
        'primary_color', 'primary_dark', 'primary_light', 'dark_color',
        'facebook_url', 'twitter_url', 'youtube_url', 'instagram_url',
        'linkedin_url', 'google_news_url',
        'address', 'maps_url', 'phone', 'fax', 'email',
        'footer_text', 'editor_name', 'editor_title', 'college_logo_url',
    ];

    foreach ($fields as $f) {
        $settings[$f] = $_POST[$f] ?? '';
    }

    $ticker_raw = trim($_POST['ticker_news_ids'] ?? '');
    $ticker_ids = [];
    if ($ticker_raw !== '') {
        $parts = array_map('trim', explode(',', $ticker_raw));
        foreach ($parts as $p) {
            if (is_numeric($p)) $ticker_ids[] = intval($p);
        }
    }
    $settings['ticker_news_ids'] = $ticker_ids;

    if (save_settings($settings)) {
        $GLOBALS['settings'] = $settings;
        $message = 'تم حفظ الإعدادات بنجاح';
    } else {
        $error = 'حدث خطأ أثناء حفظ الإعدادات';
    }
}

$ticker_str = implode(', ', $settings['ticker_news_ids'] ?? []);

$admin = $_SESSION['admin'];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الإعدادات - <?php echo htmlspecialchars(get_setting('site_name', 'الأهرام')); ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
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
            
            <a href="ads.php" class="nav-item"><i class="fas fa-ad"></i> الإعلانات</a>
            <a href="sections.php" class="nav-item"><i class="fas fa-layer-group"></i> الأقسام</a>
            <a href="settings.php" class="nav-item active"><i class="fas fa-cogs"></i> الإعدادات</a>
            <a href="logout.php" class="nav-item"><i class="fas fa-sign-out-alt"></i> تسجيل الخروج</a>
        </div>
        <div class="main-content">
            <div class="top-bar-admin">
                <div class="page-title">الإعدادات</div>
                <div class="user-info">مرحباً، <?php echo htmlspecialchars($admin['display_name'] ?? $admin['username']); ?></div>
            </div>

            <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">إعدادات الموقع</div>
                <div class="card-body">
                    <form method="post">
                        <h4 style="margin-bottom:10px;color:#555;font-size:15px">معلومات الموقع</h4>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:15px">
                            <div class="form-group">
                                <label>اسم الموقع</label>
                                <input type="text" name="site_name" class="form-control" value="<?php echo htmlspecialchars($settings['site_name'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label>الشعار الفرعي</label>
                                <input type="text" name="site_subtitle" class="form-control" value="<?php echo htmlspecialchars($settings['site_subtitle'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label>رئيس التحرير (النص)</label>
                                <input type="text" name="editor_name" class="form-control" value="<?php echo htmlspecialchars($settings['editor_name'] ?? 'رئيس التحرير'); ?>">
                            </div>
                            <div class="form-group">
                                <label>رئيس التحرير (الاسم)</label>
                                <input type="text" name="editor_title" class="form-control" value="<?php echo htmlspecialchars($settings['editor_title'] ?? 'أ.د. أحمد القاصد'); ?>">
                            </div>
                        </div>

                        <h4 style="margin-bottom:10px;color:#555;font-size:15px">الشعارات والأيقونات</h4>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:15px">
                            <div class="form-group">
                                <label>رابط الشعار</label>
                                <input type="text" name="logo_url" class="form-control" value="<?php echo htmlspecialchars($settings['logo_url'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label>رابط الأيقونة</label>
                                <input type="text" name="favicon_url" class="form-control" value="<?php echo htmlspecialchars($settings['favicon_url'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label>لوجو الكلية (يظهر شمالاً)</label>
                                <input type="text" name="college_logo_url" class="form-control" value="<?php echo htmlspecialchars($settings['college_logo_url'] ?? ''); ?>">
                            </div>
                        </div>

                        <h4 style="margin-bottom:10px;color:#555;font-size:15px">الألوان</h4>
                        <div style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:15px">
                            <div class="form-group">
                                <label>اللون الأساسي</label>
                                <input type="color" name="primary_color" class="form-control" value="<?php echo htmlspecialchars($settings['primary_color'] ?? '#157039'); ?>" style="height:40px;padding:2px">
                            </div>
                            <div class="form-group">
                                <label>اللون الداكن</label>
                                <input type="color" name="primary_dark" class="form-control" value="<?php echo htmlspecialchars($settings['primary_dark'] ?? '#0f5a2d'); ?>" style="height:40px;padding:2px">
                            </div>
                            <div class="form-group">
                                <label>اللون الفاتح</label>
                                <input type="color" name="primary_light" class="form-control" value="<?php echo htmlspecialchars($settings['primary_light'] ?? '#1a8a45'); ?>" style="height:40px;padding:2px">
                            </div>
                            <div class="form-group">
                                <label>اللون الغامق</label>
                                <input type="color" name="dark_color" class="form-control" value="<?php echo htmlspecialchars($settings['dark_color'] ?? '#091e3a'); ?>" style="height:40px;padding:2px">
                            </div>
                        </div>

                        <h4 style="margin-bottom:10px;color:#555;font-size:15px">روابط التواصل الاجتماعي</h4>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:15px">
                            <div class="form-group">
                                <label>فيسبوك</label>
                                <input type="text" name="facebook_url" class="form-control" value="<?php echo htmlspecialchars($settings['facebook_url'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label>تويتر</label>
                                <input type="text" name="twitter_url" class="form-control" value="<?php echo htmlspecialchars($settings['twitter_url'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label>يوتيوب</label>
                                <input type="text" name="youtube_url" class="form-control" value="<?php echo htmlspecialchars($settings['youtube_url'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label>انستجرام</label>
                                <input type="text" name="instagram_url" class="form-control" value="<?php echo htmlspecialchars($settings['instagram_url'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label>لينكد إن</label>
                                <input type="text" name="linkedin_url" class="form-control" value="<?php echo htmlspecialchars($settings['linkedin_url'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label>أخبار جوجل</label>
                                <input type="text" name="google_news_url" class="form-control" value="<?php echo htmlspecialchars($settings['google_news_url'] ?? ''); ?>">
                            </div>
                        </div>

                        <h4 style="margin-bottom:10px;color:#555;font-size:15px">معلومات الاتصال</h4>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:15px">
                            <div class="form-group">
                                <label>العنوان</label>
                                <input type="text" name="address" class="form-control" value="<?php echo htmlspecialchars($settings['address'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label>رابط الخريطة</label>
                                <input type="text" name="maps_url" class="form-control" value="<?php echo htmlspecialchars($settings['maps_url'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label>الهاتف</label>
                                <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($settings['phone'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label>الفاكس</label>
                                <input type="text" name="fax" class="form-control" value="<?php echo htmlspecialchars($settings['fax'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label>البريد الإلكتروني</label>
                                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($settings['email'] ?? ''); ?>">
                            </div>
                        </div>

                        <h4 style="margin-bottom:10px;color:#555;font-size:15px">تذييل الموقع ورئيس التحرير</h4>
                        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:15px">
                            <div class="form-group">
                                <label>نص التذييل</label>
                                <input type="text" name="footer_text" class="form-control" value="<?php echo htmlspecialchars($settings['footer_text'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label>رئيس التحرير</label>
                                <input type="text" name="editor_name" class="form-control" value="<?php echo htmlspecialchars($settings['editor_name'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label>لقب رئيس التحرير</label>
                                <input type="text" name="editor_title" class="form-control" value="<?php echo htmlspecialchars($settings['editor_title'] ?? ''); ?>">
                            </div>
                        </div>

                        <h4 style="margin-bottom:10px;color:#555;font-size:15px">إعدادات أخرى</h4>
                        <div style="display:grid;grid-template-columns:1fr;gap:15px">
                            <div class="form-group">
                                <label>رابط الموقع</label>
                                <input type="text" name="site_url" class="form-control" value="<?php echo htmlspecialchars($settings['site_url'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label>أرقام المقالات في الشريط المتحرك (مفصولة بفواصل)</label>
                                <input type="text" name="ticker_news_ids" class="form-control" value="<?php echo htmlspecialchars($ticker_str); ?>" placeholder="مثال: 2, 5, 8, 17, 28">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> حفظ الإعدادات</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
