<?php

/**
 * AUTH.PHP - TOÀN BỘ XÁC THỰC TÀI KHOẢN TRONG MỘT FILE
 *
 * `action=login`    : hiển thị/xử lý đăng nhập.
 * `action=register` : hiển thị/xử lý đăng ký customer.
 * `action=logout`   : đăng xuất bằng POST.
 *
 * Mục đích gom file: khi báo cáo chỉ cần trình bày một module "Tài khoản"
 * thay vì ba màn hình xác thực rời nhau.
 */

require_once 'includes/bootstrap.php';

$action = (string) ($_GET['action'] ?? $_POST['action'] ?? 'login');
$allowedActions = ['login', 'register', 'logout'];
if (!in_array($action, $allowedActions, true)) {
    $action = 'login';
}

$error = '';
$success = '';

if (is_post_request()) {
    require_csrf();

    if ($action === 'logout') {
        logout_current_user();
    } elseif ($action === 'login') {
        login_user($pdo, $error);
    } elseif ($action === 'register') {
        register_user($pdo, $error, $success);
    }
}

// Người đã đăng nhập không cần xem lại form login/register.
if (!is_post_request() && isset($_SESSION['user_id'])) {
    redirect(($_SESSION['role'] ?? '') === 'admin' ? 'admin.php' : 'profile.php');
}

/** Kiểm tra mật khẩu, tạo session mới và chuyển tới trang phù hợp với role. */
function login_user(PDO $pdo, string &$error): void
{
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    $stmt = $pdo->prepare('SELECT id, username, password, role FROM users WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        $error = 'Tài khoản hoặc mật khẩu không đúng!';
        return;
    }

    // Đổi session ID sau đăng nhập để chống session fixation.
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];

    redirect($user['role'] === 'admin' ? 'admin.php' : 'profile.php');
}

/** Validate dữ liệu, hash mật khẩu và chỉ tạo tài khoản role customer. */
function register_user(PDO $pdo, string &$error, string &$success): void
{
    $username = trim((string) ($_POST['username'] ?? ''));
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');
    $confirmation = (string) ($_POST['confirm_password'] ?? '');

    if ($username === '' || $email === '' || $password === '' || $confirmation === '') {
        $error = 'Vui lòng nhập đầy đủ thông tin!';
    } elseif (mb_strlen($username) < 4 || mb_strlen($username) > 50) {
        $error = 'Tài khoản phải có từ 4 đến 50 ký tự!';
    } elseif (!preg_match('/^[\p{L}\p{N}_.-]+$/u', $username)) {
        $error = 'Tài khoản chỉ được chứa chữ, số, dấu chấm, gạch dưới hoặc gạch ngang!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email không hợp lệ!';
    } elseif (strlen($password) < 8 || strlen($password) > 128) {
        $error = 'Mật khẩu phải có từ 8 đến 128 ký tự!';
    } elseif ($password !== $confirmation) {
        $error = 'Mật khẩu xác nhận không khớp!';
    } else {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? OR email = ?');
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $error = 'Tài khoản hoặc email này đã tồn tại!';
            return;
        }

        try {
            $stmt = $pdo->prepare(
                "INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'customer')"
            );
            $stmt->execute([$username, $email, password_hash($password, PASSWORD_DEFAULT)]);
            $success = 'Đăng ký thành công! Bạn có thể đăng nhập ngay.';
        } catch (PDOException $exception) {
            // Unique key là lớp bảo vệ cuối nếu hai request đăng ký chạy đồng thời.
            $error = 'Tài khoản hoặc email đã được sử dụng.';
        }
    }
}

/** Xóa dữ liệu session phía server và cookie session phía trình duyệt. */
function logout_current_user(): never
{
    session_unset();
    session_destroy();

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    redirect('index.php');
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $action === 'register' ? 'Đăng ký' : 'Đăng nhập' ?> - JoyTix</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="auth-page">
    <div class="auth-card">
        <h2><?= $action === 'register' ? 'Đăng Ký Tài Khoản' : 'Đăng Nhập' ?></h2>

        <?php if ($error): ?><p class="form-error"><?= e($error) ?></p><?php endif; ?>
        <?php if ($success): ?><p class="form-success"><?= e($success) ?></p><?php endif; ?>

        <?php if ($action === 'register'): ?>
            <!-- FORM ĐĂNG KÝ: tạo tài khoản customer mới. -->
            <form action="auth.php?action=register" method="POST">
                <?= csrf_field() ?>
                <input type="text" name="username" placeholder="Tài khoản" required
                    autocomplete="username" minlength="4" maxlength="50"
                    value="<?= e($_POST['username'] ?? '') ?>">
                <input type="email" name="email" placeholder="Email" required
                    autocomplete="email" value="<?= e($_POST['email'] ?? '') ?>">
                <input type="password" name="password" placeholder="Mật khẩu" required
                    autocomplete="new-password" minlength="8" maxlength="128">
                <input type="password" name="confirm_password" placeholder="Xác nhận mật khẩu" required
                    autocomplete="new-password" minlength="8" maxlength="128">
                <button type="submit" class="btn-primary">Đăng ký</button>
            </form>
            <a href="auth.php?action=login" class="auth-switch">Đã có tài khoản? Đăng nhập</a>
        <?php else: ?>
            <!-- FORM ĐĂNG NHẬP: xác thực rồi tạo session cho user. -->
            <form action="auth.php?action=login" method="POST">
                <?= csrf_field() ?>
                <input type="text" name="username" placeholder="Tài khoản" required
                    autocomplete="username" value="<?= e($_POST['username'] ?? '') ?>">
                <input type="password" name="password" placeholder="Mật khẩu" required
                    autocomplete="current-password">
                <button type="submit" class="btn-primary">Đăng nhập</button>
            </form>
            <a href="auth.php?action=register" class="auth-switch">Chưa có tài khoản? Đăng ký</a>
        <?php endif; ?>

        <a href="index.php" class="auth-home">Quay về trang chủ</a>
    </div>
</body>
</html>
