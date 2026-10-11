<?php
/**
 * Application configuration.
 * Change these values when deploying from localhost to a real server.
 */

declare(strict_types=1);

define('APP_NAME', 'NORD Home');
define('APP_TAGLINE', 'Phong cách sống hiện đại, tinh tế và tiện nghi');
define('APP_ENV', 'development'); // development | production
define('APP_VERSION', '0.1.0-foundation');

define('TIMEZONE', 'Asia/Ho_Chi_Minh');
date_default_timezone_set(TIMEZONE);

define('BASE_PATH', dirname(__DIR__));

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

// Cố định đường dẫn gốc chính xác cho dự án của bạn chạy trên cổng 88
define('BASE_URL', $scheme . '://' . $host . '/ecommerce/ecommerce');

define('DB_HOST', 'localhost');
define('DB_NAME', 'ecommerce');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

define('UPLOAD_PATH', BASE_PATH . DIRECTORY_SEPARATOR . 'uploads');
define('UPLOAD_URL', BASE_URL . '/uploads');
define('UPLOAD_PRODUCTS', UPLOAD_PATH . DIRECTORY_SEPARATOR . 'products');
define('UPLOAD_POSTS', UPLOAD_PATH . DIRECTORY_SEPARATOR . 'posts');
define('UPLOAD_BANNERS', UPLOAD_PATH . DIRECTORY_SEPARATOR . 'banners');
define('MAX_UPLOAD_BYTES', 5 * 1024 * 1024);
define('ALLOWED_IMAGE_MIMES', [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
    'image/gif' => 'gif',
]);

define('CURRENCY_CODE', 'VND');
define('CURRENCY_LOCALE', 'vi_VN');
define('ITEMS_PER_PAGE', 12);

define('SESSION_NAME', 'NORDSESSID');
define('SESSION_LIFETIME', 60 * 60 * 24 * 7);
