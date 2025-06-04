-- 취소 및 환불 관련 테이블 생성 스크립트

-- 티켓 취소 정보 테이블
CREATE TABLE IF NOT EXISTS `ticket_cancellations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ticket_id` int(11) NOT NULL,
  `cancel_reason` enum('customer_request','input_error','system_error','payment_issue','other') NOT NULL,
  `cancel_notes` text DEFAULT NULL,
  `cancelled_by` int(11) DEFAULT NULL,
  `cancelled_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `ticket_id` (`ticket_id`),
  KEY `cancelled_by` (`cancelled_by`),
  KEY `cancelled_at` (`cancelled_at`),
  CONSTRAINT `tc_ticket_id_fk` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `tc_cancelled_by_fk` FOREIGN KEY (`cancelled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 환불 정보 테이블
CREATE TABLE IF NOT EXISTS `refunds` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ticket_id` int(11) NOT NULL,
  `refund_amount` decimal(10,2) NOT NULL,
  `refund_method` enum('cash','credit_card','bank_transfer','other') NOT NULL,
  `refund_reference` varchar(100) DEFAULT NULL,
  `refunded_by` int(11) DEFAULT NULL,
  `refunded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ticket_id` (`ticket_id`),
  KEY `refunded_by` (`refunded_by`),
  KEY `refunded_at` (`refunded_at`),
  CONSTRAINT `rf_ticket_id_fk` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `rf_refunded_by_fk` FOREIGN KEY (`refunded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;