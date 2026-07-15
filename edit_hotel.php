<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}
require_once 'includes/db-connect.php';
require_once 'includes/header.php';

if (!isset($_GET['id'])) {
    die("Thiếu ID khách sạn.");
}
$id = (int)$_GET['id'];

$msg = '';
$msg_type = '';

// Xử lý khi bấm Lưu
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name        = trim($_POST['name']);
    $vibe        = trim($_POST['vibe']);
    $price_2     = (int)$_POST['price_2'];
    $price_4     = (int)$_POST['price_4'];

    // Các trường bổ sung mới
    $address     = trim($_POST['address']);
    $phone       = trim($_POST['phone']);
    $stars       = (int)$_POST['stars'];
    $description = trim($_POST['description']);
    $facilities  = trim($_POST['facilities']); // Ví dụ: Wifi, Hồ bơi, Bãi đỗ xe...

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

            // 1. Cập nhật bảng hotels (đã bổ sung address, phone, stars, description, facilities)
            $sql_hotel = "UPDATE hotels SET 
                            name = ?, 
                            vibe = ?, 
                            address = ?, 
                            phone = ?, 
                            stars = ?, 
                            description = ?, 
                            facilities = ? 
                          WHERE id = ?";
            $stmt = $pdo->prepare($sql_hotel);
            $stmt->execute([$name, $vibe, $address, $phone, $stars, $description, $facilities, $id]);

            // 2. Cập nhật giá phòng (capacity 2 và 4)
            $stmt_room = $pdo->prepare("UPDATE rooms SET price = ? WHERE hotel_id = ? AND capacity = ?");
            $stmt_room->execute([$price_2, $id, 2]);
            $stmt_room->execute([$price_4, $id, 4]);

            // 3. Thêm ảnh mới nếu có upload (giữ bảo mật cao)
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

                        // Kiểm tra định dạng đuôi file
                        if (!in_array($file_ext, $allowed_extensions)) {
                            throw new Exception("Chỉ chấp nhận các định dạng ảnh: " . implode(', ', $allowed_extensions));
                        }

                        // Kiểm tra MIME thực tế của file
                        $finfo = finfo_open(FILEINFO_MIME_TYPE);
                        $mime_type = finfo_file($finfo, $tmp_name);
                        finfo_close($finfo);
                        if (strpos($mime_type, 'image/') !== 0) {
                            throw new Exception("File tải lên không phải là ảnh hợp lệ!");
                        }

                        // Tạo tên file ngẫu nhiên an toàn
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
            header("Location: edit-hotel.php?id={$id}&status=success");
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

// Lấy dữ liệu hiện tại từ Database
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
?>

<style>
    /* CSS cơ bản giúp form hiển thị gọn gàng hơn */
    .form-group {
        margin-bottom: 15px;
    }

    .form-group label {
        display: block;
        font-weight: bold;
        margin-bottom: 5px;
    }

    .form-control {
        width: 100%;
        padding: 8px;
        box-sizing: border-box;
        border: 1px solid #ccc;
        border-radius: 4px;
    }

    textarea.form-control {
        resize: vertical;
        min-height: 80px;
    }
</style>

<h2>Sửa Khách Sạn: <?= htmlspecialchars($hotel['name']) ?></h2>

<?php if (!empty($msg)): ?>
    <div class="alert alert-<?= $msg_type ?>" style="padding: 10px; margin-bottom: 15px; border-radius: 5px; background: <?= $msg_type === 'success' ? '#d4edda' : '#f8d7da' ?>; color: <?= $msg_type === 'success' ? '#155724' : '#721c24' ?>;">
        <?= htmlspecialchars($msg) ?>
    </div>
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

    <div class="form-group">
        <label>Số điện thoại:</label>
        <input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($hotel['phone'] ?? '') ?>">
    </div>

    <div class="form-group">
        <label>Giá phòng 2 người:</label>
        <input type="number" name="price_2" class="form-control" value="<?= $price_2 ?>" min="1" required>
    </div>

    <div class="form-group">
        <label>Giá phòng 4 người:</label>
        <input type="number" name="price_4" class="form-control" value="<?= $price_4 ?>" min="1" required>
    </div>

    <div class="form-group">
        <label>Phong cách:</label>
        <input type="text" name="vibe" class="form-control" value="<?= htmlspecialchars($hotel['vibe']) ?>" required>
    </div>

    <div class="form-group">
        <label>Số sao:</label>
        <select name="stars" class="form-control" required>
            <?php
            $current_stars = (int)($hotel['stars'] ?? 1);
            for ($i = 1; $i <= 5; $i++):
            ?>
                <option value="<?= $i ?>" <?= $current_stars === $i ? 'selected' : '' ?>><?= $i ?> Sao</option>
            <?php endfor; ?>
        </select>
    </div>

    <div class="form-group">
        <label>Mô tả khách sạn:</label>
        <textarea name="description" class="form-control" rows="4"><?= htmlspecialchars($hotel['description'] ?? '') ?></textarea>
    </div>

    <div class="form-group">
        <label>Tiện nghi và dịch vụ (Cách nhau bằng dấu phẩy, VD: Wifi, Hồ bơi, Nhà hàng...):</label>
        <textarea name="facilities" class="form-control" rows="3" placeholder="Wifi, Hồ bơi, Buffet sáng, Chỗ đỗ xe..."><?= htmlspecialchars($hotel['facilities'] ?? '') ?></textarea>
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
        <input type="file" name="images[]" multiple accept="image/*">
    </div>

    <button type="submit" class="btn-primary">Lưu thay đổi</button>
    <a href="admin.php" class="btn-outline">Hủy</a>
</form>

<?php require_once 'includes/footer.php'; ?>