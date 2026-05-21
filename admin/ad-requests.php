<?php
session_start();
require_once __DIR__ . '/../includes/config.php';

if (!isset($_SESSION['admin'])) {
    header('Location: index.php');
    exit;
}

$req_file = DATA_PATH . '/ad_requests.json';
$requests = [];
if (file_exists($req_file)) {
    $requests = json_decode(file_get_contents($req_file), true) ?: [];
}

// Mark as read
if (isset($_GET['read'])) {
    $rid = $_GET['read'];
    foreach ($requests as &$r) {
        if ($r['id'] == $rid) $r['status'] = 'read';
    }
    file_put_contents($req_file, json_encode($requests, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    header('Location: ad-requests.php');
    exit;
}

// Delete
if (isset($_GET['delete'])) {
    $did = $_GET['delete'];
    $requests = array_filter($requests, function($r) use ($did) { return $r['id'] != $did; });
    $requests = array_values($requests);
    file_put_contents($req_file, json_encode($requests, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    header('Location: ad-requests.php');
    exit;
}

$admin = $_SESSION['admin'];
$unread = count(array_filter($requests, function($r) { return $r['status'] === 'new'; }));
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>طلبات الإعلانات - <?php echo htmlspecialchars(get_setting('site_name', 'الأهرام')); ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <div class="admin-container">
        <?php include __DIR__ . '/sidebar.php'; ?>
        <div class="main-content">
            <div class="top-bar-admin">
                <div class="page-title">طلبات الإعلانات <?php if ($unread > 0): ?><span style="background:#e74c3c;color:#fff;padding:2px 10px;border-radius:10px;font-size:12px"><?php echo $unread; ?> جديد</span><?php endif; ?></div>
                <div class="user-info">مرحباً، <?php echo htmlspecialchars($admin['display_name'] ?? $admin['username']); ?></div>
            </div>

            <div class="card">
                <div class="card-header"><i class="fas fa-bullhorn"></i> طلبات نشر الإعلانات</div>
                <div class="card-body">
                    <?php if (empty($requests)): ?>
                    <div class="empty-state"><i class="fas fa-inbox"></i><p style="margin-top:10px">لا توجد طلبات إعلانات.</p></div>
                    <?php else: ?>
                    <div style="overflow-x:auto">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>التاريخ</th>
                                    <th>الاسم</th>
                                    <th>البريد</th>
                                    <th>الهاتف</th>
                                    <th>الموضع</th>
                                    <th>المدة</th>
                                    <th>ملاحظات</th>
                                    <th>الحالة</th>
                                    <th>إجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_reverse($requests) as $r): ?>
                                <tr style="<?php echo $r['status'] === 'new' ? 'background:#fff8f0;font-weight:700' : ''; ?>">
                                    <td style="font-size:12px"><?php echo htmlspecialchars($r['date']); ?></td>
                                    <td><?php echo htmlspecialchars($r['name']); ?></td>
                                    <td style="font-size:12px;direction:ltr;text-align:left"><?php echo htmlspecialchars($r['email']); ?></td>
                                    <td style="font-size:12px"><?php echo htmlspecialchars($r['phone']); ?></td>
                                    <td style="font-size:12px"><?php echo htmlspecialchars($r['position_label'] ?? $r['position']); ?></td>
                                    <td style="font-size:12px"><?php echo (int)$r['duration']; ?> شهر</td>
                                    <td style="font-size:12px;max-width:150px"><?php echo htmlspecialchars($r['notes'] ?? ''); ?></td>
                                    <td><?php echo $r['status'] === 'new' ? '<span style="color:#e67e22;font-weight:700">جديد</span>' : '<span style="color:#888">تمت القراءة</span>'; ?></td>
                                    <td>
                                        <?php if ($r['status'] === 'new'): ?>
                                        <a href="?read=<?php echo $r['id']; ?>" class="btn-edit btn-edit-sm" title="تحديد كمقروء"><i class="fas fa-check"></i></a>
                                        <?php endif; ?>
                                        <a href="?delete=<?php echo $r['id']; ?>" class="btn-danger-sm" onclick="return confirm('حذف هذا الطلب؟')" title="حذف"><i class="fas fa-trash"></i></a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
