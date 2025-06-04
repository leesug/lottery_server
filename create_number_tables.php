<?php
/**
 * number_formats 테이블 생성 스크립트
 */

// 설정 및 공통 함수
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// 데이터베이스 연결
$db = get_db_connection();

// 번호 체계 테이블 생성
$query1 = "
CREATE TABLE IF NOT EXISTS `number_formats` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL COMMENT '번호 체계 이름',
  `description` text DEFAULT NULL COMMENT '설명',
  `format_type` enum('numeric','alpha_numeric','special') NOT NULL DEFAULT 'numeric' COMMENT '형식 유형',
  `number_count` int(11) NOT NULL DEFAULT 1 COMMENT '번호의 수',
  `number_range` varchar(100) NOT NULL COMMENT '번호 범위 (예: 1-45, A-Z)',
  `prefix` varchar(20) DEFAULT NULL COMMENT '접두사',
  `suffix` varchar(20) DEFAULT NULL COMMENT '접미사',
  `example` varchar(100) NOT NULL COMMENT '예시',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active' COMMENT '상태',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT '생성시간',
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp() COMMENT '수정시간',
  `created_by` int(11) DEFAULT NULL COMMENT '생성자',
  `updated_by` int(11) DEFAULT NULL COMMENT '수정자',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

// 번호 할당 테이블 생성
$query2 = "
CREATE TABLE IF NOT EXISTS `number_assignments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL COMMENT '복권 상품 ID',
  `format_id` int(11) NOT NULL COMMENT '번호 체계 ID',
  `store_id` int(11) NOT NULL COMMENT '판매점 ID',
  `start_number` varchar(100) NOT NULL COMMENT '시작 번호',
  `end_number` varchar(100) NOT NULL COMMENT '종료 번호',
  `quantity` int(11) NOT NULL COMMENT '수량',
  `notes` text DEFAULT NULL COMMENT '비고',
  `status` enum('active','used','expired','cancelled') NOT NULL DEFAULT 'active' COMMENT '상태',
  `assigned_by` int(11) DEFAULT NULL COMMENT '할당자',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT '생성시간',
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp() COMMENT '수정시간',
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `format_id` (`format_id`),
  KEY `store_id` (`store_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

// 번호 예약 테이블 생성
$query3 = "
CREATE TABLE IF NOT EXISTS `number_reservations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `format_id` int(11) NOT NULL COMMENT '번호 체계 ID',
  `numbers` varchar(255) NOT NULL COMMENT '예약 번호',
  `reason` text NOT NULL COMMENT '예약 사유',
  `status` enum('active','expired','cancelled') NOT NULL DEFAULT 'active' COMMENT '상태',
  `reserved_by` int(11) DEFAULT NULL COMMENT '예약자',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT '생성시간',
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp() COMMENT '수정시간',
  PRIMARY KEY (`id`),
  KEY `format_id` (`format_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

// 초기 데이터 삽입
$query4 = "
INSERT INTO `number_formats` (`name`, `description`, `format_type`, `number_count`, `number_range`, `prefix`, `suffix`, `example`, `status`, `created_at`)
VALUES
('기본 6자리 숫자', '일반적인 6자리 숫자 형식', 'numeric', 6, '000000-999999', NULL, NULL, '123456', 'active', NOW()),
('알파벳-숫자 혼합', '알파벳 2자리 + 숫자 4자리', 'alpha_numeric', 6, 'AA0000-ZZ9999', NULL, NULL, 'AB1234', 'active', NOW()),
('지역코드 포함 형식', '지역코드 + 6자리 숫자', 'alpha_numeric', 8, '지역코드(2) + 000000-999999', NULL, NULL, 'SE123456', 'active', NOW());
";

try {
    // 쿼리 실행
    $db->exec($query1);
    echo "number_formats 테이블이 성공적으로 생성되었습니다.<br>";
    
    $db->exec($query2);
    echo "number_assignments 테이블이 성공적으로 생성되었습니다.<br>";
    
    $db->exec($query3);
    echo "number_reservations 테이블이 성공적으로 생성되었습니다.<br>";
    
    // 초기 데이터가 이미 있는지 확인하고 없으면 삽입
    $stmt = $db->query("SELECT COUNT(*) as count FROM number_formats");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] == 0) {
        $db->exec($query4);
        echo "초기 데이터가 성공적으로 추가되었습니다.<br>";
    } else {
        echo "초기 데이터가 이미 존재합니다.<br>";
    }
    
    echo "모든 작업이 완료되었습니다.";
} catch (PDOException $e) {
    echo "오류가 발생했습니다: " . $e->getMessage();
    error_log("Database error: " . $e->getMessage());
}
