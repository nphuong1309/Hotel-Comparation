<?php

/** DB-CONNECT.PHP: tạo PDO MySQL; không chứa schema hoặc logic giao diện. */

$config ??= require __DIR__ . '/config.php';
$database = $config['database'];
$dsn = sprintf(
    'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
    $database['host'],
    $database['port'],
    $database['name']
);

try {
    $pdo = new PDO($dsn, $database['user'], $database['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $exception) {
    error_log('Database connection failed: ' . $exception->getMessage());
    http_response_code(503);
    exit('Không thể kết nối cơ sở dữ liệu. Vui lòng thử lại sau.');
}
