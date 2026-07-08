<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header("Location: login.php"); exit; }
require_once 'includes/db-connect.php';
require_once 'includes/header.php';

// Xử lý Xóa khách sạn
if (isset($_GET['delete'])) {
    $del_id = $_GET['delete'];
    
    // 1. Quét và xóa toàn bộ ảnh vật lý của khách sạn này trong thư mục uploads/
    $images_to_delete = glob("uploads/hotel_" . $del_id . "_*.*");
    foreach($images_to_delete as $img_file) {
        if(is_file($img_file)) {
            unlink($img_file);
        }
    }

    // 2. Xóa dữ liệu trong Database
    $stmt = $pdo->prepare("DELETE FROM hotels WHERE id = ?");
    $stmt->execute([$del_id]);
    echo "<script>alert('Đã xóa khách sạn và dọn dẹp thư mục ảnh thành công!'); window.location.href='admin.php';</script>";
}

// Lấy danh sách
$stmt = $pdo->query("SELECT * FROM hotels ORDER BY id DESC");
$hotels = $stmt->fetchAll();
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin: 20px 0;">
    <h2>Bảng Điều Khiển Admin (Quản lý Khách sạn)</h2>
    <a href="admin_add.php" class="btn-primary">+ Thêm Khách Sạn Mới</a>
</div>

<table class="compare-table">
    <tr>
        <th>ID</th>
        <th>Tên Khách Sạn</th>
        <th>Vị Trí</th>
        <th>Vibe</th>
        <th>Hành động</th>
    </tr>
    <?php foreach($hotels as $h): ?>
    <tr>
        <td><?= $h['id'] ?></td>
        <td><b><?= htmlspecialchars($h['name']) ?></b></td>
        <td><?= htmlspecialchars($h['address']) ?></td>
        <td><?= htmlspecialchars($h['vibe']) ?></td>
        <td>
            <!-- Nút sửa (có thể phát triển sau) và nút Xóa -->
            <a href="#" class="btn-outline">Sửa</a>
            <a href="?delete=<?= $h['id'] ?>" class="btn-outline" style="color:red; border-color:red;" onclick="return confirm('Bạn có chắc chắn muốn xóa khách sạn này?');">Xóa</a>
        </td>
    </tr>
    <?php endforeach; ?>
</table>

<?php require_once 'includes/footer.php'; ?>