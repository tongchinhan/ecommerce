<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/storefront-helpers.php';
require_once dirname(__DIR__) . '/account-data.php';

function render_product_card(array $product, bool $isWishlisted = false): void
{
    $productName = (string) ($product['name'] ?? 'Sản phẩm');
    $productPrice = (float) ($product['price'] ?? 0);
    $comparePrice = isset($product['compare_price']) ? (float) $product['compare_price'] : 0.0;
    $discount = storefront_discount_percent($productPrice, $comparePrice);
    $onSale = $comparePrice > 0 && $comparePrice > $productPrice;
    $isFeatured = !empty($product['is_featured']);
    $stock = isset($product['stock']) ? (int) $product['stock'] : null;
    $categoryName = (string) ($product['category_name'] ?? '');
    $imageUrl = storefront_product_image($product);
    $placeholderTone = storefront_category_tone((int) ($product['category_id'] ?? 1));
    $placeholder = storefront_placeholder_svg($productName, $placeholderTone);
    $detailHref = storefront_product_link($product);
    $shortDesc = (string) ($product['short_description'] ?? '');
?>
    <article class="product-card" data-product-id="<?= e((string) ($product['id'] ?? '')) ?>">
        <div class="product-card-media-shell">
            <a class="product-card-media" href="<?= e($detailHref) ?>" aria-label="<?= e($productName) ?>">
                <span class="product-card-image-wrap">
                    <img class="product-card-image" src="<?= e($imageUrl ?? $placeholder) ?>" alt="<?= e($productName) ?>" loading="lazy" decoding="async" onerror="this.onerror=null;this.src='<?= e($placeholder) ?>';">
                </span>
                <span class="product-card-badges">
                    <?php if ($isFeatured): ?><span class="badge badge--accent">Nổi bật</span><?php endif; ?>
                    <?php if ($onSale): ?><span class="badge badge--sale">-<?= (int) $discount ?>%</span><?php endif; ?>
                    <?php if ($stock !== null && $stock <= 0): ?><span class="badge badge--sold">Hết hàng</span>
                    <?php elseif ($stock !== null && $stock > 0 && $stock <= 5): ?><span class="badge badge--low">Sắp hết</span><?php endif; ?>
                </span>
            </a>
            <form class="product-card-wishlist-form" method="post" action="<?= e(url('account/wishlist.php')) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="toggle">
                <input type="hidden" name="product_id" value="<?= e((string) (int) ($product['id'] ?? 0)) ?>">
                <input type="hidden" name="return_to" value="<?= e(account_current_return()) ?>">
                <button class="product-card-wishlist<?= $isWishlisted ? ' is-active' : '' ?>" type="submit" aria-label="<?= $isWishlisted ? 'Xóa ' : 'Thêm ' ?><?= e($productName) ?> <?= $isWishlisted ? 'khỏi' : 'vào' ?> danh sách yêu thích" aria-pressed="<?= $isWishlisted ? 'true' : 'false' ?>">
                    <?= icon('heart', 'icon icon--sm') ?>
                </button>
            </form>
        </div>
        <div class="product-card-body">
            <?php if ($categoryName !== ''): ?><p class="product-card-category"><?= e($categoryName) ?></p><?php endif; ?>
            <h3 class="product-card-name"><a href="<?= e($detailHref) ?>"><?= e($productName) ?></a></h3>
            <?php if ($shortDesc !== ''): ?><p class="product-card-desc"><?= e(storefront_excerpt($shortDesc, 80)) ?></p><?php endif; ?>
            <div class="product-card-pricing">
                <span class="product-card-price"><?= e(storefront_price($productPrice)) ?></span>
                <?php if ($onSale): ?><span class="product-card-compare"><?= e(storefront_price($comparePrice)) ?></span><?php endif; ?>
            </div>
            <div class="product-card-actions">
                <a class="btn btn--ghost btn--sm" href="<?= e($detailHref) ?>">Xem sản phẩm</a>
                <?php if ($stock !== null && $stock <= 0): ?>
                    <button class="btn btn--primary btn--sm product-card-add" type="button" disabled aria-disabled="true"><?= icon('close', 'icon icon--sm') ?><span>Hết hàng</span></button>
                <?php else: ?>
                    <form class="product-card-add-form" method="post" action="<?= e(url('products.php')) ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="add_to_cart">
                        <input type="hidden" name="product_id" value="<?= e((string) (int) ($product['id'] ?? 0)) ?>">
                        <input type="hidden" name="quantity" value="1">
                        <button class="btn btn--primary btn--sm product-card-add" type="submit"><?= icon('bag', 'icon icon--sm') ?><span>Thêm vào giỏ</span></button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </article>
<?php
}

function render_product_cards(array $products, string $containerClass = 'product-grid'): void
{
    if (empty($products)) return;
    $states = is_customer() ? wishlist_product_states(array_column($products, 'id'), (int) current_user()['id']) : [];
?>
    <div class="<?= e($containerClass) ?>">
        <?php foreach ($products as $product): ?>
            <?php render_product_card($product, !empty($states[(int) ($product['id'] ?? 0)])); ?>
        <?php endforeach; ?>
    </div>
<?php
}
