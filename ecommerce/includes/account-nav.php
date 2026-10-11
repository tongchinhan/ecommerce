<?php
$accountActive = $accountActive ?? '';
$accountLinks = [
    'index' => ['Tổng quan', 'account/index.php'],
    'orders' => ['Đơn hàng', 'account/orders.php'],
    'wishlist' => ['Yêu thích', 'account/wishlist.php'],
    'profile' => ['Thông tin cá nhân', 'account/profile.php'],
    'addresses' => ['Địa chỉ giao hàng', 'account/addresses.php'],
];
?>
<aside class="account-sidebar" aria-label="Điều hướng tài khoản">
    <p class="account-sidebar-title">TÀI KHOẢN CỦA TÔI</p>
    <nav class="account-nav">
        <?php foreach ($accountLinks as $key => [$label, $href]): ?>
            <a class="account-nav-link<?= $accountActive === $key ? ' is-active' : '' ?>" href="<?= e(url($href)) ?>"<?= $accountActive === $key ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
        <?php endforeach; ?>
    </nav>
</aside>