<?php
/**
 * 발행 계획 상태 변경 API
 * 
 * 발행 계획의 상태를 변경합니다.
 * - draft: 초안
 * - ready: 준비
 * - completed: 완료
 * - cancelled: 취소
 */

// 헤더 설정
header('Content-Type: application/json');

// 설정 및 공통 함수
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

// 로그 파일 설정
$logFile = LOGS_PATH . '/issue_status_change.log';

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
if (!isset($_POST['plan_id']) || !isset($_POST['new_status'])) {
    $response['message'] = '필수 파라미터가 누락되었습니다.';
    echo json_encode($response);
    exit;
}

$planId = (int) sanitizeInput($_POST['plan_id']);
$newStatus = sanitizeInput($_POST['new_status']);

// 상태 값 검증
$allowedStatuses = ['draft', 'ready', 'completed', 'cancelled'];
if (!in_array($newStatus, $allowedStatuses)) {
    $response['message'] = '허용되지 않는 상태 값입니다.';
    echo json_encode($response);
    exit;
}

try {
    // 데이터베이스 연결
    $db = get_db_connection();
    
    // 현재 발행 계획 상태 조회
    $stmt = $db->prepare("SELECT status FROM issue_plans WHERE id = ?");
    $stmt->execute([$planId]);
    $currentPlan = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$currentPlan) {
        $response['message'] = '발행 계획을 찾을 수 없습니다.';
        echo json_encode($response);
        exit;
    }
    
    $currentStatus = $currentPlan['status'];
    
    // 상태 변경 검증
    $allowChange = true;
    $message = '';
    
    switch ($currentStatus) {
        case 'draft':
            // 초안 -> 준비, 취소 가능
            if (!in_array($newStatus, ['ready', 'cancelled'])) {
                $allowChange = false;
                $message = '초안 상태에서는 준비 또는 취소 상태로만 변경 가능합니다.';
            }
            break;
            
        case 'ready':
            // 준비 -> 초안, 취소 가능
            if (!in_array($newStatus, ['draft', 'cancelled'])) {
                $allowChange = false;
                $message = '준비 상태에서는 초안 또는 취소 상태로만 변경 가능합니다.';
            }
            break;
            
        case 'in_progress':
            // 발행 중 -> 직접 상태 변경 불가
            $allowChange = false;
            $message = '발행 중인 계획은 직접 상태를 변경할 수 없습니다.';
            break;
            
        case 'completed':
        case 'cancelled':
            // 완료, 취소 -> 상태 변경 불가
            $allowChange = false;
            $message = '완료 또는 취소된 계획의 상태는 변경할 수 없습니다.';
            break;
    }
    
    if (!$allowChange) {
        $response['message'] = $message;
        echo json_encode($response);
        exit;
    }
    
    // 상태 변경 처리
    $stmt = $db->prepare("UPDATE issue_plans SET status = ?, updated_at = NOW() WHERE id = ?");
    $result = $stmt->execute([$newStatus, $planId]);
    
    if ($result) {
        // 활동 로그 기록
        $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1; // 기본값 설정
        $logMessage = "발행 계획 ID: $planId - 상태 변경: $currentStatus -> $newStatus (사용자 ID: $userId)";
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - $logMessage\n", FILE_APPEND);
        log_activity("발행 계획 상태 변경: $currentStatus -> $newStatus", 'issue_plans', $userId);
        
        $response['status'] = 'success';
        $response['message'] = '발행 계획 상태가 성공적으로 변경되었습니다.';
        $response['data'] = [
            'plan_id' => $planId,
            'old_status' => $currentStatus,
            'new_status' => $newStatus
        ];
    } else {
        $response['message'] = '발행 계획 상태 변경에 실패했습니다.';
    }
} catch (Exception $e) {
    $response['message'] = '오류가 발생했습니다: ' . $e->getMessage();
    error_log("API 오류 (change_status.php): " . $e->getMessage());
}

echo json_encode($response);
