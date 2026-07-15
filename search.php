<?php
require_once 'includes/db-connect.php';

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

/**
 * Lấy một ảnh đại diện của khách sạn từ thư mục uploads.
 *
 * Ưu tiên:
 * 1. hotel_ID_primary.*
 * 2. Ảnh đánh số đầu tiên hotel_ID_1.*, hotel_ID_2.*...
 * 3. Ảnh placeholder nếu khách sạn chưa có ảnh.
 */
function getRepresentativeHotelImage(int $hotelId): string
{
    $primaryImages = glob(
        "uploads/hotel_{$hotelId}_primary.{jpg,jpeg,png,webp}",
        GLOB_BRACE
    ) ?: [];

    if ($primaryImages) {
        return $primaryImages[0];
    }

    $numberedImages = glob(
        "uploads/hotel_{$hotelId}_*.{jpg,jpeg,png,webp}",
        GLOB_BRACE
    ) ?: [];

    $numberedImages = array_filter(
        $numberedImages,
        static function (string $image) use ($hotelId): bool {
            return (bool) preg_match(
                "/hotel_{$hotelId}_[0-9]+\.(jpg|jpeg|png|webp)$/i",
                basename($image)
            );
        }
    );

    natsort($numberedImages);
    $numberedImages = array_values($numberedImages);

    return $numberedImages[0]
        ?? 'https://via.placeholder.com/400x250?text=No+Image';
}

// Gán một ảnh đại diện cho từng kết quả tìm kiếm.
foreach ($results as &$hotelResult) {
    $hotelResult['image_url'] = getRepresentativeHotelImage(
        (int) $hotelResult['id']
    );
}
unset($hotelResult);

/**
 * Trả về SVG nét đơn sắc theo mã icon lưu trong database.
 */
function searchAmenityIcon(?string $icon): string
{
    $icons = [
        'pool' =>
            '<path d="M3 17c1.2 0 1.8-1 3-1s1.8 1 3 1 1.8-1 3-1 1.8 1 3 1 1.8-1 3-1 1.8 1 3 1"/>
             <path d="M3 21c1.2 0 1.8-1 3-1s1.8 1 3 1 1.8-1 3-1 1.8 1 3 1 1.8-1 3-1 1.8 1 3 1"/>
             <circle cx="15" cy="5" r="2"/>
             <path d="m6 12 3-3 4 2 3-2 5 4"/>',

        'utensils' =>
            '<path d="M7 3v7"/>
             <path d="M4 3v4a3 3 0 0 0 6 0V3"/>
             <path d="M7 10v11"/>
             <path d="M17 3v18"/>
             <path d="M17 3c2.2 1.7 3 4.2 3 7h-3"/>',

        'spa' =>
            '<path d="M12 21c-4.5-2.5-7-5.7-7-9.5 3.2 0 5.6 1.3 7 3.7 1.4-2.4 3.8-3.7 7-3.7 0 3.8-2.5 7-7 9.5Z"/>
             <path d="M12 15.2C8.8 13.2 8 9.4 12 4c4 5.4 3.2 9.2 0 11.2Z"/>',

        'parking' =>
            '<rect x="4" y="3" width="16" height="18" rx="2"/>
             <path d="M9 17V7h4a3 3 0 0 1 0 6H9"/>',

        'wifi' =>
            '<path d="M5 12.5a10 10 0 0 1 14 0"/>
             <path d="M8.5 16a5 5 0 0 1 7 0"/>
             <path d="M12 20h.01"/>',

        'garden' =>
            '<path d="M12 22V10"/>
             <path d="M12 13c-4 0-7-2.5-7-6 4 0 7 2.5 7 6Z"/>
             <path d="M12 10c4 0 7-2.5 7-6-4 0-7 2.5-7 6Z"/>
             <path d="M7 22h10"/>',

        'bar' =>
            '<path d="M4 4h16l-6 7v7"/>
             <path d="M10 22h8"/>
             <path d="M14 18v4"/>
             <path d="M7 7h10"/>',

        'airport-shuttle' =>
            '<path d="M3 7h12l4 5v7H3Z"/>
             <path d="M15 7v5h4"/>
             <circle cx="7" cy="19" r="2"/>
             <circle cx="16" cy="19" r="2"/>
             <path d="M5 11h6"/>',

        'laundry' =>
            '<path d="m8 4 4-2 4 2 4 3-3 4v10H7V11L4 7Z"/>
             <path d="M9 4c.5 1.5 1.5 2.2 3 2.2S14.5 5.5 15 4"/>',

        'tour' =>
            '<path d="m3 6 6-3 6 3 6-3v15l-6 3-6-3-6 3Z"/>
             <path d="M9 3v15"/>
             <path d="M15 6v15"/>
             <circle cx="12" cy="10" r="2"/>',

        'rental' =>
            '<circle cx="6" cy="17" r="3"/>
             <circle cx="18" cy="17" r="3"/>
             <path d="m6 17 4-7h4l4 7"/>
             <path d="M10 10 8 7h3"/>
             <path d="M10 17h8"/>',

        'smoking' =>
            '<path d="M3 15h13v4H3Z"/>
             <path d="M16 15h2v4h-2"/>
             <path d="M20 15h1v4h-1"/>
             <path d="M7 11c0-2 3-2 3-4s-2-2-2-4"/>
             <path d="M13 11c0-1.6 2-1.8 2-3.5"/>',

        'air-hot-water' =>
            '<path d="M8 3v8a4 4 0 1 0 4 0V3a2 2 0 0 0-4 0Z"/>
             <path d="M10 14v.01"/>
             <path d="M17 5c2 1.5 2 3 0 4.5s-2 3 0 4.5"/>
             <path d="M21 5c2 1.5 2 3 0 4.5s-2 3 0 4.5"/>',

        'meeting' =>
            '<circle cx="8" cy="7" r="2"/>
             <circle cx="16" cy="7" r="2"/>
             <circle cx="12" cy="5" r="2"/>
             <path d="M4 21v-3a4 4 0 0 1 4-4h8a4 4 0 0 1 4 4v3"/>
             <path d="M8 14v7"/>
             <path d="M16 14v7"/>
             <path d="M8 18h8"/>',

        'reception' =>
            '<path d="M4 18h16"/>
             <path d="M6 18a6 6 0 0 1 12 0"/>
             <path d="M12 9V6"/>
             <circle cx="12" cy="4" r="1"/>
             <path d="M3 21h18"/>',

        'currency-exchange' =>
            '<path d="M7 7h11l-3-3"/>
             <path d="m18 7-3 3"/>
             <path d="M17 17H6l3 3"/>
             <path d="m6 17 3-3"/>
             <path d="M12 8v8"/>
             <path d="M14.5 10.5c-.5-1-1.3-1.5-2.5-1.5-1.4 0-2.5.7-2.5 1.8 0 2.7 5 1.2 5 3.7 0 1.1-1.1 1.8-2.5 1.8-1.2 0-2.2-.5-2.7-1.5"/>',
    ];

    $paths = $icons[$icon ?? '']
        ?? '<circle cx="12" cy="12" r="9"/>
            <path d="m8 12 2.5 2.5L16 9"/>';

    return
        '<svg
            class="selected-amenity-icon"
            viewBox="0 0 24 24"
            aria-hidden="true"
            focusable="false"
        >'
        . $paths
        . '</svg>';
}

require_once 'includes/header.php';
?>

<style>
    /*
     * Chỉ áp dụng cho phần tiêu chí tìm kiếm
     * trên trang search.php.
     */

    .search-criteria-summary {
        padding: 18px 22px;
    }

    .search-main-criteria {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 7px;
        line-height: 1.5;
    }

    .selected-amenities-section {
        margin-top: 17px;
        padding-top: 15px;
        border-top: 1px solid #e6ded9;
    }

    .selected-amenities-title {
        margin-bottom: 13px;
        font-weight: 700;
        color: #333;
    }

    .selected-amenities-list {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        column-gap: 42px;
        row-gap: 13px;
    }

    .selected-amenity-item {
        display: flex;
        align-items: center;
        gap: 11px;
        min-width: 0;
        line-height: 1.45;
    }

    .selected-amenity-icon {
        width: 20px;
        height: 20px;
        flex: 0 0 20px;

        fill: none;
        stroke: #4a4a4a;
        stroke-width: 1.6;
        stroke-linecap: round;
        stroke-linejoin: round;
    }

    .selected-amenity-name {
        color: #333;
        word-break: break-word;
    }

    .no-selected-amenity {
        margin: 14px 0 0;
        color: #777;
    }

    @media (max-width: 700px) {
        .selected-amenities-list {
            grid-template-columns: 1fr;
        }

        .criteria-separator {
            display: none;
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

                            <?= searchAmenityIcon(
                                $amenity['icon']
                            ) ?>

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