<?php
/**
 * Storefront — Blog/News list.
 *
 * Lists all published posts newest first, with category filter support.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$pageTitle = 'Tin tức';
$pageDescription = 'Cập nhật tin tức, cảm hứng và hướng dẫn mới nhất từ NORD Home.';
$bodyClass = 'page-blog';

$categoryFilter = trim((string) ($_GET['category'] ?? ''));

try {
    if ($categoryFilter !== '') {
        $stmt = db()->prepare(
            "SELECT p.*, u.name AS author_name, pc.name AS category_name, pc.slug AS category_slug
             FROM posts p
             INNER JOIN users u ON u.id = p.user_id
             LEFT JOIN post_categories pc ON pc.id = p.post_category_id
             WHERE p.is_published = 1 AND pc.slug = :slug
             ORDER BY p.published_at DESC, p.created_at DESC
             LIMIT 100"
        );
        $stmt->execute(['slug' => $categoryFilter]);
    } else {
        $stmt = db()->query(
            "SELECT p.*, u.name AS author_name, pc.name AS category_name, pc.slug AS category_slug
             FROM posts p
             INNER JOIN users u ON u.id = p.user_id
             LEFT JOIN post_categories pc ON pc.id = p.post_category_id
             WHERE p.is_published = 1
             ORDER BY p.published_at DESC, p.created_at DESC
             LIMIT 100"
        );
    }
    $posts = $stmt->fetchAll() ?: [];

    $stmt = db()->query('SELECT * FROM post_categories ORDER BY name ASC');
    $postCategories = $stmt->fetchAll() ?: [];
} catch (PDOException $e) {
    $posts = [];
    $postCategories = [];
}

require __DIR__ . '/includes/header.php';
?>
<div class="container">
    <nav class="breadcrumb" aria-label="Đường dẫn">
        <ol class="breadcrumb-list">
            <li><a href="<?= e(url()) ?>">Trang chủ</a></li>
            <li aria-current="page">Tin tức</li>
        </ol>
    </nav>
</div>

<section class="blog-list">
    <div class="container">
        <header class="blog-list__header">
            <p class="eyebrow">Câu chuyện &amp; cảm hứng</p>
            <h1>Tin tức</h1>
            <p class="blog-list__intro">Cập nhật những bài viết mới nhất về phong cách sống, hướng dẫn sử dụng và câu chuyện đằng sau các sản phẩm NORD Home.</p>
        </header>

        <?php if (!empty($postCategories)): ?>
            <div class="blog-list__categories">
                <a class="blog-list__chip <?= $categoryFilter === '' ? 'is-active' : '' ?>" href="<?= e(url('blog.php')) ?>">Tất cả</a>
                <?php foreach ($postCategories as $pc): ?>
                    <a class="blog-list__chip <?= $categoryFilter === $pc['slug'] ? 'is-active' : '' ?>" href="<?= e(url('blog.php?category=' . urlencode((string) $pc['slug']))) ?>">
                        <?= e($pc['name']) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (empty($posts)): ?>
            <div class="blog-list__empty">Chưa có bài viết nào được xuất bản.</div>
        <?php else: ?>
            <div class="blog-list__grid">
                <?php foreach ($posts as $p):
                    $thumb = storefront_image((string) ($p['thumbnail'] ?? ''));
                    $tone = storefront_category_tone((int) ($p['post_category_id'] ?? 0));
                    if (!$thumb) {
                        $thumb = storefront_placeholder_svg((string) $p['title'], $tone);
                    }
                ?>
                    <article class="blog-card">
                        <a class="blog-card__media" href="<?= e(url('post.php?slug=' . urlencode((string) $p['slug']))) ?>">
                            <img src="<?= e($thumb) ?>" alt="<?= e($p['title']) ?>" loading="lazy">
                        </a>
                        <div class="blog-card__body">
                            <?php if (!empty($p['category_name'])): ?>
                                <span class="blog-card__cat"><?= e($p['category_name']) ?></span>
                            <?php endif; ?>
                            <h2 class="blog-card__title">
                                <a href="<?= e(url('post.php?slug=' . urlencode((string) $p['slug']))) ?>"><?= e($p['title']) ?></a>
                            </h2>
                            <?php if (!empty($p['excerpt'])): ?>
                                <p class="blog-card__excerpt"><?= e($p['excerpt']) ?></p>
                            <?php endif; ?>
                            <div class="blog-card__meta">
                                <?= e($p['author_name']) ?> · <?= e(format_date($p['published_at'] ?: $p['created_at'], 'd/m/Y')) ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>