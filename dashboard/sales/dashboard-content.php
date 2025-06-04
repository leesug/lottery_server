<!-- 판매 관리 대시보드 콘텐츠 -->
<?php
// 필요한 파일 포함
if (!function_exists('formatCurrency')) {
    // 통화 타입 설정
    if (!defined('CURRENCY_SYMBOL')) define('CURRENCY_SYMBOL', '₹'); // 루피 기호
    if (!defined('DECIMAL_PLACES')) define('DECIMAL_PLACES', 2); // 소수점 자리수
    if (!defined('THOUSANDS_SEPARATOR')) define('THOUSANDS_SEPARATOR', ','); // 천 단위 구분자
    if (!defined('DECIMAL_SEPARATOR')) define('DECIMAL_SEPARATOR', '.'); // 소수점 구분자
    
    // 통화 형식화 함수
    function formatCurrency($amount, $includeSymbol = true) {
        $formattedAmount = number_format(
            $amount, 
            DECIMAL_PLACES, 
            DECIMAL_SEPARATOR, 
            THOUSANDS_SEPARATOR
        );
        
        if ($includeSymbol) {
            return CURRENCY_SYMBOL . $formattedAmount;
        } else {
            return $formattedAmount;
        }
    }
}

// 현재 날짜와 날짜 범위 설정
$today = date('Y-m-d');
$weekAgo = date('Y-m-d', strtotime('-7 days'));

// 실제 데이터를 가져오는 함수 호출
$salesData = getSalesStatistics($db, [
    'start_date' => $weekAgo,
    'end_date' => $today
]);

// 오늘 판매 데이터 계산
$todayData = getSalesStatistics($db, [
    'start_date' => $today,
    'end_date' => $today
]);

// 판매 통계 정보
$salesStats = [
    'today_sales_count' => isset($todayData['total_tickets']) ? $todayData['total_tickets'] : 0,
    'today_sales_amount' => isset($todayData['total_sales']) ? $todayData['total_sales'] : 0,
    'week_sales_count' => isset($salesData['total_tickets']) ? $salesData['total_tickets'] : 0,
    'total_sales_amount' => isset($salesData['total_sales']) ? $salesData['total_sales'] : 0
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

// 일별 판매 추이 데이터 준비
$dailyTrend = isset($salesData['daily_trend']) ? $salesData['daily_trend'] : [];
$trendLabels = [];
$trendSales = [];
$trendAmount = [];

// 최근 7일 데이터 준비
for ($i = 0; $i < 7; $i++) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $trendLabels[] = date('n월 j일', strtotime($date));
    
    // 해당 날짜의 데이터 찾기
    $found = false;
    foreach ($dailyTrend as $day) {
        if (isset($day['sale_date']) && $day['sale_date'] == $date) {
            $trendSales[] = isset($day['total_tickets']) ? $day['total_tickets'] : 0;
            $trendAmount[] = isset($day['daily_sales']) ? $day['daily_sales'] / 10000 : 0; // 만 루피 단위로 변환
            $found = true;
            break;
        }
    }
    
    // 데이터가 없으면 0으로 설정
    if (!$found) {
        $trendSales[] = 0;
        $trendAmount[] = 0;
    }
}

// 날짜 순서대로 정렬
$trendLabels = array_reverse($trendLabels);
$trendSales = array_reverse($trendSales);
$trendAmount = array_reverse($trendAmount);
?>
<div class="row">
    <!-- 판매 통계 요약 -->
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3><?php echo number_format($salesStats['today_sales_count']); ?></h3>
                <p>오늘 판매량</p>
            </div>
            <div class="icon">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <a href="<?php echo SERVER_URL; ?>/dashboard/sales/status.php" class="small-box-footer">자세히 보기 <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3><?php echo formatCurrency($salesStats['today_sales_amount']); ?></h3>
                <p>오늘 매출</p>
            </div>
            <div class="icon">
                <i class="fas fa-dollar-sign"></i>
            </div>
            <a href="<?php echo SERVER_URL; ?>/dashboard/sales/status.php?view=revenue" class="small-box-footer">자세히 보기 <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3><?php echo number_format($salesStats['week_sales_count']); ?></h3>
                <p>주간 판매량</p>
            </div>
            <div class="icon">
                <i class="fas fa-chart-line"></i>
            </div>
            <a href="<?php echo SERVER_URL; ?>/dashboard/sales/history.php?period=week" class="small-box-footer">자세히 보기 <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3><?php echo formatCurrency($salesStats['total_sales_amount']); ?></h3>
                <p>누적 매출</p>
            </div>
            <div class="icon">
                <i class="fas fa-chart-bar"></i>
            </div>
            <a href="<?php echo SERVER_URL; ?>/dashboard/sales/history.php?view=total" class="small-box-footer">자세히 보기 <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
</div>

<div class="row">
    <!-- 판매 추이 그래프 -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">판매 추이</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="chart">
                    <canvas id="salesChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 지역별 판매 현황 -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">지역별 판매 현황</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>지역</th>
                            <th>판매량</th>
                            <th>점유율</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($regionSales as $region): ?>
                        <tr>
                            <td><?php echo $region['region']; ?></td>
                            <td><?php echo number_format($region['count']); ?></td>
                            <td>
                                <div class="progress progress-xs">
                                    <div class="progress-bar bg-primary" style="width: <?php echo $region['percentage']; ?>%"></div>
                                </div>
                                <span class="badge bg-primary"><?php echo $region['percentage']; ?>%</span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- 최근 판매 내역 테이블 -->
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">최근 판매 내역</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>판매점</th>
                                <th>복권 종류</th>
                                <th>수량</th>
                                <th>금액</th>
                                <th>판매 일시</th>
                                <th>상태</th>
                                <th>액션</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentSales as $sale): ?>
                            <tr>
                                <td><?php echo $sale['id']; ?></td>
                                <td><?php echo $sale['store']; ?></td>
                                <td><?php echo $sale['type']; ?></td>
                                <td><?php echo number_format($sale['quantity']); ?></td>
                                <td><?php echo formatCurrency($sale['amount']); ?></td>
                                <td><?php echo $sale['date']; ?></td>
                                <td>
                                    <?php if ($sale['status'] == 'complete'): ?>
                                    <span class="badge bg-success">완료</span>
                                    <?php elseif ($sale['status'] == 'processing'): ?>
                                    <span class="badge bg-warning">처리 중</span>
                                    <?php else: ?>
                                    <span class="badge bg-danger">취소됨</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="#" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer">
                <a href="<?php echo SERVER_URL; ?>/dashboard/sales/history.php" class="btn btn-sm btn-primary float-right">
                    모든 판매 내역 보기
                </a>
            </div>
        </div>
    </div>
</div>

<script>
// 판매 추이 차트
$(function() {
    var salesChartCanvas = document.getElementById('salesChart').getContext('2d');
    
    var salesChartData = {
        labels: <?php echo json_encode($trendLabels); ?>,
        datasets: [
            {
                label: '판매량',
                backgroundColor: 'rgba(60,141,188,0.9)',
                borderColor: 'rgba(60,141,188,0.8)',
                pointRadius: 3,
                pointColor: '#3b8bba',
                pointStrokeColor: 'rgba(60,141,188,1)',
                pointHighlightFill: '#fff',
                pointHighlightStroke: 'rgba(60,141,188,1)',
                data: <?php echo json_encode($trendSales); ?>
            },
            {
                label: '매출(만 루피)',
                backgroundColor: 'rgba(210, 214, 222, 0.9)',
                borderColor: 'rgba(210, 214, 222, 0.8)',
                pointRadius: 3,
                pointColor: 'rgba(210, 214, 222, 1)',
                pointStrokeColor: '#c1c7d1',
                pointHighlightFill: '#fff',
                pointHighlightStroke: 'rgba(220,220,220,1)',
                data: <?php echo json_encode($trendAmount); ?>
            }
        ]
    };

    var salesChartOptions = {
        maintainAspectRatio: false,
        responsive: true,
        legend: {
            display: true
        },
        scales: {
            xAxes: [{
                gridLines: {
                    display: false
                }
            }],
            yAxes: [{
                gridLines: {
                    display: false
                }
            }]
        }
    };

    var salesChart = new Chart(salesChartCanvas, {
        type: 'line',
        data: salesChartData,
        options: salesChartOptions
    });
});
</script>