<?php
/**
 * number_formats 테이블 구조 확인
 */

// 설정 및 공통 함수
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// 데이터베이스 연결
$db = get_db_connection();

// 테이블 구조 확인
try {
    $query = "DESCRIBE number_formats";
    $stmt = $db->query($query);
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>number_formats 테이블 구조</h2>";
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . $column['Field'] . "</td>";
        echo "<td>" . $column['Type'] . "</td>";
        echo "<td>" . $column['Null'] . "</td>";
        echo "<td>" . $column['Key'] . "</td>";
        echo "<td>" . $column['Default'] . "</td>";
        echo "<td>" . $column['Extra'] . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} catch (PDOException $e) {
    echo "오류가 발생했습니다: " . $e->getMessage();
    error_log("Database error: " . $e->getMessage());
}
