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
            MIN(r.price) AS price,
            GROUP_CONCAT(
                DISTINCT CONCAT(COALESCE(i.is_primary, 0), '::', i.image_url)
                ORDER BY i.is_primary DESC, i.id ASC SEPARATOR '|||'
            ) AS db_images
        FROM hotels h
        INNER JOIN rooms r ON r.hotel_id = h.id
        LEFT JOIN hotel_images i ON i.hotel_id = h.id
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
            h.vibe
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

// Gán ảnh đại diện từ cùng một nguồn ảnh dùng chung với trang chủ.
foreach ($results as &$hotelResult) {
    $images = hotel_image_candidates((int) $hotelResult['id'], $hotelResult['db_images'] ?? null);
    $hotelResult['image_url'] = $images[0] ?? 'https://via.placeholder.com/400x250?text=No+Image';
}
unset($hotelResult);


require_once 'includes/header.php';
?>
<style>
/* Chỉ áp dụng cho phần kết quả tìm kiếm trong search.php. */
.search-results-section {
    margin-top: 38px;
}

.search-results-section > h2 {
    margin: 0 0 28px;
    color: #4b2342 !important;
    font-size: clamp(30px, 3.2vw, 42px);
    line-height: 1.2;
}

.search-results-section .search-criteria-summary {
    margin-bottom: 34px;
    padding: 0;
    overflow: hidden;
    border: 0;
    border-left: 8px solid #542548;
    border-radius: 14px 0 0 14px;
    background: #fff;
    color: #555;
    box-shadow: none;
}

.search-results-section .search-main-criteria {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 0;
    padding: 27px 38px 25px;
    border-bottom: 1px solid #eadfd8;
    font-size: 18px;
    line-height: 1.55;
}

.search-results-section .search-main-criteria strong {
    margin-left: 9px;
    color: #444;
    font-weight: 700;
}

.search-results-section .criteria-separator {
    margin: 0 22px;
    color: #baadb7;
}

.search-results-section .selected-amenities-section {
    padding: 29px 38px 34px;
}

.search-results-section .selected-amenities-title {
    margin-bottom: 24px;
    color: #262626;
    font-size: 20px;
    font-weight: 700;
    line-height: 1.3;
}

.search-results-section .selected-amenities-list {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    column-gap: 88px;
    row-gap: 20px;
}

.search-results-section .selected-amenity-item {
    display: flex;
    align-items: center;
    gap: 14px;
    min-width: 0;
    color: #333;
    font-size: 18px;
    line-height: 1.45;
}

.search-results-section .selected-amenity-icon {
    width: 22px !important;
    height: 22px !important;
    flex: 0 0 22px;
    display: block;
    fill: none !important;
    stroke: #555 !important;
    stroke-width: 1.7 !important;
    stroke-linecap: round;
    stroke-linejoin: round;
}

.search-results-section .selected-amenity-name {
    min-width: 0;
    overflow-wrap: anywhere;
}

.search-results-section .no-selected-amenity {
    margin: 0;
    padding: 28px 38px 32px;
    color: #777;
}

@media (max-width: 700px) {
    .search-results-section > h2 {
        margin-bottom: 22px;
        font-size: 31px;
    }

    .search-results-section .search-main-criteria {
        align-items: flex-start;
        flex-direction: column;
        padding: 22px 24px;
    }

    .search-results-section .search-main-criteria strong {
        margin: 2px 0 9px;
    }

    .search-results-section .criteria-separator {
        display: none;
    }

    .search-results-section .selected-amenities-section {
        padding: 24px;
    }

    .search-results-section .selected-amenities-list {
        grid-template-columns: 1fr;
        row-gap: 16px;
    }
}
</style>
<section class="hotel-list-section search-results-section">
    <h2>Kết quả gợi ý phù hợp nhất</h2>

    <div class="search-criteria-summary">

        <!-- Số người và ngân sách -->
        <div class="search-main-criteria">
            <span>Bạn đang tìm:</span>

            <strong>
                <?= (int) $capacity ?> người
            </strong>

            <span class="criteria-separator">|</span>

            <span>Ngân sách tối đa:</span>

            <strong>
                <?= number_format((float) $budget) ?> đ/đêm
            </strong>
        </div>

        <!-- Danh sách tiện nghi đã chọn -->
        <?php if ($selectedAmenities): ?>
            <div class="selected-amenities-section">

                <div class="selected-amenities-title">
                    Tiện nghi đã chọn
                </div>

                <div class="selected-amenities-list">

                    <?php foreach ($selectedAmenities as $amenityId): ?>
                        <?php
                        $amenity = $amenityMap[$amenityId];
                        ?>

                        <div class="selected-amenity-item">

                            <?= amenity_icon_svg($amenity['icon'], 'selected-amenity-icon') ?>

                            <span class="selected-amenity-name">
                                <?= htmlspecialchars(
                                    $amenity['name']
                                ) ?>
                            </span>

                        </div>
                    <?php endforeach; ?>

                </div>
            </div>
        <?php else: ?>

            <p class="no-selected-amenity">
                Không yêu cầu tiện nghi cụ thể.
            </p>

        <?php endif; ?>

    </div>

    <!-- Danh sách kết quả khách sạn -->
    <div class="hotel-grid">

        <?php if ($results): ?>

            <?php foreach ($results as $hotel): ?>

                <article class="hotel-card">

                    <img
                        src="<?= htmlspecialchars($hotel['image_url']) ?>"
                        alt="<?= htmlspecialchars($hotel['name']) ?>"
                    >

                    <div class="card-content">

                        <h3>
                            <?= htmlspecialchars($hotel['name']) ?>
                        </h3>

                        <p class="hotel-address">
                            📍 <?= htmlspecialchars($hotel['address']) ?>
                        </p>

                        <p class="price">
                            Mức giá phù hợp:
                            <?= number_format(
                                (float) $hotel['price']
                            ) ?> đ
                        </p>

                        <div class="card-actions search-result-actions">

                            <a
                                href="detail.php?id=<?=
                                    (int) $hotel['id']
                                ?>"
                                class="btn-primary search-detail-link"
                            >
                                Xem chi tiết
                            </a>

                        </div>
                    </div>

                </article>

            <?php endforeach; ?>

        <?php else: ?>

            <div class="empty-search-result">

                <p>
                    Rất tiếc, không có khách sạn nào đáp ứng
                    đầy đủ các tiêu chí đã chọn.
                </p>

                <a
                    href="index.php"
                    class="btn-outline empty-search-link"
                >
                    &laquo; Thay đổi tiêu chí tìm kiếm
                </a>

            </div>

        <?php endif; ?>

    </div>
</section>

<?php require_once 'includes/footer.php'; ?>