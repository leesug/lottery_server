<?php
/**
 * 외부 접속 감시 로그 API
 * 
 * 이 API는 외부 접속 감시 로그와 관련된 기능을 제공합니다.
 * - 로그 생성
 * - 로그 조회
 * - 로그 상세 정보 조회
 * 
 * @version 1.0
 * @author 로또 시스템 개발팀
 */

// 공통 파일 포함
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// CORS 헤더 설정
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization');

// OPTIONS 요청 처리
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 세션 시작
session_start();

// API 키 검증 (내부 시스템에서의 호출을 위한 인증)
$api_key = isset($_SERVER['HTTP_X_API_KEY']) ? $_SERVER['HTTP_X_API_KEY'] : '';
$is_internal_call = $api_key === API_KEY;

// 로그인 확인 (웹 인터페이스에서의 호출을 위한 인증)
$is_logged_in = isset($_SESSION['user_id']);
$is_admin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;

// 인증 검증
if (!$is_internal_call && !($is_logged_in && $is_admin)) {
    echo json_encode([
        'success' => false,
        'message' => '인증되지 않은 접근입니다.',
        'code' => 'UNAUTHORIZED'
    ]);
    exit;
}

// 데이터베이스 연결
$db = getDBConnection();

// 요청 처리
$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'create':
        createLog($db);
        break;
    
    case 'list':
        listLogs($db);
        break;
    
    case 'get_details':
        getLogDetails($db);
        break;
    
    case 'get_statistics':
        getStatistics($db);
        break;
    
    default:
        echo json_encode([
            'success' => false,
            'message' => '잘못된 액션입니다.',
            'code' => 'INVALID_ACTION'
        ]);
        break;
}

/**
 * 새 로그 생성
 * 
 * @param PDO $db 데이터베이스 연결 객체
 */
function createLog($db) {
    // POST 요청 확인
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode([
            'success' => false,
            'message' => '잘못된 요청 방식입니다.',
            'code' => 'INVALID_METHOD'
        ]);
        return;
    }
    
    // JSON 데이터 파싱
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        echo json_encode([
            'success' => false,
            'message' => '잘못된 데이터 형식입니다.',
            'code' => 'INVALID_DATA'
        ]);
        return;
    }
    
    // 필수 필드 검증
    $required_fields = ['entity_type', 'entity_id', 'activity_type', 'description'];
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            echo json_encode([
                'success' => false,
                'message' => "필수 필드가 누락되었습니다: {$field}",
                'code' => 'MISSING_FIELD'
            ]);
            return;
        }
    }
    
    // 엔터티 유형 검증
    $valid_entity_types = ['broadcaster', 'bank', 'government', 'fund'];
    if (!in_array($data['entity_type'], $valid_entity_types)) {
        echo json_encode([
            'success' => false,
            'message' => '유효하지 않은 엔터티 유형입니다.',
            'code' => 'INVALID_ENTITY_TYPE'
        ]);
        return;
    }
    
    // IP 주소 및 사용자 에이전트 가져오기
    $ip_address = isset($data['ip_address']) ? $data['ip_address'] : $_SERVER['REMOTE_ADDR'];
    $user_agent = isset($data['user_agent']) ? $data['user_agent'] : $_SERVER['HTTP_USER_AGENT'];
    
    // 사용자 ID 설정
    $user_id = isset($data['user_id']) ? $data['user_id'] : (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null);
    
    try {
        // 로그 저장
        $sql = "INSERT INTO external_monitoring_logs 
                (entity_type, entity_id, activity_type, description, ip_address, user_agent, user_id) 
                VALUES (:entity_type, :entity_id, :activity_type, :description, :ip_address, :user_agent, :user_id)";
        
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':entity_type', $data['entity_type'], PDO::PARAM_STR);
        $stmt->bindParam(':entity_id', $data['entity_id'], PDO::PARAM_INT);
        $stmt->bindParam(':activity_type', $data['activity_type'], PDO::PARAM_STR);
        $stmt->bindParam(':description', $data['description'], PDO::PARAM_STR);
        $stmt->bindParam(':ip_address', $ip_address, PDO::PARAM_STR);
        $stmt->bindParam(':user_agent', $user_agent, PDO::PARAM_STR);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        
        $stmt->execute();
        $log_id = $db->lastInsertId();
        
        // 성공 응답
        echo json_encode([
            'success' => true,
            'message' => '로그가 성공적으로 생성되었습니다.',
            'data' => [
                'log_id' => $log_id
            ]
        ]);
    } catch (PDOException $e) {
        // 에러 로깅
        error_log("외부 접속 감시 로그 생성 오류: " . $e->getMessage());
        
        // 오류 응답
        echo json_encode([
            'success' => false,
            'message' => '로그 저장 중 오류가 발생했습니다.',
            'code' => 'DATABASE_ERROR'
        ]);
    }
}

/**
 * 로그 목록 조회
 * 
 * @param PDO $db 데이터베이스 연결 객체
 */
function listLogs($db) {
    // 필터링 파라미터
    $entity_type = isset($_GET['entity_type']) ? $_GET['entity_type'] : '';
    $entity_id = isset($_GET['entity_id']) ? (int)$_GET['entity_id'] : 0;
    $activity_type = isset($_GET['activity_type']) ? $_GET['activity_type'] : '';
    $from_date = isset($_GET['from_date']) ? $_GET['from_date'] : '';
    $to_date = isset($_GET['to_date']) ? $_GET['to_date'] : '';
    $ip_address = isset($_GET['ip_address']) ? $_GET['ip_address'] : '';
    
    // 페이지네이션
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
    $offset = ($page - 1) * $limit;
    
    // 기본 쿼리
    $sql = "SELECT eml.*, 
            CASE 
                WHEN eml.entity_type = 'broadcaster' THEN b.name
                WHEN eml.entity_type = 'bank' THEN bk.bank_name
                WHEN eml.entity_type = 'government' THEN ga.agency_name
                WHEN eml.entity_type = 'fund' THEN fd.department_name
                ELSE 'Unknown'
            END as entity_name
            FROM external_monitoring_logs eml
            LEFT JOIN broadcaster b ON eml.entity_type = 'broadcaster' AND eml.entity_id = b.id
            LEFT JOIN banks bk ON eml.entity_type = 'bank' AND eml.entity_id = bk.id
            LEFT JOIN government_agencies ga ON eml.entity_type = 'government' AND eml.entity_id = ga.id
            LEFT JOIN fund_departments fd ON eml.entity_type = 'fund' AND eml.entity_id = fd.id
            WHERE 1=1";
    
    $params = [];
    
    // 필터 조건 추가
    if (!empty($entity_type)) {
        $sql .= " AND eml.entity_type = :entity_type";
        $params[':entity_type'] = $entity_type;
    }
    
    if ($entity_id > 0) {
        $sql .= " AND eml.entity_id = :entity_id";
        $params[':entity_id'] = $entity_id;
    }
    
    if (!empty($activity_type)) {
        $sql .= " AND eml.activity_type = :activity_type";
        $params[':activity_type'] = $activity_type;
    }
    
    if (!empty($from_date)) {
        $sql .= " AND DATE(eml.log_date) >= :from_date";
        $params[':from_date'] = $from_date;
    }
    
    if (!empty($to_date)) {
        $sql .= " AND DATE(eml.log_date) <= :to_date";
        $params[':to_date'] = $to_date;
    }
    
    if (!empty($ip_address)) {
        $sql .= " AND eml.ip_address = :ip_address";
        $params[':ip_address'] = $ip_address;
    }
    
    // 전체 레코드 수 조회
    $countSql = str_replace("eml.*, CASE", "COUNT(*) as total", $sql);
    $countSql = preg_replace('/ORDER BY.*$/i', '', $countSql);
    
    $stmt = $db->prepare($countSql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $totalRows = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // 정렬 및 페이지네이션
    $sql .= " ORDER BY eml.log_date DESC LIMIT :offset, :limit";
    
    try {
        $stmt = $db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 응답 데이터 구성
        $response = [
            'success' => true,
            'data' => [
                'logs' => $logs,
                'pagination' => [
                    'total' => $totalRows,
                    'page' => $page,
                    'limit' => $limit,
                    'pages' => ceil($totalRows / $limit)
                ]
            ]
        ];
        
        echo json_encode($response);
    } catch (PDOException $e) {
        // 에러 로깅
        error_log("외부 접속 감시 로그 조회 오류: " . $e->getMessage());
        
        // 오류 응답
        echo json_encode([
            'success' => false,
            'message' => '로그 조회 중 오류가 발생했습니다.',
            'code' => 'DATABASE_ERROR'
        ]);
    }
}

/**
 * 로그 상세 정보 조회
 * 
 * @param PDO $db 데이터베이스 연결 객체
 */
function getLogDetails($db) {
    // 로그 ID 확인
    $log_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if ($log_id <= 0) {
        echo json_encode([
            'success' => false,
            'message' => '유효하지 않은 로그 ID입니다.',
            'code' => 'INVALID_ID'
        ]);
        return;
    }
    
    try {
        // 로그 상세 정보 조회
        $sql = "SELECT eml.*, 
                CASE 
                    WHEN eml.entity_type = 'broadcaster' THEN b.name
                    WHEN eml.entity_type = 'bank' THEN bk.bank_name
                    WHEN eml.entity_type = 'government' THEN ga.agency_name
                    WHEN eml.entity_type = 'fund' THEN fd.department_name
                    ELSE 'Unknown'
                END as entity_name
                FROM external_monitoring_logs eml
                LEFT JOIN broadcaster b ON eml.entity_type = 'broadcaster' AND eml.entity_id = b.id
                LEFT JOIN banks bk ON eml.entity_type = 'bank' AND eml.entity_id = bk.id
                LEFT JOIN government_agencies ga ON eml.entity_type = 'government' AND eml.entity_id = ga.id
                LEFT JOIN fund_departments fd ON eml.entity_type = 'fund' AND eml.entity_id = fd.id
                WHERE eml.id = :log_id";
        
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':log_id', $log_id, PDO::PARAM_INT);
        $stmt->execute();
        
        $log = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$log) {
            echo json_encode([
                'success' => false,
                'message' => '로그를 찾을 수 없습니다.',
                'code' => 'LOG_NOT_FOUND'
            ]);
            return;
        }
        
        // 같은 IP의 최근 활동 조회
        if (!empty($log['ip_address'])) {
            $relatedSql = "SELECT eml.*, 
                          CASE 
                              WHEN eml.entity_type = 'broadcaster' THEN b.name
                              WHEN eml.entity_type = 'bank' THEN bk.bank_name
                              WHEN eml.entity_type = 'government' THEN ga.agency_name
                              WHEN eml.entity_type = 'fund' THEN fd.department_name
                              ELSE 'Unknown'
                          END as entity_name
                          FROM external_monitoring_logs eml
                          LEFT JOIN broadcaster b ON eml.entity_type = 'broadcaster' AND eml.entity_id = b.id
                          LEFT JOIN banks bk ON eml.entity_type = 'bank' AND eml.entity_id = bk.id
                          LEFT JOIN government_agencies ga ON eml.entity_type = 'government' AND eml.entity_id = ga.id
                          LEFT JOIN fund_departments fd ON eml.entity_type = 'fund' AND eml.entity_id = fd.id
                          WHERE eml.ip_address = :ip_address AND eml.id != :log_id
                          ORDER BY eml.log_date DESC
                          LIMIT 10";
            
            $relatedStmt = $db->prepare($relatedSql);
            $relatedStmt->bindValue(':ip_address', $log['ip_address'], PDO::PARAM_STR);
            $relatedStmt->bindValue(':log_id', $log_id, PDO::PARAM_INT);
            $relatedStmt->execute();
            
            $relatedLogs = $relatedStmt->fetchAll(PDO::FETCH_ASSOC);
            $log['related_logs'] = $relatedLogs;
            
            // IP 차단 여부 확인
            $blockSql = "SELECT COUNT(*) as blocked FROM ip_blocklist WHERE ip_address = :ip_address AND is_active = 1";
            $blockStmt = $db->prepare($blockSql);
            $blockStmt->bindValue(':ip_address', $log['ip_address'], PDO::PARAM_STR);
            $blockStmt->execute();
            $log['ip_is_blocked'] = ($blockStmt->fetch(PDO::FETCH_ASSOC)['blocked'] > 0);
        }
        
        // 응답
        echo json_encode([
            'success' => true,
            'data' => $log
        ]);
    } catch (PDOException $e) {
        // 에러 로깅
        error_log("외부 접속 감시 로그 상세 조회 오류: " . $e->getMessage());
        
        // 오류 응답
        echo json_encode([
            'success' => false,
            'message' => '로그 상세 정보 조회 중 오류가 발생했습니다.',
            'code' => 'DATABASE_ERROR'
        ]);
    }
}

/**
 * 통계 정보 조회
 * 
 * @param PDO $db 데이터베이스 연결 객체
 */
function getStatistics($db) {
    // 통계 기간
    $period = isset($_GET['period']) ? $_GET['period'] : 'today';
    
    // 기간에 따른 조건 설정
    $dateCondition = '';
    switch ($period) {
        case 'today':
            $dateCondition = "DATE(log_date) = CURDATE()";
            break;
        case 'yesterday':
            $dateCondition = "DATE(log_date) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
            break;
        case 'this_week':
            $dateCondition = "YEARWEEK(log_date, 1) = YEARWEEK(CURDATE(), 1)";
            break;
        case 'last_week':
            $dateCondition = "YEARWEEK(log_date, 1) = YEARWEEK(DATE_SUB(CURDATE(), INTERVAL 1 WEEK), 1)";
            break;
        case 'this_month':
            $dateCondition = "YEAR(log_date) = YEAR(CURDATE()) AND MONTH(log_date) = MONTH(CURDATE())";
            break;
        case 'last_month':
            $dateCondition = "YEAR(log_date) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) AND MONTH(log_date) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))";
            break;
        case 'custom':
            $from_date = isset($_GET['from_date']) ? $_GET['from_date'] : date('Y-m-d', strtotime('-30 days'));
            $to_date = isset($_GET['to_date']) ? $_GET['to_date'] : date('Y-m-d');
            $dateCondition = "DATE(log_date) BETWEEN '{$from_date}' AND '{$to_date}'";
            break;
        default:
            $dateCondition = "DATE(log_date) = CURDATE()";
            break;
    }
    
    try {
        // 총 로그 수
        $totalSql = "SELECT COUNT(*) as total FROM external_monitoring_logs WHERE {$dateCondition}";
        $totalStmt = $db->query($totalSql);
        $totalLogs = $totalStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // 엔터티 유형별 통계
        $entitySql = "SELECT entity_type, COUNT(*) as count FROM external_monitoring_logs 
                     WHERE {$dateCondition} GROUP BY entity_type ORDER BY count DESC";
        $entityStmt = $db->query($entitySql);
        $entityStats = $entityStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 활동 유형별 통계
        $activitySql = "SELECT activity_type, COUNT(*) as count FROM external_monitoring_logs 
                       WHERE {$dateCondition} GROUP BY activity_type ORDER BY count DESC";
        $activityStmt = $db->query($activitySql);
        $activityStats = $activityStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 시간별 통계
        $hourlySql = "SELECT HOUR(log_date) as hour, COUNT(*) as count FROM external_monitoring_logs 
                     WHERE {$dateCondition} GROUP BY HOUR(log_date) ORDER BY hour";
        $hourlyStmt = $db->query($hourlySql);
        $hourlyStats = $hourlyStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 상위 IP 주소
        $ipSql = "SELECT ip_address, COUNT(*) as count FROM external_monitoring_logs 
                 WHERE {$dateCondition} GROUP BY ip_address ORDER BY count DESC LIMIT 10";
        $ipStmt = $db->query($ipSql);
        $ipStats = $ipStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 응답 데이터 구성
        $response = [
            'success' => true,
            'data' => [
                'total_logs' => $totalLogs,
                'entity_stats' => $entityStats,
                'activity_stats' => $activityStats,
                'hourly_stats' => $hourlyStats,
                'ip_stats' => $ipStats,
                'period' => $period
            ]
        ];
        
        echo json_encode($response);
    } catch (PDOException $e) {
        // 에러 로깅
        error_log("외부 접속 감시 통계 조회 오류: " . $e->getMessage());
        
        // 오류 응답
        echo json_encode([
            'success' => false,
            'message' => '통계 정보 조회 중 오류가 발생했습니다.',
            'code' => 'DATABASE_ERROR'
        ]);
    }
}
