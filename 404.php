<?php
require_once 'includes/config.php';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>404 - الصفحة غير موجودة</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&family=Tajawal:wght@400;500;700;800;900&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Tajawal','Cairo',sans-serif;text-align:right;background:#f8f9fa;min-height:100vh;display:flex;align-items:center;justify-content:center}
.error-wrap{text-align:center;padding:40px 20px;max-width:500px}
.error-wrap .error-code{font-size:120px;font-weight:900;color:#157039;line-height:1;margin-bottom:10px}
.error-wrap h1{font-size:24px;font-weight:800;color:#333;margin-bottom:10px}
.error-wrap p{font-size:15px;color:#888;font-weight:400;margin-bottom:25px;line-height:1.7}
.error-wrap .btn-home{display:inline-block;padding:12px 35px;background:#157039;color:#fff;border-radius:6px;font-size:15px;font-weight:700;text-decoration:none;transition:background 0.2s}
.error-wrap .btn-home:hover{background:#0f5a2d}
.error-wrap .btn-home i{margin-left:8px}
</style>
</head>
<body>
<div class="error-wrap">
    <div class="error-code">404</div>
    <h1>عذراً، الصفحة غير موجودة</h1>
    <p>الصفحة التي تبحث عنها قد تكون محذوفة أو تم تغيير رابطها. يمكنك العودة إلى الصفحة الرئيسية.</p>
    <a href="index.php" class="btn-home"><i class="fas fa-home"></i> العودة للرئيسية</a>
</div>
</body>
</html>
