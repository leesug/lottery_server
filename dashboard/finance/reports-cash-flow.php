<?php
/**
 * 재무 관리 - 현금 흐름 보고서 페이지
 * 
 * 이 페이지는 기간별 수입과 지출의 흐름을 분석하고 순현금 흐름을 보여주는 보고서를 제공합니다.
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
$startDate = isset($_GET['start_date']) ? sanitizeInput($_GET['start_date']) : date('Y-01-01');
$endDate = isset($_GET['end_date']) ? sanitizeInput($_GET['end_date']) : date('Y-m-d');
$groupBy = isset($_GET['group_by']) ? sanitizeInput($_GET['group_by']) : 'month';
$category = isset($_GET['category']) ? sanitizeInput($_GET['category']) : '';

// 날짜 범위가 유효한지 확인
if (strtotime($startDate) > strtotime($endDate)) {
    $temp = $startDate;
    $startDate = $endDate;
    $endDate = $temp;
}

// 유효한 그룹화 옵션인지 확인
$validGroupByOptions = ['day', 'week', 'month', 'quarter', 'year'];
if (!in_array($groupBy, $validGroupByOptions)) {
    $groupBy = 'month';
}

// 그룹화에 따른 DATE_FORMAT 설정
$dateFormat = 'DATE_FORMAT(transaction_date, "%Y-%m-%d")';
$dateLabel = 'DATE_FORMAT(transaction_date, "%Y-%m-%d")';
$intervalGroup = '';

switch ($groupBy) {
    case 'week':
        $dateFormat = 'DATE_FORMAT(transaction_date, "%Y-%u")';
        $dateLabel = 'CONCAT(DATE_FORMAT(transaction_date, "%Y"), " 주차 ", WEEK(transaction_date))';
        $intervalGroup = 'WEEK';
        break;
    case 'month':
        $dateFormat = 'DATE_FORMAT(transaction_date, "%Y-%m")';
        $dateLabel = 'DATE_FORMAT(transaction_date, "%Y-%m")';
        $intervalGroup = 'MONTH';
        break;
    case 'quarter':
        $dateFormat = 'CONCAT(YEAR(transaction_date), "-", QUARTER(transaction_date))';
        $dateLabel = 'CONCAT(YEAR(transaction_date), " Q", QUARTER(transaction_date))';
        $intervalGroup = 'QUARTER';
        break;
    case 'year':
        $dateFormat = 'YEAR(transaction_date)';
        $dateLabel = 'YEAR(transaction_date)';
        $intervalGroup = 'YEAR';
        break;
}

// 현금 흐름 데이터 조회 쿼리 구성
$cashflowSql = "SELECT 
               {$dateLabel} as date_group,
               SUM(CASE WHEN transaction_type = 'income' THEN amount ELSE 0 END) as income,
               SUM(CASE WHEN transaction_type = 'expense' THEN amount ELSE 0 END) as expense,
               SUM(CASE WHEN transaction_type = 'income' THEN amount
                       WHEN transaction_type = 'expense' THEN -amount
                       ELSE 0 END) as net_cash_flow
               FROM financial_transactions
               WHERE transaction_date BETWEEN ? AND ?
               AND status = 'completed'";

$params = [$startDate, $endDate];
$paramTypes = "ss";

// 카테고리 필터 적용
if (!empty($category)) {
    $categoryIds = [];
    
    // 카테고리 ID 조회
    $catSql = "SELECT id FROM financial_categories WHERE category_type = ?";
    $catStmt = $conn->prepare($catSql);
    $catStmt->bind_param("s", $category);
    $catStmt->execute();
    $catResult = $catStmt->get_result();
    
    while ($row = $catResult->fetch_assoc()) {
        $categoryIds[] = $row['id'];
    }
    
    // 양쪽 모두 유형 카테고리 추가
    $bothSql = "SELECT id FROM financial_categories WHERE category_type = 'both'";
    $bothResult = $conn->query($bothSql);
    
    while ($row = $bothResult->fetch_assoc()) {
        $categoryIds[] = $row['id'];
    }
    
    if (!empty($categoryIds)) {
        $categoryIdStr = implode(',', $categoryIds);
        $cashflowSql .= " AND category_id IN ({$categoryIdStr})";
    }
}

// 그룹화 및 정렬
$cashflowSql .= " GROUP BY date_group
                ORDER BY MIN(transaction_date)";

// 쿼리 실행
$cashflowStmt = $conn->prepare($cashflowSql);
$cashflowStmt->bind_param($paramTypes, ...$params);
$cashflowStmt->execute();
$cashflowResult = $cashflowStmt->get_result();

// 데이터 포맷팅
$cashflowData = [];
$labels = [];
$incomeValues = [];
$expenseValues = [];
$netCashFlowValues = [];
$cumulativeNetCashFlow = [];

$totalIncome = 0;
$totalExpense = 0;
$netTotal = 0;
$cumulativeAmount = 0;

while ($row = $cashflowResult->fetch_assoc()) {
    $cashflowData[] = $row;
    
    // 차트 데이터 구성
    $labels[] = $row['date_group'];
    $incomeValues[] = $row['income'];
    $expenseValues[] = $row['expense'];
    $netCashFlowValues[] = $row['net_cash_flow'];
    
    // 누적 현금 흐름 계산
    $cumulativeAmount += $row['net_cash_flow'];
    $cumulativeNetCashFlow[] = $cumulativeAmount;
    
    // 합계 계산
    $totalIncome += $row['income'];
    $totalExpense += $row['expense'];
}

$netTotal = $totalIncome - $totalExpense;

// 카테고리별 현금 흐름 데이터 조회
$categoryCashflowSql = "SELECT 
                       fc.category_name,
                       fc.category_type,
                       SUM(CASE WHEN ft.transaction_type = 'income' THEN ft.amount ELSE 0 END) as income,
                       SUM(CASE WHEN ft.transaction_type = 'expense' THEN ft.amount ELSE 0 END) as expense,
                       SUM(CASE WHEN ft.transaction_type = 'income' THEN ft.amount
                              WHEN ft.transaction_type = 'expense' THEN -ft.amount
                              ELSE 0 END) as net_flow
                       FROM financial_transactions ft
                       JOIN financial_categories fc ON ft.category_id = fc.id
                       WHERE ft.transaction_date BETWEEN ? AND ?
                       AND ft.status = 'completed'
                       GROUP BY fc.id
                       ORDER BY net_flow DESC";

// 쿼리 실행
$categoryCashflowStmt = $conn->prepare($categoryCashflowSql);
$categoryCashflowStmt->bind_param("ss", $startDate, $endDate);
$categoryCashflowStmt->execute();
$categoryCashflowResult = $categoryCashflowStmt->get_result();

// 데이터 포맷팅
$categoryCashflowData = [];

while ($row = $categoryCashflowResult->fetch_assoc()) {
    $categoryCashflowData[] = $row;
}

// 월별 현금 흐름 추세 분석
$trendsData = [];
$isPeriodLongEnough = false;

if (count($labels) > 1) {
    $isPeriodLongEnough = true;
    
    // 증가율 계산
    $changeRates = [];
    $previousNet = null;
    
    foreach ($netCashFlowValues as $index => $value) {
        if ($previousNet !== null && $previousNet != 0) {
            $changeRate = (($value - $previousNet) / abs($previousNet)) * 100;
            $changeRates[] = $changeRate;
        }
        $previousNet = $value;
    }
    
    // 평균 변화율
    $avgChangeRate = !empty($changeRates) ? array_sum($changeRates) / count($changeRates) : 0;
    
    // 최근 3개 기간 평균
    $recentPeriods = array_slice($netCashFlowValues, -3);
    $recentAvg = !empty($recentPeriods) ? array_sum($recentPeriods) / count($recentPeriods) : 0;
    
    // 전체 기간 평균
    $overallAvg = array_sum($netCashFlowValues) / count($netCashFlowValues);
    
    // 추세 방향
    $trendDirection = $avgChangeRate > 0 ? '상승' : ($avgChangeRate < 0 ? '하락' : '안정');
    
    // 건전성 평가
    $healthStatus = '';
    $healthClass = '';
    
    if ($netTotal > 0 && $recentAvg > $overallAvg && $trendDirection === '상승') {
        $healthStatus = '매우 건전';
        $healthClass = 'success';
    } elseif ($netTotal > 0 && $trendDirection !== '하락') {
        $healthStatus = '건전';
        $healthClass = 'info';
    } elseif ($netTotal > 0 && $trendDirection === '하락') {
        $healthStatus = '주의 필요';
        $healthClass = 'warning';
    } else {
        $healthStatus = '개선 필요';
        $healthClass = 'danger';
    }
    
    $trendsData = [
        'avg_change_rate' => $avgChangeRate,
        'recent_avg' => $recentAvg,
        'overall_avg' => $overallAvg,
        'trend_direction' => $trendDirection,
        'health_status' => $healthStatus,
        'health_class' => $healthClass
    ];
}

// 페이지 제목 설정
$pageTitle = "현금 흐름 보고서";
$currentSection = "finance";
$currentPage = "reports";

// 헤더 및 네비게이션 포함
include '../../templates/header.php';
include '../../templates/navbar.php';

// 숫자 포맷 함수
function formatNumber($number, $decimals = 2) {
    return number_format($number, $decimals);
}

// 카테고리 유형 한글명 반환 함수
function getCategoryTypeLabel($type) {
    $labels = [
        'income' => '수입',
        'expense' => '지출',
        'both' => '수입/지출'
    ];
    
    return isset($labels[$type]) ? $labels[$type] : ucfirst($type);
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
                        <li class="breadcrumb-item"><a href="./">보고서</a></li>
                        <li class="breadcrumb-item active">현금 흐름 보고서</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">
            <!-- 필터 -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-filter mr-1"></i>
                                보고서 필터
                            </h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <form id="filterForm" method="get" class="form-horizontal">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="start_date">시작일</label>
                                            <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $startDate; ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="end_date">종료일</label>
                                            <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $endDate; ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="group_by">그룹화 단위</label>
                                            <select class="form-control" id="group_by" name="group_by">
                                                <option value="day" <?php if ($groupBy === 'day') echo 'selected'; ?>>일별</option>
                                                <option value="week" <?php if ($groupBy === 'week') echo 'selected'; ?>>주별</option>
                                                <option value="month" <?php if ($groupBy === 'month') echo 'selected'; ?>>월별</option>
                                                <option value="quarter" <?php if ($groupBy === 'quarter') echo 'selected'; ?>>분기별</option>
                                                <option value="year" <?php if ($groupBy === 'year') echo 'selected'; ?>>연도별</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="category">카테고리 필터</label>
                                            <select class="form-control" id="category" name="category">
                                                <option value="">모든 카테고리</option>
                                                <option value="income" <?php if ($category === 'income') echo 'selected'; ?>>수입 카테고리</option>
                                                <option value="expense" <?php if ($category === 'expense') echo 'selected'; ?>>지출 카테고리</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group mb-0">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-search mr-1"></i> 적용
                                            </button>
                                            <a href="reports-cash-flow.php" class="btn btn-default ml-1">
                                                <i class="fas fa-sync-alt mr-1"></i> 초기화
                                            </a>
                                            <button type="button" class="btn btn-success ml-1" id="exportBtn">
                                                <i class="fas fa-file-excel mr-1"></i> 엑셀 내보내기
                                            </button>
                                            <button type="button" class="btn btn-info ml-1" id="printBtn">
                                                <i class="fas fa-print mr-1"></i> 인쇄
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 현금 흐름 요약 카드 -->
            <div class="row">
                <div class="col-lg-4 col-12">
                    <div class="small-box bg-success">
                        <div class="inner">
                            <h3><?php echo formatNumber($totalIncome, 0); ?> <small>NPR</small></h3>
                            <p>총 수입</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-arrow-circle-up"></i>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4 col-12">
                    <div class="small-box bg-danger">
                        <div class="inner">
                            <h3><?php echo formatNumber($totalExpense, 0); ?> <small>NPR</small></h3>
                            <p>총 지출</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-arrow-circle-down"></i>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4 col-12">
                    <div class="small-box <?php echo $netTotal >= 0 ? 'bg-info' : 'bg-warning'; ?>">
                        <div class="inner">
                            <h3><?php echo formatNumber($netTotal, 0); ?> <small>NPR</small></h3>
                            <p>순 현금 흐름</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-exchange-alt"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 현금 흐름 차트 -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-chart-line mr-1"></i>
                                현금 흐름 추이
                            </h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if (empty($cashflowData)): ?>
                            <div class="alert alert-info">
                                <i class="icon fas fa-info-circle"></i>
                                선택한 기간에 현금 흐름 데이터가 없습니다.
                            </div>
                            <?php else: ?>
                            <ul class="nav nav-tabs" id="cashflowTab" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active" id="income-expense-tab" data-toggle="tab" href="#income-expense" role="tab">
                                        수입 & 지출
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="net-flow-tab" data-toggle="tab" href="#net-flow" role="tab">
                                        순 현금 흐름
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="cumulative-flow-tab" data-toggle="tab" href="#cumulative-flow" role="tab">
                                        누적 현금 흐름
                                    </a>
                                </li>
                            </ul>
                            
                            <div class="tab-content mt-3" id="cashflowTabContent">
                                <div class="tab-pane fade show active" id="income-expense" role="tabpanel">
                                    <div class="chart">
                                        <canvas id="incomeExpenseChart" style="min-height: 300px; height: 300px; max-height: 300px; max-width: 100%;"></canvas>
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="net-flow" role="tabpanel">
                                    <div class="chart">
                                        <canvas id="netFlowChart" style="min-height: 300px; height: 300px; max-height: 300px; max-width: 100%;"></canvas>
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="cumulative-flow" role="tabpanel">
                                    <div class="chart">
                                        <canvas id="cumulativeFlowChart" style="min-height: 300px; height: 300px; max-height: 300px; max-width: 100%;"></canvas>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 추세 분석 및 카테고리별 현금 흐름 -->
            <div class="row">
                <?php if ($isPeriodLongEnough): ?>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-chart-area mr-1"></i>
                                현금 흐름 추세 분석
                            </h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="info-box bg-<?php echo $trendsData['health_class']; ?>">
                                        <span class="info-box-icon"><i class="fas fa-heartbeat"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">현금 흐름 건전성</span>
                                            <span class="info-box-number"><?php echo $trendsData['health_status']; ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-box bg-light">
                                        <span class="info-box-icon"><i class="fas fa-chart-line"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">추세 방향</span>
                                            <span class="info-box-number"><?php echo $trendsData['trend_direction']; ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-4">
                                <table class="table table-bordered">
                                    <tr>
                                        <th>평균 변화율</th>
                                        <td><?php echo formatNumber($trendsData['avg_change_rate'], 2); ?>%</td>
                                    </tr>
                                    <tr>
                                        <th>최근 <?php echo min(3, count($netCashFlowValues)); ?> 기간 평균</th>
                                        <td><?php echo formatNumber($trendsData['recent_avg'], 2); ?> NPR</td>
                                    </tr>
                                    <tr>
                                        <th>전체 기간 평균</th>
                                        <td><?php echo formatNumber($trendsData['overall_avg'], 2); ?> NPR</td>
                                    </tr>
                                </table>
                            </div>
                            
                            <div class="alert alert-<?php echo $trendsData['health_class']; ?> mt-3">
                                <h5><i class="icon fas fa-info-circle"></i> 분석 결과</h5>
                                <?php if ($trendsData['health_class'] === 'success'): ?>
                                현금 흐름이 매우 건전합니다. 순현금 흐름이 양수이며 상승 추세를 보이고 있습니다.
                                <?php elseif ($trendsData['health_class'] === 'info'): ?>
                                현금 흐름이 건전합니다. 순현금 흐름이 양수를 유지하고 있습니다.
                                <?php elseif ($trendsData['health_class'] === 'warning'): ?>
                                현금 흐름에 주의가 필요합니다. 순현금 흐름이 양수이지만 하락 추세를 보이고 있습니다.
                                <?php else: ?>
                                현금 흐름 개선이 필요합니다. 순현금 흐름이 음수이거나 지속적인 하락 추세를 보이고 있습니다.
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="col-md-<?php echo $isPeriodLongEnough ? '6' : '12'; ?>">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-list mr-1"></i>
                                카테고리별 현금 흐름
                            </h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if (empty($categoryCashflowData)): ?>
                            <div class="alert alert-info">
                                <i class="icon fas fa-info-circle"></i>
                                선택한 기간에 현금 흐름 데이터가 없습니다.
                            </div>
                            <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <th>카테고리</th>
                                            <th>유형</th>
                                            <th class="text-right">수입 (NPR)</th>
                                            <th class="text-right">지출 (NPR)</th>
                                            <th class="text-right">순 흐름 (NPR)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($categoryCashflowData as $data): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($data['category_name']); ?></td>
                                            <td><?php echo getCategoryTypeLabel($data['category_type']); ?></td>
                                            <td class="text-right"><?php echo formatNumber($data['income'], 2); ?></td>
                                            <td class="text-right"><?php echo formatNumber($data['expense'], 2); ?></td>
                                            <td class="text-right">
                                                <span class="<?php echo $data['net_flow'] >= 0 ? 'text-success' : 'text-danger'; ?>">
                                                    <?php echo formatNumber($data['net_flow'], 2); ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr class="bg-light">
                                            <th colspan="2">합계</th>
                                            <th class="text-right"><?php echo formatNumber($totalIncome, 2); ?></th>
                                            <th class="text-right"><?php echo formatNumber($totalExpense, 2); ?></th>
                                            <th class="text-right">
                                                <span class="<?php echo $netTotal >= 0 ? 'text-success' : 'text-danger'; ?>">
                                                    <?php echo formatNumber($netTotal, 2); ?>
                                                </span>
                                            </th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 상세 현금 흐름 데이터 -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-table mr-1"></i>
                                상세 현금 흐름 데이터
                            </h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body table-responsive">
                            <?php if (empty($cashflowData)): ?>
                            <div class="alert alert-info">
                                <i class="icon fas fa-info-circle"></i>
                                선택한 기간에 현금 흐름 데이터가 없습니다.
                            </div>
                            <?php else: ?>
                            <table class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th>기간</th>
                                        <th class="text-right">수입 (NPR)</th>
                                        <th class="text-right">지출 (NPR)</th>
                                        <th class="text-right">순 현금 흐름 (NPR)</th>
                                        <th class="text-right">누적 현금 흐름 (NPR)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $runningTotal = 0;
                                    foreach ($cashflowData as $index => $data): 
                                        $runningTotal += $data['net_cash_flow'];
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($data['date_group']); ?></td>
                                        <td class="text-right"><?php echo formatNumber($data['income'], 2); ?></td>
                                        <td class="text-right"><?php echo formatNumber($data['expense'], 2); ?></td>
                                        <td class="text-right">
                                            <span class="<?php echo $data['net_cash_flow'] >= 0 ? 'text-success' : 'text-danger'; ?>">
                                                <?php echo formatNumber($data['net_cash_flow'], 2); ?>
                                            </span>
                                        </td>
                                        <td class="text-right">
                                            <span class="<?php echo $runningTotal >= 0 ? 'text-success' : 'text-danger'; ?>">
                                                <?php echo formatNumber($runningTotal, 2); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="bg-light">
                                        <th>합계</th>
                                        <th class="text-right"><?php echo formatNumber($totalIncome, 2); ?></th>
                                        <th class="text-right"><?php echo formatNumber($totalExpense, 2); ?></th>
                                        <th class="text-right">
                                            <span class="<?php echo $netTotal >= 0 ? 'text-success' : 'text-danger'; ?>">
                                                <?php echo formatNumber($netTotal, 2); ?>
                                            </span>
                                        </th>
                                        <th class="text-right">
                                            <span class="<?php echo $runningTotal >= 0 ? 'text-success' : 'text-danger'; ?>">
                                                <?php echo formatNumber($runningTotal, 2); ?>
                                            </span>
                                        </th>
                                    </tr>
                                </tfoot>
                            </table>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 인쇄용 스타일 -->
<style type="text/css" media="print">
@media print {
    .no-print, .main-header, .main-sidebar, .content-header, .card-tools, .main-footer, .nav-tabs {
        display: none !important;
    }
    
    .content-wrapper, .card {
        margin: 0 !important;
        padding: 0 !important;
        border: none !important;
        box-shadow: none !important;
    }
    
    .tab-content > .tab-pane {
        display: block !important;
        opacity: 1 !important;
        visibility: visible !important;
    }
    
    body {
        margin: 0;
        padding: 0;
        background-color: #fff;
    }
    
    .table {
        width: 100% !important;
        margin-bottom: 1rem;
        background-color: transparent;
        border-collapse: collapse;
    }
    
    .table th, .table td {
        padding: 0.75rem;
        vertical-align: top;
        border-top: 1px solid #dee2e6;
    }
    
    .table thead th {
        vertical-align: bottom;
        border-bottom: 2px solid #dee2e6;
    }
    
    .table tbody + tbody {
        border-top: 2px solid #dee2e6;
    }
    
    .table .table {
        background-color: #fff;
    }
    
    .table-bordered {
        border: 1px solid #dee2e6;
    }
    
    .table-bordered th, .table-bordered td {
        border: 1px solid #dee2e6;
    }
    
    .table-bordered thead th, .table-bordered thead td {
        border-bottom-width: 2px;
    }
    
    .chart {
        page-break-inside: avoid;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('현금 흐름 보고서 페이지 로드됨');
    
    <?php if (!empty($cashflowData)): ?>
    // 수입 & 지출 차트
    const incomeExpenseCtx = document.getElementById('incomeExpenseChart').getContext('2d');
    const incomeExpenseChart = new Chart(incomeExpenseCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($labels); ?>,
            datasets: [
                {
                    label: '수입 (NPR)',
                    data: <?php echo json_encode($incomeValues); ?>,
                    backgroundColor: 'rgba(40, 167, 69, 0.7)',
                    borderColor: '#28a745',
                    borderWidth: 1
                },
                {
                    label: '지출 (NPR)',
                    data: <?php echo json_encode($expenseValues); ?>,
                    backgroundColor: 'rgba(220, 53, 69, 0.7)',
                    borderColor: '#dc3545',
                    borderWidth: 1
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
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            label += context.parsed.y.toLocaleString() + ' NPR';
                            return label;
                        }
                    }
                }
            }
        }
    });
    
    // 순 현금 흐름 차트
    const netFlowCtx = document.getElementById('netFlowChart').getContext('2d');
    const netFlowChart = new Chart(netFlowCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($labels); ?>,
            datasets: [
                {
                    label: '순 현금 흐름 (NPR)',
                    data: <?php echo json_encode($netCashFlowValues); ?>,
                    backgroundColor: function(context) {
                        const value = context.dataset.data[context.dataIndex];
                        return value >= 0 ? 'rgba(23, 162, 184, 0.7)' : 'rgba(255, 193, 7, 0.7)';
                    },
                    borderColor: function(context) {
                        const value = context.dataset.data[context.dataIndex];
                        return value >= 0 ? '#17a2b8' : '#ffc107';
                    },
                    borderWidth: 1
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
                    beginAtZero: false,
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
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            label += context.parsed.y.toLocaleString() + ' NPR';
                            return label;
                        }
                    }
                }
            }
        }
    });
    
    // 누적 현금 흐름 차트
    const cumulativeFlowCtx = document.getElementById('cumulativeFlowChart').getContext('2d');
    const cumulativeFlowChart = new Chart(cumulativeFlowCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($labels); ?>,
            datasets: [
                {
                    label: '누적 현금 흐름 (NPR)',
                    data: <?php echo json_encode($cumulativeNetCashFlow); ?>,
                    backgroundColor: 'rgba(0, 123, 255, 0.2)',
                    borderColor: '#007bff',
                    borderWidth: 2,
                    fill: true,
                    pointRadius: 3,
                    pointBackgroundColor: '#007bff',
                    pointBorderColor: '#fff',
                    pointHoverRadius: 5,
                    pointHoverBackgroundColor: '#007bff',
                    pointHoverBorderColor: '#fff'
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
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            label += context.parsed.y.toLocaleString() + ' NPR';
                            return label;
                        }
                    }
                }
            }
        }
    });
    <?php endif; ?>
    
    // 프린트 버튼 이벤트
    document.getElementById('printBtn').addEventListener('click', function() {
        window.print();
    });
    
    // 엑셀 내보내기 버튼 이벤트
    document.getElementById('exportBtn').addEventListener('click', function() {
        // 현재 URL 파라미터에 export=excel 추가하여 리디렉션
        let currentUrl = window.location.href;
        let exportUrl = currentUrl + (currentUrl.includes('?') ? '&' : '?') + 'export=excel';
        
        // 새 창 또는 다운로드
        window.location.href = exportUrl;
    });
});
</script>

<?php
// 푸터 포함
include '../../templates/footer.php';
?>