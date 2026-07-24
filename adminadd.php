<?php
/** ADMINADD.PHP: form admin tạo khách sạn, phòng, tiện nghi và ảnh trong một transaction. */
require_once 'includes/bootstrap.php';
require_admin();

$msg = '';
$msgType = '';

// --- DỮ LIỆU ĐỘNG TỪ DATABASE ĐỂ ĐỔ VÀO GIAO DIỆN ---
try {
    // 1. Lấy danh sách tiện ích trực tiếp từ database để làm checkbox thông minh
    $amenityStmt = $pdo->query('SELECT id, name FROM amenities ORDER BY id ASC');
    $amenitiesList = $amenityStmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. Lấy các Vibe duy nhất hiện có từ bảng hotels để làm dropdown danh mục sẵn có
    $stmt_vibes = $pdo->query("SELECT DISTINCT vibe FROM hotels WHERE vibe IS NOT NULL AND vibe != '' ORDER BY vibe ASC");
    $existing_vibes = $stmt_vibes->fetchAll(PDO::FETCH_COLUMN);

    if (empty($existing_vibes)) {
        $existing_vibes = ['Hiện đại', 'Cổ điển', 'Vintage', 'Sang trọng, Cao cấp', 'Nghỉ dưỡng'];
    }
} catch (Exception $e) {
    $amenitiesList = [];
    $existing_vibes = ['Hiện đại', 'Cổ điển', 'Vintage'];
}

if (is_post_request()) {
    require_csrf();
    $name        = trim((string) ($_POST['name'] ?? ''));
    $price_2     = positive_int($_POST['price_2'] ?? null) ?? 0;
    $price_4     = positive_int($_POST['price_4'] ?? null) ?? 0;
    $vibe        = trim((string) ($_POST['vibe'] ?? ''));
    $address     = trim((string) ($_POST['address'] ?? ''));
    $phone       = trim((string) ($_POST['phone'] ?? ''));
    $stars       = filter_var($_POST['stars'] ?? null, FILTER_VALIDATE_FLOAT);
    $description = trim((string) ($_POST['description'] ?? ''));

    // Mảng chứa các ID tiện ích được Admin tick chọn
    $allowedAmenityIds = array_map('intval', array_column($amenitiesList, 'id'));
    $selected_amenities = array_values(array_intersect(
        array_unique(array_map('intval', (array) ($_POST['amenities'] ?? []))),
        $allowedAmenityIds
    ));

    // Form Validation (Backend)
    if (empty($name)) {
        $msg = "Tên không được bỏ trống!";
        $msgType = 'error';
    } elseif (empty($address)) {
        $msg = "Địa chỉ không được bỏ trống!";
        $msgType = 'error';
    } elseif ($vibe === '') {
        $msg = "Vui lòng chọn phong cách khách sạn!";
        $msgType = 'error';
    } elseif ($price_2 <= 0 || $price_4 <= 0) {
        $msg = "Giá phòng phải là số dương!";
        $msgType = 'error';
    } elseif ($stars === false || $stars < 1 || $stars > 5) {
        $msg = "Số sao không hợp lệ (chỉ từ 1 đến 5 sao)!";
        $msgType = 'error';
    } else {
        $savedImages = [];
        try {
            $pdo->beginTransaction(); // Bắt đầu Transaction

            // Tìm ID nhỏ nhất đang bị thiếu
$sql_find_id = "
    SELECT MIN(candidate_id)
    FROM (
        SELECT 1 AS candidate_id

        UNION

        SELECT id + 1 AS candidate_id
        FROM hotels
    ) AS candidates
    LEFT JOIN hotels h ON h.id = candidates.candidate_id
    WHERE h.id IS NULL
";

$hotel_id = (int) $pdo->query($sql_find_id)->fetchColumn();

// Thêm khách sạn bằng ID nhỏ nhất còn trống
$sql_hotel = "
    INSERT INTO hotels (
        id,
        name,
        vibe,
        address,
        phone,
        star_rating,
        description
    ) VALUES (?, ?, ?, ?, ?, ?, ?)
";

$stmt = $pdo->prepare($sql_hotel);
$stmt->execute([
    $hotel_id,
    $name,
    $vibe,
    $address,
    $phone,
    $stars,
    $description
]);

            // 2. Lưu bảng rooms
            $stmt_room = $pdo->prepare("INSERT INTO rooms (hotel_id, capacity, price) VALUES (?, ?, ?)");
            $stmt_room->execute([$hotel_id, 2, $price_2]);
            $stmt_room->execute([$hotel_id, 4, $price_4]);

            // 3. Lưu bảng hotel_amenities (Xử lý tiện ích thông minh bằng mối quan hệ N-N)
            if (!empty($selected_amenities) && is_array($selected_amenities)) {
                $stmt_amenity = $pdo->prepare("INSERT INTO hotel_amenities (hotel_id, amenity_id) VALUES (?, ?)");
                foreach ($selected_amenities as $amenity_id) {
                    $stmt_amenity->execute([$hotel_id, (int)$amenity_id]);
                }
            }

            if (isset($_FILES['images'])) {
                $savedImages = store_uploaded_images($_FILES['images'], "hotel_{$hotel_id}", 10, true);
                $stmt_img = $pdo->prepare('INSERT INTO hotel_images (hotel_id, image_url, is_primary) VALUES (?, ?, ?)');
                foreach ($savedImages as $index => $imagePath) {
                    $stmt_img->execute([$hotel_id, $imagePath, $index === 0 ? 1 : 0]);
                }
            }

            $pdo->commit();
            $_SESSION['flash_success'] = "Thêm khách sạn thành công!";
            redirect('admin.php');
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            foreach ($savedImages as $imagePath) {
                delete_upload_file($imagePath);
            }
            error_log('Hotel creation failed: ' . $e->getMessage());
            $msg = $e instanceof RuntimeException ? $e->getMessage() : 'Không thể thêm khách sạn lúc này.';
            $msgType = 'error';
        }
    }
}

require_once 'includes/header.php';
?>
<div class="container hotel-admin-form">
    <h2 class="hotel-admin-form__title">Thêm Khách Sạn Mới</h2>

    <?php if ($msg): ?>
        <div class="hotel-admin-form__notice <?= $msgType === 'success' ? 'hotel-admin-form__notice--success' : 'hotel-admin-form__notice--error' ?>">
            <?= htmlspecialchars($msg) ?>
        </div>
        <?php if ($msgType === 'success'): ?>
            <div class="hotel-admin-form__notice-actions">
                <a href="admin.php" class="btn-primary hotel-admin-form__link">Về trang quản trị</a>
                <a href="index.php" class="btn-outline hotel-admin-form__link">Về trang chủ</a>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <div class="hotel-admin-form__map-helper">
        <label class="hotel-admin-form__map-label">🔑 Tự động điền nhanh bằng Tên hoặc Link Google Maps:</label>
        <div class="hotel-admin-form__map-row">
            <input type="text" id="googleMapsUrl" class="form-control hotel-admin-form__map-input" placeholder="Dán link Google Maps hoặc nhập tên khách sạn...">
            <button type="button" id="btnFetchInfo" class="btn-primary hotel-admin-form__map-button">Lấy thông tin</button>
        </div>
        <span id="fetchLoading" class="hotel-admin-form__map-loading">⏳ Đang cào dữ liệu từ Google Maps, vui lòng đợi...</span>
    </div>

    <form action="" method="POST" enctype="multipart/form-data">
        <?= csrf_field() ?>

        <div class="form-group">
            <label>Tên khách sạn:</label>
            <input type="text" name="name" id="adminName" class="form-control" maxlength="150" value="<?= e($_POST['name'] ?? '') ?>" required>
        </div>

        <div class="form-group">
            <label>Địa chỉ:</label>
            <input type="text" name="address" id="adminAddress" class="form-control" maxlength="255" value="<?= e($_POST['address'] ?? '') ?>" placeholder="Số nhà, Tên đường, Quận/Huyện..." required>
        </div>

        <div class="hotel-admin-form__two-columns">
            <div class="form-group">
                <label>Số điện thoại:</label>
                <input type="tel" name="phone" id="adminPhone" class="form-control" maxlength="25" value="<?= e($_POST['phone'] ?? '') ?>" placeholder="Ví dụ: 0901234567">
            </div>

            <div class="form-group">
                <label>Đánh giá:</label>
                <input type="number" name="stars" id="adminStars" class="form-control" min="1" max="5" step="0.1" value="<?= e($_POST['stars'] ?? '') ?>" required>
            </div>
        </div>

        <div class="hotel-admin-form__two-columns">
            <div class="form-group">
                <label>Giá phòng 2 người (VNĐ):</label>
                <input type="number" name="price_2" class="form-control" min="1" value="<?= e($_POST['price_2'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label>Giá phòng 4 người (VNĐ):</label>
                <input type="number" name="price_4" class="form-control" min="1" value="<?= e($_POST['price_4'] ?? '') ?>" required>
            </div>
        </div>

        <div class="form-group">
            <label>Phong cách (Vibe):</label>
            <select name="vibe" id="adminVibe" class="form-control" required>
                <option value="">-- Chọn Phong cách --</option>
                <?php foreach ($existing_vibes as $v): ?>
                    <option value="<?= e($v) ?>" <?= (($_POST['vibe'] ?? '') === $v) ? 'selected' : '' ?>><?= e($v) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Mô tả khách sạn:</label>
            <textarea name="description" id="adminDescription" class="form-control" rows="4" placeholder="Nhập mô tả chi tiết giới thiệu về khách sạn..."><?= e($_POST['description'] ?? '') ?></textarea>
        </div>

        <div class="form-group">
            <label>Tiện nghi và dịch vụ (Tick chọn):</label>
            <div class="amenities-grid" id="facilitiesContainer">
                <?php if ($amenitiesList): ?>
                    <?php foreach ($amenitiesList as $amenity): ?>
                        <label class="amenity-checkbox" data-name="<?= htmlspecialchars(strtolower($amenity['name'])) ?>">
                            <input type="checkbox" name="amenities[]" value="<?= (int)$amenity['id'] ?>" class="facility-checkbox" <?= in_array((int) $amenity['id'], $selected_amenities ?? [], true) ? 'checked' : '' ?>>
                            <?= htmlspecialchars($amenity['name']) ?>
                        </label>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="hotel-admin-form__empty">Chưa có tiện ích nào trong cơ sở dữ liệu.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="form-group">
            <label>Chọn nhiều ảnh (Ảnh đầu tiên sẽ làm ảnh bìa):</label>
            <input type="file" name="images[]" multiple accept="image/*" class="form-control">
        </div>

        <div class="hotel-admin-form__footer-actions">
            <button type="submit" class="btn-primary">Lưu dữ liệu</button>
            <a href="admin.php" class="btn-outline hotel-admin-form__cancel">Hủy</a>
        </div>
    </form>
</div>
<?php require_once 'includes/footer.php'; ?>
