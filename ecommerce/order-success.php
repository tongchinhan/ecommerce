<?php
/**
 * NORD HOME — Order success page.
 *
 * URL: order-success.php?order=ORDER_NUMBER
 *
 * Loads a finalized order by its order number and renders a Vietnamese
 * confirmation screen. The order must belong to the current visitor
 * (admins can view any).
 *
 * This page is read-only. Refreshing it has no effect on the database
 * (Post/Redirect/Get is enforced on checkout.php itself, so the order is
 * already created before the user ever lands here).
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/cart-data.php';
require_once __DIR__ . '/includes/order-data.php';

/* Auth required to see an order. Anyone not logged in is sent to login. */
if (!is_logged_in()) {
    flash_set('error', 'Vui lòng đăng nhập để xem đơn hàng của bạn.');
    redirect('/login.php?next=' . urlencode('/order-success.php?order=' . urlencode((string) ($_GET['order'] ?? ''))));
}

$orderNumber = isset($_GET['order']) && is_string($_GET['order']) ? (string) $_GET['order'] : '';
$order       = $orderNumber !== '' ? order_find_by_number($orderNumber) : null;

$pageTitle       = $orderNumber !== '' ? ('Đặt hàng thành công — ' . $orderNumber) : 'Đặt hàng thành công';
$pageDescription = 'Đơn hàng NORD HOME của bạn đã được ghi nhận.';
$bodyClass       = 'page-order-success';
$pageCss         = 'checkout.css';
require __DIR__ . '/includes/header.php';
?>

<!-- Breadcrumb -->
<nav class="breadcrumb container" aria-label="Đường dẫn">
    <ol class="breadcrumb-list">
        <li><a href="<?= e(url()) ?>">Trang chủ</a></li>
        <li><a href="<?= e(url('products.php')) ?>">Cửa hàng</a></li>
        <li aria-current="page">Đặt hàng thành công</li>
    </ol>
</nav>

<section class="success-shell container">

    <?php if ($order === null): ?>
        <!-- Order not found / not yours -->
        <div class="checkout-empty" role="status">
            <div class="checkout-empty-inner">
                <p class="checkout-empty-eyebrow">404</p>
                <h2 class="checkout-empty-heading">Không tìm thấy đơn hàng.</h2>
                <p class="checkout-empty-text">Đơn hàng bạn yêu cầu không tồn tại hoặc đã được xử lý.</p>
                <div class="checkout-empty-actions">
                    <a class="btn btn--primary btn--lg" href="<?= e(url('products.php')) ?>">Tiếp tục mua sắm</a>
                </div>
            </div>
        </div>
    <?php else:
        $orderItems   = order_items_for((int) $order['id']);
        $orderPayment = order_payments_for((int) $order['id']);
        $firstPay     = !empty($orderPayment) ? $orderPayment[0] : null;
        $paymentMethods = checkout_payment_methods();
        $bankTransferSettings = checkout_bank_transfer_settings();
        $bankTransferConfigured = $bankTransferSettings['bank_name'] !== '' && $bankTransferSettings['account_number'] !== '';
        $methodLabel = $firstPay !== null && isset($paymentMethods[(string) $firstPay['method']])
            ? $paymentMethods[(string) $firstPay['method']]['label']
            : (string) ($firstPay['method'] ?? '—');

        /* Vietnamese status labels for the existing schema values. */
        $statusLabels = [
            ORDER_PENDING    => 'Chờ xác nhận',
            ORDER_CONFIRMED  => 'Đã xác nhận',
            ORDER_PROCESSING => 'Đang xử lý',
            ORDER_SHIPPED    => 'Đang giao',
            ORDER_DELIVERED  => 'Đã giao',
            ORDER_CANCELLED  => 'Đã hủy',
        ];
        $statusLabel = $statusLabels[$order['status']] ?? (string) $order['status'];

        $paymentLabels = [
            'unpaid'    => 'Chưa thanh toán',
            'pending'   => 'Đang chờ thanh toán',
            'paid'      => 'Đã thanh toán',
            'failed'    => 'Thanh toán thất bại',
            'refunded'  => 'Đã hoàn tiền',
        ];
        $paymentLabel = $paymentLabels[(string) $order['payment_status']] ?? (string) $order['payment_status'];
    ?>

        <header class="success-header">
            <div class="success-icon" aria-hidden="true"><?= icon('check', 'icon icon--md') ?></div>
            <p class="success-eyebrow">Cảm ơn bạn đã đặt hàng</p>
            <h1 class="success-heading">Đặt hàng thành công!</h1>
            <p class="success-sub">Đơn hàng của bạn đã được ghi nhận.</p>
        </header>

        <div class="success-card">
            <dl class="success-meta">
                <div class="success-meta-row">
                    <dt>Mã đơn hàng</dt>
                    <dd><strong><?= e((string) $order['order_number']) ?></strong></dd>
                </div>
                <div class="success-meta-row">
                    <dt>Phương thức thanh toán</dt>
                    <dd><?= e($methodLabel) ?></dd>
                </div>
                <div class="success-meta-row">
                    <dt>Trạng thái thanh toán</dt>
                    <dd><?= e($paymentLabel) ?></dd>
                </div>
                <div class="success-meta-row">
                    <dt>Trạng thái đơn hàng</dt>
                    <dd><?= e($statusLabel) ?></dd>
                </div>
                <div class="success-meta-row">
                    <dt>Ngày đặt</dt>
                    <dd><?= e(format_date((string) $order['created_at'], 'd/m/Y H:i')) ?></dd>
                </div>
                <div class="success-meta-row success-meta-row--total">
                    <dt>Tổng tiền</dt>
                    <dd><strong><?= e(storefront_price((float) $order['total'])) ?></strong></dd>
                </div>
            </dl>

            <?php if ($firstPay !== null && (string) $firstPay['method'] === 'bank_transfer'): ?>
                <section class="checkout-bank-confirmation" aria-labelledby="bank-transfer-title">
                    <h2 class="success-section-title" id="bank-transfer-title">Thông tin chuyển khoản</h2>
                    <?php if ($bankTransferSettings['bank_name'] !== ''): ?><p><span>Ngân hàng</span><strong><?= e($bankTransferSettings['bank_name']) ?></strong></p><?php endif; ?>
                    <?php if ($bankTransferSettings['account_holder'] !== ''): ?><p><span>Chủ tài khoản</span><strong><?= e($bankTransferSettings['account_holder']) ?></strong></p><?php endif; ?>
                    <?php if ($bankTransferSettings['account_number'] !== ''): ?><p><span>Số tài khoản</span><strong><?= e($bankTransferSettings['account_number']) ?></strong></p><?php endif; ?>
                    <?php if ($bankTransferSettings['branch'] !== ''): ?><p><span>Chi nhánh</span><strong><?= e($bankTransferSettings['branch']) ?></strong></p><?php endif; ?>
                    <p><span>Số tiền</span><strong><?= e(storefront_price((float) $order['total'])) ?></strong></p>
                    <p><span>Nội dung chuyển khoản</span><strong><?= e((string) $order['order_number']) ?></strong></p>
                    <?php if (!$bankTransferConfigured): ?>
                        <div class="checkout-bank-missing">Thông tin ngân hàng chưa được cấu hình. Vui lòng liên hệ NORD HOME để xác nhận trước khi chuyển khoản. Đơn hàng vẫn đang chờ thanh toán.</div>
                    <?php else: ?>
                        <p class="checkout-bank-disclaimer">Đơn hàng đang chờ xác nhận thanh toán. Đơn hàng chỉ được xác nhận sau khi cửa hàng kiểm tra giao dịch.</p>
                    <?php endif; ?>
                </section>
            <?php endif; ?>

            <h2 class="success-section-title">Sản phẩm trong đơn</h2>
            <ul class="checkout-summary-list">
                <?php foreach ($orderItems as $item): ?>
                    <li class="checkout-summary-item">
                        <div class="checkout-summary-thumb">
                            <?php
                                $imgUrl = storefront_image((string) ($item['image_path'] ?? ''));
                                $fallback = storefront_placeholder_svg((string) $item['product_name'], storefront_category_tone(1));
                            ?>
                            <img src="<?= e($imgUrl ?? $fallback) ?>" alt="<?= e((string) ($item['image_alt'] ?? $item['product_name'])) ?>" loading="lazy" decoding="async"
                                 onerror="this.onerror=null;this.src='<?= e($fallback) ?>';">
                            <span class="checkout-summary-qty" aria-hidden="true"><?= (int) $item['quantity'] ?></span>
                        </div>
                        <div class="checkout-summary-body">
                            <p class="checkout-summary-name"><?= e((string) $item['product_name']) ?></p>
                            <p class="checkout-summary-meta"><?= (int) $item['quantity'] ?> × <?= e(storefront_price((float) $item['unit_price'])) ?></p>
                        </div>
                        <div class="checkout-summary-price"><?= e(storefront_price((float) $item['total'])) ?></div>
                    </li>
                <?php endforeach; ?>
            </ul>

            <h2 class="success-section-title">Địa chỉ nhận hàng</h2>
            <address class="success-address">
                <strong><?= e((string) $order['shipping_name']) ?></strong><br>
                <?= e((string) $order['shipping_phone']) ?>
                <?php if (!empty($order['shipping_email'])): ?>
                    · <?= e((string) $order['shipping_email']) ?>
                <?php endif; ?>
                <br>
                <?= e((string) $order['shipping_address']) ?><br>
                <?php if (!empty($order['shipping_ward'])): ?>
                    <?= e((string) $order['shipping_ward']) ?>,&nbsp;
                <?php endif; ?>
                <?php if (!empty($order['shipping_district'])): ?>
                    <?= e((string) $order['shipping_district']) ?>,&nbsp;
                <?php endif; ?>
                <?= e((string) $order['shipping_city']) ?>
            </address>

            <?php if (!empty($order['notes'])): ?>
                <h2 class="success-section-title">Ghi chú</h2>
                <p class="success-notes"><?= e((string) $order['notes']) ?></p>
            <?php endif; ?>

            <dl class="success-totals">
                <div class="checkout-summary-row">
                    <dt>Tạm tính</dt>
                    <dd><?= e(storefront_price((float) $order['subtotal'])) ?></dd>
                </div>
                <div class="checkout-summary-row">
                    <dt>Phí vận chuyển</dt>
                    <dd>
                        <?php if ((float) $order['shipping_fee'] <= 0): ?>
                            <span class="checkout-summary-free">Miễn phí vận chuyển</span>
                        <?php else: ?>
                            <?= e(storefront_price((float) $order['shipping_fee'])) ?>
                        <?php endif; ?>
                    </dd>
                </div>
                <?php if ((float) $order['discount'] > 0): ?>
                    <div class="checkout-summary-row checkout-summary-row--discount">
                        <dt>Giảm giá</dt>
                        <dd>-<?= e(storefront_price((float) $order['discount'])) ?></dd>
                    </div>
                <?php endif; ?>
                <div class="checkout-summary-row checkout-summary-row--grand">
                    <dt>Tổng cộng</dt>
                    <dd><?= e(storefront_price((float) $order['total'])) ?></dd>
                </div>
            </dl>
        </div>

        <div class="success-actions">
            <a class="btn btn--primary btn--lg" href="<?= e(url('products.php')) ?>">Tiếp tục mua sắm</a>
            <?php if (is_logged_in()): ?>
                <a class="btn btn--ghost btn--lg" href="<?= e(url('account/orders.php')) ?>">Xem đơn hàng</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>