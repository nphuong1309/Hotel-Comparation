-- Thiết lập Database
CREATE DATABASE IF NOT EXISTS minihotel DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE minihotel;
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
  `star_rating` int(1) DEFAULT 3,
  `vibe` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;
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
    `star_rating`,
    `vibe`,
    `description`
  )
VALUES (
    1,
    'Mường Thanh Luxury',
    'Cồn Cái Khế, Q. Ninh Kiều, Cần Thơ',
    5,
    'Trung tâm',
    'Khách sạn 5 sao cao nhất ĐBSCL, tầm nhìn toàn cảnh sông Hậu tuyệt đẹp.'
  ),
  (
    2,
    'Azerai Cần Thơ Resort',
    'Cồn Ấu, Hưng Phú, Q. Cái Răng',
    5,
    'Hệ sinh thái',
    'Ốc đảo biệt lập hoàn toàn trên Cồn Ấu. Mang đến sự yên tĩnh tuyệt đối.'
  ),
  (
    3,
    'Victoria Resort',
    'Phường Cái Khế, Q. Ninh Kiều',
    4,
    'Yên tĩnh',
    'Khu nghỉ dưỡng mang đậm kiến trúc Đông Dương cổ điển, ẩn mình bên bờ sông Hậu.'
  ),
  (
    4,
    'TTC Hotel Premium',
    '02 Hai Bà Trưng, Bến Ninh Kiều',
    4,
    'Trung tâm',
    'Nằm ngay bến Ninh Kiều sầm uất, bước chân ra cửa là phố đi bộ và chợ đêm.'
  ),
  (
    5,
    'Iris Hotel',
    '224 Đường 30/4, Q. Ninh Kiều',
    4,
    'Hiện đại',
    'Thiết kế hiện đại, trẻ trung, nằm gần Vincom Xuân Khánh và khu vực sầm uất.'
  ),
  (
    6,
    'Green Village Mekong',
    'Phú Thứ, Q. Cái Răng',
    2,
    'Thiên nhiên',
    'Homestay hòa mình vào thiên nhiên với nhà lợp lá, vách tre, bao quanh bởi kênh rạch.'
  ),
  (
    7,
    'Ninh Kiều Riverside',
    '02 Hai Bà Trưng, Q. Ninh Kiều',
    4,
    'Trung tâm',
    'Kiến trúc mang dáng dấp của một con tàu đang vươn mình ra biển tại ngã ba sông.'
  ),
  (
    8,
    'KP Hotel Boutique',
    'Khu dân cư 91B, Q. Ninh Kiều',
    3,
    'Hiện đại',
    'Nội thất thông minh, tone màu ấm cúng, nằm trong khu dân cư an ninh.'
  ),
  (
    9,
    'Cồn Khương Resort',
    '99A Nguyễn Hữu Cầu, Cồn Khương',
    4,
    'Yên tĩnh',
    'Khu nghỉ dưỡng sinh thái được thiết kế theo hình hoa sen độc đáo.'
  ),
  (
    10,
    'Apple Hotel',
    '431 Đường 30/4, Q. Ninh Kiều',
    2,
    'Hiện đại',
    'Phòng ốc tối giản, tone màu sáng, rất phù hợp cho dân phượt hoặc sinh viên.'
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
INSERT INTO `amenities` (`id`, `name`)
VALUES (1, 'Hồ bơi vô cực'),
  (2, 'Buffet sáng'),
  (3, 'Spa & Massage'),
  (4, 'Bãi đậu xe miễn phí'),
  (5, 'Wifi tốc độ cao'),
  (6, 'Sân vườn/BBQ');
-- Gắn tiện nghi cho Khách sạn
INSERT INTO `hotel_amenities` (`hotel_id`, `amenity_id`)
VALUES (1, 1),
  (1, 2),
  (1, 3),
  (1, 5),
  (2, 1),
  (2, 3),
  (2, 6),
  (3, 2),
  (3, 3),
  (3, 6),
  (4, 2),
  (4, 4),
  (4, 5),
  (5, 2),
  (5, 4),
  (5, 5),
  (6, 4),
  (6, 5),
  (6, 6),
  (7, 1),
  (7, 2),
  (7, 5),
  (8, 4),
  (8, 5),
  (9, 1),
  (9, 2),
  (9, 6),
  (10, 4),
  (10, 5);
-- Bảng lưu lịch sử so sánh
CREATE TABLE IF NOT EXISTS `comparison_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `hotel_ids` varchar(255) NOT NULL COMMENT 'Danh sách ID khách sạn, cách nhau bởi dấu phẩy',
  `created_at` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;
-- Liên kết bài đăng cộng đồng với tài khoản (để lọc chính xác "bài đăng của tôi")
ALTER TABLE feed_posts
ADD COLUMN author_id INT NULL
AFTER author_name;
ALTER TABLE feed_posts
ADD FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE
SET NULL;