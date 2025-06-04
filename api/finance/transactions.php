<?php
/**
 * 재무 트랜잭션 API
 * 
 * 이 API는 재무 거래 정보를 가져오고 관리하는 기능을 제공합니다.
 */

require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';
require_once '../../includes/db.php';

// API 응답 형식 설정
header('Content-Type: application/json');

// 인증 확인
if (!AuthManager::isLoggedIn()) {
    http_response_code(API_UNAUTHORIZED);
    echo json_encode([
        'status' => 'error',
        'message' => '인증되지 않은 요청입니다.'
    ]);
    exit;
}

// 권한 확인 (재무 관리자 또는 관리자만 허용)
if (!AuthManager::hasPermission('admin') && !AuthManager::hasPermission('finance')) {
    http_response_code(API_FORBIDDEN);
    echo json_encode([
        'status' => 'error',
        'message' => '이 작업을 수행할 권한이 없습니다.'
    ]);
    exit;
}

// HTTP 메소드 확인
$method = $_SERVER['REQUEST_METHOD'];

// 요청 경로 분석
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$pathParts = explode('/', trim($path, '/'));
$transactionId = $pathParts[count($pathParts) - 1] ?? null;

switch ($method) {
    case 'GET':
        // 트랜잭션 목록 또는 단일 트랜잭션 정보 가져오기
        if (is_numeric($transactionId)) {
            // 단일 트랜잭션 정보 가져오기
            getTransaction($transactionId);
        } else {
            // 트랜잭션 목록 가져오기
            getTransactions();
        }
        break;
        
    case 'POST':
        // CSRF 토큰 검증
        if (!SecurityManager::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            http_response_code(API_FORBIDDEN);
            echo json_encode([
                'status' => 'error',
                'message' => '유효하지 않은 CSRF 토큰입니다.'
            ]);
            exit;
        }
        
        // 새 트랜잭션 생성
        createTransaction();
        break;
        
    case 'PUT':
        // 입력 데이터 가져오기
        parse_str(file_get_contents('php://input'), $putData);
        
        // CSRF 토큰 검증
        if (!SecurityManager::validateCsrfToken($putData['csrf_token'] ?? '')) {
            http_response_code(API_FORBIDDEN);
            echo json_encode([
                'status' => 'error',
                'message' => '유효하지 않은 CSRF 토큰입니다.'
            ]);
            exit;
        }
        
        // 트랜잭션 수정
        if (!is_numeric($transactionId)) {
            http_response_code(API_BAD_REQUEST);
            echo json_encode([
                'status' => 'error',
                'message' => '유효한 트랜잭션 ID가 제공되지 않았습니다.'
            ]);
            exit;
        }
        
        updateTransaction($transactionId, $putData);
        break;
        
    case 'DELETE':
        // 입력 데이터 가져오기
        parse_str(file_get_contents('php://input'), $deleteData);
        
        // CSRF 토큰 검증
        if (!SecurityManager::validateCsrfToken($deleteData['csrf_token'] ?? '')) {
            http_response_code(API_FORBIDDEN);
            echo json_encode([
                'status' => 'error',
                'message' => '유효하지 않은 CSRF 토큰입니다.'
            ]);
            exit;
        }
        
        // 트랜잭션 삭제
        if (!is_numeric($transactionId)) {
            http_response_code(API_BAD_REQUEST);
            echo json_encode([
                'status' => 'error',
                'message' => '유효한 트랜잭션 ID가 제공되지 않았습니다.'
            ]);
            exit;
        }
        
        deleteTransaction($transactionId);
        break;
        
    default:
        // 지원하지 않는 메소드
        http_response_code(API_BAD_REQUEST);
        echo json_encode([
            'status' => 'error',
            'message' => '지원하지 않는 HTTP 메소드입니다.'
        ]);
        exit;
}

/**
 * 트랜잭션 목록 가져오기
 */
function getTransactions() {
    // 페이지네이션 및 정렬 매개변수
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : ITEMS_PER_PAGE;
    $sort = isset($_GET['sort']) ? $_GET['sort'] : 'transaction_date';
    $order = isset($_GET['order']) && strtoupper($_GET['order']) === 'ASC' ? 'ASC' : 'DESC';
    
    // 필터 매개변수
    $type = isset($_GET['type']) ? $_GET['type'] : null;
    $startDate = isset($_GET['start_date']) ? $_GET['start_date'] : null;
    $endDate = isset($_GET['end_date']) ? $_GET['end_date'] : null;
    $status = isset($_GET['status']) ? $_GET['status'] : null;
    
    // 기본 쿼리
    $sql = "SELECT * FROM financial_transactions WHERE 1=1";
    $countSql = "SELECT COUNT(*) AS total FROM financial_transactions WHERE 1=1";
    $params = [];
    
    // 필터 적용
    if ($type) {
        $sql .= " AND transaction_type = ?";
        $countSql .= " AND transaction_type = ?";
        $params[] = ['type' => 's', 'value' => $type];
    }
    
    if ($startDate) {
        $sql .= " AND transaction_date >= ?";
        $countSql .= " AND transaction_date >= ?";
        $params[] = ['type' => 's', 'value' => $startDate . ' 00:00:00'];
    }
    
    if ($endDate) {
        $sql .= " AND transaction_date <= ?";
        $countSql .= " AND transaction_date <= ?";
        $params[] = ['type' => 's', 'value' => $endDate . ' 23:59:59'];
    }
    
    if ($status) {
        $sql .= " AND status = ?";
        $countSql .= " AND status = ?";
        $params[] = ['type' => 's', 'value' => $status];
    }
    
    // 정렬
    $sql .= " ORDER BY " . $sort . " " . $order;
    
    // 페이지네이션
    $offset = ($page - 1) * $limit;
    $sql .= " LIMIT ?, ?";
    $params[] = ['type' => 'i', 'value' => $offset];
    $params[] = ['type' => 'i', 'value' => $limit];
    
    // 총 레코드 수 가져오기
    $countResult = fetchOne($countSql, $params);
    $total = $countResult ? $countResult['total'] : 0;
    
    // 데이터 가져오기
    $transactions = fetchAll($sql, $params);
    
    // 페이지네이션 정보
    $totalPages = ceil($total / $limit);
    
    // 응답
    http_response_code(API_SUCCESS);
    echo json_encode([
        'status' => 'success',
        'data' => [
            'transactions' => $transactions,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'total_pages' => $totalPages
            ]
        ]
    ]);
}

/**
 * 단일 트랜잭션 정보 가져오기
 * 
 * @param int $id 트랜잭션 ID
 */
function getTransaction($id) {
    $sql = "SELECT * FROM financial_transactions WHERE id = ? LIMIT 1";
    $params = [
        ['type' => 'i', 'value' => $id]
    ];
    
    $transaction = fetchOne($sql, $params);
    
    if (!$transaction) {
        http_response_code(API_NOT_FOUND);
        echo json_encode([
            'status' => 'error',
            'message' => '해당 트랜잭션을 찾을 수 없습니다.'
        ]);
        exit;
    }
    
    http_response_code(API_SUCCESS);
    echo json_encode([
        'status' => 'success',
        'data' => $transaction
    ]);
}

/**
 * 새 트랜잭션 생성
 */
function createTransaction() {
    // 필수 필드 확인
    $requiredFields = ['transaction_type', 'amount', 'transaction_date', 'payment_method'];
    $missingFields = [];
    
    foreach ($requiredFields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            $missingFields[] = $field;
        }
    }
    
    if (!empty($missingFields)) {
        http_response_code(API_BAD_REQUEST);
        echo json_encode([
            'status' => 'error',
            'message' => '다음 필수 필드가 누락되었습니다: ' . implode(', ', $missingFields)
        ]);
        exit;
    }
    
    // 필드 준비
    $transactionCode = generateRandomCode('TRX', 12);
    $transactionType = SecurityManager::sanitizeInput($_POST['transaction_type']);
    $amount = (float)$_POST['amount'];
    $currency = SecurityManager::sanitizeInput($_POST['currency'] ?? 'NPR');
    $transactionDate = SecurityManager::sanitizeInput($_POST['transaction_date']);
    $description = SecurityManager::sanitizeInput($_POST['description'] ?? '');
    $categoryId = isset($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    $referenceType = SecurityManager::sanitizeInput($_POST['reference_type'] ?? '');
    $referenceId = SecurityManager::sanitizeInput($_POST['reference_id'] ?? '');
    $paymentMethod = SecurityManager::sanitizeInput($_POST['payment_method']);
    $paymentDetails = SecurityManager::sanitizeInput($_POST['payment_details'] ?? '');
    $status = SecurityManager::sanitizeInput($_POST['status'] ?? 'pending');
    $notes = SecurityManager::sanitizeInput($_POST['notes'] ?? '');
    $createdBy = SessionManager::getUserId();
    
    // SQL 쿼리
    $sql = "INSERT INTO financial_transactions (
                transaction_code, transaction_type, amount, currency, transaction_date, 
                description, category_id, reference_type, reference_id, payment_method, 
                payment_details, status, created_by, notes
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $params = [
        ['type' => 's', 'value' => $transactionCode],
        ['type' => 's', 'value' => $transactionType],
        ['type' => 'd', 'value' => $amount],
        ['type' => 's', 'value' => $currency],
        ['type' => 's', 'value' => $transactionDate],
        ['type' => 's', 'value' => $description],
        ['type' => 'i', 'value' => $categoryId],
        ['type' => 's', 'value' => $referenceType],
        ['type' => 's', 'value' => $referenceId],
        ['type' => 's', 'value' => $paymentMethod],
        ['type' => 's', 'value' => $paymentDetails],
        ['type' => 's', 'value' => $status],
        ['type' => 'i', 'value' => $createdBy],
        ['type' => 's', 'value' => $notes]
    ];
    
    $insertId = insert($sql, $params);
    
    if (!$insertId) {
        http_response_code(API_SERVER_ERROR);
        echo json_encode([
            'status' => 'error',
            'message' => '트랜잭션 생성에 실패했습니다.'
        ]);
        exit;
    }
    
    // 생성된 트랜잭션 정보 가져오기
    $newTransaction = fetchOne(
        "SELECT * FROM financial_transactions WHERE id = ? LIMIT 1",
        [['type' => 'i', 'value' => $insertId]]
    );
    
    http_response_code(API_CREATED);
    echo json_encode([
        'status' => 'success',
        'message' => '트랜잭션이 성공적으로 생성되었습니다.',
        'data' => $newTransaction
    ]);
}

/**
 * 트랜잭션 수정
 * 
 * @param int $id 트랜잭션 ID
 * @param array $data 수정할 데이터
 */
function updateTransaction($id, $data) {
    // 필드 준비
    $updateFields = [];
    $params = [];
    
    // 수정 가능한 필드 목록
    $allowedFields = [
        'transaction_type' => 's',
        'amount' => 'd',
        'currency' => 's',
        'transaction_date' => 's',
        'description' => 's',
        'category_id' => 'i',
        'reference_type' => 's',
        'reference_id' => 's',
        'payment_method' => 's',
        'payment_details' => 's',
        'status' => 's',
        'approved_by' => 'i',
        'notes' => 's'
    ];
    
    // 수정할 필드 및 값 선택
    foreach ($allowedFields as $field => $type) {
        if (isset($data[$field])) {
            $updateFields[] = "$field = ?";
            $params[] = ['type' => $type, 'value' => SecurityManager::sanitizeInput($data[$field])];
        }
    }
    
    // 수정할 필드가 없는 경우
    if (empty($updateFields)) {
        http_response_code(API_BAD_REQUEST);
        echo json_encode([
            'status' => 'error',
            'message' => '수정할 데이터가 제공되지 않았습니다.'
        ]);
        exit;
    }
    
    // 트랜잭션 존재 여부 확인
    $checkSql = "SELECT * FROM financial_transactions WHERE id = ? LIMIT 1";
    $checkParams = [
        ['type' => 'i', 'value' => $id]
    ];
    
    $transaction = fetchOne($checkSql, $checkParams);
    
    if (!$transaction) {
        http_response_code(API_NOT_FOUND);
        echo json_encode([
            'status' => 'error',
            'message' => '해당 트랜잭션을 찾을 수 없습니다.'
        ]);
        exit;
    }
    
    // SQL 쿼리
    $sql = "UPDATE financial_transactions SET " . implode(', ', $updateFields) . " WHERE id = ?";
    $params[] = ['type' => 'i', 'value' => $id];
    
    $result = execute($sql, $params);
    
    if (!$result) {
        http_response_code(API_SERVER_ERROR);
        echo json_encode([
            'status' => 'error',
            'message' => '트랜잭션 수정에 실패했습니다.'
        ]);
        exit;
    }
    
    // 수정된 트랜잭션 정보 가져오기
    $updatedTransaction = fetchOne($checkSql, $checkParams);
    
    http_response_code(API_SUCCESS);
    echo json_encode([
        'status' => 'success',
        'message' => '트랜잭션이 성공적으로 수정되었습니다.',
        'data' => $updatedTransaction
    ]);
}

/**
 * 트랜잭션 삭제
 * 
 * @param int $id 트랜잭션 ID
 */
function deleteTransaction($id) {
    // 트랜잭션 존재 여부 확인
    $checkSql = "SELECT * FROM financial_transactions WHERE id = ? LIMIT 1";
    $checkParams = [
        ['type' => 'i', 'value' => $id]
    ];
    
    $transaction = fetchOne($checkSql, $checkParams);
    
    if (!$transaction) {
        http_response_code(API_NOT_FOUND);
        echo json_encode([
            'status' => 'error',
            'message' => '해당 트랜잭션을 찾을 수 없습니다.'
        ]);
        exit;
    }
    
    // SQL 쿼리
    $sql = "DELETE FROM financial_transactions WHERE id = ?";
    $params = [
        ['type' => 'i', 'value' => $id]
    ];
    
    $result = execute($sql, $params);
    
    if (!$result) {
        http_response_code(API_SERVER_ERROR);
        echo json_encode([
            'status' => 'error',
            'message' => '트랜잭션 삭제에 실패했습니다.'
        ]);
        exit;
    }
    
    http_response_code(API_SUCCESS);
    echo json_encode([
        'status' => 'success',
        'message' => '트랜잭션이 성공적으로 삭제되었습니다.'
    ]);
}

/**
 * 랜덤 코드 생성
 * 
 * @param string $prefix 접두어
 * @param int $length 코드 길이
 * @return string 생성된 코드
 */
function generateRandomCode($prefix, $length = 8) {
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $code = $prefix;
    
    for ($i = 0; $i < $length - strlen($prefix); $i++) {
        $code .= $characters[rand(0, strlen($characters) - 1)];
    }
    
    return $code;
}
