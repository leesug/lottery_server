<?php
/**
 * 복권 발행 시작 API
 * 
 * 준비(ready) 상태의 발행 계획을 발행 중(in_progress) 상태로 변경하고 발행 큐에 추가합니다.
 */

// 헤더 설정
header('Content-Type: application/json');

// 설정 및 공통 함수
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

// 로그 파일 설정
$logFile = LOGS_PATH . '/issue_start.log';

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
    
    // 트랜잭션 시작
    $db->beginTransaction();
    
    // 발행 계획 정보 조회
    $stmt = $db->prepare("
        SELECT ip.*, lp.product_code, lp.name AS product_name 
        FROM issue_plans ip
        JOIN lottery_products lp ON ip.product_id = lp.id
        WHERE ip.id = ?
    ");
    $stmt->execute([$planId]);
    $plan = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$plan) {
        $db->rollBack();
        $response['message'] = '발행 계획을 찾을 수 없습니다.';
        echo json_encode($response);
        exit;
    }
    
    // 상태 확인 - 준비 상태만 발행 가능
    if ($plan['status'] !== 'ready') {
        $db->rollBack();
        $response['message'] = '준비 상태의 계획만 발행할 수 있습니다.';
        echo json_encode($response);
        exit;
    }
    
    // 발행 큐에 이미 존재하는지 확인
    $stmt = $db->prepare("SELECT id FROM issue_queue WHERE plan_id = ? AND status IN ('pending', 'in_progress')");
    $stmt->execute([$planId]);
    if ($stmt->fetch()) {
        $db->rollBack();
        $response['message'] = '이미 발행 큐에 포함된 계획입니다.';
        echo json_encode($response);
        exit;
    }
    
    // 발행 진행 상태로 변경
    $stmt = $db->prepare("UPDATE issue_plans SET status = 'in_progress', updated_at = NOW() WHERE id = ?");
    $stmt->execute([$planId]);
    
    // 발행 작업 큐에 추가
    $stmt = $db->prepare("
        INSERT INTO issue_queue (plan_id, status, total_tickets, processed_tickets) 
        VALUES (?, 'pending', ?, 0)
    ");
    $stmt->execute([$planId, $plan['total_tickets']]);
    $queueId = $db->lastInsertId();
    
    // 발행 이력 추가
    $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1; // 기본값 설정
    $stmt = $db->prepare("
        INSERT INTO issue_history (plan_id, queue_id, issued_by, status, notes) 
        VALUES (?, ?, ?, 'started', '발행 작업이 시작되었습니다.')
    ");
    $stmt->execute([$planId, $queueId, $userId]);
    
    // 트랜잭션 커밋
    $db->commit();
    
    // 로그 기록
    $productInfo = $plan['product_name'] . ' (' . $plan['product_code'] . ')';
    $logMessage = "발행 시작: 계획 ID: $planId - 상품: $productInfo - 큐 ID: $queueId - 총 발행량: {$plan['total_tickets']} (사용자 ID: $userId)";
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - $logMessage\n", FILE_APPEND);
    log_activity("복권 발행 시작: $productInfo", 'issue_plans', $userId);
    
    $response['status'] = 'success';
    $response['message'] = '발행 작업이 시작되었습니다.';
    $response['data'] = [
        'plan_id' => $planId,
        'queue_id' => $queueId,
        'product_name' => $plan['product_name'],
        'total_tickets' => $plan['total_tickets']
    ];
} catch (Exception $e) {
    // 오류 발생 시 롤백
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    
    $response['message'] = '오류가 발생했습니다: ' . $e->getMessage();
    error_log("API 오류 (start_issue.php): " . $e->getMessage());
}

echo json_encode($response);
