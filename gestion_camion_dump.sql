-- MySQL dump 10.13  Distrib 8.0.45, for Win64 (x86_64)
--
-- Host: localhost    Database: gestion_camion
-- ------------------------------------------------------
-- Server version	8.0.45

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
INSERT INTO `cache` VALUES ('noexiste@x.com|127.0.0.1','i:1;',1782165873),('noexiste@x.com|127.0.0.1:timer','i:1782165873;',1782165873);
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `combustible`
--

DROP TABLE IF EXISTS `combustible`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `combustible` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `fecha` date NOT NULL,
  `litros` decimal(8,2) NOT NULL,
  `precio_litro` decimal(8,2) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `km_odometro` int DEFAULT NULL,
  `lugar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `medio_pago_id` bigint unsigned DEFAULT NULL,
  `fecha_vencimiento` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `combustible`
--

LOCK TABLES `combustible` WRITE;
/*!40000 ALTER TABLE `combustible` DISABLE KEYS */;
INSERT INTO `combustible` VALUES (1,'2026-06-20',21.43,2334.00,50017.62,NULL,'RUTA 302 KM 6 BANDA RIO SALÍ',5,'2026-07-13','2026-06-21 01:13:30'),(2,'2026-06-19',11.60,2334.00,27074.40,NULL,'RUTA 302 KM 6 BANDA RIO SALÍ',7,'2026-07-10','2026-06-22 22:18:31'),(3,'2026-06-21',17.14,2334.00,40004.76,NULL,'RUTA 302 KM 6 BANDA RIO SALÍ',8,'2026-07-25','2026-06-22 22:20:15'),(4,'2026-06-22',19.29,2334.00,45022.86,NULL,'RUTA 302 KM 6 BANDA RIO SALÍ',2,'2026-06-22','2026-06-22 22:26:20');
/*!40000 ALTER TABLE `combustible` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cuotas`
--

DROP TABLE IF EXISTS `cuotas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cuotas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `prestamo_id` bigint unsigned NOT NULL,
  `numero` int NOT NULL,
  `fecha_venc` date NOT NULL,
  `monto` decimal(12,2) NOT NULL,
  `pagada` tinyint(1) NOT NULL DEFAULT '0',
  `fecha_pago` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `cuotas_prestamo_id_foreign` (`prestamo_id`),
  CONSTRAINT `cuotas_prestamo_id_foreign` FOREIGN KEY (`prestamo_id`) REFERENCES `prestamos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=55 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cuotas`
--

LOCK TABLES `cuotas` WRITE;
/*!40000 ALTER TABLE `cuotas` DISABLE KEYS */;
INSERT INTO `cuotas` VALUES (16,3,1,'2026-07-10',3750000.00,0,NULL,'2026-06-22 23:44:22'),(17,3,2,'2026-08-10',3750000.00,0,NULL,'2026-06-22 23:44:22'),(18,3,3,'2026-09-10',3750000.00,0,NULL,'2026-06-22 23:44:22'),(19,3,4,'2026-10-10',3750000.00,0,NULL,'2026-06-22 23:44:22'),(20,4,1,'2026-05-26',608333.33,0,NULL,'2026-06-22 23:47:54'),(21,4,2,'2026-06-26',608333.33,0,NULL,'2026-06-22 23:47:54'),(22,4,3,'2026-07-26',608333.33,0,NULL,'2026-06-22 23:47:54'),(23,4,4,'2026-08-26',608333.33,0,NULL,'2026-06-22 23:47:54'),(24,4,5,'2026-09-26',608333.33,0,NULL,'2026-06-22 23:47:54'),(25,4,6,'2026-10-26',608333.33,0,NULL,'2026-06-22 23:47:54'),(26,4,7,'2026-11-26',608333.33,0,NULL,'2026-06-22 23:47:54'),(27,4,8,'2026-12-26',608333.33,0,NULL,'2026-06-22 23:47:54'),(28,4,9,'2027-01-26',608333.33,0,NULL,'2026-06-22 23:47:54'),(29,4,10,'2027-02-26',608333.33,0,NULL,'2026-06-22 23:47:54'),(30,4,11,'2027-03-26',608333.33,0,NULL,'2026-06-22 23:47:54'),(31,4,12,'2027-04-26',608333.33,0,NULL,'2026-06-22 23:47:54'),(32,4,13,'2027-05-26',608333.33,0,NULL,'2026-06-22 23:47:54'),(33,4,14,'2027-06-26',608333.33,0,NULL,'2026-06-22 23:47:54'),(34,4,15,'2027-07-26',608333.33,0,NULL,'2026-06-22 23:47:54'),(35,4,16,'2027-08-26',608333.33,0,NULL,'2026-06-22 23:47:54'),(36,4,17,'2027-09-26',608333.33,0,NULL,'2026-06-22 23:47:54'),(37,4,18,'2027-10-26',608333.33,0,NULL,'2026-06-22 23:47:54'),(38,4,19,'2027-11-26',608333.33,0,NULL,'2026-06-22 23:47:54'),(39,4,20,'2027-12-26',608333.33,0,NULL,'2026-06-22 23:47:54'),(40,4,21,'2028-01-26',608333.33,0,NULL,'2026-06-22 23:47:54'),(41,4,22,'2028-02-26',608333.33,0,NULL,'2026-06-22 23:47:54'),(42,4,23,'2028-03-26',608333.33,0,NULL,'2026-06-22 23:47:54'),(43,4,24,'2028-04-26',608333.33,0,NULL,'2026-06-22 23:47:54'),(44,5,1,'2026-06-16',450000.00,0,NULL,'2026-06-22 23:49:59'),(45,5,2,'2026-07-16',450000.00,0,NULL,'2026-06-22 23:49:59'),(46,5,3,'2026-08-16',450000.00,0,NULL,'2026-06-22 23:49:59'),(47,5,4,'2026-09-16',450000.00,0,NULL,'2026-06-22 23:49:59'),(48,5,5,'2026-10-16',450000.00,0,NULL,'2026-06-22 23:49:59'),(49,5,6,'2026-11-16',450000.00,0,NULL,'2026-06-22 23:49:59'),(50,6,1,'2026-07-16',58333.33,0,NULL,'2026-06-22 23:52:03'),(51,6,2,'2026-08-16',58333.33,0,NULL,'2026-06-22 23:52:03'),(52,6,3,'2026-09-16',58333.33,0,NULL,'2026-06-22 23:52:03'),(53,7,1,'2026-07-10',100000.00,0,NULL,'2026-06-23 00:00:52'),(54,7,2,'2026-08-10',100000.00,0,NULL,'2026-06-23 00:00:52');
/*!40000 ALTER TABLE `cuotas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mantenimiento`
--

DROP TABLE IF EXISTS `mantenimiento`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mantenimiento` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `fecha` date NOT NULL,
  `tipo` enum('aceite','filtros','neumaticos','frenos','repuesto','service','otro') COLLATE utf8mb4_unicode_ci NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `km_actuales` int DEFAULT NULL,
  `proximo_service` int DEFAULT NULL,
  `detalle` text COLLATE utf8mb4_unicode_ci,
  `medio_pago_id` bigint unsigned DEFAULT NULL,
  `fecha_vencimiento` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mantenimiento`
--

LOCK TABLES `mantenimiento` WRITE;
/*!40000 ALTER TABLE `mantenimiento` DISABLE KEYS */;
INSERT INTO `mantenimiento` VALUES (1,'2026-06-20','repuesto',117500.00,NULL,NULL,'Bomba de embrague nueva + pico bomba + 1l liquido freno',5,'2026-07-13','2026-06-21 01:15:18'),(2,'2026-05-29','service',268000.00,NULL,NULL,'Instalación luces costado y traseras camión, también dos focos de luz interna y luz superior',5,'2026-07-13','2026-06-21 01:16:09'),(3,'2026-05-29','repuesto',165500.00,NULL,NULL,'Compra focos, cables, fichas, conectores, leds',5,'2026-07-13','2026-06-21 01:16:33'),(4,'2026-06-02','repuesto',70000.00,NULL,NULL,'kit de reparación de servo de embrague',5,'2026-07-13','2026-06-21 01:17:34'),(5,'2026-05-28','repuesto',0.00,NULL,NULL,'Cambio de bomba de dirección hidraulica',NULL,'2026-05-28','2026-06-21 01:17:56'),(6,'2026-06-02','service',60000.00,NULL,NULL,'Reparación de motor limpiaparabrisas',2,'2026-06-02','2026-06-21 01:18:25'),(7,'2026-06-20','neumaticos',30000.00,NULL,NULL,'Parche llanta acoplado',5,'2026-07-13','2026-06-21 01:19:10');
/*!40000 ALTER TABLE `mantenimiento` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `medios_pago`
--

DROP TABLE IF EXISTS `medios_pago`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `medios_pago` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `dia_cierre` tinyint unsigned DEFAULT NULL,
  `dia_vencimiento` tinyint unsigned DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `medios_pago`
--

LOCK TABLES `medios_pago` WRITE;
/*!40000 ALTER TABLE `medios_pago` DISABLE KEYS */;
INSERT INTO `medios_pago` VALUES (1,'Efectivo','efectivo',NULL,NULL,1,'2026-06-22 23:03:24'),(2,'Transferencia','transferencia',NULL,NULL,1,'2026-06-22 23:03:24'),(5,'Visa BBVA','credito',2,13,1,'2026-06-22 23:19:19'),(6,'Mercado Pago Maxi','credito',16,16,1,'2026-06-22 23:20:03'),(7,'Cuenta Corriente (Ezequiel)','credito',10,10,1,'2026-06-22 23:21:21'),(8,'Saldo Antonio (chofer)','credito',25,25,1,'2026-06-22 23:23:43'),(10,'Credito BNA','credito',26,26,1,'2026-06-22 23:46:53'),(11,'Mercado Pago Male','credito',16,16,1,'2026-06-22 23:51:29');
/*!40000 ALTER TABLE `medios_pago` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2026_06_18_211858_create_viajes_table',1),(5,'2026_06_18_211859_create_combustible_table',1),(6,'2026_06_18_211859_create_mantenimiento_table',1),(7,'2026_06_20_001705_add_fecha_carga_to_viajes_table',1),(8,'2026_06_20_002218_add_tipo_ingreso_motivo_and_datetime_to_viajes_table',1),(9,'2026_06_22_000001_create_medios_pago_table',1),(10,'2026_06_22_000002_create_prestamos_table',1),(11,'2026_06_22_000003_create_cuotas_table',1),(12,'2026_06_22_000004_add_medio_pago_to_combustible_table',1),(13,'2026_06_22_000005_add_medio_pago_to_mantenimiento_table',1);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `prestamos`
--

DROP TABLE IF EXISTS `prestamos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `prestamos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `descripcion` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `categoria` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'camion',
  `monto_total` decimal(12,2) NOT NULL,
  `cantidad_cuotas` int NOT NULL,
  `valor_cuota` decimal(12,2) NOT NULL,
  `dia_vencimiento` tinyint unsigned NOT NULL,
  `fecha_primera_cuota` date NOT NULL,
  `medio_pago_id` bigint unsigned DEFAULT NULL,
  `observaciones` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `prestamos`
--

LOCK TABLES `prestamos` WRITE;
/*!40000 ALTER TABLE `prestamos` DISABLE KEYS */;
INSERT INTO `prestamos` VALUES (3,'Financiación Camión','camion',15000000.00,4,3750000.00,10,'2026-07-10',7,NULL,'2026-06-22 23:44:22'),(4,'Credito BNA','camion',14600000.00,24,608333.33,26,'2026-05-26',10,NULL,'2026-06-22 23:47:54'),(5,'Mercado Pago Maxi','camion',2700000.00,6,450000.00,16,'2026-06-16',6,NULL,'2026-06-22 23:49:59'),(6,'Mercado Credito Male','camion',175000.00,3,58333.33,16,'2026-07-16',11,NULL,'2026-06-22 23:52:03'),(7,'Prestamo Marita','personal',200000.00,2,100000.00,10,'2026-07-10',2,NULL,'2026-06-23 00:00:52');
/*!40000 ALTER TABLE `prestamos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
INSERT INTO `sessions` VALUES ('8aV4aRLVNuc0Qnn3gkYcPqS6oT7jQRdChkQ2hs6H',NULL,'127.0.0.1','curl/8.18.0','YTozOntzOjY6Il90b2tlbiI7czo0MDoiajdCWWhPQklQOXlHbXJIRVdZSGpWdmdSTXRhNFBpVXI2aktIVkhvQyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjc6Imh0dHA6Ly8xMjcuMC4wLjE6ODA4MC9sb2dpbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=',1782165757),('8JraqqKhz39P2kZRBloE25bDx2BYsoXXPcVXNsTU',NULL,'127.0.0.1','curl/8.18.0','YTozOntzOjY6Il90b2tlbiI7czo0MDoibG0xZWRqclFzRmZEdzBITzBQdlM4RXRFWDd2d2pieU1pTGVYQnRwZyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjg6Imh0dHA6Ly8xMjcuMC4wLjE6ODA4MC92aWFqZXMiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1782162951),('biGLi1EFbk2JvO3RfIaGGtdQpuE8oB8lvXPI7BnH',NULL,'127.0.0.1','curl/8.18.0','YTozOntzOjY6Il90b2tlbiI7czo0MDoiblYzVm1QNTlhUW4wYmJCNFViSk5sbnVUcFZJcnZGRm03UnNKQlZXTCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjc6Imh0dHA6Ly8xMjcuMC4wLjE6ODA4MC9wYWdvcyI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=',1782162952),('bwBLrWYAWqQvNl5UDErj6yKsdHsrG1Oio5qgZfIV',NULL,'127.0.0.1','curl/8.18.0','YTozOntzOjY6Il90b2tlbiI7czo0MDoiQWtSZ2pLMk15eEQwa2FrQnBoc05HRDZ3NVFKaVdUWWRwTmhKZkl4QyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly8xMjcuMC4wLjE6ODA4MCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=',1782164950),('crNOtKAgl7qAVn1jI2ThRJueShW8kkO6nil5rtcu',2,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','YTo1OntzOjY6Il90b2tlbiI7czo0MDoiZG4yMmF4Q0tpYzVPNzRwZW1BYWFnaDFTN0dnRXhZQW5OSjBZaVFHWCI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzA6Imh0dHA6Ly8xMjcuMC4wLjE6ODA4MC9yZXBvcnRlcyI7fXM6MzoidXJsIjthOjA6e31zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aToyO30=',1782165904),('dBs3ncjBDk28uqtryNB0VTWUw8f3DRDrhBfOmwnO',NULL,'127.0.0.1','curl/8.18.0','YTozOntzOjY6Il90b2tlbiI7czo0MDoibndreWVDM0UxS0xUQ0hteFlXcVg5QWRmWGlpUWN6VDZOYW1ucmZoZCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzA6Imh0dHA6Ly8xMjcuMC4wLjE6ODA4MC9yZXBvcnRlcyI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=',1782162953),('dQaWtUdgL1yfiCzZ6oySlFUXscgidXqccFLvMEQ5',NULL,'127.0.0.1','curl/8.18.0','YTozOntzOjY6Il90b2tlbiI7czo0MDoieDVGZTB4TU1SN1pWU3Jla3JXWWVEUXJLbWpoamZDQUNlbHZaNkpiWSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjc6Imh0dHA6Ly8xMjcuMC4wLjE6ODA4MC9sb2dpbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=',1782165703),('LeIpRJl8DSLJhqeurrqzRo7z3XNuFoz7w64xruy0',NULL,'127.0.0.1','curl/8.18.0','YTozOntzOjY6Il90b2tlbiI7czo0MDoib3RkRzA4cXduRzI0d3RrOEgzUDRXT2pxcXdxMHZyOEFtME5KajY2NSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzM6Imh0dHA6Ly8xMjcuMC4wLjE6ODA4MC9jb21idXN0aWJsZSI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=',1782162951),('nbRUgWCvIvWtiPEMyyC03aqhltcWwYfPwVrXXmeN',NULL,'127.0.0.1','curl/8.18.0','YTozOntzOjY6Il90b2tlbiI7czo0MDoiWkp5SGk4RTJseTZhVlRPVUs5TldJZ0pYT2YwdzRia3htQUMzMEhXTSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjc6Imh0dHA6Ly8xMjcuMC4wLjE6ODA4MC9sb2dpbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=',1782165075),('nJjsWPLmQ644mS3pkvD0rx07NqDYis0Xnhp4YhPT',NULL,'127.0.0.1','curl/8.18.0','YTozOntzOjY6Il90b2tlbiI7czo0MDoiUWZWTzhFSlJ1RXV1NGtXU3ZrWjZ2Y0pKazd6VWVoRDhwQ2RzYmdobyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzA6Imh0dHA6Ly8xMjcuMC4wLjE6ODA4MC9yZWdpc3RlciI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=',1782165076),('Oe44XltLG3jcaCHJNC3F9WPZ8Vmokkx9ZE0UDLJN',NULL,'127.0.0.1','curl/8.18.0','YToyOntzOjY6Il90b2tlbiI7czo0MDoiTnNGODdLMWFPZmxiVW9SSlNwZFEwMmFpSUs4VW1Yc1ZVdmo2cmlmTCI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1782164985),('q0VZgeP5ldGGj0LnQsDpwb5FkLDQRMvVHMXtQqaW',NULL,'127.0.0.1','curl/8.18.0','YTozOntzOjY6Il90b2tlbiI7czo0MDoiZDRLV2ZXTFRjNFpyUkdLMUdDRTU1Unp2c25maGVPVFJZU3pybEZVNSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjc6Imh0dHA6Ly8xMjcuMC4wLjE6ODA4MC9sb2dpbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=',1782164951),('QKJYGGwrnlWscWZ71I5B4IEI9fr1w9hy5n1ATZG6',NULL,'127.0.0.1','curl/8.18.0','YTo0OntzOjY6Il90b2tlbiI7czo0MDoiZEpYdTc0QWI4SXhHU3ZxM1hOMzk3WHBnU0VkRWVocDVueGRTRk1seSI7czozOiJ1cmwiO2E6MTp7czo4OiJpbnRlbmRlZCI7czoyODoiaHR0cDovLzEyNy4wLjAuMTo4MDgwL3ZpYWplcyI7fXM6OToiX3ByZXZpb3VzIjthOjE6e3M6MzoidXJsIjtzOjI4OiJodHRwOi8vMTI3LjAuMC4xOjgwODAvdmlhamVzIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1782164985),('quMSlT2JBqI0cSBh7uPujD7lK8JNd5MozYUZRxhs',NULL,'127.0.0.1','curl/8.18.0','YTo0OntzOjY6Il90b2tlbiI7czo0MDoiMnNnNDI5ZEF1cjA0eFh6QjdCMUE1ajN0N0JpQTF3a2tTS04wa3N6cyI7czozOiJ1cmwiO2E6MTp7czo4OiJpbnRlbmRlZCI7czoyODoiaHR0cDovLzEyNy4wLjAuMTo4MDgwL3ZpYWplcyI7fXM6OToiX3ByZXZpb3VzIjthOjE6e3M6MzoidXJsIjtzOjI4OiJodHRwOi8vMTI3LjAuMC4xOjgwODAvdmlhamVzIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1782164950),('qxi3W4q1e7JRsm0GBgBcxsluWvsqdVNHqNFloo7B',NULL,'127.0.0.1','curl/8.18.0','YTozOntzOjY6Il90b2tlbiI7czo0MDoiVHEwcnVOODI1Q0t0ZDFod3JQcXBFWmZOV1loV0lJSzhRaW5RWGhETCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjc6Imh0dHA6Ly8xMjcuMC4wLjE6ODA4MC9sb2dpbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=',1782164951),('rLXS4I8WjEikCgvJEEzfViPiy9Cti4qtLSgJJIX9',NULL,'127.0.0.1','curl/8.18.0','YTozOntzOjY6Il90b2tlbiI7czo0MDoiUThFY1FuM0RVZm1pU1pOVXJ4aFdwcU0zYVNQemZGM3c3Rjk3c0Y0RSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjc6Imh0dHA6Ly8xMjcuMC4wLjE6ODA4MC9sb2dpbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=',1782165742),('v9ZELPK5r4WebTWaBeLQuavfvkeyR0KCBZ9mTdex',NULL,'127.0.0.1','curl/8.18.0','YTozOntzOjY6Il90b2tlbiI7czo0MDoic1VhaW83QnhTdk5PM2NoQlBBa2FWbnRZVjlVZWQ3aTNxSXFCWnlvNyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzU6Imh0dHA6Ly8xMjcuMC4wLjE6ODA4MC9tYW50ZW5pbWllbnRvIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1782162951),('VpneaNTufQNVwrJESmyS3B4a89rKSjsLzzH0UbPp',NULL,'127.0.0.1','curl/8.18.0','YTozOntzOjY6Il90b2tlbiI7czo0MDoiWmUxVWJoNjd0TFZkUjdLNUxjV1BSWTV6TjRpdGxQRGE3QzZGYTQ2cSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzE6Imh0dHA6Ly8xMjcuMC4wLjE6ODA4MC9wcmVzdGFtb3MiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1782162952),('w19bSm3grRVFcsjQAB9RZjDQhzL1ymX8ow0R6lWN',NULL,'127.0.0.1','curl/8.18.0','YTozOntzOjY6Il90b2tlbiI7czo0MDoibUxFM0tZd0ZqUnJ2dFNTb3FDU29hSkdtaDFRaFhFMEl1cVFQaVFhOSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjc6Imh0dHA6Ly8xMjcuMC4wLjE6ODA4MC9sb2dpbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=',1782165813),('YkscQwuiHBfQS8Vq42SJTaaozDXc4aQRC8r4FpD6',NULL,'127.0.0.1','curl/8.18.0','YTozOntzOjY6Il90b2tlbiI7czo0MDoiNkhhOEl2Tlo1T3pVczRHa3JBa1ZWNWhzZDFrNXNidzN2ZXNURUlPVCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6Mjc6Imh0dHA6Ly8xMjcuMC4wLjE6ODA4MC9wYWdvcyI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=',1782162971),('ZRjJESuNGFiElKnZOArc9l1W7354SxlnQ6QQ93jw',NULL,'127.0.0.1','curl/8.18.0','YTozOntzOjY6Il90b2tlbiI7czo0MDoiS0QxSUNuYTNSQU5OaEd5YVpmb2ZyNG85MHIxMlY5bEUzT0tzUnNYbSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzM6Imh0dHA6Ly8xMjcuMC4wLjE6ODA4MC9tZWRpb3MtcGFnbyI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=',1782162952);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (2,'Maximiliano','rivadenneira@gmail.com',NULL,'$2y$12$ijdsTgdhWwd2AtE/7Eqm..vmnCKaHO6ZE5RcIwYqDxon5d5nOuF6u',NULL,'2026-06-22 21:59:32','2026-06-22 21:59:32');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `viajes`
--

DROP TABLE IF EXISTS `viajes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `viajes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `fecha` datetime NOT NULL,
  `fecha_carga` date DEFAULT NULL,
  `nro_ingreso` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tipo_ingreso` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `motivo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bolsas` int NOT NULL,
  `precio_bolsa` decimal(10,2) NOT NULL,
  `total` decimal(12,2) NOT NULL,
  `kg_netos` decimal(10,2) DEFAULT NULL,
  `destino` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `observaciones` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `viajes`
--

LOCK TABLES `viajes` WRITE;
/*!40000 ALTER TABLE `viajes` DISABLE KEYS */;
INSERT INTO `viajes` VALUES (2,'2026-06-18 17:45:00','2026-06-20','323','Retiro Ingenio','Transferencias Internas',800,280.00,224000.00,40000.00,'DEP. CONTROL UNION - SCANIA','PRODUCTO: AZUCAR CTA MARCA CONCEPCION EN BOLSAS DE POLIPROPILENO DE 50KG','2026-06-20 06:27:32'),(3,'2026-06-20 18:51:00','2026-06-21','606','Retiro Ingenio','Transferencias Internas',800,280.00,224000.00,40000.00,'CONTROL UNION - SCANIA','PRODUCTO: AZUCAR CTA MARCA CONCEPCION EN BOLSAS DE POLIPROPILENO DE 50KG','2026-06-22 22:13:48'),(4,'2026-06-21 15:46:00','2026-06-21','644','Retiro Ingenio','Transferencias Internas',800,280.00,224000.00,40000.00,'CONTROL UNION - SCANIA','Producto: AZUCAR CTA MARCA CONCEPCION EN BOLSAS DE POLIPROPILENO DE 50KG','2026-06-22 22:15:59');
/*!40000 ALTER TABLE `viajes` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-06-22 19:38:25
