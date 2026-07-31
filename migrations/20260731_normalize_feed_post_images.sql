-- Chuẩn hóa ảnh bài viết: feed_post_images là nguồn dữ liệu ảnh duy nhất.
-- Chạy một lần cho database được tạo từ phiên bản còn feed_posts.image_url.

START TRANSACTION;

-- Bảo toàn ảnh kiểu cũ trước khi xóa cột legacy.
INSERT INTO feed_post_images (post_id, image_url)
SELECT post.id, TRIM(post.image_url)
FROM feed_posts AS post
WHERE post.image_url IS NOT NULL
  AND TRIM(post.image_url) <> ''
  AND NOT EXISTS (
      SELECT 1
      FROM feed_post_images AS image
      WHERE image.post_id = post.id
        AND image.image_url = TRIM(post.image_url)
  );

-- Chỉ giữ bản ghi có id nhỏ nhất nếu dữ liệu cũ từng tạo ảnh trùng.
DELETE duplicate_image
FROM feed_post_images AS duplicate_image
JOIN feed_post_images AS original_image
  ON original_image.post_id = duplicate_image.post_id
 AND original_image.image_url = duplicate_image.image_url
 AND original_image.id < duplicate_image.id;

COMMIT;

-- Bảo vệ tính nhất quán cho các lần ghi dữ liệu về sau.
ALTER TABLE feed_post_images
  DROP INDEX post_id,
  ADD UNIQUE KEY uq_feed_post_images_post_url (post_id, image_url);

-- Xóa nguồn ảnh legacy sau khi đã chuyển dữ liệu an toàn.
ALTER TABLE feed_posts
  DROP COLUMN image_url;
