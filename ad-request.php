<?php
session_start();
require_once 'includes/functions.php';

$success = '';
$error = '';
$position = $_GET['pos'] ?? '';

$ad_positions = [
    'navbar'        => "شريط الإعلانات العلوي (بجانب الشعار)",
    'after_hero'    => "بعد القسم الرئيسي (الهيرو)",
    'between_sections' => "بين الأقسام (بعد منشآت الجامعة)",
    'sidebar_top'   => "الشريط الجانبي - أعلى",
    'sidebar_bottom' => "الشريط الجانبي - أسفل",
    'section_page'  => "صفحة القسم - أسفل المقالات",
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $company  = trim($_POST['company'] ?? '');
    $position = trim($_POST['position'] ?? '');
    $duration = (int)($_POST['duration'] ?? 1);
    $notes    = trim($_POST['notes'] ?? '');
    $agree    = isset($_POST['agree']);

    if (!$name || !$email || !$phone || !$position || !$agree) {
        $error = "يرجى ملء جميع الحقول المطلوبة والموافقة على الشروط.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "البريد الإلكتروني غير صالح.";
    } else {
        $requests = [];
        $req_file = DATA_PATH . '/ad_requests.json';
        if (file_exists($req_file)) {
            $requests = json_decode(file_get_contents($req_file), true) ?: [];
        }
        $requests[] = [
            'id'       => time() . rand(100, 999),
            'name'     => $name,
            'email'    => $email,
            'phone'    => $phone,
            'company'  => $company,
            'position' => $position,
            'position_label' => $ad_positions[$position] ?? $position,
            'duration' => $duration,
            'notes'    => $notes,
            'date'     => date('Y-m-d H:i:s'),
            'status'   => 'new',
        ];
        file_put_contents($req_file, json_encode($requests, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        $success = "تم إرسال طلبك بنجاح. سنتواصل معك قريباً.";
    }
}

$page_title = "طلب إعلان - " . get_setting('site_name', 'الأهرام');
render_header($page_title);
render_topbar();
render_navbar();
?>
<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-7">
                <div style="background:#fff;border-radius:12px;box-shadow:0 2px 20px rgba(0,0,0,0.08);padding:40px">
                    <div style="text-align:center;margin-bottom:30px">
                        <div style="width:70px;height:70px;border-radius:50%;background:var(--red);display:flex;align-items:center;justify-content:center;margin:0 auto 15px">
                            <i class="fas fa-bullhorn" style="font-size:30px;color:#fff"></i>
                        </div>
                        <h2 style="font-size:24px;font-weight:900;color:var(--dark);margin-bottom:5px">طلب نشر إعلان</h2>
                        <p style="font-size:14px;color:#888;font-weight:400">املأ النموذج التالي وسنقوم بالتواصل معك لمناقشة التفاصيل</p>
                    </div>

                    <?php if ($success): ?>
                    <div style="background:#d4edda;color:#155724;padding:15px 20px;border-radius:8px;font-size:14px;font-weight:500;text-align:center;margin-bottom:20px">
                        <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
                    </div>
                    <?php endif; ?>
                    <?php if ($error): ?>
                    <div style="background:#f8d7da;color:#721c24;padding:15px 20px;border-radius:8px;font-size:14px;font-weight:500;text-align:center;margin-bottom:20px">
                        <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                    </div>
                    <?php endif; ?>

                    <form method="post">
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:15px">
                            <div>
                                <label style="font-size:13px;font-weight:700;color:#555">الاسم <span style="color:red">*</span></label>
                                <input type="text" name="name" required style="width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:6px;font-size:14px;font-family:var(--fonts)" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                            </div>
                            <div>
                                <label style="font-size:13px;font-weight:700;color:#555">البريد الإلكتروني <span style="color:red">*</span></label>
                                <input type="email" name="email" required style="width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:6px;font-size:14px;font-family:var(--fonts)" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                            </div>
                            <div>
                                <label style="font-size:13px;font-weight:700;color:#555">رقم الهاتف <span style="color:red">*</span></label>
                                <input type="text" name="phone" required style="width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:6px;font-size:14px;font-family:var(--fonts)" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                            </div>
                            <div>
                                <label style="font-size:13px;font-weight:700;color:#555">الشركة / المؤسسة</label>
                                <input type="text" name="company" style="width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:6px;font-size:14px;font-family:var(--fonts)" value="<?= htmlspecialchars($_POST['company'] ?? '') ?>">
                            </div>
                        </div>

                        <div style="margin-top:15px">
                            <label style="font-size:13px;font-weight:700;color:#555;display:block;margin-bottom:5px">الموضع الإعلاني <span style="color:red">*</span></label>
                            <select name="position" required style="width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:6px;font-size:14px;font-family:var(--fonts)">
                                <option value="">-- اختر الموضع --</option>
                                <?php foreach ($ad_positions as $key => $label): ?>
                                <option value="<?= htmlspecialchars($key) ?>" <?= $position === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div style="margin-top:15px">
                            <label style="font-size:13px;font-weight:700;color:#555;display:block;margin-bottom:5px">مدة الإعلان (بالأشهر)</label>
                            <select name="duration" style="width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:6px;font-size:14px;font-family:var(--fonts)">
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                <option value="<?= $m ?>"><?= $m ?> شهر<?= $m > 1 ? "اً" : "" ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <div style="margin-top:15px">
                            <label style="font-size:13px;font-weight:700;color:#555;display:block;margin-bottom:5px">ملاحظات إضافية</label>
                            <textarea name="notes" style="width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:6px;font-size:14px;font-family:var(--fonts);min-height:100px;resize:vertical"><?= htmlspecialchars($_POST['notes'] ?? '') ?></textarea>
                        </div>

                        <div style="margin-top:15px">
                            <label style="font-size:13px;font-weight:400;color:#666;display:flex;align-items:center;gap:8px;cursor:pointer">
                                <input type="checkbox" name="agree" value="1" <?= isset($_POST['agree']) ? 'checked' : '' ?>>
                                أوافق على <a href="#" style="color:var(--red)">شروط وأحكام</a> نشر الإعلانات <span style="color:red">*</span>
                            </label>
                        </div>

                        <button type="submit" style="width:100%;margin-top:20px;padding:12px;background:var(--red);color:#fff;border:none;border-radius:6px;font-size:16px;font-weight:800;cursor:pointer;font-family:var(--fonts)">
                            <i class="fas fa-paper-plane"></i> إرسال الطلب
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
<?php render_footer(); ?>
