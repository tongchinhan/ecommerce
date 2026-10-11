<?php
/**
 * Temporary connection test. Safe to keep during development.
 * Does not print database credentials.
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$pageTitle = 'Kiểm tra cơ sở dữ liệu';
$status = check_system_status();
$tables = [];

if ($status['mysql'] && $status['database']) {
    try {
        $tables = db()->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        $tables = [];
    }
}

require __DIR__ . '/includes/header.php';
?>
<div class="panel">
    <h1>Kiểm tra kết nối cơ sở dữ liệu</h1>
    <p class="muted">Trang này xác nhận ngăn xếp XAMPP cục bộ mà không hiển thị thông tin đăng nhập.</p>
    <ul>
        <li>PHP: <strong class="status-ok">hoạt động</strong> (<?= e($status['php_version']) ?>)</li>
        <li>Kết nối MySQL:
            <?php if ($status['mysql']): ?>
                <strong class="status-ok">thành công</strong>
            <?php else: ?>
                <strong class="status-bad">thất bại</strong> — <?= e($status['mysql_error']) ?>
            <?php endif; ?>
        </li>
        <li>Cơ sở dữ liệu <code>ecommerce</code>:
            <?php if ($status['database']): ?>
                <strong class="status-ok">đã tồn tại</strong>
            <?php else: ?>
                <strong class="status-bad">chưa có</strong>
            <?php endif; ?>
        </li>
    </ul>
    <?php if ($tables): ?>
        <h2>Bảng (<?= count($tables) ?>)</h2>
        <p><?= e(implode(', ', $tables)) ?></p>
    <?php endif; ?>
    <p><a class="btn" href="<?= e(url()) ?>">Về trang chủ</a></p>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
