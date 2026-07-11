<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
require_once 'includes/db-connect.php';
require_once 'includes/header.php';

$user_id = $_SESSION['user_id'];

// 1. Lịch sử so sánh
$stmt = $pdo->prepare("SELECT * FROM comparison_history WHERE user_id = ? ORDER BY created_at DESC LIMIT 20");
$stmt->execute([$user_id]);
$history_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);

$history = [];
$seen_history_keys = [];
foreach ($history_raw as $item) {
    $ids = array_values(array_unique(array_filter(array_map('trim', explode(',', $item['hotel_ids'])))));
    sort($ids, SORT_NUMERIC);
    $history_key = implode(',', $ids);

    if (isset($seen_history_keys[$history_key])) {
        continue;
    }

    $seen_history_keys[$history_key] = true;
    $item['hotel_ids'] = $history_key;
    $history[] = $item;
}

$all_ids = [];
foreach ($history as $h) {
    $all_ids = array_merge($all_ids, explode(',', $h['hotel_ids']));
}
$all_ids = array_unique(array_filter($all_ids));

$hotel_names = [];
if (!empty($all_ids)) {
    $placeholders = implode(',', array_fill(0, count($all_ids), '?'));
    $stmt_names = $pdo->prepare("SELECT id, name FROM hotels WHERE id IN ($placeholders)");
    $stmt_names->execute(array_values($all_ids));
    foreach ($stmt_names->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $hotel_names[$row['id']] = $row['name'];
    }
}

// 2. Bài đăng của tôi trong Cộng đồng
$stmt_posts = $pdo->prepare("SELECT * FROM feed_posts WHERE author_id = ? OR (author_id IS NULL AND author_name = ?) ORDER BY id DESC");
$stmt_posts->execute([$user_id, $_SESSION['username']]);
$my_posts = $stmt_posts->fetchAll(PDO::FETCH_ASSOC);
?>

<div style="max-width: 700px; margin: 30px auto;">
    <h2>Tài khoản của tôi</h2>
    <p style="color:#666; margin-bottom:25px;">Xin chào, <b><?= htmlspecialchars($_SESSION['username']) ?></b></p>

    <section style="margin-bottom:30px;">
        <h3 style="margin-bottom:15px;">🔍 Lịch sử so sánh</h3>
        <?php if (count($history) > 0): ?>
            <?php foreach ($history as $h):
                $ids = array_filter(explode(',', $h['hotel_ids']));
                $names = array_map(fn($id) => $hotel_names[$id] ?? "KS #$id", $ids);
            ?>
                <div style="background:#fff; padding:15px 20px; border-radius:8px; margin-bottom:12px; box-shadow:0 1px 4px rgba(0,0,0,0.08);">
                    <div style="display:flex; justify-content:space-between; align-items:center; gap:10px;">
                        <span><?= htmlspecialchars(implode(', ', $names)) ?></span>
                        <a href="compare.php?<?= http_build_query(['hotel_ids' => $ids]) ?>" class="btn-outline" style="white-space:nowrap;">Xem lại</a>
                    </div>
                    <div style="font-size:12px; color:#999; margin-top:6px;">
                        <?= date('d/m/Y H:i', strtotime($h['created_at'])) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="color:#999;">Bạn chưa so sánh khách sạn nào. <a href="index.php">Khám phá ngay &raquo;</a></p>
        <?php endif; ?>
    </section>

    <section>
        <h3 style="margin-bottom:15px;">📝 Bài đăng của tôi trong Cộng đồng</h3>
        <?php if (count($my_posts) > 0): ?>
            <?php foreach ($my_posts as $post): ?>
                <div style="background:#fff; padding:15px 20px; border-radius:8px; margin-bottom:12px; box-shadow:0 1px 4px rgba(0,0,0,0.08);">
                    <p style="margin-bottom:8px;"><?= nl2br(htmlspecialchars($post['content'])) ?></p>
                    <div style="font-size:12px; color:#999; display:flex; justify-content:space-between;">
                        <span><?= date('d/m/Y H:i', strtotime($post['created_at'])) ?></span>
                        <span>❤️ <?= $post['likes_count'] ?> lượt thích</span>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="color:#999;">Bạn chưa có bài đăng nào. <a href="community.php">Chia sẻ trải nghiệm ngay &raquo;</a></p>
        <?php endif; ?>
    </section>
</div>

<?php require_once 'includes/footer.php'; ?>