<?php
/** Homepage bestseller section; never label unsold products as bestsellers. */
declare(strict_types=1);

require_once dirname(__DIR__) . '/storefront-helpers.php';
require_once __DIR__ . '/home-data.php';
require_once __DIR__ . '/home-section.php';
require_once dirname(__DIR__) . '/components/product-card.php';

$products = hp_products('bestsellers', 4);
$hasSalesData = !empty($products);
if (!$hasSalesData) {
    // Keep the homepage useful when there are no accepted sales yet, while
    // describing the real fallback accurately.
    $products = hp_products('newest', 4);
}
if (empty($products)) {
    return;
}

?>
<?php hp_section([
    'eyebrow'   => $hasSalesData ? 'Được yêu thích nhất' : 'Khám phá thêm',
    'heading'   => $hasSalesData ? 'Sản phẩm bán chạy' : 'Sản phẩm mới',
    'subtext'   => $hasSalesData ? 'Những sản phẩm được khách hàng lựa chọn nhiều nhất.' : 'Khám phá thêm những sản phẩm mới tại NORD HOME.',
    'link'      => url($hasSalesData ? 'products.php?sort=bestsellers' : 'products.php?sort=newest'),
    'link_text' => $hasSalesData ? 'Xem tất cả sản phẩm bán chạy' : 'Xem sản phẩm mới',
    'id'        => 'best-sellers',
], function() use ($products) {
    render_product_cards($products, 'product-grid product-grid--four');
}); ?>
