<?php
/**
 * CSRF token helpers for HTML forms.
 */

declare(strict_types=1);

function csrf_token(): string
{
    if (empty($_SESSION['_csrf_token']) || !is_string($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['_csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function csrf_verify(?string $token = null): bool
{
    $token = $token ?? (string) ($_POST['csrf_token'] ?? '');
    $sessionToken = (string) ($_SESSION['_csrf_token'] ?? '');

    if ($token === '' || $sessionToken === '') {
        return false;
    }

    return hash_equals($sessionToken, $token);
}

function csrf_require(): void
{
    if (!csrf_verify()) {
        http_response_code(403);
        exit('Invalid security token. Please refresh the page and try again.');
    }
}
