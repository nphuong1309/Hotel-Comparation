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
} catch (PDOException $e) {
    die("Lỗi kết nối CSDL: " . $e->getMessage());
}
