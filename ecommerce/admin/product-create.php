<?php
/** Admin — create a product and its optional image gallery. */
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/admin-auth.php';
require_once dirname(__DIR__) . '/includes/admin-data.php';
require_once dirname(__DIR__) . '/includes/admin-display.php';

$pageTitle = 'Thêm sản phẩm';
$errors = [];
$values = [
    'name' => '', 'slug' => '', 'sku' => '', 'short_description' => '',
    'description' => '', 'price' => '', 'compare_price' => '', 'stock' => '',
    'category_id' => 0, 'brand_id' => 0, 'is_featured' => 0, 'is_active' => 1,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $posted = array_diff_key($_POST, array_flip(['primary_choice']));
    $posted = array_filter($posted, 'is_scalar');
    $values = array_merge($values, array_map(
        static fn ($value) => is_string($value) ? trim($value) : (int) $value,
        $posted
    ));
    $values['is_featured'] = !empty($_POST['is_featured']) ? 1 : 0;
    $values['is_active'] = !empty($_POST['is_active']) ? 1 : 0;

    $uploadValidation = admin_product_image_uploads_validate($_FILES['images'] ?? []);
    $errors = $uploadValidation['errors'];
    if ($errors === []) {
        $result = admin_product_save($values, null);
        if ($result['errors'] !== []) {
            $errors = $result['errors'];
            old_set($values);
        } else {
            $imageResult = admin_product_images_manage(
                (int) $result['id'],
                $uploadValidation['files'],
                (string) ($_POST['primary_choice'] ?? ''),
                [],
                (string) $values['name']
            );
            if ($imageResult['errors'] !== []) {
                flash_set('error', implode(' ', $imageResult['errors']));
            } else {
                flash_set('success', 'Đã tạo sản phẩm mới.');
            }
            old_clear();
            redirect('/admin/product-edit.php?id=' . (int) $result['id']);
        }
    } else {
        old_set($values);
    }
}

$categories = admin_categories_all();
$brands = admin_brands_all();
require dirname(__DIR__) . '/includes/admin-header.php';
?>
<div class="admin-card">
    <h1>Thêm sản phẩm</h1>
    <p class="admin-card-sub">Điền các trường bên dưới để tạo sản phẩm mới. SKU và slug phải là duy nhất.</p>

    <?php if ($errors !== []): ?>
        <div class="flash flash-error"><?php foreach ($errors as $error): ?><div><?= e($error) ?></div><?php endforeach; ?></div>
    <?php endif; ?>

    <form class="admin-form" method="post" action="" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="admin-form-row admin-form-row--2">
            <label><strong>Tên sản phẩm *</strong><input type="text" name="name" value="<?= e($values['name']) ?>" required maxlength="200"></label>
            <label><strong>SKU *</strong><input type="text" name="sku" value="<?= e($values['sku']) ?>" required maxlength="64"></label>
        </div>
        <label><strong>Slug (URL)</strong><input type="text" name="slug" value="<?= e($values['slug']) ?>" maxlength="220" placeholder="Bỏ trống sẽ tự sinh từ tên"></label>
        <label><strong>Mô tả ngắn</strong><input type="text" name="short_description" value="<?= e($values['short_description']) ?>" maxlength="500"></label>
        <label><strong>Mô tả chi tiết</strong><textarea name="description" rows="6"><?= e($values['description']) ?></textarea></label>
        <div class="admin-form-row admin-form-row--3">
            <label><strong>Giá bán (₫) *</strong><input type="number" name="price" min="0" step="1000" value="<?= e($values['price']) ?>" required></label>
            <label><strong>Giá so sánh (₫)</strong><input type="number" name="compare_price" min="0" step="1000" value="<?= e($values['compare_price']) ?>" placeholder="(tuỳ chọn)"></label>
            <label><strong>Tồn kho *</strong><input type="number" name="stock" min="0" step="1" value="<?= e($values['stock']) ?>" required></label>
        </div>
        <div class="admin-form-row admin-form-row--2">
            <label><strong>Danh mục *</strong><select name="category_id" required><option value="0">— Chọn danh mục —</option><?php foreach ($categories as $category): ?><option value="<?= (int) $category['id'] ?>" <?= (int) $values['category_id'] === (int) $category['id'] ? 'selected' : '' ?>><?= e($category['name']) ?></option><?php endforeach; ?></select></label>
            <label><strong>Thương hiệu</strong><select name="brand_id"><option value="0">— Không có —</option><?php foreach ($brands as $brand): ?><option value="<?= (int) $brand['id'] ?>" <?= (int) $values['brand_id'] === (int) $brand['id'] ? 'selected' : '' ?>><?= e($brand['name']) ?></option><?php endforeach; ?></select></label>
        </div>
        <div class="admin-form-row admin-form-row--2">
            <label class="admin-check"><input type="checkbox" name="is_active" value="1" <?= $values['is_active'] ? 'checked' : '' ?>> Hiển thị trên cửa hàng</label>
            <label class="admin-check"><input type="checkbox" name="is_featured" value="1" <?= $values['is_featured'] ? 'checked' : '' ?>> Sản phẩm nổi bật</label>
        </div>

        <section class="admin-image-manager" aria-labelledby="productImagesTitle">
            <div class="admin-image-manager-heading"><div><h2 id="productImagesTitle">Hình ảnh sản phẩm</h2><p>JPG, JPEG, PNG hoặc WebP · tối đa 5 MB mỗi ảnh, tối đa 8 ảnh mỗi lần.</p></div></div>
            <label class="admin-image-upload-field"><strong>Chọn hình ảnh</strong><input type="file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple><span class="admin-image-help">Chọn một ảnh làm ảnh chính trong phần xem trước.</span></label>
            <div class="admin-image-preview-grid" data-image-preview></div>
        </section>

        <div class="admin-form-actions"><button type="submit" class="admin-btn admin-btn--primary">Tạo sản phẩm</button><a href="<?= e(url('admin/products.php')) ?>" class="admin-btn admin-btn--ghost">Quay lại</a></div>
    </form>
</div>
<script>
(() => {
    const input = document.querySelector('input[name="images[]"]');
    const preview = document.querySelector('[data-image-preview]');
    if (!input || !preview) return;
    input.addEventListener('change', () => {
        preview.replaceChildren();
        Array.from(input.files || []).forEach((file, index) => {
            if (!file.type.startsWith('image/')) return;
            const tile = document.createElement('label'); tile.className = 'admin-image-preview-tile';
            const image = document.createElement('img'); image.src = URL.createObjectURL(file); image.alt = file.name;
            const choice = document.createElement('input'); choice.type = 'radio'; choice.name = 'primary_choice'; choice.value = `upload-${index}`; choice.checked = index === 0;
            const text = document.createElement('span'); text.textContent = 'Ảnh chính';
            tile.append(image, choice, text); preview.append(tile);
        });
    });
})();
</script>
<?php require dirname(__DIR__) . '/includes/admin-footer.php'; ?>