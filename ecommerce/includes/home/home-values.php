<?php
/** Benefits phrased to match currently implemented storefront features. */
declare(strict_types=1);
require_once dirname(__DIR__) . '/icons.php';
require_once __DIR__ . '/home-data.php';
$shippingSetting = hp_setting('free_shipping_min', '2000000');
$shippingThreshold = is_numeric($shippingSetting) ? format_vnd((float) $shippingSetting) : 'mức áp dụng tại thanh toán';
$benefits = [
    ['icon' => 'leaf', 'title' => 'Sản phẩm được tuyển chọn', 'desc' => 'Danh mục gồm nội thất và đồ dùng cho nhiều không gian trong nhà; thông tin cụ thể được trình bày tại từng sản phẩm.'],
    ['icon' => 'shield', 'title' => 'Phương thức thanh toán', 'desc' => 'Hiện có thanh toán khi nhận hàng và chuyển khoản ngân hàng. Chi tiết áp dụng được hiển thị trong quá trình thanh toán.'],
    ['icon' => 'truck', 'title' => 'Thông tin giao hàng rõ ràng', 'desc' => 'Phí giao hàng được tính tại bước thanh toán. Cấu hình hiện tại miễn phí vận chuyển từ ' . $shippingThreshold . '.'],
    ['icon' => 'chat', 'title' => 'Kênh hỗ trợ', 'desc' => 'Bạn có thể gửi câu hỏi qua biểu mẫu liên hệ. Thời gian phản hồi có thể thay đổi tùy thời điểm.'],
];
?>
<section class="value-section" aria-labelledby="value-heading"><div class="container">
<header class="section-header section-header--centered"><div class="section-header-text"><p class="section-eyebrow">Thông tin cửa hàng</p><h2 class="section-heading" id="value-heading">Những điều cần biết trước khi đặt hàng.</h2></div></header>
<ul class="value-grid" role="list"><?php foreach ($benefits as $b): ?><li class="value-item"><span class="value-icon" aria-hidden="true"><?= icon($b['icon'], 'icon icon--lg') ?></span><h3 class="value-title"><?= e($b['title']) ?></h3><p class="value-desc"><?= e($b['desc']) ?></p></li><?php endforeach; ?></ul>
</div></section>