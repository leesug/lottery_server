-- 판매점 관리 데이터베이스 스키마

-- 판매점 정보 테이블
CREATE TABLE IF NOT EXISTS `stores` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `store_code` varchar(20) NOT NULL COMMENT '판매점 코드',
  `store_name` varchar(100) NOT NULL COMMENT '판매점명',
  `business_number` varchar(20) NOT NULL COMMENT '사업자 등록번호',
  `owner_name` varchar(50) NOT NULL COMMENT '대표자명',
  `phone` varchar(20) NOT NULL COMMENT '전화번호',
  `mobile` varchar(20) DEFAULT NULL COMMENT '휴대폰번호',
  `email` varchar(100) DEFAULT NULL COMMENT '이메일',
  `address` text NOT NULL COMMENT '주소',
  `postal_code` varchar(10) DEFAULT NULL COMMENT '우편번호',
  `region_code` varchar(10) DEFAULT NULL COMMENT '지역 코드',
  `region_name` varchar(50) DEFAULT NULL COMMENT '지역명',
  `latitude` decimal(10,7) DEFAULT NULL COMMENT '위도',
  `longitude` decimal(10,7) DEFAULT NULL COMMENT '경도',
  `opening_hours` varchar(100) DEFAULT NULL COMMENT '영업시간',
  `status` enum('active','inactive','pending','suspended','terminated') NOT NULL DEFAULT 'pending' COMMENT '상태',
  `type` enum('regular','premium','exclusive') NOT NULL DEFAULT 'regular' COMMENT '판매점 유형',
  `registration_date` date NOT NULL COMMENT '등록일',
  `approval_date` date DEFAULT NULL COMMENT '승인일',
  `termination_date` date DEFAULT NULL COMMENT '해지일',
  `bank_name` varchar(50) DEFAULT NULL COMMENT '은행명',
  `bank_account` varchar(50) DEFAULT NULL COMMENT '계좌번호',
  `bank_account_holder` varchar(50) DEFAULT NULL COMMENT '예금주',
  `notes` text DEFAULT NULL COMMENT '비고',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_by` int(11) DEFAULT NULL COMMENT '생성자 ID',
  `updated_by` int(11) DEFAULT NULL COMMENT '수정자 ID',
  PRIMARY KEY (`id`),
  UNIQUE KEY `store_code` (`store_code`),
  UNIQUE KEY `business_number` (`business_number`),
  KEY `status` (`status`),
  KEY `region_code` (`region_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='판매점 정보';

-- 판매점 계약 정보 테이블
CREATE TABLE IF NOT EXISTS `store_contracts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `store_id` int(11) NOT NULL COMMENT '판매점 ID',
  `contract_code` varchar(20) NOT NULL COMMENT '계약 코드',
  `contract_type` enum('new','renewal','amendment') NOT NULL COMMENT '계약 유형',
  `start_date` date NOT NULL COMMENT '계약 시작일',
  `end_date` date NOT NULL COMMENT '계약 종료일',
  `commission_rate` decimal(5,2) NOT NULL COMMENT '수수료율(%)',
  `monthly_target` int(11) DEFAULT NULL COMMENT '월 판매 목표액',
  `special_terms` text DEFAULT NULL COMMENT '특별 계약 조건',
  `attachment_path` varchar(255) DEFAULT NULL COMMENT '첨부파일 경로',
  `status` enum('draft','active','expired','terminated','renewed') NOT NULL DEFAULT 'draft' COMMENT '상태',
  `termination_reason` text DEFAULT NULL COMMENT '해지 사유',
  `termination_date` date DEFAULT NULL COMMENT '해지일',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_by` int(11) DEFAULT NULL COMMENT '생성자 ID',
  `updated_by` int(11) DEFAULT NULL COMMENT '수정자 ID',
  PRIMARY KEY (`id`),
  UNIQUE KEY `contract_code` (`contract_code`),
  KEY `store_id` (`store_id`),
  KEY `status` (`status`),
  CONSTRAINT `fk_store_contracts_store` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='판매점 계약 정보';

-- 판매점 담당자 정보 테이블
CREATE TABLE IF NOT EXISTS `store_contacts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `store_id` int(11) NOT NULL COMMENT '판매점 ID',
  `name` varchar(50) NOT NULL COMMENT '담당자명',
  `position` varchar(50) DEFAULT NULL COMMENT '직책',
  `phone` varchar(20) DEFAULT NULL COMMENT '전화번호',
  `mobile` varchar(20) NOT NULL COMMENT '휴대폰번호',
  `email` varchar(100) DEFAULT NULL COMMENT '이메일',
  `is_primary` tinyint(1) NOT NULL DEFAULT '0' COMMENT '주 담당자 여부',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `store_id` (`store_id`),
  CONSTRAINT `fk_store_contacts_store` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='판매점 담당자 정보';

-- 판매점 수수료 설정 테이블
CREATE TABLE IF NOT EXISTS `store_commission_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `store_id` int(11) NOT NULL COMMENT '판매점 ID',
  `product_id` int(11) DEFAULT NULL COMMENT '상품 ID (NULL인 경우 모든 상품)',
  `base_rate` decimal(5,2) NOT NULL COMMENT '기본 수수료율(%)',
  `volume_threshold_1` int(11) DEFAULT NULL COMMENT '판매량 임계값 1',
  `rate_1` decimal(5,2) DEFAULT NULL COMMENT '임계값 1 초과 시 수수료율',
  `volume_threshold_2` int(11) DEFAULT NULL COMMENT '판매량 임계값 2',
  `rate_2` decimal(5,2) DEFAULT NULL COMMENT '임계값 2 초과 시 수수료율',
  `volume_threshold_3` int(11) DEFAULT NULL COMMENT '판매량 임계값 3',
  `rate_3` decimal(5,2) DEFAULT NULL COMMENT '임계값 3 초과 시 수수료율',
  `effective_from` date NOT NULL COMMENT '적용 시작일',
  `effective_to` date DEFAULT NULL COMMENT '적용 종료일 (NULL인 경우 무기한)',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_by` int(11) DEFAULT NULL COMMENT '생성자 ID',
  PRIMARY KEY (`id`),
  KEY `store_id` (`store_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `fk_commission_settings_store` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='판매점 수수료 설정';

-- 판매점 장비 정보 테이블
CREATE TABLE IF NOT EXISTS `store_equipment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `store_id` int(11) NOT NULL COMMENT '판매점 ID',
  `equipment_code` varchar(20) NOT NULL COMMENT '장비 코드',
  `equipment_type` enum('terminal','printer','scanner','display','router','other') NOT NULL COMMENT '장비 유형',
  `model_name` varchar(50) NOT NULL COMMENT '모델명',
  `manufacturer` varchar(50) DEFAULT NULL COMMENT '제조사',
  `serial_number` varchar(50) NOT NULL COMMENT '시리얼 번호',
  `firmware_version` varchar(20) DEFAULT NULL COMMENT '펌웨어 버전',
  `installation_date` date NOT NULL COMMENT '설치일',
  `warranty_end_date` date DEFAULT NULL COMMENT '보증 만료일',
  `status` enum('operational','maintenance','faulty','replaced','retired') NOT NULL DEFAULT 'operational' COMMENT '상태',
  `ip_address` varchar(15) DEFAULT NULL COMMENT 'IP 주소',
  `mac_address` varchar(17) DEFAULT NULL COMMENT 'MAC 주소',
  `last_maintenance_date` date DEFAULT NULL COMMENT '마지막 점검일',
  `last_online` datetime DEFAULT NULL COMMENT '마지막 접속 시간',
  `notes` text DEFAULT NULL COMMENT '비고',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `equipment_code` (`equipment_code`),
  UNIQUE KEY `serial_number` (`serial_number`),
  KEY `store_id` (`store_id`),
  KEY `equipment_type` (`equipment_type`),
  KEY `status` (`status`),
  CONSTRAINT `fk_equipment_store` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='판매점 장비 정보';

-- 장비 유지보수 정보 테이블
CREATE TABLE IF NOT EXISTS `store_equipment_maintenance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `equipment_id` int(11) NOT NULL COMMENT '장비 ID',
  `maintenance_code` varchar(20) NOT NULL COMMENT '유지보수 코드',
  `maintenance_type` enum('regular','repair','emergency','replacement','firmware','other') NOT NULL COMMENT '유지보수 유형',
  `maintenance_date` date NOT NULL COMMENT '유지보수 일자',
  `technician_name` varchar(50) DEFAULT NULL COMMENT '담당 기술자',
  `technician_contact` varchar(20) DEFAULT NULL COMMENT '기술자 연락처',
  `issue_description` text DEFAULT NULL COMMENT '문제 설명',
  `action_taken` text DEFAULT NULL COMMENT '조치 사항',
  `parts_replaced` text DEFAULT NULL COMMENT '교체 부품',
  `cost` decimal(10,2) DEFAULT NULL COMMENT '비용',
  `result` enum('completed','pending','failed','rescheduled') NOT NULL DEFAULT 'pending' COMMENT '결과',
  `scheduled_date` date DEFAULT NULL COMMENT '예약 일자',
  `completion_date` date DEFAULT NULL COMMENT '완료 일자',
  `notes` text DEFAULT NULL COMMENT '비고',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_by` int(11) DEFAULT NULL COMMENT '생성자 ID',
  `updated_by` int(11) DEFAULT NULL COMMENT '수정자 ID',
  PRIMARY KEY (`id`),
  UNIQUE KEY `maintenance_code` (`maintenance_code`),
  KEY `equipment_id` (`equipment_id`),
  KEY `maintenance_type` (`maintenance_type`),
  KEY `result` (`result`),
  CONSTRAINT `fk_maintenance_equipment` FOREIGN KEY (`equipment_id`) REFERENCES `store_equipment` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='장비 유지보수 정보';

-- 판매점 판매 실적 테이블
CREATE TABLE IF NOT EXISTS `store_sales` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `store_id` int(11) NOT NULL COMMENT '판매점 ID',
  `product_id` int(11) NOT NULL COMMENT '상품 ID',
  `year` smallint(6) NOT NULL COMMENT '년도',
  `month` tinyint(4) NOT NULL COMMENT '월',
  `week` tinyint(4) DEFAULT NULL COMMENT '주차',
  `sales_amount` decimal(15,2) NOT NULL COMMENT '판매액',
  `tickets_sold` int(11) NOT NULL COMMENT '판매 티켓 수',
  `commission_amount` decimal(12,2) NOT NULL COMMENT '수수료 금액',
  `commission_rate` decimal(5,2) NOT NULL COMMENT '적용 수수료율',
  `settlement_status` enum('pending','processed','paid') NOT NULL DEFAULT 'pending' COMMENT '정산 상태',
  `settlement_date` date DEFAULT NULL COMMENT '정산일',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `store_product_year_month_week` (`store_id`,`product_id`,`year`,`month`,`week`),
  KEY `product_id` (`product_id`),
  KEY `year_month` (`year`,`month`),
  CONSTRAINT `fk_sales_store` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='판매점 판매 실적';

-- 판매점 방문 이력 테이블
CREATE TABLE IF NOT EXISTS `store_visits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `store_id` int(11) NOT NULL COMMENT '판매점 ID',
  `visitor_id` int(11) NOT NULL COMMENT '방문자 ID (직원/담당자)',
  `visit_date` date NOT NULL COMMENT '방문일',
  `visit_time` time DEFAULT NULL COMMENT '방문 시간',
  `visit_type` enum('regular','issue','training','equipment','audit','other') NOT NULL COMMENT '방문 유형',
  `purpose` text NOT NULL COMMENT '방문 목적',
  `findings` text DEFAULT NULL COMMENT '확인 사항',
  `action_items` text DEFAULT NULL COMMENT '조치 사항',
  `follow_up_required` tinyint(1) NOT NULL DEFAULT '0' COMMENT '후속 조치 필요 여부',
  `follow_up_date` date DEFAULT NULL COMMENT '후속 조치 예정일',
  `status` enum('planned','completed','cancelled','rescheduled') NOT NULL DEFAULT 'planned' COMMENT '상태',
  `notes` text DEFAULT NULL COMMENT '비고',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `store_id` (`store_id`),
  KEY `visitor_id` (`visitor_id`),
  KEY `visit_date` (`visit_date`),
  KEY `status` (`status`),
  CONSTRAINT `fk_visits_store` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='판매점 방문 이력';

-- 판매점 평가 정보 테이블
CREATE TABLE IF NOT EXISTS `store_evaluations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `store_id` int(11) NOT NULL COMMENT '판매점 ID',
  `evaluation_date` date NOT NULL COMMENT '평가일',
  `evaluator_id` int(11) NOT NULL COMMENT '평가자 ID',
  `sales_score` tinyint(4) DEFAULT NULL COMMENT '판매 실적 점수 (0-10)',
  `service_score` tinyint(4) DEFAULT NULL COMMENT '서비스 품질 점수 (0-10)',
  `cleanliness_score` tinyint(4) DEFAULT NULL COMMENT '청결도 점수 (0-10)',
  `compliance_score` tinyint(4) DEFAULT NULL COMMENT '규정 준수 점수 (0-10)',
  `location_score` tinyint(4) DEFAULT NULL COMMENT '입지 점수 (0-10)',
  `equipment_score` tinyint(4) DEFAULT NULL COMMENT '장비 관리 점수 (0-10)',
  `overall_score` decimal(4,2) DEFAULT NULL COMMENT '종합 점수',
  `strengths` text DEFAULT NULL COMMENT '강점',
  `weaknesses` text DEFAULT NULL COMMENT '약점',
  `recommendations` text DEFAULT NULL COMMENT '개선 권고사항',
  `evaluation_period` varchar(20) DEFAULT NULL COMMENT '평가 기간 (예: 2023Q1)',
  `next_evaluation_date` date DEFAULT NULL COMMENT '다음 평가 예정일',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `store_id` (`store_id`),
  KEY `evaluation_date` (`evaluation_date`),
  KEY `evaluator_id` (`evaluator_id`),
  CONSTRAINT `fk_evaluations_store` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='판매점 평가 정보';

-- 지역 정보 테이블
CREATE TABLE IF NOT EXISTS `regions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `region_code` varchar(10) NOT NULL COMMENT '지역 코드',
  `region_name` varchar(50) NOT NULL COMMENT '지역명',
  `parent_code` varchar(10) DEFAULT NULL COMMENT '상위 지역 코드',
  `region_level` tinyint(4) NOT NULL COMMENT '지역 레벨 (1: 시도, 2: 시군구, 3: 읍면동)',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `region_code` (`region_code`),
  KEY `parent_code` (`parent_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='지역 정보';

-- 테스트 데이터 삽입
-- 지역 정보 샘플 데이터
INSERT INTO `regions` (`region_code`, `region_name`, `parent_code`, `region_level`) VALUES
('11', '서울특별시', NULL, 1),
('11010', '종로구', '11', 2),
('11020', '중구', '11', 2),
('11030', '용산구', '11', 2),
('26', '부산광역시', NULL, 1),
('26010', '중구', '26', 2),
('26020', '서구', '26', 2),
('26030', '동구', '26', 2),
('41', '경기도', NULL, 1),
('41010', '수원시', '41', 2),
('41020', '성남시', '41', 2),
('41030', '의정부시', '41', 2);

-- 판매점 샘플 데이터
INSERT INTO `stores` 
(`store_code`, `store_name`, `business_number`, `owner_name`, `phone`, `mobile`, `email`, `address`, `postal_code`, `region_code`, `region_name`, `status`, `type`, `registration_date`, `approval_date`)
VALUES 
('ST001', '행운복권', '123-45-67890', '홍길동', '02-1234-5678', '010-1234-5678', 'hong@example.com', '서울시 종로구 종로 123', '03123', '11010', '종로구', 'active', 'regular', '2023-01-15', '2023-01-20'),
('ST002', '럭키로또', '234-56-78901', '김행운', '02-2345-6789', '010-2345-6789', 'kim@example.com', '서울시 중구 명동길 45', '04567', '11020', '중구', 'active', 'premium', '2023-02-10', '2023-02-15'),
('ST003', '드림복권방', '345-67-89012', '이꿈나', '051-345-6789', '010-3456-7890', 'lee@example.com', '부산시 중구 광복로 78', '48789', '26010', '중구', 'active', 'regular', '2023-03-05', '2023-03-10'),
('ST004', '황금로또', '456-78-90123', '박황금', '031-456-7890', '010-4567-8901', 'park@example.com', '경기도 수원시 팔달구 행궁로 90', '16458', '41010', '수원시', 'pending', 'regular', '2023-04-20', NULL),
('ST005', '대박복권', '567-89-01234', '최대박', '02-5678-9012', '010-5678-9012', 'choi@example.com', '서울시 용산구 이태원로 234', '04352', '11030', '용산구', 'active', 'exclusive', '2023-05-12', '2023-05-17');

-- 판매점 계약 샘플 데이터
INSERT INTO `store_contracts` 
(`store_id`, `contract_code`, `contract_type`, `start_date`, `end_date`, `commission_rate`, `monthly_target`, `status`)
VALUES 
(1, 'CONT-ST001-001', 'new', '2023-01-20', '2024-01-19', 5.50, 5000000, 'active'),
(2, 'CONT-ST002-001', 'new', '2023-02-15', '2024-02-14', 6.00, 7000000, 'active'),
(3, 'CONT-ST003-001', 'new', '2023-03-10', '2024-03-09', 5.50, 4500000, 'active'),
(5, 'CONT-ST005-001', 'new', '2023-05-17', '2024-05-16', 6.50, 10000000, 'active');

-- 판매점 담당자 샘플 데이터
INSERT INTO `store_contacts` 
(`store_id`, `name`, `position`, `phone`, `mobile`, `email`, `is_primary`)
VALUES 
(1, '홍길동', '대표', '02-1234-5678', '010-1234-5678', 'hong@example.com', 1),
(2, '김행운', '대표', '02-2345-6789', '010-2345-6789', 'kim@example.com', 1),
(2, '김직원', '매니저', '02-2345-6780', '010-8765-4321', 'employee@example.com', 0),
(3, '이꿈나', '대표', '051-345-6789', '010-3456-7890', 'lee@example.com', 1),
(5, '최대박', '대표', '02-5678-9012', '010-5678-9012', 'choi@example.com', 1);

-- 판매점 장비 샘플 데이터
INSERT INTO `store_equipment` 
(`store_id`, `equipment_code`, `equipment_type`, `model_name`, `manufacturer`, `serial_number`, `installation_date`, `status`, `last_maintenance_date`)
VALUES 
(1, 'EQ-TERM-001', 'terminal', 'LT-2000', 'LottoTech', 'SN12345678', '2023-01-25', 'operational', '2023-04-15'),
(1, 'EQ-PRINT-001', 'printer', 'LP-100', 'LottoTech', 'SN87654321', '2023-01-25', 'operational', '2023-04-15'),
(2, 'EQ-TERM-002', 'terminal', 'LT-2000', 'LottoTech', 'SN23456789', '2023-02-20', 'operational', '2023-05-10'),
(2, 'EQ-PRINT-002', 'printer', 'LP-100', 'LottoTech', 'SN98765432', '2023-02-20', 'operational', '2023-05-10'),
(3, 'EQ-TERM-003', 'terminal', 'LT-1500', 'LottoTech', 'SN34567890', '2023-03-15', 'maintenance', '2023-05-20'),
(5, 'EQ-TERM-005', 'terminal', 'LT-3000', 'LottoTech', 'SN56789012', '2023-05-22', 'operational', NULL);

-- 장비 유지보수 샘플 데이터
INSERT INTO `store_equipment_maintenance` 
(`equipment_id`, `maintenance_code`, `maintenance_type`, `maintenance_date`, `technician_name`, `issue_description`, `action_taken`, `result`, `completion_date`)
VALUES 
(1, 'MAINT-001', 'regular', '2023-04-15', '정비사1', '정기 점검', '소프트웨어 업데이트 및 청소', 'completed', '2023-04-15'),
(2, 'MAINT-002', 'regular', '2023-04-15', '정비사1', '정기 점검', '프린터 헤드 청소 및 테스트', 'completed', '2023-04-15'),
(3, 'MAINT-003', 'regular', '2023-05-10', '정비사2', '정기 점검', '소프트웨어 업데이트 및 청소', 'completed', '2023-05-10'),
(4, 'MAINT-004', 'regular', '2023-05-10', '정비사2', '정기 점검', '프린터 헤드 청소 및 테스트', 'completed', '2023-05-10'),
(5, 'MAINT-005', 'repair', '2023-05-20', '정비사3', '화면 터치 인식 오류', '터치스크린 교체', 'pending', NULL);

-- 판매점 판매 실적 샘플 데이터
INSERT INTO `store_sales` 
(`store_id`, `product_id`, `year`, `month`, `week`, `sales_amount`, `tickets_sold`, `commission_amount`, `commission_rate`, `settlement_status`, `settlement_date`)
VALUES 
(1, 1, 2023, 1, NULL, 4500000, 4500, 247500, 5.50, 'paid', '2023-02-10'),
(1, 1, 2023, 2, NULL, 5200000, 5200, 286000, 5.50, 'paid', '2023-03-10'),
(1, 1, 2023, 3, NULL, 4800000, 4800, 264000, 5.50, 'paid', '2023-04-10'),
(1, 1, 2023, 4, NULL, 5500000, 5500, 302500, 5.50, 'paid', '2023-05-10'),
(2, 1, 2023, 2, NULL, 6800000, 6800, 408000, 6.00, 'paid', '2023-03-10'),
(2, 1, 2023, 3, NULL, 7200000, 7200, 432000, 6.00, 'paid', '2023-04-10'),
(2, 1, 2023, 4, NULL, 7500000, 7500, 450000, 6.00, 'paid', '2023-05-10'),
(3, 1, 2023, 3, NULL, 4200000, 4200, 231000, 5.50, 'paid', '2023-04-10'),
(3, 1, 2023, 4, NULL, 4600000, 4600, 253000, 5.50, 'paid', '2023-05-10'),
(5, 1, 2023, 5, NULL, 9500000, 9500, 617500, 6.50, 'pending', NULL);
