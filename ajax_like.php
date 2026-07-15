<?php
// ajax_like.php
require_once 'includes/db-connect.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Báo cho trình duyệt biết dữ liệu trả về là JSON
header('Content-Type: application/json; charset=utf-8');

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập để thả tim.']);
    exit;
}

if (isset($_POST['post_id'])) {
    $post_id = (int)$_POST['post_id'];
    $user_id = (int)$_SESSION['user_id'];

    try {
        $pdo->beginTransaction();

        // Kiểm tra xem user này đã like bài này chưa
        $stmt_check = $pdo->prepare("SELECT 1 FROM feed_post_likes WHERE post_id = ? AND user_id = ?");
        $stmt_check->execute([$post_id, $user_id]);

        if ($stmt_check->fetchColumn()) {
            // ĐÃ LIKE -> Thực hiện UNLIKE (Bỏ thích)
            $stmt = $pdo->prepare("DELETE FROM feed_post_likes WHERE post_id = ? AND user_id = ?");
            $stmt->execute([$post_id, $user_id]);

            // Trừ tim đi 1 (đảm bảo không bị âm)
            $stmt_update = $pdo->prepare("UPDATE feed_posts SET likes_count = GREATEST(likes_count - 1, 0) WHERE id = ?");
            $stmt_update->execute([$post_id]);

            $liked = false;
        } else {
            // CHƯA LIKE -> Thực hiện LIKE (Thả tim)
            $stmt = $pdo->prepare("INSERT INTO feed_post_likes (post_id, user_id) VALUES (?, ?)");
            $stmt->execute([$post_id, $user_id]);

            // Cộng tim thêm 1
            $stmt_update = $pdo->prepare("UPDATE feed_posts SET likes_count = likes_count + 1 WHERE id = ?");
            $stmt_update->execute([$post_id]);

            $liked = true;
        }

        // Lấy lại số lượng tim mới nhất để trả về
        $stmt_get = $pdo->prepare("SELECT likes_count FROM feed_posts WHERE id = ?");
        $stmt_get->execute([$post_id]);
        $result = $stmt_get->fetch(PDO::FETCH_ASSOC);

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'liked' => $liked,
            'likes_count' => (int)($result['likes_count'] ?? 0)
        ]);
        exit;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Không thể cập nhật tim lúc này.']);
        exit;
    }
}