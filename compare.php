<?php
require_once 'includes/db-connect.php';
require_once 'includes/header.php';

if (empty($_GET['hotel_ids'])) {
    die("Vui lòng chọn ít nhất 1 khách sạn để so sánh.");
}

$ids = $_GET['hotel_ids'];
if (!is_array($ids)) {
    $ids = array_filter(array_map('trim', explode(',', $ids)));
}

// Giới hạn hiển thị 5 khách sạn để tránh vỡ giao diện
$ids = array_values(array_unique(array_filter($ids)));
$ids = array_slice($ids, 0, 5);

$normalized_ids = $ids;
sort($normalized_ids, SORT_NUMERIC);
$history_key = implode(',', $normalized_ids);

if (isset($_SESSION['user_id'])) {
    $stmt_check = $pdo->prepare(
        "SELECT 1
         FROM comparison_history
         WHERE user_id = ? AND hotel_ids = ?
         LIMIT 1"
    );
    $stmt_check->execute([$_SESSION['user_id'], $history_key]);

    if (!$stmt_check->fetchColumn()) {
        $stmt_log = $pdo->prepare(
            "INSERT INTO comparison_history (user_id, hotel_ids)
             VALUES (?, ?)"
        );
        $stmt_log->execute([$_SESSION['user_id'], $history_key]);
    }
}

$placeholders = implode(',', array_fill(0, count($ids), '?'));

// Lấy thông tin khách sạn và giá phòng.
$sql = "SELECT
            h.id,
            h.name,
            h.vibe,
            MAX(CASE WHEN r.capacity = 2 THEN r.price END) AS price_2,
            MAX(CASE WHEN r.capacity = 4 THEN r.price END) AS price_4
        FROM hotels h
        LEFT JOIN rooms r ON h.id = r.hotel_id
        WHERE h.id IN ($placeholders)
        GROUP BY h.id, h.name, h.vibe";

$stmt = $pdo->prepare($sql);
$stmt->execute($ids);
$hotels = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy tiện nghi kèm mã icon cho các khách sạn được chọn.
$amenitySql = "SELECT
                    ha.hotel_id,
                    a.name,
                    a.icon
               FROM hotel_amenities ha
               INNER JOIN amenities a ON ha.amenity_id = a.id
               WHERE ha.hotel_id IN ($placeholders)
               ORDER BY ha.hotel_id ASC, a.id ASC";

$amenityStmt = $pdo->prepare($amenitySql);
$amenityStmt->execute($ids);
$amenityRows = $amenityStmt->fetchAll(PDO::FETCH_ASSOC);

$amenitiesByHotel = [];

foreach ($amenityRows as $amenityRow) {
    $hotelId = (int) $amenityRow['hotel_id'];

    if (!isset($amenitiesByHotel[$hotelId])) {
        $amenitiesByHotel[$hotelId] = [];
    }

    $amenitiesByHotel[$hotelId][] = [
        'name' => $amenityRow['name'],
        'icon' => $amenityRow['icon'],
    ];
}

/*
 * Ảnh của project được lưu trực tiếp trong thư mục uploads
 * theo dạng hotel_ID_primary.jpg, hotel_ID_primary.png,...
 */
foreach ($hotels as &$hotel) {
    $hotelId = (int) $hotel['id'];

    $primaryImages = glob(
        "uploads/hotel_{$hotelId}_primary.{jpg,jpeg,png,webp}",
        GLOB_BRACE
    );

    $hotel['image_url'] = !empty($primaryImages)
        ? $primaryImages[0]
        : 'https://via.placeholder.com/400x250?text=No+Image';
}
unset($hotel);

/**
 * Trả về SVG nét đơn sắc giống trang chi tiết khách sạn.
 */
function compareAmenityIcon(?string $icon): string
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

    $paths = $icons[$icon ?? '']
        ?? '<circle cx="12" cy="12" r="9"/><path d="m8 12 2.5 2.5L16 9"/>';

    return '<svg class="compare-amenity-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">'
        . $paths
        . '</svg>';
}
?>

<style>
    /* Chỉ áp dụng cho trang so sánh. */
    .compare-hotel-heading {
        min-width: 210px;
        vertical-align: top;
    }

    .compare-hotel-image {
        display: block;
        width: 100%;
        height: 180px;
        margin: 0 auto 9px;
        object-fit: cover;
    }

    .compare-hotel-name {
        display: block;
        line-height: 1.35;
    }

    .compare-amenities-cell {
        vertical-align: top;
        padding: 14px !important;
    }

    .compare-amenities-list {
        display: grid;
        grid-template-columns: 1fr;
        gap: 11px;
        text-align: left;
    }

    .compare-amenity-item {
        display: flex;
        align-items: flex-start;
        gap: 9px;
        min-width: 0;
        line-height: 1.4;
    }

    .compare-amenity-icon {
        width: 19px;
        height: 19px;
        flex: 0 0 19px;
        margin-top: 1px;
        fill: none;
        stroke: #4a4a4a;
        stroke-width: 1.6;
        stroke-linecap: round;
        stroke-linejoin: round;
    }

    .compare-amenity-name {
        min-width: 0;
        color: #333;
        word-break: break-word;
    }

    .compare-no-amenity {
        color: #777;
        text-align: center;
    }
</style>

<h2>Bảng So Sánh Khách Sạn</h2>

<div class="table-responsive">
    <table class="compare-table">
        <tr>
            <th>Tiêu chí</th>

            <?php foreach ($hotels as $h): ?>
                <th class="compare-hotel-heading">
                    <img
                        class="compare-hotel-image"
                        src="<?= htmlspecialchars($h['image_url']) ?>"
                        alt="<?= htmlspecialchars($h['name']) ?>"
                    >

                    <span class="compare-hotel-name">
                        <?= htmlspecialchars($h['name']) ?>
                    </span>
                </th>
            <?php endforeach; ?>
        </tr>

        <tr>
            <td>Giá phòng 2 người</td>

            <?php foreach ($hotels as $h): ?>
                <td><?= number_format((float) $h['price_2']) ?> đ</td>
            <?php endforeach; ?>
        </tr>

        <tr>
            <td>Giá phòng 4 người</td>

            <?php foreach ($hotels as $h): ?>
                <td><?= number_format((float) $h['price_4']) ?> đ</td>
            <?php endforeach; ?>
        </tr>

        <tr>
            <td>Phong cách</td>

            <?php foreach ($hotels as $h): ?>
                <td><?= htmlspecialchars($h['vibe']) ?></td>
            <?php endforeach; ?>
        </tr>

        <tr>
            <td>Tiện nghi</td>

            <?php foreach ($hotels as $h): ?>
                <?php
                $hotelAmenities = $amenitiesByHotel[(int) $h['id']] ?? [];
                ?>

                <td class="compare-amenities-cell">
                    <?php if ($hotelAmenities): ?>
                        <div class="compare-amenities-list">
                            <?php foreach ($hotelAmenities as $amenity): ?>
                                <div class="compare-amenity-item">
                                    <?= compareAmenityIcon($amenity['icon']) ?>

                                    <span class="compare-amenity-name">
                                        <?= htmlspecialchars($amenity['name']) ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="compare-no-amenity">
                            Chưa cập nhật tiện nghi.
                        </div>
                    <?php endif; ?>
                </td>
            <?php endforeach; ?>
        </tr>
    </table>
</div>

<?php require_once 'includes/footer.php'; ?>
