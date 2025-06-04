-- 판매점 관리를 위한 데이터베이스 테이블 스키마

-- 판매점 테이블
CREATE TABLE IF NOT EXISTS `stores` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `store_code` varchar(20) NOT NULL COMMENT '판매점 고유 코드',
  `store_name` varchar(100) NOT NULL COMMENT '판매점명',
  `owner_name` varchar(100) NOT NULL COMMENT '대표자명',
  `email` varchar(100) DEFAULT NULL COMMENT '이메일',
  `phone` varchar(20) NOT NULL COMMENT '전화번호',
  `address` text NOT NULL COMMENT '주소',
  `city` varchar(50) NOT NULL COMMENT '도시',
  `state` varchar(50) DEFAULT NULL COMMENT '주/도',
  `postal_code` varchar(20) DEFAULT NULL COMMENT '우편번호',
  `country` varchar(50) NOT NULL DEFAULT 'Nepal' COMMENT '국가',
  `gps_latitude` decimal(10,7) DEFAULT NULL COMMENT 'GPS 위도',
  `gps_longitude` decimal(10,7) DEFAULT NULL COMMENT 'GPS 경도',
  `business_license_number` varchar(50) DEFAULT NULL COMMENT '사업자등록번호',
  `tax_id` varchar(50) DEFAULT NULL COMMENT '세금ID',
  `bank_name` varchar(100) DEFAULT NULL COMMENT '은행명',
  `bank_account_number` varchar(50) DEFAULT NULL COMMENT '계좌번호',
  `bank_ifsc_code` varchar(20) DEFAULT NULL COMMENT '은행 지점 코드',
  `status` enum('pending','active','inactive','terminated') NOT NULL DEFAULT 'pending' COMMENT '상태',
  `store_category` enum('standard','premium','exclusive') NOT NULL DEFAULT 'standard' COMMENT '판매점 카테고리',
  `store_size` enum('small','medium','large') NOT NULL DEFAULT 'small' COMMENT '판매점 규모',
  `notes` text DEFAULT NULL COMMENT '비고',
  `registration_date` datetime NOT NULL DEFAULT current_timestamp() COMMENT '등록일',
  `created_by` int(11) DEFAULT NULL COMMENT '생성자 ID',
  `updated_by` int(11) DEFAULT NULL COMMENT '수정자 ID',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT '생성일시',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '수정일시',
  PRIMARY KEY (`id`),
  UNIQUE KEY `store_code` (`store_code`),
  KEY `store_name` (`store_name`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='판매점 정보';

-- 판매점 계약 테이블
CREATE TABLE IF NOT EXISTS `contracts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `store_id` int(11) NOT NULL COMMENT '판매점 ID',
  `contract_code` varchar(20) NOT NULL COMMENT '계약 코드',
  `contract_type` enum('standard','premium','exclusive') NOT NULL DEFAULT 'standard' COMMENT '계약 유형',
  `start_date` date NOT NULL COMMENT '계약 시작일',
  `end_date` date NOT NULL COMMENT '계약 만료일',
  `status` enum('pending','active','expired','terminated') NOT NULL DEFAULT 'pending' COMMENT '계약 상태',
  `commission_rate` decimal(5,2) NOT NULL DEFAULT 5.00 COMMENT '커미션 비율 (%)',
  `signing_bonus` decimal(10,2) DEFAULT 0.00 COMMENT '계약 보너스',
  `payment_terms` varchar(100) DEFAULT NULL COMMENT '지급 조건',
  `renewal_terms` text DEFAULT NULL COMMENT '갱신 조건',
  `termination_terms` text DEFAULT NULL COMMENT '해지 조건',
  `signed_by` varchar(100) DEFAULT NULL COMMENT '서명자',
  `signed_date` date DEFAULT NULL COMMENT '서명일',
  `document_path` varchar(255) DEFAULT NULL COMMENT '계약서 파일 경로',
  `notes` text DEFAULT NULL COMMENT '비고',
  `created_by` int(11) DEFAULT NULL COMMENT '생성자 ID',
  `updated_by` int(11) DEFAULT NULL COMMENT '수정자 ID',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT '생성일시',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '수정일시',
  PRIMARY KEY (`id`),
  UNIQUE KEY `contract_code` (`contract_code`),
  KEY `store_id` (`store_id`),
  KEY `status` (`status`),
  CONSTRAINT `fk_contracts_store_id` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='판매점 계약 정보';

-- 판매점 장비 테이블
CREATE TABLE IF NOT EXISTS `equipment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `store_id` int(11) NOT NULL COMMENT '판매점 ID',
  `equipment_code` varchar(20) NOT NULL COMMENT '장비 코드',
  `equipment_type` enum('terminal','printer','scanner','pos','other') NOT NULL COMMENT '장비 유형',
  `model` varchar(100) NOT NULL COMMENT '모델명',
  `serial_number` varchar(100) NOT NULL COMMENT '시리얼 번호',
  `manufacturer` varchar(100) DEFAULT NULL COMMENT '제조사',
  `purchase_date` date DEFAULT NULL COMMENT '구매일',
  `warranty_end_date` date DEFAULT NULL COMMENT '보증 만료일',
  `status` enum('active','inactive','maintenance','replaced','retired') NOT NULL DEFAULT 'active' COMMENT '상태',
  `ip_address` varchar(50) DEFAULT NULL COMMENT 'IP 주소',
  `mac_address` varchar(50) DEFAULT NULL COMMENT 'MAC 주소',
  `software_version` varchar(50) DEFAULT NULL COMMENT '소프트웨어 버전',
  `firmware_version` varchar(50) DEFAULT NULL COMMENT '펌웨어 버전',
  `last_maintenance_date` date DEFAULT NULL COMMENT '마지막 유지보수일',
  `next_maintenance_date` date DEFAULT NULL COMMENT '다음 유지보수 예정일',
  `notes` text DEFAULT NULL COMMENT '비고',
  `created_by` int(11) DEFAULT NULL COMMENT '생성자 ID',
  `updated_by` int(11) DEFAULT NULL COMMENT '수정자 ID',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT '생성일시',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '수정일시',
  PRIMARY KEY (`id`),
  UNIQUE KEY `equipment_code` (`equipment_code`),
  UNIQUE KEY `serial_number` (`serial_number`),
  KEY `store_id` (`store_id`),
  KEY `status` (`status`),
  KEY `equipment_type` (`equipment_type`),
  CONSTRAINT `fk_equipment_store_id` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='판매점 장비 정보';

-- 장비 유지보수 테이블
CREATE TABLE IF NOT EXISTS `equipment_maintenance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `equipment_id` int(11) NOT NULL COMMENT '장비 ID',
  `maintenance_code` varchar(20) NOT NULL COMMENT '유지보수 코드',
  `maintenance_type` enum('regular','repair','upgrade','inspection','other') NOT NULL COMMENT '유지보수 유형',
  `start_date` datetime NOT NULL COMMENT '시작일시',
  `end_date` datetime DEFAULT NULL COMMENT '종료일시',
  `status` enum('scheduled','in_progress','completed','cancelled') NOT NULL DEFAULT 'scheduled' COMMENT '상태',
  `technician_name` varchar(100) DEFAULT NULL COMMENT '기술자명',
  `technician_contact` varchar(50) DEFAULT NULL COMMENT '기술자 연락처',
  `cost` decimal(10,2) DEFAULT 0.00 COMMENT '비용',
  `parts_replaced` text DEFAULT NULL COMMENT '교체된 부품',
  `issues_found` text DEFAULT NULL COMMENT '발견된 문제',
  `actions_taken` text DEFAULT NULL COMMENT '조치 사항',
  `result` text DEFAULT NULL COMMENT '결과',
  `notes` text DEFAULT NULL COMMENT '비고',
  `created_by` int(11) DEFAULT NULL COMMENT '생성자 ID',
  `updated_by` int(11) DEFAULT NULL COMMENT '수정자 ID',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT '생성일시',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '수정일시',
  PRIMARY KEY (`id`),
  UNIQUE KEY `maintenance_code` (`maintenance_code`),
  KEY `equipment_id` (`equipment_id`),
  KEY `status` (`status`),
  KEY `maintenance_type` (`maintenance_type`),
  CONSTRAINT `fk_maintenance_equipment_id` FOREIGN KEY (`equipment_id`) REFERENCES `equipment` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='장비 유지보수 정보';

-- 판매점 실적 테이블
CREATE TABLE IF NOT EXISTS `store_performance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `store_id` int(11) NOT NULL COMMENT '판매점 ID',
  `period_type` enum('daily','weekly','monthly','quarterly','yearly') NOT NULL COMMENT '기간 유형',
  `period_start` date NOT NULL COMMENT '기간 시작일',
  `period_end` date NOT NULL COMMENT '기간 종료일',
  `sales_count` int(11) NOT NULL DEFAULT 0 COMMENT '판매 건수',
  `sales_amount` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT '판매 금액',
  `commission_amount` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT '커미션 금액',
  `prize_payout` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT '당첨금 지급액',
  `net_revenue` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT '순수익',
  `customer_count` int(11) NOT NULL DEFAULT 0 COMMENT '고객 수',
  `return_count` int(11) NOT NULL DEFAULT 0 COMMENT '환불 건수',
  `return_amount` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT '환불 금액',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT '생성일시',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '수정일시',
  PRIMARY KEY (`id`),
  UNIQUE KEY `store_period` (`store_id`,`period_type`,`period_start`,`period_end`),
  KEY `store_id` (`store_id`),
  KEY `period_type` (`period_type`),
  KEY `period_start` (`period_start`),
  KEY `period_end` (`period_end`),
  CONSTRAINT `fk_performance_store_id` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='판매점 실적 정보';

-- 판매점 결제 정산 테이블
CREATE TABLE IF NOT EXISTS `store_settlements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `store_id` int(11) NOT NULL COMMENT '판매점 ID',
  `settlement_code` varchar(20) NOT NULL COMMENT '정산 코드',
  `period_start` date NOT NULL COMMENT '정산 기간 시작일',
  `period_end` date NOT NULL COMMENT '정산 기간 종료일',
  `sales_amount` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT '판매 금액',
  `commission_amount` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT '커미션 금액',
  `prize_payout` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT '당첨금 지급액',
  `adjustments` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT '조정액',
  `net_amount` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT '최종 정산액',
  `status` enum('pending','processing','completed','cancelled') NOT NULL DEFAULT 'pending' COMMENT '상태',
  `payment_date` datetime DEFAULT NULL COMMENT '지급일시',
  `payment_method` enum('bank_transfer','check','cash','credit','adjustment') DEFAULT NULL COMMENT '지급 방법',
  `payment_reference` varchar(100) DEFAULT NULL COMMENT '지급 참조번호',
  `notes` text DEFAULT NULL COMMENT '비고',
  `created_by` int(11) DEFAULT NULL COMMENT '생성자 ID',
  `approved_by` int(11) DEFAULT NULL COMMENT '승인자 ID',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT '생성일시',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT '수정일시',
  PRIMARY KEY (`id`),
  UNIQUE KEY `settlement_code` (`settlement_code`),
  KEY `store_id` (`store_id`),
  KEY `status` (`status`),
  KEY `period_start` (`period_start`),
  KEY `period_end` (`period_end`),
  CONSTRAINT `fk_settlements_store_id` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='판매점 정산 정보';

-- 테스트용 판매점 데이터
INSERT INTO `stores` (`store_code`, `store_name`, `owner_name`, `email`, `phone`, 
                     `address`, `city`, `state`, `postal_code`, `country`, 
                     `status`, `store_category`, `store_size`, `registration_date`) 
VALUES 
('STORE12345678', '네팔 마트 #23', '김철수', 'test@example.com', '01012345678',
 '판매점 주소', '서울', '서울특별시', '12345', '대한민국',
 'active', 'standard', 'medium', NOW()),
('STORE23456789', '카트만두 센터 #05', '이영희', 'test2@example.com', '01098765432',
 '판매점 주소 2', '부산', '부산광역시', '54321', '대한민국',
 'active', 'premium', 'large', NOW()),
('STORE34567890', '포카라 샵 #18', '박지민', 'test3@example.com', '01011112222',
 '판매점 주소 3', '대구', '대구광역시', '33333', '대한민국',
 'active', 'standard', 'small', NOW());

-- 테스트용 계약 데이터
INSERT INTO `contracts` (`store_id`, `contract_code`, `contract_type`, 
                        `start_date`, `end_date`, `status`, `commission_rate`, 
                        `signing_bonus`, `signed_by`, `signed_date`)
VALUES
(1, 'CONTRACT001', 'standard', '2025-01-01', '2025-12-31', 'active', 5.00, 
 10000.00, '김관리자', '2024-12-15'),
(2, 'CONTRACT002', 'premium', '2025-02-01', '2026-01-31', 'active', 7.50, 
 15000.00, '이관리자', '2025-01-15'),
(3, 'CONTRACT003', 'standard', '2025-03-01', '2026-02-28', 'active', 5.00, 
 10000.00, '박관리자', '2025-02-15');

-- 테스트용 장비 데이터
INSERT INTO `equipment` (`store_id`, `equipment_code`, `equipment_type`, 
                        `model`, `serial_number`, `manufacturer`,
                        `purchase_date`, `status`, `software_version`)
VALUES
(1, 'EQUIP001', 'terminal', 'LT-2000', 'SN123456789', '로또테크',
 '2025-01-10', 'active', '2.1.3'),
(2, 'EQUIP002', 'printer', 'LP-1000', 'SN234567890', '로또테크',
 '2025-01-20', 'active', '1.5.2'),
(3, 'EQUIP003', 'terminal', 'LT-2000', 'SN345678901', '로또테크',
 '2025-02-05', 'active', '2.1.3');

-- 테스트용 장비 유지보수 데이터
INSERT INTO `equipment_maintenance` (`equipment_id`, `maintenance_code`, `maintenance_type`,
                                  `start_date`, `end_date`, `status`,
                                  `technician_name`, `technician_contact`, `cost`,
                                  `actions_taken`, `result`)
VALUES
(1, 'MAINT001', 'regular', '2025-03-15 10:00:00', '2025-03-15 11:30:00', 'completed',
 '정비사1', '010-1234-5678', 50000.00,
 '정기 유지보수 수행', '정상 작동 확인'),
(2, 'MAINT002', 'repair', '2025-03-20 14:00:00', '2025-03-20 16:00:00', 'completed',
 '정비사2', '010-2345-6789', 120000.00,
 '프린터 헤드 교체', '인쇄 품질 복구'),
(3, 'MAINT003', 'regular', '2025-04-10 09:00:00', NULL, 'scheduled',
 '정비사1', '010-1234-5678', 50000.00,
 '정기 유지보수 예정', NULL);
