<?php
// search.php
require_once 'includes/db-connect.php';
require_once 'includes/header.php'; // Gắn header để lấy thanh menu và CSS

// 1. Nhận dữ liệu từ form trang chủ (Có gán giá trị mặc định nếu người dùng truy cập trực tiếp link)
$capacity = isset($_GET['capacity']) ? (int)$_GET['capacity'] : 2;
$budget = isset($_GET['budget']) ? (int)$_GET['budget'] : 3000000;
$vibe = isset($_GET['vibe']) ? trim($_GET['vibe']) : 'Hiện đại';

// 2. Lệnh SQL thông minh (JOIN 3 bảng: hotels, rooms và truy vấn phụ lấy ảnh bìa)
// Giải thích logic:
// - r.capacity >= :capacity : Chấp nhận phòng có sức chứa bằng hoặc lớn hơn số người đi
// - r.price <= :budget : Giá phòng phải nhỏ hơn hoặc bằng mức ngân sách khách hàng kéo trên thanh trượt
$sql = "SELECT h.id, h.name, h.address, h.vibe, r.price,
        (SELECT image_url FROM hotel_images WHERE hotel_id = h.id AND is_primary = 1 LIMIT 1) as image_url
        FROM hotels h
        JOIN rooms r ON h.id = r.hotel_id
        WHERE r.capacity >= :capacity 
        AND r.price <= :budget 
        AND h.vibe = :vibe
        ORDER BY r.price ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    'capacity' => $capacity,
    'budget' => $budget,
    'vibe' => $vibe
]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<section class="hotel-list-section" style="margin-top: 30px;">
    <h2>Kết quả gợi ý phù hợp nhất:</h2>
    <p style="margin-bottom: 25px; color: #666; background: #fff; padding: 15px; border-radius: 8px; border-left: 5px solid var(--primary);">
        Bạn đang tìm: <strong><?= $capacity ?> người</strong> |
        Ngân sách tối đa: <strong><?= number_format($budget) ?> đ/đêm</strong> |
        Phong cách: <strong><?= htmlspecialchars($vibe) ?></strong>
    </p>

    <div class="hotel-grid">
        <?php if (count($results) > 0): ?>
            <?php foreach ($results as $hotel): ?>
                <article class="hotel-card">
                    <!-- Hiển thị ảnh bìa, nếu lỗi ảnh thì dùng ảnh placeholder -->
                    <img src="<?= htmlspecialchars($hotel['image_url'] ?: 'https://via.placeholder.com/400x250') ?>" alt="<?= htmlspecialchars($hotel['name']) ?>">

                    <div class="card-content">
                        <h3><?= htmlspecialchars($hotel['name']) ?></h3>
                        <p style="color: #666; font-size: 14px; margin-bottom: 10px;">📍 <?= htmlspecialchars($hotel['address']) ?></p>

                        <p class="price">Mức giá khớp: <?= number_format($hotel['price']) ?> đ</p>

                        <div class="card-actions" style="margin-top: 15px;">
                            <a href="detail.php?id=<?= $hotel['id'] ?>" class="btn-primary" style="display: block; width: 100%; text-align: center; text-decoration: none;">Xem chi tiết & Đặt phòng</a>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php else: ?>
            <!-- Trạng thái không tìm thấy kết quả -->
            <div style="grid-column: 1 / -1; text-align: center; padding: 50px; background: #fff; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                <p style="color: red; font-size: 18px; margin-bottom: 15px;">Rất tiếc, không có khách sạn nào khớp với toàn bộ tiêu chí của bạn.</p>
                <a href="index.php" class="btn-outline" style="text-decoration: none;">&laquo; Thay đổi tiêu chí tìm kiếm</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>