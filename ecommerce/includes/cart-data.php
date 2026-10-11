<?php
/**
 * Cart data layer.
 *
 * Centralized database access for the shopping cart.
 * - All queries use the existing PDO singleton (db()).
 * - All inputs are validated; prepared statements everywhere.
 * - Errors return safe defaults — never throw to the caller.
 *
 * Cart model:
 *   - `carts` is the owner container; identified by user_id (logged-in) or
 *     session_id (guest). Schema supports both.
 *   - `cart_items` joins product + quantity to a cart, with UNIQUE on
 *     (cart_id, product_id) so adding the same product twice increments
 *     the existing row instead of inserting a duplicate.
 *
 * The session id is a stable per-visit identifier persisted via PHP's
 * session (already started by the bootstrap). It is also written into the
 * `session_id` column of the cart row so that a guest cart survives across
 * requests as long as the browser keeps the session cookie.
 */
declare(strict_types=1);

require_once __DIR__ . '/functions.php';

/**
 * Return the current session id (the cart owner key for guests).
 *
 * Falls back to a synthesized id when the session has not been started
 * yet (defensive — bootstrap.php normally starts the session before this
 * file is loaded, but we do not want to crash if called out of order).
 */
function cart_session_key(): string
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        $id = session_id();
        if (is_string($id) && $id !== '') {
            return substr($id, 0, 128);
        }
    }
    return 'cli-' . md5((string) __FILE__);
}

/**
 * Find an existing cart row for the current visitor.
 *
 * Resolution order:
 *   1. user_id (if logged in)
 *   2. session_id (guest)
 *
 * @return array<string,mixed>|null
 */
function cart_find_for_visitor(): ?array
{
    $userId = is_logged_in() ? (int) (current_user()['id'] ?? 0) : 0;
    $sessId = cart_session_key();

    try {
        if ($userId > 0) {
            $stmt = db()->prepare(
                'SELECT id, user_id, session_id FROM carts
                 WHERE user_id = :uid LIMIT 1'
            );
            $stmt->execute(['uid' => $userId]);
            $row = $stmt->fetch();
            if ($row) {
                return $row;
            }
        }

        if ($sessId !== '') {
            $stmt = db()->prepare(
                'SELECT id, user_id, session_id FROM carts
                 WHERE user_id IS NULL AND session_id = :sid LIMIT 1'
            );
            $stmt->execute(['sid' => $sessId]);
            $row = $stmt->fetch();
            if ($row) {
                return $row;
            }
        }
    } catch (PDOException $e) {
        return null;
    }

    return null;
}

/**
 * Create a cart row for the current visitor and return it.
 */
function cart_create_for_visitor(): ?array
{
    $userId = is_logged_in() ? (int) (current_user()['id'] ?? 0) : 0;
    $sessId = cart_session_key();

    try {
        $stmt = db()->prepare(
            'INSERT INTO carts (user_id, session_id) VALUES (:uid, :sid)'
        );
        $stmt->execute([
            'uid' => $userId > 0 ? $userId : null,
            'sid' => $userId > 0 ? null : ($sessId !== '' ? $sessId : null),
        ]);
        $id = (int) db()->lastInsertId();
        return ['id' => $id, 'user_id' => $userId > 0 ? $userId : null, 'session_id' => $userId > 0 ? null : $sessId];
    } catch (PDOException $e) {
        return null;
    }
}

/**
 * Get-or-create the cart row for the current visitor.
 */
function cart_get_or_create(): ?array
{
    $row = cart_find_for_visitor();
    if ($row !== null) {
        return $row;
    }
    return cart_create_for_visitor();
}

/**
 * Add a product to the cart or increment its quantity if already present.
 *
 * - $productId must be a valid active product.
 * - $quantity is clamped to [1, max(stock, 9999)].
 *
 * Returns:
 *   ['ok' => true,  'cart_id' => int, 'item_id' => int, 'quantity' => int]
 *   ['ok' => false, 'reason' => string]
 */
function cart_add_item(int $productId, int $quantity = 1): array
{
    if ($productId <= 0) {
        return ['ok' => false, 'reason' => 'invalid_product'];
    }

    // Validate product is active and read its current stock.
    try {
        $stmt = db()->prepare('SELECT id, stock FROM products WHERE id = :id AND is_active = 1 LIMIT 1');
        $stmt->execute(['id' => $productId]);
        $product = $stmt->fetch();
        if (!$product) {
            return ['ok' => false, 'reason' => 'product_not_found'];
        }
        $stock = (int) ($product['stock'] ?? 0);
    } catch (PDOException $e) {
        return ['ok' => false, 'reason' => 'database_error'];
    }

    $cart = cart_get_or_create();
    if ($cart === null) {
        return ['ok' => false, 'reason' => 'cart_unavailable'];
    }

    // Clamp quantity. Stock 0 means out of stock — still reject the add.
    $q = max(1, $quantity);
    if ($stock <= 0) {
        return ['ok' => false, 'reason' => 'out_of_stock'];
    }
    $q = min($q, $stock);

    try {
        // Check existing line.
        $stmt = db()->prepare(
            'SELECT id, quantity FROM cart_items
             WHERE cart_id = :cid AND product_id = :pid LIMIT 1'
        );
        $stmt->execute([
            'cid' => (int) $cart['id'],
            'pid' => $productId,
        ]);
        $existing = $stmt->fetch();

        if ($existing) {
            $newQty = min($stock, (int) $existing['quantity'] + $q);
            $up = db()->prepare('UPDATE cart_items SET quantity = :q, updated_at = NOW() WHERE id = :id');
            $up->execute(['q' => $newQty, 'id' => (int) $existing['id']]);
            return ['ok' => true, 'cart_id' => (int) $cart['id'], 'item_id' => (int) $existing['id'], 'quantity' => $newQty];
        }

        $ins = db()->prepare(
            'INSERT INTO cart_items (cart_id, product_id, quantity) VALUES (:cid, :pid, :q)'
        );
        $ins->execute([
            'cid' => (int) $cart['id'],
            'pid' => $productId,
            'q'   => $q,
        ]);
        return ['ok' => true, 'cart_id' => (int) $cart['id'], 'item_id' => (int) db()->lastInsertId(), 'quantity' => $q];
    } catch (PDOException $e) {
        return ['ok' => false, 'reason' => 'database_error'];
    }
}

/**
 * Total number of items (sum of quantities) in the visitor's cart.
 *
 * Returns 0 when there is no cart yet.
 */
function cart_count(): int
{
    $cart = cart_find_for_visitor();
    if ($cart === null) {
        return 0;
    }
    try {
        $stmt = db()->prepare('SELECT COALESCE(SUM(quantity), 0) FROM cart_items WHERE cart_id = :cid');
        $stmt->execute(['cid' => (int) $cart['id']]);
        return (int) $stmt->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}

/**
 * Load the visitor's cart lines joined with current product data.
 *
 * Used by the cart page and the checkout summary. Re-reads the product
 * table on every call so the price, name and stock are always the live
 * database values (never the values the user added with).
 *
 * Returns an array of:
 *   [
 *     'item_id'    => int,
 *     'product_id' => int,
 *     'quantity'   => int,
 *     'name'       => string,
 *     'slug'       => string,
 *     'sku'        => string,
 *     'price'      => float,
 *     'compare_price' => float,
 *     'stock'      => int,
 *     'image_path' => string|null,
 *     'image_alt'  => string|null,
 *     'is_active'  => bool,
 *     'line_total' => float,   (price * quantity)
 *   ]
 *
 * If a product has been removed or deactivated since the item was added,
 * the line is still returned but flagged with `'unavailable' => true` and
 * `quantity` set to 0 so callers can drop it from totals.
 *
 * @return array<int,array<string,mixed>>
 */
function cart_get_items_with_products(): array
{
    $cart = cart_find_for_visitor();
    if ($cart === null) {
        return [];
    }

    try {
        $stmt = db()->prepare(
            'SELECT ci.id AS item_id, ci.quantity,
                    p.id AS product_id, p.name, p.slug, p.sku,
                    p.price, p.compare_price, p.stock, p.is_active,
                    pi.image_path, pi.alt_text AS image_alt
             FROM cart_items ci
             INNER JOIN products p ON p.id = ci.product_id
             LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_primary = 1
             WHERE ci.cart_id = :cid
             ORDER BY ci.id ASC'
        );
        $stmt->execute(['cid' => (int) $cart['id']]);
        $rows = $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }

    $out = [];
    foreach ($rows as $r) {
        $isActive = (int) ($r['is_active'] ?? 0) === 1;
        $stock    = (int) ($r['stock'] ?? 0);
        $qty      = (int) ($r['quantity'] ?? 0);
        $price    = (float) ($r['price'] ?? 0);
        $available = $isActive && $stock > 0 && $qty <= $stock;
        $out[] = [
            'item_id'       => (int) $r['item_id'],
            'product_id'    => (int) $r['product_id'],
            'quantity'      => $available ? $qty : 0,
            'requested_quantity' => $qty,
            'name'          => (string) $r['name'],
            'slug'          => (string) $r['slug'],
            'sku'           => (string) $r['sku'],
            'price'         => $price,
            'compare_price' => isset($r['compare_price']) ? (float) $r['compare_price'] : 0.0,
            'stock'         => $stock,
            'is_active'     => $isActive,
            'unavailable'   => !$available,
            'over_stock'    => $isActive && $stock > 0 && $qty > $stock,
            'image_path'    => $r['image_path'] ?? null,
            'image_alt'     => $r['image_alt'] ?? $r['name'],
            'line_total'    => $price * ($available ? $qty : 0),
        ];
    }

    return $out;
}

/**
 * Update a line in the current visitor's cart, enforcing live product stock.
 * Requests above stock are safely clamped and reported to the caller.
 *
 * @return array{ok:bool,quantity?:int,stock_limited?:bool,reason?:string}
 */
function cart_update_item_quantity(int $itemId, int $quantity): array
{
    if ($itemId <= 0 || $quantity < 1) {
        return ['ok' => false, 'reason' => 'invalid_quantity'];
    }

    $cart = cart_find_for_visitor();
    if ($cart === null) {
        return ['ok' => false, 'reason' => 'item_not_found'];
    }

    try {
        $stmt = db()->prepare(
            'SELECT ci.id, p.stock, p.is_active
             FROM cart_items ci
             INNER JOIN products p ON p.id = ci.product_id
             WHERE ci.id = :item_id AND ci.cart_id = :cart_id
             LIMIT 1'
        );
        $stmt->execute(['item_id' => $itemId, 'cart_id' => (int) $cart['id']]);
        $row = $stmt->fetch();
        if (!$row) {
            return ['ok' => false, 'reason' => 'item_not_found'];
        }

        $stock = (int) $row['stock'];
        if ((int) $row['is_active'] !== 1 || $stock < 1) {
            return ['ok' => false, 'reason' => 'unavailable'];
        }

        $safeQuantity = min($quantity, $stock);
        $update = db()->prepare(
            'UPDATE cart_items SET quantity = :quantity, updated_at = NOW()
             WHERE id = :item_id AND cart_id = :cart_id'
        );
        $update->execute([
            'quantity' => $safeQuantity,
            'item_id' => $itemId,
            'cart_id' => (int) $cart['id'],
        ]);

        return [
            'ok' => true,
            'quantity' => $safeQuantity,
            'stock_limited' => $safeQuantity !== $quantity,
        ];
    } catch (PDOException $e) {
        return ['ok' => false, 'reason' => 'database_error'];
    }
}

/** Remove one cart line, scoped to the current visitor's cart. */
function cart_remove_item(int $itemId): array
{
    if ($itemId <= 0) {
        return ['ok' => false, 'reason' => 'item_not_found'];
    }
    $cart = cart_find_for_visitor();
    if ($cart === null) {
        return ['ok' => false, 'reason' => 'item_not_found'];
    }
    try {
        $stmt = db()->prepare('DELETE FROM cart_items WHERE id = :item_id AND cart_id = :cart_id');
        $stmt->execute(['item_id' => $itemId, 'cart_id' => (int) $cart['id']]);
        return $stmt->rowCount() === 1
            ? ['ok' => true]
            : ['ok' => false, 'reason' => 'item_not_found'];
    } catch (PDOException $e) {
        return ['ok' => false, 'reason' => 'database_error'];
    }
}

/**
 * Compute the subtotal of the visitor's cart (sum of price * quantity).
 *
 * Unavailable / out-of-stock lines contribute 0. Returns 0.0 when the
 * cart is empty or no rows could be loaded.
 */
function cart_subtotal(): float
{
    $items = cart_get_items_with_products();
    $sum = 0.0;
    foreach ($items as $item) {
        if (!empty($item['unavailable'])) {
            continue;
        }
        $sum += (float) $item['line_total'];
    }
    return $sum;
}

/**
 * Empty the visitor's cart after a successful checkout.
 *
 * Only deletes cart_items for the visitor's own cart row. The cart
 * container itself is preserved so subsequent additions work normally.
 *
 * Returns true on success, false on database failure.
 */
function cart_clear(): bool
{
    $cart = cart_find_for_visitor();
    if ($cart === null) {
        return true; // nothing to clear
    }
    try {
        $stmt = db()->prepare('DELETE FROM cart_items WHERE cart_id = :cid');
        $stmt->execute(['cid' => (int) $cart['id']]);
        return true;
    } catch (PDOException $e) {
        return false;
    }
}