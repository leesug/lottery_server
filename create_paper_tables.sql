-- 용지관리 시스템 테이블 생성
-- 작성일: 2025-01-22

-- paper_boxes 테이블 (용지박스)
CREATE TABLE IF NOT EXISTS paper_boxes (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    box_code VARCHAR(50) NOT NULL COMMENT '박스 코드',
    qr_code VARCHAR(100) NOT NULL COMMENT 'QR 코드',
    serial_prefix VARCHAR(20) NOT NULL COMMENT '일련번호 접두사',
    total_rolls INT NOT NULL DEFAULT 10 COMMENT '박스 내 총 롤 수',
    status ENUM('registered', 'assigned', 'used', 'expired') NOT NULL DEFAULT 'registered' COMMENT '상태',
    store_id INT NULL COMMENT '할당된 판매점 ID',
    assigned_at TIMESTAMP NULL COMMENT '할당 일시',
    notes TEXT NULL COMMENT '비고',
    created_by INT NOT NULL COMMENT '생성자',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '생성일시',
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일시',
    PRIMARY KEY (id),
    UNIQUE KEY uk_paper_boxes_code (box_code),
    UNIQUE KEY uk_paper_boxes_qr (qr_code),
    INDEX idx_paper_boxes_status (status),
    INDEX idx_paper_boxes_store (store_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='용지박스 관리';

-- paper_rolls 테이블 (용지롤)
CREATE TABLE IF NOT EXISTS paper_rolls (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    roll_code VARCHAR(50) NOT NULL COMMENT '롤 코드',
    qr_code VARCHAR(100) NOT NULL COMMENT 'QR 코드',
    box_id INT UNSIGNED NOT NULL COMMENT '박스 ID',
    start_serial CHAR(10) NOT NULL COMMENT '시작 일련번호',
    end_serial CHAR(10) NOT NULL COMMENT '종료 일련번호',
    serial_count INT NOT NULL DEFAULT 900 COMMENT '일련번호 개수',
    length_mm INT NOT NULL DEFAULT 63000 COMMENT '용지 길이(mm)',
    serial_interval_mm INT NOT NULL DEFAULT 70 COMMENT '일련번호 간격(mm)',
    status ENUM('registered', 'active', 'used', 'expired', 'damaged') NOT NULL DEFAULT 'registered' COMMENT '상태',
    store_id INT NULL COMMENT '할당된 판매점 ID',
    activated_at TIMESTAMP NULL COMMENT '활성화 일시',
    used_at TIMESTAMP NULL COMMENT '사용완료 일시',
    notes TEXT NULL COMMENT '비고',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '생성일시',
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일시',
    PRIMARY KEY (id),
    UNIQUE KEY uk_paper_rolls_code (roll_code),
    UNIQUE KEY uk_paper_rolls_qr (qr_code),
    UNIQUE KEY uk_paper_rolls_serial (start_serial, end_serial),
    INDEX idx_paper_rolls_box (box_id),
    INDEX idx_paper_rolls_status (status),
    INDEX idx_paper_rolls_store (store_id),
    INDEX idx_paper_rolls_serial_range (start_serial, end_serial)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='용지롤 관리';

-- paper_usage 테이블 (용지사용현황)
CREATE TABLE IF NOT EXISTS paper_usage (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    store_id INT NOT NULL COMMENT '판매점 ID',
    roll_id INT UNSIGNED NOT NULL COMMENT '롤 ID',
    current_serial CHAR(10) NOT NULL COMMENT '현재 일련번호 (운영자 입력)',
    estimated_serial CHAR(10) NOT NULL COMMENT '추정 일련번호',
    printed_length_mm INT NOT NULL DEFAULT 0 COMMENT '인쇄된 길이(mm)',
    remaining_length_mm INT NOT NULL DEFAULT 63000 COMMENT '남은 길이(mm)',
    serial_difference INT NOT NULL DEFAULT 0 COMMENT '일련번호 차이 (현재-추정)',
    welcome_count INT NOT NULL DEFAULT 0 COMMENT 'Welcome 메시지 수',
    game1_count INT NOT NULL DEFAULT 0 COMMENT '1게임 티켓 수',
    game2_count INT NOT NULL DEFAULT 0 COMMENT '2게임 티켓 수',
    game3_count INT NOT NULL DEFAULT 0 COMMENT '3게임 티켓 수',
    game4_count INT NOT NULL DEFAULT 0 COMMENT '4게임 티켓 수',
    game5_count INT NOT NULL DEFAULT 0 COMMENT '5게임 티켓 수',
    total_tickets INT NOT NULL DEFAULT 0 COMMENT '총 티켓 수',
    is_active BOOLEAN NOT NULL DEFAULT TRUE COMMENT '활성 상태',
    last_updated TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '최종 업데이트',
    PRIMARY KEY (id),
    UNIQUE KEY uk_paper_usage_active (store_id, is_active),
    INDEX idx_paper_usage_roll (roll_id),
    INDEX idx_paper_usage_updated (last_updated)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='용지 사용 현황';

-- paper_serial_tracking 테이블 (일련번호 추적)
CREATE TABLE IF NOT EXISTS paper_serial_tracking (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    store_id INT NOT NULL COMMENT '판매점 ID',
    roll_id INT UNSIGNED NOT NULL COMMENT '롤 ID',
    input_serial CHAR(10) NOT NULL COMMENT '입력된 일련번호',
    estimated_serial CHAR(10) NOT NULL COMMENT '추정 일련번호',
    serial_difference INT NOT NULL COMMENT '차이 (입력-추정)',
    action_type ENUM('login', 'paper_change', 'manual_input') NOT NULL COMMENT '작업 유형',
    printed_length_before INT NOT NULL COMMENT '작업 전 인쇄 길이',
    printed_length_after INT NOT NULL COMMENT '작업 후 인쇄 길이',
    is_valid BOOLEAN NOT NULL DEFAULT TRUE COMMENT '유효성 (오차범위 내)',
    error_level ENUM('normal', 'warning', 'critical') NOT NULL DEFAULT 'normal' COMMENT '오류 수준',
    notes TEXT NULL COMMENT '비고',
    created_by INT NOT NULL COMMENT '입력자',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '생성일시',
    PRIMARY KEY (id),
    INDEX idx_serial_tracking_store (store_id),
    INDEX idx_serial_tracking_roll (roll_id),
    INDEX idx_serial_tracking_date (created_at),
    INDEX idx_serial_tracking_error (error_level)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='일련번호 추적 이력';

-- paper_alerts 테이블 (용지 알림)
CREATE TABLE IF NOT EXISTS paper_alerts (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    store_id INT NOT NULL COMMENT '판매점 ID',
    roll_id INT UNSIGNED NOT NULL COMMENT '롤 ID',
    alert_type ENUM('usage_90', 'usage_95', 'usage_98', 'usage_99', 'serial_warning', 'serial_error') NOT NULL COMMENT '알림 유형',
    alert_level INT NOT NULL COMMENT '알림 레벨',
    usage_percentage DECIMAL(5,2) NULL COMMENT '사용률(%)',
    serial_difference INT NULL COMMENT '일련번호 차이',
    message TEXT NOT NULL COMMENT '알림 메시지',
    is_notified BOOLEAN NOT NULL DEFAULT FALSE COMMENT '알림 발송 여부',
    notified_at TIMESTAMP NULL COMMENT '알림 발송 일시',
    acknowledged BOOLEAN NOT NULL DEFAULT FALSE COMMENT '확인 여부',
    acknowledged_by INT NULL COMMENT '확인자',
    acknowledged_at TIMESTAMP NULL COMMENT '확인 일시',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '생성일시',
    PRIMARY KEY (id),
    INDEX idx_paper_alerts_store (store_id),
    INDEX idx_paper_alerts_roll (roll_id),
    INDEX idx_paper_alerts_type (alert_type),
    INDEX idx_paper_alerts_notified (is_notified)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='용지 알림';

-- paper_stock_history 테이블 (용지 재고 이력)
CREATE TABLE IF NOT EXISTS paper_stock_history (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    store_id INT NOT NULL COMMENT '판매점 ID',
    transaction_type ENUM('receive', 'activate', 'complete', 'damage', 'return') NOT NULL COMMENT '거래 유형',
    box_id INT UNSIGNED NULL COMMENT '박스 ID',
    roll_id INT UNSIGNED NULL COMMENT '롤 ID',
    quantity INT NOT NULL DEFAULT 1 COMMENT '수량',
    reference_no VARCHAR(100) NULL COMMENT '참조 번호',
    notes TEXT NULL COMMENT '비고',
    created_by INT NOT NULL COMMENT '처리자',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '생성일시',
    PRIMARY KEY (id),
    INDEX idx_stock_history_store (store_id),
    INDEX idx_stock_history_type (transaction_type),
    INDEX idx_stock_history_date (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='용지 재고 이력';

-- 용지 길이 정보 테이블 (시스템 설정)
CREATE TABLE IF NOT EXISTS paper_length_settings (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    item_type VARCHAR(50) NOT NULL COMMENT '항목 유형',
    length_mm INT NOT NULL COMMENT '길이(mm)',
    description VARCHAR(200) NULL COMMENT '설명',
    is_active BOOLEAN NOT NULL DEFAULT TRUE COMMENT '활성 여부',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '생성일시',
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일시',
    PRIMARY KEY (id),
    UNIQUE KEY uk_length_settings_type (item_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='용지 길이 설정';

-- 기본 용지 길이 설정 입력
INSERT INTO paper_length_settings (item_type, length_mm, description) VALUES
('welcome_message', 80, 'Welcome 메시지 출력 길이'),
('game_1_ticket', 95, '1게임 티켓 길이'),
('game_2_ticket', 98, '2게임 티켓 길이'),
('game_3_ticket', 101, '3게임 티켓 길이'),
('game_4_ticket', 105, '4게임 티켓 길이'),
('game_5_ticket', 108, '5게임 티켓 길이'),
('roll_total_length', 63000, '용지롤 전체 길이'),
('serial_interval', 70, '일련번호 간격'),
('error_tolerance', 12, '오차 허용 범위(±)');
