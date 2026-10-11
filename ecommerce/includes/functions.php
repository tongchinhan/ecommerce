<?php
/**
 * Reusable helpers. Keep this file easy to explain in a project defense.
 */

declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $path): void
{
    if (preg_match('#^https?://#i', $path)) {
        header('Location: ' . $path);
        exit;
    }

    $path = '/' . ltrim($path, '/');
    header('Location: ' . BASE_URL . $path);
    exit;
}

function url(string $path = ''): string
{
    $path = ltrim($path, '/');
    return $path === '' ? BASE_URL : BASE_URL . '/' . $path;
}

function is_logged_in(): bool
{
    return !empty($_SESSION['user']['id']);
}

function current_user(): ?array
{
    return is_logged_in() ? $_SESSION['user'] : null;
}

function is_admin(): bool
{
    return is_logged_in() && (($_SESSION['user']['role'] ?? '') === ROLE_ADMIN);
}

function is_customer(): bool
{
    return is_logged_in() && (($_SESSION['user']['role'] ?? '') === ROLE_CUSTOMER);
}

function format_vnd($amount): string
{
    $amount = (float) $amount;
    return number_format($amount, 0, ',', '.') . ' ₫';
}

function format_date(?string $datetime, string $format = 'd/m/Y H:i'): string
{
    if ($datetime === null || $datetime === '') {
        return '';
    }

    $timestamp = strtotime($datetime);
    if ($timestamp === false) {
        return '';
    }

    return date($format, $timestamp);
}

function slugify(string $text): string
{
    $text = trim($text);
    $text = mb_strtolower($text, 'UTF-8');
    $map = [
        'à' => 'a', 'á' => 'a', 'ạ' => 'a', 'ả' => 'a', 'ã' => 'a',
        'â' => 'a', 'ầ' => 'a', 'ấ' => 'a', 'ậ' => 'a', 'ẩ' => 'a', 'ẫ' => 'a',
        'ă' => 'a', 'ằ' => 'a', 'ắ' => 'a', 'ặ' => 'a', 'ẳ' => 'a', 'ẵ' => 'a',
        'è' => 'e', 'é' => 'e', 'ẹ' => 'e', 'ẻ' => 'e', 'ẽ' => 'e',
        'ê' => 'e', 'ề' => 'e', 'ế' => 'e', 'ệ' => 'e', 'ể' => 'e', 'ễ' => 'e',
        'ì' => 'i', 'í' => 'i', 'ị' => 'i', 'ỉ' => 'i', 'ĩ' => 'i',
        'ò' => 'o', 'ó' => 'o', 'ọ' => 'o', 'ỏ' => 'o', 'õ' => 'o',
        'ô' => 'o', 'ồ' => 'o', 'ố' => 'o', 'ộ' => 'o', 'ổ' => 'o', 'ỗ' => 'o',
        'ơ' => 'o', 'ờ' => 'o', 'ớ' => 'o', 'ợ' => 'o', 'ở' => 'o', 'ỡ' => 'o',
        'ù' => 'u', 'ú' => 'u', 'ụ' => 'u', 'ủ' => 'u', 'ũ' => 'u',
        'ư' => 'u', 'ừ' => 'u', 'ứ' => 'u', 'ự' => 'u', 'ử' => 'u', 'ữ' => 'u',
        'ỳ' => 'y', 'ý' => 'y', 'ỵ' => 'y', 'ỷ' => 'y', 'ỹ' => 'y',
        'đ' => 'd',
    ];
    $text = strtr($text, $map);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
    $text = trim($text, '-');

    return $text === '' ? 'item' : $text;
}

function flash_set(string $type, string $message): void
{
    $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
}

function flash_get(): array
{
    $messages = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);

    return is_array($messages) ? $messages : [];
}

function old(string $key, string $default = ''): string
{
    $old = $_SESSION['_old'][$key] ?? $default;
    return is_string($old) ? $old : $default;
}

function old_set(array $data): void
{
    $_SESSION['_old'] = $data;
}

function old_clear(): void
{
    unset($_SESSION['_old']);
}

function validate_email(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validate_required(string $value): bool
{
    return trim($value) !== '';
}

function validate_length(string $value, int $min, int $max): bool
{
    $len = mb_strlen($value, 'UTF-8');
    return $len >= $min && $len <= $max;
}

function validate_upload(array $file): array
{
    $errors = [];

    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        $errors[] = 'No file was uploaded.';
        return $errors;
    }

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        $errors[] = 'The file could not be uploaded.';
        return $errors;
    }

    if (($file['size'] ?? 0) > MAX_UPLOAD_BYTES) {
        $errors[] = 'The file is larger than the allowed size (5MB).';
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name'] ?? '') ?: '';
    if (!isset(ALLOWED_IMAGE_MIMES[$mime])) {
        $errors[] = 'Only JPG, PNG, WEBP, and GIF images are allowed.';
    }

    return $errors;
}

function store_user_session(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id' => (int) $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'role' => $user['role'],
    ];
}

function find_user_by_email(string $email): ?array
{
    $stmt = db()->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    return $user ?: null;
}

function check_system_status(): array
{
    $status = [
        'php' => true,
        'php_version' => PHP_VERSION,
        'mysql' => false,
        'database' => false,
        'mysql_error' => '',
    ];

    try {
        db_server()->query('SELECT 1');
        $status['mysql'] = true;
        $status['database'] = db_database_exists();
    } catch (PDOException $e) {
        $status['mysql_error'] = 'Could not connect to MySQL. Start MySQL in XAMPP and check config/config.php.';
    }

    return $status;
}
