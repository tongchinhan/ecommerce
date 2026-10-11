<?php
/**
 * Admin display helpers.
 *
 * Pure formatters / mappers used by admin/*.php pages. No DB access here.
 */
declare(strict_types=1);

function admin_status_label(string $status): string
{
    return match ($status) {
        ORDER_PENDING    => 'Chờ xử lý',
        ORDER_CONFIRMED  => 'Đã xác nhận',
        ORDER_PROCESSING => 'Đang xử lý',
        ORDER_SHIPPED    => 'Đang giao',
        ORDER_DELIVERED  => 'Đã giao',
        ORDER_CANCELLED  => 'Đã hủy',
        default          => $status,
    };
}

function admin_status_badge(string $status): string
{
    return match ($status) {
        ORDER_PENDING    => 'warn',
        ORDER_CONFIRMED  => 'info',
        ORDER_PROCESSING => 'info',
        ORDER_SHIPPED    => 'info',
        ORDER_DELIVERED  => 'ok',
        ORDER_CANCELLED  => 'bad',
        default          => 'mute',
    };
}

function admin_payment_label(string $status): string
{
    return match ($status) {
        PAYMENT_UNPAID   => 'Chưa thanh toán',
        PAYMENT_PAID     => 'Đã thanh toán',
        PAYMENT_FAILED   => 'Thất bại',
        PAYMENT_REFUNDED => 'Đã hoàn tiền',
        default          => $status,
    };
}

function admin_payment_badge(string $status): string
{
    return match ($status) {
        PAYMENT_PAID     => 'ok',
        PAYMENT_FAILED   => 'bad',
        PAYMENT_REFUNDED => 'warn',
        default          => 'mute',
    };
}

function admin_user_status_label(string $status): string
{
    return match ($status) {
        USER_STATUS_ACTIVE   => 'Hoạt động',
        USER_STATUS_INACTIVE => 'Tạm ngưng',
        USER_STATUS_BANNED   => 'Bị cấm',
        default              => $status,
    };
}

function admin_user_status_badge(string $status): string
{
    return match ($status) {
        USER_STATUS_ACTIVE   => 'ok',
        USER_STATUS_INACTIVE => 'warn',
        USER_STATUS_BANNED   => 'bad',
        default              => 'mute',
    };
}

/**
 * Render a sortable table row of action buttons.
 */
function admin_pagination_query(string $base, int $page, int $perPage, int $total): string
{
    $pages = max(1, (int) ceil($total / max(1, $perPage)));
    if ($pages <= 1) {
        return '';
    }
    $html = '<div class="admin-pagination">';
    for ($i = 1; $i <= $pages; $i++) {
        if ($i === $page) {
            $html .= '<span class="admin-badge admin-badge--info">Trang ' . $i . '</span>';
        } else {
            $url = $base . (strpos($base, '?') === false ? '?' : '&') . 'page=' . $i;
            $html .= '<a class="admin-badge admin-badge--mute" href="' . e(url($url)) . '">' . $i . '</a>';
        }
    }
    $html .= '</div>';
    return $html;
}