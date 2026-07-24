<?php
/** ADMIN.PHP: dashboard admin, hiển thị danh sách và xóa khách sạn an toàn. */
require_once 'includes/bootstrap.php';
require_admin();

if (isset($_SESSION['flash_success'])) {
    $flashSuccess = $_SESSION['flash_success'];
    unset($_SESSION['flash_success']);
}

// Xóa bằng POST để tránh bot, prefetch hoặc link ngoài vô tình kích hoạt thao tác.
if (is_post_request() && isset($_POST['delete_hotel'])) {
    require_csrf();
    $hotelId = positive_int($_POST['hotel_id'] ?? null);

    if ($hotelId === null) {
        $_SESSION['flash_error'] = 'ID khách sạn không hợp lệ.';
        redirect('admin.php');
    }

    $imageStmt = $pdo->prepare('SELECT image_url FROM hotel_images WHERE hotel_id = ?');
    $imageStmt->execute([$hotelId]);
    $imagePaths = $imageStmt->fetchAll(PDO::FETCH_COLUMN);

    try {
        $pdo->beginTransaction();
        $historyStmt = $pdo->prepare(
            "DELETE FROM comparison_history
             WHERE FIND_IN_SET(CAST(? AS CHAR), REPLACE(hotel_ids, ' ', '')) > 0"
        );
        $historyStmt->execute([$hotelId]);

        $stmt = $pdo->prepare('DELETE FROM hotels WHERE id = ?');
        $stmt->execute([$hotelId]);
        $pdo->commit();

        foreach (array_unique(array_merge(
            $imagePaths,
            glob("uploads/hotel_{$hotelId}_*.*") ?: []
        )) as $imagePath) {
            delete_upload_file($imagePath);
        }

        $_SESSION['flash_success'] = $stmt->rowCount() > 0
            ? 'Đã xóa khách sạn và các dữ liệu liên quan.'
            : 'Khách sạn không còn tồn tại.';
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Hotel deletion failed: ' . $exception->getMessage());
        $_SESSION['flash_error'] = 'Không thể xóa khách sạn lúc này.';
    }

    redirect('admin.php');
}

// Lấy danh sách
$stmt = $pdo->query("SELECT * FROM hotels ORDER BY id ASC");
$hotels = $stmt->fetchAll();

$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_error']);

require_once 'includes/header.php';
?>

<?php if (!empty($flashSuccess)): ?>
    <div class="alert alert-success admin-flash">
        <div class="admin-flash-message">
            <?= htmlspecialchars($flashSuccess) ?>
        </div>
        <div class="admin-flash-actions">
            <a href="index.php" class="btn-primary admin-button-link">Về trang chủ</a>
            <a href="adminadd.php" class="btn-outline admin-button-link">Thêm khách sạn mới</a>
        </div>
    </div>
<?php endif; ?>

<?php if (!empty($flashError)): ?>
    <div class="alert alert-error"><?= e($flashError) ?></div>
<?php endif; ?>

<div class="admin-page-heading">
    <h2>Bảng Điều Khiển Admin (Quản lý Khách sạn)</h2>
    <a href="adminadd.php" class="btn-primary">+ Thêm Khách Sạn Mới</a>
</div>

<table class="compare-table">
    <tr>
        <th>ID</th>
        <th>Tên Khách Sạn</th>
        <th>Vị Trí</th>
        <th>Vibe</th>
        <th>Hành động</th>
    </tr>
    <?php foreach ($hotels as $h): ?>
        <tr>
            <td><?= $h['id'] ?></td>
            <td><b><?= htmlspecialchars($h['name']) ?></b></td>
            <td><?= htmlspecialchars($h['address']) ?></td>
            <td><?= htmlspecialchars($h['vibe']) ?></td>
            <td>
                <!-- Nút sửa (có thể phát triển sau) và nút Xóa -->
                <a href="edit_hotel.php?id=<?= $h['id'] ?>" class="btn-outline">Sửa</a>
                <form method="POST" class="inline-form" onsubmit="return confirm('Bạn có chắc chắn muốn xóa khách sạn này?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="hotel_id" value="<?= (int) $h['id'] ?>">
                    <button type="submit" name="delete_hotel" class="btn-outline btn-danger">Xóa</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
</table>

<?php require_once 'includes/footer.php'; ?>
