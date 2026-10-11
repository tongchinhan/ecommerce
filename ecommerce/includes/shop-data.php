<?php
/**
 * Shop data layer.
 *
 * Centralized, defensive database access for the storefront shop system.
 * - All queries use the existing PDO singleton (db()).
 * - All inputs are validated, prepared statements used everywhere.
 * - All sort/order expressions are derived from a whitelist (never raw user input).
 * - Errors return empty arrays / zero counts — never throw to the caller.
 */
declare(strict_types=1);

require_once __DIR__ . '/storefront-helpers.php';

/**
 * Whitelist of sort options mapped to safe ORDER BY clauses.
 * Each value is appended after a fixed base table alias.
 */
function shop_allowed_sorts(): array
{
    return [
        'default'    => 'p.id DESC',
        'bestsellers' => 'sales.units_sold DESC, p.created_at DESC, p.id DESC',
        'newest'     => 'p.created_at DESC, p.id DESC',
        'price_asc'  => 'p.price ASC, p.id DESC',
        'price_desc' => 'p.price DESC, p.id DESC',
        'name_asc'   => 'p.name ASC, p.id DESC',
        'name_desc'  => 'p.name DESC, p.id DESC',
    ];
}

/**
 * Resolve and validate a sort key from arbitrary input.
 */
function shop_resolve_sort(?string $raw): string
{
    $map = shop_allowed_sorts();
    $key = is_string($raw) ? trim($raw) : '';
    return isset($map[$key]) ? $key : 'default';
}

/**
 * Resolve and validate the active category.
 * Returns null when no category filter is active or when the value is invalid.
 *
 * @return array{id:int,name:string,slug:string}|null
 */
function shop_resolve_category(?string $slugRaw, ?string $idRaw): ?array
{
    $idRaw = is_string($idRaw) ? trim($idRaw) : '';
    $slugRaw = is_string($slugRaw) ? trim($slugRaw) : '';

    if ($idRaw !== '' && ctype_digit($idRaw)) {
        try {
            $stmt = db()->prepare('SELECT id, name, slug FROM categories WHERE id = :id AND is_active = 1 LIMIT 1');
            $stmt->execute(['id' => (int) $idRaw]);
            $row = $stmt->fetch();
            if ($row) {
                return ['id' => (int) $row['id'], 'name' => (string) $row['name'], 'slug' => (string) $row['slug']];
            }
        } catch (PDOException $e) {
            return null;
        }
    }

    if ($slugRaw !== '') {
        try {
            $stmt = db()->prepare('SELECT id, name, slug FROM categories WHERE slug = :slug AND is_active = 1 LIMIT 1');
            $stmt->execute(['slug' => $slugRaw]);
            $row = $stmt->fetch();
            if ($row) {
                return ['id' => (int) $row['id'], 'name' => (string) $row['name'], 'slug' => (string) $row['slug']];
            }
        } catch (PDOException $e) {
            return null;
        }
    }

    return null;
}

/**
 * Normalize price filter inputs.
 * Negative or non-numeric values become null.
 *
 * @return array{min:float|null,max:float|null}
 */
function shop_resolve_price_filter(?string $minRaw, ?string $maxRaw): array
{
    $min = null;
    $max = null;

    if (is_string($minRaw) && $minRaw !== '') {
        if (is_numeric($minRaw)) {
            $v = (float) $minRaw;
            if ($v >= 0) {
                $min = $v;
            }
        }
    }
    if (is_string($maxRaw) && $maxRaw !== '') {
        if (is_numeric($maxRaw)) {
            $v = (float) $maxRaw;
            if ($v >= 0) {
                $max = $v;
            }
        }
    }

    return ['min' => $min, 'max' => $max];
}

/**
 * Normalize search query. Returns trimmed string, empty when too short.
 */
function shop_resolve_query(?string $raw): string
{
    if (!is_string($raw)) {
        return '';
    }
    $q = trim($raw);
    if (mb_strlen($q, 'UTF-8') < 2) {
        return '';
    }
    return mb_substr($q, 0, 100, 'UTF-8');
}

/**
 * Normalize page number. Defaults to 1, minimum 1.
 */
function shop_resolve_page(?string $raw): int
{
    if (!is_string($raw) || $raw === '') {
        return 1;
    }
    if (!ctype_digit($raw)) {
        return 1;
    }
    return max(1, (int) $raw);
}

/**
 * Resolve per-page count. Capped to a sensible range.
 */
function shop_resolve_per_page(): int
{
    return 12;
}

/**
 * Query product rows for the shop listing, applying all filters.
 *
 * @param array $args {
 *   @type string      $query       Search text (matches name, short_description, description, sku, category_name)
 *   @type int|null    $category_id Category id filter
 *   @type float|null  $min_price
 *   @type float|null  $max_price
 *   @type string      $sort        One of shop_allowed_sorts() keys
 *   @type int         $limit
 *   @type int         $offset
 * }
 *
 * @return array<int,array<string,mixed>>
 */
function shop_query_products(array $args): array
{
    $where = ['p.is_active = 1'];
    $params = [];

    if (!empty($args['category_id'])) {
        $where[] = 'p.category_id = :category_id';
        $params['category_id'] = (int) $args['category_id'];
    }

    if (isset($args['min_price']) && $args['min_price'] !== null) {
        $where[] = 'p.price >= :min_price';
        $params['min_price'] = (float) $args['min_price'];
    }
    if (isset($args['max_price']) && $args['max_price'] !== null) {
        $where[] = 'p.price <= :max_price';
        $params['max_price'] = (float) $args['max_price'];
    }

    $query = isset($args['query']) ? trim((string) $args['query']) : '';
    if ($query !== '' && mb_strlen($query, 'UTF-8') >= 2) {
        $where[] = '('
            . 'p.name LIKE :q_name '
            . 'OR COALESCE(p.short_description, \'\') LIKE :q_short '
            . 'OR COALESCE(p.description, \'\') LIKE :q_desc '
            . 'OR COALESCE(p.sku, \'\') LIKE :q_sku '
            . 'OR COALESCE(c.name, \'\') LIKE :q_cat'
            . ')';
        $like = '%' . $query . '%';
        $params['q_name']  = $like;
        $params['q_short'] = $like;
        $params['q_desc']  = $like;
        $params['q_sku']   = $like;
        $params['q_cat']   = $like;
    }

    $sortKey = shop_resolve_sort($args['sort'] ?? 'default');
    $orderBy = shop_allowed_sorts()[$sortKey];
    $salesJoin = '';
    $salesSelect = '';
    if ($sortKey === 'bestsellers') {
        $salesJoin = " LEFT JOIN (
            SELECT oi.product_id, SUM(oi.quantity) AS units_sold
            FROM order_items oi
            INNER JOIN orders o ON o.id = oi.order_id
            WHERE o.status IN ('confirmed', 'processing', 'shipped', 'delivered')
            GROUP BY oi.product_id
        ) sales ON sales.product_id = p.id ";
        $salesSelect = ', COALESCE(sales.units_sold, 0) AS units_sold';
    }

    $limit = isset($args['limit']) ? max(1, min(48, (int) $args['limit'])) : 12;
    $offset = isset($args['offset']) ? max(0, (int) $args['offset']) : 0;

    $sql = 'SELECT p.id, p.slug, p.name, p.sku, p.short_description, p.description, '
         . 'p.price, p.compare_price, p.stock, p.is_featured, p.is_active, '
         . 'p.created_at, p.category_id, '
         . 'c.name AS category_name, c.slug AS category_slug, '
         . 'b.name AS brand_name, '
         . 'pi.image_path, pi.alt_text AS image_alt' . $salesSelect . ' '
         . 'FROM products p '
         . 'INNER JOIN categories c ON c.id = p.category_id '
         . 'LEFT JOIN brands b ON b.id = p.brand_id '
         . 'LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_primary = 1 '
         . $salesJoin
         . 'WHERE ' . implode(' AND ', $where) . ' '
         . 'ORDER BY ' . $orderBy . ' '
         . 'LIMIT ' . $limit . ' OFFSET ' . $offset;

    try {
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Count matching rows for a filter set (used for total / pagination).
 */
function shop_count_products(array $args): int
{
    $where = ['p.is_active = 1'];
    $params = [];

    if (!empty($args['category_id'])) {
        $where[] = 'p.category_id = :category_id';
        $params['category_id'] = (int) $args['category_id'];
    }
    if (isset($args['min_price']) && $args['min_price'] !== null) {
        $where[] = 'p.price >= :min_price';
        $params['min_price'] = (float) $args['min_price'];
    }
    if (isset($args['max_price']) && $args['max_price'] !== null) {
        $where[] = 'p.price <= :max_price';
        $params['max_price'] = (float) $args['max_price'];
    }
    $query = isset($args['query']) ? trim((string) $args['query']) : '';
    if ($query !== '' && mb_strlen($query, 'UTF-8') >= 2) {
        $where[] = '('
            . 'p.name LIKE :q_name '
            . 'OR COALESCE(p.short_description, \'\') LIKE :q_short '
            . 'OR COALESCE(p.description, \'\') LIKE :q_desc '
            . 'OR COALESCE(p.sku, \'\') LIKE :q_sku '
            . 'OR COALESCE(c.name, \'\') LIKE :q_cat'
            . ')';
        $like = '%' . $query . '%';
        $params['q_name']  = $like;
        $params['q_short'] = $like;
        $params['q_desc']  = $like;
        $params['q_sku']   = $like;
        $params['q_cat']   = $like;
    }

    $sql = 'SELECT COUNT(*) FROM products p '
         . 'INNER JOIN categories c ON c.id = p.category_id '
         . 'WHERE ' . implode(' AND ', $where);

    try {
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}

/**
 * Load all active top-level categories for the filter UI.
 *
 * @return array<int,array<string,mixed>>
 */
function shop_active_categories(): array
{
    try {
        $stmt = db()->query(
            'SELECT id, name, slug, description, image
             FROM categories
             WHERE is_active = 1 AND parent_id IS NULL
             ORDER BY sort_order ASC, name ASC'
        );
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Find a product by id (used by /search and link building).
 */
function shop_product_by_id(int $id): ?array
{
    try {
        $stmt = db()->prepare('SELECT id, slug FROM products WHERE id = :id AND is_active = 1 LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    } catch (PDOException $e) {
        return null;
    }
}

/**
 * Vietnamese labels for sort options (UI).
 */
function shop_sort_labels(): array
{
    return [
        'default'    => 'Mặc định',
        'bestsellers' => 'Bán chạy',
        'newest'     => 'Mới nhất',
        'price_asc'  => 'Giá thấp đến cao',
        'price_desc' => 'Giá cao đến thấp',
        'name_asc'   => 'Tên A–Z',
        'name_desc'  => 'Tên Z–A',
    ];
}

/**
 * Vietnamese price range buckets, adapted to the actual data range.
 *
 * @return array<int,array{label:string,min:float|null,max:float|null}>
 */
function shop_price_buckets(): array
{
    return [
        ['label' => 'Tất cả mức giá',         'min' => null, 'max' => null],
        ['label' => 'Dưới 2.000.000 ₫',      'min' => 0,     'max' => 2000000],
        ['label' => '2.000.000 – 5.000.000 ₫', 'min' => 2000000, 'max' => 5000000],
        ['label' => '5.000.000 – 10.000.000 ₫','min' => 5000000, 'max' => 10000000],
        ['label' => 'Trên 10.000.000 ₫',       'min' => 10000000, 'max' => null],
    ];
}

/**
 * Build a shop URL preserving existing filters and overriding supplied keys.
 *
 * @param array $overrides
 */
function shop_url(array $overrides = []): string
{
    $base = $_GET;
    foreach ($overrides as $k => $v) {
        if ($v === null || $v === '') {
            unset($base[$k]);
        } else {
            $base[$k] = $v;
        }
    }
    return url('products.php' . ($base ? '?' . http_build_query($base) : ''));
}

/**
 * Build a clean pagination URL (resets page to whatever is given).
 */
function shop_pagination_url(int $page): string
{
    return shop_url(['page' => $page]);
}
