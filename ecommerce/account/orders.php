<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/account-data.php';
account_require_customer('account/orders.php');
$orders = account_orders_for_user((int) current_user()['id']);
$pageTitle = 'Đơn hàng của tôi'; $pageCss = 'account.css'; $bodyClass = 'page-account'; $accountActive = 'orders';
require dirname(__DIR__) . '/includes/header.php';
?>
<div class="account-page"><nav class="breadcrumb" aria-label="Đường dẫn"><ol class="breadcrumb-list"><li><a href="<?= e(url()) ?>">Trang chủ</a></li><li><a href="<?= e(url('account/index.php')) ?>">Tài khoản</a></li><li aria-current="page">Đơn hàng</li></ol></nav>
<header class="account-page-heading"><h1>Đơn hàng của tôi</h1><p>Theo dõi trạng thái và thông tin các đơn hàng của bạn.</p></header><div class="account-layout">
<?php require dirname(__DIR__) . '/includes/account-nav.php'; ?><main class="account-content"><section class="account-card"><h2>Lịch sử đơn hàng</h2>
<?php if (!$orders): ?><div class="account-empty"><p>Bạn chưa có đơn hàng nào.</p><a class="btn btn--primary" href="<?= e(url('products.php')) ?>">Tiếp tục mua sắm</a></div>
<?php else: ?><div class="account-table-wrap"><table class="account-table"><thead><tr><th>Mã đơn</th><th>Ngày đặt</th><th>Thanh toán</th><th>Phương thức</th><th>Tổng tiền</th><th></th></tr></thead><tbody>
<?php foreach ($orders as $order): ?><tr><td><strong><?= e((string) $order['order_number']) ?></strong><br><span class="account-status account-status--<?= e((string) $order['status']) ?>"><?= e(account_order_status_label((string) $order['status'])) ?></span></td><td><?= e(format_date((string) $order['created_at'], 'd/m/Y H:i')) ?></td><td><span class="account-status account-status--<?= e((string) $order['payment_status']) ?>"><?= e(account_order_payment_status_label((string) $order['payment_status'])) ?></span></td><td><?= e(account_payment_method_label((string) $order['payment_method'])) ?></td><td><?= e(format_vnd($order['total'])) ?></td><td><a class="btn btn--ghost btn--sm" href="<?= e(url('account/order-detail.php?order=' . rawurlencode((string) $order['order_number']))) ?>">Xem đơn</a></td></tr><?php endforeach; ?>
</tbody></table></div><?php endif; ?></section></main></div></div>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>