<?php
/**
 * Journal / Blog section — 3 latest posts.
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/storefront-helpers.php';
require_once __DIR__ . '/home-data.php';
require_once __DIR__ . '/home-section.php';

$posts = hp_recent_posts(3);
if (empty($posts)) {
    return;
}
?>
<?php hp_section([
    'eyebrow'   => 'Từ Blog',
    'heading'   => 'Câu chuyện & cảm hứng',
    'subtext'   => 'Ghi chép về chất liệu, tay nghề và những chi tiết chậm rãi đằng sau những đồ vật đẹp trong cuộc sống hàng ngày.',
    'link'      => url('blog.php'),
    'link_text' => 'Xem tất cả bài viết',
    'id'        => 'journal',
], function() use ($posts) {
    foreach ($posts as $post):
        $postImage = storefront_image($post['thumbnail'] ?? null);
        $placeholder = storefront_placeholder_svg($post['title'] ?? '·', 'cool');
        $category = $post['category_name'] ?? 'Tin tức';
        $date = $post['published_at'] ?? $post['created_at'];
    ?>
    <article class="post-card">
        <a class="post-card-media" href="<?= e(storefront_post_link($post)) ?>" aria-label="<?= e($post['title']) ?>">
            <img class="post-card-image"
                 src="<?= e($postImage ?? $placeholder) ?>"
                 alt="<?= e($post['title']) ?>"
                 loading="lazy"
                 decoding="async"
                 onerror="this.onerror=null;this.src='<?= e($placeholder) ?>';">
        </a>
        <div class="post-card-body">
            <p class="post-card-meta">
                <span class="post-card-category"><?= e($category) ?></span>
                <span class="post-card-dot">·</span>
                <time datetime="<?= e(substr((string)$date, 0, 10)) ?>"><?= e(format_date((string)$date, 'd/m/Y')) ?></time>
            </p>
            <h3 class="post-card-title">
                <a href="<?= e(storefront_post_link($post)) ?>"><?= e($post['title']) ?></a>
            </h3>
            <?php if (!empty($post['excerpt'])): ?>
                <p class="post-card-excerpt"><?= e(storefront_excerpt((string) $post['excerpt'], 130)) ?></p>
            <?php endif; ?>
            <a class="post-card-link" href="<?= e(storefront_post_link($post)) ?>">
                Đọc thêm <?= icon('arrow-right', 'icon icon--xs') ?>
            </a>
        </div>
    </article>
    <?php endforeach;
}); ?>
