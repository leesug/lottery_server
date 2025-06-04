-- 외래 키 제약 조건 없이 추첨 관련 테이블 생성 스크립트

-- 추첨 테이블
CREATE TABLE IF NOT EXISTS `lottery_draws` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `draw_number` varchar(50) NOT NULL,
  `draw_date` datetime NOT NULL,
  `draw_method` enum('random_generator','machine_draw','manual_draw') NOT NULL DEFAULT 'random_generator',
  `draw_location` varchar(255) DEFAULT NULL,
  `winning_numbers` varchar(255) DEFAULT NULL,
  `draw_status` enum('scheduled','ready','in_progress','completed','cancelled') NOT NULL DEFAULT 'scheduled',
  `results_published` tinyint(1) NOT NULL DEFAULT 0,
  `published_at` datetime DEFAULT NULL,
  `winners_count` int(11) DEFAULT 0,
  `total_prizes` decimal(15,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_draw_number` (`product_id`, `draw_number`),
  KEY `product_id` (`product_id`),
  KEY `draw_date` (`draw_date`),
  KEY `draw_status` (`draw_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 당첨 정보 테이블
CREATE TABLE IF NOT EXISTS `winnings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `draw_id` int(11) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `prize_tier` int(11) NOT NULL,
  `prize_amount` decimal(15,2) NOT NULL,
  `prize_description` varchar(255) DEFAULT NULL,
  `claim_status` enum('unclaimed','pending','paid','expired') NOT NULL DEFAULT 'unclaimed',
  `claimed_at` datetime DEFAULT NULL,
  `claim_reference` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `draw_ticket` (`draw_id`, `ticket_id`),
  KEY `draw_id` (`draw_id`),
  KEY `ticket_id` (`ticket_id`),
  KEY `prize_tier` (`prize_tier`),
  KEY `claim_status` (`claim_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 추첨 이력 테이블
CREATE TABLE IF NOT EXISTS `draw_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `draw_id` int(11) NOT NULL,
  `action` varchar(50) NOT NULL,
  `details` text DEFAULT NULL,
  `performed_by` int(11) DEFAULT NULL,
  `performed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `draw_id` (`draw_id`),
  KEY `performed_by` (`performed_by`),
  KEY `performed_at` (`performed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 추첨 회차 설정 테이블
CREATE TABLE IF NOT EXISTS `draw_schedule` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `frequency` enum('daily','weekly','biweekly','monthly','custom') NOT NULL,
  `day_of_week` tinyint(1) DEFAULT NULL COMMENT '0=일, 1=월, ..., 6=토',
  `time_of_day` time DEFAULT NULL,
  `next_draw_date` datetime DEFAULT NULL,
  `last_draw_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `is_active` (`is_active`),
  KEY `next_draw_date` (`next_draw_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;