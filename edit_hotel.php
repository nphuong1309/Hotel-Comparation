<?php
/** EDIT_HOTEL.PHP: form admin cập nhật khách sạn hiện có và thêm ảnh mới. */
require_once 'includes/bootstrap.php';
require_admin();

$id = positive_int($_GET['id'] ?? null);
if ($id === null) {
    http_response_code(400);
    exit('ID khách sạn không hợp lệ.');
}

$msg = '';
$msg_type = '';

// Lấy danh sách tiện ích trực tiếp từ database để làm checkbox thông minh
$amenityStmt = $pdo->query('SELECT id, name FROM amenities ORDER BY id ASC');
$amenitiesList = $amenityStmt->fetchAll(PDO::FETCH_ASSOC);

// Xử lý khi bấm Lưu
if (is_post_request()) {
    require_csrf();
    $name        = trim((string) ($_POST['name'] ?? ''));
    $vibe        = trim((string) ($_POST['vibe'] ?? ''));
    $price_2     = positive_int($_POST['price_2'] ?? null) ?? 0;
    $price_4     = positive_int($_POST['price_4'] ?? null) ?? 0;
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

    if (empty($name)) {
        $msg = "Tên không được bỏ trống!";
        $msg_type = "error";
    } elseif (empty($address)) {
        $msg = "Địa chỉ không được bỏ trống!";
        $msg_type = "error";
    } elseif ($vibe === '') {
        $msg = "Phong cách không được bỏ trống!";
        $msg_type = "error";
    } elseif ($price_2 <= 0 || $price_4 <= 0) {
        $msg = "Giá phòng phải là số dương!";
        $msg_type = "error";
    } elseif ($stars === false || $stars < 1 || $stars > 5) {
        $msg = "Số sao không hợp lệ (chỉ từ 1 đến 5 sao)!";
        $msg_type = "error";
    } else {
        $savedImages = [];
        try {
            $pdo->beginTransaction();

            // 1. Cập nhật bảng hotels (sử dụng đúng cột star_rating, bỏ facilities)
            $sql_hotel = "UPDATE hotels SET 
                            name = ?, 
                            vibe = ?, 
                            address = ?, 
                            phone = ?, 
                            star_rating = ?, 
                            description = ? 
                          WHERE id = ?";
            $stmt = $pdo->prepare($sql_hotel);
            $stmt->execute([$name, $vibe, $address, $phone, $stars, $description, $id]);

            // 2. Cập nhật giá phòng (capacity 2 và 4)
            $stmt_room = $pdo->prepare(
                "INSERT INTO rooms (hotel_id, capacity, price) VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE price = VALUES(price)"
            );
            $stmt_room->execute([$id, 2, $price_2]);
            $stmt_room->execute([$id, 4, $price_4]);

            // 3. Cập nhật bảng hotel_amenities
            // Xóa toàn bộ tiện ích cũ của khách sạn này
            $stmt_del_amenities = $pdo->prepare("DELETE FROM hotel_amenities WHERE hotel_id = ?");
            $stmt_del_amenities->execute([$id]);
            // Thêm lại các tiện ích mới được chọn
            if (!empty($selected_amenities)) {
                $stmt_add_amenities = $pdo->prepare("INSERT INTO hotel_amenities (hotel_id, amenity_id) VALUES (?, ?)");
                foreach ($selected_amenities as $amenity_id) {
                    $stmt_add_amenities->execute([$id, (int)$amenity_id]);
                }
            }

            if (isset($_FILES['images'])) {
                $savedImages = store_uploaded_images($_FILES['images'], "hotel_{$id}", 10);
                $stmt_img = $pdo->prepare('INSERT INTO hotel_images (hotel_id, image_url, is_primary) VALUES (?, ?, 0)');
                foreach ($savedImages as $imagePath) {
                    $stmt_img->execute([$id, $imagePath]);
                }
            }

            $pdo->commit();

            $_SESSION['flash_success'] = 'Cập nhật thành công!';
            redirect("edit_hotel.php?id={$id}");
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            foreach ($savedImages as $imagePath) {
                delete_upload_file($imagePath);
            }
            error_log('Hotel update failed: ' . $e->getMessage());
            $msg = $e instanceof RuntimeException ? $e->getMessage() : 'Không thể cập nhật khách sạn lúc này.';
            $msg_type = "error";
        }
    }
}

if (isset($_SESSION['flash_success'])) {
    $msg = (string) $_SESSION['flash_success'];
    $msg_type = "success";
    unset($_SESSION['flash_success']);
}

// Lấy dữ liệu hiện tại từ Database để đưa vào form
$stmt = $pdo->prepare("SELECT * FROM hotels WHERE id = ?");
$stmt->execute([$id]);
$hotel = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$hotel) {
    die("Không tìm thấy khách sạn.");
}

$stmt_room = $pdo->prepare(
    "SELECT room.capacity, room.price
     FROM rooms room
     INNER JOIN (
         SELECT capacity, MAX(id) AS latest_id
         FROM rooms
         WHERE hotel_id = ?
         GROUP BY capacity
     ) latest ON latest.latest_id = room.id
     WHERE room.hotel_id = ?"
);
$stmt_room->execute([$id, $id]);
$rooms = $stmt_room->fetchAll(PDO::FETCH_KEY_PAIR);

$price_2 = $rooms[2] ?? 0;
$price_4 = $rooms[4] ?? 0;

$stmt_img = $pdo->prepare("SELECT image_url FROM hotel_images WHERE hotel_id = ?");
$stmt_img->execute([$id]);
$images = $stmt_img->fetchAll(PDO::FETCH_COLUMN);

// Lấy danh sách ID các tiện ích khách sạn đang có để tick sẵn
$stmt_current_amenities = $pdo->prepare("SELECT amenity_id FROM hotel_amenities WHERE hotel_id = ?");
$stmt_current_amenities->execute([$id]);
$current_amenities = $stmt_current_amenities->fetchAll(PDO::FETCH_COLUMN);

if (is_post_request() && $msg_type === 'error') {
    $hotel = array_merge($hotel, [
        'name' => $name,
        'address' => $address,
        'phone' => $phone,
        'star_rating' => $stars === false ? $hotel['star_rating'] : $stars,
        'vibe' => $vibe,
        'description' => $description,
    ]);
    $current_amenities = $selected_amenities;
}

require_once 'includes/header.php';
?>
<div class="container hotel-admin-form">
    <h2 class="hotel-admin-form__title">Sửa Khách Sạn: <?= htmlspecialchars($hotel['name']) ?></h2>

    <?php if (!empty($msg)): ?>
        <div class="hotel-admin-form__notice <?= $msg_type === 'success' ? 'hotel-admin-form__notice--success' : 'hotel-admin-form__notice--error' ?>">
            <?= htmlspecialchars($msg) ?>
        </div>
        <?php if ($msg_type === 'success'): ?>
            <div class="hotel-admin-form__notice-actions">
                <a href="admin.php" class="btn-primary hotel-admin-form__link">Về trang quản trị</a>
                <a href="index.php" class="btn-outline hotel-admin-form__link">Về trang chủ</a>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <form action="" method="POST" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="form-group">
            <label>Tên khách sạn:</label>
            <input type="text" name="name" class="form-control" maxlength="150" value="<?= e($hotel['name']) ?>" required>
        </div>

        <div class="form-group">
            <label>Địa chỉ:</label>
            <input type="text" name="address" class="form-control" maxlength="255" value="<?= e($hotel['address'] ?? '') ?>" required>
        </div>

        <div class="hotel-admin-form__two-columns">
            <div class="form-group">
                <label>Số điện thoại:</label>
                <input type="tel" name="phone" class="form-control" maxlength="25" value="<?= e($hotel['phone'] ?? '') ?>" placeholder="Ví dụ: 0901234567">
            </div>
            
            <div class="form-group">
                <label>Đánh giá:</label>
                <input type="number" name="stars" class="form-control" min="1" max="5" step="0.1" value="<?= e($hotel['star_rating'] ?? 1) ?>" required>
            </div>
        </div>

        <div class="hotel-admin-form__two-columns">
            <div class="form-group">
                <label>Giá phòng 2 người (VNĐ):</label>
                <input type="number" name="price_2" class="form-control" value="<?= $price_2 ?>" min="1" required>
            </div>

            <div class="form-group">
                <label>Giá phòng 4 người (VNĐ):</label>
                <input type="number" name="price_4" class="form-control" value="<?= $price_4 ?>" min="1" required>
            </div>
        </div>

        <div class="form-group">
            <label>Phong cách (Vibe):</label>
            <input type="text" name="vibe" class="form-control" value="<?= htmlspecialchars($hotel['vibe']) ?>" required>
        </div>

        <div class="form-group">
            <label>Mô tả khách sạn:</label>
            <textarea name="description" class="form-control" rows="4"><?= htmlspecialchars($hotel['description'] ?? '') ?></textarea>
        </div>

        <!-- Khu vực chọn tiện ích thông minh (Đã tick sẵn các dịch vụ cũ) -->
        <div class="form-group">
            <label>Tiện nghi và dịch vụ (Tick chọn):</label>
            <div class="amenities-grid">
                <?php if ($amenitiesList): ?>
                    <?php foreach ($amenitiesList as $amenity): ?>
                        <?php $isChecked = in_array($amenity['id'], $current_amenities) ? 'checked' : ''; ?>
                        <label class="amenity-checkbox">
                            <input type="checkbox" name="amenities[]" value="<?= (int)$amenity['id'] ?>" <?= $isChecked ?>>
                            <?= htmlspecialchars($amenity['name']) ?>
                        </label>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="hotel-admin-form__empty">Chưa có tiện ích nào trong cơ sở dữ liệu.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="form-group">
            <label>Ảnh hiện có:</label>
            <div class="hotel-admin-form__existing-images">
                <?php if (empty($images)): ?>
                    <p class="hotel-admin-form__image-empty">Chưa có ảnh nào.</p>
                <?php else: ?>
                    <?php foreach ($images as $img): ?>
                        <img src="<?= htmlspecialchars($img) ?>" width="100" height="100" class="hotel-admin-form__image" alt="Ảnh khách sạn hiện có">
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="form-group">
            <label>Thêm ảnh mới (không xóa ảnh cũ):</label>
            <input type="file" name="images[]" multiple accept="image/*" class="form-control">
        </div>

        <div class="hotel-admin-form__footer-actions">
            <button type="submit" class="btn-primary">Lưu thay đổi</button>
            <a href="admin.php" class="btn-outline hotel-admin-form__cancel">Hủy</a>
        </div>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>
