<?php

/** API AJAX: like bài, xóa bài và lấy thông tin khách sạn từ Google Maps. */
require_once 'includes/bootstrap.php';

if (!is_post_request()) {
    json_response(['success' => false, 'message' => 'Phương thức không được hỗ trợ.'], 405);
}

require_csrf(true);

match ((string) ($_GET['action'] ?? $_POST['action'] ?? '')) {
    'toggle-like' => toggle_post_like($pdo),
    'delete-post' => delete_community_post($pdo),
    'fetch-map'   => fetch_hotel_from_map($config, $pdo),
    default       => json_response(['success' => false, 'message' => 'Chức năng API không tồn tại.'], 404),
};

/* ========================= CỘNG ĐỒNG ========================= */

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

        $stmt = $pdo->prepare('SELECT id FROM feed_posts WHERE id = ? FOR UPDATE');
        $stmt->execute([$postId]);
        if (!$stmt->fetchColumn()) {
            $pdo->rollBack();
            json_response(['success' => false, 'message' => 'Bài đăng không còn tồn tại.'], 404);
        }

        $stmt = $pdo->prepare('SELECT 1 FROM feed_post_likes WHERE post_id = ? AND user_id = ?');
        $stmt->execute([$postId, $userId]);
        $liked = !$stmt->fetchColumn();

        $sql = $liked
            ? 'INSERT INTO feed_post_likes (post_id, user_id) VALUES (?, ?)'
            : 'DELETE FROM feed_post_likes WHERE post_id = ? AND user_id = ?';
        $pdo->prepare($sql)->execute([$postId, $userId]);

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM feed_post_likes WHERE post_id = ?');
        $stmt->execute([$postId]);
        $likesCount = (int) $stmt->fetchColumn();

        $pdo->prepare('UPDATE feed_posts SET likes_count = ? WHERE id = ?')
            ->execute([$likesCount, $postId]);
        $pdo->commit();

        json_response(['success' => true, 'liked' => $liked, 'likes_count' => $likesCount]);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Like toggle failed: ' . $e->getMessage());
        json_response(['success' => false, 'message' => 'Không thể cập nhật lượt thích lúc này.'], 500);
    }
}

function delete_community_post(PDO $pdo): never
{
    if (!isset($_SESSION['user_id'])) {
        json_response(['success' => false, 'message' => 'Vui lòng đăng nhập.'], 401);
    }

    $postId = positive_int($_POST['post_id'] ?? null);
    $userId = (int) $_SESSION['user_id'];
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

    if (($_SESSION['role'] ?? '') !== 'admin' && !$isOwner) {
        json_response(['success' => false, 'message' => 'Bạn không có quyền xóa bài này.'], 403);
    }

    try {
        $stmt = $pdo->prepare('SELECT image_url FROM feed_post_images WHERE post_id = ?');
        $stmt->execute([$postId]);
        $images = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $pdo->beginTransaction();
        $pdo->prepare('DELETE FROM feed_posts WHERE id = ?')->execute([$postId]);
        $pdo->commit();

        foreach ($images as $image) {
            delete_upload_file($image);
        }
        json_response(['success' => true, 'message' => 'Đã xóa bài đăng.']);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Post deletion failed: ' . $e->getMessage());
        json_response(['success' => false, 'message' => 'Không thể xóa bài lúc này.'], 500);
    }
}

/* ========================= GOOGLE MAPS ========================= */

function fetch_hotel_from_map(array $config, PDO $pdo): never
{
    if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
        json_response(['success' => false, 'message' => 'Phiên đăng nhập admin đã hết hạn.'], 403);
    }

    $input = trim((string) ($_POST['url'] ?? ''));
    if ($input === '' || mb_strlen($input) > 1000) {
        json_response(['success' => false, 'message' => 'Vui lòng nhập tên hoặc liên kết Google Maps hợp lệ.'], 422);
    }

    $serpApiKey = (string) ($config['serpapi_key'] ?? '');
    if ($serpApiKey === '') {
        json_response(['success' => false, 'message' => 'Chưa cấu hình HOTELTOOL_SERPAPI_KEY.'], 503);
    }

    try {
        [$reference, $directLookup] = read_map_input($input);
        $params = build_map_query($reference, $directLookup);
        $candidates = collect_google_maps_candidates(
            request_serpapi_google_maps($params, $serpApiKey)
        );

        if (!$candidates) {
            throw new DomainException(
                'Không tìm thấy khách sạn phù hợp với thông tin đã nhập. Vui lòng kiểm tra tên khách sạn hoặc dán đúng link Google Maps của địa điểm.'
            );
        }

        $requestedTitle = trim((string) $reference['title']);
        $place = $directLookup
            ? ($candidates[0] ?? null)
            : choose_best_hotel_candidate($candidates, $requestedTitle);

        if (!$directLookup) {
            $place = prefer_can_tho_hotel($place, $requestedTitle, $serpApiKey);
        }

        if ($place === null) {
            throw new DomainException(
                'Không tìm thấy khách sạn phù hợp với tên đã nhập. Vui lòng kiểm tra tên khách sạn hoặc dán đúng link Google Maps của địa điểm.'
            );
        }

        unset($place['__name_match_score']);
        $place = fetch_google_maps_place_details($place, $serpApiKey);
        $fields = hotel_fields_from_place($place);

        if ($fields['name'] === '' || $fields['address'] === '') {
            throw new DomainException('Google Maps không trả đủ tên hoặc địa chỉ của khách sạn này.');
        }
        if (!contains_can_tho($fields['address'])) {
            throw new DomainException(
                "Khách sạn tìm thấy không nằm ở Cần Thơ. Địa chỉ Google Maps: {$fields['address']}"
            );
        }

        $generatedFields = [];
        if ($fields['phone'] === '') {
            $fields['phone'] = generate_unique_phone_placeholder($pdo);
            $generatedFields[] = 'phone';
        }

        $fields['description'] = make_hotel_description(
            $fields,
            $place,
            (string) ($config['gemini_api_key'] ?? '')
        );

        json_response(array_merge([
            'success' => true,
            'missing_fields' => $fields['stars'] === '' ? ['stars'] : [],
            'generated_fields' => $generatedFields,
            'phone_generated' => in_array('phone', $generatedFields, true),
        ], $fields));
    } catch (InvalidArgumentException $e) {
        json_response(['success' => false, 'message' => $e->getMessage()], 422);
    } catch (DomainException $e) {
        json_response(['success' => false, 'message' => $e->getMessage()], 404);
    } catch (Throwable $e) {
        error_log('Map lookup failed: ' . $e->getMessage());
        json_response([
            'success' => false,
            'message' => 'Không thể lấy dữ liệu Google Maps lúc này. Hãy kiểm tra khóa hoặc hạn mức SerpApi rồi thử lại.',
        ], 502);
    }
}

function read_map_input(string $input): array
{
    $reference = [
        'title' => '', 'place_id' => '', 'data_id' => '', 'data_cid' => '',
        'latitude' => null, 'longitude' => null,
    ];

    if (filter_var($input, FILTER_VALIDATE_URL) === false) {
        $reference['title'] = $input;
        return [$reference, false];
    }

    $reference = extract_google_maps_reference(resolve_google_maps_url($input));
    $hasReference = $reference['title'] !== ''
        || $reference['place_id'] !== ''
        || $reference['data_id'] !== ''
        || $reference['data_cid'] !== '';

    if (!$hasReference) {
        throw new InvalidArgumentException('Liên kết đã nhập không chứa địa điểm Google Maps hợp lệ.');
    }

    $direct = $reference['place_id'] !== ''
        || $reference['data_cid'] !== ''
        || ($reference['data_id'] !== ''
            && $reference['latitude'] !== null
            && $reference['longitude'] !== null);

    return [$reference, $direct];
}

function build_map_query(array $reference, bool $directLookup): array
{
    $params = maps_base_params();

    if ($reference['place_id'] !== '') {
        $params['place_id'] = $reference['place_id'];
    } elseif ($reference['data_cid'] !== '') {
        $params['data_cid'] = $reference['data_cid'];
    } elseif ($directLookup) {
        $params['type'] = 'place';
        $params['data'] = build_google_maps_place_data(
            $reference['data_id'],
            (float) $reference['latitude'],
            (float) $reference['longitude']
        );
    } else {
        $title = trim((string) $reference['title']);
        if ($title === '') {
            throw new InvalidArgumentException('Không đọc được tên địa điểm từ liên kết Google Maps.');
        }
        $params += ['type' => 'search', 'q' => $title];
    }

    return $params;
}

function prefer_can_tho_hotel(?array $place, string $title, string $apiKey): ?array
{
    if (
        $title === ''
        || contains_can_tho($title)
        || ($place !== null && contains_can_tho((string) ($place['address'] ?? '')))
    ) {
        return $place;
    }

    $params = maps_base_params([
        'type' => 'search',
        'q' => $title . ', Cần Thơ',
        'll' => '@10.045162,105.746857,13z',
    ]);
    $canThoPlace = choose_best_hotel_candidate(
        collect_google_maps_candidates(request_serpapi_google_maps($params, $apiKey)),
        $title
    );

    if ($canThoPlace === null || !contains_can_tho((string) ($canThoPlace['address'] ?? ''))) {
        return $place;
    }

    $oldScore = (float) ($place['__name_match_score'] ?? -1);
    $newScore = (float) ($canThoPlace['__name_match_score'] ?? -1);
    return $place === null || $newScore >= $oldScore ? $canThoPlace : $place;
}

function maps_base_params(array $extra = []): array
{
    return array_merge([
        'engine' => 'google_maps',
        'google_domain' => 'google.com',
        'hl' => 'vi',
        'gl' => 'vn',
    ], $extra);
}

function is_allowed_google_maps_host(string $host): bool
{
    $host = strtolower(rtrim($host, '.'));
    return in_array($host, ['maps.app.goo.gl', 'goo.gl', 'share.google'], true)
        || (bool) preg_match('/(^|\.)google\.(com|com\.vn|[a-z]{2})$/', $host);
}

function is_google_maps_destination(string $url): bool
{
    $host = strtolower((string) parse_url($url, PHP_URL_HOST));
    if (in_array($host, ['maps.app.goo.gl', 'goo.gl', 'share.google'], true)) {
        return false;
    }

    $path = (string) parse_url($url, PHP_URL_PATH);
    return is_allowed_google_maps_host($host)
        && (str_contains($path, '/maps/') || str_starts_with($host, 'maps.google.'));
}

function resolve_google_maps_url(string $url): string
{
    $host = strtolower((string) parse_url($url, PHP_URL_HOST));
    if ($host === '' || !is_allowed_google_maps_host($host)) {
        throw new InvalidArgumentException('Chỉ chấp nhận liên kết Google Maps.');
    }
    if (is_google_maps_destination($url)) {
        return $url;
    }

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 8,
        CURLOPT_RETURNTRANSFER => false,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) JoyTix/1.0',
        CURLOPT_WRITEFUNCTION => static fn ($curl, string $chunk): int => strlen($chunk),
    ]);

    $ok = curl_exec($ch);
    $error = curl_error($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $finalUrl = (string) curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    curl_close($ch);

    if ($ok === false) {
        throw new RuntimeException('Không mở được liên kết Google Maps: ' . $error);
    }
    if ($status >= 400 || $finalUrl === '') {
        throw new InvalidArgumentException('Liên kết Google Maps không còn hoạt động hoặc không truy cập được.');
    }
    if (!is_google_maps_destination($finalUrl)) {
        throw new InvalidArgumentException(
            'Liên kết đã nhập không dẫn tới một địa điểm Google Maps. Hãy sao chép liên kết từ nút Chia sẻ của địa điểm trên Google Maps.'
        );
    }

    return $finalUrl;
}

function extract_google_maps_reference(string $url): array
{
    $result = [
        'title' => '', 'place_id' => '', 'data_id' => '', 'data_cid' => '',
        'latitude' => null, 'longitude' => null,
    ];
    $decodedUrl = rawurldecode($url);
    $path = (string) parse_url($url, PHP_URL_PATH);
    parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

    if (preg_match('~/maps/(?:place|search)/([^/@?]+)~i', $path, $m)) {
        $result['title'] = trim(str_replace('+', ' ', rawurldecode($m[1])));
    }
    foreach (['q', 'query', 'destination'] as $key) {
        if ($result['title'] === '' && isset($query[$key]) && is_string($query[$key])) {
            $result['title'] = trim($query[$key]);
        }
    }
    foreach (['query_place_id', 'place_id'] as $key) {
        if (isset($query[$key]) && is_string($query[$key]) && $query[$key] !== '') {
            $result['place_id'] = trim($query[$key]);
            break;
        }
    }
    if (isset($query['cid']) && is_scalar($query['cid']) && ctype_digit((string) $query['cid'])) {
        $result['data_cid'] = (string) $query['cid'];
    }
    if (preg_match('~!1s([^!]+)~', $decodedUrl, $m)) {
        $result['data_id'] = trim(rawurldecode($m[1]));
    }

    if (
        preg_match('~@(-?\d+(?:\.\d+)?),(-?\d+(?:\.\d+)?)~', $url, $m)
        || preg_match('~!3d(-?\d+(?:\.\d+)?)!4d(-?\d+(?:\.\d+)?)~', $decodedUrl, $m)
    ) {
        $result['latitude'] = (float) $m[1];
        $result['longitude'] = (float) $m[2];
    }

    return $result;
}

function request_serpapi_google_maps(array $params, string $apiKey): array
{
    $params['api_key'] = $apiKey;
    $ch = curl_init('https://serpapi.com/search.json?' . http_build_query($params));
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => 'JoyTix/1.0',
    ]);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false) {
        throw new RuntimeException('Lỗi cURL SerpApi: ' . $error);
    }

    $data = json_decode((string) $response, true, 512, JSON_THROW_ON_ERROR);
    if ($status >= 400 || !empty($data['error'])) {
        throw new RuntimeException((string) ($data['error'] ?? "SerpApi HTTP {$status}"));
    }

    return $data;
}

function collect_google_maps_candidates(array $data): array
{
    $result = [];
    if (!empty($data['place_results']) && is_array($data['place_results'])) {
        $result[] = $data['place_results'];
    }
    foreach (!empty($data['local_results']) && is_array($data['local_results']) ? $data['local_results'] : [] as $place) {
        if (is_array($place)) {
            $result[] = $place;
        }
    }
    return $result;
}

function build_google_maps_place_data(string $dataId, float $lat, float $lng): string
{
    return "!4m5!3m4!1s{$dataId}!8m2!3d{$lat}!4d{$lng}";
}

function fetch_google_maps_place_details(array $place, string $apiKey): array
{
    $params = maps_base_params();
    $coordinates = $place['gps_coordinates'] ?? [];

    if (!empty($place['place_id'])) {
        $params['place_id'] = trim((string) $place['place_id']);
    } elseif (!empty($place['data_cid'])) {
        $params['data_cid'] = trim((string) $place['data_cid']);
    } elseif (
        !empty($place['data_id'])
        && is_array($coordinates)
        && isset($coordinates['latitude'], $coordinates['longitude'])
        && is_numeric($coordinates['latitude'])
        && is_numeric($coordinates['longitude'])
    ) {
        $params['type'] = 'place';
        $params['data'] = build_google_maps_place_data(
            trim((string) $place['data_id']),
            (float) $coordinates['latitude'],
            (float) $coordinates['longitude']
        );
    } else {
        return $place;
    }

    try {
        $details = request_serpapi_google_maps($params, $apiKey)['place_results'] ?? null;
        if (is_array($details)) {
            foreach ($details as $key => $value) {
                if ($value !== null && $value !== '' && $value !== []) {
                    $place[$key] = $value;
                }
            }
        }
    } catch (Throwable $e) {
        error_log('Google Maps place detail lookup failed: ' . $e->getMessage());
    }

    return $place;
}

function hotel_fields_from_place(array $place): array
{
    $rating = isset($place['rating']) && is_numeric($place['rating'])
        ? max(1.0, min(5.0, round((float) $place['rating'], 1)))
        : null;

    return [
        'name' => trim((string) ($place['title'] ?? '')),
        'address' => trim((string) ($place['address'] ?? '')),
        'phone' => trim((string) ($place['phone'] ?? '')),
        'stars' => $rating === null ? '' : number_format($rating, 1, '.', ''),
        'description' => '',
    ];
}

/* ========================= SO KHỚP TÊN ========================= */

function normalize_map_text(string $value): string
{
    $value = mb_strtolower(trim($value), 'UTF-8');
    $value = preg_replace(
        [
            '/[àáạảãâầấậẩẫăằắặẳẵ]/u', '/[èéẹẻẽêềếệểễ]/u',
            '/[ìíịỉĩ]/u', '/[òóọỏõôồốộổỗơờớợởỡ]/u',
            '/[ùúụủũưừứựửữ]/u', '/[ỳýỵỷỹ]/u', '/đ/u',
        ],
        ['a', 'e', 'i', 'o', 'u', 'y', 'd'],
        $value
    );
    return trim((string) preg_replace('/[^a-z0-9]+/', ' ', (string) $value));
}

function contains_can_tho(string $value): bool
{
    return str_contains(normalize_map_text($value), 'can tho');
}

function hotel_name_match_score(string $requestedTitle, string $candidateTitle): float
{
    $stopWords = [
        'khach', 'san', 'hotel', 'resort', 'motel', 'homestay', 'hostel',
        'nha', 'nghi', 'can', 'tho', 'viet', 'nam',
    ];
    $clean = static function (string $value) use ($stopWords): string {
        return implode(' ', array_values(array_filter(
            explode(' ', normalize_map_text($value)),
            static fn (string $word): bool => $word !== '' && !in_array($word, $stopWords, true)
        )));
    };

    $requested = $clean($requestedTitle);
    $candidate = $clean($candidateTitle);
    if ($requested === '' || $candidate === '') {
        return 0.0;
    }
    if ($requested === $candidate) {
        return 1.0;
    }
    if (str_contains($candidate, $requested) || str_contains($requested, $candidate)) {
        return 0.9;
    }

    $requestedTokens = array_values(array_unique(explode(' ', $requested)));
    $candidateTokens = array_values(array_unique(explode(' ', $candidate)));
    $common = count(array_intersect($requestedTokens, $candidateTokens));
    $recall = $common / max(1, count($requestedTokens));
    $precision = $common / max(1, count($candidateTokens));
    $tokenScore = ($precision + $recall) > 0
        ? (2 * $precision * $recall) / ($precision + $recall)
        : 0.0;

    similar_text($requested, $candidate, $percent);
    return max($tokenScore, ($percent / 100) * 0.9);
}

function choose_best_hotel_candidate(array $candidates, string $requestedTitle): ?array
{
    $best = null;
    $bestScore = -1.0;

    foreach ($candidates as $candidate) {
        $title = trim((string) ($candidate['title'] ?? ''));
        $address = trim((string) ($candidate['address'] ?? ''));
        if ($title === '' || $address === '') {
            continue;
        }

        $nameScore = $requestedTitle === '' ? 1.0 : hotel_name_match_score($requestedTitle, $title);
        if ($requestedTitle !== '' && $nameScore < 0.82) {
            continue;
        }

        $reviewsBonus = isset($candidate['reviews']) && is_numeric($candidate['reviews'])
            ? min(2.0, log10(max(1.0, (float) $candidate['reviews'])) / 2)
            : 0.0;
        $score = ($nameScore * 100) + $reviewsBonus;

        if ($score > $bestScore) {
            $candidate['__name_match_score'] = $nameScore;
            $best = $candidate;
            $bestScore = $score;
        }
    }

    return $best;
}

/* ========================= DỮ LIỆU DỰ PHÒNG ========================= */

function generate_unique_phone_placeholder(PDO $pdo): string
{
    $used = [];
    $phones = $pdo->query(
        "SELECT phone FROM hotels WHERE phone IS NOT NULL AND TRIM(phone) <> ''"
    )->fetchAll(PDO::FETCH_COLUMN);

    foreach ($phones as $phone) {
        $number = preg_replace('/\D+/', '', (string) $phone);
        if ($number !== '') {
            $used[$number] = true;
        }
    }

    for ($i = 0; $i < 1000; $i++) {
        $phone = '000' . str_pad((string) random_int(0, 9999999), 7, '0', STR_PAD_LEFT);
        if (!isset($used[$phone])) {
            return $phone;
        }
    }

    throw new RuntimeException('Không thể tạo mã liên hệ dự phòng duy nhất.');
}

function make_hotel_description(array $fields, array $place, string $geminiKey): string
{
    $type = $place['type'] ?? 'Khách sạn';
    $type = is_array($type) ? (string) ($type[0] ?? 'Khách sạn') : (string) $type;
    $fallback = "{$type} tọa lạc tại {$fields['address']}.";

    if ($geminiKey === '') {
        return $fallback;
    }

    try {
        return generate_hotel_description_with_gemini($fields, $place, $geminiKey);
    } catch (Throwable $e) {
        error_log('Gemini Error: ' . $e->getMessage());
        $rating = $fields['stars'] !== '' ? " Khách sạn có mức đánh giá {$fields['stars']}/5." : '';
        return "Chào mừng bạn đến với {$fields['name']}. Tọa lạc tại {$fields['address']}.{$rating} Không gian nghỉ dưỡng thoáng mát cùng các dịch vụ tiện lợi phù hợp cho chuyến đi của bạn.";
    }
}

function generate_hotel_description_with_gemini(array $fields, array $place, string $apiKey): string
{
    $type = $place['type'] ?? 'Khách sạn';
    $type = is_array($type) ? implode(', ', $type) : (string) $type;
    $rating = $place['rating'] ?? $fields['stars'];

    $prompt = <<<PROMPT
Bạn là một chuyên gia viết nội dung du lịch. Hãy viết một đoạn mô tả ngắn (khoảng 3-4 câu) giới thiệu về khách sạn bằng tiếng Việt hấp dẫn, chuyên nghiệp để thu hút khách đặt phòng.

Thông tin khách sạn:
- Tên khách sạn: {$fields['name']}
- Loại hình: {$type}
- Địa chỉ: {$fields['address']}
- Đánh giá: {$rating}/5 sao

Yêu cầu:
- Giọng văn chào đón, mượt mà, chuyên nghiệp.
- Nhấn mạnh vị trí thuận tiện và không gian nghỉ dưỡng lý tưởng.
- KHÔNG thêm tiêu đề, KHÔNG thêm lời chào, CHỈ trả về duy nhất đoạn văn mô tả.
PROMPT;

    $payload = [
        'contents' => [['parts' => [['text' => $prompt]]]],
        'generationConfig' => ['temperature' => 0.7, 'maxOutputTokens' => 300],
    ];
    $url = 'https://generativelanguage.googleapis.com/v1beta/models/'
        . 'gemini-2.0-flash-lite:generateContent?key=' . urlencode($apiKey);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
        CURLOPT_TIMEOUT => 15,
    ]);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false) {
        throw new RuntimeException('cURL Gemini lỗi: ' . $error);
    }

    $data = json_decode((string) $response, true);
    if ($status !== 200) {
        throw new RuntimeException('Gemini HTTP ' . $status . ': ' . ($data['error']['message'] ?? 'Lỗi không xác định'));
    }

    $text = trim((string) ($data['candidates'][0]['content']['parts'][0]['text'] ?? ''));
    if ($text === '') {
        throw new RuntimeException('Gemini không trả về nội dung mô tả.');
    }

    return $text;
}