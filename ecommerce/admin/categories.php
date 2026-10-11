<?php
/**
 * Admin — Category CRUD.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/admin-auth.php';
require_once dirname(__DIR__) . '/includes/admin-data.php';
require_once dirname(__DIR__) . '/includes/admin-display.php';

$pageTitle = 'Danh mục';

$editId = (int) ($_GET['edit'] ?? 0);
$editing = $editId > 0 ? admin_category_find($editId) : null;

$errors = [];
$values = [
    'name'        => '',
    'slug'        => '',
    'description' => '',
    'parent_id'   => 0,
    'sort_order'  => 0,
    'is_active'   => 1,
];

if ($editing) {
    $values = [
        'name'        => (string) $editing['name'],
        'slug'        => (string) $editing['slug'],
        'description' => (string) ($editing['description'] ?? ''),
        'parent_id'   => (int) ($editing['parent_id'] ?? 0),
        'sort_order'  => (int) $editing['sort_order'],
        'is_active'   => (int) $editing['is_active'],
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        $result = admin_category_delete($id);
        flash_set($result['ok'] ? 'success' : 'error', $result['message']);
        redirect('/admin/categories.php');
    }
    $values = [
        'name'        => trim((string) ($_POST['name'] ?? '')),
        'slug'        => trim((string) ($_POST['slug'] ?? '')),
        'description' => trim((string) ($_POST['description'] ?? '')),
        'parent_id'   => (int) ($_POST['parent_id'] ?? 0),
        'sort_order'  => (int) ($_POST['sort_order'] ?? 0),
        'is_active'   => !empty($_POST['is_active']) ? 1 : 0,
    ];
    $saveId = (int) ($_POST['id'] ?? 0);
    $result = admin_category_save($values, $saveId > 0 ? $saveId : null);
    if ($result['errors'] !== []) {
        $errors = $result['errors'];
        $editId = $saveId;
    } else {
        flash_set('success', $saveId > 0 ? 'Đã cập nhật danh mục.' : 'Đã tạo danh mục mới.');
        redirect('/admin/categories.php');
    }
}

$categories = admin_categories_all();

require dirname(__DIR__) . '/includes/admin-header.php';
?>
<div class="admin-card">
    <h1>Danh mục</h1>
    <p class="admin-card-sub">Quản lý các danh mục hiển thị trên cửa hàng. Xóa danh mục có sản phẩm sẽ được ẩn thay vì xóa vĩnh viễn để bảo toàn dữ liệu đơn hàng.</p>

    <form class="admin-form" method="post" action="" style="margin-bottom:24px;">
        <?= csrf_field() ?>
        <h2><?= $editing ? 'Sửa danh mục' : 'Tạo danh mục mới' ?></h2>
        <?php if ($errors !== []): ?>
            <div class="flash flash-error">
                <?php foreach ($errors as $err): ?>
                    <div><?= e($err) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <?php if ($editing): ?>
            <input type="hidden" name="id" value="<?= (int) $editing['id'] ?>">
        <?php endif; ?>
        <div class="admin-form-row admin-form-row--2">
            <label>
                <strong>Tên *</strong>
                <input type="text" name="name" value="<?= e($values['name']) ?>" required maxlength="120">
            </label>
            <label>
                <strong>Slug</strong>
                <input type="text" name="slug" value="<?= e($values['slug']) ?>" placeholder="Bỏ trống để tự sinh" maxlength="160">
            </label>
        </div>
        <label>
            <strong>Mô tả</strong>
            <textarea name="description" rows="3"><?= e($values['description']) ?></textarea>
        </label>
        <div class="admin-form-row admin-form-row--3">
            <label>
                <strong>Thứ tự</strong>
                <input type="number" name="sort_order" value="<?= (int) $values['sort_order'] ?>" min="0">
            </label>
            <label>
                <strong>Danh mục cha</strong>
                <select name="parent_id">
                    <option value="0">— Không —</option>
                    <?php foreach ($categories as $c): ?>
                        <?php if ($editing && (int) $c['id'] === (int) $editing['id']) continue; ?>
                        <option value="<?= (int) $c['id'] ?>" <?= (int) $values['parent_id'] === (int) $c['id'] ? 'selected' : '' ?>>
                            <?= e($c['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="admin-check" style="align-self:end;margin-bottom:8px;">
                <input type="checkbox" name="is_active" value="1" <?= $values['is_active'] ? 'checked' : '' ?>>
                Đang hiển thị
            </label>
        </div>
        <div class="admin-form-actions">
            <button type="submit" class="admin-btn admin-btn--primary"><?= $editing ? 'Lưu thay đổi' : 'Tạo danh mục' ?></button>
            <?php if ($editing): ?>
                <a href="<?= e(url('admin/categories.php')) ?>" class="admin-btn admin-btn--ghost">Huỷ</a>
            <?php endif; ?>
        </div>
    </form>

    <?php if (empty($categories)): ?>
        <div class="admin-empty">Chưa có danh mục nào.</div>
    <?php else: ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Tên</th>
                        <th>Slug</th>
                        <th class="col-num">Sản phẩm</th>
                        <th class="col-num">Thứ tự</th>
                        <th>Trạng thái</th>
                        <th class="col-actions">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($categories as $c): ?>
                    <tr>
                        <td class="col-product-cell">
                            <strong><?= e($c['name']) ?></strong>
                            <?php if (!empty($c['description'])): ?>
                                <span class="secondary"><?= e(storefront_excerpt((string) $c['description'], 80)) ?></span>
                            <?php endif; ?>
                        </td>
                        <td><span class="admin-mono"><?= e($c['slug']) ?></span></td>
                        <td class="col-num"><?= (int) $c['product_count'] ?></td>
                        <td class="col-num"><?= (int) $c['sort_order'] ?></td>
                        <td>
                            <?php if ((int) $c['is_active'] === 1): ?>
                                <span class="admin-badge admin-badge--ok">Hiển thị</span>
                            <?php else: ?>
                                <span class="admin-badge admin-badge--mute">Ẩn</span>
                            <?php endif; ?>
                        </td>
                        <td class="col-actions">
                            <span class="admin-action-row">
                                <a class="admin-btn admin-btn--small admin-btn--secondary" href="<?= e(url('admin/categories.php?edit=' . (int) $c['id'])) ?>">Sửa</a>
                                <form method="post" action="" onsubmit="return confirm('Xóa danh mục này? Nếu có sản phẩm, danh mục sẽ chỉ bị ẩn.');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                                    <button type="submit" class="admin-btn admin-btn--small admin-btn--danger">Xóa</button>
                                </form>
                                <a class="admin-btn admin-btn--small" href="<?= e(url('products.php?category=' . urlencode((string) $c['slug']))) ?>" target="_blank" rel="noopener">Xem</a>
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