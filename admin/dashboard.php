<?php
session_start();
require_once __DIR__ . '/../includes/config.php';

if (!isset($_SESSION['admin'])) {
    header('Location: index.php');
    exit;
}

$articles_file = DATA_PATH . '/articles.json';
$articles = [];
if (file_exists($articles_file)) {
    $articles = json_decode(file_get_contents($articles_file), true);
    if (json_last_error() !== JSON_ERROR_NONE) $articles = [];
}

$total_articles = count($articles);
$sections = [
    'أخبار المنوفية' => 0,
    'منشآت الجامعة' => 0,
    'ندوات ومؤتمرات' => 0,
    'تكريم ومسابقات' => 0,
    'الفن والمسابقات' => 0,
];
foreach ($articles as $a) {
    $sec = $a['section'] ?? '';
    if (isset($sections[$sec])) $sections[$sec]++;
}

$recent_articles = array_slice(array_reverse($articles), 0, 5);

$msgsFile = __DIR__ . '/../data/messages.json';
$unreadMsgs = 0;
if (file_exists($msgsFile)) {
    $allMsgs = json_decode(file_get_contents($msgsFile), true);
    if (is_array($allMsgs)) {
        foreach ($allMsgs as $m) { if (empty($m['read'])) $unreadMsgs++; }
    }
}

$admin = $_SESSION['admin'];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحكم - <?php echo htmlspecialchars(get_setting('site_name', 'الأهرام')); ?></title>
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
            <a href="dashboard.php" class="nav-item active"><i class="fas fa-tachometer-alt"></i> الرئيسية</a>
            <a href="articles.php" class="nav-item"><i class="fas fa-newspaper"></i> المقالات</a>
            <a href="article-add.php" class="nav-item"><i class="fas fa-plus-circle"></i> إضافة مقال</a>
            <a href="messages.php" class="nav-item"><i class="fas fa-envelope"></i> الرسائل<?php if ($unreadMsgs > 0): ?> <span style="background:#e74c3c;color:#fff;border-radius:10px;padding:1px 8px;font-size:11px;margin-right:4px"><?= $unreadMsgs ?></span><?php endif; ?></a>

            <a href="sections.php" class="nav-item"><i class="fas fa-layer-group"></i> الأقسام</a>
            <a href="settings.php" class="nav-item"><i class="fas fa-cogs"></i> الإعدادات</a>
            <a href="logout.php" class="nav-item"><i class="fas fa-sign-out-alt"></i> تسجيل الخروج</a>
        </div>
        <div class="main-content">
            <div class="top-bar-admin">
                <div class="page-title">الرئيسية</div>
                <div class="user-info">مرحباً، <?php echo htmlspecialchars($admin['display_name'] ?? $admin['username']); ?></div>
            </div>

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:15px;margin-bottom:20px">
                <div class="stat-card stat-green">
                    <div class="stat-number"><?php echo $total_articles; ?></div>
                    <div class="stat-label">إجمالي المقالات</div>
                </div>
                <div class="stat-card stat-blue">
                    <div class="stat-number"><?php echo $sections['أخبار المنوفية']; ?></div>
                    <div class="stat-label">أخبار المنوفية</div>
                </div>
                <div class="stat-card" style="background:#27ae60">
                    <div class="stat-number"><?php echo $sections['منشآت الجامعة']; ?></div>
                    <div class="stat-label">منشآت الجامعة</div>
                </div>
                <div class="stat-card stat-orange">
                    <div class="stat-number"><?php echo $sections['ندوات ومؤتمرات']; ?></div>
                    <div class="stat-label">ندوات ومؤتمرات</div>
                </div>
                <div class="stat-card" style="background:#8e44ad">
                    <div class="stat-number"><?php echo $sections['تكريم ومسابقات']; ?></div>
                    <div class="stat-label">تكريم ومسابقات</div>
                </div>
                <div class="stat-card stat-red">
                    <div class="stat-number"><?php echo $sections['الفن والمسابقات']; ?></div>
                    <div class="stat-label">الفن والمسابقات</div>
                </div>
                <div class="stat-card" style="background:#e74c3c">
                    <div class="stat-number"><?php echo $unreadMsgs; ?></div>
                    <div class="stat-label">رسائل غير مقروءة</div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">أحدث المقالات</div>
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>العنوان</th>
                                <th>القسم</th>
                                <th>التاريخ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_articles as $a): ?>
                            <tr>
                                <td><?php echo $a['id']; ?></td>
                                <td><?php echo htmlspecialchars(mb_substr($a['title'], 0, 60)) . (mb_strlen($a['title']) > 60 ? '...' : ''); ?></td>
                                <td><?php echo htmlspecialchars($a['section']); ?></td>
                                <td><?php echo htmlspecialchars($a['date']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($recent_articles)): ?>
                            <tr><td colspan="4" style="text-align:center;color:#999">لا توجد مقالات</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div style="display:flex;gap:10px;flex-wrap:wrap">
                <a href="article-add.php" class="btn btn-primary"><i class="fas fa-plus"></i> إضافة مقال جديد</a>
                <a href="articles.php" class="btn btn-secondary"><i class="fas fa-list"></i> عرض كل المقالات</a>
                <a href="settings.php" class="btn btn-warning"><i class="fas fa-cogs"></i> الإعدادات</a>
            </div>
        </div>
    </div>
</body>
</html>
