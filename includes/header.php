<?php /** HEADER.PHP: mở HTML, nạp CSS chung và dựng navbar theo trạng thái đăng nhập. */ ?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Cầu nối dữ liệu động duy nhất PHP truyền cho script.js. -->
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title>JoyTix - Khám phá khách sạn Cần Thơ</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@500;600;700&family=Cormorant+Garamond:wght@500;600;700&family=Lato:wght@300;400;700&family=Oswald:wght@400;500;600&display=swap" rel="stylesheet">
</head>

<body>
    <header class="navbar">
        <div class="container nav-content">
            <h1 class="logo">
                <a href="index.php">Joy<span>Tix</span></a>
            </h1>

            <nav>
                <a href="index.php">Trang chủ</a>
                <a href="community.php" class="nav-community">
                    <span class="nav-community-dot" aria-hidden="true"></span>
                    Cộng đồng
                </a>

                <?php if (isset($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'admin'): ?>
                    <a href="admin.php" class="nav-user">
                        <b><?= htmlspecialchars($_SESSION['username']) ?></b>
                    </a>
                    <form action="auth.php?action=logout" method="POST" class="logout-form">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn-outline">Đăng xuất</button>
                    </form>

                <?php elseif (isset($_SESSION['user_id'])): ?>
                    <span class="nav-user">
                        <b><?= htmlspecialchars($_SESSION['username']) ?></b>
                    </span>
                    <a href="profile.php">Tài khoản của tôi</a>
                    <form action="auth.php?action=logout" method="POST" class="logout-form">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn-outline">Đăng xuất</button>
                    </form>

                <?php else: ?>
                    <a href="auth.php?action=login">Đăng nhập</a>
                    <a href="auth.php?action=register" class="btn-register">Đăng ký</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <main class="container">
