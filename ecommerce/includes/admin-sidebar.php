<?php
/**
 * Admin sidebar with active state highlighting and grouped navigation.
 *
 * Marks the current page's nav link as `.is-active` by comparing the
 * request script basename against each link.
 */

declare(strict_types=1);

$current = basename($_SERVER['SCRIPT_NAME'] ?? '');
?>
<aside class="admin-sidebar">
    <a class="admin-brand" href="<?= e(url('admin/dashboard.php')) ?>">
        <span><?= e(APP_NAME) ?></span>
        <small>Admin</small>
    </a>

    <div class="admin-nav-group-label">Quản lý</div>
    <nav aria-label="Quản lý">
        <a href="<?= e(url('admin/dashboard.php')) ?>" class="<?= $current === 'dashboard.php' ? 'is-active' : '' ?>">Tổng quan</a>
        <a href="<?= e(url('admin/products.php')) ?>" class="<?= $current === 'products.php' || $current === 'product-create.php' || $current === 'product-edit.php' ? 'is-active' : '' ?>">Sản phẩm</a>
        <a href="<?= e(url('admin/categories.php')) ?>" class="<?= $current === 'categories.php' ? 'is-active' : '' ?>">Danh mục</a>
        <a href="<?= e(url('admin/orders.php')) ?>" class="<?= $current === 'orders.php' || $current === 'order-detail.php' ? 'is-active' : '' ?>">Đơn hàng</a>
        <a href="<?= e(url('admin/customers.php')) ?>" class="<?= $current === 'customers.php' ? 'is-active' : '' ?>">Khách hàng</a>
        <a href="<?= e(url('admin/posts.php')) ?>" class="<?= $current === 'posts.php' || $current === 'post-create.php' || $current === 'post-edit.php' ? 'is-active' : '' ?>">Bài viết</a>
        <a href="<?= e(url('admin/coupons.php')) ?>" class="<?= $current === 'coupons.php' ? 'is-active' : '' ?>">Mã giảm giá</a>
        <a href="<?= e(url('admin/banners.php')) ?>" class="<?= $current === 'banners.php' ? 'is-active' : '' ?>">Banner</a>
        <a href="<?= e(url('admin/reviews.php')) ?>" class="<?= $current === 'reviews.php' ? 'is-active' : '' ?>">Đánh giá</a>
        <a href="<?= e(url('admin/statistics.php')) ?>" class="<?= $current === 'statistics.php' ? 'is-active' : '' ?>">Thống kê</a>
    </nav>

    <div class="admin-nav-group-label">Cửa hàng</div>
    <nav aria-label="Cửa hàng">
        <a href="<?= e(url()) ?>" target="_blank" rel="noopener">Xem cửa hàng</a>
    </nav>

    <div class="admin-nav-group-label">Đăng xuất</div>
    <nav aria-label="Đăng xuất">
        <a href="<?= e(url('admin/logout.php')) ?>">Đăng xuất</a>
    </nav>

    <div class="admin-sidebar-meta">v<?= e(APP_VERSION) ?></div>
</aside>