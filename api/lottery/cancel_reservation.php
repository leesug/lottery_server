<?php
/**
 * 번호 예약 취소 API
 * 
 * 지정된 ID의 번호 예약을 취소합니다.
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
if (!isset($_POST['reservation_id'])) {
    $response['message'] = '필수 파라미터가 누락되었습니다.';
    echo json_encode($response);
    exit;
}

$reservation_id = (int) sanitize_input($_POST['reservation_id']);

try {
    // 데이터베이스 연결
    $db = get_db_connection();
    
    // 번호 예약 테이블 존재 여부 확인
    $stmt = $db->query("SHOW TABLES LIKE 'number_reservations'");
    $tableExists = $stmt->rowCount() > 0;
    
    if (!$tableExists) {
        throw new Exception("number_reservations 테이블이 존재하지 않습니다.");
    }
    
    // 해당 예약이 존재하는지 확인
    $stmt = $db->prepare("SELECT id, status FROM number_reservations WHERE id = ?");
    $stmt->execute([$reservation_id]);
    $reservation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$reservation) {
        $response['message'] = '해당 ID의 번호 예약을 찾을 수 없습니다.';
        echo json_encode($response);
        exit;
    }
    
    // 이미 취소된 예약인지 확인
    if ($reservation['status'] === 'cancelled') {
        $response['message'] = '이미 취소된 예약입니다.';
        echo json_encode($response);
        exit;
    }
    
    // 현재 사용자 ID 가져오기
    $userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 1;
    
    // 예약 상태 업데이트
    $stmt = $db->prepare("
        UPDATE number_reservations
        SET status = 'cancelled',
            updated_by = ?,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = ?
    ");
    
    $success = $stmt->execute([$userId, $reservation_id]);
    
    if (!$success) {
        throw new Exception("데이터베이스 업데이트 오류가 발생했습니다.");
    }
    
    $response['status'] = 'success';
    $response['message'] = '번호 예약이 성공적으로 취소되었습니다.';
    $response['data'] = [
        'id' => $reservation_id,
        'status' => 'cancelled'
    ];
    
    // 로그 기록
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - 번호 예약 취소: ID: $reservation_id, 사용자: $userId\n", FILE_APPEND);
    
} catch (Exception $e) {
    $response['message'] = '오류가 발생했습니다: ' . $e->getMessage();
    error_log("API 오류 (cancel_reservation.php): " . $e->getMessage());
}

echo json_encode($response);
