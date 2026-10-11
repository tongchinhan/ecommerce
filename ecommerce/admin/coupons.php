<?php
/**
 * Admin — Coupon CRUD.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/admin-auth.php';
require_once dirname(__DIR__) . '/includes/admin-data.php';
require_once dirname(__DIR__) . '/includes/admin-display.php';

$pageTitle = 'Mã giảm giá';

$editId = (int) ($_GET['edit'] ?? 0);
$editing = $editId > 0 ? admin_coupon_find($editId) : null;

$errors = [];
$values = [
    'code'         => '',
    'type'         => COUPON_PERCENT,
    'value'        => '',
    'min_order'    => '0',
    'max_discount' => '',
    'usage_limit'  => '',
    'starts_at'    => '',
    'expires_at'   => '',
    'is_active'    => 1,
];

if ($editing) {
    $values = [
        'code'         => (string) $editing['code'],
        'type'         => (string) $editing['type'],
        'value'        => (string) $editing['value'],
        'min_order'    => (string) $editing['min_order'],
        'max_discount' => $editing['max_discount'] !== null ? (string) $editing['max_discount'] : '',
        'usage_limit'  => $editing['usage_limit'] !== null ? (string) $editing['usage_limit'] : '',
        'starts_at'    => $editing['starts_at'] ? date('Y-m-d\TH:i', strtotime((string) $editing['starts_at'])) : '',
        'expires_at'   => $editing['expires_at'] ? date('Y-m-d\TH:i', strtotime((string) $editing['expires_at'])) : '',
        'is_active'    => (int) $editing['is_active'],
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        $result = admin_coupon_delete($id);
        flash_set($result['ok'] ? 'success' : 'error', $result['message']);
        redirect('/admin/coupons.php');
    }
    $values = [
        'code'         => trim((string) ($_POST['code'] ?? '')),
        'type'         => (string) ($_POST['type'] ?? COUPON_PERCENT),
        'value'        => (string) ($_POST['value'] ?? '0'),
        'min_order'    => (string) ($_POST['min_order'] ?? '0'),
        'max_discount' => (string) ($_POST['max_discount'] ?? ''),
        'usage_limit'  => (string) ($_POST['usage_limit'] ?? ''),
        'starts_at'    => (string) ($_POST['starts_at'] ?? ''),
        'expires_at'   => (string) ($_POST['expires_at'] ?? ''),
        'is_active'    => !empty($_POST['is_active']) ? 1 : 0,
    ];
    $saveId = (int) ($_POST['id'] ?? 0);
    $result = admin_coupon_save($values, $saveId > 0 ? $saveId : null);
    if ($result['errors'] !== []) {
        $errors = $result['errors'];
        $editId = $saveId;
    } else {
        flash_set('success', $saveId > 0 ? 'Đã cập nhật mã giảm giá.' : 'Đã tạo mã giảm giá mới.');
        redirect('/admin/coupons.php');
    }
}

$coupons = admin_coupons_all();

require dirname(__DIR__) . '/includes/admin-header.php';
?>
<div class="admin-card">
    <h1>Mã giảm giá</h1>
    <p class="admin-card-sub">Tạo và quản lý các mã giảm giá áp dụng tại checkout.</p>

    <form class="admin-form" method="post" action="" style="margin-bottom:24px;">
        <?= csrf_field() ?>
        <h2><?= $editing ? 'Sửa mã' : 'Tạo mã mới' ?></h2>
        <?php if ($errors !== []): ?>
            <div class="flash flash-error">
                <?php foreach ($errors as $err): ?>
                    <div><?= e($err) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <?php if ($editing): ?>
            <input type="hidden" name="id" value="<?= (int) $editing['id'] ?>">
        <?php endif; ?>
        <div class="admin-form-row admin-form-row--2">
            <label>
                <strong>Mã *</strong>
                <input type="text" name="code" value="<?= e($values['code']) ?>" required maxlength="40" style="text-transform:uppercase;">
            </label>
            <label>
                <strong>Loại</strong>
                <select name="type">
                    <option value="percent" <?= $values['type'] === COUPON_PERCENT ? 'selected' : '' ?>>Phần trăm (%)</option>
                    <option value="fixed"   <?= $values['type'] === COUPON_FIXED   ? 'selected' : '' ?>>Số tiền cố định (₫)</option>
                </select>
            </label>
        </div>
        <div class="admin-form-row admin-form-row--3">
            <label>
                <strong>Giá trị giảm *</strong>
                <input type="number" name="value" min="0" step="0.01" value="<?= e($values['value']) ?>" required>
            </label>
            <label>
                <strong>Đơn tối thiểu (₫)</strong>
                <input type="number" name="min_order" min="0" step="1000" value="<?= e($values['min_order']) ?>">
            </label>
            <label>
                <strong>Giảm tối đa (₫)</strong>
                <input type="number" name="max_discount" min="0" step="1000" value="<?= e($values['max_discount']) ?>" placeholder="(tuỳ chọn)">
            </label>
        </div>
        <div class="admin-form-row admin-form-row--3">
            <label>
                <strong>Giới hạn lượt dùng</strong>
                <input type="number" name="usage_limit" min="0" step="1" value="<?= e($values['usage_limit']) ?>" placeholder="(không giới hạn)">
            </label>
            <label>
                <strong>Bắt đầu</strong>
                <input type="datetime-local" name="starts_at" value="<?= e($values['starts_at']) ?>">
            </label>
            <label>
                <strong>Hết hạn</strong>
                <input type="datetime-local" name="expires_at" value="<?= e($values['expires_at']) ?>">
            </label>
        </div>
        <label class="admin-check">
            <input type="checkbox" name="is_active" value="1" <?= $values['is_active'] ? 'checked' : '' ?>>
            Kích hoạt
        </label>
        <div class="admin-form-actions">
            <button type="submit" class="admin-btn admin-btn--primary"><?= $editing ? 'Lưu thay đổi' : 'Tạo mã' ?></button>
            <?php if ($editing): ?>
                <a href="<?= e(url('admin/coupons.php')) ?>" class="admin-btn admin-btn--ghost">Huỷ</a>
            <?php endif; ?>
        </div>
    </form>

    <?php if (empty($coupons)): ?>
        <div class="admin-empty">Chưa có mã giảm giá nào.</div>
    <?php else: ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Mã</th>
                        <th>Loại</th>
                        <th class="col-num">Giá trị</th>
                        <th class="col-num">Đơn tối thiểu</th>
                        <th class="col-num">Đã dùng / Giới hạn</th>
                        <th>Hiệu lực</th>
                        <th>Trạng thái</th>
                        <th class="col-actions">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($coupons as $cp): ?>
                    <tr>
                        <td><strong><span class="admin-mono"><?= e($cp['code']) ?></span></strong></td>
                        <td><?= $cp['type'] === COUPON_PERCENT ? 'Phần trăm' : 'Số tiền' ?></td>
                        <td class="col-num"><strong>
                            <?= $cp['type'] === COUPON_PERCENT ? rtrim(rtrim((string) $cp['value'], '0'), '.') . '%' : e(format_vnd($cp['value'])) ?>
                        </strong></td>
                        <td class="col-num"><?= e(format_vnd($cp['min_order'])) ?></td>
                        <td class="col-num"><?= (int) $cp['used_count'] ?> / <?= $cp['usage_limit'] !== null ? (int) $cp['usage_limit'] : '∞' ?></td>
                        <td>
                            <?php if ($cp['starts_at']): ?>
                                <?= e(format_date((string) $cp['starts_at'], 'd/m/Y H:i')) ?>
                            <?php endif; ?>
                            <?php if ($cp['expires_at']): ?>
                                <div class="secondary admin-mono">→ <?= e(format_date((string) $cp['expires_at'], 'd/m/Y H:i')) ?></div>
                            <?php endif; ?>
                            <?php if (!$cp['starts_at'] && !$cp['expires_at']): ?>
                                <span class="admin-muted">Không giới hạn</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ((int) $cp['is_active'] === 1): ?>
                                <span class="admin-badge admin-badge--ok">Hoạt động</span>
                            <?php else: ?>
                                <span class="admin-badge admin-badge--mute">Tắt</span>
                            <?php endif; ?>
                        </td>
                        <td class="col-actions">
                            <span class="admin-action-row">
                                <a class="admin-btn admin-btn--small admin-btn--secondary" href="<?= e(url('admin/coupons.php?edit=' . (int) $cp['id'])) ?>">Sửa</a>
                                <form method="post" action="" onsubmit="return confirm('Xóa mã giảm giá này? Nếu đã có lượt dùng, mã chỉ bị ẩn.');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int) $cp['id'] ?>">
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