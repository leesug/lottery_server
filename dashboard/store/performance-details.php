<?php
/**
 * 판매 실적 상세 페이지
 * 
 * 이 페이지는 판매점의 특정 기간에 대한 성과 지표를 상세하게 분석합니다.
 */

// 필수 파일 포함
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

// 페이지 접근 권한 확인
checkPageAccess('store_management');

// URL 파라미터 가져오기
$storeId = isset($_GET['store_id']) ? intval($_GET['store_id']) : 0;
$period = isset($_GET['period']) ? sanitizeInput($_GET['period']) : date('Y-m');
$periodType = isset($_GET['period_type']) ? sanitizeInput($_GET['period_type']) : 'monthly';

if ($storeId <= 0) {
    // 유효한 ID가 없으면 판매점 목록으로 리다이렉트
    header('Location: store-list.php');
    exit;
}

// 변수 초기화
$message = '';
$messageType = '';
$storeInfo = null;
$performanceData = null;
$salesByProduct = [];
$salesByDay = [];
$comparisonData = [];

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

// 기간 유형에 따라 표시할 제목 설정
$periodLabel = '';
$startDate = '';
$endDate = '';

switch ($periodType) {
    case 'yearly':
        $periodLabel = $period . '년';
        $startDate = $period . '-01-01';
        $endDate = $period . '-12-31';
        break;
    
    case 'monthly':
        $year = substr($period, 0, 4);
        $month = substr($period, 5, 2);
        $monthName = ''; 
        
        // 월 이름 매핑
        $monthNames = [
            '01' => '1월', '02' => '2월', '03' => '3월', '04' => '4월',
            '05' => '5월', '06' => '6월', '07' => '7월', '08' => '8월',
            '09' => '9월', '10' => '10월', '11' => '11월', '12' => '12월'
        ];
        
        if (isset($monthNames[$month])) {
            $monthName = $monthNames[$month];
        }
        
        $periodLabel = $year . '년 ' . $monthName;
        $startDate = $period . '-01';
        $endDate = date('Y-m-t', strtotime($startDate));
        break;
    
    case 'weekly':
        $weekStart = date('Y-m-d', strtotime($period));
        $weekEnd = date('Y-m-d', strtotime('+6 days', strtotime($period)));
        $periodLabel = $weekStart . ' ~ ' . $weekEnd;
        $startDate = $weekStart;
        $endDate = $weekEnd;
        break;
    
    default:
        $periodLabel = $period;
        $startDate = $period . '-01';
        $endDate = date('Y-m-t', strtotime($startDate));
}

// 성과 데이터 생성 (테스트용)
$salesAmount = mt_rand(100000, 500000);
$salesCount = mt_rand(200, 1000);
$commissionRate = 0.05;
$commissionAmount = $salesAmount * $commissionRate;
$targetAmount = 300000;
$achievementRate = min(100, ($salesAmount / $targetAmount) * 100);

$performanceData = [
    'reporting_period' => $period,
    'period_type' => $periodType,
    'sales_amount' => $salesAmount,
    'sales_count' => $salesCount,
    'commission_rate' => $commissionRate * 100,
    'commission_amount' => $commissionAmount,
    'prize_claims_amount' => mt_rand(50000, 200000),
    'prize_claims_count' => mt_rand(50, 200),
    'customer_count' => mt_rand(20, 100),
    'new_customer_count' => mt_rand(5, 30),
    'achievement_rate' => $achievementRate,
    'performance_rating' => getPerformanceRating($achievementRate),
    'target_amount' => $targetAmount
];

// 제품별 판매 데이터 생성 (테스트용)
$products = [
    'LOTTO645' => '로또 6/45',
    'POWERBALL' => '파워볼',
    'SPEETTO' => '스피또',
    'PENSION' => '연금복권 520',
    'FUND' => '기금복권',
    'BIG' => '빙고'
];

foreach ($products as $productCode => $productName) {
    $productSales = mt_rand(5000, 100000);
    $productCount = mt_rand(10, 200);
    
    $salesByProduct[] = [
        'product_code' => $productCode,
        'product_name' => $productName,
        'sales_amount' => $productSales,
        'sales_count' => $productCount,
        'sales_percentage' => ($productSales / $salesAmount) * 100
    ];
}

// 일별 판매 데이터 생성 (테스트용)
if ($periodType === 'monthly') {
    $daysInMonth = date('t', strtotime($startDate));
    
    for ($day = 1; $day <= $daysInMonth; $day++) {
        $dayDate = date('Y-m-d', strtotime($period . '-' . str_pad($day, 2, '0', STR_PAD_LEFT)));
        $dayOfWeek = date('w', strtotime($dayDate));
        
        // 주말에는 판매량이 더 많게 설정
        $multiplier = ($dayOfWeek == 0 || $dayOfWeek == 6) ? 1.5 : 1.0;
        $daySales = mt_rand(1000, 5000) * $multiplier;
        
        $salesByDay[] = [
            'date' => $dayDate,
            'day_of_week' => getDayOfWeekName($dayOfWeek),
            'sales_amount' => $daySales,
            'sales_count' => mt_rand(5, 50) * $multiplier
        ];
    }
}

// 비교 데이터 생성 (테스트용)
// 이전 동일 기간과의 비교
$prevSalesAmount = mt_rand(80000, 400000);
$prevAchievementRate = min(100, ($prevSalesAmount / $targetAmount) * 100);

$comparisonData = [
    'prev_period' => [
        'period_label' => '이전 동일 기간',
        'sales_amount' => $prevSalesAmount,
        'sales_count' => mt_rand(150, 800),
        'commission_amount' => $prevSalesAmount * $commissionRate,
        'achievement_rate' => $prevAchievementRate,
        'prize_claims_amount' => mt_rand(40000, 150000)
    ]
];

// 평균 대비 비교
$avgSalesAmount = mt_rand(90000, 450000);
$avgAchievementRate = min(100, ($avgSalesAmount / $targetAmount) * 100);

$comparisonData['avg'] = [
    'period_label' => '전체 판매점 평균',
    'sales_amount' => $avgSalesAmount,
    'sales_count' => mt_rand(180, 900),
    'commission_amount' => $avgSalesAmount * $commissionRate,
    'achievement_rate' => $avgAchievementRate,
    'prize_claims_amount' => mt_rand(45000, 180000)
];

// 세션에 메시지가 있는지 확인
if (isset($_SESSION['message']) && isset($_SESSION['message_type'])) {
    $message = $_SESSION['message'];
    $messageType = $_SESSION['message_type'];
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}

// 페이지 제목 및 메타데이터
$pageTitle = "판매 실적 상세 분석: " . htmlspecialchars($storeInfo['store_name']) . " - " . $periodLabel;
$pageDescription = "판매점의 상세 성과 분석 및 통계 정보";
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

/**
 * 요일 이름 반환
 * 
 * @param int $dayOfWeek 요일 번호 (0: 일요일, 6: 토요일)
 * @return string 요일 이름
 */
function getDayOfWeekName($dayOfWeek) {
    $days = ['일', '월', '화', '수', '목', '금', '토'];
    return isset($days[$dayOfWeek]) ? $days[$dayOfWeek] : '';
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
                    <li><a href="store-performance.php?store_id=<?php echo $storeId; ?>">성과 대시보드</a></li>
                    <li class="active">성과 상세 분석</li>
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
        
        <!-- 판매점 및 기간 정보 카드 -->
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-6">
                                <dl class="dl-horizontal">
                                    <dt>판매점명:</dt>
                                    <dd><?php echo htmlspecialchars($storeInfo['store_name']); ?></dd>
                                    <dt>판매점 코드:</dt>
                                    <dd><?php echo htmlspecialchars($storeInfo['store_code']); ?></dd>
                                    <dt>상태:</dt>
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
                            <div class="col-md-6">
                                <dl class="dl-horizontal">
                                    <dt>분석 기간:</dt>
                                    <dd><?php echo $periodLabel; ?></dd>
                                    <dt>기간 유형:</dt>
                                    <dd>
                                        <?php 
                                        $periodTypeLabels = [
                                            'yearly' => '연간',
                                            'monthly' => '월간',
                                            'weekly' => '주간',
                                            'daily' => '일간'
                                        ];
                                        echo isset($periodTypeLabels[$periodType]) 
                                            ? $periodTypeLabels[$periodType] 
                                            : htmlspecialchars($periodType);
                                        ?>
                                    </dd>
                                    <dt>분석 기준일:</dt>
                                    <dd><?php echo date('Y-m-d H:i:s'); ?></dd>
                                </dl>
                            </div>
                        </div>
                        <div class="text-right">
                            <a href="store-performance.php?store_id=<?php echo $storeId; ?>" class="btn btn-info btn-sm">
                                <i class="fa fa-arrow-left"></i> 성과 대시보드로 돌아가기
                            </a>
                            <a href="#" class="btn btn-success btn-sm" id="exportBtn">
                                <i class="fa fa-download"></i> 보고서 내보내기
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 성과 요약 카드 -->
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title">성과 요약</h3>
                    </div>
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-3 text-center">
                                <div class="card">
                                    <h4>판매액</h4>
                                    <h2 class="text-primary">₩ <?php echo number_format($performanceData['sales_amount']); ?></h2>
                                    <p><?php echo number_format($performanceData['sales_count']); ?>건</p>
                                </div>
                            </div>
                            <div class="col-md-3 text-center">
                                <div class="card">
                                    <h4>수수료 수익</h4>
                                    <h2 class="text-success">₩ <?php echo number_format($performanceData['commission_amount']); ?></h2>
                                    <p>요율: <?php echo number_format($performanceData['commission_rate'], 1); ?>%</p>
                                </div>
                            </div>
                            <div class="col-md-3 text-center">
                                <div class="card">
                                    <h4>당첨금 지급</h4>
                                    <h2 class="text-info">₩ <?php echo number_format($performanceData['prize_claims_amount']); ?></h2>
                                    <p><?php echo number_format($performanceData['prize_claims_count']); ?>건</p>
                                </div>
                            </div>
                            <div class="col-md-3 text-center">
                                <div class="card">
                                    <h4>목표 달성률</h4>
                                    <h2 class="
                                        <?php
                                        if ($performanceData['achievement_rate'] >= 90) echo 'text-success';
                                        else if ($performanceData['achievement_rate'] >= 75) echo 'text-info';
                                        else if ($performanceData['achievement_rate'] >= 50) echo 'text-warning';
                                        else echo 'text-danger';
                                        ?>">
                                        <?php echo number_format($performanceData['achievement_rate'], 1); ?>%
                                    </h2>
                                    <p>
                                        <?php 
                                        $ratingLabels = [
                                            'excellent' => '최우수',
                                            'good' => '우수',
                                            'average' => '보통',
                                            'poor' => '미흡'
                                        ];
                                        echo isset($ratingLabels[$performanceData['performance_rating']]) 
                                            ? $ratingLabels[$performanceData['performance_rating']] 
                                            : htmlspecialchars($performanceData['performance_rating']);
                                        ?>
                                        등급
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-4">
                            <div class="col-md-12">
                                <div class="progress">
                                    <div class="progress-bar
                                        <?php
                                        if ($performanceData['achievement_rate'] >= 90) echo 'progress-bar-success';
                                        else if ($performanceData['achievement_rate'] >= 75) echo 'progress-bar-info';
                                        else if ($performanceData['achievement_rate'] >= 50) echo 'progress-bar-warning';
                                        else echo 'progress-bar-danger';
                                        ?>" role="progressbar" 
                                        style="width: <?php echo min(100, $performanceData['achievement_rate']); ?>%"
                                        aria-valuenow="<?php echo $performanceData['achievement_rate']; ?>" 
                                        aria-valuemin="0" 
                                        aria-valuemax="100">
                                        <?php echo number_format($performanceData['achievement_rate'], 1); ?>%
                                    </div>
                                </div>
                                <div class="text-center">
                                    <small>목표: ₩ <?php echo number_format($performanceData['target_amount']); ?> / 
                                    실적: ₩ <?php echo number_format($performanceData['sales_amount']); ?></small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 비교 분석 그래프 -->
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title">비교 분석</h3>
                    </div>
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-6">
                                <canvas id="salesComparisonChart" width="100%" height="50"></canvas>
                            </div>
                            <div class="col-md-6">
                                <canvas id="achievementComparisonChart" width="100%" height="50"></canvas>
                            </div>
                        </div>
                        <div class="table-responsive mt-4">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>구분</th>
                                        <th>판매액 (₩)</th>
                                        <th>판매 건수</th>
                                        <th>수수료 (₩)</th>
                                        <th>당첨금 지급 (₩)</th>
                                        <th>목표 달성률</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><strong>현재 기간</strong></td>
                                        <td class="text-right"><?php echo number_format($performanceData['sales_amount']); ?></td>
                                        <td class="text-right"><?php echo number_format($performanceData['sales_count']); ?></td>
                                        <td class="text-right"><?php echo number_format($performanceData['commission_amount']); ?></td>
                                        <td class="text-right"><?php echo number_format($performanceData['prize_claims_amount']); ?></td>
                                        <td class="text-right"><?php echo number_format($performanceData['achievement_rate'], 1); ?>%</td>
                                    </tr>
                                    <tr>
                                        <td><?php echo $comparisonData['prev_period']['period_label']; ?></td>
                                        <td class="text-right">
                                            <?php echo number_format($comparisonData['prev_period']['sales_amount']); ?>
                                            <?php 
                                            $diff = $performanceData['sales_amount'] - $comparisonData['prev_period']['sales_amount'];
                                            $diffPct = ($comparisonData['prev_period']['sales_amount'] > 0) 
                                                ? ($diff / $comparisonData['prev_period']['sales_amount']) * 100 
                                                : 0;
                                            if ($diff > 0) {
                                                echo ' <span class="text-success">(+' . number_format($diffPct, 1) . '%)</span>';
                                            } else if ($diff < 0) {
                                                echo ' <span class="text-danger">(' . number_format($diffPct, 1) . '%)</span>';
                                            }
                                            ?>
                                        </td>
                                        <td class="text-right"><?php echo number_format($comparisonData['prev_period']['sales_count']); ?></td>
                                        <td class="text-right"><?php echo number_format($comparisonData['prev_period']['commission_amount']); ?></td>
                                        <td class="text-right"><?php echo number_format($comparisonData['prev_period']['prize_claims_amount']); ?></td>
                                        <td class="text-right"><?php echo number_format($comparisonData['prev_period']['achievement_rate'], 1); ?>%</td>
                                    </tr>
                                    <tr>
                                        <td><?php echo $comparisonData['avg']['period_label']; ?></td>
                                        <td class="text-right">
                                            <?php echo number_format($comparisonData['avg']['sales_amount']); ?>
                                            <?php 
                                            $diff = $performanceData['sales_amount'] - $comparisonData['avg']['sales_amount'];
                                            $diffPct = ($comparisonData['avg']['sales_amount'] > 0) 
                                                ? ($diff / $comparisonData['avg']['sales_amount']) * 100 
                                                : 0;
                                            if ($diff > 0) {
                                                echo ' <span class="text-success">(+' . number_format($diffPct, 1) . '%)</span>';
                                            } else if ($diff < 0) {
                                                echo ' <span class="text-danger">(' . number_format($diffPct, 1) . '%)</span>';
                                            }
                                            ?>
                                        </td>
                                        <td class="text-right"><?php echo number_format($comparisonData['avg']['sales_count']); ?></td>
                                        <td class="text-right"><?php echo number_format($comparisonData['avg']['commission_amount']); ?></td>
                                        <td class="text-right"><?php echo number_format($comparisonData['avg']['prize_claims_amount']); ?></td>
                                        <td class="text-right"><?php echo number_format($comparisonData['avg']['achievement_rate'], 1); ?>%</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 제품별 판매 분석 -->
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title">제품별 판매 분석</h3>
                    </div>
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-6">
                                <canvas id="productSalesChart" width="100%" height="60"></canvas>
                            </div>
                            <div class="col-md-6">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped">
                                        <thead>
                                            <tr>
                                                <th>제품</th>
                                                <th>판매액 (₩)</th>
                                                <th>비율</th>
                                                <th>판매 건수</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($salesByProduct as $product): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                                                <td class="text-right"><?php echo number_format($product['sales_amount']); ?></td>
                                                <td class="text-right"><?php echo number_format($product['sales_percentage'], 1); ?>%</td>
                                                <td class="text-right"><?php echo number_format($product['sales_count']); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <th>합계</th>
                                                <th class="text-right"><?php echo number_format($performanceData['sales_amount']); ?></th>
                                                <th class="text-right">100.0%</th>
                                                <th class="text-right"><?php echo number_format($performanceData['sales_count']); ?></th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if ($periodType === 'monthly' && !empty($salesByDay)): ?>
        <!-- 일별 판매 추이 -->
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title">일별 판매 추이</h3>
                    </div>
                    <div class="panel-body">
                        <canvas id="dailySalesChart" width="100%" height="50"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- 고객 분석 -->
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title">고객 분석</h3>
                    </div>
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-6">
                                <canvas id="customerChart" width="100%" height="50"></canvas>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="row">
                                        <div class="col-md-6 text-center">
                                            <h4>총 고객수</h4>
                                            <h2 class="text-primary"><?php echo number_format($performanceData['customer_count']); ?>명</h2>
                                        </div>
                                        <div class="col-md-6 text-center">
                                            <h4>신규 고객</h4>
                                            <h2 class="text-success"><?php echo number_format($performanceData['new_customer_count']); ?>명</h2>
                                            <p>(<?php echo number_format(($performanceData['new_customer_count'] / $performanceData['customer_count']) * 100, 1); ?>%)</p>
                                        </div>
                                    </div>
                                    <div class="row mt-4">
                                        <div class="col-md-6 text-center">
                                            <h4>고객당 평균 구매액</h4>
                                            <h3 class="text-info">
                                                ₩ <?php 
                                                $avgPerCustomer = ($performanceData['customer_count'] > 0) 
                                                    ? $performanceData['sales_amount'] / $performanceData['customer_count'] 
                                                    : 0;
                                                echo number_format($avgPerCustomer);
                                                ?>
                                            </h3>
                                        </div>
                                        <div class="col-md-6 text-center">
                                            <h4>고객당 평균 구매 횟수</h4>
                                            <h3 class="text-info">
                                                <?php 
                                                $avgCount = ($performanceData['customer_count'] > 0) 
                                                    ? $performanceData['sales_count'] / $performanceData['customer_count'] 
                                                    : 0;
                                                echo number_format($avgCount, 1);
                                                ?>회
                                            </h3>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 성과 분석 및 제안 -->
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title">성과 분석 및 제안</h3>
                    </div>
                    <div class="panel-body">
                        <h4>주요 분석 결과</h4>
                        <ul>
                            <?php 
                            // 성과에 따른 분석 내용 생성
                            $analysisPoints = [];
                            
                            // 이전 기간 대비 분석
                            $salesDiff = $performanceData['sales_amount'] - $comparisonData['prev_period']['sales_amount'];
                            $salesDiffPct = ($comparisonData['prev_period']['sales_amount'] > 0) 
                                ? ($salesDiff / $comparisonData['prev_period']['sales_amount']) * 100 
                                : 0;
                            
                            if ($salesDiff > 0) {
                                $analysisPoints[] = "이전 동일 기간 대비 판매액이 " . number_format(abs($salesDiffPct), 1) . "% 증가했습니다.";
                            } else {
                                $analysisPoints[] = "이전 동일 기간 대비 판매액이 " . number_format(abs($salesDiffPct), 1) . "% 감소했습니다.";
                            }
                            
                            // 최고 판매 제품 찾기
                            $topProduct = null;
                            $topAmount = 0;
                            foreach ($salesByProduct as $product) {
                                if ($product['sales_amount'] > $topAmount) {
                                    $topAmount = $product['sales_amount'];
                                    $topProduct = $product;
                                }
                            }
                            
                            if ($topProduct) {
                                $analysisPoints[] = "'" . $topProduct['product_name'] . "'이(가) 총 판매액의 " . 
                                    number_format($topProduct['sales_percentage'], 1) . "%를 차지하는 최고 매출 상품입니다.";
                            }
                            
                            // 목표 달성률 분석
                            if ($performanceData['achievement_rate'] >= 100) {
                                $analysisPoints[] = "목표를 " . number_format($performanceData['achievement_rate'] - 100, 1) . "% 초과 달성했습니다.";
                            } else if ($performanceData['achievement_rate'] >= 90) {
                                $analysisPoints[] = "목표의 " . number_format($performanceData['achievement_rate'], 1) . "%를 달성했으며 목표 달성률이 매우 높습니다.";
                            } else if ($performanceData['achievement_rate'] >= 75) {
                                $analysisPoints[] = "목표의 " . number_format($performanceData['achievement_rate'], 1) . "%를 달성했으며 양호한 성과를 보이고 있습니다.";
                            } else if ($performanceData['achievement_rate'] >= 50) {
                                $analysisPoints[] = "목표의 " . number_format($performanceData['achievement_rate'], 1) . "%만 달성하여 목표 달성이 미흡합니다.";
                            } else {
                                $analysisPoints[] = "목표의 " . number_format($performanceData['achievement_rate'], 1) . "%만 달성하여 목표 달성이 매우 부진합니다.";
                            }
                            
                            // 평균 대비 분석
                            $avgDiff = $performanceData['sales_amount'] - $comparisonData['avg']['sales_amount'];
                            $avgDiffPct = ($comparisonData['avg']['sales_amount'] > 0) 
                                ? ($avgDiff / $comparisonData['avg']['sales_amount']) * 100 
                                : 0;
                            
                            if ($avgDiff > 10) {
                                $analysisPoints[] = "전체 판매점 평균보다 " . number_format(abs($avgDiffPct), 1) . "% 높은 판매 성과를 보이고 있습니다.";
                            } else if ($avgDiff > 0) {
                                $analysisPoints[] = "전체 판매점 평균과 비슷한 수준의 판매 성과를 보이고 있습니다.";
                            } else {
                                $analysisPoints[] = "전체 판매점 평균보다 " . number_format(abs($avgDiffPct), 1) . "% 낮은 판매 성과를 보이고 있습니다.";
                            }
                            
                            // 신규 고객 비율 분석
                            $newCustomerRatio = ($performanceData['customer_count'] > 0) 
                                ? ($performanceData['new_customer_count'] / $performanceData['customer_count']) * 100 
                                : 0;
                            
                            if ($newCustomerRatio > 20) {
                                $analysisPoints[] = "신규 고객 비율이 " . number_format($newCustomerRatio, 1) . "%로 높은 편입니다.";
                            } else if ($newCustomerRatio > 10) {
                                $analysisPoints[] = "신규 고객 비율이 " . number_format($newCustomerRatio, 1) . "%로 적절한 수준입니다.";
                            } else {
                                $analysisPoints[] = "신규 고객 비율이 " . number_format($newCustomerRatio, 1) . "%로 낮은 편입니다.";
                            }
                            
                            // 분석 결과 출력
                            foreach ($analysisPoints as $point) {
                                echo "<li>" . $point . "</li>";
                            }
                            ?>
                        </ul>
                        
                        <h4 class="mt-4">제안 사항</h4>
                        <ul>
                            <?php 
                            // 성과에 따른 제안 내용 생성
                            $suggestions = [];
                            
                            // 목표 달성률에 따른 제안
                            if ($performanceData['achievement_rate'] < 75) {
                                $suggestions[] = "목표 달성률이 낮으므로, 추가 마케팅 활동 및 프로모션을 통해 판매 촉진이 필요합니다.";
                                $suggestions[] = "고객 유입을 늘리기 위한 지역 홍보 활동을 강화하세요.";
                            }
                            
                            // 제품 다양성 관련 제안
                            $productCount = count($salesByProduct);
                            if ($productCount <= 3 || ($topProduct && $topProduct['sales_percentage'] > 60)) {
                                $suggestions[] = "특정 상품에 편중된 판매 구조를 개선하기 위해 다양한 복권 상품의 균형 있는 판매를 권장합니다.";
                            }
                            
                            // 신규 고객 관련 제안
                            if ($newCustomerRatio < 10) {
                                $suggestions[] = "신규 고객 유치를 위한 전략이 필요합니다. 지역 이벤트 참여나 SNS를 통한 홍보를 고려해보세요.";
                            }
                            
                            // 판매 추이에 따른 제안
                            if ($salesDiff < 0) {
                                $suggestions[] = "판매액이 감소하고 있으므로, 고객 만족도 조사를 통해 개선점을 파악해보세요.";
                                $suggestions[] = "매장 환경 개선이나 서비스 품질 향상을 통해 고객 경험을 개선하세요.";
                            }
                            
                            // 평균 대비 성과에 따른 제안
                            if ($avgDiff < 0) {
                                $suggestions[] = "다른 판매점보다 성과가 낮으므로, 성공적인 판매점의 사례를 참고하여 벤치마킹하세요.";
                                $suggestions[] = "판매 기법 교육이나 상품 지식 향상을 위한 트레이닝에 참여하세요.";
                            }
                            
                            // 추가 일반 제안
                            $suggestions[] = "고객 데이터를 활용하여 고객의 구매 패턴을 분석하고 맞춤형 마케팅을 시도해보세요.";
                            $suggestions[] = "단골 고객에게 특별한 혜택이나 이벤트를 제공하여 고객 충성도를 높이세요.";
                            
                            // 제안 일부만 선택하여 출력 (최대 5개)
                            shuffle($suggestions);
                            $selectedSuggestions = array_slice($suggestions, 0, min(5, count($suggestions)));
                            
                            foreach ($selectedSuggestions as $suggestion) {
                                echo "<li>" . $suggestion . "</li>";
                            }
                            ?>
                        </ul>
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
// 판매액 비교 차트
var salesComparisonCtx = document.getElementById('salesComparisonChart').getContext('2d');
var salesComparisonChart = new Chart(salesComparisonCtx, {
    type: 'bar',
    data: {
        labels: ['현재 기간', '<?php echo $comparisonData['prev_period']['period_label']; ?>', '<?php echo $comparisonData['avg']['period_label']; ?>'],
        datasets: [{
            label: '판매액',
            backgroundColor: ['rgba(54, 162, 235, 0.5)', 'rgba(75, 192, 192, 0.5)', 'rgba(153, 102, 255, 0.5)'],
            borderColor: ['rgba(54, 162, 235, 1)', 'rgba(75, 192, 192, 1)', 'rgba(153, 102, 255, 1)'],
            borderWidth: 1,
            data: [
                <?php echo $performanceData['sales_amount']; ?>,
                <?php echo $comparisonData['prev_period']['sales_amount']; ?>,
                <?php echo $comparisonData['avg']['sales_amount']; ?>
            ]
        }]
    },
    options: {
        responsive: true,
        title: {
            display: true,
            text: '판매액 비교'
        },
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

// 목표 달성률 비교 차트
var achievementComparisonCtx = document.getElementById('achievementComparisonChart').getContext('2d');
var achievementComparisonChart = new Chart(achievementComparisonCtx, {
    type: 'bar',
    data: {
        labels: ['현재 기간', '<?php echo $comparisonData['prev_period']['period_label']; ?>', '<?php echo $comparisonData['avg']['period_label']; ?>'],
        datasets: [{
            label: '목표 달성률',
            backgroundColor: ['rgba(255, 99, 132, 0.5)', 'rgba(255, 159, 64, 0.5)', 'rgba(255, 205, 86, 0.5)'],
            borderColor: ['rgba(255, 99, 132, 1)', 'rgba(255, 159, 64, 1)', 'rgba(255, 205, 86, 1)'],
            borderWidth: 1,
            data: [
                <?php echo $performanceData['achievement_rate']; ?>,
                <?php echo $comparisonData['prev_period']['achievement_rate']; ?>,
                <?php echo $comparisonData['avg']['achievement_rate']; ?>
            ]
        }]
    },
    options: {
        responsive: true,
        title: {
            display: true,
            text: '목표 달성률 비교'
        },
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

// 제품별 판매 차트
var productSalesCtx = document.getElementById('productSalesChart').getContext('2d');
var productSalesChart = new Chart(productSalesCtx, {
    type: 'pie',
    data: {
        labels: [
            <?php foreach ($salesByProduct as $product): ?>
            '<?php echo $product['product_name']; ?>',
            <?php endforeach; ?>
        ],
        datasets: [{
            data: [
                <?php foreach ($salesByProduct as $product): ?>
                <?php echo $product['sales_amount']; ?>,
                <?php endforeach; ?>
            ],
            backgroundColor: [
                'rgba(255, 99, 132, 0.5)',
                'rgba(54, 162, 235, 0.5)',
                'rgba(255, 206, 86, 0.5)',
                'rgba(75, 192, 192, 0.5)',
                'rgba(153, 102, 255, 0.5)',
                'rgba(255, 159, 64, 0.5)'
            ],
            borderColor: [
                'rgba(255, 99, 132, 1)',
                'rgba(54, 162, 235, 1)',
                'rgba(255, 206, 86, 1)',
                'rgba(75, 192, 192, 1)',
                'rgba(153, 102, 255, 1)',
                'rgba(255, 159, 64, 1)'
            ],
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        title: {
            display: true,
            text: '제품별 판매액 분포'
        },
        tooltips: {
            callbacks: {
                label: function(tooltipItem, data) {
                    var label = data.labels[tooltipItem.index] || '';
                    if (label) {
                        label += ': ';
                    }
                    var value = data.datasets[0].data[tooltipItem.index];
                    label += '₩' + value.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                    
                    // 백분율 추가
                    var total = data.datasets[0].data.reduce((a, b) => a + b, 0);
                    var percentage = ((value / total) * 100).toFixed(1) + '%';
                    return label + ' (' + percentage + ')';
                }
            }
        }
    }
});

<?php if ($periodType === 'monthly' && !empty($salesByDay)): ?>
// 일별 판매 추이 차트
var dailySalesCtx = document.getElementById('dailySalesChart').getContext('2d');
var dailySalesChart = new Chart(dailySalesCtx, {
    type: 'line',
    data: {
        labels: [
            <?php foreach ($salesByDay as $day): ?>
            '<?php echo date('j', strtotime($day['date'])) . '일(' . $day['day_of_week'] . ')'; ?>',
            <?php endforeach; ?>
        ],
        datasets: [{
            label: '일별 판매액',
            data: [
                <?php foreach ($salesByDay as $day): ?>
                <?php echo $day['sales_amount']; ?>,
                <?php endforeach; ?>
            ],
            backgroundColor: 'rgba(54, 162, 235, 0.2)',
            borderColor: 'rgba(54, 162, 235, 1)',
            borderWidth: 2,
            pointBackgroundColor: 'rgba(54, 162, 235, 1)',
            pointBorderColor: '#fff',
            tension: 0.1,
            fill: true
        }]
    },
    options: {
        responsive: true,
        title: {
            display: true,
            text: '일별 판매액 추이'
        },
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
<?php endif; ?>

// 고객 분석 차트
var customerCtx = document.getElementById('customerChart').getContext('2d');
var customerChart = new Chart(customerCtx, {
    type: 'doughnut',
    data: {
        labels: ['기존 고객', '신규 고객'],
        datasets: [{
            data: [
                <?php echo $performanceData['customer_count'] - $performanceData['new_customer_count']; ?>,
                <?php echo $performanceData['new_customer_count']; ?>
            ],
            backgroundColor: [
                'rgba(54, 162, 235, 0.5)',
                'rgba(75, 192, 192, 0.5)'
            ],
            borderColor: [
                'rgba(54, 162, 235, 1)',
                'rgba(75, 192, 192, 1)'
            ],
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        title: {
            display: true,
            text: '고객 구성'
        },
        tooltips: {
            callbacks: {
                label: function(tooltipItem, data) {
                    var label = data.labels[tooltipItem.index] || '';
                    if (label) {
                        label += ': ';
                    }
                    var value = data.datasets[0].data[tooltipItem.index];
                    label += value.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",") + '명';
                    
                    // 백분율 추가
                    var total = data.datasets[0].data.reduce((a, b) => a + b, 0);
                    var percentage = ((value / total) * 100).toFixed(1) + '%';
                    return label + ' (' + percentage + ')';
                }
            }
        }
    }
});

// Excel 내보내기 버튼 클릭 이벤트
document.getElementById('exportBtn').addEventListener('click', function(e) {
    e.preventDefault();
    alert('보고서 내보내기 기능은 아직 구현되지 않았습니다.');
});
</script>

<?php
// 푸터 템플릿 포함
include 'test-footer.php';
?>