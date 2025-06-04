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
