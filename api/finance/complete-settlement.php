<?php
/**
 * 재무 관리 API - 정산 완료 처리
 * 
 * 이 API는 정산을 완료 처리하는 기능을 제공합니다.
 */

// 세션 시작 및 필수 파일 포함
session_start();
require_once '../../includes/auth.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// API 응답 헤더 설정
header('Content-Type: application/json');

// 인증 확인
if (!isAuthenticated()) {
    echo json_encode([
        'success' => false,
        'message' => '인증되지 않은 요청입니다.'
    ]);
    exit;
}

// 필요한 권한 확인
if (!hasPermission('finance_settlements_complete')) {
    echo json_encode([
        'success' => false,
        'message' => '권한이 없습니다.'
    ]);
    exit;
}

// POST 요청 확인
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => '잘못된 요청 방식입니다.'
    ]);
    exit;
}

// 필수 파라미터 확인
if (!isset($_POST['settlement_id']) || !is_numeric($_POST['settlement_id'])) {
    echo json_encode([
        'success' => false,
        'message' => '정산 ID가 유효하지 않습니다.'
    ]);
    exit;
}

if (!isset($_POST['payment_reference']) || empty(trim($_POST['payment_reference']))) {
    echo json_encode([
        'success' => false,
        'message' => '지불 참조 번호는 필수입니다.'
    ]);
    exit;
}

if (!isset($_POST['settlement_date']) || empty(trim($_POST['settlement_date']))) {
    echo json_encode([
        'success' => false,
        'message' => '정산 완료일은 필수입니다.'
    ]);
    exit;
}

$settlementId = intval($_POST['settlement_id']);
$paymentReference = sanitizeInput($_POST['payment_reference']);
$settlementDate = sanitizeInput($_POST['settlement_date']);
$notes = isset($_POST['notes']) ? sanitizeInput($_POST['notes']) : null;
$userId = $_SESSION['user_id'];

// 데이터베이스 연결
$conn = getDBConnection();

try {
    // 트랜잭션 시작
    $conn->begin_transaction();
    
    // 정산 정보 조회
    $sql = "SELECT settlement_code, status, entity_type, entity_id, total_amount, net_amount, payment_method FROM settlements WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("데이터베이스 준비 오류: " . $conn->error);
    }
    
    $stmt->bind_param("i", $settlementId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("존재하지 않는 정산입니다.");
    }
    
    $settlement = $result->fetch_assoc();
    
    // 정산 상태 확인
    if ($settlement['status'] !== 'processing') {
        throw new Exception("완료 처리 가능한 상태의 정산이 아닙니다.");
    }
    
    // 정산 완료 처리 (상태를 'completed'로 변경)
    $updateSql = "UPDATE settlements SET 
                  status = 'completed', 
                  settlement_date = ?, 
                  payment_reference = ?, 
                  approved_by = ?, 
                  updated_at = NOW()";
    
    // 메모가 있으면 추가
    if ($notes) {
        $updateSql .= ", notes = CONCAT(IFNULL(notes, ''), '\n\n완료 메모: " . $conn->real_escape_string($notes) . "')";
    }
    
    $updateSql .= " WHERE id = ?";
    
    $updateStmt = $conn->prepare($updateSql);
    if (!$updateStmt) {
        throw new Exception("데이터베이스 준비 오류: " . $conn->error);
    }
    
    $updateStmt->bind_param("ssii", $settlementDate, $paymentReference, $userId, $settlementId);
    
    $updateResult = $updateStmt->execute();
    if (!$updateResult) {
        throw new Exception("정산 완료 처리 중 오류가 발생했습니다: " . $updateStmt->error);
    }
    
    // 정산 완료 시 재무 거래 자동 생성
    $transactionCode = 'TXN-' . date('Ymd') . '-' . $settlementId;
    
    // 정산 대상에 따른 거래 내용 설정
    $transactionDescription = "";
    switch ($settlement['entity_type']) {
        case 'store':
            $transactionDescription = "판매점 정산 지급 - 정산 코드: " . $settlement['settlement_code'];
            break;
        case 'vendor':
            $transactionDescription = "공급업체 정산 지급 - 정산 코드: " . $settlement['settlement_code'];
            break;
        case 'employee':
            $transactionDescription = "직원 정산 지급 - 정산 코드: " . $settlement['settlement_code'];
            break;
        case 'tax':
            $transactionDescription = "세금 납부 - 정산 코드: " . $settlement['settlement_code'];
            break;
        default:
            $transactionDescription = "정산 지급 - 정산 코드: " . $settlement['settlement_code'];
    }
    
    // 재무 거래 추가
    $transactionSql = "INSERT INTO financial_transactions (
                        transaction_code, 
                        transaction_type, 
                        amount, 
                        currency, 
                        transaction_date, 
                        description, 
                        reference_type, 
                        reference_id, 
                        payment_method, 
                        payment_details, 
                        status, 
                        created_by, 
                        approved_by
                    ) VALUES (?, 'expense', ?, 'NPR', ?, ?, 'settlement', ?, ?, ?, 'completed', ?, ?)";
    
    $transactionStmt = $conn->prepare($transactionSql);
    if (!$transactionStmt) {
        throw new Exception("데이터베이스 준비 오류: " . $conn->error);
    }
    
    $paymentDetails = "정산 ID: " . $settlementId . ", 지불 참조: " . $paymentReference;
    
    $transactionStmt->bind_param(
        "sdssssiii", 
        $transactionCode, 
        $settlement['net_amount'], 
        $settlementDate, 
        $transactionDescription, 
        $settlementId, 
        $settlement['payment_method'], 
        $paymentDetails, 
        $userId, 
        $userId
    );
    
    $transactionResult = $transactionStmt->execute();
    if (!$transactionResult) {
        throw new Exception("재무 거래 생성 중 오류가 발생했습니다: " . $transactionStmt->error);
    }
    
    // 로그 기록
    logActivity('finance', 'settlement_complete', "정산 완료 처리: {$settlement['settlement_code']}");
    
    // 트랜잭션 커밋
    $conn->commit();
    
    // 성공 응답
    echo json_encode([
        'success' => true,
        'message' => '정산이 성공적으로 완료 처리되었습니다.',
        'settlement_id' => $settlementId
    ]);
    
} catch (Exception $e) {
    // 트랜잭션 롤백
    $conn->rollback();
    
    // 오류 로깅
    logError("정산 완료 처리 오류: " . $e->getMessage());
    
    // 오류 응답
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    // 연결 종료
    if (isset($stmt)) $stmt->close();
    if (isset($updateStmt)) $updateStmt->close();
    if (isset($transactionStmt)) $transactionStmt->close();
    $conn->close();
}
?>