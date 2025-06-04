<?php
/**
 * 세션 관리 전용 파일
 * 
 * 이 파일은 프로젝트 전체에서 세션을 관리하는 모든 함수들을 포함합니다.
 * 모든 페이지에서 이 파일을 포함하여 세션 관리를 일관되게 처리합니다.
 */

// 세션이 아직 시작되지 않았다면 세션 시작
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 세션 시간 설정
define('SESSION_TIMEOUT', 3600); // 1시간 (초 단위)

/**
 * 세션을 초기화합니다.
 * 
 * 이 함수는 세션 변수를 모두
 * 제거하고 세션을 재시작합니다.
 * 
 * @return void
 */
function initSession() {
    // 기존 세션 데이터 삭제
    $_SESSION = [];
    
    // 세션 쿠키 삭제
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
    
    // 세션 파괴
    session_destroy();
    
    // 새로운 세션 시작
    session_start();
    
    // CSRF 토큰 초기화
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    
    // 세션 시작 시간 설정
    $_SESSION['session_start'] = time();
    $_SESSION['last_activity'] = time();
}

/**
 * 사용자 로그인 상태를 세션에 설정합니다.
 * 
 * @param int $userId 사용자 ID
 * @param string $username 사용자명
 * @param string $email 이메일
 * @param string $role 역할
 * @param array $extraData 추가 세션 데이터 (선택 사항)
 * @return void
 */
function setUserSession($userId, $username, $email, $role, $extraData = []) {
    $_SESSION['user_id'] = $userId;
    $_SESSION['username'] = $username;
    $_SESSION['email'] = $email;
    $_SESSION['user_role'] = $role;
    $_SESSION['logged_in'] = true;
    $_SESSION['last_activity'] = time();
    
    // 추가 데이터가 있으면 세션에 추가
    if (!empty($extraData) && is_array($extraData)) {
        foreach ($extraData as $key => $value) {
            $_SESSION[$key] = $value;
        }
    }
    
    // 로그인 시간 기록
    $_SESSION['login_time'] = time();
}

/**
 * 로그인한 사용자의 세션을 종료합니다.
 * 
 * @return void
 */
function endUserSession() {
    // 로그아웃 전 사용자 ID 저장 (로그용)
    $userId = $_SESSION['user_id'] ?? null;
    
    // 세션 초기화
    initSession();
    
    // 로그아웃 메시지 저장
    if ($userId) {
        $_SESSION['logout_message'] = '로그아웃 되었습니다.';
        
        // 로그 기록 (함수가 있다면)
        if (function_exists('logInfo')) {
            logInfo("User ID $userId logged out", 'auth');
        }
    }
}

/**
 * 현재 사용자가 로그인되어 있는지 확인합니다.
 * 
 * @return bool 로그인 여부
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

/**
 * 세션 유효 시간이 만료되었는지 확인합니다.
 * 
 * @return bool 만료 여부
 */
function isSessionExpired() {
    if (!isset($_SESSION['last_activity'])) {
        return true;
    }
    
    $currentTime = time();
    $lastActivity = $_SESSION['last_activity'];
    
    return ($currentTime - $lastActivity) > SESSION_TIMEOUT;
}

/**
 * 세션 활동 시간을 갱신합니다.
 * 
 * @return void
 */
function updateSessionActivity() {
    $_SESSION['last_activity'] = time();
}

/**
 * 사용자가 로그인되어 있는지 확인하고, 
 * 로그인되어 있지 않으면 로그인 페이지로 리다이렉트합니다.
 * 
 * @param bool $checkExpiration 세션 만료 여부도 확인할지 여부
 * @return void
 */
function requireLogin($checkExpiration = true) {
    // 로그인 되어 있지 않은 경우
    if (!isLoggedIn()) {
        // 현재 요청 URL 저장
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        
        // 로그인 페이지로 리다이렉트
        header('Location: ' . getLoginUrl());
        exit;
    }
    
    // 세션 만료 여부 확인이 필요한 경우
    if ($checkExpiration && isSessionExpired()) {
        // 세션 종료
        endUserSession();
        
        // 현재 요청 URL 저장
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        $_SESSION['login_message'] = '세션이 만료되었습니다. 다시 로그인해주세요.';
        
        // 로그인 페이지로 리다이렉트
        header('Location: ' . getLoginUrl());
        exit;
    }
    
    // 세션 활동 시간 갱신
    updateSessionActivity();
}

/**
 * 로그인 페이지 URL을 반환합니다.
 * 
 * @return string 로그인 페이지 URL
 */
function getLoginUrl() {
    return '/server/pages/login.php';
}

/**
 * 사용자 역할이 특정 역할과 일치하는지 확인합니다.
 * 
 * @param string $role 확인할 역할
 * @return bool 역할 일치 여부
 */
function hasRole($role) {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
}

/**
 * 현재 사용자가 관리자인지 확인합니다.
 * 
 * @return bool 관리자 여부
 */
function isAdmin() {
    return hasRole('admin');
}

/**
 * 현재 사용자가 에이전트(판매점)인지 확인합니다.
 * 
 * @return bool 에이전트 여부
 */
function isAgent() {
    return hasRole('agent');
}

/**
 * 현재 사용자가 재무 담당자인지 확인합니다.
 * 
 * @return bool 재무 담당자 여부
 */
function isFinance() {
    return hasRole('finance');
}

/**
 * 현재 사용자가 판매원인지 확인합니다.
 * 
 * @return bool 판매원 여부
 */
function isStore() {
    return hasRole('store');
}

/**
 * 현재 사용자가 특정 권한을 가지고 있는지 확인합니다.
 * 
 * @param string|array $permissions 확인할 권한 또는 권한 배열
 * @param bool $requireAll 모든 권한이 필요한지 여부 (기본값: false)
 * @return bool 권한 보유 여부
 */
function hasPermission($permissions, $requireAll = false) {
    // 개발 모드일 때 모든 권한 부여 (설정 파일에 정의된 경우)
    if (defined('DEVELOPMENT_MODE') && DEVELOPMENT_MODE === true) {
        return true;
    }
    
    // 로그인되지 않은 경우 권한 없음
    if (!isLoggedIn()) {
        return false;
    }
    
    // 관리자는 항상 모든 권한 있음
    if (isAdmin()) {
        return true;
    }
    
    // 권한이 문자열 하나인 경우 배열로 변환
    if (!is_array($permissions)) {
        $permissions = [$permissions];
    }
    
    // 사용자 권한 목록이 세션에 없는 경우 데이터베이스에서 가져옴
    if (!isset($_SESSION['user_permissions']) || !is_array($_SESSION['user_permissions'])) {
        $_SESSION['user_permissions'] = getUserPermissions($_SESSION['user_id']);
    }
    
    // 권한 확인
    if ($requireAll) {
        // 모든 권한이 필요한 경우
        foreach ($permissions as $permission) {
            if (!in_array($permission, $_SESSION['user_permissions'])) {
                return false;
            }
        }
        return true;
    } else {
        // 하나라도 있으면 되는 경우
        foreach ($permissions as $permission) {
            if (in_array($permission, $_SESSION['user_permissions'])) {
                return true;
            }
        }
        return false;
    }
}

/**
 * 사용자의 권한 목록을 가져옵니다.
 * 실제 구현에서는 데이터베이스에서 권한을 조회해야 합니다.
 * 
 * @param int $userId 사용자 ID
 * @return array 권한 목록
 */
function getUserPermissions($userId) {
    // 개발 중에는 더미 권한 목록 반환
    // 실제 구현에서는 데이터베이스에서 조회해야 함
    $dummyPermissions = [
        'dashboard_view',
        'lottery_management',
        'sales_management',
        'prize_management',
        'customer_management',
        'store_management',
        'finance_reports',
        'finance_management'
    ];
    
    return $dummyPermissions;
    
    /*
    // 실제 구현 예시 (비활성화)
    global $conn;
    
    $sql = "SELECT p.permission_name 
            FROM user_permissions up
            JOIN permissions p ON up.permission_id = p.id
            WHERE up.user_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $permissions = [];
    while ($row = $result->fetch_assoc()) {
        $permissions[] = $row['permission_name'];
    }
    
    return $permissions;
    */
}

/**
 * 사용자가 특정 권한을 가지고 있는지 확인하고, 
 * 권한이 없으면 접근 거부 페이지로 리다이렉트합니다.
 * 
 * @param string|array $permissions 필요한 권한 또는 권한 배열
 * @param bool $requireAll 모든 권한이 필요한지 여부 (기본값: false)
 * @return void
 */
function requirePermission($permissions, $requireAll = false) {
    // 먼저 로그인 확인
    requireLogin();
    
    // 권한 확인
    if (!hasPermission($permissions, $requireAll)) {
        // 접근 거부 페이지로 리다이렉트
        header('Location: /server/pages/access-denied.php');
        exit;
    }
}

/**
 * CSRF 토큰을 생성하고 반환합니다.
 * 토큰이 이미 세션에 있으면 기존 토큰을 반환합니다.
 * 
 * @return string CSRF 토큰
 */
function getCsrfToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    return $_SESSION['csrf_token'];
}

/**
 * CSRF 토큰의 유효성을 검사합니다.
 * 
 * @param string $token 검사할 CSRF 토큰
 * @return bool 유효 여부
 */
function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * 현재 로그인한 사용자의 ID를 반환합니다.
 * 
 * @return int|null 사용자 ID 또는 로그인하지 않았으면 null
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * 플래시 메시지를 세션에 설정합니다.
 * 
 * @param string $message 메시지 내용
 * @param string $type 메시지 유형 (success, error, info, warning)
 * @return void
 */
function setFlashMessage($message, $type = 'info') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}

/**
 * 플래시 메시지를 가져오고 세션에서 제거합니다.
 * 
 * @return array|null 메시지 배열 또는 메시지가 없으면 null
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = [
            'message' => $_SESSION['flash_message'],
            'type' => $_SESSION['flash_type'] ?? 'info'
        ];
        
        // 메시지를 가져온 후 세션에서 제거
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
        
        return $message;
    }
    
    return null;
}

/**
 * 플래시 메시지를 HTML로 렌더링합니다.
 * 
 * @return string 렌더링된 HTML 또는 메시지가 없으면 빈 문자열
 */
function renderFlashMessage() {
    $flash = getFlashMessage();
    
    if ($flash) {
        $type = htmlspecialchars($flash['type']);
        $message = htmlspecialchars($flash['message']);
        
        return <<<HTML
        <div class="alert alert-{$type} alert-dismissible fade show">
            {$message}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        HTML;
    }
    
    return '';
}

/**
 * 페이지를 다른 URL로 리다이렉트합니다.
 * 
 * @param string $url 리다이렉트할 URL
 * @param string $message 리다이렉트 후 표시할 메시지 (선택 사항)
 * @param string $type 메시지 유형 (success, error, info, warning) (선택 사항)
 * @return void
 */
function redirect($url, $message = null, $type = 'info') {
    if ($message !== null) {
        setFlashMessage($message, $type);
    }
    
    header("Location: $url");
    exit;
}

// 별칭 함수 - 스네이크 케이스 버전 (호환성을 위해)
function is_logged_in() { return isLoggedIn(); }
function is_session_expired() { return isSessionExpired(); }
function update_session_activity() { updateSessionActivity(); }
function require_login($checkExpiration = true) { requireLogin($checkExpiration); }
function get_login_url() { return getLoginUrl(); }
function has_role($role) { return hasRole($role); }
function is_admin() { return isAdmin(); }
function is_agent() { return isAgent(); }
function has_permission($permissions, $requireAll = false) { return hasPermission($permissions, $requireAll); }
function require_permission($permissions, $requireAll = false) { requirePermission($permissions, $requireAll); }
function get_csrf_token() { return getCsrfToken(); }
function verify_csrf_token($token) { return verifyCsrfToken($token); }
function get_current_user_id() { return getCurrentUserId(); }
function set_flash_message($message, $type = 'info') { setFlashMessage($message, $type); }
function get_flash_message() { return getFlashMessage(); }
function render_flash_message() { return renderFlashMessage(); }

// 기존 사용 중인 함수명 호환성
function checkAuth() { requireLogin(); }
function check_auth() { requireLogin(); }
function checkPermissions($permissions) { requirePermission($permissions); }

// 테스트용 샘플 사용자 세션 설정 (개발 중에만 사용)
if (defined('DEVELOPMENT_MODE') && DEVELOPMENT_MODE === true && !isLoggedIn()) {
    setUserSession(1, 'admin', 'admin@example.com', 'admin');
}
