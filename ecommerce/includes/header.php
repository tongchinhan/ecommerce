<?php
$pageTitle = $pageTitle ?? APP_NAME;
$pageDescription = $pageDescription ?? APP_NAME . ' · ' . APP_TAGLINE . ' — Những sản phẩm tinh tế cho cuộc sống mỗi ngày, giao hàng toàn quốc.';
$bodyClass = $bodyClass ?? '';
$isHome = ($bodyClass === 'page-home');
$isShop = ($bodyClass === 'page-shop' || $bodyClass === 'page-product');
$isBlog = ($bodyClass === 'page-blog' || $bodyClass === 'page-post');
$pageCss = $pageCss ?? null;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> — <?= e(APP_NAME) ?></title>
    <meta name="description" content="<?= e($pageDescription) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700&family=Cormorant+Garamond:wght@500;600&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
    <?php if ($isHome): ?>
        <link rel="stylesheet" href="<?= e(url('assets/css/home.css')) ?>">
    <?php endif; ?>
    <?php if ($isShop): ?>
        <link rel="stylesheet" href="<?= e(url('assets/css/shop.css')) ?>">
    <?php endif; ?>
    <?php if ($isBlog): ?>
        <link rel="stylesheet" href="<?= e(url('assets/css/blog.css')) ?>">
    <?php endif; ?>
    <?php if (is_string($pageCss) && $pageCss !== ''): ?>
        <link rel="stylesheet" href="<?= e(url('assets/css/' . $pageCss)) ?>">
    <?php endif; ?>
</head>
<body class="<?= e($bodyClass) ?>">
<?php require __DIR__ . '/announcement.php'; ?>
<?php require __DIR__ . '/navbar.php'; ?>
<main class="site-main">
    <?php foreach (flash_get() as $flash): ?>
        <div class="flash flash-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
    <?php endforeach; ?>
