<?php
require_once __DIR__ . '/home/home-data.php';
$footerEmail = trim(hp_setting('store_email'));
$footerEmailConfigured = filter_var($footerEmail, FILTER_VALIDATE_EMAIL) && !str_ends_with(strtolower($footerEmail), '.local');
$footerPhone = trim(hp_setting('store_phone'));
$footerPhoneHref = preg_replace('/\D+/', '', $footerPhone) ?? '';
$footerAddress = trim(hp_setting('store_address'));
?>
</main>
<footer class="site-footer"><div class="container footer-inner"><div class="footer-grid">
<section class="footer-col footer-brand"><h3 class="footer-brand-name"><?= e(APP_NAME) ?></h3><p class="footer-tagline">Phong cách sống hiện đại, tinh tế và tiện nghi.</p><p class="footer-line">Những sản phẩm gia dụng được thiết kế tỉ mỉ, tạo nên không gian sống yên bình.</p></section>
<nav class="footer-col" aria-label="Liên kết cửa hàng"><h4 class="footer-heading">Cửa hàng</h4><ul class="footer-list"><li><a href="<?= e(url('products.php')) ?>">Tất cả sản phẩm</a></li><li><a href="<?= e(url('products.php?featured=1')) ?>">Sản phẩm nổi bật</a></li><li><a href="<?= e(url('products.php?sort=newest')) ?>">Sản phẩm mới</a></li><li><a href="<?= e(url('products.php?sort=bestsellers')) ?>">Sản phẩm bán chạy</a></li></ul></nav>
<nav class="footer-col" aria-label="Liên kết về NORD Home"><h4 class="footer-heading">Về chúng tôi</h4><ul class="footer-list"><li><a href="<?= e(url('about.php')) ?>">Giới thiệu</a></li><li><a href="<?= e(url('blog.php')) ?>">Tin tức</a></li><li><a href="<?= e(url('contact.php')) ?>">Liên hệ</a></li></ul></nav>
<nav class="footer-col" aria-label="Thông tin hỗ trợ"><h4 class="footer-heading">Hỗ trợ</h4><ul class="footer-list"><li><a href="<?= e(url('shipping-policy.php')) ?>">Giao hàng</a></li><li><a href="<?= e(url('return-policy.php')) ?>">Đổi trả</a></li><li><a href="<?= e(url('faq.php')) ?>">Câu hỏi thường gặp</a></li></ul></nav>
<section class="footer-col"><h4 class="footer-heading">Liên hệ</h4><ul class="footer-list footer-contact">
<li><?= icon('mail', 'icon icon--sm') ?><?php if ($footerEmailConfigured): ?><a href="mailto:<?= e($footerEmail) ?>"><?= e($footerEmail) ?></a><?php else: ?><span>Email cửa hàng chưa được cấu hình</span><?php endif; ?></li>
<?php if ($footerPhone !== ''): ?><li><?= icon('phone', 'icon icon--sm') ?> <a href="tel:<?= e($footerPhoneHref) ?>"><?= e($footerPhone) ?></a></li><?php endif; ?>
<?php if ($footerAddress !== ''): ?><li><?= icon('pin', 'icon icon--sm') ?> <?= e($footerAddress) ?></li><?php endif; ?>
</ul></section></div><div class="footer-bottom"><p class="footer-copy">&copy; <?= date('Y') ?> <?= e(APP_NAME) ?>. Bảo lưu mọi quyền.</p><ul class="footer-legal"><li><a href="<?= e(url('privacy-policy.php')) ?>">Quyền riêng tư</a></li><li><a href="<?= e(url('terms.php')) ?>">Điều khoản sử dụng</a></li></ul></div></div></footer>
<script src="<?= e(url('assets/js/main.js')) ?>"></script></body></html>