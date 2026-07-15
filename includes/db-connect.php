<?php
$host = 'localhost';
$dbname = 'hoteltool';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdo->exec("CREATE TABLE IF NOT EXISTS feed_post_likes (
      post_id int(11) NOT NULL,
      user_id int(11) NOT NULL,
      created_at timestamp DEFAULT current_timestamp(),
      PRIMARY KEY (post_id, user_id),
      FOREIGN KEY (post_id) REFERENCES feed_posts(id) ON DELETE CASCADE,
      FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Tạo bảng feed_post_images nếu chưa có
    $pdo->exec("CREATE TABLE IF NOT EXISTS feed_post_images (
      id INT AUTO_INCREMENT PRIMARY KEY,
      post_id INT NOT NULL,
      image_url VARCHAR(255) NOT NULL,
      FOREIGN KEY (post_id) REFERENCES feed_posts(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Di chuyển dữ liệu cũ từ feed_posts.image_url sang feed_post_images (chạy mỗi lần nhưng bỏ qua bài đã migrate)
    $stmt_old = $pdo->query("SELECT id, image_url FROM feed_posts WHERE image_url IS NOT NULL AND image_url != ''");
    $old_posts = $stmt_old->fetchAll(PDO::FETCH_ASSOC);
    if (!empty($old_posts)) {
        $stmt_check_img = $pdo->prepare("SELECT COUNT(*) FROM feed_post_images WHERE post_id = ?");
        $stmt_insert = $pdo->prepare("INSERT INTO feed_post_images (post_id, image_url) VALUES (?, ?)");
        foreach ($old_posts as $op) {
            $stmt_check_img->execute([$op['id']]);
            if ($stmt_check_img->fetchColumn() == 0) {
                $stmt_insert->execute([$op['id'], $op['image_url']]);
            }
        }
    }
} catch (PDOException $e) {
    die("Lỗi kết nối CSDL: " . $e->getMessage());
}
