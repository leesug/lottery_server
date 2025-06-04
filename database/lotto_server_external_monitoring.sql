-- phpMyAdmin SQL 스크립트
-- 외부접속감시 데이터베이스 테이블 생성 및 샘플 데이터 삽입
-- 작성일: 2025-05-19

-- 데이터베이스 사용 선택
USE `lotto_server`;

-- 외래키 체크 해제 (테이블 생성/수정 중 오류 방지)
SET FOREIGN_KEY_CHECKS = 0;

-- 외부접속감시 로그 테이블
CREATE TABLE IF NOT EXISTS `external_monitoring_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entity_type` enum('broadcaster','bank','government','fund') NOT NULL COMMENT '엔터티 유형: 방송국, 은행, 정부, 기금처',
  `entity_id` int(11) NOT NULL COMMENT '관련 엔터티 ID',
  `activity_type` varchar(50) NOT NULL COMMENT '활동 유형',
  `description` text NOT NULL COMMENT '활동 설명',
  `ip_address` varchar(45) DEFAULT NULL COMMENT 'IP 주소',
  `user_agent` varchar(255) DEFAULT NULL COMMENT '사용자 에이전트',
  `user_id` int(11) DEFAULT NULL COMMENT '사용자 ID',
  `log_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '로그 기록 시간',
  PRIMARY KEY (`id`),
  KEY `idx_entity_type` (`entity_type`),
  KEY `idx_entity_id` (`entity_id`),
  KEY `idx_log_date` (`log_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='외부 접속 감시 로그';

-- 추첨 방송국 테이블
CREATE TABLE IF NOT EXISTS `broadcaster` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL COMMENT '방송국 이름',
  `contact_person` varchar(50) NOT NULL COMMENT '담당자 이름',
  `contact_phone` varchar(20) NOT NULL COMMENT '담당자 전화번호',
  `contact_email` varchar(100) NOT NULL COMMENT '담당자 이메일',
  `address` varchar(255) NOT NULL COMMENT '주소',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active' COMMENT '상태',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '생성 시간',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정 시간',
  `created_by` int(11) DEFAULT NULL COMMENT '생성자 ID',
  `updated_by` int(11) DEFAULT NULL COMMENT '수정자 ID',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='추첨 방송국 정보';

-- 추첨 체크리스트 테이블
CREATE TABLE IF NOT EXISTS `broadcaster_checklist` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `draw_id` int(11) NOT NULL COMMENT '추첨 ID',
  `broadcaster_id` int(11) NOT NULL COMMENT '방송국 ID',
  `checklist_item` varchar(255) NOT NULL COMMENT '체크리스트 항목',
  `is_completed` tinyint(1) NOT NULL DEFAULT 0 COMMENT '완료 여부',
  `completion_date` datetime DEFAULT NULL COMMENT '완료 시간',
  `remarks` text DEFAULT NULL COMMENT '비고',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '생성 시간',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정 시간',
  PRIMARY KEY (`id`),
  KEY `idx_draw_id` (`draw_id`),
  KEY `idx_broadcaster_id` (`broadcaster_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='추첨 방송국 체크리스트';

-- 추첨 일정 테이블
CREATE TABLE IF NOT EXISTS `draw_schedule` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `draw_id` int(11) NOT NULL COMMENT '추첨 ID',
  `broadcaster_id` int(11) NOT NULL COMMENT '방송국 ID',
  `schedule_title` varchar(100) NOT NULL COMMENT '일정 제목',
  `schedule_date` datetime NOT NULL COMMENT '일정 날짜 및 시간',
  `duration_minutes` int(11) NOT NULL DEFAULT 60 COMMENT '예상 소요 시간(분)',
  `location` varchar(255) DEFAULT NULL COMMENT '장소',
  `status` enum('scheduled','in_progress','completed','cancelled') NOT NULL DEFAULT 'scheduled' COMMENT '상태',
  `notes` text DEFAULT NULL COMMENT '참고사항',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '생성 시간',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정 시간',
  PRIMARY KEY (`id`),
  KEY `idx_draw_id` (`draw_id`),
  KEY `idx_broadcaster_id` (`broadcaster_id`),
  KEY `idx_schedule_date` (`schedule_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='추첨 일정';

-- 추첨 담당자 테이블
CREATE TABLE IF NOT EXISTS `draw_managers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `draw_id` int(11) NOT NULL COMMENT '추첨 ID',
  `manager_name` varchar(50) NOT NULL COMMENT '담당자 이름',
  `position` varchar(50) NOT NULL COMMENT '직책',
  `organization` varchar(100) NOT NULL COMMENT '소속 기관',
  `contact_phone` varchar(20) NOT NULL COMMENT '연락처',
  `contact_email` varchar(100) DEFAULT NULL COMMENT '이메일',
  `role_description` text DEFAULT NULL COMMENT '역할 설명',
  `is_primary` tinyint(1) NOT NULL DEFAULT 0 COMMENT '주 담당자 여부',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '생성 시간',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정 시간',
  PRIMARY KEY (`id`),
  KEY `idx_draw_id` (`draw_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='추첨 담당자';

-- 참관인 테이블
CREATE TABLE IF NOT EXISTS `draw_observers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `draw_id` int(11) NOT NULL COMMENT '추첨 ID',
  `observer_name` varchar(50) NOT NULL COMMENT '참관인 이름',
  `organization` varchar(100) NOT NULL COMMENT '소속 기관',
  `position` varchar(50) NOT NULL COMMENT '직책',
  `contact_phone` varchar(20) NOT NULL COMMENT '연락처',
  `contact_email` varchar(100) DEFAULT NULL COMMENT '이메일',
  `registration_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '등록 날짜',
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending' COMMENT '상태',
  `approval_date` datetime DEFAULT NULL COMMENT '승인 날짜',
  `remarks` text DEFAULT NULL COMMENT '비고',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '생성 시간',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정 시간',
  PRIMARY KEY (`id`),
  KEY `idx_draw_id` (`draw_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='추첨 참관인';

-- 은행 정보 테이블
CREATE TABLE IF NOT EXISTS `banks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bank_name` varchar(100) NOT NULL COMMENT '은행명',
  `branch_name` varchar(100) DEFAULT NULL COMMENT '지점명',
  `contact_person` varchar(50) NOT NULL COMMENT '담당자 이름',
  `contact_phone` varchar(20) NOT NULL COMMENT '담당자 전화번호',
  `contact_email` varchar(100) NOT NULL COMMENT '담당자 이메일',
  `address` varchar(255) NOT NULL COMMENT '주소',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active' COMMENT '상태',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '생성 시간',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정 시간',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='은행 정보';

-- 당첨금 지급 절차 테이블
CREATE TABLE IF NOT EXISTS `prize_payment_procedures` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bank_id` int(11) NOT NULL COMMENT '은행 ID',
  `rank` int(11) NOT NULL COMMENT '당첨 등수 (1등, 2등, 3등 등)',
  `procedure_name` varchar(100) NOT NULL COMMENT '절차 이름',
  `procedure_order` int(11) NOT NULL COMMENT '절차 순서',
  `description` text NOT NULL COMMENT '절차 설명',
  `required_documents` text DEFAULT NULL COMMENT '필요 서류',
  `verification_method` varchar(255) DEFAULT NULL COMMENT '검증 방법',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '생성 시간',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정 시간',
  PRIMARY KEY (`id`),
  KEY `idx_bank_id` (`bank_id`),
  KEY `idx_rank` (`rank`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='당첨금 지급 절차';

-- 당첨 복권 인증 체크리스트 테이블
CREATE TABLE IF NOT EXISTS `ticket_verification_checklist` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `checklist_name` varchar(100) NOT NULL COMMENT '체크리스트 이름',
  `description` text NOT NULL COMMENT '설명',
  `verification_steps` text NOT NULL COMMENT '검증 단계 (JSON)',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '활성 여부',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '생성 시간',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정 시간',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='당첨 복권 인증 체크리스트';

-- 당첨자 인터뷰 템플릿 테이블
CREATE TABLE IF NOT EXISTS `winner_interview_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `template_name` varchar(100) NOT NULL COMMENT '템플릿 이름',
  `rank` int(11) NOT NULL COMMENT '당첨 등수',
  `questions` text NOT NULL COMMENT '인터뷰 질문 (JSON)',
  `instructions` text DEFAULT NULL COMMENT '인터뷰 지침',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '활성 여부',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '생성 시간',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정 시간',
  PRIMARY KEY (`id`),
  KEY `idx_rank` (`rank`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='당첨자 인터뷰 템플릿';

-- 당첨금 정보 테이블
CREATE TABLE IF NOT EXISTS `prize_amounts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `draw_id` int(11) NOT NULL COMMENT '추첨 ID',
  `draw_round` int(11) NOT NULL COMMENT '추첨 회차',
  `rank` int(11) NOT NULL COMMENT '당첨 등수',
  `prize_amount` decimal(20,2) NOT NULL COMMENT '당첨금액',
  `winners_count` int(11) NOT NULL DEFAULT 0 COMMENT '당첨자 수',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '생성 시간',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정 시간',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_draw_rank` (`draw_id`, `rank`),
  KEY `idx_draw_round` (`draw_round`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='당첨금 정보';

-- 정부 감시 기관 테이블
CREATE TABLE IF NOT EXISTS `government_agencies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `agency_name` varchar(100) NOT NULL COMMENT '기관명',
  `department` varchar(100) NOT NULL COMMENT '부서',
  `contact_person` varchar(50) NOT NULL COMMENT '담당자 이름',
  `contact_phone` varchar(20) NOT NULL COMMENT '담당자 전화번호',
  `contact_email` varchar(100) NOT NULL COMMENT '담당자 이메일',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active' COMMENT '상태',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '생성 시간',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정 시간',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='정부 감시 기관';

-- 회차별 판매 정보 테이블
CREATE TABLE IF NOT EXISTS `round_sales_info` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `draw_round` int(11) NOT NULL COMMENT '추첨 회차',
  `total_tickets_sold` int(11) NOT NULL DEFAULT 0 COMMENT '총 판매 복권 수',
  `total_sales_amount` decimal(20,2) NOT NULL DEFAULT 0.00 COMMENT '총 판매 금액',
  `sales_start_date` date NOT NULL COMMENT '판매 시작일',
  `sales_end_date` date NOT NULL COMMENT '판매 종료일',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '생성 시간',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정 시간',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_draw_round` (`draw_round`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='회차별 판매 정보';

-- 기금 액수 및 사용 내역 테이블
CREATE TABLE IF NOT EXISTS `lottery_funds` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `draw_round` int(11) NOT NULL COMMENT '추첨 회차',
  `fund_amount` decimal(20,2) NOT NULL COMMENT '기금 금액',
  `fund_date` date NOT NULL COMMENT '기금 적립일',
  `description` text DEFAULT NULL COMMENT '설명',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '생성 시간',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정 시간',
  PRIMARY KEY (`id`),
  KEY `idx_draw_round` (`draw_round`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='기금 정보';

-- 기금 분과 테이블
CREATE TABLE IF NOT EXISTS `fund_departments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `department_name` varchar(100) NOT NULL COMMENT '분과명',
  `description` text DEFAULT NULL COMMENT '설명',
  `contact_person` varchar(50) NOT NULL COMMENT '담당자 이름',
  `contact_phone` varchar(20) NOT NULL COMMENT '담당자 전화번호',
  `contact_email` varchar(100) NOT NULL COMMENT '담당자 이메일',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active' COMMENT '상태',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '생성 시간',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정 시간',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='기금 분과';

-- 기금 분배 정보 테이블
CREATE TABLE IF NOT EXISTS `fund_allocations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fund_id` int(11) NOT NULL COMMENT '기금 ID',
  `department_id` int(11) NOT NULL COMMENT '분과 ID',
  `allocation_amount` decimal(20,2) NOT NULL COMMENT '할당 금액',
  `allocation_date` date NOT NULL COMMENT '할당일',
  `status` enum('pending','approved','transferred','completed') NOT NULL DEFAULT 'pending' COMMENT '상태',
  `approved_by` int(11) DEFAULT NULL COMMENT '승인자 ID',
  `approval_date` datetime DEFAULT NULL COMMENT '승인일',
  `notes` text DEFAULT NULL COMMENT '비고',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '생성 시간',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정 시간',
  PRIMARY KEY (`id`),
  KEY `idx_fund_id` (`fund_id`),
  KEY `idx_department_id` (`department_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='기금 분배 정보';

-- 외래키 체크 다시 활성화
SET FOREIGN_KEY_CHECKS = 1;

-- 샘플 데이터 삽입 (각 테이블 데이터가 없는 경우에만 삽입)
-- ------------------------------------------------------------

-- 추첨 방송국 샘플 데이터
INSERT INTO `broadcaster` (`name`, `contact_person`, `contact_phone`, `contact_email`, `address`, `status`)
SELECT 'KBS', '김상혁', '010-1234-5678', 'kim@example.com', '서울시 영등포구 여의도동 13', 'active'
FROM dual
WHERE NOT EXISTS (SELECT 1 FROM `broadcaster` WHERE `name` = 'KBS' LIMIT 1);

-- 추첨 체크리스트 샘플 데이터
INSERT INTO `broadcaster_checklist` (`draw_id`, `broadcaster_id`, `checklist_item`, `is_completed`, `completion_date`, `remarks`)
SELECT 126, 1, '추첨기 점검', 1, '2025-05-15 14:30:00', '추첨기 장비 정상 작동 확인 및 테스트 완료'
FROM dual
WHERE NOT EXISTS (SELECT 1 FROM `broadcaster_checklist` WHERE `draw_id` = 126 AND `checklist_item` = '추첨기 점검' LIMIT 1);

INSERT INTO `broadcaster_checklist` (`draw_id`, `broadcaster_id`, `checklist_item`, `is_completed`, `completion_date`, `remarks`)
SELECT 126, 1, '카메라 세팅', 1, '2025-05-16 10:15:00', '추첨 장면 촬영을 위한 카메라 설치 및 테스트'
FROM dual
WHERE NOT EXISTS (SELECT 1 FROM `broadcaster_checklist` WHERE `draw_id` = 126 AND `checklist_item` = '카메라 세팅' LIMIT 1);

INSERT INTO `broadcaster_checklist` (`draw_id`, `broadcaster_id`, `checklist_item`, `is_completed`, `completion_date`, `remarks`)
SELECT 126, 1, '방송 송출 테스트', 0, NULL, '실시간 방송 송출 테스트 및 네트워크 연결 확인'
FROM dual
WHERE NOT EXISTS (SELECT 1 FROM `broadcaster_checklist` WHERE `draw_id` = 126 AND `checklist_item` = '방송 송출 테스트' LIMIT 1);

INSERT INTO `broadcaster_checklist` (`draw_id`, `broadcaster_id`, `checklist_item`, `is_completed`, `completion_date`, `remarks`)
SELECT 126, 1, '참관인 배치', 0, NULL, '추첨 참관인 좌석 배치 및 안내 자료 준비'
FROM dual
WHERE NOT EXISTS (SELECT 1 FROM `broadcaster_checklist` WHERE `draw_id` = 126 AND `checklist_item` = '참관인 배치' LIMIT 1);

INSERT INTO `broadcaster_checklist` (`draw_id`, `broadcaster_id`, `checklist_item`, `is_completed`, `completion_date`, `remarks`)
SELECT 126, 1, '공증인 섭외', 1, '2025-05-12 16:45:00', '추첨 공정성 확보를 위한 공증인 섭외 완료'
FROM dual
WHERE NOT EXISTS (SELECT 1 FROM `broadcaster_checklist` WHERE `draw_id` = 126 AND `checklist_item` = '공증인 섭외' LIMIT 1);

-- 추첨 일정 샘플 데이터
INSERT INTO `draw_schedule` (`draw_id`, `broadcaster_id`, `schedule_title`, `schedule_date`, `duration_minutes`, `location`, `status`, `notes`)
SELECT 126, 1, '126회 추첨 리허설', '2025-05-20 14:00:00', 180, '스튜디오 A', 'scheduled', '출연자, 진행자, 기술진 전원 참석 필수'
FROM dual
WHERE NOT EXISTS (SELECT 1 FROM `draw_schedule` WHERE `draw_id` = 126 AND `schedule_title` = '126회 추첨 리허설' LIMIT 1);

INSERT INTO `draw_schedule` (`draw_id`, `broadcaster_id`, `schedule_title`, `schedule_date`, `duration_minutes`, `location`, `status`, `notes`)
SELECT 126, 1, '126회 추첨 방송', '2025-05-21 20:00:00', 60, '스튜디오 A', 'scheduled', '생방송 (공증인, 감시단 참석)'
FROM dual
WHERE NOT EXISTS (SELECT 1 FROM `draw_schedule` WHERE `draw_id` = 126 AND `schedule_title` = '126회 추첨 방송' LIMIT 1);

INSERT INTO `draw_schedule` (`draw_id`, `broadcaster_id`, `schedule_title`, `schedule_date`, `duration_minutes`, `location`, `status`, `notes`)
SELECT 127, 1, '127회 사전 미팅', '2025-05-26 15:00:00', 90, '회의실 B', 'scheduled', '127회 추첨 관련 기획 회의, 담당자 전원 참석'
FROM dual
WHERE NOT EXISTS (SELECT 1 FROM `draw_schedule` WHERE `draw_id` = 127 AND `schedule_title` = '127회 사전 미팅' LIMIT 1);

-- 추첨 담당자 샘플 데이터
INSERT INTO `draw_managers` (`draw_id`, `manager_name`, `position`, `organization`, `contact_phone`, `contact_email`, `role_description`, `is_primary`)
SELECT 126, '김상혁', '제작 책임자', 'KBS', '010-1234-5678', 'kim@example.com', '추첨 행사 전체 총괄 및 진행 감독', 1
FROM dual
WHERE NOT EXISTS (SELECT 1 FROM `draw_managers` WHERE `draw_id` = 126 AND `manager_name` = '김상혁' LIMIT 1);

INSERT INTO `draw_managers` (`draw_id`, `manager_name`, `position`, `organization`, `contact_phone`, `contact_email`, `role_description`, `is_primary`)
SELECT 126, '이민우', '추첨 감독관', '로또 위원회', '010-9876-5432', 'lee@example.com', '추첨 과정 관리 및 검증, 공정성 확보', 0
FROM dual
WHERE NOT EXISTS (SELECT 1 FROM `draw_managers` WHERE `draw_id` = 126 AND `manager_name` = '이민우' LIMIT 1);

INSERT INTO `draw_managers` (`draw_id`, `manager_name`, `position`, `organization`, `contact_phone`, `contact_email`, `role_description`, `is_primary`)
SELECT 126, '박지은', '방송 PD', 'KBS', '010-2222-3333', 'park@example.com', '방송 연출 및 카메라 감독', 0
FROM dual
WHERE NOT EXISTS (SELECT 1 FROM `draw_managers` WHERE `draw_id` = 126 AND `manager_name` = '박지은' LIMIT 1);

-- 참관인 샘플 데이터
INSERT INTO `draw_observers` (`draw_id`, `observer_name`, `organization`, `position`, `contact_phone`, `contact_email`, `status`, `approval_date`, `remarks`)
SELECT 126, '최재현', '시민단체', '감사위원', '010-5555-6666', 'choi@example.com', 'approved', '2025-05-12 09:30:00', '이전 참관 경험 있음'
FROM dual
WHERE NOT EXISTS (SELECT 1 FROM `draw_observers` WHERE `draw_id` = 126 AND `observer_name` = '최재현' LIMIT 1);

INSERT INTO `draw_observers` (`draw_id`, `observer_name`, `organization`, `position`, `contact_phone`, `contact_email`, `status`, `approval_date`, `remarks`)
SELECT 126, '장현석', '로또 애호가 협회', '회원', '010-7777-8888', 'jang@example.com', 'pending', NULL, '추첨 과정 이해도 높음'
FROM dual
WHERE NOT EXISTS (SELECT 1 FROM `draw_observers` WHERE `draw_id` = 126 AND `observer_name` = '장현석' LIMIT 1);

INSERT INTO `draw_observers` (`draw_id`, `observer_name`, `organization`, `position`, `contact_phone`, `contact_email`, `status`, `approval_date`, `remarks`)
SELECT 126, '정수미', '방송심의위원회', '위원', '010-3333-4444', 'jung@example.com', 'rejected', '2025-05-16 14:20:00', '내부 일정 충돌로 인한 거절'
FROM dual
WHERE NOT EXISTS (SELECT 1 FROM `draw_observers` WHERE `draw_id` = 126 AND `observer_name` = '정수미' LIMIT 1);

-- 은행 정보 샘플 데이터
INSERT INTO `banks` (`bank_name`, `branch_name`, `contact_person`, `contact_phone`, `contact_email`, `address`, `status`)
SELECT '국민은행', '여의도지점', '박은행', '010-2345-6789', 'park@bank.com', '서울시 영등포구 여의도동 45', 'active'
FROM dual
WHERE NOT EXISTS (SELECT 1 FROM `banks` WHERE `bank_name` = '국민은행' AND `branch_name` = '여의도지점' LIMIT 1);

-- 당첨금 지급 절차 샘플 데이터
INSERT INTO `prize_payment_procedures` (`bank_id`, `rank`, `procedure_name`, `procedure_order`, `description`, `required_documents`, `verification_method`)
SELECT 1, 1, '당첨 복권 원본 확인', 1, '당첨 복권의 바코드, 일련번호, 회차, 발행일자 등을 확인합니다.', '당첨 복권 원본', '바코드 스캐닝, 육안 확인'
FROM dual
WHERE NOT EXISTS (SELECT 1 FROM `prize_payment_procedures` WHERE `bank_id` = 1 AND `rank` = 1 AND `procedure_name` = '당첨 복권 원본 확인' LIMIT 1);

INSERT INTO `prize_payment_procedures` (`bank_id`, `rank`, `procedure_name`, `procedure_order`, `description`, `required_documents`, `verification_method`)
SELECT 1, 1, '당첨자 신분 확인', 2, '당첨자의 신분을 확인합니다.', '신분증, 주민등록등본', '신분증 대조 확인'
FROM dual
WHERE NOT EXISTS (SELECT 1 FROM `prize_payment_procedures` WHERE `bank_id` = 1 AND `rank` = 1 AND `procedure_name` = '당첨자 신분 확인' LIMIT 1);

INSERT INTO `prize_payment_procedures` (`bank_id`, `rank`, `procedure_name`, `procedure_order`, `description`, `required_documents`, `verification_method`)
SELECT 1, 1, '당첨 복권 위조 여부 검증', 3, '특수 장비를 통해 복권의 진위 여부를 확인합니다.', '당첨 복권 원본', '특수 장비 검증, UV 램프 확인'
FROM dual
WHERE NOT EXISTS (SELECT 1 FROM `prize_payment_procedures` WHERE `bank_id` = 1 AND `rank` = 1 AND `procedure_name` = '당첨 복권 위조 여부 검증' LIMIT 1);

-- 당첨 복권 인증 체크리스트 샘플 데이터
INSERT INTO `ticket_verification_checklist` (`checklist_name`, `description`, `verification_steps`, `is_active`)
SELECT '고액 당첨 복권 검증', '1등, 2등 당첨 복권 검증용 체크리스트', '[{"step":"용지 재질 확인","method":"특수 조명"},{"step":"바코드 스캐닝","method":"바코드 리더"},{"step":"홀로그램 확인","method":"육안 확인"},{"step":"UV 반응 확인","method":"UV 램프"}]', 1
FROM dual
WHERE NOT EXISTS (SELECT 1 FROM `ticket_verification_checklist` WHERE `checklist_name` = '고액 당첨 복권 검증' LIMIT 1);

-- 당첨자 인터뷰 템플릿 샘플 데이터
INSERT INTO `winner_interview_templates` (`template_name`, `rank`, `questions`, `instructions`, `is_active`)
SELECT '1등 당첨자 인터뷰', 1, '[{"question":"복권에 당첨되었을 때 첫 느낌은 어떠셨나요?","required":true},{"question":"당첨금을 어떻게 사용하실 계획인가요?","required":true},{"question":"당첨 소식을 가족이나 지인들과 공유하셨나요?","required":false}]', '인터뷰는 당첨자의 동의하에 진행되며, 최대 30분을 초과하지 않도록 합니다.', 1
FROM dual
WHERE NOT EXISTS (SELECT 1 FROM `winner_interview_templates` WHERE `template_name` = '1등 당첨자 인터뷰' AND `rank` = 1 LIMIT 1);

-- 당첨금 정보 샘플 데이터
INSERT INTO `prize_amounts` (`draw_id`, `draw_round`, `rank`, `prize_amount`, `winners_count`)
SELECT 125, 125, 1, 3125750000.00, 3
FROM dual
WHERE NOT EXISTS (SELECT 1 FROM `prize_amounts` WHERE `draw_id` = 125 AND `draw_round` = 125 AND `rank` = 1 LIMIT 1);

INSERT INTO `prize_amounts` (`draw_id`, `draw_round`, `rank`, `prize_amount`, `winners_count`)
SELECT 125, 125, 2, 52546150.00, 9
FROM dual
WHERE NOT EXISTS (SELECT 1 FROM `prize_amounts` WHERE `draw_id` = 125 AND `draw_round` = 125 AND `rank` = 2 LIMIT 1);

INSERT INTO `prize_amounts` (`draw_id`, `draw_round`, `rank`, `prize_amount`, `winners_count`)
SELECT 125, 125, 3, 1486215.00, 238
FROM dual
WHERE NOT EXISTS (SELECT 1 FROM `prize_amounts` WHERE `draw_id` = 125 AND `draw_round` = 125 AND `rank` = 3 LIMIT 1);

-- 정부 감시 기관 샘플 데이터
INSERT INTO `government_agencies` (`agency_name`, `department`, `contact_person`, `contact_phone`, `contact_email`, `status`)
SELECT '기획재정부', '복권사업팀', '정부원', '010-1111-2222', 'jung@gov.kr', 'active'
FROM dual
WHERE NOT EXISTS (SELECT 1 FROM `government_agencies` WHERE `agency_name` = '기획재정부' AND `department` = '복권사업팀' LIMIT 1);

-- 회차별 판매 정보 샘플 데이터
INSERT INTO `round_sales_info` (`draw_round`, `total_tickets_sold`, `total_sales_amount`, `sales_start_date`, `sales_end_date`)
SELECT 125, 8545632, 85456324000.00, '2025-05-08', '2025-05-14'
FROM dual
WHERE NOT EXISTS (SELECT 1 FROM `round_sales_info` WHERE `draw_round` = 125 LIMIT 1);

-- 기금 정보 샘플 데이터
INSERT INTO `lottery_funds` (`draw_round`, `fund_amount`, `fund_date`, `description`)
SELECT 125, 25636897200.00, '2025-05-16', '125회 추첨 기금 (판매액의 30%)'
FROM dual
WHERE NOT EXISTS (SELECT 1 FROM `lottery_funds` WHERE `draw_round` = 125 LIMIT 1);

-- 기금 분과 샘플 데이터
INSERT INTO `fund_departments` (`department_name`, `description`, `contact_person`, `contact_phone`, `contact_email`, `status`)
SELECT '문화예술 분과', '문화 및 예술 관련 사업 지원', '김예술', '010-1111-2222', 'kim@fund.org', 'active'
FROM dual
WHERE NOT EXISTS (SELECT 1 FROM `fund_departments` WHERE `department_name` = '문화예술 분과' LIMIT 1);

INSERT INTO `fund_departments` (`department_name`, `description`, `contact_person`, `contact_phone`, `contact_email`, `status`)
SELECT '체육진흥 분과', '체육 관련 사업 지원', '박체육', '010-2222-3333', 'park@fund.org', 'active'
FROM dual
WHERE NOT EXISTS (SELECT 1 FROM `fund_departments` WHERE `department_name` = '체육진흥 분과' LIMIT 1);

INSERT INTO `fund_departments` (`department_name`, `description`, `contact_person`, `contact_phone`, `contact_email`, `status`)
SELECT '사회복지 분과', '복지 관련 사업 지원', '이복지', '010-3333-4444', 'lee@fund.org', 'active'
FROM dual
WHERE NOT EXISTS (SELECT 1 FROM `fund_departments` WHERE `department_name` = '사회복지 분과' LIMIT 1);

INSERT INTO `fund_departments` (`department_name`, `description`, `contact_person`, `contact_phone`, `contact_email`, `status`)
SELECT '재난구호 분과', '재난 관련 구호 사업 지원', '최재난', '010-4444-5555', 'choi@fund.org', 'active'
FROM dual
WHERE NOT EXISTS (SELECT 1 FROM `fund_departments` WHERE `department_name` = '재난구호 분과' LIMIT 1);

INSERT INTO `fund_departments` (`department_name`, `description`, `contact_person`, `contact_phone`, `contact_email`, `status`)
SELECT '지역사회 분과', '지역사회 관련 사업 지원', '정지역', '010-5555-6666', 'jung@fund.org', 'active'
FROM dual
WHERE NOT EXISTS (SELECT 1 FROM `fund_departments` WHERE `department_name` = '지역사회 분과' LIMIT 1);

-- 기금 분배 정보 샘플 데이터 (fund_id를 자동으로 찾아서 삽입)
INSERT INTO `fund_allocations` (`fund_id`, `department_id`, `allocation_amount`, `allocation_date`, `status`, `approval_date`)
SELECT 
    (SELECT id FROM lottery_funds WHERE draw_round = 125 LIMIT 1),
    (SELECT id FROM fund_departments WHERE department_name = '문화예술 분과' LIMIT 1),
    4614641496.00, '2025-05-16', 'transferred', '2025-05-16 14:30:00'
FROM dual
WHERE NOT EXISTS (
    SELECT 1 FROM `fund_allocations` 
    WHERE `department_id` = (SELECT id FROM fund_departments WHERE department_name = '문화예술 분과' LIMIT 1)
    AND `allocation_date` = '2025-05-16' 
    LIMIT 1
);

INSERT INTO `fund_allocations` (`fund_id`, `department_id`, `allocation_amount`, `allocation_date`, `status`, `approval_date`)
SELECT 
    (SELECT id FROM lottery_funds WHERE draw_round = 125 LIMIT 1),
    (SELECT id FROM fund_departments WHERE department_name = '체육진흥 분과' LIMIT 1),
    5127379440.00, '2025-05-16', 'transferred', '2025-05-16 14:30:00'
FROM dual
WHERE NOT EXISTS (
    SELECT 1 FROM `fund_allocations` 
    WHERE `department_id` = (SELECT id FROM fund_departments WHERE department_name = '체육진흥 분과' LIMIT 1)
    AND `allocation_date` = '2025-05-16' 
    LIMIT 1
);

-- 외부접속감시 로그 샘플 데이터
INSERT INTO `external_monitoring_logs` (`entity_type`, `entity_id`, `activity_type`, `description`, `ip_address`, `log_date`)
SELECT 'broadcaster', 1, '로그인', 'KBS 담당자 로그인', '192.168.1.100', '2025-05-19 10:30:45'
FROM dual
WHERE NOT EXISTS (
    SELECT 1 FROM `external_monitoring_logs` 
    WHERE `entity_type` = 'broadcaster' AND `entity_id` = 1 
    AND `activity_type` = '로그인' AND `log_date` = '2025-05-19 10:30:45'
    LIMIT 1
);

INSERT INTO `external_monitoring_logs` (`entity_type`, `entity_id`, `activity_type`, `description`, `ip_address`, `log_date`)
SELECT 'bank', 1, '로그인', '국민은행 담당자 로그인', '192.168.1.101', '2025-05-19 09:45:22'
FROM dual
WHERE NOT EXISTS (
    SELECT 1 FROM `external_monitoring_logs` 
    WHERE `entity_type` = 'bank' AND `entity_id` = 1 
    AND `activity_type` = '로그인' AND `log_date` = '2025-05-19 09:45:22'
    LIMIT 1
);
