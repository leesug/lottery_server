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

// 추첨 현황 정보 (Mock 데이터 사용)
$drawStats = [
    'next_draw_date' => '2024-05-22',
    'next_draw_type' => 'KHUSHI Weekly',
    'total_draws' => 124,
    'total_prize_amount' => 125450000,
    'total_winners' => 7845
];

// 최근 추첨 결과 (Mock 데이터 사용)
$recentDraws = [
    ['id' => 124, 'type' => 'KHUSHI Weekly', 'date' => '2024-05-15', 'numbers' => [12, 18, 24, 35, 42, 7], 'jackpot_winners' => 2, 'jackpot_prize' => 5000000],
    ['id' => 123, 'type' => 'KHUSHI Weekly', 'date' => '2024-05-08', 'numbers' => [8, 15, 27, 33, 44, 2], 'jackpot_winners' => 1, 'jackpot_prize' => 10000000],
    ['id' => 122, 'type' => 'KHUSHI Weekly', 'date' => '2024-05-01', 'numbers' => [3, 11, 22, 31, 42, 9], 'jackpot_winners' => 0, 'jackpot_prize' => 0],
    ['id' => 45, 'type' => 'KHUSHI Bumper', 'date' => '2024-04-30', 'numbers' => [5, 13, 28, 36, 45, 4], 'jackpot_winners' => 1, 'jackpot_prize' => 25000000]
];

// 향후 추첨 계획 (Mock 데이터 사용)
$upcomingDraws = [
    ['id' => 'DW202405220001', 'type' => 'KHUSHI Weekly', 'date' => '2024-05-22', 'time' => '15:00', 'status' => 'scheduled'],
    ['id' => 'DW202405250001', 'type' => 'KHUSHI Daily', 'date' => '2024-05-25', 'time' => '16:00', 'status' => 'scheduled'],
    ['id' => 'DW202405290001', 'type' => 'KHUSHI Weekly', 'date' => '2024-05-29', 'time' => '15:00', 'status' => 'scheduled'],
    ['id' => 'DW202405300001', 'type' => 'KHUSHI Bumper', 'date' => '2024-05-30', 'time' => '18:00', 'status' => 'scheduled']
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
        <?php require_once 'dashboard.php'; ?>
    </div>
</section>

<?php
// 템플릿 푸터 포함
include_once TEMPLATES_PATH . '/dashboard_footer.php';
?>
