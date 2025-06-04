<?php
/**
 * 복권 발행 취소 API
 * 
 * 진행 중인 발행 작업을 취소합니다.
 */

// 헤더 설정
header('Content-Type: application/json');

// 설정 및 공통 함수
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

// 로그 파일 설정
$logFile = LOGS_PATH . '/issue_cancel.log';

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
if (!isset($_POST['queue_id'])) {
    $response['message'] = '필수 파라미터가 누락되었습니다.';
    echo json_encode($response);
    exit;
}

$queueId = (int) sanitizeInput($_POST['queue_id']);

try {
    // 데이터베이스 연결
    $db = get_db_connection();
    
    // 트랜잭션 시작
    $db->beginTransaction();
    
    // 큐 정보 조회
    $stmt = $db->prepare("
        SELECT iq.*, ip.product_id, lp.product_code, lp.name AS product_name 
        FROM issue_queue iq
        JOIN issue_plans ip ON iq.plan_id = ip.id
        JOIN lottery_products lp ON ip.product_id = lp.id
        WHERE iq.id = ?
    ");
    $stmt->execute([$queueId]);
    $queue = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$queue) {
        $db->rollBack();
        $response['message'] = '발행 큐를 찾을 수 없습니다.';
        echo json_encode($response);
        exit;
    }
    
    // 상태 확인 - pending 또는 in_progress 상태만 취소 가능
    if (!in_array($queue['status'], ['pending', 'in_progress'])) {
        $db->rollBack();
        $response['message'] = '진행 중이거나 대기 중인 발행 작업만 취소할 수 있습니다.';
        echo json_encode($response);
        exit;
    }
    
    $planId = $queue['plan_id'];
    
    // 큐 상태 변경
    $stmt = $db->prepare("UPDATE issue_queue SET status = 'cancelled', updated_at = NOW() WHERE id = ?");
    $stmt->execute([$queueId]);
    
    // 발행 계획 상태 변경
    $stmt = $db->prepare("UPDATE issue_plans SET status = 'cancelled', updated_at = NOW() WHERE id = ?");
    $stmt->execute([$planId]);
    
    // 발행 이력 추가
    $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1; // 기본값 설정
    $stmt = $db->prepare("
        INSERT INTO issue_history (plan_id, queue_id, issued_by, status, notes) 
        VALUES (?, ?, ?, 'cancelled', '발행 작업이 취소되었습니다.')
    ");
    $stmt->execute([$planId, $queueId, $userId]);
    
    // 트랜잭션 커밋
    $db->commit();
    
    // 로그 기록
    $productInfo = $queue['product_name'] . ' (' . $queue['product_code'] . ')';
    $processedTickets = $queue['processed_tickets'];
    $totalTickets = $queue['total_tickets'];
    $progress = $totalTickets > 0 ? round(($processedTickets / $totalTickets) * 100, 1) : 0;
    
    $logMessage = "발행 취소: 계획 ID: $planId - 큐 ID: $queueId - 상품: $productInfo - 진행률: $progress% ($processedTickets/$totalTickets) (사용자 ID: $userId)";
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - $logMessage\n", FILE_APPEND);
    log_activity("복권 발행 취소: $productInfo (진행률: $progress%)", 'issue_plans', $userId);
    
    $response['status'] = 'success';
    $response['message'] = '발행 작업이 취소되었습니다.';
    $response['data'] = [
        'plan_id' => $planId,
        'queue_id' => $queueId,
        'product_name' => $queue['product_name'],
        'processed_tickets' => $processedTickets,
        'total_tickets' => $totalTickets
    ];
} catch (Exception $e) {
    // 오류 발생 시 롤백
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    
    $response['message'] = '오류가 발생했습니다: ' . $e->getMessage();
    error_log("API 오류 (cancel_issue.php): " . $e->getMessage());
}

echo json_encode($response);
