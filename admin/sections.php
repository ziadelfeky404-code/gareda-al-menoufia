<?php
session_start();
require_once __DIR__ . '/../includes/config.php';

if (!isset($_SESSION['admin'])) {
    header('Location: index.php');
    exit;
}

$message = '';
$error = '';

$sectionsData = get_sections();

// Add section
if (isset($_POST['add_section'])) {
    $name = trim($_POST['name'] ?? '');
    if (!$name) {
        $error = 'اسم القسم مطلوب';
    } else {
        foreach ($sectionsData as $s) {
            if ($s['name'] === $name) {
                $error = 'القسم موجود بالفعل';
                break;
            }
        }
        if (!$error) {
            $slug = trim($_POST['slug'] ?? '');
            if (!$slug) {
                $slug = 'sec_' . bin2hex(random_bytes(3));
            }
            $file = trim($_POST['file'] ?? '');
            if (!$file) $file = $name . '.htm';

            $sectionsData[] = ['name' => $name, 'slug' => $slug, 'file' => $file];
            save_sections($sectionsData);
            $message = 'تم إضافة القسم "' . htmlspecialchars($name) . '" بنجاح';
        }
    }
}

// Delete section
if (isset($_GET['delete'])) {
    $delIdx = (int)$_GET['delete'];
    if (isset($sectionsData[$delIdx])) {
        $deleted = $sectionsData[$delIdx];
        array_splice($sectionsData, $delIdx, 1);
        save_sections($sectionsData);
        $message = 'تم حذف القسم "' . htmlspecialchars($deleted['name']) . '"';
    }
}

// Undo delete
if (isset($_GET['undo'])) {
    header('Location: sections.php');
    exit;
}

$admin = $_SESSION['admin'];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الأقسام - <?php echo htmlspecialchars(get_setting('site_name', 'الأهرام')); ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .sec-card{background:#fff;border-radius:8px;box-shadow:0 1px 3px rgba(0,0,0,0.06);margin-bottom:12px;padding:16px 20px;display:flex;align-items:center;justify-content:space-between;transition:all .2s}
        .sec-card:hover{box-shadow:0 2px 8px rgba(0,0,0,0.1)}
        .sec-card .sec-name{font-size:16px;font-weight:800;color:#333}
        .sec-card .sec-info{font-size:12px;color:#999;font-weight:400;margin-top:2px}
        .sec-card .sec-info span{margin-left:12px}
        .sec-card .sec-info code{background:#f0f0f0;padding:1px 6px;border-radius:3px;font-size:11px;color:#666}
        .btn-del{background:#c0392b;color:#fff;border:none;padding:6px 16px;border-radius:4px;font-size:12px;font-weight:700;cursor:pointer;transition:background .2s;text-decoration:none;display:inline-flex;align-items:center;gap:4px}
        .btn-del:hover{background:#a93226;color:#fff;text-decoration:none}
        .empty-state{text-align:center;padding:40px;color:#999}
        .empty-state i{font-size:40px;color:#ddd;display:block;margin-bottom:10px}
        .form-inline{display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end}
        .form-inline .form-group{margin-bottom:0;flex:1;min-width:150px}
        .form-inline .form-group label{font-size:12px;font-weight:700;color:#555;display:block;margin-bottom:3px}
        .form-inline .form-group input{width:100%;padding:8px 12px;border:1px solid #ddd;border-radius:4px;font-size:13px;font-family:inherit}
        .form-inline .form-group input:focus{outline:none;border-color:#157039}
        .btn-add{background:#157039;color:#fff;border:none;padding:8px 20px;border-radius:4px;font-size:13px;font-weight:800;cursor:pointer;white-space:nowrap;height:36px;display:inline-flex;align-items:center;gap:5px}
        .btn-add:hover{background:#0f5a2d}
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
            
            <a href="ads.php" class="nav-item"><i class="fas fa-ad"></i> الإعلانات</a>
            <a href="sections.php" class="nav-item active"><i class="fas fa-layer-group"></i> الأقسام</a>
            <a href="settings.php" class="nav-item"><i class="fas fa-cogs"></i> الإعدادات</a>
            <a href="logout.php" class="nav-item"><i class="fas fa-sign-out-alt"></i> تسجيل الخروج</a>
        </div>
        <div class="main-content">
            <div class="top-bar-admin">
                <div class="page-title">إدارة الأقسام</div>
                <div class="user-info">مرحباً، <?php echo htmlspecialchars($admin['display_name'] ?? $admin['username']); ?></div>
            </div>

            <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <!-- Add Section -->
            <div class="card">
                <div class="card-header"><i class="fas fa-plus-circle"></i> إضافة قسم جديد</div>
                <div class="card-body">
                    <form method="post" class="form-inline">
                        <div class="form-group">
                            <label>اسم القسم</label>
                            <input type="text" name="name" required placeholder="مثال: أخبار كلية الطب">
                        </div>
                        <div class="form-group">
                            <label>الرابط المختصر (slug)</label>
                            <input type="text" name="slug" placeholder="اختياري — auto">
                        </div>
                        <div class="form-group">
                            <label>اسم الملف</label>
                            <input type="text" name="file" placeholder="اختياري — auto">
                        </div>
                        <button type="submit" name="add_section" class="btn-add"><i class="fas fa-plus"></i> إضافة</button>
                    </form>
                </div>
            </div>

            <!-- Sections List -->
            <div class="card">
                <div class="card-header"><i class="fas fa-list"></i> الأقسام الحالية (<?php echo count($sectionsData); ?>)</div>
                <div class="card-body">
                    <?php if (empty($sectionsData)): ?>
                    <div class="empty-state">
                        <i class="fas fa-layer-group"></i>
                        <p>لا توجد أقسام. أضف القسم الأول أعلاه.</p>
                    </div>
                    <?php else: ?>
                    <?php foreach ($sectionsData as $i => $sec): ?>
                    <div class="sec-card">
                        <div>
                            <div class="sec-name"><?php echo htmlspecialchars($sec['name']); ?></div>
                            <div class="sec-info">
                                <span><i class="fas fa-link"></i> <code><?php echo htmlspecialchars($sec['slug']); ?></code></span>
                                <span><i class="fas fa-file"></i> <?php echo htmlspecialchars($sec['file']); ?></span>
                                <span><i class="fas fa-newspaper"></i> <?php echo count(get_articles_by_section($sec['name'])); ?> مقال</span>
                            </div>
                        </div>
                        <button type="button" class="btn-del" onclick="confirmDelete(<?php echo $i; ?>, '<?php echo htmlspecialchars($sec['name'], ENT_QUOTES); ?>')">
                            <i class="fas fa-trash"></i> حذف
                        </button>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);z-index:99999;align-items:center;justify-content:center">
        <div style="background:#fff;border-radius:12px;padding:30px;max-width:440px;width:90%;box-shadow:0 20px 60px rgba(0,0,0,0.3);text-align:center;position:relative;overflow:hidden">
            <div id="modalProgressBar" style="position:absolute;top:0;left:0;height:4px;background:#e74c3c;width:0%;transition:width 0.1s linear;border-radius:12px 12px 0 0"></div>

            <div style="margin:15px 0 10px">
                <div style="width:60px;height:60px;border-radius:50%;background:#fde8e8;display:flex;align-items:center;justify-content:center;margin:0 auto">
                    <i class="fas fa-exclamation-triangle" style="font-size:28px;color:#e74c3c"></i>
                </div>
            </div>
            <h3 style="font-size:18px;font-weight:800;color:#333;margin-bottom:6px">حذف القسم</h3>
            <p style="font-size:14px;color:#666;font-weight:400;margin-bottom:4px">هل أنت متأكد من حذف القسم</p>
            <p id="modalSectionName" style="font-size:16px;font-weight:800;color:#c0392b;margin-bottom:15px"></p>
            <p style="font-size:12px;color:#999;font-weight:400;margin-bottom:15px;background:#fafafa;padding:8px 12px;border-radius:6px">
                <i class="fas fa-info-circle"></i> جميع المقالات في هذا القسم ستبقى لكن القسم لن يظهر في الموقع
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
    var deleteIndex = -1;
    var countdownInterval = null;

    function confirmDelete(idx, name) {
        deleteIndex = idx;
        document.getElementById('modalSectionName').textContent = '"' + name + '"';
        document.getElementById('deleteModal').style.display = 'flex';
        document.getElementById('modalProgressBar').style.width = '0%';

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
            document.getElementById('modalProgressBar').style.width = pct + '%';
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
        if (deleteIndex >= 0) {
            window.location.href = '?delete=' + deleteIndex;
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
