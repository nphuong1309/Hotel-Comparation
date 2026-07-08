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
$id = $_GET['id'];

$msg = '';

// Xử lý khi bấm Lưu
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name    = trim($_POST['name']);
    $vibe    = trim($_POST['vibe']);
    $price_2 = (int)$_POST['price_2'];
    $price_4 = (int)$_POST['price_4'];

    if (empty($name)) {
        $msg = "Tên không được bỏ trống!";
    } elseif ($price_2 <= 0 || $price_4 <= 0) {
        $msg = "Giá phòng phải là số dương!";
    } else {
        try {
            $pdo->beginTransaction();

            // Cập nhật bảng hotels
            $stmt = $pdo->prepare("UPDATE hotels SET name = ?, vibe = ? WHERE id = ?");
            $stmt->execute([$name, $vibe, $id]);

            // Cập nhật giá phòng (capacity 2 và 4)
            $stmt_room = $pdo->prepare("UPDATE rooms SET price = ? WHERE hotel_id = ? AND capacity = ?");
            $stmt_room->execute([$price_2, $id, 2]);
            $stmt_room->execute([$price_4, $id, 4]);

            // Thêm ảnh mới nếu có upload (không xóa ảnh cũ)
            if (!empty($_FILES['images']['name'][0])) {
                $upload_dir = 'uploads/';
                $stmt_img = $pdo->prepare("INSERT INTO hotel_images (hotel_id, image_url, is_primary) VALUES (?, ?, 0)");

                foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                    $file_name = time() . '_' . $_FILES['images']['name'][$key];
                    $target = $upload_dir . $file_name;

                    if (move_uploaded_file($tmp_name, $target)) {
                        $stmt_img->execute([$id, $target]);
                    }
                }
            }

            $pdo->commit();
            $msg = "Cập nhật thành công!";
        } catch (Exception $e) {
            $pdo->rollBack();
            $msg = "Lỗi: " . $e->getMessage();
        }
    }
}

// Lấy dữ liệu hiện tại để đổ vào form
$stmt = $pdo->prepare("SELECT * FROM hotels WHERE id = ?");
$stmt->execute([$id]);
$hotel = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$hotel) {
    die("Không tìm thấy khách sạn.");
}

$stmt_room = $pdo->prepare("SELECT capacity, price FROM rooms WHERE hotel_id = ?");
$stmt_room->execute([$id]);
$rooms = $stmt_room->fetchAll(PDO::FETCH_KEY_PAIR); // [capacity => price]

$price_2 = $rooms[2] ?? 0;
$price_4 = $rooms[4] ?? 0;

$stmt_img = $pdo->prepare("SELECT image_url FROM hotel_images WHERE hotel_id = ?");
$stmt_img->execute([$id]);
$images = $stmt_img->fetchAll(PDO::FETCH_COLUMN);
?>

<h2>Sửa Khách Sạn: <?= htmlspecialchars($hotel['name']) ?></h2>
<p><?= $msg ?></p>

<form action="" method="POST" enctype="multipart/form-data">
    <div class="form-group">
        <label>Tên khách sạn:</label>
        <input type="text" name="name" value="<?= htmlspecialchars($hotel['name']) ?>" required>
    </div>
    <div class="form-group">
        <label>Giá phòng 2 người:</label>
        <input type="number" name="price_2" value="<?= $price_2 ?>" required>
    </div>
    <div class="form-group">
        <label>Giá phòng 4 người:</label>
        <input type="number" name="price_4" value="<?= $price_4 ?>" required>
    </div>
    <div class="form-group">
        <label>Phong cách:</label>
        <input type="text" name="vibe" value="<?= htmlspecialchars($hotel['vibe']) ?>" required>
    </div>

    <div class="form-group">
        <label>Ảnh hiện có:</label>
        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <?php foreach ($images as $img): ?>
                <img src="<?= htmlspecialchars($img) ?>" width="100" style="border-radius:5px;">
            <?php endforeach; ?>
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