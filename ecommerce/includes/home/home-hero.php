<?php
/** Homepage hero and live storefront metrics. */
declare(strict_types=1);
require_once dirname(__DIR__) . '/storefront-helpers.php';
require_once __DIR__ . '/home-data.php';
$heroBanner = hp_hero_banner();
$bannerImageUrl = ($heroBanner !== null && !empty($heroBanner['image'])) ? storefront_image($heroBanner['image']) : null;
$homeMetrics = hp_storefront_metrics();
$shippingSetting = hp_setting('free_shipping_min', '2000000');
$shippingMinimum = is_numeric($shippingSetting) ? format_vnd((float) $shippingSetting) : 'Xem tại bước thanh toán';
$ratingText = $homeMetrics['average_rating'] === null ? 'Chưa có' : number_format($homeMetrics['average_rating'], 1, ',', '.');
$ratingLabel = $homeMetrics['approved_reviews'] > 0 ? 'Đánh giá trung bình' : 'Chưa có đánh giá';
?>
<section class="hero" aria-labelledby="hero-heading"><div class="hero-content">
<p class="hero-eyebrow">Phong cách sống hiện đại</p><h1 class="hero-heading" id="hero-heading">Những sản phẩm tinh tế<br>cho cuộc sống mỗi ngày.</h1>
<p class="hero-sub">Khám phá những sản phẩm được tuyển chọn kỹ lưỡng, mang đến sự tiện nghi, tinh tế và cá tính cho không gian sống của bạn.</p>
<div class="hero-actions"><a class="btn btn--primary btn--lg" href="<?= e(url('products.php')) ?>">Mua sắm ngay <?= icon('arrow-right', 'icon icon--xs') ?></a><a class="btn btn--ghost btn--lg" href="<?= e(url('blog.php')) ?>">Khám phá bài viết</a></div>
<div class="hero-meta">
<div class="hero-meta-item"><span class="hero-meta-value"><?= e($shippingMinimum) ?></span><span class="hero-meta-label">Miễn phí vận chuyển từ</span></div><div class="hero-meta-divider"></div>
<div class="hero-meta-item"><span class="hero-meta-value"><?= e((string) $homeMetrics['active_products']) ?></span><span class="hero-meta-label">Sản phẩm đang mở bán</span></div><div class="hero-meta-divider"></div>
<div class="hero-meta-item"><span class="hero-meta-value"><?= e($ratingText) ?></span><span class="hero-meta-label"><?= e($ratingLabel) ?></span></div>
</div></div><div class="hero-visual" aria-hidden="true">
<?php if ($bannerImageUrl !== null): ?><img class="hero-image" src="<?= e($bannerImageUrl) ?>" alt="NORD Home — nội thất và đồ gia dụng được tuyển chọn" loading="eager" decoding="async">
<?php else: ?><div class="hero-image-placeholder"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 560 640" role="img" aria-label="NORD Home">
<rect width="560" height="640" fill="#ede5d5"/><g opacity="0.18" stroke="#5a4a38" stroke-width="1.5" fill="none"><rect x="60" y="360" width="440" height="180" rx="20"/><rect x="60" y="320" width="120" height="200" rx="16"/><rect x="380" y="320" width="120" height="200" rx="16"/><rect x="200" y="540" width="40" height="60" rx="6"/><rect x="320" y="540" width="40" height="60" rx="6"/><path d="M440 260 L440 380"/><ellipse cx="440" cy="240" rx="48" ry="36"/></g><text x="50%" y="88%" text-anchor="middle" font-family="Cormorant Garamond, Georgia, serif" font-size="72" fill="#8c7e63" letter-spacing="0.12em" opacity="0.5">NORD</text>
</svg></div><?php endif; ?>
</div></section>