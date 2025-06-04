<?php
/**
 * 재무 관리 - 재무 요약 대시보드
 * 
 * 이 페이지는 재무 현황 요약, 주요 지표, 최근 거래 내역 등을 보여주는 대시보드입니다.
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

// 2. 최근 거래 내역 (전체)
$recentTransactionsSql = "SELECT ft.id, ft.fund_id, ft.transaction_type, ft.amount, 
                         ft.transaction_date, ft.status, ft.description,
                         f.fund_name, f.fund_code, f.fund_type
                         FROM fund_transactions ft
                         JOIN funds f ON ft.fund_id = f.id
                         ORDER BY ft.transaction_date DESC
                         LIMIT 10";

$recentTransactionsResult = $db->query($recentTransactionsSql);
$recentTransactions = [];

while ($row = $recentTransactionsResult->fetch(PDO::FETCH_ASSOC)) {
    $recentTransactions[] = $row;
}

// 3. 월별 수입/지출 추이 (최근 12개월)
$monthlyFinanceSql = "SELECT 
                      DATE_FORMAT(transaction_date, '%Y-%m') as month,
                      SUM(CASE WHEN transaction_type = 'income' THEN amount ELSE 0 END) as income,
                      SUM(CASE WHEN transaction_type = 'expense' THEN amount ELSE 0 END) as expense
                      FROM financial_transactions
                      WHERE transaction_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                      AND status = 'completed'
                      GROUP BY DATE_FORMAT(transaction_date, '%Y-%m')
                      ORDER BY month";

$monthlyFinanceResult = $db->query($monthlyFinanceSql);
$monthlyFinance = [];
$months = [];
$incomeData = [];
$expenseData = [];

while ($row = $monthlyFinanceResult->fetch(PDO::FETCH_ASSOC)) {
    $monthlyFinance[] = $row;
    $months[] = date('M Y', strtotime($row['month'] . '-01'));
    $incomeData[] = $row['income'];
    $expenseData[] = $row['expense'];
}

// 4. 기금 유형별 잔액 분포
$fundTypeBalanceSql = "SELECT fund_type, SUM(current_balance) as balance
                      FROM funds
                      WHERE status = 'active'
                      GROUP BY fund_type
                      ORDER BY balance DESC";

$fundTypeBalanceResult = $db->query($fundTypeBalanceSql);
$fundTypeBalance = [];
$fundTypeLabels = [];
$fundTypeBalanceData = [];

while ($row = $fundTypeBalanceResult->fetch(PDO::FETCH_ASSOC)) {
    $fundTypeBalance[] = $row;
    $fundTypeLabels[] = getFundTypeLabel($row['fund_type']);
    $fundTypeBalanceData[] = $row['balance'];
}

// 5. 대기 중인 승인 건수
$pendingApprovalsSql = "SELECT COUNT(*) as count FROM fund_transactions 
                       WHERE status = 'pending'";
$pendingApprovalsResult = $db->query($pendingApprovalsSql);
$resultRow = $pendingApprovalsResult->fetch(PDO::FETCH_ASSOC);
$pendingApprovals = $resultRow['count'] ?? 0;

// 6. 이번 달 정산 진행 상황
$settlementsSql = "SELECT 
                  COUNT(*) as total_count,
                  SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_count,
                  SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
                  SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing_count,
                  SUM(total_amount) as total_amount,
                  SUM(CASE WHEN status = 'completed' THEN total_amount ELSE 0 END) as completed_amount
                  FROM settlements
                  WHERE YEAR(start_date) = YEAR(CURDATE()) AND MONTH(start_date) = MONTH(CURDATE())";

$settlementsResult = $db->query($settlementsSql);
$settlements = $settlementsResult->fetch(PDO::FETCH_ASSOC);

// 결과가 없거나 키가 없는 경우를 대비한 기본값 설정
$settlements['total_count'] = $settlements['total_count'] ?? 0;
$settlements['completed_count'] = $settlements['completed_count'] ?? 0;
$settlements['pending_count'] = $settlements['pending_count'] ?? 0;
$settlements['processing_count'] = $settlements['processing_count'] ?? 0;
$settlements['total_amount'] = $settlements['total_amount'] ?? 0;
$settlements['completed_amount'] = $settlements['completed_amount'] ?? 0;

// 페이지 제목 설정
$pageTitle = "재무 요약 대시보드";
$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir = dirname($_SERVER['PHP_SELF']);
$parts = explode('/', $currentDir);
$currentSection = end($parts);

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
                    <li class="breadcrumb-item active">재무 요약</li>
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
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3><?php echo number_format($totalBalance, 0); ?> <small>NPR</small></h3>
                        <p>전체 기금 잔액</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-wallet"></i>
                    </div>
                    <a href="funds.php" class="small-box-footer">
                        기금 관리 <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3><?php echo number_format($totalAllocation, 0); ?> <small>NPR</small></h3>
                        <p>전체 기금 할당액</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-hand-holding-usd"></i>
                    </div>
                    <a href="funds.php" class="small-box-footer">
                        기금 관리 <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3><?php echo $pendingApprovals; ?></h3>
                        <p>승인 대기 거래</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-hourglass-half"></i>
                    </div>
                    <a href="funds.php" class="small-box-footer">
                        거래 승인 <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3><?php echo ($settlements['pending_count'] ?? 0) + ($settlements['processing_count'] ?? 0); ?></h3>
                        <p>진행 중인 정산</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-calculator"></i>
                    </div>
                    <a href="settlements.php" class="small-box-footer">
                        정산 관리 <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
        </div>
            
            <!-- 차트 영역 -->
            <div class="row">
                <div class="col-md-8">
                    <!-- 월별 수입/지출 추이 차트 -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-chart-line mr-1"></i>
                                월별 수입/지출 추이 (최근 12개월)
                            </h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <button type="button" class="btn btn-tool" data-card-widget="remove">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="chart">
                                <canvas id="monthlyFinanceChart" style="min-height: 300px; height: 300px; max-height: 300px; max-width: 100%;"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 최근 거래 내역 -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-exchange-alt mr-1"></i>
                                최근 거래 내역
                            </h3>
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
                                            <th>기금</th>
                                            <th>유형</th>
                                            <th>금액</th>
                                            <th>날짜</th>
                                            <th>상태</th>
                                            <th>액션</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentTransactions as $transaction): ?>
                                        <?php $statusBadge = getStatusBadge($transaction['status']); ?>
                                        <tr>
                                            <td>
                                                <span data-toggle="tooltip" title="<?php echo htmlspecialchars($transaction['fund_name']); ?>">
                                                    <?php echo htmlspecialchars($transaction['fund_code']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo getTransactionTypeLabel($transaction['transaction_type']); ?></td>
                                            <td><?php echo number_format($transaction['amount'], 2); ?> NPR</td>
                                            <td><?php echo date('Y-m-d', strtotime($transaction['transaction_date'])); ?></td>
                                            <td>
                                                <span class="badge badge-<?php echo $statusBadge['color']; ?>">
                                                    <?php echo $statusBadge['label']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="fund-transaction-details.php?id=<?php echo $transaction['id']; ?>" class="btn btn-xs btn-info">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                        
                                        <?php if (empty($recentTransactions)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center">최근 거래 내역이 없습니다.</td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer text-center">
                            <a href="funds.php" class="btn btn-sm btn-primary">
                                모든 거래 보기
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <!-- 기금 유형별 잔액 분포 차트 -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-chart-pie mr-1"></i>
                                기금 유형별 잔액 분포
                            </h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="chart">
                                <canvas id="fundTypeChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 이번 달 정산 진행 상황 -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-calculator mr-1"></i>
                                이번 달 정산 진행 상황
                            </h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="info-box bg-gradient-info">
                                <span class="info-box-icon"><i class="fas fa-money-check-alt"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">전체 정산 금액</span>
                                    <span class="info-box-number"><?php echo number_format($settlements['total_amount'] ?? 0, 2); ?> NPR</span>
                                </div>
                            </div>
                            
                            <div class="info-box bg-gradient-success">
                                <span class="info-box-icon"><i class="fas fa-check-circle"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">완료된 정산</span>
                                    <span class="info-box-number"><?php echo $settlements['completed_count'] ?? 0; ?> / <?php echo $settlements['total_count'] ?? 0; ?></span>
                                    <div class="progress">
                                        <div class="progress-bar" style="width: <?php echo ($settlements['total_count'] > 0) ? ($settlements['completed_count'] / $settlements['total_count'] * 100) : 0; ?>%"></div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="info-box bg-gradient-warning">
                                <span class="info-box-icon"><i class="fas fa-hourglass-half"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">진행 중인 정산</span>
                                    <span class="info-box-number"><?php echo $settlements['processing_count'] ?? 0; ?></span>
                                </div>
                            </div>
                            
                            <div class="info-box bg-gradient-danger">
                                <span class="info-box-icon"><i class="fas fa-clock"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">대기 중인 정산</span>
                                    <span class="info-box-number"><?php echo $settlements['pending_count'] ?? 0; ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer text-center">
                            <a href="settlements.php" class="btn btn-sm btn-primary">
                                정산 관리 페이지로 이동
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js 스크립트 -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('재무 요약 대시보드 페이지 로드됨');
    
    // 월별 수입/지출 추이 차트
    var monthlyCtx = document.getElementById('monthlyFinanceChart').getContext('2d');
    var monthlyFinanceChart = new Chart(monthlyCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($months); ?>,
            datasets: [
                {
                    label: '수입',
                    backgroundColor: 'rgba(60,141,188,0.2)',
                    borderColor: 'rgba(60,141,188,1)',
                    pointRadius: 3,
                    pointColor: '#3b8bba',
                    pointStrokeColor: 'rgba(60,141,188,1)',
                    pointHighlightFill: '#fff',
                    pointHighlightStroke: 'rgba(60,141,188,1)',
                    data: <?php echo json_encode($incomeData); ?>
                },
                {
                    label: '지출',
                    backgroundColor: 'rgba(210, 214, 222, 0.2)',
                    borderColor: 'rgba(210, 214, 222, 1)',
                    pointRadius: 3,
                    pointColor: 'rgba(210, 214, 222, 1)',
                    pointStrokeColor: '#c1c7d1',
                    pointHighlightFill: '#fff',
                    pointHighlightStroke: 'rgba(220,220,220,1)',
                    data: <?php echo json_encode($expenseData); ?>
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    grid: {
                        display: false
                    }
                },
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value.toLocaleString() + ' NPR';
                        }
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + context.raw.toLocaleString() + ' NPR';
                        }
                    }
                }
            }
        }
    });
    
    // 기금 유형별 잔액 분포 차트
    var pieCtx = document.getElementById('fundTypeChart').getContext('2d');
    var fundTypeChart = new Chart(pieCtx, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($fundTypeLabels); ?>,
            datasets: [
                {
                    data: <?php echo json_encode($fundTypeBalanceData); ?>,
                    backgroundColor: [
                        '#f56954', '#00a65a', '#f39c12', '#00c0ef', '#3c8dbc', '#d2d6de', '#6610f2', '#fd7e14'
                    ]
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            var label = context.label || '';
                            var value = context.raw || 0;
                            var total = context.dataset.data.reduce((a, b) => a + b, 0);
                            var percentage = Math.round((value / total) * 100);
                            
                            return label + ': ' + value.toLocaleString() + ' NPR (' + percentage + '%)';
                        }
                    }
                }
            }
        }
    });
    
    // 툴팁 초기화
    $('[data-toggle="tooltip"]').tooltip();
});
</script>

<?php
// 템플릿 푸터 포함
include_once TEMPLATES_PATH . '/dashboard_footer.php';
?>