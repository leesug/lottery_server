<?php
/**
 * 번호 예약 API
 * 
 * 특정 번호 체계에 대해 번호를 예약합니다.
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
$logFile = LOGS_PATH . '/number_reservation.log';

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
if (!isset($_POST['format_id']) || !isset($_POST['reserved_number']) || !isset($_POST['reason']) || !isset($_POST['status'])) {
    $response['message'] = '필수 파라미터가 누락되었습니다.';
    echo json_encode($response);
    exit;
}

$format_id = (int) sanitize_input($_POST['format_id']);
$numbers = sanitize_input($_POST['reserved_number']); // 클라이언트에서는 reserved_number로 보냄
$reason = sanitize_input($_POST['reason']);
$status = sanitize_input($_POST['status']);

// 허용된 상태 값 확인
if (!in_array($status, ['active', 'expired', 'cancelled'])) {
    $response['message'] = '유효하지 않은 상태 값입니다.';
    echo json_encode($response);
    exit;
}

// 필수 필드 검증
if ($format_id <= 0 || empty($numbers) || empty($reason)) {
    $response['message'] = '유효하지 않은 입력값이 있습니다.';
    echo json_encode($response);
    exit;
}

try {
    // 데이터베이스 연결
    $db = get_db_connection();
    
    // 테이블 존재 여부 확인
    $stmt = $db->query("SHOW TABLES LIKE 'number_reservations'");
    $tableExists = $stmt->rowCount() > 0;
    
    // 테이블이 없으면 생성
    if (!$tableExists) {
        $db->exec("CREATE TABLE `number_reservations` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `format_id` int(11) NOT NULL,
            `reserved_number` varchar(255) NOT NULL,
            `reason` text NOT NULL,
            `status` enum('active','expired','cancelled') NOT NULL DEFAULT 'active',
            `reserved_by` int(11) DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `format_id` (`format_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
        
        // 로그 기록
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - number_reservations 테이블이 존재하지 않아 새로 생성했습니다.\n", FILE_APPEND);
    }
    
    // 현재 사용자 ID 가져오기
    $userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 1;
    
    // 번호 예약 추가
    $stmt = $db->prepare("
        INSERT INTO number_reservations (
            format_id, reserved_number, reason, status, reserved_by
        ) VALUES (
            ?, ?, ?, ?, ?
        )
    ");
    
    $stmt->execute([
        $format_id, $numbers, $reason, $status, $userId
    ]);
    
    $reservationId = $db->lastInsertId();
    
    $response['status'] = 'success';
    $response['message'] = '번호 예약이 성공적으로 추가되었습니다.';
    $response['data'] = [
        'id' => $reservationId,
        'format_id' => $format_id,
        'numbers' => $numbers,
        'status' => $status
    ];
    
    // 로그 기록
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - 번호 예약 추가: 번호 체계 ID: $format_id, 번호: $numbers (ID: $reservationId, 사용자: $userId)\n", FILE_APPEND);
    
} catch (Exception $e) {
    $response['message'] = '오류가 발생했습니다: ' . $e->getMessage();
    error_log("API 오류 (reserve_numbers.php): " . $e->getMessage());
}

echo json_encode($response);
