<?php
require_once 'includes/db-connect.php';
require_once 'includes/header.php';

if (empty($_GET['hotel_ids'])) {
    die("Vui lòng chọn ít nhất 1 khách sạn để so sánh.");
}

$ids = $_GET['hotel_ids'];
// Giới hạn hiển thị 5 khách sạn để tránh vỡ giao diện
$ids = array_slice($ids, 0, 5); 
$placeholders = implode(',', array_fill(0, count($ids), '?'));

$sql = "SELECT h.id, h.name, h.vibe, hi.image_url,
        MAX(CASE WHEN r.capacity = 2 THEN r.price END) as price_2,
        MAX(CASE WHEN r.capacity = 4 THEN r.price END) as price_4,
        GROUP_CONCAT(DISTINCT a.name SEPARATOR ', ') as amenities
        FROM hotels h
        LEFT JOIN hotel_images hi ON h.id = hi.hotel_id AND hi.is_primary = 1
        LEFT JOIN rooms r ON h.id = r.hotel_id
        LEFT JOIN hotel_amenities ha ON h.id = ha.hotel_id
        LEFT JOIN amenities a ON ha.amenity_id = a.id
        WHERE h.id IN ($placeholders)
        GROUP BY h.id";

$stmt = $pdo->prepare($sql);
$stmt->execute($ids);
$hotels = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>Bảng So Sánh Khách Sạn</h2>
<div class="table-responsive">
    <table class="compare-table">
        <tr>
            <th>Tiêu chí</th>
            <?php foreach($hotels as $h): ?>
                <th>
                    <img src="<?= htmlspecialchars($h['image_url']) ?>" width="100%"><br>
                    <?= htmlspecialchars($h['name']) ?>
                </th>
            <?php endforeach; ?>
        </tr>
        <tr>
            <td>Giá phòng 2 người</td>
            <?php foreach($hotels as $h): ?>
                <td><?= number_format($h['price_2']) ?> đ</td>
            <?php endforeach; ?>
        </tr>
        <tr>
            <td>Giá phòng 4 người</td>
            <?php foreach($hotels as $h): ?>
                <td><?= number_format($h['price_4']) ?> đ</td>
            <?php endforeach; ?>
        </tr>
        <tr>
            <td>Phong cách</td>
            <?php foreach($hotels as $h): ?>
                <td><?= htmlspecialchars($h['vibe']) ?></td>
            <?php endforeach; ?>
        </tr>
        <tr>
            <td>Tiện nghi</td>
            <?php foreach($hotels as $h): ?>
                <td><?= htmlspecialchars($h['amenities']) ?></td>
            <?php endforeach; ?>
        </tr>
    </table>
</div>
<?php require_once 'includes/footer.php'; ?>