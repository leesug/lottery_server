<?php
// 추첨 관련 테이블 자동 생성 스크립트

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
    
    // 1. lottery_products 테이블 (복권 상품 정보)
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
    
    // 2. lottery_draws 테이블 (추첨 정보)
    $createDrawsTable = "
    CREATE TABLE IF NOT EXISTS `lottery_draws` (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    
    $pdo->exec($createDrawsTable);
    echo "lottery_draws 테이블 존재 확인 또는 생성 완료<br>";
    
    // 3. winnings 테이블 (당첨 정보)
    $createWinningsTable = "
    CREATE TABLE IF NOT EXISTS `winnings` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `draw_id` int(11) NOT NULL,
        `ticket_id` int(11) NOT NULL,
        `prize_tier` int(11) NOT NULL,
        `prize_amount` decimal(15,2) NOT NULL,
        `prize_description` varchar(255) DEFAULT NULL,
        `claim_status` enum('unclaimed','pending','paid','expired') NOT NULL DEFAULT 'unclaimed',
        `claimed_at` datetime DEFAULT NULL,
        `claim_reference` varchar(100) DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`),
        UNIQUE KEY `draw_ticket` (`draw_id`, `ticket_id`),
        KEY `draw_id` (`draw_id`),
        KEY `ticket_id` (`ticket_id`),
        KEY `prize_tier` (`prize_tier`),
        KEY `claim_status` (`claim_status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    
    $pdo->exec($createWinningsTable);
    echo "winnings 테이블 존재 확인 또는 생성 완료<br>";
    
    // 4. draw_history 테이블 (추첨 이력)
    $createDrawHistoryTable = "
    CREATE TABLE IF NOT EXISTS `draw_history` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `draw_id` int(11) NOT NULL,
        `action` varchar(50) NOT NULL,
        `details` text DEFAULT NULL,
        `performed_by` int(11) DEFAULT NULL,
        `performed_at` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`),
        KEY `draw_id` (`draw_id`),
        KEY `performed_by` (`performed_by`),
        KEY `performed_at` (`performed_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    
    $pdo->exec($createDrawHistoryTable);
    echo "draw_history 테이블 존재 확인 또는 생성 완료<br>";
    
    // 5. draw_schedule 테이블 (추첨 회차 설정)
    $createDrawScheduleTable = "
    CREATE TABLE IF NOT EXISTS `draw_schedule` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `product_id` int(11) NOT NULL,
        `frequency` enum('daily','weekly','biweekly','monthly','custom') NOT NULL,
        `day_of_week` tinyint(1) DEFAULT NULL COMMENT '0=일, 1=월, ..., 6=토',
        `time_of_day` time DEFAULT NULL,
        `next_draw_date` datetime DEFAULT NULL,
        `last_draw_id` int(11) DEFAULT NULL,
        `is_active` tinyint(1) NOT NULL DEFAULT 1,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`),
        KEY `product_id` (`product_id`),
        KEY `is_active` (`is_active`),
        KEY `next_draw_date` (`next_draw_date`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    
    $pdo->exec($createDrawScheduleTable);
    echo "draw_schedule 테이블 존재 확인 또는 생성 완료<br>";
    
    // 필요시 외래 키 관계 추가 시도 (실패해도 계속 진행)
    try {
        // 외래 키 추가 시도
        $pdo->exec("
            ALTER TABLE `lottery_draws`
            ADD CONSTRAINT `ld_product_id_fk` FOREIGN KEY (`product_id`) REFERENCES `lottery_products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
        ");
        
        $pdo->exec("
            ALTER TABLE `winnings`
            ADD CONSTRAINT `w_draw_id_fk` FOREIGN KEY (`draw_id`) REFERENCES `lottery_draws` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            ADD CONSTRAINT `w_ticket_id_fk` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
        ");
        
        $pdo->exec("
            ALTER TABLE `draw_history`
            ADD CONSTRAINT `dh_draw_id_fk` FOREIGN KEY (`draw_id`) REFERENCES `lottery_draws` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            ADD CONSTRAINT `dh_performed_by_fk` FOREIGN KEY (`performed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;
        ");
        
        $pdo->exec("
            ALTER TABLE `draw_schedule`
            ADD CONSTRAINT `ds_product_id_fk` FOREIGN KEY (`product_id`) REFERENCES `lottery_products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            ADD CONSTRAINT `ds_last_draw_id_fk` FOREIGN KEY (`last_draw_id`) REFERENCES `lottery_draws` (`id`) ON DELETE SET NULL;
        ");
        
        echo "외래 키 관계 설정 완료<br>";
    } catch (Exception $e) {
        echo "외래 키 설정 중 오류 발생 (무시함): " . $e->getMessage() . "<br>";
    }
    
    // tickets 테이블에 draw_id 필드 추가 (없는 경우)
    try {
        $query = "DESCRIBE tickets";
        $stmt = $pdo->query($query);
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (!in_array('draw_id', $columns)) {
            $pdo->exec("ALTER TABLE tickets ADD COLUMN draw_id int(11) DEFAULT NULL AFTER product_id");
            $pdo->exec("ALTER TABLE tickets ADD KEY draw_id (draw_id)");
            echo "tickets 테이블에 draw_id 컬럼 추가 완료<br>";
        } else {
            echo "tickets 테이블의 draw_id 컬럼이 이미 존재함<br>";
        }
    } catch (Exception $e) {
        echo "tickets 테이블 수정 중 오류 발생: " . $e->getMessage() . "<br>";
    }
    
    // 샘플 데이터 추가
    // 1. 샘플 복권 상품 데이터 (없을 경우)
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
    
    // 2. 샘플 추첨 데이터 (없을 경우)
    $checkDrawsQuery = "SELECT COUNT(*) FROM lottery_draws";
    $stmt = $pdo->query($checkDrawsQuery);
    $drawsCount = $stmt->fetchColumn();
    
    if ($drawsCount == 0) {
        $insertSampleDraws = "
        INSERT INTO `lottery_draws` 
            (`product_id`, `draw_number`, `draw_date`, `draw_method`, `draw_location`, `winning_numbers`, `draw_status`, `results_published`, `winners_count`, `total_prizes`, `created_at`) 
        VALUES
            (1, '977', '2025-05-18 20:30:00', 'machine_draw', '로또 추첨 스튜디오', '3, 12, 19, 23, 33, 45', 'completed', 1, 12, 1500000.00, '2025-05-15 10:00:00'),
            (1, '978', '2025-05-11 20:30:00', 'machine_draw', '로또 추첨 스튜디오', '7, 13, 18, 22, 35, 42', 'completed', 1, 8, 1200000.00, '2025-05-08 10:00:00'),
            (2, '121', '2025-05-17 21:00:00', 'random_generator', '온라인 추첨', '11, 15, 28, 36, 41 + 7', 'completed', 1, 5, 800000.00, '2025-05-14 11:00:00'),
            (3, '52', '2025-05-19 19:00:00', 'machine_draw', '로또 추첨 스튜디오', '4, 9, 17, 25, 38, 44', 'completed', 1, 10, 950000.00, '2025-05-16 09:00:00'),
            (4, '135', '2025-05-20 18:00:00', 'machine_draw', '로또 추첨 스튜디오', '2, 11, 19, 27, 33, 41', 'completed', 1, 7, 600000.00, '2025-05-17 09:00:00'),
            (5, '25', '2025-05-16 20:00:00', 'machine_draw', '로또 추첨 스튜디오', '6, 14, 22, 31, 39, 43', 'completed', 1, 9, 1100000.00, '2025-05-13 08:00:00'),
            (1, '979', '2025-05-25 20:30:00', 'machine_draw', '로또 추첨 스튜디오', NULL, 'scheduled', 0, 0, 0.00, '2025-05-18 10:00:00'),
            (2, '122', '2025-05-24 21:00:00', 'random_generator', '온라인 추첨', NULL, 'scheduled', 0, 0, 0.00, '2025-05-18 11:00:00'),
            (4, '136', '2025-05-21 18:00:00', 'machine_draw', '로또 추첨 스튜디오', NULL, 'ready', 0, 0, 0.00, '2025-05-19 09:00:00');";
        
        $pdo->exec($insertSampleDraws);
        echo "샘플 추첨 데이터 추가 완료<br>";
    } else {
        echo "추첨 데이터가 이미 존재함<br>";
    }
    
    // 3. 샘플 당첨 데이터 (없을 경우)
    $checkWinningsQuery = "SELECT COUNT(*) FROM winnings";
    $stmt = $pdo->query($checkWinningsQuery);
    $winningsCount = $stmt->fetchColumn();
    
    if ($winningsCount == 0) {
        $insertSampleWinnings = "
        INSERT INTO `winnings` 
            (`draw_id`, `ticket_id`, `prize_tier`, `prize_amount`, `prize_description`, `claim_status`, `created_at`) 
        VALUES
            (1, 1, 3, 50000.00, '3등', 'paid', '2025-05-18 21:30:00'),
            (1, 2, 4, 5000.00, '4등', 'unclaimed', '2025-05-18 21:30:00'),
            (2, 3, 2, 200000.00, '2등', 'pending', '2025-05-11 21:30:00'),
            (3, 5, 1, 500000.00, '1등', 'paid', '2025-05-17 22:00:00');";
        
        $pdo->exec($insertSampleWinnings);
        echo "샘플 당첨 데이터 추가 완료<br>";
    } else {
        echo "당첨 데이터가 이미 존재함<br>";
    }
    
    // 4. 샘플 추첨 이력 데이터 (없을 경우)
    $checkDrawHistoryQuery = "SELECT COUNT(*) FROM draw_history";
    $stmt = $pdo->query($checkDrawHistoryQuery);
    $drawHistoryCount = $stmt->fetchColumn();
    
    if ($drawHistoryCount == 0) {
        $insertSampleDrawHistory = "
        INSERT INTO `draw_history` 
            (`draw_id`, `action`, `details`, `performed_by`, `performed_at`) 
        VALUES
            (1, 'created', '추첨 계획 생성', 1, '2025-05-15 10:00:00'),
            (1, 'started', '추첨 시작', 1, '2025-05-18 20:25:00'),
            (1, 'completed', '추첨 완료', 1, '2025-05-18 20:35:00'),
            (1, 'published', '추첨 결과 공개', 1, '2025-05-18 21:00:00'),
            (2, 'created', '추첨 계획 생성', 1, '2025-05-08 10:00:00'),
            (2, 'started', '추첨 시작', 1, '2025-05-11 20:25:00'),
            (2, 'completed', '추첨 완료', 1, '2025-05-11 20:35:00'),
            (2, 'published', '추첨 결과 공개', 1, '2025-05-11 21:00:00'),
            (7, 'created', '추첨 계획 생성', 1, '2025-05-18 10:00:00'),
            (8, 'created', '추첨 계획 생성', 1, '2025-05-18 11:00:00'),
            (9, 'created', '추첨 계획 생성', 1, '2025-05-19 09:00:00'),
            (9, 'ready', '추첨 준비 완료', 1, '2025-05-19 14:00:00');";
        
        $pdo->exec($insertSampleDrawHistory);
        echo "샘플 추첨 이력 데이터 추가 완료<br>";
    } else {
        echo "추첨 이력 데이터가 이미 존재함<br>";
    }
    
    // 5. 샘플 추첨 스케줄 데이터 (없을 경우)
    $checkDrawScheduleQuery = "SELECT COUNT(*) FROM draw_schedule";
    $stmt = $pdo->query($checkDrawScheduleQuery);
    $drawScheduleCount = $stmt->fetchColumn();
    
    if ($drawScheduleCount == 0) {
        $insertSampleDrawSchedule = "
        INSERT INTO `draw_schedule` 
            (`product_id`, `frequency`, `day_of_week`, `time_of_day`, `next_draw_date`, `last_draw_id`, `is_active`, `created_at`) 
        VALUES
            (1, 'weekly', 0, '20:30:00', '2025-05-25 20:30:00', 1, 1, '2025-01-01 00:00:00'),
            (2, 'weekly', 6, '21:00:00', '2025-05-24 21:00:00', 3, 1, '2025-01-01 00:00:00'),
            (3, 'biweekly', 1, '19:00:00', '2025-06-02 19:00:00', 4, 1, '2025-01-01 00:00:00'),
            (4, 'daily', NULL, '18:00:00', '2025-05-21 18:00:00', 5, 1, '2025-01-01 00:00:00'),
            (5, 'monthly', 5, '20:00:00', '2025-06-20 20:00:00', 6, 1, '2025-01-01 00:00:00');";
        
        $pdo->exec($insertSampleDrawSchedule);
        echo "샘플 추첨 스케줄 데이터 추가 완료<br>";
    } else {
        echo "추첨 스케줄 데이터가 이미 존재함<br>";
    }
    
    echo "<hr>";
    echo "모든 테이블 생성 및 샘플 데이터 설정이 완료되었습니다.<br>";
    echo "<a href='/server/dashboard/draw/history.php'>추첨 이력 페이지로 돌아가기</a>";
    
} catch(PDOException $e) {
    echo "오류: " . $e->getMessage() . "<br>";
}
