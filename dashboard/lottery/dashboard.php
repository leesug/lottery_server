<?php
/**
 * 복권 관리 대시보드 페이지
 * 
 * 이 페이지는 복권 관리와 관련된 통계 및 정보를 표시합니다.
 */

// 공통 파일 포함
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// 페이지 변수 설정
$pageTitle = "복권 관리 대시보드";
$currentSection = "lottery";
$currentPage = basename($_SERVER['PHP_SELF']);

// 데이터베이스 연결
$db = get_db_connection();

// 복권 상품 정보 (Mock 데이터 사용)
$lotteryProducts = [
    ['name' => 'KHUSHI Bumper', 'status' => 'active', 'prize_pool' => 25000000, 'sales' => 145320],
    ['name' => 'KHUSHI Weekly', 'status' => 'active', 'prize_pool' => 10000000, 'sales' => 89745],
    ['name' => 'KHUSHI Daily', 'status' => 'active', 'prize_pool' => 5000000, 'sales' => 125680],
    ['name' => 'KHUSHI Special', 'status' => 'pending', 'prize_pool' => 30000000, 'sales' => 0]
];

// 최근 배치 정보 (Mock 데이터 사용)
$recentBatches = [
    ['id' => 'B2024051801', 'product' => 'KHUSHI Bumper', 'date' => '2024-05-18', 'quantity' => 50000, 'allocated' => 15000, 'status' => 'active'],
    ['id' => 'B2024051701', 'product' => 'KHUSHI Weekly', 'date' => '2024-05-17', 'quantity' => 30000, 'allocated' => 28500, 'status' => 'active'],
    ['id' => 'B2024051601', 'product' => 'KHUSHI Daily', 'date' => '2024-05-16', 'quantity' => 20000, 'allocated' => 20000, 'status' => 'closed'],
    ['id' => 'B2024051501', 'product' => 'KHUSHI Bumper', 'date' => '2024-05-15', 'quantity' => 50000, 'allocated' => 50000, 'status' => 'closed']
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
                    <li class="breadcrumb-item"><a href="<?php echo SERVER_URL; ?>/dashboard/index.php">홈</a></li>
                    <li class="breadcrumb-item">복권 관리</li>
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
        <?php include_once 'dashboard-content.php'; ?>
    </div>
</section>

<?php
// 템플릿 푸터 포함
include_once TEMPLATES_PATH . '/dashboard_footer.php';
?>