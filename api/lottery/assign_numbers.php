<?php
/**
 * 번호 할당 API
 * 
 * 복권 상품, 판매점, 번호 체계에 따라 번호를 할당합니다.
 */

// 헤더 설정
header('Content-Type: application/json');

// 설정 및 공통 함수
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

// sanitize_input 함수가 없을 경우 정의
if (!function_exists('sanitize_input')) {
    function sanitize_input($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        return $data;
    }
}

// 로그 파일 설정
$logFile = LOGS_PATH . '/number_assignment.log';

// 응답 초기화
$response = [
    'status' => 'error',
    'message' => '잘못된 요청입니다.',
    'data' => null
];

// POST 요청 확인
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = '허용되지 않는 요청 방식입니다.';
    echo json_encode($response);
    exit;
}

// 필수 파라미터 확인
if (!isset($_POST['product_id']) || !isset($_POST['format_id']) || !isset($_POST['store_id']) || 
    !isset($_POST['start_number']) || !isset($_POST['end_number']) || !isset($_POST['quantity'])) {
    $response['message'] = '필수 파라미터가 누락되었습니다.';
    echo json_encode($response);
    exit;
}

$product_id = (int) sanitize_input($_POST['product_id']);
$format_id = (int) sanitize_input($_POST['format_id']);
$store_id = (int) sanitize_input($_POST['store_id']);
$start_number = sanitize_input($_POST['start_number']);
$end_number = sanitize_input($_POST['end_number']);
$quantity = (int) sanitize_input($_POST['quantity']);
$notes = sanitize_input($_POST['notes'] ?? '');

// 필수 필드 검증
if ($product_id <= 0 || $format_id <= 0 || $store_id <= 0 || empty($start_number) || empty($end_number) || $quantity <= 0) {
    $response['message'] = '유효하지 않은 입력값이 있습니다.';
    echo json_encode($response);
    exit;
}

try {
    // 데이터베이스 연결
    $db = get_db_connection();
    
    // 테이블 존재 여부 확인
    $stmt = $db->query("SHOW TABLES LIKE 'number_assignments'");
    $tableExists = $stmt->rowCount() > 0;
    
    // 테이블이 없으면 생성
    if (!$tableExists) {
        $db->exec("CREATE TABLE `number_assignments` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `product_id` int(11) NOT NULL,
            `format_id` int(11) NOT NULL,
            `store_id` int(11) NOT NULL,
            `start_number` varchar(50) NOT NULL,
            `end_number` varchar(50) NOT NULL,
            `quantity` int(11) NOT NULL,
            `notes` text,
            `status` enum('active','used','expired','cancelled') NOT NULL DEFAULT 'active',
            `assigned_by` int(11) DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `product_id` (`product_id`),
            KEY `format_id` (`format_id`),
            KEY `store_id` (`store_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
        
        // 로그 기록
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - number_assignments 테이블이 존재하지 않아 새로 생성했습니다.\n", FILE_APPEND);
    }
    
    // 현재 사용자 ID 가져오기
    $userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 1;
    
    // 중복 확인 (동일 상품, 번호 체계, 판매점에 대한 동일 범위의 번호가 이미 할당되어 있는지)
    $stmt = $db->prepare("
        SELECT COUNT(*) as count 
        FROM number_assignments 
        WHERE product_id = ? 
          AND format_id = ? 
          AND store_id = ? 
          AND status = 'active'
          AND (
              (start_number <= ? AND end_number >= ?) OR
              (start_number <= ? AND end_number >= ?)
          )
    ");
    
    $stmt->execute([
        $product_id, $format_id, $store_id, 
        $start_number, $start_number, 
        $end_number, $end_number
    ]);
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] > 0) {
        $response['message'] = '해당 범위의 번호가 이미 할당되어 있습니다.';
        echo json_encode($response);
        exit;
    }
    
    // 번호 할당 추가
    $stmt = $db->prepare("
        INSERT INTO number_assignments (
            product_id, format_id, store_id, start_number, end_number,
            quantity, notes, status, assigned_by
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, 'active', ?
        )
    ");
    
    $stmt->execute([
        $product_id, $format_id, $store_id, $start_number, $end_number,
        $quantity, $notes, $userId
    ]);
    
    $assignmentId = $db->lastInsertId();
    
    $response['status'] = 'success';
    $response['message'] = '번호 할당이 성공적으로 추가되었습니다.';
    $response['data'] = [
        'id' => $assignmentId,
        'product_id' => $product_id,
        'format_id' => $format_id,
        'store_id' => $store_id,
        'start_number' => $start_number,
        'end_number' => $end_number,
        'quantity' => $quantity
    ];
    
    // 로그 기록
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - 번호 할당 추가: 상품 ID: $product_id, 판매점 ID: $store_id, 범위: $start_number ~ $end_number (ID: $assignmentId, 사용자: $userId)\n", FILE_APPEND);
    
} catch (Exception $e) {
    $response['message'] = '오류가 발생했습니다: ' . $e->getMessage();
    error_log("API 오류 (assign_numbers.php): " . $e->getMessage());
}

echo json_encode($response);
