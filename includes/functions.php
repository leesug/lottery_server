<?php
/**
 * 공통 함수 파일
 * 
 * 이 파일은 애플리케이션 전체에서 사용되는 공통 유틸리티 함수를 포함합니다.
 */

require_once 'config.php';

/**
 * 로그 메시지를 파일에 기록합니다.
 * 
 * @param string $message 로그 메시지
 * @param string $level 로그 레벨 (info, warning, error, critical)
 * @param string $source 로그 출처 (선택 사항)
 * @return bool 성공 여부
 */
function logMessage($message, $level = 'info', $source = 'system') {
    if (!is_dir(LOGS_PATH)) {
        mkdir(LOGS_PATH, 0755, true);
    }
    
    $logFile = LOGS_PATH . '/' . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] [$level] [$source] $message" . PHP_EOL;
    
    return file_put_contents($logFile, $logEntry, FILE_APPEND) !== false;
}

/**
 * 고유한 유지보수 코드를 생성합니다.
 * 
 * @param string $maintenance_type 유지보수 유형 (regular, repair, upgrade, inspection 등)
 * @param string $equipment_code 장비 코드
 * @return string 생성된 유지보수 코드 (형식: MT + 연도 2자리 + 월 2자리 + 유지보수유형 1자리 + 장비코드 약어 + 일련번호 3자리)
 */
function generate_maintenance_code($maintenance_type = '', $equipment_code = '') {
    global $db;
    
    // 현재 연도와 월 정보 가져오기
    $year = date('y');
    $month = date('m');
    
    // 유지보수 유형에 따른 접두사 지정
    $type_prefix = '';
    switch ($maintenance_type) {
        case 'regular':
            $type_prefix = 'R';
            break;
        case 'repair':
            $type_prefix = 'F';
            break;
        case 'upgrade':
            $type_prefix = 'U';
            break;
        case 'inspection':
            $type_prefix = 'I';
            break;
        case 'other':
            $type_prefix = 'O';
            break;
        default:
            $type_prefix = 'X';
    }
    
    // 장비 코드에서 마지막 4자리를 추출 (EQ2505T00001 -> T001)
    $equipment_suffix = '';
    if (!empty($equipment_code) && strlen($equipment_code) >= 5) {
        $type_char = substr($equipment_code, 6, 1); // 장비 유형 문자
        $seq_chars = substr($equipment_code, -3); // 일련번호 마지막 3자리
        $equipment_suffix = $type_char . $seq_chars;
    } else {
        $equipment_suffix = 'X000';
    }
    
    // 기본 코드 형식: MT + 연도 2자리 + 월 2자리 + 유지보수유형 1자리 + 장비코드 약어
    $prefix = "MT{$year}{$month}{$type_prefix}{$equipment_suffix}";
    
    // 같은 접두사를 가진 가장 최근 유지보수 코드 조회
    $sql = "SELECT maintenance_code FROM store_equipment_maintenance WHERE maintenance_code LIKE ? ORDER BY maintenance_code DESC LIMIT 1";
    $stmt = $db->prepare($sql);
    
    if (!$stmt) {
        logError("유지보수 코드 생성 쿼리 준비 실패: " . $db->error);
        // 오류 발생 시 대체 코드 생성
        return $prefix . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
    }
    
    $search_pattern = $prefix . '%';
    $stmt->bind_param("s", $search_pattern);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // 기존 코드가 있는 경우 일련번호 증가
        $row = $result->fetch_assoc();
        $last_code = $row['maintenance_code'];
        $sequence_number = (int)substr($last_code, -3);
        $next_sequence = $sequence_number + 1;
    } else {
        // 기존 코드가 없는 경우 1부터 시작
        $next_sequence = 1;
    }
    
    // 일련번호를 3자리 숫자로 포맷팅
    $formatted_sequence = str_pad($next_sequence, 3, '0', STR_PAD_LEFT);
    
    // 최종 유지보수 코드 생성
    $maintenance_code = $prefix . $formatted_sequence;
    
    return $maintenance_code;
}

/**
 * 장비 코드 유효성을 검증합니다.
 * 
 * @param string $equipment_code 검증할 장비 코드
 * @return bool 장비 코드가 유효하면 true, 그렇지 않으면 false
 */
function validate_equipment_code($equipment_code) {
    global $db;
    
    if (empty($equipment_code)) {
        return false;
    }
    
    // 코드 형식 유효성 검사 (EQ + 연도 2자리 + 월 2자리 + 장비유형 1자리 + 일련번호 5자리)
    if (!preg_match('/^EQ\d{4}[A-Z]\d{5}$/', $equipment_code)) {
        return false;
    }
    
    // 데이터베이스에 해당 코드가 존재하는지 확인
    $sql = "SELECT id FROM store_equipment WHERE equipment_code = ?";
    $stmt = $db->prepare($sql);
    
    if (!$stmt) {
        logError("장비 코드 검증 쿼리 준비 실패: " . $db->error);
        return false;
    }
    
    $stmt->bind_param("s", $equipment_code);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->num_rows > 0;
}

/**
 * 장비 코드를 기반으로 장비 정보를 조회합니다.
 * 
 * @param string $equipment_code 조회할 장비 코드
 * @return array|null 장비 정보가 담긴 배열 또는 장비가 없는 경우 null
 */
function get_equipment_by_code($equipment_code) {
    global $db;
    
    if (empty($equipment_code)) {
        return null;
    }
    
    $sql = "
        SELECT e.*, s.store_name, s.store_code
        FROM store_equipment e
        JOIN stores s ON e.store_id = s.id
        WHERE e.equipment_code = ?
    ";
    
    $stmt = $db->prepare($sql);
    
    if (!$stmt) {
        logError("장비 정보 조회 쿼리 준비 실패: " . $db->error);
        return null;
    }
    
    $stmt->bind_param("s", $equipment_code);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        return null;
    }
    
    return $result->fetch_assoc();
}

/**
 * 장비 ID를 기반으로 장비 코드를 조회합니다.
 * 
 * @param int $equipment_id 조회할 장비 ID
 * @return string|null 장비 코드 또는 장비가 없는 경우 null
 */
function get_equipment_code_by_id($equipment_id) {
    global $db;
    
    if (empty($equipment_id) || $equipment_id <= 0) {
        return null;
    }
    
    $sql = "SELECT equipment_code FROM store_equipment WHERE id = ?";
    $stmt = $db->prepare($sql);
    
    if (!$stmt) {
        logError("장비 코드 조회 쿼리 준비 실패: " . $db->error);
        return null;
    }
    
    $stmt->bind_param("i", $equipment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        return null;
    }
    
    $row = $result->fetch_assoc();
    return $row['equipment_code'];
}

/**
 * 고유한 장비 코드를 생성합니다.
 * 
 * @param string $equipment_type 장비 유형 (terminal, printer 등)
 * @return string 생성된 장비 코드 (형식: EQ + 연도 2자리 + 월 2자리 + 일련번호 5자리)
 */
function generate_equipment_code($equipment_type = '') {
    global $db;
    
    // 현재 연도와 월 정보 가져오기
    $year = date('y');
    $month = date('m');
    
    // 장비 유형에 따른 접두사 지정
    $type_prefix = '';
    switch ($equipment_type) {
        case 'terminal':
            $type_prefix = 'T';
            break;
        case 'printer':
            $type_prefix = 'P';
            break;
        case 'scanner':
            $type_prefix = 'S';
            break;
        case 'display':
            $type_prefix = 'D';
            break;
        case 'router':
            $type_prefix = 'R';
            break;
        case 'other':
            $type_prefix = 'O';
            break;
        default:
            $type_prefix = 'X';
    }
    
    // 기본 코드 형식: EQ + 연도 2자리 + 월 2자리 + 장비유형 1자리
    $prefix = "EQ{$year}{$month}{$type_prefix}";
    
    // 같은 접두사를 가진 가장 최근 장비 코드 조회
    $sql = "SELECT equipment_code FROM store_equipment WHERE equipment_code LIKE ? ORDER BY equipment_code DESC LIMIT 1";
    $stmt = $db->prepare($sql);
    
    if (!$stmt) {
        logError("장비 코드 생성 쿼리 준비 실패: " . $db->error);
        // 오류 발생 시 대체 코드 생성
        return $prefix . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
    }
    
    $search_pattern = $prefix . '%';
    $stmt->bind_param("s", $search_pattern);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // 기존 코드가 있는 경우 일련번호 증가
        $row = $result->fetch_assoc();
        $last_code = $row['equipment_code'];
        $sequence_number = (int)substr($last_code, -5);
        $next_sequence = $sequence_number + 1;
    } else {
        // 기존 코드가 없는 경우 1부터 시작
        $next_sequence = 1;
    }
    
    // 일련번호를 5자리 숫자로 포맷팅
    $formatted_sequence = str_pad($next_sequence, 5, '0', STR_PAD_LEFT);
    
    // 최종 장비 코드 생성
    $equipment_code = $prefix . $formatted_sequence;
    
    return $equipment_code;
}

/**
 * 정보 로그 메시지를 기록합니다.
 * 
 * @param string $message 로그 메시지
 * @param string $source 로그 출처 (선택 사항)
 * @return bool 성공 여부
 */
function logInfo($message, $source = 'system') {
    return logMessage($message, 'info', $source);
}

/**
 * 경고 로그 메시지를 기록합니다.
 * 
 * @param string $message 로그 메시지
 * @param string $source 로그 출처 (선택 사항)
 * @return bool 성공 여부
 */
function logWarning($message, $source = 'system') {
    return logMessage($message, 'warning', $source);
}

/**
 * 오류 로그 메시지를 기록합니다.
 * 
 * @param string $message 로그 메시지
 * @param string $source 로그 출처 (선택 사항)
 * @return bool 성공 여부
 */
function logError($message, $source = 'system') {
    return logMessage($message, 'error', $source);
}

/**
 * 치명적 오류 로그 메시지를 기록합니다.
 * 
 * @param string $message 로그 메시지
 * @param string $source 로그 출처 (선택 사항)
 * @return bool 성공 여부
 */
function logCritical($message, $source = 'system') {
    return logMessage($message, 'critical', $source);
}

/**
 * 성공 로그 메시지를 기록합니다.
 * 
 * @param string $message 로그 메시지
 * @param string $source 로그 출처 (선택 사항)
 * @return bool 성공 여부
 */
function logSuccess($message, $source = 'system') {
    return logMessage($message, 'success', $source);
}

/**
 * 개발 로그 메시지를 기록합니다. (개발 모드에서만 활성화)
 * 
 * @param string $message 로그 메시지
 * @param string $source 로그 출처 (선택 사항)
 * @return bool 성공 여부
 */
function logDebug($message, $source = 'system') {
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        return logMessage($message, 'debug', $source);
    }
    return true;
}

/**
 * 보안을 위한 출력 이스케이프 처리
 * 
 * @param string $value 이스케이프 처리할 값
 * @return string 이스케이프 처리된 값
 */
function escape($value) {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

/**
 * 보안을 위한 입력 필터링
 * 
 * @param string|array $value 필터링할 값
 * @return string|array 필터링된 값
 */
function sanitizeInput($value) {
    if (is_array($value)) {
        foreach ($value as $key => $val) {
            $value[$key] = sanitizeInput($val);
        }
        return $value;
    }
    
    return trim(strip_tags($value));
}

/**
 * JSON 결과를 반환합니다.
 * 
 * @param bool $success 성공 여부
 * @param string $message 메시지
 * @param array $data 추가 데이터 (선택 사항)
 * @return void
 */
function returnJson($success, $message, $data = []) {
    header('Content-Type: application/json');
    
    $result = [
        'success' => $success,
        'message' => $message
    ];
    
    if (!empty($data)) {
        $result['data'] = $data;
    }
    
    echo json_encode($result);
    exit;
}

/**
 * 사용자가 로그인되어 있는지 확인합니다.
 * 세션 관리 기능이 비활성화되어 있어 항상 로그인되어 있다고 간주합니다.
 * 
 * @return bool 로그인 여부
 */
function isLoggedIn() {
    return true;
}

/**
 * 사용자가 관리자인지 확인합니다.
 * 세션 관리 기능이 비활성화되어 있어 항상 관리자로 간주합니다.
 * 
 * @return bool 관리자 여부
 */
function isAdmin() {
    return true;
}

/**
 * 로그인되어 있지 않으면 로그인 페이지로 리디렉션합니다.
 * 세션 관리 기능이 비활성화되어 항상 로그인되어 있다고 간주합니다.
 */
function requireLogin() {
    return true;
}

/**
 * 인증 상태를 확인합니다. (함수 별칭)
 * 세션 관리 기능이 비활성화되어 항상 로그인되어 있다고 간주합니다.
 * 
 * @param string $requiredRole 필요한 역할 (선택 사항)
 * @return bool 로그인 여부
 */
function check_auth($requiredRole = null) {
    return true;
}

/**
 * 사용자가 관리자가 아니면 권한 없음 페이지로 리디렉션합니다.
 */
function requireAdmin() {
    requireLogin();
    
    if (!isAdmin()) {
        header('Location: /server/pages/unauthorized.php');
        exit;
    }
}

/**
 * 현재 사용자가 특정 권한을 가지고 있는지 확인합니다.
 * 참고: 이 함수의 구현은 issues.php에 있습니다.
 */
// function has_permission($permission) {
//     // 개발/테스트 모드에서는 모든 권한을 부여
//     return true;
// }

/**
 * 사용자의 권한을 확인하고 접근 제어를 수행합니다.
 * 권한이 없는 경우 접근 거부 페이지로 리디렉션합니다.
 * 
 * @param string $permission 필요한 권한
 * @return bool 권한 존재 여부
 */
function check_permission($permission) {
    // 개발/테스트 모드에서는 모든 권한을 부여
    if (defined('DEVELOPMENT_MODE') && DEVELOPMENT_MODE) {
        return true;
    }
    
    // 실제 환경에서는 권한 확인 로직 구현
    // 권한이 없으면 접근 거부 페이지로 리디렉션
    
    // 임시로 항상 권한 부여 (개발 편의를 위해)
    return true;
}

/**
 * 플래시 메시지를 설정합니다.
 * 세션 관리 기능이 비활성화되어 있어 화면에 직접 출력합니다.
 * 
 * @param string $type 메시지 타입 (success, info, warning, error)
 * @param string $message 메시지 내용
 * @return void
 */
function set_flash_message($type, $message) {
    // 세션 관리 기능이 활성화되어 있으면 세션에 저장
    // 개발/테스트 모드에서는 화면에 바로 출력
    echo "<div class=\"alert alert-$type\">$message</div>";
}

/**
 * 클라이언트의 IP 주소를 가져옵니다.
 * 
 * @return string IP 주소
 */
function getClientIp() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    
    return $ip;
}

/**
 * 사용자 에이전트 문자열을 가져옵니다.
 * 
 * @return string 사용자 에이전트
 */
function getUserAgent() {
    return $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
}

/**
 * CSRF 토큰을 생성하고 세션에 저장합니다.
 * 세션 관리 기능이 비활성화되어 단순 토큰을 반환합니다.
 * 
 * @return string CSRF 토큰
 */
function generateCsrfToken() {
    return md5(uniqid(mt_rand(), true));
}

/**
 * CSRF 토큰의 유효성을 검사합니다.
 * 세션 관리 기능이 비활성화되어 항상 유효하다고 간주합니다.
 * 
 * @param string $token 검사할 CSRF 토큰
 * @return bool 유효 여부
 */
function validateCsrfToken($token) {
    return true;
}

/**
 * CSRF 토큰을 검증합니다 (별칭).
 * 세션 관리 기능이 비활성화되어 항상 유효하다고 간주합니다.
 * 
 * @return bool 항상 true 반환
 */
function verify_csrf_token() {
    // 개발/테스트 모드에서는 CSRF 검증을 생략
    if (defined('DEVELOPMENT_MODE') && DEVELOPMENT_MODE) {
        return true;
    }
    
    // 실제 환경에서는 CSRF 토큰 검증
    if (isset($_POST['csrf_token']) && !empty($_POST['csrf_token'])) {
        return validateCsrfToken($_POST['csrf_token']);
    }
    
    // 개발 편의를 위해 항상 성공 반환
    return true;
}

/**
 * 주어진 날짜를 원하는 형식으로 포맷팅합니다.
 * 
 * @param string $date 포맷팅할 날짜 문자열
 * @param string $format 원하는 날짜 형식 (선택 사항)
 * @return string 포맷팅된 날짜
 */
function formatDate($date, $format = 'Y-m-d H:i:s') {
    $timestamp = strtotime($date);
    return date($format, $timestamp);
}

/**
 * 랜덤 토큰을 생성합니다.
 * 
 * @param int $length 토큰 길이 (선택 사항)
 * @return string 생성된 토큰
 */
function generateRandomToken($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * 테이블 이름에 접두사를 추가합니다.
 * 
 * @param string $tableName 테이블 이름
 * @return string 접두사가 추가된 테이블 이름
 */
function getTableName($tableName) {
    $prefix = 'lotto_';
    return $prefix . $tableName;
}

/**
 * 바이트 크기를 사람이 읽기 쉬운 형식으로 변환합니다.
 * 
 * @param int $bytes 바이트 크기
 * @param int $precision 소수점 자릿수
 * @return string 변환된 크기 문자열 (예: 1.5 KB, 2.3 MB)
 */
if (!function_exists('formatBytes')) {
    function formatBytes($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}

/**
 * 사용자 활동을 로그에 기록합니다.
 * 
 * @param string $activity 활동 내용
 * @param string $module 모듈 이름
 * @param int $userId 사용자 ID (선택 사항)
 * @return bool 성공 여부
 */
function log_activity($activity, $module = 'system', $userId = null) {
    if ($userId === null && isset($_SESSION['user_id'])) {
        $userId = $_SESSION['user_id'];
    }
    
    // 파일 로그에 기록
    logInfo($activity, $module);
    
    // 데이터베이스에 로그 기록 (실제 환경에서 활성화)
    /*
    require_once 'db.php';
    
    $sql = "INSERT INTO activity_logs (user_id, activity, module, ip_address) VALUES (?, ?, ?, ?)";
    $params = [
        ['type' => 'i', 'value' => $userId],
        ['type' => 's', 'value' => $activity],
        ['type' => 's', 'value' => $module],
        ['type' => 's', 'value' => getClientIp()]
    ];
    
    return insert($sql, $params) !== false;
    */
    
    return true;
}

/**
 * 페이지 접근 권한을 확인합니다.
 * 세션 관리 기능이 비활성화되어 있어 항상 접근을 허용합니다.
 * 
 * @param string $permission 필요한 권한
 * @return bool 접근 허용 여부
 */
function checkPageAccess($permission) {
    // 세션 관리 기능이 비활성화되어 있으므로 항상 접근을 허용합니다.
    return true;
}

/**
 * 지정된 URL로 리디렉션합니다.
 * 
 * @param string $url 리디렉션할 URL
 * @return void
 */
function redirect_to($url) {
    // 개발/테스트 모드에서는 리디렉션 대신 메시지 출력
    if (defined('DEVELOPMENT_MODE') && DEVELOPMENT_MODE) {
        echo "<div class=\"alert alert-info\">리디렉션: $url</div>";
    } else {
        // 실제 환경에서는 리디렉션 수행
        header("Location: " . SERVER_URL . $url);
        exit;
    }
}

/**
 * 데이터베이스 연결을 가져옵니다. (함수 별칭)
 * 
 * @deprecated get_db_connection() 함수를 사용하세요.
 * @return PDO 데이터베이스 연결 객체
 */
function getDBConnection() {
    // db.php의 get_db_connection() 함수 사용
    if (function_exists('get_db_connection')) {
        return get_db_connection();
    } else {
        die("Database connection function not available");
    }
}

/**
 * 기금 유형 코드에 해당하는 레이블을 반환합니다.
 * 
 * @param string $fundType 기금 유형 코드
 * @return string 기금 유형 레이블
 */
function getFundTypeLabel($fundType) {
    $labels = [
        'operational' => '운영 기금',
        'reserve' => '예비 기금',
        'special' => '특별 기금',
        'project' => '프로젝트 기금',
        'emergency' => '비상 기금',
        'development' => '개발 기금',
        'maintenance' => '유지보수 기금',
        'charity' => '자선 기금',
        'jackpot' => '대박 기금',
        'prize' => '상금 기금',
        'marketing' => '마케팅 기금',
        'admin' => '행정 기금',
        'other' => '기타 기금'
    ];
    
    return $labels[$fundType] ?? '알 수 없는 기금';
}
