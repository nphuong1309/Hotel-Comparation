
/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `amenities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `amenities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `icon` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `amenities` WRITE;
/*!40000 ALTER TABLE `amenities` DISABLE KEYS */;
INSERT INTO `amenities` VALUES (1,'Hồ bơi vô cực','pool'),(2,'Buffet sáng','utensils'),(3,'Spa & Massage','spa'),(4,'Bãi đậu xe miễn phí','parking'),(5,'Wifi tốc độ cao','wifi'),(6,'Sân vườn/BBQ','garden'),(7,'Quầy bar','bar'),(8,'Xe đưa đón sân bay','airport-shuttle'),(9,'Dịch vụ giặt ủi','laundry'),(10,'Dịch vụ hỗ trợ đặt tour','tour'),(11,'Dịch vụ thuê xe máy/xe đạp','rental'),(12,'Khu vực hút thuốc','smoking'),(13,'Máy lạnh, phòng tắm nước nóng','air-hot-water'),(14,'Phòng họp/Phòng hội nghị miễn phí','meeting'),(15,'Lễ tân 24 giờ','reception'),(16,'Dịch vụ thu đổi ngoại tệ','currency-exchange');
/*!40000 ALTER TABLE `amenities` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `comparison_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `comparison_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `hotel_ids` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_comparison_user_hotels` (`user_id`,`hotel_ids`),
  CONSTRAINT `comparison_history_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `comparison_history` WRITE;
/*!40000 ALTER TABLE `comparison_history` DISABLE KEYS */;
INSERT INTO `comparison_history` VALUES (1,2,'5,7,9','2026-07-15 07:47:48'),(2,2,'2,3','2026-07-15 07:52:09'),(10,1,'10,16','2026-07-24 05:10:26'),(11,1,'6,7','2026-07-24 05:12:12'),(12,9,'10','2026-07-24 05:20:57'),(13,9,'8,10,16','2026-07-24 05:21:23'),(17,7,'10,16','2026-07-24 06:04:53'),(18,4,'8,16','2026-07-31 09:42:50');
/*!40000 ALTER TABLE `comparison_history` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `feed_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `feed_comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `post_id` int(11) NOT NULL,
  `author_name` varchar(100) NOT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `post_id` (`post_id`),
  CONSTRAINT `feed_comments_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `feed_posts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `feed_comments` WRITE;
/*!40000 ALTER TABLE `feed_comments` DISABLE KEYS */;
INSERT INTO `feed_comments` VALUES (1,1,'admin','thankss','2026-07-15 07:27:07'),(2,1,'cats','meo meo','2026-07-15 07:43:41'),(3,7,'sun6pack','seeding','2026-07-24 04:55:44'),(4,9,'admin','JoyTix chân thành cảm ơn','2026-07-24 05:11:11');
/*!40000 ALTER TABLE `feed_comments` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `feed_post_images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `feed_post_images` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `post_id` int(11) NOT NULL,
  `image_url` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_feed_post_images_post_url` (`post_id`,`image_url`),
  CONSTRAINT `feed_post_images_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `feed_posts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `feed_post_images` WRITE;
/*!40000 ALTER TABLE `feed_post_images` DISABLE KEYS */;
INSERT INTO `feed_post_images` VALUES (1,1,'uploads/post_1784098198_2.jpg'),(2,2,'uploads/post_1784102373_4.jpg'),(11,7,'uploads/post_1784103499_0_4.jpg'),(12,7,'uploads/post_1784103499_1_4.jpg'),(13,8,'uploads/post_8_7_739d11a65a067920.jpg'),(14,9,'uploads/post_9_9_86a758871961945d.jpg');
/*!40000 ALTER TABLE `feed_post_images` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `feed_post_likes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `feed_post_likes` (
  `post_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`post_id`,`user_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `feed_post_likes_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `feed_posts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `feed_post_likes_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `feed_post_likes` WRITE;
/*!40000 ALTER TABLE `feed_post_likes` DISABLE KEYS */;
INSERT INTO `feed_post_likes` VALUES (1,1,'2026-07-15 07:27:00'),(1,2,'2026-07-15 06:50:06'),(2,3,'2026-07-15 08:18:41'),(2,4,'2026-07-15 08:00:55'),(7,7,'2026-07-24 04:55:37'),(8,1,'2026-07-24 05:12:15');
/*!40000 ALTER TABLE `feed_post_likes` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `feed_posts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `feed_posts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `author_name` varchar(100) NOT NULL,
  `author_id` int(11) DEFAULT NULL,
  `hotel_id` int(11) DEFAULT NULL COMMENT 'Khách sạn được review',
  `content` text NOT NULL,
  `likes_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `author_id` (`author_id`),
  KEY `hotel_id` (`hotel_id`),
  CONSTRAINT `feed_posts_ibfk_1` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `feed_posts_ibfk_2` FOREIGN KEY (`hotel_id`) REFERENCES `hotels` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `feed_posts` WRITE;
/*!40000 ALTER TABLE `feed_posts` DISABLE KEYS */;
INSERT INTO `feed_posts` VALUES (1,'nganphuong',NULL,3,'phòng ok',2,'2026-07-15 06:49:58'),(2,'phuong',NULL,1,'Vừa qua nhà mình có chuyến đi tới Cần Thơ, phân vân không biết lựa khách sạn nào tại cũng lần đầu tới miền Tây, may mà có trang web này hỗ trợ so sánh tìm kiếm được khách sạn Mường Thanh này vừa đúng nhu cầu mà nhanh gọn luôn không cần phải đi lựa nhiều trang web. Phòng ốc sạch sẽ, có bữa sáng ngon và đậm chất miền Tây lắm. Vị trí cũng gần bến Ninh Kiều, buổi tối đi chơi cũng gần và ngắm cảnh đêm từ khách sạn rất đẹp.',2,'2026-07-15 07:59:33'),(3,'nnnnnn',NULL,1,'Rất đẹp và sang trọng, giá hơi cao nhưng dịch vụ rất ok.\r\nNhược điểm là tôi chưa được đi bao giờ',0,'2026-07-15 08:04:20'),(7,'phuong',4,NULL,'ksan này oke nha đồ ăn ngon',1,'2026-07-15 08:18:19'),(8,'sun6pack',7,1,'lễ tân nhiệt tình, chu đáo',1,'2026-07-24 04:58:29'),(9,'binu',9,1,'view đẹp, nhân viên nhiệt tình thân thiẹn',0,'2026-07-24 04:58:48');
/*!40000 ALTER TABLE `feed_posts` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `hotel_amenities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hotel_amenities` (
  `hotel_id` int(11) NOT NULL,
  `amenity_id` int(11) NOT NULL,
  PRIMARY KEY (`hotel_id`,`amenity_id`),
  KEY `amenity_id` (`amenity_id`),
  CONSTRAINT `hotel_amenities_ibfk_1` FOREIGN KEY (`hotel_id`) REFERENCES `hotels` (`id`) ON DELETE CASCADE,
  CONSTRAINT `hotel_amenities_ibfk_2` FOREIGN KEY (`amenity_id`) REFERENCES `amenities` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `hotel_amenities` WRITE;
/*!40000 ALTER TABLE `hotel_amenities` DISABLE KEYS */;
INSERT INTO `hotel_amenities` VALUES (1,1),(1,2),(1,3),(1,4),(1,5),(1,7),(1,8),(1,9),(1,10),(1,12),(1,13),(1,14),(1,15),(1,16),(2,1),(2,2),(2,3),(2,4),(2,5),(2,6),(2,7),(2,8),(2,9),(2,10),(2,11),(2,13),(2,14),(2,15),(2,16),(3,1),(3,2),(3,3),(3,4),(3,5),(3,6),(3,7),(3,8),(3,9),(3,10),(3,11),(3,13),(3,15),(3,16),(4,1),(4,2),(4,4),(4,5),(4,7),(4,8),(4,9),(4,10),(4,12),(4,13),(4,14),(4,15),(4,16),(5,2),(5,4),(5,5),(5,7),(5,9),(5,10),(5,11),(5,12),(5,13),(5,14),(5,15),(6,4),(6,5),(6,6),(6,9),(6,10),(6,11),(6,12),(6,13),(7,1),(7,2),(7,4),(7,5),(7,7),(7,8),(7,9),(7,10),(7,12),(7,13),(7,14),(7,15),(7,16),(8,4),(8,5),(8,9),(8,10),(8,11),(8,12),(8,13),(8,15),(9,1),(9,2),(9,3),(9,4),(9,5),(9,6),(9,7),(9,8),(9,9),(9,10),(9,11),(9,12),(9,13),(9,14),(9,15),(10,4),(10,5),(10,9),(10,10),(10,11),(10,12),(10,13),(10,15),(16,2),(16,3),(16,4),(16,5),(16,10),(16,11),(16,13);
/*!40000 ALTER TABLE `hotel_amenities` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `hotel_images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hotel_images` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hotel_id` int(11) NOT NULL,
  `image_url` varchar(255) NOT NULL,
  `is_primary` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_hotel_images_hotel_url` (`hotel_id`,`image_url`),
  CONSTRAINT `hotel_images_ibfk_1` FOREIGN KEY (`hotel_id`) REFERENCES `hotels` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=107 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `hotel_images` WRITE;
/*!40000 ALTER TABLE `hotel_images` DISABLE KEYS */;
INSERT INTO `hotel_images` VALUES (35,16,'uploads/hotel_16_primary.jpg',1),(36,16,'uploads/hotel_16_17c19cb25a2537c7.jpg',0),(37,16,'uploads/hotel_16_1473577a59126eab.png',0),(38,1,'uploads/hotel_1_1.jpg',0),(39,1,'uploads/hotel_1_2.jpg',0),(40,1,'uploads/hotel_1_3.jpg',0),(41,1,'uploads/hotel_1_4.jpg',0),(42,1,'uploads/hotel_1_5.jpg',0),(43,1,'uploads/hotel_1_6.jpg',0),(44,1,'uploads/hotel_1_primary.jpg',1),(45,2,'uploads/hotel_2_1.jpg',0),(46,2,'uploads/hotel_2_2.jpg',0),(47,2,'uploads/hotel_2_3.jpg',0),(48,2,'uploads/hotel_2_4.jpg',0),(49,2,'uploads/hotel_2_5.jpg',0),(50,2,'uploads/hotel_2_6.jpg',0),(51,2,'uploads/hotel_2_primary.jpg',1),(52,3,'uploads/hotel_3_1.jpg',0),(53,3,'uploads/hotel_3_2.jpg',0),(54,3,'uploads/hotel_3_3.jpg',0),(55,3,'uploads/hotel_3_4.jpg',0),(56,3,'uploads/hotel_3_5.jpg',0),(57,3,'uploads/hotel_3_primary.jpg',1),(58,4,'uploads/hotel_4_1.jpg',0),(59,4,'uploads/hotel_4_2.jpg',0),(60,4,'uploads/hotel_4_3.jpg',0),(61,4,'uploads/hotel_4_4.jpg',0),(62,4,'uploads/hotel_4_5.jpg',0),(63,4,'uploads/hotel_4_primary.jpg',1),(64,5,'uploads/hotel_5_1.jpg',0),(65,5,'uploads/hotel_5_3.jpg',0),(66,5,'uploads/hotel_5_4.jpg',0),(67,5,'uploads/hotel_5_5.jpg',0),(68,5,'uploads/hotel_5_6.jpg',0),(69,5,'uploads/hotel_5_primary.jpg',1),(70,6,'uploads/hotel_6_1.jpg',0),(71,6,'uploads/hotel_6_2.jpg',0),(72,6,'uploads/hotel_6_3.jpg',0),(73,6,'uploads/hotel_6_4.jpg',0),(74,6,'uploads/hotel_6_5.jpg',0),(75,6,'uploads/hotel_6_6.jpg',0),(76,6,'uploads/hotel_6_primary.jpg',1),(77,7,'uploads/hotel_7_1.jpg',0),(78,7,'uploads/hotel_7_2.jpg',0),(79,7,'uploads/hotel_7_3.jpg',0),(80,7,'uploads/hotel_7_4.jpg',0),(81,7,'uploads/hotel_7_5.jpg',0),(82,7,'uploads/hotel_7_6.jpg',0),(83,7,'uploads/hotel_7_primary.jpg',1),(84,8,'uploads/hotel_8_1.jpg',0),(85,8,'uploads/hotel_8_2.jpg',0),(86,8,'uploads/hotel_8_3.jpg',0),(87,8,'uploads/hotel_8_4.jpg',0),(88,8,'uploads/hotel_8_5.jpg',0),(89,8,'uploads/hotel_8_6.jpg',0),(90,8,'uploads/hotel_8_primary.jpg',1),(91,9,'uploads/hotel_9_1.jpg',0),(92,9,'uploads/hotel_9_2.jpg',0),(93,9,'uploads/hotel_9_3.jpg',0),(94,9,'uploads/hotel_9_4.jpg',0),(95,9,'uploads/hotel_9_5.jpg',0),(96,9,'uploads/hotel_9_6.jpg',0),(97,9,'uploads/hotel_9_primary.jpg',1),(98,10,'uploads/hotel_10_1.jpg',0),(99,10,'uploads/hotel_10_2.jpg',0),(100,10,'uploads/hotel_10_3.jpg',0),(101,10,'uploads/hotel_10_4.jpg',0),(102,10,'uploads/hotel_10_5.jpg',0),(103,10,'uploads/hotel_10_primary.jpg',1);
/*!40000 ALTER TABLE `hotel_images` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `hotels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hotels` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `phone` varchar(25) DEFAULT NULL,
  `star_rating` decimal(2,1) DEFAULT 3.0,
  `vibe` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_hotels_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `hotels` WRITE;
/*!40000 ALTER TABLE `hotels` DISABLE KEYS */;
INSERT INTO `hotels` VALUES (1,'Mường Thanh Luxury','Khu 1, Cồn Cái Khế, Phường Cái Khế, Ninh Kiều, Cần Thơ, Việt Nam','0292 399 1001',4.5,'Sang trọng','Muong Thanh Luxury Can Tho Hotel là khách sạn đạt tiêu chuẩn 5 sao đẳng cấp quốc tế đầu tiên tại khu vực Đồng bằng sông Cửu Long. Nơi đây hòa trộn nét kiến trúc hiện đại, sang trọng với vẻ đẹp tự nhiên, thanh bình của vùng sông nước miền Tây.'),(2,'Azerai Cần Thơ Resort','Phường Hưng Phú, Quận Cái Răng, Cần Thơ, Việt Nam','0292 399 1002',5.0,'Sang trọng','Azerai Cần Thơ Resort là khu nghỉ dưỡng 5 sao sang trọng độc bản, tọa lạc biệt lập trên Cồn Ấu mộc mạc giữa dòng sông Hậu thơ mộng mang phong cách kiến trúc Đông Dương đương đại thanh lịch, hòa quyện hoàn hảo với thiên nhiên xanh mướt của vùng sông nước miền Tây.'),(3,'Victoria Resort','Phường Cái Khế, Ninh Kiều, Cần Thơ, Việt Nam','0292 399 1003',4.5,'Nghỉ dưỡng','Victoria Cần Thơ Resort - khu nghỉ dưỡng quốc tế đầu tiên tại miền Tây, tọa lạc yên bình bên bờ sông Hậu với khuôn viên vườn nhiệt đới xanh mướt rộng 8.000m². Khách sạn mang đậm dấu ấn kiến trúc Pháp cổ điển hòa quyện cùng nét duyên dáng của văn hóa Đông Dương, là điểm đến lý tưởng cho kỳ nghỉ thư thái liền kề trung tâm thành phố.'),(4,'TTC Hotel Premium','2 Hai Bà Trưng, Phường Tân An, Ninh Kiều, Cần Thơ, Việt Nam','0292 399 1004',5.0,'Thanh lịch','TTC Hotel - Cần Thơ mang bến Ninh Kiều vào ngay tầm mắt bạn với ban công mở toang hướng thẳng ra ngã ba sông Hậu. Không chọn cách tách biệt, khách sạn đặt bạn vào ngay tâm điểm nhịp sống Tây Đô sôi động với chợ đêm và phố đi bộ ngay dưới chân mình.'),(5,'Iris Hotel','224 Đường 30 tháng 4, Xuân Khánh, Ninh Kiều, Cần Thơ, Việt Nam','0292 399 1005',4.0,'Thanh lịch','Iris Hotel Cần Thơ tọa lạc tại trung tâm trục đường giao thương sầm uất. Nơi đây sở hữu hệ thống phòng nghỉ sang trọng, dịch vụ hội nghị chuyên nghiệp và điểm nhấn độc đáo là Sky Bar trên tầng thượng với tầm nhìn bao trọn toàn cảnh thành phố lung linh về đêm.'),(6,'Green Village Mekong','Phú Hưng, Cái Răng, Cần Thơ, Việt Nam','0292 399 1006',3.0,'Thiên nhiên','Green Village Mekong rũ bỏ hoàn toàn những khối bê tông để đưa bạn về với những căn bungalow tre mái lá ẩn mình bên ao sen thanh tịnh. Không gian nơi đây lưu giữ trọn vẹn nhịp sống miền Tây nguyên bản thông qua tiếng chèo khua nước, những vòng xe đạp men theo bờ rạch và mâm cơm nhà rực lửa chuẩn vị Nam Bộ.'),(7,'Ninh Kiều Riverside','02 Hai Bà Trưng, Phường Tân An, Ninh Kiều, Cần Thơ, Việt Nam','0292 399 1007',3.5,'Thanh lịch','Ninh Kiều Riverside Hotel mang hình dáng con tàu uy nghi neo đậu ngay ngã ba sông Hậu. Với hơn 70% số phòng ôm trọn tầm nhìn hướng cầu đi bộ và Cồn Ấu, khách sạn kết nối bạn trực tiếp với nhịp sống giao thương và du thuyền ẩm thực sầm uất ngay khi bước ra cửa.'),(8,'KP Hotel Boutique','45 Ngô Quyền, Ninh Kiều, Cần Thơ, Việt Nam','0292 399 1008',5.0,'Thân thiện','KP Hotel mang phong cách tối giản, lịch lãm, nép mình yên tĩnh cách bến Ninh Kiều vài phút đi bộ. Như một trạm sạc năng lượng giữa lòng phố thị, khách sạn sở hữu phòng nghỉ tông màu trung tính ấm áp và nhà hàng giao thoa ẩm thực Á - Singapore độc đáo.'),(9,'Cồn Khương Resort','99A Nguyễn Hữu Cầu, Ninh Kiều, Cần Thơ, Việt Nam','0292 399 1009',4.0,'Nghỉ dưỡng','Cồn Khương Resort sở hữu vị trí đắc địa ôm sát dòng sông Hậu hiền hòa, gây ấn tượng bởi hệ thống bungalow mang hình dáng đó bắt cá độc đáo. Khu nghỉ dưỡng kết hợp hài hòa giữa không gian lưu trú hiện đại và sân vườn ngập tràn sắc sen súng, mang đến cho du khách một khoảng lặng thư thái, riêng tư tuyệt đối ngay cạnh trung tâm Tây Đô sầm uất.'),(10,'Apple Hotel','431 Đường 30 tháng 4, Ninh Kiều, Cần Thơ, Việt Nam','0292 399 1010',3.0,'Thanh lịch','Apple Hotel Cần Thơ rũ bỏ vẻ trầm mặc truyền thống để khoác lên mình phong cách hiện đại, năng động. Không chỉ là nơi lưu trú, khách sạn mang đến trải nghiệm đô thị tiện lợi với hệ thống phòng tối giản ngập tràn ánh sáng, hồ bơi lộng gió và xe đạp miễn phí để bạn tự do len lỏi khám phá các khu phố mua sắm náo nhiệt xung quanh.'),(16,'Cantho Eco Resort','Km7+, QL61C, Nhơn Thuận, Nhơn Ái, Cần Thơ, Việt Nam','+842926295999',4.6,'Thiên nhiên','Khách sạn nghỉ dưỡng tọa lạc tại  Nhơn Thuận, Nhơn Ái, Cần Thơ, Việt Nam.');
/*!40000 ALTER TABLE `hotels` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `rooms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rooms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hotel_id` int(11) NOT NULL,
  `capacity` int(11) NOT NULL,
  `price` decimal(10,0) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_rooms_hotel_capacity` (`hotel_id`,`capacity`),
  CONSTRAINT `rooms_ibfk_1` FOREIGN KEY (`hotel_id`) REFERENCES `hotels` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=44 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `rooms` WRITE;
/*!40000 ALTER TABLE `rooms` DISABLE KEYS */;
INSERT INTO `rooms` VALUES (3,2,2,4500000),(4,2,4,8500000),(5,3,2,2100000),(6,3,4,3800000),(7,4,2,950000),(8,4,4,1600000),(9,5,2,800000),(10,5,4,1450000),(11,6,2,400000),(12,6,4,650000),(13,7,2,1100000),(14,7,4,1900000),(15,8,2,550000),(16,8,4,850000),(17,9,2,1300000),(18,9,4,2200000),(19,10,2,350000),(20,10,4,550000),(37,1,2,1200000),(38,1,4,2500000),(41,16,2,1500000),(42,16,4,2500000);
/*!40000 ALTER TABLE `rooms` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','customer') DEFAULT 'customer',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`),
  UNIQUE KEY `uq_users_username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'admin','admin@minihotel.local','$2y$10$LZ4fRDfGbEk3A6vDCdYP5e09uqfqbkO1nmR6.rvJs3vKMVF0IIDLS','admin'),(2,'nganphuong','ngphuong1396@gmail.com','$2y$10$SKjctAMPklEsn.qIsHxu7OjTIwjYot3nO98dYn.V7OV0hW73msg2m','customer'),(3,'cats','catmeomeo@gmail.com','$2y$10$WIJktN70PDkOiJO/1Y0ym.CCY9ZlmKQ/7QwOBBZugRdGYMGVENhvG','customer'),(4,'phuong','ipadxau2016@gmail.com','$2y$10$8McqqAjLw2GrJgPYc7uKE.rY6oEhYZo8afQ9LJDy7u.ChQjun97b.','customer'),(5,'nnnnnn','dinhngan.jwe@gmail.com','$2y$10$RXf/C5z5BPJSMq6QG.YI0OwxBqkaUujsjRqojT.bidt6sNeFPNA.O','customer'),(6,'testuser1','testuser1@example.com','$2y$10$Fx7Qb2HeFlkg0lrsgXNdXumdC.tnvgSZAxqaliejfvQnyq3Mfil2C','customer'),(7,'sun6pack','sun6pack@gmail.com','$2y$10$GDUH8lTqgzIQcLrVVQM1UOiaAcm15shQTFYGVQhqI9bQd/cPJvvZu','customer'),(8,'nuuu','nuuu@gmail.com','$2y$10$uLThTRQBiasFtzinHTFM9.6ayzbK4Lt6Wf.B1cKSkRA7JwkUJ2Gz.','customer'),(9,'binu','binu@gmail.com','$2y$10$nuRHN9HTBdSHO2DLtmuBM.NgK5XnmFBxC5fhQJhf8cYCDDbE0.BmS','customer');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
