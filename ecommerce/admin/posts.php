<?php
/**
 * Admin — Posts (Blog/News) list.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/admin-auth.php';
require_once dirname(__DIR__) . '/includes/admin-data.php';
require_once dirname(__DIR__) . '/includes/admin-display.php';

$pageTitle = 'Bài viết';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (string) ($_POST['action'] ?? '') === 'delete') {
    csrf_require();
    $id = (int) ($_POST['id'] ?? 0);
    $result = admin_post_delete($id);
    flash_set($result['ok'] ? 'success' : 'error', $result['message']);
    redirect('/admin/posts.php');
}

$filter = (string) ($_GET['filter'] ?? '');
$posts = admin_posts_all($filter);

require dirname(__DIR__) . '/includes/admin-header.php';
?>
<div class="admin-card">
    <h1>Bài viết</h1>
    <p class="admin-card-sub">Quản lý các bài viết đăng trên trang Tin tức của cửa hàng.</p>

    <div class="admin-toolbar">
        <a class="admin-btn <?= $filter === '' ? 'admin-btn--primary' : '' ?>" href="<?= e(url('admin/posts.php')) ?>">Tất cả</a>
        <a class="admin-btn <?= $filter === 'published' ? 'admin-btn--primary' : '' ?>" href="<?= e(url('admin/posts.php?filter=published')) ?>">Đã xuất bản</a>
        <a class="admin-btn <?= $filter === 'draft' ? 'admin-btn--primary' : '' ?>" href="<?= e(url('admin/posts.php?filter=draft')) ?>">Bản nháp</a>
        <span style="flex:1;"></span>
        <a class="admin-btn admin-btn--primary" href="<?= e(url('admin/post-create.php')) ?>">+ Viết bài mới</a>
    </div>

    <?php if (empty($posts)): ?>
        <div class="admin-empty">Chưa có bài viết nào.</div>
    <?php else: ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Tiêu đề</th>
                        <th>Danh mục</th>
                        <th>Tác giả</th>
                        <th>Trạng thái</th>
                        <th>Ngày tạo</th>
                        <th class="col-actions">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($posts as $p): ?>
                    <tr>
                        <td class="col-product-cell">
                            <strong><?= e($p['title']) ?></strong>
                            <span class="secondary admin-mono"><?= e($p['slug']) ?></span>
                        </td>
                        <td><?= e($p['category_name'] ?? '—') ?></td>
                        <td><?= e($p['author_name']) ?></td>
                        <td>
                            <?php if ((int) $p['is_published'] === 1): ?>
                                <span class="admin-badge admin-badge--ok">Đã xuất bản</span>
                            <?php else: ?>
                                <span class="admin-badge admin-badge--warn">Bản nháp</span>
                            <?php endif; ?>
                        </td>
                        <td><?= e(format_date($p['created_at'])) ?></td>
                        <td class="col-actions">
                            <span class="admin-action-row">
                                <a class="admin-btn admin-btn--small admin-btn--secondary" href="<?= e(url('admin/post-edit.php?id=' . (int) $p['id'])) ?>">Sửa</a>
                                <?php if ((int) $p['is_published'] === 1): ?>
                                    <a class="admin-btn admin-btn--small" href="<?= e(url('post.php?slug=' . urlencode($p['slug']))) ?>" target="_blank" rel="noopener">Xem</a>
                                <?php endif; ?>
                                <form method="post" action="" onsubmit="return confirm('Xóa bài viết này?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                                    <button type="submit" class="admin-btn admin-btn--small admin-btn--danger">Xóa</button>
                                </form>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?php require dirname(__DIR__) . '/includes/admin-footer.php'; ?>