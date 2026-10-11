<?php
/**
 * Storefront — Single post detail.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$slug = trim((string) ($_GET['slug'] ?? ''));
$post = null;
$related = [];

if ($slug !== '') {
    try {
        $stmt = db()->prepare(
            "SELECT p.*, u.name AS author_name, pc.name AS category_name, pc.slug AS category_slug
             FROM posts p
             INNER JOIN users u ON u.id = p.user_id
             LEFT JOIN post_categories pc ON pc.id = p.post_category_id
             WHERE p.slug = :slug AND p.is_published = 1
             LIMIT 1"
        );
        $stmt->execute(['slug' => $slug]);
        $post = $stmt->fetch() ?: null;

        if ($post) {
            $stmt = db()->prepare(
                "SELECT p.*, u.name AS author_name
                 FROM posts p
                 INNER JOIN users u ON u.id = p.user_id
                 WHERE p.is_published = 1 AND p.id <> :id
                 ORDER BY p.published_at DESC, p.created_at DESC
                 LIMIT 3"
            );
            $stmt->execute(['id' => (int) $post['id']]);
            $related = $stmt->fetchAll() ?: [];
        }
    } catch (PDOException $e) {
        $post = null;
    }
}

if (!$post) {
    http_response_code(404);
    $pageTitle = 'Không tìm thấy bài viết';
    require __DIR__ . '/includes/header.php';
    echo '<div class="container" style="padding:80px 0;text-align:center;">';
    echo '<h1>Không tìm thấy bài viết</h1>';
    echo '<p style="color:var(--muted);">Bài viết này không tồn tại hoặc chưa được xuất bản.</p>';
    echo '<a class="btn btn--ghost" href="' . e(url('blog.php')) . '">← Quay lại trang Tin tức</a>';
    echo '</div>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$pageTitle = (string) $post['title'];
$pageDescription = (string) ($post['excerpt'] ?? '');
$bodyClass = 'page-blog page-post';

require __DIR__ . '/includes/header.php';
?>
<div class="container">
    <nav class="breadcrumb" aria-label="Đường dẫn">
        <ol class="breadcrumb-list">
            <li><a href="<?= e(url()) ?>">Trang chủ</a></li>
            <li><a href="<?= e(url('blog.php')) ?>">Tin tức</a></li>
            <li aria-current="page"><?= e($post['title']) ?></li>
        </ol>
    </nav>
</div>

<article class="post-detail">
    <div class="container">
        <header class="post-detail__header">
            <?php if (!empty($post['category_name'])): ?>
                <span class="post-detail__cat"><?= e($post['category_name']) ?></span>
            <?php endif; ?>
            <h1><?= e($post['title']) ?></h1>
            <div class="post-detail__meta">
                <?= e($post['author_name']) ?> · <?= e(format_date($post['published_at'] ?: $post['created_at'], 'd/m/Y')) ?>
            </div>
        </header>

        <?php
            $thumb = storefront_image((string) ($post['thumbnail'] ?? ''));
            $tone = storefront_category_tone((int) ($post['post_category_id'] ?? 0));
            if (!$thumb) {
                $thumb = storefront_placeholder_svg((string) $post['title'], $tone);
            }
        ?>
        <div class="post-detail__hero">
            <img src="<?= e($thumb) ?>" alt="<?= e($post['title']) ?>">
        </div>

        <?php if (!empty($post['excerpt'])): ?>
            <p class="post-detail__excerpt"><?= e($post['excerpt']) ?></p>
        <?php endif; ?>

        <div class="post-detail__content">
            <?= nl2br(e((string) $post['content'])) ?>
        </div>
    </div>
</article>

<?php if (!empty($related)): ?>
<section class="post-related">
    <div class="container">
        <h2 class="post-related__title">Bài viết khác</h2>
        <div class="blog-list__grid">
            <?php foreach ($related as $rp):
                $rthumb = storefront_image((string) ($rp['thumbnail'] ?? ''));
                $rtone = storefront_category_tone((int) ($rp['post_category_id'] ?? 0));
                if (!$rthumb) {
                    $rthumb = storefront_placeholder_svg((string) $rp['title'], $rtone);
                }
            ?>
                <article class="blog-card">
                    <a class="blog-card__media" href="<?= e(url('post.php?slug=' . urlencode((string) $rp['slug']))) ?>">
                        <img src="<?= e($rthumb) ?>" alt="<?= e($rp['title']) ?>" loading="lazy">
                    </a>
                    <div class="blog-card__body">
                        <h2 class="blog-card__title">
                            <a href="<?= e(url('post.php?slug=' . urlencode((string) $rp['slug']))) ?>"><?= e($rp['title']) ?></a>
                        </h2>
                        <div class="blog-card__meta">
                            <?= e(format_date($rp['published_at'] ?: $rp['created_at'], 'd/m/Y')) ?>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>