<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
$pageTitle = 'Chính sách quyền riêng tư';
$pageDescription = 'Thông tin về dữ liệu được gửi qua các biểu mẫu và tài khoản NORD Home.';
$bodyClass = 'page-info';
$pageCss = 'info-pages.css';
require __DIR__ . '/includes/header.php';
?>
<div class="info-page">
<nav class="breadcrumb" aria-label="Đường dẫn"><ol class="breadcrumb-list"><li><a href="<?= e(url()) ?>">Trang chủ</a></li><li aria-current="page">Quyền riêng tư</li></ol></nav>
<header class="info-page__header"><p class="info-page__eyebrow">Thông tin dữ liệu</p><h1>Quyền riêng tư</h1><p>Trang này mô tả khái quát dữ liệu mà các chức năng hiện có của cửa hàng xử lý.</p></header>
<div class="info-page__content">
<section class="info-card"><h2>Dữ liệu bạn cung cấp</h2><p>Khi tạo tài khoản, đặt hàng hoặc gửi biểu mẫu liên hệ, bạn cung cấp các thông tin cần thiết cho chức năng tương ứng. Biểu mẫu liên hệ lưu tên, email, số điện thoại tùy chọn và nội dung trong hệ thống cửa hàng.</p></section>
<section class="info-card"><h2>Mục đích sử dụng</h2><p>Thông tin được sử dụng để vận hành tài khoản, ghi nhận đơn hàng hoặc tiếp nhận yêu cầu liên hệ tương ứng. Không nhập thông tin thanh toán nhạy cảm vào trường nội dung liên hệ.</p></section>
<section class="info-card"><h2>Câu hỏi về dữ liệu</h2><p>Nếu cần trao đổi về thông tin đã gửi, hãy liên hệ cửa hàng qua <a href="<?= e(url('contact.php')) ?>">biểu mẫu Liên hệ</a>. Thời hạn lưu trữ và quy trình xử lý yêu cầu dữ liệu cần được cửa hàng xác nhận theo cấu hình vận hành thực tế.</p></section>
</div></div>
<?php require __DIR__ . '/includes/footer.php'; ?>