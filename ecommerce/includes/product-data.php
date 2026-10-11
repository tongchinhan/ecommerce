<?php
/**
 * Product detail data layer.
 *
 * Centralized database access for product.php (and any future product
 * detail surface). Mirrors the defensive style of shop-data.php:
 * - Uses the shared PDO singleton (db()).
 * - Prepared statements with parameter binding only.
 * - Errors return empty arrays / zero counts.
 *
 * Tables queried: products, categories, brands, product_images, reviews.
 */
declare(strict_types=1);

require_once __DIR__ . '/storefront-helpers.php';

/**
 * Load a product by id (must be active).
 *
 * Returns the full row joined with its category, brand, and primary image.
 *
 * @return array<string,mixed>|null
 */
function product_find_by_id(int $id): ?array
{
    if ($id <= 0) {
        return null;
    }
    try {
        $stmt = db()->prepare(
            'SELECT p.id, p.slug, p.name, p.sku, p.short_description, p.description,
                    p.price, p.compare_price, p.stock, p.is_featured, p.is_active,
                    p.weight, p.created_at, p.updated_at, p.category_id, p.brand_id,
                    c.name AS category_name, c.slug AS category_slug,
                    b.name AS brand_name,
                    pi.image_path AS primary_image, pi.alt_text AS primary_alt
             FROM products p
             INNER JOIN categories c ON c.id = p.category_id
             LEFT JOIN brands b ON b.id = p.brand_id
             LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_primary = 1
             WHERE p.id = :id AND p.is_active = 1 LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    } catch (PDOException $e) {
        return null;
    }
}

/**
 * Load all images for a product, ordered by sort_order then id.
 *
 * @return array<int,array<string,mixed>>
 */
function product_images(int $productId): array
{
    if ($productId <= 0) {
        return [];
    }
    try {
        $stmt = db()->prepare(
            'SELECT id, product_id, image_path, alt_text, is_primary, sort_order
             FROM product_images
             WHERE product_id = :pid
             ORDER BY is_primary DESC, sort_order ASC, id ASC'
        );
        $stmt->execute(['pid' => $productId]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Aggregate rating summary for a product.
 *
 * @return array{average: float, count: int}
 */
function product_review_summary(int $productId): array
{
    if ($productId <= 0) {
        return ['average' => 0.0, 'count' => 0];
    }
    try {
        $stmt = db()->prepare(
            'SELECT COALESCE(AVG(rating), 0) AS avg_rating, COUNT(*) AS total
             FROM reviews
             WHERE product_id = :pid AND is_approved = 1'
        );
        $stmt->execute(['pid' => $productId]);
        $row = $stmt->fetch();
        if (!$row) {
            return ['average' => 0.0, 'count' => 0];
        }
        return [
            'average' => (float) $row['avg_rating'],
            'count'   => (int) $row['total'],
        ];
    } catch (PDOException $e) {
        return ['average' => 0.0, 'count' => 0];
    }
}

/**
 * Stock state label and class hint.
 *
 * Returns one of:
 *   ['key' => 'in_stock',   'label' => 'Còn hàng',           'hint' => 'Còn {n} sản phẩm']
 *   ['key' => 'low_stock',  'label' => 'Sắp hết',            'hint' => 'Chỉ còn {n} sản phẩm']
 *   ['key' => 'out_stock',  'label' => 'Hết hàng',           'hint' => 'Sản phẩm tạm hết']
 *
 * @return array{key:string,label:string,hint:string,stock:int}
 */
function product_stock_state(int $stock): array
{
    if ($stock <= 0) {
        return [
            'key'   => 'out_stock',
            'label' => 'Hết hàng',
            'hint'  => 'Sản phẩm hiện đang tạm hết hàng',
            'stock' => 0,
        ];
    }
    if ($stock <= 5) {
        return [
            'key'   => 'low_stock',
            'label' => 'Sắp hết',
            'hint'  => 'Chỉ còn ' . $stock . ' sản phẩm',
            'stock' => $stock,
        ];
    }
    return [
        'key'   => 'in_stock',
        'label' => 'Còn hàng',
        'hint'  => 'Còn ' . $stock . ' sản phẩm — giao hàng toàn quốc',
        'stock' => $stock,
    ];
}

/**
 * Build the gallery image list for the product detail page.
 *
 * Resolves each DB row through storefront_image() and falls back to the
 * placeholder when no file exists on disk. If the product has no images
 * at all, returns a single placeholder entry so the gallery still has a
 * main image.
 *
 * @return array<int, array{url:string, alt:string, is_primary:bool}>
 */
function product_gallery(array $product): array
{
    $rows = product_images((int) ($product['id'] ?? 0));
    $tone = storefront_category_tone((int) ($product['category_id'] ?? 1));
    $fallback = storefront_placeholder_svg((string) ($product['name'] ?? 'Sản phẩm'), $tone);
    $name = (string) ($product['name'] ?? 'Sản phẩm');

    $out = [];
    if (!empty($rows)) {
        foreach ($rows as $r) {
            $url = storefront_image((string) ($r['image_path'] ?? ''));
            $out[] = [
                'url'        => $url ?? $fallback,
                'alt'        => (string) ($r['alt_text'] ?? $name),
                'is_primary' => (int) ($r['is_primary'] ?? 0) === 1,
            ];
        }
    }

    if (empty($out)) {
        $out[] = [
            'url'        => $fallback,
            'alt'        => $name,
            'is_primary' => true,
        ];
    }

    return $out;
}

/**
 * Related products (same category, excluding the current id).
 *
 * Reuses shop_query_products so the same JOIN, ORDER BY whitelist and
 * performance guarantees apply.
 *
 * @return array<int,array<string,mixed>>
 */
function product_related(int $categoryId, int $excludeId, int $limit = 4): array
{
    if ($categoryId <= 0) {
        return [];
    }
    $rows = shop_query_products([
        'category_id' => $categoryId,
        'sort'        => 'default',
        'limit'       => max(1, min(8, $limit)),
        'offset'      => 0,
    ]);
    $filtered = [];
    foreach ($rows as $r) {
        if ((int) $r['id'] !== $excludeId) {
            $filtered[] = $r;
        }
        if (count($filtered) >= $limit) {
            break;
        }
    }
    return $filtered;
}