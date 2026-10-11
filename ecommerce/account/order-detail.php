<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/account-data.php';
require_once dirname(__DIR__) . '/includes/order-data.php';
account_require_customer('account/order-detail.php');
$orderNumber = isset($_GET['order']) && is_string($_GET['order']) ? trim($_GET['order']) : '';
if ($orderNumber === '' && isset($_GET['id']) && is_string($_GET['id']) && ctype_digit($_GET['id'])) {
    try { $stmt = db()->prepare('SELECT order_number FROM orders WHERE id = :id AND user_id = :uid LIMIT 1'); $stmt->execute(['id' => (int) $_GET['id'], 'uid' => (int) current_user()['id']]); $orderNumber = (string) ($stmt->fetchColumn() ?: ''); } catch (PDOException $e) { $orderNumber = ''; }
}
$order = account_order_for_user((int) current_user()['id'], $orderNumber);
if (!$order) { http_response_code(404); }
$items = $order ? order_items_for((int) $order['id']) : [];
$pageTitle = $order ? 'Đơn hàng ' . $order['order_number'] : 'Không tìm thấy đơn hàng'; $pageCss = 'account.css'; $bodyClass = 'page-account'; $accountActive = 'orders';
require dirname(__DIR__) . '/includes/header.php';
?>
<div class="account-page"><nav class="breadcrumb" aria-label="Đường dẫn"><ol class="breadcrumb-list"><li><a href="<?= e(url()) ?>">Trang chủ</a></li><li><a href="<?= e(url('account/orders.php')) ?>">Đơn hàng</a></li><li aria-current="page">Chi tiết</li></ol></nav>
<header class="account-page-heading"><h1><?= $order ? 'Đơn hàng ' . e((string) $order['order_number']) : 'Không tìm thấy đơn hàng' ?></h1><p>Thông tin chỉ hiển thị cho chủ sở hữu đơn hàng.</p></header><div class="account-layout">
<?php require dirname(__DIR__) . '/includes/account-nav.php'; ?><main class="account-content">
<?php if (!$order): ?><section class="account-card"><div class="account-empty"><p>Đơn hàng không tồn tại hoặc bạn không có quyền xem đơn hàng này.</p><a class="btn btn--primary" href="<?= e(url('account/orders.php')) ?>">Quay lại đơn hàng</a></div></section>
<?php else: ?>
<section class="account-card"><div class="account-welcome"><div><h2>Thông tin đơn hàng</h2><p>Đặt ngày <?= e(format_date((string) $order['created_at'], 'd/m/Y H:i')) ?></p></div><span class="account-status account-status--<?= e((string) $order['status']) ?>"><?= e(account_order_status_label((string) $order['status'])) ?></span></div>
<div class="account-summary-grid account-summary-grid--spaced"><div class="account-summary"><span>Thanh toán</span><strong><?= e(account_order_payment_status_label((string) $order['payment_status'])) ?></strong></div><div class="account-summary"><span>Phương thức</span><strong><?= e(account_payment_method_label((string) $order['payment_method'])) ?></strong></div><div class="account-summary"><span>Ngày cập nhật</span><strong><?= e(format_date((string) $order['updated_at'], 'd/m/Y')) ?></strong></div></div></section>
<section class="account-card"><h2>Thông tin nhận hàng</h2><p><strong><?= e((string) $order['shipping_name']) ?></strong> · <?= e((string) $order['shipping_phone']) ?></p><p><?= e((string) $order['shipping_address']) ?>, <?= e((string) $order['shipping_city']) ?></p><?php if (!empty($order['notes'])): ?><p class="muted">Ghi chú: <?= nl2br(e((string) $order['notes'])) ?></p><?php endif; ?></section>
<section class="account-card"><h2>Sản phẩm trong đơn</h2><?php if (!$items): ?><p class="muted">Không có thông tin sản phẩm.</p><?php else: ?><div class="account-order-items">
<?php foreach ($items as $item): $img = !empty($item['image_path']) ? storefront_image((string) $item['image_path']) : null; ?><article class="account-order-item"><img src="<?= e($img ?? storefront_placeholder_svg((string) $item['product_name'])) ?>" alt="<?= e((string) $item['product_name']) ?>"><div><strong><?= e((string) $item['product_name']) ?></strong><p>Mã: <?= e((string) $item['sku']) ?> · SL: <?= (int) $item['quantity'] ?> × <?= e(format_vnd($item['unit_price'])) ?></p></div><strong><?= e(format_vnd($item['total'])) ?></strong></article><?php endforeach; ?></div><?php endif; ?>
<div class="account-total"><span>Tạm tính</span><span><?= e(format_vnd($order['subtotal'])) ?></span></div><?php if ((float) $order['discount'] > 0): ?><div class="account-total"><span>Giảm giá</span><span>−<?= e(format_vnd($order['discount'])) ?></span></div><?php endif; ?><div class="account-total"><span>Phí vận chuyển</span><span><?= (float) $order['shipping_fee'] <= 0 ? 'Miễn phí' : e(format_vnd($order['shipping_fee'])) ?></span></div><div class="account-total account-total--grand"><span>Tổng cộng</span><span><?= e(format_vnd($order['total'])) ?></span></div></section>
<?php endif; ?></main></div></div>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>