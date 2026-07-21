<?php

/**
 * API.PHP - CỔNG AJAX DUY NHẤT CỦA WEBSITE
 *
 * Nhiệm vụ:
 * - Nhận các thao tác JavaScript không cần tải lại trang.
 * - Dùng tham số `action` để chuyển request tới đúng chức năng.
 * - Luôn yêu cầu POST + CSRF trước khi thay đổi dữ liệu hoặc gọi dịch vụ ngoài.
 *
 * Các action hiện có:
 * - toggle-like: thích/bỏ thích bài cộng đồng.
 * - delete-post: xóa bài cộng đồng nếu là chủ bài hoặc admin.
 * - fetch-map: admin lấy thông tin khách sạn từ SerpApi.
 *
 * File này chỉ trả JSON, không xuất giao diện HTML.
 */

require_once 'includes/bootstrap.php';

if (!is_post_request()) {
    json_response(['success' => false, 'message' => 'Phương thức không được hỗ trợ.'], 405);
}

require_csrf(true);
$action = (string) ($_GET['action'] ?? $_POST['action'] ?? '');

match ($action) {
    'toggle-like' => toggle_post_like($pdo),
    'delete-post' => delete_community_post($pdo),
    'fetch-map' => fetch_hotel_from_map($config),
    default => json_response(['success' => false, 'message' => 'Chức năng API không tồn tại.'], 404),
};

/** Thích hoặc bỏ thích, đồng thời tính lại bộ đếm từ dữ liệu thật. */
function toggle_post_like(PDO $pdo): never
{
    if (!isset($_SESSION['user_id'])) {
        json_response(['success' => false, 'message' => 'Vui lòng đăng nhập để thả tim.'], 401);
    }

    $postId = positive_int($_POST['post_id'] ?? null);
    $userId = (int) $_SESSION['user_id'];
    if ($postId === null) {
        json_response(['success' => false, 'message' => 'Bài đăng không hợp lệ.'], 422);
    }

    try {
        $pdo->beginTransaction();

        // Khóa bài để hai click đồng thời không làm sai tổng lượt thích.
        $postStmt = $pdo->prepare('SELECT id FROM feed_posts WHERE id = ? FOR UPDATE');
        $postStmt->execute([$postId]);
        if (!$postStmt->fetchColumn()) {
            $pdo->rollBack();
            json_response(['success' => false, 'message' => 'Bài đăng không còn tồn tại.'], 404);
        }

        $checkStmt = $pdo->prepare('SELECT 1 FROM feed_post_likes WHERE post_id = ? AND user_id = ?');
        $checkStmt->execute([$postId, $userId]);
        $liked = !$checkStmt->fetchColumn();

        $changeSql = $liked
            ? 'INSERT INTO feed_post_likes (post_id, user_id) VALUES (?, ?)'
            : 'DELETE FROM feed_post_likes WHERE post_id = ? AND user_id = ?';
        $pdo->prepare($changeSql)->execute([$postId, $userId]);

        $countStmt = $pdo->prepare('SELECT COUNT(*) FROM feed_post_likes WHERE post_id = ?');
        $countStmt->execute([$postId]);
        $likesCount = (int) $countStmt->fetchColumn();

        $pdo->prepare('UPDATE feed_posts SET likes_count = ? WHERE id = ?')
            ->execute([$likesCount, $postId]);
        $pdo->commit();

        json_response(['success' => true, 'liked' => $liked, 'likes_count' => $likesCount]);
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Like toggle failed: ' . $exception->getMessage());
        json_response(['success' => false, 'message' => 'Không thể cập nhật lượt thích lúc này.'], 500);
    }
}

/** Xóa bài và chỉ dọn file ảnh sau khi database đã commit thành công. */
function delete_community_post(PDO $pdo): never
{
    if (!isset($_SESSION['user_id'])) {
        json_response(['success' => false, 'message' => 'Vui lòng đăng nhập.'], 401);
    }

    $postId = positive_int($_POST['post_id'] ?? null);
    $userId = (int) $_SESSION['user_id'];
    $isAdmin = ($_SESSION['role'] ?? '') === 'admin';
    if ($postId === null) {
        json_response(['success' => false, 'message' => 'Bài đăng không hợp lệ.'], 422);
    }

    $stmt = $pdo->prepare('SELECT author_id, author_name FROM feed_posts WHERE id = ?');
    $stmt->execute([$postId]);
    $post = $stmt->fetch();
    if (!$post) {
        json_response(['success' => false, 'message' => 'Bài đăng không tồn tại.'], 404);
    }

    $isOwner = (int) $post['author_id'] === $userId
        || ($post['author_id'] === null
            && hash_equals((string) $post['author_name'], (string) $_SESSION['username']));
    if (!$isAdmin && !$isOwner) {
        json_response(['success' => false, 'message' => 'Bạn không có quyền xóa bài này.'], 403);
    }

    try {
        $imageStmt = $pdo->prepare('SELECT image_url FROM feed_post_images WHERE post_id = ?');
        $imageStmt->execute([$postId]);
        $imagePaths = $imageStmt->fetchAll(PDO::FETCH_COLUMN);

        $pdo->beginTransaction();
        $pdo->prepare('DELETE FROM feed_posts WHERE id = ?')->execute([$postId]);
        $pdo->commit();

        foreach ($imagePaths as $imagePath) {
            delete_upload_file($imagePath);
        }
        json_response(['success' => true, 'message' => 'Đã xóa bài đăng.']);
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Post deletion failed: ' . $exception->getMessage());
        json_response(['success' => false, 'message' => 'Không thể xóa bài lúc này.'], 500);
    }
}

/** Gọi SerpApi để hỗ trợ admin điền nhanh thông tin khách sạn. */
function fetch_hotel_from_map(array $config): never
{
    if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
        json_response(['success' => false, 'message' => 'Phiên đăng nhập admin đã hết hạn.'], 403);
    }

    $mapInput = trim((string) ($_POST['url'] ?? ''));
    if ($mapInput === '' || mb_strlen($mapInput) > 1000) {
        json_response(['success' => false, 'message' => 'Vui lòng nhập tên hoặc liên kết Google Maps hợp lệ.'], 422);
    }

    $apiKey = (string) ($config['serpapi_key'] ?? '');
    if ($apiKey === '') {
        json_response(['success' => false, 'message' => 'Chưa cấu hình HOTELTOOL_SERPAPI_KEY.'], 503);
    }

    $hotelTitle = $mapInput;
    if (preg_match('~maps/(?:place|search)/([^/?]+)~i', $mapInput, $matches)) {
        $hotelTitle = str_replace('+', ' ', urldecode($matches[1]));
    }

    $apiUrl = 'https://serpapi.com/search.json?' . http_build_query([
        'engine' => 'google_maps',
        'q' => $hotelTitle,
        'google_domain' => 'google.com',
        'hl' => 'vi',
        'gl' => 'vn',
        'api_key' => $apiKey,
    ]);

    try {
        $context = stream_context_create(['http' => [
            'timeout' => 12,
            'ignore_errors' => true,
            'user_agent' => 'JoyTix/1.0',
        ]]);
        $response = file_get_contents($apiUrl, false, $context);
        if ($response === false) {
            throw new RuntimeException('Không thể kết nối dịch vụ bản đồ.');
        }

        $data = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
        if (!empty($data['error'])) {
            throw new RuntimeException('Dịch vụ bản đồ từ chối yêu cầu.');
        }

        $place = $data['place_results'] ?? $data['local_results'][0] ?? null;
        if (!is_array($place)) {
            json_response(['success' => false, 'message' => 'Không tìm thấy khách sạn phù hợp.'], 404);
        }

        $stars = 4;
        if (!empty($place['hotel_class']) && preg_match('/\d+/', (string) $place['hotel_class'], $match)) {
            $stars = (int) $match[0];
        } elseif (isset($place['rating'])) {
            $stars = (int) round((float) $place['rating']);
        }

        $description = trim((string) ($place['description'] ?? ''));
        if ($description === '') {
            $type = $place['type'] ?? 'Khách sạn';
            $type = is_array($type) ? (string) ($type[0] ?? 'Khách sạn') : (string) $type;
            $address = trim((string) ($place['address'] ?? 'Cần Thơ'));
            $description = "{$type} tọa lạc tại {$address}. Vui lòng kiểm tra và bổ sung mô tả trước khi lưu.";
        }

        json_response([
            'success' => true,
            'name' => (string) ($place['title'] ?? ''),
            'address' => (string) ($place['address'] ?? ''),
            'phone' => (string) ($place['phone'] ?? ''),
            'stars' => max(1, min(5, $stars)),
            'description' => $description,
        ]);
    } catch (Throwable $exception) {
        error_log('Map lookup failed: ' . $exception->getMessage());
        json_response(['success' => false, 'message' => 'Không thể lấy dữ liệu bản đồ lúc này.'], 502);
    }
}
