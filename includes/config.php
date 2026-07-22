<?php

/** CONFIG.PHP: một nơi duy nhất đọc cấu hình môi trường cho database, API và upload. */

return [
    'database' => [
        'host' => getenv('HOTELTOOL_DB_HOST') ?: 'localhost',
        'port' => getenv('HOTELTOOL_DB_PORT') ?: '3306',
        'name' => getenv('HOTELTOOL_DB_NAME') ?: 'hoteltool',
        'user' => getenv('HOTELTOOL_DB_USER') ?: 'root',
        'password' => getenv('HOTELTOOL_DB_PASSWORD') ?: '',
    ],
    'gemini_api_key' => getenv('HOTELTOOL_GEMINI_API_KEY') ?: '',
    'serpapi_key'    => $_SERVER['HOTELTOOL_SERPAPI_KEY'] ?? getenv('HOTELTOOL_SERPAPI_KEY') ?: '',
    'uploads' => [
        'directory' => dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads',
        'public_prefix' => 'uploads/',
        'max_file_size' => 5 * 1024 * 1024,
    ],
];
