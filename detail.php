<?php
require_once 'includes/db-connect.php';
require_once 'includes/header.php';

if (!isset($_GET['id'])) { die("Không tìm thấy khách sạn."); }
$id = $_GET['id'];

// 1. Lấy thông tin chung của khách sạn
$stmt = $pdo->prepare("SELECT * FROM hotels WHERE id = ?");
$stmt->execute([$id]);
$hotel = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$hotel) { die("Khách sạn không tồn tại."); }

// 2. Lấy hình ảnh bằng cách QUÉT THƯ MỤC (Xóa lệnh SQL lấy ảnh cũ)
// Tìm tất cả file bắt đầu bằng hotel_{id}_
$image_pattern = "uploads/hotel_" . $id . "_*.*";
$images = glob($image_pattern);

// 3. Lấy phòng & giá
$stmt_room = $pdo->prepare("SELECT * FROM rooms WHERE hotel_id = ?");
$stmt_room->execute([$id]);
$rooms = $stmt_room->fetchAll(PDO::FETCH_ASSOC);

// 4. Lấy tiện nghi
$stmt_amen = $pdo->prepare("SELECT a.name FROM amenities a JOIN hotel_amenities ha ON a.id = ha.amenity_id WHERE ha.hotel_id = ?");
$stmt_amen->execute([$id]);
$amenities = $stmt_amen->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="detail-container" style="background: #fff; padding: 20px; border-radius: 8px; margin-top: 20px;">
    <!-- Khu vực 1: Banner / Hình ảnh -->
    <div class="hotel-gallery" style="display: flex; gap: 10px; overflow-x: auto; margin-bottom: 20px;">
    <?php if (count($images) > 0): ?>
        <?php foreach($images as $img_path): ?>
            <img src="<?= htmlspecialchars($img_path) ?>" style="height: 300px; border-radius: 8px; object-fit: cover;">
        <?php endforeach; ?>
    <?php else: ?>
        <!-- Nếu thư mục không có ảnh nào, hiển thị ảnh mặc định -->
        <img src="https://via.placeholder.com/600x400?text=No+Image" style="height: 300px; border-radius: 8px; object-fit: cover;">
    <?php endif; ?>
</div>

    <!-- Khu vực 2: Thông tin chung -->
    <h1><?= htmlspecialchars($hotel['name']) ?></h1>
    <p style="color: #666;">📍 <?= htmlspecialchars($hotel['address']) ?> | ⭐ <?= $hotel['star_rating'] ?> Sao | Vibe: <?= htmlspecialchars($hotel['vibe']) ?></p>
    
    <div style="margin: 20px 0;">
        <h3>Mô tả:</h3>
        <p><?= nl2br(htmlspecialchars($hotel['description'])) ?></p>
    </div>

    <div style="margin: 20px 0;">
        <h3>Tiện nghi nổi bật:</h3>
        <ul>
            <?php foreach($amenities as $amenity): ?>
                <li>✔️ <?= htmlspecialchars($amenity['name']) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>

    <!-- Khu vực 3: Bảng giá phòng -->
    <h3>Bảng giá phòng:</h3>
    <table class="compare-table">
        <tr>
            <th>Loại phòng</th>
            <th>Mức giá / Đêm</th>
            <th>Trạng thái</th>
        </tr>
        <?php foreach($rooms as $room): ?>
        <tr>
            <td>Phòng cho <?= $room['capacity'] ?> người</td>
            <td style="color: red; font-weight: bold;"><?= number_format($room['price']) ?> đ</td>
            <td><button class="btn-primary">Đặt phòng</button></td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

<?php require_once 'includes/footer.php'; ?>