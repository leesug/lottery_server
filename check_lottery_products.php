<?php
// 데이터베이스 연결 설정
$host = 'localhost';
$dbname = 'lotto_server';
$username = 'root';
$password = '';

try {
    // PDO 연결
    $db = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // lottery_products 테이블 구조 확인
    $query = "DESCRIBE lottery_products";
    $stmt = $db->query($query);
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h1>lottery_products 테이블 구조</h1>";
    echo "<table border='1'><tr><th>필드</th><th>타입</th><th>NULL</th><th>키</th><th>기본값</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (PDOException $e) {
    echo "<h1>오류 발생</h1>";
    echo "<p>오류 메시지: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>