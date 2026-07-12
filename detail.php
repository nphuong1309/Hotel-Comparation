<?php
require_once 'includes/db-connect.php';
require_once 'includes/header.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id || $id < 1) {
    die('Không tìm thấy khách sạn.');
}

// 1. Lấy thông tin chung của khách sạn
$stmt = $pdo->prepare('SELECT * FROM hotels WHERE id = ?');
$stmt->execute([$id]);
$hotel = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$hotel) {
    die('Khách sạn không tồn tại.');
}

// Chuẩn hóa hạng sao để hiển thị được số thập phân, ví dụ 3,5 hoặc 4,5.
$starRating = max(0, min(5, (float) ($hotel['star_rating'] ?? 0)));

/**
 * Hiển thị tối đa 5 biểu tượng sao, có hỗ trợ nửa sao.
 * Ví dụ: 4.5 = 4 sao đầy + 1 nửa sao.
 */
function renderHotelStars(float $rating): string
{
    $rating = max(0, min(5, $rating));
    $fullStars = (int) floor($rating);
    $hasHalfStar = ($rating - $fullStars) >= 0.5;

    $html = '<span class="hotel-stars" aria-label="' .
        htmlspecialchars(number_format($rating, 1, ',', '.')) .
        ' trên 5 sao">';

    for ($i = 0; $i < $fullStars; $i++) {
        $html .= '<span class="hotel-star hotel-star-full" aria-hidden="true">★</span>';
    }

    if ($hasHalfStar) {
        $html .= '<span class="hotel-star hotel-star-half" aria-hidden="true">★</span>';
    }

    return $html . '</span>';
}

// 2. Lấy hình ảnh bằng cách quét thư mục uploads
$imagePattern = 'uploads/hotel_' . $id . '_*.*';
$images = glob($imagePattern) ?: [];

// Đưa ảnh primary lên đầu nếu có
usort($images, static function (string $a, string $b): int {
    $aPrimary = strpos($a, '_primary.') !== false;
    $bPrimary = strpos($b, '_primary.') !== false;
    return $bPrimary <=> $aPrimary;
});

// 3. Lấy phòng và giá
$stmtRoom = $pdo->prepare('SELECT * FROM rooms WHERE hotel_id = ? ORDER BY capacity ASC, price ASC');
$stmtRoom->execute([$id]);
$rooms = $stmtRoom->fetchAll(PDO::FETCH_ASSOC);

// 4. Lấy tiện nghi cùng mã icon
$stmtAmenity = $pdo->prepare(
    'SELECT a.name, a.icon
     FROM amenities a
     INNER JOIN hotel_amenities ha ON a.id = ha.amenity_id
     WHERE ha.hotel_id = ?
     ORDER BY a.id ASC'
);
$stmtAmenity->execute([$id]);
$amenities = $stmtAmenity->fetchAll(PDO::FETCH_ASSOC);

/**
 * Trả về SVG nét đơn sắc theo mã icon lưu trong database.
 * Icon dùng currentColor nên luôn hiển thị đen/xám, không có màu riêng.
 */
function amenityIcon(?string $icon): string
{
    $icons = [
        'pool' => '<path d="M3 17c1.2 0 1.8-1 3-1s1.8 1 3 1 1.8-1 3-1 1.8 1 3 1 1.8-1 3-1 1.8 1 3 1"/><path d="M3 21c1.2 0 1.8-1 3-1s1.8 1 3 1 1.8-1 3-1 1.8 1 3 1 1.8-1 3-1 1.8 1 3 1"/><circle cx="15" cy="5" r="2"/><path d="m6 12 3-3 4 2 3-2 5 4"/>',
        'utensils' => '<path d="M7 3v7"/><path d="M4 3v4a3 3 0 0 0 6 0V3"/><path d="M7 10v11"/><path d="M17 3v18"/><path d="M17 3c2.2 1.7 3 4.2 3 7h-3"/>',
        'spa' => '<path d="M12 21c-4.5-2.5-7-5.7-7-9.5 3.2 0 5.6 1.3 7 3.7 1.4-2.4 3.8-3.7 7-3.7 0 3.8-2.5 7-7 9.5Z"/><path d="M12 15.2C8.8 13.2 8 9.4 12 4c4 5.4 3.2 9.2 0 11.2Z"/>',
        'parking' => '<rect x="4" y="3" width="16" height="18" rx="2"/><path d="M9 17V7h4a3 3 0 0 1 0 6H9"/>',
        'wifi' => '<path d="M5 12.5a10 10 0 0 1 14 0"/><path d="M8.5 16a5 5 0 0 1 7 0"/><path d="M12 20h.01"/>',
        'garden' => '<path d="M12 22V10"/><path d="M12 13c-4 0-7-2.5-7-6 4 0 7 2.5 7 6Z"/><path d="M12 10c4 0 7-2.5 7-6-4 0-7 2.5-7 6Z"/><path d="M7 22h10"/>',
        'bar' => '<path d="M4 4h16l-6 7v7"/><path d="M10 22h8"/><path d="M14 18v4"/><path d="M7 7h10"/>',
        'airport-shuttle' => '<path d="M3 7h12l4 5v7H3Z"/><path d="M15 7v5h4"/><circle cx="7" cy="19" r="2"/><circle cx="16" cy="19" r="2"/><path d="M5 11h6"/>',
        'laundry' => '<path d="m8 4 4-2 4 2 4 3-3 4v10H7V11L4 7Z"/><path d="M9 4c.5 1.5 1.5 2.2 3 2.2S14.5 5.5 15 4"/>',
        'tour' => '<path d="m3 6 6-3 6 3 6-3v15l-6 3-6-3-6 3Z"/><path d="M9 3v15"/><path d="M15 6v15"/><circle cx="12" cy="10" r="2"/>',
        'rental' => '<circle cx="6" cy="17" r="3"/><circle cx="18" cy="17" r="3"/><path d="m6 17 4-7h4l4 7"/><path d="M10 10 8 7h3"/><path d="M10 17h8"/>',
        'smoking' => '<path d="M3 15h13v4H3Z"/><path d="M16 15h2v4h-2"/><path d="M20 15h1v4h-1"/><path d="M7 11c0-2 3-2 3-4s-2-2-2-4"/><path d="M13 11c0-1.6 2-1.8 2-3.5"/>',
        'air-hot-water' => '<path d="M8 3v8a4 4 0 1 0 4 0V3a2 2 0 0 0-4 0Z"/><path d="M10 14v.01"/><path d="M17 5c2 1.5 2 3 0 4.5s-2 3 0 4.5"/><path d="M21 5c2 1.5 2 3 0 4.5s-2 3 0 4.5"/>',
        'meeting' => '<circle cx="8" cy="7" r="2"/><circle cx="16" cy="7" r="2"/><circle cx="12" cy="5" r="2"/><path d="M4 21v-3a4 4 0 0 1 4-4h8a4 4 0 0 1 4 4v3"/><path d="M8 14v7"/><path d="M16 14v7"/><path d="M8 18h8"/>',
        'reception' => '<path d="M4 18h16"/><path d="M6 18a6 6 0 0 1 12 0"/><path d="M12 9V6"/><circle cx="12" cy="4" r="1"/><path d="M3 21h18"/>',
        'currency-exchange' => '<path d="M7 7h11l-3-3"/><path d="m18 7-3 3"/><path d="M17 17H6l3 3"/><path d="m6 17 3-3"/><path d="M12 8v8"/><path d="M14.5 10.5c-.5-1-1.3-1.5-2.5-1.5-1.4 0-2.5.7-2.5 1.8 0 2.7 5 1.2 5 3.7 0 1.1-1.1 1.8-2.5 1.8-1.2 0-2.2-.5-2.7-1.5"/>',
    ];

    $paths = $icons[$icon ?? ''] ?? '<circle cx="12" cy="12" r="9"/><path d="m8 12 2.5 2.5L16 9"/>';

    return '<svg class="amenity-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">'
        . $paths
        . '</svg>';
}
?>

<style>
    .detail-container {
        background: #fff;
        padding: 20px;
        border-radius: 8px;
        margin-top: 20px;
    }

    .hotel-gallery {
        display: flex;
        gap: 10px;
        overflow-x: auto;
        margin-bottom: 20px;
    }

    .hotel-gallery img {
        height: 300px;
        max-width: 100%;
        border-radius: 8px;
        object-fit: cover;
    }

    .hotel-summary {
        color: #666;
    }

    .hotel-information {
        display: grid;
        gap: 10px;
        margin: 14px 0 22px;
        color: #555;
    }

    .hotel-information-row {
        display: grid;
        grid-template-columns: 125px minmax(0, 1fr);
        align-items: start;
        gap: 12px;
        line-height: 1.5;
    }

    .hotel-information-label {
        color: #2f2f2f;
        font-weight: 700;
    }

    .hotel-information-value {
        min-width: 0;
        word-break: break-word;
    }

    .hotel-rating-value {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
    }

    .hotel-stars {
        display: inline-flex;
        align-items: center;
        gap: 3px;
        line-height: 1;
    }

    .hotel-star {
        position: relative;
        display: inline-block;
        width: 22px;
        height: 22px;
        color: #d9d9d9;
        font-size: 24px;
        line-height: 22px;
    }

    .hotel-star-full {
        color: #ffc400;
    }

    .hotel-star-half {
        color: #d9d9d9;
    }

    .hotel-star-half::before {
        content: '★';
        position: absolute;
        top: 0;
        left: 0;
        width: 50%;
        overflow: hidden;
        color: #ffc400;
        white-space: nowrap;
    }

    .detail-section {
        margin: 24px 0;
    }

    .amenities-list {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        column-gap: 48px;
        row-gap: 14px;
        margin-top: 14px;
    }

    .amenity-item {
        display: flex;
        align-items: center;
        gap: 11px;
        min-width: 0;
        line-height: 1.45;
    }

    .amenity-icon {
        width: 21px;
        height: 21px;
        flex: 0 0 21px;
        fill: none;
        stroke: #4a4a4a;
        stroke-width: 1.6;
        stroke-linecap: round;
        stroke-linejoin: round;
    }

    .amenity-name {
        color: #333;
        word-break: break-word;
    }

    .amenities-empty {
        color: #777;
        margin: 10px 0 0;
    }


    .room-price-section {
        margin-top: 30px;
    }

    .room-price-title {
        margin-bottom: 14px;
    }

    .room-price-wrapper {
        overflow-x: auto;
        background: #fff;
    }

    .room-price-table {
        width: 100%;
        min-width: 460px;
        table-layout: fixed;
        border-collapse: collapse;
        border: 1px solid #d8cec8;
    }

    .room-price-table th,
    .room-price-table td {
        width: 50%;
        padding: 14px 16px;
        border: 1px solid #d8cec8;
        text-align: center;
        vertical-align: middle;
    }

    .room-price-table th {
        background: #fff;
        color: #2f2f2f;
        font-weight: 700;
    }

    .room-price {
        color: #e65337;
        font-weight: 700;
        white-space: nowrap;
    }

    .rooms-empty {
        color: #777;
        text-align: center !important;
    }

    @media (max-width: 700px) {
        .hotel-information-row {
            grid-template-columns: 1fr;
            gap: 2px;
        }

        .amenities-list {
            grid-template-columns: 1fr;
            row-gap: 13px;
        }

        .hotel-gallery img {
            height: 230px;
        }
    }
</style>

<div class="detail-container">
    <!-- Khu vực 1: Banner / Hình ảnh -->
    <div class="hotel-gallery">
        <?php if ($images): ?>
            <?php foreach ($images as $imagePath): ?>
                <img src="<?= htmlspecialchars($imagePath) ?>" alt="Hình ảnh <?= htmlspecialchars($hotel['name']) ?>">
            <?php endforeach; ?>
        <?php else: ?>
            <img src="https://via.placeholder.com/600x400?text=No+Image" alt="Khách sạn chưa có hình ảnh">
        <?php endif; ?>
    </div>

    <!-- Khu vực 2: Thông tin chung -->
    <h1><?= htmlspecialchars($hotel['name']) ?></h1>

    <div class="hotel-information">
        <div class="hotel-information-row">
            <span class="hotel-information-label">Địa chỉ:</span>
            <span class="hotel-information-value">
                <?= htmlspecialchars($hotel['address'] ?? 'Chưa cập nhật') ?>
            </span>
        </div>

        <div class="hotel-information-row">
            <span class="hotel-information-label">Số điện thoại:</span>
            <span class="hotel-information-value">
                <?= htmlspecialchars($hotel['phone'] ?? 'Chưa cập nhật') ?>
            </span>
        </div>

        <div class="hotel-information-row">
            <span class="hotel-information-label">Hạng sao:</span>
            <span class="hotel-information-value hotel-rating-value">
                <?= renderHotelStars($starRating) ?>
            </span>
        </div>

        <div class="hotel-information-row">
            <span class="hotel-information-label">Phong cách:</span>
            <span class="hotel-information-value">
                <?= htmlspecialchars($hotel['vibe'] ?? 'Chưa cập nhật') ?>
            </span>
        </div>
    </div>

    <div class="detail-section">
        <h3>Mô tả</h3>
        <p><?= nl2br(htmlspecialchars($hotel['description'])) ?></p>
    </div>

    <div class="detail-section amenities-section">
        <h3>Tiện nghi và dịch vụ</h3>

        <?php if ($amenities): ?>
            <div class="amenities-list">
                <?php foreach ($amenities as $amenity): ?>
                    <div class="amenity-item">
                        <?= amenityIcon($amenity['icon']) ?>
                        <span class="amenity-name"><?= htmlspecialchars($amenity['name']) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="amenities-empty">Khách sạn chưa cập nhật thông tin tiện nghi.</p>
        <?php endif; ?>
    </div>

    <!-- Khu vực 3: Bảng giá phòng -->
    <section class="room-price-section">
        <h3 class="room-price-title">Bảng giá phòng</h3>

        <div class="room-price-wrapper">
            <table class="room-price-table">
                <thead>
                    <tr>
                        <th>Loại phòng</th>
                        <th>Mức giá / Đêm</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if ($rooms): ?>
                        <?php foreach ($rooms as $room): ?>
                            <tr>
                                <td>Phòng cho <?= (int) $room['capacity'] ?> người</td>
                                <td class="room-price"><?= number_format((float) $room['price']) ?> đ</td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="2" class="rooms-empty">Khách sạn chưa cập nhật giá phòng.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<?php require_once 'includes/footer.php'; ?>