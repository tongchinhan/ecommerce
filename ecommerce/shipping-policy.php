<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/home/home-data.php';
require_once __DIR__ . '/includes/order-data.php';
$pageTitle = 'Thông tin giao hàng';
$pageDescription = 'Thông tin về phí và thời gian giao hàng hiển thị tại bước thanh toán NORD Home.';
$bodyClass = 'page-info';
$pageCss = 'info-pages.css';
$shipping = checkout_shipping_fee(0);
$thresholdText = format_vnd($shipping['threshold']);
require __DIR__ . '/includes/header.php';
?>
<div class="info-page">
<nav class="breadcrumb" aria-label="Đường dẫn"><ol class="breadcrumb-list"><li><a href="<?= e(url()) ?>">Trang chủ</a></li><li aria-current="page">Giao hàng</li></ol></nav>
<header class="info-page__header"><p class="info-page__eyebrow">Thông tin đơn hàng</p><h1>Giao hàng</h1><p>Phí và thông tin vận chuyển được trình bày tại bước thanh toán trước khi bạn đặt hàng.</p></header>
<div class="info-page__content">
<section class="info-card"><h2>Phí vận chuyển</h2><p>Cấu hình cửa hàng hiện tại áp dụng miễn phí vận chuyển cho đơn từ <strong><?= e($thresholdText) ?></strong>. Với đơn dưới ngưỡng, phí được tính theo cấu hình tại hệ thống và hiển thị trong phần tóm tắt thanh toán.</p></section>
<section class="info-card"><h2>Thời gian tham khảo</h2><p>Nội dung trên cửa hàng hiện nêu thời gian tham khảo khoảng 2–4 ngày làm việc. Đây không phải cam kết thời hạn giao; thời gian thực tế có thể thay đổi theo địa chỉ nhận hàng và quá trình vận chuyển.</p></section>
<section class="info-card"><h2>Theo dõi và hỗ trợ</h2><p>Nếu cần cập nhật thông tin giao nhận, vui lòng gửi mã đơn hàng qua <a href="<?= e(url('contact.php')) ?>">biểu mẫu liên hệ</a>. Khả năng điều chỉnh phụ thuộc vào trạng thái xử lý của đơn.</p></section>
</div></div>
<?php require __DIR__ . '/includes/footer.php'; ?>