<?php
// 데이터베이스 연결 및 테이블 생성 테스트 스크립트

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
    
    // users 테이블이 없으면 생성 (외래 키 제약 조건을 위해)
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
    
    // lottery_products 테이블이 없으면 생성 (외래 키 제약 조건을 위해)
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
    
    // price_policies 테이블 생성
    $createPricePoliciesTable = "
    CREATE TABLE IF NOT EXISTS `price_policies` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `product_id` int(11) NOT NULL,
        `price` decimal(10,2) NOT NULL,
        `min_price` decimal(10,2) DEFAULT NULL,
        `max_price` decimal(10,2) DEFAULT NULL,
        `effective_date` date NOT NULL,
        `description` text DEFAULT NULL,
        `created_by` int(11) DEFAULT NULL,
        `updated_by` int(11) DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`),
        UNIQUE KEY `product_effective_date` (`product_id`, `effective_date`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    
    $pdo->exec($createPricePoliciesTable);
    echo "price_policies 테이블 존재 확인 또는 생성 완료<br>";
    
    // sales_limits 테이블 생성
    $createSalesLimitsTable = "
    CREATE TABLE IF NOT EXISTS `sales_limits` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `product_id` int(11) NOT NULL,
        `customer_daily_limit` int(11) DEFAULT 0,
        `store_daily_limit` int(11) DEFAULT 0,
        `min_purchase` int(11) DEFAULT 1,
        `max_purchase` int(11) DEFAULT 10,
        `effective_date` date NOT NULL,
        `description` text DEFAULT NULL,
        `created_by` int(11) DEFAULT NULL,
        `updated_by` int(11) DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`),
        UNIQUE KEY `product_effective_date` (`product_id`, `effective_date`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    
    $pdo->exec($createSalesLimitsTable);
    echo "sales_limits 테이블 존재 확인 또는 생성 완료<br>";
    
    // promotions 테이블 생성
    $createPromotionsTable = "
    CREATE TABLE IF NOT EXISTS `promotions` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(255) NOT NULL,
        `type` enum('percentage','fixed','bonus') NOT NULL DEFAULT 'percentage',
        `discount_amount` decimal(10,2) NOT NULL,
        `min_quantity` int(11) NOT NULL DEFAULT 1,
        `start_date` date NOT NULL,
        `end_date` date NOT NULL,
        `status` enum('active','inactive','scheduled') NOT NULL DEFAULT 'active',
        `description` text DEFAULT NULL,
        `created_by` int(11) DEFAULT NULL,
        `updated_by` int(11) DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    
    $pdo->exec($createPromotionsTable);
    echo "promotions 테이블 존재 확인 또는 생성 완료<br>";
    
    // promotion_products 테이블 생성
    $createPromotionProductsTable = "
    CREATE TABLE IF NOT EXISTS `promotion_products` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `promotion_id` int(11) NOT NULL,
        `product_id` int(11) NOT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`),
        UNIQUE KEY `promotion_product` (`promotion_id`, `product_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    
    $pdo->exec($createPromotionProductsTable);
    echo "promotion_products 테이블 존재 확인 또는 생성 완료<br>";
    
    // 샘플 데이터 추가 (lottery_products)
    $checkProductsQuery = "SELECT COUNT(*) FROM lottery_products";
    $stmt = $pdo->query($checkProductsQuery);
    $productCount = $stmt->fetchColumn();
    
    if ($productCount == 0) {
        $insertSampleProducts = "
        INSERT INTO `lottery_products` (`product_code`, `name`, `price`, `description`, `status`) VALUES
        ('LO001', '로또 6/45', 1000.00, '1부터 45까지의 숫자 중 6개 선택', 'active'),
        ('PL001', '파워볼', 2000.00, '1부터 69까지의 숫자 중 5개와 파워볼 1개 선택', 'active'),
        ('QU001', '로또 퀵픽', 1000.00, '자동으로 번호 선택', 'active');";
        
        $pdo->exec($insertSampleProducts);
        echo "샘플 상품 데이터 추가 완료<br>";
    } else {
        echo "상품 데이터가 이미 존재함<br>";
    }
    
    echo "<hr>";
    echo "모든 테이블 생성 및 초기 설정이 완료되었습니다.<br>";
    echo "<a href='/server/dashboard/sales/policy.php'>판매 정책 페이지로 돌아가기</a>";
    
} catch(PDOException $e) {
    echo "오류: " . $e->getMessage() . "<br>";
}
