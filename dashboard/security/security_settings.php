<?php
/**
 * 보안 설정 페이지
 * 
 * 이 페이지는 시스템의 보안 설정을 관리합니다.
 * 비밀번호 정책, 로그인 제한, IP 차단 정책 등을 설정할 수 있습니다.
 */

// 공통 파일 포함
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// 페이지 변수 설정
$pageTitle = "보안 설정";
$currentSection = "security";
$currentPage = basename($_SERVER['PHP_SELF']);

// 데이터베이스 연결
$db = get_db_connection();

// 현재 설정 가져오기
$sql = "SELECT * FROM system_settings WHERE category = 'security'";
$stmt = $db->prepare($sql);
$stmt->execute();
$settings = [];

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// 기본값 설정
$defaults = [
    'password_min_length' => '8',
    'password_complexity' => 'medium',
    'password_expiry_days' => '90',
    'max_login_attempts' => '5',
    'account_lockout_duration' => '30',
    'session_timeout' => '30',
    'require_2fa' => '0',
    'ip_whitelist_enabled' => '0',
    'ip_blacklist_enabled' => '1',
    'captcha_enabled' => '1',
    'auto_blocklist' => '1',
    'suspicious_login_alert' => '1',
    'login_notification' => '1'
];

// 기본값으로 빈 설정 채우기
foreach ($defaults as $key => $value) {
    if (!isset($settings[$key])) {
        $settings[$key] = $value;
    }
}

// 폼 제출 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF 토큰 검증 (실제 구현 시 활성화)
    // if (!verify_csrf_token()) {
    //     die("CSRF 토큰이 유효하지 않습니다.");
    // }
    
    // 비밀번호 정책 저장
    if (isset($_POST['password_policy'])) {
        $passwordMinLength = isset($_POST['password_min_length']) ? (int)$_POST['password_min_length'] : 8;
        $passwordComplexity = isset($_POST['password_complexity']) ? $_POST['password_complexity'] : 'medium';
        $passwordExpiryDays = isset($_POST['password_expiry_days']) ? (int)$_POST['password_expiry_days'] : 90;
        $maxLoginAttempts = isset($_POST['max_login_attempts']) ? (int)$_POST['max_login_attempts'] : 5;
        $accountLockoutDuration = isset($_POST['account_lockout_duration']) ? (int)$_POST['account_lockout_duration'] : 30;
        $require2FA = isset($_POST['require_2fa']) ? 1 : 0;

        // 설정 업데이트
        updateSetting($db, 'password_min_length', $passwordMinLength);
        updateSetting($db, 'password_complexity', $passwordComplexity);
        updateSetting($db, 'password_expiry_days', $passwordExpiryDays);
        updateSetting($db, 'max_login_attempts', $maxLoginAttempts);
        updateSetting($db, 'account_lockout_duration', $accountLockoutDuration);
        updateSetting($db, 'require_2fa', $require2FA);
        
        // 설정 값 업데이트
        $settings['password_min_length'] = $passwordMinLength;
        $settings['password_complexity'] = $passwordComplexity;
        $settings['password_expiry_days'] = $passwordExpiryDays;
        $settings['max_login_attempts'] = $maxLoginAttempts;
        $settings['account_lockout_duration'] = $accountLockoutDuration;
        $settings['require_2fa'] = $require2FA;
        
        logInfo("비밀번호 정책 설정이 업데이트되었습니다.", "security");
    }
    
    // 세션 설정 저장
    elseif (isset($_POST['session_settings'])) {
        $sessionTimeout = isset($_POST['session_timeout']) ? (int)$_POST['session_timeout'] : 30;
        
        // 설정 업데이트
        updateSetting($db, 'session_timeout', $sessionTimeout);
        
        // 설정 값 업데이트
        $settings['session_timeout'] = $sessionTimeout;
        
        logInfo("세션 설정이 업데이트되었습니다.", "security");
    }
    
    // IP 관리 설정 저장
    elseif (isset($_POST['ip_management'])) {
        $ipWhitelistEnabled = isset($_POST['ip_whitelist_enabled']) ? 1 : 0;
        $ipBlacklistEnabled = isset($_POST['ip_blacklist_enabled']) ? 1 : 0;
        
        // 설정 업데이트
        updateSetting($db, 'ip_whitelist_enabled', $ipWhitelistEnabled);
        updateSetting($db, 'ip_blacklist_enabled', $ipBlacklistEnabled);
        
        // 설정 값 업데이트
        $settings['ip_whitelist_enabled'] = $ipWhitelistEnabled;
        $settings['ip_blacklist_enabled'] = $ipBlacklistEnabled;
        
        logInfo("IP 관리 설정이 업데이트되었습니다.", "security");
    }
    
    // 기타 보안 설정 저장
    elseif (isset($_POST['other_security'])) {
        $captchaEnabled = isset($_POST['captcha_enabled']) ? 1 : 0;
        $autoBlocklist = isset($_POST['auto_blocklist']) ? 1 : 0;
        $suspiciousLoginAlert = isset($_POST['suspicious_login_alert']) ? 1 : 0;
        $loginNotification = isset($_POST['login_notification']) ? 1 : 0;
        
        // 설정 업데이트
        updateSetting($db, 'captcha_enabled', $captchaEnabled);
        updateSetting($db, 'auto_blocklist', $autoBlocklist);
        updateSetting($db, 'suspicious_login_alert', $suspiciousLoginAlert);
        updateSetting($db, 'login_notification', $loginNotification);
        
        // 설정 값 업데이트
        $settings['captcha_enabled'] = $captchaEnabled;
        $settings['auto_blocklist'] = $autoBlocklist;
        $settings['suspicious_login_alert'] = $suspiciousLoginAlert;
        $settings['login_notification'] = $loginNotification;
        
        logInfo("기타 보안 설정이 업데이트되었습니다.", "security");
    }
}

/**
 * 설정을 업데이트합니다.
 */
function updateSetting($db, $key, $value) {
    $sql = "INSERT INTO system_settings (category, setting_key, setting_value) 
            VALUES ('security', :key, :value) 
            ON DUPLICATE KEY UPDATE setting_value = :value";
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':key', $key, PDO::PARAM_STR);
    $stmt->bindParam(':value', $value, PDO::PARAM_STR);
    return $stmt->execute();
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
                    <li class="breadcrumb-item">보안 관리</li>
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
            <div class="col-md-6">
                <!-- 비밀번호 정책 -->
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title">비밀번호 정책</h3>
                    </div>
                    <form method="post">
                        <div class="card-body">
                            <div class="form-group">
                                <label for="password_min_length">비밀번호 최소 길이</label>
                                <input type="number" class="form-control" id="password_min_length" name="password_min_length" min="6" max="20" value="<?php echo $settings['password_min_length']; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="password_complexity">비밀번호 복잡성 요구사항</label>
                                <select class="form-control" id="password_complexity" name="password_complexity">
                                    <option value="low" <?php echo $settings['password_complexity'] === 'low' ? 'selected' : ''; ?>>낮음 (숫자만 필요)</option>
                                    <option value="medium" <?php echo $settings['password_complexity'] === 'medium' ? 'selected' : ''; ?>>중간 (숫자 + 알파벳)</option>
                                    <option value="high" <?php echo $settings['password_complexity'] === 'high' ? 'selected' : ''; ?>>높음 (숫자 + 대소문자 + 특수문자)</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="password_expiry_days">비밀번호 만료 기간 (일)</label>
                                <input type="number" class="form-control" id="password_expiry_days" name="password_expiry_days" min="0" value="<?php echo $settings['password_expiry_days']; ?>">
                                <small class="form-text text-muted">0으로 설정하면 만료되지 않습니다.</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="max_login_attempts">최대 로그인 시도 횟수</label>
                                <input type="number" class="form-control" id="max_login_attempts" name="max_login_attempts" min="1" max="10" value="<?php echo $settings['max_login_attempts']; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="account_lockout_duration">계정 잠금 시간 (분)</label>
                                <input type="number" class="form-control" id="account_lockout_duration" name="account_lockout_duration" min="5" value="<?php echo $settings['account_lockout_duration']; ?>">
                            </div>
                            
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="require_2fa" name="require_2fa" <?php echo $settings['require_2fa'] == 1 ? 'checked' : ''; ?>>
                                    <label class="custom-control-label" for="require_2fa">2단계 인증 필수화</label>
                                </div>
                                <small class="form-text text-muted">활성화하면 모든 사용자는 로그인 시 2단계 인증을 설정하고 사용해야 합니다.</small>
                            </div>
                        </div>
                        
                        <div class="card-footer">
                            <input type="hidden" name="password_policy" value="1">
                            <button type="submit" class="btn btn-primary">설정 저장</button>
                        </div>
                    </form>
                </div>
                <!-- /비밀번호 정책 -->
                
                <!-- 세션 설정 -->
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title">세션 설정</h3>
                    </div>
                    <form method="post">
                        <div class="card-body">
                            <div class="form-group">
                                <label for="session_timeout">세션 타임아웃 (분)</label>
                                <input type="number" class="form-control" id="session_timeout" name="session_timeout" min="5" max="180" value="<?php echo $settings['session_timeout']; ?>">
                                <small class="form-text text-muted">사용자 세션이 자동 종료되기까지의 비활성 시간</small>
                            </div>
                        </div>
                        
                        <div class="card-footer">
                            <input type="hidden" name="session_settings" value="1">
                            <button type="submit" class="btn btn-primary">설정 저장</button>
                        </div>
                    </form>
                </div>
                <!-- /세션 설정 -->
            </div>
            
            <div class="col-md-6">
                <!-- IP 관리 설정 -->
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title">IP 관리 설정</h3>
                    </div>
                    <form method="post">
                        <div class="card-body">
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="ip_whitelist_enabled" name="ip_whitelist_enabled" <?php echo $settings['ip_whitelist_enabled'] == 1 ? 'checked' : ''; ?>>
                                    <label class="custom-control-label" for="ip_whitelist_enabled">IP 화이트리스트 활성화</label>
                                </div>
                                <small class="form-text text-muted">활성화하면 허용된 IP 주소만 시스템에 접근할 수 있습니다.</small>
                            </div>
                            
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="ip_blacklist_enabled" name="ip_blacklist_enabled" <?php echo $settings['ip_blacklist_enabled'] == 1 ? 'checked' : ''; ?>>
                                    <label class="custom-control-label" for="ip_blacklist_enabled">IP 블랙리스트 활성화</label>
                                </div>
                                <small class="form-text text-muted">활성화하면 차단된 IP 주소는 시스템에 접근할 수 없습니다.</small>
                            </div>
                            
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> IP 목록을 관리하려면 <a href="<?php echo SERVER_URL; ?>/dashboard/security/ip_blocklist.php">IP 차단 관리</a> 페이지를 이용하세요.
                            </div>
                        </div>
                        
                        <div class="card-footer">
                            <input type="hidden" name="ip_management" value="1">
                            <button type="submit" class="btn btn-primary">설정 저장</button>
                        </div>
                    </form>
                </div>
                <!-- /IP 관리 설정 -->
                
                <!-- 기타 보안 설정 -->
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title">기타 보안 설정</h3>
                    </div>
                    <form method="post">
                        <div class="card-body">
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="captcha_enabled" name="captcha_enabled" <?php echo $settings['captcha_enabled'] == 1 ? 'checked' : ''; ?>>
                                    <label class="custom-control-label" for="captcha_enabled">CAPTCHA 활성화</label>
                                </div>
                                <small class="form-text text-muted">로그인, 계정 생성, 비밀번호 재설정 등의 페이지에서 CAPTCHA를 요구합니다.</small>
                            </div>
                            
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="auto_blocklist" name="auto_blocklist" <?php echo $settings['auto_blocklist'] == 1 ? 'checked' : ''; ?>>
                                    <label class="custom-control-label" for="auto_blocklist">자동 IP 차단 활성화</label>
                                </div>
                                <small class="form-text text-muted">일정 기간 내에 여러 번의 로그인 실패 시 해당 IP를 자동으로 차단합니다.</small>
                            </div>
                            
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="suspicious_login_alert" name="suspicious_login_alert" <?php echo $settings['suspicious_login_alert'] == 1 ? 'checked' : ''; ?>>
                                    <label class="custom-control-label" for="suspicious_login_alert">의심스러운 로그인 알림</label>
                                </div>
                                <small class="form-text text-muted">일반적이지 않은 위치나 기기에서 로그인 시 관리자에게 알림을 보냅니다.</small>
                            </div>
                            
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="login_notification" name="login_notification" <?php echo $settings['login_notification'] == 1 ? 'checked' : ''; ?>>
                                    <label class="custom-control-label" for="login_notification">로그인 알림 이메일</label>
                                </div>
                                <small class="form-text text-muted">계정 로그인 시 사용자에게 이메일 알림을 보냅니다.</small>
                            </div>
                        </div>
                        
                        <div class="card-footer">
                            <input type="hidden" name="other_security" value="1">
                            <button type="submit" class="btn btn-primary">설정 저장</button>
                        </div>
                    </form>
                </div>
                <!-- /기타 보안 설정 -->
            </div>
        </div>
        
        <!-- 보안 정보 -->
        <div class="row">
            <div class="col-md-12">
                <div class="card card-info card-outline">
                    <div class="card-header">
                        <h3 class="card-title">보안 상태 정보</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="info-box">
                                    <span class="info-box-icon bg-info"><i class="fas fa-lock"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">현재 보안 등급</span>
                                        <span class="info-box-number">
                                            <?php
                                            // 보안 등급 계산
                                            $securityScore = 0;
                                            
                                            // 비밀번호 정책
                                            $securityScore += (int)$settings['password_min_length'] >= 10 ? 20 : ((int)$settings['password_min_length'] >= 8 ? 10 : 0);
                                            $securityScore += $settings['password_complexity'] === 'high' ? 20 : ($settings['password_complexity'] === 'medium' ? 10 : 0);
                                            
                                            // 2FA, IP 관리
                                            $securityScore += (int)$settings['require_2fa'] ? 20 : 0;
                                            $securityScore += (int)$settings['ip_whitelist_enabled'] || (int)$settings['ip_blacklist_enabled'] ? 15 : 0;
                                            
                                            // 기타 설정
                                            $securityScore += (int)$settings['captcha_enabled'] ? 5 : 0;
                                            $securityScore += (int)$settings['auto_blocklist'] ? 10 : 0;
                                            $securityScore += (int)$settings['suspicious_login_alert'] ? 5 : 0;
                                            $securityScore += (int)$settings['login_notification'] ? 5 : 0;
                                            
                                            // 등급 표시
                                            if ($securityScore >= 80) {
                                                echo '<span class="text-success">높음</span>';
                                            } elseif ($securityScore >= 50) {
                                                echo '<span class="text-warning">중간</span>';
                                            } else {
                                                echo '<span class="text-danger">낮음</span>';
                                            }
                                            
                                            echo ' ('.$securityScore.'/100)';
                                            ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="info-box">
                                    <span class="info-box-icon bg-warning"><i class="fas fa-shield-alt"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">마지막 보안 감사</span>
                                        <span class="info-box-number">
                                            <?php
                                            // 마지막 보안 감사 날짜 조회
                                            $stmt = $db->prepare("SELECT MAX(audit_date) as last_audit FROM security_audit_logs");
                                            $stmt->execute();
                                            $lastAudit = $stmt->fetch(PDO::FETCH_ASSOC)['last_audit'];
                                            
                                            if ($lastAudit) {
                                                echo date('Y-m-d', strtotime($lastAudit));
                                            } else {
                                                echo '감사 기록 없음';
                                            }
                                            ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="info-box">
                                    <span class="info-box-icon bg-danger"><i class="fas fa-ban"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">차단된 IP 주소 수</span>
                                        <span class="info-box-number">
                                            <?php
                                            // 차단된 IP 수 조회
                                            $stmt = $db->prepare("SELECT COUNT(*) as count FROM ip_blocklist WHERE status = 'active'");
                                            $stmt->execute();
                                            $blockedIPs = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                                            
                                            echo $blockedIPs;
                                            ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-12">
                                <div class="alert alert-info">
                                    <h5><i class="icon fas fa-info-circle"></i> 보안 권장사항</h5>
                                    <ul class="mb-0">
                                        <?php if ((int)$settings['password_min_length'] < 10): ?>
                                        <li>더 강력한 보안을 위해 비밀번호 최소 길이를 10자 이상으로 설정하세요.</li>
                                        <?php endif; ?>
                                        
                                        <?php if ($settings['password_complexity'] !== 'high'): ?>
                                        <li>비밀번호 복잡성을 '높음'으로 설정하여 보안을 강화하세요.</li>
                                        <?php endif; ?>
                                        
                                        <?php if (!(int)$settings['require_2fa']): ?>
                                        <li>2단계 인증을 필수화하여 추가적인 보안 계층을 추가하세요.</li>
                                        <?php endif; ?>
                                        
                                        <?php if ((int)$settings['session_timeout'] > 60): ?>
                                        <li>세션 타임아웃 시간을 60분 이하로 줄여서 보안을 강화하세요.</li>
                                        <?php endif; ?>
                                        
                                        <?php if (!(int)$settings['auto_blocklist']): ?>
                                        <li>자동 IP 차단 기능을 활성화하여 무차별 대입 공격에 대비하세요.</li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- /보안 정보 -->
    </div>
</section>
<!-- /.content -->

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log("보안 설정 페이지가 로드되었습니다.");
});
</script>

<?php include_once TEMPLATES_PATH . '/dashboard_footer.php'; ?>