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
    /* ===== Thiết lập chung cho riêng trang chi tiết ===== */
    .hotel-detail-page {
        --detail-accent: #744631;
        --detail-text: #2c2c2c;
        --detail-muted: #686868;
        --detail-surface: #ffffff;
        --detail-page-bg: #f7f4ef;

        margin-right: calc(50% - 50vw);
        margin-left: calc(50% - 50vw);
        overflow-x: clip;
        background: var(--detail-page-bg);
        color: var(--detail-text);
        font-family: "Segoe UI", Arial, Helvetica, sans-serif;
    }

    .hotel-detail-page *,
    .hotel-detail-page *::before,
    .hotel-detail-page *::after {
        box-sizing: border-box;
    }

    /* ===== Ảnh primary và tên khách sạn ===== */
    .hotel-hero {
        position: relative;
        min-height: 520px;
        padding: 78px 24px 190px;

        display: flex;
        align-items: flex-start;
        justify-content: center;

        background-position: center;
        background-repeat: no-repeat;
        background-size: cover;
        color: #fff;
        text-align: center;
    }

    .hotel-hero::after {
        content: "";
        position: absolute;
        inset: 0;
        pointer-events: none;
        background:
            linear-gradient(
                180deg,
                rgba(4, 18, 39, 0.34) 0%,
                rgba(4, 18, 39, 0.44) 58%,
                rgba(4, 18, 39, 0.52) 100%
            );
    }

    .hotel-hero-title {
        position: relative;
        z-index: 1;
        max-width: 1080px;
        margin: 0;
        padding-top: 30px;

        color: #fff;
        font-family: Cambria, "Times New Roman", serif;
        font-size: clamp(34px, 4.2vw, 58px);
        font-weight: 500;
        line-height: 1.18;
        letter-spacing: 0;
        text-wrap: balance;
        text-shadow: 0 3px 13px rgba(0, 0, 0, 0.58);
    }

    /* ===== Thông tin khách sạn và slider ===== */
    .hotel-feature-section {
        position: relative;
        z-index: 10;
        width: min(1140px, calc(100% - 40px));
        margin: -150px auto 78px;

        display: grid;
        grid-template-columns: minmax(0, 56%) minmax(0, 44%);
        align-items: center;
    }

    .hotel-intro-card {
        position: relative;
        z-index: 1;
        min-height: 370px;
        padding: 52px 118px 48px 54px;

        background: rgba(255, 255, 255, 0.99);
        box-shadow: 0 14px 38px rgba(37, 28, 20, 0.13);
    }

    .hotel-intro-number {
        position: absolute;
        top: 28px;
        left: 34px;

        color: #f0efed;
        font-family: Cambria, "Times New Roman", serif;
        font-size: 88px;
        font-weight: 400;
        line-height: 1;
        user-select: none;
    }

    .hotel-intro-title {
        position: relative;
        z-index: 1;
        margin: 28px 0 30px;

        color: #242424;
        font-size: 29px;
        font-weight: 400;
        line-height: 1.25;
        letter-spacing: 0.2px;
        text-transform: uppercase;
    }

    .hotel-quick-information {
        position: relative;
        z-index: 1;
        display: grid;
        gap: 16px;
        margin: 0;
    }

    .hotel-quick-row {
        display: grid;
        grid-template-columns: 122px minmax(0, 1fr);
        gap: 18px;
        align-items: start;
        line-height: 1.55;
    }

    .hotel-quick-label {
        color: #303030;
        font-weight: 650;
    }

    .hotel-quick-value {
        min-width: 0;
        color: var(--detail-muted);
        word-break: normal;
        overflow-wrap: anywhere;
    }

    .hotel-rating-value {
        display: flex;
        align-items: center;
        min-height: 24px;
    }

    .hotel-stars {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        line-height: 1;
    }

    .hotel-star {
        position: relative;
        display: inline-block;
        width: 21px;
        height: 21px;
        color: #d7d7d7;
        font-size: 23px;
        line-height: 21px;
    }

    .hotel-star-full {
        color: #edae00;
    }

    .hotel-star-half {
        color: #d7d7d7;
    }

    .hotel-star-half::before {
        content: "★";
        position: absolute;
        inset: 0 auto 0 0;
        width: 50%;
        overflow: hidden;
        color: #edae00;
        white-space: nowrap;
    }

    /* ===== Slider ảnh phụ ===== */
    .hotel-carousel {
        position: relative;
        z-index: 2;
        height: 338px;
        margin-left: -66px;
        overflow: hidden;

        background: #ececec;
        box-shadow: 0 16px 38px rgba(31, 25, 20, 0.22);
    }

    .hotel-carousel-track {
        display: flex;
        height: 100%;
        transition: transform 0.55s ease;
        will-change: transform;
    }

    .hotel-carousel-slide {
        min-width: 100%;
        height: 100%;
        background: #e7e7e7;
    }

    .hotel-carousel-slide img {
        display: block;
        width: 100%;
        height: 100%;
        object-fit: cover;
        object-position: center;
    }

    .hotel-carousel-button {
        position: absolute;
        z-index: 3;
        top: 50%;
        width: 42px;
        height: 42px;
        padding: 0;
        border: 1px solid rgba(255, 255, 255, 0.72);
        border-radius: 50%;

        display: flex;
        align-items: center;
        justify-content: center;

        background: rgba(26, 26, 26, 0.52);
        color: #fff;
        cursor: pointer;
        font-size: 27px;
        line-height: 1;
        transform: translateY(-50%);
        transition:
            background 0.2s ease,
            transform 0.2s ease,
            opacity 0.2s ease;
    }

    .hotel-carousel-button:hover {
        background: rgba(26, 26, 26, 0.80);
        transform: translateY(-50%) scale(1.05);
    }

    .hotel-carousel-button.previous {
        left: 15px;
    }

    .hotel-carousel-button.next {
        right: 15px;
    }

    .hotel-carousel-dots {
        position: absolute;
        z-index: 3;
        right: 0;
        bottom: 16px;
        left: 0;

        display: flex;
        justify-content: center;
        gap: 7px;
    }

    .hotel-carousel-dot {
        width: 8px;
        height: 8px;
        padding: 0;
        border: 1px solid rgba(255, 255, 255, 0.90);
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.30);
        cursor: pointer;
    }

    .hotel-carousel-dot.active {
        background: #fff;
        transform: scale(1.15);
    }

    /* ===== Card trắng chính giữa ===== */
    .hotel-content-card {
        width: min(960px, calc(100% - 40px));
        margin: 0 auto 74px;
        padding: 54px 62px 58px;

        background: var(--detail-surface);
        color: var(--detail-text);
        box-shadow: 0 7px 29px rgba(38, 31, 25, 0.17);
    }

    .hotel-content-section + .hotel-content-section {
        margin-top: 47px;
    }

    .hotel-section-heading {
        position: relative;
        margin: 0 0 29px;
        padding-bottom: 18px;

        color: #282828;
        font-family: Cambria, "Times New Roman", serif;
        font-size: 25px;
        font-weight: 500;
        line-height: 1.3;
        letter-spacing: 0;
        text-align: center;
    }

    .hotel-section-heading::before {
        content: "";
        position: absolute;
        bottom: 4px;
        left: 50%;
        width: 84px;
        height: 1px;
        background: #dcdcdc;
        transform: translateX(-50%);
    }

    .hotel-section-heading::after {
        content: "";
        position: absolute;
        bottom: 0;
        left: 50%;
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #dcdcdc;
        transform: translateX(-50%);
    }

    .hotel-description {
        max-width: 820px;
        margin: 0 auto;

        color: #606060;
        font-size: 15px;
        line-height: 1.85;
        text-align: left;
    }

    .amenities-list {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        column-gap: 64px;
        row-gap: 16px;
    }

    .amenity-item {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        min-width: 0;

        color: #484848;
        line-height: 1.5;
    }

    .amenity-icon {
        width: 21px;
        height: 21px;
        flex: 0 0 21px;
        margin-top: 1px;

        fill: none;
        stroke: var(--detail-accent);
        stroke-width: 1.7;
        stroke-linecap: round;
        stroke-linejoin: round;
    }

    .amenity-name {
        min-width: 0;
        overflow-wrap: anywhere;
    }

    .amenities-empty,
    .rooms-empty {
        color: #777;
        text-align: center;
    }

    .room-price-wrapper {
        overflow-x: auto;
    }

    .room-price-table {
        width: 100%;
        min-width: 520px;
        border-collapse: collapse;
        table-layout: fixed;
    }

    .room-price-table th,
    .room-price-table td {
        padding: 17px 16px;
        border-bottom: 1px solid #e2e2e2;
        text-align: left;
        vertical-align: middle;
    }

    .room-price-table th {
        color: #313131;
        font-weight: 700;
    }

    .room-price-table th:last-child,
    .room-price-table td:last-child {
        text-align: right;
    }

    .room-price {
        color: #dc5639;
        font-weight: 700;
        white-space: nowrap;
    }

    @media (max-width: 1020px) {
        .hotel-hero {
            min-height: 470px;
            padding-bottom: 165px;
        }

        .hotel-feature-section {
            grid-template-columns: 1fr;
            width: min(900px, calc(100% - 36px));
            margin-top: -130px;
        }

        .hotel-intro-card {
            min-height: auto;
            padding: 52px 48px 122px;
        }

        .hotel-carousel {
            width: calc(100% - 70px);
            height: 390px;
            margin: -82px auto 0;
        }
    }

    @media (max-width: 700px) {
        .hotel-hero {
            min-height: 390px;
            padding: 55px 18px 140px;
        }

        .hotel-hero-title {
            padding-top: 22px;
            font-size: clamp(31px, 9vw, 43px);
        }

        .hotel-feature-section {
            width: min(100% - 22px, 900px);
            margin-top: -105px;
            margin-bottom: 48px;
        }

        .hotel-intro-card {
            padding: 42px 25px 96px;
        }

        .hotel-intro-number {
            top: 24px;
            left: 22px;
            font-size: 70px;
        }

        .hotel-intro-title {
            margin-top: 28px;
            font-size: 23px;
        }

        .hotel-quick-row {
            grid-template-columns: 1fr;
            gap: 3px;
        }

        .hotel-carousel {
            width: calc(100% - 24px);
            height: 270px;
            margin-top: -64px;
        }

        .hotel-carousel-button {
            width: 38px;
            height: 38px;
        }

        .hotel-content-card {
            width: min(100% - 22px, 960px);
            padding: 40px 24px 44px;
            margin-bottom: 48px;
        }

        .amenities-list {
            grid-template-columns: 1fr;
            row-gap: 14px;
        }
    }
</style>

<div class="hotel-detail-page">
    <!-- Ảnh primary đặt dưới tên khách sạn -->
    <section
        class="hotel-hero"
        style="
            background-image:
                linear-gradient(rgba(8, 8, 8, 0.62), rgba(8, 8, 8, 0.66)),
                url('<?= htmlspecialchars($primaryImage, ENT_QUOTES, 'UTF-8') ?>');
        "
    >
        <h1 class="hotel-hero-title">
            <?= htmlspecialchars($hotel['name']) ?>
        </h1>
    </section>

    <!-- Card thông tin bên trái và slider ảnh bên phải -->
    <section class="hotel-feature-section">
        <div class="hotel-intro-card">
            <span class="hotel-intro-number" aria-hidden="true">01</span>

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
                    <span class="hotel-quick-label">Hạng sao:</span>
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
                            <?= amenityIcon($amenity['icon']) ?>

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

<script>
document.addEventListener('DOMContentLoaded', function () {
    const carousel = document.getElementById('hotelCarousel');

    if (!carousel) {
        return;
    }

    const track = carousel.querySelector('.hotel-carousel-track');
    const slides = Array.from(
        carousel.querySelectorAll('.hotel-carousel-slide')
    );
    const previousButton = carousel.querySelector(
        '.hotel-carousel-button.previous'
    );
    const nextButton = carousel.querySelector(
        '.hotel-carousel-button.next'
    );
    const dots = Array.from(
        carousel.querySelectorAll('.hotel-carousel-dot')
    );

    if (!track || slides.length <= 1) {
        return;
    }

    let currentIndex = 0;
    let autoSlideTimer = null;

    function showSlide(index) {
        currentIndex = (index + slides.length) % slides.length;
        track.style.transform =
            'translateX(-' + (currentIndex * 100) + '%)';

        dots.forEach(function (dot, dotIndex) {
            dot.classList.toggle('active', dotIndex === currentIndex);
        });
    }

    function startAutoSlide() {
        window.clearInterval(autoSlideTimer);

        autoSlideTimer = window.setInterval(function () {
            showSlide(currentIndex + 1);
        }, 3000);
    }

    function restartAutoSlide() {
        startAutoSlide();
    }

    if (previousButton) {
        previousButton.addEventListener('click', function () {
            showSlide(currentIndex - 1);
            restartAutoSlide();
        });
    }

    if (nextButton) {
        nextButton.addEventListener('click', function () {
            showSlide(currentIndex + 1);
            restartAutoSlide();
        });
    }

    dots.forEach(function (dot) {
        dot.addEventListener('click', function () {
            showSlide(Number(this.dataset.index));
            restartAutoSlide();
        });
    });

    carousel.addEventListener('mouseenter', function () {
        window.clearInterval(autoSlideTimer);
    });

    carousel.addEventListener('mouseleave', function () {
        startAutoSlide();
    });

    showSlide(0);
    startAutoSlide();
});
</script>

<?php require_once 'includes/footer.php'; ?>