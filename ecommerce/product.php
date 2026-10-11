<?php
/**
 * NORD HOME — Product detail page.
 *
 * URL: product.php?id=PRODUCT_ID  (also accepts ?slug=... as a fallback).
 *
 * Loads real data from MySQL via the helpers in includes/product-data.php.
 * Supports:
 *   - Real image gallery (multiple images, primary highlighted)
 *   - Real stock + price + discount (no fabricated values)
 *   - Real Vietnamese-only copy
 *   - Quantity stepper (vanilla JS, max = stock)
 *   - Real add-to-cart via POST → cart-data.php helper
 *   - Vietnamese confirmation + "Xem giỏ hàng" link
 *   - Related products from the same category
 *
 * On invalid id / missing product: a professional 404 page is rendered with
 * no PHP warnings or SQL errors leaking to the customer.
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/shop-data.php';
require_once __DIR__ . '/includes/product-data.php';
require_once __DIR__ . '/includes/cart-data.php';
require_once __DIR__ . '/includes/components/product-card.php';

/* ----- 1. Read and validate the requested product id ----- */
$idRaw = isset($_GET['id']) && is_string($_GET['id']) ? $_GET['id'] : '';
$slugRaw = isset($_GET['slug']) && is_string($_GET['slug']) ? $_GET['slug'] : '';

$productId = 0;
if ($idRaw !== '' && ctype_digit($idRaw)) {
    $productId = (int) $idRaw;
} elseif ($slugRaw !== '') {
    try {
        $stmt = db()->prepare('SELECT id FROM products WHERE slug = :slug AND is_active = 1 LIMIT 1');
        $stmt->execute(['slug' => $slugRaw]);
        $row = $stmt->fetch();
        if ($row) {
            $productId = (int) $row['id'];
        }
    } catch (PDOException $e) {
        $productId = 0;
    }
}

$product = $productId > 0 ? product_find_by_id($productId) : null;

/* ----- 2. Invalid / missing product → professional 404 ----- */
if ($product === null) {
    http_response_code(404);
    $pageTitle = 'Không tìm thấy sản phẩm';
    $pageDescription = 'Sản phẩm bạn đang tìm kiếm không tồn tại hoặc đã được gỡ khỏi cửa hàng.';
    $bodyClass = 'page-product';
    $pageCss = 'product-detail.css';
    require __DIR__ . '/includes/header.php';
    ?>
    <div class="container pd-missing" role="status">
        <div class="pd-missing-inner">
            <p class="pd-missing-eyebrow">404</p>
            <h1 class="pd-missing-heading">Không tìm thấy sản phẩm</h1>
            <p class="pd-missing-text">Xin lỗi, sản phẩm bạn đang tìm kiếm không tồn tại hoặc đã được gỡ khỏi cửa hàng.</p>
            <div class="pd-missing-actions">
                <a class="btn btn--primary btn--lg" href="<?= e(url('products.php')) ?>">Quay lại cửa hàng</a>
                <a class="btn btn--ghost btn--lg" href="<?= e(url()) ?>">Về trang chủ</a>
            </div>
        </div>
    </div>
    <?php
    require __DIR__ . '/includes/footer.php';
    exit;
}

/* ----- 3. Handle POST: add-to-cart ----- */
$cartMessage = null;
$cartMessageOk = false;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!csrf_verify()) {
        $cartMessage = 'Phiên làm việc đã hết hạn. Vui lòng tải lại trang và thử lại.';
        $cartMessageOk = false;
    } else {
        $action = isset($_POST['action']) && is_string($_POST['action']) ? $_POST['action'] : '';
        $qtyPost = isset($_POST['quantity']) && is_string($_POST['quantity']) ? (int) $_POST['quantity'] : 1;
        $productIdPost = isset($_POST['product_id']) && is_string($_POST['product_id']) ? (int) $_POST['product_id'] : 0;

        if ($action === 'add_to_cart' && $productIdPost === (int) $product['id']) {
            $result = cart_add_item((int) $product['id'], $qtyPost);
            if (!empty($result['ok'])) {
                $cartMessageOk = true;
                $cartMessage = 'Đã thêm sản phẩm vào giỏ hàng.';
            } else {
                $cartMessageOk = false;
                $reason = (string) ($result['reason'] ?? '');
                $cartMessage = match ($reason) {
                    'out_of_stock'     => 'Sản phẩm hiện đang tạm hết hàng.',
                    'product_not_found'=> 'Sản phẩm không tồn tại hoặc đã ngừng kinh doanh.',
                    'cart_unavailable' => 'Không thể kết nối tới giỏ hàng. Vui lòng thử lại sau.',
                    'invalid_product'  => 'Mã sản phẩm không hợp lệ.',
                    default            => 'Đã có lỗi xảy ra. Vui lòng thử lại.',
                };
            }
        }
    }
}

/* ----- 4. Compute derived view data ----- */
$gallery     = product_gallery($product);
$stockState  = product_stock_state((int) $product['stock']);
$onSale      = (float) ($product['compare_price'] ?? 0) > 0 && (float) $product['price'] < (float) $product['compare_price'];
$discount    = storefront_discount_percent((float) $product['price'], (float) ($product['compare_price'] ?? 0));
$review      = product_review_summary((int) $product['id']);
$related     = product_related((int) $product['category_id'], (int) $product['id'], 4);

$pageTitle       = $product['name'];
$pageDescription = (string) ($product['short_description'] ?? $product['name']);
$bodyClass       = 'page-product';
$pageCss         = 'product-detail.css';
require __DIR__ . '/includes/header.php';

/* Max quantity the user may pick for this product (1..stock). */
$maxQty = max(1, (int) $stockState['stock']);
?>

<!-- Breadcrumb -->
<nav class="breadcrumb container" aria-label="Đường dẫn">
    <ol class="breadcrumb-list">
        <li><a href="<?= e(url()) ?>">Trang chủ</a></li>
        <li><a href="<?= e(url('products.php')) ?>">Cửa hàng</a></li>
        <?php if (!empty($product['category_slug']) && !empty($product['category_name'])): ?>
            <li>
                <a href="<?= e(url('products.php?category=' . urlencode((string) $product['category_slug']))) ?>">
                    <?= e((string) $product['category_name']) ?>
                </a>
            </li>
        <?php endif; ?>
        <li aria-current="page"><?= e((string) $product['name']) ?></li>
    </ol>
</nav>

<!-- Product detail -->
<section class="pd-shell container">

    <!-- Add-to-cart result banner -->
    <?php if ($cartMessage !== null): ?>
        <div class="pd-flash <?= $cartMessageOk ? 'pd-flash--ok' : 'pd-flash--err' ?>" role="status">
            <div class="pd-flash-text">
                <span class="pd-flash-icon" aria-hidden="true">
                    <?= icon($cartMessageOk ? 'check' : 'close', 'icon icon--sm') ?>
                </span>
                <span><?= e($cartMessage) ?></span>
            </div>
            <?php if ($cartMessageOk): ?>
                <a class="btn btn--ghost btn--sm pd-flash-cta" href="<?= e(url('cart.php')) ?>">Xem giỏ hàng</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="pd-grid">

        <!-- LEFT: image gallery -->
        <div class="pd-gallery">
            <div class="pd-gallery-main" id="pdGalleryMain" data-zoomable="false">
                <?php foreach ($gallery as $i => $img): ?>
                    <img
                        class="pd-gallery-image<?= $i === 0 ? ' is-active' : '' ?>"
                        src="<?= e($img['url']) ?>"
                        alt="<?= e($img['alt']) ?>"
                        data-index="<?= (int) $i ?>"
                        loading="<?= $i === 0 ? 'eager' : 'lazy' ?>"
                        decoding="async"
                        <?= $i === 0 ? '' : 'hidden' ?>>
                <?php endforeach; ?>
            </div>
            <?php if (count($gallery) > 1): ?>
                <div class="pd-gallery-thumbs" role="tablist" aria-label="Ảnh sản phẩm">
                    <?php foreach ($gallery as $i => $img): ?>
                        <button
                            type="button"
                            class="pd-gallery-thumb<?= $i === 0 ? ' is-active' : '' ?>"
                            role="tab"
                            aria-selected="<?= $i === 0 ? 'true' : 'false' ?>"
                            aria-label="Xem ảnh <?= (int) ($i + 1) ?>"
                            data-index="<?= (int) $i ?>">
                            <img src="<?= e($img['url']) ?>" alt="" loading="lazy" decoding="async">
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- RIGHT: product information -->
        <div class="pd-info">

            <?php if (!empty($product['category_name'])): ?>
                <p class="pd-category">
                    <a href="<?= e(url('products.php?category=' . urlencode((string) ($product['category_slug'] ?? '')))) ?>">
                        <?= e((string) $product['category_name']) ?>
                    </a>
                </p>
            <?php endif; ?>

            <h1 class="pd-name"><?= e((string) $product['name']) ?></h1>

            <!-- Rating / reviews -->
            <div class="pd-rating" aria-label="Đánh giá sản phẩm">
                <?php if ($review['count'] > 0): ?>
                    <span class="pd-stars" aria-hidden="true">
                        <?php
                        $filled = (int) round($review['average']);
                        for ($i = 1; $i <= 5; $i++) {
                            $cls = $i <= $filled ? 'is-filled' : '';
                            echo '<span class="pd-star ' . $cls . '">' . icon('star', 'icon icon--sm') . '</span>';
                        }
                        ?>
                    </span>
                    <span class="pd-rating-text">
                        <?= e(rtrim(rtrim(number_format($review['average'], 1, ',', '.'), '0'), ',')) ?>
                        /5 · <?= e((string) $review['count']) ?> đánh giá
                    </span>
                <?php else: ?>
                    <span class="pd-rating-empty">Chưa có đánh giá</span>
                <?php endif; ?>
            </div>

            <!-- Pricing -->
            <div class="pd-pricing">
                <span class="pd-price"><?= e(storefront_price((float) $product['price'])) ?></span>
                <?php if ($onSale): ?>
                    <span class="pd-price-compare"><?= e(storefront_price((float) $product['compare_price'])) ?></span>
                    <span class="badge badge--sale">-<?= (int) $discount ?>%</span>
                <?php endif; ?>
            </div>

            <!-- Stock -->
            <p class="pd-stock pd-stock--<?= e($stockState['key']) ?>">
                <span class="pd-stock-dot" aria-hidden="true"></span>
                <span class="pd-stock-label"><?= e($stockState['label']) ?></span>
                <span class="pd-stock-hint"><?= e($stockState['hint']) ?></span>
            </p>

            <!-- Short description -->
            <?php if (!empty($product['short_description'])): ?>
                <p class="pd-short"><?= e((string) $product['short_description']) ?></p>
            <?php endif; ?>

            <!-- Add to cart -->
            <?php if ($stockState['stock'] > 0): ?>
                <form class="pd-buy" method="post" action="<?= e(url('product.php?id=' . (int) $product['id'])) ?>" id="pdAddToCartForm">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="add_to_cart">
                    <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">

                    <div class="pd-qty" role="group" aria-label="Số lượng">
                        <button type="button" class="pd-qty-btn pd-qty-btn--minus" aria-label="Giảm số lượng" data-dir="minus"><?= icon('minus', 'icon icon--sm') ?></button>
                        <input
                            type="number"
                            class="pd-qty-input"
                            name="quantity"
                            id="pdQtyInput"
                            value="1"
                            min="1"
                            max="<?= (int) $maxQty ?>"
                            step="1"
                            inputmode="numeric"
                            aria-label="Số lượng">
                        <button type="button" class="pd-qty-btn pd-qty-btn--plus" aria-label="Tăng số lượng" data-dir="plus"><?= icon('plus', 'icon icon--sm') ?></button>
                    </div>

                    <button type="submit" class="btn btn--primary btn--lg pd-buy-submit">
                        <?= icon('bag', 'icon icon--sm') ?>
                        <span>Thêm vào giỏ</span>
                    </button>
                </form>
            <?php else: ?>
                <div class="pd-buy pd-buy--disabled" role="status">
                    <button type="button" class="btn btn--primary btn--lg" disabled>
                        <?= icon('close', 'icon icon--sm') ?>
                        <span>Hết hàng</span>
                    </button>
                    <p class="pd-buy-note">Sản phẩm này hiện đang tạm hết. Vui lòng quay lại sau hoặc chọn sản phẩm tương tự.</p>
                </div>
            <?php endif; ?>

            <p class="pd-meta">Mã sản phẩm: <strong><?= e((string) $product['sku']) ?></strong></p>
            <?php if (!empty($product['brand_name'])): ?>
                <p class="pd-meta">Thương hiệu: <strong><?= e((string) $product['brand_name']) ?></strong></p>
            <?php endif; ?>

            <!-- Reassurance badges -->
            <ul class="pd-perks" aria-label="Thông tin dịch vụ">
                <li class="pd-perk">
                    <?= icon('truck', 'icon icon--sm') ?>
                    <a href="<?= e(url('shipping-policy.php')) ?>">Thông tin giao hàng</a>
                </li>
                <li class="pd-perk">
                    <?= icon('shield', 'icon icon--sm') ?>
                    <a href="<?= e(url('contact.php')) ?>">Hỏi về bảo hành</a>
                </li>
                <li class="pd-perk">
                    <?= icon('leaf', 'icon icon--sm') ?>
                    <a href="<?= e(url('return-policy.php')) ?>">Thông tin đổi trả</a>
                </li>
            </ul>
        </div>
    </div>

    <!-- Below: tabs / sections -->
    <?php
    $hasDescription    = !empty($product['description']);
    $hasSpecs          = !empty($product['weight']);
    ?>
    <div class="pd-tabs" role="tablist" aria-label="Thông tin sản phẩm">
        <button type="button" class="pd-tab is-active" role="tab" aria-selected="true" data-tab="desc">Mô tả</button>
        <button type="button" class="pd-tab" role="tab" aria-selected="false" data-tab="spec">Thông tin sản phẩm</button>
        <button type="button" class="pd-tab" role="tab" aria-selected="false" data-tab="ship">Vận chuyển &amp; đổi trả</button>
    </div>
    <div class="pd-panels">
        <section class="pd-panel is-active" role="tabpanel" data-panel="desc">
            <?php if ($hasDescription): ?>
                <div class="pd-prose">
                    <?= nl2br(e((string) $product['description'])) ?>
                </div>
            <?php else: ?>
                <p class="pd-prose-empty">Mô tả chi tiết cho sản phẩm này sẽ được cập nhật sớm.</p>
            <?php endif; ?>
        </section>
        <section class="pd-panel" role="tabpanel" data-panel="spec" hidden>
            <dl class="pd-specs">
                <div class="pd-spec">
                    <dt>Mã sản phẩm (SKU)</dt>
                    <dd><?= e((string) $product['sku']) ?></dd>
                </div>
                <div class="pd-spec">
                    <dt>Danh mục</dt>
                    <dd>
                        <?php if (!empty($product['category_slug']) && !empty($product['category_name'])): ?>
                            <a href="<?= e(url('products.php?category=' . urlencode((string) $product['category_slug']))) ?>">
                                <?= e((string) $product['category_name']) ?>
                            </a>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </dd>
                </div>
                <?php if (!empty($product['brand_name'])): ?>
                    <div class="pd-spec">
                        <dt>Thương hiệu</dt>
                        <dd><?= e((string) $product['brand_name']) ?></dd>
                    </div>
                <?php endif; ?>
                <?php if ($hasSpecs): ?>
                    <div class="pd-spec">
                        <dt>Khối lượng</dt>
                        <dd><?= e(rtrim(rtrim(number_format((float) $product['weight'], 2, ',', '.'), '0'), ',')) ?> kg</dd>
                    </div>
                <?php endif; ?>
                <div class="pd-spec">
                    <dt>Tình trạng</dt>
                    <dd><?= e($stockState['label']) ?> — <?= e($stockState['hint']) ?></dd>
                </div>
            </dl>
        </section>
        <section class="pd-panel" role="tabpanel" data-panel="ship" hidden>
            <div class="pd-prose">
                <p><strong>Giao hàng.</strong> Thời gian 2–4 ngày làm việc là thông tin tham khảo đang được nêu trên cửa hàng; thời gian thực tế có thể thay đổi theo địa chỉ nhận và quá trình vận chuyển. Phí giao hàng được hiển thị tại bước thanh toán. <a href="<?= e(url('shipping-policy.php')) ?>">Xem thông tin giao hàng</a>.</p>
                <p><strong>Đổi trả.</strong> Điều kiện cụ thể cần được xác nhận với cửa hàng trước khi gửi sản phẩm. <a href="<?= e(url('return-policy.php')) ?>">Xem thông tin đổi trả</a>.</p>
                <p><strong>Bảo hành.</strong> Thông tin bảo hành cần được xác nhận theo từng sản phẩm; vui lòng liên hệ trước khi đặt nếu cần làm rõ.</p>
            </div>
        </section>
    </div>
</section>

<?php if (!empty($related)): ?>
<section class="container pd-related">
    <header class="section-header section-header--centered">
        <div class="section-header-text">
            <p class="section-eyebrow">Có thể bạn cũng thích</p>
            <h2 class="section-heading">Sản phẩm liên quan</h2>
        </div>
    </header>
    <div class="product-grid product-grid--shop">
        <?php foreach ($related as $r): ?>
            <?php render_product_card($r); ?>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<script>
/* ----- Product detail — gallery + quantity stepper ----- */
(function () {
    /* Gallery thumbnail switching */
    var mainImages = document.querySelectorAll('.pd-gallery-image');
    var thumbs = document.querySelectorAll('.pd-gallery-thumb');
    if (mainImages.length > 1 && thumbs.length) {
        thumbs.forEach(function (btn) {
            btn.addEventListener('click', function () {
                var idx = parseInt(btn.getAttribute('data-index') || '0', 10);
                if (isNaN(idx) || idx < 0 || idx >= mainImages.length) return;
                mainImages.forEach(function (img, i) {
                    var on = i === idx;
                    img.classList.toggle('is-active', on);
                    if (on) {
                        img.removeAttribute('hidden');
                    } else {
                        img.setAttribute('hidden', '');
                    }
                });
                thumbs.forEach(function (t, i) {
                    var onT = i === idx;
                    t.classList.toggle('is-active', onT);
                    t.setAttribute('aria-selected', onT ? 'true' : 'false');
                });
            });
        });
    }

    /* Quantity stepper — clamped to [1, max] */
    var form = document.getElementById('pdAddToCartForm');
    if (form) {
        var input = form.querySelector('.pd-qty-input');
        var max = Math.max(1, parseInt(input.getAttribute('max') || '1', 10) || 1);
        var min = 1;

        function setQty(n) {
            if (isNaN(n)) n = min;
            n = Math.max(min, Math.min(max, n));
            input.value = String(n);
        }

        form.querySelectorAll('.pd-qty-btn').forEach(function (b) {
            b.addEventListener('click', function () {
                var dir = b.getAttribute('data-dir');
                var cur = parseInt(input.value || '1', 10) || 1;
                if (dir === 'plus')  setQty(cur + 1);
                if (dir === 'minus') setQty(cur - 1);
            });
        });

        input.addEventListener('input', function () {
            var v = parseInt(input.value || '1', 10) || 1;
            setQty(v);
        });
        input.addEventListener('blur', function () {
            setQty(parseInt(input.value || '1', 10) || 1);
        });
    }

    /* Tabs */
    var tabBtns = document.querySelectorAll('.pd-tab');
    var panels  = document.querySelectorAll('.pd-panel');
    if (tabBtns.length && panels.length) {
        tabBtns.forEach(function (btn) {
            btn.addEventListener('click', function () {
                var key = btn.getAttribute('data-tab');
                tabBtns.forEach(function (b) {
                    var on = b === btn;
                    b.classList.toggle('is-active', on);
                    b.setAttribute('aria-selected', on ? 'true' : 'false');
                });
                panels.forEach(function (p) {
                    var match = p.getAttribute('data-panel') === key;
                    p.classList.toggle('is-active', match);
                    if (match) {
                        p.removeAttribute('hidden');
                    } else {
                        p.setAttribute('hidden', '');
                    }
                });
            });
        });
    }
})();
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>