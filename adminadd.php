<?php
session_start();
// Mở khóa dòng check quyền nếu cần thiết
// if(!isset($_SESSION['admin'])) { header("Location: login.php"); exit; }
require_once 'includes/db-connect.php';

$msg = '';
$msgType = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name        = trim($_POST['name']);
    $price_2     = (int)$_POST['price_2'];
    $price_4     = (int)$_POST['price_4'];
    $vibe        = trim($_POST['vibe']);

    // Các trường mới được đồng bộ hóa
    $address     = trim($_POST['address']);
    $phone       = trim($_POST['phone']);
    $stars       = (int)$_POST['stars'];
    $description = trim($_POST['description']);
    $facilities  = trim($_POST['facilities']);

    // Form Validation (Backend)
    if (empty($name)) {
        $msg = "Tên không được bỏ trống!";
        $msgType = 'error';
    } elseif (empty($address)) {
        $msg = "Địa chỉ không được bỏ trống!";
        $msgType = 'error';
    } elseif ($price_2 <= 0 || $price_4 <= 0) {
        $msg = "Giá phòng phải là số dương!";
        $msgType = 'error';
    } elseif ($stars < 1 || $stars > 5) {
        $msg = "Số sao không hợp lệ (chỉ từ 1 đến 5 sao)!";
        $msgType = 'error';
    } else {
        try {
            $pdo->beginTransaction(); // Bắt đầu Transaction

            // 1. Lưu bảng hotels (Đầy đủ các trường mới đồng bộ)
            $sql_hotel = "INSERT INTO hotels (name, vibe, address, phone, stars, description, facilities) 
                          VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql_hotel);
            $stmt->execute([$name, $vibe, $address, $phone, $stars, $description, $facilities]);
            $hotel_id = $pdo->lastInsertId();

            // 2. Lưu bảng rooms
            $stmt_room = $pdo->prepare("INSERT INTO rooms (hotel_id, capacity, price) VALUES (?, ?, ?)");
            $stmt_room->execute([$hotel_id, 2, $price_2]);
            $stmt_room->execute([$hotel_id, 4, $price_4]);

            // 3. Xử lý File Upload có kiểm duyệt Bảo Mật Cao
            if (isset($_FILES['images']) && !empty($_FILES['images']['tmp_name'][0])) {
                $upload_dir = 'uploads/';

                // Tạo thư mục uploads nếu chưa tồn tại
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }

                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

                foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                    if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                        $original_name = $_FILES['images']['name'][$key];
                        $ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));

                        // Kiểm tra định dạng đuôi file hợp lệ
                        if (!in_array($ext, $allowed_extensions)) {
                            throw new Exception("Chỉ chấp nhận các định dạng ảnh: " . implode(', ', $allowed_extensions));
                        }

                        // Kiểm tra mã nhị phân MIME thực tế để phòng ngừa mã độc giả dạng ảnh
                        $finfo = finfo_open(FILEINFO_MIME_TYPE);
                        $mime_type = finfo_file($finfo, $tmp_name);
                        finfo_close($finfo);
                        if (strpos($mime_type, 'image/') !== 0) {
                            throw new Exception("File tải lên không phải là ảnh hợp lệ!");
                        }

                        // Đặt tên file theo cấu trúc cũ của bạn
                        if ($key == 0) {
                            $file_name = "hotel_" . $hotel_id . "_primary." . $ext;
                        } else {
                            $file_name = "hotel_" . $hotel_id . "_" . $key . "." . $ext;
                        }

                        $target = $upload_dir . $file_name;
                        move_uploaded_file($tmp_name, $target);
                    }
                }
            }

            $pdo->commit();
            $_SESSION['flash_success'] = "Thêm khách sạn thành công!";
            header("Location: admin.php");
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $msg = "Lỗi: " . $e->getMessage();
            $msgType = 'error';
        }
    }
}

if (isset($_SESSION['flash_success'])) {
    $msg = $_SESSION['flash_success'];
    $msgType = 'success';
    unset($_SESSION['flash_success']);
}

require_once 'includes/header.php';
?>

<style>
    /* Đồng bộ CSS cơ bản để quản lý form gọn gàng */
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

<h2>Thêm Khách Sạn Mới</h2>

<?php if ($msg): ?>
    <div style="margin: 15px 0; padding: 12px 15px; border-radius: 6px; <?= $msgType === 'success' ? 'background:#e8f7ee; color:#1f6b3b; border:1px solid #b7e2c7;' : 'background:#fdecec; color:#a11f1f; border:1px solid #f2bbbb;' ?>">
        <?= htmlspecialchars($msg) ?>
    </div>
    <?php if ($msgType === 'success'): ?>
        <div style="margin-bottom: 18px; display:flex; gap:10px; flex-wrap:wrap;">
            <a href="admin.php" class="btn-primary" style="text-decoration:none; display:inline-block;">Về trang quản trị</a>
            <a href="index.php" class="btn-outline" style="text-decoration:none; display:inline-block;">Về trang chủ</a>
        </div>
    <?php endif; ?>
<?php endif; ?>

<form action="" method="POST" enctype="multipart/form-data" onsubmit="return validateAdminForm()">

    <div class="form-group">
        <label>Tên khách sạn:</label>
        <input type="text" name="name" id="adminName" class="form-control" required>
    </div>

    <div class="form-group">
        <label>Địa chỉ:</label>
        <input type="text" name="address" class="form-control" placeholder="Số nhà, Tên đường, Quận/Huyện..." required>
    </div>

    <div class="form-group">
        <label>Số điện thoại:</label>
        <input type="tel" name="phone" class="form-control" placeholder="Ví dụ: 0901234567">
    </div>

    <div class="form-group">
        <label>Giá phòng 2 người:</label>
        <input type="number" name="price_2" id="adminPrice2" class="form-control" min="1" required>
    </div>

    <div class="form-group">
        <label>Giá phòng 4 người:</label>
        <input type="number" name="price_4" id="adminPrice4" class="form-control" min="1" required>
    </div>

    <div class="form-group">
        <label>Phong cách (Vibe):</label>
        <input type="text" name="vibe" class="form-control" placeholder="Ví dụ: Hiện đại, Cổ điển, Vintage..." required>
    </div>

    <div class="form-group">
        <label>Số sao:</label>
        <select name="stars" class="form-control" required>
            <option value="1">1 Sao</option>
            <option value="2">2 Sao</option>
            <option value="3">3 Sao</option>
            <option value="4" selected>4 Sao</option>
            <option value="5">5 Sao</option>
        </select>
    </div>

    <div class="form-group">
        <label>Mô tả khách sạn:</label>
        <textarea name="description" class="form-control" rows="4" placeholder="Nhập mô tả chi tiết giới thiệu về khách sạn..."></textarea>
    </div>

    <div class="form-group">
        <label>Tiện nghi và dịch vụ (Cách nhau bằng dấu phẩy):</label>
        <textarea name="facilities" class="form-control" rows="3" placeholder="Ví dụ: Wifi miễn phí, Hồ bơi vô cực, Bãi đỗ xe ô tô, Buffet sáng..."></textarea>
    </div>

    <div class="form-group">
        <label>Chọn nhiều ảnh:</label>
        <input type="file" name="images[]" multiple accept="image/*" class="form-control">
    </div>

    <button type="submit" class="btn-primary">Lưu dữ liệu</button>
    <a href="admin.php" class="btn-outline" style="text-decoration:none; display:inline-block; margin-left:10px;">Hủy</a>
</form>

<?php require_once 'includes/footer.php'; ?>