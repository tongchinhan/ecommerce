<?php
/**
 * Featured Products grid.
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/storefront-helpers.php';
require_once __DIR__ . '/home-data.php';
require_once __DIR__ . '/home-section.php';
require_once dirname(__DIR__) . '/components/product-card.php';

$products = hp_products('featured', 8);
if (empty($products)) {
    return;
}
?>
<?php hp_section([
    'eyebrow'   => 'Tuyển chọn',
    'heading'   => 'Sản phẩm nổi bật',
    'subtext'   => 'Những sản phẩm được lựa chọn kỹ lưỡng cho mùa này — mỗi sản phẩm đều mang đến sự tinh tế, tiện nghi và phong cách vượt thời gian.',
    'link'      => url('products.php?featured=1'),
    'link_text' => 'Xem tất cả sản phẩm nổi bật',
    'id'        => 'featured-products',
], function() use ($products) {
    render_product_cards($products, 'product-grid product-grid--four');
}); ?>
