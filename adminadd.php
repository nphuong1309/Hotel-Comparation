<?php
session_start();
// Mở khóa dòng check quyền nếu cần thiết
// if(!isset($_SESSION['admin'])) { header("Location: login.php"); exit; }
require_once 'includes/db-connect.php';

$msg = '';
$msgType = '';

// Lấy danh sách tiện ích trực tiếp từ database để làm checkbox thông minh
$amenityStmt = $pdo->query('SELECT id, name FROM amenities ORDER BY id ASC');
$amenitiesList = $amenityStmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name        = trim($_POST['name']);
    $price_2     = (int)$_POST['price_2'];
    $price_4     = (int)$_POST['price_4'];
    $vibe        = trim($_POST['vibe']);
    $address     = trim($_POST['address']);
    $phone       = trim($_POST['phone']);
    $stars       = (float)$_POST['stars'];
    $description = trim($_POST['description']);
    
    // Mảng chứa các ID tiện ích được Admin tick chọn
    $selected_amenities = isset($_POST['amenities']) ? $_POST['amenities'] : [];

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

            // 1. Lưu bảng hotels (Đã sửa 'stars' thành 'star_rating' và xóa 'facilities')
            $sql_hotel = "INSERT INTO hotels (name, vibe, address, phone, star_rating, description) 
                          VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql_hotel);
            $stmt->execute([$name, $vibe, $address, $phone, $stars, $description]);
            $hotel_id = $pdo->lastInsertId();

            // 2. Lưu bảng rooms
            $stmt_room = $pdo->prepare("INSERT INTO rooms (hotel_id, capacity, price) VALUES (?, ?, ?)");
            $stmt_room->execute([$hotel_id, 2, $price_2]);
            $stmt_room->execute([$hotel_id, 4, $price_4]);
            
            // 3. Lưu bảng hotel_amenities (Xử lý tiện ích thông minh)
            if (!empty($selected_amenities)) {
                $stmt_amenity = $pdo->prepare("INSERT INTO hotel_amenities (hotel_id, amenity_id) VALUES (?, ?)");
                foreach ($selected_amenities as $amenity_id) {
                    $stmt_amenity->execute([$hotel_id, (int)$amenity_id]);
                }
            }

            // 4. Xử lý File Upload có kiểm duyệt Bảo Mật Cao
            if (isset($_FILES['images']) && !empty($_FILES['images']['tmp_name'][0])) {
                $upload_dir = 'uploads/';

                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }

                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

                foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                    if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                        $original_name = $_FILES['images']['name'][$key];
                        $ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));

                        if (!in_array($ext, $allowed_extensions)) {
                            throw new Exception("Chỉ chấp nhận các định dạng ảnh: " . implode(', ', $allowed_extensions));
                        }

                        $finfo = finfo_open(FILEINFO_MIME_TYPE);
                        $mime_type = finfo_file($finfo, $tmp_name);
                        finfo_close($finfo);
                        if (strpos($mime_type, 'image/') !== 0) {
                            throw new Exception("File tải lên không phải là ảnh hợp lệ!");
                        }

                        if ($key == 0) {
                            $file_name = "hotel_" . $hotel_id . "_primary." . $ext;
                        } else {
                            $file_name = "hotel_" . $hotel_id . "_" . $key . "." . $ext;
                        }

                        $target = $upload_dir . $file_name;
                        
                        // Sau khi di chuyển ảnh thành công, lưu đường dẫn vào bảng hotel_images
                        if (move_uploaded_file($tmp_name, $target)) {
                            $is_primary = ($key == 0) ? 1 : 0;
                            $stmt_img = $pdo->prepare("INSERT INTO hotel_images (hotel_id, image_url, is_primary) VALUES (?, ?, ?)");
                            $stmt_img->execute([$hotel_id, $target, $is_primary]);
                        }
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
    <h2 style="color: var(--primary); margin-top: 0;">Thêm Khách Sạn Mới</h2>

    <?php if ($msg): ?>
        <div style="margin: 15px 0; padding: 12px 15px; border-radius: 6px; <?= $msgType === 'success' ? 'background:#e8f7ee; color:#1f6b3b; border:1px solid #b7e2c7;' : 'background:#fdecec; color:#a11f1f; border:1px solid #f2bbbb;' ?>">
            <?= htmlspecialchars($msg) ?>
        </div>
        <?php if ($msgType === 'success'): ?>
            <div style="margin-bottom: 18px; display:flex; gap:10px; flex-wrap:wrap;">
                <a href="admin.php" class="btn-primary" style="text-decoration:none;">Về trang quản trị</a>
                <a href="index.php" class="btn-outline" style="text-decoration:none;">Về trang chủ</a>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <form action="" method="POST" enctype="multipart/form-data">

        <div class="form-group">
            <label>Tên khách sạn:</label>
            <input type="text" name="name" class="form-control" required>
        </div>

        <div class="form-group">
            <label>Địa chỉ:</label>
            <input type="text" name="address" class="form-control" placeholder="Số nhà, Tên đường, Quận/Huyện..." required>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
            <div class="form-group">
                <label>Số điện thoại:</label>
                <input type="tel" name="phone" class="form-control" placeholder="Ví dụ: 0901234567">
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
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
            <div class="form-group">
                <label>Giá phòng 2 người (VNĐ):</label>
                <input type="number" name="price_2" class="form-control" min="1" required>
            </div>

            <div class="form-group">
                <label>Giá phòng 4 người (VNĐ):</label>
                <input type="number" name="price_4" class="form-control" min="1" required>
            </div>
        </div>

        <div class="form-group">
            <label>Phong cách (Vibe):</label>
            <input type="text" name="vibe" class="form-control" placeholder="Ví dụ: Hiện đại, Cổ điển, Vintage..." required>
        </div>

        <div class="form-group">
            <label>Mô tả khách sạn:</label>
            <textarea name="description" class="form-control" rows="4" placeholder="Nhập mô tả chi tiết giới thiệu về khách sạn..."></textarea>
        </div>

        <!-- Khu vực chọn tiện ích thông minh -->
        <div class="form-group">
            <label>Tiện nghi và dịch vụ (Tick chọn):</label>
            <div class="amenities-grid">
                <?php if ($amenitiesList): ?>
                    <?php foreach ($amenitiesList as $amenity): ?>
                        <label class="amenity-checkbox">
                            <input type="checkbox" name="amenities[]" value="<?= (int)$amenity['id'] ?>">
                            <?= htmlspecialchars($amenity['name']) ?>
                        </label>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color: #777; font-size: 14px;">Chưa có tiện ích nào trong cơ sở dữ liệu.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="form-group">
            <label>Chọn nhiều ảnh (Ảnh đầu tiên sẽ làm ảnh bìa):</label>
            <input type="file" name="images[]" multiple accept="image/*" class="form-control">
        </div>

        <div style="margin-top: 25px;">
            <button type="submit" class="btn-primary">Lưu dữ liệu</button>
            <a href="admin.php" class="btn-outline" style="text-decoration:none; display:inline-block; margin-left:10px;">Hủy</a>
        </div>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>