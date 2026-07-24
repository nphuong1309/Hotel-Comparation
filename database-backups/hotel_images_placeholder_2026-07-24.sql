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
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Dumping data for table `hotel_images`
--
-- WHERE:  image_url LIKE '%via.placeholder.com%'

LOCK TABLES `hotel_images` WRITE;
/*!40000 ALTER TABLE `hotel_images` DISABLE KEYS */;
INSERT INTO `hotel_images` (`id`, `hotel_id`, `image_url`, `is_primary`) VALUES (3,2,'https://via.placeholder.com/600x400?text=Azerai+1',1),(4,2,'https://via.placeholder.com/600x400?text=Azerai+2',0),(5,3,'https://via.placeholder.com/600x400?text=Victoria+1',1),(6,3,'https://via.placeholder.com/600x400?text=Victoria+2',0),(7,4,'https://via.placeholder.com/600x400?text=TTC+1',1),(8,4,'https://via.placeholder.com/600x400?text=TTC+2',0),(9,5,'https://via.placeholder.com/600x400?text=Iris+1',1),(10,5,'https://via.placeholder.com/600x400?text=Iris+2',0),(11,6,'https://via.placeholder.com/600x400?text=Green+Village+1',1),(12,6,'https://via.placeholder.com/600x400?text=Green+Village+2',0),(13,7,'https://via.placeholder.com/600x400?text=Riverside+1',1),(14,7,'https://via.placeholder.com/600x400?text=Riverside+2',0),(15,8,'https://via.placeholder.com/600x400?text=KP+Hotel+1',1),(16,8,'https://via.placeholder.com/600x400?text=KP+Hotel+2',0),(17,9,'https://via.placeholder.com/600x400?text=Con+Khuong+1',1),(18,9,'https://via.placeholder.com/600x400?text=Con+Khuong+2',0),(19,10,'https://via.placeholder.com/600x400?text=Apple+1',1),(20,10,'https://via.placeholder.com/600x400?text=Apple+2',0);
/*!40000 ALTER TABLE `hotel_images` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-07-24 11:25:51
