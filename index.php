<?php
require_once 'includes/db-connect.php';

$limit = 12;
$offset = 0;

// Lấy danh sách tiện nghi trực tiếp từ database để hiển thị trong bộ lọc.
$amenityStmt = $pdo->query('SELECT id, name FROM amenities ORDER BY id ASC');
$amenities = $amenityStmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy danh sách khách sạn cho trang chủ.
$sql = "SELECT h.id, h.name, h.address, h.description, h.vibe,
        MIN(r.price) AS min_price,
        GROUP_CONCAT(DISTINCT i.image_url ORDER BY i.id SEPARATOR ',') AS images
        FROM hotels h
        LEFT JOIN rooms r ON h.id = r.hotel_id
        LEFT JOIN hotel_images i ON h.id = i.hotel_id
        GROUP BY h.id, h.name, h.address, h.description, h.vibe
        ORDER BY h.id DESC
        LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$hotels = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once 'includes/header.php';
?>

<?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'customer'): ?>
    <section class="welcome-banner" style="background:#fff; padding:15px 20px; border-radius:8px; margin:20px 0;">
        <p>
            Chào mừng trở lại, <b><?= htmlspecialchars($_SESSION['username']) ?></b>!
            Khám phá các khách sạn phù hợp với bạn ở Cần Thơ nhé.
        </p>
    </section>
<?php endif; ?>

<!-- Form gợi ý thông minh -->
<section class="smart-search-section">
    <div class="search-box">
        <h2>Tìm Khách Sạn Chân Ái</h2>

        <form action="search.php" method="GET" id="smartSearchForm">
            <div class="form-group capacity-group">
                <label for="capacity">Số người (1-4):</label>
                <input
                    type="number"
                    id="capacity"
                    name="capacity"
                    min="1"
                    max="4"
                    value="2"
                    required
                >
            </div>

            <div class="form-group range-group">
                <label for="budgetRange">
                    Ngân sách:
                    <span id="budgetValue">500.000</span> VNĐ
                </label>

                <input
                    type="range"
                    id="budgetRange"
                    name="budget"
                    min="50000"
                    max="3000000"
                    step="50000"
                    value="500000"
                >
            </div>

            <!-- Bộ lọc tiện nghi: lấy trực tiếp từ bảng amenities -->
            <div class="form-group amenity-filter">
                <label>Tiện nghi:</label>

                <details class="amenity-dropdown" id="amenityDropdown">
                    <summary class="amenity-summary">
                        <span id="amenitySummaryText">Chọn tiện nghi</span>

                        <span class="amenity-arrow" aria-hidden="true">
                            <svg viewBox="0 0 24 24" focusable="false">
                                <path d="M6 9l6 6 6-6"></path>
                            </svg>
                        </span>
                    </summary>

                    <div class="amenity-menu">
                        <div class="amenity-menu-header">
                            <strong>Chọn nhiều tiện nghi</strong>

                            <button
                                type="button"
                                id="clearAmenitiesDetails"
                                class="amenity-clear-button"
                            >
                                Bỏ chọn
                            </button>
                        </div>

                        <div class="amenity-options">
                            <?php if ($amenities): ?>
                                <?php foreach ($amenities as $amenity): ?>
                                    <label class="amenity-option">
                                        <input
                                            type="checkbox"
                                            name="amenities[]"
                                            value="<?= (int) $amenity['id'] ?>"
                                        >
                                        <span><?= htmlspecialchars($amenity['name']) ?></span>
                                    </label>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="amenity-empty-option">
                                    Chưa có tiện nghi trong database.
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </details>
            </div>

            <button type="submit" class="btn-primary search-submit-button">
                Tìm ngay
            </button>
        </form>
    </div>
</section>

<style>
    /*
     * Chỉ áp dụng cho bộ lọc tiện nghi và phần mô tả nhanh.
     * Không thay đổi cấu hình thanh ngân sách hoặc các thành phần khác.
     */

    .amenity-filter {
        position: relative;
    }

    .amenity-dropdown {
        position: relative;
        width: 100%;
    }

    .amenity-summary {
        box-sizing: border-box;
        width: 100%;
        min-height: 48px;
        padding: 0 14px;
        border: 1px solid #edc1b5;
        border-radius: 7px;
        background: #fff;
        color: #2f2f2f;
        cursor: pointer;

        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;

        list-style: none;
    }

    .amenity-summary::-webkit-details-marker {
        display: none;
    }

    .amenity-dropdown[open] .amenity-summary {
        border-color: #e85d3f;
    }

    .amenity-arrow {
        width: 20px;
        height: 20px;
        flex: 0 0 20px;

        display: inline-flex;
        align-items: center;
        justify-content: center;

        color: #333;
        transition: transform 0.2s ease;
    }

    .amenity-arrow svg {
        width: 18px;
        height: 18px;
        fill: none;
        stroke: currentColor;
        stroke-width: 2;
        stroke-linecap: round;
        stroke-linejoin: round;
    }

    .amenity-dropdown[open] .amenity-arrow {
        transform: rotate(180deg);
    }

    .amenity-menu {
        position: absolute;
        z-index: 2000;
        top: calc(100% + 7px);
        left: 0;

        box-sizing: border-box;
        width: 100%;
        min-width: 310px;
        max-height: 330px;
        overflow: hidden;

        border: 1px solid #edc1b5;
        border-radius: 9px;
        background: #fff;
        box-shadow: 0 9px 24px rgba(0, 0, 0, 0.16);
    }

    .amenity-menu-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;

        padding: 12px 14px;
        border-bottom: 1px solid #eee;
        background: #fffdf8;
    }

    .amenity-clear-button {
        padding: 0;
        border: 0;
        background: transparent;
        color: #c94e32;
        cursor: pointer;
        font-size: 14px;
    }

    .amenity-options {
        max-height: 270px;
        overflow-y: auto;
        padding: 7px;
    }

    .amenity-option {
        display: flex;
        align-items: flex-start;
        gap: 10px;

        padding: 9px;
        border-radius: 6px;
        cursor: pointer;
        line-height: 1.35;
    }

    .amenity-option:hover {
        background: #fff4e6;
    }

    .amenity-option input[type="checkbox"] {
        appearance: auto;
        width: 17px;
        height: 17px;
        min-width: 17px;
        margin: 1px 0 0;
        padding: 0;
        accent-color: #e85d3f;
        cursor: pointer;
    }

    .amenity-empty-option {
        margin: 8px;
        color: #777;
    }

    /* Chỉ thẻ được bấm mới tăng chiều cao; các thẻ cùng hàng không bị kéo giãn. */
    .hotel-grid {
        align-items: start;
    }

    .hotel-card {
        align-self: start;
    }

    /* Chỉ áp dụng cho phần thông tin nhanh trong thẻ khách sạn. */
    .hotel-card .dropdown-detail {
        margin-top: 16px;
        padding-top: 14px;
        border-top: 1px solid #ead6ce;
        font-size: 14px;
    }

    .quick-detail-row {
        display: grid;
        grid-template-columns: 88px minmax(0, 1fr);
        gap: 10px;
        margin-bottom: 10px;
        line-height: 1.5;
    }

    .quick-detail-label {
        font-weight: 700;
        color: #3f2940;
    }

    .quick-detail-value {
        min-width: 0;
        color: #555;
        word-break: break-word;
    }

    .quick-detail-description {
        display: -webkit-box;
        overflow: hidden;
        -webkit-box-orient: vertical;
        -webkit-line-clamp: 3;
    }

    .quick-detail-link {
        display: flex;
        align-items: center;
        justify-content: center;

        width: fit-content;
        margin: 12px 0 0 auto;
        padding: 7px 14px;

        border: 1px solid #e85d3f;
        border-radius: 4px;
        background: #e85d3f;
        color: #fff;

        font-weight: 600;
        text-decoration: none;
        cursor: pointer;
    }

    .quick-detail-link:hover {
        border-color: #d94f34;
        background: #d94f34;
        color: #fff;
        text-decoration: none;
    }

    @media (max-width: 768px) {
        .amenity-menu {
            min-width: 100%;
        }
    }

    @media (max-width: 520px) {
        .quick-detail-row {
            grid-template-columns: 1fr;
            gap: 2px;
        }
    }
</style>

<!-- Danh sách khách sạn -->
<section class="hotel-list-section">
    <h2>Tất cả khách sạn</h2>

    <div class="hotel-grid">
        <?php foreach ($hotels as $hotel):
            $hotelId = (int) $hotel['id'];

            // Ảnh bìa chính: hotel_1_primary.jpg, hotel_2_primary.jpg,...
            $primaryImages = glob(
                "uploads/hotel_{$hotelId}_primary.{jpg,jpeg,png,webp}",
                GLOB_BRACE
            );

            $firstImg = !empty($primaryImages)
                ? $primaryImages[0]
                : 'https://via.placeholder.com/400x250';

            // Ảnh dùng cho hiệu ứng tự nhảy: hotel_1_1.jpg, hotel_1_2.jpg,...
            $slideImages = glob(
                "uploads/hotel_{$hotelId}_*.{jpg,jpeg,png,webp}",
                GLOB_BRACE
            );

            // Chỉ lấy file dạng hotel_ID_SỐ.jpg, không lấy hotel_ID_primary.jpg.
            $slideImages = array_filter(
                $slideImages,
                static function (string $image) use ($hotelId): bool {
                    return (bool) preg_match(
                        "/hotel_{$hotelId}_[0-9]+\.(jpg|jpeg|png|webp)$/i",
                        basename($image)
                    );
                }
            );

            natsort($slideImages);
            $slideImages = array_values($slideImages);

            $allCarouselImages = array_merge($primaryImages, $slideImages);

            if (empty($allCarouselImages)) {
                $allCarouselImages[] = 'https://via.placeholder.com/400x250';
            }

            $imagesString = implode(',', $allCarouselImages);
        ?>
            <article class="hotel-card">
                <img
                    src="<?= htmlspecialchars($firstImg) ?>"
                    class="auto-slide-img"
                    data-images="<?= htmlspecialchars($imagesString) ?>"
                    alt="<?= htmlspecialchars($hotel['name']) ?>"
                >

                <div class="card-content">
                    <h3><?= htmlspecialchars($hotel['name']) ?></h3>

                    <p class="price">
                        Từ <?= number_format((float) $hotel['min_price']) ?> đ/đêm
                    </p>

                    <div class="card-actions">
                        <button
                            type="button"
                            class="btn-outline btn-toggle-detail"
                        >
                            Xem mô tả
                        </button>

                        <label class="compare-checkbox">
                            <input
                                type="checkbox"
                                class="cb-compare"
                                value="<?= $hotelId ?>"
                            >
                            <span>➕ So sánh</span>
                        </label>
                    </div>

                    <div class="dropdown-detail" style="display: none;">
                        <div class="quick-detail-row">
                            <span class="quick-detail-label">Địa chỉ</span>
                            <span class="quick-detail-value">
                                <?= htmlspecialchars($hotel['address']) ?>
                            </span>
                        </div>

                        <div class="quick-detail-row">
                            <span class="quick-detail-label">Phong cách</span>
                            <span class="quick-detail-value">
                                <?= htmlspecialchars($hotel['vibe']) ?>
                            </span>
                        </div>

                        <div class="quick-detail-row">
                            <span class="quick-detail-label">Mô tả</span>
                            <span class="quick-detail-value quick-detail-description">
                                <?= htmlspecialchars($hotel['description']) ?>
                            </span>
                        </div>

                        <a
                            class="quick-detail-link"
                            href="detail.php?id=<?= $hotelId ?>"
                        >
                            Xem chi tiết
                        </a>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<!-- Thanh dock so sánh nổi -->
<div id="compareDock" class="compare-dock hidden">
    <div class="container dock-content">
        <span>
            Đã chọn <strong id="compareCount">0</strong>
            khách sạn (Tối đa 5)
        </span>

        <div class="dock-actions">
            <button id="btnClearCompare" class="btn-text">
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
    const budgetRange = document.getElementById('budgetRange');
    const budgetValue = document.getElementById('budgetValue');

    if (!budgetRange) {
        return;
    }

    function updateBudgetRange() {
        const min = Number(budgetRange.min);
        const max = Number(budgetRange.max);
        const value = Number(budgetRange.value);
        const percent = ((value - min) / (max - min)) * 100;

        budgetRange.style.background = `
            linear-gradient(
                to right,
                #FFD6BF 0%,
                #FF7A59 ${percent}%,
                #FFFFFF ${percent}%,
                #FFFFFF 100%
            )
        `;

        if (budgetValue) {
            budgetValue.textContent = value.toLocaleString('vi-VN');
        }
    }

    budgetRange.addEventListener('input', updateBudgetRange);
    updateBudgetRange();
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const dropdown = document.getElementById('amenityDropdown');
    const summaryText = document.getElementById('amenitySummaryText');
    const clearButton = document.getElementById('clearAmenitiesDetails');

    if (!dropdown || !summaryText) {
        return;
    }

    const checkboxes = Array.from(
        dropdown.querySelectorAll('input[name="amenities[]"]')
    );

    function updateAmenitySummary() {
        const selected = checkboxes.filter(function (checkbox) {
            return checkbox.checked;
        });

        if (selected.length === 0) {
            summaryText.textContent = 'Chọn tiện nghi';
            return;
        }

        if (selected.length === 1) {
            const label = selected[0].closest('.amenity-option');
            const text = label ? label.querySelector('span') : null;

            summaryText.textContent = text
                ? text.textContent.trim()
                : 'Đã chọn 1 tiện nghi';

            return;
        }

        summaryText.textContent =
            'Đã chọn ' + selected.length + ' tiện nghi';
    }

    checkboxes.forEach(function (checkbox) {
        checkbox.addEventListener('change', updateAmenitySummary);
    });

    if (clearButton) {
        clearButton.addEventListener('click', function () {
            checkboxes.forEach(function (checkbox) {
                checkbox.checked = false;
            });

            updateAmenitySummary();
        });
    }

    document.addEventListener('click', function (event) {
        if (dropdown.open && !dropdown.contains(event.target)) {
            dropdown.removeAttribute('open');
        }
    });

    updateAmenitySummary();
});
</script>

<?php require_once 'includes/footer.php'; ?>