<?php
/** DETAIL.PHP: hiển thị thông tin, ảnh, phòng và tiện nghi của một khách sạn. */
require_once 'includes/bootstrap.php';

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

/**
 * Chuẩn hóa chuỗi Unicode về dạng dựng sẵn để dấu tiếng Việt
 * không bị tách khỏi ký tự khi dùng font serif.
 */
function normalizeDisplayText(?string $value): string
{
    $value = (string) ($value ?? '');

    if (class_exists('Normalizer')) {
        $normalized = Normalizer::normalize($value, Normalizer::FORM_C);

        if ($normalized !== false) {
            return $normalized;
        }
    }

    return $value;
}

foreach (['name', 'address', 'phone', 'vibe', 'description'] as $field) {
    if (array_key_exists($field, $hotel)) {
        $hotel[$field] = normalizeDisplayText($hotel[$field]);
    }
}

// Chuẩn hóa hạng sao để hiển thị được số thập phân, ví dụ 3,5 hoặc 4,5.
$starRating = max(0, min(5, (float) ($hotel['star_rating'] ?? $hotel['stars'] ?? 0)));

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

// 2. Lấy hình ảnh của khách sạn.
// Ảnh hotel_ID_primary.* dùng làm ảnh nền chính.
// Chỉ đưa các ảnh còn tồn tại vào slider để tránh hiện khung ảnh lỗi.
function normalizeHotelImagePath(?string $imagePath): ?string
{
    $imagePath = trim((string) ($imagePath ?? ''));

    if ($imagePath === '') {
        return null;
    }

    // Link ảnh bên ngoài vẫn giữ nguyên.
    if (preg_match('~^https?://~i', $imagePath)) {
        return $imagePath;
    }

    $imagePath = str_replace('\\', '/', $imagePath);
    $imagePath = preg_replace('~^\./~', '', $imagePath);

    // Chuẩn hóa một số đường dẫn thường gặp trong database.
    if (strpos($imagePath, '/hoteltool/') === 0) {
        $imagePath = substr($imagePath, strlen('/hoteltool/'));
    } else {
        $imagePath = ltrim($imagePath, '/');
    }

    return is_file($imagePath) ? $imagePath : null;
}

$diskImages = glob(
    'uploads/hotel_' . $id . '_*.{jpg,jpeg,png,gif,webp}',
    GLOB_BRACE
) ?: [];

$dbImageRows = [];

try {
    $stmtHotelImages = $pdo->prepare(
        'SELECT image_url, is_primary
         FROM hotel_images
         WHERE hotel_id = ?
         ORDER BY is_primary DESC, id ASC'
    );
    $stmtHotelImages->execute([$id]);
    $dbImageRows = $stmtHotelImages->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $dbImageRows = [];
}

$allImages = [];

foreach ($diskImages as $imagePath) {
    $usableImage = normalizeHotelImagePath($imagePath);

    if ($usableImage !== null) {
        $allImages[] = $usableImage;
    }
}

foreach ($dbImageRows as $imageRow) {
    $usableImage = normalizeHotelImagePath($imageRow['image_url'] ?? null);

    if ($usableImage !== null) {
        $allImages[] = $usableImage;
    }
}

$allImages = array_values(array_unique($allImages));
natsort($allImages);
$allImages = array_values($allImages);

$primaryImage = null;

// Ưu tiên ảnh có đúng cú pháp hotel_ID_primary.*
foreach ($allImages as $imagePath) {
    if (strpos(basename(parse_url($imagePath, PHP_URL_PATH) ?? $imagePath), '_primary.') !== false) {
        $primaryImage = $imagePath;
        break;
    }
}

// Nếu bảng hotel_images đánh dấu ảnh chính thì dùng ảnh đó.
if ($primaryImage === null) {
    foreach ($dbImageRows as $imageRow) {
        if (!empty($imageRow['is_primary'])) {
            $usableImage = normalizeHotelImagePath($imageRow['image_url'] ?? null);

            if ($usableImage !== null) {
                $primaryImage = $usableImage;
                break;
            }
        }
    }
}

if ($primaryImage === null) {
    $primaryImage = $allImages[0]
        ?? 'https://via.placeholder.com/1600x760?text=No+Image';
}

// Slider ưu tiên các ảnh phụ; nếu không có thì vẫn hiển thị ảnh primary.
$carouselImages = array_values(array_filter(
    $allImages,
    static fn (string $imagePath): bool => $imagePath !== $primaryImage
));

if (!$carouselImages) {
    $carouselImages = [$primaryImage];
}

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

require_once 'includes/header.php';
?>
<div class="hotel-detail-page">
    <!-- Ảnh primary đặt dưới tên khách sạn -->
    <section class="hotel-hero">
        <!-- Ảnh là dữ liệu động; đặt thành phần HTML thay vì nhúng CSS vào PHP. -->
        <img class="hotel-hero-background" src="<?= htmlspecialchars($primaryImage, ENT_QUOTES, 'UTF-8') ?>" alt="" aria-hidden="true">
        <h1 class="hotel-hero-title">
            <?= htmlspecialchars($hotel['name']) ?>
        </h1>
    </section>

    <!-- Card thông tin bên trái và slider ảnh bên phải -->
    <section class="hotel-feature-section">
        <div class="hotel-intro-card">
            <h2 class="hotel-intro-title">Thông tin khách sạn</h2>

            <div class="hotel-quick-information">
                <div class="hotel-quick-row">
                    <span class="hotel-quick-label">Địa chỉ:</span>
                    <span class="hotel-quick-value">
                        <?= htmlspecialchars($hotel['address'] ?? 'Chưa cập nhật') ?>
                    </span>
                </div>

                <div class="hotel-quick-row">
                    <span class="hotel-quick-label">Số điện thoại:</span>
                    <span class="hotel-quick-value">
                        <?= htmlspecialchars($hotel['phone'] ?? 'Chưa cập nhật') ?>
                    </span>
                </div>

                <div class="hotel-quick-row">
                    <span class="hotel-quick-label">Đánh giá:</span>
                    <span class="hotel-quick-value hotel-rating-value">
                        <?= renderHotelStars($starRating) ?>
                    </span>
                </div>

                <div class="hotel-quick-row">
                    <span class="hotel-quick-label">Phong cách:</span>
                    <span class="hotel-quick-value">
                        <?= htmlspecialchars($hotel['vibe'] ?? 'Chưa cập nhật') ?>
                    </span>
                </div>
            </div>
        </div>

        <div class="hotel-carousel" id="hotelCarousel" aria-label="Hình ảnh khách sạn">
            <div class="hotel-carousel-track">
                <?php foreach ($carouselImages as $imageIndex => $imagePath): ?>
                    <div class="hotel-carousel-slide">
                        <img
                            src="<?= htmlspecialchars($imagePath) ?>"
                            alt="Hình ảnh <?= $imageIndex + 1 ?> của <?= htmlspecialchars($hotel['name']) ?>"
                            loading="<?= $imageIndex === 0 ? 'eager' : 'lazy' ?>"
                            onerror="this.onerror=null; this.src='<?= htmlspecialchars($primaryImage, ENT_QUOTES, 'UTF-8') ?>';"
                        >
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if (count($carouselImages) > 1): ?>
                <button
                    type="button"
                    class="hotel-carousel-button previous"
                    aria-label="Xem ảnh trước"
                >
                    &#8249;
                </button>

                <button
                    type="button"
                    class="hotel-carousel-button next"
                    aria-label="Xem ảnh tiếp theo"
                >
                    &#8250;
                </button>

                <div class="hotel-carousel-dots" aria-label="Chọn ảnh">
                    <?php foreach ($carouselImages as $imageIndex => $imagePath): ?>
                        <button
                            type="button"
                            class="hotel-carousel-dot <?= $imageIndex === 0 ? 'active' : '' ?>"
                            data-index="<?= $imageIndex ?>"
                            aria-label="Xem ảnh <?= $imageIndex + 1 ?>"
                        ></button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Card trắng chính giữa có đổ bóng -->
    <section class="hotel-content-card">
        <div class="hotel-content-section">
            <h2 class="hotel-section-heading">Mô tả</h2>

            <p class="hotel-description">
                <?= nl2br(htmlspecialchars($hotel['description'] ?? 'Khách sạn chưa cập nhật mô tả.')) ?>
            </p>
        </div>

        <div class="hotel-content-section">
            <h2 class="hotel-section-heading">Tiện nghi và dịch vụ</h2>

            <?php if ($amenities): ?>
                <div class="amenities-list">
                    <?php foreach ($amenities as $amenity): ?>
                        <div class="amenity-item">
                            <?= amenity_icon_svg($amenity['icon'], 'amenity-icon') ?>

                            <span class="amenity-name">
                                <?= htmlspecialchars($amenity['name']) ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="amenities-empty">
                    Khách sạn chưa cập nhật thông tin tiện nghi.
                </p>
            <?php endif; ?>
        </div>

        <div class="hotel-content-section">
            <h2 class="hotel-section-heading">Bảng giá phòng</h2>

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
                                    <td>
                                        Phòng cho <?= (int) $room['capacity'] ?> người
                                    </td>

                                    <td class="room-price">
                                        <?= number_format((float) $room['price']) ?> đ
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="2" class="rooms-empty">
                                    Khách sạn chưa cập nhật giá phòng.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>
<?php require_once 'includes/footer.php'; ?>
