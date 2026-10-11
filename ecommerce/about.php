<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
$pageTitle = 'Giới thiệu';
$pageDescription = 'Tìm hiểu cách NORD Home tuyển chọn sản phẩm cho không gian sống và cách cửa hàng hỗ trợ khách hàng.';
$bodyClass = 'page-info';
$pageCss = 'info-pages.css';
require __DIR__ . '/includes/header.php';
?>
<div class="info-page">
<nav class="breadcrumb" aria-label="Đường dẫn"><ol class="breadcrumb-list"><li><a href="<?= e(url()) ?>">Trang chủ</a></li><li aria-current="page">Giới thiệu</li></ol></nav>
<header class="info-page__header"><p class="info-page__eyebrow">Về NORD Home</p><h1>Không gian sống, được lựa chọn bằng sự cân nhắc.</h1><p>NORD Home là cửa hàng trực tuyến giới thiệu các sản phẩm nội thất và đồ dùng cho đời sống hằng ngày. Chúng tôi tập trung trình bày thông tin rõ ràng để bạn có thêm cơ sở lựa chọn cho ngôi nhà của mình.</p></header>
<div class="info-page__content">
<section class="info-card"><h2>Lời giới thiệu</h2><p>Bộ sưu tập tại NORD Home gồm các sản phẩm cho phòng khách, phòng ngủ, chiếu sáng, bếp, trang trí và không gian ngoài trời. Mỗi trang sản phẩm cung cấp thông tin hiện có về giá, mô tả và tình trạng hàng để khách hàng tham khảo trước khi đặt mua.</p></section>
<section class="info-card"><h2>Cách chúng tôi tuyển chọn</h2><p>Chúng tôi hướng đến những thiết kế hài hòa, công năng dễ hiểu và phù hợp với nhiều không gian sống. Danh mục được cập nhật theo dữ liệu sản phẩm đang có trong cửa hàng; thông số cụ thể được trình bày tại từng sản phẩm khi có sẵn.</p></section>
<section class="info-card"><h2>Hỗ trợ khách hàng</h2><p>Nếu cần làm rõ thông tin sản phẩm, giao hàng hoặc đơn đặt hàng, bạn có thể gửi câu hỏi qua trang <a href="<?= e(url('contact.php')) ?>">Liên hệ</a>. Cửa hàng sẽ tiếp nhận yêu cầu qua biểu mẫu; thời gian phản hồi có thể thay đổi tùy thời điểm.</p></section>
<section class="info-card"><h2>Thông tin trước khi đặt hàng</h2><p>Phí vận chuyển và phương thức thanh toán hiện có sẽ được hiển thị trong quá trình thanh toán. Các nội dung về giao nhận, đổi trả và quyền riêng tư được trình bày tại những trang thông tin tương ứng.</p><div class="info-link-row"><a class="btn btn--ghost" href="<?= e(url('shipping-policy.php')) ?>">Thông tin giao hàng</a><a class="btn btn--ghost" href="<?= e(url('return-policy.php')) ?>">Thông tin đổi trả</a></div></section>
</div></div>
<?php require __DIR__ . '/includes/footer.php'; ?>