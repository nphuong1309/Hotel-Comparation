<?php
require_once 'includes/db-connect.php';

$limit = 12;
$offset = 0;

/**
 * Lấy toàn bộ tiện nghi để dùng cho bộ lọc và khu vực "Our Services".
 */
$amenityStmt = $pdo->query(
    "SELECT
        a.id,
        a.name,
        a.icon,
        COUNT(DISTINCT ha.hotel_id) AS hotel_count
     FROM amenities a
     LEFT JOIN hotel_amenities ha ON ha.amenity_id = a.id
     GROUP BY a.id, a.name, a.icon
     ORDER BY a.id ASC"
);
$amenities = $amenityStmt->fetchAll(PDO::FETCH_ASSOC);

/**
 * Lấy tối đa 12 khách sạn cùng giá thấp nhất và danh sách ảnh trong database.
 * Giao diện vẫn tự ưu tiên ảnh vật lý trong thư mục uploads/ nếu có.
 */
$sql = "SELECT
            h.id,
            h.name,
            h.address,
            h.phone,
            h.star_rating,
            h.description,
            h.vibe,
            MIN(r.price) AS min_price,
            GROUP_CONCAT(
                DISTINCT CONCAT(COALESCE(i.is_primary, 0), '::', i.image_url)
                ORDER BY i.is_primary DESC, i.id ASC
                SEPARATOR '|||'
            ) AS db_images
        FROM hotels h
        LEFT JOIN rooms r ON h.id = r.hotel_id
        LEFT JOIN hotel_images i ON h.id = i.hotel_id
        GROUP BY
            h.id,
            h.name,
            h.address,
            h.phone,
            h.star_rating,
            h.description,
            h.vibe
        ORDER BY h.id DESC
        LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$hotels = $stmt->fetchAll(PDO::FETCH_ASSOC);

/**
 * Tìm ảnh vật lý của một khách sạn trong thư mục uploads/.
 */
function localHotelImages(int $hotelId): array
{
    $extensions = ['jpg', 'jpeg', 'png', 'webp', 'jfif', 'gif'];
    $images = [];

    foreach ($extensions as $extension) {
        $matches = glob("uploads/hotel_{$hotelId}_*.{$extension}") ?: [];
        $images = array_merge($images, $matches);
    }

    $images = array_values(array_unique($images));

    usort($images, static function (string $a, string $b): int {
        $aPrimary = stripos(basename($a), '_primary.') !== false;
        $bPrimary = stripos(basename($b), '_primary.') !== false;

        if ($aPrimary !== $bPrimary) {
            return $aPrimary ? -1 : 1;
        }

        return strnatcasecmp($a, $b);
    });

    return $images;
}

/**
 * Gộp ảnh trong uploads/ và ảnh trong bảng hotel_images.
 * Ảnh local được ưu tiên vì đây là cách dự án hiện tại đang hiển thị ổn định nhất.
 */
function hotelImages(int $hotelId, ?string $dbImages): array
{
    $images = localHotelImages($hotelId);

    if (!empty($dbImages)) {
        foreach (explode('|||', $dbImages) as $rawImage) {
            $parts = explode('::', $rawImage, 2);
            $imageUrl = trim($parts[1] ?? $parts[0] ?? '');

            if ($imageUrl !== '' && !in_array($imageUrl, $images, true)) {
                $images[] = $imageUrl;
            }
        }
    }

    if (!$images) {
        $images[] = 'https://via.placeholder.com/1200x760?text=JoyTix';
    }

    return $images;
}


/**
 * Chấm điểm ảnh dùng cho banner đầu trang.
 *
 * Tiêu chí chính:
 * - Ưu tiên ảnh vật lý trong thư mục uploads để đọc được kích thước thật.
 * - Ưu tiên ảnh ngang, độ phân giải cao và tỷ lệ gần với khung hero.
 * - Loại ảnh placeholder và hạn chế ảnh quá vuông/dọc vì sẽ bị cắt nhiều.
 */
function heroImageScore(string $imagePath): float
{
    if (
        stripos($imagePath, 'via.placeholder.com') !== false
        || stripos($imagePath, 'placeholder') !== false
    ) {
        return -1000000;
    }

    $score = 0.0;
    $isLocal = !preg_match('~^https?://~i', $imagePath);

    if ($isLocal && is_file($imagePath)) {
        $size = @getimagesize($imagePath);

        if ($size && !empty($size[0]) && !empty($size[1])) {
            $width = (int) $size[0];
            $height = (int) $size[1];
            $ratio = $width / max(1, $height);
            $area = $width * $height;

            // Điểm độ phân giải: ảnh càng lớn càng ít bị vỡ trên màn hình rộng.
            $score += min(420.0, $area / 9000.0);
            $score += min(180.0, $width / 10.0);

            if ($width >= 1920) {
                $score += 180;
            } elseif ($width >= 1600) {
                $score += 130;
            } elseif ($width >= 1280) {
                $score += 80;
            } elseif ($width < 1000) {
                $score -= 180;
            }

            if ($height >= 850) {
                $score += 75;
            } elseif ($height < 600) {
                $score -= 110;
            }

            // Khung hero thực tế khá rộng; tỷ lệ 1.75–2.35 ít bị mất chi tiết nhất.
            $targetRatio = 2.05;
            $score += max(0.0, 220.0 - abs($ratio - $targetRatio) * 260.0);

            if ($ratio < 1.45) {
                $score -= 320;
            } elseif ($ratio > 2.65) {
                $score -= 130;
            }
        } else {
            $score -= 80;
        }

        $score += 35; // Ảnh local ổn định hơn URL ngoài.
    } else {
        // Ảnh URL ngoài vẫn được dùng làm dự phòng nhưng không đứng trước ảnh local tốt.
        $score += 20;
    }

    if (stripos(basename($imagePath), '_primary.') !== false) {
        $score += 45;
    }

    return $score;
}

/**
 * Lập danh sách ảnh banner. Ảnh phòng nhìn ra toàn cảnh Cần Thơ của Sheraton
 * được ưu tiên làm ảnh mở đầu nếu file primary hiện có; các ảnh sau được xếp
 * theo độ phân giải và độ phù hợp với khung ngang.
 */
function buildHeroSlides(array $hotels, int $limit = 4): array
{
    $candidates = [];

    foreach ($hotels as $hotel) {
        foreach (($hotel['display_images'] ?? []) as $imagePath) {
            $imagePath = trim((string) $imagePath);

            if ($imagePath === '' || isset($candidates[$imagePath])) {
                continue;
            }

            $candidates[$imagePath] = [
                'image' => $imagePath,
                'hotel_name' => (string) ($hotel['name'] ?? 'Khách sạn tại Cần Thơ'),
                'score' => heroImageScore($imagePath),
            ];
        }
    }

    uasort($candidates, static function (array $a, array $b): int {
        return $b['score'] <=> $a['score'];
    });

    $slides = [];

    // Ảnh này đang là ảnh phòng có cửa kính lớn và toàn cảnh Cần Thơ trong bộ ảnh hiện tại.
    $preferredFirst = 'uploads/hotel_12_primary.jpg';

    if (isset($candidates[$preferredFirst]) && $candidates[$preferredFirst]['score'] > -100000) {
        $slides[] = $candidates[$preferredFirst];
        unset($candidates[$preferredFirst]);
    }

    foreach ($candidates as $candidate) {
        if ($candidate['score'] <= -100000) {
            continue;
        }

        $slides[] = $candidate;

        if (count($slides) >= $limit) {
            break;
        }
    }

    if (!$slides) {
        $slides[] = [
            'image' => 'https://via.placeholder.com/1600x900?text=JoyTix',
            'hotel_name' => 'JoyTix',
            'score' => 0,
        ];
    }

    return array_slice($slides, 0, $limit);
}

/**
 * SVG tiện nghi dùng currentColor để đồng bộ màu giao diện.
 */
function homeAmenityIcon(?string $icon): string
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

    return '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">'
        . $paths
        . '</svg>';
}


foreach ($hotels as &$hotel) {
    $hotel['display_images'] = hotelImages(
        (int) $hotel['id'],
        $hotel['db_images'] ?? null
    );
}
unset($hotel);

// Chọn ảnh banner theo chất lượng ảnh thay vì chỉ lấy ảnh đầu của 4 khách sạn mới nhất.
$heroSlides = buildHeroSlides($hotels, 4);

require_once 'includes/header.php';
?>

<style>
    /* =============================================================
       CSS trang chủ (index.php) – chỉ áp dụng cho trang này.
       Các class dùng prefix "patel-" hoặc "hero-", "hotel-modern-",
       "welcome-modern", "services-modern" để tránh xung đột toàn site.
       Biến --patel-* được khai báo tại đây thay vì style.css vì
       chúng chỉ dùng riêng cho layout trang chủ.
    ============================================================= */
    :root {
        --patel-accent: #dda975;
        --patel-accent-dark: #c58b56;
        --patel-ink: #171514;
        --patel-muted: #77716d;
        --patel-line: #e8e2dd;
        --patel-bg: #ffffff;
        --patel-soft: #faf8f6;
    }

    .patel-home,
    .patel-home * {
        box-sizing: border-box;
    }

    .patel-home {
        font-family: var(--font-body, 'Lato', Arial, sans-serif);
        width: 100vw;
        margin-left: calc(50% - 50vw);
        margin-right: calc(50% - 50vw);
        margin-top: 0;
        overflow: hidden;
        background: var(--patel-bg);
        color: var(--patel-ink);
    }

    .patel-shell {
        width: min(1460px, calc(100% - 64px));
        margin: 0 auto;
    }

    .patel-eyebrow {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 22px;
        margin: 0 0 22px;
        font-family: var(--font-label, 'Oswald', Arial, sans-serif);
        font-size: 17px;
        font-weight: 600;
        letter-spacing: .03em;
        text-transform: uppercase;
    }

    .patel-eyebrow::before,
    .patel-eyebrow::after {
        content: '';
        width: 105px;
        height: 1px;
        background: var(--patel-accent);
    }

    .patel-section-title {
        margin: 0;
        font-family: var(--font-heading, 'Cormorant Garamond', Georgia, serif);
        font-size: clamp(44px, 5vw, 72px);
        font-weight: 600;
        line-height: 1.05;
        text-align: center;
    }

    /* ============================================================= */
    /* HERO BANNER                                                   */
    /* ============================================================= */
    .patel-hero {
        position: relative;
        min-height: 720px;
        background: #3f332b;
    }

    .hero-slides,
    .hero-slide,
    .hero-slide::after {
        position: absolute;
        inset: 0;
    }

    .hero-slide {
        opacity: 0;
        transition: opacity 1s ease;
    }

    .hero-slide.is-active {
        opacity: 1;
    }

    .hero-slide img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        object-position: center 48%;
    }

    .hero-slide::after {
        content: '';
        background:
            linear-gradient(90deg, rgba(20, 14, 10, .45), rgba(20, 14, 10, .12) 55%, rgba(20, 14, 10, .34)),
            linear-gradient(0deg, rgba(20, 14, 10, .46), transparent 58%);
    }

    .hero-copy {
        position: relative;
        z-index: 3;
        min-height: 720px;
        padding: 90px 20px 160px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        color: #fff;
        text-align: center;
    }

    .hero-kicker {
        display: flex;
        align-items: center;
        gap: 28px;
        margin-bottom: 34px;
        font-family: var(--font-label, 'Oswald', Arial, sans-serif);
        font-size: 20px;
        font-weight: 600;
        letter-spacing: .025em;
        text-transform: uppercase;
    }

    .hero-kicker::before,
    .hero-kicker::after {
        content: '';
        width: 155px;
        height: 2px;
        background: rgba(255, 255, 255, .88);
    }

    .hero-title {
        max-width: 1220px;
        margin: 0;
        font-family: var(--font-heading, 'Cormorant Garamond', Georgia, serif);
        font-size: clamp(52px, 5.7vw, 92px);
        font-weight: 600;
        line-height: 1.03;
        text-wrap: balance;
        text-shadow: 0 5px 28px rgba(0, 0, 0, .2);
    }

    .hero-subtitle {
        max-width: 760px;
        margin: 28px auto 0;
        font-size: 18px;
        line-height: 1.7;
        color: rgba(255, 255, 255, .9);
    }

    .hero-action {
        display: inline-flex;
        align-items: center;
        gap: 16px;
        margin-top: 34px;
        color: #fff;
        text-decoration: none;
        font-family: var(--font-label, 'Oswald', Arial, sans-serif);
        font-size: 17px;
        font-weight: 500;
        letter-spacing: .03em;
        text-transform: uppercase;
    }

    .hero-action-icon {
        width: 72px;
        height: 72px;
        border: 2px solid var(--patel-accent);
        border-radius: 50%;
        display: grid;
        place-items: center;
        transition: background .25s ease, transform .25s ease;
    }

    .hero-action:hover .hero-action-icon {
        background: var(--patel-accent);
        transform: scale(1.06);
    }

    .hero-action-icon::before {
        content: '';
        width: 0;
        height: 0;
        margin-left: 5px;
        border-top: 10px solid transparent;
        border-bottom: 10px solid transparent;
        border-left: 15px solid var(--patel-accent);
    }

    .hero-action:hover .hero-action-icon::before {
        border-left-color: #fff;
    }

    .hero-dots {
        position: absolute;
        z-index: 4;
        left: 50%;
        bottom: 118px;
        transform: translateX(-50%);
        display: flex;
        gap: 10px;
    }

    .hero-dot {
        width: 9px;
        height: 9px;
        padding: 0;
        border: 1px solid #fff;
        border-radius: 50%;
        background: transparent;
        cursor: pointer;
    }

    .hero-dot.is-active {
        background: #fff;
    }

    /* ============================================================= */
    /* SEARCH BAR (nằm chồng lên cuối hero)                          */
    /* ============================================================= */
    .hero-search-wrap {
        position: relative;
        z-index: 8;
        margin-top: -105px;
    }

    #smartSearchForm.hero-search {
        width: 100% !important;
        max-width: none !important;
        min-height: 182px;
        margin: 0 !important;
        padding: 0 !important;
        display: grid;
        grid-template-columns: .78fr 1.28fr 1.34fr 1.12fr !important;
        gap: 0 !important;
        align-items: stretch;
        background: #fff;
        box-shadow: 0 22px 50px rgba(32, 22, 15, .16);
    }

    .hero-search > * {
        min-width: 0;
    }

    .hero-search-field {
        position: relative;
        min-width: 0;
        padding: 29px 42px;
        border-right: 1px dashed #d9d3cf;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .hero-search-label {
        margin-bottom: 15px;
        font-family: var(--font-heading, 'Cormorant Garamond', Georgia, serif);
        font-size: 18px;
        color: #2a2725;
    }

    .hero-search-value,
    .hero-search input[type='number'] {
        width: 100%;
        border: 0;
        outline: 0;
        background: transparent;
        color: #090909;
        font-family: var(--font-label, 'Oswald', Arial, sans-serif);
        font-size: 28px;
        font-weight: 600;
        text-transform: uppercase;
    }

    .hero-search input[type='number'] {
        appearance: textfield;
    }

    .hero-search input[type='number']::-webkit-inner-spin-button,
    .hero-search input[type='number']::-webkit-outer-spin-button {
        margin: 0;
        appearance: none;
    }

    .budget-value-line {
        display: flex;
        align-items: baseline;
        gap: 8px;
        margin-bottom: 12px;
        font-family: var(--font-label, 'Oswald', Arial, sans-serif);
        font-size: 28px;
        font-weight: 600;
    }

    .budget-unit {
        font-size: 16px;
        color: #807a75;
    }

    .hero-search input[type='range'] {
        width: 100%;
        height: 7px;
        border-radius: 999px;
        outline: none;
        appearance: none;
        cursor: pointer;
    }

    .hero-search input[type='range']::-webkit-slider-thumb {
        width: 19px;
        height: 19px;
        border: 3px solid #fff;
        border-radius: 50%;
        background: var(--patel-accent-dark);
        box-shadow: 0 1px 5px rgba(0,0,0,.2);
        appearance: none;
    }

    .hero-search input[type='range']::-moz-range-thumb {
        width: 16px;
        height: 16px;
        border: 3px solid #fff;
        border-radius: 50%;
        background: var(--patel-accent-dark);
        box-shadow: 0 1px 5px rgba(0,0,0,.2);
    }

    .hero-amenity-details {
        position: relative;
    }

    .hero-amenity-summary {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        list-style: none;
        cursor: pointer;
        font-family: var(--font-label, 'Oswald', Arial, sans-serif);
        font-size: 24px;
        font-weight: 600;
        text-transform: uppercase;
    }

    .hero-amenity-summary::-webkit-details-marker {
        display: none;
    }

    .hero-amenity-summary svg {
        width: 22px;
        height: 22px;
        fill: none;
        stroke: currentColor;
        stroke-width: 2;
    }

    .hero-amenity-details[open] .hero-amenity-summary svg {
        transform: rotate(180deg);
    }

    .hero-amenity-menu {
        position: absolute;
        z-index: 50;
        top: calc(100% + 22px);
        left: -24px;
        width: 360px;
        max-height: 340px;
        overflow-y: auto;
        padding: 12px;
        border: 1px solid #e8ded7;
        background: #fff;
        box-shadow: 0 18px 45px rgba(0,0,0,.18);
    }

    .hero-amenity-option {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        padding: 10px;
        border-radius: 3px;
        cursor: pointer;
        font-size: 14px;
        line-height: 1.35;
    }

    .hero-amenity-option:hover {
        background: #fff6ef;
    }

    .hero-amenity-option input {
        width: 17px;
        height: 17px;
        margin: 1px 0 0;
        accent-color: var(--patel-accent-dark);
    }

    .hero-search-button {
        width: 100% !important;
        height: 100%;
        min-width: 0;
        align-self: stretch;
        justify-self: stretch;
        border: 0;
        background: var(--patel-accent);
        color: #fff;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 18px;
        padding: 30px;
        font-family: var(--font-label, 'Oswald', Arial, sans-serif);
        font-size: 20px;
        font-weight: 600;
        letter-spacing: .02em;
        text-transform: uppercase;
        transition: background .25s ease;
    }

    .hero-search-button:hover {
        background: var(--patel-accent-dark);
    }

    .hero-search-button svg {
        width: 42px;
        height: 42px;
        fill: none;
        stroke: currentColor;
        stroke-width: 1.5;
    }

    /* ============================================================= */
    /* HOTEL CARDS – lưới 2 cột kiểu editorial                       */
    /* ============================================================= */
    .hotel-showcase {
        padding: 120px 0 100px;
    }

    .hotel-showcase-heading {
        margin-bottom: 58px;
    }

    .hotel-list-modern {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 58px 60px;
    }

    .hotel-modern-card {
        min-width: 0;
        background: #fff;
        border: 1px solid #efebe8;
        transition: transform .3s ease, box-shadow .3s ease;
    }

    .hotel-modern-card:hover {
        transform: translateY(-7px);
        box-shadow: 0 18px 45px rgba(41, 31, 24, .12);
    }

    .hotel-modern-image-wrap {
        position: relative;
        height: 500px;
        overflow: hidden;
        background: #eee;
    }

    .hotel-modern-image {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform .65s ease;
    }

    .hotel-modern-card:hover .hotel-modern-image {
        transform: scale(1.045);
    }

    .hotel-price-ribbon {
        position: absolute;
        left: 0;
        bottom: 0;
        min-width: 190px;
        padding: 17px 26px;
        background: var(--patel-accent);
        color: #fff;
        font-family: var(--font-label, 'Oswald', Arial, sans-serif);
        font-size: 17px;
        font-weight: 600;
        letter-spacing: .02em;
        text-transform: uppercase;
    }

    .hotel-star-badge {
        position: absolute;
        right: 18px;
        top: 18px;
        padding: 9px 13px;
        background: rgba(20, 17, 15, .78);
        color: #fff;
        backdrop-filter: blur(6px);
        font-size: 14px;
        font-weight: 600;
    }

    .hotel-modern-body {
        padding: 34px 32px 32px;
    }

    .hotel-meta {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 22px;
        margin-bottom: 18px;
        color: #86817d;
        font-family: var(--font-label, 'Oswald', Arial, sans-serif);
        font-size: 15px;
        text-transform: uppercase;
    }

    .hotel-meta-item {
        display: inline-flex;
        align-items: center;
        gap: 9px;
    }

    .hotel-meta-item svg {
        width: 20px;
        height: 20px;
        fill: none;
        stroke: var(--patel-accent);
        stroke-width: 1.7;
    }

    .hotel-modern-title {
        margin: 0 0 14px;
        font-family: var(--font-heading, 'Cormorant Garamond', Georgia, serif);
        font-size: clamp(31px, 2.7vw, 45px);
        font-weight: 600;
        line-height: 1.14;
    }

    .hotel-modern-description {
        min-height: 78px;
        margin: 0;
        color: #79746f;
        font-size: 16px;
        line-height: 1.7;
        display: -webkit-box;
        overflow: hidden;
        -webkit-box-orient: vertical;
        -webkit-line-clamp: 3;
    }

    .hotel-modern-address {
        margin: 19px 0 0;
        color: #5c5753;
        font-size: 14px;
        line-height: 1.55;
    }

    .hotel-modern-actions {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        margin-top: 25px;
        padding-top: 22px;
        border-top: 1px solid var(--patel-line);
    }

    .hotel-detail-link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 44px;
        padding: 0 20px;
        border: 1px solid var(--patel-ink);
        color: var(--patel-ink);
        text-decoration: none;
        font-family: var(--font-label, 'Oswald', Arial, sans-serif);
        font-size: 15px;
        font-weight: 600;
        text-transform: uppercase;
        transition: background .25s ease, color .25s ease;
    }

    .hotel-detail-link:hover {
        background: var(--patel-ink);
        color: #fff;
    }

    .hotel-compare {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        color: #4a4541;
        font-family: var(--font-label, 'Oswald', Arial, sans-serif);
        font-size: 15px;
        text-transform: uppercase;
    }

    .hotel-compare input {
        width: 18px;
        height: 18px;
        accent-color: var(--patel-accent-dark);
    }

    /* ============================================================= */
    /* WELCOME SECTION                                               */
    /* ============================================================= */
    .welcome-modern {
        position: relative;
        padding: 115px 20px 120px;
        background:
            linear-gradient(rgba(255,255,255,.9), rgba(255,255,255,.9)),
            radial-gradient(circle at 50% 45%, rgba(217,164,115,.11), transparent 42%),
            repeating-linear-gradient(0deg, transparent, transparent 34px, rgba(0,0,0,.025) 35px);
        text-align: center;
    }

    .welcome-copy {
        max-width: 1020px;
        margin: 25px auto 0;
        color: #73706d;
        font-size: 18px;
        line-height: 1.8;
    }

    .welcome-signature {
        margin-top: 28px;
        font-family: var(--font-heading, 'Cormorant Garamond', Georgia, serif);
        font-size: 55px;
        font-style: italic;
        line-height: 1;
    }

    .welcome-team {
        margin-top: 12px;
        font-family: var(--font-label, 'Oswald', Arial, sans-serif);
        font-size: 16px;
        font-weight: 600;
        letter-spacing: .06em;
        text-transform: uppercase;
    }

    .welcome-role {
        margin-top: 8px;
        color: #777;
        font-size: 15px;
    }

    /* ============================================================= */
    /* SERVICES / AMENITIES GRID                                     */
    /* ============================================================= */
    .services-modern {
        padding: 110px 0 125px;
        background: #fff;
    }

    .services-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 72px 38px;
        margin-top: 105px;
    }

    .service-card {
        position: relative;
        min-height: 350px;
        padding: 112px 54px 42px;
        border: 1px solid #e8e4e1;
        text-align: center;
        background: #fff;
        transition: transform .3s ease, box-shadow .3s ease;
    }

    .service-card:hover {
        transform: translateY(-7px);
        box-shadow: 0 18px 45px rgba(31, 24, 19, .09);
    }

    .service-icon {
        position: absolute;
        top: -67px;
        left: 50%;
        width: 136px;
        height: 136px;
        transform: translateX(-50%);
        border: 7px solid #fff;
        border-radius: 50%;
        background: var(--patel-accent);
        display: grid;
        place-items: center;
        box-shadow: 0 10px 32px rgba(35, 25, 19, .12);
    }

    .service-icon svg {
        width: 52px;
        height: 52px;
        fill: none;
        stroke: #111;
        stroke-width: 1.65;
        stroke-linecap: round;
        stroke-linejoin: round;
    }

    .service-title {
        margin: 0 0 19px;
        font-family: var(--font-heading, 'Cormorant Garamond', Georgia, serif);
        font-size: 33px;
        font-weight: 600;
        line-height: 1.25;
    }

    .service-description {
        min-height: 78px;
        margin: 0;
        color: #77726e;
        font-size: 16px;
        line-height: 1.7;
    }

    .service-link {
        display: inline-block;
        margin-top: 22px;
        color: #161413;
        font-family: var(--font-label, 'Oswald', Arial, sans-serif);
        font-size: 15px;
        font-weight: 600;
        text-transform: uppercase;
        text-underline-offset: 4px;
    }



    .services-database-note {
        max-width: 720px;
        margin: 20px auto 0;
        color: #77726e;
        text-align: center;
        font-size: 15px;
        line-height: 1.7;
    }

    .service-description strong {
        color: #191919;
    }

    /* ============================================================= */
    /* COMPARE DOCK – ghi đè style.css bằng ID selector (#compareDock)
       để tăng specificity mà không cần !important quá nhiều.        */
    /* Xem block #compareDock bên dưới – đây là block duy nhất.      */
    /* ============================================================= */

    /* Checkbox so sánh */
    .hotel-compare input,
    .compare-checkbox input {
        accent-color: var(--patel-accent-dark) !important;
    }

    .hotel-compare:has(input:checked) {
        color: var(--patel-accent-dark);
        font-weight: 600;
    }

    @media (max-width: 1180px) {
        #smartSearchForm.hero-search {
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        }

        .hero-search-field:nth-child(2) {
            border-right: 0;
        }

        .hero-search-button {
            min-height: 145px;
        }

        .hotel-modern-image-wrap {
            height: 410px;
        }

        .service-card {
            padding-left: 30px;
            padding-right: 30px;
        }
    }

    @media (max-width: 900px) {
        .patel-shell {
            width: min(100% - 36px, 760px);
        }

        .patel-hero,
        .hero-copy {
            min-height: 650px;
        }

        .hero-kicker::before,
        .hero-kicker::after {
            width: 70px;
        }

        .hotel-list-modern,
        .services-grid {
            grid-template-columns: 1fr;
        }

        .hotel-modern-image-wrap {
            height: 470px;
        }

        .services-grid {
            gap: 100px;
        }
    }

    @media (max-width: 680px) {
        .patel-shell {
            width: min(100% - 24px, 560px);
        }

        .patel-hero,
        .hero-copy {
            min-height: 610px;
        }

        .hero-copy {
            padding: 70px 20px 155px;
        }

        .hero-kicker {
            gap: 12px;
            font-size: 14px;
        }

        .hero-kicker::before,
        .hero-kicker::after {
            width: 36px;
        }

        .hero-title {
            font-size: clamp(44px, 15vw, 68px);
        }

        .hero-subtitle {
            font-size: 15px;
        }

        .hero-action-icon {
            width: 58px;
            height: 58px;
        }

        .hero-search-wrap {
            margin-top: -80px;
        }

        #smartSearchForm.hero-search {
            grid-template-columns: 1fr !important;
        }

        .hero-search-field {
            min-height: 126px;
            padding: 24px;
            border-right: 0;
            border-bottom: 1px dashed #d9d3cf;
        }

        .hero-search-button {
            min-height: 115px;
        }

        .hero-amenity-menu {
            left: 0;
            width: min(360px, calc(100vw - 72px));
        }

        .hotel-showcase {
            padding-top: 90px;
        }

        .hotel-modern-image-wrap {
            height: 330px;
        }

        .hotel-modern-body {
            padding: 26px 22px;
        }

        .hotel-modern-actions {
            align-items: stretch;
            flex-direction: column;
        }

        .hotel-detail-link,
        .hotel-compare {
            width: 100%;
            justify-content: center;
        }

        .patel-eyebrow {
            gap: 12px;
            font-size: 14px;
        }

        .patel-eyebrow::before,
        .patel-eyebrow::after {
            width: 45px;
        }

        .patel-section-title {
            font-size: 42px;
        }

        .welcome-modern {
            padding-left: 18px;
            padding-right: 18px;
        }

        .service-card {
            padding: 100px 24px 34px;
        }
    }


    /* #compareDock – ID selector để đạt specificity cao nhất,
       ghi đè style.css mà không cần cascade phức tạp. */
    #compareDock.compare-dock {
        position: fixed;
        left: 0;
        right: 0;
        bottom: 0;
        z-index: 9999;
        width: 100%;
        background: #191919 !important;
        color: #fffaf5 !important;
        border-top: 3px solid var(--patel-accent) !important;
        box-shadow: 0 -8px 24px rgba(0, 0, 0, .28) !important;
    }

    #compareDock.compare-dock.hidden {
        display: none !important;
    }

    #compareDock .dock-content {
        min-height: 66px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 24px;
        background: transparent !important;
    }

    #compareDock .dock-actions {
        display: flex;
        align-items: center;
        gap: 16px;
        background: transparent !important;
    }

    #compareDock form {
        background: transparent !important;
    }

    #compareDock strong {
        color: var(--patel-accent) !important;
    }

    #compareDock #btnClearCompare {
        border: 0 !important;
        background: transparent !important;
        color: #fff !important;
        cursor: pointer;
    }

    #compareDock #btnClearCompare:hover {
        color: var(--patel-accent) !important;
    }

    #compareDock .btn-primary {
        border: 1px solid var(--patel-accent) !important;
        background: var(--patel-accent) !important;
        color: #191919 !important;
        box-shadow: none !important;
    }

    #compareDock .btn-primary:hover {
        border-color: var(--patel-accent-dark) !important;
        background: var(--patel-accent-dark) !important;
        color: #fff !important;
    }

    @media (max-width: 680px) {
        #compareDock .dock-content {
            min-height: 78px;
            padding-top: 10px;
            padding-bottom: 10px;
            align-items: flex-start;
            flex-direction: column;
            gap: 8px;
        }

        #compareDock .dock-actions {
            width: 100%;
            justify-content: space-between;
        }
    }
</style>

<div class="patel-home">
    <!-- HERO -->
    <section class="patel-hero" aria-label="Khám phá khách sạn tại Cần Thơ">
        <div class="hero-slides" id="heroSlides">
            <?php if ($heroSlides): ?>
                <?php foreach ($heroSlides as $index => $heroSlide): ?>
                    <div class="hero-slide <?= $index === 0 ? 'is-active' : '' ?>">
                        <img
                            src="<?= htmlspecialchars($heroSlide['image']) ?>"
                            alt="<?= htmlspecialchars($heroSlide['hotel_name']) ?>"
                            <?= $index === 0 ? 'fetchpriority="high"' : 'loading="lazy"' ?>
                            decoding="async"
                        >
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="hero-slide is-active">
                    <img
                        src="https://via.placeholder.com/1600x900?text=JoyTix"
                        alt="JoyTix"
                    >
                </div>
            <?php endif; ?>
        </div>

        <div class="hero-copy">
            <div class="hero-kicker">Khám phá Cần Thơ trọn vẹn</div>
            <h1 class="hero-title">Tìm khách sạn phù hợp cho hành trình của bạn</h1>
            <p class="hero-subtitle">
                Tìm kiếm, xem thông tin và so sánh những khách sạn nổi bật tại Cần Thơ
                ngay trên một nền tảng duy nhất.
            </p>
        </div>

        <?php if (count($heroSlides) > 1): ?>
            <div class="hero-dots" aria-label="Chọn ảnh banner">
                <?php foreach ($heroSlides as $index => $heroSlide): ?>
                    <button
                        type="button"
                        class="hero-dot <?= $index === 0 ? 'is-active' : '' ?>"
                        data-slide="<?= $index ?>"
                        aria-label="Ảnh <?= $index + 1 ?>"
                    ></button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- SEARCH BOX -->
    <div class="hero-search-wrap">
        <div class="patel-shell">
            <form action="search.php" method="GET" class="hero-search" id="smartSearchForm">
                <div class="hero-search-field">
                    <label class="hero-search-label" for="capacity">Số người</label>
                    <input
                        type="number"
                        id="capacity"
                        name="capacity"
                        min="1"
                        max="4"
                        value="2"
                        aria-label="Số người"
                        required
                    >
                </div>

                <div class="hero-search-field">
                    <label class="hero-search-label" for="budgetRange">Ngân sách tối đa</label>
                    <div class="budget-value-line">
                        <span id="budgetValue">500.000</span>
                        <span class="budget-unit">VNĐ/đêm</span>
                    </div>
                    <input
                        type="range"
                        id="budgetRange"
                        name="budget"
                        min="50000"
                        max="5000000"
                        step="50000"
                        value="500000"
                    >
                </div>

                <div class="hero-search-field">
                    <span class="hero-search-label">Tiện nghi</span>
                    <details class="hero-amenity-details" id="amenityDropdown">
                        <summary class="hero-amenity-summary">
                            <span id="amenitySummaryText">Chọn tiện nghi</span>
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="m6 9 6 6 6-6"></path>
                            </svg>
                        </summary>

                        <div class="hero-amenity-menu">
                            <?php if ($amenities): ?>
                                <?php foreach ($amenities as $amenity): ?>
                                    <label class="hero-amenity-option">
                                        <input
                                            type="checkbox"
                                            name="amenities[]"
                                            value="<?= (int) $amenity['id'] ?>"
                                        >
                                        <span><?= htmlspecialchars($amenity['name']) ?></span>
                                    </label>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p>Chưa có tiện nghi trong cơ sở dữ liệu.</p>
                            <?php endif; ?>
                        </div>
                    </details>
                </div>

                <button type="submit" class="hero-search-button">
                    <svg viewBox="0 0 48 48" aria-hidden="true">
                        <path d="M10 8h28v32H10z"></path>
                        <path d="M16 16h16M16 23h16M16 30h9"></path>
                        <path d="m31 34 8-8 3 3-8 8-5 1z"></path>
                    </svg>
                    <span>Tìm khách sạn</span>
                </button>
            </form>
        </div>
    </div>

    <!-- HOTEL LIST -->
    <section class="hotel-showcase" id="hotel-showcase">
        <div class="patel-shell">
            <div class="hotel-showcase-heading">
                <div class="patel-eyebrow">Lựa chọn nổi bật tại Cần Thơ</div>
                <h2 class="patel-section-title">Tất cả khách sạn</h2>
            </div>

            <div class="hotel-list-modern">
                <?php if ($hotels): ?>
                    <?php foreach ($hotels as $hotel): ?>
                        <?php
                        $hotelId = (int) $hotel['id'];
                        $rating = max(0, min(5, (float) ($hotel['star_rating'] ?? 0)));
                        $minPrice = (float) ($hotel['min_price'] ?? 0);
                        ?>
                        <article class="hotel-modern-card">
                            <div class="hotel-modern-image-wrap">
                                <img
                                    class="hotel-modern-image auto-slide-img"
                                    src="<?= htmlspecialchars($hotel['display_images'][0]) ?>"
                                    data-images="<?= htmlspecialchars(implode('|||', $hotel['display_images'])) ?>"
                                    alt="<?= htmlspecialchars($hotel['name']) ?>"
                                    loading="lazy"
                                >

                                <div class="hotel-price-ribbon">
                                    Từ <?= $minPrice > 0 ? number_format($minPrice) . ' đ' : 'Liên hệ' ?>
                                </div>

                                <div class="hotel-star-badge">
                                    ★ <?= number_format($rating, 1, ',', '.') ?>
                                </div>
                            </div>

                            <div class="hotel-modern-body">
                                <div class="hotel-meta">
                                    <span class="hotel-meta-item">
                                        <svg viewBox="0 0 24 24" aria-hidden="true">
                                            <path d="M12 21s6-5.2 6-11a6 6 0 1 0-12 0c0 5.8 6 11 6 11Z"></path>
                                            <circle cx="12" cy="10" r="2"></circle>
                                        </svg>
                                        Cần Thơ
                                    </span>
                                    <span class="hotel-meta-item">
                                        <svg viewBox="0 0 24 24" aria-hidden="true">
                                            <path d="M4 12h16"></path>
                                            <path d="M12 4v16"></path>
                                            <circle cx="12" cy="12" r="9"></circle>
                                        </svg>
                                        <?= htmlspecialchars($hotel['vibe'] ?: 'Đang cập nhật') ?>
                                    </span>
                                </div>

                                <h3 class="hotel-modern-title">
                                    <?= htmlspecialchars($hotel['name']) ?>
                                </h3>

                                <p class="hotel-modern-description">
                                    <?= htmlspecialchars(
                                        $hotel['description']
                                        ?: 'Khách sạn đang cập nhật phần giới thiệu và thông tin nổi bật.'
                                    ) ?>
                                </p>

                                <p class="hotel-modern-address">
                                    <strong>Địa chỉ:</strong>
                                    <?= htmlspecialchars($hotel['address'] ?: 'Chưa cập nhật') ?>
                                </p>

                                <div class="hotel-modern-actions">
                                    <a
                                        href="detail.php?id=<?= $hotelId ?>"
                                        class="hotel-detail-link"
                                    >
                                        Xem chi tiết
                                    </a>

                                    <label class="hotel-compare compare-checkbox">
                                        <input
                                            type="checkbox"
                                            class="cb-compare"
                                            value="<?= $hotelId ?>"
                                            data-name="<?= htmlspecialchars($hotel['name']) ?>"
                                        >
                                        <span>Thêm so sánh</span>
                                    </label>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>Chưa có khách sạn để hiển thị.</p>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- WELCOME -->
    <section class="welcome-modern">
        <div class="patel-eyebrow">Chào mừng đến với JoyTix</div>
        <h2 class="patel-section-title">Khám phá Cần Thơ dễ dàng hơn</h2>
        <p class="welcome-copy">
            JoyTix giúp bạn tìm kiếm, xem thông tin và so sánh khách sạn theo số người,
            ngân sách và tiện nghi mong muốn. Từ khu nghỉ dưỡng ven sông đến khách sạn ngay
            trung tâm thành phố, mọi lựa chọn đều được trình bày rõ ràng để bạn tiết kiệm thời gian
            và chọn nơi lưu trú phù hợp hơn cho chuyến đi.
        </p>
        <div class="welcome-signature">JoyTix</div>
        <div class="welcome-team">Đội ngũ JoyTix</div>
        <div class="welcome-role">Đồng hành cùng bạn trên mọi hành trình tại Cần Thơ</div>
    </section>

    <!-- SERVICES / AMENITIES -->
    <section class="services-modern" id="services">
        <div class="patel-shell">
            <div class="patel-eyebrow">Nhanh chóng và tiện lợi</div>
            <h2 class="patel-section-title">Tiện nghi &amp; dịch vụ</h2>
            <p class="services-database-note">Danh sách và số khách sạn được lấy trực tiếp từ dữ liệu tiện nghi hiện có.</p>

            <div class="services-grid">
                <?php if ($amenities): ?>
                    <?php foreach ($amenities as $amenity): ?>
                        <article class="service-card">
                            <div class="service-icon">
                                <?= homeAmenityIcon($amenity['icon']) ?>
                            </div>
                            <h3 class="service-title">
                                <?= htmlspecialchars($amenity['name']) ?>
                            </h3>
                            <?php $hotelCount = (int) ($amenity['hotel_count'] ?? 0); ?>
                            <p class="service-description">
                                <?php if ($hotelCount > 0): ?>
                                    Có tại <strong><?= $hotelCount ?></strong> khách sạn trong hệ thống.
                                <?php else: ?>
                                    Chưa có khách sạn nào được gắn tiện nghi này.
                                <?php endif; ?>
                            </p>
                            <a
                                class="service-link"
                                href="search.php?<?= http_build_query(['amenities' => [(int) $amenity['id']]]) ?>"
                            >
                                Xem khách sạn
                            </a>
                        </article>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>Chưa có tiện nghi trong cơ sở dữ liệu.</p>
                <?php endif; ?>
            </div>
        </div>
    </section>
</div>

<!-- Giữ nguyên chức năng so sánh đang có của website -->
<div id="compareDock" class="compare-dock hidden">
    <div class="container dock-content">
        <span>
            Đã chọn <strong id="compareCount">0</strong>
            khách sạn (Tối đa 5)
        </span>

        <div class="dock-actions">
            <button id="btnClearCompare" class="btn-text" type="button">
                Xóa tất cả
            </button>

            <form action="compare.php" method="GET" id="compareForm">
                <button type="submit" class="btn-primary">
                    Bắt đầu so sánh
                </button>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    /* Banner tự chuyển ảnh */
    const slides = Array.from(document.querySelectorAll('.hero-slide'));
    const dots = Array.from(document.querySelectorAll('.hero-dot'));
    let currentHero = 0;
    let heroTimer = null;

    function showHero(index) {
        if (!slides.length) return;

        currentHero = (index + slides.length) % slides.length;
        slides.forEach((slide, slideIndex) => {
            slide.classList.toggle('is-active', slideIndex === currentHero);
        });
        dots.forEach((dot, dotIndex) => {
            dot.classList.toggle('is-active', dotIndex === currentHero);
        });
    }

    function restartHeroTimer() {
        if (heroTimer) window.clearInterval(heroTimer);
        if (slides.length > 1) {
            heroTimer = window.setInterval(() => showHero(currentHero + 1), 5200);
        }
    }

    dots.forEach(dot => {
        dot.addEventListener('click', function () {
            showHero(Number(this.dataset.slide || 0));
            restartHeroTimer();
        });
    });

    restartHeroTimer();

    /* Giữ màu thanh ngân sách giống chức năng cũ */
    const budgetRange = document.getElementById('budgetRange');
    const budgetValue = document.getElementById('budgetValue');

    function updateBudgetRange() {
        if (!budgetRange) return;

        const min = Number(budgetRange.min);
        const max = Number(budgetRange.max);
        const value = Number(budgetRange.value);
        const percent = ((value - min) / (max - min)) * 100;

        budgetRange.style.background = `
            linear-gradient(
                to right,
                #ead2bb 0%,
                #c58b56 ${percent}%,
                #FFFFFF ${percent}%,
                #FFFFFF 100%
            )
        `;

        if (budgetValue) {
            budgetValue.textContent = value.toLocaleString('vi-VN');
        }
    }

    if (budgetRange) {
        budgetRange.addEventListener('input', updateBudgetRange);
        updateBudgetRange();
    }

    /* Tóm tắt tiện nghi đã chọn */
    const amenityDropdown = document.getElementById('amenityDropdown');
    const amenitySummaryText = document.getElementById('amenitySummaryText');

    if (amenityDropdown && amenitySummaryText) {
        const amenityCheckboxes = Array.from(
            amenityDropdown.querySelectorAll('input[name="amenities[]"]')
        );

        function updateAmenitySummary() {
            const selected = amenityCheckboxes.filter(item => item.checked);

            if (selected.length === 0) {
                amenitySummaryText.textContent = 'Chọn tiện nghi';
            } else if (selected.length === 1) {
                const label = selected[0].closest('label');
                amenitySummaryText.textContent = label
                    ? label.querySelector('span').textContent.trim()
                    : 'Đã chọn 1 tiện nghi';
            } else {
                amenitySummaryText.textContent = `Đã chọn ${selected.length} tiện nghi`;
            }
        }

        amenityCheckboxes.forEach(item => {
            item.addEventListener('change', updateAmenitySummary);
        });

        document.addEventListener('click', function (event) {
            if (amenityDropdown.open && !amenityDropdown.contains(event.target)) {
                amenityDropdown.removeAttribute('open');
            }
        });
    }

    /* Tự đổi ảnh trong từng thẻ khách sạn */
    document.querySelectorAll('.auto-slide-img').forEach(function (image) {
        const images = (image.dataset.images || '')
            .split('|||')
            .map(item => item.trim())
            .filter(Boolean);

        if (images.length <= 1) return;

        let imageIndex = 0;
        window.setInterval(function () {
            imageIndex = (imageIndex + 1) % images.length;
            image.style.opacity = '0.25';

            window.setTimeout(function () {
                image.src = images[imageIndex];
                image.style.opacity = '1';
            }, 220);
        }, 4300);
    });

    /*
     * Chức năng so sánh dự phòng.
     * Nếu js/script.js của dự án đã xử lý, đoạn này vẫn dùng cùng ID/class nên không làm đổi dữ liệu.
     */
    const compareCheckboxes = Array.from(document.querySelectorAll('.cb-compare'));
    const compareDock = document.getElementById('compareDock');
    const compareCount = document.getElementById('compareCount');
    const compareForm = document.getElementById('compareForm');
    const clearCompareButton = document.getElementById('btnClearCompare');

    function selectedCompareIds() {
        return compareCheckboxes
            .filter(item => item.checked)
            .map(item => item.value);
    }

    function renderCompareDock() {
        if (!compareDock || !compareForm || !compareCount) return;

        const ids = selectedCompareIds();
        compareCount.textContent = String(ids.length);
        compareDock.classList.toggle('hidden', ids.length === 0);
        document.body.classList.toggle('compare-dock-visible', ids.length > 0);

        compareForm.querySelectorAll('input[name="hotel_ids[]"]').forEach(input => input.remove());

        ids.forEach(id => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'hotel_ids[]';
            input.value = id;
            compareForm.appendChild(input);
        });
    }

    compareCheckboxes.forEach(item => {
        item.addEventListener('change', function () {
            const selected = selectedCompareIds();

            if (selected.length > 5) {
                this.checked = false;
                window.alert('Bạn chỉ có thể chọn tối đa 5 khách sạn để so sánh.');
            }

            renderCompareDock();
        });
    });

    if (clearCompareButton) {
        clearCompareButton.addEventListener('click', function () {
            compareCheckboxes.forEach(item => {
                item.checked = false;
            });
            renderCompareDock();
        });
    }

    if (compareForm) {
        compareForm.addEventListener('submit', function (event) {
            if (selectedCompareIds().length === 0) {
                event.preventDefault();
                window.alert('Vui lòng chọn ít nhất một khách sạn.');
            }
        });
    }

    renderCompareDock();
});
</script>

<?php require_once 'includes/footer.php'; ?>