<?php
/**
 * NORD HOME — Checkout page.
 *
 * URL: checkout.php
 *
 * GET  → render the checkout form (customer info + order summary + payment)
 * POST → validate inputs, run the transactional order_create(), redirect
 *        to order-success.php on success, or re-render with errors.
 *
 * Authentication:
 *   The existing `orders.user_id` column is NOT NULL, so checkout requires
 *   a logged-in customer. Unauthenticated visitors are sent to login.php
 *   with a return destination back to checkout.php.
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/cart-data.php';
require_once __DIR__ . '/includes/order-data.php';

/* ---------- Authentication gate ----------
   Honor both ?next= and ?redirect= when sending guests to the login page
   so they come back here after authentication. */
$nextParam = isset($_GET['next']) && $_GET['next'] !== ''
    ? '/checkout.php?next=' . urlencode((string) $_GET['next'])
    : (isset($_GET['redirect']) && $_GET['redirect'] !== ''
        ? '/checkout.php?next=' . urlencode((string) $_GET['redirect'])
        : '/checkout.php');

if (!is_logged_in()) {
    flash_set('error', 'Bạn cần đăng nhập để tiếp tục thanh toán.');
    redirect('/login.php?next=' . urlencode($nextParam));
}

/* ---------- Handle POST (create order) ---------- */
$formErrors  = [];
$oldInput    = []; // preserved across re-renders
$orderResult = null;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!csrf_verify()) {
        $formErrors[] = 'Phiên làm việc đã hết hạn. Vui lòng tải lại trang và thử lại.';
    } else {
        $oldInput = [
            'shipping_name'    => trim((string) ($_POST['shipping_name'] ?? '')),
            'shipping_phone'   => trim((string) ($_POST['shipping_phone'] ?? '')),
            'shipping_email'   => trim((string) ($_POST['shipping_email'] ?? '')),
            'shipping_address' => trim((string) ($_POST['shipping_address'] ?? '')),
            'shipping_city'    => trim((string) ($_POST['shipping_city'] ?? '')),
            'shipping_district'=> trim((string) ($_POST['shipping_district'] ?? '')),
            'shipping_ward'    => trim((string) ($_POST['shipping_ward'] ?? '')),
            'notes'            => trim((string) ($_POST['notes'] ?? '')),
            'payment_method'   => trim((string) ($_POST['payment_method'] ?? '')),
        ];
        $orderResult = order_create($oldInput);
        if (!empty($orderResult['ok'])) {
            /* Post-Redirect-Get: prevents double-submit on refresh. */
            flash_set('success', 'Đặt hàng thành công!');
            redirect('/order-success.php?order=' . urlencode((string) $orderResult['order_number']));
        }
        $formErrors[] = (string) ($orderResult['message'] ?? 'Không thể tạo đơn hàng. Vui lòng thử lại.');
    }
}

/* ---------- Load cart for display + summary ---------- */
$cartItems    = cart_get_items_with_products();
$cartEmpty    = empty($cartItems);
$availableItems = array_values(array_filter($cartItems, function ($i) {
    return empty($i['unavailable']);
}));
$hasUnavailable = count($availableItems) !== count($cartItems);

$subtotal = 0.0;
foreach ($availableItems as $item) {
    $subtotal += (float) $item['line_total'];
}
$ship = checkout_shipping_fee($subtotal);
$shippingFee = (float) $ship['shipping_fee'];
$total       = $subtotal + $shippingFee;

$paymentMethods = checkout_payment_methods();
$bankTransferSettings = checkout_bank_transfer_settings();
$bankTransferConfigured = $bankTransferSettings['bank_name'] !== '' && $bankTransferSettings['account_number'] !== '';

/* Prefill defaults for the form (only used on first GET, not after error) */
$user = current_user();
if (empty($oldInput)) {
    /* Try to prefill from the user's default address (if any). Defensive
       — failure to read it just leaves the form blank. */
    $prefill = [
        'shipping_name'  => (string) ($user['name']  ?? ''),
        'shipping_phone' => (string) ($user['phone'] ?? ''),
        'shipping_email' => (string) ($user['email'] ?? ''),
    ];
    try {
        $stmt = db()->prepare(
            'SELECT full_name, phone, address_line, ward, district, city
             FROM addresses
             WHERE user_id = :uid AND is_default = 1
             ORDER BY id ASC LIMIT 1'
        );
        $stmt->execute(['uid' => (int) $user['id']]);
        $addr = $stmt->fetch();
        if ($addr) {
            if (!empty($addr['full_name']))    $prefill['shipping_name']    = (string) $addr['full_name'];
            if (!empty($addr['phone']))        $prefill['shipping_phone']   = (string) $addr['phone'];
            if (!empty($addr['address_line'])) $prefill['shipping_address'] = (string) $addr['address_line'];
            if (!empty($addr['city']))         $prefill['shipping_city']    = (string) $addr['city'];
            if (!empty($addr['district']))     $prefill['shipping_district']= (string) $addr['district'];
            if (!empty($addr['ward']))         $prefill['shipping_ward']    = (string) $addr['ward'];
        }
    } catch (PDOException $e) {
        // ignore
    }
    $oldInput = $prefill;
}

/* ---------- SEO & header ---------- */
$pageTitle       = 'Thanh toán';
$pageDescription = 'Hoàn tất đơn hàng NORD HOME của bạn.';
$bodyClass       = 'page-checkout';
$pageCss         = 'checkout.css';
require __DIR__ . '/includes/header.php';
?>

<!-- Breadcrumb -->
<nav class="breadcrumb container" aria-label="Đường dẫn">
    <ol class="breadcrumb-list">
        <li><a href="<?= e(url()) ?>">Trang chủ</a></li>
        <li><a href="<?= e(url('cart.php')) ?>">Giỏ hàng</a></li>
        <li aria-current="page">Thanh toán</li>
    </ol>
</nav>

<section class="checkout-shell container">

    <header class="checkout-header">
        <p class="checkout-eyebrow">Bước cuối</p>
        <h1 class="checkout-heading">Thanh toán</h1>
        <p class="checkout-sub">Vui lòng điền thông tin nhận hàng để hoàn tất đơn hàng.</p>
    </header>

    <?php if ($cartEmpty): ?>
        <!-- Empty cart: cannot checkout -->
        <div class="checkout-empty" role="status">
            <div class="checkout-empty-inner">
                <p class="checkout-empty-eyebrow">Giỏ hàng</p>
                <h2 class="checkout-empty-heading">Giỏ hàng của bạn đang trống.</h2>
                <p class="checkout-empty-text">Hãy chọn sản phẩm bạn yêu thích trước khi tiến hành thanh toán.</p>
                <div class="checkout-empty-actions">
                    <a class="btn btn--primary btn--lg" href="<?= e(url('products.php')) ?>">Tiếp tục mua sắm</a>
                </div>
            </div>
        </div>
    <?php else: ?>

        <?php if (!empty($formErrors)): ?>
            <div class="checkout-flash checkout-flash--err" role="alert">
                <div class="checkout-flash-text">
                    <span class="checkout-flash-icon" aria-hidden="true">
                        <?= icon('close', 'icon icon--sm') ?>
                    </span>
                    <div>
                        <?php foreach ($formErrors as $err): ?>
                            <p><?= e($err) ?></p>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($hasUnavailable): ?>
            <div class="checkout-flash checkout-flash--warn" role="status">
                <div class="checkout-flash-text">
                    <span class="checkout-flash-icon" aria-hidden="true">
                        <?= icon('close', 'icon icon--sm') ?>
                    </span>
                    <p>Một số sản phẩm không còn khả dụng hoặc số lượng vượt tồn kho. Hãy cập nhật giỏ hàng trước khi đặt đơn.</p>
                    <a href="<?= e(url('cart.php')) ?>" class="btn btn--ghost btn--sm">Quay lại giỏ hàng</a>
                </div>
            </div>
        <?php endif; ?>

        <form class="checkout-form" method="post" action="<?= e(url('checkout.php')) ?>" id="checkoutForm" novalidate>
            <?= csrf_field() ?>

            <div class="checkout-grid">

                <!-- LEFT: customer info + payment -->
                <div class="checkout-fields">
                    <fieldset class="checkout-section">
                        <legend class="checkout-section-title">Thông tin nhận hàng</legend>

                        <div class="checkout-row checkout-row--2">
                            <label class="checkout-field">
                                <span class="checkout-label">Họ và tên <em>*</em></span>
                                <input class="checkout-input" type="text" name="shipping_name"
                                       value="<?= e($oldInput['shipping_name'] ?? '') ?>"
                                       required minlength="2" maxlength="120" autocomplete="name">
                            </label>
                            <label class="checkout-field">
                                <span class="checkout-label">Số điện thoại <em>*</em></span>
                                <input class="checkout-input" type="tel" name="shipping_phone"
                                       value="<?= e($oldInput['shipping_phone'] ?? '') ?>"
                                       required inputmode="tel" autocomplete="tel"
                                       placeholder="VD: 0901234567">
                            </label>
                        </div>

                        <label class="checkout-field">
                            <span class="checkout-label">Email</span>
                            <input class="checkout-input" type="email" name="shipping_email"
                                   value="<?= e($oldInput['shipping_email'] ?? '') ?>"
                                   autocomplete="email" placeholder="email@example.com">
                        </label>

                        <label class="checkout-field">
                            <span class="checkout-label">Địa chỉ nhận hàng <em>*</em></span>
                            <input class="checkout-input" type="text" name="shipping_address"
                                   value="<?= e($oldInput['shipping_address'] ?? '') ?>"
                                   required minlength="5" maxlength="255"
                                   placeholder="Số nhà, ngõ, đường..."
                                   autocomplete="street-address">
                        </label>

                        <div class="checkout-row checkout-row--3">
                            <label class="checkout-field">
                                <span class="checkout-label">Tỉnh / Thành phố <em>*</em></span>
                                <input class="checkout-input" type="text" name="shipping_city"
                                       value="<?= e($oldInput['shipping_city'] ?? '') ?>"
                                       required minlength="2" maxlength="120" autocomplete="address-level2">
                            </label>
                            <label class="checkout-field">
                                <span class="checkout-label">Quận / Huyện</span>
                                <input class="checkout-input" type="text" name="shipping_district"
                                       value="<?= e($oldInput['shipping_district'] ?? '') ?>"
                                       maxlength="120" autocomplete="address-level3">
                            </label>
                            <label class="checkout-field">
                                <span class="checkout-label">Phường / Xã</span>
                                <input class="checkout-input" type="text" name="shipping_ward"
                                       value="<?= e($oldInput['shipping_ward'] ?? '') ?>"
                                       maxlength="120" autocomplete="address-level4">
                            </label>
                        </div>

                        <label class="checkout-field">
                            <span class="checkout-label">Ghi chú đơn hàng</span>
                            <textarea class="checkout-input checkout-input--textarea" name="notes"
                                      maxlength="500" rows="3"
                                      placeholder="Ví dụ: giao giờ hành chính, hướng dẫn giao hàng..."><?= e($oldInput['notes'] ?? '') ?></textarea>
                        </label>
                    </fieldset>

                    <fieldset class="checkout-section">
                        <legend class="checkout-section-title">Phương thức thanh toán</legend>
                        <div class="checkout-payments">
                            <?php $firstMethod = true; foreach ($paymentMethods as $method): ?>
                                <label class="checkout-payment">
                                    <input type="radio"
                                           name="payment_method"
                                           value="<?= e($method['key']) ?>"
                                           <?= ($oldInput['payment_method'] ?? '') === $method['key'] || ($firstMethod && empty($oldInput['payment_method'])) ? 'checked' : '' ?>
                                           required>
                                    <span class="checkout-payment-radio" aria-hidden="true"></span>
                                    <span class="checkout-payment-body">
                                        <span class="checkout-payment-label"><?= e($method['label']) ?></span>
                                        <span class="checkout-payment-desc"><?= e($method['description']) ?></span>
                                        <?php if ($method['key'] === 'bank_transfer'): ?>
                                            <span class="checkout-bank-instructions" hidden>
                                                <strong>Thông tin chuyển khoản</strong>
                                                <?php if ($bankTransferSettings['bank_name'] !== ''): ?><span class="checkout-bank-row"><span>Ngân hàng</span><b><?= e($bankTransferSettings['bank_name']) ?></b></span><?php endif; ?>
                                                <?php if ($bankTransferSettings['account_holder'] !== ''): ?><span class="checkout-bank-row"><span>Chủ tài khoản</span><b><?= e($bankTransferSettings['account_holder']) ?></b></span><?php endif; ?>
                                                <?php if ($bankTransferSettings['account_number'] !== ''): ?><span class="checkout-bank-row"><span>Số tài khoản</span><b><?= e($bankTransferSettings['account_number']) ?></b></span><?php endif; ?>
                                                <?php if ($bankTransferSettings['branch'] !== ''): ?><span class="checkout-bank-row"><span>Chi nhánh</span><b><?= e($bankTransferSettings['branch']) ?></b></span><?php endif; ?>
                                                <span class="checkout-bank-row"><span>Số tiền</span><b><?= e(storefront_price($total)) ?></b></span>
                                                <span class="checkout-bank-row"><span>Nội dung chuyển khoản</span><b>Mã đơn hàng sẽ được cung cấp sau khi đặt hàng.</b></span>
                                                <?php if (!$bankTransferConfigured): ?>
                                                    <span class="checkout-bank-missing">Thông tin ngân hàng chưa được cấu hình. Vui lòng liên hệ NORD HOME trước khi chuyển khoản.</span>
                                                <?php endif; ?>
                                            </span>
                                        <?php endif; ?>
                                    </span>
                                </label>
                                <?php $firstMethod = false; ?>
                            <?php endforeach; ?>
                        </div>
                    </fieldset>
                </div>

                <!-- RIGHT: order summary -->
                <aside class="checkout-summary" aria-label="Tóm tắt đơn hàng">
                    <h2 class="checkout-summary-title">Đơn hàng của bạn</h2>

                    <ul class="checkout-summary-list">
                        <?php foreach ($cartItems as $item): ?>
                            <?php $unavail = !empty($item['unavailable']); ?>
                            <li class="checkout-summary-item<?= $unavail ? ' is-unavailable' : '' ?>">
                                <div class="checkout-summary-thumb">
                                    <?php if (!empty($item['image_path'])):
                                        $imgUrl = storefront_image((string) $item['image_path']);
                                        $fallback = storefront_placeholder_svg((string) $item['name'], storefront_category_tone(1));
                                    ?>
                                        <img src="<?= e($imgUrl ?? $fallback) ?>" alt="<?= e((string) $item['image_alt']) ?>" loading="lazy" decoding="async"
                                             onerror="this.onerror=null;this.src='<?= e($fallback) ?>';">
                                    <?php else:
                                        $fallback = storefront_placeholder_svg((string) $item['name'], storefront_category_tone(1));
                                    ?>
                                        <img src="<?= e($fallback) ?>" alt="<?= e((string) $item['name']) ?>">
                                    <?php endif; ?>
                                    <span class="checkout-summary-qty" aria-hidden="true"><?= (int) $item['requested_quantity'] ?></span>
                                </div>
                                <div class="checkout-summary-body">
                                    <p class="checkout-summary-name"><?= e((string) $item['name']) ?></p>
                                    <?php if ($unavail): ?>
                                        <p class="checkout-summary-status">Sản phẩm hiện không khả dụng</p>
                                    <?php else: ?>
                                        <p class="checkout-summary-meta"><?= (int) $item['quantity'] ?> × <?= e(storefront_price((float) $item['price'])) ?></p>
                                    <?php endif; ?>
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

                    <dl class="checkout-summary-totals">
                        <div class="checkout-summary-row">
                            <dt>Tạm tính</dt>
                            <dd><?= e(storefront_price($subtotal)) ?></dd>
                        </div>
                        <div class="checkout-summary-row">
                            <dt>Phí vận chuyển</dt>
                            <dd>
                                <?php if ($ship['free_shipping']): ?>
                                    <span class="checkout-summary-free">Miễn phí vận chuyển</span>
                                <?php else: ?>
                                    <?= e(storefront_price($shippingFee)) ?>
                                <?php endif; ?>
                            </dd>
                        </div>
                        <?php if ($ship['free_shipping'] && $ship['threshold'] > 0): ?>
                            <div class="checkout-summary-note">
                                Đơn hàng của bạn đã được miễn phí vận chuyển (từ <?= e(storefront_price($ship['threshold'])) ?>).
                            </div>
                        <?php elseif ($ship['threshold'] > 0): ?>
                            <div class="checkout-summary-note">
                                Mua thêm <?= e(storefront_price($ship['threshold'] - $subtotal)) ?> để được miễn phí vận chuyển.
                            </div>
                        <?php endif; ?>
                        <div class="checkout-summary-row checkout-summary-row--discount">
                            <dt>Giảm giá</dt>
                            <dd><?= e(storefront_price(0)) ?></dd>
                        </div>
                        <div class="checkout-summary-row checkout-summary-row--grand">
                            <dt>Tổng cộng</dt>
                            <dd><?= e(storefront_price($total)) ?></dd>
                        </div>
                    </dl>

                    <button class="btn btn--primary btn--lg checkout-submit" type="submit" <?= $hasUnavailable ? 'disabled aria-disabled="true"' : '' ?>>
                        <?= icon('check', 'icon icon--sm') ?>
                        <?php if ($hasUnavailable): ?><span>Cập nhật giỏ hàng trước khi đặt</span><?php else: ?><span>Đặt hàng</span><?php endif; ?>
                    </button>

                    <p class="checkout-summary-disclaimer">
                        Bằng việc đặt hàng, bạn đồng ý với các điều khoản mua hàng của NORD HOME.
                    </p>
                </aside>
            </div>
        </form>
    <?php endif; ?>
</section>

<script>
/* ----- Checkout — simple client-side guards to avoid obvious submits ----- */
(function () {
    var form = document.getElementById('checkoutForm');
    if (!form) return;

    var paymentInputs = form.querySelectorAll('input[name="payment_method"]');
    function syncBankInstructions() {
        paymentInputs.forEach(function (input) {
            var card = input.closest('.checkout-payment');
            var panel = card ? card.querySelector('.checkout-bank-instructions') : null;
            var selected = input.checked;
            if (card) card.classList.toggle('is-selected', selected);
            if (panel) panel.hidden = !selected;
        });
    }
    paymentInputs.forEach(function (input) {
        input.addEventListener('change', syncBankInstructions);
    });
    syncBankInstructions();

    var submitBtn = form.querySelector('.checkout-submit');
    form.addEventListener('submit', function () {
        /* Disable the button so a rapid double-click can't submit twice.
           The server uses Post/Redirect/Get anyway, but this also gives
           immediate visual feedback. */
        if (submitBtn) {
            submitBtn.setAttribute('disabled', 'disabled');
            submitBtn.classList.add('is-loading');
            var label = submitBtn.querySelector('span');
            if (label) label.textContent = 'Đang xử lý...';
        }
    });
})();
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>