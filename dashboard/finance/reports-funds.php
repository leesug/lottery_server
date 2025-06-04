<?php
/**
 * 재무 관리 - 기금 상태 보고서 페이지
 * 
 * 이 페이지는 기금별 상태, 할당 및 사용 현황을 분석하고 보고서를 제공합니다.
 */

// 세션 시작 및 필수 파일 포함
session_start();
require_once '../../includes/auth.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// 인증 확인
checkAuth();

// 필요한 권한 확인
$requiredPermissions = ['finance_reports'];
checkPermissions($requiredPermissions);

// 데이터베이스 연결
$conn = getDBConnection();

// 필터 파라미터
$fundType = isset($_GET['fund_type']) ? sanitizeInput($_GET['fund_type']) : '';
$startDate = isset($_GET['start_date']) ? sanitizeInput($_GET['start_date']) : date('Y-01-01');
$endDate = isset($_GET['end_date']) ? sanitizeInput($_GET['end_date']) : date('Y-m-d');

// 날짜 범위가 유효한지 확인
if (strtotime($startDate) > strtotime($endDate)) {
    $temp = $startDate;
    $startDate = $endDate;
    $endDate = $temp;
}

// 기금 유형 목록 조회
$fundTypesSql = "SELECT DISTINCT fund_type FROM funds ORDER BY fund_type";
$fundTypesResult = $conn->query($fundTypesSql);
$fundTypes = [];

while ($row = $fundTypesResult->fetch_assoc()) {
    $fundTypes[] = $row['fund_type'];
}

// 기금 유형 한글명 매핑
$fundTypeLabels = [
    'prize' => '당첨금 기금',
    'charity' => '자선 기금',
    'development' => '개발 기금',
    'operational' => '운영 기금',
    'reserve' => '예비 기금',
    'other' => '기타 기금'
];

// 기금 상태 데이터 조회
$fundStatusSql = "SELECT 
                 f.id,
                 f.fund_name,
                 f.fund_code,
                 f.fund_type,
                 f.description,
                 f.total_allocation,
                 f.current_balance,
                 f.allocation_percentage,
                 f.status,
                 (SELECT SUM(amount) FROM fund_transactions 
                  WHERE fund_id = f.id 
                  AND transaction_type = 'allocation'
                  AND transaction_date BETWEEN ? AND ?) AS period_allocations,
                 (SELECT SUM(amount) FROM fund_transactions 
                  WHERE fund_id = f.id 
                  AND transaction_type = 'withdrawal'
                  AND transaction_date BETWEEN ? AND ?) AS period_withdrawals,
                 (SELECT COUNT(*) FROM fund_transactions 
                  WHERE fund_id = f.id 
                  AND transaction_date BETWEEN ? AND ?) AS transaction_count
                 FROM funds f
                 WHERE 1=1";

$params = [$startDate, $endDate, $startDate, $endDate, $startDate, $endDate];
$paramTypes = "ssssss";

// 기금 유형 필터 적용
if (!empty($fundType)) {
    $fundStatusSql .= " AND f.fund_type = ?";
    $params[] = $fundType;
    $paramTypes .= "s";
}

$fundStatusSql .= " ORDER BY f.fund_type, f.fund_name";

// 쿼리 실행
$fundStatusStmt = $conn->prepare($fundStatusSql);
$fundStatusStmt->bind_param($paramTypes, ...$params);
$fundStatusStmt->execute();
$fundStatusResult = $fundStatusStmt->get_result();

// 데이터 포맷팅
$fundStatusData = [];
$totalAllocations = 0;
$totalWithdrawals = 0;
$totalBalance = 0;
$fundTypeAllocation = [];
$fundTypeWithdrawal = [];
$fundTypeBalance = [];

while ($row = $fundStatusResult->fetch_assoc()) {
    // 기간 할당 및 인출이 NULL인 경우 0으로 설정
    $row['period_allocations'] = $row['period_allocations'] ? $row['period_allocations'] : 0;
    $row['period_withdrawals'] = $row['period_withdrawals'] ? $row['period_withdrawals'] : 0;
    
    // 순 변화 계산
    $row['net_change'] = $row['period_allocations'] - $row['period_withdrawals'];
    
    // 이용률 계산
    $row['utilization_rate'] = $row['total_allocation'] > 0 ? 
        (($row['total_allocation'] - $row['current_balance']) / $row['total_allocation']) * 100 : 0;
    
    $fundStatusData[] = $row;
    
    // 합계 계산
    $totalAllocations += $row['period_allocations'];
    $totalWithdrawals += $row['period_withdrawals'];
    $totalBalance += $row['current_balance'];
    
    // 기금 유형별 합계 계산
    $fundType = $row['fund_type'];
    if (!isset($fundTypeAllocation[$fundType])) {
        $fundTypeAllocation[$fundType] = 0;
        $fundTypeWithdrawal[$fundType] = 0;
        $fundTypeBalance[$fundType] = 0;
    }
    
    $fundTypeAllocation[$fundType] += $row['period_allocations'];
    $fundTypeWithdrawal[$fundType] += $row['period_withdrawals'];
    $fundTypeBalance[$fundType] += $row['current_balance'];
}

// 기금 유형별 데이터 차트용 포맷팅
$fundTypeLabelsChart = [];
$fundTypeAllocationValues = [];
$fundTypeWithdrawalValues = [];
$fundTypeBalanceValues = [];

foreach ($fundTypeAllocation as $type => $value) {
    $label = isset($fundTypeLabels[$type]) ? $fundTypeLabels[$type] : ucfirst($type);
    $fundTypeLabelsChart[] = $label;
    $fundTypeAllocationValues[] = $value;
    $fundTypeWithdrawalValues[] = $fundTypeWithdrawal[$type];
    $fundTypeBalanceValues[] = $fundTypeBalance[$type];
}

// 기금 거래 추세 데이터 조회
$transactionTrendsSql = "SELECT 
                        DATE_FORMAT(transaction_date, '%Y-%m') AS month,
                        SUM(CASE WHEN transaction_type = 'allocation' THEN amount ELSE 0 END) AS allocations,
                        SUM(CASE WHEN transaction_type = 'withdrawal' THEN amount ELSE 0 END) AS withdrawals,
                        SUM(CASE WHEN transaction_type = 'allocation' THEN amount 
                                WHEN transaction_type = 'withdrawal' THEN -amount 
                                ELSE 0 END) AS net_change,
                        COUNT(*) AS transaction_count
                        FROM fund_transactions
                        WHERE transaction_date BETWEEN ? AND ?
                        AND status = 'completed'";

$trendParams = [$startDate, $endDate];
$trendParamTypes = "ss";

// 기금 유형 필터 적용
if (!empty($fundType)) {
    $transactionTrendsSql .= " AND fund_id IN (SELECT id FROM funds WHERE fund_type = ?)";
    $trendParams[] = $fundType;
    $trendParamTypes .= "s";
}

$transactionTrendsSql .= " GROUP BY month
                          ORDER BY month";

// 쿼리 실행
$transactionTrendsStmt = $conn->prepare($transactionTrendsSql);
$transactionTrendsStmt->bind_param($trendParamTypes, ...$trendParams);
$transactionTrendsStmt->execute();
$transactionTrendsResult = $transactionTrendsStmt->get_result();

// 데이터 포맷팅
$transactionTrendsData = [];
$trendLabels = [];
$allocationTrends = [];
$withdrawalTrends = [];
$netChangeTrends = [];

while ($row = $transactionTrendsResult->fetch_assoc()) {
    $transactionTrendsData[] = $row;
    
    // 차트 데이터 구성
    $trendLabels[] = $row['month'];
    $allocationTrends[] = $row['allocations'];
    $withdrawalTrends[] = $row['withdrawals'];
    $netChangeTrends[] = $row['net_change'];
}

// 상위 기금 거래 조회
$topTransactionsSql = "SELECT 
                      ft.id,
                      ft.transaction_type,
                      ft.amount,
                      ft.transaction_date,
                      ft.description,
                      ft.reference_type,
                      ft.reference_id,
                      ft.status,
                      f.fund_name,
                      f.fund_code,
                      f.fund_type
                      FROM fund_transactions ft
                      JOIN funds f ON ft.fund_id = f.id
                      WHERE ft.transaction_date BETWEEN ? AND ?
                      AND ft.status = 'completed'";

$ttParams = [$startDate, $endDate];
$ttParamTypes = "ss";

// 기금 유형 필터 적용
if (!empty($fundType)) {
    $topTransactionsSql .= " AND f.fund_type = ?";
    $ttParams[] = $fundType;
    $ttParamTypes .= "s";
}

$topTransactionsSql .= " ORDER BY ft.amount DESC
                        LIMIT 10";

// 쿼리 실행
$topTransactionsStmt = $conn->prepare($topTransactionsSql);
$topTransactionsStmt->bind_param($ttParamTypes, ...$ttParams);
$topTransactionsStmt->execute();
$topTransactionsResult = $topTransactionsStmt->get_result();

// 데이터 포맷팅
$topTransactionsData = [];

// 거래 유형 한글명 매핑
$transactionTypeLabels = [
    'allocation' => '할당',
    'withdrawal' => '인출',
    'transfer' => '이체',
    'adjustment' => '조정'
];

while ($row = $topTransactionsResult->fetch_assoc()) {
    // 거래 유형 한글명 설정
    $ttLabel = isset($transactionTypeLabels[$row['transaction_type']]) ?
        $transactionTypeLabels[$row['transaction_type']] : ucfirst($row['transaction_type']);
    
    $row['transaction_type_label'] = $ttLabel;
    $topTransactionsData[] = $row;
}

// 페이지 제목 설정
$pageTitle = "기금 상태 보고서";
$currentSection = "finance";
$currentPage = "reports";

// 헤더 및 네비게이션 포함
include './header.php';
include './navbar.php';

// 숫자 포맷 함수
function formatNumber($number, $decimals = 2) {
    return number_format($number, $decimals);
}

// 기금 유형 한글명 반환 함수
function getFundTypeLabel($type) {
    global $fundTypeLabels;
    return isset($fundTypeLabels[$type]) ? $fundTypeLabels[$type] : ucfirst($type);
}

// 기금 상태 라벨 및 클래스 반환 함수
function getFundStatusInfo($status) {
    $labels = [
        'active' => ['label' => '활성', 'class' => 'success'],
        'inactive' => ['label' => '비활성', 'class' => 'secondary'],
        'depleted' => ['label' => '고갈', 'class' => 'danger']
    ];
    
    return isset($labels[$status]) ? $labels[$status] : ['label' => $status, 'class' => 'info'];
}
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0"><?php echo $pageTitle; ?></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="../../dashboard/">대시보드</a></li>
                        <li class="breadcrumb-item"><a href="../">재무 관리</a></li>
                        <li class="breadcrumb-item"><a href="./reports.php">보고서</a></li>
                        <li class="breadcrumb-item active">기금 상태 보고서</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">
            <!-- 필터 카드 -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">보고서 필터</h3>
                </div>
                <div class="card-body">
                    <form method="get" action="" id="reportFilterForm">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="fund_type">기금 유형</label>
                                    <select name="fund_type" id="fund_type" class="form-control">
                                        <option value="">모든 기금</option>
                                        <?php foreach ($fundTypes as $type): ?>
                                            <option value="<?php echo $type; ?>" <?php if ($fundType === $type) echo 'selected'; ?>>
                                                <?php echo getFundTypeLabel($type); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="start_date">시작일</label>
                                    <input type="date" name="start_date" id="start_date" class="form-control" value="<?php echo $startDate; ?>">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="end_date">종료일</label>
                                    <input type="date" name="end_date" id="end_date" class="form-control" value="<?php echo $endDate; ?>">
                                </div>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary btn-block">적용</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- 요약 정보 카드 -->
            <div class="row">
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3><?php echo formatNumber($totalAllocations); ?></h3>
                            <p>기간 내 총 할당</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-arrow-down"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-warning">
                        <div class="inner">
                            <h3><?php echo formatNumber($totalWithdrawals); ?></h3>
                            <p>기간 내 총 인출</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-arrow-up"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-success">
                        <div class="inner">
                            <h3><?php echo formatNumber($totalBalance); ?></h3>
                            <p>총 현재 잔액</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-balance-scale"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-primary">
                        <div class="inner">
                            <h3><?php echo count($fundStatusData); ?></h3>
                            <p>활성 기금 수</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-piggy-bank"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 기금 유형별 상태 차트 -->
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">기금 유형별 할당 및 인출</h3>
                        </div>
                        <div class="card-body">
                            <canvas id="fundTypeChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">기금 유형별 현재 잔액</h3>
                        </div>
                        <div class="card-body">
                            <canvas id="fundBalanceChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 기금 거래 추세 차트 -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">기금 거래 추세</h3>
                </div>
                <div class="card-body">
                    <canvas id="fundTrendsChart" style="min-height: 300px; height: 300px; max-height: 300px; max-width: 100%;"></canvas>
                </div>
            </div>

            <!-- 기금 상태 테이블 -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">기금 상세 현황</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>기금명</th>
                                    <th>기금 코드</th>
                                    <th>유형</th>
                                    <th>총 할당</th>
                                    <th>현재 잔액</th>
                                    <th>할당 비율</th>
                                    <th>이용률</th>
                                    <th>기간 내 할당</th>
                                    <th>기간 내 인출</th>
                                    <th>순 변화</th>
                                    <th>상태</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($fundStatusData)): ?>
                                <tr>
                                    <td colspan="11" class="text-center">데이터가 없습니다.</td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($fundStatusData as $fund): ?>
                                        <?php $statusInfo = getFundStatusInfo($fund['status']); ?>
                                        <tr>
                                            <td>
                                                <a href="../finance/fund-details.php?id=<?php echo $fund['id']; ?>">
                                                    <?php echo $fund['fund_name']; ?>
                                                </a>
                                            </td>
                                            <td><?php echo $fund['fund_code']; ?></td>
                                            <td><?php echo getFundTypeLabel($fund['fund_type']); ?></td>
                                            <td><?php echo formatNumber($fund['total_allocation']); ?></td>
                                            <td><?php echo formatNumber($fund['current_balance']); ?></td>
                                            <td><?php echo formatNumber($fund['allocation_percentage']); ?>%</td>
                                            <td>
                                                <div class="progress">
                                                    <div class="progress-bar bg-info" role="progressbar" style="width: <?php echo min(100, $fund['utilization_rate']); ?>%" 
                                                         aria-valuenow="<?php echo $fund['utilization_rate']; ?>" aria-valuemin="0" aria-valuemax="100">
                                                        <?php echo formatNumber($fund['utilization_rate']); ?>%
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo formatNumber($fund['period_allocations']); ?></td>
                                            <td><?php echo formatNumber($fund['period_withdrawals']); ?></td>
                                            <td class="<?php echo $fund['net_change'] >= 0 ? 'text-success' : 'text-danger'; ?>">
                                                <?php echo formatNumber($fund['net_change']); ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $statusInfo['class']; ?>">
                                                    <?php echo $statusInfo['label']; ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- 상위 기금 거래 -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">상위 10개 기금 거래</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>거래 코드</th>
                                    <th>기금명</th>
                                    <th>유형</th>
                                    <th>거래 유형</th>
                                    <th>금액</th>
                                    <th>거래일</th>
                                    <th>설명</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($topTransactionsData)): ?>
                                <tr>
                                    <td colspan="7" class="text-center">데이터가 없습니다.</td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($topTransactionsData as $transaction): ?>
                                        <tr>
                                            <td><?php echo $transaction['id']; ?></td>
                                            <td><?php echo $transaction['fund_name']; ?></td>
                                            <td><?php echo getFundTypeLabel($transaction['fund_type']); ?></td>
                                            <td><?php echo $transaction['transaction_type_label']; ?></td>
                                            <td><?php echo formatNumber($transaction['amount']); ?></td>
                                            <td><?php echo date('Y-m-d', strtotime($transaction['transaction_date'])); ?></td>
                                            <td><?php echo $transaction['description']; ?></td>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 콘솔 로그 기록
    console.log('기금 상태 보고서 페이지 로드됨');
    
    // Chart.js 차트 생성 - 기금 유형별 할당 및 인출
    var ctxFundType = document.getElementById('fundTypeChart').getContext('2d');
    var fundTypeChart = new Chart(ctxFundType, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($fundTypeLabelsChart); ?>,
            datasets: [
                {
                    label: '할당',
                    data: <?php echo json_encode($fundTypeAllocationValues); ?>,
                    backgroundColor: 'rgba(60, 141, 188, 0.8)',
                    borderColor: 'rgba(60, 141, 188, 1)',
                    borderWidth: 1
                },
                {
                    label: '인출',
                    data: <?php echo json_encode($fundTypeWithdrawalValues); ?>,
                    backgroundColor: 'rgba(255, 193, 7, 0.8)',
                    borderColor: 'rgba(255, 193, 7, 1)',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
    
    // Chart.js 차트 생성 - 기금 유형별 잔액
    var ctxFundBalance = document.getElementById('fundBalanceChart').getContext('2d');
    var fundBalanceChart = new Chart(ctxFundBalance, {
        type: 'pie',
        data: {
            labels: <?php echo json_encode($fundTypeLabelsChart); ?>,
            datasets: [{
                data: <?php echo json_encode($fundTypeBalanceValues); ?>,
                backgroundColor: [
                    'rgba(60, 141, 188, 0.8)',
                    'rgba(40, 167, 69, 0.8)',
                    'rgba(255, 193, 7, 0.8)',
                    'rgba(220, 53, 69, 0.8)',
                    'rgba(108, 117, 125, 0.8)',
                    'rgba(23, 162, 184, 0.8)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true
        }
    });
    
    // Chart.js 차트 생성 - 기금 거래 추세
    var ctxFundTrends = document.getElementById('fundTrendsChart').getContext('2d');
    var fundTrendsChart = new Chart(ctxFundTrends, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($trendLabels); ?>,
            datasets: [
                {
                    label: '할당',
                    data: <?php echo json_encode($allocationTrends); ?>,
                    backgroundColor: 'rgba(60, 141, 188, 0.2)',
                    borderColor: 'rgba(60, 141, 188, 1)',
                    borderWidth: 2,
                    fill: true
                },
                {
                    label: '인출',
                    data: <?php echo json_encode($withdrawalTrends); ?>,
                    backgroundColor: 'rgba(255, 193, 7, 0.2)',
                    borderColor: 'rgba(255, 193, 7, 1)',
                    borderWidth: 2,
                    fill: true
                },
                {
                    label: '순 변화',
                    data: <?php echo json_encode($netChangeTrends); ?>,
                    backgroundColor: 'rgba(40, 167, 69, 0.2)',
                    borderColor: 'rgba(40, 167, 69, 1)',
                    borderWidth: 2,
                    fill: false
                }
            ]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
    
    // 필터 폼 이벤트 리스너
    const reportFilterForm = document.getElementById('reportFilterForm');
    if (reportFilterForm) {
        console.log('필터 폼 이벤트 리스너 등록');
    }
});
</script>

<?php
// 데이터베이스 연결 종료
$conn->close();
?>

<?php
// 푸터 인클루드
include './footer.php';
