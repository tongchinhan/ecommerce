<?php
/**
 * Admin Dashboard — overview built from real DB queries.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/admin-auth.php';
require_once dirname(__DIR__) . '/includes/admin-data.php';
require_once dirname(__DIR__) . '/includes/admin-display.php';

$pageTitle = 'Tổng quan';
$counts = [
    'products' => 0,
    'orders'   => 0,
    'users'    => 0,
    'posts'    => 0,
];
$recentOrders = [];
try {
    $counts['products'] = (int) db()->query('SELECT COUNT(*) FROM products')->fetchColumn();
    $counts['orders']   = (int) db()->query('SELECT COUNT(*) FROM orders')->fetchColumn();
    $counts['users']    = (int) db()->query("SELECT COUNT(*) FROM users WHERE role = '" . ROLE_CUSTOMER . "'")->fetchColumn();
    $counts['posts']    = (int) db()->query('SELECT COUNT(*) FROM posts')->fetchColumn();
    $recentOrders = admin_orders_all();
    $recentOrders = array_slice($recentOrders, 0, 5);
} catch (PDOException $e) {
    flash_set('error', 'Không thể đọc số liệu tổng quan.');
}

require dirname(__DIR__) . '/includes/admin-header.php';
?>
<div class="admin-card">
    <div class="admin-card-head">
        <div>
            <h1>Nền tảng quản trị</h1>
            <p class="admin-card-sub">Chào <?= e(current_user()['name'] ?? '') ?>. Số liệu bên dưới được truy vấn trực tiếp từ cơ sở dữ liệu MySQL của cửa hàng.</p>
        </div>
    </div>

    <div class="admin-stats">
        <a class="admin-stat-card is-products" href="<?= e(url('admin/products.php')) ?>">
            <span class="admin-stat-icon-bg" aria-hidden="true"><?= icon('package', 'icon') ?></span>
            <span class="admin-stat-label"><?= icon('bag', 'icon') ?> Sản phẩm</span>
            <span class="admin-stat-value"><?= number_format((int) $counts['products'], 0, ',', '.') ?></span>
            <span class="admin-stat-foot">Đang hiển thị trên cửa hàng</span>
        </a>
        <a class="admin-stat-card is-orders" href="<?= e(url('admin/orders.php')) ?>">
            <span class="admin-stat-icon-bg" aria-hidden="true"><?= icon('receipt', 'icon') ?></span>
            <span class="admin-stat-label"><?= icon('truck', 'icon') ?> Đơn hàng</span>
            <span class="admin-stat-value"><?= number_format((int) $counts['orders'], 0, ',', '.') ?></span>
            <span class="admin-stat-foot">Tổng số đơn trong hệ thống</span>
        </a>
        <a class="admin-stat-card is-users" href="<?= e(url('admin/customers.php')) ?>">
            <span class="admin-stat-icon-bg" aria-hidden="true"><?= icon('users', 'icon') ?></span>
            <span class="admin-stat-label"><?= icon('user', 'icon') ?> Khách hàng</span>
            <span class="admin-stat-value"><?= number_format((int) $counts['users'], 0, ',', '.') ?></span>
            <span class="admin-stat-foot">Tài khoản khách hàng đã đăng ký</span>
        </a>
        <a class="admin-stat-card is-posts" href="<?= e(url('admin/posts.php')) ?>">
            <span class="admin-stat-icon-bg" aria-hidden="true"><?= icon('document', 'icon') ?></span>
            <span class="admin-stat-label"><?= icon('chat', 'icon') ?> Bài viết</span>
            <span class="admin-stat-value"><?= number_format((int) $counts['posts'], 0, ',', '.') ?></span>
            <span class="admin-stat-foot">Bài viết / tin tức đã xuất bản</span>
        </a>
    </div>
</div>

<div class="admin-card">
    <div class="admin-card-head">
        <div>
            <h1>Đơn hàng gần đây</h1>
            <p class="admin-card-sub">5 đơn hàng mới nhất trong hệ thống.</p>
        </div>
        <a class="admin-btn admin-btn--secondary" href="<?= e(url('admin/orders.php')) ?>">Xem tất cả</a>
    </div>

    <?php if (empty($recentOrders)): ?>
        <div class="admin-empty">Chưa có đơn hàng nào.</div>
    <?php else: ?>
        <div class="admin-table-wrap">
            <table class="admin-table admin-dashboard-orders">
                <thead>
                    <tr>
                        <th>Mã đơn</th>
                        <th>Khách hàng</th>
                        <th class="col-num">Tổng</th>
                        <th>Trạng thái</th>
                        <th>Ngày tạo</th>
                        <th class="col-actions">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($recentOrders as $o): ?>
                    <tr>
                        <td><strong><?= e($o['order_number']) ?></strong></td>
                        <td class="col-product-cell">
                            <strong><?= e($o['customer_name']) ?></strong>
                            <span class="secondary"><?= e($o['customer_email']) ?></span>
                        </td>
                        <td class="col-num"><strong><?= e(format_vnd($o['total'])) ?></strong></td>
                        <td>
                            <span class="admin-status admin-status--<?= e(admin_status_badge($o['status'])) ?>">
                                <?= e(admin_status_label($o['status'])) ?>
                            </span>
                        </td>
                        <td><?= e(format_date($o['created_at'])) ?></td>
                        <td class="col-actions">
                            <span class="admin-action-row">
                                <a class="admin-btn admin-btn--small admin-btn--secondary" href="<?= e(url('admin/order-detail.php?id=' . (int) $o['id'])) ?>">Chi tiết</a>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div class="admin-card">
    <div class="admin-card-head">
        <div>
            <h1>Lối tắt</h1>
            <p class="admin-card-sub">Các thao tác nhanh dành cho quản trị viên.</p>
        </div>
    </div>
    <div class="admin-toolbar" style="margin-top:0;">
        <a class="admin-btn admin-btn--primary" href="<?= e(url('admin/product-create.php')) ?>">+ Thêm sản phẩm</a>
        <a class="admin-btn admin-btn--secondary" href="<?= e(url('admin/post-create.php')) ?>">+ Viết bài</a>
        <a class="admin-btn admin-btn--secondary" href="<?= e(url('admin/coupons.php')) ?>">Mã giảm giá</a>
        <a class="admin-btn admin-btn--secondary" href="<?= e(url('admin/banners.php')) ?>">Banner</a>
        <a class="admin-btn admin-btn--secondary" href="<?= e(url('admin/statistics.php')) ?>">Thống kê</a>
    </div>
</div>
<?php require dirname(__DIR__) . '/includes/admin-footer.php'; ?>