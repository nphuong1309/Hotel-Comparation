<?php if (session_status() === PHP_SESSION_NONE) session_start(); ?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MiniHotel Aggregator</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <header class="navbar">
        <div class="container nav-content">
            <h1 class="logo"><a href="index.php" style="text-decoration:none; color:inherit;">Mini<span>Hotel</span></a></h1>
            <nav>
                <a href="index.php">Trang chủ</a>
                <!-- Thêm menu Cộng đồng vào đây -->
                <a href="community.php" style="color: var(--primary); font-weight: bold;">🌍 Cộng đồng </a>

                <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'admin'): ?>
                    <!-- Đã đăng nhập với quyền ADMIN -->
                    &nbsp;<span class="nav-user"><b><?= htmlspecialchars($_SESSION['username']) ?></b></span>
                    <a href="logout.php" class="btn-outline">Đăng xuất</a>

                <?php elseif (isset($_SESSION['user_id'])): ?>
                    <!-- Đã đăng nhập với quyền CUSTOMER -->
                    &nbsp;<span class="nav-user"><b><?= htmlspecialchars($_SESSION['username']) ?></b></span>
                    <a href="profile.php">Tài khoản của tôi</a>
                    <a href="logout.php" class="btn-outline">Đăng xuất</a>

                <?php else: ?>
                    <!-- Chưa đăng nhập -->
                    <a href="login.php">Đăng nhập</a>
                    <a href="register.php" class="btn-register">Đăng ký</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>
    <main class="container">