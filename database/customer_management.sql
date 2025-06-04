-- 고객 관리를 위한 테이블 생성 SQL

-- 고객 정보 테이블 
CREATE TABLE IF NOT EXISTS `lotto_customers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_code` varchar(20) NOT NULL COMMENT '고객 고유번호',
  `name` varchar(100) NOT NULL COMMENT '고객명',
  `email` varchar(255) NOT NULL COMMENT '이메일',
  `phone` varchar(20) NOT NULL COMMENT '전화번호',
  `address` text COMMENT '주소',
  `birth_date` date DEFAULT NULL COMMENT '생년월일',
  `gender` enum('M','F','O') DEFAULT NULL COMMENT '성별',
  `status` enum('active','inactive','suspended','pending') NOT NULL DEFAULT 'pending' COMMENT '상태',
  `level` varchar(20) DEFAULT 'regular' COMMENT '고객 등급',
  `registration_date` datetime NOT NULL COMMENT '가입일',
  `last_login` datetime DEFAULT NULL COMMENT '마지막 로그인',
  `notes` text COMMENT '비고',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_by` int(11) DEFAULT NULL COMMENT '생성자 ID',
  `updated_by` int(11) DEFAULT NULL COMMENT '수정자 ID',
  PRIMARY KEY (`id`),
  UNIQUE KEY `customer_code` (`customer_code`),
  UNIQUE KEY `email` (`email`),
  KEY `status` (`status`),
  KEY `level` (`level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='고객 정보';

-- 고객 인증 정보 테이블
CREATE TABLE IF NOT EXISTS `lotto_customer_verification` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL COMMENT '고객 ID',
  `id_verified` tinyint(1) NOT NULL DEFAULT '0' COMMENT '신분증 인증 여부',
  `id_verification_date` datetime DEFAULT NULL COMMENT '신분증 인증 일시',
  `id_verification_method` varchar(50) DEFAULT NULL COMMENT '신분증 인증 방법',
  `id_verification_by` int(11) DEFAULT NULL COMMENT '신분증 인증 담당자',
  `email_verified` tinyint(1) NOT NULL DEFAULT '0' COMMENT '이메일 인증 여부',
  `email_verification_date` datetime DEFAULT NULL COMMENT '이메일 인증 일시',
  `email_verification_token` varchar(255) DEFAULT NULL COMMENT '이메일 인증 토큰',
  `phone_verified` tinyint(1) NOT NULL DEFAULT '0' COMMENT '전화번호 인증 여부',
  `phone_verification_date` datetime DEFAULT NULL COMMENT '전화번호 인증 일시',
  `phone_verification_code` varchar(10) DEFAULT NULL COMMENT '전화번호 인증 코드',
  `address_verified` tinyint(1) NOT NULL DEFAULT '0' COMMENT '주소 인증 여부',
  `address_verification_date` datetime DEFAULT NULL COMMENT '주소 인증 일시',
  `tfa_enabled` tinyint(1) NOT NULL DEFAULT '0' COMMENT '2단계 인증 활성화 여부',
  `tfa_secret` varchar(255) DEFAULT NULL COMMENT '2단계 인증 비밀키',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `customer_id` (`customer_id`),
  CONSTRAINT `fk_customer_verification_customer` FOREIGN KEY (`customer_id`) REFERENCES `lotto_customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='고객 인증 정보';

-- 고객 거래 내역 테이블
CREATE TABLE IF NOT EXISTS `lotto_customer_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `transaction_code` varchar(30) NOT NULL COMMENT '거래 코드',
  `customer_id` int(11) NOT NULL COMMENT '고객 ID',
  `transaction_type` enum('purchase','refund','withdrawal','deposit','prize','commission','adjustment') NOT NULL COMMENT '거래 유형',
  `amount` decimal(12,2) NOT NULL COMMENT '금액',
  `status` enum('pending','completed','cancelled','failed') NOT NULL DEFAULT 'pending' COMMENT '상태',
  `notes` text COMMENT '비고',
  `payment_method` varchar(50) DEFAULT NULL COMMENT '결제 방법',
  `reference_id` varchar(255) DEFAULT NULL COMMENT '참조 ID',
  `transaction_date` datetime NOT NULL COMMENT '거래 일시',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_by` int(11) DEFAULT NULL COMMENT '생성자 ID',
  `updated_by` int(11) DEFAULT NULL COMMENT '수정자 ID',
  PRIMARY KEY (`id`),
  UNIQUE KEY `transaction_code` (`transaction_code`),
  KEY `customer_id` (`customer_id`),
  KEY `transaction_type` (`transaction_type`),
  KEY `status` (`status`),
  KEY `transaction_date` (`transaction_date`),
  CONSTRAINT `fk_customer_transactions_customer` FOREIGN KEY (`customer_id`) REFERENCES `lotto_customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='고객 거래 내역';

-- 고객 문서 테이블
CREATE TABLE IF NOT EXISTS `lotto_customer_documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL COMMENT '고객 ID',
  `document_type` enum('id_card','passport','driver_license','utility_bill','bank_statement','other') NOT NULL COMMENT '문서 유형',
  `file_name` varchar(255) NOT NULL COMMENT '파일명',
  `file_path` varchar(255) NOT NULL COMMENT '파일 경로',
  `file_size` int(11) NOT NULL COMMENT '파일 크기',
  `mime_type` varchar(100) NOT NULL COMMENT '파일 MIME 타입',
  `upload_date` datetime NOT NULL COMMENT '업로드 일시',
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending' COMMENT '상태',
  `verified_by` int(11) DEFAULT NULL COMMENT '검증 담당자',
  `verified_date` datetime DEFAULT NULL COMMENT '검증 일시',
  `rejection_reason` text COMMENT '거부 사유',
  `notes` text COMMENT '비고',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_by` int(11) DEFAULT NULL COMMENT '생성자 ID',
  `updated_by` int(11) DEFAULT NULL COMMENT '수정자 ID',
  PRIMARY KEY (`id`),
  KEY `customer_id` (`customer_id`),
  KEY `document_type` (`document_type`),
  KEY `status` (`status`),
  CONSTRAINT `fk_customer_documents_customer` FOREIGN KEY (`customer_id`) REFERENCES `lotto_customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='고객 문서';

-- 고객 활동 내역 테이블
CREATE TABLE IF NOT EXISTS `lotto_customer_activities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL COMMENT '고객 ID',
  `activity_type` varchar(50) NOT NULL COMMENT '활동 유형',
  `activity_details` text COMMENT '활동 세부 정보',
  `ip_address` varchar(45) DEFAULT NULL COMMENT 'IP 주소',
  `user_agent` text COMMENT '사용자 에이전트',
  `activity_date` datetime NOT NULL COMMENT '활동 일시',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `customer_id` (`customer_id`),
  KEY `activity_type` (`activity_type`),
  KEY `activity_date` (`activity_date`),
  CONSTRAINT `fk_customer_activities_customer` FOREIGN KEY (`customer_id`) REFERENCES `lotto_customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='고객 활동 내역';

-- 고객 선호도 설정 테이블
CREATE TABLE IF NOT EXISTS `lotto_customer_preferences` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL COMMENT '고객 ID',
  `preference_key` varchar(50) NOT NULL COMMENT '설정 키',
  `preference_value` text COMMENT '설정 값',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `customer_preference` (`customer_id`,`preference_key`),
  CONSTRAINT `fk_customer_preferences_customer` FOREIGN KEY (`customer_id`) REFERENCES `lotto_customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='고객 선호도 설정';

-- 테스트 데이터 추가
INSERT INTO `lotto_customers` 
(`customer_code`, `name`, `email`, `phone`, `address`, `birth_date`, `gender`, `status`, `level`, `registration_date`, `notes`)
VALUES 
('CUST000001', '홍길동', 'hong@example.com', '010-1234-5678', '서울시 강남구 테헤란로 123', '1990-01-15', 'M', 'active', 'vip', NOW(), '우수 고객'),
('CUST000002', '김영희', 'kim@example.com', '010-9876-5432', '서울시 마포구 월드컵북로 456', '1985-05-20', 'F', 'active', 'regular', NOW(), ''),
('CUST000003', '이철수', 'lee@example.com', '010-5555-1234', '경기도 성남시 분당구 판교로 789', '1995-11-30', 'M', 'pending', 'regular', NOW(), '신규 가입');

-- 고객 인증 정보 추가
INSERT INTO `lotto_customer_verification` 
(`customer_id`, `id_verified`, `id_verification_date`, `id_verification_method`, `email_verified`, `email_verification_date`, `phone_verified`, `phone_verification_date`)
VALUES 
(1, 1, NOW(), 'admin_verification', 1, NOW(), 1, NOW()),
(2, 1, NOW(), 'admin_verification', 1, NOW(), 1, NOW()),
(3, 0, NULL, NULL, 1, NOW(), 0, NULL);

-- 고객 거래 내역 추가
INSERT INTO `lotto_customer_transactions` 
(`transaction_code`, `customer_id`, `transaction_type`, `amount`, `status`, `payment_method`, `transaction_date`)
VALUES 
('TR20250517001', 1, 'purchase', 50000.00, 'completed', 'card', NOW()),
('TR20250517002', 1, 'prize', 100000.00, 'completed', 'bank_transfer', NOW()),
('TR20250517003', 2, 'purchase', 30000.00, 'completed', 'card', NOW()),
('TR20250517004', 3, 'deposit', 20000.00, 'pending', 'bank_transfer', NOW());
