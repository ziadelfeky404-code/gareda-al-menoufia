<?php
session_start();
require_once __DIR__ . '/../includes/config.php';

if (isset($_SESSION['admin'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$logo = get_setting('logo_url', '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $users_file = DATA_PATH . '/users.json';
    if (file_exists($users_file)) {
        $users = json_decode(file_get_contents($users_file), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $error = 'خطأ في قراءة بيانات المستخدمين';
        } else {
            foreach ($users as $user) {
                if ($user['username'] === $username && password_verify($password, $user['password_hash'])) {
                    $_SESSION['admin'] = $user;
                    header('Location: dashboard.php');
                    exit;
                }
            }
            $error = 'اسم المستخدم أو كلمة المرور غير صحيحة';
        }
    } else {
        $error = 'ملف المستخدمين غير موجود';
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول - <?php echo htmlspecialchars(get_setting('site_name', 'لوحة التحكم')); ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="login-wrapper">
        <div class="login-box">
            <?php if ($logo): ?>
                <img src="<?php echo htmlspecialchars($logo); ?>" alt="شعار الموقع" class="login-logo">
            <?php endif; ?>
            <h3>لوحة التحكم</h3>
            <p>تسجيل الدخول لإدارة المحتوى</p>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <form method="post">
                <div class="form-group">
                    <input type="text" name="username" class="form-control" placeholder="اسم المستخدم" required>
                </div>
                <div class="form-group">
                    <input type="password" name="password" class="form-control" placeholder="كلمة المرور" required>
                </div>
                <button type="submit" class="btn btn-primary">دخول</button>
            </form>
        </div>
    </div>
</body>
</html>
