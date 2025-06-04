<?php
/**
 * QR코드 관리
 * 복권 QR코드 생성 및 관리 페이지
 * 
 * @package Lottery Management
 * @author Claude
 * @created 2025-05-16
 */

// 설정 및 공통 함수
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';
require_once '../../includes/db.php';

// 데이터베이스 연결
$db = getDbConnection();

// 현재 페이지 정보
$pageTitle = "QR코드 관리";
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
// $page_title = "QR코드 관리"; // 중복 선언이므로 주석 처리
$page_description = "복권 QR코드 형식, 설정 및 생성 규칙을 관리합니다.";

// 데이터베이스 연결
$db = getDbConnection();

// 작업 결과 메시지
$success_message = '';
$error_message = '';

// 폼 처리: 바코드 설정 저장 또는 바코드 생성
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // CSRF 토큰 검증
    if (!SecurityManager::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error_message = "보안 토큰이 유효하지 않습니다. 페이지를 새로고침 후 다시 시도해 주세요.";
    } else {
        try {
            switch ($_POST['action']) {
                case 'save_barcode_settings':
                    // 바코드 설정 저장
                    $barcode_type = sanitizeInput($_POST['barcode_type'] ?? '');
                    $prefix = sanitizeInput($_POST['prefix'] ?? '');
                    $length = sanitizeInput($_POST['length'] ?? '');
                    $check_digit = isset($_POST['check_digit']) ? 1 : 0;
                    $encryption = isset($_POST['encryption']) ? 1 : 0;
                    $encryption_key = sanitizeInput($_POST['encryption_key'] ?? '');
                    
                    // 현재 설정 확인
                    $query = "SELECT * FROM lottery_barcode_settings WHERE id = 1";
                    try {
                        $checkStmt = $db->prepare($query);
                        $checkStmt->execute();
                        $currentSettings = $checkStmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($currentSettings) {
                            // 기존 설정 업데이트
                            $query = "UPDATE lottery_barcode_settings 
                                      SET barcode_type = ?, 
                                          prefix = ?, 
                                          length = ?, 
                                          check_digit = ?, 
                                          encryption = ?, 
                                          encryption_key = ?, 
                                          updated_by = ?, 
                                          updated_at = NOW() 
                                      WHERE id = 1";
                            
                            $stmt = $db->prepare($query);
                            $user_id = AuthManager::getUserId();
                            $stmt->bindParam(1, $barcode_type, PDO::PARAM_STR);
                            $stmt->bindParam(2, $prefix, PDO::PARAM_STR);
                            $stmt->bindParam(3, $length, PDO::PARAM_INT);
                            $stmt->bindParam(4, $check_digit, PDO::PARAM_INT);
                            $stmt->bindParam(5, $encryption, PDO::PARAM_INT);
                            $stmt->bindParam(6, $encryption_key, PDO::PARAM_STR);
                            $stmt->bindParam(7, $user_id, PDO::PARAM_INT);
                            
                            if ($stmt->execute()) {
                                $success_message = "바코드 설정이 성공적으로 저장되었습니다.";
                                logActivity('바코드 설정 업데이트');
                            } else {
                                $error_message = "바코드 설정 저장 중 오류가 발생했습니다.";
                                logError('barcode_management.php: ' . json_encode($stmt->errorInfo()));
                            }
                        } else {
                            // 새 설정 생성
                            $query = "INSERT INTO lottery_barcode_settings 
                                      (id, barcode_type, prefix, length, check_digit, encryption, encryption_key, created_by, created_at) 
                                      VALUES (1, ?, ?, ?, ?, ?, ?, ?, NOW())";
                            
                            $stmt = $db->prepare($query);
                            $user_id = AuthManager::getUserId();
                            $stmt->bindParam(1, $barcode_type, PDO::PARAM_STR);
                            $stmt->bindParam(2, $prefix, PDO::PARAM_STR);
                            $stmt->bindParam(3, $length, PDO::PARAM_INT);
                            $stmt->bindParam(4, $check_digit, PDO::PARAM_INT);
                            $stmt->bindParam(5, $encryption, PDO::PARAM_INT);
                            $stmt->bindParam(6, $encryption_key, PDO::PARAM_STR);
                            $stmt->bindParam(7, $user_id, PDO::PARAM_INT);
                            
                            if ($stmt->execute()) {
                                $success_message = "바코드 설정이 성공적으로 생성되었습니다.";
                                logActivity('바코드 설정 생성');
                            } else {
                                $error_message = "바코드 설정 생성 중 오류가 발생했습니다.";
                                logError('barcode_management.php: ' . json_encode($stmt->errorInfo()));
                            }
                        }
                    } catch (PDOException $e) {
                        $error_message = "데이터베이스 오류: " . $e->getMessage();
                        logError('barcode_management.php: ' . $e->getMessage());
                    }
                    break;
                    
                case 'generate_barcodes':
                    // 바코드 생성 처리
                    $lottery_product_id = sanitizeInput($_POST['lottery_product_id'] ?? '');
                    $issue_id = sanitizeInput($_POST['issue_id'] ?? '');
                    $batch_id = sanitizeInput($_POST['batch_id'] ?? '');
                    $quantity = sanitizeInput($_POST['quantity'] ?? '');
                    $start_number = sanitizeInput($_POST['start_number'] ?? '');
                    
                    if (empty($lottery_product_id) || empty($issue_id) || empty($quantity)) {
                        $error_message = "복권 상품, 발행 ID, 생성 수량은 필수입니다.";
                    } else {
                        try {
                            // 바코드 설정 불러오기
                            $query = "SELECT * FROM lottery_barcode_settings WHERE id = 1";
                            $settingsStmt = $db->prepare($query);
                            $settingsStmt->execute();
                            $barcode_settings = $settingsStmt->fetch(PDO::FETCH_ASSOC);
                            
                            if ($barcode_settings) {
                                // 바코드 생성 작업
                                // 실제로는 대량의 바코드를 생성하는 로직이 복잡하므로 여기서는 작업을 큐에 넣는 형태로 설계
                                $query = "INSERT INTO lottery_barcode_generation_tasks 
                                          (lottery_product_id, issue_id, batch_id, quantity, start_number, status, created_by, created_at) 
                                          VALUES (?, ?, ?, ?, ?, 'pending', ?, NOW())";
                                
                                $stmt = $db->prepare($query);
                                $user_id = AuthManager::getUserId();
                                $stmt->bindParam(1, $lottery_product_id, PDO::PARAM_INT);
                                $stmt->bindParam(2, $issue_id, PDO::PARAM_INT);
                                $stmt->bindParam(3, $batch_id, PDO::PARAM_INT);
                                $stmt->bindParam(4, $quantity, PDO::PARAM_INT);
                                $stmt->bindParam(5, $start_number, PDO::PARAM_STR);
                                $stmt->bindParam(6, $user_id, PDO::PARAM_INT);
                                
                                if ($stmt->execute()) {
                                    $task_id = $db->lastInsertId();
                                    $success_message = "바코드 생성 작업이 큐에 추가되었습니다. 작업 ID: " . $task_id;
                                    logActivity('바코드 생성 작업 추가: ' . $quantity . '개');
                                    
                                    // 여기에 실제 바코드 생성 작업을 시작하는 호출 추가 (백그라운드 작업 형태)
                                    // startBarcodeGenerationJob($task_id);
                                } else {
                                    $error_message = "바코드 생성 작업 추가 중 오류가 발생했습니다.";
                                    logError('barcode_management.php: ' . json_encode($stmt->errorInfo()));
                                }
                            } else {
                                $error_message = "바코드 설정을 찾을 수 없습니다. 먼저 바코드 설정을 저장해주세요.";
                            }
                        } catch (PDOException $e) {
                            $error_message = "데이터베이스 오류: " . $e->getMessage();
                            logError('barcode_management.php: ' . $e->getMessage());
                        }
                    }
                    break;
                    
                case 'cancel_task':
                    // 바코드 생성 작업 취소
                    $task_id = sanitizeInput($_POST['task_id'] ?? '');
                    
                    if (empty($task_id)) {
                        $error_message = "작업 ID는 필수입니다.";
                    } else {
                        try {
                            $query = "UPDATE lottery_barcode_generation_tasks 
                                      SET status = 'cancelled', 
                                          updated_by = ?, 
                                          updated_at = NOW() 
                                      WHERE id = ? AND status = 'pending'";
                            
                            $stmt = $db->prepare($query);
                            $user_id = AuthManager::getUserId();
                            $stmt->bindParam(1, $user_id, PDO::PARAM_INT);
                            $stmt->bindParam(2, $task_id, PDO::PARAM_INT);
                            
                            if ($stmt->execute()) {
                                $success_message = "바코드 생성 작업이 취소되었습니다.";
                                logActivity('바코드 생성 작업 취소: ID ' . $task_id);
                            } else {
                                $error_message = "작업 취소 중 오류가 발생했습니다.";
                                logError('barcode_management.php: ' . json_encode($stmt->errorInfo()));
                            }
                        } catch (PDOException $e) {
                            $error_message = "데이터베이스 오류: " . $e->getMessage();
                            logError('barcode_management.php: ' . $e->getMessage());
                        }
                    }
                    break;
                    
                case 'retry_task':
                    // 실패한 바코드 생성 작업 재시도
                    $task_id = sanitizeInput($_POST['task_id'] ?? '');
                    
                    if (empty($task_id)) {
                        $error_message = "작업 ID는 필수입니다.";
                    } else {
                        try {
                            $query = "UPDATE lottery_barcode_generation_tasks 
                                      SET status = 'pending', 
                                          attempts = attempts + 1, 
                                          updated_by = ?, 
                                          updated_at = NOW() 
                                      WHERE id = ? AND (status = 'failed' OR status = 'error')";
                            
                            $stmt = $db->prepare($query);
                            $user_id = AuthManager::getUserId();
                            $stmt->bindParam(1, $user_id, PDO::PARAM_INT);
                            $stmt->bindParam(2, $task_id, PDO::PARAM_INT);
                            
                            if ($stmt->execute()) {
                                $success_message = "바코드 생성 작업이 재시도 큐에 추가되었습니다.";
                                logActivity('바코드 생성 작업 재시도: ID ' . $task_id);
                                
                                // 여기에 실제 바코드 생성 작업을 시작하는 호출 추가 (백그라운드 작업 형태)
                                // startBarcodeGenerationJob($task_id);
                            } else {
                                $error_message = "작업 재시도 중 오류가 발생했습니다.";
                                logError('barcode_management.php: ' . json_encode($stmt->errorInfo()));
                            }
                        } catch (PDOException $e) {
                            $error_message = "데이터베이스 오류: " . $e->getMessage();
                            logError('barcode_management.php: ' . $e->getMessage());
                        }
                    }
                    break;
            }
        } catch (Exception $e) {
            $error_message = "오류가 발생했습니다: " . $e->getMessage();
            logError('barcode_management.php: ' . $e->getMessage());
        }
    }
}

// 바코드 설정 불러오기
$barcode_settings = [];

// 바코드 설정 테이블 존재 여부 확인 및 필요시 생성
try {
    // 테이블 존재 여부 확인
    $checkTableQuery = "SHOW TABLES LIKE 'lottery_barcode_settings'";
    $tableExists = $db->query($checkTableQuery)->rowCount() > 0;
    
    if (!$tableExists) {
        // 테이블이 없으면 생성
        $createTableSQL = "CREATE TABLE IF NOT EXISTS lottery_barcode_settings (
            id INT PRIMARY KEY,
            barcode_type VARCHAR(50) DEFAULT 'qrcode_v2',
            prefix VARCHAR(10) DEFAULT 'LT',
            length INT DEFAULT 12,
            check_digit TINYINT(1) DEFAULT 1,
            encryption TINYINT(1) DEFAULT 0,
            encryption_key VARCHAR(255) DEFAULT NULL,
            created_by INT,
            updated_by INT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP
        )";
        
        $db->exec($createTableSQL);
        
        // 기본 설정 데이터 삽입
        $insertDefaultSQL = "INSERT INTO lottery_barcode_settings (
            id, barcode_type, prefix, length, check_digit, encryption, created_by
        ) VALUES (
            1, 'qrcode_v2', 'LT', 12, 1, 0, 1
        )";
        
        $db->exec($insertDefaultSQL);
        logActivity('바코드 설정 테이블 생성 및 기본 설정 추가');
    }

    // 바코드 생성 작업 테이블 존재 여부 확인
    $checkTaskTableQuery = "SHOW TABLES LIKE 'lottery_barcode_generation_tasks'";
    $taskTableExists = $db->query($checkTaskTableQuery)->rowCount() > 0;
    
    if (!$taskTableExists) {
        // 테이블이 없으면 생성
        $createTaskTableSQL = "CREATE TABLE IF NOT EXISTS lottery_barcode_generation_tasks (
            id INT AUTO_INCREMENT PRIMARY KEY,
            lottery_product_id INT NOT NULL,
            issue_id INT NOT NULL,
            batch_id INT,
            quantity INT NOT NULL,
            start_number VARCHAR(50),
            processed INT DEFAULT 0,
            status ENUM('pending', 'in_progress', 'completed', 'failed', 'cancelled', 'error') DEFAULT 'pending',
            progress INT DEFAULT 0,
            attempts INT DEFAULT 1,
            error_message TEXT,
            sample_barcodes TEXT,
            created_by INT,
            updated_by INT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME,
            completed_at DATETIME,
            INDEX idx_product (lottery_product_id),
            INDEX idx_issue (issue_id),
            INDEX idx_batch (batch_id),
            INDEX idx_status (status)
        )";
        
        $db->exec($createTaskTableSQL);
        logActivity('바코드 생성 작업 테이블 생성');
    }

    // 바코드 설정 불러오기
    $query = "SELECT * FROM lottery_barcode_settings WHERE id = 1";
    $result = $db->query($query);
    if ($result && $result->rowCount() > 0) {
        $barcode_settings = $result->fetch(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    logError('barcode_management.php: 바코드 설정 불러오기 오류 - ' . $e->getMessage());
}

// 복권 상품 목록 불러오기
$products = [];
$query = "SELECT id, name FROM lottery_products WHERE status = 'active'";
try {
    $result = $db->query($query);
    if ($result) {
        $products = $result->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    logError('barcode_management.php: 복권 상품 목록 불러오기 오류 - ' . $e->getMessage());
}

// 발행 목록 불러오기
$issues = [];
$query = "SELECT id, issue_code FROM lottery_issues WHERE status = 'active'";
try {
    $result = $db->query($query);
    if ($result) {
        $issues = $result->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    logError('barcode_management.php: 발행 목록 불러오기 오류 - ' . $e->getMessage());
}

// 배치 목록 불러오기
$batches = [];
$query = "SELECT id, batch_name FROM lottery_batches WHERE status IN ('pending', 'in_progress')";
try {
    $result = $db->query($query);
    if ($result) {
        $batches = $result->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    logError('barcode_management.php: 배치 목록 불러오기 오류 - ' . $e->getMessage());
}

// 바코드 생성 작업 목록 불러오기
$tasks = [];
$query = "SELECT t.*, p.name AS product_name, i.issue_code, b.batch_name 
          FROM lottery_barcode_generation_tasks t
          LEFT JOIN lottery_products p ON t.lottery_product_id = p.id
          LEFT JOIN lottery_issues i ON t.issue_id = i.id
          LEFT JOIN lottery_batches b ON t.batch_id = b.id
          ORDER BY t.created_at DESC
          LIMIT 20";
try {
    $result = $db->query($query);
    if ($result) {
        $tasks = $result->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    logError('barcode_management.php: 바코드 생성 작업 목록 불러오기 오류 - ' . $e->getMessage());
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
    
    <div class="row">
        <div class="col-xl-12">
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
            
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-qrcode me-1"></i>
                    <?php echo $page_description; ?>
                </div>
                <div class="card-body">
                    <ul class="nav nav-tabs" id="barcodeTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="settings-tab" data-bs-toggle="tab" data-bs-target="#settings" type="button" role="tab" aria-controls="settings" aria-selected="true">QR코드 설정</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="generate-tab" data-bs-toggle="tab" data-bs-target="#generate" type="button" role="tab" aria-controls="generate" aria-selected="false">QR코드 생성</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="tasks-tab" data-bs-toggle="tab" data-bs-target="#tasks" type="button" role="tab" aria-controls="tasks" aria-selected="false">생성 작업 목록</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="verify-tab" data-bs-toggle="tab" data-bs-target="#verify" type="button" role="tab" aria-controls="verify" aria-selected="false">QR코드 검증</button>
                        </li>
                    </ul>
                    
                    <div class="tab-content" id="barcodeTabsContent">
                        <!-- QR코드 설정 탭 -->
                        <div class="tab-pane fade show active p-3" id="settings" role="tabpanel" aria-labelledby="settings-tab">
                            <form id="barcodeSettingsForm" method="post" action="">
                                <input type="hidden" name="action" value="save_barcode_settings">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                
                                <div class="row mt-3">
                                    <div class="col-md-6 mb-3">
                                        <label for="barcode_type" class="form-label">QR코드 버전</label>
                                        <select class="form-select" id="barcode_type" name="barcode_type">
                                            <option value="qrcode_v1" <?php echo ($barcode_settings['barcode_type'] ?? '') === 'qrcode_v1' ? 'selected' : ''; ?>>QR 코드 V1</option>
                                            <option value="qrcode_v2" <?php echo ($barcode_settings['barcode_type'] ?? '') === 'qrcode_v2' ? 'selected' : ''; ?>>QR 코드 V2</option>
                                            <option value="qrcode_v3" <?php echo ($barcode_settings['barcode_type'] ?? '') === 'qrcode_v3' ? 'selected' : ''; ?>>QR 코드 V3</option>
                                            <option value="qrcode_micro" <?php echo ($barcode_settings['barcode_type'] ?? '') === 'qrcode_micro' ? 'selected' : ''; ?>>마이크로 QR 코드</option>
                                        </select>
                                        <div class="form-text">QR코드 버전을 선택합니다. 일반적으로 QR 코드 V2가 많이 사용됩니다.</div>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="prefix" class="form-label">QR코드 접두사</label>
                                        <input type="text" class="form-control" id="prefix" name="prefix" maxlength="10" value="<?php echo $barcode_settings['prefix'] ?? 'LT'; ?>">
                                        <div class="form-text">QR코드 데이터의 시작 부분에 추가되는 고정 문자열입니다.</div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="length" class="form-label">데이터 길이</label>
                                        <input type="number" class="form-control" id="length" name="length" min="8" max="30" value="<?php echo $barcode_settings['length'] ?? '12'; ?>">
                                        <div class="form-text">생성할 QR코드에 포함될 데이터의 길이입니다. 접두사를 포함한 길이입니다.</div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="check_digit" name="check_digit" <?php echo ($barcode_settings['check_digit'] ?? 1) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="check_digit">
                                                체크섬 추가
                                            </label>
                                            <div class="form-text">QR코드 데이터에 검증용 체크섬을 추가합니다.</div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="encryption" name="encryption" <?php echo ($barcode_settings['encryption'] ?? 0) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="encryption">
                                                암호화 사용
                                            </label>
                                            <div class="form-text">QR코드 데이터를 암호화하여 저장합니다.</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3" id="encryption_key_container" style="<?php echo ($barcode_settings['encryption'] ?? 0) ? '' : 'display: none;'; ?>">
                                        <label for="encryption_key" class="form-label">암호화 키</label>
                                        <input type="password" class="form-control" id="encryption_key" name="encryption_key" value="<?php echo $barcode_settings['encryption_key'] ?? ''; ?>">
                                        <div class="form-text">QR코드 데이터 암호화에 사용할 키입니다. 안전한 곳에 백업해 두세요.</div>
                                    </div>
                                </div>
                                
                                <div class="row mt-3">
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i>
                                            설정 저장
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                        
                        <!-- QR코드 생성 탭 -->
                        <div class="tab-pane fade p-3" id="generate" role="tabpanel" aria-labelledby="generate-tab">
                            <div class="alert alert-info mt-3" role="alert">
                                <i class="fas fa-info-circle me-2"></i>
                                QR코드 생성은 배치 작업으로 처리됩니다. 생성 요청 후 '생성 작업 목록' 탭에서 진행 상황을 확인할 수 있습니다.
                            </div>
                            
                            <form id="generateBarcodesForm" method="post" action="">
                                <input type="hidden" name="action" value="generate_barcodes">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                
                                <div class="row mt-3">
                                    <div class="col-md-6 mb-3">
                                        <label for="lottery_product_id" class="form-label">복권 상품 <span class="text-danger">*</span></label>
                                        <select class="form-select" id="lottery_product_id" name="lottery_product_id" required>
                                            <option value="">선택하세요</option>
                                            <?php foreach ($products as $product): ?>
                                                <option value="<?php echo htmlspecialchars($product['id']); ?>"><?php echo htmlspecialchars($product['name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="issue_id" class="form-label">발행 코드 <span class="text-danger">*</span></label>
                                        <select class="form-select" id="issue_id" name="issue_id" required>
                                            <option value="">선택하세요</option>
                                            <?php foreach ($issues as $issue): ?>
                                                <option value="<?php echo htmlspecialchars($issue['id']); ?>"><?php echo htmlspecialchars($issue['issue_code']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="batch_id" class="form-label">배치 (선택사항)</label>
                                        <select class="form-select" id="batch_id" name="batch_id">
                                            <option value="">선택하세요</option>
                                            <?php foreach ($batches as $batch): ?>
                                                <option value="<?php echo htmlspecialchars($batch['id']); ?>"><?php echo htmlspecialchars($batch['batch_name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="form-text">특정 배치에 대한 QR코드를 생성할 경우 선택합니다.</div>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="quantity" class="form-label">생성 수량 <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" id="quantity" name="quantity" min="1" max="100000" required>
                                        <div class="form-text">생성할 QR코드의 수량입니다. 대량 생성은 시스템 부하를 고려하세요.</div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="start_number" class="form-label">시작 번호 (선택사항)</label>
                                        <input type="number" class="form-control" id="start_number" name="start_number" min="1">
                                        <div class="form-text">QR코드 일련번호의 시작 값입니다. 비워두면 자동으로 부여됩니다.</div>
                                    </div>
                                </div>
                                
                                <div class="row mt-3">
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary" id="generateButton">
                                            <i class="fas fa-cogs me-2"></i>
                                            QR코드 생성 요청
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                        
                        <!-- 생성 작업 목록 탭 -->
                        <div class="tab-pane fade p-3" id="tasks" role="tabpanel" aria-labelledby="tasks-tab">
                            <div class="table-responsive mt-3">
                                <table class="table table-bordered table-striped" id="tasksTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>복권 상품</th>
                                            <th>발행 코드</th>
                                            <th>배치</th>
                                            <th>수량</th>
                                            <th>상태</th>
                                            <th>진행률</th>
                                            <th>생성일</th>
                                            <th>완료일</th>
                                            <th>작업</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($tasks as $task): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($task['id']); ?></td>
                                            <td><?php echo htmlspecialchars($task['product_name']); ?></td>
                                            <td><?php echo htmlspecialchars($task['issue_code']); ?></td>
                                            <td><?php echo htmlspecialchars($task['batch_name'] ?? '-'); ?></td>
                                            <td><?php echo htmlspecialchars($task['quantity']); ?></td>
                                            <td>
                                                <?php 
                                                $status_class = '';
                                                $status_text = '';
                                                
                                                switch ($task['status']) {
                                                    case 'pending':
                                                        $status_class = 'bg-secondary';
                                                        $status_text = '대기 중';
                                                        break;
                                                    case 'in_progress':
                                                        $status_class = 'bg-primary';
                                                        $status_text = '진행 중';
                                                        break;
                                                    case 'completed':
                                                        $status_class = 'bg-success';
                                                        $status_text = '완료';
                                                        break;
                                                    case 'failed':
                                                        $status_class = 'bg-danger';
                                                        $status_text = '실패';
                                                        break;
                                                    case 'cancelled':
                                                        $status_class = 'bg-warning';
                                                        $status_text = '취소됨';
                                                        break;
                                                    case 'error':
                                                        $status_class = 'bg-danger';
                                                        $status_text = '오류';
                                                        break;
                                                    default:
                                                        $status_class = 'bg-secondary';
                                                        $status_text = $task['status'];
                                                }
                                                ?>
                                                <span class="badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                            </td>
                                            <td>
                                                <?php if ($task['status'] === 'in_progress'): ?>
                                                <div class="progress">
                                                    <div class="progress-bar" role="progressbar" style="width: <?php echo $task['progress'] ?? 0; ?>%;" 
                                                         aria-valuenow="<?php echo $task['progress'] ?? 0; ?>" aria-valuemin="0" aria-valuemax="100">
                                                        <?php echo $task['progress'] ?? 0; ?>%
                                                    </div>
                                                </div>
                                                <?php elseif ($task['status'] === 'completed'): ?>
                                                <div class="progress">
                                                    <div class="progress-bar bg-success" role="progressbar" style="width: 100%;" 
                                                         aria-valuenow="100" aria-valuemin="0" aria-valuemax="100">
                                                        100%
                                                    </div>
                                                </div>
                                                <?php else: ?>
                                                -
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo formatDateTime($task['created_at']); ?></td>
                                            <td><?php echo !empty($task['completed_at']) ? formatDateTime($task['completed_at']) : '-'; ?></td>
                                            <td>
                                                <div class="btn-group">
                                                    <button type="button" class="btn btn-info btn-sm view-task" data-id="<?php echo $task['id']; ?>" title="상세보기">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    
                                                    <?php if ($task['status'] === 'pending'): ?>
                                                    <form method="post" action="" class="d-inline">
                                                        <input type="hidden" name="action" value="cancel_task">
                                                        <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                                        <button type="submit" class="btn btn-warning btn-sm" title="취소">
                                                            <i class="fas fa-ban"></i>
                                                        </button>
                                                    </form>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($task['status'] === 'failed' || $task['status'] === 'error'): ?>
                                                    <form method="post" action="" class="d-inline">
                                                        <input type="hidden" name="action" value="retry_task">
                                                        <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                                        <button type="submit" class="btn btn-primary btn-sm" title="재시도">
                                                            <i class="fas fa-redo"></i>
                                                        </button>
                                                    </form>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($task['status'] === 'completed'): ?>
                                                    <a href="/dashboard/lottery/barcode-export.php?task_id=<?php echo $task['id']; ?>" class="btn btn-success btn-sm" title="내보내기">
                                                        <i class="fas fa-file-export"></i>
                                                    </a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <?php if (empty($tasks)): ?>
                            <div class="alert alert-info mt-3">
                                <i class="fas fa-info-circle me-2"></i>
                                등록된 QR코드 생성 작업이 없습니다.
                            </div>
                            <?php endif; ?>
                            
                            <div class="mt-3">
                                <a href="/dashboard/lottery/barcode-tasks.php" class="btn btn-primary">
                                    <i class="fas fa-list me-2"></i>
                                    모든 작업 보기
                                </a>
                            </div>
                        </div>
                        
                        <!-- 바코드 검증 탭 -->
                        <div class="tab-pane fade p-3" id="verify" role="tabpanel" aria-labelledby="verify-tab">
                            <div class="alert alert-info mt-3" role="alert">
                                <i class="fas fa-info-circle me-2"></i>
                                QR코드 검증을 통해 QR코드의 유효성을 확인할 수 있습니다.
                            </div>
                            
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <i class="fas fa-check-circle me-1"></i>
                                            QR코드 검증
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3">
                                                <label for="verifyBarcode" class="form-label">QR코드 입력</label>
                                                <input type="text" class="form-control" id="verifyBarcode" placeholder="QR코드를 입력하세요">
                                            </div>
                                            
                                            <button type="button" id="verifyButton" class="btn btn-primary">
                                                <i class="fas fa-search me-2"></i>
                                                QR코드 검증
                                            </button>
                                            
                                            <hr>
                                            
                                            <div id="verifyResult" class="mt-3" style="display: none;">
                                                <!-- 검증 결과가 여기에 표시됩니다 -->
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <i class="fas fa-qrcode me-1"></i>
                                            QR코드 스캐너
                                        </div>
                                        <div class="card-body">
                                            <p>QR코드 스캐너를 통해 QR코드를 스캔할 수 있습니다.</p>
                                            
                                            <div class="text-center">
                                                <button type="button" id="scanButton" class="btn btn-success mb-3">
                                                    <i class="fas fa-camera me-2"></i>
                                                    스캐너 활성화
                                                </button>
                                                
                                                <div id="scanner-container" class="mb-3" style="display: none;">
                                                    <div id="scanner" style="width: 100%; height: 300px; border: 1px solid #ccc;"></div>
                                                </div>
                                                
                                                <div id="scanResult" class="mt-3" style="display: none;">
                                                    <!-- 스캔 결과가 여기에 표시됩니다 -->
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 작업 상세 정보 모달 -->
<div class="modal fade" id="taskDetailsModal" tabindex="-1" aria-labelledby="taskDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="taskDetailsModalLabel">QR코드 생성 작업 상세 정보</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="taskDetails">
                    <!-- 작업 상세 정보가 JavaScript로 채워집니다 -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">닫기</button>
                <a href="#" id="exportTaskData" class="btn btn-primary" target="_blank">내보내기</a>
            </div>
        </div>
    </div>
</div>

<?php
// 페이지 하단 포함
include_once TEMPLATES_PATH . '/dashboard_footer.php';
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 암호화 체크박스 변경 시 암호화 키 입력 필드 표시/숨김
    document.getElementById('encryption').addEventListener('change', function() {
        document.getElementById('encryption_key_container').style.display = this.checked ? 'block' : 'none';
    });
    
    // 바코드 생성 폼 제출 시 확인
    document.getElementById('generateBarcodesForm').addEventListener('submit', function(event) {
        const quantity = parseInt(document.getElementById('quantity').value);
        
        if (quantity > 10000) {
            if (!confirm('대량의 바코드 (' + quantity + '개)를 생성하려고 합니다. 시스템에 부하가 걸릴 수 있습니다. 계속하시겠습니까?')) {
                event.preventDefault();
            }
        }
    });
    
    // 작업 상세 보기 버튼 클릭 이벤트
    document.querySelectorAll('.view-task').forEach(function(button) {
        button.addEventListener('click', function() {
            const taskId = this.getAttribute('data-id');
            console.log('작업 상세 보기 버튼 클릭: ID = ' + taskId);
            
            // AJAX로 작업 데이터 가져오기
            fetch('/api/lottery/get_barcode_task.php?id=' + taskId)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        const task = data.task;
                        
                        // 작업 상세 정보 템플릿 생성
                        let detailsHTML = `
                            <div class="row">
                                <div class="col-md-6">
                                    <h6 class="fw-bold">기본 정보</h6>
                                    <table class="table table-bordered">
                                        <tr>
                                            <th width="35%">작업 ID</th>
                                            <td>${task.id}</td>
                                        </tr>
                                        <tr>
                                            <th>복권 상품</th>
                                            <td>${task.product_name}</td>
                                        </tr>
                                        <tr>
                                            <th>발행 코드</th>
                                            <td>${task.issue_code}</td>
                                        </tr>
                                        <tr>
                                            <th>배치</th>
                                            <td>${task.batch_name || '-'}</td>
                                        </tr>
                                        <tr>
                                            <th>수량</th>
                                            <td>${task.quantity}</td>
                                        </tr>
                                        <tr>
                                            <th>시작 번호</th>
                                            <td>${task.start_number || '자동'}</td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="fw-bold">상태 정보</h6>
                                    <table class="table table-bordered">
                                        <tr>
                                            <th width="35%">상태</th>
                                            <td>
                                                <span class="badge ${getStatusClass(task.status)}">${getStatusText(task.status)}</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>진행률</th>
                                            <td>
                                                ${task.status === 'in_progress' ? 
                                                `<div class="progress">
                                                    <div class="progress-bar" role="progressbar" style="width: ${task.progress || 0}%;" 
                                                         aria-valuenow="${task.progress || 0}" aria-valuemin="0" aria-valuemax="100">
                                                        ${task.progress || 0}%
                                                    </div>
                                                </div>` : 
                                                (task.status === 'completed' ? '100%' : '-')}
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>시도 횟수</th>
                                            <td>${task.attempts}</td>
                                        </tr>
                                        <tr>
                                            <th>생성일</th>
                                            <td>${formatDateTime(task.created_at)}</td>
                                        </tr>
                                        <tr>
                                            <th>완료일</th>
                                            <td>${task.completed_at ? formatDateTime(task.completed_at) : '-'}</td>
                                        </tr>
                                        <tr>
                                            <th>생성자</th>
                                            <td>${task.created_by_name || '-'}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        `;
                        
                        // 오류 정보가 있는 경우 표시
                        if (task.error_message) {
                            detailsHTML += `
                                <div class="row mt-3">
                                    <div class="col-12">
                                        <h6 class="fw-bold">오류 정보</h6>
                                        <div class="alert alert-danger">
                                            ${task.error_message}
                                        </div>
                                    </div>
                                </div>
                            `;
                        }
                        
                        // 바코드 샘플 정보 표시 (있는 경우)
                        if (task.status === 'completed' && task.sample_barcodes) {
                            try {
                                const samples = JSON.parse(task.sample_barcodes);
                                if (samples.length > 0) {
                                    detailsHTML += `
                                        <div class="row mt-3">
                                            <div class="col-12">
                                                <h6 class="fw-bold">바코드 샘플 (처음 5개)</h6>
                                                <ul class="list-group">
                                    `;
                                    
                                    samples.forEach(barcode => {
                                        detailsHTML += `<li class="list-group-item">${barcode}</li>`;
                                    });
                                    
                                    detailsHTML += `
                                                </ul>
                                            </div>
                                        </div>
                                    `;
                                }
                            } catch (e) {
                                console.error('QR코드 샘플 파싱 오류:', e);
                            }
                        }
                        
                        document.getElementById('taskDetails').innerHTML = detailsHTML;
                        document.getElementById('exportTaskData').href = '/dashboard/lottery/barcode-export.php?task_id=' + task.id;
                        
                        // 모달 열기
                        new bootstrap.Modal(document.getElementById('taskDetailsModal')).show();
                    } else {
                        alert('작업 정보를 가져오는 중 오류가 발생했습니다: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('작업 정보를 가져오는 중 오류가 발생했습니다.');
                });
        });
    });
    
    // QR코드 검증 버튼 클릭 이벤트
    document.getElementById('verifyButton').addEventListener('click', function() {
        const barcode = document.getElementById('verifyBarcode').value.trim();
        if (!barcode) {
            alert('QR코드를 입력해주세요.');
            return;
        }
        
        // 검증 결과 영역 초기화 및 표시
        const resultContainer = document.getElementById('verifyResult');
        resultContainer.innerHTML = '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>';
        resultContainer.style.display = 'block';
        
        // AJAX로 QR코드 검증
        fetch('/api/lottery/verify_barcode.php?barcode=' + encodeURIComponent(barcode))
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    if (data.valid) {
                        // 유효한 QR코드인 경우
                        resultContainer.innerHTML = `
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i>
                                <strong>유효한 QR코드입니다.</strong>
                            </div>
                            <table class="table table-bordered mt-3">
                                <tr>
                                    <th width="35%">복권 상품</th>
                                    <td>${data.info.product_name}</td>
                                </tr>
                                <tr>
                                    <th>발행 코드</th>
                                    <td>${data.info.issue_code}</td>
                                </tr>
                                <tr>
                                    <th>상태</th>
                                    <td><span class="badge ${getBarcodeStatusClass(data.info.status)}">${getBarcodeStatusText(data.info.status)}</span></td>
                                </tr>
                                <tr>
                                    <th>생성일</th>
                                    <td>${formatDateTime(data.info.created_at)}</td>
                                </tr>
                            </table>
                        `;
                    } else {
                        // 유효하지 않은 QR코드인 경우
                        resultContainer.innerHTML = `
                            <div class="alert alert-danger">
                                <i class="fas fa-times-circle me-2"></i>
                                <strong>유효하지 않은 QR코드입니다.</strong>
                            </div>
                            <p>${data.message}</p>
                        `;
                    }
                } else {
                    // 오류 발생
                    resultContainer.innerHTML = `
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>오류 발생</strong>
                        </div>
                        <p>${data.message}</p>
                    `;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                resultContainer.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <strong>통신 오류</strong>
                    </div>
                    <p>바코드 검증 중 오류가 발생했습니다.</p>
                `;
            });
    });
    
    // 바코드 스캐너 활성화 버튼 클릭 이벤트
    document.getElementById('scanButton').addEventListener('click', function() {
        const scannerContainer = document.getElementById('scanner-container');
        
        if (scannerContainer.style.display === 'none') {
            // 스캐너 표시
            scannerContainer.style.display = 'block';
            this.innerHTML = '<i class="fas fa-times me-2"></i> 스캐너 비활성화';
            
            // 여기에 실제 스캐너 활성화 코드를 추가 (여기서는 예시로 HTML5 QR 코드 스캐너를 가정)
            // 실제 구현 시에는 적절한 바코드 스캐너 라이브러리를 사용해야 함
            initScanner();
        } else {
            // 스캐너 숨김
            scannerContainer.style.display = 'none';
            this.innerHTML = '<i class="fas fa-camera me-2"></i> 스캐너 활성화';
            
            // 스캐너 비활성화 코드
            if (window.scanner) {
                window.scanner.stop();
            }
        }
    });
    
    // 스캐너 초기화 함수 (예시)
    function initScanner() {
        // 실제 구현 시에는 적절한 바코드 스캐너 라이브러리를 사용해야 함
        document.getElementById('scanner').innerHTML = `
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                카메라가 연결된 경우, 바코드를 스캔할 수 있습니다.
            </div>
            <div class="text-center">
                <button type="button" id="mockScanButton" class="btn btn-primary">테스트 스캔</button>
            </div>
        `;
        
        // 테스트용 스캔 버튼 이벤트
        document.getElementById('mockScanButton').addEventListener('click', function() {
            const testBarcode = 'LT' + Math.floor(Math.random() * 1000000000).toString().padStart(10, '0');
            onScanSuccess(testBarcode);
        });
    }
    
    // 스캔 성공 시 처리 함수
    function onScanSuccess(barcode) {
        console.log('스캔된 바코드:', barcode);
        
        // 스캔 결과 표시
        const scanResult = document.getElementById('scanResult');
        scanResult.innerHTML = `
            <div class="alert alert-success">
                <i class="fas fa-check-circle me-2"></i>
                <strong>바코드 스캔 성공</strong>
            </div>
            <p>스캔된 바코드: <strong>${barcode}</strong></p>
            <button type="button" id="verifyScanButton" class="btn btn-primary">바코드 검증</button>
        `;
        scanResult.style.display = 'block';
        
        // 바코드 검증 이벤트
        document.getElementById('verifyScanButton').addEventListener('click', function() {
            document.getElementById('verifyBarcode').value = barcode;
            
            // 바코드 검증 탭으로 전환
            document.getElementById('verify-tab').click();
            
            // 검증 버튼 클릭
            document.getElementById('verifyButton').click();
        });
    }
    
    // 상태 클래스 반환 함수
    function getStatusClass(status) {
        switch (status) {
            case 'pending': return 'bg-secondary';
            case 'in_progress': return 'bg-primary';
            case 'completed': return 'bg-success';
            case 'failed': return 'bg-danger';
            case 'cancelled': return 'bg-warning';
            case 'error': return 'bg-danger';
            default: return 'bg-secondary';
        }
    }
    
    // 상태 텍스트 반환 함수
    function getStatusText(status) {
        switch (status) {
            case 'pending': return '대기 중';
            case 'in_progress': return '진행 중';
            case 'completed': return '완료';
            case 'failed': return '실패';
            case 'cancelled': return '취소됨';
            case 'error': return '오류';
            default: return status;
        }
    }
    
    // 바코드 상태 클래스 반환 함수
    function getBarcodeStatusClass(status) {
        switch (status) {
            case 'active': return 'bg-success';
            case 'used': return 'bg-primary';
            case 'voided': return 'bg-danger';
            case 'expired': return 'bg-warning';
            default: return 'bg-secondary';
        }
    }
    
    // 바코드 상태 텍스트 반환 함수
    function getBarcodeStatusText(status) {
        switch (status) {
            case 'active': return '활성';
            case 'used': return '사용됨';
            case 'voided': return '무효화됨';
            case 'expired': return '만료됨';
            default: return status;
        }
    }
    
    // 날짜 포맷 함수
    function formatDateTime(dateString) {
        if (!dateString) return '-';
        
        const date = new Date(dateString);
        return date.getFullYear() + '-' + 
               String(date.getMonth() + 1).padStart(2, '0') + '-' + 
               String(date.getDate()).padStart(2, '0') + ' ' + 
               String(date.getHours()).padStart(2, '0') + ':' + 
               String(date.getMinutes()).padStart(2, '0');
    }
});
</script>
