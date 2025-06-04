-- 데이터베이스 이전 SQL 스크립트
-- lotto_system에서 lotto_server로 데이터 이전

-- 로그인 로그 테이블 이전
CREATE TABLE IF NOT EXISTS `lotto_server`.`login_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` varchar(255) NOT NULL,
  `status` enum('success','failed') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `lotto_server`.`login_logs` 
SELECT * FROM `lotto_system`.`login_logs`;

-- 비밀번호 재설정 토큰 테이블 이전
CREATE TABLE IF NOT EXISTS `lotto_server`.`password_reset_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `lotto_server`.`password_reset_tokens` 
SELECT * FROM `lotto_system`.`password_reset_tokens`;

-- system_logs 테이블 데이터만 이전 (구조는 유지)
INSERT IGNORE INTO `lotto_server`.`system_logs` 
SELECT * FROM `lotto_system`.`system_logs`;

-- terminals 테이블 데이터만 이전 (구조는 유지)
INSERT IGNORE INTO `lotto_server`.`terminals` 
SELECT * FROM `lotto_system`.`terminals`;

-- tickets 테이블 데이터만 이전 (구조는 유지)
INSERT IGNORE INTO `lotto_server`.`tickets` 
SELECT * FROM `lotto_system`.`tickets`;

-- users 테이블에 중복되지 않는 사용자만 이전
INSERT IGNORE INTO `lotto_server`.`users` 
SELECT * FROM `lotto_system`.`users`;

-- winnings 테이블 이전
CREATE TABLE IF NOT EXISTS `lotto_server`.`winnings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ticket_id` int(11) NOT NULL,
  `draw_id` int(11) NOT NULL,
  `prize_rank` int(11) NOT NULL,
  `prize_amount` decimal(15,2) NOT NULL,
  `status` enum('pending','paid','cancelled') NOT NULL DEFAULT 'pending',
  `payment_date` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `ticket_id` (`ticket_id`),
  KEY `draw_id` (`draw_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `lotto_server`.`winnings` 
SELECT * FROM `lotto_system`.`winnings`;

-- lotto_system 데이터베이스 삭제
DROP DATABASE IF EXISTS `lotto_system`;
