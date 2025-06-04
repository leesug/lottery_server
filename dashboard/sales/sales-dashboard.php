<?php
/**
 * 판매 관리 대시보드 페이지
 * 
 * 이 페이지는 판매 관리와 관련된 통계 및 정보를 표시합니다.
 */

// 공통 파일 포함
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// 페이지 변수 설정
$pageTitle = "판매 관리 대시보드";
$currentSection = "sales";
$currentPage = basename($_SERVER['PHP_SELF']);

// 데이터베이스 연결
$db = get_db_connection();

// 판매 현황 정보 (Mock 데이터 사용)
$salesStats = [
    'today_sales_count' => 10245,
    'today_sales_amount' => 2048500,
    'week_sales_count' => 65750,
    'total_sales_amount' => 12456800
];

// 최근 판매 내역 (Mock 데이터 사용)
$recentSales = [
    ['id' => 'S202405180001', 'store' => '판매점 #123', 'type' => 'KHUSHI Bumper', 'quantity' => 5, 'amount' => 1000, 'date' => '2024-05-18 09:15:22', 'status' => 'complete'],
    ['id' => 'S202405180002', 'store' => '판매점 #045', 'type' => 'KHUSHI Weekly', 'quantity' => 10, 'amount' => 1000, 'date' => '2024-05-18 09:18:45', 'status' => 'complete'],
    ['id' => 'S202405180003', 'store' => '판매점 #078', 'type' => 'KHUSHI Daily', 'quantity' => 20, 'amount' => 1000, 'date' => '2024-05-18 09:22:10', 'status' => 'complete'],
    ['id' => 'S202405180004', 'store' => '판매점 #156', 'type' => 'KHUSHI Bumper', 'quantity' => 2, 'amount' => 400, 'date' => '2024-05-18 09:25:33', 'status' => 'complete'],
    ['id' => 'S202405180005', 'store' => '판매점 #091', 'type' => 'KHUSHI Weekly', 'quantity' => 15, 'amount' => 1500, 'date' => '2024-05-18 09:30:15', 'status' => 'processing']
];

// 지역별 판매 현황 (Mock 데이터 사용)
$regionSales = [
    ['region' => '카트만두', 'stores' => 45, 'count' => 3820, 'amount' => 764000, 'percentage' => 38],
    ['region' => '포카라', 'stores' => 32, 'count' => 2640, 'amount' => 528000, 'percentage' => 26],
    ['region' => '비랏나가르', 'stores' => 24, 'count' => 1950, 'amount' => 390000, 'percentage' => 19],
    ['region' => '네팔군지', 'stores' => 18, 'count' => 1520, 'amount' => 304000, 'percentage' => 15]
];

// 템플릿 헤더 포함 - 여기서 content-wrapper 클래스를 가진 div가 시작됨
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
                    <li class="breadcrumb-item">판매 관리</li>
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
        <?php require_once 'dashboard.php'; ?>
    </div>
</section>

<?php
// 템플릿 푸터 포함
include_once TEMPLATES_PATH . '/dashboard_footer.php';
?>
