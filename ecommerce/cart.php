<?php
/**
 * NORD HOME — Cart page.
 *
 * URL: cart.php
 *
 * Shows current cart lines and handles quantity/removal actions through
 * the existing session/database cart architecture.
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/cart-data.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!csrf_verify()) {
        flash_set('error', 'Phiên làm việc đã hết hạn. Vui lòng tải lại trang và thử lại.');
        redirect('/cart.php');
    }

    $action = is_string($_POST['cart_action'] ?? null) ? $_POST['cart_action'] : '';
    $itemIdRaw = is_string($_POST['item_id'] ?? null) ? $_POST['item_id'] : '';
    $itemId = ctype_digit($itemIdRaw) ? (int) $itemIdRaw : 0;

    if ($action === 'remove' && $itemId > 0) {
        $result = cart_remove_item($itemId);
        flash_set(!empty($result['ok']) ? 'success' : 'error', !empty($result['ok'])
            ? 'Đã xóa sản phẩm khỏi giỏ hàng.'
            : 'Không thể xóa sản phẩm. Vui lòng thử lại.');
    } elseif ($action === 'update_quantity' && $itemId > 0) {
        $quantityRaw = is_string($_POST['quantity'] ?? null) ? $_POST['quantity'] : '';
        $quantity = ctype_digit($quantityRaw) ? (int) $quantityRaw : 0;
        $result = cart_update_item_quantity($itemId, $quantity);
        if (!empty($result['ok'])) {
            flash_set('success', !empty($result['stock_limited'])
                ? 'Số lượng đã được điều chỉnh theo tồn kho hiện tại.'
                : 'Đã cập nhật số lượng sản phẩm.');
        } else {
            $message = ($result['reason'] ?? '') === 'unavailable'
                ? 'Sản phẩm hiện đã hết hàng hoặc ngừng kinh doanh. Vui lòng xóa sản phẩm khỏi giỏ.'
                : 'Số lượng không hợp lệ hoặc sản phẩm không còn trong giỏ.';
            flash_set('error', $message);
        }
    } else {
        flash_set('error', 'Thao tác giỏ hàng không hợp lệ.');
    }

    redirect('/cart.php');
}

$cartItems = cart_get_items_with_products();
$availableItems = array_values(array_filter($cartItems, function ($i) {
    return empty($i['unavailable']);
}));
$subtotal = 0.0;
foreach ($availableItems as $item) {
    $subtotal += (float) $item['line_total'];
}
$hasUnavailable = count($availableItems) !== count($cartItems);
$cartEmpty = empty($cartItems);

$pageTitle       = 'Giỏ hàng';
$pageDescription = 'Giỏ hàng của bạn tại NORD HOME.';
$bodyClass       = 'page-cart';
$pageCss         = 'checkout.css';
require __DIR__ . '/includes/header.php';
?>

<!-- Breadcrumb -->
<nav class="breadcrumb container" aria-label="Đường dẫn">
    <ol class="breadcrumb-list">
        <li><a href="<?= e(url()) ?>">Trang chủ</a></li>
        <li aria-current="page">Giỏ hàng</li>
    </ol>
</nav>

<section class="checkout-shell container">
    <header class="checkout-header">
        <p class="checkout-eyebrow">Giỏ hàng</p>
        <h1 class="checkout-heading">Giỏ hàng của bạn</h1>
        <p class="checkout-sub">
            <?php if ($cartEmpty): ?>
                Chưa có sản phẩm nào trong giỏ.
            <?php else: ?>
                <?= e((string) array_sum(array_map(fn($i) => (int) $i['requested_quantity'], $cartItems))) ?> sản phẩm đang chờ thanh toán.
            <?php endif; ?>
        </p>
    </header>

    <?php if ($cartEmpty): ?>
        <div class="checkout-empty" role="status">
            <div class="checkout-empty-inner">
                <p class="checkout-empty-eyebrow">Trống</p>
                <h2 class="checkout-empty-heading">Giỏ hàng của bạn đang trống.</h2>
                <p class="checkout-empty-text">Hãy chọn sản phẩm bạn yêu thích trước khi tiến hành thanh toán.</p>
                <div class="checkout-empty-actions">
                    <a class="btn btn--primary btn--lg" href="<?= e(url('products.php')) ?>">Tiếp tục mua sắm</a>
                </div>
            </div>
        </div>
    <?php else: ?>

        <?php if ($hasUnavailable): ?>
            <div class="checkout-flash checkout-flash--warn" role="status">
                <div class="checkout-flash-text">
                    <span class="checkout-flash-icon" aria-hidden="true">
                        <?= icon('close', 'icon icon--sm') ?>
                    </span>
                    <p>Một số sản phẩm không còn khả dụng hoặc số lượng vượt tồn kho. Hãy giảm số lượng hoặc xóa các sản phẩm này trước khi thanh toán.</p>
                </div>
            </div>
        <?php endif; ?>

        <div class="checkout-grid">
            <div class="checkout-fields">
                <fieldset class="checkout-section">
                    <legend class="checkout-section-title">Sản phẩm</legend>
                    <ul class="checkout-summary-list">
                        <?php foreach ($cartItems as $item): ?>
                            <?php $unavail = !empty($item['unavailable']); $requestedQuantity = (int) $item['requested_quantity']; $stock = (int) $item['stock']; ?>
                            <li class="checkout-summary-item<?= $unavail ? ' is-unavailable' : '' ?>">
                                <div class="checkout-summary-thumb">
                                    <?php if (!empty($item['image_path'])):
                                        $imgUrl = storefront_image((string) $item['image_path']);
                                        $fallback = storefront_placeholder_svg((string) $item['name'], storefront_category_tone(1));
                                    ?>
                                        <img src="<?= e($imgUrl ?? $fallback) ?>" alt="<?= e((string) ($item['image_alt'] ?? $item['name'])) ?>" loading="lazy" decoding="async"
                                             onerror="this.onerror=null;this.src='<?= e($fallback) ?>';">
                                    <?php else:
                                        $fallback = storefront_placeholder_svg((string) $item['name'], storefront_category_tone(1));
                                    ?>
                                        <img src="<?= e($fallback) ?>" alt="<?= e((string) $item['name']) ?>">
                                    <?php endif; ?>
                                    <span class="checkout-summary-qty" aria-hidden="true"><?= (int) $item['requested_quantity'] ?></span>
                                </div>
                                <div class="checkout-summary-body">
                                    <p class="checkout-summary-name">
                                        <a href="<?= e(url('product.php?id=' . (int) $item['product_id'])) ?>"><?= e((string) $item['name']) ?></a>
                                    </p>
                                    <?php if ($unavail): ?>
                                        <p class="checkout-summary-status"><?= !empty($item['over_stock']) ? 'Số lượng vượt tồn kho hiện tại' : 'Sản phẩm hiện không khả dụng' ?></p>
                                    <?php else: ?>
                                        <p class="checkout-summary-meta"><?= (int) $item['quantity'] ?> × <?= e(storefront_price((float) $item['price'])) ?></p>
                                    <?php endif; ?>
                                    <div class="cart-line-actions">
                                        <?php if ($stock > 0 && !empty($item['is_active'])): ?>
                                            <form class="cart-quantity-form" method="post" action="<?= e(url('cart.php')) ?>">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="cart_action" value="update_quantity">
                                                <input type="hidden" name="item_id" value="<?= (int) $item['item_id'] ?>">
                                                <button class="cart-quantity-step" type="submit" name="quantity" value="<?= max(1, $requestedQuantity - 1) ?>" aria-label="Giảm số lượng <?= e((string) $item['name']) ?>" <?= $requestedQuantity <= 1 ? 'disabled' : '' ?>>−</button>
                                                <span class="cart-quantity-value" aria-label="Số lượng"><?= $requestedQuantity ?></span>
                                                <button class="cart-quantity-step" type="submit" name="quantity" value="<?= min($stock, $requestedQuantity + 1) ?>" aria-label="Tăng số lượng <?= e((string) $item['name']) ?>" <?= $requestedQuantity >= $stock ? 'disabled' : '' ?>>+</button>
                                            </form>
                                        <?php endif; ?>
                                        <form method="post" action="<?= e(url('cart.php')) ?>">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="cart_action" value="remove">
                                            <input type="hidden" name="item_id" value="<?= (int) $item['item_id'] ?>">
                                            <button class="cart-remove-button" type="submit">Xóa</button>
                                        </form>
                                    </div>
                                </div>
                                <div class="checkout-summary-price">
                                    <?php if (!$unavail): ?>
                                        <?= e(storefront_price((float) $item['line_total'])) ?>
                                    <?php else: ?>
                                        <span class="checkout-summary-strike">—</span>
                                    <?php endif; ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>

                </fieldset>
            </div>

            <aside class="checkout-summary" aria-label="Tóm tắt giỏ hàng">
                <h2 class="checkout-summary-title">Tóm tắt</h2>
                <dl class="checkout-summary-totals">
                    <div class="checkout-summary-row checkout-summary-row--grand">
                        <dt>Tạm tính</dt>
                        <dd><?= e(storefront_price($subtotal)) ?></dd>
                    </div>
                </dl>
                <?php if (!$hasUnavailable): ?>
                <a class="btn btn--primary btn--lg checkout-submit" href="<?= e(url('checkout.php')) ?>">
                    <?= icon('arrow-right', 'icon icon--sm') ?>
                    <span>Tiến hành thanh toán</span>
                </a>
                <?php else: ?>
                <button class="btn btn--primary btn--lg checkout-submit" type="button" disabled aria-disabled="true">
                    <span>Kiểm tra sản phẩm trước khi thanh toán</span>
                </button>
                <?php endif; ?>
                <a class="btn btn--ghost btn--sm checkout-summary-secondary" href="<?= e(url('products.php')) ?>">
                    <span>Tiếp tục mua sắm</span>
                </a>
            </aside>
        </div>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>