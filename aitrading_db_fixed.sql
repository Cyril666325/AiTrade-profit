/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19-11.8.6-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: aitrading_db
-- ------------------------------------------------------
-- Server version	11.8.6-MariaDB-0+deb13u1 from Debian

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;

--
-- Table structure for table `admin`
--

DROP TABLE IF EXISTS `admin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `admin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `admin` WRITE;
/*!40000 ALTER TABLE `admin` DISABLE KEYS */;
INSERT INTO `admin` VALUES
(1,'Admin','admin@aitrading.com','$2y$10$UedwQqSjBCyFC6OynHcxL.lg6xI7IOlnhzwryQhQIBBEV8txej0MO');
/*!40000 ALTER TABLE `admin` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `ai_trades`
--

DROP TABLE IF EXISTS `ai_trades`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ai_trades` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pair` varchar(20) NOT NULL,
  `action` enum('BUY','SELL') NOT NULL,
  `entry_price` decimal(15,4) NOT NULL,
  `exit_price` decimal(15,4) DEFAULT NULL,
  `profit_pct` decimal(8,4) DEFAULT NULL,
  `status` enum('open','closed') NOT NULL DEFAULT 'open',
  `bot_type` varchar(50) NOT NULL DEFAULT 'scalper',
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ai_trades`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `ai_trades` WRITE;
/*!40000 ALTER TABLE `ai_trades` DISABLE KEYS */;
INSERT INTO `ai_trades` VALUES
(1,'BTC/USDT','BUY',67234.5000,68891.2000,2.4600,'closed','scalper','2026-07-09 02:14:24'),
(2,'ETH/USDT','SELL',3521.8000,3448.5000,2.0800,'closed','arbitrage','2026-07-09 02:14:24'),
(3,'SOL/USDT','BUY',178.4000,183.9000,3.0800,'closed','trend','2026-07-09 02:14:24'),
(4,'BTC/USDT','BUY',66980.0000,67450.5000,0.7000,'closed','scalper','2026-07-09 02:14:24'),
(5,'BNB/USDT','SELL',598.2000,581.1000,2.8600,'closed','quantum','2026-07-09 02:14:24'),
(6,'ETH/USDT','BUY',3489.0000,3561.0000,2.0600,'closed','trend','2026-07-09 02:14:24'),
(7,'BTC/USDT','SELL',68100.0000,67500.0000,0.8800,'closed','scalper','2026-07-09 02:14:24'),
(8,'SOL/USDT','BUY',181.0000,185.3000,2.3800,'closed','arbitrage','2026-07-09 02:14:24'),
(9,'BTC/USDT','BUY',67800.0000,NULL,NULL,'open','quantum','2026-07-09 02:14:24'),
(10,'ETH/USDT','BUY',3540.0000,NULL,NULL,'open','scalper','2026-07-09 02:14:24');
/*!40000 ALTER TABLE `ai_trades` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `bot_settings`
--

DROP TABLE IF EXISTS `bot_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `bot_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `plan_id` int(11) NOT NULL,
  `bot_name` varchar(100) NOT NULL DEFAULT 'AI Bot v1.0',
  `bot_type` varchar(50) NOT NULL DEFAULT 'scalper',
  `win_rate` decimal(5,2) NOT NULL DEFAULT 87.50,
  `accuracy` decimal(5,2) NOT NULL DEFAULT 92.30,
  `uptime_pct` decimal(5,2) NOT NULL DEFAULT 99.70,
  `trades_per_day` int(11) NOT NULL DEFAULT 45,
  PRIMARY KEY (`id`),
  KEY `plan_id` (`plan_id`),
  CONSTRAINT `bot_settings_ibfk_1` FOREIGN KEY (`plan_id`) REFERENCES `plans` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bot_settings`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `bot_settings` WRITE;
/*!40000 ALTER TABLE `bot_settings` DISABLE KEYS */;
INSERT INTO `bot_settings` VALUES
(1,1,'AI Scalper v2.4','scalper',87.50,92.30,99.70,120),
(2,2,'AI Arbitrage v1.9','arbitrage',84.20,89.60,99.50,45),
(3,3,'AI Trend v3.1','trend',79.80,86.10,99.80,18),
(4,4,'AI Quantum v1.0','quantum',76.50,83.40,99.90,8);
/*!40000 ALTER TABLE `bot_settings` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `deposits`
--

DROP TABLE IF EXISTS `deposits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `deposits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) NOT NULL,
  `method` varchar(50) NOT NULL,
  `tranxId` varchar(100) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `proof` varchar(255) DEFAULT NULL,
  `hash` varchar(255) DEFAULT NULL,
  `status` enum('pending','completed','rejected') NOT NULL DEFAULT 'pending',
  `date` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `tranxId` (`tranxId`),
  KEY `userId` (`userId`),
  CONSTRAINT `deposits_ibfk_1` FOREIGN KEY (`userId`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `deposits`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `deposits` WRITE;
/*!40000 ALTER TABLE `deposits` DISABLE KEYS */;
INSERT INTO `deposits` VALUES
(1,1,'USDT (TRC20)','DEP-DEMO-001',200.00,NULL,NULL,'completed','2026-07-01 10:30:00'),
(2,1,'Bitcoin (BTC)','DEP-DEMO-002',300.00,NULL,NULL,'completed','2026-07-05 14:15:00');
/*!40000 ALTER TABLE `deposits` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `investments`
--

DROP TABLE IF EXISTS `investments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `investments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) NOT NULL,
  `planId` int(11) NOT NULL,
  `invest_amt` decimal(15,2) NOT NULL,
  `plan_name` varchar(122) NOT NULL,
  `bot_type` varchar(50) NOT NULL DEFAULT 'scalper',
  `increase` decimal(8,4) NOT NULL,
  `duration` int(11) NOT NULL,
  `total_profit` decimal(15,2) NOT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `status` enum('running','completed','cancelled') NOT NULL DEFAULT 'running',
  `date` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `userId` (`userId`),
  KEY `planId` (`planId`),
  CONSTRAINT `investments_ibfk_1` FOREIGN KEY (`userId`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `investments_ibfk_2` FOREIGN KEY (`planId`) REFERENCES `plans` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `investments`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `investments` WRITE;
/*!40000 ALTER TABLE `investments` DISABLE KEYS */;
INSERT INTO `investments` VALUES
(1,1,1,100.00,'AI Scalper','scalper',5.0000,1,5.00,'2026-07-09 00:00:00','2026-07-10 00:00:00','completed','2026-07-09 00:00:00'),
(2,1,2,200.00,'AI Arbitrage','arbitrage',8.0000,2,16.00,'2026-07-13 00:00:00','2026-07-15 00:00:00','running','2026-07-13 00:00:00');
/*!40000 ALTER TABLE `investments` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `plans`
--

DROP TABLE IF EXISTS `plans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `plans` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pname` varchar(122) NOT NULL,
  `bot_type` varchar(50) NOT NULL DEFAULT 'scalper',
  `increase` decimal(8,4) NOT NULL,
  `duration` int(11) NOT NULL,
  `min_amt` decimal(15,2) NOT NULL,
  `max_amt` decimal(15,2) NOT NULL,
  `duration_unit` varchar(20) DEFAULT 'days',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `plans`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `plans` WRITE;
/*!40000 ALTER TABLE `plans` DISABLE KEYS */;
INSERT INTO `plans` VALUES
(1,'AI Scalper','scalper',5.0000,1,50.00,500.00,'days'),
(2,'AI Arbitrage','arbitrage',8.0000,2,600.00,5000.00,'days'),
(3,'AI Trend','trend',15.0000,5,5000.00,10000.00,'days'),
(4,'AI Quantum','quantum',25.0000,7,10000.00,30000.00,'days');
/*!40000 ALTER TABLE `plans` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sitename` varchar(255) NOT NULL DEFAULT 'AI Trading',
  `siteurl` varchar(255) NOT NULL DEFAULT 'http://localhost/AiTrading',
  `sitemail` varchar(255) NOT NULL DEFAULT 'support@aitrading.com',
  `ref_bonus` decimal(5,2) NOT NULL DEFAULT 10.00,
  `whatsapp` varchar(50) DEFAULT NULL,
  `livechat` text DEFAULT NULL,
  `btc_wallet` varchar(255) DEFAULT NULL,
  `eth_wallet` varchar(255) DEFAULT NULL,
  `usdt_trc20` varchar(255) DEFAULT NULL,
  `usdt_erc20` varchar(255) DEFAULT NULL,
  `ltc_wallet` varchar(255) DEFAULT NULL,
  `bnb_wallet` varchar(255) DEFAULT NULL,
  `doge_wallet` varchar(255) DEFAULT NULL,
  `sol_wallet` varchar(255) DEFAULT NULL,
  `ai_autogen` tinyint(1) NOT NULL DEFAULT 1,
  `stat_managed` varchar(20) DEFAULT '$12M+',
  `stat_bots` varchar(20) DEFAULT '48K+',
  `stat_winrate` varchar(20) DEFAULT '87.5%',
  `stat_countries` varchar(20) DEFAULT '80+',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES
(1,'AI Trading','http://localhost/AiTrading','support@aitrading.com',10.00,NULL,NULL,'YOUR_BTC_WALLET_HERE','YOUR_ETH_WALLET_HERE','YOUR_USDT_TRC20_WALLET_HERE','YOUR_USDT_ERC20_WALLET_HERE','YOUR_LTC_WALLET_HERE','YOUR_BNB_WALLET_HERE','YOUR_DOGE_WALLET_HERE','YOUR_SOL_WALLET_HERE',1,'$12M+','48K+','87.5%','80+');
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `transactions`
--

DROP TABLE IF EXISTS `transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `type` varchar(50) NOT NULL DEFAULT 'transfer',
  `status` varchar(50) NOT NULL DEFAULT 'completed',
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transactions`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `transactions` WRITE;
/*!40000 ALTER TABLE `transactions` DISABLE KEYS */;
/*!40000 ALTER TABLE `transactions` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `fname` varchar(255) NOT NULL,
  `lname` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `gender` varchar(20) NOT NULL DEFAULT '',
  `country` varchar(100) DEFAULT NULL,
  `address` text NOT NULL DEFAULT '',
  `occupation` varchar(255) DEFAULT 'User',
  `refcode` varchar(20) NOT NULL,
  `ref_balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `profit` decimal(15,2) NOT NULL DEFAULT 0.00,
  `balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `bonus` decimal(15,2) NOT NULL DEFAULT 0.00,
  `Id_photo` varchar(255) DEFAULT NULL,
  `profile_photo` varchar(255) DEFAULT NULL,
  `pin` varchar(10) DEFAULT NULL,
  `referral` varchar(20) DEFAULT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL,
  `date` datetime DEFAULT current_timestamp(),
  `status` enum('Not Verified','submitted','Verified') NOT NULL DEFAULT 'Not Verified',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `refcode` (`refcode`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES
(1,'user@aitrading.com','John','Doe','$2y$10$Kh030UHdWu8/6oViBlwuKu7WwcAA9J8TGuX6fqM/g6vJ0WuarsG5y','+12345678901','male','United States of America','123 Main Street, New York','User','C6F9DFA3',20.00,50.00,300.00,0.00,NULL,NULL,NULL,'',NULL,NULL,'2026-07-01 09:00:00','Verified');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `withdrawals`
--

DROP TABLE IF EXISTS `withdrawals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `withdrawals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `type` enum('balance','refbonus') NOT NULL DEFAULT 'balance',
  `method` varchar(50) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `wallet_address` varchar(255) DEFAULT NULL,
  `tranxId` varchar(100) NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `date` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `tranxId` (`tranxId`),
  KEY `userId` (`userId`),
  CONSTRAINT `withdrawals_ibfk_1` FOREIGN KEY (`userId`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `withdrawals`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `withdrawals` WRITE;
/*!40000 ALTER TABLE `withdrawals` DISABLE KEYS */;
INSERT INTO `withdrawals` VALUES
(1,1,'user@aitrading.com','refbonus','USDT (TRC20)',10.00,'TRC20DEMO000000000000000','WD-DEMO-001','approved','2026-07-10 09:00:00');
/*!40000 ALTER TABLE `withdrawals` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

-- Dump completed on 2026-07-12 16:00:03
