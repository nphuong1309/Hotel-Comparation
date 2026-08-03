-- JoyTix - Sample data
-- Import schema.sql before importing this file.
-- Demo accounts:
--   admin / Admin@123
--   demo  / Demo@123
-- The accounts and email addresses below are fictional and intended for grading only.

SET NAMES utf8mb4;
USE `hoteltool`;

START TRANSACTION;

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`) VALUES
  (1, 'admin', 'admin@joytix.local', '$2y$10$x99zFDPNgR/KeV8PjuJs4uUPW3ttZr7xCT1qz0xw7FItcRRBstY7y', 'admin'),
  (2, 'demo', 'demo@joytix.local', '$2y$10$/Zahc8gl8n3dXZtCi2AYNet/3vjn0gP3Sjh5LsYF9I97raZcAl3Fu', 'customer');

INSERT INTO `hotels`
  (`id`, `name`, `address`, `phone`, `star_rating`, `vibe`, `description`)
VALUES
  (1, 'Mường Thanh Luxury', 'Khu 1, Cồn Cái Khế, Cái Khế, Ninh Kiều, Cần Thơ', '0292 399 1001', 4.5, 'Sang trọng', 'Khách sạn cao tầng gần trung tâm, phù hợp với khách công tác và du lịch cần nhiều tiện ích.'),
  (5, 'Iris Hotel', '224 Đường 30 tháng 4, Xuân Khánh, Ninh Kiều, Cần Thơ', '0292 399 1005', 4.0, 'Thanh lịch', 'Khách sạn nằm trên trục đường trung tâm, có phòng nghỉ hiện đại và không gian phù hợp cho hội nghị.'),
  (6, 'Green Village Mekong', 'Phú Hưng, Cái Răng, Cần Thơ', '0292 399 1006', 3.0, 'Thiên nhiên', 'Khu lưu trú mang phong cách miền Tây với bungalow, ao sen và không gian xanh yên tĩnh.'),
  (7, 'Ninh Kiều Riverside', '02 Hai Bà Trưng, Tân An, Ninh Kiều, Cần Thơ', '0292 399 1007', 3.5, 'Thanh lịch', 'Khách sạn ven sông, thuận tiện tham quan bến Ninh Kiều, chợ đêm và khu vực trung tâm.'),
  (8, 'KP Hotel Boutique', '45 Ngô Quyền, Ninh Kiều, Cần Thơ', '0292 399 1008', 5.0, 'Thân thiện', 'Khách sạn boutique có không gian tối giản, mức giá vừa phải và vị trí thuận tiện đi bộ.'),
  (10, 'Apple Hotel', '431 Đường 30 tháng 4, Ninh Kiều, Cần Thơ', '0292 399 1010', 3.0, 'Thanh lịch', 'Khách sạn hiện đại với phòng sáng, hồ bơi và vị trí gần các khu mua sắm của thành phố.');

INSERT INTO `amenities` (`id`, `name`, `icon`) VALUES
  (1, 'Hồ bơi vô cực', 'pool'),
  (2, 'Buffet sáng', 'utensils'),
  (3, 'Spa & Massage', 'spa'),
  (4, 'Bãi đậu xe miễn phí', 'parking'),
  (5, 'Wifi tốc độ cao', 'wifi'),
  (6, 'Sân vườn/BBQ', 'garden'),
  (7, 'Quầy bar', 'bar'),
  (8, 'Xe đưa đón sân bay', 'airport-shuttle'),
  (9, 'Dịch vụ giặt ủi', 'laundry'),
  (10, 'Dịch vụ hỗ trợ đặt tour', 'tour'),
  (11, 'Dịch vụ thuê xe máy/xe đạp', 'rental'),
  (12, 'Khu vực hút thuốc', 'smoking'),
  (13, 'Máy lạnh, phòng tắm nước nóng', 'air-hot-water'),
  (14, 'Phòng họp/Phòng hội nghị miễn phí', 'meeting'),
  (15, 'Lễ tân 24 giờ', 'reception'),
  (16, 'Dịch vụ thu đổi ngoại tệ', 'currency-exchange');

INSERT INTO `rooms` (`id`, `hotel_id`, `capacity`, `price`) VALUES
  (1, 1, 2, 1200000),
  (2, 1, 4, 2500000),
  (3, 5, 2, 800000),
  (4, 5, 4, 1450000),
  (5, 6, 2, 400000),
  (6, 6, 4, 650000),
  (7, 7, 2, 1100000),
  (8, 7, 4, 1900000),
  (9, 8, 2, 550000),
  (10, 8, 4, 850000),
  (11, 10, 2, 350000),
  (12, 10, 4, 550000);

INSERT INTO `hotel_images` (`id`, `hotel_id`, `image_url`, `is_primary`) VALUES
  (1, 1, 'uploads/hotel_1_primary.jpg', 1),
  (2, 1, 'uploads/hotel_1_1.jpg', 0),
  (3, 5, 'uploads/hotel_5_primary.jpg', 1),
  (4, 5, 'uploads/hotel_5_1.jpg', 0),
  (5, 6, 'uploads/hotel_6_primary.jpg', 1),
  (6, 6, 'uploads/hotel_6_1.jpg', 0),
  (7, 7, 'uploads/hotel_7_primary.jpg', 1),
  (8, 7, 'uploads/hotel_7_1.jpg', 0),
  (9, 8, 'uploads/hotel_8_primary.jpg', 1),
  (10, 8, 'uploads/hotel_8_1.jpg', 0),
  (11, 10, 'uploads/hotel_10_primary.jpg', 1),
  (12, 10, 'uploads/hotel_10_1.jpg', 0);

INSERT INTO `hotel_amenities` (`hotel_id`, `amenity_id`) VALUES
  (1, 1), (1, 2), (1, 3), (1, 4), (1, 5), (1, 7), (1, 8), (1, 14), (1, 15),
  (5, 2), (5, 4), (5, 5), (5, 7), (5, 9), (5, 13), (5, 14), (5, 15),
  (6, 4), (6, 5), (6, 6), (6, 9), (6, 10), (6, 11), (6, 13),
  (7, 1), (7, 2), (7, 4), (7, 5), (7, 7), (7, 8), (7, 13), (7, 15),
  (8, 4), (8, 5), (8, 9), (8, 10), (8, 11), (8, 13), (8, 15),
  (10, 4), (10, 5), (10, 9), (10, 10), (10, 11), (10, 13), (10, 15);

INSERT INTO `comparison_history` (`id`, `user_id`, `hotel_ids`, `created_at`) VALUES
  (1, 2, '6,8,10', '2026-07-31 09:00:00'),
  (2, 2, '1,5,7', '2026-07-31 09:15:00');

INSERT INTO `feed_posts`
  (`id`, `author_name`, `author_id`, `hotel_id`, `content`, `likes_count`, `created_at`)
VALUES
  (1, 'demo', 2, 6, 'Không gian yên tĩnh và nhiều cây xanh. Bộ lọc của JoyTix giúp mình tìm được nơi phù hợp với nhóm bốn người và ngân sách đã đặt.', 2, '2026-07-31 10:00:00'),
  (2, 'demo', 2, 8, 'Khách sạn ở vị trí thuận tiện, phòng sạch và nhân viên hỗ trợ nhiệt tình. Mức giá hiển thị trên trang chi tiết phù hợp với nhu cầu của nhóm.', 1, '2026-07-31 10:30:00');

INSERT INTO `feed_post_images` (`id`, `post_id`, `image_url`) VALUES
  (1, 1, 'uploads/post_1784103499_0_4.jpg'),
  (2, 2, 'uploads/post_8_7_739d11a65a067920.jpg');

INSERT INTO `feed_comments` (`id`, `post_id`, `author_name`, `content`, `created_at`) VALUES
  (1, 1, 'admin', 'Cảm ơn bạn đã chia sẻ trải nghiệm thực tế.', '2026-07-31 10:10:00'),
  (2, 2, 'admin', 'Thông tin này sẽ hữu ích cho những người đang cân nhắc cùng phân khúc.', '2026-07-31 10:40:00');

INSERT INTO `feed_post_likes` (`post_id`, `user_id`, `created_at`) VALUES
  (1, 1, '2026-07-31 10:05:00'),
  (1, 2, '2026-07-31 10:06:00'),
  (2, 2, '2026-07-31 10:35:00');

COMMIT;

