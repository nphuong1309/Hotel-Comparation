<?php
/** PROFILE.PHP: lịch sử so sánh và các bài cộng đồng của người dùng đăng nhập. */
require_once 'includes/bootstrap.php';
require_login();
require_once 'includes/header.php';

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
        $my_post_images[$pi['post_id']][] = $pi['image_url'];
    }
}
?>

<div class="profile-page">
    <h2>Tài khoản của tôi</h2>
    <p class="profile-greeting">Xin chào, <b><?= htmlspecialchars($_SESSION['username']) ?></b></p>

    <section class="profile-section">
        <h3>🔍 Lịch sử so sánh</h3>
        <?php if (count($history) > 0): ?>
            <?php foreach ($history as $h):
                $ids = array_filter(explode(',', $h['hotel_ids']));
                $names = array_map(fn($id) => $hotel_names[(int) $id], $ids);
            ?>
                <div class="profile-card">
                    <div class="profile-card-row">
                        <span><?= htmlspecialchars(implode(', ', $names)) ?></span>
                        <a href="compare.php?<?= http_build_query(['hotel_ids' => $ids]) ?>" class="btn-outline profile-nowrap">Xem lại</a>
                    </div>
                    <div class="profile-meta">
                        <?= date('d/m/Y H:i', strtotime($h['created_at'])) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="profile-empty">Bạn chưa so sánh khách sạn nào. <a href="index.php">Khám phá ngay &raquo;</a></p>
        <?php endif; ?>
    </section>

    <section>
        <h3>📝 Bài đăng của tôi trong Cộng đồng</h3>
        <?php if (count($my_posts) > 0): ?>
            <?php foreach ($my_posts as $post): ?>
                <?php $post_imgs = $my_post_images[$post['id']] ?? []; ?>
                <div class="profile-card">
                    <?php if (!empty($post_imgs)): ?>
                        <div class="profile-image-grid">
                            <?php foreach (array_slice($post_imgs, 0, 4) as $idx => $pimg): ?>
                                <?php if ($idx === 3 && count($post_imgs) > 4): ?>
                                    <div class="profile-image-more">
                                        <img src="<?= htmlspecialchars($pimg) ?>" class="profile-thumbnail" alt="Ảnh bài đăng">
                                        <div class="profile-image-overlay">+<?= count($post_imgs) - 3 ?></div>
                                    </div>
                                <?php else: ?>
                                    <img src="<?= htmlspecialchars($pimg) ?>" class="profile-thumbnail" alt="Ảnh bài đăng">
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <p class="profile-post-content"><?= nl2br(htmlspecialchars($post['content'])) ?></p>
                    <div class="profile-card-row profile-meta">
                        <span><?= date('d/m/Y H:i', strtotime($post['created_at'])) ?></span>
                        <span>❤️ <?= $post['likes_count'] ?> lượt thích</span>
                    </div>
                    <a href="community.php#post-<?= (int) $post['id'] ?>" class="btn-outline profile-post-link">
                        Xem bài đăng
                    </a>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="profile-empty">Bạn chưa có bài đăng nào. <a href="community.php">Chia sẻ trải nghiệm ngay &raquo;</a></p>
        <?php endif; ?>
    </section>
</div>

<?php require_once 'includes/footer.php'; ?>
