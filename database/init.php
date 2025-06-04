<?php
/**
 * 데이터베이스 초기화 스크립트
 * 
 * 필요한 데이터베이스 테이블을 생성하고 초기 데이터를 설정합니다.
 */

// 필요한 설정 및 함수 로드
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/db.php';

// 로그 설정
$logFile = '../logs/' . date('Y-m-d') . '-db-init.log';
function logInitMessage($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message" . PHP_EOL, FILE_APPEND);
    echo $message . PHP_EOL;
}

logInitMessage("데이터베이스 초기화를 시작합니다.");

// 데이터베이스 연결
try {
    // 데이터베이스 연결 시도
    $dsn = "mysql:host=" . DB_HOST;
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ];
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    logInitMessage("MySQL 서버에 연결되었습니다.");
    
    // 데이터베이스 생성
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    logInitMessage("데이터베이스 " . DB_NAME . "가 생성되었습니다.");
    
    // 데이터베이스 선택
    $pdo->exec("USE `" . DB_NAME . "`");
    logInitMessage("데이터베이스 " . DB_NAME . "를 선택했습니다.");
    
    // 판매점 테이블
    $pdo->exec("
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
          `registration_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '등록일',
          `created_by` int(11) DEFAULT NULL COMMENT '생성자 ID',
          `updated_by` int(11) DEFAULT NULL COMMENT '수정자 ID',
          `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '생성일시',
          `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일시',
          PRIMARY KEY (`id`),
          UNIQUE KEY `store_code` (`store_code`),
          KEY `store_name` (`store_name`),
          KEY `status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='판매점 정보';
    ");
    logInitMessage("stores 테이블이 생성되었습니다.");
    
    // 계약 테이블
    $pdo->exec("
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
          `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '생성일시',
          `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일시',
          PRIMARY KEY (`id`),
          UNIQUE KEY `contract_code` (`contract_code`),
          KEY `store_id` (`store_id`),
          KEY `status` (`status`),
          CONSTRAINT `fk_contracts_store_id` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='판매점 계약 정보';
    ");
    logInitMessage("contracts 테이블이 생성되었습니다.");
    
    // 테스트 데이터 삽입
    $storeCount = $pdo->query("SELECT COUNT(*) FROM stores")->fetchColumn();
    if ($storeCount == 0) {
        $stmt = $pdo->prepare("
            INSERT INTO `stores` (`store_code`, `store_name`, `owner_name`, `email`, `phone`, 
                                `address`, `city`, `state`, `postal_code`, `country`, 
                                `status`, `store_category`, `store_size`, `registration_date`) 
            VALUES 
            (?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?, ?, ?, ?)
        ");
        
        // 판매점 1
        $stmt->execute([
            'STORE12345678', '네팔 마트 #23', '김철수', 'test@example.com', '01012345678',
            '판매점 주소', '서울', '서울특별시', '12345', '대한민국',
            'active', 'standard', 'medium', date('Y-m-d H:i:s')
        ]);
        
        // 판매점 2
        $stmt->execute([
            'STORE23456789', '카트만두 센터 #05', '이영희', 'test2@example.com', '01098765432',
            '판매점 주소 2', '부산', '부산광역시', '54321', '대한민국',
            'active', 'premium', 'large', date('Y-m-d H:i:s')
        ]);
        
        // 판매점 3
        $stmt->execute([
            'STORE34567890', '포카라 샵 #18', '박지민', 'test3@example.com', '01011112222',
            '판매점 주소 3', '대구', '대구광역시', '33333', '대한민국',
            'active', 'standard', 'small', date('Y-m-d H:i:s')
        ]);
        
        logInitMessage("샘플 판매점 데이터가 추가되었습니다.");
    }
    
    $contractCount = $pdo->query("SELECT COUNT(*) FROM contracts")->fetchColumn();
    if ($contractCount == 0) {
        $stmt = $pdo->prepare("
            INSERT INTO `contracts` (`store_id`, `contract_code`, `contract_type`, 
                                   `start_date`, `end_date`, `status`, `commission_rate`, 
                                   `signing_bonus`, `signed_by`, `signed_date`)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        // 계약 1
        $stmt->execute([
            1, 'CONTRACT001', 'standard', 
            date('Y-m-d', strtotime('-1 month')), date('Y-m-d', strtotime('+11 months')), 'active', 5.00,
            10000.00, '김관리자', date('Y-m-d', strtotime('-1 month'))
        ]);
        
        // 계약 2
        $stmt->execute([
            2, 'CONTRACT002', 'premium', 
            date('Y-m-d', strtotime('-2 months')), date('Y-m-d', strtotime('+10 months')), 'active', 7.50,
            15000.00, '이관리자', date('Y-m-d', strtotime('-2 months'))
        ]);
        
        // 계약 3
        $stmt->execute([
            3, 'CONTRACT003', 'standard', 
            date('Y-m-d', strtotime('-3 months')), date('Y-m-d', strtotime('+9 months')), 'active', 5.00,
            10000.00, '박관리자', date('Y-m-d', strtotime('-3 months'))
        ]);
        
        logInitMessage("샘플 계약 데이터가 추가되었습니다.");
    }
    
    logInitMessage("데이터베이스 초기화가 완료되었습니다.");
    
} catch (PDOException $e) {
    logInitMessage("데이터베이스 연결/초기화 오류: " . $e->getMessage());
}
