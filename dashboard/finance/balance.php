<?php
/**
 * 재무 관리 - 자금 잔액 현황
 * 
 * 이 페이지는 현재 기금 잔액 현황, 계정별 잔액 등을 보여주는 페이지입니다.
 */

// 설정 및 공통 함수
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';
require_once '../../includes/db.php';

// 데이터베이스 연결
$db = get_db_connection();

// 1. 전체 기금 잔액 요약
$fundsSql = "SELECT fund_type, 
             COUNT(*) as count, 
             SUM(current_balance) as total_balance,
             SUM(total_allocation) as total_allocation
             FROM funds
             WHERE status = 'active'
             GROUP BY fund_type
             ORDER BY fund_type";

$fundsResult = $db->query($fundsSql);
$fundSummary = [];
$totalBalance = 0;
$totalAllocation = 0;

while ($row = $fundsResult->fetch(PDO::FETCH_ASSOC)) {
    $fundSummary[] = $row;
    $totalBalance += $row['total_balance'];
    $totalAllocation += $row['total_allocation'];
}

// 2. 기금별 상세 정보
$fundsDetailSql = "SELECT id, fund_code, fund_name, fund_type, 
                   current_balance, total_allocation, 
                   (current_balance / total_allocation * 100) as usage_percentage
                   FROM funds
                   WHERE status = 'active'
                   ORDER BY fund_type, fund_name";

$fundsDetailResult = $db->query($fundsDetailSql);
$fundsDetail = [];

while ($row = $fundsDetailResult->fetch(PDO::FETCH_ASSOC)) {
    $fundsDetail[] = $row;
}

// 3. 월별 잔액 변동 추이 (최근 6개월)
$monthlyCashFlowSql = "SELECT 
                      DATE_FORMAT(transaction_date, '%Y-%m') as month,
                      SUM(CASE WHEN transaction_type = 'income' THEN amount ELSE 0 END) as income,
                      SUM(CASE WHEN transaction_type = 'expense' THEN amount ELSE 0 END) as expense,
                      SUM(CASE WHEN transaction_type = 'income' THEN amount 
                          WHEN transaction_type = 'expense' THEN -amount
                          ELSE 0 END) as net_change
                      FROM fund_transactions
                      WHERE transaction_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                      AND status = 'completed'
                      GROUP BY DATE_FORMAT(transaction_date, '%Y-%m')
                      ORDER BY month";

$monthlyCashFlowResult = $db->query($monthlyCashFlowSql);
$monthlyCashFlow = [];
$months = [];
$incomeData = [];
$expenseData = [];
$netChangeData = [];

while ($row = $monthlyCashFlowResult->fetch(PDO::FETCH_ASSOC)) {
    $monthlyCashFlow[] = $row;
    $months[] = date('M Y', strtotime($row['month'] . '-01'));
    $incomeData[] = $row['income'];
    $expenseData[] = $row['expense'];
    $netChangeData[] = $row['net_change'];
}

// 페이지 제목 설정
$pageTitle = "자금 잔액 현황";
$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir = dirname($_SERVER['PHP_SELF']);
$parts = explode('/', $currentDir);
$currentSection = end($parts);

/**
 * 기금 유형 코드를 사람이 읽기 쉬운 레이블로 변환합니다.
 * 
 * @param string $fundType 기금 유형 코드
 * @return string 사람이 읽기 쉬운 레이블
 */
function getFundTypeLabel($fundType) {
    $labels = [
        'general' => '일반 기금',
        'reserve' => '예비 기금',
        'operational' => '운영 기금',
        'development' => '개발 기금',
        'emergency' => '비상 기금',
        'special' => '특별 기금',
        'grant' => '보조금',
        'donation' => '기부금',
        'investment' => '투자 기금',
        'other' => '기타 기금'
    ];
    
    return $labels[$fundType] ?? '알 수 없음';
}

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
                    <li class="breadcrumb-item">재무 관리</li>
                    <li class="breadcrumb-item active">자금 잔액 현황</li>
                </ol>
            </div>
        </div>
    </div>
</div>
<!-- /.content-header -->

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <!-- 요약 정보 카드 -->
        <div class="row">
            <div class="col-lg-4 col-md-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3><?php echo number_format($totalBalance, 0); ?> <small>NPR</small></h3>
                        <p>전체 기금 잔액</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-wallet"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3><?php echo number_format($totalAllocation, 0); ?> <small>NPR</small></h3>
                        <p>전체 기금 할당액</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-hand-holding-usd"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3><?php echo $totalAllocation > 0 ? number_format($totalBalance / $totalAllocation * 100, 1) : '0'; ?><small>%</small></h3>
                        <p>기금 사용률</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-percentage"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 기금 유형별 잔액 -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-money-bill mr-1"></i>
                            기금 유형별 잔액 현황
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>기금 유형</th>
                                        <th>기금 수</th>
                                        <th class="text-right">잔액 합계</th>
                                        <th class="text-right">할당액 합계</th>
                                        <th class="text-right">사용률</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($fundSummary as $fund): ?>
                                        <tr>
                                            <td><?php echo getFundTypeLabel($fund['fund_type']); ?></td>
                                            <td><?php echo $fund['count']; ?></td>
                                            <td class="text-right"><?php echo number_format($fund['total_balance'], 0); ?> NPR</td>
                                            <td class="text-right"><?php echo number_format($fund['total_allocation'], 0); ?> NPR</td>
                                            <td class="text-right">
                                                <?php 
                                                $usagePercent = ($fund['total_allocation'] > 0) ? 
                                                                ($fund['total_balance'] / $fund['total_allocation'] * 100) : 0;
                                                echo number_format($usagePercent, 1) . '%'; 
                                                ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="font-weight-bold">
                                        <td>합계</td>
                                        <td><?php echo array_sum(array_column($fundSummary, 'count')); ?></td>
                                        <td class="text-right"><?php echo number_format($totalBalance, 0); ?> NPR</td>
                                        <td class="text-right"><?php echo number_format($totalAllocation, 0); ?> NPR</td>
                                        <td class="text-right">
                                            <?php 
                                            $totalUsagePercent = ($totalAllocation > 0) ? 
                                                                ($totalBalance / $totalAllocation * 100) : 0;
                                            echo number_format($totalUsagePercent, 1) . '%'; 
                                            ?>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 월별 잔액 변동 추이 차트 -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-chart-line mr-1"></i>
                            월별 자금 흐름 (최근 6개월)
                        </h3>
                    </div>
                    <div class="card-body">
                        <canvas id="cashFlowChart" height="300"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 기금별 상세 정보 테이블 -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-list mr-1"></i>
                            기금별 상세 정보
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" id="fundsTable">
                                <thead>
                                    <tr>
                                        <th>기금 코드</th>
                                        <th>기금명</th>
                                        <th>유형</th>
                                        <th class="text-right">현재 잔액</th>
                                        <th class="text-right">할당액</th>
                                        <th class="text-right">사용률</th>
                                        <th>작업</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($fundsDetail as $fund): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($fund['fund_code']); ?></td>
                                            <td><?php echo htmlspecialchars($fund['fund_name']); ?></td>
                                            <td><?php echo getFundTypeLabel($fund['fund_type']); ?></td>
                                            <td class="text-right"><?php echo number_format($fund['current_balance'], 0); ?> NPR</td>
                                            <td class="text-right"><?php echo number_format($fund['total_allocation'], 0); ?> NPR</td>
                                            <td class="text-right">
                                                <div class="progress progress-sm">
                                                    <div class="progress-bar bg-green" style="width: <?php echo isset($fund['usage_percentage']) ? min(100, max(0, $fund['usage_percentage'])) : 0; ?>%"></div>
                                                </div>
                                                <small>
                                                    <?php echo isset($fund['usage_percentage']) ? number_format($fund['usage_percentage'], 1) : '0'; ?>%
                                                </small>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="fund-details.php?id=<?php echo $fund['id']; ?>" class="btn btn-sm btn-info">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="fund-transaction-add.php?fund_id=<?php echo $fund['id']; ?>" class="btn btn-sm btn-success">
                                                        <i class="fas fa-plus"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- /.content -->

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@2.9.4/dist/Chart.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 월별 자금 흐름 차트
    var cashFlowCtx = document.getElementById('cashFlowChart').getContext('2d');
    var cashFlowChart = new Chart(cashFlowCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($months); ?>,
            datasets: [
                {
                    label: '수입',
                    backgroundColor: 'rgba(40, 167, 69, 0.7)',
                    borderColor: 'rgba(40, 167, 69, 1)',
                    borderWidth: 1,
                    data: <?php echo json_encode($incomeData); ?>
                },
                {
                    label: '지출',
                    backgroundColor: 'rgba(220, 53, 69, 0.7)',
                    borderColor: 'rgba(220, 53, 69, 1)',
                    borderWidth: 1,
                    data: <?php echo json_encode($expenseData); ?>
                },
                {
                    label: '순 변동',
                    type: 'line',
                    fill: false,
                    backgroundColor: 'rgba(0, 123, 255, 0.7)',
                    borderColor: 'rgba(0, 123, 255, 1)',
                    borderWidth: 2,
                    pointRadius: 5,
                    pointBackgroundColor: 'rgba(0, 123, 255, 1)',
                    data: <?php echo json_encode($netChangeData); ?>
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                xAxes: [{
                    gridLines: {
                        display: false
                    }
                }],
                yAxes: [{
                    ticks: {
                        beginAtZero: true,
                        callback: function(value) {
                            return value.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",") + ' NPR';
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
                        label += tooltipItem.yLabel.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",") + ' NPR';
                        return label;
                    }
                }
            }
        }
    });
    
    // 데이터테이블 초기화
    $(document).ready(function() {
        $('#fundsTable').DataTable({
            "pageLength": 10,
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.10.22/i18n/Korean.json"
            }
        });
    });
});
</script>

<?php
// 템플릿 푸터 포함
include_once TEMPLATES_PATH . '/dashboard_footer.php';
?>
