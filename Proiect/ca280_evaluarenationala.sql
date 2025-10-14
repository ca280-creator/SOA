-- MySQL dump 10.13  Distrib 8.0.38, for Win64 (x86_64)
--
-- Host: ca280.cti.ugal.ro    Database: ca280
-- ------------------------------------------------------
-- Server version	8.0.42-0ubuntu0.20.04.1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `evaluarenationala`
--

DROP TABLE IF EXISTS `evaluarenationala`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `evaluarenationala` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `varsta` int DEFAULT NULL,
  `lb_romana` decimal(10,0) DEFAULT NULL,
  `matematica` decimal(10,0) DEFAULT NULL,
  `media` decimal(10,0) DEFAULT NULL,
  `nume` varchar(100) DEFAULT NULL,
  `gen` char(1) DEFAULT NULL,
  `scoala` varchar(100) DEFAULT NULL,
  `localitate` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=61 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `evaluarenationala`
--

LOCK TABLES `evaluarenationala` WRITE;
/*!40000 ALTER TABLE `evaluarenationala` DISABLE KEYS */;
INSERT INTO `evaluarenationala` VALUES (1,16,10,9,9,'Ion Alexandrescu','M','20','Bucuresti'),(3,15,9,9,9,'Vlad Dumitru','M','15','Cluj-Napoca'),(4,16,10,8,9,'Eliza Stoica','F','25','Iasi'),(5,15,7,7,7,'Andrei Popa','M','12','Galati'),(6,14,8,7,8,'Gabriela Ionescu','F','17','Bucuresti'),(7,15,9,9,9,'Cristina Vasile','F','26','Iasi'),(8,14,8,7,7,'Nicolae Tudor','M','22','Galati'),(9,16,10,9,9,'Diana Petrescu','F','35','Cluj-Napoca'),(10,14,7,7,7,'Paul Radu','M','18','Iasi'),(11,15,9,7,8,'Laura Ionescu','F','40','Bucuresti'),(12,16,9,9,9,'Daciana Dima','F','32','Galati'),(13,16,8,9,8,'Cristian Dragan','M','19','Cluj-Napoca'),(14,15,9,7,8,'Monica Sandu','F','25','Bucuresti'),(15,16,10,9,9,'Stefan Munteanu','M','36','Iasi'),(16,15,7,7,7,'Simona Lungu','F','23','Cluj-Napoca'),(18,15,10,9,9,'Irina Vasilescu','F','24','Bucuresti'),(19,14,7,7,7,'George Stanciu','M','21','Iasi'),(20,15,9,8,8,'Ioana Moraru','F','29','Galati'),(21,16,10,9,10,'Radu Marinescu','M','10','Cluj-Napoca'),(22,15,3,3,3,'Maria Popescu','F','25','Bucuresti'),(23,17,8,9,10,'Mihai Ionescu','M','40','Iasi'),(27,16,10,9,9,'Ion Alexandrescu','M','20','Bucuresti'),(28,16,8,8,8,'Ana Georgescu','F','30','Bucuresti'),(29,15,9,8,9,'Vlad Dumitru','M','15','Cluj-Napoca'),(30,16,10,10,10,'Eliza Stoica','F','25','Iasi'),(32,14,8,7,8,'Gabriela Ionescu','F','17','Bucuresti'),(33,15,9,9,9,'Cristina Vasile','F','26','Iasi'),(34,14,8,7,7,'Nicolae Tudor','M','22','Galati'),(35,16,10,9,9,'Diana Petrescu','F','35','Cluj-Napoca'),(36,14,7,7,7,'Paul Radu','M','18','Iasi'),(37,15,9,7,8,'Laura Ionescu','F','40','Bucuresti'),(38,16,9,9,9,'Daciana Dima','F','32','Galati'),(40,15,9,7,8,'Monica Sandu','F','25','Bucuresti'),(41,16,10,9,9,'Stefan Munteanu','M','36','Iasi'),(42,15,7,7,7,'Simona Lungu','F','23','Cluj-Napoca'),(43,15,9,9,9,'Daniel Ionescu','M','29','Galati'),(44,15,10,9,9,'Irina Vasilescu','F','24','Bucuresti'),(45,14,7,7,7,'George Stanciu','M','21','Iasi'),(46,15,9,8,8,'Ioana Moraru','F','29','Galati'),(47,16,10,9,10,'Radu Marinescu','M','10','Cluj-Napoca'),(49,17,8,9,10,'Mihai Ionescu','M','40','Iasi'),(53,16,10,9,10,'Ion Alexandrescu','M','20','Bucuresti'),(55,16,10,9,9,'GG ggg','0','20','Bucuresti'),(56,16,10,9,9,'BBBB','M','20','Bucuresti'),(57,16,10,9,9,'aaaaa','M','20','Galati'),(59,16,10,9,9,'SSSSSSS','M','20','Bucuresti'),(60,16,10,9,9,'ssssss','M','20','Bucuresti');
/*!40000 ALTER TABLE `evaluarenationala` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-10-14 22:15:43
