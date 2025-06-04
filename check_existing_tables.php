<?php
// 기존 테이블 확인
require_once 'includes/config.php';
require_once 'includes/db.php';

try {
    $conn = get_db_connection();
    
    echo "=== 기존 테이블 확인 ===\n";
    
    // stores 테이블 확인
    $stmt = $conn->query("SHOW TABLES LIKE 'stores'");
    if ($stmt->rowCount() > 0) {
        echo "✓ stores 테이블 존재\n";
        $stmt = $conn->query("DESCRIBE stores");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "  주요 컬럼:\n";
        foreach ($columns as $col) {
            if ($col['Field'] == 'id' || $col['Field'] == 'store_code' || $col['Field'] == 'store_name') {
                echo "    - {$col['Field']} ({$col['Type']})\n";
            }
        }
    } else {
        echo "✗ stores 테이블 없음\n";
    }
    
    // users 테이블 확인
    $stmt = $conn->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() > 0) {
        echo "✓ users 테이블 존재\n";
        $stmt = $conn->query("DESCRIBE users");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "  주요 컬럼:\n";
        foreach ($columns as $col) {
            if ($col['Field'] == 'id' || $col['Field'] == 'username' || $col['Field'] == 'email') {
                echo "    - {$col['Field']} ({$col['Type']})\n";
            }
        }
    } else {
        echo "✗ users 테이블 없음\n";
    }
    
    // 모든 테이블 목록
    echo "\n=== 전체 테이블 목록 ===\n";
    $stmt = $conn->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $table) {
        echo "- {$table}\n";
    }
    
} catch (Exception $e) {
    echo "오류 발생: " . $e->getMessage() . "\n";
}
