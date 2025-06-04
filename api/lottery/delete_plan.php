<?php
/**
 * 발행 계획 삭제 API
 * 
 * 발행 계획을 삭제합니다.
 * 초안(draft) 상태의 계획만 삭제 가능합니다.
 */

// 헤더 설정
header('Content-Type: application/json');

// 설정 및 공통 함수
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

// 로그 파일 설정
$logFile = LOGS_PATH . '/issue_delete.log';

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
if (!isset($_POST['plan_id'])) {
    $response['message'] = '필수 파라미터가 누락되었습니다.';
    echo json_encode($response);
    exit;
}

$planId = (int) sanitizeInput($_POST['plan_id']);

try {
    // 데이터베이스 연결
    $db = get_db_connection();
    
    // 현재 발행 계획 상태 조회
    $stmt = $db->prepare("SELECT status, product_id FROM issue_plans WHERE id = ?");
    $stmt->execute([$planId]);
    $currentPlan = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$currentPlan) {
        $response['message'] = '발행 계획을 찾을 수 없습니다.';
        echo json_encode($response);
        exit;
    }
    
    $currentStatus = $currentPlan['status'];
    $productId = $currentPlan['product_id'];
    
    // 초안 상태의 계획만 삭제 가능
    if ($currentStatus !== 'draft') {
        $response['message'] = '초안 상태의 계획만 삭제할 수 있습니다.';
        echo json_encode($response);
        exit;
    }
    
    // 관련된 복권 상품 정보 조회 (로그용)
    $stmt = $db->prepare("SELECT product_code, name FROM lottery_products WHERE id = ?");
    $stmt->execute([$productId]);
    $productInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    $productName = $productInfo ? $productInfo['name'] . ' (' . $productInfo['product_code'] . ')' : '알 수 없음';
    
    // 삭제 처리
    $stmt = $db->prepare("DELETE FROM issue_plans WHERE id = ?");
    $result = $stmt->execute([$planId]);
    
    if ($result) {
        // 활동 로그 기록
        $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1; // 기본값 설정
        $logMessage = "발행 계획 ID: $planId - 상품: $productName - 삭제됨 (사용자 ID: $userId)";
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - $logMessage\n", FILE_APPEND);
        log_activity("발행 계획 삭제: $productName", 'issue_plans', $userId);
        
        $response['status'] = 'success';
        $response['message'] = '발행 계획이 성공적으로 삭제되었습니다.';
        $response['data'] = [
            'plan_id' => $planId
        ];
    } else {
        $response['message'] = '발행 계획 삭제에 실패했습니다.';
    }
} catch (Exception $e) {
    $response['message'] = '오류가 발생했습니다: ' . $e->getMessage();
    error_log("API 오류 (delete_plan.php): " . $e->getMessage());
}

echo json_encode($response);
