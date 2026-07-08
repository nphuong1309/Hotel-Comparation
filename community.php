<?php
require_once 'includes/db-connect.php';
require_once 'includes/header.php';

// 1. XỬ LÝ ĐĂNG BÀI VIẾT (POST)
if (isset($_POST['submit_post'])) {
    $author = trim($_POST['author_name']);
    $hotel_id = !empty($_POST['hotel_id']) ? $_POST['hotel_id'] : null;
    $content = trim($_POST['content']);
    $image_url = null;

    if (!empty($author) && !empty($content)) {
        // Xử lý upload ảnh review
        if (isset($_FILES['post_image']) && $_FILES['post_image']['error'] == 0) {
            $filename = time() . '_' . $_FILES['post_image']['name'];
            $target = 'uploads/' . $filename;
            if (move_uploaded_file($_FILES['post_image']['tmp_name'], $target)) {
                $image_url = $target;
            }
        }

        $stmt = $pdo->prepare("INSERT INTO feed_posts (author_name, hotel_id, content, image_url) VALUES (?, ?, ?, ?)");
        $stmt->execute([$author, $hotel_id, $content, $image_url]);
        // Tải lại trang để tránh gửi lại form khi F5
        header("Location: community.php"); 
        exit;
    }
}

// 2. XỬ LÝ ĐĂNG BÌNH LUẬN (COMMENT)
if (isset($_POST['submit_comment'])) {
    $post_id = $_POST['post_id'];
    $comment_author = trim($_POST['comment_author']);
    $comment_content = trim($_POST['comment_content']);

    if (!empty($comment_author) && !empty($comment_content)) {
        $stmt = $pdo->prepare("INSERT INTO feed_comments (post_id, author_name, content) VALUES (?, ?, ?)");
        $stmt->execute([$post_id, $comment_author, $comment_content]);
        header("Location: community.php");
        exit;
    }
}

// 3. LẤY DỮ LIỆU ĐỂ HIỂN THỊ
// Lấy danh sách khách sạn cho form chọn thẻ
$hotels = $pdo->query("SELECT id, name FROM hotels")->fetchAll(PDO::FETCH_ASSOC);

// Lấy danh sách bài viết (Giảm dần theo thời gian mới nhất)
$posts = $pdo->query("SELECT p.*, h.name as hotel_name FROM feed_posts p LEFT JOIN hotels h ON p.hotel_id = h.id ORDER BY p.id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div style="max-width: 600px; margin: 0 auto; padding-top: 20px;">
    
    <!-- KHU VỰC 1: FORM ĐĂNG BÀI VIẾT (Tạo Status) -->
    <div style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 4px rgba(0,0,0,0.1); margin-bottom: 20px;">
        <h3 style="margin-bottom: 15px; color: var(--primary);">Chia sẻ trải nghiệm của bạn</h3>
        <form action="" method="POST" enctype="multipart/form-data">
            <input type="text" name="author_name" placeholder="Tên của bạn..." required style="width: 100%; padding: 10px; margin-bottom: 10px; border: 1px solid #ddd; border-radius: 4px;">
            
            <select name="hotel_id" style="width: 100%; padding: 10px; margin-bottom: 10px; border: 1px solid #ddd; border-radius: 4px;">
                <option value="">-- Bạn đang review khách sạn nào? (Không bắt buộc) --</option>
                <?php foreach($hotels as $h): ?>
                    <option value="<?= $h['id'] ?>"><?= htmlspecialchars($h['name']) ?></option>
                <?php endforeach; ?>
            </select>

            <textarea name="content" rows="3" placeholder="Khách sạn này thế nào? Đồ ăn có ngon không..." required style="width: 100%; padding: 10px; margin-bottom: 10px; border: 1px solid #ddd; border-radius: 4px; resize: none;"></textarea>
            
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <input type="file" name="post_image" accept="image/*" style="font-size: 14px;">
                <button type="submit" name="submit_post" class="btn-primary">Đăng bài</button>
            </div>
        </form>
    </div>

    <!-- KHU VỰC 2: HIỂN THỊ BẢNG TIN (Feed) -->
    <?php foreach($posts as $post): ?>
    <div style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 4px rgba(0,0,0,0.1); margin-bottom: 20px;">
        <!-- Header Bài viết -->
        <div style="display: flex; align-items: center; margin-bottom: 15px;">
            <div style="width: 40px; height: 40px; background: #ddd; border-radius: 50%; display: flex; justify-content: center; align-items: center; font-weight: bold; margin-right: 10px;">
                <?= substr(htmlspecialchars($post['author_name']), 0, 1) ?>
            </div>
            <div>
                <strong style="font-size: 16px;"><?= htmlspecialchars($post['author_name']) ?></strong>
                <?php if($post['hotel_name']): ?>
                    <span style="color: #666;"> đã review </span> <a href="detail.php?id=<?= $post['hotel_id'] ?>" style="font-weight: bold; color: var(--primary); text-decoration: none;">📍 <?= htmlspecialchars($post['hotel_name']) ?></a>
                <?php endif; ?>
                <div style="font-size: 12px; color: #999;"><?= date('d/m/Y H:i', strtotime($post['created_at'])) ?></div>
            </div>
        </div>

        <!-- Nội dung -->
        <p style="margin-bottom: 15px; line-height: 1.5;"><?= nl2br(htmlspecialchars($post['content'])) ?></p>
        <?php if($post['image_url']): ?>
            <img src="<?= htmlspecialchars($post['image_url']) ?>" style="width: 100%; max-height: 400px; object-fit: cover; border-radius: 8px; margin-bottom: 15px;">
        <?php endif; ?>

        <!-- Nút Thả tim (Like) -->
        <div style="border-top: 1px solid #eee; border-bottom: 1px solid #eee; padding: 10px 0; margin-bottom: 15px;">
            <button class="btn-like" data-id="<?= $post['id'] ?>" style="background: none; border: none; cursor: pointer; color: #666; font-size: 15px; display: flex; align-items: center; gap: 5px;">
                ❤️ <span class="like-count"><?= $post['likes_count'] ?></span> Yêu thích
            </button>
        </div>

        <!-- Khu vực Bình luận -->
        <div style="background: #f9f9f9; padding: 15px; border-radius: 8px;">
            <?php
            // Lấy bình luận của bài viết này
            $stmt_cmt = $pdo->prepare("SELECT * FROM feed_comments WHERE post_id = ? ORDER BY id ASC");
            $stmt_cmt->execute([$post['id']]);
            $comments = $stmt_cmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach($comments as $cmt):
            ?>
                <div style="margin-bottom: 10px; display: flex; gap: 10px;">
                    <strong style="font-size: 14px;"><?= htmlspecialchars($cmt['author_name']) ?>:</strong>
                    <span style="font-size: 14px;"><?= htmlspecialchars($cmt['content']) ?></span>
                </div>
            <?php endforeach; ?>

            <!-- Form Viết bình luận -->
            <form action="" method="POST" style="margin-top: 10px; display: flex; gap: 10px;">
                <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                <input type="text" name="comment_author" placeholder="Tên..." required style="width: 80px; padding: 6px; border: 1px solid #ccc; border-radius: 4px;">
                <input type="text" name="comment_content" placeholder="Viết bình luận..." required style="flex: 1; padding: 6px; border: 1px solid #ccc; border-radius: 4px;">
                <button type="submit" name="submit_comment" class="btn-outline">Gửi</button>
            </form>
        </div>
    </div>
    <?php endforeach; ?>

</div>

<!-- Script xử lý nút Thả tim bằng AJAX (Không load lại trang) -->
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
        .then(response => response.text())
        .then(newCount => {
            countSpan.textContent = newCount; // Cập nhật số tim mới
            this.style.color = 'red'; // Đổi màu chữ báo hiệu đã thích
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>