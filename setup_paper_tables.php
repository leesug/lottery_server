<?php
// 용지관리 시스템 테이블 생성
require_once 'includes/config.php';
require_once 'includes/db.php';

try {
    $conn = get_db_connection();
    
    echo "용지관리 시스템 테이블 생성 시작...\n";
    
    // SQL 파일 읽기
    $sql = file_get_contents('create_paper_tables.sql');
    
    // SQL 문을 세미콜론으로 분리
    $queries = array_filter(array_map('trim', explode(';', $sql)));
    
    $success_count = 0;
    $error_count = 0;
    
    foreach ($queries as $query) {
        if (empty($query)) continue;
        
        try {
            $conn->exec($query);
            $success_count++;
            
            // 테이블 생성 확인
            if (stripos($query, 'CREATE TABLE') !== false) {
                preg_match('/CREATE TABLE IF NOT EXISTS (\w+)/', $query, $matches);
                if (isset($matches[1])) {
                    echo "✓ 테이블 생성됨: {$matches[1]}\n";
                }
            } elseif (stripos($query, 'INSERT INTO') !== false) {
                echo "✓ 데이터 입력 완료\n";
            }
        } catch (PDOException $e) {
            $error_count++;
            echo "✗ 오류 발생: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n=== 실행 결과 ===\n";
    echo "성공: {$success_count}개\n";
    echo "실패: {$error_count}개\n";
    
    // 생성된 테이블 목록 확인
    echo "\n=== 생성된 테이블 확인 ===\n";
    $tables = [
        'paper_boxes',
        'paper_rolls', 
        'paper_usage',
        'paper_serial_tracking',
        'paper_alerts',
        'paper_stock_history',
        'paper_length_settings'
    ];
    
    foreach ($tables as $table) {
        $stmt = $conn->query("SHOW TABLES LIKE '{$table}'");
        if ($stmt->rowCount() > 0) {
            echo "✓ {$table} 테이블 존재\n";
            
            // 테이블 구조 확인
            $stmt = $conn->query("DESCRIBE {$table}");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            echo "  컬럼: " . implode(', ', $columns) . "\n";
        } else {
            echo "✗ {$table} 테이블 없음\n";
        }
    }
    
} catch (Exception $e) {
    echo "오류 발생: " . $e->getMessage() . "\n";
}
