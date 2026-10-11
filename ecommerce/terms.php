<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
$pageTitle = 'Điều khoản sử dụng';
$pageDescription = 'Thông tin sử dụng website và đặt hàng tại NORD Home.';
$bodyClass = 'page-info';
$pageCss = 'info-pages.css';
require __DIR__ . '/includes/header.php';
?>
<div class="info-page">
<nav class="breadcrumb" aria-label="Đường dẫn"><ol class="breadcrumb-list"><li><a href="<?= e(url()) ?>">Trang chủ</a></li><li aria-current="page">Điều khoản sử dụng</li></ol></nav>
<header class="info-page__header"><p class="info-page__eyebrow">Sử dụng cửa hàng</p><h1>Điều khoản sử dụng</h1><p>Thông tin khái quát về việc duyệt sản phẩm và đặt hàng trên website.</p></header>
<div class="info-page__content">
<section class="info-card"><h2>Thông tin sản phẩm và giá</h2><p>Thông tin được hiển thị theo dữ liệu hiện có của cửa hàng và có thể được cập nhật. Giá, tình trạng sản phẩm, phí giao hàng và tổng tiền cuối cùng được thể hiện trong quá trình thanh toán trước khi gửi đơn.</p></section>
<section class="info-card"><h2>Tài khoản và đơn hàng</h2><p>Khách hàng cần đăng nhập để hoàn tất bước thanh toán. Vui lòng cung cấp thông tin nhận hàng chính xác và kiểm tra lại nội dung đơn trước khi đặt.</p></section>
<section class="info-card"><h2>Hỗ trợ và nội dung</h2><p>Nếu có thông tin chưa rõ hoặc cần hỗ trợ với một đơn hàng, vui lòng liên hệ cửa hàng. Các chi tiết vận chuyển, thanh toán và đổi trả cụ thể được xác nhận theo thông tin hiển thị trong đơn và trao đổi với cửa hàng.</p></section>
</div></div>
<?php require __DIR__ . '/includes/footer.php'; ?>