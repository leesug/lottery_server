<?php
/**
 * 접근 거부 페이지
 */

require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// 로그인 확인 (로그인 상태여야만 이 페이지 접근 가능)
if (!AuthManager::isLoggedIn()) {
    header('Location: ' . SERVER_URL . '/pages/login.php');
    exit;
}

// 현재 사용자 정보
$userId = SessionManager::getUserId();
$username = SessionManager::get('username');
$userRole = SessionManager::getUserRole();

// 이전 페이지 URL (없으면 기본값 사용)
$referrer = $_SERVER['HTTP_REFERER'] ?? SERVER_URL . '/index.php';

// 세션에서 거부된 페이지 정보 가져오기
$deniedPage = SessionManager::get('denied_page', '요청한 페이지');
$requiredRole = SessionManager::get('required_role', '더 높은 권한');

// 페이지 타이틀 설정
$pageTitle = "접근 거부";
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - 로또 서버 관리 시스템</title>
    <link rel="stylesheet" href="<?php echo SERVER_URL; ?>/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body class="error-page">
    <div class="container">
        <div class="error-content">
            <div class="error-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h1>접근 권한이 없습니다</h1>
            <p>
                죄송합니다. <strong><?php echo SecurityManager::sanitizeOutput($username); ?></strong>님은 
                <strong><?php echo SecurityManager::sanitizeOutput($deniedPage); ?></strong>에 접근할 수 있는 
                권한이 없습니다.
            </p>
            <p>
                현재 권한: <strong><?php echo SecurityManager::sanitizeOutput($userRole); ?></strong><br>
                필요한 권한: <strong><?php echo SecurityManager::sanitizeOutput($requiredRole); ?></strong>
            </p>
            <div class="actions">
                <a href="<?php echo SecurityManager::sanitizeOutput($referrer); ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> 이전 페이지로 돌아가기
                </a>
                <a href="<?php echo SERVER_URL; ?>/index.php" class="btn btn-primary">
                    <i class="fas fa-home"></i> 메인 페이지로 이동
                </a>
            </div>
            <p class="small-text">
                권한 관련 문의는 시스템 관리자에게 연락하세요.
            </p>
        </div>
    </div>
    
    <script src="<?php echo SERVER_URL; ?>/assets/js/common.js"></script>
    <!-- 세션 타임아웃 관리 스크립트 -->
    <script>
        // 세션 타임아웃 관리
        (function() {
            // 세션 타임아웃 시간 (밀리초)
            const sessionTimeout = <?php echo SESSION_TIMEOUT * 1000; ?>;
            // 경고 표시 시간 (밀리초, 타임아웃 1분 전)
            const warningTime = 60000;
            // 세션 연장 AJAX 호출 간격 (밀리초, 5분)
            const refreshInterval = 300000;
            
            let timeoutId;
            let warningId;
            
            // 세션 타이머 재설정
            function resetSessionTimer() {
                clearTimeout(timeoutId);
                clearTimeout(warningId);
                
                // 경고 타이머 설정
                warningId = setTimeout(function() {
                    showTimeoutWarning();
                }, sessionTimeout - warningTime);
                
                // 세션 만료 타이머 설정
                timeoutId = setTimeout(function() {
                    window.location.href = '<?php echo SERVER_URL; ?>/pages/logout.php';
                }, sessionTimeout);
            }
            
            // 세션 연장
            function extendSession() {
                fetch('<?php echo SERVER_URL; ?>/api/session/extend.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'csrf_token=<?php echo SecurityManager::generateCsrfToken(); ?>'
                })
                .then(response => response.json())
                .then(data => {
                    console.log('세션이 연장되었습니다.');
                })
                .catch(error => {
                    console.error('세션 연장 실패:', error);
                });
                
                resetSessionTimer();
            }
            
            // 타임아웃 경고 표시
            function showTimeoutWarning() {
                // 경고 표시 (간단한 경고 메시지)
                if (confirm('세션이 곧 만료됩니다. 계속 작업하시겠습니까?')) {
                    extendSession();
                }
            }
            
            // 초기 타이머 설정
            resetSessionTimer();
            
            // 주기적인 세션 연장
            setInterval(extendSession, refreshInterval);
            
            // 사용자 활동 감지
            document.addEventListener('click', extendSession);
            document.addEventListener('keypress', extendSession);
        })();
    </script>
</body>
</html>
