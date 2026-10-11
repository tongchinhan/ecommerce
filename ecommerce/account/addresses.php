<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/account-data.php';
require_once dirname(__DIR__) . '/includes/order-data.php';
account_require_customer('account/addresses.php');
$userId = (int) current_user()['id']; $errors = []; $editing = null;
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    account_csrf_require();
    $action = isset($_POST['action']) && is_string($_POST['action']) ? $_POST['action'] : '';
    $addressId = isset($_POST['address_id']) && is_string($_POST['address_id']) && ctype_digit($_POST['address_id']) ? (int) $_POST['address_id'] : 0;
    try {
        if ($action === 'delete') {
            db()->beginTransaction();
            $stmt = db()->prepare('SELECT is_default FROM addresses WHERE id = :id AND user_id = :uid FOR UPDATE'); $stmt->execute(['id' => $addressId, 'uid' => $userId]); $wasDefault = $stmt->fetchColumn();
            $delete = db()->prepare('DELETE FROM addresses WHERE id = :id AND user_id = :uid'); $delete->execute(['id' => $addressId, 'uid' => $userId]);
            if ($delete->rowCount() && (int) $wasDefault === 1) {
                $next = db()->prepare('SELECT id FROM addresses WHERE user_id = :uid ORDER BY id ASC LIMIT 1'); $next->execute(['uid' => $userId]); $nextId = $next->fetchColumn();
                if ($nextId !== false) { $set = db()->prepare('UPDATE addresses SET is_default = 1 WHERE id = :id AND user_id = :uid'); $set->execute(['id' => (int) $nextId, 'uid' => $userId]); }
            }
            db()->commit(); flash_set($delete->rowCount() ? 'success' : 'error', $delete->rowCount() ? 'Đã xóa địa chỉ.' : 'Không tìm thấy địa chỉ.'); redirect('account/addresses.php');
        }
        if ($action === 'set_default') {
            db()->beginTransaction();
            $check = db()->prepare('SELECT id FROM addresses WHERE id = :id AND user_id = :uid'); $check->execute(['id' => $addressId, 'uid' => $userId]);
            if (!$check->fetchColumn()) { db()->rollBack(); flash_set('error', 'Không tìm thấy địa chỉ.'); redirect('account/addresses.php'); }
            $clear = db()->prepare('UPDATE addresses SET is_default = 0 WHERE user_id = :uid'); $clear->execute(['uid' => $userId]);
            $set = db()->prepare('UPDATE addresses SET is_default = 1 WHERE id = :id AND user_id = :uid'); $set->execute(['id' => $addressId, 'uid' => $userId]);
            db()->commit(); flash_set('success', 'Đã đặt địa chỉ mặc định.'); redirect('account/addresses.php');
        }
        if ($action === 'save') {
            $data = ['full_name' => trim((string) ($_POST['full_name'] ?? '')), 'phone' => trim((string) ($_POST['phone'] ?? '')), 'address_line' => trim((string) ($_POST['address_line'] ?? '')), 'ward' => trim((string) ($_POST['ward'] ?? '')), 'district' => trim((string) ($_POST['district'] ?? '')), 'city' => trim((string) ($_POST['city'] ?? ''))];
            if (!validate_length($data['full_name'], 2, 120)) $errors[] = 'Họ và tên cần có từ 2 đến 120 ký tự.';
            if (!validate_vn_phone($data['phone'])) $errors[] = 'Số điện thoại không hợp lệ.';
            if (!validate_length($data['address_line'], 5, 255)) $errors[] = 'Vui lòng nhập địa chỉ từ 5 đến 255 ký tự.';
            if (!validate_length($data['city'], 2, 120)) $errors[] = 'Vui lòng nhập tỉnh / thành phố.';
            foreach (['ward' => 'Phường / Xã', 'district' => 'Quận / Huyện'] as $field => $label) if (mb_strlen($data[$field], 'UTF-8') > 120) $errors[] = $label . ' không được vượt quá 120 ký tự.';
            $isDefault = isset($_POST['is_default']) && $_POST['is_default'] === '1';
            if (!$errors) {
                db()->beginTransaction();
                $existing = null;
                if ($addressId > 0) { $find = db()->prepare('SELECT * FROM addresses WHERE id = :id AND user_id = :uid FOR UPDATE'); $find->execute(['id' => $addressId, 'uid' => $userId]); $existing = $find->fetch(); if (!$existing) throw new RuntimeException('Address not found'); }
                $count = db()->prepare('SELECT COUNT(*) FROM addresses WHERE user_id = :uid'); $count->execute(['uid' => $userId]);
                $isDefault = $isDefault || (int) $count->fetchColumn() === 0 || ($existing && (int) $existing['is_default'] === 1);
                if ($isDefault) { $clear = db()->prepare('UPDATE addresses SET is_default = 0 WHERE user_id = :uid'); $clear->execute(['uid' => $userId]); }
                if ($existing) {
                    $stmt = db()->prepare('UPDATE addresses SET full_name=:name, phone=:phone, address_line=:line, ward=:ward, district=:district, city=:city, is_default=:default WHERE id=:id AND user_id=:uid');
                    $stmt->execute(['name'=>$data['full_name'],'phone'=>$data['phone'],'line'=>$data['address_line'],'ward'=>$data['ward'] ?: null,'district'=>$data['district'] ?: null,'city'=>$data['city'],'default'=>$isDefault ? 1 : 0,'id'=>$addressId,'uid'=>$userId]);
                } else {
                    $stmt = db()->prepare('INSERT INTO addresses (user_id,full_name,phone,address_line,ward,district,city,is_default) VALUES (:uid,:name,:phone,:line,:ward,:district,:city,:default)');
                    $stmt->execute(['uid'=>$userId,'name'=>$data['full_name'],'phone'=>$data['phone'],'line'=>$data['address_line'],'ward'=>$data['ward'] ?: null,'district'=>$data['district'] ?: null,'city'=>$data['city'],'default'=>$isDefault ? 1 : 0]);
                }
                db()->commit(); flash_set('success', $existing ? 'Đã cập nhật địa chỉ.' : 'Đã thêm địa chỉ mới.'); redirect('account/addresses.php');
            }
            $editing = array_merge($data, ['id' => $addressId, 'is_default' => $isDefault]);
        }
    } catch (Throwable $e) {
        if (db()->inTransaction()) db()->rollBack();
        if (!$errors) $errors[] = 'Không thể lưu thay đổi địa chỉ. Vui lòng thử lại.';
    }
}
if (!$editing && isset($_GET['edit']) && is_string($_GET['edit']) && ctype_digit($_GET['edit'])) {
    try { $stmt = db()->prepare('SELECT * FROM addresses WHERE id = :id AND user_id = :uid LIMIT 1'); $stmt->execute(['id'=>(int)$_GET['edit'],'uid'=>$userId]); $editing = $stmt->fetch() ?: null; } catch (PDOException $e) { $editing = null; }
}
try { $stmt = db()->prepare('SELECT * FROM addresses WHERE user_id = :uid ORDER BY is_default DESC, id DESC'); $stmt->execute(['uid'=>$userId]); $addresses = $stmt->fetchAll(); } catch (PDOException $e) { $addresses = []; }
$pageTitle = 'Địa chỉ giao hàng'; $pageCss = 'account.css'; $bodyClass = 'page-account'; $accountActive = 'addresses';
require dirname(__DIR__) . '/includes/header.php';
?>
<div class="account-page"><nav class="breadcrumb" aria-label="Đường dẫn"><ol class="breadcrumb-list"><li><a href="<?= e(url()) ?>">Trang chủ</a></li><li><a href="<?= e(url('account/index.php')) ?>">Tài khoản</a></li><li aria-current="page">Địa chỉ</li></ol></nav>
<header class="account-page-heading"><h1>Địa chỉ giao hàng</h1><p>Quản lý các địa chỉ nhận hàng của bạn.</p></header><div class="account-layout">
<?php require dirname(__DIR__) . '/includes/account-nav.php'; ?><main class="account-content">
<section class="account-card"><h2><?= $editing ? 'Cập nhật địa chỉ' : 'Thêm địa chỉ mới' ?></h2><?php foreach ($errors as $error): ?><p class="account-notice"><?= e($error) ?></p><?php endforeach; ?>
<form class="account-form" method="post"><input type="hidden" name="action" value="save"><input type="hidden" name="address_id" value="<?= e((string) ($editing['id'] ?? '0')) ?>"><?= csrf_field() ?>
<div class="account-form-grid"><label class="account-field">Họ và tên *<input name="full_name" maxlength="120" required value="<?= e((string) ($editing['full_name'] ?? '')) ?>" autocomplete="name"></label><label class="account-field">Số điện thoại *<input name="phone" type="tel" maxlength="30" required value="<?= e((string) ($editing['phone'] ?? '')) ?>" autocomplete="tel"></label><label class="account-field account-field--full">Địa chỉ nhận hàng *<input name="address_line" maxlength="255" required value="<?= e((string) ($editing['address_line'] ?? '')) ?>" autocomplete="street-address"></label><label class="account-field">Phường / Xã<input name="ward" maxlength="120" value="<?= e((string) ($editing['ward'] ?? '')) ?>"></label><label class="account-field">Quận / Huyện<input name="district" maxlength="120" value="<?= e((string) ($editing['district'] ?? '')) ?>"></label><label class="account-field account-field--full">Tỉnh / Thành phố *<input name="city" maxlength="120" required value="<?= e((string) ($editing['city'] ?? '')) ?>" autocomplete="address-level1"></label></div>
<label class="account-field"><span><input type="checkbox" name="is_default" value="1" <?= !empty($editing['is_default']) || !$addresses ? 'checked' : '' ?>> Đặt làm địa chỉ mặc định</span></label><div class="account-form-actions"><button class="btn btn--primary" type="submit"><?= $editing ? 'Lưu thay đổi' : 'Thêm địa chỉ' ?></button><?php if ($editing): ?><a class="btn btn--ghost" href="<?= e(url('account/addresses.php')) ?>">Hủy</a><?php endif; ?></div></form></section>
<section class="account-card"><h2>Địa chỉ đã lưu</h2><?php if (!$addresses): ?><div class="account-empty"><p>Bạn chưa lưu địa chỉ giao hàng nào.</p></div><?php else: ?><div class="account-address-list"><?php foreach ($addresses as $address): ?><article class="account-address"><div><strong><?= e((string) $address['full_name']) ?><?php if ((int) $address['is_default'] === 1): ?><span class="account-address-default">Mặc định</span><?php endif; ?></strong><p><?= e((string) $address['phone']) ?><br><?= e((string) $address['address_line']) ?><?php if (!empty($address['ward'])): ?>, <?= e((string) $address['ward']) ?><?php endif; ?><?php if (!empty($address['district'])): ?>, <?= e((string) $address['district']) ?><?php endif; ?>, <?= e((string) $address['city']) ?></p></div><div class="account-inline-actions"><a class="btn btn--ghost btn--sm" href="<?= e(url('account/addresses.php?edit=' . (int) $address['id'])) ?>">Sửa</a><?php if ((int) $address['is_default'] !== 1): ?><form method="post"><input type="hidden" name="action" value="set_default"><input type="hidden" name="address_id" value="<?= (int) $address['id'] ?>"><?= csrf_field() ?><button class="btn btn--ghost btn--sm" type="submit">Đặt mặc định</button></form><?php endif; ?><form method="post" onsubmit="return confirm('Bạn có chắc muốn xóa địa chỉ này?')"><input type="hidden" name="action" value="delete"><input type="hidden" name="address_id" value="<?= (int) $address['id'] ?>"><?= csrf_field() ?><button class="btn btn--ghost btn--sm" type="submit">Xóa</button></form></div></article><?php endforeach; ?></div><?php endif; ?></section>
</main></div></div>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>