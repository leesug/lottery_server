-- 티켓 관련 기본 테이블 생성 스크립트

-- 티켓 테이블
CREATE TABLE IF NOT EXISTS `tickets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ticket_number` varchar(50) NOT NULL,
  `product_id` int(11) NOT NULL,
  `terminal_id` int(11) NOT NULL,
  `numbers` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `status` enum('active','cancelled','verified','expired','invalid') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `ticket_number` (`ticket_number`),
  KEY `product_id` (`product_id`),
  KEY `terminal_id` (`terminal_id`),
  KEY `status` (`status`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 단말기 테이블
CREATE TABLE IF NOT EXISTS `terminals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `terminal_code` varchar(20) NOT NULL,
  `store_id` int(11) NOT NULL,
  `model` varchar(50) DEFAULT NULL,
  `serial_number` varchar(50) DEFAULT NULL,
  `installation_date` date DEFAULT NULL,
  `status` enum('active','inactive','maintenance','decommissioned') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `terminal_code` (`terminal_code`),
  KEY `store_id` (`store_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 판매점 테이블
CREATE TABLE IF NOT EXISTS `stores` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `store_code` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `region_id` int(11) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `owner_name` varchar(100) DEFAULT NULL,
  `status` enum('active','inactive','pending','suspended') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `store_code` (`store_code`),
  KEY `region_id` (`region_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 지역 테이블
CREATE TABLE IF NOT EXISTS `regions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `code` varchar(20) DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `level` int(11) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `parent_id` (`parent_id`),
  KEY `level` (`level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;