<?php
session_start();
require_once 'includes/db-connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Kiểm tra DB
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND password = ? AND role = 'admin'");
    $stmt->execute([$username, $password]);
    $admin = $stmt->fetch();

    if ($admin) {
        $_SESSION['admin_id'] = $admin['id'];
        header("Location: admin.php");
        exit;
    } else {
        $error = "Tài khoản hoặc mật khẩu không đúng!";
    }
}
?>
<!DOCTYPE html>
<html>
<head><link rel="stylesheet" href="css/style.css"></head>
<body style="display: flex; justify-content: center; align-items: center; height: 100vh;">
    <div style="background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); width: 300px;">
        <h2 style="text-align:center; color: var(--primary);">Đăng Nhập Admin</h2>
        <?php if(isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
        <form action="" method="POST">
            <input type="text" name="username" placeholder="Tài khoản (admin)" required style="width: 100%; padding: 10px; margin-bottom: 15px;">
            <input type="password" name="password" placeholder="Mật khẩu (123456)" required style="width: 100%; padding: 10px; margin-bottom: 15px;">
            <button type="submit" class="btn-primary" style="width: 100%;">Đăng nhập</button>
        </form>
        <a href="index.php" style="display:block; text-align:center; margin-top:15px; color: #666;">Quay về trang chủ</a>
    </div>
</body>
</html>