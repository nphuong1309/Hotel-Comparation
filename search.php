<?php
// search.php
require_once 'includes/db-connect.php';

// Nhận dữ liệu từ form (Sử dụng toán tử null coalescing để tránh lỗi nếu thiếu)
$capacity = $_GET['capacity'] ?? 2;
$budget = $_GET['budget'] ?? 'medium';
$vibe = $_GET['vibe'] ?? 'modern';

// Chuyển đổi Budget text sang giá trị số
$price_condition = "";
if ($budget == 'low') {
    $price_condition = "r.price < 500000";
} elseif ($budget == 'medium') {
    $price_condition = "r.price BETWEEN 500000 AND 1000000";
} else {
    $price_condition = "r.price > 1000000";
}

// Lệnh SQL thông minh (JOIN 2 bảng)
$sql = "SELECT h.id, h.name, h.image_url, r.price 
        FROM hotels h 
        JOIN rooms r ON h.id = r.hotel_id 
        WHERE r.capacity = :capacity 
        AND h.vibe = :vibe 
        AND $price_condition 
        ORDER BY r.price ASC 
        LIMIT 2"; // Lấy tối đa 2 kết quả khớp nhất

$stmt = $pdo->prepare($sql);
$stmt->execute(['capacity' => $capacity, 'vibe' => $vibe]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Phần in kết quả ra HTML (Bạn có thể lồng CSS vào để đẹp như trang index) -->
<h2>Khách sạn phù hợp nhất với bạn:</h2>
<?php if(count($results) > 0): ?>
    <?php foreach($results as $hotel): ?>
        <div>
            <img src="<?= $hotel['image_url'] ?>" width="200">
            <h3><?= htmlspecialchars($hotel['name']) ?></h3>
            <p>Giá tham khảo: <?= number_format($hotel['price']) ?> VNĐ/đêm</p>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <p>Rất tiếc, chưa tìm thấy khách sạn nào khớp với nhu cầu của bạn. Vui lòng thử tiêu chí khác!</p>
<?php endif; ?>