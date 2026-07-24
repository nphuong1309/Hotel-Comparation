<?php

if (session_status() === PHP_SESSION_NONE) {
    $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_start([
        'cookie_httponly' => true,
        'cookie_secure' => $isHttps,
        'cookie_samesite' => 'Lax',
        'use_strict_mode' => true,
    ]);
}

$config = require __DIR__ . '/config.php';
require_once __DIR__ . '/db-connect.php';

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function is_post_request(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function redirect(string $location): never
{
    header('Location: ' . $location);
    exit;
}

function require_login(): void
{
    if (!isset($_SESSION['user_id'])) {
        redirect('auth.php?action=login');
    }
}

function require_admin(): void
{
    if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
        redirect('auth.php?action=login');
    }
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf_token(): bool
{
    $submittedToken = $_POST['csrf_token'] ?? '';
    $sessionToken = $_SESSION['csrf_token'] ?? '';

    return is_string($submittedToken)
        && is_string($sessionToken)
        && $sessionToken !== ''
        && hash_equals($sessionToken, $submittedToken);
}

function require_csrf(bool $json = false): void
{
    if (verify_csrf_token()) {
        return;
    }

    if ($json) {
        json_response(['success' => false, 'message' => 'Phiên làm việc đã hết hạn. Vui lòng tải lại trang.'], 419);
    }

    http_response_code(419);
    exit('Phiên làm việc đã hết hạn. Vui lòng quay lại và thử lại.');
}

function json_response(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function positive_int(mixed $value): ?int
{
    $value = filter_var($value, FILTER_VALIDATE_INT);
    return $value !== false && $value > 0 ? $value : null;
}

function amenity_icon_svg(?string $icon, string $class = ''): string
{
    static $icons = [
        'pool' => '<path d="M3 17c1.2 0 1.8-1 3-1s1.8 1 3 1 1.8-1 3-1 1.8 1 3 1 1.8-1 3-1 1.8 1 3 1"/><path d="M3 21c1.2 0 1.8-1 3-1s1.8 1 3 1 1.8-1 3-1 1.8 1 3 1 1.8-1 3-1 1.8 1 3 1"/><circle cx="15" cy="5" r="2"/><path d="m6 12 3-3 4 2 3-2 5 4"/>',
        'utensils' => '<path d="M7 3v7"/><path d="M4 3v4a3 3 0 0 0 6 0V3"/><path d="M7 10v11"/><path d="M17 3v18"/><path d="M17 3c2.2 1.7 3 4.2 3 7h-3"/>',
        'spa' => '<path d="M12 21c-4.5-2.5-7-5.7-7-9.5 3.2 0 5.6 1.3 7 3.7 1.4-2.4 3.8-3.7 7-3.7 0 3.8-2.5 7-7 9.5Z"/><path d="M12 15.2C8.8 13.2 8 9.4 12 4c4 5.4 3.2 9.2 0 11.2Z"/>',
        'parking' => '<rect x="4" y="3" width="16" height="18" rx="2"/><path d="M9 17V7h4a3 3 0 0 1 0 6H9"/>',
        'wifi' => '<path d="M5 12.5a10 10 0 0 1 14 0"/><path d="M8.5 16a5 5 0 0 1 7 0"/><path d="M12 20h.01"/>',
        'garden' => '<path d="M12 22V10"/><path d="M12 13c-4 0-7-2.5-7-6 4 0 7 2.5 7 6Z"/><path d="M12 10c4 0 7-2.5 7-6-4 0-7 2.5-7 6Z"/><path d="M7 22h10"/>',
        'bar' => '<path d="M4 4h16l-6 7v7"/><path d="M10 22h8"/><path d="M14 18v4"/><path d="M7 7h10"/>',
        'airport-shuttle' => '<path d="M3 7h12l4 5v7H3Z"/><path d="M15 7v5h4"/><circle cx="7" cy="19" r="2"/><circle cx="16" cy="19" r="2"/><path d="M5 11h6"/>',
        'laundry' => '<path d="m8 4 4-2 4 2 4 3-3 4v10H7V11L4 7Z"/><path d="M9 4c.5 1.5 1.5 2.2 3 2.2S14.5 5.5 15 4"/>',
        'tour' => '<path d="m3 6 6-3 6 3 6-3v15l-6 3-6-3-6 3Z"/><path d="M9 3v15"/><path d="M15 6v15"/><circle cx="12" cy="10" r="2"/>',
        'rental' => '<circle cx="6" cy="17" r="3"/><circle cx="18" cy="17" r="3"/><path d="m6 17 4-7h4l4 7"/><path d="M10 10 8 7h3"/><path d="M10 17h8"/>',
        'smoking' => '<path d="M3 15h13v4H3Z"/><path d="M16 15h2v4h-2"/><path d="M20 15h1v4h-1"/><path d="M7 11c0-2 3-2 3-4s-2-2-2-4"/><path d="M13 11c0-1.6 2-1.8 2-3.5"/>',
        'air-hot-water' => '<path d="M8 3v8a4 4 0 1 0 4 0V3a2 2 0 0 0-4 0Z"/><path d="M10 14v.01"/><path d="M17 5c2 1.5 2 3 0 4.5s-2 3 0 4.5"/><path d="M21 5c2 1.5 2 3 0 4.5s-2 3 0 4.5"/>',
        'meeting' => '<circle cx="8" cy="7" r="2"/><circle cx="16" cy="7" r="2"/><circle cx="12" cy="5" r="2"/><path d="M4 21v-3a4 4 0 0 1 4-4h8a4 4 0 0 1 4 4v3"/><path d="M8 14v7"/><path d="M16 14v7"/><path d="M8 18h8"/>',
        'reception' => '<path d="M4 18h16"/><path d="M6 18a6 6 0 0 1 12 0"/><path d="M12 9V6"/><circle cx="12" cy="4" r="1"/><path d="M3 21h18"/>',
        'currency-exchange' => '<path d="M7 7h11l-3-3"/><path d="m18 7-3 3"/><path d="M17 17H6l3 3"/><path d="m6 17 3-3"/><path d="M12 8v8"/><path d="M14.5 10.5c-.5-1-1.3-1.5-2.5-1.5-1.4 0-2.5.7-2.5 1.8 0 2.7 5 1.2 5 3.7 0 1.1-1.1 1.8-2.5 1.8-1.2 0-2.2-.5-2.7-1.5"/>',
    ];

    $paths = $icons[$icon ?? '']
        ?? '<circle cx="12" cy="12" r="9"/><path d="m8 12 2.5 2.5L16 9"/>';
    $classAttribute = $class !== '' ? ' class="' . e($class) . '"' : '';

    return '<svg' . $classAttribute . ' viewBox="0 0 24 24" aria-hidden="true" focusable="false">'
        . $paths
        . '</svg>';
}

/**
 * Gộp ảnh local và ảnh trong database, ưu tiên ảnh được đánh dấu primary.
 * Chuỗi database dùng định dạng "is_primary::image_url|||...".
 *
 * @return string[]
 */
function hotel_image_candidates(int $hotelId, ?string $databaseImages = null): array
{
    global $config;

    $rankedImages = [];
    $patterns = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'jfif'];
    foreach ($patterns as $extension) {
        $pattern = $config['uploads']['directory'] . DIRECTORY_SEPARATOR . "hotel_{$hotelId}_*.{$extension}";
        foreach (glob($pattern) ?: [] as $absolutePath) {
            $publicPath = $config['uploads']['public_prefix'] . basename($absolutePath);
            $rankedImages[$publicPath] = stripos(basename($publicPath), '_primary.') !== false ? 3 : 1;
        }
    }

    foreach (explode('|||', (string) $databaseImages) as $rawImage) {
        if ($rawImage === '') {
            continue;
        }

        [$primary, $imagePath] = array_pad(explode('::', $rawImage, 2), 2, null);
        if ($imagePath === null) {
            $imagePath = $primary;
            $primary = '0';
        }
        $imagePath = trim((string) $imagePath);
        if (
            $imagePath === ''
            || stripos($imagePath, 'via.placeholder.com') !== false
            || strpos($imagePath, '..') !== false
        ) {
            continue;
        }

        $isExternal = (bool) preg_match('~^https?://~i', $imagePath);
        $localPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . ltrim(str_replace('/', DIRECTORY_SEPARATOR, $imagePath), DIRECTORY_SEPARATOR);
        if (!$isExternal && !is_file($localPath)) {
            continue;
        }

        $rankedImages[$imagePath] = max($rankedImages[$imagePath] ?? 0, (int) $primary > 0 ? 2 : 1);
    }

    uksort($rankedImages, static function (string $left, string $right) use ($rankedImages): int {
        return ($rankedImages[$right] <=> $rankedImages[$left]) ?: strnatcasecmp($left, $right);
    });

    return array_keys($rankedImages);
}

/**
 * Trả về ảnh chính có thật trên server hoặc ảnh mặc định của website.
 */
function hotel_primary_image(int $hotelId): string
{
    global $config;

    foreach (['jpg', 'jpeg', 'png', 'webp', 'gif', 'jfif'] as $extension) {
        $filename = "hotel_{$hotelId}_primary.{$extension}";
        $absolutePath = $config['uploads']['directory'] . DIRECTORY_SEPARATOR . $filename;

        if (is_file($absolutePath)) {
            return $config['uploads']['public_prefix'] . $filename;
        }
    }

    return $config['uploads']['public_prefix'] . 'default-hotel.jpg';
}

/**
 * Lưu danh sách ảnh đã được kiểm tra MIME và kích thước.
 *
 * @return string[] Đường dẫn public tương đối của các ảnh đã lưu.
 */
function store_uploaded_images(
    array $files,
    string $filenamePrefix,
    int $maxFiles = 10,
    bool $primaryFirst = false
): array {
    global $config;

    if (!isset($files['name'], $files['tmp_name'], $files['error'], $files['size'])) {
        return [];
    }

    $names = is_array($files['name']) ? $files['name'] : [$files['name']];
    $tempNames = is_array($files['tmp_name']) ? $files['tmp_name'] : [$files['tmp_name']];
    $errors = is_array($files['error']) ? $files['error'] : [$files['error']];
    $sizes = is_array($files['size']) ? $files['size'] : [$files['size']];

    if (count($names) > $maxFiles) {
        throw new RuntimeException("Chỉ được tải tối đa {$maxFiles} ảnh.");
    }

    $mimeExtensions = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
    ];
    $uploadDirectory = $config['uploads']['directory'];
    $maxFileSize = (int) $config['uploads']['max_file_size'];

    if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0755, true) && !is_dir($uploadDirectory)) {
        throw new RuntimeException('Không thể tạo thư mục lưu ảnh.');
    }

    $savedPaths = [];

    try {
        foreach ($names as $index => $originalName) {
            $error = (int) ($errors[$index] ?? UPLOAD_ERR_NO_FILE);
            if ($error === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            if ($error !== UPLOAD_ERR_OK) {
                throw new RuntimeException('Một ảnh tải lên không hoàn tất.');
            }

            $size = (int) ($sizes[$index] ?? 0);
            if ($size <= 0 || $size > $maxFileSize) {
                throw new RuntimeException('Mỗi ảnh phải có dung lượng không quá 5 MB.');
            }

            $tempName = (string) ($tempNames[$index] ?? '');
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tempName);
            if (!isset($mimeExtensions[$mime])) {
                throw new RuntimeException('Chỉ chấp nhận ảnh JPG, PNG, GIF hoặc WEBP hợp lệ.');
            }

            $suffix = $primaryFirst && $index === 0
                ? 'primary'
                : bin2hex(random_bytes(8));
            $filename = $filenamePrefix . '_' . $suffix . '.' . $mimeExtensions[$mime];
            $absolutePath = $uploadDirectory . DIRECTORY_SEPARATOR . $filename;

            if (!move_uploaded_file($tempName, $absolutePath)) {
                throw new RuntimeException('Không thể lưu ảnh tải lên.');
            }

            $savedPaths[] = $config['uploads']['public_prefix'] . $filename;
        }
    } catch (Throwable $exception) {
        foreach ($savedPaths as $savedPath) {
            delete_upload_file($savedPath);
        }
        throw $exception;
    }

    return $savedPaths;
}

function delete_upload_file(?string $publicPath): bool
{
    global $config;

    if (!$publicPath) {
        return false;
    }

    $uploadDirectory = realpath($config['uploads']['directory']);
    $candidate = realpath(dirname(__DIR__) . DIRECTORY_SEPARATOR . ltrim(str_replace('/', DIRECTORY_SEPARATOR, $publicPath), DIRECTORY_SEPARATOR));

    if ($uploadDirectory === false || $candidate === false) {
        return false;
    }

    $prefix = rtrim($uploadDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    if (!str_starts_with($candidate, $prefix) || !is_file($candidate)) {
        return false;
    }

    return unlink($candidate);
}
