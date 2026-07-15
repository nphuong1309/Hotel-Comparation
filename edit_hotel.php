<?php
session_start();
// Mở khóa dòng check quyền nếu cần thiết
// if(!isset($_SESSION['admin'])) { header("Location: login.php"); exit; }
require_once 'includes/db-connect.php';

if (!isset($_GET['id'])) {
    die("Thiếu ID khách sạn.");
}
$id = (int)$_GET['id'];

$msg = '';
$msg_type = '';

// Lấy danh sách tiện ích trực tiếp từ database để làm checkbox thông minh
$amenityStmt = $pdo->query('SELECT id, name FROM amenities ORDER BY id ASC');
$amenitiesList = $amenityStmt->fetchAll(PDO::FETCH_ASSOC);

// Xử lý khi bấm Lưu
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name        = trim($_POST['name']);
    $vibe        = trim($_POST['vibe']);
    $price_2     = (int)$_POST['price_2'];
    $price_4     = (int)$_POST['price_4'];
    $address     = trim($_POST['address']);
    $phone       = trim($_POST['phone']);
    $stars       = (float)$_POST['stars'];
    $description = trim($_POST['description']);
    
    // Mảng chứa các ID tiện ích được Admin tick chọn
    $selected_amenities = isset($_POST['amenities']) ? $_POST['amenities'] : [];

    if (empty($name)) {
        $msg = "Tên không được bỏ trống!";
        $msg_type = "error";
    } elseif (empty($address)) {
        $msg = "Địa chỉ không được bỏ trống!";
        $msg_type = "error";
    } elseif ($price_2 <= 0 || $price_4 <= 0) {
        $msg = "Giá phòng phải là số dương!";
        $msg_type = "error";
    } elseif ($stars < 1 || $stars > 5) {
        $msg = "Số sao không hợp lệ (chỉ từ 1 đến 5 sao)!";
        $msg_type = "error";
    } else {
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
            $stmt_room = $pdo->prepare("UPDATE rooms SET price = ? WHERE hotel_id = ? AND capacity = ?");
            $stmt_room->execute([$price_2, $id, 2]);
            $stmt_room->execute([$price_4, $id, 4]);

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

            // 4. Thêm ảnh mới nếu có upload
            if (isset($_FILES['images']) && !empty($_FILES['images']['tmp_name'][0])) {
                $upload_dir = 'uploads/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }

                $stmt_img = $pdo->prepare("INSERT INTO hotel_images (hotel_id, image_url, is_primary) VALUES (?, ?, 0)");
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

                foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                    if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                        $file_name = $_FILES['images']['name'][$key];
                        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

                        if (!in_array($file_ext, $allowed_extensions)) {
                            throw new Exception("Chỉ chấp nhận các định dạng ảnh: " . implode(', ', $allowed_extensions));
                        }

                        $finfo = finfo_open(FILEINFO_MIME_TYPE);
                        $mime_type = finfo_file($finfo, $tmp_name);
                        finfo_close($finfo);
                        if (strpos($mime_type, 'image/') !== 0) {
                            throw new Exception("File tải lên không phải là ảnh hợp lệ!");
                        }

                        // Sử dụng logic tạo tên file an toàn của bạn
                        $new_file_name = time() . '_' . bin2hex(random_bytes(4)) . '.' . $file_ext;
                        $target = $upload_dir . $new_file_name;

                        if (move_uploaded_file($tmp_name, $target)) {
                            $stmt_img->execute([$id, $target]);
                        }
                    }
                }
            }

            $pdo->commit();

            // Redirect để tránh resubmit form khi F5
            header("Location: edit_hotel.php?id={$id}&status=success");
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $msg = "Lỗi: " . $e->getMessage();
            $msg_type = "error";
        }
    }
}

// Kiểm tra trạng thái thành công sau Redirect
if (isset($_GET['status']) && $_GET['status'] === 'success') {
    $msg = "Cập nhật thành công!";
    $msg_type = "success";
}

// Lấy dữ liệu hiện tại từ Database để đưa vào form
$stmt = $pdo->prepare("SELECT * FROM hotels WHERE id = ?");
$stmt->execute([$id]);
$hotel = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$hotel) {
    die("Không tìm thấy khách sạn.");
}

$stmt_room = $pdo->prepare("SELECT capacity, price FROM rooms WHERE hotel_id = ?");
$stmt_room->execute([$id]);
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

require_once 'includes/header.php';
?>

<style>
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; font-weight: bold; margin-bottom: 5px; }
    .form-control { width: 100%; padding: 8px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px; }
    textarea.form-control { resize: vertical; min-height: 80px; }
    
    /* CSS cho khu vực chọn tiện ích */
    .amenities-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 10px;
        background: #fff;
        padding: 15px;
        border: 1px solid #ccc;
        border-radius: 4px;
        max-height: 250px;
        overflow-y: auto;
    }
    .amenity-checkbox {
        display: flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        font-weight: normal !important;
    }
    .amenity-checkbox input {
        accent-color: #df6040;
        width: 16px;
        height: 16px;
    }
</style>

<div class="container" style="max-width: 800px; margin: 30px auto; background: #fffdf8; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
    <h2 style="color: var(--primary); margin-top: 0;">Sửa Khách Sạn: <?= htmlspecialchars($hotel['name']) ?></h2>

    <?php if (!empty($msg)): ?>
        <div style="margin: 15px 0; padding: 12px 15px; border-radius: 6px; <?= $msg_type === 'success' ? 'background:#e8f7ee; color:#1f6b3b; border:1px solid #b7e2c7;' : 'background:#fdecec; color:#a11f1f; border:1px solid #f2bbbb;' ?>">
            <?= htmlspecialchars($msg) ?>
        </div>
        <?php if ($msg_type === 'success'): ?>
            <div style="margin-bottom: 18px; display:flex; gap:10px; flex-wrap:wrap;">
                <a href="admin.php" class="btn-primary" style="text-decoration:none;">Về trang quản trị</a>
                <a href="index.php" class="btn-outline" style="text-decoration:none;">Về trang chủ</a>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <form action="" method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label>Tên khách sạn:</label>
            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($hotel['name']) ?>" required>
        </div>

        <div class="form-group">
            <label>Địa chỉ:</label>
            <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($hotel['address'] ?? '') ?>" required>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
            <div class="form-group">
                <label>Số điện thoại:</label>
                <input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($hotel['phone'] ?? '') ?>" placeholder="Ví dụ: 0901234567">
            </div>
            
            <div class="form-group">
                <label>Số sao:</label>
                <select name="stars" class="form-control" required>
                    <?php
                    $current_stars = (int)($hotel['star_rating'] ?? 1);
                    for ($i = 1; $i <= 5; $i++):
                    ?>
                        <option value="<?= $i ?>" <?= $current_stars === $i ? 'selected' : '' ?>><?= $i ?> Sao</option>
                    <?php endfor; ?>
                </select>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
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
                    <p style="color: #777; font-size: 14px;">Chưa có tiện ích nào trong cơ sở dữ liệu.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="form-group">
            <label>Ảnh hiện có:</label>
            <div style="display:flex; gap:10px; flex-wrap:wrap; margin-top:5px;">
                <?php if (empty($images)): ?>
                    <p style="color: #666; font-style: italic;">Chưa có ảnh nào.</p>
                <?php else: ?>
                    <?php foreach ($images as $img): ?>
                        <img src="<?= htmlspecialchars($img) ?>" width="100" height="100" style="object-fit:cover; border-radius:5px; border: 1px solid #ddd;">
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="form-group">
            <label>Thêm ảnh mới (không xóa ảnh cũ):</label>
            <input type="file" name="images[]" multiple accept="image/*" class="form-control">
        </div>

        <div style="margin-top: 25px;">
            <button type="submit" class="btn-primary">Lưu thay đổi</button>
            <a href="admin.php" class="btn-outline" style="text-decoration:none; display:inline-block; margin-left:10px;">Hủy</a>
        </div>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>