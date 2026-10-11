<?php
/**
 * Admin — Customer management.
 *
 * Passwords are NEVER selected from the database — only safe display
 * columns are pulled, so there is no risk of accidentally exposing
 * a password hash in the rendered UI.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/admin-auth.php';
require_once dirname(__DIR__) . '/includes/admin-data.php';
require_once dirname(__DIR__) . '/includes/admin-display.php';

$pageTitle = 'Khách hàng';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'set_status') {
        $id = (int) ($_POST['id'] ?? 0);
        $status = (string) ($_POST['status'] ?? '');
        $result = admin_customer_set_status($id, $status);
        flash_set($result['ok'] ? 'success' : 'error', $result['message']);
        redirect('/admin/customers.php');
    }
}

$customers = admin_customers_all();

require dirname(__DIR__) . '/includes/admin-header.php';
?>
<div class="admin-card">
    <h1>Khách hàng</h1>
    <p class="admin-card-sub">Danh sách khách hàng đã đăng ký. Mật khẩu chỉ được lưu dưới dạng mã băm và không hiển thị trong trang này.</p>

    <?php if (empty($customers)): ?>
        <div class="admin-empty">Chưa có khách hàng nào.</div>
    <?php else: ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Họ tên</th>
                        <th>Email</th>
                        <th>Số điện thoại</th>
                        <th class="col-num">Đơn hàng</th>
                        <th class="col-num">Tổng chi tiêu</th>
                        <th>Trạng thái</th>
                        <th>Ngày đăng ký</th>
                        <th class="col-actions">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($customers as $u): ?>
                    <tr>
                        <td class="col-product-cell"><strong><?= e($u['name']) ?></strong></td>
                        <td><?= e($u['email']) ?></td>
                        <td><?= e($u['phone'] ?? '') ?></td>
                        <td class="col-num"><?= (int) $u['order_count'] ?></td>
                        <td class="col-num"><strong><?= e(format_vnd($u['lifetime_total'])) ?></strong></td>
                        <td><span class="admin-badge admin-badge--<?= admin_user_status_badge($u['status']) ?>"><?= e(admin_user_status_label($u['status'])) ?></span></td>
                        <td><?= e(format_date($u['created_at'])) ?></td>
                        <td class="col-actions">
                            <?php if ($u['status'] === USER_STATUS_ACTIVE): ?>
                                <form method="post" action="" onsubmit="return confirm('Tạm ngưng tài khoản này?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="set_status">
                                    <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                                    <input type="hidden" name="status" value="<?= USER_STATUS_INACTIVE ?>">
                                    <button type="submit" class="admin-btn admin-btn--small">Tạm ngưng</button>
                                </form>
                            <?php else: ?>
                                <form method="post" action="" onsubmit="return confirm('Kích hoạt lại tài khoản?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="set_status">
                                    <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                                    <input type="hidden" name="status" value="<?= USER_STATUS_ACTIVE ?>">
                                    <button type="submit" class="admin-btn admin-btn--small admin-btn--primary">Kích hoạt</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?php require dirname(__DIR__) . '/includes/admin-footer.php'; ?>