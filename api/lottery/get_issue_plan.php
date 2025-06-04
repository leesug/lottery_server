<?php
/**
 * 발행 계획 조회 API
 * 
 * 특정 ID를 가진 발행 계획의 상세 정보를 조회합니다.
 */

// 헤더 설정
header('Content-Type: application/json');

// 설정 및 공통 함수
require_once '../../includes/config.php';
require_once '../../includes/db.php';

// 응답 초기화
$response = [
    'status' => 'error',
    'message' => '잘못된 요청입니다.',
    'data' => null
];

// ID 확인
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode($response);
    exit;
}

$id = (int) $_GET['id'];

try {
    // 데이터베이스 연결
    $db = get_db_connection();
    
    // 발행 계획 조회
    $stmt = $db->prepare("
        SELECT 
            ip.*,
            lp.product_code,
            lp.name AS product_name,
            IFNULL(iq.status, '') AS queue_status,
            IFNULL(iq.processed_tickets, 0) AS processed_tickets,
            IFNULL(iq.id, 0) AS queue_id
        FROM 
            issue_plans ip
        JOIN 
            lottery_products lp ON ip.product_id = lp.id
        LEFT JOIN 
            issue_queue iq ON ip.id = iq.plan_id AND iq.status IN ('pending', 'in_progress')
        WHERE 
            ip.id = ?
    ");
    
    $stmt->execute([$id]);
    $plan = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($plan) {
        $response['status'] = 'success';
        $response['message'] = '';
        $response['data'] = $plan;
    } else {
        $response['message'] = '발행 계획을 찾을 수 없습니다.';
    }
} catch (PDOException $e) {
    $response['message'] = '데이터베이스 오류가 발생했습니다: ' . $e->getMessage();
    error_log("API 오류: " . $e->getMessage());
}

echo json_encode($response);
