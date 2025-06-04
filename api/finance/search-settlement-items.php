<?php
/**
 * 재무 관리 API - 정산 항목 검색
 * 
 * 이 API는 정산 생성 시 항목을 검색하는 기능을 제공합니다.
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
if (!hasPermission('finance_settlements_add')) {
    echo json_encode([
        'success' => false,
        'message' => '권한이 없습니다.'
    ]);
    exit;
}

// GET 요청 확인
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode([
        'success' => false,
        'message' => '잘못된 요청 방식입니다.'
    ]);
    exit;
}

// 필수 파라미터 확인
if (!isset($_GET['type']) || empty($_GET['type'])) {
    echo json_encode([
        'success' => false,
        'message' => '항목 유형이 지정되지 않았습니다.'
    ]);
    exit;
}

$type = sanitizeInput($_GET['type']);
$query = isset($_GET['query']) ? sanitizeInput($_GET['query']) : '';
$entityId = isset($_GET['entity_id']) ? intval($_GET['entity_id']) : 0;
$startDate = isset($_GET['start_date']) ? sanitizeInput($_GET['start_date']) : '';
$endDate = isset($_GET['end_date']) ? sanitizeInput($_GET['end_date']) : '';

// 데이터베이스 연결
$conn = getDBConnection();

try {
    $results = [];
    
    // 항목 유형에 따른 검색
    switch ($type) {
        case 'sale':
            // 판매 항목 검색
            $sql = "SELECT s.id, s.ticket_number as description, DATE_FORMAT(s.sale_date, '%Y-%m-%d') as date, s.total_amount as amount 
                    FROM sales s 
                    WHERE 1=1";
            
            // 판매점 필터
            if ($entityId > 0) {
                $sql .= " AND s.store_id = ?";
            }
            
            // 날짜 범위 필터
            if (!empty($startDate)) {
                $sql .= " AND s.sale_date >= ?";
            }
            
            if (!empty($endDate)) {
                $sql .= " AND s.sale_date <= ?";
            }
            
            // 검색어 필터
            if (!empty($query)) {
                $sql .= " AND (s.ticket_number LIKE ? OR s.customer_name LIKE ?)";
            }
            
            $sql .= " ORDER BY s.sale_date DESC LIMIT 20";
            
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("데이터베이스 준비 오류: " . $conn->error);
            }
            
            // 바인드 파라미터 배열
            $bindParams = [];
            $bindTypes = "";
            
            // 판매점 필터
            if ($entityId > 0) {
                $bindTypes .= "i";
                $bindParams[] = $entityId;
            }
            
            // 날짜 범위 필터
            if (!empty($startDate)) {
                $bindTypes .= "s";
                $bindParams[] = $startDate . " 00:00:00";
            }
            
            if (!empty($endDate)) {
                $bindTypes .= "s";
                $bindParams[] = $endDate . " 23:59:59";
            }
            
            // 검색어 필터
            if (!empty($query)) {
                $bindTypes .= "ss";
                $searchPattern = "%{$query}%";
                $bindParams[] = $searchPattern;
                $bindParams[] = $searchPattern;
            }
            
            // 바인드 파라미터 적용
            if (!empty($bindParams)) {
                $bindParamsRef = [];
                $bindParamsRef[] = &$bindTypes;
                
                foreach ($bindParams as $key => $value) {
                    $bindParamsRef[] = &$bindParams[$key];
                }
                
                call_user_func_array([$stmt, 'bind_param'], $bindParamsRef);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $results[] = [
                    'id' => $row['id'],
                    'description' => '티켓 번호: ' . $row['description'],
                    'date' => $row['date'],
                    'amount' => floatval($row['amount'])
                ];
            }
            break;
            
        case 'prize':
            // 당첨금 항목 검색
            $sql = "SELECT p.id, CONCAT('당첨자: ', p.winner_name) as description, DATE_FORMAT(p.claim_date, '%Y-%m-%d') as date, p.amount 
                    FROM prize_claims p 
                    WHERE 1=1";
            
            // 판매점 필터
            if ($entityId > 0) {
                $sql .= " AND p.store_id = ?";
            }
            
            // 날짜 범위 필터
            if (!empty($startDate)) {
                $sql .= " AND p.claim_date >= ?";
            }
            
            if (!empty($endDate)) {
                $sql .= " AND p.claim_date <= ?";
            }
            
            // 검색어 필터
            if (!empty($query)) {
                $sql .= " AND (p.winner_name LIKE ? OR p.ticket_number LIKE ?)";
            }
            
            $sql .= " ORDER BY p.claim_date DESC LIMIT 20";
            
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("데이터베이스 준비 오류: " . $conn->error);
            }
            
            // 바인드 파라미터 배열
            $bindParams = [];
            $bindTypes = "";
            
            // 판매점 필터
            if ($entityId > 0) {
                $bindTypes .= "i";
                $bindParams[] = $entityId;
            }
            
            // 날짜 범위 필터
            if (!empty($startDate)) {
                $bindTypes .= "s";
                $bindParams[] = $startDate . " 00:00:00";
            }
            
            if (!empty($endDate)) {
                $bindTypes .= "s";
                $bindParams[] = $endDate . " 23:59:59";
            }
            
            // 검색어 필터
            if (!empty($query)) {
                $bindTypes .= "ss";
                $searchPattern = "%{$query}%";
                $bindParams[] = $searchPattern;
                $bindParams[] = $searchPattern;
            }
            
            // 바인드 파라미터 적용
            if (!empty($bindParams)) {
                $bindParamsRef = [];
                $bindParamsRef[] = &$bindTypes;
                
                foreach ($bindParams as $key => $value) {
                    $bindParamsRef[] = &$bindParams[$key];
                }
                
                call_user_func_array([$stmt, 'bind_param'], $bindParamsRef);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $results[] = [
                    'id' => $row['id'],
                    'description' => $row['description'],
                    'date' => $row['date'],
                    'amount' => floatval($row['amount'])
                ];
            }
            break;
            
        case 'commission':
            // 수수료 항목 검색
            $sql = "SELECT c.id, CONCAT('판매점 수수료: ', DATE_FORMAT(c.period_start, '%Y-%m-%d'), ' ~ ', DATE_FORMAT(c.period_end, '%Y-%m-%d')) as description, 
                    DATE_FORMAT(c.calculation_date, '%Y-%m-%d') as date, c.commission_amount as amount 
                    FROM store_commissions c 
                    WHERE 1=1";
            
            // 판매점 필터
            if ($entityId > 0) {
                $sql .= " AND c.store_id = ?";
            }
            
            // 날짜 범위 필터
            if (!empty($startDate)) {
                $sql .= " AND c.period_start >= ?";
            }
            
            if (!empty($endDate)) {
                $sql .= " AND c.period_end <= ?";
            }
            
            $sql .= " ORDER BY c.calculation_date DESC LIMIT 20";
            
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("데이터베이스 준비 오류: " . $conn->error);
            }
            
            // 바인드 파라미터 배열
            $bindParams = [];
            $bindTypes = "";
            
            // 판매점 필터
            if ($entityId > 0) {
                $bindTypes .= "i";
                $bindParams[] = $entityId;
            }
            
            // 날짜 범위 필터
            if (!empty($startDate)) {
                $bindTypes .= "s";
                $bindParams[] = $startDate;
            }
            
            if (!empty($endDate)) {
                $bindTypes .= "s";
                $bindParams[] = $endDate;
            }
            
            // 바인드 파라미터 적용
            if (!empty($bindParams)) {
                $bindParamsRef = [];
                $bindParamsRef[] = &$bindTypes;
                
                foreach ($bindParams as $key => $value) {
                    $bindParamsRef[] = &$bindParams[$key];
                }
                
                call_user_func_array([$stmt, 'bind_param'], $bindParamsRef);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $results[] = [
                    'id' => $row['id'],
                    'description' => $row['description'],
                    'date' => $row['date'],
                    'amount' => floatval($row['amount'])
                ];
            }
            break;
            
        case 'deduction':
            // 공제 항목 검색 (예시로 간단하게 구현)
            // 실제로는 공제 항목 테이블이 있어야 함
            $results = [
                [
                    'id' => 'D-001',
                    'description' => '공제 항목 예시 1',
                    'date' => date('Y-m-d'),
                    'amount' => 500.00
                ],
                [
                    'id' => 'D-002',
                    'description' => '공제 항목 예시 2',
                    'date' => date('Y-m-d'),
                    'amount' => 1000.00
                ]
            ];
            break;
            
        case 'tax':
            // 세금 항목 검색 (예시로 간단하게 구현)
            // 실제로는 세금 항목 테이블이 있어야 함
            $results = [
                [
                    'id' => 'T-001',
                    'description' => '부가가치세',
                    'date' => date('Y-m-d'),
                    'amount' => 1500.00
                ],
                [
                    'id' => 'T-002',
                    'description' => '소득세',
                    'date' => date('Y-m-d'),
                    'amount' => 2000.00
                ]
            ];
            break;
            
        case 'other':
            // 기타 항목 검색 (예시로 간단하게 구현)
            $results = [
                [
                    'id' => 'O-001',
                    'description' => '기타 항목 예시 1',
                    'date' => date('Y-m-d'),
                    'amount' => 800.00
                ],
                [
                    'id' => 'O-002',
                    'description' => '기타 항목 예시 2',
                    'date' => date('Y-m-d'),
                    'amount' => 1200.00
                ]
            ];
            break;
            
        default:
            throw new Exception("지원하지 않는 항목 유형입니다.");
    }
    
    // 성공 응답
    echo json_encode([
        'success' => true,
        'message' => '검색 결과를 찾았습니다.',
        'type' => $type,
        'query' => $query,
        'data' => $results
    ]);
    
} catch (Exception $e) {
    // 오류 로깅
    logError("정산 항목 검색 오류: " . $e->getMessage());
    
    // 오류 응답
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    // 연결 종료
    if (isset($stmt)) $stmt->close();
    $conn->close();
}
?>