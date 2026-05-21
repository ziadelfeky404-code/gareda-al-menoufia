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

$sections = [
    'أخبار المنوفية', 'منشآت الجامعة', 'ندوات ومؤتمرات',
    'تكريم ومسابقات', 'الفن والمسابقات',
    'رياضة ومسابقات', 'قيادات جامعية', 'تقارير'
];

$selected_section = $_GET['section'] ?? '';
if ($selected_section) {
    $filtered = [];
    foreach ($articles as $a) {
        if ($a['section'] === $selected_section) $filtered[] = $a;
    }
    $articles = $filtered;
}

$per_page = 20;
$page = max(1, intval($_GET['p'] ?? 1));
$total = count($articles);
$total_pages = max(1, ceil($total / $per_page));
$offset = ($page - 1) * $per_page;
$page_articles = array_slice($articles, $offset, $per_page);

$admin = $_SESSION['admin'];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>المقالات - <?php echo htmlspecialchars(get_setting('site_name', 'الأهرام')); ?></title>
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
            <a href="articles.php" class="nav-item active"><i class="fas fa-newspaper"></i> المقالات</a>
            <a href="article-add.php" class="nav-item"><i class="fas fa-plus-circle"></i> إضافة مقال</a>
            
            <a href="ads.php" class="nav-item"><i class="fas fa-ad"></i> الإعلانات</a>
            <a href="sections.php" class="nav-item"><i class="fas fa-layer-group"></i> الأقسام</a>
            <a href="settings.php" class="nav-item"><i class="fas fa-cogs"></i> الإعدادات</a>
            <a href="logout.php" class="nav-item"><i class="fas fa-sign-out-alt"></i> تسجيل الخروج</a>
        </div>
        <div class="main-content">
            <div class="top-bar-admin">
                <div class="page-title">المقالات</div>
                <div class="user-info">مرحباً، <?php echo htmlspecialchars($admin['display_name'] ?? $admin['username']); ?></div>
            </div>

            <div class="card">
                <div class="card-header">تصفية حسب القسم</div>
                <div class="card-body">
                    <form method="get" style="display:flex;gap:10px;align-items:center">
                        <select name="section" class="form-control" style="width:250px">
                            <option value="">جميع الأقسام</option>
                            <?php foreach ($sections as $s): ?>
                            <option value="<?php echo htmlspecialchars($s); ?>" <?php echo $selected_section === $s ? 'selected' : ''; ?>><?php echo htmlspecialchars($s); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-primary btn-sm">تصفية</button>
                        <?php if ($selected_section): ?>
                        <a href="articles.php" class="btn btn-secondary btn-sm">إلغاء التصفية</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
                    <span>قائمة المقالات (<?php echo $total; ?>)</span>
                    <a href="article-add.php" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> إضافة</a>
                </div>
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>العنوان</th>
                                <th>القسم</th>
                                <th>التاريخ</th>
                                <th>إجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($page_articles as $a): ?>
                            <tr>
                                <td><?php echo $a['id']; ?></td>
                                <td><?php echo htmlspecialchars(mb_substr($a['title'], 0, 60)) . (mb_strlen($a['title']) > 60 ? '...' : ''); ?></td>
                                <td><?php echo htmlspecialchars($a['section']); ?></td>
                                <td><?php echo htmlspecialchars($a['date']); ?></td>
                                <td>
                                    <a href="../article.php?id=<?php echo $a['id']; ?>" class="btn btn-sm" style="background:#3498db;color:#fff" title="عرض"><i class="fas fa-eye"></i></a>
                                    <a href="article-edit.php?id=<?php echo $a['id']; ?>" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i> تعديل</a>
                                    <button onclick="confirmDelete(<?php echo $a['id']; ?>, '<?php echo htmlspecialchars(addslashes($a['title']), ENT_QUOTES); ?>')" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i> حذف</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($page_articles)): ?>
                            <tr><td colspan="5" style="text-align:center;color:#999">لا توجد مقالات</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>

                    <?php if ($total_pages > 1): ?>
                    <div style="display:flex;justify-content:center;gap:5px;margin-top:20px">
                        <?php if ($page > 1): ?>
                        <a href="?<?php echo $selected_section ? 'section=' . urlencode($selected_section) . '&' : ''; ?>p=<?php echo $page - 1; ?>" class="btn btn-secondary btn-sm">السابق</a>
                        <?php endif; ?>
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?<?php echo $selected_section ? 'section=' . urlencode($selected_section) . '&' : ''; ?>p=<?php echo $i; ?>" class="btn btn-sm <?php echo $i === $page ? 'btn-primary' : 'btn-secondary'; ?>"><?php echo $i; ?></a>
                        <?php endfor; ?>
                        <?php if ($page < $total_pages): ?>
                        <a href="?<?php echo $selected_section ? 'section=' . urlencode($selected_section) . '&' : ''; ?>p=<?php echo $page + 1; ?>" class="btn btn-secondary btn-sm">التالي</a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
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

function confirmDelete(id, title) {
    deleteId = id;
    document.getElementById('modalArticleTitle').textContent = '"' + title + '"';
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
        window.location.href = 'article-delete.php?id=' + deleteId + '&redirect=' + encodeURIComponent(window.location.href);
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
