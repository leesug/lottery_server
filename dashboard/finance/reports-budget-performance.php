<?php
/**
 * 재무 관리 - 예산 대비 실적 보고서 페이지
 * 
 * 이 페이지는 예산 할당 대비 실제 사용 현황을 분석하고 보고서를 제공합니다.
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
$periodId = isset($_GET['period_id']) ? intval($_GET['period_id']) : 0;
$categoryType = isset($_GET['category_type']) ? sanitizeInput($_GET['category_type']) : '';

// 예산 기간 목록 조회
$periodsSql = "SELECT id, period_name, start_date, end_date, status FROM budget_periods ORDER BY start_date DESC";
$periodsResult = $conn->query($periodsSql);
$periods = [];
$currentPeriod = null;

while ($row = $periodsResult->fetch_assoc()) {
    $periods[] = $row;
    
    // 기본 선택 예산 기간 설정
    if ($periodId == 0 && ($row['status'] == 'active' || $currentPeriod === null)) {
        $currentPeriod = $row;
        $periodId = $row['id'];
    } else if ($row['id'] == $periodId) {
        $currentPeriod = $row;
    }
}

// 현재 선택된 기간이 없을 경우 첫 번째 기간 선택
if ($periodId == 0 && !empty($periods)) {
    $currentPeriod = $periods[0];
    $periodId = $currentPeriod['id'];
}

// 카테고리 유형 목록
$categoryTypes = [
    'income' => '수입',
    'expense' => '지출',
    'both' => '양쪽 모두'
];

// 예산 대비 실적 데이터 조회
$budgetPerformanceSql = "SELECT 
                        ba.id,
                        fc.category_name,
                        fc.category_type,
                        ba.allocated_amount,
                        ba.utilized_amount,
                        ba.remaining_amount,
                        ba.utilization_percentage
                        FROM budget_allocations ba
                        JOIN financial_categories fc ON ba.category_id = fc.id
                        WHERE ba.period_id = ?";

$params = [$periodId];
$paramTypes = "i";

// 카테고리 유형 필터 적용
if (!empty($categoryType)) {
    $budgetPerformanceSql .= " AND (fc.category_type = ? OR fc.category_type = 'both')";
    $params[] = $categoryType;
    $paramTypes .= "s";
}

$budgetPerformanceSql .= " ORDER BY fc.category_type, fc.category_name";

// 쿼리 실행
$budgetPerformanceStmt = $conn->prepare($budgetPerformanceSql);
$budgetPerformanceStmt->bind_param($paramTypes, ...$params);
$budgetPerformanceStmt->execute();
$budgetPerformanceResult = $budgetPerformanceStmt->get_result();

// 데이터 포맷팅
$budgetPerformanceData = [];
$totalAllocated = 0;
$totalUtilized = 0;
$totalRemaining = 0;
$categoryLabels = [];
$allocatedValues = [];
$utilizedValues = [];
$remainingValues = [];
$utilizationPercentages = [];

while ($row = $budgetPerformanceResult->fetch_assoc()) {
    $budgetPerformanceData[] = $row;
    
    // 차트 데이터 구성
    $categoryLabels[] = $row['category_name'];
    $allocatedValues[] = $row['allocated_amount'];
    $utilizedValues[] = $row['utilized_amount'];
    $remainingValues[] = $row['remaining_amount'];
    $utilizationPercentages[] = $row['utilization_percentage'];
    
    // 합계 계산
    $totalAllocated += $row['allocated_amount'];
    $totalUtilized += $row['utilized_amount'];
    $totalRemaining += $row['remaining_amount'];
}

// 총 활용률 계산
$totalUtilizationPercentage = $totalAllocated > 0 ? ($totalUtilized / $totalAllocated) * 100 : 0;

// 카테고리 유형별 예산 비율 데이터 조회
$categoryTypeSql = "SELECT 
                   fc.category_type,
                   SUM(ba.allocated_amount) AS allocated_amount,
                   SUM(ba.utilized_amount) AS utilized_amount,
                   SUM(ba.remaining_amount) AS remaining_amount,
                   ROUND((SUM(ba.utilized_amount) / SUM(ba.allocated_amount)) * 100, 2) AS utilization_percentage
                   FROM budget_allocations ba
                   JOIN financial_categories fc ON ba.category_id = fc.id
                   WHERE ba.period_id = ?
                   GROUP BY fc.category_type
                   ORDER BY fc.category_type";

// 쿼리 실행
$categoryTypeStmt = $conn->prepare($categoryTypeSql);
$categoryTypeStmt->bind_param("i", $periodId);
$categoryTypeStmt->execute();
$categoryTypeResult = $categoryTypeStmt->get_result();

// 데이터 포맷팅
$categoryTypeData = [];
$categoryTypeLabels = [];
$categoryTypeAllocated = [];
$categoryTypeUtilized = [];

while ($row = $categoryTypeResult->fetch_assoc()) {
    $categoryTypeData[] = $row;
    
    // 차트 데이터 구성
    $label = isset($categoryTypes[$row['category_type']]) ? $categoryTypes[$row['category_type']] : ucfirst($row['category_type']);
    $categoryTypeLabels[] = $label;
    $categoryTypeAllocated[] = $row['allocated_amount'];
    $categoryTypeUtilized[] = $row['utilized_amount'];
}

// 월간 예산 집행 추세 데이터 조회
if ($currentPeriod) {
    $startDate = $currentPeriod['start_date'];
    $endDate = $currentPeriod['end_date'];
    
    $monthlyTrendsSql = "SELECT 
                        DATE_FORMAT(ft.transaction_date, '%Y-%m') AS month,
                        SUM(ft.amount) AS amount_spent
                        FROM financial_transactions ft
                        JOIN financial_categories fc ON ft.category_id = fc.id
                        WHERE ft.transaction_date BETWEEN ? AND ?
                        AND ft.transaction_type = 'expense'
                        AND ft.status = 'completed'";
    
    $mtParams = [$startDate, $endDate];
    $mtParamTypes = "ss";
    
    // 카테고리 유형 필터 적용
    if (!empty($categoryType)) {
        $monthlyTrendsSql .= " AND (fc.category_type = ? OR fc.category_type = 'both')";
        $mtParams[] = $categoryType;
        $mtParamTypes .= "s";
    }
    
    $monthlyTrendsSql .= " GROUP BY month
                          ORDER BY month";
    
    // 쿼리 실행
    $monthlyTrendsStmt = $conn->prepare($monthlyTrendsSql);
    $monthlyTrendsStmt->bind_param($mtParamTypes, ...$mtParams);
    $monthlyTrendsStmt->execute();
    $monthlyTrendsResult = $monthlyTrendsStmt->get_result();
    
    // 데이터 포맷팅
    $monthlyTrendsData = [];
    $monthLabels = [];
    $monthlyAmounts = [];
    
    while ($row = $monthlyTrendsResult->fetch_assoc()) {
        $monthlyTrendsData[] = $row;
        
        // 차트 데이터 구성
        $monthLabels[] = $row['month'];
        $monthlyAmounts[] = $row['amount_spent'];
    }
}

// 상위 예산 초과 항목 조회
$overBudgetSql = "SELECT 
                 fc.category_name,
                 fc.category_type,
                 ba.allocated_amount,
                 ba.utilized_amount,
                 ba.remaining_amount,
                 ba.utilization_percentage
                 FROM budget_allocations ba
                 JOIN financial_categories fc ON ba.category_id = fc.id
                 WHERE ba.period_id = ?
                 AND ba.utilized_amount > ba.allocated_amount
                 ORDER BY ba.utilization_percentage DESC
                 LIMIT 10";

// 쿼리 실행
$overBudgetStmt = $conn->prepare($overBudgetSql);
$overBudgetStmt->bind_param("i", $periodId);
$overBudgetStmt->execute();
$overBudgetResult = $overBudgetStmt->get_result();

// 데이터 포맷팅
$overBudgetData = [];

while ($row = $overBudgetResult->fetch_assoc()) {
    $overBudgetData[] = $row;
}

// 상위 미사용 예산 항목 조회
$underBudgetSql = "SELECT 
                  fc.category_name,
                  fc.category_type,
                  ba.allocated_amount,
                  ba.utilized_amount,
                  ba.remaining_amount,
                  ba.utilization_percentage
                  FROM budget_allocations ba
                  JOIN financial_categories fc ON ba.category_id = fc.id
                  WHERE ba.period_id = ?
                  AND ba.allocated_amount > 0
                  ORDER BY ba.utilization_percentage ASC
                  LIMIT 10";

// 쿼리 실행
$underBudgetStmt = $conn->prepare($underBudgetSql);
$underBudgetStmt->bind_param("i", $periodId);
$underBudgetStmt->execute();
$underBudgetResult = $underBudgetStmt->get_result();

// 데이터 포맷팅
$underBudgetData = [];

while ($row = $underBudgetResult->fetch_assoc()) {
    $underBudgetData[] = $row;
}

// 페이지 제목 설정
$pageTitle = "예산 대비 실적 보고서";
$currentSection = "finance";
$currentPage = "reports";

// 헤더 및 네비게이션 포함
include './header.php';
include './navbar.php';

// 숫자 포맷 함수
function formatNumber($number, $decimals = 2) {
    return number_format($number, $decimals);
}

// 카테고리 유형 한글명 반환 함수
function getCategoryTypeLabel($type) {
    global $categoryTypes;
    return isset($categoryTypes[$type]) ? $categoryTypes[$type] : ucfirst($type);
}

// 사용률에 따른 상태 클래스 반환 함수
function getUtilizationClass($percentage) {
    if ($percentage < 70) {
        return 'success';
    } else if ($percentage < 90) {
        return 'info';
    } else if ($percentage < 100) {
        return 'warning';
    } else {
        return 'danger';
    }
}

// 예산 기간 상태 반환 함수
function getPeriodStatusLabel($status) {
    $labels = [
        'planning' => ['label' => '계획중', 'class' => 'info'],
        'active' => ['label' => '활성', 'class' => 'success'],
        'closed' => ['label' => '종료', 'class' => 'secondary']
    ];
    
    return isset($labels[$status]) ? $labels[$status] : ['label' => $status, 'class' => 'primary'];
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
                        <li class="breadcrumb-item active">예산 대비 실적 보고서</li>
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
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="period_id">예산 기간</label>
                                    <select name="period_id" id="period_id" class="form-control">
                                        <?php if (empty($periods)): ?>
                                            <option value="0">예산 기간이 없습니다</option>
                                        <?php else: ?>
                                            <?php foreach ($periods as $period): ?>
                                                <?php $statusInfo = getPeriodStatusLabel($period['status']); ?>
                                                <option value="<?php echo $period['id']; ?>" <?php if ($periodId == $period['id']) echo 'selected'; ?>>
                                                    <?php echo $period['period_name']; ?> 
                                                    (<?php echo date('Y-m-d', strtotime($period['start_date'])); ?> ~ 
                                                    <?php echo date('Y-m-d', strtotime($period['end_date'])); ?>) 
                                                    - <?php echo $statusInfo['label']; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="category_type">카테고리 유형</label>
                                    <select name="category_type" id="category_type" class="form-control">
                                        <option value="">모든 유형</option>
                                        <?php foreach ($categoryTypes as $type => $label): ?>
                                            <option value="<?php echo $type; ?>" <?php if ($categoryType === $type) echo 'selected'; ?>>
                                                <?php echo $label; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary btn-block">적용</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <?php if ($currentPeriod): ?>
            <!-- 예산 기간 정보 -->
            <div class="alert alert-info">
                <h5><i class="fas fa-info-circle"></i> 예산 기간 정보</h5>
                <p>
                    <strong>기간명:</strong> <?php echo $currentPeriod['period_name']; ?><br>
                    <strong>기간:</strong> <?php echo date('Y-m-d', strtotime($currentPeriod['start_date'])); ?> ~ 
                    <?php echo date('Y-m-d', strtotime($currentPeriod['end_date'])); ?><br>
                    <strong>상태:</strong> 
                    <?php 
                    $statusInfo = getPeriodStatusLabel($currentPeriod['status']);
                    echo '<span class="badge bg-' . $statusInfo['class'] . '">' . $statusInfo['label'] . '</span>';
                    ?>
                </p>
            </div>

            <!-- 요약 정보 카드 -->
            <div class="row">
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3><?php echo formatNumber($totalAllocated); ?></h3>
                            <p>총 할당 예산</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-coins"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-success">
                        <div class="inner">
                            <h3><?php echo formatNumber($totalUtilized); ?></h3>
                            <p>총 사용 금액</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-warning">
                        <div class="inner">
                            <h3><?php echo formatNumber($totalRemaining); ?></h3>
                            <p>총 잔여 예산</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-wallet"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-<?php echo getUtilizationClass($totalUtilizationPercentage); ?>">
                        <div class="inner">
                            <h3><?php echo formatNumber($totalUtilizationPercentage); ?>%</h3>
                            <p>총 예산 활용률</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-chart-pie"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 예산 대비 실적 그래프 -->
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">카테고리별 예산 대비 실적</h3>
                        </div>
                        <div class="card-body">
                            <canvas id="budgetPerformanceChart" style="min-height: 300px; height: 300px; max-height: 300px; max-width: 100%;"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">카테고리별 예산 활용률</h3>
                        </div>
                        <div class="card-body">
                            <canvas id="utilizationChart" style="min-height: 300px; height: 300px; max-height: 300px; max-width: 100%;"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 카테고리 유형별 예산 비율 및 월간 추세 -->
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">카테고리 유형별 예산 비율</h3>
                        </div>
                        <div class="card-body">
                            <canvas id="categoryTypeChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">월간 예산 집행 추세</h3>
                        </div>
                        <div class="card-body">
                            <?php if (isset($monthlyTrendsData) && !empty($monthlyTrendsData)): ?>
                                <canvas id="monthlyTrendsChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                            <?php else: ?>
                                <div class="text-center p-5">
                                    <p class="text-muted">월간 예산 집행 데이터가 없습니다.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 상위 예산 초과 및 미사용 예산 항목 -->
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-danger">
                            <h3 class="card-title">상위 예산 초과 항목</h3>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>카테고리</th>
                                            <th>유형</th>
                                            <th>할당 예산</th>
                                            <th>사용 금액</th>
                                            <th>초과 금액</th>
                                            <th>사용률</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($overBudgetData)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center">예산 초과 항목이 없습니다.</td>
                                        </tr>
                                        <?php else: ?>
                                            <?php foreach ($overBudgetData as $item): ?>
                                                <tr>
                                                    <td><?php echo $item['category_name']; ?></td>
                                                    <td><?php echo getCategoryTypeLabel($item['category_type']); ?></td>
                                                    <td><?php echo formatNumber($item['allocated_amount']); ?></td>
                                                    <td><?php echo formatNumber($item['utilized_amount']); ?></td>
                                                    <td class="text-danger"><?php echo formatNumber(abs($item['remaining_amount'])); ?></td>
                                                    <td>
                                                        <span class="badge bg-danger"><?php echo formatNumber($item['utilization_percentage']); ?>%</span>
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
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-success">
                            <h3 class="card-title">상위 미사용 예산 항목</h3>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>카테고리</th>
                                            <th>유형</th>
                                            <th>할당 예산</th>
                                            <th>사용 금액</th>
                                            <th>잔여 금액</th>
                                            <th>사용률</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($underBudgetData)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center">미사용 예산 항목이 없습니다.</td>
                                        </tr>
                                        <?php else: ?>
                                            <?php foreach ($underBudgetData as $item): ?>
                                                <tr>
                                                    <td><?php echo $item['category_name']; ?></td>
                                                    <td><?php echo getCategoryTypeLabel($item['category_type']); ?></td>
                                                    <td><?php echo formatNumber($item['allocated_amount']); ?></td>
                                                    <td><?php echo formatNumber($item['utilized_amount']); ?></td>
                                                    <td class="text-success"><?php echo formatNumber($item['remaining_amount']); ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php echo getUtilizationClass($item['utilization_percentage']); ?>">
                                                            <?php echo formatNumber($item['utilization_percentage']); ?>%
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
                </div>
            </div>

            <!-- 전체 예산 대비 실적 테이블 -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">모든 카테고리 예산 대비 실적</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>카테고리</th>
                                    <th>유형</th>
                                    <th>할당 예산</th>
                                    <th>사용 금액</th>
                                    <th>잔여 예산</th>
                                    <th>사용률</th>
                                    <th>상태</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($budgetPerformanceData)): ?>
                                <tr>
                                    <td colspan="7" class="text-center">데이터가 없습니다.</td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($budgetPerformanceData as $item): ?>
                                        <tr>
                                            <td><?php echo $item['category_name']; ?></td>
                                            <td><?php echo getCategoryTypeLabel($item['category_type']); ?></td>
                                            <td><?php echo formatNumber($item['allocated_amount']); ?></td>
                                            <td><?php echo formatNumber($item['utilized_amount']); ?></td>
                                            <td class="<?php echo $item['remaining_amount'] >= 0 ? 'text-success' : 'text-danger'; ?>">
                                                <?php echo formatNumber($item['remaining_amount']); ?>
                                            </td>
                                            <td>
                                                <div class="progress">
                                                    <div class="progress-bar bg-<?php echo getUtilizationClass($item['utilization_percentage']); ?>" 
                                                         role="progressbar" 
                                                         style="width: <?php echo min(100, $item['utilization_percentage']); ?>%"
                                                         aria-valuenow="<?php echo $item['utilization_percentage']; ?>" 
                                                         aria-valuemin="0" 
                                                         aria-valuemax="100">
                                                        <?php echo formatNumber($item['utilization_percentage']); ?>%
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <?php
                                                $utilizationClass = getUtilizationClass($item['utilization_percentage']);
                                                $status = '정상';
                                                
                                                if ($item['utilization_percentage'] >= 100) {
                                                    $status = '초과';
                                                } else if ($item['utilization_percentage'] < 30) {
                                                    $status = '저조';
                                                }
                                                ?>
                                                <span class="badge bg-<?php echo $utilizationClass; ?>"><?php echo $status; ?></span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="alert alert-warning">
                <h5><i class="fas fa-exclamation-triangle"></i> 예산 기간이 없습니다</h5>
                <p>예산 대비 실적 보고서를 보려면 먼저 예산 기간을 생성해야 합니다.</p>
                <a href="budget-periods.php" class="btn btn-warning btn-sm">예산 기간 관리</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 콘솔 로그 기록
    console.log('예산 대비 실적 보고서 페이지 로드됨');
    
    <?php if ($currentPeriod && !empty($budgetPerformanceData)): ?>
    // Chart.js 차트 생성 - 카테고리별 예산 대비 실적
    var ctxBudgetPerformance = document.getElementById('budgetPerformanceChart').getContext('2d');
    var budgetPerformanceChart = new Chart(ctxBudgetPerformance, {
        type: 'horizontalBar',
        data: {
            labels: <?php echo json_encode($categoryLabels); ?>,
            datasets: [
                {
                    label: '할당 예산',
                    data: <?php echo json_encode($allocatedValues); ?>,
                    backgroundColor: 'rgba(60, 141, 188, 0.8)',
                    borderColor: 'rgba(60, 141, 188, 1)',
                    borderWidth: 1
                },
                {
                    label: '사용 금액',
                    data: <?php echo json_encode($utilizedValues); ?>,
                    backgroundColor: 'rgba(40, 167, 69, 0.8)',
                    borderColor: 'rgba(40, 167, 69, 1)',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            scales: {
                x: {
                    beginAtZero: true
                }
            }
        }
    });
    
    // Chart.js 차트 생성 - 카테고리별 예산 활용률
    var ctxUtilization = document.getElementById('utilizationChart').getContext('2d');
    var utilizationChart = new Chart(ctxUtilization, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($categoryLabels); ?>,
            datasets: [{
                label: '예산 활용률 (%)',
                data: <?php echo json_encode($utilizationPercentages); ?>,
                backgroundColor: function(context) {
                    var index = context.dataIndex;
                    var value = context.dataset.data[index];
                    
                    if (value < 70) return 'rgba(40, 167, 69, 0.8)'; // success
                    else if (value < 90) return 'rgba(23, 162, 184, 0.8)'; // info
                    else if (value < 100) return 'rgba(255, 193, 7, 0.8)'; // warning
                    else return 'rgba(220, 53, 69, 0.8)'; // danger
                },
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100
                }
            }
        }
    });
    
    // Chart.js 차트 생성 - 카테고리 유형별 예산 비율
    var ctxCategoryType = document.getElementById('categoryTypeChart').getContext('2d');
    var categoryTypeChart = new Chart(ctxCategoryType, {
        type: 'pie',
        data: {
            labels: <?php echo json_encode($categoryTypeLabels); ?>,
            datasets: [{
                label: '할당 예산',
                data: <?php echo json_encode($categoryTypeAllocated); ?>,
                backgroundColor: [
                    'rgba(60, 141, 188, 0.8)',
                    'rgba(220, 53, 69, 0.8)',
                    'rgba(255, 193, 7, 0.8)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true
        }
    });
    
    <?php if (isset($monthlyTrendsData) && !empty($monthlyTrendsData)): ?>
    // Chart.js 차트 생성 - 월간 예산 집행 추세
    var ctxMonthlyTrends = document.getElementById('monthlyTrendsChart').getContext('2d');
    var monthlyTrendsChart = new Chart(ctxMonthlyTrends, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($monthLabels); ?>,
            datasets: [{
                label: '월별 지출',
                data: <?php echo json_encode($monthlyAmounts); ?>,
                backgroundColor: 'rgba(60, 141, 188, 0.2)',
                borderColor: 'rgba(60, 141, 188, 1)',
                borderWidth: 2,
                fill: true
            }]
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
    <?php endif; ?>
    <?php endif; ?>
    
    // 필터 폼 이벤트 리스너
    const reportFilterForm = document.getElementById('reportFilterForm');
    if (reportFilterForm) {
        console.log('필터 폼 이벤트 리스너 등록');
        
        // 예산 기간 변경 이벤트 처리
        const periodSelect = document.getElementById('period_id');
        if (periodSelect) {
            periodSelect.addEventListener('change', function() {
                console.log('예산 기간 변경:', this.value);
            });
        }
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
