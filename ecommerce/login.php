<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

if (is_logged_in()) {
    redirect(is_admin() ? '/admin/dashboard.php' : '/account/index.php');
}

/* Resolve a safe local return path so guests who try to check out are sent
   back to checkout (or another protected page) after login.

   Supported input parameters (any of these is honored, first match wins):
     - ?next=<path>      canonical parameter
     - ?redirect=<path>  alias used by some integrations / docs
     - POST  next=<path> / redirect=<path> (preserved across form re-render)

   Only LOCAL relative paths are accepted (no absolute URLs) to prevent
   open-redirect attacks. */
$nextRaw = $_GET['next'] ?? $_GET['redirect'] ?? $_POST['next'] ?? $_POST['redirect'] ?? '';
$nextSafe = '';
if (is_string($nextRaw) && $nextRaw !== '') {
    if (preg_match('#^/[A-Za-z0-9._/?&=%-]*$#', $nextRaw) && strpos($nextRaw, '//') !== 0) {
        $nextSafe = $nextRaw;
    }
}

$errors = [];
$email = old('email');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    old_clear();

    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    old_set(['email' => $email]);

    if (!validate_email($email)) {
        $errors[] = 'Email không hợp lệ. Vui lòng nhập đúng định dạng email.';
    }
    if (!validate_required($password)) {
        $errors[] = 'Vui lòng nhập mật khẩu.';
    }

    if (!$errors) {
        try {
            $user = find_user_by_email($email);
            if (
                !$user
                || !password_verify($password, $user['password'])
                || $user['status'] !== USER_STATUS_ACTIVE
            ) {
                $errors[] = 'Thông tin đăng nhập không đúng. Vui lòng kiểm tra lại email và mật khẩu.';
            } else {
                store_user_session($user);
                old_clear();
                flash_set('success', 'Đăng nhập thành công!');
                /* Admins always go to admin. Customers go to ?next= when set
                   (e.g. /checkout.php after guest attempt). */
                if ($user['role'] === ROLE_ADMIN) {
                    redirect('/admin/dashboard.php');
                }
                if ($nextSafe !== '') {
                    redirect($nextSafe);
                }
                redirect('/account/index.php');
            }
        } catch (PDOException $e) {
            $errors[] = 'Cơ sở dữ liệu chưa sẵn sàng. Vui lòng nhập database.sql trước.';
        }
    }
}

$pageTitle = 'Đăng nhập';
require __DIR__ . '/includes/header.php';
?>
<div class="auth-layout">
    <div>
        <h1>Đăng nhập</h1>
        <p class="muted">Phiên đăng nhập được quản lý trên máy chủ; mật khẩu được bảo vệ bằng hàm băm bảo mật. Tài khoản demo có trong README.</p>
    </div>
    <div class="auth-card">
        <?php foreach ($errors as $error): ?>
            <p class="error"><?= e($error) ?></p>
        <?php endforeach; ?>
        <form class="form" method="post" action="">
            <?= csrf_field() ?>
            <?php if ($nextSafe !== ''): ?>
                <input type="hidden" name="next" value="<?= e($nextSafe) ?>">
            <?php endif; ?>
            <label>
                Email
                <input type="email" name="email" value="<?= e($email) ?>" required autocomplete="email">
            </label>
            <label>
                Mật khẩu
                <input type="password" name="password" required autocomplete="current-password">
            </label>
            <button class="btn" type="submit">Đăng nhập</button>
        </form>
        <p><a href="<?= e(url('register.php')) ?>">Tạo tài khoản khách hàng mới</a></p>
        <p><a href="<?= e(url('admin/login.php')) ?>">Đăng nhập quản trị</a></p>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
