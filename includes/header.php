<?php
/** HEADER.PHP: mở HTML, nạp design system và dựng navbar theo trạng thái đăng nhập. */
$pageName = pathinfo((string) ($_SERVER['SCRIPT_NAME'] ?? ''), PATHINFO_FILENAME);
$pageName = preg_replace('/[^a-z0-9_-]/i', '', $pageName) ?: 'page';
$isHomePage = $pageName === 'index';
$isFullWidthPage = in_array($pageName, ['index', 'detail'], true);
$pageTitles = [
    'index' => 'Khám phá khách sạn Cần Thơ',
    'search' => 'Kết quả tìm kiếm',
    'detail' => 'Chi tiết khách sạn',
    'compare' => 'So sánh khách sạn',
    'community' => 'Cộng đồng du lịch',
    'profile' => 'Tài khoản của tôi',
    'admin' => 'Quản trị khách sạn',
    'adminadd' => 'Thêm khách sạn',
    'edit_hotel' => 'Chỉnh sửa khách sạn',
];
$documentTitle = ($pageTitles[$pageName] ?? 'Khám phá Cần Thơ') . ' | JoyTix';
$username = (string) ($_SESSION['username'] ?? '');
$userInitial = $username !== '' ? mb_strtoupper(mb_substr($username, 0, 1)) : '';
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Cầu nối dữ liệu động duy nhất PHP truyền cho script.js. -->
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e($documentTitle) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset_url('css/base.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset_url($isHomePage ? 'css/home.css' : 'css/pages.css')) ?>">
</head>

<body class="page-<?= e($pageName) ?>">
    <header class="navbar">
        <div class="container nav-content">
            <a href="index.php" class="logo" aria-label="JoyTix - Trang chủ">Joy<span>Tix</span></a>

            <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="primaryNavigation" aria-label="Mở menu">
                <span></span><span></span><span></span>
            </button>

            <nav id="primaryNavigation" class="primary-navigation">
                <a href="index.php" class="<?= $pageName === 'index' ? 'is-active' : '' ?>" <?= $pageName === 'index' ? 'aria-current="page"' : '' ?>>Trang chủ</a>
                <a href="community.php" class="nav-community <?= $pageName === 'community' ? 'is-active' : '' ?>" <?= $pageName === 'community' ? 'aria-current="page"' : '' ?>>
                    <span class="nav-community-dot" aria-hidden="true"></span>
                    Cộng đồng
                </a>

                <?php if (isset($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'admin'): ?>
                    <a href="admin.php" class="nav-account <?= in_array($pageName, ['admin', 'adminadd', 'edit_hotel'], true) ? 'is-active' : '' ?>" <?= in_array($pageName, ['admin', 'adminadd', 'edit_hotel'], true) ? 'aria-current="page"' : '' ?>>
                        <span class="nav-avatar" aria-hidden="true"><?= e($userInitial) ?></span>
                        <span class="nav-account-copy"><small>Quản trị</small><b><?= e($username) ?></b></span>
                    </a>
                    <form action="auth.php?action=logout" method="POST" class="logout-form">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn-outline">Đăng xuất</button>
                    </form>

                <?php elseif (isset($_SESSION['user_id'])): ?>
                    <a href="profile.php" class="nav-account <?= $pageName === 'profile' ? 'is-active' : '' ?>" <?= $pageName === 'profile' ? 'aria-current="page"' : '' ?>>
                        <span class="nav-avatar" aria-hidden="true"><?= e($userInitial) ?></span>
                        <span class="nav-account-copy"><small>Tài khoản</small><b><?= e($username) ?></b></span>
                    </a>
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

    <main class="site-main<?= $isFullWidthPage ? ' site-main--full' : ' container' ?>">
