-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: bookstore
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
-- Table structure for table `address`
--

DROP TABLE IF EXISTS `address`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `address` (
  `AddressID` int(11) NOT NULL AUTO_INCREMENT,
  `CustomerID` int(11) DEFAULT NULL,
  `ReceiverName` varchar(100) NOT NULL,
  `Phone` varchar(20) NOT NULL,
  `FullAddress` text NOT NULL,
  `IsDefault` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`AddressID`),
  KEY `fk_address_user` (`CustomerID`),
  CONSTRAINT `fk_address_user` FOREIGN KEY (`CustomerID`) REFERENCES `user` (`CustomerID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `address`
--

LOCK TABLES `address` WRITE;
/*!40000 ALTER TABLE `address` DISABLE KEYS */;
/*!40000 ALTER TABLE `address` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cart`
--

DROP TABLE IF EXISTS `cart`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cart` (
  `CartID` int(11) NOT NULL AUTO_INCREMENT,
  `CustomerID` int(11) DEFAULT NULL,
  `CreatedDate` datetime DEFAULT current_timestamp(),
  `Status` enum('Active','Abandoned','Completed') DEFAULT 'Active',
  `Note` text DEFAULT NULL,
  PRIMARY KEY (`CartID`),
  KEY `fk_cart_user` (`CustomerID`),
  CONSTRAINT `fk_cart_user` FOREIGN KEY (`CustomerID`) REFERENCES `user` (`CustomerID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cart`
--

LOCK TABLES `cart` WRITE;
/*!40000 ALTER TABLE `cart` DISABLE KEYS */;
INSERT INTO `cart` VALUES (1,19,'2026-08-28 18:41:15','Active',NULL),(2,20,'2026-08-28 18:49:17','Completed',NULL),(3,18,'2026-08-28 20:16:37','Completed',NULL),(4,18,'2026-08-28 20:48:36','Completed',NULL),(5,18,'2026-08-28 20:50:08','Completed',NULL),(6,18,'2026-08-28 21:16:10','Completed',NULL),(7,21,'2026-08-29 19:14:09','Completed',NULL),(8,18,'2026-08-29 22:55:23','Completed',NULL),(9,21,'2026-08-31 19:04:13','Active',NULL),(10,20,'2026-08-31 19:17:05','Active',NULL),(11,18,'2026-08-31 19:21:05','Completed',NULL),(12,18,'2026-08-31 19:43:36','Active',NULL),(13,22,'2026-09-01 15:17:08','Completed',NULL),(14,22,'2026-09-01 15:18:58','Completed',NULL),(15,22,'2026-09-01 15:21:26','Active',NULL);
/*!40000 ALTER TABLE `cart` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cart_detail`
--

DROP TABLE IF EXISTS `cart_detail`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cart_detail` (
  `CartID` int(11) NOT NULL,
  `ProductID` int(11) NOT NULL,
  `SizeID` int(11) DEFAULT NULL,
  `Quantity` int(11) NOT NULL DEFAULT 1,
  `AddedAt` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`CartID`,`ProductID`),
  KEY `fk_cartdetail_product` (`ProductID`),
  CONSTRAINT `fk_cartdetail_cart` FOREIGN KEY (`CartID`) REFERENCES `cart` (`CartID`) ON DELETE CASCADE,
  CONSTRAINT `fk_cartdetail_product` FOREIGN KEY (`ProductID`) REFERENCES `product` (`ProductID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cart_detail`
--

LOCK TABLES `cart_detail` WRITE;
/*!40000 ALTER TABLE `cart_detail` DISABLE KEYS */;
INSERT INTO `cart_detail` VALUES (10,28,NULL,1,'2026-08-31 19:17:25');
/*!40000 ALTER TABLE `cart_detail` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `category`
--

DROP TABLE IF EXISTS `category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `category` (
  `CategoryID` int(11) NOT NULL AUTO_INCREMENT,
  `CategoryName` varchar(100) NOT NULL,
  `Description` text DEFAULT NULL,
  PRIMARY KEY (`CategoryID`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `category`
--

LOCK TABLES `category` WRITE;
/*!40000 ALTER TABLE `category` DISABLE KEYS */;
INSERT INTO `category` VALUES (1,'Văn học Việt Nam','Tiểu thuyết, truyện ngắn trong nước'),(2,'Văn học Nước ngoài','Tiểu thuyết, truyện dịch'),(3,'Kinh tế','Sách kinh doanh, quản trị, đầu tư'),(4,'Tâm lý - Kỹ năng sống','Sách phát triển bản thân'),(5,'Khoa học Công nghệ','Sách CNTT, mạng máy tính, lập trình'),(6,'Thiếu nhi','Truyện tranh, sách khám phá cho trẻ em'),(7,'Lịch sử','Sách về các triều đại, sự kiện lịch sử'),(8,'Triết học','Sách tư tưởng, triết học Mác-Lênin, phương Tây'),(9,'Ngoại ngữ','Sách học tiếng Anh, Nhật, Hàn...'),(10,'Giáo trình','Tài liệu học tập cấp Đại học');
/*!40000 ALTER TABLE `category` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `delivery`
--

DROP TABLE IF EXISTS `delivery`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `delivery` (
  `DeliveryID` int(11) NOT NULL AUTO_INCREMENT,
  `OrderID` int(11) DEFAULT NULL,
  `DeliveryStatus` enum('Preparing','Shipping','Delivered','Failed') DEFAULT 'Preparing',
  `DeliveryDate` datetime DEFAULT NULL,
  `ShippingFee` decimal(10,2) DEFAULT 0.00,
  PRIMARY KEY (`DeliveryID`),
  KEY `fk_delivery_order` (`OrderID`),
  CONSTRAINT `fk_delivery_order` FOREIGN KEY (`OrderID`) REFERENCES `order` (`OrderID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `delivery`
--

LOCK TABLES `delivery` WRITE;
/*!40000 ALTER TABLE `delivery` DISABLE KEYS */;
INSERT INTO `delivery` VALUES (1,1,'Preparing',NULL,0.00),(2,2,'Preparing',NULL,0.00),(3,3,'Delivered','2026-08-28 21:18:31',0.00),(4,4,'Preparing',NULL,0.00),(5,5,'Preparing',NULL,0.00),(6,6,'Preparing',NULL,0.00),(7,7,'Preparing',NULL,0.00),(8,8,'Preparing',NULL,0.00),(9,9,'Delivered','2026-09-01 15:21:45',0.00),(10,10,'Preparing',NULL,0.00);
/*!40000 ALTER TABLE `delivery` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `image`
--

DROP TABLE IF EXISTS `image`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `image` (
  `ImageID` int(11) NOT NULL AUTO_INCREMENT,
  `ProductID` int(11) DEFAULT NULL,
  `ImageURL` varchar(255) NOT NULL,
  `AltText` varchar(255) DEFAULT NULL,
  `IsThumbnail` tinyint(1) DEFAULT 0,
  `SortOrder` int(11) DEFAULT 0,
  PRIMARY KEY (`ImageID`),
  KEY `fk_image_product` (`ProductID`),
  CONSTRAINT `fk_image_product` FOREIGN KEY (`ProductID`) REFERENCES `product` (`ProductID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `image`
--

LOCK TABLES `image` WRITE;
/*!40000 ALTER TABLE `image` DISABLE KEYS */;
INSERT INTO `image` VALUES (1,1,'https://salt.tikicdn.com/ts/product/5e/18/24/2a6154ba08df6ce6161c13f4303fa19e.jpg','Bìa sách Cây Cam Ngọt Của Tôi',1,1),(2,2,'https://salt.tikicdn.com/ts/product/d7/99/24/17bff8d26027846b2d7478ad4ba83fea.jpg','Bìa sách Hành Tinh Của Một Kẻ Nghĩ Nhiều',1,1),(3,3,'https://salt.tikicdn.com/ts/product/8d/96/9e/c0c1f23db756d50b1944dff9c3988753.jpg','Bìa sách Những Tù Nhân Của Địa Lý',1,1),(4,4,'https://salt.tikicdn.com/ts/product/45/3b/fc/aa81d0a534b45706ae1eee1e344e80d9.jpg','Bìa sách Nhà Giả Kim (Tái Bản 2020)',1,1),(5,5,'https://salt.tikicdn.com/ts/product/2f/b5/4e/a8208e9019c8510e8a8eebe06f50299c.jpg','Bìa sách Một Thoáng Ta Rực Rỡ Ở Nhân Gian',1,1),(6,6,'https://salt.tikicdn.com/ts/product/dd/49/7f/ab94b8b2e35c49fc38b063fae4e8266a.jpg','Bìa sách Điều Kỳ Diệu Của Tiệm Tạp Hóa NAMIYA (Tái Bản)',1,1),(7,7,'https://salt.tikicdn.com/ts/product/90/49/97/ec88ab408c1997378344486c94dbac40.jpg','Bìa sách Thao Túng Tâm Lý',1,1),(8,8,'https://salt.tikicdn.com/ts/product/8e/32/3d/e4487c4c7e335bbda4f06dd54d8e35b8.jpg','Bìa sách Thư Viện Nửa Đêm',1,1),(9,9,'https://salt.tikicdn.com/ts/product/5b/96/23/348b84ca8e0d0b49bf9c8d9595336e69.jpg','Bìa sách Không Phải Sói Nhưng Cũng Đừng Là Cừu',1,1),(10,10,'https://salt.tikicdn.com/ts/product/ca/b9/76/3496cc7fd438e19e0a5732cba25e0aee.png','Bìa sách Càng Bình Tĩnh Càng Hạnh Phúc',1,1),(11,11,'https://salt.tikicdn.com/ts/product/5b/96/23/348b84ca8e0d0b49bf9c8d9595336e69.jpg','Bìa sách Không Phải Sói Nhưng Cũng Đừng Là Cừu',1,1),(12,12,'https://salt.tikicdn.com/ts/product/54/55/d6/4ceb6ba3bd81614df8ff4c1411b11f59.jpg','Bìa sách Yêu Những Điều Không Hoàn Hảo',1,1),(13,13,'https://salt.tikicdn.com/media/catalog/producttmp/35/e9/f2/4fc9547b96ed1e6a449ce4e06edb9010.jpg','Bìa sách Hiểu Về Trái Tim (Tái Bản)',1,1),(14,14,'https://salt.tikicdn.com/ts/product/6c/87/e7/db72a8050a15d86d8102cd21ab1d8b11.jpg','Bìa sách Quyền Lực Của Địa Lý',1,1),(15,15,'https://salt.tikicdn.com/ts/product/a1/ef/4f/0b39e40dca3827604c8bc4e867cc9423.jpg','Bìa sách Chiến Binh Cầu Vồng (Tái Bản 2020)',1,1),(16,16,'https://salt.tikicdn.com/ts/product/38/bb/4b/5f03392a2fb98bc19b63de688b2ad218.jpg','Bìa sách Thiên Tài Bên Trái, Kẻ Điên Bên Phải',1,1),(17,17,'https://salt.tikicdn.com/ts/product/2b/84/ff/cb34637573525a998596b58d6939411e.jpg','Bìa sách How Psychology Works',1,1),(18,18,'https://salt.tikicdn.com/ts/product/f7/fb/9a/e8b9a94478dc887c4b84b6b6439f6335.jpg','Bìa sách Đại Dương Đen',1,1),(19,19,'https://salt.tikicdn.com/ts/product/45/6c/b1/1d809c7be82ee19ca6b7ddcb18a494bc.jpg','Bìa sách Cú Săn Đêm',1,1),(20,20,'https://salt.tikicdn.com/ts/product/0c/49/5f/751b9c711c12def9beb2a3bbc3290d77.jpg','Bìa sách Vị Thần Của Những Quyết Định',1,1),(21,21,'https://salt.tikicdn.com/ts/product/5c/e7/68/26838e18d7f96d562d828980520019d2.jpg','Bìa sách Hoàng Tử Bé (Tái Bản 2019)',1,1),(22,22,'https://salt.tikicdn.com/media/catalog/producttmp/df/75/ac/207386a97daf1b2338598005cc8139c9.jpg','Bìa sách Muôn Kiếp Nhân Sinh',1,1),(23,23,'https://salt.tikicdn.com/ts/product/7a/18/8e/2f70de3ea7eec9c34f55e402254e27ed.jpg','Bìa sách Bước Chậm Lại Giữa Thế Gian Vội Vã',1,1),(24,24,'https://salt.tikicdn.com/ts/product/24/39/01/1718d16b33315c03026cee717adad4b3.jpg','Bìa sách Totto - Chan Bên Cửa Sổ',1,1),(25,25,'https://salt.tikicdn.com/media/catalog/producttmp/19/3a/7d/c0ba28d461ef53622b58fddbdbe0c47d.jpg','Bìa sách Thay Đổi Cuộc Sống Với Nhân Số Học',1,1),(26,26,'https://salt.tikicdn.com/ts/product/16/63/e2/b9e75fdf59ba03521829dfe7e2f2034b.jpg','Bìa sách Những Người Khốn Khổ',1,1),(27,27,'https://salt.tikicdn.com/ts/product/37/3b/5d/5efd681d48d5a81e3e6675121e69d38d.jpg','Bìa sách Trăm Năm Cô Đơn',1,1),(28,28,'https://salt.tikicdn.com/ts/product/36/48/39/2ef073f6a40a268deb12ecc35a6b1145.jpg','Bìa sách Dịch Hạch',1,1),(29,29,'https://salt.tikicdn.com/ts/product/46/4d/81/dadc621baaa9d45b52a444616d8e3cb8.jpg','Bìa sách Luật Tâm Thức',1,1),(30,30,'https://salt.tikicdn.com/ts/product/74/11/ff/6304c47fec56e6f0b2110be65af0c7c2.jpg','Bìa sách Dám Bị Ghét',1,1),(31,31,'https://salt.tikicdn.com/ts/product/19/4f/27/99b31589a12ac561e769081c4eb32d1f.jpg','Bìa sách Chú Bé Mang Pyjama Sọc',1,1),(32,32,'https://salt.tikicdn.com/ts/product/c9/e4/18/a9cfc425fa590c453f20307229804bb3.jpg','Bìa sách Rừng Nauy',1,1),(33,33,'https://salt.tikicdn.com/ts/product/cd/2a/5b/926ca6c7b295c6e7cea39390efe08968.jpg','Bìa sách Sự Im Lặng Của Bầy Cừu',1,1),(34,34,'https://salt.tikicdn.com/ts/product/85/b4/a3/dc8b8311f434cc946563963bc8e30071.jpg','Bìa sách Bố Con Cá Gai',1,1),(35,35,'https://salt.tikicdn.com/ts/product/b4/f0/81/5d5e4a26cb029fdecd04e0c30cbef17a.jpg','Bìa sách Xứ Cát',1,1);
/*!40000 ALTER TABLE `image` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order`
--

DROP TABLE IF EXISTS `order`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `order` (
  `OrderID` int(11) NOT NULL AUTO_INCREMENT,
  `CustomerID` int(11) DEFAULT NULL,
  `EmployeeID` int(11) DEFAULT NULL,
  `VoucherID` int(11) DEFAULT NULL,
  `OrderDate` datetime DEFAULT current_timestamp(),
  `ShippingAddress` text NOT NULL,
  `OrderStatus` enum('Pending','Processing','Shipped','Delivered','Cancelled') DEFAULT 'Pending',
  `TotalAmount` decimal(15,2) NOT NULL,
  `Note` text DEFAULT NULL,
  PRIMARY KEY (`OrderID`),
  KEY `fk_order_user` (`CustomerID`),
  KEY `fk_order_voucher` (`VoucherID`),
  KEY `fk_order_employee` (`EmployeeID`),
  CONSTRAINT `fk_order_employee` FOREIGN KEY (`EmployeeID`) REFERENCES `user` (`CustomerID`) ON DELETE SET NULL,
  CONSTRAINT `fk_order_user` FOREIGN KEY (`CustomerID`) REFERENCES `user` (`CustomerID`) ON DELETE CASCADE,
  CONSTRAINT `fk_order_voucher` FOREIGN KEY (`VoucherID`) REFERENCES `voucher` (`VoucherID`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order`
--

LOCK TABLES `order` WRITE;
/*!40000 ALTER TABLE `order` DISABLE KEYS */;
INSERT INTO `order` VALUES (1,18,NULL,5,'2026-08-28 20:48:25','123','Pending',340000.00,NULL),(2,18,NULL,10,'2026-08-28 20:48:49','123','Processing',59000.00,NULL),(3,18,NULL,NULL,'2026-08-28 21:11:19','123','Delivered',213300.00,'123'),(4,18,NULL,NULL,'2026-08-28 21:16:26','123','Processing',221000.00,'123'),(5,20,NULL,NULL,'2026-08-28 21:19:34','456','Pending',355501.00,'456'),(6,21,NULL,NULL,'2026-08-29 20:11:10','123','Processing',149400.00,NULL),(7,18,NULL,NULL,'2026-08-31 18:59:26','123','Processing',149400.00,NULL),(8,18,NULL,NULL,'2026-08-31 19:31:07','123','Pending',211800.00,NULL),(9,22,NULL,2,'2026-09-01 15:18:34','Số 2 Võ Oanh','Delivered',166600.00,NULL),(10,22,NULL,9,'2026-09-01 15:19:12','Số 2 Võ Oanh','Processing',95300.00,NULL);
/*!40000 ALTER TABLE `order` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_detail`
--

DROP TABLE IF EXISTS `order_detail`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `order_detail` (
  `OrderID` int(11) NOT NULL,
  `ProductID` int(11) NOT NULL,
  `SizeID` int(11) DEFAULT NULL,
  `Quantity` int(11) NOT NULL,
  `Price` int(11) NOT NULL DEFAULT 0,
  `UnitPrice` decimal(15,2) NOT NULL,
  PRIMARY KEY (`OrderID`,`ProductID`),
  KEY `fk_orderdetail_product` (`ProductID`),
  CONSTRAINT `fk_orderdetail_order` FOREIGN KEY (`OrderID`) REFERENCES `order` (`OrderID`) ON DELETE CASCADE,
  CONSTRAINT `fk_orderdetail_product` FOREIGN KEY (`ProductID`) REFERENCES `product` (`ProductID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_detail`
--

LOCK TABLES `order_detail` WRITE;
/*!40000 ALTER TABLE `order_detail` DISABLE KEYS */;
INSERT INTO `order_detail` VALUES (1,14,NULL,1,221000,221000.00),(1,32,NULL,1,90000,90000.00),(1,33,NULL,1,69000,69000.00),(2,32,NULL,1,90000,90000.00),(2,33,NULL,1,69000,69000.00),(3,22,NULL,1,72000,72000.00),(3,25,NULL,1,141300,141300.00),(4,14,NULL,1,221000,221000.00),(5,20,NULL,1,55100,55100.00),(5,28,NULL,1,77400,77400.00),(5,29,NULL,1,223001,223001.00),(6,35,NULL,1,149400,149400.00),(7,35,NULL,1,149400,149400.00),(8,5,NULL,1,81000,81000.00),(8,22,NULL,1,72000,72000.00),(8,24,NULL,1,58800,58800.00),(9,30,NULL,1,57600,57600.00),(9,32,NULL,1,90000,90000.00),(9,33,NULL,1,69000,69000.00),(10,19,NULL,1,113900,113900.00),(10,27,NULL,1,101400,101400.00);
/*!40000 ALTER TABLE `order_detail` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payment`
--

DROP TABLE IF EXISTS `payment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payment` (
  `PaymentID` int(11) NOT NULL AUTO_INCREMENT,
  `OrderID` int(11) DEFAULT NULL,
  `PaymentMethod` varchar(50) NOT NULL,
  `PaymentStatus` enum('Pending','Completed','Failed','Refunded') DEFAULT 'Pending',
  `PaymentDate` datetime DEFAULT NULL,
  PRIMARY KEY (`PaymentID`),
  KEY `fk_payment_order` (`OrderID`),
  CONSTRAINT `fk_payment_order` FOREIGN KEY (`OrderID`) REFERENCES `order` (`OrderID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment`
--

LOCK TABLES `payment` WRITE;
/*!40000 ALTER TABLE `payment` DISABLE KEYS */;
INSERT INTO `payment` VALUES (1,1,'COD','Pending',NULL),(2,2,'VNPAY','Completed','2026-08-28 20:49:28'),(3,3,'COD','Completed','2026-08-28 21:18:31'),(4,4,'VNPAY','Completed','2026-08-28 21:16:56'),(5,5,'COD','Pending',NULL),(6,6,'VNPAY','Completed','2026-08-29 20:11:53'),(7,7,'VNPAY','Completed','2026-08-31 19:00:03'),(8,8,'COD','Pending',NULL),(9,9,'COD','Completed','2026-09-01 15:21:45'),(10,10,'VNPAY','Completed','2026-09-01 15:19:40');
/*!40000 ALTER TABLE `payment` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product`
--

DROP TABLE IF EXISTS `product`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product` (
  `ProductID` int(11) NOT NULL AUTO_INCREMENT,
  `CategoryID` int(11) DEFAULT NULL,
  `BrandID` int(11) DEFAULT NULL,
  `ProductName` varchar(255) NOT NULL,
  `Brand` varchar(100) DEFAULT NULL,
  `Price` int(11) NOT NULL DEFAULT 0,
  `Quantity` int(11) DEFAULT 0,
  `Description` text DEFAULT NULL,
  `Publisher` varchar(100) DEFAULT 'AlphaBooks',
  `Status` enum('Còn hàng','Hết hàng') DEFAULT 'Còn hàng',
  PRIMARY KEY (`ProductID`),
  KEY `fk_product_category` (`CategoryID`),
  CONSTRAINT `fk_product_category` FOREIGN KEY (`CategoryID`) REFERENCES `category` (`CategoryID`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product`
--

LOCK TABLES `product` WRITE;
/*!40000 ALTER TABLE `product` DISABLE KEYS */;
INSERT INTO `product` VALUES (1,2,NULL,'Cây Cam Ngọt Của Tôi',NULL,64800,50,'Tác giả: José Mauro de Vasconcelos - Thể loại: Tiểu Thuyết','Nhà Xuất Bản Hội Nhà Văn','Còn hàng'),(2,4,NULL,'Hành Tinh Của Một Kẻ Nghĩ Nhiều',NULL,59900,50,'Tác giả: Nguyễn Đoàn Minh Thư - Thể loại: Sách tư duy - Kỹ năng sống','Nhà Xuất Bản Thế Giới','Còn hàng'),(3,1,NULL,'Những Tù Nhân Của Địa Lý',NULL,126000,50,'Tác giả: Tim Marshall - Thể loại: Lĩnh vực khác','Nhà Xuất Bản Hội Nhà Văn','Còn hàng'),(4,2,NULL,'Nhà Giả Kim (Tái Bản 2020)',NULL,47400,50,'Tác giả: Paulo Coelho - Thể loại: Tác phẩm kinh điển','Nhà Xuất Bản Hà Nội','Còn hàng'),(5,2,NULL,'Một Thoáng Ta Rực Rỡ Ở Nhân Gian',NULL,81000,49,'Thể loại: Tiểu Thuyết','Nhà Xuất Bản Hội Nhà Văn','Còn hàng'),(6,2,NULL,'Điều Kỳ Diệu Của Tiệm Tạp Hóa NAMIYA (Tái Bản)',NULL,63000,50,'Tác giả: Higashino Keigo - Thể loại: Truyện ngắn - Tản văn - Tạp văn','NXB Trẻ','Còn hàng'),(7,4,NULL,'Thao Túng Tâm Lý',NULL,103000,50,'Tác giả: Shannon Thomas - Thể loại: Tâm lý học','Nhà Xuất Bản Dân Trí','Còn hàng'),(8,2,NULL,'Thư Viện Nửa Đêm',NULL,90000,50,'Tác giả: Matt Haig - Thể loại: Tiểu Thuyết','Nhà Xuất Bản Hội Nhà Văn','Còn hàng'),(9,4,NULL,'Không Phải Sói Nhưng Cũng Đừng Là Cừu',NULL,96000,50,'Tác giả: Lê Bảo Ngọc - Thể loại: Bài học thành công','Nhà Xuất Bản Thế Giới','Còn hàng'),(10,4,NULL,'Càng Bình Tĩnh Càng Hạnh Phúc',NULL,85180,50,'Tác giả: Vãn Tình - Thể loại: Sách tư duy - Kỹ năng sống','Nhà Xuất Bản Thế Giới','Còn hàng'),(11,4,NULL,'Không Phải Sói Nhưng Cũng Đừng Là Cừu - Tặng kèm bookmark',NULL,96000,50,'Tác giả: Lê Bảo Ngọc - Thể loại: Sách tư duy - Kỹ năng sống','Nhà Xuất Bản Thế Giới','Còn hàng'),(12,2,NULL,'Yêu Những Điều Không Hoàn Hảo',NULL,83400,50,'Tác giả: Hae Min - Thể loại: Truyện ngắn - Tản văn - Tạp Văn','NXB Trẻ','Còn hàng'),(13,4,NULL,'Hiểu Về Trái Tim (Tái Bản)',NULL,95200,50,'Tác giả: Minh Niệm - Thể loại: Sách tư duy - Kỹ năng sống','NXB Trẻ','Còn hàng'),(14,3,NULL,'Quyền Lực Của Địa Lý - The Power Of Geography',NULL,221000,48,'Tác giả: Tim Marshall - Thể loại: Sách kinh tế học','Nhà Xuất Bản Phụ Nữ','Còn hàng'),(15,2,NULL,'Chiến Binh Cầu Vồng (Tái Bản 2020)',NULL,65400,50,'Tác giả: Andrea Hirata - Thể loại: Tiểu Thuyết','Nhà Xuất Bản Hội Nhà Văn','Còn hàng'),(16,2,NULL,'Thiên Tài Bên Trái, Kẻ Điên Bên Phải (Tái Bản)',NULL,115001,50,'Tác giả: Cao Minh - Thể loại: Truyện ngắn - Tản văn - Tạp Văn','Nhà Xuất Bản Thế Giới','Còn hàng'),(17,4,NULL,'How Psychology Works - Hiểu Hết Về Tâm Lý Học',NULL,180000,50,'Tác giả: Jo Hemmings - Thể loại: Sách tư duy - Kỹ năng sống','Nhà Xuất Bản Thế Giới','Còn hàng'),(18,1,NULL,'Đại Dương Đen - Những Câu Chuyện Từ Thế Giới Của Trầm Cảm',NULL,144000,50,'Tác giả: Đặng Hoàng Giang - Thể loại: Truyện ngắn - Tản văn - Tạp Văn','Nhà Xuất Bản Hội Nhà Văn','Còn hàng'),(19,2,NULL,'Cú Săn Đêm',NULL,113900,49,'Tác giả: Samuel Bjork - Thể loại: Tiểu Thuyết','NXB Trẻ','Còn hàng'),(20,1,NULL,'Vị Thần Của Những Quyết Định',NULL,55100,49,'Tác giả: Universe - Thể loại: Sách Chiêm Tinh - Horoscope','Nhà Xuất Bản Thế Giới','Còn hàng'),(21,6,NULL,'Hoàng Tử Bé (Tái Bản 2019)',NULL,45000,50,'Tác giả: Antoine De Saint-Exupéry - Thể loại: Truyện kể cho bé','NXB Trẻ','Còn hàng'),(22,1,NULL,'Muôn Kiếp Nhân Sinh (Khổ Nhỏ)',NULL,72000,48,'Tác giả: Nguyên Phong - Thể loại: Sách Tâm Linh','Nhà Xuất Bản Tổng hợp TP.HCM','Còn hàng'),(23,2,NULL,'Bước Chậm Lại Giữa Thế Gian Vội Vã (Tái Bản)',NULL,51000,50,'Tác giả: Hae Min - Thể loại: Truyện ngắn - Tản văn - Tạp Văn','NXB Trẻ','Còn hàng'),(24,2,NULL,'Totto - Chan Bên Cửa Sổ (Tái Bản)',NULL,58800,49,'Tác giả: Kuroyanagi Tetsuko - Thể loại: Truyện dài','NXB Trẻ','Còn hàng'),(25,4,NULL,'Thay Đổi Cuộc Sống Với Nhân Số Học',NULL,141300,49,'Tác giả: David A. Phillips - Thể loại: Sách tư duy - Kỹ năng sống','Nhà Xuất Bản Tổng hợp TP.HCM','Còn hàng'),(26,2,NULL,'Những Người Khốn Khổ (Boxet 2 Tập)',NULL,399000,50,'Tác giả: Victor Hugo - Thể loại: Tác phẩm kinh điển','Nhà Xuất Bản Văn Học','Còn hàng'),(27,2,NULL,'Trăm Năm Cô Đơn',NULL,101400,49,'Tác giả: Gabriel Garcia Marquez - Thể loại: Truyện ngắn - Tản văn - Tạp Văn','NXB Trẻ','Còn hàng'),(28,2,NULL,'Dịch Hạch (Nobel Văn Chương 1957)',NULL,77400,49,'Tác giả: Albert Camus - Thể loại: Tác phẩm kinh điển','Nhà Xuất Bản Dân Trí','Còn hàng'),(29,1,NULL,'Luật Tâm Thức - Giải Mã Ma Trận Vũ Trụ',NULL,223001,49,'Tác giả: Ngô Sa Thạch - Thể loại: Lĩnh vực khác','Nhà Xuất Bản Dân Trí','Còn hàng'),(30,2,NULL,'Dám Bị Ghét',NULL,57600,49,'Tác giả: Koga Fumitake - Thể loại: Tiểu Thuyết','NXB Trẻ','Còn hàng'),(31,1,NULL,'Chú Bé Mang Pyjama Sọc (Tái Bản 2018)',NULL,40800,50,'Tác giả: John Boyne - Thể loại: Truyện kể cho bé','NXB Trẻ','Còn hàng'),(32,2,NULL,'Rừng Nauy (Tái Bản)',NULL,90000,47,'Tác giả: Haruki Murakami - Thể loại: Tiểu Thuyết','Nhà Xuất Bản Hội Nhà Văn','Còn hàng'),(33,2,NULL,'Sự Im Lặng Của Bầy Cừu (Tái Bản)',NULL,69000,47,'Tác giả: Thomas Harris - Thể loại: Truyện dài','NXB Trẻ','Còn hàng'),(34,2,NULL,'Bố Con Cá Gai (Tái Bản 2019)',NULL,57600,50,'Tác giả: Cho Chang - In - Thể loại: Tiểu Thuyết','NXB Trẻ','Còn hàng'),(35,2,NULL,'Xứ Cát',NULL,149400,48,'Tác giả: Frank Herbert - Thể loại: Truyện Giả tưởng - Huyền Bí - Phiêu Lưu','Nhà Xuất Bản Hội Nhà Văn','Còn hàng');
/*!40000 ALTER TABLE `product` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `promotion`
--

DROP TABLE IF EXISTS `promotion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `promotion` (
  `PromotionID` int(11) NOT NULL AUTO_INCREMENT,
  `PromotionName` varchar(255) NOT NULL,
  `DiscountPercent` decimal(5,2) NOT NULL,
  `StartDate` datetime DEFAULT NULL,
  `EndDate` datetime DEFAULT NULL,
  PRIMARY KEY (`PromotionID`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `promotion`
--

LOCK TABLES `promotion` WRITE;
/*!40000 ALTER TABLE `promotion` DISABLE KEYS */;
INSERT INTO `promotion` VALUES (1,'Summer Sale',15.00,'2026-06-01 00:00:00','2026-07-30 00:00:00'),(2,'Back to School',20.00,'2026-08-15 00:00:00','2026-09-15 00:00:00'),(3,'Black Friday',50.00,'2026-11-20 00:00:00','2026-11-30 00:00:00'),(4,'Flash Sale Tháng 4',10.00,'2026-04-01 00:00:00','2026-04-05 00:00:00'),(5,'Mừng tuổi mới',25.00,'2026-01-01 00:00:00','2026-01-10 00:00:00'),(6,'Giải phóng miền Nam',30.00,'2026-04-28 00:00:00','2026-05-02 00:00:00'),(7,'Quốc tế Phụ nữ',15.00,'2026-03-05 00:00:00','2026-03-10 00:00:00'),(8,'Trung Thu Yêu Thương',10.00,'2026-09-10 00:00:00','2026-09-20 00:00:00'),(9,'Clearance Sale',40.00,'2026-12-15 00:00:00','2026-12-31 00:00:00'),(10,'Happy Weekend',5.00,'2026-05-08 00:00:00','2026-05-10 00:00:00');
/*!40000 ALTER TABLE `promotion` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `promotion_detail`
--

DROP TABLE IF EXISTS `promotion_detail`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `promotion_detail` (
  `ProductID` int(11) NOT NULL,
  `PromotionID` int(11) NOT NULL,
  `DiscountRate` decimal(5,2) NOT NULL,
  `StartDate` datetime DEFAULT NULL,
  `EndDate` datetime DEFAULT NULL,
  PRIMARY KEY (`ProductID`,`PromotionID`),
  KEY `fk_promodetail_promo` (`PromotionID`),
  CONSTRAINT `fk_promodetail_product` FOREIGN KEY (`ProductID`) REFERENCES `product` (`ProductID`) ON DELETE CASCADE,
  CONSTRAINT `fk_promodetail_promo` FOREIGN KEY (`PromotionID`) REFERENCES `promotion` (`PromotionID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `promotion_detail`
--

LOCK TABLES `promotion_detail` WRITE;
/*!40000 ALTER TABLE `promotion_detail` DISABLE KEYS */;
/*!40000 ALTER TABLE `promotion_detail` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `review`
--

DROP TABLE IF EXISTS `review`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `review` (
  `ReviewID` int(11) NOT NULL AUTO_INCREMENT,
  `CustomerID` int(11) DEFAULT NULL,
  `ProductID` int(11) DEFAULT NULL,
  `Rating` int(11) DEFAULT NULL,
  `Comment` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ReviewDate` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`ReviewID`),
  KEY `fk_review_user` (`CustomerID`),
  KEY `fk_review_product` (`ProductID`),
  CONSTRAINT `fk_review_product` FOREIGN KEY (`ProductID`) REFERENCES `product` (`ProductID`) ON DELETE CASCADE,
  CONSTRAINT `fk_review_user` FOREIGN KEY (`CustomerID`) REFERENCES `user` (`CustomerID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `review`
--

LOCK TABLES `review` WRITE;
/*!40000 ALTER TABLE `review` DISABLE KEYS */;
INSERT INTO `review` VALUES (13,22,30,5,'okela','2026-09-01 15:22:10'),(14,22,32,5,'hay','2026-09-01 15:22:23'),(15,22,33,5,'9.5 điểm','2026-09-01 15:22:33');
/*!40000 ALTER TABLE `review` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `role`
--

DROP TABLE IF EXISTS `role`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `role` (
  `RoleID` int(11) NOT NULL AUTO_INCREMENT,
  `RoleName` varchar(50) NOT NULL,
  `Description` text DEFAULT NULL,
  PRIMARY KEY (`RoleID`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role`
--

LOCK TABLES `role` WRITE;
/*!40000 ALTER TABLE `role` DISABLE KEYS */;
INSERT INTO `role` VALUES (1,'Admin','Quản trị viên hệ thống'),(2,'Customer','Khách hàng');
/*!40000 ALTER TABLE `role` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user` (
  `CustomerID` int(11) NOT NULL AUTO_INCREMENT,
  `RoleID` int(11) DEFAULT NULL,
  `LastName` varchar(50) NOT NULL,
  `FirstName` varchar(50) NOT NULL,
  `Email` varchar(100) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `Phone` varchar(20) DEFAULT NULL,
  `Address` text DEFAULT NULL,
  `CreatedDate` datetime DEFAULT current_timestamp(),
  `username` varchar(100) DEFAULT NULL,
  `ResetToken` varchar(10) DEFAULT NULL,
  `ResetTokenExpires` datetime DEFAULT NULL,
  PRIMARY KEY (`CustomerID`),
  UNIQUE KEY `Email` (`Email`),
  KEY `fk_user_role` (`RoleID`),
  CONSTRAINT `fk_user_role` FOREIGN KEY (`RoleID`) REFERENCES `role` (`RoleID`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user`
--

LOCK TABLES `user` WRITE;
/*!40000 ALTER TABLE `user` DISABLE KEYS */;
INSERT INTO `user` VALUES (18,2,'Công','Thành','congvt123lol321@gmail.com','','0123456789','123','2026-08-28 16:44:47',NULL,NULL,NULL),(19,1,'','admin','admin@gmail.com','$2y$10$SbwTCHE/cWSI4Ma0L0RUte029fKCyo53Xidu62NzBfsdl/xvocaBm','0398910317','123','2026-08-28 16:50:43','admin',NULL,NULL),(20,2,'có','không','congvt321lol123@gmail.com','','456','456','2026-08-28 18:49:17',NULL,NULL,NULL),(21,2,'','123','123@gmail.com','$2y$10$KJoomyZTwPmIYboBUhBRn.rX0t3b5QbzZCpH0gItzLSbK3L2Scywq','123','123','2026-08-29 19:14:03','123',NULL,NULL),(22,2,'','congvt','congvt@gmail.com','$2y$10$iZny3F0m.agoU2Z.VT90eOF7yub/q8hlmTEQRW5g7i557NxEEkmtu','0987654321','Số 2 Võ Oanh','2026-09-01 15:17:01','congvt',NULL,NULL);
/*!40000 ALTER TABLE `user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_log`
--

DROP TABLE IF EXISTS `user_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_log` (
  `LogID` int(11) NOT NULL AUTO_INCREMENT,
  `CustomerID` int(11) DEFAULT NULL,
  `EmployeeID` int(11) DEFAULT NULL,
  `Action` varchar(255) NOT NULL,
  `LogDate` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`LogID`),
  KEY `fk_userlog_user` (`CustomerID`),
  KEY `fk_userlog_employee` (`EmployeeID`),
  CONSTRAINT `fk_userlog_employee` FOREIGN KEY (`EmployeeID`) REFERENCES `user` (`CustomerID`) ON DELETE SET NULL,
  CONSTRAINT `fk_userlog_user` FOREIGN KEY (`CustomerID`) REFERENCES `user` (`CustomerID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=120 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_log`
--

LOCK TABLES `user_log` WRITE;
/*!40000 ALTER TABLE `user_log` DISABLE KEYS */;
INSERT INTO `user_log` VALUES (1,NULL,19,'Thêm mới sản phẩm ID 41 - Tên: ntc','2026-08-28 17:12:38'),(2,NULL,19,'Xóa sản phẩm ID 41','2026-08-28 17:13:40'),(3,NULL,19,'Thêm mới sản phẩm ID 36 - Tên: 123','2026-08-28 17:14:03'),(4,NULL,19,'Cập nhật sản phẩm ID 36 - Tên: 1234','2026-08-28 17:14:41'),(5,NULL,19,'Cập nhật sản phẩm ID 36 - Tên: 1234','2026-08-28 17:14:51'),(6,NULL,19,'Xóa sản phẩm ID 36','2026-08-28 17:14:55'),(7,19,NULL,'Đăng nhập hệ thống','2026-08-28 18:41:15'),(8,NULL,19,'Đăng nhập hệ thống','2026-08-28 18:41:15'),(9,20,NULL,'Đăng nhập hệ thống','2026-08-28 18:49:17'),(10,20,NULL,'Đăng nhập hệ thống bằng Google','2026-08-28 18:49:17'),(11,18,NULL,'Đăng nhập hệ thống','2026-08-28 20:16:37'),(12,18,NULL,'Đăng nhập hệ thống bằng Google','2026-08-28 20:16:37'),(13,18,NULL,'Đặt hàng thành công đơn hàng #WBS-1','2026-08-28 20:48:25'),(14,18,NULL,'Đặt hàng thành công đơn hàng #WBS-2','2026-08-28 20:48:49'),(15,18,NULL,'Thanh toán thành công đơn hàng #WBS-2 qua VNPAY','2026-08-28 20:49:28'),(16,18,NULL,'Đặt hàng thành công đơn hàng #WBS-3','2026-08-28 21:11:19'),(17,18,NULL,'Đăng nhập hệ thống','2026-08-28 21:16:10'),(18,18,NULL,'Đăng nhập hệ thống bằng Google','2026-08-28 21:16:10'),(19,18,NULL,'Đặt hàng thành công đơn hàng #WBS-4','2026-08-28 21:16:26'),(20,18,NULL,'Thanh toán thành công đơn hàng #WBS-4 qua VNPAY','2026-08-28 21:16:56'),(21,19,NULL,'Đăng nhập hệ thống','2026-08-28 21:18:14'),(22,NULL,19,'Đăng nhập hệ thống','2026-08-28 21:18:14'),(23,NULL,19,'Cập nhật trạng thái đơn hàng #WBS-3 thành: Delivered','2026-08-28 21:18:31'),(24,20,NULL,'Đăng nhập hệ thống','2026-08-28 21:19:21'),(25,20,NULL,'Đăng nhập hệ thống bằng Google','2026-08-28 21:19:21'),(26,20,NULL,'Đặt hàng thành công đơn hàng #WBS-5','2026-08-28 21:19:34'),(27,19,NULL,'Đăng nhập hệ thống','2026-08-28 21:29:28'),(28,NULL,19,'Đăng nhập hệ thống','2026-08-28 21:29:28'),(29,19,NULL,'Đăng nhập hệ thống','2026-08-28 21:41:19'),(30,NULL,19,'Đăng nhập hệ thống','2026-08-28 21:41:19'),(31,19,NULL,'Đăng nhập hệ thống','2026-08-29 18:58:52'),(32,NULL,19,'Đăng nhập hệ thống','2026-08-29 18:58:52'),(33,21,NULL,'Đăng nhập hệ thống','2026-08-29 19:14:09'),(34,21,NULL,'Đăng nhập hệ thống','2026-08-29 19:14:09'),(35,21,NULL,'Đặt hàng thành công đơn hàng #WBS-6','2026-08-29 20:11:10'),(36,21,NULL,'Thanh toán thành công đơn hàng #WBS-6 qua VNPAY','2026-08-29 20:11:53'),(37,19,NULL,'Đăng nhập hệ thống','2026-08-29 22:03:20'),(38,NULL,19,'Đăng nhập hệ thống','2026-08-29 22:03:20'),(39,19,NULL,'Đăng nhập hệ thống','2026-08-29 22:08:17'),(40,18,NULL,'Đăng nhập hệ thống','2026-08-29 22:55:23'),(41,18,NULL,'Đăng nhập hệ thống bằng Google','2026-08-29 22:55:23'),(42,18,NULL,'Đăng nhập hệ thống','2026-08-31 18:58:38'),(43,18,NULL,'Đăng nhập hệ thống bằng Google','2026-08-31 18:58:38'),(44,18,NULL,'Đặt hàng thành công đơn hàng #WBS-7','2026-08-31 18:59:26'),(45,18,NULL,'Thanh toán thành công đơn hàng #WBS-7 qua VNPAY','2026-08-31 19:00:03'),(46,21,NULL,'Đăng nhập hệ thống','2026-08-31 19:04:13'),(47,21,NULL,'Đăng nhập hệ thống','2026-08-31 19:04:13'),(48,19,NULL,'Đăng nhập hệ thống','2026-08-31 19:04:56'),(49,NULL,19,'Đăng nhập hệ thống','2026-08-31 19:04:56'),(50,20,NULL,'Đăng nhập hệ thống','2026-08-31 19:17:05'),(51,20,NULL,'Đăng nhập hệ thống bằng Google','2026-08-31 19:17:05'),(52,18,NULL,'Đăng nhập hệ thống','2026-08-31 19:21:05'),(53,18,NULL,'Đăng nhập hệ thống bằng Google','2026-08-31 19:21:05'),(54,19,NULL,'Đăng nhập hệ thống','2026-08-31 19:21:48'),(55,NULL,19,'Đăng nhập hệ thống','2026-08-31 19:21:48'),(56,18,NULL,'Đăng nhập hệ thống','2026-08-31 19:29:39'),(57,18,NULL,'Đăng nhập hệ thống bằng Google','2026-08-31 19:29:39'),(58,18,NULL,'Đặt hàng thành công đơn hàng #WBS-8','2026-08-31 19:31:07'),(59,19,NULL,'Đăng nhập hệ thống','2026-08-31 19:35:08'),(60,NULL,19,'Đăng nhập hệ thống','2026-08-31 19:35:08'),(61,18,NULL,'Đăng nhập hệ thống','2026-08-31 19:43:36'),(62,18,NULL,'Đăng nhập hệ thống bằng Google','2026-08-31 19:43:36'),(63,18,NULL,'Đăng nhập hệ thống','2026-08-31 19:49:18'),(64,18,NULL,'Đăng nhập hệ thống bằng Google','2026-08-31 19:49:18'),(65,18,NULL,'Đăng nhập hệ thống','2026-08-31 19:52:48'),(66,18,NULL,'Đăng nhập hệ thống bằng Google','2026-08-31 19:52:48'),(67,18,NULL,'Đăng nhập hệ thống','2026-08-31 19:55:12'),(68,18,NULL,'Đăng nhập hệ thống bằng Google','2026-08-31 19:55:12'),(69,18,NULL,'Đăng nhập hệ thống','2026-08-31 19:56:05'),(70,18,NULL,'Đăng nhập hệ thống bằng Google','2026-08-31 19:56:05'),(71,18,NULL,'Đăng nhập hệ thống','2026-08-31 19:58:06'),(72,18,NULL,'Đăng nhập hệ thống bằng Google','2026-08-31 19:58:06'),(73,18,NULL,'Đăng nhập hệ thống','2026-08-31 20:30:24'),(74,18,NULL,'Đăng nhập hệ thống bằng Google','2026-08-31 20:30:24'),(75,19,NULL,'Đăng nhập hệ thống','2026-08-31 20:30:38'),(76,NULL,19,'Đăng nhập hệ thống','2026-08-31 20:30:38'),(77,19,NULL,'Đăng nhập hệ thống','2026-09-01 14:58:17'),(78,NULL,19,'Đăng nhập hệ thống','2026-09-01 14:58:17'),(79,18,NULL,'Đăng nhập hệ thống','2026-09-01 15:00:25'),(80,18,NULL,'Đăng nhập hệ thống bằng Google','2026-09-01 15:00:25'),(81,19,NULL,'Đăng nhập hệ thống','2026-09-01 15:00:33'),(82,NULL,19,'Đăng nhập hệ thống','2026-09-01 15:00:33'),(83,NULL,19,'Cập nhật mã giảm giá ID 2 - Mã: FREESHIP','2026-09-01 15:01:26'),(84,NULL,19,'Cập nhật mã giảm giá ID 1 - Mã: NEWUSER100','2026-09-01 15:01:32'),(85,NULL,19,'Cập nhật mã giảm giá ID 3 - Mã: GIAM30K','2026-09-01 15:01:59'),(86,NULL,19,'Cập nhật mã giảm giá ID 4 - Mã: BOOKSHOP','2026-09-01 15:02:26'),(87,NULL,19,'Cập nhật mã giảm giá ID 2 - Mã: FREESHIP','2026-09-01 15:02:47'),(88,NULL,19,'Cập nhật mã giảm giá ID 5 - Mã: STUDENT','2026-09-01 15:02:55'),(89,NULL,19,'Cập nhật mã giảm giá ID 6 - Mã: BLACKFRIDAY','2026-09-01 15:03:49'),(90,NULL,19,'Cập nhật mã giảm giá ID 6 - Mã: BLACKFRIDAY','2026-09-01 15:03:58'),(91,NULL,19,'Cập nhật mã giảm giá ID 8 - Mã: WEEKEND','2026-09-01 15:04:24'),(92,NULL,19,'Cập nhật mã giảm giá ID 10 - Mã: BIRTHDAY','2026-09-01 15:04:35'),(93,NULL,19,'Cập nhật mã giảm giá ID 9 - Mã: TET','2026-09-01 15:04:59'),(94,NULL,19,'Cập nhật mã giảm giá ID 7 - Mã: SALEALL','2026-09-01 15:05:09'),(95,NULL,19,'Cập nhật mã giảm giá ID 9 - Mã: TET','2026-09-01 15:05:13'),(96,19,NULL,'Đăng nhập hệ thống','2026-09-01 15:16:45'),(97,NULL,19,'Đăng nhập hệ thống','2026-09-01 15:16:45'),(98,22,NULL,'Đăng nhập hệ thống','2026-09-01 15:17:07'),(99,22,NULL,'Đăng nhập hệ thống','2026-09-01 15:17:08'),(100,22,NULL,'Đặt hàng thành công đơn hàng #WBS-9','2026-09-01 15:18:34'),(101,22,NULL,'Đặt hàng thành công đơn hàng #WBS-10','2026-09-01 15:19:12'),(102,22,NULL,'Thanh toán thành công đơn hàng #WBS-10 qua VNPAY','2026-09-01 15:19:40'),(103,19,NULL,'Đăng nhập hệ thống','2026-09-01 15:20:48'),(104,NULL,19,'Đăng nhập hệ thống','2026-09-01 15:20:48'),(105,NULL,19,'Cập nhật trạng thái đơn hàng #WBS-9 thành: Shipped','2026-09-01 15:21:21'),(106,22,NULL,'Đăng nhập hệ thống','2026-09-01 15:21:26'),(107,22,NULL,'Đăng nhập hệ thống','2026-09-01 15:21:26'),(108,19,NULL,'Đăng nhập hệ thống','2026-09-01 15:21:36'),(109,NULL,19,'Đăng nhập hệ thống','2026-09-01 15:21:36'),(110,NULL,19,'Cập nhật trạng thái đơn hàng #WBS-9 thành: Delivered','2026-09-01 15:21:45'),(111,22,NULL,'Đăng nhập hệ thống','2026-09-01 15:21:49'),(112,22,NULL,'Đăng nhập hệ thống','2026-09-01 15:21:49'),(113,22,NULL,'Đánh giá sản phẩm ID 30 (5 sao)','2026-09-01 15:22:10'),(114,22,NULL,'Đánh giá sản phẩm ID 32 (5 sao)','2026-09-01 15:22:23'),(115,22,NULL,'Đánh giá sản phẩm ID 33 (5 sao)','2026-09-01 15:22:33'),(116,19,NULL,'Đăng nhập hệ thống','2026-09-01 15:25:39'),(117,NULL,19,'Đăng nhập hệ thống','2026-09-01 15:25:39'),(118,22,NULL,'Đăng nhập hệ thống','2026-09-01 15:52:35'),(119,22,NULL,'Đăng nhập hệ thống','2026-09-01 15:52:35');
/*!40000 ALTER TABLE `user_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_provider`
--

DROP TABLE IF EXISTS `user_provider`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_provider` (
  `ProviderID` int(11) NOT NULL AUTO_INCREMENT,
  `User_ID` int(11) NOT NULL,
  `ProviderName` varchar(50) NOT NULL,
  `Provider_userID` varchar(255) NOT NULL,
  `access_token` text DEFAULT NULL,
  `refresh_token` text DEFAULT NULL,
  `CreatedAt` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`ProviderID`),
  KEY `fk_userprovider_user` (`User_ID`),
  CONSTRAINT `fk_userprovider_user` FOREIGN KEY (`User_ID`) REFERENCES `user` (`CustomerID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_provider`
--

LOCK TABLES `user_provider` WRITE;
/*!40000 ALTER TABLE `user_provider` DISABLE KEYS */;
INSERT INTO `user_provider` VALUES (4,18,'Google','118055642448411235421','',NULL,'2026-08-28 16:44:47'),(5,20,'Google','106787151448759840956','',NULL,'2026-08-28 18:49:17');
/*!40000 ALTER TABLE `user_provider` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `voucher`
--

DROP TABLE IF EXISTS `voucher`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `voucher` (
  `VoucherID` int(11) NOT NULL AUTO_INCREMENT,
  `VoucherCode` varchar(50) NOT NULL,
  `DiscountValue` decimal(10,2) NOT NULL,
  `ExpiredDate` datetime DEFAULT NULL,
  PRIMARY KEY (`VoucherID`),
  UNIQUE KEY `VoucherCode` (`VoucherCode`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `voucher`
--

LOCK TABLES `voucher` WRITE;
/*!40000 ALTER TABLE `voucher` DISABLE KEYS */;
INSERT INTO `voucher` VALUES (1,'NEWUSER100',100000.00,'2027-12-28 00:00:00'),(2,'FREESHIP',50000.00,'2028-09-29 00:00:00'),(3,'GIAM30K',30000.00,'2027-09-24 00:00:00'),(4,'BOOKSHOP',70000.00,'2028-01-01 00:00:00'),(5,'STUDENT',80000.00,'2026-10-01 00:00:00'),(6,'BLACKFRIDAY',150000.00,'2026-11-27 00:00:00'),(7,'SALEALL',25000.00,'2026-07-01 00:00:00'),(8,'WEEKEND',35000.00,'2026-05-31 00:00:00'),(9,'TET',120000.00,'2026-12-31 00:00:00'),(10,'BIRTHDAY',180000.00,'2026-12-31 00:00:00');
/*!40000 ALTER TABLE `voucher` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `voucher_detail`
--

DROP TABLE IF EXISTS `voucher_detail`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `voucher_detail` (
  `CustomerID` int(11) NOT NULL,
  `VoucherID` int(11) NOT NULL,
  `ReceivedDate` datetime DEFAULT current_timestamp(),
  `UsedStatus` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`CustomerID`,`VoucherID`),
  KEY `fk_voucherdetail_voucher` (`VoucherID`),
  CONSTRAINT `fk_voucherdetail_user` FOREIGN KEY (`CustomerID`) REFERENCES `user` (`CustomerID`) ON DELETE CASCADE,
  CONSTRAINT `fk_voucherdetail_voucher` FOREIGN KEY (`VoucherID`) REFERENCES `voucher` (`VoucherID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `voucher_detail`
--

LOCK TABLES `voucher_detail` WRITE;
/*!40000 ALTER TABLE `voucher_detail` DISABLE KEYS */;
INSERT INTO `voucher_detail` VALUES (18,5,'2026-08-28 20:48:25',1),(18,10,'2026-08-28 20:48:49',1),(22,2,'2026-09-01 15:18:34',1),(22,9,'2026-09-01 15:19:12',1);
/*!40000 ALTER TABLE `voucher_detail` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'bookstore'
--

--
-- Dumping routines for database 'bookstore'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-09-01 17:46:00
