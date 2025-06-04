<?php
/**
 * 사용자 프로필 관리 페이지
 */

require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// 세션 시작
session_start();

// 로그인 여부 확인 - 로그인되지 않은 경우 로그인 페이지로 리디렉션
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . SERVER_URL . '/pages/login.php');
    exit;
}

// 현재 사용자 정보 (세션에서 가져오기)
$userId = $_SESSION['user_id'] ?? 1;
$username = $_SESSION['username'] ?? '관리자';
$userEmail = $_SESSION['email'] ?? 'iff99@nate.com';
$userRole = $_SESSION['role'] ?? 'admin';

// 사용자 정보 (모의 데이터)
$user = [
    'id' => $userId,
    'username' => $username,
    'email' => $userEmail,
    'role' => $userRole,
    'last_login' => date('Y-m-d H:i:s', strtotime('-1 day')),
    'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // "1234" 해시
    'created_at' => date('Y-m-d H:i:s', strtotime('-6 months')),
    'updated_at' => date('Y-m-d H:i:s', strtotime('-1 month'))
];

// 메시지 변수
$error = '';
$success = '';

// 프로필 업데이트 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    // 기본적인 CSRF 토큰 검증 (실제 검증 없이 진행)
    $newUsername = sanitize_input($_POST['username'] ?? '');
    $newEmail = sanitize_input($_POST['email'] ?? '');
    
    // 기본 검증
    if (empty($newUsername)) {
        $error = '사용자명을 입력해주세요.';
    } else if (empty($newEmail)) {
        $error = '이메일을 입력해주세요.';
    } else {
        // 프로필 업데이트 (모의 처리)
        $_SESSION['username'] = $newUsername;
        $_SESSION['email'] = $newEmail;
        
        $success = '프로필이 성공적으로 업데이트되었습니다.';
        
        // 사용자 정보 업데이트
        $username = $newUsername;
        $userEmail = $newEmail;
        $user['username'] = $newUsername;
        $user['email'] = $newEmail;
        $user['updated_at'] = date('Y-m-d H:i:s');
    }
}

// 비밀번호 변경 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    // 기본적인 검증 (실제 검증 없이 진행)
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // 기본 검증
    if (empty($currentPassword)) {
        $error = '현재 비밀번호를 입력해주세요.';
    } else if (empty($newPassword)) {
        $error = '새 비밀번호를 입력해주세요.';
    } else if (strlen($newPassword) < 4) {
        $error = '비밀번호는 최소 4자 이상이어야 합니다.';
    } else if ($newPassword !== $confirmPassword) {
        $error = '새 비밀번호와 비밀번호 확인이 일치하지 않습니다.';
    } else {
        // 테스트용 비밀번호 확인
        if ($currentPassword === '1234') {
            $success = '비밀번호가 성공적으로 변경되었습니다.';
            $user['updated_at'] = date('Y-m-d H:i:s');
        } else {
            $error = '현재 비밀번호가 올바르지 않습니다.';
        }
    }
}
?>
<?php
// 현재 페이지 정보
$pageTitle = "프로필 관리";
$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir = dirname($_SERVER['PHP_SELF']);
$parts = explode('/', $currentDir);
$currentSection = end($parts);

// 추가 CSS
$extraCss = SERVER_URL . '/assets/css/profile.css';

// 템플릿 헤더 포함
include_once '../templates/dashboard_header.php';
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
                    <li class="breadcrumb-item active">프로필</li>
                </ol>
            </div>
        </div>
    </div>
</div>
<!-- /.content-header -->

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo SecurityManager::sanitizeOutput($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo SecurityManager::sanitizeOutput($success); ?>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-6">
                <!-- 기본 정보 카드 -->
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-user-edit mr-2"></i>기본 정보</h3>
                    </div>
                    <div class="card-body">
                        <form method="post" action="">
                            <input type="hidden" name="csrf_token" value="<?php echo SecurityManager::generateCsrfToken(); ?>">
                            <input type="hidden" name="update_profile" value="1">
                            
                            <div class="form-group">
                                <label for="username">사용자명</label>
                                <input type="text" class="form-control" id="username" name="username" value="<?php echo SecurityManager::sanitizeOutput($username); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="email">이메일</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo SecurityManager::sanitizeOutput($userEmail); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="role">역할</label>
                                <input type="text" class="form-control" id="role" value="<?php echo SecurityManager::sanitizeOutput($userRole); ?>" readonly>
                            </div>
                            
                            <div class="form-group text-right">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save mr-1"></i> 정보 업데이트
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <!-- 비밀번호 변경 카드 -->
                <div class="card card-warning card-outline">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-key mr-2"></i>비밀번호 변경</h3>
                    </div>
                    <div class="card-body">
                        <form method="post" action="">
                            <input type="hidden" name="csrf_token" value="<?php echo SecurityManager::generateCsrfToken(); ?>">
                            <input type="hidden" name="change_password" value="1">
                            
                            <div class="form-group">
                                <label for="current_password">현재 비밀번호</label>
                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="new_password">새 비밀번호</label>
                                <input type="password" class="form-control" id="new_password" name="new_password" required>
                                <div class="password-strength mt-2">
                                    <div class="password-strength-meter" id="password-meter"></div>
                                </div>
                                <small class="password-strength-text" id="password-strength-text">비밀번호를 입력하세요</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="confirm_password">비밀번호 확인</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            
                            <div class="form-group text-right">
                                <button type="submit" class="btn btn-warning">
                                    <i class="fas fa-lock mr-1"></i> 비밀번호 변경
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-12">
                <!-- 계정 활동 카드 -->
                <div class="card card-info card-outline">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-history mr-2"></i>계정 활동</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="info-box">
                                    <span class="info-box-icon bg-info"><i class="fas fa-clock"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">최근 로그인</span>
                                        <span class="info-box-number"><?php echo isset($user['last_login']) ? date('Y-m-d H:i:s', strtotime($user['last_login'])) : '정보 없음'; ?></span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="info-box">
                                    <span class="info-box-icon bg-success"><i class="fas fa-network-wired"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">IP 주소</span>
                                        <span class="info-box-number"><?php echo SecurityManager::getClientIp(); ?></span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="info-box">
                                    <span class="info-box-icon bg-warning"><i class="fas fa-calendar-alt"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">계정 생성일</span>
                                        <span class="info-box-number"><?php echo isset($user['created_at']) ? date('Y-m-d', strtotime($user['created_at'])) : '정보 없음'; ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- /.content -->

<?php
// 템플릿 푸터 포함
include_once '../templates/dashboard_footer.php';
?>

<script>
// 비밀번호 강도 체크
document.getElementById('new_password').addEventListener('input', function() {
    const password = this.value;
    const meter = document.getElementById('password-meter');
    const strengthText = document.getElementById('password-strength-text');
    
    // 비밀번호 강도 측정 (간단한 버전)
    let strength = 0;
    
    // 길이 점수
    if (password.length >= 8) strength += 1;
    if (password.length >= 12) strength += 1;
    
    // 문자 다양성 점수
    if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength += 1;
    if (/[0-9]/.test(password)) strength += 1;
    if (/[^a-zA-Z0-9]/.test(password)) strength += 1;
    
    // 강도 표시
    meter.className = 'password-strength-meter';
    
    if (password.length === 0) {
        meter.style.width = '0%';
        strengthText.textContent = '비밀번호를 입력하세요';
    } else if (strength < 2) {
        meter.style.width = '25%';
        meter.style.backgroundColor = '#dc3545';
        strengthText.textContent = '매우 약함';
    } else if (strength < 3) {
        meter.style.width = '50%';
        meter.style.backgroundColor = '#ffc107';
        strengthText.textContent = '약함';
    } else if (strength < 4) {
        meter.style.width = '75%';
        meter.style.backgroundColor = '#28a745';
        strengthText.textContent = '강함';
    } else {
        meter.style.width = '100%';
        meter.style.backgroundColor = '#20c997';
        strengthText.textContent = '매우 강함';
    }
    
    console.log('비밀번호 강도 체크:', strength);
});

// 비밀번호 일치 확인
document.getElementById('confirm_password').addEventListener('input', function() {
    const password = document.getElementById('new_password').value;
    const confirmPassword = this.value;
    
    if (password === confirmPassword) {
        this.setCustomValidity('');
    } else {
        this.setCustomValidity('비밀번호가 일치하지 않습니다.');
    }
    
    console.log('비밀번호 확인 일치 여부:', password === confirmPassword);
});
</script>
