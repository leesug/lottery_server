<?php
/**
 * 재무 관리 API - 참조 항목 검색
 * 
 * 이 API는 거래 추가/편집 시 참조할 항목을 검색하는 기능을 제공합니다.
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
if (!hasPermission('finance_transactions_view')) {
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
        'message' => '참조 유형이 지정되지 않았습니다.'
    ]);
    exit;
}

$type = sanitizeInput($_GET['type']);
$query = isset($_GET['query']) ? sanitizeInput($_GET['query']) : '';

// 데이터베이스 연결
$conn = getDBConnection();

try {
    $results = [];
    
    // 참조 유형에 따른 검색
    switch ($type) {
        case 'sale':
            // 판매 데이터 검색
            $sql = "SELECT s.id, s.ticket_number as description, s.sale_date as date, s.total_amount as amount 
                    FROM sales s
                    WHERE 1=1";
            
            // 검색어가 있는 경우 조건 추가
            if (!empty($query)) {
                $sql .= " AND (s.ticket_number LIKE ? OR s.customer_name LIKE ?)";
                $searchPattern = "%{$query}%";
            }
            
            $sql .= " ORDER BY s.sale_date DESC LIMIT 20";
            
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("데이터베이스 준비 오류: " . $conn->error);
            }
            
            if (!empty($query)) {
                $stmt->bind_param("ss", $searchPattern, $searchPattern);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $results[] = [
                    'id' => $row['id'],
                    'description' => '티켓 번호: ' . $row['description'],
                    'date' => date('Y-m-d H:i', strtotime($row['date'])),
                    'amount' => floatval($row['amount'])
                ];
            }
            break;
            
        case 'prize':
            // 당첨금 데이터 검색
            $sql = "SELECT p.id, CONCAT('당첨자: ', p.winner_name) as description, p.claim_date as date, p.amount as amount 
                    FROM prize_claims p
                    WHERE 1=1";
            
            // 검색어가 있는 경우 조건 추가
            if (!empty($query)) {
                $sql .= " AND (p.winner_name LIKE ? OR p.ticket_number LIKE ?)";
                $searchPattern = "%{$query}%";
            }
            
            $sql .= " ORDER BY p.claim_date DESC LIMIT 20";
            
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("데이터베이스 준비 오류: " . $conn->error);
            }
            
            if (!empty($query)) {
                $stmt->bind_param("ss", $searchPattern, $searchPattern);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $results[] = [
                    'id' => $row['id'],
                    'description' => $row['description'],
                    'date' => date('Y-m-d H:i', strtotime($row['date'])),
                    'amount' => floatval($row['amount'])
                ];
            }
            break;
            
        case 'refund':
            // 환불 데이터 검색
            $sql = "SELECT r.id, CONCAT('티켓: ', r.ticket_number) as description, r.refund_date as date, r.amount as amount 
                    FROM refunds r
                    WHERE 1=1";
            
            // 검색어가 있는 경우 조건 추가
            if (!empty($query)) {
                $sql .= " AND (r.ticket_number LIKE ? OR r.customer_name LIKE ?)";
                $searchPattern = "%{$query}%";
            }
            
            $sql .= " ORDER BY r.refund_date DESC LIMIT 20";
            
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("데이터베이스 준비 오류: " . $conn->error);
            }
            
            if (!empty($query)) {
                $stmt->bind_param("ss", $searchPattern, $searchPattern);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $results[] = [
                    'id' => $row['id'],
                    'description' => $row['description'],
                    'date' => date('Y-m-d H:i', strtotime($row['date'])),
                    'amount' => floatval($row['amount'])
                ];
            }
            break;
            
        case 'commission':
            // 판매점 수수료 데이터 검색
            $sql = "SELECT c.id, CONCAT('판매점: ', s.store_name) as description, c.settlement_date as date, c.commission_amount as amount 
                    FROM store_commissions c
                    JOIN stores s ON c.store_id = s.id
                    WHERE 1=1";
            
            // 검색어가 있는 경우 조건 추가
            if (!empty($query)) {
                $sql .= " AND (s.store_name LIKE ? OR s.store_code LIKE ?)";
                $searchPattern = "%{$query}%";
            }
            
            $sql .= " ORDER BY c.settlement_date DESC LIMIT 20";
            
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("데이터베이스 준비 오류: " . $conn->error);
            }
            
            if (!empty($query)) {
                $stmt->bind_param("ss", $searchPattern, $searchPattern);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $results[] = [
                    'id' => $row['id'],
                    'description' => $row['description'],
                    'date' => date('Y-m-d H:i', strtotime($row['date'])),
                    'amount' => floatval($row['amount'])
                ];
            }
            break;
            
        case 'expense':
            // 지출 데이터 검색 (예: 운영 비용)
            $sql = "SELECT e.id, e.description, e.expense_date as date, e.amount 
                    FROM operational_expenses e
                    WHERE 1=1";
            
            // 검색어가 있는 경우 조건 추가
            if (!empty($query)) {
                $sql .= " AND (e.description LIKE ? OR e.category LIKE ?)";
                $searchPattern = "%{$query}%";
            }
            
            $sql .= " ORDER BY e.expense_date DESC LIMIT 20";
            
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("데이터베이스 준비 오류: " . $conn->error);
            }
            
            if (!empty($query)) {
                $stmt->bind_param("ss", $searchPattern, $searchPattern);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $results[] = [
                    'id' => $row['id'],
                    'description' => $row['description'],
                    'date' => date('Y-m-d H:i', strtotime($row['date'])),
                    'amount' => floatval($row['amount'])
                ];
            }
            break;
            
        case 'other':
            // 기타 거래 참조 데이터 검색 - 예시로 간단하게 구현
            // 실제로는 다른 항목들과 유사하게 데이터베이스에서 검색해야 함
            $results = [
                [
                    'id' => 'OTH-'.date('Ymd').'-001',
                    'description' => '기타 거래 예시 1',
                    'date' => date('Y-m-d H:i'),
                    'amount' => 1000.00
                ],
                [
                    'id' => 'OTH-'.date('Ymd').'-002',
                    'description' => '기타 거래 예시 2',
                    'date' => date('Y-m-d H:i'),
                    'amount' => 2000.00
                ]
            ];
            break;
            
        default:
            throw new Exception("지원하지 않는 참조 유형입니다.");
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
    logError("참조 검색 오류: " . $e->getMessage());
    
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