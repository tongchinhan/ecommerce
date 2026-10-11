<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/order-data.php';
require_once __DIR__ . '/includes/home/home-data.php';
$pageTitle = 'Câu hỏi thường gặp';
$pageDescription = 'Thông tin tham khảo về đặt hàng, thanh toán, giao hàng, đổi trả và tài khoản tại NORD Home.';
$bodyClass = 'page-info';
$pageCss = 'info-pages.css';
$shipping = checkout_shipping_fee(0);
$freeShippingText = format_vnd($shipping['threshold']);
$questions = [
['Đặt hàng như thế nào?', 'Chọn sản phẩm, thêm vào giỏ hàng và tiếp tục đến bước thanh toán. Bạn cần đăng nhập để hoàn tất đơn hàng; thông tin và tổng tiền được hiển thị trước khi đặt.'],
['Có thể kiểm tra đơn hàng ở đâu?', 'Đăng nhập bằng tài khoản đã đặt hàng và mở khu vực tài khoản để xem các đơn gắn với tài khoản đó. Nếu cần hỗ trợ, hãy gửi mã đơn qua trang Liên hệ.'],
['Cửa hàng hỗ trợ phương thức thanh toán nào?', 'Trang thanh toán hiện cung cấp thanh toán khi nhận hàng (COD) và chuyển khoản ngân hàng. Hệ thống chưa tích hợp cổng thanh toán trực tuyến.'],
['Phí giao hàng được tính ra sao?', 'Phí được tính tại bước thanh toán theo giá trị giỏ hàng và cấu hình hiện tại. Cấu hình cửa hàng đang áp dụng miễn phí vận chuyển từ ' . $freeShippingText . '; mức phí cụ thể sẽ xuất hiện trong phần tóm tắt đơn hàng.'],
['Đơn hàng mất bao lâu để giao?', 'Thời gian 2–4 ngày làm việc là thông tin tham khảo đang được nêu trên cửa hàng; thời gian thực tế có thể thay đổi theo địa chỉ nhận và quá trình vận chuyển.'],
['Làm sao yêu cầu đổi hoặc trả sản phẩm?', 'Điều kiện cụ thể chưa được cấu hình đầy đủ trên hệ thống. Vui lòng gửi thông tin đơn hàng để cửa hàng xác nhận trước khi gửi sản phẩm lại. Gửi yêu cầu không đồng nghĩa yêu cầu đã được chấp thuận.'],
['Tôi có thể thay đổi thông tin sau khi đặt hàng không?', 'Hãy liên hệ cửa hàng sớm và cung cấp mã đơn. Việc điều chỉnh phụ thuộc vào trạng thái xử lý hiện tại của đơn hàng.'],
];
require __DIR__ . '/includes/header.php';
?>
<div class="info-page">
<nav class="breadcrumb" aria-label="Đường dẫn"><ol class="breadcrumb-list"><li><a href="<?= e(url()) ?>">Trang chủ</a></li><li aria-current="page">Câu hỏi thường gặp</li></ol></nav>
<header class="info-page__header"><p class="info-page__eyebrow">Hỗ trợ</p><h1>Câu hỏi thường gặp</h1><p>Thông tin dưới đây mô tả các chức năng hiện có. Chi tiết áp dụng cho từng đơn hàng được hiển thị khi thanh toán hoặc xác nhận với cửa hàng.</p></header>
<div class="faq-list"><?php foreach ($questions as $index => [$question, $answer]): ?><details class="faq-item"<?= $index === 0 ? ' open' : '' ?>><summary><?= e($question) ?></summary><div class="faq-item__answer"><p><?= e($answer) ?></p></div></details><?php endforeach; ?></div>
<p class="info-callout">Bạn cần hỗ trợ thêm? <a href="<?= e(url('contact.php')) ?>">Gửi câu hỏi cho NORD Home</a>.</p>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>