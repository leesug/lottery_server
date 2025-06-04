-- 예치금 관리 시스템 테이블 생성 (외래키 제약 없음)
-- 작성일: 2025-01-22

-- store_deposits 테이블 (판매점 예치금)
CREATE TABLE IF NOT EXISTS store_deposits (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    store_id INT NOT NULL COMMENT '판매점 ID',
    equipment_deposit DECIMAL(12,2) NOT NULL DEFAULT 280000.00 COMMENT '기기보증금',
    sales_deposit DECIMAL(12,2) NOT NULL DEFAULT 200000.00 COMMENT '판매보증금',
    total_deposit DECIMAL(12,2) NOT NULL DEFAULT 480000.00 COMMENT '총 예치금',
    sales_limit DECIMAL(12,2) NOT NULL DEFAULT 210000.00 COMMENT '판매한도',
    used_limit DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '사용한 한도',
    remaining_limit DECIMAL(12,2) GENERATED ALWAYS AS (sales_limit - used_limit) STORED COMMENT '잔여 한도',
    usage_percentage DECIMAL(5,2) GENERATED ALWAYS AS ((used_limit / sales_limit) * 100) STORED COMMENT '사용율(%)',
    store_grade ENUM('S', 'A', 'B', 'C', 'D') NOT NULL DEFAULT 'B' COMMENT '판매점 등급',
    leverage_rate DECIMAL(3,2) NOT NULL DEFAULT 1.00 COMMENT '레버리지 비율',
    status ENUM('active', 'suspended', 'blocked', 'terminated') NOT NULL DEFAULT 'active' COMMENT '상태',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '생성일시',
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일시',
    PRIMARY KEY (id),
    UNIQUE KEY uk_store_deposits_store (store_id),
    INDEX idx_store_deposits_status (status),
    INDEX idx_store_deposits_grade (store_grade),
    INDEX idx_store_deposits_usage (usage_percentage)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='판매점 예치금 관리';

-- deposit_transactions 테이블 (예치금 거래내역)
CREATE TABLE IF NOT EXISTS deposit_transactions (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    store_id INT NOT NULL COMMENT '판매점 ID',
    transaction_type ENUM('initial', 'deposit', 'increase', 'decrease', 'refund', 'adjustment') NOT NULL COMMENT '거래유형',
    deposit_type ENUM('equipment', 'sales', 'both') NOT NULL COMMENT '예치금 유형',
    amount DECIMAL(12,2) NOT NULL COMMENT '거래금액',
    balance_before DECIMAL(12,2) NOT NULL COMMENT '거래전 잔액',
    balance_after DECIMAL(12,2) NOT NULL COMMENT '거래후 잔액',
    reference_no VARCHAR(50) NULL COMMENT '참조번호',
    payment_method VARCHAR(50) NULL COMMENT '결제방법',
    bank_name VARCHAR(100) NULL COMMENT '은행명',
    account_number VARCHAR(50) NULL COMMENT '계좌번호',
    notes TEXT NULL COMMENT '비고',
    status ENUM('pending', 'completed', 'cancelled') NOT NULL DEFAULT 'pending' COMMENT '상태',
    approved_by INT NULL COMMENT '승인자',
    approved_at TIMESTAMP NULL COMMENT '승인일시',
    created_by INT NOT NULL COMMENT '생성자',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '생성일시',
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일시',
    PRIMARY KEY (id),
    INDEX idx_deposit_trans_store (store_id),
    INDEX idx_deposit_trans_type (transaction_type),
    INDEX idx_deposit_trans_status (status),
    INDEX idx_deposit_trans_date (created_at),
    INDEX idx_deposit_trans_reference (reference_no)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='예치금 거래내역';

-- sales_limit_alerts 테이블 (판매한도 알림 설정)
CREATE TABLE IF NOT EXISTS sales_limit_alerts (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    store_id INT NOT NULL COMMENT '판매점 ID',
    alert_level INT NOT NULL COMMENT '알림 레벨 (1:75%, 2:90%, 3:95%, 4:98%)',
    alert_percentage DECIMAL(5,2) NOT NULL COMMENT '알림 기준 퍼센트',
    is_notified BOOLEAN NOT NULL DEFAULT FALSE COMMENT '알림 발송 여부',
    notified_at TIMESTAMP NULL COMMENT '알림 발송 일시',
    acknowledged BOOLEAN NOT NULL DEFAULT FALSE COMMENT '확인 여부',
    acknowledged_by INT NULL COMMENT '확인자',
    acknowledged_at TIMESTAMP NULL COMMENT '확인 일시',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '생성일시',
    PRIMARY KEY (id),
    UNIQUE KEY uk_limit_alerts (store_id, alert_level),
    INDEX idx_limit_alerts_level (alert_level),
    INDEX idx_limit_alerts_notified (is_notified)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='판매한도 알림 설정';

-- deposit_limit_history 테이블 (예치금 한도 변경 이력)
CREATE TABLE IF NOT EXISTS deposit_limit_history (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    store_id INT NOT NULL COMMENT '판매점 ID',
    change_type ENUM('grade_change', 'deposit_change', 'manual_adjustment') NOT NULL COMMENT '변경 유형',
    old_grade ENUM('S', 'A', 'B', 'C', 'D') NULL COMMENT '이전 등급',
    new_grade ENUM('S', 'A', 'B', 'C', 'D') NULL COMMENT '새 등급',
    old_deposit DECIMAL(12,2) NULL COMMENT '이전 예치금',
    new_deposit DECIMAL(12,2) NULL COMMENT '새 예치금',
    old_limit DECIMAL(12,2) NOT NULL COMMENT '이전 한도',
    new_limit DECIMAL(12,2) NOT NULL COMMENT '새 한도',
    reason TEXT NULL COMMENT '변경 사유',
    changed_by INT NOT NULL COMMENT '변경자',
    changed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '변경일시',
    PRIMARY KEY (id),
    INDEX idx_limit_history_store (store_id),
    INDEX idx_limit_history_type (change_type),
    INDEX idx_limit_history_date (changed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='예치금 한도 변경 이력';
