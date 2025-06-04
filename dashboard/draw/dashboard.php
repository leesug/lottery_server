<?php
/**
 * 추첨 관리 대시보드 페이지
 * 
 * 이 페이지는 추첨 관리와 관련된 통계 및 정보를 표시합니다.
 */

// 공통 파일 포함
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// 페이지 변수 설정
$pageTitle = "추첨 관리 대시보드";
$currentSection = "draw";
$currentPage = basename($_SERVER['PHP_SELF']);

// 데이터베이스 연결
$db = get_db_connection();

// 추첨 정보 (Mock 데이터 사용)
$drawInfo = [
    'scheduled_draws' => 3,
    'completed_draws' => 124,
    'total_winners' => 85642,
    'next_draw_date' => '2024-05-22'
];

// 최근 추첨 결과 (Mock 데이터 사용)
$recentDraws = [
    [
        'id' => 125, 
        'product' => 'KHUSHI Weekly', 
        'date' => '2024-05-18', 
        'numbers' => '08, 15, 22, 30, 37, 42', 
        'winners' => 6820, 
        'prize_pool' => 10000000
    ],
    [
        'id' => 124, 
        'product' => 'KHUSHI Daily', 
        'date' => '2024-05-17', 
        'numbers' => '03, 11, 19, 25, 32, 40', 
        'winners' => 5240, 
        'prize_pool' => 5000000
    ],
    [
        'id' => 123, 
        'product' => 'KHUSHI Weekly', 
        'date' => '2024-05-11', 
        'numbers' => '05, 13, 20, 28, 35, 41', 
        'winners' => 7152, 
        'prize_pool' => 10000000
    ],
    [
        'id' => 122, 
        'product' => 'KHUSHI Bumper', 
        'date' => '2024-05-05', 
        'numbers' => '07, 12, 24, 31, 39, 45', 
        'winners' => 12450, 
        'prize_pool' => 25000000
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
                    <li class="breadcrumb-item">추첨 관리</li>
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