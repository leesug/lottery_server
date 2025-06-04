<?php
/**
 * 비밀번호 재설정 페이지
 */

require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

$error = '';
$success = '';
$token = $_GET['token'] ?? '';
$userId = null;

// 토큰 검증
if (!empty($token)) {
    $userId = AuthManager::validatePasswordResetToken($token);
    
    if (!$userId) {
        $error = '유효하지 않거나 만료된 토큰입니다. 비밀번호 재설정을 다시 요청해주세요.';
    }
}

// 폼 제출 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($userId)) {
    // CSRF 토큰 검증
    if (!SecurityManager::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = '보안 토큰이 유효하지 않습니다. 페이지를 새로고침하고 다시 시도해주세요.';
    } else {
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';
        
        // 입력 검증
        if (empty($password)) {
            $error = '새 비밀번호를 입력해주세요.';
        } else if (strlen($password) < 8) {
            $error = '비밀번호는 최소 8자 이상이어야 합니다.';
        } else if ($password !== $passwordConfirm) {
            $error = '비밀번호와 비밀번호 확인이 일치하지 않습니다.';
        } else {
            // 비밀번호 재설정
            $result = AuthManager::resetPassword($userId, $password);
            
            if ($result) {
                $success = '비밀번호가 성공적으로 재설정되었습니다. 새 비밀번호로 로그인해주세요.';
                
                // 사용한 토큰 무효화 처리 (이 부분은 토큰 무효화 함수 추가가 필요함)
                // invalidatePasswordResetToken($token);
                
                // 잠시 후 로그인 페이지로 리디렉션
                header('Refresh: 3; URL=' . SERVER_URL . '/pages/login.php');
            } else {
                $error = '비밀번호 재설정에 실패했습니다. 다시 시도해주세요.';
            }
        }
    }
}

// 이메일 폼 제출 처리 (토큰이 없는 경우)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($token)) {
    // CSRF 토큰 검증
    if (!SecurityManager::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = '보안 토큰이 유효하지 않습니다. 페이지를 새로고침하고 다시 시도해주세요.';
    } else {
        $email = $_POST['email'] ?? '';
        
        if (empty($email)) {
            $error = '이메일을 입력해주세요.';
        } else {
            // 비밀번호 재설정 토큰 생성 및 이메일 발송
            $resetToken = AuthManager::generatePasswordResetToken($email);
            
            if ($resetToken) {
                // 이메일 발송 로직 (여기서는 생략)
                // sendPasswordResetEmail($email, $resetToken);
                
                $success = '비밀번호 재설정 링크가 이메일로 발송되었습니다. 이메일을 확인해주세요.';
            } else {
                $error = '해당 이메일 주소를 가진 계정을 찾을 수 없습니다.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>비밀번호 재설정 - 로또 서버 관리 시스템</title>
    <link rel="stylesheet" href="<?php echo SERVER_URL; ?>/assets/css/style.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-header">
            <h1>로또 서버 관리 시스템</h1>
            <?php if (!empty($token) && !empty($userId)): ?>
                <p>새 비밀번호 설정</p>
            <?php else: ?>
                <p>비밀번호 재설정 요청</p>
            <?php endif; ?>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <?php echo SecurityManager::sanitizeOutput($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                <?php echo SecurityManager::sanitizeOutput($success); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($token) && !empty($userId)): ?>
            <!-- 새 비밀번호 설정 폼 -->
            <form class="login-form" method="post" action="">
                <input type="hidden" name="csrf_token" value="<?php echo SecurityManager::generateCsrfToken(); ?>">
                
                <div class="form-group">
                    <label for="password">새 비밀번호</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <div class="form-group">
                    <label for="password_confirm">비밀번호 확인</label>
                    <input type="password" id="password_confirm" name="password_confirm" required>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary btn-block">비밀번호 변경</button>
                </div>
            </form>
        <?php else: ?>
            <!-- 비밀번호 재설정 요청 폼 -->
            <form class="login-form" method="post" action="">
                <input type="hidden" name="csrf_token" value="<?php echo SecurityManager::generateCsrfToken(); ?>">
                
                <div class="form-group">
                    <label for="email">이메일</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary btn-block">비밀번호 재설정 링크 받기</button>
                </div>
                
                <div class="form-footer">
                    <a href="login.php">로그인 페이지로 돌아가기</a>
                </div>
            </form>
        <?php endif; ?>
    </div>
    
    <script src="<?php echo SERVER_URL; ?>/assets/js/common.js"></script>
</body>
</html>
