<?php
/**
 * 당첨금 관리 대시보드 페이지
 * 
 * 이 페이지는 당첨금 관리와 관련된 통계 및 정보를 표시합니다.
 */

// 공통 파일 포함
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// 페이지 변수 설정
$pageTitle = "당첨금 관리 대시보드";
$currentSection = "prize";
$currentPage = basename($_SERVER['PHP_SELF']);

// 데이터베이스 연결
$db = get_db_connection();

// 당첨금 정보 (Mock 데이터 사용)
$prizeInfo = [
    'total_prize_pool' => 45000000,
    'claimed_amount' => 12850000,
    'unclaimed_amount' => 7150000,
    'carryover_amount' => 25000000
];

// 최근 당첨금 지급 내역 (Mock 데이터 사용)
$recentPrizes = [
    [
        'id' => 'P202405180001', 
        'draw_id' => 125, 
        'product' => 'KHUSHI Weekly', 
        'ticket' => 'TK24W17505', 
        'amount' => 5000000, 
        'rank' => 1, 
        'date' => '2024-05-18 14:30:25', 
        'status' => 'paid'
    ],
    [
        'id' => 'P202405180002', 
        'draw_id' => 125, 
        'product' => 'KHUSHI Weekly', 
        'ticket' => 'TK24W18720', 
        'amount' => 2000000, 
        'rank' => 2, 
        'date' => '2024-05-18 15:10:45', 
        'status' => 'paid'
    ],
    [
        'id' => 'P202405180003', 
        'draw_id' => 125, 
        'product' => 'KHUSHI Weekly', 
        'ticket' => 'TK24W20145', 
        'amount' => 1000000, 
        'rank' => 3, 
        'date' => '2024-05-18 15:45:12', 
        'status' => 'processing'
    ],
    [
        'id' => 'P202405170001', 
        'draw_id' => 124, 
        'product' => 'KHUSHI Daily', 
        'ticket' => 'TK24D08925', 
        'amount' => 2500000, 
        'rank' => 1, 
        'date' => '2024-05-17 14:15:30', 
        'status' => 'paid'
    ]
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
                    <li class="breadcrumb-item">당첨금 관리</li>
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