<?php
header('Content-Type: application/json; charset=utf-8');

$map_url = $_GET['url'] ?? '';

if (empty($map_url)) {
    echo json_encode(['error' => 'Vui lòng cung cấp link Google Maps hợp lệ!']);
    exit;
}

$decoded_url = urldecode($map_url);
$hotel_title = '';

if (preg_match('/maps\/place\/([^\/]+)/', $decoded_url, $matches)) {
    $hotel_title = str_replace('+', ' ', $matches[1]);
} else {
    $hotel_title = $map_url;
}

try {

    $api_key = '39615811bbe4b8c4268777d9a7ba01ef3ea9a54d9c24900375fc6a5ded064425';
    $params = [
        "engine"        => "google_maps",
        "q"             => $hotel_title,
        "google_domain" => "google.com",
        "hl"            => "vi",
        "gl"            => "vn",
        "api_key"       => $api_key
    ];

    // Tạo đường dẫn URL đầy đủ kèm tham số query
    $api_url = "https://serpapi.com/search.json?" . http_build_query($params);

    // Sử dụng hàm file_get_contents mặc định của PHP để lấy dữ liệu từ SerpApi
    $response = @file_get_contents($api_url);

    if ($response === FALSE) {
        throw new Exception("Không thể kết nối đến máy chủ SerpApi hoặc API Key đã hết hạn.");
    }

    $data = json_decode($response, true);

    $place = null;
    if (!empty($data['place_results'])) {
        $place = $data['place_results'];
    } elseif (!empty($data['local_results'][0])) {
        $place = $data['local_results'][0];
    }

    if ($place) {
        // Xử lý lấy số sao
        $stars = 4; // Mặc định
        if (isset($place['hotel_class'])) {
            if (preg_match('/\d+/', $place['hotel_class'], $star_matches)) {
                $stars = (int)$star_matches[0];
            }
        } elseif (isset($place['rating'])) {
            $stars = (int)round($place['rating']);
        }
        $stars = max(1, min(5, $stars));

        $description = $place['description'] ?? '';
        if (empty($description)) {
            $type = $place['type'] ?? 'Khách sạn';

            // Nếu $type bị trả về dạng Mảng, ta lấy phần tử đầu tiên hoặc nối chúng lại bằng dấu phẩy
            if (is_array($type)) {
                $type = !empty($type[0]) ? $type[0] : 'Khách sạn';
            }

            $description = "$type cao cấp tọa lạc tại khu vực " . ($place['address'] ?? '') . ". Sở hữu không gian nghỉ dưỡng lý tưởng cùng dịch vụ chất lượng.";
        }

        echo json_encode([
            'success'     => true,
            'name'        => $place['title'] ?? '',
            'address'     => $place['address'] ?? '',
            'phone'       => $place['phone'] ?? '',
            'stars'       => $stars,
            'description' => $description
        ]);
    } else {
        echo json_encode(['error' => 'Không tìm thấy thông tin chi tiết của khách sạn từ liên kết này trên Google Maps.']);
    }
} catch (Exception $e) {
    echo json_encode(['error' => 'Lỗi: ' . $e->getMessage()]);
}
