-- Chạy trên database JoyTix hiện có.
-- Migration có thể chạy lại an toàn nếu index đã tồn tại.
-- Nếu đang có dữ liệu trùng tên, ALTER TABLE sẽ dừng để quản trị viên xử lý
-- các bản ghi trùng trước khi chạy lại migration.

SET @hotel_name_index_exists = (
  SELECT COUNT(*)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'hotels'
    AND index_name = 'uq_hotels_name'
);

SET @hotel_name_index_sql = IF(
  @hotel_name_index_exists = 0,
  'ALTER TABLE `hotels` ADD UNIQUE KEY `uq_hotels_name` (`name`)',
  'SELECT ''uq_hotels_name already exists'' AS migration_status'
);

PREPARE hotel_name_index_statement FROM @hotel_name_index_sql;
EXECUTE hotel_name_index_statement;
DEALLOCATE PREPARE hotel_name_index_statement;
