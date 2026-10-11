<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
$pageTitle = 'Thông tin đổi trả';
$pageDescription = 'Hướng dẫn liên hệ để xác nhận yêu cầu đổi hoặc trả sản phẩm tại NORD Home.';
$bodyClass = 'page-info';
$pageCss = 'info-pages.css';
require __DIR__ . '/includes/header.php';
?>
<div class="info-page">
<nav class="breadcrumb" aria-label="Đường dẫn"><ol class="breadcrumb-list"><li><a href="<?= e(url()) ?>">Trang chủ</a></li><li aria-current="page">Đổi trả</li></ol></nav>
<header class="info-page__header"><p class="info-page__eyebrow">Hỗ trợ đơn hàng</p><h1>Thông tin đổi trả</h1><p>Điều kiện đổi trả cụ thể cần được xác nhận với cửa hàng trước khi gửi sản phẩm.</p></header>
<div class="info-page__content">
<section class="info-card"><h2>Gửi yêu cầu</h2><p>Hãy liên hệ và cung cấp mã đơn hàng, sản phẩm cần hỗ trợ cùng mô tả tình trạng. Cửa hàng sẽ trao đổi các bước tiếp theo sau khi tiếp nhận thông tin.</p></section>
<section class="info-card"><h2>Trước khi gửi sản phẩm</h2><p>Vui lòng chưa gửi hàng về khi chưa nhận được hướng dẫn cụ thể. Trang này không xác nhận tự động điều kiện, thời hạn hoặc chi phí đổi trả; các nội dung đó cần được cửa hàng xác nhận cho từng trường hợp.</p></section>
<section class="info-card"><h2>Liên hệ</h2><p>Gửi yêu cầu qua <a href="<?= e(url('contact.php')) ?>">trang Liên hệ</a> và giữ lại thông tin đơn hàng để tiện trao đổi.</p></section>
</div></div>
<?php require __DIR__ . '/includes/footer.php'; ?>