<?php
/** COMPARE.PHP: nhận tối đa 5 ID khách sạn và dựng bảng so sánh. */
require_once 'includes/bootstrap.php';

$rawIds = $_GET['hotel_ids'] ?? [];
$rawIds = is_array($rawIds) ? $rawIds : explode(',', (string) $rawIds);
$ids = [];
foreach ($rawIds as $rawId) {
    $id = positive_int($rawId);
    if ($id !== null) {
        $ids[] = $id;
    }
}

// Giới hạn 5 ID hợp lệ để truy vấn gọn và giao diện không bị vỡ.
$ids = array_values(array_unique($ids));
$ids = array_slice($ids, 0, 5);

if (!$ids) {
    redirect('index.php');
}

$placeholders = implode(',', array_fill(0, count($ids), '?'));

// Lấy thông tin khách sạn và giá phòng.
$sql = "SELECT
            h.id,
            h.name,
            h.vibe,
            MAX(CASE WHEN r.capacity = 2 THEN r.price END) AS price_2,
            MAX(CASE WHEN r.capacity = 4 THEN r.price END) AS price_4,
            GROUP_CONCAT(
                DISTINCT CONCAT(COALESCE(i.is_primary, 0), '::', i.image_url)
                ORDER BY i.is_primary DESC, i.id ASC SEPARATOR '|||'
            ) AS db_images
        FROM hotels h
        LEFT JOIN rooms r ON h.id = r.hotel_id
        LEFT JOIN hotel_images i ON i.hotel_id = h.id
        WHERE h.id IN ($placeholders)
        GROUP BY h.id, h.name, h.vibe";

$stmt = $pdo->prepare($sql);
$stmt->execute($ids);
$hotels = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$hotels) {
    redirect('index.php');
}

$ids = array_map('intval', array_column($hotels, 'id'));
$placeholders = implode(',', array_fill(0, count($ids), '?'));
$historyIds = $ids;
sort($historyIds, SORT_NUMERIC);

if (isset($_SESSION['user_id'])) {
    $historyStmt = $pdo->prepare(
        'INSERT IGNORE INTO comparison_history (user_id, hotel_ids) VALUES (?, ?)'
    );
    $historyStmt->execute([(int) $_SESSION['user_id'], implode(',', $historyIds)]);
}

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

foreach ($hotels as &$hotel) {
    $hotelId = (int) $hotel['id'];
    $images = hotel_image_candidates($hotelId, $hotel['db_images'] ?? null);
    $hotel['image_url'] = $images[0] ?? 'https://via.placeholder.com/400x250?text=No+Image';
}
unset($hotel);

require_once 'includes/header.php';
?>
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
                                    <?= amenity_icon_svg($amenity['icon'], 'compare-amenity-icon') ?>

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
