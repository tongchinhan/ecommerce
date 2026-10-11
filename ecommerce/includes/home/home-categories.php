<?php
/**
 * Featured Categories section.
 * Loads categories from DB and renders a 4-column grid.
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/storefront-helpers.php';
require_once __DIR__ . '/home-data.php';
require_once __DIR__ . '/home-section.php';

$categories = hp_featured_categories(6);
if (empty($categories)) {
    return;
}
?>
<?php hp_section([
    'eyebrow'   => 'Khám phá',
    'heading'   => 'Khám phá theo danh mục',
    'subtext'   => 'Tìm kiếm sản phẩm phù hợp cho mọi không gian trong ngôi nhà của bạn.',
    'link'      => url('products.php'),
    'link_text' => 'Xem tất cả',
    'id'        => 'categories',
], function() use ($categories) { ?>
    <div class="category-grid">
        <?php foreach ($categories as $cat):
            $count = hp_category_product_count((int) $cat['id']);
            $imageUrl = storefront_image($cat['image'] ?? null);
            $tone = storefront_category_tone((int) $cat['id']);
            $label = strtoupper(substr(trim($cat['name']), 0, 1) ?: '·');
            $placeholder = storefront_placeholder_svg($label, $tone);
        ?>
        <a class="category-card" href="<?= e(storefront_category_link($cat)) ?>">
            <div class="category-card-media">
                <img class="category-card-image"
                     src="<?= e($imageUrl ?? $placeholder) ?>"
                     alt="<?= e($cat['name']) ?>"
                     loading="lazy"
                     decoding="async"
                     onerror="this.onerror=null;this.src='<?= e($placeholder) ?>';">
                <div class="category-card-overlay"></div>
            </div>
            <div class="category-card-body">
                <h3 class="category-card-name"><?= e($cat['name']) ?></h3>
                <p class="category-card-count">
                    <?php if ($count > 0): ?>
                        <?= e((string) $count) ?> sản phẩm
                    <?php else: ?>
                        Khám phá
                    <?php endif; ?>
                    <?= icon('arrow-right', 'icon icon--xs') ?>
                </p>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
<?php }); ?>
