<?php
/**
 * Setup script for جامعة المنوفية News Portal
 * Run this once after deploying to initialize the admin user.
 * Delete or protect this file after use.
 */
session_start();
require_once __DIR__ . '/includes/config.php';

$message = '';

// Handle setup form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['setup'])) {
    $username = trim($_POST['username'] ?? 'admin');
    $password = $_POST['password'] ?? '';
    $display = trim($_POST['display_name'] ?? 'مدير الموقع');

    if (strlen($password) < 6) {
        $message = '<div class="alert alert-danger">كلمة المرور يجب أن تكون 6 أحرف على الأقل</div>';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $users = [
            [
                'username' => $username,
                'password_hash' => $hash,
                'role' => 'admin',
                'display_name' => $display,
                'created_at' => date('Y-m-d')
            ]
        ];
        if (file_put_contents(DATA_PATH . '/users.json', json_encode($users, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT))) {
            $message = '<div class="alert alert-success">تم إنشاء المستخدم admin بنجاح! يمكنك الآن <a href="admin/index.php">تسجيل الدخول</a></div>';
        } else {
            $message = '<div class="alert alert-danger">فشل في كتابة ملف users.json. تأكد من صلاحيات الكتابة.</div>';
        }
    }
}

// Check if users file exists and has content
$has_users = file_exists(DATA_PATH . '/users.json') && filesize(DATA_PATH . '/users.json') > 20;

// Check if data dir is writable
$data_writable = is_writable(DATA_PATH);
$articles_ok = file_exists(DATA_PATH . '/articles.json') && filesize(DATA_PATH . '/articles.json') > 1000;
$settings_ok = file_exists(DATA_PATH . '/settings.json') && filesize(DATA_PATH . '/settings.json') > 100;
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تنصيب النظام - <?= htmlspecialchars(get_setting('site_name')) ?></title>
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Tajawal','Cairo',sans-serif;background:#f0f2f5;text-align:right;font-size:14px;color:#333;padding:40px 20px}
        .setup-box{max-width:600px;margin:0 auto;background:#fff;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,0.1);padding:30px}
        h1{font-size:22px;color:#157039;margin-bottom:5px;text-align:center}
        .subtitle{text-align:center;color:#888;font-size:13px;margin-bottom:20px}
        .check-list{list-style:none;margin:15px 0;padding:0}
        .check-list li{padding:8px 0;border-bottom:1px solid #eee;font-size:13px}
        .check-list li .check{color:#157039;font-weight:700}
        .check-list li .cross{color:#c0392b;font-weight:700}
        .form-group{margin-bottom:12px}
        label{display:block;font-weight:600;margin-bottom:4px;font-size:13px;color:#555}
        input{width:100%;padding:8px 12px;border:1px solid #ddd;border-radius:4px;font-size:14px;font-family:inherit}
        input:focus{outline:none;border-color:#157039}
        .btn{display:inline-block;padding:10px 24px;background:#157039;color:#fff;border:none;border-radius:4px;font-size:14px;font-weight:600;cursor:pointer;width:100%}
        .btn:hover{background:#0f5a2d}
        .alert{padding:12px;border-radius:4px;margin:12px 0;font-size:13px}
        .alert-success{background:#d4edda;color:#155724;border:1px solid #c3e6cb}
        .alert-danger{background:#f8d7da;color:#721c24;border:1px solid #f5c6cb}
        .notes{margin-top:15px;font-size:12px;color:#888;text-align:center}
    </style>
</head>
<body>
    <div class="setup-box">
        <h1>🌐 تنصيب بوابة جامعة المنوفية</h1>
        <p class="subtitle">التحقق من النظام وإعداد المستخدم الأول</p>

        <?= $message ?>

        <ul class="check-list">
            <li><span class="<?= $data_writable ? 'check' : 'cross' ?>"><?= $data_writable ? '✓' : '✗' ?></span> صلاحية الكتابة لمجلد data/</li>
            <li><span class="<?= $articles_ok ? 'check' : 'cross' ?>"><?= $articles_ok ? '✓' : '✗' ?></span> ملف المقالات (articles.json) — <?= $articles_ok ? 'موجود' : 'غير موجود أو فارغ' ?></li>
            <li><span class="<?= $settings_ok ? 'check' : 'cross' ?>"><?= $settings_ok ? '✓' : '✗' ?></span> ملف الإعدادات (settings.json) — <?= $settings_ok ? 'موجود' : 'غير موجود أو فارغ' ?></li>
            <li><span class="<?= $has_users ? 'check' : 'cross' ?>"><?= $has_users ? '✓' : '✗' ?></span> ملف المستخدمين (users.json) — <?= $has_users ? 'موجود' : 'غير موجود أو فارغ — يجب إنشاء مستخدم' ?></li>
        </ul>

        <?php if (!$has_users): ?>
        <form method="post" style="margin-top:15px">
            <input type="hidden" name="setup" value="1">
            <div class="form-group">
                <label>اسم المستخدم</label>
                <input type="text" name="username" value="admin" required>
            </div>
            <div class="form-group">
                <label>كلمة المرور</label>
                <input type="text" name="password" value="admin123" required>
            </div>
            <div class="form-group">
                <label>الاسم المعروض</label>
                <input type="text" name="display_name" value="مدير الموقع" required>
            </div>
            <button type="submit" class="btn">إنشاء المستخدم</button>
        </form>
        <?php else: ?>
        <p style="text-align:center;color:#157039;font-weight:700;margin-top:10px;">✅ النظام جاهز. <a href="admin/index.php">تسجيل الدخول إلى لوحة التحكم</a></p>
        <?php endif; ?>

        <div class="notes">
            <p>📌 بعد الانتهاء، احذف ملف setup.php لأمان موقعك.</p>
        </div>
    </div>
</body>
</html>
