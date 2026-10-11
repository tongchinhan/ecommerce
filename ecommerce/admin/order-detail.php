<?php
/**
 * Admin — Order detail with status & payment updates.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/admin-auth.php';
require_once dirname(__DIR__) . '/includes/admin-data.php';
require_once dirname(__DIR__) . '/includes/admin-display.php';
require_once dirname(__DIR__) . '/includes/order-data.php';

$pageTitle = 'Chi tiết đơn hàng';

$id = (int) ($_GET['id'] ?? 0);
$order = $id > 0 ? admin_order_find($id) : null;
if (!$order) {
    flash_set('error', 'Không tìm thấy đơn hàng.');
    redirect('/admin/orders.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'update_status') {
        $status = (string) ($_POST['status'] ?? '');
        $paymentStatus = (string) ($_POST['payment_status'] ?? '');
        $result = admin_order_update_status((int) $order['id'], $status, $paymentStatus);
        flash_set($result['ok'] ? 'success' : 'error', $result['message']);
        redirect('/admin/order-detail.php?id=' . (int) $order['id']);
    }
}

$items    = order_items_for((int) $order['id']);
$payments = order_payments_for((int) $order['id']);

$allowedStatuses = [ORDER_PENDING, ORDER_CONFIRMED, ORDER_PROCESSING, ORDER_SHIPPED, ORDER_DELIVERED, ORDER_CANCELLED];
$allowedPayments = [PAYMENT_UNPAID, PAYMENT_PAID, PAYMENT_FAILED, PAYMENT_REFUNDED];

require dirname(__DIR__) . '/includes/admin-header.php';
?>
<div class="admin-card">
    <h1>Đơn hàng #<?= e($order['order_number']) ?></h1>
    <p class="admin-card-sub">
        Ngày tạo: <?= e(format_date($order['created_at'])) ?> · Cập nhật: <?= e(format_date($order['updated_at'])) ?>
        &nbsp;·&nbsp;
        <a href="<?= e(url('admin/orders.php')) ?>" class="admin-btn admin-btn--small admin-btn--ghost">← Quay lại</a>
    </p>

    <div class="admin-info-grid admin-info-grid--detail">
        <section class="admin-info-card">
            <h3>Thông tin đơn hàng</h3>
            <dl class="admin-info-list">
                <dt>Trạng thái đơn hàng</dt>
                <dd><span class="admin-badge admin-badge--<?= admin_status_badge($order['status']) ?>"><?= e(admin_status_label($order['status'])) ?></span></dd>
                <dt>Trạng thái thanh toán</dt>
                <dd><span class="admin-badge admin-badge--<?= admin_payment_badge($order['payment_status']) ?>"><?= e(admin_payment_label($order['payment_status'])) ?></span></dd>
                <dt>Phương thức thanh toán</dt>
                <dd><?= e(checkout_payment_methods()[$payments[0]['method'] ?? '']['label'] ?? 'Chưa có thông tin') ?></dd>
                <dt>Ngày tạo</dt>
                <dd><?= e(format_date($order['created_at'])) ?></dd>
                <dt>Cập nhật lần cuối</dt>
                <dd><?= e(format_date($order['updated_at'])) ?></dd>
            </dl>
        </section>

        <section class="admin-info-card">
            <h3>Thông tin khách hàng</h3>
            <dl class="admin-info-list">
                <dt>Khách hàng</dt>
                <dd class="is-strong"><?= e($order['customer_name']) ?></dd>
                <dt>Email</dt>
                <dd><?= e($order['customer_email']) ?></dd>
                <dt>Số điện thoại</dt>
                <dd><?= e($order['customer_phone'] ?? '') ?></dd>
                <dt>Người nhận</dt>
                <dd class="is-strong"><?= e($order['shipping_name']) ?></dd>
                <dt>Số điện thoại nhận</dt>
                <dd><?= e($order['shipping_phone']) ?></dd>
                <dt>Địa chỉ nhận hàng</dt>
                <dd><?= e($order['shipping_address']) ?><br><?= e($order['shipping_city']) ?></dd>
            </dl>
        </section>

        <?php if (!empty($order['notes'])): ?>
            <section class="admin-info-card admin-info-card--wide">
                <h3>Ghi chú đơn hàng</h3>
                <p class="admin-order-note"><?= e($order['notes']) ?></p>
            </section>
        <?php endif; ?>
    </div>

    <form class="admin-form" method="post" action="">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="update_status">
        <div class="admin-form-row admin-form-row--2">
            <label>
                <strong>Trạng thái đơn hàng</strong>
                <select name="status">
                    <?php foreach ($allowedStatuses as $st): ?>
                        <option value="<?= e($st) ?>" <?= $order['status'] === $st ? 'selected' : '' ?>>
                            <?= e(admin_status_label($st)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <strong>Trạng thái thanh toán</strong>
                <select name="payment_status">
                    <?php foreach ($allowedPayments as $ps): ?>
                        <option value="<?= e($ps) ?>" <?= $order['payment_status'] === $ps ? 'selected' : '' ?>>
                            <?= e(admin_payment_label($ps)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>
        <div class="admin-form-actions">
            <button type="submit" class="admin-btn admin-btn--primary">Cập nhật trạng thái</button>
        </div>
    </form>
</div>

<div class="admin-card">
    <h1>Sản phẩm trong đơn</h1>
    <?php if (empty($items)): ?>
        <div class="admin-empty">Đơn hàng không có sản phẩm nào.</div>
    <?php else: ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th></th>
                        <th>Sản phẩm</th>
                        <th>SKU</th>
                        <th class="col-num">Đơn giá</th>
                        <th class="col-num">Số lượng</th>
                        <th class="col-num">Thành tiền</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($items as $it): ?>
                    <tr>
                        <td class="col-thumb">
                            <?php $img = storefront_image((string) ($it['image_path'] ?? '')); ?>
                            <?php if ($img): ?>
                                <img src="<?= e($img) ?>" alt="">
                            <?php else: ?>
                                <span class="col-thumb-placeholder">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong><?= e($it['product_name']) ?></strong>
                            <?php if (!empty($it['product_id'])): ?>
                                <div class="secondary"><a class="admin-badge admin-badge--mute" href="<?= e(url('admin/product-edit.php?id=' . (int) $it['product_id'])) ?>">Mở sản phẩm</a></div>
                            <?php endif; ?>
                        </td>
                        <td><span class="admin-mono"><?= e($it['sku']) ?></span></td>
                        <td class="col-num"><?= e(format_vnd($it['unit_price'])) ?></td>
                        <td class="col-num"><?= (int) $it['quantity'] ?></td>
                        <td class="col-num"><strong><?= e(format_vnd($it['total'])) ?></strong></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="admin-summary">
            <div class="admin-summary-row">
                <span>Tạm tính</span><span><?= e(format_vnd($order['subtotal'])) ?></span>
            </div>
            <?php if ((float) $order['discount'] > 0): ?>
                <div class="admin-summary-row">
                    <span>Giảm giá</span><span>-<?= e(format_vnd($order['discount'])) ?></span>
                </div>
            <?php endif; ?>
            <div class="admin-summary-row">
                <span>Phí vận chuyển</span><span><?= e(format_vnd($order['shipping_fee'])) ?></span>
            </div>
            <div class="admin-summary-row is-total">
                <span>Tổng</span><span><?= e(format_vnd($order['total'])) ?></span>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php if (!empty($payments)): ?>
<div class="admin-card">
    <h1>Thanh toán</h1>
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Phương thức</th>
                    <th>Số tiền</th>
                    <th>Trạng thái</th>
                    <th>Mã giao dịch</th>
                    <th>Thời điểm</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($payments as $pay): ?>
                <tr>
                    <td><?= e(checkout_payment_methods()[$pay['method']]['label'] ?? (string) $pay['method']) ?></td>
                    <td><?= e(format_vnd($pay['amount'])) ?></td>
<td><span class="admin-badge admin-badge--<?= admin_payment_badge($pay['status']) ?>"><?= e(admin_payment_label($pay['status'])) ?></span></td>
                        <td><?= $pay['transaction_id'] ? e($pay['transaction_id']) : '<span class="admin-muted">—</span>' ?></td>
                    <td><?= e(format_date($pay['paid_at'] ?? null)) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
<?php require dirname(__DIR__) . '/includes/admin-footer.php'; ?>