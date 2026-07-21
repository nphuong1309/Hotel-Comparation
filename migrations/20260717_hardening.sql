-- Chạy một lần cho database đã được tạo từ phiên bản cũ.
START TRANSACTION;

-- Chuyển ảnh bài viết kiểu cũ sang bảng quan hệ, không tạo bản ghi trùng.
INSERT INTO feed_post_images (post_id, image_url)
SELECT post.id, post.image_url
FROM feed_posts post
WHERE post.image_url IS NOT NULL
  AND post.image_url <> ''
  AND NOT EXISTS (
      SELECT 1
      FROM feed_post_images image
      WHERE image.post_id = post.id
        AND image.image_url = post.image_url
  );

-- Dọn bản ghi trùng trước khi thêm ràng buộc duy nhất.
DELETE newer
FROM comparison_history newer
JOIN comparison_history older
  ON newer.user_id = older.user_id
 AND newer.hotel_ids = older.hotel_ids
 AND newer.id > older.id;

DELETE newer
FROM rooms newer
JOIN rooms older
  ON newer.hotel_id = older.hotel_id
 AND newer.capacity = older.capacity
 AND newer.id > older.id;

ALTER TABLE users
  ADD UNIQUE KEY uq_users_username (username);

ALTER TABLE comparison_history
  ADD UNIQUE KEY uq_comparison_user_hotels (user_id, hotel_ids);

ALTER TABLE rooms
  ADD UNIQUE KEY uq_rooms_hotel_capacity (hotel_id, capacity);

-- Đồng bộ lại bộ đếm lượt thích từ bảng quan hệ nguồn.
UPDATE feed_posts post
LEFT JOIN (
    SELECT post_id, COUNT(*) AS total
    FROM feed_post_likes
    GROUP BY post_id
) likes ON likes.post_id = post.id
SET post.likes_count = COALESCE(likes.total, 0);

COMMIT;
