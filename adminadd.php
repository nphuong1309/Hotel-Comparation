<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}
require_once 'includes/db-connect.php';
require_once 'includes/header.php';

$msg = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $price_2 = (int)$_POST['price_2'];
    $price_4 = (int)$_POST['price_4'];
    $vibe = $_POST['vibe'];

    // Form Validation (Backend)
    if (empty($name)) {
        $msg = "Tên không được bỏ trống!";
    } elseif ($price_2 <= 0 || $price_4 <= 0) {
        $msg = "Giá phòng phải là số dương!";
    } else {
        try {
            $pdo->beginTransaction(); // Bắt đầu Transaction

            // Lưu bảng hotels
            $stmt = $pdo->prepare("INSERT INTO hotels (name, vibe) VALUES (?, ?)");
            $stmt->execute([$name, $vibe]);
            $hotel_id = $pdo->lastInsertId();

            // Lưu bảng rooms
            $stmt_room = $pdo->prepare("INSERT INTO rooms (hotel_id, capacity, price) VALUES (?, ?, ?)");
            $stmt_room->execute([$hotel_id, 2, $price_2]);
            $stmt_room->execute([$hotel_id, 4, $price_4]);

            // ... (code INSERT bảng hotels và rooms giữ nguyên) ...

            // Xử lý File Upload và lưu vào hotel_images
            if (!empty($_FILES['images']['name'][0])) {
                $upload_dir = 'uploads/';
                $is_first = true;

                foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                    // Lấy đuôi file (jpg, png...)
                    $ext = strtolower(pathinfo($_FILES['images']['name'][$key], PATHINFO_EXTENSION));

                    // Ảnh đầu tiên (key == 0) sẽ làm ảnh bìa
                    if ($key == 0) {
                        $file_name = "hotel_" . $hotel_id . "_primary." . $ext;
                    } else {
                        $file_name = "hotel_" . $hotel_id . "_" . $key . "." . $ext;
                    }

                    $target = $upload_dir . $file_name;
                    if (move_uploaded_file($tmp_name, $target)) {
                        // Lưu vào bảng hotel_images
                        $is_primary = ($key == 0) ? 1 : 0;
                        $stmt_img = $pdo->prepare("INSERT INTO hotel_images (hotel_id, image_url, is_primary) VALUES (?, ?, ?)");
                        $stmt_img->execute([$hotel_id, $target, $is_primary]);
                    }
                }
            }

            $pdo->commit();
            $msg = "Thêm thành công!";
        } catch (Exception $e) {
            $pdo->rollBack();
            $msg = "Lỗi: " . $e->getMessage();
        }
    }
}
?>

<h2>Thêm Khách Sạn Mới</h2>
<p><?= $msg ?></p>
<form action="" method="POST" enctype="multipart/form-data" onsubmit="return validateAdminForm()">
    <div class="form-group">
        <label>Tên khách sạn:</label>
        <input type="text" name="name" id="adminName" required>
    </div>
    <div class="form-group">
        <label>Giá phòng 2 người:</label>
        <input type="number" name="price_2" id="adminPrice2" required>
    </div>
    <div class="form-group">
        <label>Giá phòng 4 người:</label>
        <input type="number" name="price_4" id="adminPrice4" required>
    </div>
    <div class="form-group">
        <label>Phong cách:</label>
        <input type="text" name="vibe" required>
    </div>
    <div class="form-group">
        <label>Chọn nhiều ảnh:</label>
        <input type="file" name="images[]" multiple accept="image/*">
    </div>
    <button type="submit" class="btn-primary">Lưu dữ liệu</button>
</form>

<?php require_once 'includes/footer.php'; ?>