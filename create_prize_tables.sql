-- 당첨금 관련 테이블 생성 스크립트

-- lottery_products 테이블의 prize_structure 컬럼 추가 (없는 경우에만)
ALTER TABLE `lottery_products` 
ADD COLUMN IF NOT EXISTS `prize_structure` TEXT NULL COMMENT '당첨금 구조 정보 (JSON)' AFTER `description`;

-- 당첨금 이력 테이블
CREATE TABLE IF NOT EXISTS `winnings` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ticket_id` INT UNSIGNED NOT NULL COMMENT '티켓 ID',
  `draw_id` INT UNSIGNED NOT NULL COMMENT '추첨 ID',
  `prize_tier` INT NOT NULL COMMENT '당첨 등수',
  `prize_amount` DECIMAL(18,2) NOT NULL COMMENT '당첨 금액',
  `status` ENUM('pending', 'claimed', 'paid') NOT NULL DEFAULT 'pending' COMMENT '상태 (대기중, 확인됨, 지급완료)',
  `claimed_at` DATETIME NULL COMMENT '확인 일시',
  `claimed_by` INT UNSIGNED NULL COMMENT '확인 처리자',
  `customer_info` TEXT NULL COMMENT '고객 정보 (JSON)',
  `paid_at` DATETIME NULL COMMENT '지급 일시',
  `paid_by` INT UNSIGNED NULL COMMENT '지급 처리자',
  `payment_method` VARCHAR(50) NULL COMMENT '지급 방법',
  `payment_reference` VARCHAR(100) NULL COMMENT '지급 참조번호',
  `notes` TEXT NULL COMMENT '메모',
  `created_at` DATETIME NOT NULL COMMENT '생성 일시',
  `updated_at` DATETIME NULL COMMENT '수정 일시',
  PRIMARY KEY (`id`),
  INDEX `IDX_WINNINGS_TICKET` (`ticket_id`),
  INDEX `IDX_WINNINGS_DRAW` (`draw_id`),
  INDEX `IDX_WINNINGS_STATUS` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='당첨금 정보';

-- 지급 이력 테이블
CREATE TABLE IF NOT EXISTS `payment_history` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `winning_id` INT UNSIGNED NOT NULL COMMENT '당첨금 ID',
  `ticket_id` INT UNSIGNED NOT NULL COMMENT '티켓 ID',
  `draw_id` INT UNSIGNED NOT NULL COMMENT '추첨 ID',
  `amount` DECIMAL(18,2) NOT NULL COMMENT '지급 금액',
  `payment_method` VARCHAR(50) NOT NULL COMMENT '지급 방법',
  `payment_reference` VARCHAR(100) NULL COMMENT '지급 참조번호',
  `processed_by` INT UNSIGNED NOT NULL COMMENT '처리자',
  `notes` TEXT NULL COMMENT '메모',
  `created_at` DATETIME NOT NULL COMMENT '생성 일시',
  PRIMARY KEY (`id`),
  INDEX `IDX_PAYMENT_WINNING` (`winning_id`),
  INDEX `IDX_PAYMENT_TICKET` (`ticket_id`),
  INDEX `IDX_PAYMENT_DRAW` (`draw_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='당첨금 지급 이력';

-- 당첨금 이월 테이블
CREATE TABLE IF NOT EXISTS `prize_carryovers` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `source_draw_id` INT UNSIGNED NOT NULL COMMENT '원본 추첨 ID',
  `target_draw_id` INT UNSIGNED NOT NULL COMMENT '대상 추첨 ID',
  `carryover_amount` DECIMAL(18,2) NOT NULL COMMENT '이월 금액',
  `carryover_tier` INT NOT NULL DEFAULT 1 COMMENT '이월 대상 등수',
  `status` ENUM('active', 'cancelled', 'applied') NOT NULL DEFAULT 'active' COMMENT '상태 (활성, 취소됨, 적용됨)',
  `notes` TEXT NULL COMMENT '메모',
  `created_by` INT UNSIGNED NOT NULL COMMENT '생성자',
  `created_at` DATETIME NOT NULL COMMENT '생성 일시',
  `updated_by` INT UNSIGNED NULL COMMENT '수정자',
  `updated_at` DATETIME NULL COMMENT '수정 일시',
  PRIMARY KEY (`id`),
  INDEX `IDX_CARRYOVER_SOURCE` (`source_draw_id`),
  INDEX `IDX_CARRYOVER_TARGET` (`target_draw_id`),
  INDEX `IDX_CARRYOVER_STATUS` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='당첨금 이월 정보';
