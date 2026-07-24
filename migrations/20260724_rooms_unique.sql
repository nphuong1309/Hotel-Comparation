-- Chạy một lần cho database đã tồn tại trước bản sửa lỗi trùng giá phòng.
-- Sao lưu database trước khi chạy migration này.

START TRANSACTION;

-- Giữ bản ghi mới nhất cho mỗi cặp khách sạn + sức chứa.
DELETE older
FROM rooms older
INNER JOIN rooms newer
  ON newer.hotel_id = older.hotel_id
 AND newer.capacity = older.capacity
 AND newer.id > older.id;

COMMIT;

-- Chỉ thêm unique key khi database hiện tại chưa có.
SET @rooms_unique_exists = (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'rooms'
      AND index_name = 'uq_rooms_hotel_capacity'
);

SET @rooms_unique_sql = IF(
    @rooms_unique_exists = 0,
    'ALTER TABLE rooms ADD UNIQUE KEY uq_rooms_hotel_capacity (hotel_id, capacity)',
    'SELECT 1'
);

PREPARE rooms_unique_statement FROM @rooms_unique_sql;
EXECUTE rooms_unique_statement;
DEALLOCATE PREPARE rooms_unique_statement;
