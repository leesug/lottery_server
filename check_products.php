<?php
// 데이터베이스 조회 스크립트
error_reporting(E_ALL);
ini_set('display_errors', 1);

// DB 연결 정보
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'lottery';

try {
    // PDO 연결
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ];
    
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    // lottery_products 테이블 데이터 조회
    $sql = "SELECT * FROM lottery_products ORDER BY id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $products = $stmt->fetchAll();
    
    echo "<h1>복권 상품 데이터</h1>";
    
    if (count($products) > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr>";
        echo "<th>ID</th>";
        echo "<th>상품코드</th>";
        echo "<th>상품명</th>";
        echo "<th>가격</th>";
        echo "<th>번호형식</th>";
        echo "<th>추첨일정</th>";
        echo "<th>상태</th>";
        echo "<th>생성일</th>";
        echo "<th>수정일</th>";
        echo "</tr>";
        
        foreach ($products as $product) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($product['id']) . "</td>";
            echo "<td>" . htmlspecialchars($product['product_code']) . "</td>";
            echo "<td>" . htmlspecialchars($product['name']) . "</td>";
            echo "<td>" . htmlspecialchars($product['price']) . "</td>";
            echo "<td>" . htmlspecialchars($product['number_format']) . "</td>";
            echo "<td>" . htmlspecialchars($product['draw_schedule']) . "</td>";
            echo "<td>" . htmlspecialchars($product['status']) . "</td>";
            echo "<td>" . htmlspecialchars($product['created_at']) . "</td>";
            echo "<td>" . htmlspecialchars($product['updated_at']) . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p>등록된 상품이 없습니다.</p>";
    }
    
    // 삭제 테스트 폼
    echo "<h2>삭제 테스트</h2>";
    echo "<form method='post' action=''>";
    echo "<label>삭제할 상품 ID: <input type='number' name='delete_id' required></label> ";
    echo "<button type='submit' name='action' value='delete'>삭제</button>";
    echo "</form>";
    
    // 삭제 처리
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
        $deleteId = (int)$_POST['delete_id'];
        
        $sql = "DELETE FROM lottery_products WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $deleteId, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            echo "<p style='color:green'>ID $deleteId 상품이 성공적으로 삭제되었습니다.</p>";
            echo "<script>setTimeout(function() { window.location.reload(); }, 1500);</script>";
        } else {
            echo "<p style='color:red'>삭제 중 오류가 발생했습니다.</p>";
        }
    }
    
} catch (PDOException $e) {
    echo "<h1>오류 발생</h1>";
    echo "<p style='color:red'>" . $e->getMessage() . "</p>";
}
?>