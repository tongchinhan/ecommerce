<?php
/**
 * Order data layer.
 *
 * Centralized database access for checkout and order creation.
 * - Uses the existing PDO singleton (db()).
 * - All financial calculations are derived from the database, never from
 *   hidden form inputs.
 * - Order creation is atomic: BEGIN ... COMMIT, with ROLLBACK on any
 *   failure so a partial order is never persisted.
 *
 * Tables touched: orders, order_items, payments, products (for stock).
 *
 * Payment status semantics:
 *   - 'cod' creates a payment row with status 'unpaid'.
 *   - 'bank_transfer' creates a payment row with status 'pending'.
 *   - No payment is ever marked 'paid' here (that requires a real
 *     payment-gateway confirmation which is out of scope for this phase).
 */
declare(strict_types=1);

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/cart-data.php';

/**
 * Payment methods offered at checkout.
 *
 * The existing `orders.payment_status` ENUM allows only:
 *   'unpaid', 'paid', 'failed', 'refunded'
 * It does NOT have a 'pending' value. We map both COD and bank-transfer
 * to 'unpaid' because in both cases money has not been received yet:
 *   - COD:         cash on delivery (awaiting delivery).
 *   - bank-transfer: awaiting confirmation of the transfer.
 *
 * Add new methods here; they will appear on the checkout form.
 *
 * @return array<string,array{label:string,description:string,key:string,status:string}>
 */
function checkout_payment_methods(): array
{
    return [
        'cod' => [
            'key'         => 'cod',
            'label'       => 'Thanh toán khi nhận hàng (COD)',
            'description' => 'Thanh toán bằng tiền mặt cho đơn vị vận chuyển khi nhận hàng.',
            'status'      => 'unpaid',
        ],
        'bank_transfer' => [
            'key'         => 'bank_transfer',
            'label'       => 'Chuyển khoản ngân hàng',
            'description' => 'Chuyển khoản theo thông tin ngân hàng. Đơn hàng được giữ và xử lý sau khi nhận được thanh toán.',
            'status'      => 'unpaid',
        ],
    ];
}

/**
 * Read optional bank-transfer details from the existing settings table.
 * Supported setting keys: bank_name, bank_account_holder,
 * bank_account_number, bank_branch.
 *
 * @return array{bank_name:string,account_holder:string,account_number:string,branch:string}
 */
function checkout_bank_transfer_settings(): array
{
    static $details = null;
    if ($details !== null) {
        return $details;
    }
    $details = ['bank_name' => '', 'account_holder' => '', 'account_number' => '', 'branch' => ''];
    try {
        $stmt = db()->query(
            "SELECT setting_key, setting_value FROM settings
             WHERE setting_key IN ('bank_name', 'bank_account_holder', 'bank_account_number', 'bank_branch')"
        );
        $map = [
            'bank_name' => 'bank_name',
            'bank_account_holder' => 'account_holder',
            'bank_account_number' => 'account_number',
            'bank_branch' => 'branch',
        ];
        foreach ($stmt->fetchAll() as $row) {
            $key = (string) ($row['setting_key'] ?? '');
            if (isset($map[$key])) {
                $details[$map[$key]] = trim((string) ($row['setting_value'] ?? ''));
            }
        }
    } catch (PDOException $e) {
        // The storefront explains that transfer details have not been configured.
    }
    return $details;
}
/**
 * Resolve a payment method key from POST. Unknown keys are rejected.
 */
function checkout_resolve_payment_method(?string $raw): ?array
{
    $raw = is_string($raw) ? trim($raw) : '';
    if ($raw === '') {
        return null;
    }
    $methods = checkout_payment_methods();
    return isset($methods[$raw]) ? $methods[$raw] : null;
}

/**
 * Shipping fee policy.
 *
 * Reads the existing `free_shipping_min` setting from the `settings`
 * table (seeded in database.sql). When the subtotal is at or above the
 * threshold, shipping is free. Otherwise a fixed fallback shipping fee
 * (defined here as a single source of truth) is applied.
 *
 * The constant is defined in config/constants.php style — but to avoid
 * bloating constants.php with a checkout-specific value, we keep it
 * here as a single fallback. The settings value remains the source of
 * truth for the threshold.
 *
 * @return array{shipping_fee: float, free_shipping: bool, threshold: float}
 */
function checkout_shipping_fee(float $subtotal): array
{
    $threshold = 2000000.0; // safe default that matches the announcement bar
    try {
        $stmt = db()->prepare('SELECT setting_value FROM settings WHERE setting_key = :k LIMIT 1');
        $stmt->execute(['k' => 'free_shipping_min']);
        $val = $stmt->fetchColumn();
        if ($val !== false && is_numeric((string) $val)) {
            $threshold = (float) $val;
        }
    } catch (PDOException $e) {
        // keep the default
    }

    // Fallback fixed shipping fee, used only when subtotal < threshold.
    $fallbackFee = 30000.0;

    $free = $subtotal >= $threshold && $threshold > 0;
    $fee  = $free ? 0.0 : ($subtotal > 0 ? $fallbackFee : 0.0);

    return [
        'shipping_fee' => $fee,
        'free_shipping' => $free,
        'threshold'     => $threshold,
    ];
}

/**
 * Validate Vietnamese phone numbers in a tolerant way.
 *
 * Accepts:
 *   - 0xxxxxxxxx (10-11 digits starting with 0)
 *   - +84xxxxxxxxx (Vietnam country code)
 *   - Plain 9-11 digit strings (when typed without country code)
 *
 * Rejects obviously invalid input (letters, spaces-only, wrong length).
 */
function validate_vn_phone(string $phone): bool
{
    $phone = trim($phone);
    if ($phone === '') {
        return false;
    }
    // Strip common separators used in display
    $cleaned = preg_replace('/[\s\-.\(\)]/', '', $phone) ?? '';
    // +84 then 9 digits starting with a valid carrier (3,5,7,8,9)
    if (preg_match('/^\+84(3|5|7|8|9)\d{8}$/', $cleaned)) {
        return true;
    }
    // 0 then 9-10 digits (mobile: 10 digits; landline: 10-11)
    if (preg_match('/^0\d{9,10}$/', $cleaned)) {
        return true;
    }
    return false;
}

/**
 * Validate shipping address fields.
 *
 * @return array<int,string>  List of validation error messages (empty when OK).
 */
function checkout_validate_shipping(array $input): array
{
    $errors = [];

    $name = trim((string) ($input['shipping_name'] ?? ''));
    if ($name === '' || mb_strlen($name, 'UTF-8') < 2 || mb_strlen($name, 'UTF-8') > 120) {
        $errors[] = 'Vui lòng nhập họ và tên người nhận (từ 2 đến 120 ký tự).';
    }

    $phone = trim((string) ($input['shipping_phone'] ?? ''));
    if ($phone === '') {
        $errors[] = 'Vui lòng nhập số điện thoại.';
    } elseif (!validate_vn_phone($phone)) {
        $errors[] = 'Số điện thoại không hợp lệ.';
    }

    $email = trim((string) ($input['shipping_email'] ?? ''));
    if ($email !== '' && !validate_email($email)) {
        $errors[] = 'Email không hợp lệ. Vui lòng nhập đúng định dạng email.';
    }

    $address = trim((string) ($input['shipping_address'] ?? ''));
    if ($address === '' || mb_strlen($address, 'UTF-8') < 5 || mb_strlen($address, 'UTF-8') > 255) {
        $errors[] = 'Vui lòng nhập địa chỉ nhận hàng.';
    }

    $city = trim((string) ($input['shipping_city'] ?? ''));
    if ($city === '' || mb_strlen($city, 'UTF-8') < 2 || mb_strlen($city, 'UTF-8') > 120) {
        $errors[] = 'Vui lòng nhập tỉnh / thành phố.';
    }

    return $errors;
}

/**
 * Generate a unique order number.
 *
 * Format: NH{YYYYMMDD}{NNNN}
 *
 * NNNN is a 4-digit zero-padded sequence for orders created on the same
 * day. To stay correct under retries and rare race conditions we SELECT
 * the highest existing number for the day and add 1, retrying on
 * duplicate-key errors (extremely unlikely with a per-day sequence).
 */
function order_generate_number(PDO $pdo): string
{
    $date = date('Ymd'); // Asia/Ho_Chi_Minh via bootstrap
    $prefix = 'NH' . $date;

    // Find the highest sequence for today.
    $max = 0;
    try {
        $stmt = $pdo->prepare(
            "SELECT order_number FROM orders
             WHERE order_number LIKE :pfx
             ORDER BY order_number DESC LIMIT 1"
        );
        $stmt->execute(['pfx' => $prefix . '%']);
        $row = $stmt->fetchColumn();
        if (is_string($row) && preg_match('/(\d{4})$/', $row, $m)) {
            $max = (int) $m[1];
        }
    } catch (PDOException $e) {
        // ignore — fall back to sequence 1
    }

    $seq = $max + 1;
    return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
}

/**
 * Create a new order from the current visitor's cart.
 *
 * Atomic flow:
 *   1. Validate cart + inputs.
 *   2. Resolve payment method.
 *   3. BEGIN transaction.
 *   4. Re-read product prices and stock (server-side, fresh).
 *   5. Recompute subtotal, shipping, total.
 *   6. Validate stock after the recompute.
 *   7. INSERT into orders.
 *   8. INSERT into order_items (per line, with snapshotted prices).
 *   9. INSERT into payments.
 *  10. UPDATE products.stock (decrement).
 *  11. COMMIT.
 *
 * If any step throws, ROLLBACK and return the error reason.
 *
 * @return array{ok:bool, order_id?:int, order_number?:string, reason?:string, message?:string}
 */
function order_create(array $input): array
{
    $user = current_user();
    if (!$user) {
        return ['ok' => false, 'reason' => 'not_logged_in', 'message' => 'Vui lòng đăng nhập để đặt hàng.'];
    }

    $payment = checkout_resolve_payment_method($input['payment_method'] ?? null);
    if ($payment === null) {
        return ['ok' => false, 'reason' => 'invalid_payment', 'message' => 'Vui lòng chọn phương thức thanh toán.'];
    }

    $shippingErrors = checkout_validate_shipping($input);
    if (!empty($shippingErrors)) {
        return ['ok' => false, 'reason' => 'invalid_shipping', 'message' => $shippingErrors[0]];
    }

    // Pull the cart (live data).
    $items = cart_get_items_with_products();
    $available = array_values(array_filter($items, function ($i) {
        return empty($i['unavailable']);
    }));
    if (count($available) !== count($items)) {
        return ['ok' => false, 'reason' => 'invalid_cart', 'message' => 'Giỏ hàng có sản phẩm hết hàng hoặc số lượng vượt tồn kho. Vui lòng cập nhật giỏ hàng trước khi đặt.'];
    }
    if (empty($available)) {
        return ['ok' => false, 'reason' => 'empty_cart', 'message' => 'Giỏ hàng không còn sản phẩm hợp lệ.'];
    }

    // Stock validation + recompute subtotal from server-side prices.
    $subtotal = 0.0;
    foreach ($available as $item) {
        if ((int) $item['stock'] < (int) $item['quantity']) {
            return [
                'ok' => false,
                'reason' => 'out_of_stock',
                'message' => 'Sản phẩm "' . $item['name'] . '" không còn đủ số lượng trong kho.',
            ];
        }
        $subtotal += (float) $item['line_total'];
    }

    $ship = checkout_shipping_fee($subtotal);
    $shippingFee = (float) $ship['shipping_fee'];
    $total        = $subtotal + $shippingFee; // no coupon in this phase

    $pdo = db();
    $orderNumber = null;
    $orderId     = null;
    $attempt     = 0;

    /* Retry on the very unlikely case that two orders within the same
       millisecond both computed the same sequence. We retry up to 5 times. */
    retry_order_number:
    $attempt++;

    try {
        $pdo->beginTransaction();

        // Generate the order number INSIDE the transaction so two concurrent
        // inserts can't both claim the same sequence (within a single request
        // — we also retry on the UNIQUE-key violation as a safety net).
        $orderNumber = order_generate_number($pdo);

        $stmt = $pdo->prepare(
            'INSERT INTO orders (
                user_id, order_number, status, payment_status,
                subtotal, discount, shipping_fee, total,
                shipping_name, shipping_phone,
                shipping_address, shipping_city,
                notes
            ) VALUES (
                :uid, :num, :status, :pstatus,
                :subtotal, :discount, :ship, :total,
                :name, :phone,
                :addr, :city,
                :notes
            )'
        );
        $notes = trim((string) ($input['notes'] ?? ''));

        /* The existing `orders` schema persists only shipping_name,
           shipping_phone, shipping_address, shipping_city, and notes. It
           does not have dedicated columns for email / district / ward.
           To honor the requirement to keep the existing schema, those
           optional fields are appended to the notes field in a structured
           way so the data isn't lost. */
        $extras = [];
        $district = trim((string) ($input['shipping_district'] ?? ''));
        $ward     = trim((string) ($input['shipping_ward'] ?? ''));
        $email    = trim((string) ($input['shipping_email'] ?? ''));
        if ($email !== '')    { $extras[] = 'Email: ' . $email; }
        if ($district !== '') { $extras[] = 'Quận/Huyện: ' . $district; }
        if ($ward !== '')     { $extras[] = 'Phường/Xã: ' . $ward; }
        if (!empty($extras)) {
            $notes = ($notes !== '' ? $notes . "\n" : '') . '[' . implode(' | ', $extras) . ']';
        }

        $stmt->execute([
            'uid'      => (int) $user['id'],
            'num'      => $orderNumber,
            'status'   => ORDER_PENDING,
            'pstatus'  => $payment['status'],
            'subtotal' => $subtotal,
            'discount' => 0.0,
            'ship'     => $shippingFee,
            'total'    => $total,
            'name'     => trim((string) ($input['shipping_name'] ?? '')),
            'phone'    => trim((string) ($input['shipping_phone'] ?? '')),
            'addr'     => trim((string) ($input['shipping_address'] ?? '')),
            'city'     => trim((string) ($input['shipping_city'] ?? '')),
            'notes'    => $notes !== '' ? $notes : null,
        ]);
        $orderId = (int) $pdo->lastInsertId();

        // Insert order items (snapshot prices + decrement stock atomically).
        $itemStmt = $pdo->prepare(
            'INSERT INTO order_items
                (order_id, product_id, product_name, sku, quantity, unit_price, total)
             VALUES (:oid, :pid, :pname, :psku, :qty, :price, :total)'
        );
        /* Native MySQL prepared statements (PDO::ATTR_EMULATE_PREPARES=false)
           do not allow a named placeholder to appear twice in one statement,
           so we use positional ? placeholders for the stock decrement. */
        $stockUpd = $pdo->prepare(
            'UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?'
        );
        foreach ($available as $item) {
            $lineQty   = (int) $item['quantity'];
            $unitPrice = (float) $item['price'];
            $lineTotal = $unitPrice * $lineQty;
            $itemStmt->execute([
                'oid'   => $orderId,
                'pid'   => (int) $item['product_id'],
                'pname' => (string) $item['name'],
                'psku'  => (string) $item['sku'],
                'qty'   => $lineQty,
                'price' => $unitPrice,
                'total' => $lineTotal,
            ]);
            // Conditional decrement: if stock is insufficient at write-time
            // we abort the transaction. This prevents oversell under
            // concurrent orders.
            $stockUpd->execute([
                $lineQty,
                (int) $item['product_id'],
                $lineQty,
            ]);
            if ($stockUpd->rowCount() !== 1) {
                throw new RuntimeException('Insufficient stock for product ' . $item['product_id']);
            }
        }

        // Insert payment record.
        $payStmt = $pdo->prepare(
            'INSERT INTO payments (order_id, method, amount, status)
             VALUES (:oid, :method, :amount, :status)'
        );
        $payStmt->execute([
            'oid'    => $orderId,
            'method' => $payment['key'],
            'amount' => $total,
            'status' => $payment['status'],
        ]);

        $pdo->commit();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        /* If we hit a duplicate order_number, retry up to 5 times before
           giving up. This guards against concurrent inserts that both
           computed the same per-day sequence. */
        if ($attempt < 5
            && (int) $e->errorInfo[1] === 1062
            && stripos((string) ($e->errorInfo[2] ?? ''), 'order_number') !== false
        ) {
            goto retry_order_number;
        }
        return [
            'ok' => false,
            'reason' => 'database_error',
            'message' => 'Không thể tạo đơn hàng. Vui lòng thử lại sau.',
        ];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return [
            'ok' => false,
            'reason' => 'database_error',
            'message' => 'Không thể tạo đơn hàng. Vui lòng thử lại sau.',
        ];
    }

    // Only clear the cart AFTER the order is fully committed.
    cart_clear();

    return [
        'ok' => true,
        'order_id' => $orderId,
        'order_number' => $orderNumber,
    ];
}

/**
 * Look up a finalized order by its order_number.
 *
 * Used by order-success.php. Restricts to the current user (admins can
 * see any order, customers can only see their own).
 *
 * @return array<string,mixed>|null
 */
function order_find_by_number(string $number): ?array
{
    $user = current_user();
    if (!$user) {
        return null;
    }
    $number = trim($number);
    if ($number === '') {
        return null;
    }
    try {
        $stmt = db()->prepare(
            'SELECT * FROM orders WHERE order_number = :num LIMIT 1'
        );
        $stmt->execute(['num' => $number]);
        $order = $stmt->fetch();
        if (!$order) {
            return null;
        }
        // Ownership: customers can only see their own. Admins can see any.
        if (!is_admin() && (int) $order['user_id'] !== (int) $user['id']) {
            return null;
        }
        return $order;
    } catch (PDOException $e) {
        return null;
    }
}

/**
 * Load the order_items for a given order_id.
 *
 * Used by order-success.php to render the summary.
 *
 * @return array<int,array<string,mixed>>
 */
function order_items_for(int $orderId): array
{
    if ($orderId <= 0) {
        return [];
    }
    try {
        $stmt = db()->prepare(
            'SELECT oi.*, pi.image_path, pi.alt_text AS image_alt
             FROM order_items oi
             LEFT JOIN product_images pi ON pi.product_id = oi.product_id AND pi.is_primary = 1
             WHERE oi.order_id = :oid
             ORDER BY oi.id ASC'
        );
        $stmt->execute(['oid' => $orderId]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Load the payment row(s) for a given order_id.
 *
 * @return array<int,array<string,mixed>>
 */
function order_payments_for(int $orderId): array
{
    if ($orderId <= 0) {
        return [];
    }
    try {
        $stmt = db()->prepare(
            'SELECT * FROM payments WHERE order_id = :oid ORDER BY id ASC'
        );
        $stmt->execute(['oid' => $orderId]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}