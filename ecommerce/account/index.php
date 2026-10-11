<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/account-data.php';
account_require_customer('account/index.php');
$user = current_user();
$userId = (int) $user['id'];
$recentOrders = account_orders_for_user($userId, 5);
$orderSummary = account_order_summary($userId);
$wishlistCount = 0;
try { $stmt = db()->prepare('SELECT COUNT(*) FROM wishlist_items wi INNER JOIN wishlists w ON w.id = wi.wishlist_id WHERE w.user_id = :uid'); $stmt->execute(['uid' => $userId]); $wishlistCount = (int) $stmt->fetchColumn(); } catch (PDOException $e) { $wishlistCount = 0; }
$pageTitle = 'Tài khoản của tôi'; $pageCss = 'account.css'; $bodyClass = 'page-account'; $accountActive = 'index';
require dirname(__DIR__) . '/includes/header.php';
?>
<div class="account-page">
    <nav class="breadcrumb" aria-label="Đường dẫn"><ol class="breadcrumb-list"><li><a href="<?= e(url()) ?>">Trang chủ</a></li><li aria-current="page">Tài khoản</li></ol></nav>
    <header class="account-page-heading"><h1>Tài khoản của tôi</h1><p>Quản lý thông tin cá nhân và theo dõi các đơn hàng của bạn.</p></header>
    <div class="account-layout">
        <?php require dirname(__DIR__) . '/includes/account-nav.php'; ?>
        <div class="account-content">
            <section class="account-card account-welcome"><div><h2>Xin chào, <?= e((string) $user['name']) ?></h2><p><?= e((string) $user['email']) ?></p></div><a class="btn btn--ghost" href="<?= e(url('account/profile.php')) ?>">Cập nhật hồ sơ</a></section>
            <section class="account-summary-grid" aria-label="Tóm tắt tài khoản">
                <div class="account-summary"><span>Tổng đơn hàng</span><strong><?= $orderSummary['total'] ?></strong></div>
                <div class="account-summary"><span>Chờ xử lý</span><strong><?= $orderSummary['pending'] ?></strong></div>
                <div class="account-summary"><span>Sản phẩm yêu thích</span><strong><?= $wishlistCount ?></strong></div>
            </section>
            <section class="account-card"><h2>Truy cập nhanh</h2><div class="account-quick-links">
                <a class="account-quick-link" href="<?= e(url('account/orders.php')) ?>">Đơn hàng <span>→</span></a>
                <a class="account-quick-link" href="<?= e(url('account/wishlist.php')) ?>">Danh sách yêu thích <span>→</span></a>
                <a class="account-quick-link" href="<?= e(url('account/addresses.php')) ?>">Địa chỉ giao hàng <span>→</span></a>
            </div></section>
            <section class="account-card"><h2>Đơn hàng gần đây</h2>
                <?php if (!$recentOrders): ?><div class="account-empty"><p>Bạn chưa có đơn hàng nào.</p><a class="btn btn--primary" href="<?= e(url('products.php')) ?>">Khám phá sản phẩm</a></div>
                <?php else: ?><div class="account-table-wrap"><table class="account-table"><thead><tr><th>Mã đơn</th><th>Ngày đặt</th><th>Trạng thái</th><th>Tổng tiền</th><th></th></tr></thead><tbody>
                    <?php foreach ($recentOrders as $order): ?><tr><td><strong><?= e((string) $order['order_number']) ?></strong></td><td><?= e(format_date((string) $order['created_at'], 'd/m/Y')) ?></td><td><span class="account-status account-status--<?= e((string) $order['status']) ?>"><?= e(account_order_status_label((string) $order['status'])) ?></span></td><td><?= e(format_vnd($order['total'])) ?></td><td><a class="btn btn--ghost btn--sm" href="<?= e(url('account/order-detail.php?order=' . rawurlencode((string) $order['order_number']))) ?>">Chi tiết</a></td></tr><?php endforeach; ?>
                </tbody></table></div><?php endif; ?>
            </section>
        </div>
    </div>
</div>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>