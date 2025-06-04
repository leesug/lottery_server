<?php
/**
 * 접근 권한 없음 페이지
 */

// 설정 및 공통 함수
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// 현재 페이지 정보
$pageTitle = "접근 권한 없음";
$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir = dirname($_SERVER['PHP_SELF']);
$parts = explode('/', $currentDir);
$currentSection = end($parts);

// 헤더 포함
include_once TEMPLATES_PATH . '/dashboard_header.php';
?>

<!-- Content Header (Page header) -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">접근 권한 없음</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?php echo SERVER_URL; ?>/dashboard/index.php">홈</a></li>
                    <li class="breadcrumb-item active">접근 권한 없음</li>
                </ol>
            </div>
        </div>
    </div>
</div>
<!-- /.content-header -->

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <div class="error-page">
            <h2 class="headline text-danger">403</h2>
            <div class="error-content">
                <h3><i class="fas fa-exclamation-triangle text-danger"></i> 접근 권한이 없습니다!</h3>
                <p>
                    이 페이지에 접근할 수 있는 권한이 없습니다.
                    <a href="<?php echo SERVER_URL; ?>/dashboard/index.php">대시보드로 돌아가기</a>를 클릭하여 다른 기능을 이용해 주세요.
                </p>
            </div>
        </div>
    </div>
</section>
<!-- /.content -->

<?php
// 푸터 포함
include_once TEMPLATES_PATH . '/dashboard_footer.php';
?>