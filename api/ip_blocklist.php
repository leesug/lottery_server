<?php
/**
 * IP 차단 API
 * 
 * 이 API는 IP 주소 차단 관련 기능을 제공합니다.
 * - IP 차단
 * - IP 차단 해제
 * - 차단된 IP 목록 조회
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
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
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

// 테이블이 없으면 생성
ensureIpBlocklistTable($db);

// 요청 메서드 확인
$method = $_SERVER['REQUEST_METHOD'];

// IP 차단 테이블이 없으면 생성하는 함수
function ensureIpBlocklistTable($db) {
    $sql = "CREATE TABLE IF NOT EXISTS `ip_blocklist` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `ip_address` varchar(45) NOT NULL COMMENT 'IP 주소',
        `reason` text DEFAULT NULL COMMENT '차단 이유',
        `blocked_by` int(11) DEFAULT NULL COMMENT '차단한 사용자 ID',
        `source_log_id` int(11) DEFAULT NULL COMMENT '관련 로그 ID',
        `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '활성 상태',
        `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '생성 시간',
        `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정 시간',
        PRIMARY KEY (`id`),
        UNIQUE KEY `idx_ip_address` (`ip_address`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='IP 차단 목록';";
    
    try {
        $db->exec($sql);
    } catch (PDOException $e) {
        error_log("IP 차단 테이블 생성 오류: " . $e->getMessage());
    }
}

// POST 요청 처리 (IP 차단)
if ($method === 'POST') {
    // JSON 데이터 파싱
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        echo json_encode([
            'success' => false,
            'message' => '잘못된 데이터 형식입니다.',
            'code' => 'INVALID_DATA'
        ]);
        exit;
    }
    
    $action = isset($data['action']) ? $data['action'] : '';
    
    if ($action === 'block_ip') {
        blockIp($db, $data);
    } else {
        echo json_encode([
            'success' => false,
            'message' => '잘못된 액션입니다.',
            'code' => 'INVALID_ACTION'
        ]);
    }
}
// GET 요청 처리 (IP 차단 목록 조회)
else if ($method === 'GET') {
    $action = isset($_GET['action']) ? $_GET['action'] : 'list';
    
    if ($action === 'list') {
        listBlockedIps($db);
    } else if ($action === 'check') {
        checkIpBlocked($db);
    } else {
        echo json_encode([
            'success' => false,
            'message' => '잘못된 액션입니다.',
            'code' => 'INVALID_ACTION'
        ]);
    }
}
// PUT 요청 처리 (IP 차단 상태 업데이트)
else if ($method === 'PUT') {
    // JSON 데이터 파싱
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        echo json_encode([
            'success' => false,
            'message' => '잘못된 데이터 형식입니다.',
            'code' => 'INVALID_DATA'
        ]);
        exit;
    }
    
    updateIpBlock($db, $data);
}
// DELETE 요청 처리 (IP 차단 해제)
else if ($method === 'DELETE') {
    $ip_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if ($ip_id <= 0) {
        echo json_encode([
            'success' => false,
            'message' => '유효하지 않은 ID입니다.',
            'code' => 'INVALID_ID'
        ]);
        exit;
    }
    
    unblockIp($db, $ip_id);
}
else {
    echo json_encode([
        'success' => false,
        'message' => '지원하지 않는 메서드입니다.',
        'code' => 'METHOD_NOT_ALLOWED'
    ]);
}

/**
 * IP 주소 차단
 * 
 * @param PDO $db 데이터베이스 연결 객체
 * @param array $data 요청 데이터
 */
function blockIp($db, $data) {
    // 필수 필드 검증
    if (!isset($data['ip_address']) || empty($data['ip_address'])) {
        echo json_encode([
            'success' => false,
            'message' => 'IP 주소가 필요합니다.',
            'code' => 'MISSING_IP'
        ]);
        return;
    }
    
    $ip_address = $data['ip_address'];
    $reason = isset($data['reason']) ? $data['reason'] : '관리자에 의한 차단';
    $source_log_id = isset($data['log_id']) ? (int)$data['log_id'] : null;
    $blocked_by = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    
    try {
        // 이미 차단된 IP인지 확인
        $checkSql = "SELECT id, is_active FROM ip_blocklist WHERE ip_address = :ip_address";
        $checkStmt = $db->prepare($checkSql);
        $checkStmt->bindParam(':ip_address', $ip_address, PDO::PARAM_STR);
        $checkStmt->execute();
        $existingBlock = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existingBlock) {
            // 이미 차단된 상태이면 업데이트
            if ($existingBlock['is_active'] == 1) {
                echo json_encode([
                    'success' => false,
                    'message' => '이미 차단된 IP 주소입니다.',
                    'code' => 'ALREADY_BLOCKED'
                ]);
                return;
            }
            
            // 비활성화된 상태이면 활성화
            $updateSql = "UPDATE ip_blocklist 
                         SET is_active = 1, reason = :reason, blocked_by = :blocked_by, 
                         source_log_id = :source_log_id, updated_at = NOW()
                         WHERE id = :id";
            
            $updateStmt = $db->prepare($updateSql);
            $updateStmt->bindParam(':reason', $reason, PDO::PARAM_STR);
            $updateStmt->bindParam(':blocked_by', $blocked_by, PDO::PARAM_INT);
            $updateStmt->bindParam(':source_log_id', $source_log_id, PDO::PARAM_INT);
            $updateStmt->bindParam(':id', $existingBlock['id'], PDO::PARAM_INT);
            $updateStmt->execute();
            
            echo json_encode([
                'success' => true,
                'message' => 'IP 주소가 다시 차단되었습니다.',
                'data' => [
                    'ip_id' => $existingBlock['id'],
                    'ip_address' => $ip_address
                ]
            ]);
        } else {
            // 새로운 차단 추가
            $insertSql = "INSERT INTO ip_blocklist 
                         (ip_address, reason, blocked_by, source_log_id)
                         VALUES (:ip_address, :reason, :blocked_by, :source_log_id)";
            
            $insertStmt = $db->prepare($insertSql);
            $insertStmt->bindParam(':ip_address', $ip_address, PDO::PARAM_STR);
            $insertStmt->bindParam(':reason', $reason, PDO::PARAM_STR);
            $insertStmt->bindParam(':blocked_by', $blocked_by, PDO::PARAM_INT);
            $insertStmt->bindParam(':source_log_id', $source_log_id, PDO::PARAM_INT);
            $insertStmt->execute();
            
            $ip_id = $db->lastInsertId();
            
            echo json_encode([
                'success' => true,
                'message' => 'IP 주소가 성공적으로 차단되었습니다.',
                'data' => [
                    'ip_id' => $ip_id,
                    'ip_address' => $ip_address
                ]
            ]);
        }
        
        // 활동 로그 기록
        $logSql = "INSERT INTO activity_logs (user_id, activity_type, description, ip_address)
                  VALUES (:user_id, 'IP 차단', :description, :admin_ip)";
        
        $logStmt = $db->prepare($logSql);
        $logStmt->bindParam(':user_id', $blocked_by, PDO::PARAM_INT);
        $description = "IP 주소 {$ip_address}를 차단함. 이유: {$reason}";
        $logStmt->bindParam(':description', $description, PDO::PARAM_STR);
        $admin_ip = $_SERVER['REMOTE_ADDR'];
        $logStmt->bindParam(':admin_ip', $admin_ip, PDO::PARAM_STR);
        $logStmt->execute();
        
    } catch (PDOException $e) {
        // 에러 로깅
        error_log("IP 차단 오류: " . $e->getMessage());
        
        // 오류 응답
        echo json_encode([
            'success' => false,
            'message' => 'IP 차단 중 오류가 발생했습니다.',
            'code' => 'DATABASE_ERROR'
        ]);
    }
}

/**
 * 차단된 IP 목록 조회
 * 
 * @param PDO $db 데이터베이스 연결 객체
 */
function listBlockedIps($db) {
    // 필터링 파라미터
    $is_active = isset($_GET['is_active']) ? (int)$_GET['is_active'] : 1;
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    
    // 페이지네이션
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
    $offset = ($page - 1) * $limit;
    
    // 기본 쿼리
    $sql = "SELECT b.*, u.username as blocked_by_username
            FROM ip_blocklist b
            LEFT JOIN users u ON b.blocked_by = u.id
            WHERE 1=1";
    
    $params = [];
    
    // 필터 조건 추가
    if ($is_active !== '') {
        $sql .= " AND b.is_active = :is_active";
        $params[':is_active'] = $is_active;
    }
    
    if (!empty($search)) {
        $sql .= " AND (b.ip_address LIKE :search OR b.reason LIKE :search)";
        $search = '%' . $search . '%';
        $params[':search'] = $search;
    }
    
    // 전체 레코드 수 조회
    $countSql = str_replace("b.*, u.username as blocked_by_username", "COUNT(*) as total", $sql);
    $countSql = preg_replace('/ORDER BY.*$/i', '', $countSql);
    
    $stmt = $db->prepare($countSql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $totalRows = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // 정렬 및 페이지네이션
    $sql .= " ORDER BY b.created_at DESC LIMIT :offset, :limit";
    
    try {
        $stmt = $db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        $ips = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 응답 데이터 구성
        $response = [
            'success' => true,
            'data' => [
                'ips' => $ips,
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
        error_log("IP 목록 조회 오류: " . $e->getMessage());
        
        // 오류 응답
        echo json_encode([
            'success' => false,
            'message' => 'IP 목록 조회 중 오류가 발생했습니다.',
            'code' => 'DATABASE_ERROR'
        ]);
    }
}

/**
 * IP 차단 상태 확인
 * 
 * @param PDO $db 데이터베이스 연결 객체
 */
function checkIpBlocked($db) {
    $ip_address = isset($_GET['ip']) ? $_GET['ip'] : $_SERVER['REMOTE_ADDR'];
    
    try {
        $sql = "SELECT * FROM ip_blocklist WHERE ip_address = :ip_address AND is_active = 1";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':ip_address', $ip_address, PDO::PARAM_STR);
        $stmt->execute();
        
        $block = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($block) {
            echo json_encode([
                'success' => true,
                'data' => [
                    'is_blocked' => true,
                    'block_info' => $block
                ]
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'data' => [
                    'is_blocked' => false
                ]
            ]);
        }
    } catch (PDOException $e) {
        // 에러 로깅
        error_log("IP 차단 상태 확인 오류: " . $e->getMessage());
        
        // 오류 응답
        echo json_encode([
            'success' => false,
            'message' => 'IP 차단 상태 확인 중 오류가 발생했습니다.',
            'code' => 'DATABASE_ERROR'
        ]);
    }
}

/**
 * IP 차단 상태 업데이트
 * 
 * @param PDO $db 데이터베이스 연결 객체
 * @param array $data 요청 데이터
 */
function updateIpBlock($db, $data) {
    // 필수 필드 검증
    if (!isset($data['id']) || empty($data['id'])) {
        echo json_encode([
            'success' => false,
            'message' => 'IP 차단 ID가 필요합니다.',
            'code' => 'MISSING_ID'
        ]);
        return;
    }
    
    $id = (int)$data['id'];
    $is_active = isset($data['is_active']) ? (int)$data['is_active'] : 1;
    $reason = isset($data['reason']) ? $data['reason'] : null;
    $updated_by = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    
    try {
        // IP 차단 정보 확인
        $checkSql = "SELECT * FROM ip_blocklist WHERE id = :id";
        $checkStmt = $db->prepare($checkSql);
        $checkStmt->bindParam(':id', $id, PDO::PARAM_INT);
        $checkStmt->execute();
        
        $block = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$block) {
            echo json_encode([
                'success' => false,
                'message' => '존재하지 않는 IP 차단 정보입니다.',
                'code' => 'BLOCK_NOT_FOUND'
            ]);
            return;
        }
        
        // 상태 업데이트
        $updateSql = "UPDATE ip_blocklist 
                     SET is_active = :is_active";
        
        $params = [
            ':id' => $id,
            ':is_active' => $is_active
        ];
        
        if ($reason !== null) {
            $updateSql .= ", reason = :reason";
            $params[':reason'] = $reason;
        }
        
        $updateSql .= " WHERE id = :id";
        
        $updateStmt = $db->prepare($updateSql);
        foreach ($params as $key => $value) {
            $updateStmt->bindValue($key, $value);
        }
        $updateStmt->execute();
        
        // 활동 로그 기록
        $activity_type = $is_active ? 'IP 차단 활성화' : 'IP 차단 비활성화';
        $description = "IP 주소 {$block['ip_address']}의 차단 상태를 " . ($is_active ? '활성화' : '비활성화') . "함.";
        
        $logSql = "INSERT INTO activity_logs (user_id, activity_type, description, ip_address)
                  VALUES (:user_id, :activity_type, :description, :admin_ip)";
        
        $logStmt = $db->prepare($logSql);
        $logStmt->bindParam(':user_id', $updated_by, PDO::PARAM_INT);
        $logStmt->bindParam(':activity_type', $activity_type, PDO::PARAM_STR);
        $logStmt->bindParam(':description', $description, PDO::PARAM_STR);
        $admin_ip = $_SERVER['REMOTE_ADDR'];
        $logStmt->bindParam(':admin_ip', $admin_ip, PDO::PARAM_STR);
        $logStmt->execute();
        
        echo json_encode([
            'success' => true,
            'message' => 'IP 차단 상태가 업데이트되었습니다.',
            'data' => [
                'id' => $id,
                'is_active' => $is_active
            ]
        ]);
    } catch (PDOException $e) {
        // 에러 로깅
        error_log("IP 차단 상태 업데이트 오류: " . $e->getMessage());
        
        // 오류 응답
        echo json_encode([
            'success' => false,
            'message' => 'IP 차단 상태 업데이트 중 오류가 발생했습니다.',
            'code' => 'DATABASE_ERROR'
        ]);
    }
}

/**
 * IP 차단 해제
 * 
 * @param PDO $db 데이터베이스 연결 객체
 * @param int $id IP 차단 ID
 */
function unblockIp($db, $id) {
    try {
        // IP 차단 정보 확인
        $checkSql = "SELECT * FROM ip_blocklist WHERE id = :id";
        $checkStmt = $db->prepare($checkSql);
        $checkStmt->bindParam(':id', $id, PDO::PARAM_INT);
        $checkStmt->execute();
        
        $block = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$block) {
            echo json_encode([
                'success' => false,
                'message' => '존재하지 않는 IP 차단 정보입니다.',
                'code' => 'BLOCK_NOT_FOUND'
            ]);
            return;
        }
        
        // 이미 해제된 상태인지 확인
        if ($block['is_active'] == 0) {
            echo json_encode([
                'success' => false,
                'message' => '이미 해제된 IP 차단입니다.',
                'code' => 'ALREADY_UNBLOCKED'
            ]);
            return;
        }
        
        // 차단 해제 (is_active = 0으로 설정)
        $updateSql = "UPDATE ip_blocklist SET is_active = 0 WHERE id = :id";
        $updateStmt = $db->prepare($updateSql);
        $updateStmt->bindParam(':id', $id, PDO::PARAM_INT);
        $updateStmt->execute();
        
        // 활동 로그 기록
        $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
        $activity_type = 'IP 차단 해제';
        $description = "IP 주소 {$block['ip_address']}의 차단을 해제함.";
        $admin_ip = $_SERVER['REMOTE_ADDR'];
        
        $logSql = "INSERT INTO activity_logs (user_id, activity_type, description, ip_address)
                  VALUES (:user_id, :activity_type, :description, :admin_ip)";
        
        $logStmt = $db->prepare($logSql);
        $logStmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $logStmt->bindParam(':activity_type', $activity_type, PDO::PARAM_STR);
        $logStmt->bindParam(':description', $description, PDO::PARAM_STR);
        $logStmt->bindParam(':admin_ip', $admin_ip, PDO::PARAM_STR);
        $logStmt->execute();
        
        echo json_encode([
            'success' => true,
            'message' => 'IP 차단이 해제되었습니다.',
            'data' => [
                'id' => $id,
                'ip_address' => $block['ip_address']
            ]
        ]);
    } catch (PDOException $e) {
        // 에러 로깅
        error_log("IP 차단 해제 오류: " . $e->getMessage());
        
        // 오류 응답
        echo json_encode([
            'success' => false,
            'message' => 'IP 차단 해제 중 오류가 발생했습니다.',
            'code' => 'DATABASE_ERROR'
        ]);
    }
}
