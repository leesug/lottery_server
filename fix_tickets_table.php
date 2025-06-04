<?php
// 티켓 테이블 필드 수정 스크립트

// 연결 설정
$host = 'localhost';
$username = 'root';
$password = '';
$dbname = 'lotto_server';

// 연결 시도
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 티켓 테이블 구조 확인
    $query = "DESCRIBE tickets";
    $stmt = $pdo->query($query);
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "현재 tickets 테이블 구조:<br>";
    echo "<pre>";
    print_r($columns);
    echo "</pre>";
    
    // price 컬럼이 없으면 추가
    if (!in_array('price', $columns)) {
        $pdo->exec("ALTER TABLE tickets ADD COLUMN price decimal(10,2) NOT NULL DEFAULT 0 AFTER numbers");
        echo "price 컬럼이 추가되었습니다.<br>";
    } else {
        echo "price 컬럼이 이미 존재합니다.<br>";
    }
    
    // ticket_number 컬럼이 없으면 추가
    if (!in_array('ticket_number', $columns)) {
        $pdo->exec("ALTER TABLE tickets ADD COLUMN ticket_number varchar(50) NOT NULL AFTER id");
        $pdo->exec("ALTER TABLE tickets ADD UNIQUE KEY ticket_number (ticket_number)");
        echo "ticket_number 컬럼이 추가되었습니다.<br>";
    } else {
        echo "ticket_number 컬럼이 이미 존재합니다.<br>";
    }
    
    // terminal_id 컬럼이 없으면 추가
    if (!in_array('terminal_id', $columns)) {
        $pdo->exec("ALTER TABLE tickets ADD COLUMN terminal_id int(11) NOT NULL AFTER product_id");
        $pdo->exec("ALTER TABLE tickets ADD KEY terminal_id (terminal_id)");
        echo "terminal_id 컬럼이 추가되었습니다.<br>";
    } else {
        echo "terminal_id 컬럼이 이미 존재합니다.<br>";
    }
    
    // numbers 컬럼이 없으면 추가
    if (!in_array('numbers', $columns)) {
        $pdo->exec("ALTER TABLE tickets ADD COLUMN numbers varchar(255) NOT NULL AFTER terminal_id");
        echo "numbers 컬럼이 추가되었습니다.<br>";
    } else {
        echo "numbers 컬럼이 이미 존재합니다.<br>";
    }
    
    // status 컬럼이 없으면 추가
    if (!in_array('status', $columns)) {
        $pdo->exec("ALTER TABLE tickets ADD COLUMN status enum('active','cancelled','verified','expired','invalid') NOT NULL DEFAULT 'active' AFTER price");
        $pdo->exec("ALTER TABLE tickets ADD KEY status (status)");
        echo "status 컬럼이 추가되었습니다.<br>";
    } else {
        echo "status 컬럼이 이미 존재합니다.<br>";
    }
    
    // 수정 후 테이블 구조 다시 확인
    $query = "DESCRIBE tickets";
    $stmt = $pdo->query($query);
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<hr>수정 후 tickets 테이블 구조:<br>";
    echo "<pre>";
    print_r($columns);
    echo "</pre>";
    
    echo "<a href='/server/dashboard/sales/refund.php'>판매 취소/환불 페이지로 돌아가기</a>";
    
} catch(PDOException $e) {
    echo "오류: " . $e->getMessage() . "<br>";
}
