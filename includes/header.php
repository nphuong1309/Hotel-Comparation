<?php if (session_status() === PHP_SESSION_NONE) session_start(); ?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JoyTix - Khám phá khách sạn Cần Thơ</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@500;600;700&family=Cormorant+Garamond:wght@500;600;700&family=Lato:wght@300;400;700&family=Oswald:wght@400;500;600&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #dda975;
            --primary-dark: #c58b56;
            --site-primary: #dda975;
            --site-primary-dark: #c58b56;
            --site-deep: #191919;
            --site-deep-soft: #252525;
            --site-cream: #f8f5f1;
            --site-text: #191919;
            --font-logo: 'Cinzel', Georgia, serif;
            --font-heading: 'Cormorant Garamond', Georgia, serif;
            --font-body: 'Lato', Arial, sans-serif;
            --font-label: 'Oswald', Arial, sans-serif;
        }

        html {
            min-height: 100%;
            scroll-behavior: smooth;
            background: #fef4e8;
        }

        body {
            min-height: 100vh;
            margin: 0;
            padding-bottom: 0 !important;
            overflow-x: hidden;
            background: #fef4e8;
            color: var(--site-text);
            display: flex;
            flex-direction: column;
            font-family: var(--font-body);
            font-size: 16px;
            font-weight: 400;
            line-height: 1.65;
            -webkit-font-smoothing: antialiased;
            text-rendering: optimizeLegibility;
        }



        /* Giữ footer sát cuối trang và chỉ chừa chỗ khi thanh so sánh đang hiện. */
        body > main.container {
            width: 100%;
            flex: 1 0 auto;
            background: transparent;
        }

        body.compare-dock-visible {
            padding-bottom: 68px !important;
        }

        @media (max-width: 680px) {
            body.compare-dock-visible {
                padding-bottom: 90px !important;
            }
        }

        body,
        input,
        select,
        textarea,
        button {
            font-family: var(--font-body);
        }

        h1, h2, h3, h4, h5, h6 {
            font-family: var(--font-heading);
            font-weight: 600;
            letter-spacing: -0.015em;
        }

        button,
        .btn-primary,
        .btn-outline,
        .btn-register {
            font-family: var(--font-label);
            font-weight: 500;
            letter-spacing: .035em;
        }

        /* Đồng bộ màu các nút dùng chung của toàn website. */
        .btn-primary {
            border-color: var(--site-primary) !important;
            background: var(--site-primary) !important;
            color: var(--site-deep) !important;
            box-shadow: none !important;
        }

        .btn-primary:hover {
            border-color: var(--site-primary-dark) !important;
            background: var(--site-primary-dark) !important;
            color: #fff !important;
        }

        .btn-outline {
            border-color: var(--site-deep) !important;
            background: transparent !important;
            color: var(--site-deep) !important;
        }

        .btn-outline:hover {
            border-color: var(--site-primary) !important;
            background: var(--site-primary) !important;
            color: var(--site-deep) !important;
        }

        input[type="checkbox"],
        input[type="radio"],
        input[type="range"] {
            accent-color: var(--site-primary-dark);
        }

        /* Header màu đen - vàng cát và luôn bám theo màn hình khi cuộn. */
        .navbar {
            position: sticky !important;
            top: 0;
            z-index: 12000;
            width: 100%;
            margin: 0;
            padding: 0 !important;
            background: var(--site-deep) !important;
            color: #fff;
            border-top: 3px solid var(--site-primary);
            border-bottom: 1px solid rgba(221, 169, 117, .35);
            box-shadow: 0 6px 22px rgba(0, 0, 0, .2);
        }

        .navbar .nav-content {
            min-height: 76px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 28px;
        }

        .navbar .logo,
        .navbar .logo a {
            margin: 0;
            color: #fff !important;
            text-decoration: none;
        }

        .navbar .logo {
            font-family: var(--font-logo);
            font-size: 32px;
            font-weight: 600;
            line-height: 1;
            letter-spacing: .045em;
        }

        .navbar .logo span {
            color: var(--site-primary) !important;
            font-weight: 600;
        }

        .navbar nav {
            font-family: var(--font-body);
            font-size: 15px;
            font-weight: 700;
            letter-spacing: .01em;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            flex-wrap: wrap;
            gap: 10px 24px;
        }

        .navbar nav a,
        .navbar .nav-user {
            color: #fff !important;
            text-decoration: none;
            transition: color .22s ease, border-color .22s ease, background .22s ease;
        }

        .navbar nav a:hover,
        .navbar .nav-user:hover,
        .navbar .nav-community:hover {
            color: var(--site-primary) !important;
        }

        .navbar .nav-community {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            font-weight: 600;
        }

        .navbar .nav-community-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--site-primary);
            box-shadow: 0 0 0 3px rgba(221, 169, 117, .16);
        }

        .navbar .btn-outline,
        .navbar .btn-register {
            padding: 8px 15px;
            border: 1px solid var(--site-primary) !important;
            border-radius: 0;
            background: transparent !important;
            color: var(--site-primary) !important;
        }

        .navbar .btn-outline:hover,
        .navbar .btn-register:hover {
            background: var(--site-primary) !important;
            color: var(--site-deep) !important;
        }

        @media (max-width: 780px) {
            .navbar .nav-content {
                padding-top: 12px;
                padding-bottom: 12px;
                align-items: flex-start;
                flex-direction: column;
                gap: 10px;
            }

            .navbar nav {
                width: 100%;
                justify-content: flex-start;
                gap: 9px 16px;
            }
        }
    </style>
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

                <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'admin'): ?>
                    <a href="admin.php" class="nav-user">
                        <b><?= htmlspecialchars($_SESSION['username']) ?></b>
                    </a>
                    <a href="logout.php" class="btn-outline">Đăng xuất</a>

                <?php elseif (isset($_SESSION['user_id'])): ?>
                    <span class="nav-user">
                        <b><?= htmlspecialchars($_SESSION['username']) ?></b>
                    </span>
                    <a href="profile.php">Tài khoản của tôi</a>
                    <a href="logout.php" class="btn-outline">Đăng xuất</a>

                <?php else: ?>
                    <a href="login.php">Đăng nhập</a>
                    <a href="register.php" class="btn-register">Đăng ký</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <main class="container">