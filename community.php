<?php
/** COMMUNITY.PHP: bảng tin cộng đồng, tạo bài, upload ảnh và tạo bình luận. */
require_once 'includes/bootstrap.php';

// Kiểm tra trạng thái đăng nhập
$is_logged_in = isset($_SESSION['user_id']);
$current_username = $is_logged_in ? $_SESSION['username'] : '';

/* CHÚ Ý LÔ-GÍC: 
   Chỉ xử lý Đăng bài và Bình luận nếu người dùng đã đăng nhập.
*/

// 1. XỬ LÝ ĐĂNG BÀI VIẾT (POST)
if ($is_logged_in && isset($_POST['submit_post'])) {
    require_csrf();
    $hotel_id = positive_int($_POST['hotel_id'] ?? null);
    $content = trim((string) ($_POST['content'] ?? ''));
    $author_id = (int) $_SESSION['user_id'];

    if ($content !== '' && mb_strlen($content) <= 5000) {
        $savedImages = [];
        try {
            if ($hotel_id !== null) {
                $hotelStmt = $pdo->prepare('SELECT 1 FROM hotels WHERE id = ?');
                $hotelStmt->execute([$hotel_id]);
                $hotel_id = $hotelStmt->fetchColumn() ? $hotel_id : null;
            }

            $pdo->beginTransaction();
            $stmt = $pdo->prepare('INSERT INTO feed_posts (author_name, author_id, hotel_id, content) VALUES (?, ?, ?, ?)');
            $stmt->execute([$current_username, $author_id, $hotel_id, $content]);
            $post_id = (int) $pdo->lastInsertId();

            if (isset($_FILES['post_images'])) {
                $savedImages = store_uploaded_images($_FILES['post_images'], "post_{$post_id}_{$author_id}", 10);
                $stmt_img = $pdo->prepare('INSERT INTO feed_post_images (post_id, image_url) VALUES (?, ?)');
                foreach ($savedImages as $imagePath) {
                    $stmt_img->execute([$post_id, $imagePath]);
                }
            }

            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            foreach ($savedImages as $imagePath) {
                delete_upload_file($imagePath);
            }
            error_log('Community post creation failed: ' . $exception->getMessage());
            $_SESSION['flash_error'] = $exception instanceof RuntimeException
                ? $exception->getMessage()
                : 'Không thể đăng bài lúc này.';
        }
    } else {
        $_SESSION['flash_error'] = 'Nội dung bài viết phải có từ 1 đến 5.000 ký tự.';
    }

    redirect('community.php');
}

// 2. XỬ LÝ ĐĂNG BÌNH LUẬN (COMMENT)
if ($is_logged_in && isset($_POST['submit_comment'])) {
    require_csrf();
    $post_id = positive_int($_POST['post_id'] ?? null);
    $comment_content = trim((string) ($_POST['comment_content'] ?? ''));

    if ($post_id !== null && $comment_content !== '' && mb_strlen($comment_content) <= 1000) {
        $stmt = $pdo->prepare("INSERT INTO feed_comments (post_id, author_name, content) VALUES (?, ?, ?)");
        $stmt->execute([$post_id, $current_username, $comment_content]);
    }

    redirect('community.php');
}

// 3. LẤY DỮ LIỆU ĐỂ HIỂN THỊ
$hotels = $pdo->query("SELECT id, name FROM hotels ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$posts = $pdo->query("SELECT p.*, h.name as hotel_name FROM feed_posts p LEFT JOIN hotels h ON p.hotel_id = h.id ORDER BY p.id DESC")->fetchAll(PDO::FETCH_ASSOC);

// Lấy danh sách ảnh cho các bài đăng
$post_ids = array_column($posts, 'id');
$images_by_post = [];
$comments_by_post = [];
$liked_post_ids = [];
if (!empty($post_ids)) {
    $placeholders = implode(',', array_fill(0, count($post_ids), '?'));
    $stmt_images = $pdo->prepare("SELECT * FROM feed_post_images WHERE post_id IN ($placeholders) ORDER BY id ASC");
    $stmt_images->execute($post_ids);
    $images = $stmt_images->fetchAll(PDO::FETCH_ASSOC);
    foreach ($images as $img) {
        $imagePath = ltrim(str_replace('\\', '/', trim((string) $img['image_url'])), '/');
        $absoluteImagePath = __DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $imagePath);
        if ($imagePath !== '' && !str_contains($imagePath, '..') && is_file($absoluteImagePath)) {
            $images_by_post[$img['post_id']][] = $imagePath;
        }
    }

    $stmt_comments = $pdo->prepare("SELECT * FROM feed_comments WHERE post_id IN ($placeholders) ORDER BY id ASC");
    $stmt_comments->execute($post_ids);
    foreach ($stmt_comments->fetchAll(PDO::FETCH_ASSOC) as $comment) {
        $comments_by_post[$comment['post_id']][] = $comment;
    }

    if ($is_logged_in) {
        $likeParams = array_merge([(int) $_SESSION['user_id']], $post_ids);
        $stmt_likes = $pdo->prepare("SELECT post_id FROM feed_post_likes WHERE user_id = ? AND post_id IN ($placeholders)");
        $stmt_likes->execute($likeParams);
        $liked_post_ids = array_map('intval', $stmt_likes->fetchAll(PDO::FETCH_COLUMN));
    }
}

$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_error']);

require_once 'includes/header.php';
?>

<section class="community-page">
    <header class="community-hero">
        <div class="community-hero__copy">
            <p class="page-eyebrow">Góc nhìn từ người thật</p>
            <h1>Cộng đồng JoyTix</h1>
            <p>Cảm nhận, hình ảnh và những mẹo lưu trú thực tế từ cộng đồng yêu Cần Thơ.</p>
        </div>
        <div class="community-hero__stats" aria-label="Thống kê cộng đồng">
            <div><strong><?= count($posts) ?></strong><span>Bài chia sẻ</span></div>
            <div><strong><?= count($hotels) ?></strong><span>Điểm lưu trú</span></div>
        </div>
    </header>

    <div class="community-layout">
    <div class="community-feed">
    <?php if ($flashError): ?>
        <div class="alert alert-error"><?= e($flashError) ?></div>
    <?php endif; ?>
    
    <!-- KHU VỰC ĐĂNG BÀI -->
    <?php if ($is_logged_in): ?>
        <div class="post-card community-composer">
            <div class="community-composer__author">
                <div class="avatar"><?= e(mb_strtoupper(mb_substr($current_username, 0, 1))) ?></div>
                <span class="community-composer__greeting">Cùng chia sẻ trải nghiệm của bạn, <b><?= htmlspecialchars($current_username) ?></b>!</span>
            </div>
            <form action="" method="POST" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <select name="hotel_id" class="community-composer__field">
                    <option value="">-- Gắn thẻ khách sạn (Tùy chọn) --</option>
                    <?php foreach($hotels as $h): ?>
                        <option value="<?= $h['id'] ?>"><?= htmlspecialchars($h['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <textarea name="content" rows="3" placeholder="Góc view phòng hôm nay thế nào?" required class="community-composer__field community-composer__content"></textarea>
                <!-- Khung hiển thị preview ảnh -->
                <div id="image-preview-container" class="preview-grid"></div>
                <div class="community-composer__actions">
                    <input type="file" name="post_images[]" id="post_images" accept="image/*" multiple class="community-composer__file">
                    <button type="submit" name="submit_post" class="btn-primary community-composer__submit">Đăng bài</button>
                </div>
            </form>
        </div>
    <?php else: ?>
        <!-- Nhắc nhở đăng nhập nếu là Khách vãng lai -->
        <div class="login-overlay">
            <h3 class="login-overlay__title">Bạn đã có một chuyến đi tuyệt vời?</h3>
            <p class="login-overlay__description">Đăng nhập để chia sẻ hình ảnh, cảm nhận và trao đổi cùng cộng đồng JoyTix.</p>
            <a href="auth.php?action=login" class="btn-primary login-overlay__link">Đăng nhập để tham gia</a>
        </div>
    <?php endif; ?>

    <!-- KHU VỰC BẢNG TIN -->
    <?php foreach($posts as $post): ?>
    <div class="post-card" id="post-<?= (int) $post['id'] ?>">
        <!-- Header -->
        <div class="post-header">
            <div class="avatar" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($post['author_name'], 0, 1))) ?></div>
            <div class="post-author-copy">
                <div class="author-name"><?= htmlspecialchars($post['author_name']) ?></div>
                <div class="post-time">
                    <?= date('d/m/Y H:i', strtotime($post['created_at'])) ?>
                    <?php if($post['hotel_name']): ?>
                        <span aria-hidden="true">·</span> Đang ở <a href="detail.php?id=<?= $post['hotel_id'] ?>" class="hotel-tag"><?= htmlspecialchars($post['hotel_name']) ?></a>
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
                    <button class="menu-item-delete" onclick="deletePost(<?= $post['id'] ?>, this)">Xóa bài đăng</button>
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
                            <img src="<?= e($img) ?>" alt="Ảnh bài đăng <?= $index + 1 ?>" loading="lazy" decoding="async">
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
                <button class="btn-action btn-like <?= in_array((int) $post['id'], $liked_post_ids, true) ? 'is-liked' : '' ?>" data-id="<?= $post['id'] ?>" aria-pressed="<?= in_array((int) $post['id'], $liked_post_ids, true) ? 'true' : 'false' ?>">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8l1.1 1.1L12 21l7.8-7.5 1.1-1.1a5.5 5.5 0 0 0-.1-7.8Z"></path></svg>
                    <span class="action-label">Yêu thích</span> <span class="like-count"><?= $post['likes_count'] ?></span>
                </button>
            <?php else: ?>
                <button class="btn-action" onclick="alert('Vui lòng đăng nhập để thả tim!');">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8l1.1 1.1L12 21l7.8-7.5 1.1-1.1a5.5 5.5 0 0 0-.1-7.8Z"></path></svg>
                    <span class="action-label">Yêu thích</span> <span class="like-count"><?= $post['likes_count'] ?></span>
                </button>
            <?php endif; ?>
            <span class="btn-action"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4Z"></path></svg><span class="action-label">Bình luận</span> <?= count($comments_by_post[$post['id']] ?? []) ?></span>
        </div>

        <!-- Khu vực Comment -->
        <div class="comment-section">
            <?php
            $comments = $comments_by_post[$post['id']] ?? [];
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
                    <?= csrf_field() ?>
                    <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                    <input type="text" name="comment_content" class="comment-input" placeholder="Thêm bình luận..." required autocomplete="off">
                    <button type="submit" name="submit_comment" class="btn-post-cmt">Đăng</button>
                </form>
            <?php else: ?>
                <div class="community-comment-login">
                    <a href="auth.php?action=login">Đăng nhập</a> để bình luận bài viết này.
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
    </div>

    <aside class="community-sidebar">
        <section class="community-sidebar-card community-sidebar-card--accent">
            <p class="page-eyebrow">Cùng nhau khám phá</p>
            <h2>Mỗi trải nghiệm đều hữu ích</h2>
            <p>Một góc nhìn thật có thể giúp người khác chọn nơi lưu trú phù hợp hơn.</p>
            <?php if ($is_logged_in): ?>
                <a href="#post_images" class="btn-primary">Viết bài chia sẻ</a>
            <?php else: ?>
                <a href="auth.php?action=register" class="btn-primary">Tham gia JoyTix</a>
            <?php endif; ?>
        </section>

        <section class="community-sidebar-card">
            <h2>Gợi ý khi chia sẻ</h2>
            <ul class="community-tips">
                <li>Nêu rõ điểm bạn thích và điều cần lưu ý.</li>
                <li>Chọn ảnh rõ, đúng với trải nghiệm thực tế.</li>
                <li>Tôn trọng người đăng và trao đổi lịch sự.</li>
            </ul>
        </section>

        <section class="community-sidebar-card">
            <h2>Khám phá khách sạn</h2>
            <div class="community-hotel-links">
                <?php foreach (array_slice($hotels, 0, 4) as $hotel): ?>
                    <a href="detail.php?id=<?= (int) $hotel['id'] ?>"><span><?= e(mb_strtoupper(mb_substr($hotel['name'], 0, 1))) ?></span><?= e($hotel['name']) ?></a>
                <?php endforeach; ?>
            </div>
        </section>
    </aside>
    </div>
</section>

<!-- Script xử lý nút Thả tim bằng AJAX -->

<?php require_once 'includes/footer.php'; ?>
