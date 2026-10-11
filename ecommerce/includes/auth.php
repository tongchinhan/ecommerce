<?php
/**
 * Require a logged-in customer (or admin) for account pages.
 */

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

if (!is_logged_in()) {
    flash_set('error', 'Bạn cần đăng nhập để tiếp tục.');
    redirect('/login.php');
}
