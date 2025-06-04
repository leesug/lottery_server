<?php
/**
 * 번호 예약 상세 정보 조회 API
 * 
 * 지정된 ID의 번호 예약 정보를 가져옵니다.
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

// GET 요청 확인
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    $response['message'] = '허용되지 않는 요청 방식입니다.';
    echo json_encode($response);
    exit;
}

// 필수 파라미터 확인
if (!isset($_GET['id'])) {
    $response['message'] = '필수 파라미터가 누락되었습니다.';
    echo json_encode($response);
    exit;
}

$id = (int) sanitize_input($_GET['id']);

try {
    // 데이터베이스 연결
    $db = get_db_connection();
    
    // 번호 체계 테이블 존재 여부 확인
    $stmt = $db->query("SHOW TABLES LIKE 'number_formats'");
    $formatTableExists = $stmt->rowCount() > 0;
    
    // 번호 예약 테이블 존재 여부 확인
    $stmt = $db->query("SHOW TABLES LIKE 'number_reservations'");
    $reservationTableExists = $stmt->rowCount() > 0;
    
    if (!$formatTableExists || !$reservationTableExists) {
        throw new Exception("필요한 테이블이 존재하지 않습니다.");
    }
    
    // 번호 체계 이름 필드 확인
    $stmt = $db->query("DESCRIBE number_formats");
    $columns = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $columns[] = $row['Field'];
    }
    $formatNameColumn = in_array('name', $columns) ? 'name' : 
                       (in_array('format_name', $columns) ? 'format_name' : 'id');
    
    // 예약 번호 필드 확인
    $stmt = $db->query("DESCRIBE number_reservations");
    $columns = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $columns[] = $row['Field'];
    }
    $reservedNumberColumn = in_array('reserved_number', $columns) ? 'reserved_number' : 
                           (in_array('numbers', $columns) ? 'numbers' : '');
    
    if (empty($reservedNumberColumn)) {
        throw new Exception("예약 번호 필드를 찾을 수 없습니다.");
    }
    
    // 번호 예약 정보 조회
    $stmt = $db->prepare("
        SELECT 
            nr.*,
            nf.$formatNameColumn AS format_name,
            u.username AS reserved_by_name
        FROM 
            number_reservations nr
        LEFT JOIN 
            number_formats nf ON nr.format_id = nf.id
        LEFT JOIN 
            users u ON nr.reserved_by = u.id
        WHERE 
            nr.id = ?
    ");
    
    $stmt->execute([$id]);
    $reservation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$reservation) {
        $response['message'] = '해당 ID의 번호 예약을 찾을 수 없습니다.';
        echo json_encode($response);
        exit;
    }
    
    // 사용자 정보가 없는 경우 처리
    if (!isset($reservation['reserved_by_name']) || $reservation['reserved_by_name'] === null) {
        $reservation['reserved_by_name'] = '알 수 없음';
    }
    
    $response['status'] = 'success';
    $response['message'] = '번호 예약 정보를 성공적으로 불러왔습니다.';
    $response['data'] = $reservation;
    
    // 로그 기록
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - 번호 예약 조회: ID: $id\n", FILE_APPEND);
    
} catch (Exception $e) {
    $response['message'] = '오류가 발생했습니다: ' . $e->getMessage();
    error_log("API 오류 (get_reservation.php): " . $e->getMessage());
}

echo json_encode($response);
