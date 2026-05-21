<?php
require_once 'includes/functions.php';

$sent = false;
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $message = trim($_POST['message'] ?? '');
    if (!$name || !$email || !$message) {
        $error = 'برجاء ملء جميع الحقول';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'البريد الإلكتروني غير صحيح';
    } else {
        $msgsFile = __DIR__ . '/data/messages.json';
        $msgs = [];
        if (file_exists($msgsFile)) {
            $msgs = json_decode(file_get_contents($msgsFile), true);
            if (json_last_error() !== JSON_ERROR_NONE) $msgs = [];
        }
        $msgs[] = [
            'id' => time() . '_' . bin2hex(random_bytes(4)),
            'name' => $name,
            'email' => $email,
            'message' => $message,
            'date' => (new DateTime('now', new DateTimeZone('Africa/Cairo')))->format('Y-m-d H:i:s'),
            'read' => false,
        ];
        file_put_contents($msgsFile, json_encode($msgs, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        $sent = true;
    }
}

render_header('اتصل بنا');
render_topbar();
render_navbar();
?>
<style>
.contact-page{padding:40px 0 60px}
.contact-page h1{font-size:28px;font-weight:900;color:var(--dark);margin-bottom:30px;padding-bottom:12px;border-bottom:2px solid var(--red)}
.contact-form .form-control{border-radius:0;border:1px solid #ddd;padding:12px 15px;font-size:15px;font-weight:400;font-family:'Cairo',sans-serif}
.contact-form .form-control:focus{border-color:var(--red);box-shadow:0 0 0 2px rgba(21,112,57,0.1)}
.contact-form textarea{resize:vertical;min-height:150px}
.contact-form .btn-submit{background:var(--red);color:#fff;border:none;padding:12px 40px;font-size:16px;font-weight:700;border-radius:0;cursor:pointer;transition:background 0.2s}
.contact-form .btn-submit:hover{background:var(--red-dark)}
.contact-info-card{background:#fafafa;border:1px solid #e8e8e8;padding:25px;margin-bottom:20px}
.contact-info-card h5{font-size:18px;font-weight:800;color:var(--dark);margin-bottom:15px;padding-bottom:8px;border-bottom:2px solid var(--red)}
.contact-info-card .info-item{display:flex;align-items:flex-start;gap:12px;margin-bottom:15px;font-size:14px;font-weight:400;color:#555}
.contact-info-card .info-item i{color:var(--red);font-size:18px;margin-top:3px;width:22px;text-align:center}
.contact-info-card .info-item a{color:#555;font-weight:400}
.contact-info-card .info-item a:hover{color:var(--red)}
.contact-social{display:flex;gap:10px;margin-top:15px}
.contact-social a{display:flex;align-items:center;justify-content:center;width:40px;height:40px;background:var(--dark);color:#fff;border-radius:50%;font-size:16px;transition:background 0.2s}
.contact-social a:hover{background:var(--red)}
.alert-success{background:#d4edda;color:#155724;padding:15px;font-size:15px;font-weight:400;border:1px solid #c3e6cb;margin-bottom:20px}
.alert-error{background:#f8d7da;color:#721c24;padding:15px;font-size:15px;font-weight:400;border:1px solid #f5c6cb;margin-bottom:20px}
.breadcrumb-section{background:#f8f8f8;padding:12px 0;border-bottom:1px solid #eee}
.breadcrumb-section .breadcrumb{margin:0;font-size:14px;font-weight:400;background:none}
.breadcrumb-section .breadcrumb a{color:var(--red)}
.breadcrumb-section .breadcrumb .active{color:#666}
@media(max-width:767px){.contact-page h1{font-size:22px}}
</style>

<section class="breadcrumb-section">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php"><i class="fas fa-home ms-1"></i>الرئيسية</a></li>
                <li class="breadcrumb-item active">اتصل بنا</li>
            </ol>
        </nav>
    </div>
</section>

<section class="contact-page">
    <div class="container">
        <h1>اتصل بنا</h1>
        <div class="row">
            <div class="col-lg-8">
                <?php if ($sent): ?>
                <div class="alert-success"><i class="fas fa-check-circle ms-1"></i> تم إرسال رسالتك بنجاح، سنتواصل معك في أقرب وقت.</div>
                <?php endif; ?>
                <?php if ($error): ?>
                <div class="alert-error"><i class="fas fa-exclamation-circle ms-1"></i> <?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <form class="contact-form" method="post">
                    <div class="mb-3">
                        <input type="text" name="name" class="form-control" placeholder="الاسم بالكامل" required>
                    </div>
                    <div class="mb-3">
                        <input type="email" name="email" class="form-control" placeholder="البريد الإلكتروني" required>
                    </div>
                    <div class="mb-3">
                        <textarea name="message" class="form-control" placeholder="رسالتك..." required></textarea>
                    </div>
                    <button type="submit" class="btn-submit">إرسال</button>
                </form>
            </div>
            <div class="col-lg-4">
                <div class="contact-info-card">
                    <h5>معلومات الاتصال</h5>
                    <div class="info-item"><i class="fas fa-map-marker-alt"></i><span><?= htmlspecialchars(get_setting('address')) ?></span></div>
                    <div class="info-item"><i class="fas fa-phone"></i><span><?= htmlspecialchars(get_setting('phone')) ?></span></div>
                    <div class="info-item"><i class="fas fa-fax"></i><span><?= htmlspecialchars(get_setting('fax')) ?></span></div>
                    <div class="info-item"><i class="fas fa-envelope"></i><a href="mailto:<?= htmlspecialchars(get_setting('email')) ?>"><?= htmlspecialchars(get_setting('email')) ?></a></div>
                    <div class="info-item"><i class="fas fa-globe"></i><span>للإعلان: 01033336523</span></div>
                    <div class="info-item"><i class="fas fa-envelope"></i><a href="mailto:ahramgate2018@gmail.com">ahramgate2018@gmail.com</a></div>
                    <h5 style="margin-top:20px">تابعنا</h5>
                    <div class="contact-social">
                        <a href="<?= htmlspecialchars(get_setting('facebook_url')) ?>" target="_blank"><i class="fab fa-facebook-f"></i></a>
                        <a href="<?= htmlspecialchars(get_setting('twitter_url')) ?>" target="_blank"><i class="fab fa-twitter"></i></a>
                        <a href="<?= htmlspecialchars(get_setting('youtube_url')) ?>" target="_blank"><i class="fab fa-youtube"></i></a>
                        <a href="<?= htmlspecialchars(get_setting('instagram_url')) ?>" target="_blank"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php render_footer(); ?>
