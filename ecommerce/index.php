<?php
/**
 * NORD Home — Customer homepage.
 *
 * Composes the full storefront homepage from small reusable partials.
 * All dynamic data is pulled from MySQL via includes/home/home-data.php.
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$pageTitle = 'Trang chủ';
$bodyClass = 'page-home';

require __DIR__ . '/includes/header.php';
?>
<?php require __DIR__ . '/includes/home/home-hero.php'; ?>
<?php require __DIR__ . '/includes/home/home-categories.php'; ?>
<?php require __DIR__ . '/includes/home/home-featured.php'; ?>
<?php require __DIR__ . '/includes/home/home-promo.php'; ?>
<?php require __DIR__ . '/includes/home/home-bestsellers.php'; ?>
<?php require __DIR__ . '/includes/home/home-new-arrivals.php'; ?>
<?php require __DIR__ . '/includes/home/home-values.php'; ?>
<?php require __DIR__ . '/includes/home/home-journal.php'; ?>
<?php require __DIR__ . '/includes/home/home-newsletter.php'; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
