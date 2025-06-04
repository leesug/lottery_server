<?php
/**
 * 기금 거래 관리 API
 * 
 * 이 API는 기금 거래의 승인, 거부, 취소 등 처리 기능을 제공합니다.
 */

// 세션 시작 및 필수 파일 포함
session_start();
require_once '../../includes/auth.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// 요청 확인
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(false, 'Invalid request method');
}

// API 키 또는 세션 인증 확인
$isAuthenticated = false;

if (isset($_SERVER['HTTP_X_API_KEY'])) {
    // API 키 인증 확인
    $apiKey = $_SERVER['HTTP_X_API_KEY'];
    if (verifyApiKey($apiKey, ['finance_api'])) {
        $isAuthenticated = true;
    }
} elseif (isLoggedIn()) {
    // 세션 인증 확인
    $isAuthenticated = true;
}

if (!$isAuthenticated) {
    sendJsonResponse(false, 'Authentication failed', 401);
}

// 요청 데이터 파싱
$requestData = json_decode(file_get_contents('php://input'), true);

// 액션 타입 확인
if (!isset($requestData['action']) || !isset($requestData['transaction_id'])) {
    sendJsonResponse(false, 'Missing required parameters: action and transaction_id');
}

$action = $requestData['action'];
$transactionId = intval($requestData['transaction_id']);

// 데이터베이스 연결
$conn = getDBConnection();

// 액션 타입에 따른 처리
try {
    switch ($action) {
        case 'approve':
            // 승인 권한 확인 (API 키 사용 시는 키 자체가 권한 체크)
            if (isLoggedIn() && !hasPermission('finance_funds_approval')) {
                throw new Exception('Permission denied: finance_funds_approval is required');
            }
            
            // 거래 승인 처리
            $result = approveTransaction($conn, $transactionId);
            sendJsonResponse(true, 'Transaction approved successfully', 200, ['transaction' => $result]);
            break;
            
        case 'cancel':
            // 취소 권한 확인
            if (isLoggedIn() && !hasPermission('finance_funds_approval')) {
                throw new Exception('Permission denied: finance_funds_approval is required');
            }
            
            // 거래 취소 처리
            $result = cancelTransaction($conn, $transactionId);
            sendJsonResponse(true, 'Transaction cancelled successfully', 200, ['transaction' => $result]);
            break;
            
        case 'get_details':
            // 거래 조회 권한 확인
            if (isLoggedIn() && !hasPermission('finance_funds_transactions')) {
                throw new Exception('Permission denied: finance_funds_transactions is required');
            }
            
            // 거래 상세 정보 조회
            $result = getTransactionDetails($conn, $transactionId);
            sendJsonResponse(true, 'Transaction details retrieved', 200, ['transaction' => $result]);
            break;
            
        default:
            sendJsonResponse(false, 'Invalid action type');
    }
} catch (Exception $e) {
    // 오류 로깅
    logError("Fund transaction API error: " . $e->getMessage());
    
    // 오류 응답
    sendJsonResponse(false, $e->getMessage());
}

/**
 * 기금 거래 승인 처리
 * 
 * @param mysqli $conn 데이터베이스 연결
 * @param int $transactionId 거래 ID
 * @return array 처리된 거래 정보
 * @throws Exception 처리 중 오류 발생시
 */
function approveTransaction($conn, $transactionId) {
    // 거래 정보 조회
    $transaction = getFundTransaction($conn, $transactionId);
    
    // 이미 처리된 거래인지 확인
    if ($transaction['status'] !== 'pending') {
        throw new Exception('Transaction is already processed: ' . $transaction['status']);
    }
    
    // 기금 정보 조회
    $fund = getFund($conn, $transaction['fund_id']);
    
    try {
        // 트랜잭션 시작
        $conn->begin_transaction();
        
        // 거래 상태 업데이트
        $userId = isLoggedIn() ? $_SESSION['user_id'] : null;
        $updateSql = "UPDATE fund_transactions SET 
                     status = 'completed',
                     approved_by = ?,
                     updated_at = NOW()
                     WHERE id = ?";
                     
        $updateStmt = $conn->prepare($updateSql);
        if (!$updateStmt) {
            throw new Exception("Database prepare error: " . $conn->error);
        }
        
        $updateStmt->bind_param("ii", $userId, $transactionId);
        
        $updateResult = $updateStmt->execute();
        if (!$updateResult) {
            throw new Exception("Error updating transaction status: " . $updateStmt->error);
        }
        
        // 기금 잔액 업데이트
        $newBalance = $fund['current_balance'];
        
        if ($transaction['transaction_type'] === 'allocation') {
            $newBalance += $transaction['amount'];
        } elseif ($transaction['transaction_type'] === 'withdrawal') {
            // 잔액 충분한지 확인
            if ($transaction['amount'] > $fund['current_balance']) {
                throw new Exception("Withdrawal amount exceeds current balance");
            }
            $newBalance -= $transaction['amount'];
        } elseif ($transaction['transaction_type'] === 'adjustment') {
            // 조정은 금액을 그대로 반영 (양수는 증가, 음수는 감소)
            $newBalance += $transaction['amount'];
        }
        
        // 잔액 업데이트
        $balanceUpdateSql = "UPDATE funds SET 
                            current_balance = ?,
                            updated_at = NOW()
                            WHERE id = ?";
                            
        $balanceUpdateStmt = $conn->prepare($balanceUpdateSql);
        if (!$balanceUpdateStmt) {
            throw new Exception("Database prepare error: " . $conn->error);
        }
        
        $balanceUpdateStmt->bind_param("di", $newBalance, $transaction['fund_id']);
        
        $balanceUpdateResult = $balanceUpdateStmt->execute();
        if (!$balanceUpdateResult) {
            throw new Exception("Error updating fund balance: " . $balanceUpdateStmt->error);
        }
        
        // 트랜잭션 커밋
        $conn->commit();
        
        // 로그 기록
        $logMessage = "Fund transaction approved: ID {$transactionId}, Fund: {$fund['fund_name']} ({$fund['fund_code']}), Amount: {$transaction['amount']} NPR";
        logActivity('finance', 'fund_transaction_approve', $logMessage);
        
        // 업데이트된 거래 정보 반환
        return getTransactionDetails($conn, $transactionId);
        
    } catch (Exception $e) {
        // 트랜잭션 롤백
        $conn->rollback();
        throw $e;
    }
}

/**
 * 기금 거래 취소 처리
 * 
 * @param mysqli $conn 데이터베이스 연결
 * @param int $transactionId 거래 ID
 * @return array 처리된 거래 정보
 * @throws Exception 처리 중 오류 발생시
 */
function cancelTransaction($conn, $transactionId) {
    // 거래 정보 조회
    $transaction = getFundTransaction($conn, $transactionId);
    
    // 이미 처리된 거래인지 확인
    if ($transaction['status'] !== 'pending') {
        throw new Exception('Transaction is already processed: ' . $transaction['status']);
    }
    
    // 기금 정보 조회
    $fund = getFund($conn, $transaction['fund_id']);
    
    try {
        // 트랜잭션 시작
        $conn->begin_transaction();
        
        // 거래 상태 업데이트
        $userId = isLoggedIn() ? $_SESSION['user_id'] : null;
        $updateSql = "UPDATE fund_transactions SET 
                     status = 'cancelled',
                     approved_by = ?,
                     updated_at = NOW()
                     WHERE id = ?";
                     
        $updateStmt = $conn->prepare($updateSql);
        if (!$updateStmt) {
            throw new Exception("Database prepare error: " . $conn->error);
        }
        
        $updateStmt->bind_param("ii", $userId, $transactionId);
        
        $updateResult = $updateStmt->execute();
        if (!$updateResult) {
            throw new Exception("Error updating transaction status: " . $updateStmt->error);
        }
        
        // 트랜잭션 커밋
        $conn->commit();
        
        // 로그 기록
        $logMessage = "Fund transaction cancelled: ID {$transactionId}, Fund: {$fund['fund_name']} ({$fund['fund_code']}), Amount: {$transaction['amount']} NPR";
        logActivity('finance', 'fund_transaction_cancel', $logMessage);
        
        // 업데이트된 거래 정보 반환
        return getTransactionDetails($conn, $transactionId);
        
    } catch (Exception $e) {
        // 트랜잭션 롤백
        $conn->rollback();
        throw $e;
    }
}

/**
 * 기금 거래 정보 조회
 * 
 * @param mysqli $conn 데이터베이스 연결
 * @param int $transactionId 거래 ID
 * @return array 거래 정보
 * @throws Exception 조회 중 오류 발생시
 */
function getFundTransaction($conn, $transactionId) {
    $sql = "SELECT * FROM fund_transactions WHERE id = ?";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Database prepare error: " . $conn->error);
    }
    
    $stmt->bind_param("i", $transactionId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Transaction not found: ID {$transactionId}");
    }
    
    return $result->fetch_assoc();
}

/**
 * 기금 정보 조회
 * 
 * @param mysqli $conn 데이터베이스 연결
 * @param int $fundId 기금 ID
 * @return array 기금 정보
 * @throws Exception 조회 중 오류 발생시
 */
function getFund($conn, $fundId) {
    $sql = "SELECT * FROM funds WHERE id = ?";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Database prepare error: " . $conn->error);
    }
    
    $stmt->bind_param("i", $fundId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Fund not found: ID {$fundId}");
    }
    
    return $result->fetch_assoc();
}

/**
 * 거래 상세 정보 조회
 * 
 * @param mysqli $conn 데이터베이스 연결
 * @param int $transactionId 거래 ID
 * @return array 상세 거래 정보
 * @throws Exception 조회 중 오류 발생시
 */
function getTransactionDetails($conn, $transactionId) {
    $sql = "SELECT ft.*, f.fund_name, f.fund_code, f.current_balance, f.fund_type,
            u1.username as created_by_username, u2.username as approved_by_username
            FROM fund_transactions ft
            JOIN funds f ON ft.fund_id = f.id
            LEFT JOIN users u1 ON ft.created_by = u1.id
            LEFT JOIN users u2 ON ft.approved_by = u2.id
            WHERE ft.id = ?";
            
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Database prepare error: " . $conn->error);
    }
    
    $stmt->bind_param("i", $transactionId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Transaction not found: ID {$transactionId}");
    }
    
    $transaction = $result->fetch_assoc();
    
    // 선택적으로 민감한 데이터 제거 또는 포맷팅
    return [
        'id' => $transaction['id'],
        'fund_id' => $transaction['fund_id'],
        'fund_name' => $transaction['fund_name'],
        'fund_code' => $transaction['fund_code'],
        'fund_type' => $transaction['fund_type'],
        'transaction_type' => $transaction['transaction_type'],
        'amount' => (float) $transaction['amount'],
        'transaction_date' => $transaction['transaction_date'],
        'description' => $transaction['description'],
        'reference_type' => $transaction['reference_type'],
        'reference_id' => $transaction['reference_id'],
        'status' => $transaction['status'],
        'notes' => $transaction['notes'],
        'created_by' => $transaction['created_by'],
        'created_by_username' => $transaction['created_by_username'],
        'approved_by' => $transaction['approved_by'],
        'approved_by_username' => $transaction['approved_by_username'],
        'created_at' => $transaction['created_at'],
        'updated_at' => $transaction['updated_at'],
        'current_fund_balance' => (float) $transaction['current_balance']
    ];
}

/**
 * API 키 검증
 * 
 * @param string $apiKey API 키
 * @param array $requiredScopes 필요한 스코프(권한) 배열
 * @return bool 검증 결과
 */
function verifyApiKey($apiKey, $requiredScopes = []) {
    // TODO: 실제 API 키 검증 로직 구현
    // 이 예시에서는 간단히 하드코딩된 키로 확인
    $validApiKeys = [
        'finance_system_key' => ['finance_api', 'finance_funds', 'finance_funds_approval'],
        'read_only_key' => ['finance_api']
    ];
    
    // API 키 존재 확인
    if (!array_key_exists($apiKey, $validApiKeys)) {
        return false;
    }
    
    // 스코프 확인
    $keyScopes = $validApiKeys[$apiKey];
    foreach ($requiredScopes as $scope) {
        if (!in_array($scope, $keyScopes)) {
            return false;
        }
    }
    
    return true;
}

/**
 * JSON 응답 전송
 * 
 * @param bool $success 성공 여부
 * @param string $message 메시지
 * @param int $statusCode HTTP 상태 코드
 * @param array $data 추가 데이터
 */
function sendJsonResponse($success, $message, $statusCode = 200, $data = []) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    
    $response = [
        'success' => $success,
        'message' => $message
    ];
    
    if (!empty($data)) {
        $response['data'] = $data;
    }
    
    echo json_encode($response);
    exit;
}
?>