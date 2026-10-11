<?php
/**
 * Storefront helper functions: graceful image fallback,
 * formatted numbers, category colors, and discount math.
 *
 * All helpers are defensive — they return safe defaults if data is missing.
 */
declare(strict_types=1);

require_once __DIR__ . '/icons.php';

/**
 * Generate a graceful image URL for a product/post/banner.
 *
 * Path is whatever the database stored (relative path inside uploads/, etc).
 * If the local file does not exist, we return null so the caller can render
 * a CSS-driven placeholder.
 */
function storefront_image(?string $path): ?string
{
    if ($path === null || $path === '') {
        return null;
    }

    // Already an absolute URL.
    if (preg_match('#^(https?:)?//#i', $path)) {
        return $path;
    }

    // Strip leading slashes then map to UPLOAD_URL.
    $relative = ltrim($path, '/');

    $absoluteFs = UPLOAD_PATH . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
    if (is_file($absoluteFs)) {
        return UPLOAD_URL . '/' . $relative;
    }

    return null;
}

/**
 * Format a price like 18.990.000 ₫
 */
function storefront_price($amount): string
{
    return format_vnd($amount);
}

/**
 * Compute the discount percentage between two prices.
 * Returns an integer between 1 and 99, or 0 when not a discount.
 */
function storefront_discount_percent($price, $comparePrice): int
{
    $price = (float) $price;
    $compare = (float) $comparePrice;
    if ($compare <= 0 || $compare <= $price) {
        return 0;
    }
    $pct = (int) round((($compare - $price) / $compare) * 100);
    return max(1, min(99, $pct));
}

/**
 * Render an inline product placeholder SVG when no image is available.
 * Uses the product's first letter for a soft, brand-aligned chip.
 */
function storefront_placeholder_svg(string $label, string $tone = 'paper'): string
{
    $label = strtoupper(substr(trim($label), 0, 1) ?: '·');
    $tones = [
        'paper' => ['#f1e9d8', '#8c7e63'],
        'warm'  => ['#e9dcc4', '#8c4a2f'],
        'cool'  => ['#dde2e0', '#3f5249'],
        'stone' => ['#e6dfd3', '#6f675c'],
    ];
    [$bg, $fg] = $tones[$tone] ?? $tones['paper'];

    $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 400" role="img" aria-label="Placeholder image">';
    $svg .= '<rect width="400" height="400" fill="' . $bg . '"/>';
    $svg .= '<circle cx="200" cy="180" r="78" fill="none" stroke="' . $fg . '" stroke-width="1.5" opacity="0.45"/>';
    $svg .= '<text x="50%" y="58%" text-anchor="middle" font-family="Cormorant Garamond, Georgia, serif" font-size="120" fill="' . $fg . '" opacity="0.55">' . e($label) . '</text>';
    $svg .= '</svg>';
    return 'data:image/svg+xml;utf8,' . rawurlencode($svg);
}

/**
 * Pick a placeholder tone for a category based on its id (stable rotation).
 */
function storefront_category_tone(int $id): string
{
    return match ($id % 4) {
        0 => 'paper',
        1 => 'warm',
        2 => 'cool',
        default => 'stone',
    };
}

/**
 * Resolve primary image for a product row joined from product_images.
 */
function storefront_product_image(array $product): ?string
{
    if (!empty($product['image_path'])) {
        $url = storefront_image($product['image_path']);
        if ($url !== null) {
            return $url;
        }
    }

    // Fall back to the legacy `image` column on categories (not products).
    return null;
}

/**
 * Build a product detail link.
 *
 * Uses the product's id so product.php can resolve by id OR slug.
 * This matches the spec for shop/category/search cards.
 */
function storefront_product_link(array $product): string
{
    $id = (int) ($product['id'] ?? 0);
    return url('product.php?id=' . $id);
}

/**
 * Build a category link.
 * Uses the unified shop URL with the category slug as the filter.
 */
function storefront_category_link(array $category): string
{
    return url('products.php?category=' . urlencode((string) ($category['slug'] ?? '')));
}

/**
 * Build a post link.
 */
function storefront_post_link(array $post): string
{
    return url('post.php?slug=' . urlencode((string) ($post['slug'] ?? '')));
}

/**
 * Truncate a string to a soft word boundary.
 */
function storefront_excerpt(?string $text, int $max = 110): string
{
    if ($text === null) {
        return '';
    }
    $text = trim($text);
    if ($text === '') {
        return '';
    }
    if (mb_strlen($text, 'UTF-8') <= $max) {
        return $text;
    }
    $cut = mb_substr($text, 0, $max, 'UTF-8');
    $last = mb_strrpos($cut, ' ', 0, 'UTF-8');
    if ($last !== false) {
        $cut = mb_substr($cut, 0, $last, 'UTF-8');
    }
    return rtrim($cut, ' ,.;:') . '…';
}
