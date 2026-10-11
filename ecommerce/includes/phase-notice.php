<?php
/**
 * Thông báo cho trang đang trong giai đoạn xây dựng.
 */
?>
<div class="panel">
    <h1><?= e($pageHeading ?? 'Sắp ra mắt') ?></h1>
    <p><?= e($pageNote ?? 'Trang này được dành riêng. Giao diện đầy đủ sẽ được xây dựng sau khi nền tảng được xác minh.') ?></p>
</div>
