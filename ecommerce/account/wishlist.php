<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/account-data.php';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    account_csrf_require();
    if (!is_customer()) {
        flash_set('error', 'Vui lòng đăng nhập để sử dụng danh sách yêu thích.');
        redirect('/login.php?next=' . rawurlencode('/account/wishlist.php'));
    }
    $action = isset($_POST['action']) && is_string($_POST['action']) ? $_POST['action'] : '';
    $productId = isset($_POST['product_id']) && is_string($_POST['product_id']) && ctype_digit($_POST['product_id']) ? (int) $_POST['product_id'] : 0;
    if ($action === 'toggle') {
        $result = wishlist_toggle_for_user((int) current_user()['id'], $productId);
        if (!empty($result['ok'])) flash_set('success', (string) $result['message']);
        else flash_set('error', ($result['reason'] ?? '') === 'unavailable' ? 'Sản phẩm không còn khả dụng.' : 'Không thể cập nhật danh sách yêu thích. Vui lòng thử lại.');
        redirect(account_safe_return((string) ($_POST['return_to'] ?? 'account/wishlist.php')));
    }
    if ($action === 'remove') {
        if (wishlist_remove_for_user((int) current_user()['id'], $productId)) flash_set('success', 'Đã xóa sản phẩm khỏi danh sách yêu thích.');
        else flash_set('error', 'Không tìm thấy sản phẩm trong danh sách yêu thích.');
    }
    redirect('account/wishlist.php');
}
account_require_customer('account/wishlist.php');
require_once dirname(__DIR__) . '/includes/components/product-card.php';
$items = wishlist_items_for_user((int) current_user()['id']);
$pageTitle = 'Danh sách yêu thích'; $pageCss = 'account.css'; $bodyClass = 'page-account'; $accountActive = 'wishlist';
require dirname(__DIR__) . '/includes/header.php';
?>
<div class="account-page"><nav class="breadcrumb" aria-label="Đường dẫn"><ol class="breadcrumb-list"><li><a href="<?= e(url()) ?>">Trang chủ</a></li><li><a href="<?= e(url('account/index.php')) ?>">Tài khoản</a></li><li aria-current="page">Yêu thích</li></ol></nav>
<header class="account-page-heading"><h1>Danh sách yêu thích</h1><p>Lưu lại những sản phẩm bạn muốn xem thêm.</p></header><div class="account-layout">
<?php require dirname(__DIR__) . '/includes/account-nav.php'; ?><main class="account-content"><section class="account-card">
<?php if (!$items): ?><div class="account-empty"><p>Danh sách yêu thích của bạn đang trống.</p><a class="btn btn--primary" href="<?= e(url('products.php')) ?>">Khám phá sản phẩm</a></div>
<?php else: ?><div class="account-product-grid"><?php foreach ($items as $product): ?><?php render_product_card($product, true); ?><?php endforeach; ?></div><?php endif; ?>
</section></main></div></div>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>