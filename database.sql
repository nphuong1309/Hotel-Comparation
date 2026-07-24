-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th7 24, 2026 lúc 06:15 AM
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `hoteltool`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `amenities`
--

CREATE TABLE `amenities` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `icon` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `amenities`
--

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

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `comparison_history`
--

CREATE TABLE `comparison_history` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `hotel_ids` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `comparison_history`
--

INSERT INTO `comparison_history` (`id`, `user_id`, `hotel_ids`, `created_at`) VALUES
(1, 2, '5,7,9', '2026-07-15 07:47:48'),
(2, 2, '2,3', '2026-07-15 07:52:09'),
(3, 1, '14,15', '2026-07-24 03:32:37'),
(4, 1, '9,10,14,15', '2026-07-24 03:33:10'),
(5, 1, '8,9,10,14,15', '2026-07-24 03:33:27');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `feed_comments`
--

CREATE TABLE `feed_comments` (
  `id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `author_name` varchar(100) NOT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `feed_comments`
--

INSERT INTO `feed_comments` (`id`, `post_id`, `author_name`, `content`, `created_at`) VALUES
(1, 1, 'admin', 'thankss', '2026-07-15 07:27:07'),
(2, 1, 'cats', 'meo meo', '2026-07-15 07:43:41');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `feed_posts`
--

CREATE TABLE `feed_posts` (
  `id` int(11) NOT NULL,
  `author_name` varchar(100) NOT NULL,
  `author_id` int(11) DEFAULT NULL,
  `hotel_id` int(11) DEFAULT NULL COMMENT 'Khách sạn được review',
  `content` text NOT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `likes_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `feed_posts`
--

INSERT INTO `feed_posts` (`id`, `author_name`, `author_id`, `hotel_id`, `content`, `image_url`, `likes_count`, `created_at`) VALUES
(1, 'nganphuong', NULL, 3, 'phòng ok', 'uploads/post_1784098198_2.jpg', 2, '2026-07-15 06:49:58'),
(2, 'phuong', NULL, 1, 'Vừa qua nhà mình có chuyến đi tới Cần Thơ, phân vân không biết lựa khách sạn nào tại cũng lần đầu tới miền Tây, may mà có trang web này hỗ trợ so sánh tìm kiếm được khách sạn Mường Thanh này vừa đúng nhu cầu mà nhanh gọn luôn không cần phải đi lựa nhiều trang web. Phòng ốc sạch sẽ, có bữa sáng ngon và đậm chất miền Tây lắm. Vị trí cũng gần bến Ninh Kiều, buổi tối đi chơi cũng gần và ngắm cảnh đêm từ khách sạn rất đẹp.', 'uploads/post_1784102373_4.jpg', 2, '2026-07-15 07:59:33'),
(3, 'nnnnnn', NULL, 1, 'Rất đẹp và sang trọng, giá hơi cao nhưng dịch vụ rất ok.\r\nNhược điểm là tôi chưa được đi bao giờ', NULL, 0, '2026-07-15 08:04:20'),
(7, 'phuong', 4, NULL, 'ksan này oke nha đồ ăn ngon', NULL, 0, '2026-07-15 08:18:19');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `feed_post_images`
--

CREATE TABLE `feed_post_images` (
  `id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `image_url` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `feed_post_images`
--

INSERT INTO `feed_post_images` (`id`, `post_id`, `image_url`) VALUES
(1, 1, 'uploads/post_1784098198_2.jpg'),
(2, 2, 'uploads/post_1784102373_4.jpg'),
(11, 7, 'uploads/post_1784103499_0_4.jpg'),
(12, 7, 'uploads/post_1784103499_1_4.jpg');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `feed_post_likes`
--

CREATE TABLE `feed_post_likes` (
  `post_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `feed_post_likes`
--

INSERT INTO `feed_post_likes` (`post_id`, `user_id`, `created_at`) VALUES
(1, 1, '2026-07-15 07:27:00'),
(1, 2, '2026-07-15 06:50:06'),
(2, 3, '2026-07-15 08:18:41'),
(2, 4, '2026-07-15 08:00:55');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `hotels`
--

CREATE TABLE `hotels` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `phone` varchar(25) DEFAULT NULL,
  `star_rating` decimal(2,1) DEFAULT 3.0,
  `vibe` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `hotels`
--

INSERT INTO `hotels` (`id`, `name`, `address`, `phone`, `star_rating`, `vibe`, `description`) VALUES
(1, 'Mường Thanh Luxury', 'Khu 1, Cồn Cái Khế, Phường Cái Khế, Ninh Kiều, Cần Thơ, Việt Nam', '0292 399 1001', 4.5, 'Sang trọng', 'Muong Thanh Luxury Can Tho Hotel là khách sạn đạt tiêu chuẩn 5 sao đẳng cấp quốc tế đầu tiên tại khu vực Đồng bằng sông Cửu Long. Nơi đây hòa trộn nét kiến trúc hiện đại, sang trọng với vẻ đẹp tự nhiên, thanh bình của vùng sông nước miền Tây.'),
(2, 'Azerai Cần Thơ Resort', 'Phường Hưng Phú, Quận Cái Răng, Cần Thơ, Việt Nam', '0292 399 1002', 5.0, 'Sang trọng', 'Azerai Cần Thơ Resort là khu nghỉ dưỡng 5 sao sang trọng độc bản, tọa lạc biệt lập trên Cồn Ấu mộc mạc giữa dòng sông Hậu thơ mộng mang phong cách kiến trúc Đông Dương đương đại thanh lịch, hòa quyện hoàn hảo với thiên nhiên xanh mướt của vùng sông nước miền Tây.'),
(3, 'Victoria Resort', 'Phường Cái Khế, Ninh Kiều, Cần Thơ, Việt Nam', '0292 399 1003', 4.5, 'Nghỉ dưỡng', 'Victoria Cần Thơ Resort - khu nghỉ dưỡng quốc tế đầu tiên tại miền Tây, tọa lạc yên bình bên bờ sông Hậu với khuôn viên vườn nhiệt đới xanh mướt rộng 8.000m². Khách sạn mang đậm dấu ấn kiến trúc Pháp cổ điển hòa quyện cùng nét duyên dáng của văn hóa Đông Dương, là điểm đến lý tưởng cho kỳ nghỉ thư thái liền kề trung tâm thành phố.'),
(4, 'TTC Hotel Premium', '2 Hai Bà Trưng, Phường Tân An, Ninh Kiều, Cần Thơ, Việt Nam', '0292 399 1004', 5.0, 'Thanh lịch', 'TTC Hotel - Cần Thơ mang bến Ninh Kiều vào ngay tầm mắt bạn với ban công mở toang hướng thẳng ra ngã ba sông Hậu. Không chọn cách tách biệt, khách sạn đặt bạn vào ngay tâm điểm nhịp sống Tây Đô sôi động với chợ đêm và phố đi bộ ngay dưới chân mình.'),
(5, 'Iris Hotel', '224 Đường 30 tháng 4, Xuân Khánh, Ninh Kiều, Cần Thơ, Việt Nam', '0292 399 1005', 4.0, 'Thanh lịch', 'Iris Hotel Cần Thơ tọa lạc tại trung tâm trục đường giao thương sầm uất. Nơi đây sở hữu hệ thống phòng nghỉ sang trọng, dịch vụ hội nghị chuyên nghiệp và điểm nhấn độc đáo là Sky Bar trên tầng thượng với tầm nhìn bao trọn toàn cảnh thành phố lung linh về đêm.'),
(6, 'Green Village Mekong', 'Phú Hưng, Cái Răng, Cần Thơ, Việt Nam', '0292 399 1006', 3.0, 'Thiên nhiên', 'Green Village Mekong rũ bỏ hoàn toàn những khối bê tông để đưa bạn về với những căn bungalow tre mái lá ẩn mình bên ao sen thanh tịnh. Không gian nơi đây lưu giữ trọn vẹn nhịp sống miền Tây nguyên bản thông qua tiếng chèo khua nước, những vòng xe đạp men theo bờ rạch và mâm cơm nhà rực lửa chuẩn vị Nam Bộ.'),
(7, 'Ninh Kiều Riverside', '02 Hai Bà Trưng, Phường Tân An, Ninh Kiều, Cần Thơ, Việt Nam', '0292 399 1007', 3.5, 'Thanh lịch', 'Ninh Kiều Riverside Hotel mang hình dáng con tàu uy nghi neo đậu ngay ngã ba sông Hậu. Với hơn 70% số phòng ôm trọn tầm nhìn hướng cầu đi bộ và Cồn Ấu, khách sạn kết nối bạn trực tiếp với nhịp sống giao thương và du thuyền ẩm thực sầm uất ngay khi bước ra cửa.'),
(8, 'KP Hotel Boutique', '45 Ngô Quyền, Ninh Kiều, Cần Thơ, Việt Nam', '0292 399 1008', 5.0, 'Thân thiện', 'KP Hotel mang phong cách tối giản, lịch lãm, nép mình yên tĩnh cách bến Ninh Kiều vài phút đi bộ. Như một trạm sạc năng lượng giữa lòng phố thị, khách sạn sở hữu phòng nghỉ tông màu trung tính ấm áp và nhà hàng giao thoa ẩm thực Á - Singapore độc đáo.'),
(9, 'Cồn Khương Resort', '99A Nguyễn Hữu Cầu, Ninh Kiều, Cần Thơ, Việt Nam', '0292 399 1009', 4.0, 'Nghỉ dưỡng', 'Cồn Khương Resort sở hữu vị trí đắc địa ôm sát dòng sông Hậu hiền hòa, gây ấn tượng bởi hệ thống bungalow mang hình dáng đó bắt cá độc đáo. Khu nghỉ dưỡng kết hợp hài hòa giữa không gian lưu trú hiện đại và sân vườn ngập tràn sắc sen súng, mang đến cho du khách một khoảng lặng thư thái, riêng tư tuyệt đối ngay cạnh trung tâm Tây Đô sầm uất.'),
(10, 'Apple Hotel', '431 Đường 30 tháng 4, Ninh Kiều, Cần Thơ, Việt Nam', '0292 399 1010', 3.0, 'Thanh lịch', 'Apple Hotel Cần Thơ rũ bỏ vẻ trầm mặc truyền thống để khoác lên mình phong cách hiện đại, năng động. Không chỉ là nơi lưu trú, khách sạn mang đến trải nghiệm đô thị tiện lợi với hệ thống phòng tối giản ngập tràn ánh sáng, hồ bơi lộng gió và xe đạp miễn phí để bạn tự do len lỏi khám phá các khu phố mua sắm náo nhiệt xung quanh.'),
(14, 'Khách sạn Sheraton Cần Thơ', '209 Đ. 30 Tháng 4, Ninh Kiều, Cần Thơ 900000, Việt Nam', '02923761888', 5.0, 'Sang trọng', 'Khách sạn cao cấp ven sông với phòng ốc lịch sự, có nhà hàng, quán bar, spa & bể bơi ngoài trời. Tọa lạc trong tòa nhà cao tầng cạnh Sông Cần Thơ, khách sạn tao nhã này cách bến xe buýt 1 phút đi bộ, cách Đền Ông xây dựng từ thế kỷ 19 và Bảo tàng Cần Thơ 2 km. Phòng ốc lịch sự với cửa sổ lớn từ sàn tới trần và tầm nhìn ra sông hoặc thành phố, có Wi-Fi, TV màn hình phẳng và tủ lạnh nhỏ. Các phòng suite có phòng khách riêng, một số phòng có khu vực ăn uống sang trọng. Có 2 nhà hàng sang trọng, cùng với phòng chờ ở sảnh và quán bar bên bể bơi. Các tiện nghi khác bao gồm bể bơi ngoài trời, spa và không gian hội họp.'),
(15, 'Lion 16 Hotel', '13-15 Đ. Hoàng Văn Thái, KDC Hưng Phú 1, Hưng Phú, Cần Thơ 900000, Việt Nam', '02923883300', 5.0, 'Sang trọng', 'Chào mừng bạn đến với Lion 16 Hotel. Tọa lạc tại vị trí thuận tiện tại 13-15 Đ. Hoàng Văn Thái, KDC Hưng Phú 1, Hưng Phú, Cần Thơ 900000, Việt Nam, khách sạn đạt tiêu chuẩn 5 sao với không gian nghỉ dưỡng thoáng mát, đầy đủ tiện nghi hiện đại và dịch vụ chăm sóc khách hàng chu đáo. Đây là điểm dừng chân lý tưởng cho chuyến du lịch hoặc công tác của bạn.');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `hotel_amenities`
--

CREATE TABLE `hotel_amenities` (
  `hotel_id` int(11) NOT NULL,
  `amenity_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `hotel_amenities`
--

INSERT INTO `hotel_amenities` (`hotel_id`, `amenity_id`) VALUES
(1, 1),
(1, 2),
(1, 3),
(1, 4),
(1, 5),
(1, 7),
(1, 8),
(1, 9),
(1, 10),
(1, 12),
(1, 13),
(1, 14),
(1, 15),
(1, 16),
(2, 1),
(2, 2),
(2, 3),
(2, 4),
(2, 5),
(2, 6),
(2, 7),
(2, 8),
(2, 9),
(2, 10),
(2, 11),
(2, 13),
(2, 14),
(2, 15),
(2, 16),
(3, 1),
(3, 2),
(3, 3),
(3, 4),
(3, 5),
(3, 6),
(3, 7),
(3, 8),
(3, 9),
(3, 10),
(3, 11),
(3, 13),
(3, 15),
(3, 16),
(4, 1),
(4, 2),
(4, 4),
(4, 5),
(4, 7),
(4, 8),
(4, 9),
(4, 10),
(4, 12),
(4, 13),
(4, 14),
(4, 15),
(4, 16),
(5, 2),
(5, 4),
(5, 5),
(5, 7),
(5, 9),
(5, 10),
(5, 11),
(5, 12),
(5, 13),
(5, 14),
(5, 15),
(6, 4),
(6, 5),
(6, 6),
(6, 9),
(6, 10),
(6, 11),
(6, 12),
(6, 13),
(7, 1),
(7, 2),
(7, 4),
(7, 5),
(7, 7),
(7, 8),
(7, 9),
(7, 10),
(7, 12),
(7, 13),
(7, 14),
(7, 15),
(7, 16),
(8, 4),
(8, 5),
(8, 9),
(8, 10),
(8, 11),
(8, 12),
(8, 13),
(8, 15),
(9, 1),
(9, 2),
(9, 3),
(9, 4),
(9, 5),
(9, 6),
(9, 7),
(9, 8),
(9, 9),
(9, 10),
(9, 11),
(9, 12),
(9, 13),
(9, 14),
(9, 15),
(10, 4),
(10, 5),
(10, 9),
(10, 10),
(10, 11),
(10, 12),
(10, 13),
(10, 15),
(14, 5),
(14, 13),
(14, 15),
(15, 15);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `hotel_images`
--

CREATE TABLE `hotel_images` (
  `id` int(11) NOT NULL,
  `hotel_id` int(11) NOT NULL,
  `image_url` varchar(255) NOT NULL,
  `is_primary` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `hotel_images`
--

INSERT INTO `hotel_images` (`id`, `hotel_id`, `image_url`, `is_primary`) VALUES
(3, 2, 'https://via.placeholder.com/600x400?text=Azerai+1', 1),
(4, 2, 'https://via.placeholder.com/600x400?text=Azerai+2', 0),
(5, 3, 'https://via.placeholder.com/600x400?text=Victoria+1', 1),
(6, 3, 'https://via.placeholder.com/600x400?text=Victoria+2', 0),
(7, 4, 'https://via.placeholder.com/600x400?text=TTC+1', 1),
(8, 4, 'https://via.placeholder.com/600x400?text=TTC+2', 0),
(9, 5, 'https://via.placeholder.com/600x400?text=Iris+1', 1),
(10, 5, 'https://via.placeholder.com/600x400?text=Iris+2', 0),
(11, 6, 'https://via.placeholder.com/600x400?text=Green+Village+1', 1),
(12, 6, 'https://via.placeholder.com/600x400?text=Green+Village+2', 0),
(13, 7, 'https://via.placeholder.com/600x400?text=Riverside+1', 1),
(14, 7, 'https://via.placeholder.com/600x400?text=Riverside+2', 0),
(15, 8, 'https://via.placeholder.com/600x400?text=KP+Hotel+1', 1),
(16, 8, 'https://via.placeholder.com/600x400?text=KP+Hotel+2', 0),
(17, 9, 'https://via.placeholder.com/600x400?text=Con+Khuong+1', 1),
(18, 9, 'https://via.placeholder.com/600x400?text=Con+Khuong+2', 0),
(19, 10, 'https://via.placeholder.com/600x400?text=Apple+1', 1),
(20, 10, 'https://via.placeholder.com/600x400?text=Apple+2', 0),
(30, 14, 'uploads/hotel_14_primary.jpg', 1),
(32, 15, 'uploads/hotel_15_primary.jpg', 1),
(33, 15, 'uploads/hotel_15_6182320960b885de.jpg', 0),
(34, 15, 'uploads/hotel_15_40302de13d35f0ae.jpg', 0);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `rooms`
--

CREATE TABLE `rooms` (
  `id` int(11) NOT NULL,
  `hotel_id` int(11) NOT NULL,
  `capacity` int(11) NOT NULL,
  `price` decimal(10,0) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `rooms`
--

INSERT INTO `rooms` (`id`, `hotel_id`, `capacity`, `price`) VALUES
(1, 1, 2, 1200000),
(2, 1, 4, 2500000),
(3, 2, 2, 4500000),
(4, 2, 4, 8500000),
(5, 3, 2, 2100000),
(6, 3, 4, 3800000),
(7, 4, 2, 950000),
(8, 4, 4, 1600000),
(9, 5, 2, 800000),
(10, 5, 4, 1450000),
(11, 6, 2, 400000),
(12, 6, 4, 650000),
(13, 7, 2, 1100000),
(14, 7, 4, 1900000),
(15, 8, 2, 550000),
(16, 8, 4, 850000),
(17, 9, 2, 1300000),
(18, 9, 4, 2200000),
(19, 10, 2, 350000),
(20, 10, 4, 550000),
(27, 14, 2, 2000000),
(28, 14, 4, 4500000),
(31, 15, 2, 500000),
(32, 15, 4, 1500000);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','customer') DEFAULT 'customer'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`) VALUES
(1, 'admin', 'admin@minihotel.local', '$2y$10$LZ4fRDfGbEk3A6vDCdYP5e09uqfqbkO1nmR6.rvJs3vKMVF0IIDLS', 'admin'),
(2, 'nganphuong', 'ngphuong1396@gmail.com', '$2y$10$SKjctAMPklEsn.qIsHxu7OjTIwjYot3nO98dYn.V7OV0hW73msg2m', 'customer'),
(3, 'cats', 'catmeomeo@gmail.com', '$2y$10$WIJktN70PDkOiJO/1Y0ym.CCY9ZlmKQ/7QwOBBZugRdGYMGVENhvG', 'customer'),
(4, 'phuong', 'ipadxau2016@gmail.com', '$2y$10$8McqqAjLw2GrJgPYc7uKE.rY6oEhYZo8afQ9LJDy7u.ChQjun97b.', 'customer'),
(5, 'nnnnnn', 'dinhngan.jwe@gmail.com', '$2y$10$RXf/C5z5BPJSMq6QG.YI0OwxBqkaUujsjRqojT.bidt6sNeFPNA.O', 'customer'),
(6, 'testuser1', 'testuser1@example.com', '$2y$10$Fx7Qb2HeFlkg0lrsgXNdXumdC.tnvgSZAxqaliejfvQnyq3Mfil2C', 'customer');

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `amenities`
--
ALTER TABLE `amenities`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `comparison_history`
--
ALTER TABLE `comparison_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Chỉ mục cho bảng `feed_comments`
--
ALTER TABLE `feed_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `post_id` (`post_id`);

--
-- Chỉ mục cho bảng `feed_posts`
--
ALTER TABLE `feed_posts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `author_id` (`author_id`),
  ADD KEY `hotel_id` (`hotel_id`);

--
-- Chỉ mục cho bảng `feed_post_images`
--
ALTER TABLE `feed_post_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `post_id` (`post_id`);

--
-- Chỉ mục cho bảng `feed_post_likes`
--
ALTER TABLE `feed_post_likes`
  ADD PRIMARY KEY (`post_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Chỉ mục cho bảng `hotels`
--
ALTER TABLE `hotels`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `hotel_amenities`
--
ALTER TABLE `hotel_amenities`
  ADD PRIMARY KEY (`hotel_id`,`amenity_id`),
  ADD KEY `amenity_id` (`amenity_id`);

--
-- Chỉ mục cho bảng `hotel_images`
--
ALTER TABLE `hotel_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `hotel_id` (`hotel_id`);

--
-- Chỉ mục cho bảng `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_rooms_hotel_capacity` (`hotel_id`,`capacity`);

--
-- Chỉ mục cho bảng `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_users_email` (`email`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `amenities`
--
ALTER TABLE `amenities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT cho bảng `comparison_history`
--
ALTER TABLE `comparison_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT cho bảng `feed_comments`
--
ALTER TABLE `feed_comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT cho bảng `feed_posts`
--
ALTER TABLE `feed_posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT cho bảng `feed_post_images`
--
ALTER TABLE `feed_post_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT cho bảng `hotels`
--
ALTER TABLE `hotels`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT cho bảng `hotel_images`
--
ALTER TABLE `hotel_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT cho bảng `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT cho bảng `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `comparison_history`
--
ALTER TABLE `comparison_history`
  ADD CONSTRAINT `comparison_history_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `feed_comments`
--
ALTER TABLE `feed_comments`
  ADD CONSTRAINT `feed_comments_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `feed_posts` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `feed_posts`
--
ALTER TABLE `feed_posts`
  ADD CONSTRAINT `feed_posts_ibfk_1` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `feed_posts_ibfk_2` FOREIGN KEY (`hotel_id`) REFERENCES `hotels` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `feed_post_images`
--
ALTER TABLE `feed_post_images`
  ADD CONSTRAINT `feed_post_images_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `feed_posts` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `feed_post_likes`
--
ALTER TABLE `feed_post_likes`
  ADD CONSTRAINT `feed_post_likes_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `feed_posts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `feed_post_likes_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `hotel_amenities`
--
ALTER TABLE `hotel_amenities`
  ADD CONSTRAINT `hotel_amenities_ibfk_1` FOREIGN KEY (`hotel_id`) REFERENCES `hotels` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `hotel_amenities_ibfk_2` FOREIGN KEY (`amenity_id`) REFERENCES `amenities` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `hotel_images`
--
ALTER TABLE `hotel_images`
  ADD CONSTRAINT `hotel_images_ibfk_1` FOREIGN KEY (`hotel_id`) REFERENCES `hotels` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `rooms`
--
ALTER TABLE `rooms`
  ADD CONSTRAINT `rooms_ibfk_1` FOREIGN KEY (`hotel_id`) REFERENCES `hotels` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
