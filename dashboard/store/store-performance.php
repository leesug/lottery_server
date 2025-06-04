<?php
/**
 * 판매점 성과 대시보드 페이지
 * 
 * 이 페이지는 판매점의 성과 지표를 시각적으로 표시하는 대시보드입니다.
 */

// 필수 파일 포함
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

// 페이지 접근 권한 확인
checkPageAccess('store_management');

// 판매점 ID 가져오기 (URL 파라미터)
$storeId = isset($_GET['store_id']) ? intval($_GET['store_id']) : 0;

if ($storeId <= 0) {
    // 유효한 ID가 없으면 판매점 목록으로 리다이렉트
    header('Location: store-list.php');
    exit;
}

// 변수 초기화
$message = '';
$messageType = '';
$storeInfo = null;
$performanceData = [];
$yearlyPerformance = [];
$monthlyPerformance = [];
$weeklyPerformance = [];
$currentYear = date('Y');
$selectedYear = isset($_GET['year']) ? intval($_GET['year']) : $currentYear;
$selectedPeriodType = isset($_GET['period_type']) ? sanitizeInput($_GET['period_type']) : 'monthly';

// SIMULATING DATABASE CONNECTION FOR TESTING
// 임시 테스트 데이터 - 실제로는 데이터베이스에서 가져옵니다
$storeInfo = [
    'id' => $storeId,
    'store_name' => '테스트 판매점',
    'store_code' => 'ST001',
    'status' => 'active',
    'store_category' => 'standard',
    'owner_name' => '홍길동',
    'city' => '서울'
];

// 성과 데이터 생성 (테스트용)
$monthNames = [
    '01' => '1월', '02' => '2월', '03' => '3월', '04' => '4월',
    '05' => '5월', '06' => '6월', '07' => '7월', '08' => '8월',
    '09' => '9월', '10' => '10월', '11' => '11월', '12' => '12월'
];

// 연간 데이터 생성
for ($year = $currentYear - 2; $year <= $currentYear; $year++) {
    $salesAmount = mt_rand(1000000, 5000000);
    $salesCount = mt_rand(2000, 10000);
    $commissionAmount = $salesAmount * 0.05;
    $targetAmount = 4000000;
    $achievementRate = min(100, ($salesAmount / $targetAmount) * 100);
    
    $yearlyPerformance[] = [
        'reporting_period' => $year,
        'period_type' => 'yearly',
        'sales_amount' => $salesAmount,
        'sales_count' => $salesCount,
        'commission_amount' => $commissionAmount,
        'prize_claims_amount' => mt_rand(500000, 2000000),
        'prize_claims_count' => mt_rand(500, 2000),
        'customer_count' => mt_rand(100, 500),
        'achievement_rate' => $achievementRate,
        'performance_rating' => getPerformanceRating($achievementRate)
    ];
}

// 월간 데이터 생성 (선택된 연도)
for ($month = 1; $month <= 12; $month++) {
    $monthStr = str_pad($month, 2, '0', STR_PAD_LEFT);
    $reportingPeriod = "$selectedYear-$monthStr";
    
    // 현재 월 이후 데이터는 생성하지 않음
    if ($selectedYear == $currentYear && $month > date('n')) {
        continue;
    }
    
    $salesAmount = mt_rand(50000, 500000);
    $salesCount = mt_rand(100, 1000);
    $commissionAmount = $salesAmount * 0.05;
    $targetAmount = 300000;
    $achievementRate = min(100, ($salesAmount / $targetAmount) * 100);
    
    $monthlyPerformance[] = [
        'reporting_period' => $reportingPeriod,
        'period_label' => $monthNames[$monthStr],
        'period_type' => 'monthly',
        'sales_amount' => $salesAmount,
        'sales_count' => $salesCount,
        'commission_amount' => $commissionAmount,
        'prize_claims_amount' => mt_rand(10000, 200000),
        'prize_claims_count' => mt_rand(20, 200),
        'customer_count' => mt_rand(10, 50),
        'achievement_rate' => $achievementRate,
        'performance_rating' => getPerformanceRating($achievementRate)
    ];
}

// 주간 데이터 생성 (최근 4주)
$weekCount = 4;
for ($week = 1; $week <= $weekCount; $week++) {
    $weekDate = date('Y-m-d', strtotime("-" . ($week - 1) . " weeks"));
    $weekStart = date('Y-m-d', strtotime('monday this week', strtotime($weekDate)));
    $weekEnd = date('Y-m-d', strtotime('sunday this week', strtotime($weekDate)));
    $reportingPeriod = "$weekStart";
    
    $salesAmount = mt_rand(10000, 100000);
    $salesCount = mt_rand(20, 200);
    $commissionAmount = $salesAmount * 0.05;
    $targetAmount = 70000;
    $achievementRate = min(100, ($salesAmount / $targetAmount) * 100);
    
    $weeklyPerformance[] = [
        'reporting_period' => $reportingPeriod,
        'period_label' => $weekStart . ' ~ ' . $weekEnd,
        'period_type' => 'weekly',
        'sales_amount' => $salesAmount,
        'sales_count' => $salesCount,
        'commission_amount' => $commissionAmount,
        'prize_claims_amount' => mt_rand(5000, 50000),
        'prize_claims_count' => mt_rand(10, 50),
        'customer_count' => mt_rand(5, 20),
        'achievement_rate' => $achievementRate,
        'performance_rating' => getPerformanceRating($achievementRate)
    ];
}

// 선택된 기간 유형에 따라 데이터 설정
switch ($selectedPeriodType) {
    case 'yearly':
        $performanceData = $yearlyPerformance;
        break;
    
    case 'monthly':
        $performanceData = $monthlyPerformance;
        break;
    
    case 'weekly':
        $performanceData = $weeklyPerformance;
        break;
    
    default:
        $performanceData = $monthlyPerformance;
}

// 세션에 메시지가 있는지 확인
if (isset($_SESSION['message']) && isset($_SESSION['message_type'])) {
    $message = $_SESSION['message'];
    $messageType = $_SESSION['message_type'];
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}

// 페이지 제목 및 메타데이터
$pageTitle = "판매점 성과 대시보드: " . htmlspecialchars($storeInfo['store_name']);
$pageDescription = "판매점의 성과 지표 및 통계 정보";
$activeMenu = "store";
$activeSubMenu = "store-list";

// CSRF 토큰 생성 (테스트용)
$_SESSION['csrf_token'] = md5(uniqid(mt_rand(), true));

/**
 * 달성률에 따른 성과 등급 반환
 * 
 * @param float $achievementRate 달성률
 * @return string 성과 등급
 */
function getPerformanceRating($achievementRate) {
    if ($achievementRate >= 90) {
        return 'excellent';
    } else if ($achievementRate >= 75) {
        return 'good';
    } else if ($achievementRate >= 50) {
        return 'average';
    } else {
        return 'poor';
    }
}

// 헤더 템플릿 포함
include 'test-header.php';
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="page-title-div">
                    <h2 class="title"><?php echo $pageTitle; ?></h2>
                    <p class="sub-title"><?php echo $pageDescription; ?></p>
                </div>
            </div>
        </div>
        
        <!-- 탐색 경로 -->
        <div class="row">
            <div class="col-md-12">
                <ol class="breadcrumb">
                    <li><a href="../../dashboard/"><i class="fa fa-dashboard"></i> 대시보드</a></li>
                    <li><a href="store-list.php">판매점 관리</a></li>
                    <li><a href="store-details.php?id=<?php echo $storeId; ?>"><?php echo htmlspecialchars($storeInfo['store_name']); ?></a></li>
                    <li class="active">성과 대시보드</li>
                </ol>
            </div>
        </div>
        
        <!-- 알림 메시지 -->
        <?php if (!empty($message)): ?>
        <div class="row">
            <div class="col-md-12">
                <div class="alert alert-<?php echo $messageType; ?> alert-dismissible" role="alert">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <?php echo $message; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- 판매점 정보 카드 -->
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title">판매점 정보</h3>
                    </div>
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-4">
                                <dl class="dl-horizontal">
                                    <dt>판매점명:</dt>
                                    <dd><?php echo htmlspecialchars($storeInfo['store_name']); ?></dd>
                                </dl>
                            </div>
                            <div class="col-md-4">
                                <dl class="dl-horizontal">
                                    <dt>판매점 코드:</dt>
                                    <dd><?php echo htmlspecialchars($storeInfo['store_code']); ?></dd>
                                </dl>
                            </div>
                            <div class="col-md-4">
                                <dl class="dl-horizontal">
                                    <dt>판매점 상태:</dt>
                                    <dd>
                                        <?php 
                                        $statusLabels = [
                                            'active' => '<span class="label label-success">활성</span>',
                                            'inactive' => '<span class="label label-warning">비활성</span>',
                                            'pending' => '<span class="label label-info">대기중</span>',
                                            'terminated' => '<span class="label label-danger">계약해지</span>'
                                        ];
                                        echo isset($statusLabels[$storeInfo['status']]) 
                                            ? $statusLabels[$storeInfo['status']] 
                                            : htmlspecialchars($storeInfo['status']);
                                        ?>
                                    </dd>
                                </dl>
                            </div>
                        </div>
                        <div class="text-right">
                            <a href="store-details.php?id=<?php echo $storeId; ?>" class="btn btn-info btn-sm">
                                <i class="fa fa-eye"></i> 판매점 상세 정보
                            </a>
                            <a href="store-contracts.php?store_id=<?php echo $storeId; ?>" class="btn btn-primary btn-sm">
                                <i class="fa fa-file-contract"></i> 계약 관리
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 필터 및 컨트롤 -->
        <div class="row mb-3">
            <div class="col-md-12">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <form method="get" action="store-performance.php" class="form-inline">
                            <input type="hidden" name="store_id" value="<?php echo $storeId; ?>">
                            
                            <div class="form-group mr-3">
                                <label for="year" class="mr-2">연도:</label>
                                <select class="form-control" id="year" name="year">
                                    <?php for ($y = $currentYear - 5; $y <= $currentYear; $y++): ?>
                                    <option value="<?php echo $y; ?>" <?php echo ($y == $selectedYear) ? 'selected' : ''; ?>>
                                        <?php echo $y; ?>년
                                    </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            
                            <div class="form-group mr-3">
                                <label for="period_type" class="mr-2">기간 유형:</label>
                                <select class="form-control" id="period_type" name="period_type">
                                    <option value="yearly" <?php echo ($selectedPeriodType === 'yearly') ? 'selected' : ''; ?>>연간</option>
                                    <option value="monthly" <?php echo ($selectedPeriodType === 'monthly') ? 'selected' : ''; ?>>월간</option>
                                    <option value="weekly" <?php echo ($selectedPeriodType === 'weekly') ? 'selected' : ''; ?>>주간</option>
                                </select>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-filter"></i> 필터 적용
                            </button>
                            
                            <div class="pull-right">
                                <a href="performance-details.php?store_id=<?php echo $storeId; ?>" class="btn btn-info">
                                    <i class="fa fa-chart-bar"></i> 상세 분석
                                </a>
                                <a href="#" class="btn btn-success" id="exportBtn">
                                    <i class="fa fa-download"></i> 내보내기 (Excel)
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 주요 지표 카드 -->
        <div class="row mb-4">
            <?php 
            // 가장 최근 성과 데이터 가져오기
            $latestPerformance = !empty($performanceData) ? $performanceData[0] : null;
            
            // 총 누적 데이터 계산
            $totalSales = 0;
            $totalCommission = 0;
            $totalPrizeClaims = 0;
            $averageAchievement = 0;
            
            foreach ($performanceData as $data) {
                $totalSales += $data['sales_amount'];
                $totalCommission += $data['commission_amount'];
                $totalPrizeClaims += $data['prize_claims_amount'];
                $averageAchievement += $data['achievement_rate'];
            }
            
            $dataCount = count($performanceData);
            $averageAchievement = $dataCount > 0 ? $averageAchievement / $dataCount : 0;
            ?>
            
            <div class="col-md-3 col-sm-6">
                <div class="card bg-primary text-white mb-4">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fa fa-shopping-cart"></i> 총 판매액</h5>
                        <h3 class="mt-3">₩ <?php echo number_format($totalSales); ?></h3>
                        <p class="card-text">
                            <?php if ($selectedPeriodType === 'yearly'): ?>
                            (<?php echo $dataCount; ?>년 누적)
                            <?php elseif ($selectedPeriodType === 'monthly'): ?>
                            (<?php echo $selectedYear; ?>년 누적)
                            <?php elseif ($selectedPeriodType === 'weekly'): ?>
                            (최근 <?php echo $dataCount; ?>주 누적)
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 col-sm-6">
                <div class="card bg-success text-white mb-4">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fa fa-money-bill"></i> 수수료 수익</h5>
                        <h3 class="mt-3">₩ <?php echo number_format($totalCommission); ?></h3>
                        <p class="card-text">
                            <?php if ($selectedPeriodType === 'yearly'): ?>
                            (<?php echo $dataCount; ?>년 누적)
                            <?php elseif ($selectedPeriodType === 'monthly'): ?>
                            (<?php echo $selectedYear; ?>년 누적)
                            <?php elseif ($selectedPeriodType === 'weekly'): ?>
                            (최근 <?php echo $dataCount; ?>주 누적)
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 col-sm-6">
                <div class="card bg-info text-white mb-4">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fa fa-trophy"></i> 당첨금 지급액</h5>
                        <h3 class="mt-3">₩ <?php echo number_format($totalPrizeClaims); ?></h3>
                        <p class="card-text">
                            <?php if ($selectedPeriodType === 'yearly'): ?>
                            (<?php echo $dataCount; ?>년 누적)
                            <?php elseif ($selectedPeriodType === 'monthly'): ?>
                            (<?php echo $selectedYear; ?>년 누적)
                            <?php elseif ($selectedPeriodType === 'weekly'): ?>
                            (최근 <?php echo $dataCount; ?>주 누적)
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 col-sm-6">
                <div class="card 
                    <?php
                    if ($averageAchievement >= 90) echo 'bg-success';
                    else if ($averageAchievement >= 75) echo 'bg-info';
                    else if ($averageAchievement >= 50) echo 'bg-warning';
                    else echo 'bg-danger';
                    ?> text-white mb-4">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fa fa-chart-line"></i> 평균 목표 달성률</h5>
                        <h3 class="mt-3"><?php echo number_format($averageAchievement, 1); ?>%</h3>
                        <p class="card-text">
                            <?php if ($selectedPeriodType === 'yearly'): ?>
                            (<?php echo $dataCount; ?>년 평균)
                            <?php elseif ($selectedPeriodType === 'monthly'): ?>
                            (<?php echo $selectedYear; ?>년 평균)
                            <?php elseif ($selectedPeriodType === 'weekly'): ?>
                            (최근 <?php echo $dataCount; ?>주 평균)
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 성과 그래프 -->
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title">
                            <?php if ($selectedPeriodType === 'yearly'): ?>
                            연간 판매 실적 (<?php echo $currentYear - 2; ?> ~ <?php echo $currentYear; ?>)
                            <?php elseif ($selectedPeriodType === 'monthly'): ?>
                            월간 판매 실적 (<?php echo $selectedYear; ?>년)
                            <?php elseif ($selectedPeriodType === 'weekly'): ?>
                            주간 판매 실적 (최근 <?php echo $dataCount; ?>주)
                            <?php endif; ?>
                        </h3>
                    </div>
                    <div class="panel-body">
                        <canvas id="salesPerformanceChart" width="100%" height="40"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 목표 달성률 그래프 -->
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title">
                            <?php if ($selectedPeriodType === 'yearly'): ?>
                            연간 목표 달성률 (<?php echo $currentYear - 2; ?> ~ <?php echo $currentYear; ?>)
                            <?php elseif ($selectedPeriodType === 'monthly'): ?>
                            월간 목표 달성률 (<?php echo $selectedYear; ?>년)
                            <?php elseif ($selectedPeriodType === 'weekly'): ?>
                            주간 목표 달성률 (최근 <?php echo $dataCount; ?>주)
                            <?php endif; ?>
                        </h3>
                    </div>
                    <div class="panel-body">
                        <canvas id="achievementRateChart" width="100%" height="40"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 성과 데이터 테이블 -->
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title">
                            <?php if ($selectedPeriodType === 'yearly'): ?>
                            연간 성과 데이터 (<?php echo $currentYear - 2; ?> ~ <?php echo $currentYear; ?>)
                            <?php elseif ($selectedPeriodType === 'monthly'): ?>
                            월간 성과 데이터 (<?php echo $selectedYear; ?>년)
                            <?php elseif ($selectedPeriodType === 'weekly'): ?>
                            주간 성과 데이터 (최근 <?php echo $dataCount; ?>주)
                            <?php endif; ?>
                        </h3>
                    </div>
                    <div class="panel-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>기간</th>
                                        <th>판매액 (₩)</th>
                                        <th>판매 건수</th>
                                        <th>수수료 (₩)</th>
                                        <th>당첨금 지급액 (₩)</th>
                                        <th>고객 수</th>
                                        <th>목표 달성률</th>
                                        <th>등급</th>
                                        <th>상세</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($performanceData)): ?>
                                    <tr>
                                        <td colspan="9" class="text-center">데이터가 없습니다.</td>
                                    </tr>
                                    <?php else: ?>
                                        <?php foreach ($performanceData as $data): ?>
                                        <tr>
                                            <td>
                                                <?php 
                                                if ($selectedPeriodType === 'yearly') {
                                                    echo $data['reporting_period'] . '년';
                                                } elseif ($selectedPeriodType === 'monthly') {
                                                    echo $data['period_label'];
                                                } elseif ($selectedPeriodType === 'weekly') {
                                                    echo $data['period_label'];
                                                }
                                                ?>
                                            </td>
                                            <td class="text-right"><?php echo number_format($data['sales_amount']); ?></td>
                                            <td class="text-right"><?php echo number_format($data['sales_count']); ?></td>
                                            <td class="text-right"><?php echo number_format($data['commission_amount']); ?></td>
                                            <td class="text-right"><?php echo number_format($data['prize_claims_amount']); ?></td>
                                            <td class="text-right"><?php echo number_format($data['customer_count']); ?></td>
                                            <td class="text-center">
                                                <div class="progress">
                                                    <div class="progress-bar
                                                        <?php
                                                        if ($data['achievement_rate'] >= 90) echo 'progress-bar-success';
                                                        else if ($data['achievement_rate'] >= 75) echo 'progress-bar-info';
                                                        else if ($data['achievement_rate'] >= 50) echo 'progress-bar-warning';
                                                        else echo 'progress-bar-danger';
                                                        ?>" role="progressbar" 
                                                        style="width: <?php echo $data['achievement_rate']; ?>%"
                                                        aria-valuenow="<?php echo $data['achievement_rate']; ?>" 
                                                        aria-valuemin="0" 
                                                        aria-valuemax="100">
                                                        <?php echo number_format($data['achievement_rate'], 1); ?>%
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <?php 
                                                $ratingLabels = [
                                                    'excellent' => '<span class="label label-success">최우수</span>',
                                                    'good' => '<span class="label label-info">우수</span>',
                                                    'average' => '<span class="label label-warning">보통</span>',
                                                    'poor' => '<span class="label label-danger">미흡</span>'
                                                ];
                                                echo isset($ratingLabels[$data['performance_rating']]) 
                                                    ? $ratingLabels[$data['performance_rating']] 
                                                    : htmlspecialchars($data['performance_rating']);
                                                ?>
                                            </td>
                                            <td class="text-center">
                                                <a href="performance-details.php?store_id=<?php echo $storeId; ?>&period=<?php echo $data['reporting_period']; ?>&period_type=<?php echo $data['period_type']; ?>" 
                                                   class="btn btn-info btn-xs">
                                                    <i class="fa fa-search"></i> 상세
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js 라이브러리 -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@2.9.4/dist/Chart.min.js"></script>

<!-- 차트 초기화 스크립트 -->
<script>
// 판매 실적 차트
var salesCtx = document.getElementById('salesPerformanceChart').getContext('2d');
var salesPerformanceChart = new Chart(salesCtx, {
    type: 'bar',
    data: {
        labels: [
            <?php foreach ($performanceData as $data): ?>
            <?php 
            if ($selectedPeriodType === 'yearly') {
                echo "'" . $data['reporting_period'] . "년', ";
            } elseif ($selectedPeriodType === 'monthly') {
                echo "'" . $data['period_label'] . "', ";
            } elseif ($selectedPeriodType === 'weekly') {
                echo "'" . $data['period_label'] . "', ";
            }
            ?>
            <?php endforeach; ?>
        ],
        datasets: [
            {
                label: '판매액',
                backgroundColor: 'rgba(54, 162, 235, 0.5)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1,
                data: [
                    <?php foreach ($performanceData as $data): ?>
                    <?php echo $data['sales_amount'] . ', '; ?>
                    <?php endforeach; ?>
                ]
            },
            {
                label: '수수료',
                backgroundColor: 'rgba(75, 192, 192, 0.5)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 1,
                data: [
                    <?php foreach ($performanceData as $data): ?>
                    <?php echo $data['commission_amount'] . ', '; ?>
                    <?php endforeach; ?>
                ]
            },
            {
                label: '당첨금 지급',
                backgroundColor: 'rgba(255, 159, 64, 0.5)',
                borderColor: 'rgba(255, 159, 64, 1)',
                borderWidth: 1,
                data: [
                    <?php foreach ($performanceData as $data): ?>
                    <?php echo $data['prize_claims_amount'] . ', '; ?>
                    <?php endforeach; ?>
                ]
            }
        ]
    },
    options: {
        responsive: true,
        scales: {
            yAxes: [{
                ticks: {
                    beginAtZero: true,
                    callback: function(value, index, values) {
                        return '₩' + value.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                    }
                }
            }]
        },
        tooltips: {
            callbacks: {
                label: function(tooltipItem, data) {
                    var label = data.datasets[tooltipItem.datasetIndex].label || '';
                    if (label) {
                        label += ': ';
                    }
                    label += '₩' + tooltipItem.yLabel.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                    return label;
                }
            }
        }
    }
});

// 목표 달성률 차트
var achievementCtx = document.getElementById('achievementRateChart').getContext('2d');
var achievementRateChart = new Chart(achievementCtx, {
    type: 'line',
    data: {
        labels: [
            <?php foreach ($performanceData as $data): ?>
            <?php 
            if ($selectedPeriodType === 'yearly') {
                echo "'" . $data['reporting_period'] . "년', ";
            } elseif ($selectedPeriodType === 'monthly') {
                echo "'" . $data['period_label'] . "', ";
            } elseif ($selectedPeriodType === 'weekly') {
                echo "'" . $data['period_label'] . "', ";
            }
            ?>
            <?php endforeach; ?>
        ],
        datasets: [
            {
                label: '목표 달성률',
                backgroundColor: 'rgba(255, 99, 132, 0.2)',
                borderColor: 'rgba(255, 99, 132, 1)',
                borderWidth: 2,
                pointBackgroundColor: 'rgba(255, 99, 132, 1)',
                pointBorderColor: '#fff',
                pointHoverBackgroundColor: '#fff',
                pointHoverBorderColor: 'rgba(255, 99, 132, 1)',
                data: [
                    <?php foreach ($performanceData as $data): ?>
                    <?php echo $data['achievement_rate'] . ', '; ?>
                    <?php endforeach; ?>
                ],
                fill: false,
                tension: 0.1
            },
            {
                label: '목표',
                backgroundColor: 'rgba(128, 128, 128, 0.2)',
                borderColor: 'rgba(128, 128, 128, 1)',
                borderWidth: 2,
                pointRadius: 0,
                data: [
                    <?php foreach ($performanceData as $data): ?>
                    100, 
                    <?php endforeach; ?>
                ],
                fill: false,
                borderDash: [5, 5]
            }
        ]
    },
    options: {
        responsive: true,
        scales: {
            yAxes: [{
                ticks: {
                    beginAtZero: true,
                    max: 120,
                    callback: function(value, index, values) {
                        return value + '%';
                    }
                }
            }]
        },
        tooltips: {
            callbacks: {
                label: function(tooltipItem, data) {
                    var label = data.datasets[tooltipItem.datasetIndex].label || '';
                    if (label) {
                        label += ': ';
                    }
                    label += tooltipItem.yLabel.toFixed(1) + '%';
                    return label;
                }
            }
        }
    }
});

// Excel 내보내기 버튼 클릭 이벤트
document.getElementById('exportBtn').addEventListener('click', function(e) {
    e.preventDefault();
    alert('Excel 내보내기 기능은 아직 구현되지 않았습니다.');
});
</script>

<?php
// 푸터 템플릿 포함
include 'test-footer.php';
?>