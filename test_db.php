<?php
// 데이터베이스 연결 테스트 스크립트
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>MySQL 연결 테스트</h1>";

// 연결 정보
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'lottery';

// MySQL 서비스 실행 중인지 확인
$connection_test = @fsockopen($host, 3306);
if (!$connection_test) {
    echo "<p style='color:red'>MySQL 서비스에 연결할 수 없습니다. XAMPP Control Panel에서 MySQL이 실행 중인지 확인하세요.</p>";
    exit;
}
fclose($connection_test);
echo "<p style='color:green'>MySQL 서비스가 실행 중입니다.</p>";

// PDO 방식으로 연결 시도
try {
    echo "<h2>PDO 방식 연결 테스트</h2>";
    $dsn = "mysql:host=$host;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ];
    
    $pdo = new PDO($dsn, $user, $pass, $options);
    echo "<p style='color:green'>MySQL 서버에 성공적으로 연결했습니다.</p>";
    
    // 데이터베이스가 있는지 확인
    $stmt = $pdo->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$dbname'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color:green'>데이터베이스 '$dbname'이(가) 존재합니다.</p>";
        
        // 데이터베이스 선택
        $pdo->exec("USE `$dbname`");
        
        // 테이블 목록 조회
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        if (count($tables) > 0) {
            echo "<p>데이터베이스에 다음 테이블이 있습니다:</p>";
            echo "<ul>";
            foreach ($tables as $table) {
                echo "<li>$table</li>";
            }
            echo "</ul>";
        } else {
            echo "<p>데이터베이스에 테이블이 없습니다.</p>";
            
            // lottery_products 테이블 생성
            echo "<h3>lottery_products 테이블 생성 시도</h3>";
            $createTableSQL = "CREATE TABLE IF NOT EXISTS lottery_products (
                id INT AUTO_INCREMENT PRIMARY KEY,
                product_code VARCHAR(50) NOT NULL UNIQUE,
                name VARCHAR(100) NOT NULL,
                description TEXT,
                price DECIMAL(10, 2) NOT NULL,
                number_format VARCHAR(50),
                draw_schedule VARCHAR(100),
                prize_structure TEXT,
                status ENUM('active', 'preparing', 'inactive') DEFAULT 'inactive',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            try {
                $pdo->exec($createTableSQL);
                echo "<p style='color:green'>lottery_products 테이블이 성공적으로 생성되었습니다.</p>";
            } catch (PDOException $e) {
                echo "<p style='color:red'>테이블 생성 실패: " . $e->getMessage() . "</p>";
            }
        }
    } else {
        echo "<p style='color:blue'>데이터베이스 '$dbname'이(가) 존재하지 않습니다. 생성을 시도합니다.</p>";
        
        // 데이터베이스 생성
        try {
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            echo "<p style='color:green'>데이터베이스 '$dbname'이(가) 성공적으로 생성되었습니다.</p>";
            
            // 데이터베이스 선택
            $pdo->exec("USE `$dbname`");
            
            // lottery_products 테이블 생성
            echo "<h3>lottery_products 테이블 생성 시도</h3>";
            $createTableSQL = "CREATE TABLE IF NOT EXISTS lottery_products (
                id INT AUTO_INCREMENT PRIMARY KEY,
                product_code VARCHAR(50) NOT NULL UNIQUE,
                name VARCHAR(100) NOT NULL,
                description TEXT,
                price DECIMAL(10, 2) NOT NULL,
                number_format VARCHAR(50),
                draw_schedule VARCHAR(100),
                prize_structure TEXT,
                status ENUM('active', 'preparing', 'inactive') DEFAULT 'inactive',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            try {
                $pdo->exec($createTableSQL);
                echo "<p style='color:green'>lottery_products 테이블이 성공적으로 생성되었습니다.</p>";
            } catch (PDOException $e) {
                echo "<p style='color:red'>테이블 생성 실패: " . $e->getMessage() . "</p>";
            }
        } catch (PDOException $e) {
            echo "<p style='color:red'>데이터베이스 생성 실패: " . $e->getMessage() . "</p>";
        }
    }
    
} catch (PDOException $e) {
    echo "<p style='color:red'>PDO 연결 실패: " . $e->getMessage() . "</p>";
}

// mysqli 방식으로 연결 시도
try {
    echo "<h2>mysqli 방식 연결 테스트</h2>";
    $mysqli = new mysqli($host, $user, $pass);
    
    if ($mysqli->connect_error) {
        echo "<p style='color:red'>MySQLi 연결 실패: " . $mysqli->connect_error . "</p>";
    } else {
        echo "<p style='color:green'>MySQLi 방식으로 MySQL 서버에 성공적으로 연결했습니다.</p>";
        
        // 데이터베이스가 있는지 확인
        $result = $mysqli->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$dbname'");
        if ($result && $result->num_rows > 0) {
            echo "<p style='color:green'>데이터베이스 '$dbname'이(가) 존재합니다.</p>";
            
            // 데이터베이스 선택
            $mysqli->select_db($dbname);
        } else {
            echo "<p style='color:blue'>데이터베이스 '$dbname'이(가) 존재하지 않습니다.</p>";
        }
        
        $mysqli->close();
    }
} catch (Exception $e) {
    echo "<p style='color:red'>MySQLi 테스트 실패: " . $e->getMessage() . "</p>";
}

echo "<h2>연결 정보 요약</h2>";
echo "<p>호스트: $host</p>";
echo "<p>사용자: $user</p>";
echo "<p>데이터베이스: $dbname</p>";
echo "<p>XAMPP 경로: C:\\xampp</p>";

echo "<h2>문제 해결 가이드</h2>";
echo "<ol>";
echo "<li>XAMPP Control Panel을 실행하여 MySQL 서비스가 실행 중인지 확인하세요.</li>";
echo "<li>MySQL 서비스가 중지되어 있다면 'Start' 버튼을 클릭하여 시작하세요.</li>";
echo "<li>MySQL 서비스를 시작하는 데 문제가 있다면, XAMPP Control Panel에서 'Logs' 버튼을 클릭하여 오류 로그를 확인하세요.</li>";
echo "<li>still access denied? Try setting password='' for the user 'root' in the MySQL config.</li>";
echo "</ol>";

echo "<p>이 페이지를 다시 로드하여 연결을 다시 테스트하세요.</p>";
?>