<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/home/home-data.php';
$pageTitle = 'Liên hệ';
$pageDescription = 'Gửi câu hỏi đến NORD Home qua biểu mẫu liên hệ.';
$bodyClass = 'page-info';
$pageCss = 'info-pages.css';
$form = ['name' => '', 'email' => '', 'phone' => '', 'message' => ''];
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($form as $key => $_) {
        $form[$key] = trim((string) ($_POST[$key] ?? ''));
    }
    if (!csrf_verify()) {
        $errors[] = 'Phiên gửi biểu mẫu đã hết hạn. Vui lòng tải lại trang và thử lại.';
    }
    if (mb_strlen($form['name'], 'UTF-8') < 2 || mb_strlen($form['name'], 'UTF-8') > 120) {
        $errors[] = 'Vui lòng nhập họ tên từ 2 đến 120 ký tự.';
    }
    if (!validate_email($form['email']) || mb_strlen($form['email'], 'UTF-8') > 190) {
        $errors[] = 'Vui lòng nhập địa chỉ email hợp lệ.';
    }
    if ($form['phone'] !== '') {
        $phone = preg_replace('/[\s\-().]/', '', $form['phone']) ?? '';
        if (!preg_match('/^(?:0\d{9,10}|\+84[35789]\d{8})$/', $phone)) {
            $errors[] = 'Số điện thoại chưa đúng định dạng.';
        }
    }
    if (mb_strlen($form['message'], 'UTF-8') < 10 || mb_strlen($form['message'], 'UTF-8') > 5000) {
        $errors[] = 'Nội dung cần có từ 10 đến 5.000 ký tự.';
    }
    if ($errors === []) {
        try {
            $stmt = db()->prepare("INSERT INTO contacts (name, email, phone, subject, message, status) VALUES (:name, :email, :phone, NULL, :message, 'new')");
            $stmt->execute(['name' => $form['name'], 'email' => $form['email'], 'phone' => $form['phone'] !== '' ? $form['phone'] : null, 'message' => $form['message']]);
            flash_set('success', 'Yêu cầu liên hệ đã được lưu. Cảm ơn bạn đã gửi thông tin.');
            redirect('contact.php');
        } catch (PDOException $exception) {
            error_log('Contact form storage failed: ' . $exception->getMessage());
            $errors[] = 'Chưa thể lưu yêu cầu lúc này. Vui lòng thử lại sau.';
        }
    }
}
$contactEmail = trim(hp_setting('store_email'));
$contactEmailConfigured = filter_var($contactEmail, FILTER_VALIDATE_EMAIL) && !str_ends_with(strtolower($contactEmail), '.local');
$contactPhone = trim(hp_setting('store_phone'));
$contactAddress = trim(hp_setting('store_address'));
require __DIR__ . '/includes/header.php';
?>
<div class="info-page">
<nav class="breadcrumb" aria-label="Đường dẫn"><ol class="breadcrumb-list"><li><a href="<?= e(url()) ?>">Trang chủ</a></li><li aria-current="page">Liên hệ</li></ol></nav>
<header class="info-page__header"><p class="info-page__eyebrow">Chúng tôi có thể hỗ trợ gì?</p><h1>Liên hệ NORD Home</h1><p>Gửi câu hỏi về sản phẩm, giao hàng hoặc đơn hàng. Biểu mẫu sẽ lưu yêu cầu để cửa hàng tiếp nhận.</p></header>
<div class="contact-layout">
<section class="info-card contact-details" aria-labelledby="contact-details-title"><h2 id="contact-details-title">Thông tin cửa hàng</h2>
<p><span>Email</span><?php if ($contactEmailConfigured): ?><a href="mailto:<?= e($contactEmail) ?>"><?= e($contactEmail) ?></a><?php else: ?><strong>Email cửa hàng chưa được cấu hình.</strong><?php endif; ?></p>
<?php if ($contactPhone !== ''): ?><p><span>Điện thoại</span><a href="tel:<?= e(preg_replace('/\D+/', '', $contactPhone) ?? '') ?>"><?= e($contactPhone) ?></a></p><?php endif; ?>
<?php if ($contactAddress !== ''): ?><p><span>Địa chỉ đang cấu hình</span><strong><?= e($contactAddress) ?></strong></p><?php endif; ?>
<p class="info-note">Thông tin liên hệ lấy từ cấu hình cửa hàng. Vui lòng xác nhận các thông tin này trước khi công bố rộng rãi.</p></section>
<section class="info-card" aria-labelledby="contact-form-title"><h2 id="contact-form-title">Gửi lời nhắn</h2>
<?php if ($errors !== []): ?><div class="info-alert info-alert--error" role="alert"><p>Vui lòng kiểm tra lại thông tin:</p><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<form class="info-form" method="post" action="<?= e(url('contact.php')) ?>"><?= csrf_field() ?>
<div class="info-form__row"><label>Họ và tên *<input name="name" type="text" required minlength="2" maxlength="120" autocomplete="name" value="<?= e($form['name']) ?>"></label><label>Email *<input name="email" type="email" required maxlength="190" autocomplete="email" value="<?= e($form['email']) ?>"></label></div>
<label>Số điện thoại<input name="phone" type="tel" maxlength="30" autocomplete="tel" value="<?= e($form['phone']) ?>"></label>
<label>Nội dung *<textarea name="message" required minlength="10" maxlength="5000" rows="6"><?= e($form['message']) ?></textarea></label>
<button class="btn btn--primary" type="submit">Gửi yêu cầu</button></form></section>
</div></div>
<?php require __DIR__ . '/includes/footer.php'; ?>