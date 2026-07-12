-- Thiết lập Database
CREATE DATABASE IF NOT EXISTS hoteltool DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE hoteltool;
-- Xóa các bảng cũ nếu đã tồn tại để làm sạch dữ liệu
DROP TABLE IF EXISTS `feed_comments`,
`feed_posts`,
`comparison_history`,
`hotel_amenities`,
`amenities`,
`rooms`,
`hotel_images`,
`hotels`,
`users`;
-- ==========================================
-- 1. TẠO BẢNG & RÀNG BUỘC (SCHEMA)
-- ==========================================
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin', 'customer') DEFAULT 'customer',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;
CREATE TABLE `hotels` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `star_rating` decimal(2, 1) DEFAULT 3.0,
  `vibe` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- Bổ sung cột số điện thoại nếu cột phone chưa tồn tại.
SET @phone_column_exists = (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'hotels'
    AND COLUMN_NAME = 'phone'
);

SET @add_phone_column_sql = IF(
  @phone_column_exists = 0,
  'ALTER TABLE `hotels` ADD COLUMN `phone` VARCHAR(25) DEFAULT NULL AFTER `address`',
  'SELECT 1'
);

PREPARE add_phone_column_stmt FROM @add_phone_column_sql;
EXECUTE add_phone_column_stmt;
DEALLOCATE PREPARE add_phone_column_stmt;

CREATE TABLE `hotel_images` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hotel_id` int(11) NOT NULL,
  `image_url` varchar(255) NOT NULL,
  `is_primary` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`hotel_id`) REFERENCES `hotels`(`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;
CREATE TABLE `rooms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hotel_id` int(11) NOT NULL,
  `capacity` int(11) NOT NULL,
  `price` decimal(10, 0) NOT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`hotel_id`) REFERENCES `hotels`(`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;
CREATE TABLE `amenities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `icon` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;
CREATE TABLE `hotel_amenities` (
  `hotel_id` int(11) NOT NULL,
  `amenity_id` int(11) NOT NULL,
  PRIMARY KEY (`hotel_id`, `amenity_id`),
  FOREIGN KEY (`hotel_id`) REFERENCES `hotels`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`amenity_id`) REFERENCES `amenities`(`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;
-- Bảng lưu Bài đăng (Feed Posts)
CREATE TABLE `feed_posts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `author_name` varchar(100) NOT NULL,
  `author_id` int(11) DEFAULT NULL,
  `hotel_id` int(11) DEFAULT NULL COMMENT 'Khách sạn được review',
  `content` text NOT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `likes_count` int(11) DEFAULT 0,
  `created_at` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  FOREIGN KEY (`author_id`) REFERENCES `users`(`id`) ON DELETE
  SET NULL,
    FOREIGN KEY (`hotel_id`) REFERENCES `hotels`(`id`) ON DELETE
  SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;
CREATE TABLE `comparison_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `hotel_ids` varchar(255) NOT NULL,
  `created_at` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;
-- Bảng lưu Bình luận (Comments)
CREATE TABLE `feed_comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `post_id` int(11) NOT NULL,
  `author_name` varchar(100) NOT NULL,
  `content` text NOT NULL,
  `created_at` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  FOREIGN KEY (`post_id`) REFERENCES `feed_posts`(`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;
-- ==========================================
-- 2. THÊM DỮ LIỆU MẪU (SAMPLE DATA)
-- ==========================================
-- Tài khoản admin mặc định: 123456
INSERT INTO `users` (`username`, `email`, `password`, `role`)
VALUES (
    'admin',
    'admin@minihotel.local',
    '$2y$10$LZ4fRDfGbEk3A6vDCdYP5e09uqfqbkO1nmR6.rvJs3vKMVF0IIDLS',
    'admin'
  );
-- Thêm 10 Khách sạn đa dạng Vibe
INSERT INTO `hotels` (
    `id`,
    `name`,
    `address`,
    `phone`,
    `star_rating`,
    `vibe`,
    `description`
  )
VALUES (
    1,
    'Mường Thanh Luxury',
    'Khu 1, Cồn Cái Khế, Phường Cái Khế, Ninh Kiều, Cần Thơ, Việt Nam',
    '0292 399 1001',
    4.5,
    'Sang trọng',
    'Muong Thanh Luxury Can Tho Hotel là khách sạn đạt tiêu chuẩn 5 sao đẳng cấp quốc tế đầu tiên tại khu vực Đồng bằng sông Cửu Long. Nơi đây hòa trộn nét kiến trúc hiện đại, sang trọng với vẻ đẹp tự nhiên, thanh bình của vùng sông nước miền Tây.'
  ),
  (
    2,
    'Azerai Cần Thơ Resort',
    'Phường Hưng Phú, Quận Cái Răng, Cần Thơ, Việt Nam',
    '0292 399 1002',
    5.0,
    'Sang trọng',
    'Azerai Cần Thơ Resort là khu nghỉ dưỡng 5 sao sang trọng độc bản, tọa lạc biệt lập trên Cồn Ấu mộc mạc giữa dòng sông Hậu thơ mộng mang phong cách kiến trúc Đông Dương đương đại thanh lịch, hòa quyện hoàn hảo với thiên nhiên xanh mướt của vùng sông nước miền Tây.'
  ),
  (
    3,
    'Victoria Resort',
    'Phường Cái Khế, Ninh Kiều, Cần Thơ, Việt Nam',
    '0292 399 1003',
    4.5,
    'Nghỉ dưỡng',
    'Victoria Cần Thơ Resort - khu nghỉ dưỡng quốc tế đầu tiên tại miền Tây, tọa lạc yên bình bên bờ sông Hậu với khuôn viên vườn nhiệt đới xanh mướt rộng 8.000m². Khách sạn mang đậm dấu ấn kiến trúc Pháp cổ điển hòa quyện cùng nét duyên dáng của văn hóa Đông Dương, là điểm đến lý tưởng cho kỳ nghỉ thư thái liền kề trung tâm thành phố.'
  ),
  (
    4,
    'TTC Hotel Premium',
    '2 Hai Bà Trưng, Phường Tân An, Ninh Kiều, Cần Thơ, Việt Nam',
    '0292 399 1004',
    5.0,
    'Thanh lịch',
    'TTC Hotel - Cần Thơ mang bến Ninh Kiều vào ngay tầm mắt bạn với ban công mở toang hướng thẳng ra ngã ba sông Hậu. Không chọn cách tách biệt, khách sạn đặt bạn vào ngay tâm điểm nhịp sống Tây Đô sôi động với chợ đêm và phố đi bộ ngay dưới chân mình.'
  ),
  (
    5,
    'Iris Hotel',
    '224 Đường 30 tháng 4, Xuân Khánh, Ninh Kiều, Cần Thơ, Việt Nam',
    '0292 399 1005',
    4.0,
    'Thanh lịch',
    'Iris Hotel Cần Thơ tọa lạc tại trung tâm trục đường giao thương sầm uất. Nơi đây sở hữu hệ thống phòng nghỉ sang trọng, dịch vụ hội nghị chuyên nghiệp và điểm nhấn độc đáo là Sky Bar trên tầng thượng với tầm nhìn bao trọn toàn cảnh thành phố lung linh về đêm.'
  ),
  (
    6,
    'Green Village Mekong',
    'Phú Hưng, Cái Răng, Cần Thơ, Việt Nam',
    '0292 399 1006',
    3.0,
    'Thiên nhiên',
    'Green Village Mekong rũ bỏ hoàn toàn những khối bê tông để đưa bạn về với những căn bungalow tre mái lá ẩn mình bên ao sen thanh tịnh. Không gian nơi đây lưu giữ trọn vẹn nhịp sống miền Tây nguyên bản thông qua tiếng chèo khua nước, những vòng xe đạp men theo bờ rạch và mâm cơm nhà rực lửa chuẩn vị Nam Bộ.'
  ),
  (
    7,
    'Ninh Kiều Riverside',
    '02 Hai Bà Trưng, Phường Tân An, Ninh Kiều, Cần Thơ, Việt Nam',
    '0292 399 1007',
    3.5,
    'Thanh lịch',
    'Ninh Kiều Riverside Hotel mang hình dáng con tàu uy nghi neo đậu ngay ngã ba sông Hậu. Với hơn 70% số phòng ôm trọn tầm nhìn hướng cầu đi bộ và Cồn Ấu, khách sạn kết nối bạn trực tiếp với nhịp sống giao thương và du thuyền ẩm thực sầm uất ngay khi bước ra cửa.'
  ),
  (
    8,
    'KP Hotel Boutique',
    '45 Ngô Quyền, Ninh Kiều, Cần Thơ, Việt Nam',
    '0292 399 1008',
    5.0,
    'Thân thiện',
    'KP Hotel mang phong cách tối giản, lịch lãm, nép mình yên tĩnh cách bến Ninh Kiều vài phút đi bộ. Như một trạm sạc năng lượng giữa lòng phố thị, khách sạn sở hữu phòng nghỉ tông màu trung tính ấm áp và nhà hàng giao thoa ẩm thực Á - Singapore độc đáo.'
  ),
  (
    9,
    'Cồn Khương Resort',
    '99A Nguyễn Hữu Cầu, Ninh Kiều, Cần Thơ, Việt Nam',
    '0292 399 1009',
    4.0,
    'Nghỉ dưỡng',
    'Cồn Khương Resort sở hữu vị trí đắc địa ôm sát dòng sông Hậu hiền hòa, gây ấn tượng bởi hệ thống bungalow mang hình dáng đó bắt cá độc đáo. Khu nghỉ dưỡng kết hợp hài hòa giữa không gian lưu trú hiện đại và sân vườn ngập tràn sắc sen súng, mang đến cho du khách một khoảng lặng thư thái, riêng tư tuyệt đối ngay cạnh trung tâm Tây Đô sầm uất.'
  ),
  (
    10,
    'Apple Hotel',
    '431 Đường 30 tháng 4, Ninh Kiều, Cần Thơ, Việt Nam',
    '0292 399 1010',
    3.0,
    'Thanh lịch',
    'Apple Hotel Cần Thơ rũ bỏ vẻ trầm mặc truyền thống để khoác lên mình phong cách hiện đại, năng động. Không chỉ là nơi lưu trú, khách sạn mang đến trải nghiệm đô thị tiện lợi với hệ thống phòng tối giản ngập tràn ánh sáng, hồ bơi lộng gió và xe đạp miễn phí để bạn tự do len lỏi khám phá các khu phố mua sắm náo nhiệt xung quanh.'
  );
-- Thêm Thư viện ảnh (Mỗi khách sạn 2 ảnh để test hiệu ứng Carousel)
INSERT INTO `hotel_images` (`hotel_id`, `image_url`, `is_primary`)
VALUES (
    1,
    'https://cdn1.ivivu.com/iVivu/2016/12/18/20/khach-san-muong-thanh-luxury-can-tho-27-800x450.jpg',
    1
  ),
  (
    1,
    'https://via.placeholder.com/600x400?text=Muong+Thanh+2',
    0
  ),
  (
    2,
    'https://via.placeholder.com/600x400?text=Azerai+1',
    1
  ),
  (
    2,
    'https://via.placeholder.com/600x400?text=Azerai+2',
    0
  ),
  (
    3,
    'https://via.placeholder.com/600x400?text=Victoria+1',
    1
  ),
  (
    3,
    'https://via.placeholder.com/600x400?text=Victoria+2',
    0
  ),
  (
    4,
    'https://via.placeholder.com/600x400?text=TTC+1',
    1
  ),
  (
    4,
    'https://via.placeholder.com/600x400?text=TTC+2',
    0
  ),
  (
    5,
    'https://via.placeholder.com/600x400?text=Iris+1',
    1
  ),
  (
    5,
    'https://via.placeholder.com/600x400?text=Iris+2',
    0
  ),
  (
    6,
    'https://via.placeholder.com/600x400?text=Green+Village+1',
    1
  ),
  (
    6,
    'https://via.placeholder.com/600x400?text=Green+Village+2',
    0
  ),
  (
    7,
    'https://via.placeholder.com/600x400?text=Riverside+1',
    1
  ),
  (
    7,
    'https://via.placeholder.com/600x400?text=Riverside+2',
    0
  ),
  (
    8,
    'https://via.placeholder.com/600x400?text=KP+Hotel+1',
    1
  ),
  (
    8,
    'https://via.placeholder.com/600x400?text=KP+Hotel+2',
    0
  ),
  (
    9,
    'https://via.placeholder.com/600x400?text=Con+Khuong+1',
    1
  ),
  (
    9,
    'https://via.placeholder.com/600x400?text=Con+Khuong+2',
    0
  ),
  (
    10,
    'https://via.placeholder.com/600x400?text=Apple+1',
    1
  ),
  (
    10,
    'https://via.placeholder.com/600x400?text=Apple+2',
    0
  );
-- Thêm Phòng & Giá (Mỗi KS có 1 phòng 2 người và 1 phòng 4 người)
INSERT INTO `rooms` (`hotel_id`, `capacity`, `price`)
VALUES (1, 2, 1200000),
  (1, 4, 2500000),
  (2, 2, 4500000),
  (2, 4, 8500000),
  (3, 2, 2100000),
  (3, 4, 3800000),
  (4, 2, 950000),
  (4, 4, 1600000),
  (5, 2, 800000),
  (5, 4, 1450000),
  (6, 2, 400000),
  (6, 4, 650000),
  (7, 2, 1100000),
  (7, 4, 1900000),
  (8, 2, 550000),
  (8, 4, 850000),
  (9, 2, 1300000),
  (9, 4, 2200000),
  (10, 2, 350000),
  (10, 4, 550000);
-- Thêm Kho Tiện nghi
-- Cột icon lưu mã biểu tượng để detail.php hiển thị icon nét đơn sắc.
INSERT INTO `amenities` (`id`, `name`, `icon`)
VALUES
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

-- Gắn tiện nghi cho khách sạn theo hạng sao, mức giá và loại hình lưu trú.
INSERT INTO `hotel_amenities` (`hotel_id`, `amenity_id`)
VALUES
  -- 1. Mường Thanh Luxury: khách sạn 4.5 sao trung tâm, thiên về dịch vụ cao cấp và công vụ
  (1, 1), (1, 2), (1, 3), (1, 4), (1, 5), (1, 7),
  (1, 8), (1, 9), (1, 10), (1, 12), (1, 13), (1, 14), (1, 15), (1, 16),

  -- 2. Azerai Cần Thơ Resort: resort 5 sao cao cấp, nghỉ dưỡng và trải nghiệm trọn gói
  (2, 1), (2, 2), (2, 3), (2, 4), (2, 5), (2, 6), (2, 7), (2, 8),
  (2, 9), (2, 10), (2, 11), (2, 13), (2, 14), (2, 15), (2, 16),

  -- 3. Victoria Resort: resort 4.5 sao, phù hợp nghỉ dưỡng và khách quốc tế
  (3, 1), (3, 2), (3, 3), (3, 4), (3, 5), (3, 6), (3, 7), (3, 8),
  (3, 9), (3, 10), (3, 11), (3, 13), (3, 15), (3, 16),

  -- 4. TTC Hotel Premium: khách sạn 5 sao ở trung tâm, có dịch vụ hội họp và khách công tác
  (4, 1), (4, 2), (4, 4), (4, 5), (4, 7), (4, 8), (4, 9), (4, 10),
  (4, 12), (4, 13), (4, 14), (4, 15), (4, 16),

  -- 5. Iris Hotel: khách sạn 4 sao tầm trung, dịch vụ khá đầy đủ nhưng không quá cao cấp
  (5, 2), (5, 4), (5, 5), (5, 7), (5, 9), (5, 10), (5, 11), (5, 12),
  (5, 13), (5, 14), (5, 15),

  -- 6. Green Village Mekong: homestay 3 sao thiên nhiên, ưu tiên trải nghiệm và thuê xe
  (6, 4), (6, 5), (6, 6), (6, 9), (6, 10), (6, 11), (6, 12), (6, 13),

  -- 7. Ninh Kiều Riverside: khách sạn 3.5 sao trung tâm, phù hợp du lịch và hội nghị
  (7, 1), (7, 2), (7, 4), (7, 5), (7, 7), (7, 8), (7, 9), (7, 10),
  (7, 12), (7, 13), (7, 14), (7, 15), (7, 16),

  -- 8. KP Hotel Boutique: khách sạn 5 sao, tập trung tiện nghi thiết yếu
  (8, 4), (8, 5), (8, 9), (8, 10), (8, 11), (8, 12), (8, 13), (8, 15),

  -- 9. Cồn Khương Resort: resort 4 sao, có tiện nghi nghỉ dưỡng, tour và hội họp
  (9, 1), (9, 2), (9, 3), (9, 4), (9, 5), (9, 6), (9, 7), (9, 8),
  (9, 9), (9, 10), (9, 11), (9, 12), (9, 13), (9, 14), (9, 15),

  -- 10. Apple Hotel: khách sạn 3 sao bình dân, chỉ giữ các dịch vụ cơ bản và thiết thực
  (10, 4), (10, 5), (10, 9), (10, 10), (10, 11), (10, 12), (10, 13), (10, 15);
-- Bảng lưu lịch sử so sánh
CREATE TABLE IF NOT EXISTS `comparison_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `hotel_ids` varchar(255) NOT NULL COMMENT 'Danh sách ID khách sạn, cách nhau bởi dấu phẩy',
  `created_at` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;