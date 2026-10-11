<?php
/**
 * Require an authenticated admin for admin pages.
 */

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

if (!is_logged_in()) {
    /* Capture a safe local return path so users who tried to reach an admin
       page while signed out are sent back there after signing in. Only
       internal /admin/* paths are honored — never absolute URLs. */
    $nextRaw = $_GET['next'] ?? $_POST['next'] ?? '';
    if (is_string($nextRaw) && preg_match('#^/admin/[A-Za-z0-9._/?&=%-]*$#', $nextRaw) && strpos($nextRaw, '//') !== 0) {
        flash_set('error', 'Bạn cần đăng nhập quản trị để tiếp tục.');
        redirect('/admin/login.php?next=' . urlencode($nextRaw));
    }
    flash_set('error', 'Bạn cần đăng nhập quản trị để tiếp tục.');
    redirect('/admin/login.php');
}

if (!is_admin()) {
    /* A signed-in non-admin (customer) tried to reach an admin URL.
       Route them to their own dashboard rather than the storefront so
       this can never be confused with an "admin sent to homepage" bug. */
    flash_set('error', 'Tài khoản của bạn không có quyền truy cập khu vực quản trị.');
    redirect('/account/index.php');
}
