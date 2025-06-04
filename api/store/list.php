<?php
/**
 * 판매점 목록 API
 * 
 * 판매점 목록을 JSON 형식으로 제공하는 API
 * 
 * @param int region_id 지역 ID (선택)
 * @param string status 판매점 상태 (선택, 기본값: active)
 * @return JSON 판매점 목록 데이터
 */

// 세션 시작 및 로그인 체크
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    http_response_code(401);
    exit(json_encode([
        'status' => 'error',
        'message' => '인증되지 않은 접근입니다.'
    ]));
}

// 필요한 파일 포함
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// 입력 파라미터 검증
$region_id = isset($_GET['region_id']) ? intval($_GET['region_id']) : 0;
$status = isset($_GET['status']) ? $_GET['status'] : 'active';

// 허용된 상태 값만 사용
$allowed_statuses = ['active', 'inactive', 'suspended', 'all'];
if (!in_array($status, $allowed_statuses)) {
    $status = 'active';
}

// 데이터베이스 연결
$conn = getDBConnection();

// 판매점 목록 가져오기
function getStoresList($conn, $region_id = 0, $status = 'active') {
    $query = "
        SELECT 
            s.id,
            s.store_code,
            s.name,
            s.location,
            s.contact_name,
            s.contact_phone,
            s.status,
            r.name as region_name
        FROM 
            stores s
        LEFT JOIN 
            regions r ON s.region_id = r.id
        WHERE 1=1
    ";
    
    $params = [];
    
    if ($region_id > 0) {
        $query .= " AND s.region_id = ?";
        $params[] = $region_id;
    }
    
    if ($status !== 'all') {
        $query .= " AND s.status = ?";
        $params[] = $status;
    }
    
    $query .= " ORDER BY s.region_id, s.name";
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// 데이터 가져오기
$stores = getStoresList($conn, $region_id, $status);

// JSON 응답 출력
header('Content-Type: application/json; charset=UTF-8');
echo json_encode([
    'status' => 'success',
    'message' => count($stores) . '개의 판매점이 검색되었습니다.',
    'data' => $stores
]);
