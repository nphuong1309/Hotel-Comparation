-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: hoteltool
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

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

--
-- Table structure for table `feed_posts`
--

DROP TABLE IF EXISTS `feed_posts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `feed_posts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `author_name` varchar(100) NOT NULL,
  `author_id` int(11) DEFAULT NULL,
  `hotel_id` int(11) DEFAULT NULL COMMENT 'Khách sạn được review',
  `content` text NOT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `likes_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `author_id` (`author_id`),
  KEY `hotel_id` (`hotel_id`),
  CONSTRAINT `feed_posts_ibfk_1` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `feed_posts_ibfk_2` FOREIGN KEY (`hotel_id`) REFERENCES `hotels` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `feed_posts`
--

LOCK TABLES `feed_posts` WRITE;
/*!40000 ALTER TABLE `feed_posts` DISABLE KEYS */;
INSERT INTO `feed_posts` VALUES (1,'nganphuong',NULL,3,'phòng ok','uploads/post_1784098198_2.jpg',2,'2026-07-15 06:49:58'),(2,'phuong',NULL,1,'Vừa qua nhà mình có chuyến đi tới Cần Thơ, phân vân không biết lựa khách sạn nào tại cũng lần đầu tới miền Tây, may mà có trang web này hỗ trợ so sánh tìm kiếm được khách sạn Mường Thanh này vừa đúng nhu cầu mà nhanh gọn luôn không cần phải đi lựa nhiều trang web. Phòng ốc sạch sẽ, có bữa sáng ngon và đậm chất miền Tây lắm. Vị trí cũng gần bến Ninh Kiều, buổi tối đi chơi cũng gần và ngắm cảnh đêm từ khách sạn rất đẹp.','uploads/post_1784102373_4.jpg',2,'2026-07-15 07:59:33'),(3,'nnnnnn',NULL,1,'Rất đẹp và sang trọng, giá hơi cao nhưng dịch vụ rất ok.\r\nNhược điểm là tôi chưa được đi bao giờ',NULL,0,'2026-07-15 08:04:20'),(7,'phuong',4,NULL,'ksan này oke nha đồ ăn ngon',NULL,1,'2026-07-15 08:18:19'),(8,'sun6pack',7,1,'lễ tân nhiệt tình, chu đáo',NULL,1,'2026-07-24 04:58:29'),(9,'binu',9,1,'view đẹp, nhân viên nhiệt tình thân thiẹn',NULL,0,'2026-07-24 04:58:48');
/*!40000 ALTER TABLE `feed_posts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `feed_post_images`
--

DROP TABLE IF EXISTS `feed_post_images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `feed_post_images` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `post_id` int(11) NOT NULL,
  `image_url` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `post_id` (`post_id`),
  CONSTRAINT `feed_post_images_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `feed_posts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `feed_post_images`
--

LOCK TABLES `feed_post_images` WRITE;
/*!40000 ALTER TABLE `feed_post_images` DISABLE KEYS */;
INSERT INTO `feed_post_images` VALUES (1,1,'uploads/post_1784098198_2.jpg'),(2,2,'uploads/post_1784102373_4.jpg'),(11,7,'uploads/post_1784103499_0_4.jpg'),(12,7,'uploads/post_1784103499_1_4.jpg'),(13,8,'uploads/post_8_7_739d11a65a067920.jpg'),(14,9,'uploads/post_9_9_86a758871961945d.jpg');
/*!40000 ALTER TABLE `feed_post_images` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-07-31 16:20:47
