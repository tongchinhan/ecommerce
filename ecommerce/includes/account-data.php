<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/order-data.php';

function account_csrf_require(): void
{
    if (!csrf_verify()) {
        http_response_code(403);
        exit('Phiên bảo mật đã hết hạn. Vui lòng tải lại trang và thử lại.');
    }
}
function account_require_customer(string $route): void
{
    if (!is_logged_in()) {
        flash_set('error', 'Vui lòng đăng nhập để xem khu vực tài khoản.');
        redirect('/login.php?next=' . rawurlencode('/' . ltrim($route, '/')));
    }
    if (!is_customer()) {
        if (is_admin()) redirect('/admin/dashboard.php');
        flash_set('error', 'Tài khoản không có quyền truy cập trang này.');
        redirect('/login.php');
    }
}

function account_current_return(): string
{
    $scriptPath = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/products.php'));
    $basePath = (string) (parse_url(BASE_URL, PHP_URL_PATH) ?: '');
    $relative = ltrim(substr($scriptPath, strlen(rtrim($basePath, '/'))), '/');
    $allowed = ['index.php', 'products.php', 'category.php', 'product.php', 'search.php', 'blog.php', 'post.php'];
    if ($relative === 'account/wishlist.php') return 'account/wishlist.php';
    $route = in_array($relative, $allowed, true) ? $relative : 'products.php';
    $allowedQuery = ['q', 'category', 'category_id', 'min_price', 'max_price', 'sort', 'page', 'id', 'slug'];
    $query = [];
    foreach ($_GET as $key => $value) {
        if (in_array((string) $key, $allowedQuery, true) && is_scalar($value)) $query[$key] = mb_substr((string) $value, 0, 240, 'UTF-8');
    }
    return $route . ($query ? '?' . http_build_query($query) : '');
}

function account_safe_return(string $candidate): string
{
    $parts = parse_url($candidate);
    if (!is_array($parts) || isset($parts['scheme']) || isset($parts['host']) || isset($parts['port']) || isset($parts['user'])) return 'account/wishlist.php';
    $path = ltrim((string) ($parts['path'] ?? ''), '/');
    $allowed = ['index.php', 'products.php', 'category.php', 'product.php', 'search.php', 'blog.php', 'post.php',
        'account/wishlist.php', 'account/index.php', 'account/profile.php', 'account/addresses.php', 'account/orders.php', 'account/order-detail.php'];
    if (!in_array($path, $allowed, true)) return 'account/wishlist.php';
    $query = [];
    parse_str((string) ($parts['query'] ?? ''), $rawQuery);
    foreach ($rawQuery as $key => $value) {
        if (in_array((string) $key, ['q', 'category', 'category_id', 'min_price', 'max_price', 'sort', 'page', 'id', 'slug'], true) && is_scalar($value)) $query[$key] = mb_substr((string) $value, 0, 240, 'UTF-8');
    }
    return $path . ($query ? '?' . http_build_query($query) : '');
}

function wishlist_product_states(array $productIds, int $userId): array
{
    $ids = array_values(array_unique(array_filter(array_map('intval', $productIds), static fn(int $id): bool => $id > 0)));
    if ($userId <= 0 || !$ids) return [];
    $marks = implode(',', array_fill(0, count($ids), '?'));
    try {
        $stmt = db()->prepare('SELECT wi.product_id FROM wishlist_items wi INNER JOIN wishlists w ON w.id = wi.wishlist_id WHERE w.user_id = ? AND wi.product_id IN (' . $marks . ')');
        $stmt->execute(array_merge([$userId], $ids));
        $states = [];
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $id) $states[(int) $id] = true;
        return $states;
    } catch (PDOException $e) { return []; }
}

function wishlist_items_for_user(int $userId): array
{
    if ($userId <= 0) return [];
    try {
        $stmt = db()->prepare('SELECT p.id, p.name, p.slug, p.price, p.compare_price, p.stock, p.is_active, p.short_description, p.category_id, c.name AS category_name, pi.image_path, pi.alt_text AS image_alt, wi.created_at AS added_at FROM wishlists w INNER JOIN wishlist_items wi ON wi.wishlist_id = w.id INNER JOIN products p ON p.id = wi.product_id LEFT JOIN categories c ON c.id = p.category_id LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_primary = 1 WHERE w.user_id = :uid AND p.is_active = 1 ORDER BY wi.created_at DESC, wi.id DESC');
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll();
    } catch (PDOException $e) { return []; }
}

function wishlist_toggle_for_user(int $userId, int $productId): array
{
    if ($userId <= 0 || $productId <= 0) return ['ok' => false, 'reason' => 'invalid'];
    $pdo = db();
    try {
        $product = $pdo->prepare('SELECT id, is_active FROM products WHERE id = :id LIMIT 1');
        $product->execute(['id' => $productId]);
        $productRow = $product->fetch();
        if (!$productRow) return ['ok' => false, 'reason' => 'unavailable'];
        $pdo->beginTransaction();
        $create = $pdo->prepare('INSERT IGNORE INTO wishlists (user_id) VALUES (:uid)');
        $create->execute(['uid' => $userId]);
        $find = $pdo->prepare('SELECT id FROM wishlists WHERE user_id = :uid LIMIT 1 FOR UPDATE');
        $find->execute(['uid' => $userId]);
        $wishlistId = (int) $find->fetchColumn();
        if ($wishlistId <= 0) throw new RuntimeException('Wishlist unavailable');
        $findItem = $pdo->prepare('SELECT id FROM wishlist_items WHERE wishlist_id = :wid AND product_id = :pid LIMIT 1');
        $findItem->execute(['wid' => $wishlistId, 'pid' => $productId]);
        $itemId = $findItem->fetchColumn();
        if ($itemId !== false) {
            $delete = $pdo->prepare('DELETE FROM wishlist_items WHERE id = :id AND wishlist_id = :wid');
            $delete->execute(['id' => (int) $itemId, 'wid' => $wishlistId]);
            $pdo->commit();
            return ['ok' => true, 'state' => false, 'message' => 'Đã xóa sản phẩm khỏi danh sách yêu thích.'];
        }
        if ((int) $productRow['is_active'] !== 1) { $pdo->rollBack(); return ['ok' => false, 'reason' => 'unavailable']; }
        $insert = $pdo->prepare('INSERT IGNORE INTO wishlist_items (wishlist_id, product_id) VALUES (:wid, :pid)');
        $insert->execute(['wid' => $wishlistId, 'pid' => $productId]);
        $pdo->commit();
        return ['ok' => true, 'state' => true, 'message' => 'Đã thêm sản phẩm vào danh sách yêu thích.'];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        return ['ok' => false, 'reason' => 'database'];
    }
}

function wishlist_remove_for_user(int $userId, int $productId): bool
{
    if ($userId <= 0 || $productId <= 0) return false;
    try {
        $stmt = db()->prepare('DELETE wi FROM wishlist_items wi INNER JOIN wishlists w ON w.id = wi.wishlist_id WHERE w.user_id = :uid AND wi.product_id = :pid');
        $stmt->execute(['uid' => $userId, 'pid' => $productId]);
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) { return false; }
}

function account_orders_for_user(int $userId, int $limit = 0): array
{
    if ($userId <= 0) return [];
    $limitSql = $limit > 0 ? ' LIMIT ' . min(20, max(1, $limit)) : '';
    try {
        $stmt = db()->prepare('SELECT o.*, (SELECT p.method FROM payments p WHERE p.order_id = o.id ORDER BY p.id DESC LIMIT 1) AS payment_method FROM orders o WHERE o.user_id = :uid ORDER BY o.created_at DESC, o.id DESC' . $limitSql);
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll();
    } catch (PDOException $e) { return []; }
}

function account_order_summary(int $userId): array
{
    $summary = ['total' => 0, 'pending' => 0, 'delivered' => 0];
    if ($userId <= 0) return $summary;
    try {
        $stmt = db()->prepare("SELECT COUNT(*) AS total, COALESCE(SUM(status = 'pending'), 0) AS pending, COALESCE(SUM(status = 'delivered'), 0) AS delivered FROM orders WHERE user_id = :uid");
        $stmt->execute(['uid' => $userId]);
        $row = $stmt->fetch() ?: [];
        foreach ($summary as $key => $_) $summary[$key] = (int) ($row[$key] ?? 0);
    } catch (PDOException $e) { }
    return $summary;
}
function account_order_for_user(int $userId, string $orderNumber): ?array
{
    if ($userId <= 0 || trim($orderNumber) === '') return null;
    try {
        $stmt = db()->prepare('SELECT * FROM orders WHERE order_number = :number AND user_id = :uid LIMIT 1');
        $stmt->execute(['number' => trim($orderNumber), 'uid' => $userId]);
        $order = $stmt->fetch();
        if (!$order) return null;
        $payment = db()->prepare('SELECT method FROM payments WHERE order_id = :oid ORDER BY id DESC LIMIT 1');
        $payment->execute(['oid' => (int) $order['id']]);
        $order['payment_method'] = (string) ($payment->fetchColumn() ?: '');
        return $order;
    } catch (PDOException $e) { return null; }
}

function account_order_status_label(string $status): string
{
    return ['pending' => 'Chờ xác nhận', 'confirmed' => 'Đã xác nhận', 'processing' => 'Đang xử lý', 'shipped' => 'Đang giao', 'delivered' => 'Đã giao', 'cancelled' => 'Đã hủy'][$status] ?? 'Đang cập nhật';
}
function account_payment_method_label(string $method): string
{
    return ['cod' => 'Thanh toán khi nhận hàng (COD)', 'bank_transfer' => 'Chuyển khoản ngân hàng'][$method] ?? ($method !== '' ? $method : 'Chưa cập nhật');
}
function account_order_payment_status_label(string $status): string
{
    return ['unpaid' => 'Chưa thanh toán', 'paid' => 'Đã thanh toán', 'failed' => 'Thanh toán thất bại', 'refunded' => 'Đã hoàn tiền'][$status] ?? 'Đang cập nhật';
}