<?php
/**
 * 로그인 페이지
 */

require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// 세션 시작
session_start();

$error = '';
$successMessage = '';

// 로그인 폼 제출 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = '이메일과 비밀번호를 모두 입력해주세요.';
    } else {
        // 테스트 계정 확인
        if ($email === 'iff99@nate.com' && $password === '1234') {
            // 세션에 사용자 정보 저장
            $_SESSION['user_id'] = 1;
            $_SESSION['username'] = '관리자';
            $_SESSION['email'] = $email;
            $_SESSION['role'] = 'admin';
            
            // 로그인 성공 로그 기록
            error_log("로그인 성공: " . $email);
            
            // 대시보드로 리디렉션
            header('Location: ' . SERVER_URL . '/dashboard/index.php');
            exit;
        } else {
            $error = '아이디 또는 비밀번호가 일치하지 않습니다.';
            error_log("로그인 실패: " . $email);
        }
    }
}

// 세션 만료 메시지
$expiredMessage = '';
if (isset($_GET['expired']) && $_GET['expired'] == 1) {
    $expiredMessage = '세션이 만료되어 다시 로그인해주세요.';
}

// 로그아웃 메시지
$logoutMessage = '';
if (isset($_GET['logout']) && $_GET['logout'] == 1) {
    $logoutMessage = '성공적으로 로그아웃되었습니다.';
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>로그인 - 로또 서버 관리 시스템</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="<?php echo SERVER_URL; ?>/assets/css/login.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="logo-container">
            <i class="fas fa-ticket-alt fa-5x" style="color: #007bff;"></i>
        </div>
        
        <div class="login-header">
            <h1>로또 서버 관리 시스템</h1>
            <p>계정 정보로 로그인하세요</p>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($expiredMessage)): ?>
            <div class="alert alert-warning">
                <i class="fas fa-clock"></i> <?php echo htmlspecialchars($expiredMessage); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($logoutMessage)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($logoutMessage); ?>
            </div>
        <?php endif; ?>
        
        <form class="login-form" method="post" action="">
            <input type="hidden" name="csrf_token" value="<?php echo md5(uniqid()); ?>">
            
            <div class="form-group">
                <label for="email"><i class="fas fa-envelope"></i> 이메일</label>
                <input type="email" id="email" name="email" placeholder="이메일 주소를 입력하세요" required autofocus>
            </div>
            
            <div class="form-group">
                <label for="password"><i class="fas fa-lock"></i> 비밀번호</label>
                <input type="password" id="password" name="password" placeholder="비밀번호를 입력하세요" required>
            </div>
            
            <div class="remember-me">
                <input type="checkbox" id="remember" name="remember">
                <label for="remember">로그인 상태 유지</label>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-sign-in-alt"></i> 로그인
                </button>
            </div>
            
            <div class="form-footer">
                <a href="forgot-password.php"><i class="fas fa-question-circle"></i> 비밀번호를 잊으셨나요?</a>
            </div>
        </form>
        
        <div class="version-info">
            <p>KHUSHI LOTTERY © 2025 | Version 1.0.0</p>
        </div>
    </div>
    
    <script>
        // 로그인 폼 제출 시 로딩 표시
        document.querySelector('.login-form').addEventListener('submit', function(e) {
            const button = this.querySelector('button[type="submit"]');
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 로그인 중...';
            button.disabled = true;
        });
        
        // 자동으로 이메일 필드에 포커스
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('email').focus();
        });
    </script>
</body>
</html>
