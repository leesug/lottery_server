<?php
// 취소 및 환불 관련 테이블 자동 생성 스크립트

// 연결 설정
$host = 'localhost';
$username = 'root';
$password = '';
$dbname = 'lotto_server';

// 연결 시도
try {
    // lotto_server 데이터베이스 없으면 생성
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 초기 설정
    $pdo->exec("SET NAMES utf8mb4");
    $pdo->exec("SET CHARACTER SET utf8mb4");
    
    // 데이터베이스 확인 및 생성
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $dbname CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "데이터베이스 존재 확인 또는 생성 완료: $dbname<br>";
    
    // 해당 DB 선택
    $pdo->exec("USE $dbname");
    
    // 기본 테이블 생성 (필요한 경우)
    
    // 1. users 테이블 (사용자 정보)
    $createUsersTable = "
    CREATE TABLE IF NOT EXISTS `users` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `username` varchar(50) NOT NULL,
        `email` varchar(100) NOT NULL,
        `password` varchar(255) NOT NULL,
        `role` varchar(20) NOT NULL DEFAULT 'user',
        `status` enum('active','inactive','suspended') NOT NULL DEFAULT 'active',
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`),
        UNIQUE KEY `username` (`username`),
        UNIQUE KEY `email` (`email`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    
    $pdo->exec($createUsersTable);
    echo "users 테이블 존재 확인 또는 생성 완료<br>";
    
    // 2. lottery_products 테이블 (복권 상품 정보)
    $createProductsTable = "
    CREATE TABLE IF NOT EXISTS `lottery_products` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `product_code` varchar(20) NOT NULL,
        `name` varchar(100) NOT NULL,
        `price` decimal(10,2) NOT NULL,
        `description` text DEFAULT NULL,
        `prize_structure` text DEFAULT NULL,
        `status` enum('active','inactive','upcoming','discontinued') NOT NULL DEFAULT 'active',
        `created_by` int(11) DEFAULT NULL,
        `updated_by` int(11) DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`),
        UNIQUE KEY `product_code` (`product_code`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    
    $pdo->exec($createProductsTable);
    echo "lottery_products 테이블 존재 확인 또는 생성 완료<br>";
    
    // 3. regions 테이블 (지역 정보)
    $createRegionsTable = "
    CREATE TABLE IF NOT EXISTS `regions` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(100) NOT NULL,
        `code` varchar(20) DEFAULT NULL,
        `parent_id` int(11) DEFAULT NULL,
        `level` int(11) DEFAULT 1,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`),
        KEY `parent_id` (`parent_id`),
        KEY `level` (`level`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    
    $pdo->exec($createRegionsTable);
    echo "regions 테이블 존재 확인 또는 생성 완료<br>";
    
    // 4. stores 테이블 (판매점 정보)
    $createStoresTable = "
    CREATE TABLE IF NOT EXISTS `stores` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `store_code` varchar(20) NOT NULL,
        `name` varchar(100) NOT NULL,
        `region_id` int(11) DEFAULT NULL,
        `address` text DEFAULT NULL,
        `phone` varchar(20) DEFAULT NULL,
        `email` varchar(100) DEFAULT NULL,
        `owner_name` varchar(100) DEFAULT NULL,
        `status` enum('active','inactive','pending','suspended') NOT NULL DEFAULT 'active',
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`),
        UNIQUE KEY `store_code` (`store_code`),
        KEY `region_id` (`region_id`),
        KEY `status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    
    $pdo->exec($createStoresTable);
    echo "stores 테이블 존재 확인 또는 생성 완료<br>";
    
    // 5. terminals 테이블 (단말기 정보)
    $createTerminalsTable = "
    CREATE TABLE IF NOT EXISTS `terminals` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `terminal_code` varchar(20) NOT NULL,
        `store_id` int(11) NOT NULL,
        `model` varchar(50) DEFAULT NULL,
        `serial_number` varchar(50) DEFAULT NULL,
        `installation_date` date DEFAULT NULL,
        `status` enum('active','inactive','maintenance','decommissioned') NOT NULL DEFAULT 'active',
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`),
        UNIQUE KEY `terminal_code` (`terminal_code`),
        KEY `store_id` (`store_id`),
        KEY `status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    
    $pdo->exec($createTerminalsTable);
    echo "terminals 테이블 존재 확인 또는 생성 완료<br>";
    
    // 6. tickets 테이블 (티켓 정보)
    $createTicketsTable = "
    CREATE TABLE IF NOT EXISTS `tickets` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `ticket_number` varchar(50) NOT NULL,
        `product_id` int(11) NOT NULL,
        `terminal_id` int(11) NOT NULL,
        `numbers` varchar(255) NOT NULL,
        `price` decimal(10,2) NOT NULL,
        `status` enum('active','cancelled','verified','expired','invalid') NOT NULL DEFAULT 'active',
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`),
        UNIQUE KEY `ticket_number` (`ticket_number`),
        KEY `product_id` (`product_id`),
        KEY `terminal_id` (`terminal_id`),
        KEY `status` (`status`),
        KEY `created_at` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    
    $pdo->exec($createTicketsTable);
    echo "tickets 테이블 존재 확인 또는 생성 완료<br>";
    
    // 7. ticket_cancellations 테이블 (티켓 취소 정보)
    $createTicketCancellationsTable = "
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
        KEY `cancelled_at` (`cancelled_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    
    $pdo->exec($createTicketCancellationsTable);
    echo "ticket_cancellations 테이블 존재 확인 또는 생성 완료<br>";
    
    // 8. refunds 테이블 (환불 정보)
    $createRefundsTable = "
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
        KEY `refunded_at` (`refunded_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    
    $pdo->exec($createRefundsTable);
    echo "refunds 테이블 존재 확인 또는 생성 완료<br>";
    
    // 필요시 외래 키 관계 추가 시도 (실패해도 계속 진행)
    try {
        // 기존 외래 키 삭제 (있으면)
        $pdo->exec("
            ALTER TABLE `ticket_cancellations` 
            DROP FOREIGN KEY IF EXISTS `tc_ticket_id_fk`,
            DROP FOREIGN KEY IF EXISTS `tc_cancelled_by_fk`;
        ");
        
        $pdo->exec("
            ALTER TABLE `refunds` 
            DROP FOREIGN KEY IF EXISTS `rf_ticket_id_fk`,
            DROP FOREIGN KEY IF EXISTS `rf_refunded_by_fk`;
        ");
        
        // 외래 키 추가 시도
        $pdo->exec("
            ALTER TABLE `ticket_cancellations` 
            ADD CONSTRAINT `tc_ticket_id_fk` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            ADD CONSTRAINT `tc_cancelled_by_fk` FOREIGN KEY (`cancelled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;
        ");
        
        $pdo->exec("
            ALTER TABLE `refunds` 
            ADD CONSTRAINT `rf_ticket_id_fk` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            ADD CONSTRAINT `rf_refunded_by_fk` FOREIGN KEY (`refunded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;
        ");
        
        echo "외래 키 관계 설정 완료<br>";
    } catch (Exception $e) {
        echo "외래 키 설정 중 오류 발생 (무시함): " . $e->getMessage() . "<br>";
    }
    
    // 샘플 데이터 추가
    
    // 1. 샘플 지역 데이터 (없을 경우)
    $checkRegionsQuery = "SELECT COUNT(*) FROM regions";
    $stmt = $pdo->query($checkRegionsQuery);
    $regionsCount = $stmt->fetchColumn();
    
    if ($regionsCount == 0) {
        $insertSampleRegions = "
        INSERT INTO `regions` (`name`, `code`, `level`) VALUES
        ('카트만두', 'KTM', 1),
        ('포카라', 'PKR', 1),
        ('랄릿푸르', 'LLT', 1),
        ('바라트푸르', 'BRT', 1);";
        
        $pdo->exec($insertSampleRegions);
        echo "샘플 지역 데이터 추가 완료<br>";
    } else {
        echo "지역 데이터가 이미 존재함<br>";
    }
    
    // 2. 샘플 판매점 데이터 (없을 경우)
    $checkStoresQuery = "SELECT COUNT(*) FROM stores";
    $stmt = $pdo->query($checkStoresQuery);
    $storesCount = $stmt->fetchColumn();
    
    if ($storesCount == 0) {
        $insertSampleStores = "
        INSERT INTO `stores` (`store_code`, `name`, `region_id`, `address`, `phone`, `email`, `owner_name`, `status`) VALUES
        ('ST001', '센트럴 로또', 1, '카트만두 시내', '123-456-7890', 'central@example.com', '라메시 파텔', 'active'),
        ('ST002', '럭키 스토어', 2, '포카라 호수가', '987-654-3210', 'lucky@example.com', '시타 시레스타', 'active'),
        ('ST003', '드림 로또', 3, '랄릿푸르 중앙', '555-123-4567', 'dream@example.com', '라준 만안다르', 'active');";
        
        $pdo->exec($insertSampleStores);
        echo "샘플 판매점 데이터 추가 완료<br>";
    } else {
        echo "판매점 데이터가 이미 존재함<br>";
    }
    
    // 3. 샘플 단말기 데이터 (없을 경우)
    $checkTerminalsQuery = "SELECT COUNT(*) FROM terminals";
    $stmt = $pdo->query($checkTerminalsQuery);
    $terminalsCount = $stmt->fetchColumn();
    
    if ($terminalsCount == 0) {
        $insertSampleTerminals = "
        INSERT INTO `terminals` (`terminal_code`, `store_id`, `model`, `serial_number`, `installation_date`, `status`) VALUES
        ('TRM001', 1, 'LottoX-200', 'SN12345678', '2024-01-15', 'active'),
        ('TRM002', 1, 'LottoX-200', 'SN23456789', '2024-01-15', 'active'),
        ('TRM003', 2, 'LottoX-100', 'SN34567890', '2024-02-10', 'active'),
        ('TRM004', 3, 'LottoX-300', 'SN45678901', '2024-03-05', 'active');";
        
        $pdo->exec($insertSampleTerminals);
        echo "샘플 단말기 데이터 추가 완료<br>";
    } else {
        echo "단말기 데이터가 이미 존재함<br>";
    }
    
    // 4. 샘플 상품 데이터 (없을 경우)
    $checkProductsQuery = "SELECT COUNT(*) FROM lottery_products";
    $stmt = $pdo->query($checkProductsQuery);
    $productCount = $stmt->fetchColumn();
    
    if ($productCount == 0) {
        $insertSampleProducts = "
        INSERT INTO `lottery_products` (`product_code`, `name`, `price`, `description`, `status`) VALUES
        ('LO001', '로또 6/45', 1000.00, '1부터 45까지의 숫자 중 6개 선택', 'active'),
        ('PL001', '파워볼', 2000.00, '1부터 69까지의 숫자 중 5개와 파워볼 1개 선택', 'active'),
        ('QU001', '로또 퀵픽', 1000.00, '자동으로 번호 선택', 'active'),
        ('DAILY001', '일일 복권', 2000.00, '매일 추첨하는 복권', 'active'),
        ('WEEKLY001', '주간 복권', 5000.00, '매주 추첨하는 복권', 'active');";
        
        $pdo->exec($insertSampleProducts);
        echo "샘플 복권 상품 데이터 추가 완료<br>";
    } else {
        echo "복권 상품 데이터가 이미 존재함<br>";
    }
    
    // 5. 샘플 사용자 데이터 (없을 경우)
    $checkUsersQuery = "SELECT COUNT(*) FROM users";
    $stmt = $pdo->query($checkUsersQuery);
    $usersCount = $stmt->fetchColumn();
    
    if ($usersCount == 0) {
        // 암호화된 비밀번호 (기본값: 1234)
        $defaultPassword = password_hash('1234', PASSWORD_DEFAULT);
        
        $insertSampleUsers = "
        INSERT INTO `users` (`username`, `email`, `password`, `role`, `status`) VALUES
        ('admin', 'admin@example.com', '$defaultPassword', 'admin', 'active'),
        ('manager', 'manager@example.com', '$defaultPassword', 'manager', 'active'),
        ('staff', 'staff@example.com', '$defaultPassword', 'staff', 'active'),
        ('store1', 'store1@example.com', '$defaultPassword', 'store', 'active');";
        
        $pdo->exec($insertSampleUsers);
        echo "샘플 사용자 데이터 추가 완료<br>";
    } else {
        echo "사용자 데이터가 이미 존재함<br>";
    }
    
    // 6. 샘플 티켓 데이터 (없을 경우)
    $checkTicketsQuery = "SELECT COUNT(*) FROM tickets";
    $stmt = $pdo->query($checkTicketsQuery);
    $ticketsCount = $stmt->fetchColumn();
    
    if ($ticketsCount == 0) {
        $insertSampleTickets = "
        INSERT INTO `tickets` (`ticket_number`, `product_id`, `terminal_id`, `numbers`, `price`, `status`, `created_at`) VALUES
        ('T202505000001', 1, 1, '01-09-23-34-42-45', 1000.00, 'active', DATE_SUB(NOW(), INTERVAL 2 DAY)),
        ('T202505000002', 1, 1, '04-12-15-29-36-44', 1000.00, 'active', DATE_SUB(NOW(), INTERVAL 2 DAY)),
        ('T202505000003', 2, 2, '03-11-19-25-31-PB15', 2000.00, 'active', DATE_SUB(NOW(), INTERVAL 1 DAY)),
        ('T202505000004', 3, 3, '02-05-21-27-35-39', 1000.00, 'cancelled', DATE_SUB(NOW(), INTERVAL 1 DAY)),
        ('T202505000005', 4, 4, '07-14-22-33-41-43', 2000.00, 'active', DATE_SUB(NOW(), INTERVAL 12 HOUR)),
        ('T202505000006', 5, 2, '06-13-18-26-37-48', 5000.00, 'active', DATE_SUB(NOW(), INTERVAL 6 HOUR)),
        ('T202505000007', 5, 3, '08-17-19-28-38-47', 5000.00, 'active', DATE_SUB(NOW(), INTERVAL 3 HOUR)),
        ('T202505000008', 4, 1, '10-16-24-30-40-46', 2000.00, 'active', DATE_SUB(NOW(), INTERVAL 1 HOUR));";
        
        $pdo->exec($insertSampleTickets);
        echo "샘플 티켓 데이터 추가 완료<br>";
    } else {
        echo "티켓 데이터가 이미 존재함<br>";
    }
    
    // 7. 샘플 취소 데이터 (없을 경우)
    $checkCancellationsQuery = "SELECT COUNT(*) FROM ticket_cancellations";
    $stmt = $pdo->query($checkCancellationsQuery);
    $cancellationsCount = $stmt->fetchColumn();
    
    if ($cancellationsCount == 0) {
        $insertSampleCancellations = "
        INSERT INTO `ticket_cancellations` (`ticket_id`, `cancel_reason`, `cancel_notes`, `cancelled_by`, `cancelled_at`) VALUES
        (4, 'customer_request', '고객이 번호 변경을 원함', 1, DATE_SUB(NOW(), INTERVAL 23 HOUR));";
        
        $pdo->exec($insertSampleCancellations);
        echo "샘플 취소 데이터 추가 완료<br>";
    } else {
        echo "취소 데이터가 이미 존재함<br>";
    }
    
    // 8. 샘플 환불 데이터 (없을 경우)
    $checkRefundsQuery = "SELECT COUNT(*) FROM refunds";
    $stmt = $pdo->query($checkRefundsQuery);
    $refundsCount = $stmt->fetchColumn();
    
    if ($refundsCount == 0) {
        $insertSampleRefunds = "
        INSERT INTO `refunds` (`ticket_id`, `refund_amount`, `refund_method`, `refund_reference`, `refunded_by`, `refunded_at`) VALUES
        (4, 1000.00, 'cash', 'REF20250518001', 1, DATE_SUB(NOW(), INTERVAL 23 HOUR));";
        
        $pdo->exec($insertSampleRefunds);
        echo "샘플 환불 데이터 추가 완료<br>";
    } else {
        echo "환불 데이터가 이미 존재함<br>";
    }
    
    echo "<hr>";
    echo "모든 테이블 생성 및 샘플 데이터 설정이 완료되었습니다.<br>";
    echo "<a href='/server/dashboard/sales/refund.php'>판매 취소/환불 페이지로 돌아가기</a>";
    
} catch(PDOException $e) {
    echo "오류: " . $e->getMessage() . "<br>";
}
