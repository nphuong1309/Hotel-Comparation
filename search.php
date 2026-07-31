<?php
/** SEARCH.PHP: lọc khách sạn theo sức chứa, ngân sách và toàn bộ tiện nghi đã chọn. */
require_once 'includes/bootstrap.php';

// Nhận và kiểm tra dữ liệu từ form.
$capacity = filter_input(INPUT_GET, 'capacity', FILTER_VALIDATE_INT);
$budget = filter_input(INPUT_GET, 'budget', FILTER_VALIDATE_INT);

$capacity = ($capacity !== false && $capacity !== null)
    ? max(1, min(4, $capacity))
    : 2;

$budget = ($budget !== false && $budget !== null)
    ? max(50000, $budget)
    : 5000000;

// Lấy danh sách tiện nghi cùng mã icon trực tiếp từ database.
$amenityRows = $pdo->query(
    'SELECT id, name, icon FROM amenities ORDER BY id ASC'
)->fetchAll(PDO::FETCH_ASSOC);

$amenityMap = [];

foreach ($amenityRows as $amenityRow) {
    $amenityMap[(int) $amenityRow['id']] = [
        'name' => $amenityRow['name'],
        'icon' => $amenityRow['icon'],
    ];
}

// Nhận danh sách tiện nghi người dùng đã chọn.
$requestedAmenities = $_GET['amenities'] ?? [];

if (!is_array($requestedAmenities)) {
    $requestedAmenities = [];
}

// Chỉ giữ những ID tiện nghi hợp lệ và có trong database.
$selectedAmenities = [];

foreach ($requestedAmenities as $amenityId) {
    $amenityId = filter_var($amenityId, FILTER_VALIDATE_INT);

    if (
        $amenityId !== false
        && isset($amenityMap[$amenityId])
    ) {
        $selectedAmenities[] = (int) $amenityId;
    }
}

// Loại bỏ ID bị trùng.
$selectedAmenities = array_values(
    array_unique($selectedAmenities)
);

/*
 * Tìm phòng có sức chứa và mức giá phù hợp.
 *
 * Khi chọn nhiều tiện nghi, khách sạn phải có đủ
 * tất cả tiện nghi được chọn.
 */
$sql = "SELECT
            h.id,
            h.name,
            h.address,
            h.vibe,
            h.description,
            h.star_rating,
            MIN(r.price) AS price
        FROM hotels h
        INNER JOIN rooms r ON r.hotel_id = h.id
        WHERE r.capacity >= :capacity
          AND r.price <= :budget";

$params = [
    ':capacity' => $capacity,
    ':budget' => $budget,
];

// Thêm điều kiện lọc theo tiện nghi.
if ($selectedAmenities) {
    $amenityPlaceholders = [];

    foreach ($selectedAmenities as $index => $amenityId) {
        $placeholder = ':amenity_' . $index;

        $amenityPlaceholders[] = $placeholder;
        $params[$placeholder] = $amenityId;
    }

    $sql .= "
        AND (
            SELECT COUNT(DISTINCT ha.amenity_id)
            FROM hotel_amenities ha
            WHERE ha.hotel_id = h.id
              AND ha.amenity_id IN (
                  " . implode(', ', $amenityPlaceholders) . "
              )
        ) = :amenity_count";

    $params[':amenity_count'] = count($selectedAmenities);
}

$sql .= "
        GROUP BY
            h.id,
            h.name,
            h.address,
            h.vibe,
            h.description,
            h.star_rating
        ORDER BY
            price ASC,
            h.name ASC";

// Thực thi truy vấn.
$stmt = $pdo->prepare($sql);

foreach ($params as $placeholder => $value) {
    $stmt->bindValue(
        $placeholder,
        $value,
        PDO::PARAM_INT
    );
}

$stmt->execute();

$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Luôn dùng ảnh chính có thật trên server, không phụ thuộc dữ liệu ảnh mẫu cũ.
foreach ($results as &$hotelResult) {
    $hotelId = (int) $hotelResult['id'];
    $hotelResult['image_url'] = hotel_primary_image($hotelId);
}
unset($hotelResult);


require_once 'includes/header.php';
?>
<section class="search-results-section">
    <header class="page-heading search-page-heading">
        <div>
            <p class="page-eyebrow">Lựa chọn dành riêng cho bạn</p>
            <h1>Kết quả phù hợp nhất</h1>
            <p class="page-lead"><?= count($results) ?> khách sạn đáp ứng các tiêu chí đã chọn.</p>
        </div>
        <a href="index.php#smartSearchForm" class="btn-outline">Điều chỉnh tìm kiếm</a>
    </header>

    <div class="search-criteria-summary">
        <div class="search-main-criteria">
            <div class="criteria-item">
                <span>Số khách</span>
                <strong><?= (int) $capacity ?> người</strong>
            </div>
            <div class="criteria-item">
                <span>Ngân sách tối đa</span>
                <strong><?= number_format((float) $budget) ?> đ/đêm</strong>
            </div>
        </div>

        <div class="selected-amenities-section">
            <div class="selected-amenities-title">Tiện nghi yêu cầu</div>
            <?php if ($selectedAmenities): ?>
                <div class="selected-amenities-list">
                    <?php foreach ($selectedAmenities as $amenityId): ?>
                        <?php $amenity = $amenityMap[$amenityId]; ?>
                        <div class="selected-amenity-item">
                            <?= amenity_icon_svg($amenity['icon'], 'selected-amenity-icon') ?>
                            <span class="selected-amenity-name"><?= htmlspecialchars($amenity['name']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="no-selected-amenity">Không yêu cầu tiện nghi cụ thể.</p>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($results): ?>
        <div class="hotel-list-modern search-hotel-list">
            <?php foreach ($results as $hotel): ?>
                <?php $rating = max(0, min(5, (float) ($hotel['star_rating'] ?? 0))); ?>
                <article class="hotel-modern-card">
                    <div class="hotel-modern-image-wrap">
                        <img
                            class="hotel-modern-image"
                            src="<?= htmlspecialchars($hotel['image_url'], ENT_QUOTES, 'UTF-8') ?>"
                            alt="<?= htmlspecialchars($hotel['name'], ENT_QUOTES, 'UTF-8') ?>"
                            loading="lazy"
                            onerror="this.onerror=null;this.src='uploads/default-hotel.jpg';"
                        >
                        <div class="hotel-price-ribbon">Từ <?= number_format((float) $hotel['price']) ?> đ</div>
                        <div class="hotel-star-badge">★ <?= number_format($rating, 1, ',', '.') ?></div>
                    </div>

                    <div class="hotel-modern-body">
                        <div class="hotel-meta">
                            <span class="hotel-meta-item">Cần Thơ</span>
                            <span class="hotel-meta-item"><?= htmlspecialchars($hotel['vibe'] ?: 'Đang cập nhật') ?></span>
                        </div>
                        <h2 class="hotel-modern-title"><?= htmlspecialchars($hotel['name']) ?></h2>
                        <p class="hotel-modern-description">
                            <?= htmlspecialchars($hotel['description'] ?: 'Khách sạn đang cập nhật phần giới thiệu và thông tin nổi bật.') ?>
                        </p>
                        <p class="hotel-modern-address">
                            <strong>Địa chỉ:</strong> <?= htmlspecialchars($hotel['address'] ?: 'Chưa cập nhật') ?>
                        </p>
                        <div class="hotel-modern-actions search-result-actions">
                            <a href="detail.php?id=<?= (int) $hotel['id'] ?>" class="hotel-detail-link search-detail-link">Xem chi tiết</a>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-search-result">
            <h2>Chưa tìm thấy lựa chọn phù hợp</h2>
            <p>Hãy tăng ngân sách hoặc giảm số tiện nghi bắt buộc để xem thêm khách sạn.</p>
            <a href="index.php#smartSearchForm" class="btn-primary empty-search-link">Thay đổi tiêu chí tìm kiếm</a>
        </div>
    <?php endif; ?>
</section>

<?php require_once 'includes/footer.php'; ?>
