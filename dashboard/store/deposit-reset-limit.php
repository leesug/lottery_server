<?php
// 판매한도 리셋 처리
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// 로그인 체크
checkLogin();

// JSON 응답 헤더
header('Content-Type: application/json');

// POST 요청만 허용
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// JSON 데이터 파싱
$input = json_decode(file_get_contents('php://input'), true);
$store_id = isset($input['store_id']) ? intval($input['store_id']) : 0;

if ($store_id <= 0) {
    echo json_encode(['success' => false, 'message' => '유효하지 않은 판매점 ID입니다.']);
    exit;
}

try {
    $conn = get_db_connection();
    $conn->beginTransaction();
    
    // 현재 예치금 정보 조회
    $query = "
        SELECT sd.*, s.store_name 
        FROM store_deposits sd
        INNER JOIN stores s ON sd.store_id = s.id
        WHERE sd.store_id = ?
    ";
    $stmt = $conn->prepare($query);
    $stmt->execute([$store_id]);
    $deposit = $stmt->fetch();
    
    if (!$deposit) {
        throw new Exception('예치금 정보를 찾을 수 없습니다.');
    }
    
    // 판매한도가 100% 도달했는지 확인
    if ($deposit['usage_percentage'] < 100) {
        throw new Exception('판매한도가 100%에 도달하지 않았습니다.');
    }
    
    // 사용한도 리셋
    $updateQuery = "
        UPDATE store_deposits 
        SET used_limit = 0,
            status = 'active'
        WHERE store_id = ?
    ";
    $stmt = $conn->prepare($updateQuery);
    $stmt->execute([$store_id]);
    
    // 리셋 거래 내역 저장
    $transQuery = "
        INSERT INTO deposit_transactions (
            store_id, transaction_type, deposit_type, amount,
            balance_before, balance_after, notes,
            status, created_by
        ) VALUES (?, 'adjustment', 'sales', ?, ?, ?, ?, 'completed', ?)
    ";
    $stmt = $conn->prepare($transQuery);
    $stmt->execute([
        $store_id,
        $deposit['used_limit'],  // 리셋된 금액
        $deposit['total_deposit'],
        $deposit['total_deposit'],
        '판매한도 리셋 - 용지 교체로 인한 리셋',
        $_SESSION['user_id']
    ]);
    
    // 한도 변경 이력 저장
    $historyQuery = "
        INSERT INTO deposit_limit_history (
            store_id, change_type, old_limit, new_limit,
            reason, changed_by
        ) VALUES (?, 'manual_adjustment', ?, ?, ?, ?)
    ";
    $stmt = $conn->prepare($historyQuery);
    $stmt->execute([
        $store_id,
        $deposit['sales_limit'],
        $deposit['sales_limit'],
        '판매한도 100% 도달로 인한 리셋',
        $_SESSION['user_id']
    ]);
    
    // 알림 상태 초기화
    $alertQuery = "
        UPDATE sales_limit_alerts 
        SET is_notified = FALSE,
            notified_at = NULL,
            acknowledged = FALSE,
            acknowledged_by = NULL,
            acknowledged_at = NULL
        WHERE store_id = ?
    ";
    $stmt = $conn->prepare($alertQuery);
    $stmt->execute([$store_id]);
    
    $conn->commit();
    
    // 활동 로그 기록
    logActivity('deposit_reset', [
        'store_id' => $store_id,
        'store_name' => $deposit['store_name'],
        'reset_amount' => $deposit['used_limit'],
        'user_id' => $_SESSION['user_id']
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => '판매한도가 성공적으로 리셋되었습니다.',
        'data' => [
            'store_id' => $store_id,
            'reset_amount' => $deposit['used_limit'],
            'new_used_limit' => 0
        ]
    ]);
    
} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollBack();
    }
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
