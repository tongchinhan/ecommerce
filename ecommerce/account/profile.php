<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/account-data.php';
require_once dirname(__DIR__) . '/includes/order-data.php';
account_require_customer('account/profile.php');
$userId = (int) current_user()['id']; $errors = []; $passwordErrors = [];
try { $stmt = db()->prepare('SELECT id, name, email, phone FROM users WHERE id = :id AND role = :role LIMIT 1'); $stmt->execute(['id' => $userId, 'role' => ROLE_CUSTOMER]); $profile = $stmt->fetch() ?: []; } catch (PDOException $e) { $profile = []; $errors[] = 'Không thể tải thông tin tài khoản.'; }
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    account_csrf_require();
    $action = isset($_POST['action']) && is_string($_POST['action']) ? $_POST['action'] : '';
    if ($action === 'save_profile') {
        $name = trim((string) ($_POST['name'] ?? '')); $phone = trim((string) ($_POST['phone'] ?? ''));
        if (!validate_length($name, 2, 120)) $errors[] = 'Họ và tên cần có từ 2 đến 120 ký tự.';
        if ($phone !== '' && !validate_vn_phone($phone)) $errors[] = 'Số điện thoại không hợp lệ.';
        if (!$errors) {
            try {
                $stmt = db()->prepare('UPDATE users SET name = :name, phone = :phone WHERE id = :id AND role = :role');
                $stmt->execute(['name' => $name, 'phone' => $phone !== '' ? $phone : null, 'id' => $userId, 'role' => ROLE_CUSTOMER]);
                $_SESSION['user']['name'] = $name;
                flash_set('success', 'Đã cập nhật thông tin cá nhân.');
                redirect('account/profile.php');
            } catch (PDOException $e) { $errors[] = 'Không thể lưu thông tin. Vui lòng thử lại.'; }
        }
        $profile['name'] = $name; $profile['phone'] = $phone;
    } elseif ($action === 'change_password') {
        $currentPassword = (string) ($_POST['current_password'] ?? ''); $newPassword = (string) ($_POST['new_password'] ?? ''); $confirmPassword = (string) ($_POST['confirm_password'] ?? '');
        try { $stmt = db()->prepare('SELECT password FROM users WHERE id = :id AND role = :role LIMIT 1'); $stmt->execute(['id' => $userId, 'role' => ROLE_CUSTOMER]); $hash = (string) ($stmt->fetchColumn() ?: ''); }
        catch (PDOException $e) { $hash = ''; }
        if ($currentPassword === '' || $hash === '' || !password_verify($currentPassword, $hash)) $passwordErrors[] = 'Mật khẩu hiện tại không đúng.';
        if (strlen($newPassword) < 8 || strlen($newPassword) > 200) $passwordErrors[] = 'Mật khẩu mới cần có ít nhất 8 ký tự.';
        if ($newPassword !== $confirmPassword) $passwordErrors[] = 'Xác nhận mật khẩu mới không khớp.';
        if (!$passwordErrors) {
            try {
                $stmt = db()->prepare('UPDATE users SET password = :password WHERE id = :id AND role = :role');
                $stmt->execute(['password' => password_hash($newPassword, PASSWORD_DEFAULT), 'id' => $userId, 'role' => ROLE_CUSTOMER]);
                session_regenerate_id(true);
                flash_set('success', 'Mật khẩu đã được cập nhật.');
                redirect('account/profile.php');
            } catch (PDOException $e) { $passwordErrors[] = 'Không thể cập nhật mật khẩu. Vui lòng thử lại.'; }
        }
    }
}
$pageTitle = 'Thông tin cá nhân'; $pageCss = 'account.css'; $bodyClass = 'page-account'; $accountActive = 'profile';
require dirname(__DIR__) . '/includes/header.php';
?>
<div class="account-page"><nav class="breadcrumb" aria-label="Đường dẫn"><ol class="breadcrumb-list"><li><a href="<?= e(url()) ?>">Trang chủ</a></li><li><a href="<?= e(url('account/index.php')) ?>">Tài khoản</a></li><li aria-current="page">Thông tin cá nhân</li></ol></nav>
<header class="account-page-heading"><h1>Thông tin cá nhân</h1><p>Cập nhật thông tin liên hệ và bảo mật tài khoản.</p></header><div class="account-layout">
<?php require dirname(__DIR__) . '/includes/account-nav.php'; ?><main class="account-content">
<section class="account-card"><h2>Thông tin hồ sơ</h2><?php foreach ($errors as $error): ?><p class="account-notice"><?= e($error) ?></p><?php endforeach; ?>
<form class="account-form" method="post"><input type="hidden" name="action" value="save_profile"><?= csrf_field() ?>
<div class="account-form-grid"><label class="account-field">Họ và tên *<input name="name" value="<?= e((string) ($profile['name'] ?? '')) ?>" minlength="2" maxlength="120" required autocomplete="name"></label><label class="account-field">Số điện thoại<input name="phone" type="tel" value="<?= e((string) ($profile['phone'] ?? '')) ?>" maxlength="30" autocomplete="tel"></label><label class="account-field account-field--full">Email<input type="email" value="<?= e((string) ($profile['email'] ?? current_user()['email'])) ?>" readonly aria-describedby="emailHelp"><small id="emailHelp" class="muted">Email đăng nhập không thể thay đổi tại đây.</small></label></div>
<div class="account-form-actions"><button class="btn btn--primary" type="submit">Lưu thông tin</button></div></form></section>
<section class="account-card"><h2>Đổi mật khẩu</h2><?php foreach ($passwordErrors as $error): ?><p class="account-notice"><?= e($error) ?></p><?php endforeach; ?>
<form class="account-form" method="post"><input type="hidden" name="action" value="change_password"><?= csrf_field() ?><label class="account-field">Mật khẩu hiện tại *<input type="password" name="current_password" required autocomplete="current-password"></label><div class="account-form-grid"><label class="account-field">Mật khẩu mới *<input type="password" name="new_password" minlength="8" maxlength="200" required autocomplete="new-password"></label><label class="account-field">Nhập lại mật khẩu mới *<input type="password" name="confirm_password" minlength="8" maxlength="200" required autocomplete="new-password"></label></div><div class="account-form-actions"><button class="btn btn--primary" type="submit">Cập nhật mật khẩu</button></div></form></section>
</main></div></div>
<?php require dirname(__DIR__) . '/includes/footer.php'; ?>