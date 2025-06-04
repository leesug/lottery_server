-- 최종 수정된 데이터베이스 이전 SQL 스크립트
-- lotto_system에서 lotto_server로 데이터 이전

-- ---------------------------------------------------
-- 사용자 데이터 이전 (충돌 제외)
-- ---------------------------------------------------
INSERT INTO `lotto_server`.`users` 
(username, email, password, status, created_at, updated_at)
SELECT ls.username, ls.email, ls.password, ls.status, ls.created_at, ls.updated_at
FROM `lotto_system`.`users` ls
LEFT JOIN `lotto_server`.`users` lv ON ls.email = lv.email
WHERE lv.id IS NULL;

-- ---------------------------------------------------
-- 로그인 로그 테이블 이전
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `lotto_server`.`login_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` varchar(255) NOT NULL,
  `status` enum('success','failed') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ID 충돌을 피하기 위해 ID 필드를 제외하고 삽입
INSERT INTO `lotto_server`.`login_logs` 
(user_id, ip_address, user_agent, status, created_at)
SELECT user_id, ip_address, user_agent, status, created_at 
FROM `lotto_system`.`login_logs`;

-- ---------------------------------------------------
-- 비밀번호 재설정 토큰 테이블 이전
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `lotto_server`.`password_reset_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expiry_date` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ID 충돌을 피하기 위해 ID 필드를 제외하고 삽입
INSERT INTO `lotto_server`.`password_reset_tokens` 
(user_id, token, expiry_date, created_at)
SELECT user_id, token, expiry_date, created_at 
FROM `lotto_system`.`password_reset_tokens`;

-- ---------------------------------------------------
-- system_logs 테이블 데이터 이전
-- ---------------------------------------------------
-- 이미 있는 테이블인지 확인 후 데이터 이전 
-- 두 테이블의 열 이름이 다를 수 있으므로 가장 기본적인 열만 이전
INSERT INTO `lotto_server`.`system_logs` 
(log_type, message, ip_address, user_id, created_at)
SELECT log_type, message, ip_address, user_id, created_at 
FROM `lotto_system`.`system_logs`;

-- ---------------------------------------------------
-- winnings 테이블 이전
-- ---------------------------------------------------
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
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ID 충돌을 피하기 위해 ID 필드를 제외하고 삽입
INSERT INTO `lotto_server`.`winnings` 
(ticket_id, draw_id, prize_rank, prize_amount, status, payment_date, created_at, updated_at)
SELECT ticket_id, draw_id, prize_rank, prize_amount, status, payment_date, created_at, updated_at
FROM `lotto_system`.`winnings`;

-- 마이그레이션 완료 후 lotto_system 데이터베이스 삭제
DROP DATABASE IF EXISTS `lotto_system`;
