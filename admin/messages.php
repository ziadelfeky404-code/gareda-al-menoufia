<?php
session_start();
require_once __DIR__ . '/../includes/config.php';

if (!isset($_SESSION['admin'])) {
    header('Location: index.php');
    exit;
}

$msgsFile = __DIR__ . '/../data/messages.json';
$msgs = [];
if (file_exists($msgsFile)) {
    $msgs = json_decode(file_get_contents($msgsFile), true);
    if (json_last_error() !== JSON_ERROR_NONE) $msgs = [];
}

$msgs = array_reverse($msgs);

if (isset($_GET['mark'])) {
    $markId = $_GET['mark'];
    $all = json_decode(file_get_contents($msgsFile), true);
    foreach ($all as &$m) {
        if ($m['id'] == $markId) $m['read'] = true;
    }
    v_put_data($msgsFile, json_encode($all, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    header('Location: messages.php');
    exit;
}

if (isset($_GET['delete'])) {
    $delId = $_GET['delete'];
    $all = json_decode(file_get_contents($msgsFile), true);
    $new = [];
    foreach ($all as $m) {
        if ($m['id'] != $delId) $new[] = $m;
    }
    v_put_data($msgsFile, json_encode($new, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    header('Location: messages.php');
    exit;
}

$admin = $_SESSION['admin'];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الرسائل - <?php echo htmlspecialchars(get_setting('site_name', 'الأهرام')); ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .msg-card{background:#fff;border-radius:8px;box-shadow:0 1px 3px rgba(0,0,0,0.08);margin-bottom:15px;padding:18px 20px;border-right:4px solid #157039}
        .msg-card.unread{border-right-color:#e74c3c;background:#fff8f8}
        .msg-card .msg-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;flex-wrap:wrap;gap:8px}
        .msg-card .msg-name{font-size:16px;font-weight:800;color:var(--dark)}
        .msg-card .msg-date{font-size:12px;color:#999;font-weight:400}
        .msg-card .msg-email{font-size:13px;color:#157039;font-weight:700;margin-bottom:6px}
        .msg-card .msg-body{font-size:14px;color:#555;font-weight:400;line-height:1.7;background:#f9f9f9;padding:12px 15px;border-radius:6px;margin-top:8px}
        .msg-card .msg-actions{display:flex;gap:8px;margin-top:10px}
        .msg-card .badge-unread{background:#e74c3c;color:#fff;font-size:11px;padding:2px 10px;border-radius:3px;font-weight:700}
        .empty-state{text-align:center;padding:60px 20px;color:#999}
        .empty-state i{font-size:60px;color:#ddd;margin-bottom:15px}
        .empty-state p{font-size:16px;font-weight:400}
    </style>
</head>
<body>

<div class="topbar">
    <h2><i class="fas fa-envelope"></i> الرسائل</h2>
    <div>
        <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> لوحة التحكم</a>
        <span style="margin:0 8px;opacity:0.5">|</span>
        <a href="../index.php"><i class="fas fa-home"></i> العودة للموقع</a>
    </div>
</div>

<div class="wrap" style="max-width:900px;margin:20px auto;padding:0 15px">

    <div style="background:#fff;border-radius:8px;box-shadow:0 1px 4px rgba(0,0,0,0.08);padding:20px;margin-bottom:20px">
        <div style="font-size:16px;font-weight:800;color:#157039;margin-bottom:5px"><i class="fas fa-inbox"></i> رسائل الزوار</div>
        <div style="font-size:13px;color:#888;font-weight:400">إجمالي الرسائل: <?php echo count($msgs); ?></div>
    </div>

    <?php if (empty($msgs)): ?>
    <div class="empty-state">
        <i class="fas fa-envelope-open-text"></i>
        <p>لا توجد رسائل بعد</p>
    </div>
    <?php endif; ?>

    <?php foreach ($msgs as $m): ?>
    <div class="msg-card<?php echo empty($m['read']) ? ' unread' : ''; ?>">
        <div class="msg-header">
            <div>
                <span class="msg-name"><?php echo htmlspecialchars($m['name']); ?></span>
                <?php if (empty($m['read'])): ?>
                <span class="badge-unread">جديد</span>
                <?php endif; ?>
            </div>
            <span class="msg-date"><?php echo htmlspecialchars($m['date']); ?></span>
        </div>
        <div class="msg-email"><i class="fas fa-envelope ms-1"></i><?php echo htmlspecialchars($m['email']); ?></div>
        <div class="msg-body"><?php echo nl2br(htmlspecialchars($m['message'])); ?></div>
        <div class="msg-actions">
            <?php if (empty($m['read'])): ?>
            <a href="?mark=<?php echo urlencode($m['id']); ?>" class="btn btn-secondary" style="font-size:12px;padding:4px 14px;text-decoration:none"><i class="fas fa-check"></i> حدد كمقروء</a>
            <?php endif; ?>
            <a href="?delete=<?php echo urlencode($m['id']); ?>" onclick="return confirm('حذف الرسالة؟')" class="btn btn-danger-sm" style="font-size:12px;padding:4px 14px;text-decoration:none"><i class="fas fa-trash"></i> حذف</a>
        </div>
    </div>
    <?php endforeach; ?>

</div>

</body>
</html>
