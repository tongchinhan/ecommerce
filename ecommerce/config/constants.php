<?php
/**
 * Application constants (roles, statuses, order states).
 */

declare(strict_types=1);

define('ROLE_CUSTOMER', 'customer');
define('ROLE_ADMIN', 'admin');

define('USER_STATUS_ACTIVE', 'active');
define('USER_STATUS_INACTIVE', 'inactive');
define('USER_STATUS_BANNED', 'banned');

define('ORDER_PENDING', 'pending');
define('ORDER_CONFIRMED', 'confirmed');
define('ORDER_PROCESSING', 'processing');
define('ORDER_SHIPPED', 'shipped');
define('ORDER_DELIVERED', 'delivered');
define('ORDER_CANCELLED', 'cancelled');

define('PAYMENT_UNPAID', 'unpaid');
define('PAYMENT_PAID', 'paid');
define('PAYMENT_FAILED', 'failed');
define('PAYMENT_REFUNDED', 'refunded');

define('COUPON_PERCENT', 'percent');
define('COUPON_FIXED', 'fixed');
