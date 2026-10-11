<?php
/**
 * Site announcement bar (configurable text).
 */
$announcementText = 'MIỄN PHÍ VẬN CHUYỂN CHO ĐƠN HÀNG TỪ 2.000.000 ₫ · GIAO HÀNG TOÀN QUỐC';
?>
<div class="announcement-bar" role="region" aria-label="Thông báo">
    <div class="container announcement-inner">
        <p class="announcement-text"><?= e($announcementText) ?></p>
    </div>
</div>
