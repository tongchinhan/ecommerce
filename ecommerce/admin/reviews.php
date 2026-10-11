<?php
/**
 * Admin — Review moderation.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/admin-auth.php';
require_once dirname(__DIR__) . '/includes/admin-data.php';
require_once dirname(__DIR__) . '/includes/admin-display.php';

$pageTitle = 'Đánh giá';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'approve') {
        $id = (int) ($_POST['id'] ?? 0);
        $result = admin_review_set_approved($id, true);
        flash_set($result['ok'] ? 'success' : 'error', $result['message']);
    } elseif ($action === 'unapprove') {
        $id = (int) ($_POST['id'] ?? 0);
        $result = admin_review_set_approved($id, false);
        flash_set($result['ok'] ? 'success' : 'error', $result['message']);
    } elseif ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        $result = admin_review_delete($id);
        flash_set($result['ok'] ? 'success' : 'error', $result['message']);
    }
    redirect('/admin/reviews.php' . (isset($_GET['filter']) ? '?filter=' . urlencode((string) $_GET['filter']) : ''));
}

$filter = (string) ($_GET['filter'] ?? '');
$reviews = admin_reviews_all($filter);

require dirname(__DIR__) . '/includes/admin-header.php';
?>
<div class="admin-card">
    <h1>Đánh giá</h1>
    <p class="admin-card-sub">Kiểm duyệt các đánh giá khách hàng gửi cho sản phẩm. Đánh giá được duyệt sẽ hiển thị trên trang sản phẩm.</p>

    <div class="admin-toolbar">
        <a class="admin-btn <?= $filter === '' ? 'admin-btn--primary' : '' ?>" href="<?= e(url('admin/reviews.php')) ?>">Tất cả</a>
        <a class="admin-btn <?= $filter === 'pending'  ? 'admin-btn--primary' : '' ?>" href="<?= e(url('admin/reviews.php?filter=pending')) ?>">Chờ duyệt</a>
        <a class="admin-btn <?= $filter === 'approved' ? 'admin-btn--primary' : '' ?>" href="<?= e(url('admin/reviews.php?filter=approved')) ?>">Đã duyệt</a>
    </div>

    <?php if (empty($reviews)): ?>
        <div class="admin-empty">Chưa có đánh giá nào phù hợp.</div>
    <?php else: ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Sản phẩm</th>
                        <th>Khách hàng</th>
                        <th>Rating</th>
                        <th>Bình luận</th>
                        <th>Ngày gửi</th>
                        <th>Trạng thái</th>
                        <th class="col-actions">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($reviews as $rv): ?>
                    <tr>
                        <td>
                            <a href="<?= e(url('product.php?id=' . (int) $rv['product_id'])) ?>" target="_blank" rel="noopener">
                                <strong><?= e($rv['product_name']) ?></strong>
                            </a>
                        </td>
                        <td class="col-product-cell">
                            <strong><?= e($rv['user_name']) ?></strong>
                            <span class="secondary"><?= e($rv['user_email']) ?></span>
                        </td>
                        <td>
                            <?php $rating = (int) $rv['rating']; ?>
                            <span class="admin-stars" aria-label="<?= $rating ?>/5">
                                <?php for ($i = 0; $i < 5; $i++): ?>
                                    <?php if ($i < $rating): ?>★<?php else: ?><span class="admin-stars-empty">★</span><?php endif; ?>
                                <?php endfor; ?>
                                <span class="admin-stars-meta"><?= $rating ?>/5</span>
                            </span>
                        </td>
                        <td style="max-width:380px;"><?= e(storefront_excerpt((string) ($rv['comment'] ?? ''), 200)) ?></td>
                        <td><?= e(format_date($rv['created_at'])) ?></td>
                        <td>
                            <?php if ((int) $rv['is_approved'] === 1): ?>
                                <span class="admin-badge admin-badge--ok">Đã duyệt</span>
                            <?php else: ?>
                                <span class="admin-badge admin-badge--warn">Chờ duyệt</span>
                            <?php endif; ?>
                        </td>
                        <td class="col-actions">
                            <span class="admin-action-row">
                                <?php if ((int) $rv['is_approved'] === 1): ?>
                                    <form method="post" action="">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="unapprove">
                                        <input type="hidden" name="id" value="<?= (int) $rv['id'] ?>">
                                        <button type="submit" class="admin-btn admin-btn--small">Ẩn</button>
                                    </form>
                                <?php else: ?>
                                    <form method="post" action="">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="approve">
                                        <input type="hidden" name="id" value="<?= (int) $rv['id'] ?>">
                                        <button type="submit" class="admin-btn admin-btn--small admin-btn--primary">Duyệt</button>
                                    </form>
                                <?php endif; ?>
                                <form method="post" action="" onsubmit="return confirm('Xóa đánh giá này?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int) $rv['id'] ?>">
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