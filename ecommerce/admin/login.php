<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

/* Resolve a safe local return path so admins who tried to reach a
   specific admin page before login are sent back there after auth. */
$nextRaw = $_GET['next'] ?? $_POST['next'] ?? '';
$nextSafe = '';
if (is_string($nextRaw) && $nextRaw !== '') {
    if (preg_match('#^/admin/[A-Za-z0-9._/?&=%-]*$#', $nextRaw) && strpos($nextRaw, '//') !== 0) {
        $nextSafe = $nextRaw;
    }
}

if (is_logged_in()) {
    if (is_admin()) {
        /* If a return path was supplied and is safe, honor it; otherwise
           send admins to the standard dashboard entry. Admins never get
           redirected to the storefront or to /account. */
        redirect($nextSafe !== '' ? $nextSafe : '/admin/dashboard.php');
    }
    flash_set('error', 'Tài khoản khách hàng không thể truy cập khu vực quản trị.');
    redirect('/account/index.php');
}

$errors = [];
$email = old('email');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    old_clear();

    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    old_set(['email' => $email]);

    if (!validate_email($email) || !validate_required($password)) {
        $errors[] = 'Vui lòng nhập email và mật khẩu quản trị.';
    } else {
        try {
            $user = find_user_by_email($email);
            if (
                !$user
                || $user['role'] !== ROLE_ADMIN
                || $user['status'] !== USER_STATUS_ACTIVE
                || !password_verify($password, $user['password'])
            ) {
                $errors[] = 'Đăng nhập quản trị thất bại.';
            } else {
                store_user_session($user);
                old_clear();
                flash_set('success', 'Chào mừng trở lại.');
                /* Admins always land in /admin/* — either the originally
                   requested admin page (when safe) or the dashboard. */
                redirect($nextSafe !== '' ? $nextSafe : '/admin/dashboard.php');
            }
        } catch (PDOException $e) {
            $errors[] = 'Cơ sở dữ liệu chưa sẵn sàng. Vui lòng nhập database.sql trước.';
        }
    }
}

$pageTitle = 'Đăng nhập quản trị';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <link rel="stylesheet" href="<?= e(url('assets/css/admin.css')) ?>">
</head>
<body class="admin-login-body">
    <div class="admin-login-wrap">
        <div class="admin-login">
            <h1><?= e(APP_NAME) ?> · Quản trị</h1>
            <p class="admin-login-sub">Đăng nhập để truy cập khu vực quản trị.</p>
            <?php foreach (flash_get() as $flash): ?>
                <div class="flash flash-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
            <?php endforeach; ?>
            <?php if (!empty($errors)): ?>
                <div class="admin-login-errors">
                    <?php foreach ($errors as $error): ?>
                        <p><?= e($error) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <form method="post" action="">
                <?= csrf_field() ?>
                <?php if ($nextSafe !== ''): ?>
                    <input type="hidden" name="next" value="<?= e($nextSafe) ?>">
                <?php endif; ?>
                <input type="email" name="email" value="<?= e($email) ?>" placeholder="Email" required>
                <input type="password" name="password" placeholder="Mật khẩu" required>
                <button type="submit">Đăng nhập</button>
            </form>
            <p class="admin-login-foot"><a href="<?= e(url()) ?>">← Quay lại cửa hàng</a></p>
        </div>
    </div>
</body>
</html>
