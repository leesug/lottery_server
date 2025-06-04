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
    
    // 테이블 목록 가져오기
    $stmt = $db->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h1>lotto_server 데이터베이스 테이블 목록</h1>";
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>" . htmlspecialchars($table) . "</li>";
    }
    echo "</ul>";
    
    // 테이블 구조 확인
    echo "<h2>당첨 관련 테이블 구조</h2>";
    
    // 'winnings' 테이블이 있는지 확인
    if (in_array('winnings', $tables)) {
        $stmt = $db->query("DESCRIBE winnings");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>winnings 테이블 구조</h3>";
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
    } else {
        echo "<p>'winnings' 테이블이 존재하지 않습니다.</p>";
    }
    
    // 'draw_winners' 테이블이 있는지 확인
    if (in_array('draw_winners', $tables)) {
        $stmt = $db->query("DESCRIBE draw_winners");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>draw_winners 테이블 구조</h3>";
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
    } else {
        echo "<p>'draw_winners' 테이블이 존재하지 않습니다.</p>";
    }
    
    // 'lottery_draws' 테이블이 있는지 확인
    if (in_array('lottery_draws', $tables)) {
        $stmt = $db->query("DESCRIBE lottery_draws");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>lottery_draws 테이블 구조</h3>";
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
    } else {
        echo "<p>'lottery_draws' 테이블이 존재하지 않습니다.</p>";
    }
    
    // 'draws' 테이블이 있는지 확인
    if (in_array('draws', $tables)) {
        $stmt = $db->query("DESCRIBE draws");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>draws 테이블 구조</h3>";
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
    } else {
        echo "<p>'draws' 테이블이 존재하지 않습니다.</p>";
    }
    
} catch (PDOException $e) {
    echo "<h1>오류 발생</h1>";
    echo "<p>오류 메시지: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>