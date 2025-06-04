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
