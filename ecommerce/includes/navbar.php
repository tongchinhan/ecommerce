<?php
/**
 * Site navbar — desktop + mobile.
 * Requires: icons.php (icon())
 * Requires: session/user (current_user())
 */
declare(strict_types=1);

require_once __DIR__ . '/icons.php';

$user = current_user();

/* Cart count for the header badge. cart_count() is defensive — returns 0
   when there is no cart row yet. Loaded once per request. */
$navCartCount = 0;
if (function_exists('cart_count')) {
    $navCartCount = cart_count();
}
?>
<div class="mobile-menu-overlay" id="mobileMenuOverlay" aria-hidden="true"></div>

<header class="site-header" id="siteHeader">
    <div class="container header-inner<?= is_admin() ? ' header-inner--admin' : '' ?>">
        <!-- LEFT: Logo -->
        <div class="header-left">
            <a class="logo" href="<?= e(url()) ?>" aria-label="<?= e(APP_NAME) ?>">
                <?= e(APP_NAME) ?>
            </a>
        </div>

        <!-- CENTER: Desktop Navigation -->
        <div class="header-center">
            <nav class="nav-desktop" aria-label="Điều hướng chính">
                <a class="nav-link" href="<?= e(url()) ?>">Trang chủ</a>
                <a class="nav-link" href="<?= e(url('products.php')) ?>">Cửa hàng</a>
                <a class="nav-link" href="<?= e(url('blog.php')) ?>">Tin tức</a>
                <a class="nav-link" href="<?= e(url('about.php')) ?>">Giới thiệu</a>
                <a class="nav-link" href="<?= e(url('contact.php')) ?>">Liên hệ</a>
            </nav>
        </div>

        <!-- RIGHT: Header Actions -->
        <div class="header-right">
            <div class="header-actions">
                <!-- Search form (hidden inputs preserve query for resubmission) -->
                <form class="header-search" action="<?= e(url('products.php')) ?>" method="get" role="search">
                    <label class="visually-hidden" for="headerSearchInput">Tìm kiếm sản phẩm</label>
                    <input
                        id="headerSearchInput"
                        class="header-search-input"
                        type="search"
                        name="q"
                        placeholder="Tìm sản phẩm..."
                        value="<?= isset($_GET['q']) && is_string($_GET['q']) ? e((string) $_GET['q']) : '' ?>"
                        autocomplete="off"
                        aria-label="Tìm sản phẩm">
                    <button class="header-search-btn action-btn" type="submit" aria-label="Tìm kiếm">
                        <?= icon('search', 'icon icon--nav') ?>
                    </button>
                </form>
                <a class="action-btn action-btn--icon" href="<?= e(url('account/wishlist.php')) ?>" aria-label="Yêu thích">
                    <?= icon('heart-outline', 'icon icon--nav') ?>
                </a>
                <a class="action-btn action-btn--icon action-btn--cart" href="<?= e(url('cart.php')) ?>" aria-label="Giỏ hàng<?= $navCartCount > 0 ? ' (' . (int) $navCartCount . ' sản phẩm)' : '' ?>">
                    <?= icon('bag', 'icon icon--nav') ?>
                    <?php if ($navCartCount > 0): ?>
                        <span class="cart-badge" aria-hidden="true"><?= e((string) $navCartCount) ?></span>
                    <?php endif; ?>
                </a>
                <?php if ($user): ?>
                    <a class="action-btn action-btn--user" href="<?= e(url('account/index.php')) ?>" aria-label="Tài khoản">
                        <?= icon('user', 'icon icon--nav') ?>
                        <span class="user-name-label"><?= e($user['name']) ?></span>
                    </a>
                    <?php if (is_admin()): ?>
                        <a class="action-btn action-btn--admin" href="<?= e(url('admin/dashboard.php')) ?>" aria-label="Quản trị">
                            Quản trị
                        </a>
                    <?php endif; ?>
                    <a class="action-btn action-btn--logout" href="<?= e(url('logout.php')) ?>" aria-label="Đăng xuất">
                        Đăng xuất
                    </a>
                <?php else: ?>
                    <a class="action-btn action-btn--signin" href="<?= e(url('login.php')) ?>">
                        Đăng nhập
                    </a>
                <?php endif; ?>

                <!-- Mobile menu toggle -->
                <button class="mobile-menu-toggle" id="mobileMenuToggle" aria-label="Mở menu" aria-expanded="false" aria-controls="mobileMenu">
                    <?= icon('menu', 'icon icon--nav icon--menu') ?>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Navigation -->
    <nav class="nav-mobile" id="mobileMenu" aria-label="Menu di động" aria-hidden="true">
        <div class="mobile-menu-header">
            <span class="mobile-menu-title">Menu</span>
            <button class="mobile-menu-close" id="mobileMenuClose" aria-label="Đóng menu">
                <?= icon('close', 'icon icon--nav') ?>
            </button>
        </div>
        <div class="mobile-menu-links">
            <form class="mobile-menu-search" action="<?= e(url('products.php')) ?>" method="get" role="search">
                <label for="mobileSearchInput">Tìm kiếm</label>
                <div class="mobile-menu-search-control">
                    <input id="mobileSearchInput" type="search" name="q" placeholder="Nhập tên sản phẩm..." aria-label="Tìm kiếm sản phẩm" autocomplete="off">
                    <button type="submit" aria-label="Tìm kiếm">
                        <?= icon('search', 'icon icon--nav') ?>
                    </button>
                </div>
            </form>
            <a class="mobile-nav-link" href="<?= e(url()) ?>">Trang chủ</a>
            <a class="mobile-nav-link" href="<?= e(url('products.php')) ?>">Cửa hàng</a>
            <a class="mobile-nav-link" href="<?= e(url('products.php')) ?>">Danh mục</a>
            <a class="mobile-nav-link" href="<?= e(url('blog.php')) ?>">Tin tức</a>
            <a class="mobile-nav-link" href="<?= e(url('about.php')) ?>">Giới thiệu</a>
            <a class="mobile-nav-link" href="<?= e(url('contact.php')) ?>">Liên hệ</a>
        </div>
        <div class="mobile-menu-footer">
            <?php if ($user): ?>
                <?php if (is_admin()): ?><a class="mobile-nav-link" href="<?= e(url('admin/dashboard.php')) ?>">Khu vực quản trị</a><?php else: ?><a class="mobile-nav-link" href="<?= e(url('account/index.php')) ?>">Tài khoản của tôi</a><?php endif; ?>
                <a class="mobile-nav-link" href="<?= e(url('account/wishlist.php')) ?>">Yêu thích</a>
                <a class="mobile-nav-link" href="<?= e(url('logout.php')) ?>">Đăng xuất</a>
            <?php else: ?>
                <a class="mobile-nav-link" href="<?= e(url('login.php')) ?>">Đăng nhập</a>
                <a class="mobile-nav-link" href="<?= e(url('register.php')) ?>">Tạo tài khoản</a>
            <?php endif; ?>
        </div>
    </nav>
</header>
