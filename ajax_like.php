<?php
require_once 'includes/db-connect.php';

if (isset($_POST['post_id'])) {
    $post_id = (int)$_POST['post_id'];
    
    // Cập nhật tăng lượt thích lên 1
    $stmt = $pdo->prepare("UPDATE feed_posts SET likes_count = likes_count + 1 WHERE id = ?");
    $stmt->execute([$post_id]);
    
    // Lấy số lượt thích mới nhất trả về cho Javascript
    $stmt_get = $pdo->prepare("SELECT likes_count FROM feed_posts WHERE id = ?");
    $stmt_get->execute([$post_id]);
    $result = $stmt_get->fetch(PDO::FETCH_ASSOC);
    
    echo $result['likes_count'];
}
?>