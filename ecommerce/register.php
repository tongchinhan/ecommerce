<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

if (is_logged_in()) {
    redirect('/account/index.php');
}

$errors = [];
$name = old('name');
$email = old('email');
$phone = old('phone');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    old_clear();

    $name = trim((string) ($_POST['name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $phone = trim((string) ($_POST['phone'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $confirm = (string) ($_POST['password_confirm'] ?? '');
    old_set(['name' => $name, 'email' => $email, 'phone' => $phone]);

    if (!validate_length($name, 2, 120)) {
        $errors[] = 'Họ tên phải từ 2 đến 120 ký tự.';
    }
    if (!validate_email($email)) {
        $errors[] = 'Email không hợp lệ. Vui lòng nhập đúng định dạng email.';
    }
    if (!validate_length($password, 8, 72)) {
        $errors[] = 'Mật khẩu phải có ít nhất 8 ký tự.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Xác nhận mật khẩu không khớp.';
    }

    if (!$errors) {
        try {
            if (find_user_by_email($email)) {
                $errors[] = 'Đã có tài khoản sử dụng email này.';
            } else {
                $stmt = db()->prepare(
                    'INSERT INTO users (name, email, password, phone, role, status)
                     VALUES (:name, :email, :password, :phone, :role, :status)'
                );
                $stmt->execute([
                    'name' => $name,
                    'email' => $email,
                    'password' => password_hash($password, PASSWORD_DEFAULT),
                    'phone' => $phone !== '' ? $phone : null,
                    'role' => ROLE_CUSTOMER,
                    'status' => USER_STATUS_ACTIVE,
                ]);
                $user = find_user_by_email($email);
                if ($user) {
                    store_user_session($user);
                    old_clear();
                    flash_set('success', 'Tạo tài khoản thành công! Bạn đã được đăng nhập.');
                    redirect('/account/index.php');
                }
            }
        } catch (PDOException $e) {
            $errors[] = 'Cơ sở dữ liệu chưa sẵn sàng. Vui lòng nhập database.sql trước.';
        }
    }
}

$pageTitle = 'Tạo tài khoản';
require __DIR__ . '/includes/header.php';
?>
<div class="auth-layout">
    <div>
        <h1>Tạo tài khoản</h1>
        <p class="muted">Tài khoản mới được tạo với vai trò khách hàng. Mật khẩu được lưu bằng PHP <code>password_hash()</code>.</p>
    </div>
    <div class="auth-card">
        <?php foreach ($errors as $error): ?>
            <p class="error"><?= e($error) ?></p>
        <?php endforeach; ?>
        <form class="form" method="post" action="">
            <?= csrf_field() ?>
            <label>
                Họ và tên
                <input type="text" name="name" value="<?= e($name) ?>" required>
            </label>
            <label>
                Email
                <input type="email" name="email" value="<?= e($email) ?>" required autocomplete="email">
            </label>
            <label>
                Điện thoại (tùy chọn)
                <input type="text" name="phone" value="<?= e($phone) ?>">
            </label>
            <label>
                Mật khẩu
                <input type="password" name="password" required autocomplete="new-password">
            </label>
            <label>
                Xác nhận mật khẩu
                <input type="password" name="password_confirm" required autocomplete="new-password">
            </label>
            <button class="btn" type="submit">Đăng ký</button>
        </form>
        <p><a href="<?= e(url('login.php')) ?>">Đã có tài khoản? Đăng nhập ngay</a></p>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
