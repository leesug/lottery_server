<?php
// 헤더 템플릿 파일 
if (!defined('SERVER_URL')) {
    require_once __DIR__ . '/../includes/config.php';
    require_once __DIR__ . '/../includes/functions.php';
    require_once __DIR__ . '/../includes/auth.php';
}

// 테스트용 고정 사용자 정보
$userId = 1;
$username = "관리자";
$userRole = "admin";

// 현재 사용자 정보를 배열로 구성 (기존 호환성 유지)
$currentUser = [
    'id' => $userId,
    'username' => $username,
    'role' => $userRole
];
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? '로또 서버 관리 시스템'; ?></title>
    <link rel="stylesheet" href="http://localhost/server/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <?php if (isset($extraCss)): ?>
        <link rel="stylesheet" href="<?php echo $extraCss; ?>">
    <?php endif; ?>
</head>
<body>
    <div class="dashboard-layout">
        <!-- 사이드바 포함 -->
        <?php include TEMPLATES_PATH . '/sidebar.php'; ?>
        
        <div class="main-content">
            <header class="header">
                <div class="header-title">
                    <h1><?php echo $pageHeader ?? $pageTitle ?? '대시보드'; ?></h1>
                </div>
                
                <div class="header-user-info">
                    <span class="user-name">
                        <?php echo htmlspecialchars($username); ?>
                        (<?php echo htmlspecialchars($userRole); ?>)
                    </span>
                    <form action="http://localhost/server/pages/logout.php" method="post" class="d-inline">
                        <input type="hidden" name="csrf_token" value="<?php echo md5(uniqid()); ?>">
                        <button type="submit" class="btn btn-secondary btn-sm">
                            <i class="fas fa-sign-out-alt"></i> 로그아웃
                        </button>
                    </form>
                </div>
            </header>
            
            <div class="dashboard-content">
                <?php 
                // 플래시 메시지 기능을 세션 없이 사용하지 않음
                $flashMessage = "";
                $flashType = "info";
                
                if ($flashMessage): 
                ?>
                    <div class="alert alert-<?php echo $flashType; ?>">
                        <?php echo htmlspecialchars($flashMessage); ?>
                    </div>
                <?php endif; ?>
