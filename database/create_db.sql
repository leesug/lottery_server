CREATE DATABASE IF NOT EXISTS `lotto_system` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `lotto_system`;

-- 복권 상품 테이블
CREATE TABLE IF NOT EXISTS `lottery_products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_code` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `lottery_type` varchar(50) NOT NULL,
  `description` text,
  `number_format` varchar(50),
  `draw_schedule` varchar(100),
  `template_id` int(11),
  `status` enum('active','inactive','suspended') NOT NULL DEFAULT 'active',
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_code` (`product_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 복권 템플릿 테이블
CREATE TABLE IF NOT EXISTS `lottery_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `template_name` varchar(100) NOT NULL,
  `template_description` text,
  `layout_json` text,
  `lottery_type` varchar(50) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_by` int(11),
  `updated_by` int(11),
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 복권 발행 계획 테이블
CREATE TABLE IF NOT EXISTS `issue_plans` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `issue_date` date NOT NULL,
  `total_tickets` int(11) NOT NULL,
  `batch_size` int(11) NOT NULL,
  `start_number` varchar(50) NOT NULL,
  `notes` text,
  `status` enum('draft','ready','in_progress','completed','cancelled') NOT NULL DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `fk_issue_plans_product` FOREIGN KEY (`product_id`) REFERENCES `lottery_products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 발행 큐 테이블
CREATE TABLE IF NOT EXISTS `issue_queue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `plan_id` int(11) NOT NULL,
  `status` enum('pending','in_progress','completed','failed','cancelled') NOT NULL DEFAULT 'pending',
  `total_tickets` int(11) NOT NULL,
  `processed_tickets` int(11) NOT NULL DEFAULT '0',
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `plan_id` (`plan_id`),
  CONSTRAINT `fk_issue_queue_plan` FOREIGN KEY (`plan_id`) REFERENCES `issue_plans` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 발행 이력 테이블
CREATE TABLE IF NOT EXISTS `issue_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `plan_id` int(11) NOT NULL,
  `queue_id` int(11),
  `issued_by` int(11) NOT NULL,
  `status` enum('started','completed','failed','cancelled') NOT NULL,
  `notes` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `plan_id` (`plan_id`),
  KEY `queue_id` (`queue_id`),
  CONSTRAINT `fk_issue_history_plan` FOREIGN KEY (`plan_id`) REFERENCES `issue_plans` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_issue_history_queue` FOREIGN KEY (`queue_id`) REFERENCES `issue_queue` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 유효성 검증 설정 테이블
CREATE TABLE IF NOT EXISTS `lottery_validation_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `enable_validation` tinyint(1) NOT NULL DEFAULT '1',
  `validation_method` varchar(50) NOT NULL DEFAULT 'online',
  `api_endpoint` varchar(255) DEFAULT NULL,
  `api_key` varchar(100) DEFAULT NULL,
  `verify_check_digit` tinyint(1) NOT NULL DEFAULT '1',
  `verify_sequence` tinyint(1) NOT NULL DEFAULT '1',
  `verify_batch` tinyint(1) NOT NULL DEFAULT '1',
  `verify_issue` tinyint(1) NOT NULL DEFAULT '1',
  `validate_expiry` tinyint(1) NOT NULL DEFAULT '1',
  `max_failures` int(11) NOT NULL DEFAULT '3',
  `block_duration` int(11) NOT NULL DEFAULT '30',
  `log_validations` tinyint(1) NOT NULL DEFAULT '1',
  `alert_threshold` int(11) NOT NULL DEFAULT '5',
  `notify_admin` tinyint(1) NOT NULL DEFAULT '1',
  `ip_restriction` tinyint(1) NOT NULL DEFAULT '0',
  `allowed_ips` text,
  `time_restriction` tinyint(1) NOT NULL DEFAULT '0',
  `allowed_time_start` time DEFAULT NULL,
  `allowed_time_end` time DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 샘플 복권 상품 데이터
INSERT INTO `lottery_products` (`product_code`, `name`, `price`, `lottery_type`, `description`, `number_format`, `draw_schedule`, `status`)
VALUES
('DAILY001', '일일 복권', 2000.00, 'standard', '매일 추첨되는 복권', '6자리 숫자', '매일 18:00', 'active'),
('WEEKLY001', '주간 복권', 5000.00, 'standard', '매주 일요일에 추첨되는 복권', '6자리 숫자', '매주 일요일 15:00', 'active'),
('MONTHLY001', '월간 특별 복권', 10000.00, 'special', '매월 마지막 일요일에 추첨되는 복권', '8자리 숫자', '매월 마지막 일요일', 'active'),
('FESTIVAL001', '명절 특별 복권', 15000.00, 'special', '명절에 추첨되는 특별 복권', '10자리 숫자', '특별 지정일', 'active'),
('YEARLY001', '연간 대형 복권', 50000.00, 'premium', '연말에 추첨되는 대형 복권', '12자리 숫자', '12월 31일 23:59', 'inactive');

-- 샘플 템플릿 데이터
INSERT INTO `lottery_templates` (`template_name`, `template_description`, `layout_json`, `lottery_type`, `is_active`, `created_by`)
VALUES
('기본 템플릿', '일반 복권용 기본 디자인 템플릿', '{"layout":"standard","background":"white","textColor":"black","elements":[{"type":"header","text":"일일 복권","position":"top","fontsize":18},{"type":"number","position":"center","fontsize":24},{"type":"barcode","position":"bottom","type":"code128"}]}', 'standard', 1, 1),
('프리미엄 템플릿', '고급 복권용 디자인 템플릿', '{"layout":"premium","background":"gold","textColor":"darkblue","elements":[{"type":"header","text":"프리미엄 복권","position":"top","fontsize":20},{"type":"image","path":"premium-logo.png","position":"top-right"},{"type":"number","position":"center","fontsize":28},{"type":"barcode","position":"bottom","type":"code128"},{"type":"footer","text":"행운을 빕니다","position":"bottom","fontsize":14}]}', 'premium', 1, 1),
('명절 특별 템플릿', '명절 특별 복권용 디자인 템플릿', '{"layout":"festive","background":"red","textColor":"gold","elements":[{"type":"header","text":"명절 특별 복권","position":"top","fontsize":22},{"type":"image","path":"festive-logo.png","position":"top-center"},{"type":"number","position":"center","fontsize":26},{"type":"barcode","position":"bottom","type":"code128"},{"type":"footer","text":"즐거운 명절 되세요","position":"bottom","fontsize":16}]}', 'special', 1, 1);

-- 샘플 발행 계획 데이터
INSERT INTO `issue_plans` (`product_id`, `issue_date`, `total_tickets`, `batch_size`, `start_number`, `notes`, `status`)
VALUES
(1, '2025-05-20', 10000, 1000, 'D20250520001', '5월 20일 일일 복권 발행', 'ready'),
(2, '2025-05-18', 20000, 2000, 'W20250518001', '5월 18일 주간 복권 발행', 'ready'),
(3, '2025-05-25', 15000, 1500, 'M20250525001', '5월 25일 월간 특별 복권 발행', 'draft');
