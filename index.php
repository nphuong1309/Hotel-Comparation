<?php
require_once 'includes/db-connect.php';
require_once 'includes/header.php';

$limit = 12;
$offset = 0;

$sql = "SELECT h.id, h.name, h.address, h.description, h.vibe,
MIN(r.price) as min_price,
GROUP_CONCAT(DISTINCT i.image_url ORDER BY i.id SEPARATOR ',') AS images
FROM hotels h
LEFT JOIN rooms r ON h.id = r.hotel_id
LEFT JOIN hotel_images i ON h.id = i.hotel_id
GROUP BY h.id
ORDER BY h.id DESC
LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
$stmt->execute();
$hotels = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'customer'): ?>
    <section class="welcome-banner" style="background:#fff; padding:15px 20px; border-radius:8px; margin:20px 0;">
        <p>Chào mừng trở lại, <b><?= htmlspecialchars($_SESSION['username']) ?></b>!
            Khám phá các khách sạn phù hợp với bạn ở Cần Thơ nhé.</p>
    </section>
<?php endif; ?>

<!-- Form Gợi ý thông minh -->
<section class="smart-search-section">
    <div class="search-box">
        <h2>Tìm Khách Sạn Chân Ái</h2>
        <form action="search.php" method="GET" id="smartSearchForm">
            <div class="form-group">
                <label>Số người (1-30):</label>
                <input type="number" name="capacity" min="1" max="30" value="2">
            </div>
            <div class="form-group range-group">
                <label>Ngân sách: <span id="budgetValue">500,000</span> VNĐ</label>
                <input type="range" id="budgetRange" name="budget" min="50000" max="3000000" step="50000" value="500000">
            </div>
            <div class="form-group">
                <label>Phong cách:</label>
                <select name="vibe">
                    <option value="Hiện đại">Hiện đại</option>
                    <option value="Yên tĩnh">Yên tĩnh</option>
                    <option value="Trung tâm">Trung tâm</option>
                    <option value="Hệ sinh thái">Hệ sinh thái</option>
                    <option value="Thiên nhiên">Thiên nhiên</option>
                </select>
            </div>
            <button type="submit" class="btn-primary">Tìm ngay</button>
        </form>
    </div>
</section>

<!-- Danh sách khách sạn -->
<section class="hotel-list-section">
    <h2>Tất cả khách sạn</h2>
    <div class="hotel-grid">
        <?php foreach ($hotels as $hotel):
            $images = explode(',', $hotel['images']);
            $first_img = $images[0] ?: 'https://via.placeholder.com/400x250';
        ?>
            <article class="hotel-card">
                <!-- Ảnh bìa có data-images để JS tạo hiệu ứng tự nhảy ảnh -->
                <img src="<?= htmlspecialchars($first_img) ?>" class="auto-slide-img" data-images="<?= htmlspecialchars($hotel['images']) ?>" alt="Hotel">

                <div class="card-content">
                    <h3><?= htmlspecialchars($hotel['name']) ?></h3>
                    <p class="price">Từ <?= number_format($hotel['min_price']) ?> đ/đêm</p>

                    <div class="card-actions">
                        <button class="btn-outline btn-toggle-detail">Xem chi tiết</button>
                        <label class="compare-checkbox">
                            <input type="checkbox" class="cb-compare" value="<?= $hotel['id'] ?>">
                            <span>➕ So sánh</span>
                        </label>
                    </div>

                    <!-- Phần thông tin xổ xuống -->
                    <div class="dropdown-detail" style="display: none;">
                        <p><strong>Địa chỉ:</strong> <?= htmlspecialchars($hotel['address']) ?></p>
                        <p><strong>Phong cách:</strong> <?= htmlspecialchars($hotel['vibe']) ?></p>
                        <p><strong>Mô tả:</strong> <?= htmlspecialchars($hotel['description']) ?></p>
                        <a href="detail.php?id=<?= $hotel['id'] ?>">Tới trang chi tiết đầy đủ &raquo;</a>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<!-- Thanh Dock So Sánh Nổi -->
<div id="compareDock" class="compare-dock hidden">
    <div class="container dock-content">
        <span>Đã chọn <strong id="compareCount">0</strong> khách sạn (Tối đa 5)</span>
        <div class="dock-actions">
            <button id="btnClearCompare" class="btn-text">Xóa tất cả</button>
            <form action="compare.php" method="GET" id="compareForm">
                <button type="submit" class="btn-primary">Bắt đầu so sánh</button>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>