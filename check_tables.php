<?php
// 데이터베이스 연결 정보
$host = 'localhost';
$dbname = 'lotto_server';
$username = 'root';
$password = '';

try {
    // PDO 객체 생성 (데이터베이스 연결)
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 테이블 목록 가져오기
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h1>lotto_server 데이터베이스 구조</h1>";
    
    // 각 테이블의 구조 표시
    foreach ($tables as $table) {
        echo "<h2>테이블: $table</h2>";
        
        // 테이블 구조 가져오기
        $columns = $pdo->query("SHOW COLUMNS FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>{$column['Field']}</td>";
            echo "<td>{$column['Type']}</td>";
            echo "<td>{$column['Null']}</td>";
            echo "<td>{$column['Key']}</td>";
            echo "<td>{$column['Default']}</td>";
            echo "<td>{$column['Extra']}</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        
        // 레코드 수 표시
        $count = $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
        echo "<p>레코드 수: $count</p>";
        
        if ($count > 0 && $count < 10) {
            // 데이터 샘플 표시 (최대 10개 행)
            echo "<h3>데이터 샘플:</h3>";
            $data = $pdo->query("SELECT * FROM `$table` LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($data)) {
                echo "<table border='1' cellpadding='5'>";
                
                // 테이블 헤더
                echo "<tr>";
                foreach (array_keys($data[0]) as $header) {
                    echo "<th>$header</th>";
                }
                echo "</tr>";
                
                // 테이블 데이터
                foreach ($data as $row) {
                    echo "<tr>";
                    foreach ($row as $value) {
                        echo "<td>" . (strlen($value) > 50 ? substr($value, 0, 50) . "..." : $value) . "</td>";
                    }
                    echo "</tr>";
                }
                
                echo "</table>";
            }
        }
        
        echo "<hr>";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>