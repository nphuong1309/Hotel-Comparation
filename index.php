<?php
/** INDEX.PHP: trang chủ, tìm kiếm nhanh, danh sách khách sạn và thanh so sánh. */
require_once 'includes/bootstrap.php';

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

foreach ($hotels as &$hotel) {
    $hotel['display_images'] = hotel_image_candidates(
        (int) $hotel['id'],
        $hotel['db_images'] ?? null
    );
    if (!$hotel['display_images']) {
        $hotel['display_images'] = ['https://via.placeholder.com/1200x760?text=JoyTix'];
    }
}
unset($hotel);

// Chọn ảnh banner theo chất lượng ảnh thay vì chỉ lấy ảnh đầu của 4 khách sạn mới nhất.
$heroSlides = buildHeroSlides($hotels, 4);

require_once 'includes/header.php';
?>
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
                                <?= amenity_icon_svg($amenity['icon']) ?>
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
<?php require_once 'includes/footer.php'; ?>
