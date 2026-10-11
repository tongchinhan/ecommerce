<?php
/**
 * Admin — Orders list.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/admin-auth.php';
require_once dirname(__DIR__) . '/includes/admin-data.php';
require_once dirname(__DIR__) . '/includes/admin-display.php';

$pageTitle = 'Đơn hàng';

$statusFilter = (string) ($_GET['status'] ?? '');
$allowedStatuses = [ORDER_PENDING, ORDER_CONFIRMED, ORDER_PROCESSING, ORDER_SHIPPED, ORDER_DELIVERED, ORDER_CANCELLED];
if (!in_array($statusFilter, $allowedStatuses, true)) {
    $statusFilter = '';
}

$orders = admin_orders_all($statusFilter);

require dirname(__DIR__) . '/includes/admin-header.php';
?>
<div class="admin-card">
    <h1>Đơn hàng</h1>
    <p class="admin-card-sub">Danh sách đơn hàng trong hệ thống. Nhấp vào một đơn để xem chi tiết và cập nhật trạng thái.</p>

    <div class="admin-toolbar">
        <a class="admin-btn <?= $statusFilter === '' ? 'admin-btn--primary' : '' ?>" href="<?= e(url('admin/orders.php')) ?>">Tất cả</a>
        <?php foreach ($allowedStatuses as $st): ?>
            <a class="admin-btn <?= $statusFilter === $st ? 'admin-btn--primary' : '' ?>" href="<?= e(url('admin/orders.php?status=' . $st)) ?>">
                <?= e(admin_status_label($st)) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if (empty($orders)): ?>
        <div class="admin-empty">Chưa có đơn hàng nào phù hợp bộ lọc.</div>
    <?php else: ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Mã đơn</th>
                        <th>Khách hàng</th>
                        <th class="col-num">Sản phẩm</th>
                        <th class="col-num">Tổng</th>
                        <th>Trạng thái</th>
                        <th>Thanh toán</th>
                        <th>Ngày tạo</th>
                        <th class="col-actions">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($orders as $o): ?>
                    <tr>
                        <td><strong><?= e($o['order_number']) ?></strong></td>
                        <td class="col-product-cell">
                            <strong><?= e($o['customer_name']) ?></strong>
                            <span class="secondary"><?= e($o['customer_email']) ?></span>
                        </td>
                        <td class="col-num"><?= (int) $o['item_count'] ?> món</td>
                        <td class="col-num"><strong><?= e(format_vnd($o['total'])) ?></strong></td>
                        <td><span class="admin-badge admin-badge--<?= admin_status_badge($o['status']) ?>"><?= e(admin_status_label($o['status'])) ?></span></td>
                        <td><span class="admin-badge admin-badge--<?= admin_payment_badge($o['payment_status']) ?>"><?= e(admin_payment_label($o['payment_status'])) ?></span></td>
                        <td><?= e(format_date($o['created_at'])) ?></td>
                        <td class="col-actions">
                            <a class="admin-btn admin-btn--small admin-btn--secondary" href="<?= e(url('admin/order-detail.php?id=' . (int) $o['id'])) ?>">Chi tiết</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?php require dirname(__DIR__) . '/includes/admin-footer.php'; ?>