<?php
/**
 * New arrivals section. Sorted newest first.
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/storefront-helpers.php';
require_once __DIR__ . '/home-data.php';
require_once __DIR__ . '/home-section.php';
require_once dirname(__DIR__) . '/components/product-card.php';

$products = hp_products('newest', 4);
if (empty($products)) {
    return;
}
?>
<?php hp_section([
    'eyebrow'   => 'Mới về',
    'heading'   => 'Sản phẩm mới',
    'subtext'   => 'Những sản phẩm mới được thêm vào, sẵn sàng làm mới không gian của bạn.',
    'link'      => url('products.php?sort=newest'),
    'link_text' => 'Xem tất cả sản phẩm mới',
    'id'        => 'new-arrivals',
], function() use ($products) {
    render_product_cards($products, 'product-grid product-grid--four');
}); ?>
