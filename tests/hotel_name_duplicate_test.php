<?php

declare(strict_types=1);

require dirname(__DIR__) . '/includes/bootstrap.php';

function assert_test(bool $condition, string $label): void
{
    echo $label . '=' . ($condition ? 'PASS' : 'FAIL') . PHP_EOL;
    if (!$condition) {
        exit(1);
    }
}

$hotelCountBefore = (int) $pdo->query('SELECT COUNT(*) FROM hotels')->fetchColumn();
$hotels = $pdo->query('SELECT id, name FROM hotels ORDER BY id ASC LIMIT 2')
    ->fetchAll(PDO::FETCH_ASSOC);

assert_test(count($hotels) === 2, 'fixture_has_two_hotels');

$firstHotelId = (int) $hotels[0]['id'];
$firstHotelName = (string) $hotels[0]['name'];
$secondHotelName = (string) $hotels[1]['name'];

assert_test(
    normalize_hotel_name("  {$firstHotelName}   Test  ") === "{$firstHotelName} Test",
    'normalize_whitespace'
);
assert_test(
    normalize_hotel_name("\u{00A0}{$firstHotelName}\u{00A0}") === $firstHotelName,
    'normalize_unicode_whitespace'
);
assert_test(hotel_name_exists($pdo, $firstHotelName), 'detect_existing_name');
assert_test(
    hotel_name_exists($pdo, mb_strtoupper($firstHotelName)),
    'detect_case_insensitive_name'
);
assert_test(
    !hotel_name_exists($pdo, $firstHotelName, $firstHotelId),
    'exclude_current_hotel_on_edit'
);
assert_test(
    hotel_name_exists($pdo, $secondHotelName, $firstHotelId),
    'detect_other_hotel_on_edit'
);

$indexes = $pdo->query('SHOW INDEX FROM hotels')->fetchAll(PDO::FETCH_ASSOC);
$uniqueNameIndexes = array_filter(
    $indexes,
    static fn(array $row): bool => ($row['Key_name'] ?? '') === 'uq_hotels_name'
        && (int) ($row['Non_unique'] ?? 1) === 0
);
assert_test(count($uniqueNameIndexes) === 1, 'database_unique_name_index');

$hotelCountAfter = (int) $pdo->query('SELECT COUNT(*) FROM hotels')->fetchColumn();
assert_test($hotelCountAfter === $hotelCountBefore, 'test_is_read_only');

echo 'All hotel-name duplicate tests passed.' . PHP_EOL;
