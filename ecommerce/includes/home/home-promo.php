<?php
/**
 * Promotional banner section.
 * Pulls a `home_promo` banner from the DB; falls back to a hard-coded copy.
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/storefront-helpers.php';
require_once __DIR__ . '/home-data.php';
require_once dirname(__DIR__) . '/shop-data.php';

/** @var array|null $promoBanner */
$promoBanner = hp_promo_banner();
$promoImageUrl = null;
if ($promoBanner !== null && !empty($promoBanner['image'])) {
    $promoImageUrl = storefront_image((string) $promoBanner['image']);
}

$promoLink = $promoBanner['link'] ?? url('products.php');
$promoLinkPath = parse_url((string) $promoLink, PHP_URL_PATH);
if (is_string($promoLinkPath) && basename($promoLinkPath) === 'category.php') {
    $promoQuery = [];
    parse_str((string) parse_url((string) $promoLink, PHP_URL_QUERY), $promoQuery);
    $requestedSlug = isset($promoQuery['slug']) && is_string($promoQuery['slug']) ? $promoQuery['slug'] : '';
    $promoCategory = $requestedSlug !== '' ? shop_resolve_category($requestedSlug, null) : null;

    // This active banner used the English alias outdoor, while the live
    // category table uses the Vietnamese slug ngoai-troi.
    if ($promoCategory === null && $requestedSlug === 'outdoor') {
        $promoCategory = shop_resolve_category('ngoai-troi', null);
    }

    $promoLink = $promoCategory !== null
        ? url('category.php?slug=' . urlencode($promoCategory['slug']))
        : url('products.php');
}
?>
<section class="promo-banner" aria-labelledby="promo-heading">
    <div class="container promo-inner">
        <div class="promo-content">
            <p class="promo-eyebrow">Bộ sưu tập mới</p>
            <h2 class="promo-heading" id="promo-heading">Những lựa chọn đơn giản<br>tạo nên không gian khác biệt.</h2>
            <p class="promo-sub">Khám phá bộ sưu tập mới nhất của chúng tôi — những sản phẩm được tuyển chọn kỹ lưỡng mang đến sự thoải mái và tinh tế cho không gian của bạn.</p>
            <a class="btn btn--primary btn--lg" href="<?= e($promoLink) ?>">
                Khám phá ngay
                <?= icon('arrow-right', 'icon icon--xs') ?>
            </a>
        </div>
        <div class="promo-visual" aria-hidden="true">
            <?php if ($promoImageUrl !== null): ?>
                <img class="promo-image"
                     src="<?= e($promoImageUrl) ?>"
                     alt=""
                     loading="lazy"
                     decoding="async">
            <?php else: ?>
                <div class="promo-image-placeholder">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 600 480" role="img" aria-label="Bộ sưu tập mới">
                        <defs>
                            <linearGradient id="promoGrad" x1="0" x2="1" y1="0" y2="1">
                                <stop offset="0%" stop-color="#e9dcc4"/>
                                <stop offset="100%" stop-color="#d8c7a5"/>
                            </linearGradient>
                        </defs>
                        <rect width="600" height="480" fill="url(#promoGrad)"/>
                        <g opacity="0.22" stroke="#5a4a38" stroke-width="1.5" fill="none">
                            <rect x="160" y="260" width="280" height="14" rx="2"/>
                            <path d="M180 274 L180 360"/>
                            <path d="M420 274 L420 360"/>
                            <path d="M280 220 Q260 220 260 240 L260 260 L300 260 L300 240 Q300 220 280 220Z"/>
                            <path d="M270 200 Q280 180 290 200"/>
                            <rect x="380" y="180" width="20" height="80"/>
                            <ellipse cx="390" cy="170" rx="38" ry="28"/>
                        </g>
                        <text x="50%" y="92%" text-anchor="middle"
                              font-family="Cormorant Garamond, Georgia, serif"
                              font-size="36" fill="#5a4a38" letter-spacing="0.2em"
                              opacity="0.6">BỘ SƯU TẬP MỚI</text>
                    </svg>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
