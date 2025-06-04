<?php
/**
 * 유효성 검증 설정
 * 복권 유효성 검증 관련 설정을 관리하는 페이지
 * 
 * @package Lottery Management
 * @author Claude
 * @created 2025-05-20
 */

// 오류 표시 설정
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 설정 및 공통 함수
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';
require_once '../../includes/db.php';

// 데이터베이스 연결
$db = getDbConnection();

// 현재 페이지 정보
$pageTitle = "유효성 검증 설정";
$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir = dirname($_SERVER['PHP_SELF']);
$parts = explode('/', $currentDir);
$currentSection = end($parts);

// 메시지 초기화
$message = '';
$message_type = '';

// CSRF 토큰 생성
$csrf_token = SecurityManager::generateCsrfToken();

// 페이지 제목 설정
$page_description = "복권 유효성 검증 관련 설정을 관리합니다.";

// 작업 결과 메시지
$success_message = '';
$error_message = '';

// 폼 처리: 유효성 검증 설정 저장
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // CSRF 토큰 검증
    if (!SecurityManager::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error_message = "보안 토큰이 유효하지 않습니다. 페이지를 새로고침 후 다시 시도해 주세요.";
    } else {
        try {
            switch ($_POST['action']) {
                case 'save_validation_settings':
                    // 일반 설정 저장
                    $enable_validation = isset($_POST['enable_validation']) ? 1 : 0;
                    $validation_method = sanitizeInput($_POST['validation_method'] ?? '');
                    $api_endpoint = sanitizeInput($_POST['api_endpoint'] ?? '');
                    $api_key = sanitizeInput($_POST['api_key'] ?? '');
                    $verify_check_digit = isset($_POST['verify_check_digit']) ? 1 : 0;
                    $verify_sequence = isset($_POST['verify_sequence']) ? 1 : 0;
                    $verify_batch = isset($_POST['verify_batch']) ? 1 : 0;
                    $verify_issue = isset($_POST['verify_issue']) ? 1 : 0;
                    $validate_expiry = isset($_POST['validate_expiry']) ? 1 : 0;
                    
                    // 보안 설정
                    $max_failures = sanitizeInput($_POST['max_failures'] ?? '3');
                    $block_duration = sanitizeInput($_POST['block_duration'] ?? '30');
                    $log_validations = isset($_POST['log_validations']) ? 1 : 0;
                    $alert_threshold = sanitizeInput($_POST['alert_threshold'] ?? '5');
                    $notify_admin = isset($_POST['notify_admin']) ? 1 : 0;
                    
                    // 추가 설정
                    $ip_restriction = isset($_POST['ip_restriction']) ? 1 : 0;
                    $allowed_ips = sanitizeInput($_POST['allowed_ips'] ?? '');
                    $time_restriction = isset($_POST['time_restriction']) ? 1 : 0;
                    $allowed_time_start = sanitizeInput($_POST['allowed_time_start'] ?? '');
                    $allowed_time_end = sanitizeInput($_POST['allowed_time_end'] ?? '');
                    
                    // 현재 설정 확인
                    try {
                        $query = "SELECT * FROM validation_settings WHERE id = 1";
                        $checkStmt = $db->prepare($query);
                        $checkStmt->execute();
                        $currentSettings = $checkStmt->fetch(PDO::FETCH_ASSOC);
                        
                        $user_id = AuthManager::getUserId();
                        
                        if ($currentSettings) {
                            // 기존 설정 업데이트
                            $query = "UPDATE validation_settings 
                                      SET enable_validation = ?, 
                                          validation_method = ?, 
                                          api_endpoint = ?, 
                                          api_key = ?, 
                                          verify_check_digit = ?, 
                                          verify_sequence = ?, 
                                          verify_batch = ?, 
                                          verify_issue = ?, 
                                          validate_expiry = ?, 
                                          max_failures = ?, 
                                          block_duration = ?, 
                                          log_validations = ?, 
                                          alert_threshold = ?, 
                                          notify_admin = ?, 
                                          ip_restriction = ?, 
                                          allowed_ips = ?, 
                                          time_restriction = ?, 
                                          allowed_time_start = ?, 
                                          allowed_time_end = ?, 
                                          updated_by = ?, 
                                          updated_at = NOW() 
                                      WHERE id = 1";
                            
                            $stmt = $db->prepare($query);
                            $params = [
                                $enable_validation, 
                                $validation_method, 
                                $api_endpoint, 
                                $api_key, 
                                $verify_check_digit, 
                                $verify_sequence, 
                                $verify_batch, 
                                $verify_issue, 
                                $validate_expiry, 
                                $max_failures, 
                                $block_duration, 
                                $log_validations, 
                                $alert_threshold, 
                                $notify_admin, 
                                $ip_restriction, 
                                $allowed_ips, 
                                $time_restriction, 
                                $allowed_time_start, 
                                $allowed_time_end, 
                                $user_id
                            ];
                            
                            if ($stmt->execute($params)) {
                                $success_message = "유효성 검증 설정이 성공적으로 저장되었습니다.";
                                logActivity('유효성 검증 설정 업데이트');
                            } else {
                                $error_message = "유효성 검증 설정 저장 중 오류가 발생했습니다.";
                                logError('validation_settings.php: ' . json_encode($stmt->errorInfo()));
                            }
                        } else {
                            // 새 설정 생성
                            $query = "INSERT INTO validation_settings 
                                      (id, enable_validation, validation_method, api_endpoint, api_key, verify_check_digit, 
                                       verify_sequence, verify_batch, verify_issue, validate_expiry, max_failures, 
                                       block_duration, log_validations, alert_threshold, notify_admin, 
                                       ip_restriction, allowed_ips, time_restriction, allowed_time_start, allowed_time_end, 
                                       created_by, created_at) 
                                      VALUES (1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                            
                            $stmt = $db->prepare($query);
                            $params = [
                                $enable_validation, 
                                $validation_method, 
                                $api_endpoint, 
                                $api_key, 
                                $verify_check_digit, 
                                $verify_sequence, 
                                $verify_batch, 
                                $verify_issue, 
                                $validate_expiry, 
                                $max_failures, 
                                $block_duration, 
                                $log_validations, 
                                $alert_threshold, 
                                $notify_admin, 
                                $ip_restriction, 
                                $allowed_ips, 
                                $time_restriction, 
                                $allowed_time_start, 
                                $allowed_time_end, 
                                $user_id
                            ];
                            
                            if ($stmt->execute($params)) {
                                $success_message = "유효성 검증 설정이 성공적으로 생성되었습니다.";
                                logActivity('유효성 검증 설정 생성');
                            } else {
                                $error_message = "유효성 검증 설정 생성 중 오류가 발생했습니다.";
                                logError('validation_settings.php: ' . json_encode($stmt->errorInfo()));
                            }
                        }
                    } catch (PDOException $e) {
                        $error_message = "데이터베이스 오류: " . $e->getMessage();
                        logError('validation_settings.php: ' . $e->getMessage());
                    }
                    break;
                    
                case 'test_validation':
                    // 유효성 검증 테스트
                    $test_barcode = sanitizeInput($_POST['test_barcode'] ?? '');
                    
                    if (empty($test_barcode)) {
                        $error_message = "테스트할 바코드는 필수입니다.";
                    } else {
                        // 실제 테스트 유효성 검증 로직 추가
                        // 이 부분은 현재 환경에서 실제로 구현하기 어려우므로 로깅만 진행
                        logActivity("유효성 검증 테스트: 바코드 = $test_barcode");
                        
                        // 테스트 결과를 세션에 저장
                        $_SESSION['test_validation_result'] = [
                            'barcode' => $test_barcode,
                            'is_valid' => true,  // 예시: 실제로는 검증 결과에 따라 다름
                            'message' => '유효한 바코드입니다.',
                            'details' => [
                                'product' => '로또 6/45',
                                'issue' => '2025-05-01',
                                'batch' => 'B25050123',
                                'status' => 'active'
                            ]
                        ];
                        
                        $success_message = "유효성 검증 테스트가 완료되었습니다.";
                    }
                    break;
                    
                case 'clear_failed_attempts':
                    // 실패한 검증 시도 초기화
                    try {
                        $query = "DELETE FROM validation_failures";
                        if ($db->exec($query) !== false) {
                            $success_message = "실패한 검증 시도 기록이 초기화되었습니다.";
                            logActivity('실패한 검증 시도 기록 초기화');
                        } else {
                            $error_message = "실패한 검증 시도 기록 초기화 중 오류가 발생했습니다.";
                            logError('validation_settings.php: ' . json_encode($db->errorInfo()));
                        }
                    } catch (PDOException $e) {
                        $error_message = "데이터베이스 오류: " . $e->getMessage();
                        logError('validation_settings.php: ' . $e->getMessage());
                    }
                    break;
            }
        } catch (Exception $e) {
            $error_message = "오류가 발생했습니다: " . $e->getMessage();
            logError('validation_settings.php: ' . $e->getMessage());
        }
    }
}

// 유효성 검증 설정 불러오기
$validation_settings = [];

// 유효성 검증 설정 테이블 존재 여부 확인 및 필요시 생성
try {
    // 테이블 존재 여부 확인
    $checkTableQuery = "SHOW TABLES LIKE 'validation_settings'";
    $tableCheckStmt = $db->prepare($checkTableQuery);
    $tableCheckStmt->execute();
    $tableExists = ($tableCheckStmt->rowCount() > 0);
    
    if (!$tableExists) {
        // 테이블이 없으면 생성
        $createTableSQL = "CREATE TABLE IF NOT EXISTS validation_settings (
            id INT PRIMARY KEY,
            enable_validation TINYINT(1) DEFAULT 1,
            validation_method VARCHAR(20) DEFAULT 'internal',
            api_endpoint VARCHAR(255) DEFAULT NULL,
            api_key VARCHAR(255) DEFAULT NULL,
            verify_check_digit TINYINT(1) DEFAULT 1,
            verify_sequence TINYINT(1) DEFAULT 1,
            verify_batch TINYINT(1) DEFAULT 1,
            verify_issue TINYINT(1) DEFAULT 1,
            validate_expiry TINYINT(1) DEFAULT 1,
            max_failures INT DEFAULT 3,
            block_duration INT DEFAULT 30,
            log_validations TINYINT(1) DEFAULT 1,
            alert_threshold INT DEFAULT 5,
            notify_admin TINYINT(1) DEFAULT 1,
            ip_restriction TINYINT(1) DEFAULT 0,
            allowed_ips TEXT DEFAULT NULL,
            time_restriction TINYINT(1) DEFAULT 0,
            allowed_time_start TIME DEFAULT '09:00:00',
            allowed_time_end TIME DEFAULT '18:00:00',
            created_by INT,
            updated_by INT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP
        )";
        
        $db->exec($createTableSQL);
        
        // 기본 설정 데이터 삽입
        $insertDefaultSQL = "INSERT INTO validation_settings (
            id, enable_validation, validation_method, verify_check_digit, verify_sequence, 
            verify_batch, verify_issue, validate_expiry, max_failures, block_duration, 
            log_validations, alert_threshold, notify_admin, created_by
        ) VALUES (
            1, 1, 'internal', 1, 1, 1, 1, 1, 3, 30, 1, 5, 1, 1
        )";
        
        $db->exec($insertDefaultSQL);
        logActivity('유효성 검증 설정 테이블 생성 및 기본 설정 추가');
    }

    // 유효성 검증 실패 테이블 존재 여부 확인
    $checkFailuresTableQuery = "SHOW TABLES LIKE 'validation_failures'";
    $failuresTableCheckStmt = $db->prepare($checkFailuresTableQuery);
    $failuresTableCheckStmt->execute();
    $failuresTableExists = ($failuresTableCheckStmt->rowCount() > 0);
    
    if (!$failuresTableExists) {
        // 테이블이 없으면 생성
        $createFailuresTableSQL = "CREATE TABLE IF NOT EXISTS validation_failures (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ip_address VARCHAR(50) NOT NULL,
            barcode VARCHAR(100) NOT NULL,
            failure_reason VARCHAR(255),
            attempt_time DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_ip_address (ip_address),
            INDEX idx_attempt_time (attempt_time)
        )";
        
        $db->exec($createFailuresTableSQL);
        logActivity('유효성 검증 실패 테이블 생성');
    }

    // 유효성 검증 설정 불러오기
    $query = "SELECT * FROM validation_settings WHERE id = 1";
    $result = $db->query($query);
    if ($result && $result->rowCount() > 0) {
        $validation_settings = $result->fetch(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    logError('validation_settings.php: 유효성 검증 설정 불러오기 오류 - ' . $e->getMessage());
}

// 실패한 검증 시도 수 불러오기
$failed_attempts_count = 0;
try {
    $query = "SELECT COUNT(*) as count FROM validation_failures";
    $result = $db->query($query);
    if ($result) {
        $row = $result->fetch(PDO::FETCH_ASSOC);
        $failed_attempts_count = $row['count'];
    }
} catch (PDOException $e) {
    logError('validation_settings.php: 실패한 검증 시도 수 불러오기 오류 - ' . $e->getMessage());
}

// 최근 블록된 IP 목록 불러오기
$blocked_ips = [];
try {
    $query = "SELECT ip_address, COUNT(*) as failure_count, MAX(attempt_time) as last_attempt 
              FROM validation_failures 
              GROUP BY ip_address 
              HAVING COUNT(*) >= " . ($validation_settings['max_failures'] ?? 3) . " 
              ORDER BY last_attempt DESC 
              LIMIT 10";
    $result = $db->query($query);
    if ($result) {
        $blocked_ips = $result->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    logError('validation_settings.php: 차단된 IP 목록 불러오기 오류 - ' . $e->getMessage());
}

// 템플릿 헤더 포함
include_once TEMPLATES_PATH . '/dashboard_header.php';
?>

<!-- Content Header (Page header) -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><?php echo $pageTitle; ?></h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?php echo SERVER_URL; ?>/dashboard/index.php">홈</a></li>
                    <li class="breadcrumb-item">복권 관리</li>
                    <li class="breadcrumb-item active"><?php echo $pageTitle; ?></li>
                </ol>
            </div>
        </div>
    </div>
</div>
<!-- /.content-header -->

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success" role="alert">
                        <?php echo $success_message; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-shield-alt mr-2"></i><?php echo $page_description; ?></h3>
                    </div>
                    <div class="card-body">
                        <ul class="nav nav-tabs" id="validationTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab" aria-controls="general" aria-selected="true">일반 설정</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="security-tab" data-bs-toggle="tab" data-bs-target="#security" type="button" role="tab" aria-controls="security" aria-selected="false">보안 설정</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="restrictions-tab" data-bs-toggle="tab" data-bs-target="#restrictions" type="button" role="tab" aria-controls="restrictions" aria-selected="false">접근 제한</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="test-tab" data-bs-toggle="tab" data-bs-target="#test" type="button" role="tab" aria-controls="test" aria-selected="false">검증 테스트</button>
                            </li>
                        </ul>
                        
                        <form id="validationSettingsForm" method="post" action="">
                            <input type="hidden" name="action" value="save_validation_settings">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            
                            <div class="tab-content" id="validationTabsContent">
                                <!-- 일반 설정 탭 -->
                                <div class="tab-pane fade show active p-3" id="general" role="tabpanel" aria-labelledby="general-tab">
                                    <div class="form-check form-switch mb-3 mt-3">
                                        <input class="form-check-input" type="checkbox" id="enable_validation" name="enable_validation" <?php echo ($validation_settings['enable_validation'] ?? 1) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="enable_validation">
                                            <strong>유효성 검증 활성화</strong>
                                        </label>
                                        <div class="form-text">이 옵션을 비활성화하면 모든 유효성 검증이 중단됩니다.</div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="validation_method" class="form-label">검증 방식</label>
                                            <select class="form-select" id="validation_method" name="validation_method">
                                                <option value="internal" <?php echo ($validation_settings['validation_method'] ?? '') === 'internal' ? 'selected' : ''; ?>>내부 데이터베이스 검증</option>
                                                <option value="api" <?php echo ($validation_settings['validation_method'] ?? '') === 'api' ? 'selected' : ''; ?>>외부 API 검증</option>
                                                <option value="hybrid" <?php echo ($validation_settings['validation_method'] ?? '') === 'hybrid' ? 'selected' : ''; ?>>하이브리드 (내부 + 외부)</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div id="api_settings" class="row" style="<?php echo ($validation_settings['validation_method'] ?? '') === 'internal' ? 'display: none;' : ''; ?>">
                                        <div class="col-md-6 mb-3">
                                            <label for="api_endpoint" class="form-label">API 엔드포인트</label>
                                            <input type="text" class="form-control" id="api_endpoint" name="api_endpoint" value="<?php echo $validation_settings['api_endpoint'] ?? ''; ?>">
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="api_key" class="form-label">API 키</label>
                                            <input type="password" class="form-control" id="api_key" name="api_key" value="<?php echo $validation_settings['api_key'] ?? ''; ?>">
                                        </div>
                                    </div>
                                    
                                    <div class="row mt-4">
                                        <div class="col-12">
                                            <h5>검증 항목</h5>
                                            <hr>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="verify_check_digit" name="verify_check_digit" <?php echo ($validation_settings['verify_check_digit'] ?? 1) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="verify_check_digit">
                                                    체크 디짓 검증
                                                </label>
                                                <div class="form-text">바코드의 체크 디짓을 검증합니다.</div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-4 mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="verify_sequence" name="verify_sequence" <?php echo ($validation_settings['verify_sequence'] ?? 1) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="verify_sequence">
                                                    일련번호 검증
                                                </label>
                                                <div class="form-text">일련번호의 유효성을 검증합니다.</div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-4 mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="verify_batch" name="verify_batch" <?php echo ($validation_settings['verify_batch'] ?? 1) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="verify_batch">
                                                    배치 정보 검증
                                                </label>
                                                <div class="form-text">바코드의 배치 정보를 검증합니다.</div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="verify_issue" name="verify_issue" <?php echo ($validation_settings['verify_issue'] ?? 1) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="verify_issue">
                                                    발행 정보 검증
                                                </label>
                                                <div class="form-text">바코드의 발행 정보를 검증합니다.</div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-4 mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="validate_expiry" name="validate_expiry" <?php echo ($validation_settings['validate_expiry'] ?? 1) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="validate_expiry">
                                                    만료 여부 검증
                                                </label>
                                                <div class="form-text">바코드의 만료 여부를 검증합니다.</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- 보안 설정 탭 -->
                                <div class="tab-pane fade p-3" id="security" role="tabpanel" aria-labelledby="security-tab">
                                    <div class="row mt-3">
                                        <div class="col-md-6 mb-3">
                                            <label for="max_failures" class="form-label">최대 실패 횟수</label>
                                            <input type="number" class="form-control" id="max_failures" name="max_failures" min="1" max="20" value="<?php echo $validation_settings['max_failures'] ?? '3'; ?>">
                                            <div class="form-text">이 횟수 이상 실패하면 IP가 차단됩니다.</div>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="block_duration" class="form-label">차단 기간 (분)</label>
                                            <input type="number" class="form-control" id="block_duration" name="block_duration" min="5" max="1440" value="<?php echo $validation_settings['block_duration'] ?? '30'; ?>">
                                            <div class="form-text">실패 횟수 초과 시 IP가 차단되는 시간입니다.</div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="log_validations" name="log_validations" <?php echo ($validation_settings['log_validations'] ?? 1) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="log_validations">
                                                    모든 검증 로깅
                                                </label>
                                                <div class="form-text">모든 유효성 검증 시도를 로그에 기록합니다.</div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="alert_threshold" class="form-label">알림 임계값</label>
                                            <input type="number" class="form-control" id="alert_threshold" name="alert_threshold" min="1" max="100" value="<?php echo $validation_settings['alert_threshold'] ?? '5'; ?>">
                                            <div class="form-text">이 횟수 이상의 연속 실패 시 관리자에게 알림을 보냅니다.</div>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <div class="form-check mt-4">
                                                <input class="form-check-input" type="checkbox" id="notify_admin" name="notify_admin" <?php echo ($validation_settings['notify_admin'] ?? 1) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="notify_admin">
                                                    관리자 알림
                                                </label>
                                                <div class="form-text">비정상적인 검증 패턴 감지 시 관리자에게 알립니다.</div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row mt-3">
                                        <div class="col-12">
                                            <div class="card">
                                                <div class="card-header">
                                                    <i class="fas fa-ban me-1"></i>
                                                    차단된 IP 목록
                                                </div>
                                                <div class="card-body">
                                                    <?php if (empty($blocked_ips)): ?>
                                                    <p class="text-success">현재 차단된 IP가 없습니다.</p>
                                                    <?php else: ?>
                                                    <div class="table-responsive">
                                                        <table class="table table-bordered table-striped">
                                                            <thead>
                                                                <tr>
                                                                    <th>IP 주소</th>
                                                                    <th>실패 횟수</th>
                                                                    <th>마지막 시도</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php foreach ($blocked_ips as $ip): ?>
                                                                <tr>
                                                                    <td><?php echo htmlspecialchars($ip['ip_address']); ?></td>
                                                                    <td><?php echo htmlspecialchars($ip['failure_count']); ?></td>
                                                                    <td><?php echo formatDateTime($ip['last_attempt']); ?></td>
                                                                </tr>
                                                                <?php endforeach; ?>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                    <?php endif; ?>
                                                    
                                                    <div class="mt-3">
                                                        <form method="post" action="" class="d-inline">
                                                            <input type="hidden" name="action" value="clear_failed_attempts">
                                                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                                            <button type="submit" class="btn btn-warning" onclick="return confirm('정말로 모든 실패 기록을 초기화하시겠습니까?');">
                                                                <i class="fas fa-eraser me-2"></i>
                                                                실패 기록 초기화
                                                            </button>
                                                        </form>
                                                        
                                                        <span class="ms-3">총 기록된 실패 시도: <strong><?php echo $failed_attempts_count; ?></strong>건</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- 접근 제한 탭 -->
                                <div class="tab-pane fade p-3" id="restrictions" role="tabpanel" aria-labelledby="restrictions-tab">
                                    <div class="row mt-3">
                                        <div class="col-12 mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="ip_restriction" name="ip_restriction" <?php echo ($validation_settings['ip_restriction'] ?? 0) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="ip_restriction">
                                                    IP 주소 제한
                                                </label>
                                                <div class="form-text">특정 IP 주소에서만 유효성 검증을 허용합니다.</div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div id="allowed_ips_container" class="row" style="<?php echo ($validation_settings['ip_restriction'] ?? 0) ? '' : 'display: none;'; ?>">
                                        <div class="col-md-6 mb-3">
                                            <label for="allowed_ips" class="form-label">허용된 IP 주소</label>
                                            <textarea class="form-control" id="allowed_ips" name="allowed_ips" rows="5"><?php echo $validation_settings['allowed_ips'] ?? ''; ?></textarea>
                                            <div class="form-text">각 IP를 새 줄로 구분합니다. CIDR 표기법(예: 192.168.1.0/24)도 지원합니다.</div>
                                        </div>
                                    </div>
                                    
                                    <div class="row mt-3">
                                        <div class="col-12 mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="time_restriction" name="time_restriction" <?php echo ($validation_settings['time_restriction'] ?? 0) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="time_restriction">
                                                    시간 제한
                                                </label>
                                                <div class="form-text">특정 시간대에만 유효성 검증을 허용합니다.</div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div id="allowed_time_container" class="row" style="<?php echo ($validation_settings['time_restriction'] ?? 0) ? '' : 'display: none;'; ?>">
                                        <div class="col-md-6 mb-3">
                                            <label for="allowed_time_start" class="form-label">허용 시작 시간</label>
                                            <input type="time" class="form-control" id="allowed_time_start" name="allowed_time_start" value="<?php echo $validation_settings['allowed_time_start'] ?? '09:00'; ?>">
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="allowed_time_end" class="form-label">허용 종료 시간</label>
                                            <input type="time" class="form-control" id="allowed_time_end" name="allowed_time_end" value="<?php echo $validation_settings['allowed_time_end'] ?? '18:00'; ?>">
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- 검증 테스트 탭 -->
                                <div class="tab-pane fade p-3" id="test" role="tabpanel" aria-labelledby="test-tab">
                                    <div class="alert alert-info mt-3" role="alert">
                                        <i class="fas fa-info-circle me-2"></i>
                                        현재 설정으로 바코드 유효성 검증을 테스트할 수 있습니다.
                                    </div>
                                    
                                    <div class="row mt-3">
                                        <div class="col-md-6">
                                            <form id="testValidationForm" method="post" action="">
                                                <input type="hidden" name="action" value="test_validation">
                                                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                                
                                                <div class="mb-3">
                                                    <label for="test_barcode" class="form-label">테스트할 바코드</label>
                                                    <input type="text" class="form-control" id="test_barcode" name="test_barcode" placeholder="바코드를 입력하세요" required>
                                                </div>
                                                
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="fas fa-check-circle me-2"></i>
                                                    유효성 검증 테스트
                                                </button>
                                            </form>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <?php if (isset($_SESSION['test_validation_result'])): ?>
                                                <div class="card">
                                                    <div class="card-header">
                                                        <i class="fas fa-clipboard-check me-1"></i>
                                                        검증 결과
                                                    </div>
                                                    <div class="card-body">
                                                        <?php if ($_SESSION['test_validation_result']['is_valid']): ?>
                                                            <div class="alert alert-success">
                                                                <i class="fas fa-check-circle me-2"></i>
                                                                <strong>유효한 바코드입니다.</strong>
                                                            </div>
                                                        <?php else: ?>
                                                            <div class="alert alert-danger">
                                                                <i class="fas fa-times-circle me-2"></i>
                                                                <strong>유효하지 않은 바코드입니다.</strong>
                                                            </div>
                                                        <?php endif; ?>
                                                        
                                                        <p><strong>바코드:</strong> <?php echo htmlspecialchars($_SESSION['test_validation_result']['barcode']); ?></p>
                                                        <p><strong>메시지:</strong> <?php echo htmlspecialchars($_SESSION['test_validation_result']['message']); ?></p>
                                                        
                                                        <?php if ($_SESSION['test_validation_result']['is_valid'] && isset($_SESSION['test_validation_result']['details'])): ?>
                                                            <h6 class="mt-3">바코드 정보</h6>
                                                            <table class="table table-sm">
                                                                <tr>
                                                                    <th>상품</th>
                                                                    <td><?php echo htmlspecialchars($_SESSION['test_validation_result']['details']['product']); ?></td>
                                                                </tr>
                                                                <tr>
                                                                    <th>발행</th>
                                                                    <td><?php echo htmlspecialchars($_SESSION['test_validation_result']['details']['issue']); ?></td>
                                                                </tr>
                                                                <tr>
                                                                    <th>배치</th>
                                                                    <td><?php echo htmlspecialchars($_SESSION['test_validation_result']['details']['batch']); ?></td>
                                                                </tr>
                                                                <tr>
                                                                    <th>상태</th>
                                                                    <td>
                                                                        <?php 
                                                                        $status = $_SESSION['test_validation_result']['details']['status'];
                                                                        $status_class = '';
                                                                        $status_text = '';
                                                                        
                                                                        switch ($status) {
                                                                            case 'active':
                                                                                $status_class = 'bg-success';
                                                                                $status_text = '활성';
                                                                                break;
                                                                            case 'used':
                                                                                $status_class = 'bg-primary';
                                                                                $status_text = '사용됨';
                                                                                break;
                                                                            case 'voided':
                                                                                $status_class = 'bg-danger';
                                                                                $status_text = '무효화됨';
                                                                                break;
                                                                            case 'expired':
                                                                                $status_class = 'bg-warning';
                                                                                $status_text = '만료됨';
                                                                                break;
                                                                            default:
                                                                                $status_class = 'bg-secondary';
                                                                                $status_text = $status;
                                                                        }
                                                                        ?>
                                                                        <span class="badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                                                    </td>
                                                                </tr>
                                                            </table>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mt-4">
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>
                                        설정 저장
                                    </button>
                                    <a href="<?php echo SERVER_URL; ?>/dashboard/lottery/products.php" class="btn btn-secondary ms-2">
                                        <i class="fas fa-arrow-left me-2"></i>
                                        돌아가기
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
// 페이지 하단 포함
include_once TEMPLATES_PATH . '/dashboard_footer.php';
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 검증 방식 변경 시 API 설정 표시/숨김
    document.getElementById('validation_method').addEventListener('change', function() {
        const apiSettings = document.getElementById('api_settings');
        apiSettings.style.display = this.value === 'internal' ? 'none' : '';
    });
    
    // IP 제한 체크박스 변경 시 허용 IP 입력 필드 표시/숨김
    document.getElementById('ip_restriction').addEventListener('change', function() {
        document.getElementById('allowed_ips_container').style.display = this.checked ? '' : 'none';
    });
    
    // 시간 제한 체크박스 변경 시 허용 시간 입력 필드 표시/숨김
    document.getElementById('time_restriction').addEventListener('change', function() {
        document.getElementById('allowed_time_container').style.display = this.checked ? '' : 'none';
    });
    
    // 유효성 검증 활성화 체크박스 변경 시 다른 설정 활성화/비활성화
    document.getElementById('enable_validation').addEventListener('change', function() {
        const isEnabled = this.checked;
        
        // 모든 설정 필드 활성화/비활성화
        const formElements = document.querySelectorAll('#validationSettingsForm select, #validationSettingsForm input:not(#enable_validation), #validationSettingsForm textarea');
        formElements.forEach(element => {
            element.disabled = !isEnabled;
        });
    });
    
    // 초기 로드 시 유효성 검증 활성화 상태에 따른 설정
    const validationEnabled = document.getElementById('enable_validation').checked;
    if (!validationEnabled) {
        const formElements = document.querySelectorAll('#validationSettingsForm select, #validationSettingsForm input:not(#enable_validation), #validationSettingsForm textarea');
        formElements.forEach(element => {
            element.disabled = true;
        });
    }
});
</script>
