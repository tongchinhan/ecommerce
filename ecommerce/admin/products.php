<?php
/**
 * Admin — Product list, search, filter, and delete.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/admin-auth.php';
require_once dirname(__DIR__) . '/includes/admin-data.php';
require_once dirname(__DIR__) . '/includes/admin-display.php';

$pageTitle = 'Sản phẩm';

$search     = trim((string) ($_GET['q'] ?? ''));
$categoryId = (int) ($_GET['category'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (string) ($_POST['action'] ?? '') === 'delete') {
    csrf_require();
    $id = (int) ($_POST['id'] ?? 0);
    $result = admin_product_delete($id);
    flash_set($result['ok'] ? 'success' : 'error', $result['message']);
    redirect('/admin/products.php' . ($search !== '' || $categoryId > 0 ? '?' . http_build_query(array_filter(['q' => $search, 'category' => $categoryId > 0 ? $categoryId : null])) : ''));
}

$products  = admin_products_all($search, $categoryId > 0 ? $categoryId : null);
$categories = admin_categories_all();

require dirname(__DIR__) . '/includes/admin-header.php';
?>
<div class="admin-card">
    <div class="admin-card-head">
        <div>
            <h1>Sản phẩm</h1>
            <p class="admin-card-sub">Danh sách sản phẩm đang có trong cửa hàng. Tìm kiếm theo tên hoặc SKU, lọc theo danh mục.</p>
        </div>
        <a class="admin-btn admin-btn--primary" href="<?= e(url('admin/product-create.php')) ?>">+ Thêm sản phẩm</a>
    </div>

    <form class="admin-toolbar" method="get" action="">
        <input type="search" name="q" value="<?= e($search) ?>" placeholder="Tìm theo tên hoặc SKU">
        <select name="category">
            <option value="0">Tất cả danh mục</option>
            <?php foreach ($categories as $c): ?>
                <option value="<?= (int) $c['id'] ?>" <?= $categoryId === (int) $c['id'] ? 'selected' : '' ?>>
                    <?= e($c['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button class="admin-btn" type="submit">Lọc</button>
        <a class="admin-btn admin-btn--ghost" href="<?= e(url('admin/products.php')) ?>">Đặt lại</a>
        <span class="admin-toolbar-spacer"></span>
        <span class="admin-stat-pill">Tổng: <b><?= count($products) ?></b></span>
    </form>

    <?php if (empty($products)): ?>
        <div class="admin-empty">Chưa có sản phẩm nào phù hợp. <a class="admin-btn admin-btn--small admin-btn--primary" href="<?= e(url('admin/product-create.php')) ?>" style="margin-left:8px;">Tạo sản phẩm đầu tiên</a></div>
    <?php else: ?>
        <div class="admin-table-wrap">
            <table class="admin-table admin-products-table">
                <thead>
                    <tr>
                        <th></th>
                        <th>Sản phẩm</th>
                        <th>SKU</th>
                        <th>Danh mục</th>
                        <th>Giá</th>
                        <th>Tồn kho</th>
                        <th>Trạng thái</th>
                        <th class="col-actions">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($products as $p): ?>
                    <tr>
                        <td class="col-thumb">
                            <?php $img = storefront_image((string) ($p['primary_image'] ?? '')); ?>
                            <?php if ($img): ?>
                                <img src="<?= e($img) ?>" alt="">
                            <?php else: ?>
                                <span class="col-thumb-placeholder">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="col-product-cell">
                            <strong><?= e($p['name']) ?></strong>
                            <?php if (!empty($p['brand_name'])): ?>
                                <span class="secondary"><?= e($p['brand_name']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td><span class="admin-mono"><?= e($p['sku']) ?></span></td>
                        <td><?= e($p['category_name']) ?></td>
                        <td class="col-price">
                            <strong><?= e(format_vnd($p['price'])) ?></strong>
                            <?php if ($p['compare_price'] !== null && (float) $p['compare_price'] > (float) $p['price']): ?>
                                <span class="compare-price"><?= e(format_vnd($p['compare_price'])) ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="col-num">
                            <?php $stock = (int) $p['stock']; ?>
                            <?php if ($stock <= 0): ?>
                                <span class="admin-badge admin-badge--bad">Hết hàng</span>
                            <?php elseif ($stock <= 5): ?>
                                <span class="admin-badge admin-badge--warn"><?= $stock ?></span>
                            <?php else: ?>
                                <span class="admin-badge admin-badge--ok"><?= $stock ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="admin-badge-stack">
                                <?php if ((int) $p['is_active'] === 1): ?>
                                    <span class="admin-badge admin-badge--ok">Hiển thị</span>
                                <?php else: ?>
                                    <span class="admin-badge admin-badge--mute">Ẩn</span>
                                <?php endif; ?>
                                <?php if ((int) $p['is_featured'] === 1): ?>
                                    <span class="admin-badge admin-badge--info">Nổi bật</span>
                                <?php endif; ?>
                            </span>
                        </td>
                        <td class="col-actions">
                            <span class="admin-action-row admin-product-actions">
                                <a class="admin-btn admin-btn--small admin-btn--secondary" href="<?= e(url('admin/product-edit.php?id=' . (int) $p['id'])) ?>"><?= icon('edit', 'icon') ?> Sửa</a>
                                <a class="admin-btn admin-btn--small admin-btn--secondary" href="<?= e(url('product.php?id=' . (int) $p['id'])) ?>" target="_blank" rel="noopener"><?= icon('eye', 'icon') ?> Xem</a>
                                <form class="admin-product-delete-form" method="post" action="" onsubmit="return confirm('Xóa sản phẩm này?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                                    <button type="submit" class="admin-btn admin-btn--small admin-btn--danger"><?= icon('trash', 'icon') ?> Xóa</button>
                                </form>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?php require dirname(__DIR__) . '/includes/admin-footer.php'; ?>