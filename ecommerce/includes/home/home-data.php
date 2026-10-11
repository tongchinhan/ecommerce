<?php
/**
 * Homepage data loader.
 *
 * Centralizes all DB queries used by the storefront homepage.
 * Returns associative arrays ready for the section partials.
 *
 * All queries use the existing PDO singleton (db()) and prepared statements
 * where parameters are bound. They are tolerant to missing data and
 * never throw — empty arrays are returned on failure.
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/storefront-helpers.php';

/**
 * Count products in a category, returns 0 on any failure.
 */
function hp_category_product_count(int $categoryId): int
{
    static $cache = [];
    if (isset($cache[$categoryId])) {
        return $cache[$categoryId];
    }
    try {
        $stmt = db()->prepare('SELECT COUNT(*) FROM products WHERE category_id = :cid AND is_active = 1');
        $stmt->execute(['cid' => $categoryId]);
        $cache[$categoryId] = (int) $stmt->fetchColumn();
    } catch (PDOException $e) {
        $cache[$categoryId] = 0;
    }
    return $cache[$categoryId];
}

/**
 * Featured categories with active=1.
 * Returns up to $limit rows.
 */
function hp_featured_categories(int $limit = 6): array
{
    try {
        $stmt = db()->query(
            'SELECT id, parent_id, name, slug, description, image, sort_order, is_active
             FROM categories
             WHERE is_active = 1 AND parent_id IS NULL
             ORDER BY sort_order ASC, name ASC
             LIMIT ' . $limit
        );
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Fetch a list of products joined with primary image and category.
 *
 * @param string $mode  one of: featured, bestsellers, newest, related
 * @param int    $limit
 */
function hp_products(string $mode = 'featured', int $limit = 8): array
{
    $limit = max(1, min(24, $limit));
    $salesJoin = '';
    $salesSelect = '0 AS units_sold';
    $whereExtra = '';
    $orderBy = 'p.id DESC';

    if ($mode === 'featured') {
        $whereExtra = ' AND p.is_featured = 1';
        $orderBy = 'p.id ASC';
    } elseif ($mode === 'newest') {
        $orderBy = 'p.created_at DESC, p.id DESC';
    } elseif ($mode === 'bestsellers') {
        // Count only accepted/fulfilled sales. Pending and cancelled orders
        // are not evidence that a product has sold.
        $salesJoin = " LEFT JOIN (
            SELECT oi.product_id, SUM(oi.quantity) AS units_sold
            FROM order_items oi
            INNER JOIN orders o ON o.id = oi.order_id
            WHERE o.status IN ('confirmed', 'processing', 'shipped', 'delivered')
            GROUP BY oi.product_id
        ) sales ON sales.product_id = p.id";
        $salesSelect = 'COALESCE(sales.units_sold, 0) AS units_sold';
        $whereExtra = ' AND COALESCE(sales.units_sold, 0) > 0';
        $orderBy = 'sales.units_sold DESC, p.created_at DESC, p.id DESC';
    } elseif ($mode === 'related') {
        $orderBy = 'p.id DESC';
    }

    $sql = 'SELECT p.id, p.slug, p.name, p.short_description, p.price, p.compare_price,
                   p.stock, p.is_featured, p.created_at, ' . $salesSelect . ',
                   p.category_id, c.name AS category_name, c.slug AS category_slug,
                   pi.image_path
            FROM products p
            INNER JOIN categories c ON c.id = p.category_id
            LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_primary = 1'
            . $salesJoin . '
            WHERE p.is_active = 1' . $whereExtra . '
            ORDER BY ' . $orderBy . ' LIMIT ' . $limit;

    try {
        return db()->query($sql)->fetchAll() ?: [];
    } catch (PDOException $e) {
        return [];
    }
}
/**
 * Most recent published posts.
 */
function hp_recent_posts(int $limit = 3): array
{
    try {
        $stmt = db()->prepare(
            'SELECT p.id, p.title, p.slug, p.excerpt, p.thumbnail, p.published_at,
                    p.created_at, pc.name AS category_name, pc.slug AS category_slug
             FROM posts p
             LEFT JOIN post_categories pc ON pc.id = p.post_category_id
             WHERE p.is_published = 1
             ORDER BY COALESCE(p.published_at, p.created_at) DESC, p.id DESC
             LIMIT ' . $limit
        );
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * All key/value settings as an associative array.
 */
function hp_settings(): array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    try {
        $rows = db()->query('SELECT setting_key, setting_value FROM settings')->fetchAll();
        $cache = [];
        foreach ($rows as $row) {
            $cache[$row['setting_key']] = $row['setting_value'];
        }
    } catch (PDOException $e) {
        $cache = [];
    }
    return $cache;
}

/**
 * Get a single setting with a default fallback.
 */
function hp_setting(string $key, string $default = ''): string
{
    $settings = hp_settings();
    return isset($settings[$key]) ? (string) $settings[$key] : $default;
}

/**
 * Fetch active home_hero banner (if any).
 */
function hp_hero_banner(): ?array
{
    try {
        $stmt = db()->query(
            "SELECT * FROM banners
             WHERE position = 'home_hero' AND is_active = 1
             ORDER BY sort_order ASC, id ASC
             LIMIT 1"
        );
        $row = $stmt->fetch();
        return $row ?: null;
    } catch (PDOException $e) {
        return null;
    }
}

/**
 * Fetch active home_promo banner (if any).
 */
function hp_promo_banner(): ?array
{
    try {
        $stmt = db()->query(
            "SELECT * FROM banners
             WHERE position = 'home_promo' AND is_active = 1
             ORDER BY sort_order ASC, id ASC
             LIMIT 1"
        );
        $row = $stmt->fetch();
        return $row ?: null;
    } catch (PDOException $e) {
        return null;
    }
}

/**
 * Homepage metrics from the active product catalog and approved reviews.
 *
 * @return array{active_products:int,average_rating:?float,approved_reviews:int}
 */
function hp_storefront_metrics(): array
{
    static $metrics = null;
    if ($metrics !== null) {
        return $metrics;
    }
    $metrics = ['active_products' => 0, 'average_rating' => null, 'approved_reviews' => 0];
    try {
        $metrics['active_products'] = (int) db()->query('SELECT COUNT(*) FROM products WHERE is_active = 1')->fetchColumn();
        $reviewStats = db()->query('SELECT COUNT(*) AS review_count, AVG(rating) AS average_rating FROM reviews WHERE is_approved = 1')->fetch();
        $metrics['approved_reviews'] = (int) ($reviewStats['review_count'] ?? 0);
        $metrics['average_rating'] = $metrics['approved_reviews'] > 0 ? (float) $reviewStats['average_rating'] : null;
    } catch (PDOException $exception) {
        // Keep neutral values if optional metrics cannot be read.
    }
    return $metrics;
}