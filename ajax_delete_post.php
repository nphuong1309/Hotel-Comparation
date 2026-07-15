<?php
// ajax_delete_post.php
session_start();
require_once 'includes/db-connect.php';

header('Content-Type: application/json; charset=utf-8');

// Bắt buộc đăng nhập
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập.']);
    exit;
}

if (!isset($_POST['post_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Thiếu post_id.']);
    exit;
}

$post_id  = (int)$_POST['post_id'];
$user_id  = (int)$_SESSION['user_id'];
$is_admin = ($_SESSION['role'] ?? '') === 'admin';

// Lấy thông tin bài đăng
$stmt = $pdo->prepare("SELECT author_id, author_name FROM feed_posts WHERE id = ?");
$stmt->execute([$post_id]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$post) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Bài đăng không tồn tại.']);
    exit;
}

// Kiểm tra quyền: admin xóa tất cả, user thường chỉ xóa bài của mình
$is_owner = ((int)$post['author_id'] === $user_id)
            || ($post['author_id'] === null && $post['author_name'] === $_SESSION['username']);

if (!$is_admin && !$is_owner) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Bạn không có quyền xóa bài này.']);
    exit;
}

try {
    // Xóa ảnh vật lý trong thư mục uploads
    $stmt_imgs = $pdo->prepare("SELECT image_url FROM feed_post_images WHERE post_id = ?");
    $stmt_imgs->execute([$post_id]);
    $img_files = $stmt_imgs->fetchAll(PDO::FETCH_COLUMN);
    foreach ($img_files as $img_path) {
        if (is_file($img_path)) {
            unlink($img_path);
        }
    }

    // Xóa bài đăng (CASCADE tự xóa feed_post_images, feed_post_likes, feed_comments)
    $stmt_del = $pdo->prepare("DELETE FROM feed_posts WHERE id = ?");
    $stmt_del->execute([$post_id]);

    echo json_encode(['success' => true, 'message' => 'Đã xóa bài đăng.']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi server khi xóa bài.']);
}
