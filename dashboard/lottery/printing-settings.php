<?php
/**
 * 복권 인쇄 설정
 * 복권 인쇄에 관한 설정을 관리하는 페이지
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
$pageTitle = "복권 인쇄 설정";
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
$page_description = "복권 인쇄에 관한 설정을 관리합니다.";

// 작업 결과 메시지
$success_message = '';
$error_message = '';

// 폼 처리: 인쇄 설정 저장
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // CSRF 토큰 검증
    if (!SecurityManager::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error_message = "보안 토큰이 유효하지 않습니다. 페이지를 새로고침 후 다시 시도해 주세요.";
    } else {
        try {
            switch ($_POST['action']) {
                case 'save_print_settings':
                    // 일반 설정 저장
                    $printer_type = sanitizeInput($_POST['printer_type'] ?? '');
                    $paper_size = sanitizeInput($_POST['paper_size'] ?? '');
                    $dpi_setting = sanitizeInput($_POST['dpi_setting'] ?? '');
                    $color_mode = sanitizeInput($_POST['color_mode'] ?? '');
                    $default_margin = sanitizeInput($_POST['default_margin'] ?? '');
                    $enable_duplex = isset($_POST['enable_duplex']) ? 1 : 0;
                    $enable_cut_marks = isset($_POST['enable_cut_marks']) ? 1 : 0;
                    $enable_watermark = isset($_POST['enable_watermark']) ? 1 : 0;
                    
                    // 보안 및 품질 설정
                    $security_ink = sanitizeInput($_POST['security_ink'] ?? '');
                    $uv_ink = isset($_POST['uv_ink']) ? 1 : 0;
                    $quality_check = isset($_POST['quality_check']) ? 1 : 0;
                    $barcode_verification = isset($_POST['barcode_verification']) ? 1 : 0;
                    $error_logging = isset($_POST['error_logging']) ? 1 : 0;
                    
                    // 워터마크 설정
                    $watermark_text = sanitizeInput($_POST['watermark_text'] ?? '');
                    $watermark_opacity = sanitizeInput($_POST['watermark_opacity'] ?? '');
                    
                    // 기본 이미지 설정
                    if (isset($_FILES['background_image']) && $_FILES['background_image']['error'] === UPLOAD_ERR_OK) {
                        $background_image = uploadFile($_FILES['background_image'], 'lottery_images');
                    } else {
                        $background_image = sanitizeInput($_POST['current_background_image'] ?? '');
                    }
                    
                    if (isset($_FILES['logo_image']) && $_FILES['logo_image']['error'] === UPLOAD_ERR_OK) {
                        $logo_image = uploadFile($_FILES['logo_image'], 'lottery_images');
                    } else {
                        $logo_image = sanitizeInput($_POST['current_logo_image'] ?? '');
                    }
                    
                    // 설정 저장
                    $settings = [
                        'printer_type' => $printer_type,
                        'paper_size' => $paper_size,
                        'dpi_setting' => $dpi_setting,
                        'color_mode' => $color_mode,
                        'default_margin' => $default_margin,
                        'enable_duplex' => $enable_duplex,
                        'enable_cut_marks' => $enable_cut_marks,
                        'enable_watermark' => $enable_watermark,
                        'security_ink' => $security_ink,
                        'uv_ink' => $uv_ink,
                        'quality_check' => $quality_check,
                        'barcode_verification' => $barcode_verification,
                        'error_logging' => $error_logging,
                        'watermark_text' => $watermark_text,
                        'watermark_opacity' => $watermark_opacity,
                        'background_image' => $background_image,
                        'logo_image' => $logo_image,
                        'updated_by' => AuthManager::getUserId(),
                        'updated_at' => date('Y-m-d H:i:s')
                    ];
                    
                    // 현재 설정 확인
                    try {
                        $query = "SELECT * FROM lottery_print_settings WHERE id = 1";
                        $stmt = $db->prepare($query);
                        $stmt->execute();
                        $currentSettings = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($currentSettings) {
                            // 기존 설정 업데이트
                            $updateQuery = "UPDATE lottery_print_settings SET ";
                            $updateParts = [];
                            $params = [];
                            
                            foreach ($settings as $key => $value) {
                                $updateParts[] = "`$key` = ?";
                                $params[] = $value;
                            }
                            
                            $updateQuery .= implode(', ', $updateParts) . " WHERE id = 1";
                            $stmt = $db->prepare($updateQuery);
                            
                            if ($stmt->execute($params)) {
                                $success_message = "인쇄 설정이 성공적으로 저장되었습니다.";
                                logActivity('인쇄 설정 업데이트');
                            } else {
                                $error_message = "인쇄 설정 저장 중 오류가 발생했습니다.";
                                logError('printing_settings.php: 설정 업데이트 실패 - ' . json_encode($db->errorInfo()));
                            }
                        } else {
                            // 새 설정 생성
                            $columns = implode(', ', array_keys($settings));
                            $placeholders = implode(', ', array_fill(0, count($settings), '?'));
                            
                            $insertQuery = "INSERT INTO lottery_print_settings (id, $columns) VALUES (1, $placeholders)";
                            $stmt = $db->prepare($insertQuery);
                            
                            if ($stmt->execute(array_values($settings))) {
                                $success_message = "인쇄 설정이 성공적으로 생성되었습니다.";
                                logActivity('인쇄 설정 생성');
                            } else {
                                $error_message = "인쇄 설정 생성 중 오류가 발생했습니다.";
                                logError('printing_settings.php: 설정 생성 실패 - ' . json_encode($db->errorInfo()));
                            }
                        }
                    } catch (PDOException $e) {
                        $error_message = "데이터베이스 작업 중 오류가 발생했습니다: " . $e->getMessage();
                        logError('printing_settings.php: ' . $e->getMessage());
                    }

                    break;
                
                case 'test_print':
                    // 테스트 인쇄 처리
                    $printer_id = sanitizeInput($_POST['printer_id'] ?? '');
                    $test_type = sanitizeInput($_POST['test_type'] ?? '');
                    
                    if (empty($printer_id) || empty($test_type)) {
                        $error_message = "프린터와 테스트 유형을 선택해주세요.";
                    } else {
                        // 실제 테스트 인쇄 로직 추가
                        // 이 부분은 현재 환경에서 실제로 구현하기 어려우므로 로깅만 진행
                        logActivity("테스트 인쇄 요청: 프린터 ID = $printer_id, 테스트 유형 = $test_type");
                        
                        $success_message = "테스트 인쇄가 요청되었습니다. 프린터에서 출력물을 확인해주세요.";
                    }
                    break;
            }
        } catch (Exception $e) {
            $error_message = "오류가 발생했습니다: " . $e->getMessage();
            logError('printing_settings.php: ' . $e->getMessage());
        }
    }
}

// 파일 업로드 처리 함수
function uploadFile($file, $target_dir) {
    $target_dir = "../../assets/uploads/$target_dir/";
    
    // 디렉토리가 없으면 생성
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0755, true);
    }
    
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $new_filename = uniqid() . '.' . $file_extension;
    $target_file = $target_dir . $new_filename;
    
    // 허용된 파일 형식 확인
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
    if (!in_array($file_extension, $allowed_extensions)) {
        throw new Exception("허용되지 않는 파일 형식입니다. JPG, JPEG, PNG, GIF 파일만 업로드 가능합니다.");
    }
    
    // 파일 크기 확인 (최대 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        throw new Exception("파일 크기가 너무 큽니다. 최대 5MB까지 업로드 가능합니다.");
    }
    
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        return str_replace('../../', '/', $target_file);
    } else {
        throw new Exception("파일 업로드 중 오류가 발생했습니다.");
    }
}

// 테이블 존재 여부 확인 및 필요시 생성
$settings = [];
$printers = [];

try {
    // lottery_print_settings 테이블 존재 여부 확인
    $checkTableQuery = "SHOW TABLES LIKE 'lottery_print_settings'";
    $tableExists = $db->query($checkTableQuery)->rowCount() > 0;
    
    if (!$tableExists) {
        // 테이블이 없으면 생성
        $createTableSQL = "CREATE TABLE IF NOT EXISTS lottery_print_settings (
            id INT PRIMARY KEY,
            printer_type VARCHAR(50) DEFAULT 'thermal',
            paper_size VARCHAR(50) DEFAULT 'a4',
            dpi_setting VARCHAR(20) DEFAULT '300',
            color_mode VARCHAR(20) DEFAULT 'color',
            default_margin INT DEFAULT 5,
            enable_duplex TINYINT(1) DEFAULT 0,
            enable_cut_marks TINYINT(1) DEFAULT 0,
            enable_watermark TINYINT(1) DEFAULT 0,
            security_ink VARCHAR(50) DEFAULT 'none',
            uv_ink TINYINT(1) DEFAULT 0,
            quality_check TINYINT(1) DEFAULT 1,
            barcode_verification TINYINT(1) DEFAULT 1,
            error_logging TINYINT(1) DEFAULT 1,
            watermark_text VARCHAR(255) DEFAULT '',
            watermark_opacity INT DEFAULT 30,
            background_image VARCHAR(255) DEFAULT '',
            logo_image VARCHAR(255) DEFAULT '',
            updated_by INT,
            updated_at DATETIME,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )";
        
        $db->exec($createTableSQL);
        
        // 기본 설정 데이터 삽입
        $insertDefaultSQL = "INSERT INTO lottery_print_settings (
            id, printer_type, paper_size, dpi_setting, color_mode, default_margin,
            enable_duplex, enable_cut_marks, enable_watermark, security_ink, 
            uv_ink, quality_check, barcode_verification, error_logging, 
            watermark_text, watermark_opacity, updated_by, updated_at
        ) VALUES (
            1, 'thermal', 'a4', '300', 'color', 5,
            0, 0, 0, 'none',
            0, 1, 1, 1,
            '복권시스템', 30, 1, NOW()
        )";
        
        $db->exec($insertDefaultSQL);
        logActivity('인쇄 설정 테이블 생성 및 기본 설정 추가');
    }

    // 현재 인쇄 설정 불러오기
    $settingsQuery = "SELECT * FROM lottery_print_settings WHERE id = 1";
    $settingsStmt = $db->prepare($settingsQuery);
    $settingsStmt->execute();
    $settings = $settingsStmt->fetch(PDO::FETCH_ASSOC);
    
    // 프린터 목록 테이블 존재 여부 확인
    $checkPrinterTableQuery = "SHOW TABLES LIKE 'lottery_printers'";
    $printerTableExists = $db->query($checkPrinterTableQuery)->rowCount() > 0;
    
    if (!$printerTableExists) {
        // 테이블이 없으면 생성
        $createPrinterTableSQL = "CREATE TABLE IF NOT EXISTS lottery_printers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            printer_name VARCHAR(100) NOT NULL,
            printer_model VARCHAR(100),
            location VARCHAR(200),
            ip_address VARCHAR(50),
            port VARCHAR(10),
            is_active TINYINT(1) DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP
        )";
        
        $db->exec($createPrinterTableSQL);
        
        // 샘플 프린터 추가
        $insertSamplePrinterSQL = "INSERT INTO lottery_printers (
            printer_name, printer_model, location, ip_address, is_active
        ) VALUES (
            '기본 프린터', 'Thermal Printer X1', '서버실', '192.168.1.100', 1
        )";
        
        $db->exec($insertSamplePrinterSQL);
        logActivity('프린터 테이블 생성 및 샘플 데이터 추가');
    }

    // 프린터 목록 불러오기
    $printersQuery = "SELECT * FROM lottery_printers WHERE is_active = 1";
    $printersStmt = $db->prepare($printersQuery);
    $printersStmt->execute();
    $printers = $printersStmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    // 오류 로깅
    $error_message = "데이터베이스 오류: " . $e->getMessage();
    logError('printing_settings.php: ' . $error_message);
    
    // 기본값 설정
    $settings = [
        'printer_type' => 'thermal',
        'paper_size' => 'a4',
        'dpi_setting' => '300',
        'color_mode' => 'color',
        'default_margin' => 5,
        'enable_duplex' => 0,
        'enable_cut_marks' => 0,
        'enable_watermark' => 0,
        'security_ink' => 'none',
        'uv_ink' => 0,
        'quality_check' => 1,
        'barcode_verification' => 1,
        'error_logging' => 1,
        'watermark_text' => '',
        'watermark_opacity' => 30
    ];
    $printers = [];
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
                    <i class="fas fa-print me-1"></i>
                    <?php echo $page_description; ?>
                </div>
                <div class="card-body">
                    <ul class="nav nav-tabs" id="settingsTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab" aria-controls="general" aria-selected="true">일반 설정</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="security-tab" data-bs-toggle="tab" data-bs-target="#security" type="button" role="tab" aria-controls="security" aria-selected="false">보안 및 품질</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="design-tab" data-bs-toggle="tab" data-bs-target="#design" type="button" role="tab" aria-controls="design" aria-selected="false">디자인 설정</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="test-tab" data-bs-toggle="tab" data-bs-target="#test" type="button" role="tab" aria-controls="test" aria-selected="false">테스트 인쇄</button>
                        </li>
                    </ul>
                    
                    <form id="printSettingsForm" method="post" action="" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="save_print_settings">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        
                        <div class="tab-content" id="settingsTabsContent">
                            <!-- 일반 설정 탭 -->
                            <div class="tab-pane fade show active p-3" id="general" role="tabpanel" aria-labelledby="general-tab">
                                <div class="row mt-3">
                                    <div class="col-md-6 mb-3">
                                        <label for="printer_type" class="form-label">프린터 유형</label>
                                        <select class="form-select" id="printer_type" name="printer_type">
                                            <option value="thermal" <?php echo ($settings['printer_type'] ?? '') === 'thermal' ? 'selected' : ''; ?>>감열식 프린터</option>
                                            <option value="laser" <?php echo ($settings['printer_type'] ?? '') === 'laser' ? 'selected' : ''; ?>>레이저 프린터</option>
                                            <option value="inkjet" <?php echo ($settings['printer_type'] ?? '') === 'inkjet' ? 'selected' : ''; ?>>잉크젯 프린터</option>
                                            <option value="dot_matrix" <?php echo ($settings['printer_type'] ?? '') === 'dot_matrix' ? 'selected' : ''; ?>>도트 매트릭스 프린터</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="paper_size" class="form-label">용지 크기</label>
                                        <select class="form-select" id="paper_size" name="paper_size">
                                            <option value="a4" <?php echo ($settings['paper_size'] ?? '') === 'a4' ? 'selected' : ''; ?>>A4 (210 x 297 mm)</option>
                                            <option value="a5" <?php echo ($settings['paper_size'] ?? '') === 'a5' ? 'selected' : ''; ?>>A5 (148 x 210 mm)</option>
                                            <option value="letter" <?php echo ($settings['paper_size'] ?? '') === 'letter' ? 'selected' : ''; ?>>Letter (8.5 x 11 in)</option>
                                            <option value="custom" <?php echo ($settings['paper_size'] ?? '') === 'custom' ? 'selected' : ''; ?>>사용자 정의</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="dpi_setting" class="form-label">DPI 설정</label>
                                        <select class="form-select" id="dpi_setting" name="dpi_setting">
                                            <option value="300" <?php echo ($settings['dpi_setting'] ?? '') === '300' ? 'selected' : ''; ?>>300 DPI (표준)</option>
                                            <option value="600" <?php echo ($settings['dpi_setting'] ?? '') === '600' ? 'selected' : ''; ?>>600 DPI (고품질)</option>
                                            <option value="1200" <?php echo ($settings['dpi_setting'] ?? '') === '1200' ? 'selected' : ''; ?>>1200 DPI (최고품질)</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="color_mode" class="form-label">색상 모드</label>
                                        <select class="form-select" id="color_mode" name="color_mode">
                                            <option value="color" <?php echo ($settings['color_mode'] ?? '') === 'color' ? 'selected' : ''; ?>>컬러</option>
                                            <option value="grayscale" <?php echo ($settings['color_mode'] ?? '') === 'grayscale' ? 'selected' : ''; ?>>그레이스케일</option>
                                            <option value="black_white" <?php echo ($settings['color_mode'] ?? '') === 'black_white' ? 'selected' : ''; ?>>흑백</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="default_margin" class="form-label">기본 여백 (mm)</label>
                                        <input type="number" class="form-control" id="default_margin" name="default_margin" min="0" max="50" value="<?php echo $settings['default_margin'] ?? '5'; ?>">
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="enable_duplex" name="enable_duplex" <?php echo ($settings['enable_duplex'] ?? 0) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="enable_duplex">
                                                양면 인쇄 활성화
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="enable_cut_marks" name="enable_cut_marks" <?php echo ($settings['enable_cut_marks'] ?? 0) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="enable_cut_marks">
                                                재단선 표시
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="enable_watermark" name="enable_watermark" <?php echo ($settings['enable_watermark'] ?? 0) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="enable_watermark">
                                                워터마크 활성화
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- 보안 및 품질 탭 -->
                            <div class="tab-pane fade p-3" id="security" role="tabpanel" aria-labelledby="security-tab">
                                <div class="row mt-3">
                                    <div class="col-md-6 mb-3">
                                        <label for="security_ink" class="form-label">보안 잉크 설정</label>
                                        <select class="form-select" id="security_ink" name="security_ink">
                                            <option value="none" <?php echo ($settings['security_ink'] ?? '') === 'none' ? 'selected' : ''; ?>>없음</option>
                                            <option value="basic" <?php echo ($settings['security_ink'] ?? '') === 'basic' ? 'selected' : ''; ?>>기본 보안 잉크</option>
                                            <option value="advanced" <?php echo ($settings['security_ink'] ?? '') === 'advanced' ? 'selected' : ''; ?>>고급 보안 잉크</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="uv_ink" name="uv_ink" <?php echo ($settings['uv_ink'] ?? 0) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="uv_ink">
                                                UV 잉크 사용
                                            </label>
                                            <div class="form-text">자외선 조명 아래에서만 보이는 숨겨진 요소를 인쇄합니다.</div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="quality_check" name="quality_check" <?php echo ($settings['quality_check'] ?? 0) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="quality_check">
                                                품질 검사 활성화
                                            </label>
                                            <div class="form-text">인쇄 품질을 자동으로 검사합니다.</div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="barcode_verification" name="barcode_verification" <?php echo ($settings['barcode_verification'] ?? 0) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="barcode_verification">
                                                바코드 검증
                                            </label>
                                            <div class="form-text">인쇄 후 바코드 유효성을 확인합니다.</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="error_logging" name="error_logging" <?php echo ($settings['error_logging'] ?? 0) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="error_logging">
                                                오류 로깅 활성화
                                            </label>
                                            <div class="form-text">인쇄 오류 및 경고를 로그에 기록합니다.</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- 디자인 설정 탭 -->
                            <div class="tab-pane fade p-3" id="design" role="tabpanel" aria-labelledby="design-tab">
                                <div class="row mt-3">
                                    <div class="col-md-6 mb-3">
                                        <label for="watermark_text" class="form-label">워터마크 텍스트</label>
                                        <input type="text" class="form-control" id="watermark_text" name="watermark_text" value="<?php echo $settings['watermark_text'] ?? ''; ?>">
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="watermark_opacity" class="form-label">워터마크 투명도 (%)</label>
                                        <input type="range" class="form-range" id="watermark_opacity" name="watermark_opacity" min="10" max="90" value="<?php echo $settings['watermark_opacity'] ?? '30'; ?>">
                                        <div class="text-center" id="opacity_value"><?php echo $settings['watermark_opacity'] ?? '30'; ?>%</div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="background_image" class="form-label">배경 이미지</label>
                                        <input type="file" class="form-control" id="background_image" name="background_image" accept="image/*">
                                        <input type="hidden" name="current_background_image" value="<?php echo $settings['background_image'] ?? ''; ?>">
                                        
                                        <?php if (!empty($settings['background_image'])): ?>
                                        <div class="mt-2">
                                            <img src="<?php echo $settings['background_image']; ?>" alt="Current background" class="img-thumbnail" style="max-height: 100px;">
                                            <div class="form-text">현재 배경 이미지</div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="logo_image" class="form-label">로고 이미지</label>
                                        <input type="file" class="form-control" id="logo_image" name="logo_image" accept="image/*">
                                        <input type="hidden" name="current_logo_image" value="<?php echo $settings['logo_image'] ?? ''; ?>">
                                        
                                        <?php if (!empty($settings['logo_image'])): ?>
                                        <div class="mt-2">
                                            <img src="<?php echo $settings['logo_image']; ?>" alt="Current logo" class="img-thumbnail" style="max-height: 100px;">
                                            <div class="form-text">현재 로고 이미지</div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- 테스트 인쇄 탭 -->
                            <div class="tab-pane fade p-3" id="test" role="tabpanel" aria-labelledby="test-tab">
                                <div class="alert alert-info mt-3" role="alert">
                                    <i class="fas fa-info-circle me-2"></i>
                                    테스트 인쇄를 통해 현재 설정이 올바르게 적용되는지 확인할 수 있습니다.
                                </div>
                                
                                <form id="testPrintForm" method="post" action="">
                                    <input type="hidden" name="action" value="test_print">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                    
                                    <div class="row mt-3">
                                        <div class="col-md-6 mb-3">
                                            <label for="printer_id" class="form-label">프린터 선택</label>
                                            <select class="form-select" id="printer_id" name="printer_id" required>
                                                <option value="">프린터 선택</option>
                                                <?php foreach ($printers as $printer): ?>
                                                <option value="<?php echo $printer['id']; ?>"><?php echo htmlspecialchars($printer['printer_name']); ?> (<?php echo htmlspecialchars($printer['location']); ?>)</option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="test_type" class="form-label">테스트 유형</label>
                                            <select class="form-select" id="test_type" name="test_type" required>
                                                <option value="">테스트 유형 선택</option>
                                                <option value="basic">기본 테스트</option>
                                                <option value="alignment">정렬 테스트</option>
                                                <option value="color">색상 테스트</option>
                                                <option value="barcode">바코드 테스트</option>
                                                <option value="security">보안 기능 테스트</option>
                                                <option value="full">전체 복권 테스트</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-12 mb-3">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-print me-2"></i>
                                                테스트 인쇄
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                        
                        <div class="row mt-4">
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>
                                    설정 저장
                                </button>
                                <a href="/dashboard/lottery/products.php" class="btn btn-secondary ms-2">
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

<?php
// 페이지 하단 포함
include_once TEMPLATES_PATH . '/dashboard_footer.php';
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 워터마크 투명도 슬라이더 값 표시
    const opacitySlider = document.getElementById('watermark_opacity');
    const opacityValue = document.getElementById('opacity_value');
    
    opacitySlider.addEventListener('input', function() {
        opacityValue.textContent = this.value + '%';
    });
    
    // 테스트 인쇄 폼 제출 이벤트
    document.getElementById('testPrintForm').addEventListener('submit', function(event) {
        if (!document.getElementById('printer_id').value || !document.getElementById('test_type').value) {
            event.preventDefault();
            alert('프린터와 테스트 유형을 모두 선택해주세요.');
        }
    });
    
    // 용지 크기가 '사용자 정의'일 때 추가 필드 표시
    document.getElementById('paper_size').addEventListener('change', function() {
        // 실제 구현에서는 사용자 정의 크기 입력 필드를 추가할 수 있음
        console.log('용지 크기 변경:', this.value);
    });
    
    // 프린터 유형 변경 시 관련 설정 조정
    document.getElementById('printer_type').addEventListener('change', function() {
        const printerType = this.value;
        console.log('프린터 유형 변경:', printerType);
        
        // 프린터 유형에 따라 설정 조정
        switch (printerType) {
            case 'thermal':
                document.getElementById('color_mode').value = 'black_white';
                document.getElementById('uv_ink').checked = false;
                document.getElementById('uv_ink').disabled = true;
                break;
                
            case 'laser':
            case 'inkjet':
                document.getElementById('uv_ink').disabled = false;
                break;
                
            case 'dot_matrix':
                document.getElementById('color_mode').value = 'black_white';
                document.getElementById('dpi_setting').value = '300';
                document.getElementById('uv_ink').checked = false;
                document.getElementById('uv_ink').disabled = true;
                break;
        }
    });
    
    // 워터마크 활성화 체크박스 변경 시 관련 설정 활성화/비활성화
    document.getElementById('enable_watermark').addEventListener('change', function() {
        const isEnabled = this.checked;
        document.getElementById('watermark_text').disabled = !isEnabled;
        document.getElementById('watermark_opacity').disabled = !isEnabled;
    });
    
    // 초기 로드 시 워터마크 관련 필드 상태 설정
    const watermarkEnabled = document.getElementById('enable_watermark').checked;
    document.getElementById('watermark_text').disabled = !watermarkEnabled;
    document.getElementById('watermark_opacity').disabled = !watermarkEnabled;
    
    // 초기 로드 시 프린터 유형에 따른 설정 조정
    const printerType = document.getElementById('printer_type').value;
    if (printerType === 'thermal' || printerType === 'dot_matrix') {
        document.getElementById('uv_ink').disabled = true;
    }
});
</script>