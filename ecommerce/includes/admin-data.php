<?php
/**
 * Admin data layer.
 *
 * Centralized database access for the admin area. Mirrors the defensive
 * style used elsewhere in the project:
 *   - Uses the shared PDO singleton (db()).
 *   - Prepared statements with parameter binding only.
 *   - Errors return empty arrays / null / zero counts; mutations that
 *     need to fail will throw.
 *
 * Tables queried: products, categories, brands, orders, users, posts,
 * coupons, banners, reviews, post_categories, product_images.
 */
declare(strict_types=1);

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/storefront-helpers.php';

/* ========================================================================
 * Categories
 * ====================================================================== */

function admin_categories_all(): array
{
    try {
        $stmt = db()->query(
            'SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id) AS product_count
             FROM categories c
             ORDER BY c.sort_order ASC, c.name ASC'
        );
        return $stmt->fetchAll() ?: [];
    } catch (PDOException $e) {
        return [];
    }
}

function admin_category_find(int $id): ?array
{
    if ($id <= 0) {
        return null;
    }
    try {
        $stmt = db()->prepare('SELECT * FROM categories WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    } catch (PDOException $e) {
        return null;
    }
}

function admin_category_save(array $input, ?int $id = null): array
{
    $errors = [];
    $name  = trim((string) ($input['name'] ?? ''));
    $slug  = trim((string) ($input['slug'] ?? ''));
    $desc  = trim((string) ($input['description'] ?? ''));
    $sort  = (int) ($input['sort_order'] ?? 0);
    $active = !empty($input['is_active']) ? 1 : 0;
    $parent = (int) ($input['parent_id'] ?? 0);
    if ($parent < 0) {
        $parent = 0;
    }

    if ($name === '' || mb_strlen($name) > 120) {
        $errors[] = 'Tên danh mục là bắt buộc (tối đa 120 ký tự).';
    }
    if ($slug === '') {
        $slug = slugify($name);
    } elseif (!preg_match('/^[a-z0-9-]+$/', $slug)) {
        $errors[] = 'Slug chỉ gồm chữ thường, số và dấu gạch ngang.';
    }

    if ($errors !== []) {
        return ['errors' => $errors, 'id' => $id];
    }

    try {
        if ($id === null) {
            $stmt = db()->prepare(
                'INSERT INTO categories (parent_id, name, slug, description, sort_order, is_active)
                 VALUES (:parent, :name, :slug, :description, :sort, :active)'
            );
            $stmt->execute([
                'parent' => $parent > 0 ? $parent : null,
                'name'   => $name,
                'slug'   => $slug,
                'description' => $desc !== '' ? $desc : null,
                'sort'   => $sort,
                'active' => $active,
            ]);
            return ['errors' => [], 'id' => (int) db()->lastInsertId()];
        }
        $stmt = db()->prepare(
            'UPDATE categories
             SET parent_id = :parent, name = :name, slug = :slug,
                 description = :description, sort_order = :sort, is_active = :active
             WHERE id = :id'
        );
        $stmt->execute([
            'parent' => $parent > 0 ? $parent : null,
            'name'   => $name,
            'slug'   => $slug,
            'description' => $desc !== '' ? $desc : null,
            'sort'   => $sort,
            'active' => $active,
            'id'     => $id,
        ]);
        return ['errors' => [], 'id' => $id];
    } catch (PDOException $e) {
        // Duplicate slug
        if ((int) ($e->errorInfo[1] ?? 0) === 1062) {
            return ['errors' => ['Slug đã tồn tại. Vui lòng chọn slug khác.'], 'id' => $id];
        }
        return ['errors' => ['Không thể lưu danh mục: ' . $e->getMessage()], 'id' => $id];
    }
}

function admin_category_delete(int $id): array
{
    if ($id <= 0) {
        return ['ok' => false, 'message' => 'Danh mục không hợp lệ.'];
    }
    try {
        // Check whether any active products reference this category.
        $stmt = db()->prepare('SELECT COUNT(*) FROM products WHERE category_id = :id');
        $stmt->execute(['id' => $id]);
        $productCount = (int) $stmt->fetchColumn();

        if ($productCount > 0) {
            // Soft-delete the category to keep products intact.
            $stmt = db()->prepare(
                'UPDATE categories SET is_active = 0 WHERE id = :id'
            );
            $stmt->execute(['id' => $id]);
            return [
                'ok' => true,
                'soft' => true,
                'message' => "Danh mục đang có {$productCount} sản phẩm nên đã được ẩn thay vì xóa vĩnh viễn.",
            ];
        }
        $stmt = db()->prepare('DELETE FROM categories WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return ['ok' => true, 'soft' => false, 'message' => 'Đã xóa danh mục.'];
    } catch (PDOException $e) {
        return ['ok' => false, 'message' => 'Không thể xóa: ' . $e->getMessage()];
    }
}

/* ========================================================================
 * Products
 * ====================================================================== */

function admin_products_all(string $search = '', ?int $categoryId = null, bool $activeOnly = false): array
{
    $where = [];
    $params = [];
    if ($search !== '') {
        $where[] = '(p.name LIKE :s OR p.sku LIKE :s)';
        $params['s'] = '%' . $search . '%';
    }
    if ($categoryId !== null && $categoryId > 0) {
        $where[] = 'p.category_id = :cid';
        $params['cid'] = $categoryId;
    }
    if ($activeOnly) {
        $where[] = 'p.is_active = 1';
    }
    $whereSql = $where === [] ? '' : ('WHERE ' . implode(' AND ', $where));
    try {
        $stmt = db()->prepare(
            "SELECT p.*, c.name AS category_name, b.name AS brand_name,
                    pi.image_path AS primary_image
             FROM products p
             INNER JOIN categories c ON c.id = p.category_id
             LEFT JOIN brands b ON b.id = p.brand_id
             LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_primary = 1
             $whereSql
             ORDER BY p.created_at DESC
             LIMIT 200"
        );
        $stmt->execute($params);
        return $stmt->fetchAll() ?: [];
    } catch (PDOException $e) {
        return [];
    }
}

function admin_product_find(int $id): ?array
{
    if ($id <= 0) {
        return null;
    }
    try {
        $stmt = db()->prepare(
            'SELECT p.*, pi.image_path AS primary_image
             FROM products p
             LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_primary = 1
             WHERE p.id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    } catch (PDOException $e) {
        return null;
    }
}

function admin_product_images_all(int $productId): array
{
    if ($productId <= 0) { return []; }
    try {
        $stmt = db()->prepare('SELECT id, product_id, image_path, alt_text, is_primary, sort_order FROM product_images WHERE product_id = :id ORDER BY is_primary DESC, sort_order ASC, id ASC');
        $stmt->execute(['id' => $productId]);
        return $stmt->fetchAll() ?: [];
    } catch (PDOException $e) {
        return [];
    }
}
function admin_brands_all(): array
{
    try {
        $stmt = db()->query('SELECT id, name FROM brands ORDER BY name ASC');
        return $stmt->fetchAll() ?: [];
    } catch (PDOException $e) {
        return [];
    }
}

function admin_product_save(array $input, ?int $id = null): array
{
    $errors = [];

    $name  = trim((string) ($input['name'] ?? ''));
    $slug  = trim((string) ($input['slug'] ?? ''));
    $sku   = trim((string) ($input['sku'] ?? ''));
    $shortDesc = trim((string) ($input['short_description'] ?? ''));
    $desc  = trim((string) ($input['description'] ?? ''));
    $price = (float) ($input['price'] ?? 0);
    $comparePrice = $input['compare_price'] === '' || $input['compare_price'] === null ? null : (float) $input['compare_price'];
    $stock = (int) ($input['stock'] ?? 0);
    $categoryId = (int) ($input['category_id'] ?? 0);
    $brandId = (int) ($input['brand_id'] ?? 0);
    $featured = !empty($input['is_featured']) ? 1 : 0;
    $active   = !empty($input['is_active']) ? 1 : 1;

    if ($name === '' || mb_strlen($name) > 200) {
        $errors[] = 'Tên sản phẩm là bắt buộc (tối đa 200 ký tự).';
    }
    if ($slug === '') {
        $slug = slugify($name);
    } elseif (!preg_match('/^[a-z0-9-]+$/', $slug)) {
        $errors[] = 'Slug chỉ gồm chữ thường, số và dấu gạch ngang.';
    }
    if ($sku === '' || mb_strlen($sku) > 64) {
        $errors[] = 'SKU là bắt buộc (tối đa 64 ký tự).';
    }
    if ($categoryId <= 0) {
        $errors[] = 'Vui lòng chọn danh mục.';
    }
    if ($price < 0) {
        $errors[] = 'Giá phải >= 0.';
    }
    if ($stock < 0) {
        $errors[] = 'Tồn kho phải >= 0.';
    }

    // Duplicate SKU check
    try {
        $sql = 'SELECT id FROM products WHERE sku = :sku';
        $params = ['sku' => $sku];
        if ($id !== null) {
            $sql .= ' AND id <> :id';
            $params['id'] = $id;
        }
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        if ($stmt->fetch()) {
            $errors[] = 'SKU đã tồn tại. Vui lòng chọn mã khác.';
        }
        // Duplicate slug check
        $sql = 'SELECT id FROM products WHERE slug = :slug';
        $params = ['slug' => $slug];
        if ($id !== null) {
            $sql .= ' AND id <> :id';
            $params['id'] = $id;
        }
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        if ($stmt->fetch()) {
            $errors[] = 'Slug đã tồn tại. Vui lòng chọn slug khác.';
        }
    } catch (PDOException $e) {
        // ignore in pre-validation
    }

    if ($errors !== []) {
        return ['errors' => $errors, 'id' => $id];
    }

    try {
        if ($id === null) {
            $stmt = db()->prepare(
                'INSERT INTO products
                   (category_id, brand_id, name, slug, sku, short_description, description,
                    price, compare_price, stock, is_featured, is_active)
                 VALUES
                   (:category_id, :brand_id, :name, :slug, :sku, :short, :description,
                    :price, :compare, :stock, :featured, :active)'
            );
            $stmt->execute([
                'category_id' => $categoryId,
                'brand_id'    => $brandId > 0 ? $brandId : null,
                'name'        => $name,
                'slug'        => $slug,
                'sku'         => $sku,
                'short'       => $shortDesc !== '' ? $shortDesc : null,
                'description' => $desc !== '' ? $desc : null,
                'price'       => $price,
                'compare'     => $comparePrice,
                'stock'       => $stock,
                'featured'    => $featured,
                'active'      => $active,
            ]);
            $newId = (int) db()->lastInsertId();
            return ['errors' => [], 'id' => $newId];
        }
        $stmt = db()->prepare(
            'UPDATE products
             SET category_id = :category_id, brand_id = :brand_id,
                 name = :name, slug = :slug, sku = :sku,
                 short_description = :short, description = :description,
                 price = :price, compare_price = :compare, stock = :stock,
                 is_featured = :featured, is_active = :active
             WHERE id = :id'
        );
        $stmt->execute([
            'category_id' => $categoryId,
            'brand_id'    => $brandId > 0 ? $brandId : null,
            'name'        => $name,
            'slug'        => $slug,
            'sku'         => $sku,
            'short'       => $shortDesc !== '' ? $shortDesc : null,
            'description' => $desc !== '' ? $desc : null,
            'price'       => $price,
            'compare'     => $comparePrice,
            'stock'       => $stock,
            'featured'    => $featured,
            'active'      => $active,
            'id'          => $id,
        ]);
        return ['errors' => [], 'id' => $id];
    } catch (PDOException $e) {
        if ((int) ($e->errorInfo[1] ?? 0) === 1062) {
            return ['errors' => ['SKU hoặc slug đã tồn tại.'], 'id' => $id];
        }
        return ['errors' => ['Không thể lưu sản phẩm: ' . $e->getMessage()], 'id' => $id];
    }
}

/**
 * Validate multiple uploads as actual JPEG, PNG or WebP image files.
 * @return array{files:array<int,array<string,mixed>>,errors:array<int,string>}
 */
function admin_product_image_uploads_validate(array $upload): array
{
    $files = [];
    $errors = [];
    if (!isset($upload['name']) || !is_array($upload['name'])) {
        return ['files' => [], 'errors' => []];
    }
    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    foreach ($upload['name'] as $index => $originalName) {
        $rawError = $upload['error'][$index] ?? UPLOAD_ERR_NO_FILE;
        if (!is_scalar($rawError) || !is_string($originalName)) {
            $errors[] = 'Dữ liệu hình ảnh tải lên không hợp lệ.';
            continue;
        }
        $error = (int) $rawError;
        if ($error === UPLOAD_ERR_NO_FILE) { continue; }
        if ($error !== UPLOAD_ERR_OK) {
            $errors[] = 'Không thể tải lên một hình ảnh. Vui lòng chọn lại tệp.';
            continue;
        }
        $rawTmp = $upload['tmp_name'][$index] ?? '';
        if (!is_string($rawTmp)) { $errors[] = 'Đường dẫn tệp tải lên không hợp lệ.'; continue; }
        $tmp = $rawTmp;
        $size = is_file($tmp) ? (int) filesize($tmp) : 0;
        if ($tmp === '' || !is_uploaded_file($tmp) || $size <= 0 || $size > MAX_UPLOAD_BYTES) {
            $errors[] = 'Mỗi hình ảnh phải có dung lượng từ 1 byte đến 5 MB.';
            continue;
        }
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($tmp) ?: '';
        $imageInfo = @getimagesize($tmp);
        if (!isset($extensions[$mime]) || $imageInfo === false || ($imageInfo['mime'] ?? '') !== $mime) {
            $errors[] = 'Chỉ chấp nhận hình ảnh JPG, JPEG, PNG hoặc WebP hợp lệ.';
            continue;
        }
        $files[] = [
            'field_index' => (int) $index,
            'tmp_name' => $tmp,
            'size' => $size,
            'mime' => $mime,
            'extension' => $extensions[$mime],
        ];
        if (count($files) > 8) {
            $errors[] = 'Mỗi lần chỉ được tải lên tối đa 8 hình ảnh.';
            break;
        }
    }
    return ['files' => $files, 'errors' => array_values(array_unique($errors))];
}

/**
 * Update product image records and safely store/remove their local files.
 * @param array<int,array<string,mixed>> $uploads Previously validated upload rows.
 * @param array<mixed> $deleteIds Submitted image IDs.
 * @return array{errors:array<int,string>}
 */
function admin_product_images_manage(int $productId, array $uploads, string $primaryChoice, array $deleteIds, string $altText): array
{
    if ($productId <= 0) { return ['errors' => ['Không tìm thấy sản phẩm để cập nhật hình ảnh.']]; }
    try {
        $stmt = db()->prepare('SELECT id, image_path, is_primary, sort_order FROM product_images WHERE product_id = :id ORDER BY sort_order, id');
        $stmt->execute(['id' => $productId]);
        $rows = $stmt->fetchAll() ?: [];
    } catch (PDOException $e) {
        return ['errors' => ['Không thể đọc thư viện ảnh của sản phẩm.']];
    }
    $existing = [];
    foreach ($rows as $row) { $existing[(int) $row['id']] = $row; }
    $delete = [];
    foreach ($deleteIds as $rawId) {
        $imageId = filter_var($rawId, FILTER_VALIDATE_INT);
        if ($imageId === false || !isset($existing[(int) $imageId])) {
            return ['errors' => ['Danh sách hình ảnh cần xóa không hợp lệ.']];
        }
        $delete[(int) $imageId] = true;
    }
    $retained = array_diff_key($existing, $delete);
    $uploadByIndex = [];
    foreach ($uploads as $file) { $uploadByIndex[(int) $file['field_index']] = $file; }

    $choiceType = '';
    $choiceId = null;
    if (preg_match('/^(existing|upload)-(\d+)$/', $primaryChoice, $match)) {
        $choiceType = $match[1];
        $choiceId = (int) $match[2];
    }
    if ($choiceType === 'existing' && ($choiceId <= 0 || !isset($retained[$choiceId]))) {
        return ['errors' => ['Hãy chọn ảnh chính trong số hình ảnh sẽ được giữ lại.']];
    }
    if ($choiceType === 'upload' && !isset($uploadByIndex[$choiceId])) {
        return ['errors' => ['Hình ảnh chính được chọn không hợp lệ.']];
    }
    if ($choiceType === '') {
        foreach ($retained as $imageId => $row) {
            if ((int) $row['is_primary'] === 1) { $choiceType = 'existing'; $choiceId = (int) $imageId; break; }
        }
        if ($choiceType === '' && $uploadByIndex !== []) {
            $choiceType = 'upload'; $choiceId = (int) array_key_first($uploadByIndex);
        }
    }
    if (($rows !== [] || $uploads !== []) && $retained === [] && $uploadByIndex === []) {
        return ['errors' => ['Không thể xóa toàn bộ ảnh. Hãy giữ lại hoặc tải lên một ảnh chính.']];
    }
    if (($retained !== [] || $uploadByIndex !== []) && $choiceType === '') {
        return ['errors' => ['Vui lòng chọn một ảnh chính cho sản phẩm.']];
    }
    if ($uploads === [] && $delete === [] && ($choiceType === '' || ($choiceType === 'existing' && !empty($existing[$choiceId]['is_primary'])))) {
        return ['errors' => []];
    }

    $stored = [];
    $deletedPaths = [];
    try {
        if (!is_dir(UPLOAD_PRODUCTS) && !mkdir(UPLOAD_PRODUCTS, 0755, true) && !is_dir(UPLOAD_PRODUCTS)) {
            throw new RuntimeException('Upload directory unavailable');
        }
        foreach ($uploadByIndex as $fieldIndex => $file) {
            $filename = bin2hex(random_bytes(16)) . '.' . $file['extension'];
            $destination = UPLOAD_PRODUCTS . DIRECTORY_SEPARATOR . $filename;
            if (!move_uploaded_file((string) $file['tmp_name'], $destination)) { throw new RuntimeException('Upload move failed'); }
            $stored[$fieldIndex] = 'products/' . $filename;
        }
        db()->beginTransaction();
        $stmt = db()->prepare('UPDATE product_images SET is_primary = 0 WHERE product_id = :id');
        $stmt->execute(['id' => $productId]);
        foreach (array_keys($delete) as $imageId) {
            $deletedPaths[] = (string) $existing[$imageId]['image_path'];
            $stmt = db()->prepare('DELETE FROM product_images WHERE id = :image AND product_id = :product');
            $stmt->execute(['image' => $imageId, 'product' => $productId]);
        }
        $nextSort = 0;
        foreach ($retained as $row) { $nextSort = max($nextSort, (int) $row['sort_order'] + 1); }
        foreach ($stored as $fieldIndex => $path) {
            $stmt = db()->prepare('INSERT INTO product_images (product_id, image_path, alt_text, is_primary, sort_order) VALUES (:product, :path, :alt, :primary, :sort)');
            $stmt->execute([
                'product' => $productId,
                'path' => $path,
                'alt' => mb_substr(trim($altText), 0, 200),
                'primary' => ($choiceType === 'upload' && $choiceId === $fieldIndex) ? 1 : 0,
                'sort' => $nextSort++,
            ]);
        }
        if ($choiceType === 'existing') {
            $stmt = db()->prepare('UPDATE product_images SET is_primary = 1 WHERE id = :image AND product_id = :product');
            $stmt->execute(['image' => $choiceId, 'product' => $productId]);
        }
        db()->commit();
    } catch (Throwable $e) {
        if (db()->inTransaction()) { db()->rollBack(); }
        foreach ($stored as $path) {
            $fullPath = UPLOAD_PATH . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
            if (is_file($fullPath)) { @unlink($fullPath); }
        }
        return ['errors' => ['Không thể cập nhật hình ảnh sản phẩm. Vui lòng thử lại.']];
    }
    foreach ($deletedPaths as $path) {
        $normalized = str_replace('\\', '/', ltrim($path, '/'));
        if (str_starts_with($normalized, 'products/') && !str_contains($normalized, '../')) {
            $fullPath = UPLOAD_PATH . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $normalized);
            if (is_file($fullPath)) { @unlink($fullPath); }
        }
    }
    return ['errors' => []];
}
function admin_product_delete(int $id): array
{
    if ($id <= 0) {
        return ['ok' => false, 'message' => 'Sản phẩm không hợp lệ.'];
    }
    try {
        // Block delete if the product appears in any order_items row —
        // we keep historical data intact by soft-deleting instead.
        $stmt = db()->prepare('SELECT COUNT(*) FROM order_items WHERE product_id = :id');
        $stmt->execute(['id' => $id]);
        $orderCount = (int) $stmt->fetchColumn();

        if ($orderCount > 0) {
            $stmt = db()->prepare('UPDATE products SET is_active = 0 WHERE id = :id');
            $stmt->execute(['id' => $id]);
            return [
                'ok' => true,
                'soft' => true,
                'message' => "Sản phẩm đã có {$orderCount} lượt đặt hàng nên được ẩn thay vì xóa vĩnh viễn.",
            ];
        }
        // No order history — hard delete along with images.
        $stmt = db()->prepare('DELETE FROM product_images WHERE product_id = :id');
        $stmt->execute(['id' => $id]);
        $stmt = db()->prepare('DELETE FROM products WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return ['ok' => true, 'soft' => false, 'message' => 'Đã xóa sản phẩm.'];
    } catch (PDOException $e) {
        return ['ok' => false, 'message' => 'Không thể xóa: ' . $e->getMessage()];
    }
}

/* ========================================================================
 * Orders
 * ====================================================================== */

function admin_orders_all(string $status = ''): array
{
    $where = '';
    $params = [];
    if ($status !== '') {
        $where = 'WHERE o.status = :status';
        $params['status'] = $status;
    }
    try {
        $stmt = db()->prepare(
            "SELECT o.*, u.name AS customer_name, u.email AS customer_email,
                    (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) AS item_count
             FROM orders o
             INNER JOIN users u ON u.id = o.user_id
             $where
             ORDER BY o.created_at DESC
             LIMIT 200"
        );
        $stmt->execute($params);
        return $stmt->fetchAll() ?: [];
    } catch (PDOException $e) {
        return [];
    }
}

function admin_order_find(int $id): ?array
{
    if ($id <= 0) {
        return null;
    }
    try {
        $stmt = db()->prepare(
            'SELECT o.*, u.name AS customer_name, u.email AS customer_email, u.phone AS customer_phone
             FROM orders o
             INNER JOIN users u ON u.id = o.user_id
             WHERE o.id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $order = $stmt->fetch();
        return $order ?: null;
    } catch (PDOException $e) {
        return null;
    }
}

function admin_order_find_by_number(string $number): ?array
{
    $number = trim($number);
    if ($number === '') {
        return null;
    }
    try {
        $stmt = db()->prepare(
            'SELECT o.*, u.name AS customer_name, u.email AS customer_email, u.phone AS customer_phone
             FROM orders o
             INNER JOIN users u ON u.id = o.user_id
             WHERE o.order_number = :num LIMIT 1'
        );
        $stmt->execute(['num' => $number]);
        $order = $stmt->fetch();
        return $order ?: null;
    } catch (PDOException $e) {
        return null;
    }
}

function admin_order_update_status(int $orderId, string $status, string $paymentStatus = ''): array
{
    $allowedStatuses = [
        ORDER_PENDING, ORDER_CONFIRMED, ORDER_PROCESSING,
        ORDER_SHIPPED, ORDER_DELIVERED, ORDER_CANCELLED,
    ];
    $allowedPayment = [PAYMENT_UNPAID, PAYMENT_PAID, PAYMENT_FAILED, PAYMENT_REFUNDED];
    if (!in_array($status, $allowedStatuses, true)) {
        return ['ok' => false, 'message' => 'Trạng thái đơn hàng không hợp lệ.'];
    }
    if ($paymentStatus !== '' && !in_array($paymentStatus, $allowedPayment, true)) {
        return ['ok' => false, 'message' => 'Trạng thái thanh toán không hợp lệ.'];
    }
    try {
        if ($paymentStatus === '') {
            $stmt = db()->prepare('UPDATE orders SET status = :s WHERE id = :id');
            $stmt->execute(['s' => $status, 'id' => $orderId]);
        } else {
            $stmt = db()->prepare(
                'UPDATE orders SET status = :s, payment_status = :ps WHERE id = :id'
            );
            $stmt->execute(['s' => $status, 'ps' => $paymentStatus, 'id' => $orderId]);
        }
        // Mirror payment status onto the latest payment row if changed.
        if ($paymentStatus !== '') {
            $stmt = db()->prepare(
                'UPDATE payments SET status = :ps WHERE order_id = :id ORDER BY id DESC LIMIT 1'
            );
            $stmt->execute(['ps' => $paymentStatus, 'id' => $orderId]);
        }
        return ['ok' => true, 'message' => 'Đã cập nhật trạng thái đơn hàng.'];
    } catch (PDOException $e) {
        return ['ok' => false, 'message' => 'Không thể cập nhật: ' . $e->getMessage()];
    }
}

/* ========================================================================
 * Customers
 * ====================================================================== */

function admin_customers_all(): array
{
    try {
        $stmt = db()->prepare(
            "SELECT u.*,
                    (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.id) AS order_count,
                    (SELECT COALESCE(SUM(total),0) FROM orders o WHERE o.user_id = u.id) AS lifetime_total
             FROM users u
             WHERE u.role = :role
             ORDER BY u.created_at DESC"
        );
        $stmt->execute(['role' => ROLE_CUSTOMER]);
        return $stmt->fetchAll() ?: [];
    } catch (PDOException $e) {
        return [];
    }
}

function admin_customer_find(int $id): ?array
{
    if ($id <= 0) {
        return null;
    }
    try {
        $stmt = db()->prepare(
            'SELECT id, name, email, phone, role, status, created_at, updated_at
             FROM users WHERE id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    } catch (PDOException $e) {
        return null;
    }
}

function admin_customer_set_status(int $id, string $status): array
{
    $allowed = [USER_STATUS_ACTIVE, USER_STATUS_INACTIVE, USER_STATUS_BANNED];
    if (!in_array($status, $allowed, true)) {
        return ['ok' => false, 'message' => 'Trạng thái không hợp lệ.'];
    }
    try {
        $stmt = db()->prepare('UPDATE users SET status = :s WHERE id = :id AND role = :role');
        $stmt->execute(['s' => $status, 'id' => $id, 'role' => ROLE_CUSTOMER]);
        return ['ok' => true, 'message' => 'Đã cập nhật trạng thái khách hàng.'];
    } catch (PDOException $e) {
        return ['ok' => false, 'message' => 'Không thể cập nhật: ' . $e->getMessage()];
    }
}

/* ========================================================================
 * Posts (Blog / News)
 * ====================================================================== */

function admin_post_categories_all(): array
{
    try {
        $stmt = db()->query('SELECT * FROM post_categories ORDER BY name ASC');
        return $stmt->fetchAll() ?: [];
    } catch (PDOException $e) {
        return [];
    }
}

function admin_posts_all(string $status = ''): array
{
    $where = '';
    $params = [];
    if ($status === 'published') {
        $where = 'WHERE p.is_published = 1';
    } elseif ($status === 'draft') {
        $where = 'WHERE p.is_published = 0';
    }
    try {
        $stmt = db()->prepare(
            "SELECT p.*, u.name AS author_name, pc.name AS category_name
             FROM posts p
             INNER JOIN users u ON u.id = p.user_id
             LEFT JOIN post_categories pc ON pc.id = p.post_category_id
             $where
             ORDER BY p.created_at DESC
             LIMIT 200"
        );
        $stmt->execute($params);
        return $stmt->fetchAll() ?: [];
    } catch (PDOException $e) {
        return [];
    }
}

function admin_post_find(int $id): ?array
{
    if ($id <= 0) {
        return null;
    }
    try {
        $stmt = db()->prepare('SELECT * FROM posts WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    } catch (PDOException $e) {
        return null;
    }
}

function admin_post_save(array $input, ?int $id, int $authorId): array
{
    $errors = [];
    $title      = trim((string) ($input['title'] ?? ''));
    $slug      = trim((string) ($input['slug'] ?? ''));
    $excerpt   = trim((string) ($input['excerpt'] ?? ''));
    $content   = trim((string) ($input['content'] ?? ''));
    $category  = (int) ($input['post_category_id'] ?? 0);
    $thumbnail = trim((string) ($input['thumbnail'] ?? ''));
    $published = !empty($input['is_published']) ? 1 : 0;

    if ($title === '' || mb_strlen($title) > 220) {
        $errors[] = 'Tiêu đề là bắt buộc (tối đa 220 ký tự).';
    }
    if ($slug === '') {
        $slug = slugify($title);
    } elseif (!preg_match('/^[a-z0-9-]+$/', $slug)) {
        $errors[] = 'Slug chỉ gồm chữ thường, số và dấu gạch ngang.';
    }
    if ($content === '') {
        $errors[] = 'Nội dung bài viết là bắt buộc.';
    }

    // Slug duplicate check
    try {
        $sql = 'SELECT id FROM posts WHERE slug = :slug';
        $params = ['slug' => $slug];
        if ($id !== null) {
            $sql .= ' AND id <> :id';
            $params['id'] = $id;
        }
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        if ($stmt->fetch()) {
            $errors[] = 'Slug đã tồn tại. Vui lòng chọn slug khác.';
        }
    } catch (PDOException $e) {
        // ignore
    }

    if ($errors !== []) {
        return ['errors' => $errors, 'id' => $id];
    }

    $publishedAt = $published === 1 ? date('Y-m-d H:i:s') : null;

    try {
        if ($id === null) {
            $stmt = db()->prepare(
                'INSERT INTO posts
                   (user_id, post_category_id, title, slug, excerpt, content,
                    thumbnail, is_published, published_at)
                 VALUES
                   (:user, :category, :title, :slug, :excerpt, :content,
                    :thumbnail, :published, :published_at)'
            );
            $stmt->execute([
                'user'         => $authorId,
                'category'     => $category > 0 ? $category : null,
                'title'        => $title,
                'slug'         => $slug,
                'excerpt'      => $excerpt !== '' ? $excerpt : null,
                'content'      => $content,
                'thumbnail'    => $thumbnail !== '' ? $thumbnail : null,
                'published'    => $published,
                'published_at' => $publishedAt,
            ]);
            return ['errors' => [], 'id' => (int) db()->lastInsertId()];
        }
        $stmt = db()->prepare(
            'UPDATE posts
             SET post_category_id = :category, title = :title, slug = :slug,
                 excerpt = :excerpt, content = :content, thumbnail = :thumbnail,
                 is_published = :published,
                 published_at = COALESCE(:published_at, published_at)
             WHERE id = :id'
        );
        // Only stamp published_at when transitioning to published and it's still null.
        $stmt->execute([
            'category'     => $category > 0 ? $category : null,
            'title'        => $title,
            'slug'         => $slug,
            'excerpt'      => $excerpt !== '' ? $excerpt : null,
            'content'      => $content,
            'thumbnail'    => $thumbnail !== '' ? $thumbnail : null,
            'published'    => $published,
            'published_at' => $published === 1 ? date('Y-m-d H:i:s') : null,
            'id'           => $id,
        ]);
        return ['errors' => [], 'id' => $id];
    } catch (PDOException $e) {
        if ((int) ($e->errorInfo[1] ?? 0) === 1062) {
            return ['errors' => ['Slug đã tồn tại.'], 'id' => $id];
        }
        return ['errors' => ['Không thể lưu bài viết: ' . $e->getMessage()], 'id' => $id];
    }
}

function admin_post_delete(int $id): array
{
    if ($id <= 0) {
        return ['ok' => false, 'message' => 'Bài viết không hợp lệ.'];
    }
    try {
        $stmt = db()->prepare('DELETE FROM posts WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return ['ok' => true, 'soft' => false, 'message' => 'Đã xóa bài viết.'];
    } catch (PDOException $e) {
        return ['ok' => false, 'message' => 'Không thể xóa: ' . $e->getMessage()];
    }
}

/* ========================================================================
 * Coupons
 * ====================================================================== */

function admin_coupons_all(): array
{
    try {
        $stmt = db()->query('SELECT * FROM coupons ORDER BY created_at DESC');
        return $stmt->fetchAll() ?: [];
    } catch (PDOException $e) {
        return [];
    }
}

function admin_coupon_find(int $id): ?array
{
    if ($id <= 0) {
        return null;
    }
    try {
        $stmt = db()->prepare('SELECT * FROM coupons WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    } catch (PDOException $e) {
        return null;
    }
}

function admin_coupon_save(array $input, ?int $id = null): array
{
    $errors = [];
    $code  = strtoupper(trim((string) ($input['code'] ?? '')));
    $type  = (string) ($input['type'] ?? COUPON_PERCENT);
    $value = (float) ($input['value'] ?? 0);
    $minOrder = (float) ($input['min_order'] ?? 0);
    $maxDiscount = $input['max_discount'] === '' || $input['max_discount'] === null ? null : (float) $input['max_discount'];
    $usageLimit = $input['usage_limit'] === '' || $input['usage_limit'] === null ? null : (int) $input['usage_limit'];
    $startsAt = $input['starts_at'] ?? null;
    $expiresAt = $input['expires_at'] ?? null;
    $active = !empty($input['is_active']) ? 1 : 0;

    if ($code === '' || !preg_match('/^[A-Z0-9_-]+$/', $code)) {
        $errors[] = 'Mã giảm giá chỉ gồm chữ in hoa, số, gạch dưới và gạch ngang.';
    }
    if (!in_array($type, [COUPON_PERCENT, COUPON_FIXED], true)) {
        $errors[] = 'Loại giảm giá không hợp lệ.';
    }
    if ($value < 0) {
        $errors[] = 'Giá trị giảm phải >= 0.';
    }
    if ($type === COUPON_PERCENT && $value > 100) {
        $errors[] = 'Giảm theo phần trăm không được vượt quá 100%.';
    }
    if ($minOrder < 0) {
        $errors[] = 'Đơn tối thiểu phải >= 0.';
    }

    // Duplicate code check
    try {
        $sql = 'SELECT id FROM coupons WHERE code = :code';
        $params = ['code' => $code];
        if ($id !== null) {
            $sql .= ' AND id <> :id';
            $params['id'] = $id;
        }
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        if ($stmt->fetch()) {
            $errors[] = 'Mã giảm giá đã tồn tại.';
        }
    } catch (PDOException $e) {
        // ignore
    }

    if ($errors !== []) {
        return ['errors' => $errors, 'id' => $id];
    }

    $startsAtValue = ($startsAt !== null && $startsAt !== '') ? date('Y-m-d H:i:s', strtotime($startsAt)) : null;
    $expiresAtValue = ($expiresAt !== null && $expiresAt !== '') ? date('Y-m-d H:i:s', strtotime($expiresAt)) : null;

    try {
        if ($id === null) {
            $stmt = db()->prepare(
                'INSERT INTO coupons
                   (code, type, value, min_order, max_discount, usage_limit, used_count,
                    starts_at, expires_at, is_active)
                 VALUES
                   (:code, :type, :value, :min, :maxd, :usage_limit, 0,
                    :starts, :expires, :active)'
            );
            $stmt->execute([
                'code' => $code,
                'type' => $type,
                'value' => $value,
                'min' => $minOrder,
                'maxd' => $maxDiscount,
                'usage_limit' => $usageLimit,
                'starts' => $startsAtValue,
                'expires' => $expiresAtValue,
                'active' => $active,
            ]);
            return ['errors' => [], 'id' => (int) db()->lastInsertId()];
        }
        $stmt = db()->prepare(
            'UPDATE coupons
             SET code = :code, type = :type, value = :value, min_order = :min,
                 max_discount = :maxd, usage_limit = :usage_limit,
                 starts_at = :starts, expires_at = :expires, is_active = :active
             WHERE id = :id'
        );
        $stmt->execute([
            'code' => $code,
            'type' => $type,
            'value' => $value,
            'min' => $minOrder,
            'maxd' => $maxDiscount,
            'usage_limit' => $usageLimit,
            'starts' => $startsAtValue,
            'expires' => $expiresAtValue,
            'active' => $active,
            'id' => $id,
        ]);
        return ['errors' => [], 'id' => $id];
    } catch (PDOException $e) {
        if ((int) ($e->errorInfo[1] ?? 0) === 1062) {
            return ['errors' => ['Mã giảm giá đã tồn tại.'], 'id' => $id];
        }
        return ['errors' => ['Không thể lưu mã giảm giá: ' . $e->getMessage()], 'id' => $id];
    }
}

function admin_coupon_delete(int $id): array
{
    if ($id <= 0) {
        return ['ok' => false, 'message' => 'Mã giảm giá không hợp lệ.'];
    }
    try {
        $stmt = db()->prepare('SELECT COUNT(*) FROM coupon_usage WHERE coupon_id = :id');
        $stmt->execute(['id' => $id]);
        $used = (int) $stmt->fetchColumn();
        if ($used > 0) {
            $stmt = db()->prepare('UPDATE coupons SET is_active = 0 WHERE id = :id');
            $stmt->execute(['id' => $id]);
            return [
                'ok' => true,
                'soft' => true,
                'message' => "Mã đã có {$used} lượt sử dụng nên được ẩn thay vì xóa vĩnh viễn.",
            ];
        }
        $stmt = db()->prepare('DELETE FROM coupons WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return ['ok' => true, 'soft' => false, 'message' => 'Đã xóa mã giảm giá.'];
    } catch (PDOException $e) {
        return ['ok' => false, 'message' => 'Không thể xóa: ' . $e->getMessage()];
    }
}

/* ========================================================================
 * Banners
 * ====================================================================== */

function admin_banners_all(): array
{
    try {
        $stmt = db()->query('SELECT * FROM banners ORDER BY sort_order ASC, id DESC');
        return $stmt->fetchAll() ?: [];
    } catch (PDOException $e) {
        return [];
    }
}

function admin_banner_find(int $id): ?array
{
    if ($id <= 0) {
        return null;
    }
    try {
        $stmt = db()->prepare('SELECT * FROM banners WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    } catch (PDOException $e) {
        return null;
    }
}

function admin_banner_save(array $input, ?int $id = null): array
{
    $errors = [];
    $title    = trim((string) ($input['title'] ?? ''));
    $image    = trim((string) ($input['image'] ?? ''));
    $link     = trim((string) ($input['link'] ?? ''));
    $position = trim((string) ($input['position'] ?? 'home_hero'));
    $sort     = (int) ($input['sort_order'] ?? 0);
    $active   = !empty($input['is_active']) ? 1 : 0;
    $starts   = $input['starts_at'] ?? null;
    $ends     = $input['ends_at'] ?? null;

    if ($title === '' || mb_strlen($title) > 160) {
        $errors[] = 'Tiêu đề là bắt buộc (tối đa 160 ký tự).';
    }
    if ($image === '') {
        $errors[] = 'Đường dẫn hình ảnh là bắt buộc.';
    }

    if ($errors !== []) {
        return ['errors' => $errors, 'id' => $id];
    }

    $startsValue = ($starts !== null && $starts !== '') ? date('Y-m-d H:i:s', strtotime($starts)) : null;
    $endsValue   = ($ends !== null && $ends !== '') ? date('Y-m-d H:i:s', strtotime($ends)) : null;

    try {
        if ($id === null) {
            $stmt = db()->prepare(
                'INSERT INTO banners (title, image, link, position, sort_order, is_active, starts_at, ends_at)
                 VALUES (:title, :image, :link, :position, :sort, :active, :starts, :ends)'
            );
            $stmt->execute([
                'title' => $title,
                'image' => $image,
                'link' => $link !== '' ? $link : null,
                'position' => $position,
                'sort' => $sort,
                'active' => $active,
                'starts' => $startsValue,
                'ends' => $endsValue,
            ]);
            return ['errors' => [], 'id' => (int) db()->lastInsertId()];
        }
        $stmt = db()->prepare(
            'UPDATE banners
             SET title = :title, image = :image, link = :link, position = :position,
                 sort_order = :sort, is_active = :active, starts_at = :starts, ends_at = :ends
             WHERE id = :id'
        );
        $stmt->execute([
            'title' => $title,
            'image' => $image,
            'link' => $link !== '' ? $link : null,
            'position' => $position,
            'sort' => $sort,
            'active' => $active,
            'starts' => $startsValue,
            'ends' => $endsValue,
            'id' => $id,
        ]);
        return ['errors' => [], 'id' => $id];
    } catch (PDOException $e) {
        return ['errors' => ['Không thể lưu banner: ' . $e->getMessage()], 'id' => $id];
    }
}

function admin_banner_delete(int $id): array
{
    if ($id <= 0) {
        return ['ok' => false, 'message' => 'Banner không hợp lệ.'];
    }
    try {
        $stmt = db()->prepare('DELETE FROM banners WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return ['ok' => true, 'soft' => false, 'message' => 'Đã xóa banner.'];
    } catch (PDOException $e) {
        return ['ok' => false, 'message' => 'Không thể xóa: ' . $e->getMessage()];
    }
}

/* ========================================================================
 * Reviews
 * ====================================================================== */

function admin_reviews_all(string $filter = ''): array
{
    $where = '';
    $params = [];
    if ($filter === 'pending') {
        $where = 'WHERE r.is_approved = 0';
    } elseif ($filter === 'approved') {
        $where = 'WHERE r.is_approved = 1';
    }
    try {
        $stmt = db()->prepare(
            "SELECT r.*, u.name AS user_name, u.email AS user_email,
                    p.name AS product_name
             FROM reviews r
             INNER JOIN users u ON u.id = r.user_id
             INNER JOIN products p ON p.id = r.product_id
             $where
             ORDER BY r.created_at DESC
             LIMIT 200"
        );
        $stmt->execute($params);
        return $stmt->fetchAll() ?: [];
    } catch (PDOException $e) {
        return [];
    }
}

function admin_review_set_approved(int $id, bool $approved): array
{
    if ($id <= 0) {
        return ['ok' => false, 'message' => 'Đánh giá không hợp lệ.'];
    }
    try {
        $stmt = db()->prepare('UPDATE reviews SET is_approved = :a WHERE id = :id');
        $stmt->execute(['a' => $approved ? 1 : 0, 'id' => $id]);
        return ['ok' => true, 'message' => $approved ? 'Đã duyệt đánh giá.' : 'Đã ẩn đánh giá.'];
    } catch (PDOException $e) {
        return ['ok' => false, 'message' => 'Không thể cập nhật: ' . $e->getMessage()];
    }
}

function admin_review_delete(int $id): array
{
    if ($id <= 0) {
        return ['ok' => false, 'message' => 'Đánh giá không hợp lệ.'];
    }
    try {
        $stmt = db()->prepare('DELETE FROM reviews WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return ['ok' => true, 'soft' => false, 'message' => 'Đã xóa đánh giá.'];
    } catch (PDOException $e) {
        return ['ok' => false, 'message' => 'Không thể xóa: ' . $e->getMessage()];
    }
}

/* ========================================================================
 * Statistics
 * ====================================================================== */

function admin_statistics_overview(): array
{
    $out = [
        'revenue_total'      => 0.0,
        'revenue_paid'       => 0.0,
        'orders_total'       => 0,
        'orders_pending'     => 0,
        'orders_confirmed'   => 0,
        'orders_processing'  => 0,
        'orders_shipped'     => 0,
        'orders_delivered'   => 0,
        'orders_cancelled'   => 0,
        'products_total'     => 0,
        'products_active'    => 0,
        'customers_total'    => 0,
        'posts_total'        => 0,
        'posts_published'    => 0,
        'reviews_total'      => 0,
        'reviews_pending'    => 0,
    ];
    try {
        $out['revenue_total'] = (float) db()->query(
            "SELECT COALESCE(SUM(total),0) FROM orders WHERE status <> '" . ORDER_CANCELLED . "'"
        )->fetchColumn();
        $out['revenue_paid'] = (float) db()->query(
            "SELECT COALESCE(SUM(total),0) FROM orders WHERE payment_status = '" . PAYMENT_PAID . "'"
        )->fetchColumn();
        $out['orders_total']      = (int) db()->query('SELECT COUNT(*) FROM orders')->fetchColumn();
        $out['orders_pending']    = (int) db()->query("SELECT COUNT(*) FROM orders WHERE status='" . ORDER_PENDING . "'")->fetchColumn();
        $out['orders_confirmed']  = (int) db()->query("SELECT COUNT(*) FROM orders WHERE status='" . ORDER_CONFIRMED . "'")->fetchColumn();
        $out['orders_processing'] = (int) db()->query("SELECT COUNT(*) FROM orders WHERE status='" . ORDER_PROCESSING . "'")->fetchColumn();
        $out['orders_shipped']    = (int) db()->query("SELECT COUNT(*) FROM orders WHERE status='" . ORDER_SHIPPED . "'")->fetchColumn();
        $out['orders_delivered']  = (int) db()->query("SELECT COUNT(*) FROM orders WHERE status='" . ORDER_DELIVERED . "'")->fetchColumn();
        $out['orders_cancelled']  = (int) db()->query("SELECT COUNT(*) FROM orders WHERE status='" . ORDER_CANCELLED . "'")->fetchColumn();

        $out['products_total']  = (int) db()->query('SELECT COUNT(*) FROM products')->fetchColumn();
        $out['products_active'] = (int) db()->query('SELECT COUNT(*) FROM products WHERE is_active = 1')->fetchColumn();

        $out['customers_total'] = (int) db()->query(
            "SELECT COUNT(*) FROM users WHERE role='" . ROLE_CUSTOMER . "'"
        )->fetchColumn();

        $out['posts_total']     = (int) db()->query('SELECT COUNT(*) FROM posts')->fetchColumn();
        $out['posts_published'] = (int) db()->query('SELECT COUNT(*) FROM posts WHERE is_published = 1')->fetchColumn();

        $out['reviews_total']   = (int) db()->query('SELECT COUNT(*) FROM reviews')->fetchColumn();
        $out['reviews_pending'] = (int) db()->query('SELECT COUNT(*) FROM reviews WHERE is_approved = 0')->fetchColumn();
    } catch (PDOException $e) {
        // leave defaults
    }
    return $out;
}

function admin_statistics_revenue_by_period(string $period = 'month'): array
{
    // period: today | week | month | year
    try {
        switch ($period) {
            case 'today':
                $since = "DATE(o.created_at) = CURDATE()";
                break;
            case 'week':
                $since = "o.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                break;
            case 'year':
                $since = "o.created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
                break;
            case 'month':
            default:
                $since = "o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                break;
        }
        $stmt = db()->prepare(
            "SELECT COALESCE(SUM(total),0) AS revenue, COUNT(*) AS cnt
             FROM orders o
             WHERE $since AND o.status <> '" . ORDER_CANCELLED . "'"
        );
        $stmt->execute();
        $row = $stmt->fetch();
        return [
            'period'  => $period,
            'revenue' => (float) ($row['revenue'] ?? 0),
            'orders'  => (int) ($row['cnt'] ?? 0),
        ];
    } catch (PDOException $e) {
        return ['period' => $period, 'revenue' => 0.0, 'orders' => 0];
    }
}

function admin_statistics_best_sellers(int $limit = 10): array
{
    try {
        $stmt = db()->prepare(
            "SELECT p.id, p.name, p.sku, SUM(oi.quantity) AS sold_qty,
                    COALESCE(SUM(oi.total), 0) AS revenue
             FROM order_items oi
             INNER JOIN orders o ON o.id = oi.order_id
             INNER JOIN products p ON p.id = oi.product_id
             WHERE o.status <> '" . ORDER_CANCELLED . "'
             GROUP BY p.id, p.name, p.sku
             ORDER BY sold_qty DESC, revenue DESC
             LIMIT :lim"
        );
        $stmt->bindValue('lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    } catch (PDOException $e) {
        return [];
    }
}

function admin_statistics_sales_by_category(): array
{
    try {
        $stmt = db()->query(
            "SELECT c.id, c.name, COALESCE(SUM(oi.total), 0) AS revenue,
                    COALESCE(SUM(oi.quantity), 0) AS sold_qty
             FROM categories c
             LEFT JOIN products p ON p.category_id = c.id
             LEFT JOIN order_items oi ON oi.product_id = p.id
             LEFT JOIN orders o ON o.id = oi.order_id AND o.status <> '" . ORDER_CANCELLED . "'
             GROUP BY c.id, c.name
             ORDER BY revenue DESC"
        );
        return $stmt->fetchAll() ?: [];
    } catch (PDOException $e) {
        return [];
    }
}

function admin_statistics_payment_breakdown(): array
{
    try {
        $stmt = db()->query(
            "SELECT payment_status, COUNT(*) AS cnt, COALESCE(SUM(total), 0) AS total
             FROM orders
             GROUP BY payment_status"
        );
        return $stmt->fetchAll() ?: [];
    } catch (PDOException $e) {
        return [];
    }
}