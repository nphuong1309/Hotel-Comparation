<?php
// community.php
session_start();
require_once 'includes/db-connect.php';
require_once 'includes/header.php';

// Kiểm tra trạng thái đăng nhập
$is_logged_in = isset($_SESSION['user_id']);
$current_username = $is_logged_in ? $_SESSION['username'] : '';

/* CHÚ Ý LÔ-GÍC: 
   Chỉ xử lý Đăng bài và Bình luận nếu người dùng đã đăng nhập.
*/

// 1. XỬ LÝ ĐĂNG BÀI VIẾT (POST)
if ($is_logged_in && isset($_POST['submit_post'])) {
    $hotel_id = !empty($_POST['hotel_id']) ? $_POST['hotel_id'] : null;
    $content = trim($_POST['content']);
    $image_url = null;

    if (!empty($content)) {
        // Xử lý upload ảnh review (vào thư mục uploads)
        if (isset($_FILES['post_image']) && $_FILES['post_image']['error'] == 0) {
            $ext = strtolower(pathinfo($_FILES['post_image']['name'], PATHINFO_EXTENSION));
            $filename = "post_" . time() . "_" . $_SESSION['user_id'] . "." . $ext;
            $target = 'uploads/' . $filename;
            if (move_uploaded_file($_FILES['post_image']['tmp_name'], $target)) {
                $image_url = $target;
            }
        }

        // Tự động gán tên tác giả là Username đang đăng nhập
        $stmt = $pdo->prepare("INSERT INTO feed_posts (author_name, hotel_id, content, image_url) VALUES (?, ?, ?, ?)");
        $stmt->execute([$current_username, $hotel_id, $content, $image_url]);
        header("Location: community.php"); 
        exit;
    }
}

// 2. XỬ LÝ ĐĂNG BÌNH LUẬN (COMMENT)
if ($is_logged_in && isset($_POST['submit_comment'])) {
    $post_id = $_POST['post_id'];
    $comment_content = trim($_POST['comment_content']);

    if (!empty($comment_content)) {
        $stmt = $pdo->prepare("INSERT INTO feed_comments (post_id, author_name, content) VALUES (?, ?, ?)");
        $stmt->execute([$post_id, $current_username, $comment_content]);
        header("Location: community.php");
        exit;
    }
}

// 3. LẤY DỮ LIỆU ĐỂ HIỂN THỊ
$hotels = $pdo->query("SELECT id, name FROM hotels")->fetchAll(PDO::FETCH_ASSOC);
$posts = $pdo->query("SELECT p.*, h.name as hotel_name FROM feed_posts p LEFT JOIN hotels h ON p.hotel_id = h.id ORDER BY p.id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- CSS thiết kế riêng cho trang Cộng đồng (Giống Insta/FB) -->
<style>
    .feed-container { max-width: 600px; margin: 0 auto; padding-top: 20px; }
    .post-card { background: #fff; border-radius: 12px; box-shadow: 0 1px 4px rgba(0,0,0,0.1); margin-bottom: 25px; border: 1px solid #efefef; overflow: hidden; }
    .post-header { display: flex; align-items: center; padding: 15px; }
    .avatar { width: 42px; height: 42px; background: linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%); color: #fff; border-radius: 50%; display: flex; justify-content: center; align-items: center; font-weight: bold; font-size: 18px; margin-right: 12px; }
    .author-name { font-weight: bold; color: #262626; font-size: 15px; }
    .hotel-tag { font-size: 13px; color: var(--primary); text-decoration: none; font-weight: 500; }
    .post-time { font-size: 12px; color: #8e8e8e; }
    .post-image { width: 100%; max-height: 500px; object-fit: cover; }
    .post-content { padding: 15px; font-size: 15px; color: #262626; line-height: 1.5; }
    .post-actions { padding: 10px 15px; border-top: 1px solid #efefef; border-bottom: 1px solid #efefef; display: flex; gap: 15px; }
    .btn-action { background: none; border: none; font-size: 20px; cursor: pointer; color: #262626; display: flex; align-items: center; gap: 5px; }
    .comment-section { padding: 15px; background: #fafafa; }
    .comment-item { margin-bottom: 8px; font-size: 14px; }
    .comment-author { font-weight: bold; margin-right: 5px; color: #262626; }
    .comment-input-group { display: flex; gap: 10px; margin-top: 15px; }
    .comment-input { flex: 1; padding: 10px 15px; border: 1px solid #dbdbdb; border-radius: 20px; font-size: 14px; outline: none; }
    .btn-post-cmt { background: transparent; border: none; color: var(--primary); font-weight: bold; cursor: pointer; }
    .login-overlay { background: #f8f9fa; padding: 20px; text-align: center; border-radius: 8px; border: 1px dashed #ccc; margin-bottom: 20px; }
</style>

<div class="feed-container">
    
    <!-- KHU VỰC ĐĂNG BÀI -->
    <?php if ($is_logged_in): ?>
        <div class="post-card" style="padding: 15px;">
            <div style="display: flex; align-items: center; margin-bottom: 15px;">
                <div class="avatar"><?= strtoupper(substr($current_username, 0, 1)) ?></div>
                <span style="color: #666;">Cùng chia sẻ trải nghiệm của bạn, <b><?= htmlspecialchars($current_username) ?></b>!</span>
            </div>
            <form action="" method="POST" enctype="multipart/form-data">
                <select name="hotel_id" style="width: 100%; padding: 10px; margin-bottom: 10px; border: 1px solid #ddd; border-radius: 8px; outline: none;">
                    <option value="">-- Gắn thẻ khách sạn (Tùy chọn) --</option>
                    <?php foreach($hotels as $h): ?>
                        <option value="<?= $h['id'] ?>"><?= htmlspecialchars($h['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <textarea name="content" rows="3" placeholder="Góc view phòng hôm nay thế nào?" required style="width: 100%; padding: 10px; margin-bottom: 10px; border: 1px solid #ddd; border-radius: 8px; resize: none; outline: none;"></textarea>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <input type="file" name="post_image" accept="image/*" style="font-size: 14px; color: #666;">
                    <button type="submit" name="submit_post" class="btn-primary" style="border-radius: 20px; padding: 8px 20px;">Đăng bài</button>
                </div>
            </form>
        </div>
    <?php else: ?>
        <!-- Nhắc nhở đăng nhập nếu là Khách vãng lai -->
        <div class="login-overlay">
            <h3 style="margin-bottom: 10px;">Bạn đã có một chuyến đi tuyệt vời?</h3>
            <p style="color: #666; margin-bottom: 15px;">Đăng nhập ngay để chia sẻ hình ảnh và bình luận cùng cộng đồng MiniHotel!</p>
            <a href="login.php" class="btn-primary" style="text-decoration: none; display: inline-block;">Đăng nhập để tham gia</a>
        </div>
    <?php endif; ?>

    <!-- KHU VỰC BẢNG TIN -->
    <?php foreach($posts as $post): ?>
    <div class="post-card">
        <!-- Header -->
        <div class="post-header">
            <div class="avatar"><?= strtoupper(substr($post['author_name'], 0, 1)) ?></div>
            <div>
                <div class="author-name"><?= htmlspecialchars($post['author_name']) ?></div>
                <div class="post-time">
                    <?= date('d/m/Y H:i', strtotime($post['created_at'])) ?>
                    <?php if($post['hotel_name']): ?>
                        • Đang ở <a href="detail.php?id=<?= $post['hotel_id'] ?>" class="hotel-tag">📍 <?= htmlspecialchars($post['hotel_name']) ?></a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Ảnh (Chỉ hiện nếu có) -->
        <?php if($post['image_url']): ?>
            <img src="<?= htmlspecialchars($post['image_url']) ?>" class="post-image" alt="Post Image">
        <?php endif; ?>

        <!-- Caption -->
        <div class="post-content">
            <span class="author-name"><?= htmlspecialchars($post['author_name']) ?></span> 
            <?= nl2br(htmlspecialchars($post['content'])) ?>
        </div>

        <!-- Nút Thả tim (Chỉ click được nếu đã đăng nhập) -->
        <div class="post-actions">
            <?php if ($is_logged_in): ?>
                <button class="btn-action btn-like" data-id="<?= $post['id'] ?>">
                    ❤️ <span class="like-count" style="font-size: 15px;"><?= $post['likes_count'] ?></span>
                </button>
            <?php else: ?>
                <button class="btn-action" onclick="alert('Vui lòng đăng nhập để thả tim!');">
                    🤍 <span style="font-size: 15px;"><?= $post['likes_count'] ?></span>
                </button>
            <?php endif; ?>
            <span class="btn-action">💬 Bình luận</span>
        </div>

        <!-- Khu vực Comment -->
        <div class="comment-section">
            <?php
            $stmt_cmt = $pdo->prepare("SELECT * FROM feed_comments WHERE post_id = ? ORDER BY id ASC");
            $stmt_cmt->execute([$post['id']]);
            $comments = $stmt_cmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach($comments as $cmt):
            ?>
                <div class="comment-item">
                    <span class="comment-author"><?= htmlspecialchars($cmt['author_name']) ?></span>
                    <span><?= htmlspecialchars($cmt['content']) ?></span>
                </div>
            <?php endforeach; ?>

            <!-- Form Comment -->
            <?php if ($is_logged_in): ?>
                <form action="" method="POST" class="comment-input-group">
                    <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                    <input type="text" name="comment_content" class="comment-input" placeholder="Thêm bình luận..." required autocomplete="off">
                    <button type="submit" name="submit_comment" class="btn-post-cmt">Đăng</button>
                </form>
            <?php else: ?>
                <div style="margin-top: 15px; font-size: 13px; color: #999; text-align: center;">
                    <a href="login.php" style="color: var(--primary); font-weight: bold;">Đăng nhập</a> để bình luận bài viết này.
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>

</div>

<!-- Script xử lý nút Thả tim bằng AJAX -->
<script>
document.querySelectorAll('.btn-like').forEach(button => {
    button.addEventListener('click', function() {
        const postId = this.getAttribute('data-id');
        const countSpan = this.querySelector('.like-count');
        
        // Gửi request ngầm lên server
        fetch('ajax_like.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'post_id=' + postId
        })
        .then(response => {
            // Bắt lỗi 401 chưa đăng nhập từ PHP
            if (!response.ok && response.status === 401) {
                alert('Vui lòng đăng nhập để thao tác!');
                throw new Error('Chưa đăng nhập');
            }
            return response.json(); // Ép kiểu dữ liệu trả về thành JSON
        })
        .then(data => {
            if (data.success) {
                // Cập nhật số lượng tim
                countSpan.textContent = data.likes_count;
                
                // Đổi màu tim dựa vào trạng thái liked trả về
                if (data.liked) {
                    this.style.color = 'red'; // Đã thích -> Đỏ
                } else {
                    this.style.color = '#262626'; // Bỏ thích -> Về màu xám đen ban đầu
                }
            } else {
                alert(data.message);
            }
        })
        .catch(error => {
            console.error('Lỗi:', error);
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>