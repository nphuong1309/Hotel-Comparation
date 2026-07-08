<?php
// logout.php
session_start();
session_unset();      // Xóa hết các biến session
session_destroy();    // Hủy session trên server

// Xóa cookie session (nếu trình duyệt còn giữ)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

header("Location: index.php");
exit;
