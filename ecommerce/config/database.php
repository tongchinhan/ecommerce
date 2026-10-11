<?php
/**
 * Centralized PDO connection. Do not create other database connections.
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';

function pdo_options(): array
{
    return [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
}

/**
 * Connect to MySQL/MariaDB without selecting a database (server ping).
 */
function db_server(): PDO
{
    static $server = null;

    if ($server instanceof PDO) {
        return $server;
    }

    $dsn = sprintf('mysql:host=%s;charset=%s', DB_HOST, DB_CHARSET);
    $server = new PDO($dsn, DB_USER, DB_PASS, pdo_options());

    return $server;
}

/**
 * Application PDO connection to the ecommerce database.
 */
function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=%s',
        DB_HOST,
        DB_NAME,
        DB_CHARSET
    );
    $pdo = new PDO($dsn, DB_USER, DB_PASS, pdo_options());

    return $pdo;
}

function db_database_exists(): bool
{
    try {
        $stmt = db_server()->prepare(
            'SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = :name LIMIT 1'
        );
        $stmt->execute(['name' => DB_NAME]);

        return (bool) $stmt->fetchColumn();
    } catch (PDOException $e) {
        return false;
    }
}
