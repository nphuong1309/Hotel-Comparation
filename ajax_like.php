<?php
require_once 'includes/db-connect.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

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

        $stmt_check = $pdo->prepare("SELECT 1 FROM feed_post_likes WHERE post_id = ? AND user_id = ?");
        $stmt_check->execute([$post_id, $user_id]);

        if ($stmt_check->fetchColumn()) {
            $stmt = $pdo->prepare("DELETE FROM feed_post_likes WHERE post_id = ? AND user_id = ?");
            $stmt->execute([$post_id, $user_id]);

            $stmt_update = $pdo->prepare("UPDATE feed_posts SET likes_count = GREATEST(likes_count - 1, 0) WHERE id = ?");
            $stmt_update->execute([$post_id]);

            $liked = false;
        } else {
            $stmt = $pdo->prepare("INSERT INTO feed_post_likes (post_id, user_id) VALUES (?, ?)");
            $stmt->execute([$post_id, $user_id]);

            $stmt_update = $pdo->prepare("UPDATE feed_posts SET likes_count = likes_count + 1 WHERE id = ?");
            $stmt_update->execute([$post_id]);

            $liked = true;
        }

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
