<?php
$pageTitle = $pageTitle ?? 'Quản trị';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> — <?= e(APP_NAME) ?> Quản trị</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(url('assets/css/admin.css')) ?>">
</head>
<body class="admin-body">
<div class="admin-shell">
<?php require __DIR__ . '/admin-sidebar.php'; ?>
<div class="admin-wrap">
    <header class="admin-top">
        <div class="admin-top-title">
            <strong><?= e($pageTitle) ?></strong>
            <small><?= e(APP_NAME) ?> · Quản trị</small>
        </div>
        <div class="admin-top-meta">
            <span class="admin-top-user">
                <span class="avatar-letter"><?= e(mb_strtoupper(mb_substr((string) (current_user()['name'] ?? 'A'), 0, 1, 'UTF-8'))) ?></span>
                <span>
                    <span class="admin-top-user-label">Đang đăng nhập</span>
                    <span class="admin-top-user-name"><?= e(current_user()['name'] ?? 'Quản trị viên') ?></span>
                </span>
            </span>
            <a class="admin-top-link" href="<?= e(url('admin/logout.php')) ?>">Đăng xuất</a>
        </div>
    </header>
    <main class="admin-main">
        <?php foreach (flash_get() as $flash): ?>
            <div class="flash flash-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
        <?php endforeach; ?>