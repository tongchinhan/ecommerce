<?php
/**
 * Newsletter form is not connected to subscriber storage or email delivery yet.
 */
declare(strict_types=1);
?>
<section class="newsletter-section" aria-labelledby="newsletter-heading"><div class="container newsletter-inner">
<div class="newsletter-content"><p class="section-eyebrow">Bản tin</p><h2 class="section-heading" id="newsletter-heading">Theo dõi thông tin mới.</h2><p class="section-subtext">Biểu mẫu đăng ký nhận bản tin hiện chưa được kết nối với hệ thống lưu trữ email.</p></div>
<form class="newsletter-form" id="newsletterForm" novalidate><label class="visually-hidden" for="newsletterEmail">Địa chỉ email</label>
<div class="newsletter-field"><input type="email" id="newsletterEmail" name="email" class="newsletter-input" placeholder="Email của bạn" required autocomplete="email"><button type="submit" class="btn btn--primary newsletter-submit">Đăng ký <?= icon('arrow-right', 'icon icon--xs') ?></button></div>
<p class="newsletter-message" id="newsletterMessage" role="status" aria-live="polite"></p><p class="newsletter-fineprint">Email nhập tại đây hiện chưa được lưu hoặc gửi đi.</p></form>
</div></section>