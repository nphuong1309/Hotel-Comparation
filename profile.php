<?php
/** PROFILE.PHP: lịch sử so sánh và các bài cộng đồng của người dùng đăng nhập. */
require_once 'includes/bootstrap.php';
require_login();

$user_id = $_SESSION['user_id'];

// 1. Lịch sử so sánh
$stmt = $pdo->prepare("SELECT * FROM comparison_history WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$history_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);

$normalized_history = [];
$all_ids = [];
foreach ($history_raw as $item) {
    $ids = [];
    foreach (explode(',', (string) $item['hotel_ids']) as $raw_id) {
        $hotel_id = positive_int(trim($raw_id));
        if ($hotel_id !== null) {
            $ids[$hotel_id] = $hotel_id;
        }
    }

    $ids = array_values($ids);
    sort($ids, SORT_NUMERIC);
    $item['_hotel_ids'] = $ids;
    $normalized_history[] = $item;
    $all_ids = array_merge($all_ids, $ids);
}
$all_ids = array_values(array_unique($all_ids));

$hotel_names = [];
if (!empty($all_ids)) {
    $placeholders = implode(',', array_fill(0, count($all_ids), '?'));
    $stmt_names = $pdo->prepare("SELECT id, name FROM hotels WHERE id IN ($placeholders)");
    $stmt_names->execute(array_values($all_ids));
    foreach ($stmt_names->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $hotel_names[(int) $row['id']] = $row['name'];
    }
}

$history = [];
$stale_history_ids = [];
$seen_history_keys = [];
foreach ($normalized_history as $item) {
    $ids = $item['_hotel_ids'];
    $has_missing_hotel = empty($ids);

    foreach ($ids as $hotel_id) {
        if (!isset($hotel_names[$hotel_id])) {
            $has_missing_hotel = true;
            break;
        }
    }

    if ($has_missing_hotel) {
        $stale_history_ids[] = (int) $item['id'];
        continue;
    }

    $history_key = implode(',', $ids);
    if (isset($seen_history_keys[$history_key])) {
        continue;
    }

    $seen_history_keys[$history_key] = true;
    if (count($history) < 20) {
        $item['hotel_ids'] = $history_key;
        unset($item['_hotel_ids']);
        $history[] = $item;
    }
}

if (!empty($stale_history_ids)) {
    $stale_placeholders = implode(',', array_fill(0, count($stale_history_ids), '?'));
    $delete_stale = $pdo->prepare(
        "DELETE FROM comparison_history
         WHERE user_id = ? AND id IN ($stale_placeholders)"
    );
    $delete_stale->execute(array_merge([(int) $user_id], $stale_history_ids));
}

// 2. Bài đăng của tôi trong Cộng đồng
$stmt_posts = $pdo->prepare("SELECT * FROM feed_posts WHERE author_id = ? OR (author_id IS NULL AND author_name = ?) ORDER BY id DESC");
$stmt_posts->execute([$user_id, $_SESSION['username']]);
$my_posts = $stmt_posts->fetchAll(PDO::FETCH_ASSOC);

// Lấy ảnh của các bài đăng
$my_post_ids = array_column($my_posts, 'id');
$my_post_images = [];
if (!empty($my_post_ids)) {
    $ph = implode(',', array_fill(0, count($my_post_ids), '?'));
    $stmt_pi = $pdo->prepare("SELECT * FROM feed_post_images WHERE post_id IN ($ph) ORDER BY id ASC");
    $stmt_pi->execute($my_post_ids);
    foreach ($stmt_pi->fetchAll(PDO::FETCH_ASSOC) as $pi) {
        $imagePath = ltrim(str_replace('\\', '/', trim((string) $pi['image_url'])), '/');
        $absoluteImagePath = __DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $imagePath);
        if ($imagePath !== '' && !str_contains($imagePath, '..') && is_file($absoluteImagePath)) {
            $my_post_images[$pi['post_id']][] = $imagePath;
        }
    }
}

$profileUsername = (string) $_SESSION['username'];
$profileInitial = mb_strtoupper(mb_substr($profileUsername, 0, 1));
require_once 'includes/header.php';
?>

<div class="profile-page">
    <header class="profile-hero">
        <div class="profile-identity">
            <div class="profile-avatar" aria-hidden="true"><?= e($profileInitial) ?></div>
            <div>
                <p class="page-eyebrow">Không gian cá nhân</p>
                <h1>Tài khoản của tôi</h1>
                <p class="profile-greeting">Xin chào <strong><?= e($profileUsername) ?></strong>, xem lại hành trình và những trải nghiệm bạn đã chia sẻ.</p>
            </div>
        </div>
        <div class="profile-overview" aria-label="Tổng quan tài khoản">
            <div class="profile-stat"><strong><?= count($history) ?></strong><span>Lần so sánh</span></div>
            <div class="profile-stat"><strong><?= count($my_posts) ?></strong><span>Bài chia sẻ</span></div>
            <a href="community.php" class="btn-primary">Đến Cộng đồng</a>
        </div>
    </header>

    <section class="profile-section">
        <div class="profile-section-heading">
            <span class="profile-section-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-4-4"></path></svg>
            </span>
            <div><p class="page-eyebrow">Quyết định gần đây</p><h2>Lịch sử so sánh</h2></div>
        </div>
        <?php if (count($history) > 0): ?>
            <div class="profile-history-list">
                <?php foreach ($history as $h):
                    $ids = array_filter(explode(',', $h['hotel_ids']));
                    $names = array_map(fn($id) => $hotel_names[(int) $id], $ids);
                ?>
                    <article class="profile-history-card">
                        <div class="profile-history-main">
                            <div class="profile-history-count"><?= count($ids) ?> khách sạn</div>
                            <div class="profile-hotel-chips">
                                <?php foreach ($names as $name): ?><span><?= e($name) ?></span><?php endforeach; ?>
                            </div>
                            <time datetime="<?= e(date('c', strtotime($h['created_at']))) ?>"><?= date('d/m/Y · H:i', strtotime($h['created_at'])) ?></time>
                        </div>
                        <a href="compare.php?<?= http_build_query(['hotel_ids' => $ids]) ?>" class="btn-outline profile-nowrap">Xem lại so sánh</a>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="profile-empty-state"><h3>Chưa có lịch sử so sánh</h3><p>Chọn từ hai khách sạn để đặt các tiêu chí cạnh nhau và tìm lựa chọn phù hợp.</p><a href="index.php#hotel-showcase" class="btn-outline">Khám phá khách sạn</a></div>
        <?php endif; ?>
    </section>

    <section class="profile-section">
        <div class="profile-section-heading">
            <span class="profile-section-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="M4 5h16v12H7l-3 3Z"></path><path d="M8 9h8M8 13h5"></path></svg>
            </span>
            <div><p class="page-eyebrow">Nhật ký trải nghiệm</p><h2>Bài đăng của tôi</h2></div>
        </div>
        <?php if (count($my_posts) > 0): ?>
            <div class="profile-post-grid">
                <?php foreach ($my_posts as $post): ?>
                    <?php $post_imgs = $my_post_images[$post['id']] ?? []; ?>
                    <article class="profile-post-card">
                        <?php if (!empty($post_imgs)): ?>
                            <div class="profile-post-cover">
                                <img src="<?= e($post_imgs[0]) ?>" alt="Ảnh bài đăng" loading="lazy" decoding="async">
                                <?php if (count($post_imgs) > 1): ?><span>+<?= count($post_imgs) - 1 ?> ảnh</span><?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="profile-post-cover profile-post-cover--empty" aria-hidden="true">
                                <svg viewBox="0 0 24 24"><path d="M4 5h16v14H4Z"></path><path d="m6 16 4-4 3 3 2-2 3 3"></path><circle cx="16" cy="9" r="1.5"></circle></svg>
                            </div>
                        <?php endif; ?>
                        <div class="profile-post-body">
                            <p class="profile-post-content"><?= nl2br(e($post['content'])) ?></p>
                            <div class="profile-post-meta">
                                <time datetime="<?= e(date('c', strtotime($post['created_at']))) ?>"><?= date('d/m/Y · H:i', strtotime($post['created_at'])) ?></time>
                                <span>♥ <?= (int) $post['likes_count'] ?></span>
                            </div>
                            <a href="community.php#post-<?= (int) $post['id'] ?>" class="btn-outline profile-post-link">Xem bài đăng gốc</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="profile-empty-state"><h3>Chưa có bài chia sẻ</h3><p>Đăng ảnh, cảm nhận và mẹo lưu trú để giúp cộng đồng có thêm góc nhìn thực tế.</p><a href="community.php" class="btn-outline">Chia sẻ trải nghiệm</a></div>
        <?php endif; ?>
    </section>
</div>

<?php require_once 'includes/footer.php'; ?>
