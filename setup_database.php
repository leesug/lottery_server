<?php
/**
 * 데이터베이스 테이블을 생성하는 스크립트
 */

// 설정 파일 불러오기
require_once 'includes/config.php';

// 로그 디렉토리 확인 및 생성
if (!file_exists('logs')) {
    mkdir('logs', 0755, true);
}

/**
 * 로그 작성하는 함수
 */
function writeLog($message) {
    $logFile = 'logs/setup_' . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message" . PHP_EOL;
    
    file_put_contents($logFile, $logMessage, FILE_APPEND);
    echo $logMessage;
}

/**
 * SQL 쿼리 실행하는 함수
 */
function executeSqlQuery($conn, $query) {
    try {
        $result = $conn->exec($query);
        return true;
    } catch (PDOException $e) {
        writeLog("Error executing query: " . $e->getMessage());
        return false;
    }
}

// 데이터베이스 생성
function createDatabase() {
    try {
        $conn = new PDO("mysql:host=" . DB_HOST, DB_USER, DB_PASS);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $query = "CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
        $conn->exec($query);
        
        writeLog("Database '" . DB_NAME . "' created or already exists.");
        return true;
    } catch (PDOException $e) {
        writeLog("Error creating database: " . $e->getMessage());
        return false;
    }
}

// 데이터베이스 연결
function connectToDatabase() {
    try {
        $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        writeLog("Connected to database '" . DB_NAME . "'.");
        return $conn;
    } catch (PDOException $e) {
        writeLog("Error connecting to database: " . $e->getMessage());
        return null;
    }
}

// 테이블이 존재하는지 확인
function tableExists($conn, $tableName) {
    try {
        $stmt = $conn->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$tableName]);
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        writeLog("Error checking if table exists: " . $e->getMessage());
        return false;
    }
}

// 메인 실행
writeLog("Starting database setup...");

// 데이터베이스 생성
if (!createDatabase()) {
    writeLog("Database creation failed. Exiting.");
    exit(1);
}

// 데이터베이스 연결
$conn = connectToDatabase();
if ($conn === null) {
    writeLog("Database connection failed. Exiting.");
    exit(1);
}

// 테이블 생성
$tables = [
    'draws' => "
        CREATE TABLE IF NOT EXISTS `draws` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `product_id` int(11) NOT NULL,
          `draw_number` varchar(50) NOT NULL,
          `draw_date` datetime NOT NULL,
          `draw_method` enum('random_generator','machine_draw','manual_draw') NOT NULL DEFAULT 'random_generator',
          `draw_location` varchar(255) DEFAULT NULL,
          `winning_numbers` varchar(255) DEFAULT NULL,
          `draw_status` enum('scheduled','ready','in_progress','completed','cancelled') NOT NULL DEFAULT 'scheduled',
          `results_published` tinyint(1) NOT NULL DEFAULT 0,
          `published_at` datetime DEFAULT NULL,
          `winners_count` int(11) DEFAULT 0,
          `total_prizes` decimal(15,2) DEFAULT 0.00,
          `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
          `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
          PRIMARY KEY (`id`),
          UNIQUE KEY `product_draw_number` (`product_id`, `draw_number`),
          KEY `product_id` (`product_id`),
          KEY `draw_date` (`draw_date`),
          KEY `draw_status` (`draw_status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ",
    'lottery_products' => "
        CREATE TABLE IF NOT EXISTS `lottery_products` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `product_code` varchar(50) NOT NULL,
          `name` varchar(100) NOT NULL,
          `price` decimal(10,2) NOT NULL,
          `lottery_type` varchar(50) NOT NULL,
          `description` text,
          `number_format` varchar(50),
          `draw_schedule` varchar(100),
          `template_id` int(11),
          `prize_structure` TEXT NULL COMMENT '당첨금 구조 정보 (JSON)',
          `status` enum('active','inactive','suspended') NOT NULL DEFAULT 'active',
          `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
          `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          UNIQUE KEY `product_code` (`product_code`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ",
    'tickets' => "
        CREATE TABLE IF NOT EXISTS `tickets` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `ticket_number` varchar(50) NOT NULL,
          `product_id` int(11) NOT NULL,
          `draw_id` int(11) NOT NULL,
          `terminal_id` int(11) NOT NULL,
          `price` decimal(10,2) NOT NULL,
          `numbers` varchar(255) NOT NULL,
          `customer_id` int(11) DEFAULT NULL,
          `status` enum('active','won','lost','cancelled') NOT NULL DEFAULT 'active',
          `purchase_date` datetime NOT NULL,
          `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
          `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
          PRIMARY KEY (`id`),
          UNIQUE KEY `ticket_number` (`ticket_number`),
          KEY `product_id` (`product_id`),
          KEY `draw_id` (`draw_id`),
          KEY `terminal_id` (`terminal_id`),
          KEY `customer_id` (`customer_id`),
          KEY `status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ",
    'terminals' => "
        CREATE TABLE IF NOT EXISTS `terminals` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `terminal_code` varchar(50) NOT NULL,
          `store_id` int(11) NOT NULL,
          `status` enum('active','inactive','suspended') NOT NULL DEFAULT 'active',
          `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
          `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
          PRIMARY KEY (`id`),
          UNIQUE KEY `terminal_code` (`terminal_code`),
          KEY `store_id` (`store_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ",
    'stores' => "
        CREATE TABLE IF NOT EXISTS `stores` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `store_code` varchar(50) NOT NULL,
          `name` varchar(100) NOT NULL,
          `region_id` int(11) NOT NULL,
          `address` varchar(255) NOT NULL,
          `phone` varchar(20) DEFAULT NULL,
          `email` varchar(100) DEFAULT NULL,
          `status` enum('active','inactive','suspended') NOT NULL DEFAULT 'active',
          `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
          `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
          PRIMARY KEY (`id`),
          UNIQUE KEY `store_code` (`store_code`),
          KEY `region_id` (`region_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ",
    'regions' => "
        CREATE TABLE IF NOT EXISTS `regions` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `code` varchar(20) NOT NULL,
          `name` varchar(100) NOT NULL,
          `parent_id` int(11) DEFAULT NULL,
          `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
          `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
          PRIMARY KEY (`id`),
          UNIQUE KEY `code` (`code`),
          KEY `parent_id` (`parent_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ",
    'winnings' => "
        CREATE TABLE IF NOT EXISTS `winnings` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `ticket_id` int(11) NOT NULL COMMENT '티켓 ID',
          `draw_id` int(11) NOT NULL COMMENT '추첨 ID',
          `prize_tier` int(11) NOT NULL COMMENT '당첨 등수',
          `prize_amount` decimal(18,2) NOT NULL COMMENT '당첨 금액',
          `status` enum('pending', 'claimed', 'paid') NOT NULL DEFAULT 'pending' COMMENT '상태 (대기중, 확인됨, 지급완료)',
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ",
    'payment_history' => "
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ",
    'prize_carryovers' => "
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ",
    'users' => "
        CREATE TABLE IF NOT EXISTS `users` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `username` varchar(50) NOT NULL,
          `password` varchar(255) NOT NULL,
          `email` varchar(100) NOT NULL,
          `full_name` varchar(100) DEFAULT NULL,
          `role` enum('admin','manager','operator','agent') NOT NULL,
          `is_active` tinyint(1) NOT NULL DEFAULT 1,
          `last_login` datetime DEFAULT NULL,
          `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
          `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
          PRIMARY KEY (`id`),
          UNIQUE KEY `username` (`username`),
          UNIQUE KEY `email` (`email`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ",
    'customers' => "
        CREATE TABLE IF NOT EXISTS `customers` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `first_name` varchar(50) NOT NULL,
          `last_name` varchar(50) DEFAULT NULL,
          `email` varchar(100) DEFAULT NULL,
          `phone` varchar(20) DEFAULT NULL,
          `id_type` varchar(30) DEFAULT NULL,
          `id_number` varchar(50) DEFAULT NULL,
          `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
          `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
          PRIMARY KEY (`id`),
          KEY `email` (`email`),
          KEY `phone` (`phone`),
          KEY `id_number` (`id_number`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    "
];

// 테이블 생성
foreach ($tables as $tableName => $query) {
    if (tableExists($conn, $tableName)) {
        writeLog("Table '$tableName' already exists.");
    } else {
        if (executeSqlQuery($conn, $query)) {
            writeLog("Table '$tableName' created successfully.");
        } else {
            writeLog("Failed to create table '$tableName'.");
        }
    }
}

// 샘플 데이터 추가
$sampleData = [
    'regions' => "
        INSERT INTO `regions` (`code`, `name`) VALUES
        ('SE', '서울'),
        ('BS', '부산'),
        ('GS', '경상'),
        ('JL', '전라'),
        ('CC', '충청'),
        ('GG', '경기'),
        ('JJ', '제주');
    ",
    'users' => "
        INSERT INTO `users` (`username`, `password`, `email`, `full_name`, `role`) 
        VALUES ('admin', '\$2y\$10\$6xzRTXoG/WOFMYgB.e2fT.AcWY/rO9QR/P2.A5n9smNgWPq9e5FNe', 'admin@example.com', '관리자', 'admin');
    ",
    'stores' => "
        INSERT INTO `stores` (`store_code`, `name`, `region_id`, `address`, `phone`, `status`) VALUES
        ('ST001', '중앙 복권방', 1, '서울 강남구 테헤란로 123', '02-1234-5678', 'active'),
        ('ST002', '행운 복권', 2, '부산 해운대구 해운대로 456', '051-987-6543', 'active');
    ",
    'terminals' => "
        INSERT INTO `terminals` (`terminal_code`, `store_id`, `status`) VALUES
        ('TM001', 1, 'active'),
        ('TM002', 1, 'active'),
        ('TM003', 2, 'active');
    ",
    'lottery_products' => "
        INSERT INTO `lottery_products` (`product_code`, `name`, `price`, `lottery_type`, `description`, `number_format`, `draw_schedule`, `prize_structure`, `status`)
        VALUES
        ('LOTTO6', '로또 6/45', 1000.00, 'standard', '정통 로또 6/45', '6개 숫자 (1-45)', '매주 토요일 오후 8:45', '{\"prize_pool_percentage\":50,\"prize_structure\":{\"1\":75,\"2\":12.5,\"3\":12.5}}', 'active'),
        ('DAILY5', '데일리 5', 1000.00, 'standard', '매일 진행되는 복권', '5개 숫자 (0-9)', '매일 오후 6:00', '{\"prize_pool_percentage\":50,\"prize_structure\":{\"1\":50,\"2\":30,\"3\":20}}', 'active');
    ",
    'draws' => "
        INSERT INTO `draws` (`product_id`, `draw_number`, `draw_date`, `draw_method`, `winning_numbers`, `draw_status`, `results_published`)
        VALUES
        (1, '1038', '2025-05-17 20:45:00', 'machine_draw', '7, 12, 23, 35, 38, 42', 'completed', 1),
        (1, '1039', '2025-05-24 20:45:00', 'scheduled', NULL, 'scheduled', 0),
        (2, '321', '2025-05-20 18:00:00', 'random_generator', '3, 4, 7, 9, 2', 'completed', 1),
        (2, '322', '2025-05-21 18:00:00', 'scheduled', NULL, 'scheduled', 0);
    ",
    'tickets' => "
        INSERT INTO `tickets` (`ticket_number`, `product_id`, `draw_id`, `terminal_id`, `price`, `numbers`, `status`, `purchase_date`)
        VALUES
        ('TK2025051700001', 1, 1, 1, 1000.00, '1, 12, 23, 35, 38, 42', 'won', '2025-05-16 15:30:00'),
        ('TK2025051700002', 1, 1, 1, 1000.00, '7, 22, 23, 35, 38, 42', 'won', '2025-05-16 15:32:00'),
        ('TK2025051700003', 1, 1, 2, 1000.00, '7, 12, 23, 35, 40, 41', 'lost', '2025-05-16 16:10:00'),
        ('TK2025052000001', 2, 3, 3, 1000.00, '3, 4, 7, 9, 0', 'won', '2025-05-19 12:45:00');
    ",
    'winnings' => "
        INSERT INTO `winnings` (`ticket_id`, `draw_id`, `prize_tier`, `prize_amount`, `status`, `created_at`)
        VALUES
        (1, 1, 2, 50000000.00, 'pending', NOW()),
        (2, 1, 3, 15000000.00, 'pending', NOW()),
        (4, 3, 2, 20000000.00, 'pending', NOW());
    "
];

// 샘플 데이터 추가 전에 레코드 수 확인 후 데이터 추가
foreach ($sampleData as $tableName => $query) {
    try {
        $stmt = $conn->query("SELECT COUNT(*) FROM `$tableName`");
        $count = $stmt->fetchColumn();
        
        if ($count == 0) {
            if (executeSqlQuery($conn, $query)) {
                writeLog("Sample data added to '$tableName'.");
            } else {
                writeLog("Failed to add sample data to '$tableName'.");
            }
        } else {
            writeLog("Table '$tableName' already has data. Skipping sample data insertion.");
        }
    } catch (PDOException $e) {
        writeLog("Error checking record count for '$tableName': " . $e->getMessage());
    }
}

writeLog("Database setup completed.");
?>