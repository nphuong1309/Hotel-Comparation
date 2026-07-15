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
    $author_id = $_SESSION['user_id'];

    if (!empty($content)) {
        // Tự động gán tên tác giả là Username đang đăng nhập
        $stmt = $pdo->prepare("INSERT INTO feed_posts (author_name, author_id, hotel_id, content) VALUES (?, ?, ?, ?)");
        $stmt->execute([$current_username, $author_id, $hotel_id, $content]);
        $post_id = $pdo->lastInsertId();

        // Xử lý upload danh sách ảnh (tối đa 10 ảnh)
        if (isset($_FILES['post_images']) && is_array($_FILES['post_images']['name'])) {
            $file_count = count($_FILES['post_images']['name']);
            $limit = min($file_count, 10);
            
            $stmt_img = $pdo->prepare("INSERT INTO feed_post_images (post_id, image_url) VALUES (?, ?)");
            
            for ($i = 0; $i < $limit; $i++) {
                if ($_FILES['post_images']['error'][$i] == 0) {
                    $ext = strtolower(pathinfo($_FILES['post_images']['name'][$i], PATHINFO_EXTENSION));
                    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    
                    if (in_array($ext, $allowed)) {
                        $filename = "post_" . time() . "_" . $i . "_" . $author_id . "." . $ext;
                        $target = 'uploads/' . $filename;
                        
                        if (move_uploaded_file($_FILES['post_images']['tmp_name'][$i], $target)) {
                            $stmt_img->execute([$post_id, $target]);
                        }
                    }
                }
            }
        }

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

// Lấy danh sách ảnh cho các bài đăng
$post_ids = array_column($posts, 'id');
$images_by_post = [];
if (!empty($post_ids)) {
    $placeholders = implode(',', array_fill(0, count($post_ids), '?'));
    $stmt_images = $pdo->prepare("SELECT * FROM feed_post_images WHERE post_id IN ($placeholders) ORDER BY id ASC");
    $stmt_images->execute($post_ids);
    $images = $stmt_images->fetchAll(PDO::FETCH_ASSOC);
    foreach ($images as $img) {
        $images_by_post[$img['post_id']][] = $img['image_url'];
    }
}
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

    /* Preview grid */
    .preview-grid { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 10px; margin-bottom: 10px; }
    .preview-item { position: relative; width: 70px; height: 70px; border-radius: 8px; overflow: hidden; border: 1px solid #efefef; animation: fadeIn 0.3s ease; }
    .preview-item img { width: 100%; height: 100%; object-fit: cover; }
    @keyframes fadeIn {
        from { opacity: 0; transform: scale(0.9); }
        to { opacity: 1; transform: scale(1); }
    }

    /* Image Slider/Carousel */
    .post-images-slider-wrapper { position: relative; width: 100%; overflow: hidden; background: #fafafa; }
    .post-images-slider { display: flex; transition: transform 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94); width: 100%; }
    .slide-item { min-width: 100%; box-sizing: border-box; display: flex; justify-content: center; align-items: center; background: #000; }
    .slide-item img { width: 100%; max-height: 500px; object-fit: cover; display: block; }
    .slider-btn { position: absolute; top: 50%; transform: translateY(-50%); background: rgba(255, 255, 255, 0.75); color: #262626; border: none; width: 30px; height: 30px; cursor: pointer; font-size: 14px; border-radius: 50%; z-index: 10; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 4px rgba(0,0,0,0.15); transition: background 0.2s, opacity 0.2s; opacity: 0; }
    .post-images-slider-wrapper:hover .slider-btn { opacity: 1; }
    .slider-btn:hover { background: rgba(255, 255, 255, 0.95); }
    .slider-btn.prev { left: 10px; }
    .slider-btn.next { right: 10px; }
    .slider-dots { position: absolute; bottom: 12px; left: 50%; transform: translateX(-50%); display: flex; gap: 5px; z-index: 10; background: rgba(0, 0, 0, 0.3); padding: 4px 8px; border-radius: 10px; }
    .slider-dots .dot { width: 6px; height: 6px; background: rgba(255, 255, 255, 0.5); border-radius: 50%; cursor: pointer; transition: background 0.2s, transform 0.2s; }
    .slider-dots .dot.active { background: #fff; transform: scale(1.2); }

    /* 3-dot Post Menu */
    .post-header { position: relative; }
    .post-menu-wrap { margin-left: auto; position: relative; }
    .post-menu-btn {
        background: none; border: none; cursor: pointer;
        font-size: 20px; color: #8e8e8e; padding: 4px 8px;
        border-radius: 50%; line-height: 1; transition: background 0.2s, color 0.2s;
        display: flex; align-items: center; justify-content: center;
    }
    .post-menu-btn:hover { background: #f0f0f0; color: #262626; }
    .post-menu-dropdown {
        display: none; position: absolute; right: 0; top: calc(100% + 4px);
        background: #fff; border-radius: 10px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.15); z-index: 100;
        overflow: hidden; min-width: 150px;
        animation: menuFadeIn 0.15s ease;
    }
    @keyframes menuFadeIn {
        from { opacity: 0; transform: translateY(-6px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    .post-menu-dropdown.open { display: block; }
    .menu-item-delete {
        display: flex; align-items: center; gap: 8px;
        width: 100%; padding: 12px 16px; background: none; border: none;
        font-size: 14px; color: #e53935; cursor: pointer; text-align: left;
        transition: background 0.15s;
    }
    .menu-item-delete:hover { background: #fff5f5; }
    /* Fade-out khi xóa bài */
    @keyframes fadeOutPost {
        from { opacity: 1; transform: scaleY(1); max-height: 2000px; }
        to   { opacity: 0; transform: scaleY(0.95); max-height: 0; margin: 0; padding: 0; }
    }
    .post-removing { animation: fadeOutPost 0.35s ease forwards; overflow: hidden; }
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
                <!-- Khung hiển thị preview ảnh -->
                <div id="image-preview-container" class="preview-grid"></div>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <input type="file" name="post_images[]" id="post_images" accept="image/*" multiple style="font-size: 14px; color: #666;">
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
            <?php
            $can_delete = $is_logged_in && (
                ($_SESSION['role'] ?? '') === 'admin'
                || (int)($post['author_id']) === (int)$_SESSION['user_id']
                || ($post['author_id'] === null && $post['author_name'] === $current_username)
            );
            ?>
            <?php if ($can_delete): ?>
            <div class="post-menu-wrap">
                <button class="post-menu-btn" onclick="toggleMenu(<?= $post['id'] ?>, event)" title="Tùy chọn">⋯</button>
                <div class="post-menu-dropdown" id="menu-<?= $post['id'] ?>">
                    <button class="menu-item-delete" onclick="deletePost(<?= $post['id'] ?>, this)">🗑️ Xóa bài đăng</button>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Ảnh (Hiển thị dạng slider/carousel nếu có ảnh) -->
        <?php 
        $post_images = $images_by_post[$post['id']] ?? [];
        if (!empty($post_images)): 
        ?>
            <div class="post-images-slider-wrapper">
                <div class="post-images-slider" id="slider-<?= $post['id'] ?>">
                    <?php foreach ($post_images as $index => $img): ?>
                        <div class="slide-item">
                            <img src="<?= htmlspecialchars($img) ?>" alt="Ảnh bài đăng <?= $index + 1 ?>">
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php if (count($post_images) > 1): ?>
                    <!-- Nút chuyển ảnh -->
                    <button class="slider-btn prev" onclick="moveSlider(<?= $post['id'] ?>, -1)">&#10094;</button>
                    <button class="slider-btn next" onclick="moveSlider(<?= $post['id'] ?>, 1)">&#10095;</button>
                    <!-- Chỉ số chấm tròn -->
                    <div class="slider-dots">
                        <?php foreach ($post_images as $index => $img): ?>
                            <span class="dot <?= $index === 0 ? 'active' : '' ?>" onclick="currentSlide(<?= $post['id'] ?>, <?= $index ?>)"></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
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

// Xử lý xem trước ảnh tải lên (tối đa 10 ảnh)
const fileInput = document.getElementById('post_images');
const previewContainer = document.getElementById('image-preview-container');

if (fileInput && previewContainer) {
    fileInput.addEventListener('change', function() {
        previewContainer.innerHTML = '';
        const files = Array.from(this.files);
        
        if (files.length > 10) {
            alert('Bạn chỉ được đăng tối đa 10 ảnh trong một bài viết!');
            this.value = ''; // Reset input
            return;
        }
        
        files.forEach(file => {
            if (!file.type.startsWith('image/')) return;
            
            const reader = new FileReader();
            reader.onload = function(e) {
                const imgWrapper = document.createElement('div');
                imgWrapper.className = 'preview-item';
                
                const img = document.createElement('img');
                img.src = e.target.result;
                
                imgWrapper.appendChild(img);
                previewContainer.appendChild(imgWrapper);
            };
            reader.readAsDataURL(file);
        });
    });
}

// Trạng thái lưu index hoạt động của các slider ảnh
const sliderStates = {};

function moveSlider(postId, direction) {
    const slider = document.getElementById('slider-' + postId);
    if (!slider) return;
    const slides = slider.querySelectorAll('.slide-item');
    const totalSlides = slides.length;
    if (totalSlides <= 1) return;

    if (!(postId in sliderStates)) {
        sliderStates[postId] = 0;
    }

    sliderStates[postId] = (sliderStates[postId] + direction + totalSlides) % totalSlides;
    updateSlider(postId);
}

function currentSlide(postId, index) {
    sliderStates[postId] = index;
    updateSlider(postId);
}

function updateSlider(postId) {
    const slider = document.getElementById('slider-' + postId);
    const index = sliderStates[postId] || 0;
    slider.style.transform = `translateX(-${index * 100}%)`;
    
    // Cập nhật trạng thái các dot chỉ mục
    const wrapper = slider.closest('.post-images-slider-wrapper');
    const dots = wrapper.querySelectorAll('.slider-dots .dot');
    dots.forEach((dot, idx) => {
        if (idx === index) {
            dot.classList.add('active');
        } else {
            dot.classList.remove('active');
        }
    });
}
</script>

<script>
// ===== 3-DOT MENU =====
function toggleMenu(postId, event) {
    event.stopPropagation();
    const menu = document.getElementById('menu-' + postId);
    const isOpen = menu.classList.contains('open');
    // Đóng tất cả menu đang mở
    document.querySelectorAll('.post-menu-dropdown.open').forEach(m => m.classList.remove('open'));
    if (!isOpen) menu.classList.add('open');
}

// Đóng menu khi click ra ngoài
document.addEventListener('click', function() {
    document.querySelectorAll('.post-menu-dropdown.open').forEach(m => m.classList.remove('open'));
});

// ===== XÓA BÀI ĐĂNG =====
function deletePost(postId, btn) {
    if (!confirm('Bạn có chắc muốn xóa bài đăng này không?')) return;

    btn.disabled = true;
    btn.textContent = 'Đang xóa...';

    fetch('ajax_delete_post.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'post_id=' + postId
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            // Tìm post-card cha và chạy hiệu ứng xóa
            const card = btn.closest('.post-card');
            card.classList.add('post-removing');
            card.addEventListener('animationend', () => card.remove());
        } else {
            alert(data.message || 'Không thể xóa bài.');
            btn.disabled = false;
            btn.textContent = '🗑️ Xóa bài đăng';
        }
    })
    .catch(() => {
        alert('Lỗi kết nối. Vui lòng thử lại.');
        btn.disabled = false;
        btn.textContent = '🗑️ Xóa bài đăng';
    });
}
</script>

<?php require_once 'includes/footer.php'; ?>