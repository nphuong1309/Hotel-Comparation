<?php
session_start();
require_once 'includes/db-connect.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    //Validate
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "Vui lòng nhập đầy đủ thông tin!";
    } elseif (strlen($username) < 4) {
        $error = "Tài khoản phải có ít nhất 4 ký tự!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Email không hợp lệ!";
    } elseif (strlen($password) < 6) {
        $error = "Mật khẩu phải có ít nhất 6 ký tự!";
    } elseif ($password !== $confirm_password) {
        $error = "Mật khẩu xác nhận không khớp!";
    } else {
        // Kiểm tra trùng username/email
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);

        if ($stmt->fetch()) {
            $error = "Tài khoản hoặc email này đã tồn tại!";
        } else {
            // Mã hóa mật khẩu trước khi lưu
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            try {
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'customer')");
                $stmt->execute([$username, $email, $hashed]);
                $success = "Đăng ký thành công! Bạn có thể đăng nhập ngay.";
            } catch (PDOException $e) {
                $error = 'Tài khoản hoặc email đã được sử dụng';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="css/style.css">
</head>

<body style="display: flex; justify-content: center; align-items: center; height: 100vh;">
    <div style="background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); width: 300px;">
        <h2 style="text-align:center; color: var(--primary);">Đăng Ký Tài Khoản</h2>

        <?php if ($error): ?>
            <p style="color:red;"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <?php if ($success): ?>
            <p style="color:green;"><?= htmlspecialchars($success) ?></p>
        <?php endif; ?>

        <form action="" method="POST">
            <input type="text" name="username" placeholder="Tài khoản" required
                value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                style="width: 100%; padding: 10px; margin-bottom: 15px;">
            <input type="email" name="email" placeholder="Email" required
                value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                style="width: 100%; padding: 10px; margin-bottom: 15px;">
            <input type="password" name="password" placeholder="Mật khẩu" required
                style="width: 100%; padding: 10px; margin-bottom: 15px;">
            <input type="password" name="confirm_password" placeholder="Xác nhận mật khẩu" required
                style="width: 100%; padding: 10px; margin-bottom: 15px;">
            <button type="submit" class="btn-primary" style="width: 100%;">Đăng ký</button>
        </form>
        <a href="login.php" style="display:block; text-align:center; margin-top:15px; color: #666;">
            Đã có tài khoản? Đăng nhập
        </a>
    </div>
</body>

</html>