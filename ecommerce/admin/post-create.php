<?php
/**
 * Admin — Create new post.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/admin-auth.php';
require_once dirname(__DIR__) . '/includes/admin-data.php';
require_once dirname(__DIR__) . '/includes/admin-display.php';

$pageTitle = 'Tạo bài viết';

$errors = [];
$values = [
    'title'             => '',
    'slug'              => '',
    'excerpt'           => '',
    'content'           => '',
    'thumbnail'         => '',
    'post_category_id'  => 0,
    'is_published'      => 0,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $values = [
        'title'             => trim((string) ($_POST['title'] ?? '')),
        'slug'              => trim((string) ($_POST['slug'] ?? '')),
        'excerpt'           => trim((string) ($_POST['excerpt'] ?? '')),
        'content'           => trim((string) ($_POST['content'] ?? '')),
        'thumbnail'         => trim((string) ($_POST['thumbnail'] ?? '')),
        'post_category_id'  => (int) ($_POST['post_category_id'] ?? 0),
        'is_published'      => !empty($_POST['is_published']) ? 1 : 0,
    ];
    $authorId = (int) (current_user()['id'] ?? 0);
    $result = admin_post_save($values, null, $authorId);
    if ($result['errors'] !== []) {
        $errors = $result['errors'];
    } else {
        flash_set('success', 'Đã tạo bài viết.');
        redirect('/admin/post-edit.php?id=' . $result['id']);
    }
}

$postCategories = admin_post_categories_all();

require dirname(__DIR__) . '/includes/admin-header.php';
?>
<div class="admin-card">
    <h1>Tạo bài viết</h1>
    <p class="admin-card-sub">Điền nội dung cho bài viết mới. Tick "Xuất bản" để hiển thị trên trang Tin tức.</p>

    <?php if ($errors !== []): ?>
        <div class="flash flash-error">
            <?php foreach ($errors as $err): ?>
                <div><?= e($err) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form class="admin-form" method="post" action="">
        <?= csrf_field() ?>

        <div class="admin-form-row admin-form-row--2">
            <label>
                <strong>Tiêu đề *</strong>
                <input type="text" name="title" value="<?= e($values['title']) ?>" required maxlength="220">
            </label>
            <label>
                <strong>Slug</strong>
                <input type="text" name="slug" value="<?= e($values['slug']) ?>" placeholder="Bỏ trống để tự sinh" maxlength="240">
            </label>
        </div>

        <label>
            <strong>Mô tả ngắn (excerpt)</strong>
            <textarea name="excerpt" rows="3" maxlength="500"><?= e($values['excerpt']) ?></textarea>
        </label>

        <label>
            <strong>Nội dung *</strong>
            <textarea name="content" rows="14"><?= e($values['content']) ?></textarea>
        </label>

        <div class="admin-form-row admin-form-row--2">
            <label>
                <strong>Danh mục bài viết</strong>
                <select name="post_category_id">
                    <option value="0">— Không —</option>
                    <?php foreach ($postCategories as $pc): ?>
                        <option value="<?= (int) $pc['id'] ?>" <?= (int) $values['post_category_id'] === (int) $pc['id'] ? 'selected' : '' ?>>
                            <?= e($pc['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <strong>Đường dẫn hình thu nhỏ</strong>
                <input type="text" name="thumbnail" value="<?= e($values['thumbnail']) ?>" placeholder="vd: uploads/posts/article.jpg">
            </label>
        </div>

        <label class="admin-check">
            <input type="checkbox" name="is_published" value="1" <?= $values['is_published'] ? 'checked' : '' ?>>
            Xuất bản ngay
        </label>

        <div class="admin-form-actions">
            <button type="submit" class="admin-btn admin-btn--primary">Tạo bài viết</button>
            <a href="<?= e(url('admin/posts.php')) ?>" class="admin-btn admin-btn--ghost">Quay lại</a>
        </div>
    </form>
</div>
<?php require dirname(__DIR__) . '/includes/admin-footer.php'; ?>