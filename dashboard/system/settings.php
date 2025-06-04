<?php
/**
 * 시스템 설정 페이지
 */

// 오류 표시 설정
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 헤더 설정
header('Content-Type: text/html; charset=utf-8');

// 출력 버퍼링 시작
ob_start();

// 설정 및 공통 함수
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';
require_once '../../includes/db.php';

// 현재 페이지 정보
$pageTitle = "시스템 설정";
$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir = dirname($_SERVER['PHP_SELF']);
$parts = explode('/', $currentDir);
$currentSection = end($parts);

// 데이터베이스 연결
$db = getDbConnection();

// 설정 저장 처리
$successMessage = "";
$errorMessage = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_settings') {
    // CSRF 토큰 검증 (실제 구현 시 추가)
    
    try {
        // 설정 유형에 따라 저장 처리
        $settingType = $_POST['setting_type'] ?? 'general';
        
        switch ($settingType) {
            case 'general':
                // 일반 설정 저장
                $systemName = $_POST['system_name'] ?? '';
                $systemLogo = $_FILES['system_logo'] ?? null;
                $systemTheme = $_POST['system_theme'] ?? 'default';
                $timezone = $_POST['timezone'] ?? 'Asia/Seoul';
                $dateFormat = $_POST['date_format'] ?? 'Y-m-d';
                $timeFormat = $_POST['time_format'] ?? 'H:i:s';
                
                // 설정 업데이트 쿼리 (예시)
                // $stmt = $db->prepare("UPDATE system_settings SET value = ? WHERE setting_key = ?");
                // $stmt->execute([$systemName, 'system_name']);
                // ...
                
                $successMessage = "일반 설정이 성공적으로 저장되었습니다.";
                break;
                
            case 'email':
                // 이메일 설정 저장
                $smtpHost = $_POST['smtp_host'] ?? '';
                $smtpPort = $_POST['smtp_port'] ?? '587';
                $smtpSecurity = $_POST['smtp_security'] ?? 'tls';
                $smtpUsername = $_POST['smtp_username'] ?? '';
                $smtpPassword = $_POST['smtp_password'] ?? '';
                $senderEmail = $_POST['sender_email'] ?? '';
                $senderName = $_POST['sender_name'] ?? '';
                
                // 설정 업데이트 쿼리 (예시)
                // ...
                
                $successMessage = "이메일 설정이 성공적으로 저장되었습니다.";
                break;
                
            case 'notification':
                // 알림 설정 저장
                $enableEmailNotification = isset($_POST['enable_email_notification']) ? 1 : 0;
                $enableSmsNotification = isset($_POST['enable_sms_notification']) ? 1 : 0;
                $enableSystemNotification = isset($_POST['enable_system_notification']) ? 1 : 0;
                
                // 설정 업데이트 쿼리 (예시)
                // ...
                
                $successMessage = "알림 설정이 성공적으로 저장되었습니다.";
                break;
                
            case 'login':
                // 로그인 설정 저장
                $passwordMinLength = $_POST['password_min_length'] ?? '8';
                $passwordComplexity = $_POST['password_complexity'] ?? 'medium';
                $passwordExpireDays = $_POST['password_expire_days'] ?? '90';
                $maxLoginAttempts = $_POST['max_login_attempts'] ?? '5';
                $lockoutDuration = $_POST['lockout_duration'] ?? '30';
                $enableTwoFactorAuth = isset($_POST['enable_two_factor_auth']) ? 1 : 0;
                
                // 설정 업데이트 쿼리 (예시)
                // ...
                
                $successMessage = "로그인 설정이 성공적으로 저장되었습니다.";
                break;
                
            case 'performance':
                // 성능 설정 저장
                $enableCache = isset($_POST['enable_cache']) ? 1 : 0;
                $cacheLifetime = $_POST['cache_lifetime'] ?? '3600';
                $paginationLimit = $_POST['pagination_limit'] ?? '20';
                
                // 설정 업데이트 쿼리 (예시)
                // ...
                
                $successMessage = "성능 및 캐시 설정이 성공적으로 저장되었습니다.";
                break;
                
            default:
                $errorMessage = "잘못된 설정 유형입니다.";
                break;
        }
    } catch (Exception $e) {
        $errorMessage = "설정 저장 중 오류가 발생했습니다: " . $e->getMessage();
    }
}

// 현재 설정 불러오기 (예시)
// 실제로는 데이터베이스에서 설정 값을 가져옴
$currentSettings = [
    'general' => [
        'system_name' => '쿠시 로또 관리 시스템',
        'system_logo' => 'assets/img/logo.png',
        'system_theme' => 'default',
        'timezone' => 'Asia/Seoul',
        'date_format' => 'Y-m-d',
        'time_format' => 'H:i:s'
    ],
    'email' => [
        'smtp_host' => 'smtp.example.com',
        'smtp_port' => '587',
        'smtp_security' => 'tls',
        'smtp_username' => 'noreply@example.com',
        'smtp_password' => '',
        'sender_email' => 'noreply@example.com',
        'sender_name' => '쿠시 로또 관리 시스템'
    ],
    'notification' => [
        'enable_email_notification' => 1,
        'enable_sms_notification' => 0,
        'enable_system_notification' => 1
    ],
    'login' => [
        'password_min_length' => '8',
        'password_complexity' => 'medium',
        'password_expire_days' => '90',
        'max_login_attempts' => '5',
        'lockout_duration' => '30',
        'enable_two_factor_auth' => 0
    ],
    'performance' => [
        'enable_cache' => 1,
        'cache_lifetime' => '3600',
        'pagination_limit' => '20'
    ]
];

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
                    <li class="breadcrumb-item"><a href="<?php echo SERVER_URL; ?>/dashboard/">홈</a></li>
                    <li class="breadcrumb-item active">시스템 관리</li>
                    <li class="breadcrumb-item active">시스템 설정</li>
                </ol>
            </div>
        </div>
    </div>
</div>
<!-- /.content-header -->

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <?php if (!empty($successMessage)): ?>
        <div class="alert alert-success alert-dismissible">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
            <h5><i class="icon fas fa-check"></i> 성공!</h5>
            <?php echo $successMessage; ?>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger alert-dismissible">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
            <h5><i class="icon fas fa-ban"></i> 오류!</h5>
            <?php echo $errorMessage; ?>
        </div>
        <?php endif; ?>

        <!-- 설정 탭 카드 -->
        <div class="card card-primary card-outline card-tabs">
            <div class="card-header p-0 pt-1 border-bottom-0">
                <ul class="nav nav-tabs" id="settings-tab" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="settings-general-tab" data-toggle="pill" href="#settings-general" role="tab" aria-controls="settings-general" aria-selected="true">일반 설정</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="settings-email-tab" data-toggle="pill" href="#settings-email" role="tab" aria-controls="settings-email" aria-selected="false">이메일 설정</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="settings-notification-tab" data-toggle="pill" href="#settings-notification" role="tab" aria-controls="settings-notification" aria-selected="false">알림 설정</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="settings-login-tab" data-toggle="pill" href="#settings-login" role="tab" aria-controls="settings-login" aria-selected="false">로그인 설정</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="settings-performance-tab" data-toggle="pill" href="#settings-performance" role="tab" aria-controls="settings-performance" aria-selected="false">성능 및 캐시</a>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content" id="settings-tabContent">
                    <!-- 일반 설정 탭 -->
                    <div class="tab-pane fade show active" id="settings-general" role="tabpanel" aria-labelledby="settings-general-tab">
                        <form action="" method="post" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="save_settings">
                            <input type="hidden" name="setting_type" value="general">
                            
                            <div class="form-group">
                                <label for="system_name">시스템 이름</label>
                                <input type="text" class="form-control" id="system_name" name="system_name" value="<?php echo htmlspecialchars($currentSettings['general']['system_name']); ?>" required>
                                <small class="form-text text-muted">시스템 상단 표시되는 이름입니다.</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="system_logo">시스템 로고</label>
                                <div class="input-group">
                                    <div class="custom-file">
                                        <input type="file" class="custom-file-input" id="system_logo" name="system_logo">
                                        <label class="custom-file-label" for="system_logo">파일 선택</label>
                                    </div>
                                </div>
                                <small class="form-text text-muted">권장 크기: 200x60 픽셀, PNG 또는 SVG 형식</small>
                                <?php if (!empty($currentSettings['general']['system_logo'])): ?>
                                <div class="mt-2">
                                    <img src="<?php echo SERVER_URL . '/' . $currentSettings['general']['system_logo']; ?>" alt="현재 로고" style="max-height: 50px;">
                                    <p class="text-muted mb-0">현재 로고</p>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="form-group">
                                <label for="system_theme">시스템 테마</label>
                                <select class="form-control" id="system_theme" name="system_theme">
                                    <option value="default" <?php if ($currentSettings['general']['system_theme'] === 'default') echo 'selected'; ?>>기본</option>
                                    <option value="dark" <?php if ($currentSettings['general']['system_theme'] === 'dark') echo 'selected'; ?>>다크 모드</option>
                                    <option value="light" <?php if ($currentSettings['general']['system_theme'] === 'light') echo 'selected'; ?>>라이트 모드</option>
                                    <option value="blue" <?php if ($currentSettings['general']['system_theme'] === 'blue') echo 'selected'; ?>>블루</option>
                                    <option value="green" <?php if ($currentSettings['general']['system_theme'] === 'green') echo 'selected'; ?>>그린</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="timezone">시간대</label>
                                <select class="form-control" id="timezone" name="timezone">
                                    <option value="Asia/Seoul" <?php if ($currentSettings['general']['timezone'] === 'Asia/Seoul') echo 'selected'; ?>>아시아/서울 (GMT+9:00)</option>
                                    <option value="Asia/Tokyo" <?php if ($currentSettings['general']['timezone'] === 'Asia/Tokyo') echo 'selected'; ?>>아시아/도쿄 (GMT+9:00)</option>
                                    <option value="Asia/Shanghai" <?php if ($currentSettings['general']['timezone'] === 'Asia/Shanghai') echo 'selected'; ?>>아시아/상하이 (GMT+8:00)</option>
                                    <option value="America/New_York" <?php if ($currentSettings['general']['timezone'] === 'America/New_York') echo 'selected'; ?>>미국/뉴욕 (GMT-5:00)</option>
                                    <option value="Europe/London" <?php if ($currentSettings['general']['timezone'] === 'Europe/London') echo 'selected'; ?>>유럽/런던 (GMT+0:00)</option>
                                </select>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="date_format">날짜 형식</label>
                                        <select class="form-control" id="date_format" name="date_format">
                                            <option value="Y-m-d" <?php if ($currentSettings['general']['date_format'] === 'Y-m-d') echo 'selected'; ?>>YYYY-MM-DD (2025-05-18)</option>
                                            <option value="d/m/Y" <?php if ($currentSettings['general']['date_format'] === 'd/m/Y') echo 'selected'; ?>>DD/MM/YYYY (18/05/2025)</option>
                                            <option value="m/d/Y" <?php if ($currentSettings['general']['date_format'] === 'm/d/Y') echo 'selected'; ?>>MM/DD/YYYY (05/18/2025)</option>
                                            <option value="d.m.Y" <?php if ($currentSettings['general']['date_format'] === 'd.m.Y') echo 'selected'; ?>>DD.MM.YYYY (18.05.2025)</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="time_format">시간 형식</label>
                                        <select class="form-control" id="time_format" name="time_format">
                                            <option value="H:i:s" <?php if ($currentSettings['general']['time_format'] === 'H:i:s') echo 'selected'; ?>>24시간 (15:30:00)</option>
                                            <option value="h:i:s A" <?php if ($currentSettings['general']['time_format'] === 'h:i:s A') echo 'selected'; ?>>12시간 (03:30:00 PM)</option>
                                            <option value="H:i" <?php if ($currentSettings['general']['time_format'] === 'H:i') echo 'selected'; ?>>24시간, 초 없음 (15:30)</option>
                                            <option value="h:i A" <?php if ($currentSettings['general']['time_format'] === 'h:i A') echo 'selected'; ?>>12시간, 초 없음 (03:30 PM)</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">설정 저장</button>
                        </form>
                    </div>
                    
                    <!-- 이메일 설정 탭 -->
                    <div class="tab-pane fade" id="settings-email" role="tabpanel" aria-labelledby="settings-email-tab">
                        <form action="" method="post">
                            <input type="hidden" name="action" value="save_settings">
                            <input type="hidden" name="setting_type" value="email">
                            
                            <div class="form-group">
                                <label for="smtp_host">SMTP 서버</label>
                                <input type="text" class="form-control" id="smtp_host" name="smtp_host" value="<?php echo htmlspecialchars($currentSettings['email']['smtp_host']); ?>">
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="smtp_port">SMTP 포트</label>
                                        <input type="text" class="form-control" id="smtp_port" name="smtp_port" value="<?php echo htmlspecialchars($currentSettings['email']['smtp_port']); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="smtp_security">보안 연결</label>
                                        <select class="form-control" id="smtp_security" name="smtp_security">
                                            <option value="tls" <?php if ($currentSettings['email']['smtp_security'] === 'tls') echo 'selected'; ?>>TLS</option>
                                            <option value="ssl" <?php if ($currentSettings['email']['smtp_security'] === 'ssl') echo 'selected'; ?>>SSL</option>
                                            <option value="none" <?php if ($currentSettings['email']['smtp_security'] === 'none') echo 'selected'; ?>>없음</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="smtp_username">SMTP 사용자명</label>
                                <input type="text" class="form-control" id="smtp_username" name="smtp_username" value="<?php echo htmlspecialchars($currentSettings['email']['smtp_username']); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="smtp_password">SMTP 비밀번호</label>
                                <input type="password" class="form-control" id="smtp_password" name="smtp_password" value="<?php echo htmlspecialchars($currentSettings['email']['smtp_password']); ?>">
                                <small class="form-text text-muted">비밀번호를 변경하지 않으려면 비워두세요.</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="sender_email">발신자 이메일</label>
                                <input type="email" class="form-control" id="sender_email" name="sender_email" value="<?php echo htmlspecialchars($currentSettings['email']['sender_email']); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="sender_name">발신자 이름</label>
                                <input type="text" class="form-control" id="sender_name" name="sender_name" value="<?php echo htmlspecialchars($currentSettings['email']['sender_name']); ?>">
                            </div>
                            
                            <div class="form-group">
                                <button type="button" class="btn btn-info" id="test_email_btn">이메일 설정 테스트</button>
                                <small class="form-text text-muted">테스트 이메일을 발송하여 설정을 확인합니다.</small>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">설정 저장</button>
                        </form>
                    </div>
                    
                    <!-- 알림 설정 탭 -->
                    <div class="tab-pane fade" id="settings-notification" role="tabpanel" aria-labelledby="settings-notification-tab">
                        <form action="" method="post">
                            <input type="hidden" name="action" value="save_settings">
                            <input type="hidden" name="setting_type" value="notification">
                            
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="enable_email_notification" name="enable_email_notification" <?php if ($currentSettings['notification']['enable_email_notification']) echo 'checked'; ?>>
                                    <label class="custom-control-label" for="enable_email_notification">이메일 알림 활성화</label>
                                </div>
                                <small class="form-text text-muted">중요 이벤트에 대해 이메일 알림을 보냅니다.</small>
                            </div>
                            
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="enable_sms_notification" name="enable_sms_notification" <?php if ($currentSettings['notification']['enable_sms_notification']) echo 'checked'; ?>>
                                    <label class="custom-control-label" for="enable_sms_notification">SMS 알림 활성화</label>
                                </div>
                                <small class="form-text text-muted">중요 이벤트에 대해 SMS 알림을 보냅니다.</small>
                            </div>
                            
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="enable_system_notification" name="enable_system_notification" <?php if ($currentSettings['notification']['enable_system_notification']) echo 'checked'; ?>>
                                    <label class="custom-control-label" for="enable_system_notification">시스템 내 알림 활성화</label>
                                </div>
                                <small class="form-text text-muted">사용자 인터페이스에 알림을 표시합니다.</small>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">설정 저장</button>
                        </form>
                    </div>
                    
                    <!-- 로그인 설정 탭 -->
                    <div class="tab-pane fade" id="settings-login" role="tabpanel" aria-labelledby="settings-login-tab">
                        <form action="" method="post">
                            <input type="hidden" name="action" value="save_settings">
                            <input type="hidden" name="setting_type" value="login">
                            
                            <div class="form-group">
                                <label for="password_min_length">비밀번호 최소 길이</label>
                                <input type="number" class="form-control" id="password_min_length" name="password_min_length" min="6" max="20" value="<?php echo htmlspecialchars($currentSettings['login']['password_min_length']); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="password_complexity">비밀번호 복잡성</label>
                                <select class="form-control" id="password_complexity" name="password_complexity">
                                    <option value="low" <?php if ($currentSettings['login']['password_complexity'] === 'low') echo 'selected'; ?>>낮음 (숫자만 필요)</option>
                                    <option value="medium" <?php if ($currentSettings['login']['password_complexity'] === 'medium') echo 'selected'; ?>>중간 (숫자 + 알파벳)</option>
                                    <option value="high" <?php if ($currentSettings['login']['password_complexity'] === 'high') echo 'selected'; ?>>높음 (숫자 + 대소문자 + 특수문자)</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="password_expire_days">비밀번호 만료 기간 (일)</label>
                                <input type="number" class="form-control" id="password_expire_days" name="password_expire_days" min="0" value="<?php echo htmlspecialchars($currentSettings['login']['password_expire_days']); ?>">
                                <small class="form-text text-muted">0으로 설정하면 만료되지 않습니다.</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="max_login_attempts">최대 로그인 시도 횟수</label>
                                <input type="number" class="form-control" id="max_login_attempts" name="max_login_attempts" min="1" max="10" value="<?php echo htmlspecialchars($currentSettings['login']['max_login_attempts']); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="lockout_duration">계정 잠금 시간 (분)</label>
                                <input type="number" class="form-control" id="lockout_duration" name="lockout_duration" min="5" max="1440" value="<?php echo htmlspecialchars($currentSettings['login']['lockout_duration']); ?>">
                            </div>
                            
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="enable_two_factor_auth" name="enable_two_factor_auth" <?php if ($currentSettings['login']['enable_two_factor_auth']) echo 'checked'; ?>>
                                    <label class="custom-control-label" for="enable_two_factor_auth">2단계 인증 활성화</label>
                                </div>
                                <small class="form-text text-muted">사용자가 2단계 인증을 설정할 수 있도록 합니다.</small>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">설정 저장</button>
                        </form>
                    </div>
                    
                    <!-- 성능 및 캐시 설정 탭 -->
                    <div class="tab-pane fade" id="settings-performance" role="tabpanel" aria-labelledby="settings-performance-tab">
                        <form action="" method="post">
                            <input type="hidden" name="action" value="save_settings">
                            <input type="hidden" name="setting_type" value="performance">
                            
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="enable_cache" name="enable_cache" <?php if ($currentSettings['performance']['enable_cache']) echo 'checked'; ?>>
                                    <label class="custom-control-label" for="enable_cache">시스템 캐시 활성화</label>
                                </div>
                                <small class="form-text text-muted">시스템 성능을 향상시키기 위한 캐싱을 활성화합니다.</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="cache_lifetime">캐시 유효 시간 (초)</label>
                                <input type="number" class="form-control" id="cache_lifetime" name="cache_lifetime" min="60" max="86400" value="<?php echo htmlspecialchars($currentSettings['performance']['cache_lifetime']); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="pagination_limit">페이지당 기본 항목 수</label>
                                <input type="number" class="form-control" id="pagination_limit" name="pagination_limit" min="10" max="100" value="<?php echo htmlspecialchars($currentSettings['performance']['pagination_limit']); ?>">
                            </div>
                            
                            <div class="form-group">
                                <button type="button" class="btn btn-warning" id="clear_cache_btn">캐시 비우기</button>
                                <small class="form-text text-muted">모든 시스템 캐시를 비웁니다.</small>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">설정 저장</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <!-- /.card -->
    </div>
</section>
<!-- /.content -->

<!-- Test Email Modal -->
<div class="modal fade" id="testEmailModal" tabindex="-1" role="dialog" aria-labelledby="testEmailModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="testEmailModalLabel">테스트 이메일 발송</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="test_email">테스트 이메일 주소</label>
                    <input type="email" class="form-control" id="test_email" placeholder="테스트 이메일을 받을 주소를 입력하세요">
                </div>
                <div id="test_email_result"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">닫기</button>
                <button type="button" class="btn btn-primary" id="send_test_email">테스트 이메일 발송</button>
            </div>
        </div>
    </div>
</div>

<script>
// 파일 업로드 시 파일명 표시
$(document).ready(function() {
    bsCustomFileInput.init();
    
    // 설정 탭 유지
    var activeTab = localStorage.getItem('activeSettingsTab');
    if (activeTab) {
        $('#settings-tab a[href="' + activeTab + '"]').tab('show');
    }
    
    $('#settings-tab a').on('shown.bs.tab', function (e) {
        localStorage.setItem('activeSettingsTab', $(e.target).attr('href'));
    });
    
    // 테스트 이메일 버튼 클릭 이벤트
    $('#test_email_btn').click(function() {
        $('#testEmailModal').modal('show');
    });
    
    // 테스트 이메일 발송 버튼 클릭 이벤트
    $('#send_test_email').click(function() {
        var testEmail = $('#test_email').val();
        if (!testEmail) {
            $('#test_email_result').html('<div class="alert alert-danger">이메일 주소를 입력하세요.</div>');
            return;
        }
        
        $('#test_email_result').html('<div class="alert alert-info">테스트 이메일을 발송 중입니다...</div>');
        
        // AJAX로 테스트 이메일 발송 요청 (예시)
        $.ajax({
            url: 'send_test_email.php',
            type: 'POST',
            data: {
                email: testEmail,
                smtp_host: $('#smtp_host').val(),
                smtp_port: $('#smtp_port').val(),
                smtp_security: $('#smtp_security').val(),
                smtp_username: $('#smtp_username').val(),
                smtp_password: $('#smtp_password').val(),
                sender_email: $('#sender_email').val(),
                sender_name: $('#sender_name').val()
            },
            success: function(response) {
                $('#test_email_result').html('<div class="alert alert-success">테스트 이메일이 성공적으로 발송되었습니다.</div>');
            },
            error: function(xhr, status, error) {
                $('#test_email_result').html('<div class="alert alert-danger">테스트 이메일 발송 실패: ' + error + '</div>');
            }
        });
    });
    
    // 캐시 비우기 버튼 클릭 이벤트
    $('#clear_cache_btn').click(function() {
        if (confirm('모든 시스템 캐시를 비우시겠습니까?')) {
            $.ajax({
                url: 'clear_cache.php',
                type: 'POST',
                success: function(response) {
                    alert('캐시가 성공적으로 비워졌습니다.');
                },
                error: function(xhr, status, error) {
                    alert('캐시 비우기 실패: ' + error);
                }
            });
        }
    });
});
</script>

<?php
// 템플릿 푸터 포함
include_once TEMPLATES_PATH . '/dashboard_footer.php';

// 출력 버퍼 플러시
ob_end_flush();
?>