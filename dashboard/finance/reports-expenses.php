<?php
/**
 * 재무 관리 - 비용 보고서 페이지
 * 
 * 이 페이지는 기간별, 카테고리별 비용 데이터 분석 및 보고서를 제공합니다.
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
$startDate = isset($_GET['start_date']) ? sanitizeInput($_GET['start_date']) : date('Y-m-01');
$endDate = isset($_GET['end_date']) ? sanitizeInput($_GET['end_date']) : date('Y-m-d');
$groupBy = isset($_GET['group_by']) ? sanitizeInput($_GET['group_by']) : 'month';
$categoryId = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;
$paymentMethod = isset($_GET['payment_method']) ? sanitizeInput($_GET['payment_method']) : '';

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

// 비용 카테고리 목록 조회
$categoriesSql = "SELECT id, category_name, category_type, description
                FROM financial_categories
                WHERE category_type = 'expense' OR category_type = 'both'
                ORDER BY category_name";
$categoriesResult = $conn->query($categoriesSql);
$categories = [];

while ($row = $categoriesResult->fetch_assoc()) {
    $categories[] = $row;
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

// 비용 데이터 조회 쿼리 구성
$expensesSql = "SELECT 
               {$dateLabel} as date_group,
               COUNT(*) as transaction_count,
               SUM(amount) as total_amount
               FROM financial_transactions
               WHERE transaction_date BETWEEN ? AND ?
               AND transaction_type = 'expense'
               AND status = 'completed'";

$params = [$startDate, $endDate];
$paramTypes = "ss";

// 필터 조건 추가
if ($categoryId > 0) {
    $expensesSql .= " AND category_id = ?";
    $params[] = $categoryId;
    $paramTypes .= "i";
}

if (!empty($paymentMethod)) {
    $expensesSql .= " AND payment_method = ?";
    $params[] = $paymentMethod;
    $paramTypes .= "s";
}

// 그룹화 및 정렬
$expensesSql .= " GROUP BY date_group
                ORDER BY MIN(transaction_date)";

// 쿼리 실행
$expensesStmt = $conn->prepare($expensesSql);
$expensesStmt->bind_param($paramTypes, ...$params);
$expensesStmt->execute();
$expensesResult = $expensesStmt->get_result();

// 데이터 포맷팅
$expensesData = [];
$labels = [];
$expenseValues = [];
$transactionCountValues = [];

$totalExpenses = 0;
$totalTransactions = 0;

while ($row = $expensesResult->fetch_assoc()) {
    $expensesData[] = $row;
    
    // 차트 데이터 구성
    $labels[] = $row['date_group'];
    $expenseValues[] = $row['total_amount'];
    $transactionCountValues[] = $row['transaction_count'];
    
    // 합계 계산
    $totalExpenses += $row['total_amount'];
    $totalTransactions += $row['transaction_count'];
}

// 카테고리별 비용 비중 조회
$categoryExpensesSql = "SELECT 
                      fc.category_name,
                      COUNT(ft.id) as transaction_count,
                      SUM(ft.amount) as total_amount,
                      ROUND((SUM(ft.amount) / (SELECT SUM(amount) FROM financial_transactions 
                                           WHERE transaction_date BETWEEN ? AND ? 
                                           AND transaction_type = 'expense'
                                           AND status = 'completed')) * 100, 2) as percentage
                      FROM financial_transactions ft
                      JOIN financial_categories fc ON ft.category_id = fc.id
                      WHERE ft.transaction_date BETWEEN ? AND ?
                      AND ft.transaction_type = 'expense'
                      AND ft.status = 'completed'";

$catParams = [$startDate, $endDate, $startDate, $endDate];
$catParamTypes = "ssss";

// 필터 조건 추가
if (!empty($paymentMethod)) {
    $categoryExpensesSql .= " AND ft.payment_method = ?";
    $catParams[] = $paymentMethod;
    $catParamTypes .= "s";
}

$categoryExpensesSql .= " GROUP BY fc.id
                        ORDER BY total_amount DESC";

// 쿼리 실행
$categoryExpensesStmt = $conn->prepare($categoryExpensesSql);
$categoryExpensesStmt->bind_param($catParamTypes, ...$catParams);
$categoryExpensesStmt->execute();
$categoryExpensesResult = $categoryExpensesStmt->get_result();

// 데이터 포맷팅
$categoryExpensesData = [];
$categoryLabels = [];
$categoryValues = [];

while ($row = $categoryExpensesResult->fetch_assoc()) {
    $categoryExpensesData[] = $row;
    
    // 차트 데이터 구성
    $categoryLabels[] = $row['category_name'];
    $categoryValues[] = $row['total_amount'];
}

// 지불 방법별 비용 비중 조회
$paymentMethodsSql = "SELECT 
                    payment_method,
                    COUNT(id) as transaction_count,
                    SUM(amount) as total_amount,
                    ROUND((SUM(amount) / (SELECT SUM(amount) FROM financial_transactions 
                                       WHERE transaction_date BETWEEN ? AND ? 
                                       AND transaction_type = 'expense'
                                       AND status = 'completed')) * 100, 2) as percentage
                    FROM financial_transactions
                    WHERE transaction_date BETWEEN ? AND ?
                    AND transaction_type = 'expense'
                    AND status = 'completed'";

$pmParams = [$startDate, $endDate, $startDate, $endDate];
$pmParamTypes = "ssss";

// 필터 조건 추가
if ($categoryId > 0) {
    $paymentMethodsSql .= " AND category_id = ?";
    $pmParams[] = $categoryId;
    $pmParamTypes .= "i";
}

$paymentMethodsSql .= " GROUP BY payment_method
                     ORDER BY total_amount DESC";

// 쿼리 실행
$paymentMethodsStmt = $conn->prepare($paymentMethodsSql);
$paymentMethodsStmt->bind_param($pmParamTypes, ...$pmParams);
$paymentMethodsStmt->execute();
$paymentMethodsResult = $paymentMethodsStmt->get_result();

// 데이터 포맷팅
$paymentMethodsData = [];
$paymentLabels = [];
$paymentValues = [];

// 지불 방법 한글명 매핑
$paymentMethodLabels = [
    'cash' => '현금',
    'bank_transfer' => '계좌이체',
    'check' => '수표',
    'credit_card' => '신용카드',
    'debit_card' => '직불카드',
    'mobile_payment' => '모바일결제',
    'other' => '기타'
];

while ($row = $paymentMethodsResult->fetch_assoc()) {
    // 지불 방법 한글명 설정
    $pmLabel = isset($paymentMethodLabels[$row['payment_method']]) ?
        $paymentMethodLabels[$row['payment_method']] : $row['payment_method'];
    
    $row['payment_method_label'] = $pmLabel;
    $paymentMethodsData[] = $row;
    
    // 차트 데이터 구성
    $paymentLabels[] = $pmLabel;
    $paymentValues[] = $row['total_amount'];
}

// 상위 비용 거래 조회
$topExpensesSql = "SELECT ft.id, ft.transaction_code, ft.amount, ft.transaction_date, 
                 ft.description, ft.payment_method, ft.reference_type, ft.reference_id,
                 fc.category_name
                 FROM financial_transactions ft
                 JOIN financial_categories fc ON ft.category_id = fc.id
                 WHERE ft.transaction_date BETWEEN ? AND ?
                 AND ft.transaction_type = 'expense'
                 AND ft.status = 'completed'";

$teParams = [$startDate, $endDate];
$teParamTypes = "ss";

// 필터 조건 추가
if ($categoryId > 0) {
    $topExpensesSql .= " AND ft.category_id = ?";
    $teParams[] = $categoryId;
    $teParamTypes .= "i";
}

if (!empty($paymentMethod)) {
    $topExpensesSql .= " AND ft.payment_method = ?";
    $teParams[] = $paymentMethod;
    $teParamTypes .= "s";
}

$topExpensesSql .= " ORDER BY ft.amount DESC
                  LIMIT 10";

// 쿼리 실행
$topExpensesStmt = $conn->prepare($topExpensesSql);
$topExpensesStmt->bind_param($teParamTypes, ...$teParams);
$topExpensesStmt->execute();
$topExpensesResult = $topExpensesStmt->get_result();

// 데이터 포맷팅
$topExpensesData = [];

while ($row = $topExpensesResult->fetch_assoc()) {
    // 지불 방법 한글명 설정
    $row['payment_method_label'] = isset($paymentMethodLabels[$row['payment_method']]) ?
        $paymentMethodLabels[$row['payment_method']] : $row['payment_method'];
    
    $topExpensesData[] = $row;
}

// 페이지 제목 설정
$pageTitle = "비용 보고서";
$currentSection = "finance";
$currentPage = "reports";

// 헤더 및 네비게이션 포함
include '../../templates/header.php';
include '../../templates/navbar.php';

// 숫자 포맷 함수
function formatNumber($number, $decimals = 2) {
    return number_format($number, $decimals);
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
                        <li class="breadcrumb-item active">비용 보고서</li>
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
                                            <label for="category_id">비용 카테고리</label>
                                            <select class="form-control" id="category_id" name="category_id">
                                                <option value="0">모든 카테고리</option>
                                                <?php foreach ($categories as $category): ?>
                                                <option value="<?php echo $category['id']; ?>" <?php if ($categoryId == $category['id']) echo 'selected'; ?>>
                                                    <?php echo htmlspecialchars($category['category_name']); ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="payment_method">지불 방법</label>
                                            <select class="form-control" id="payment_method" name="payment_method">
                                                <option value="">모든 방법</option>
                                                <?php foreach ($paymentMethodLabels as $key => $label): ?>
                                                <option value="<?php echo $key; ?>" <?php if ($paymentMethod === $key) echo 'selected'; ?>>
                                                    <?php echo $label; ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-9 d-flex align-items-end">
                                        <div class="form-group mb-0">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-search mr-1"></i> 적용
                                            </button>
                                            <a href="reports-expenses.php" class="btn btn-default ml-1">
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
            
            <!-- 비용 요약 카드 -->
            <div class="row">
                <div class="col-lg-6 col-12">
                    <div class="small-box bg-danger">
                        <div class="inner">
                            <h3><?php echo formatNumber($totalExpenses, 0); ?> <small>NPR</small></h3>
                            <p>총 지출액</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-6 col-12">
                    <div class="small-box bg-warning">
                        <div class="inner">
                            <h3><?php echo formatNumber($totalTransactions, 0); ?></h3>
                            <p>총 거래 건수</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-file-invoice"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 비용 추이 차트 -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-chart-line mr-1"></i>
                                비용 추이
                            </h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if (empty($expensesData)): ?>
                            <div class="alert alert-info">
                                <i class="icon fas fa-info-circle"></i>
                                선택한 기간에 비용 데이터가 없습니다.
                            </div>
                            <?php else: ?>
                            <div class="chart">
                                <canvas id="expensesChart" style="min-height: 300px; height: 300px; max-height: 300px; max-width: 100%;"></canvas>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 카테고리별 및 지불 방법별 차트 -->
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-chart-pie mr-1"></i>
                                카테고리별 비용 비중
                            </h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if (empty($categoryExpensesData)): ?>
                            <div class="alert alert-info">
                                <i class="icon fas fa-info-circle"></i>
                                선택한 기간에 비용 데이터가 없습니다.
                            </div>
                            <?php else: ?>
                            <div class="chart">
                                <canvas id="categoryPieChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                            </div>
                            <div class="mt-4">
                                <table class="table table-bordered table-sm">
                                    <thead>
                                        <tr>
                                            <th>카테고리</th>
                                            <th class="text-right">금액 (NPR)</th>
                                            <th class="text-right">비중 (%)</th>
                                            <th class="text-right">거래 건수</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($categoryExpensesData as $data): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($data['category_name']); ?></td>
                                            <td class="text-right"><?php echo formatNumber($data['total_amount'], 2); ?></td>
                                            <td class="text-right"><?php echo formatNumber($data['percentage'], 2); ?>%</td>
                                            <td class="text-right"><?php echo formatNumber($data['transaction_count'], 0); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-credit-card mr-1"></i>
                                지불 방법별 비용 비중
                            </h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if (empty($paymentMethodsData)): ?>
                            <div class="alert alert-info">
                                <i class="icon fas fa-info-circle"></i>
                                선택한 기간에 비용 데이터가 없습니다.
                            </div>
                            <?php else: ?>
                            <div class="chart">
                                <canvas id="paymentPieChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                            </div>
                            <div class="mt-4">
                                <table class="table table-bordered table-sm">
                                    <thead>
                                        <tr>
                                            <th>지불 방법</th>
                                            <th class="text-right">금액 (NPR)</th>
                                            <th class="text-right">비중 (%)</th>
                                            <th class="text-right">거래 건수</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($paymentMethodsData as $data): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($data['payment_method_label']); ?></td>
                                            <td class="text-right"><?php echo formatNumber($data['total_amount'], 2); ?></td>
                                            <td class="text-right"><?php echo formatNumber($data['percentage'], 2); ?>%</td>
                                            <td class="text-right"><?php echo formatNumber($data['transaction_count'], 0); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 상위 비용 거래 -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-list mr-1"></i>
                                상위 비용 거래
                            </h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if (empty($topExpensesData)): ?>
                            <div class="alert alert-info">
                                <i class="icon fas fa-info-circle"></i>
                                선택한 기간에 비용 데이터가 없습니다.
                            </div>
                            <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <th>거래 코드</th>
                                            <th>날짜</th>
                                            <th>카테고리</th>
                                            <th>설명</th>
                                            <th>지불 방법</th>
                                            <th class="text-right">금액 (NPR)</th>
                                            <th>액션</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($topExpensesData as $expense): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($expense['transaction_code']); ?></td>
                                            <td><?php echo date('Y-m-d', strtotime($expense['transaction_date'])); ?></td>
                                            <td><?php echo htmlspecialchars($expense['category_name']); ?></td>
                                            <td><?php echo htmlspecialchars($expense['description'] ?: '-'); ?></td>
                                            <td><?php echo htmlspecialchars($expense['payment_method_label']); ?></td>
                                            <td class="text-right"><?php echo formatNumber($expense['amount'], 2); ?></td>
                                            <td>
                                                <a href="../transaction-details.php?id=<?php echo $expense['id']; ?>" class="btn btn-xs btn-info">
                                                    <i class="fas fa-eye"></i> 상세
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 상세 비용 데이터 -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-table mr-1"></i>
                                상세 비용 데이터
                            </h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body table-responsive">
                            <?php if (empty($expensesData)): ?>
                            <div class="alert alert-info">
                                <i class="icon fas fa-info-circle"></i>
                                선택한 기간에 비용 데이터가 없습니다.
                            </div>
                            <?php else: ?>
                            <table class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th>기간</th>
                                        <th class="text-right">비용액 (NPR)</th>
                                        <th class="text-right">거래 건수</th>
                                        <th class="text-right">거래당 평균 비용</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($expensesData as $data): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($data['date_group']); ?></td>
                                        <td class="text-right"><?php echo formatNumber($data['total_amount'], 2); ?></td>
                                        <td class="text-right"><?php echo formatNumber($data['transaction_count'], 0); ?></td>
                                        <td class="text-right">
                                            <?php 
                                            $avgExpense = $data['transaction_count'] > 0 ? $data['total_amount'] / $data['transaction_count'] : 0;
                                            echo formatNumber($avgExpense, 2); 
                                            ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="bg-light">
                                        <th>합계</th>
                                        <th class="text-right"><?php echo formatNumber($totalExpenses, 2); ?></th>
                                        <th class="text-right"><?php echo formatNumber($totalTransactions, 0); ?></th>
                                        <th class="text-right">
                                            <?php 
                                            $avgTotalExpense = $totalTransactions > 0 ? $totalExpenses / $totalTransactions : 0;
                                            echo formatNumber($avgTotalExpense, 2); 
                                            ?>
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
    .no-print, .main-header, .main-sidebar, .content-header, .card-tools, .main-footer {
        display: none !important;
    }
    
    .content-wrapper, .card {
        margin: 0 !important;
        padding: 0 !important;
        border: none !important;
        box-shadow: none !important;
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
    console.log('비용 보고서 페이지 로드됨');
    
    <?php if (!empty($expensesData)): ?>
    // 비용 추이 차트
    const ctx = document.getElementById('expensesChart').getContext('2d');
    const expensesChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($labels); ?>,
            datasets: [
                {
                    label: '비용액 (NPR)',
                    data: <?php echo json_encode($expenseValues); ?>,
                    backgroundColor: 'rgba(231, 74, 59, 0.2)',
                    borderColor: '#e74a3b',
                    pointRadius: 3,
                    pointBackgroundColor: '#e74a3b',
                    pointBorderColor: '#fff',
                    pointHoverRadius: 5,
                    pointHoverBackgroundColor: '#e74a3b',
                    pointHoverBorderColor: '#fff',
                    fill: true
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
    <?php endif; ?>
    
    <?php if (!empty($categoryExpensesData)): ?>
    // 카테고리별 파이 차트
    const categoryCtx = document.getElementById('categoryPieChart').getContext('2d');
    const categoryChart = new Chart(categoryCtx, {
        type: 'pie',
        data: {
            labels: <?php echo json_encode($categoryLabels); ?>,
            datasets: [
                {
                    data: <?php echo json_encode($categoryValues); ?>,
                    backgroundColor: [
                        '#f56954', '#00a65a', '#f39c12', '#00c0ef', '#3c8dbc', '#d2d6de',
                        '#6610f2', '#fd7e14', '#20c997', '#6c757d', '#e83e8c', '#17a2b8'
                    ]
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.label || '';
                            let value = context.raw || 0;
                            let total = context.dataset.data.reduce((a, b) => a + b, 0);
                            let percentage = Math.round((value / total) * 100);
                            
                            return label + ': ' + value.toLocaleString() + ' NPR (' + percentage + '%)';
                        }
                    }
                }
            }
        }
    });
    <?php endif; ?>
    
    <?php if (!empty($paymentMethodsData)): ?>
    // 지불 방법별 파이 차트
    const paymentCtx = document.getElementById('paymentPieChart').getContext('2d');
    const paymentChart = new Chart(paymentCtx, {
        type: 'pie',
        data: {
            labels: <?php echo json_encode($paymentLabels); ?>,
            datasets: [
                {
                    data: <?php echo json_encode($paymentValues); ?>,
                    backgroundColor: [
                        '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#858796',
                        '#5a5c69', '#fd7e14', '#20c997', '#6c757d', '#e83e8c', '#17a2b8'
                    ]
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.label || '';
                            let value = context.raw || 0;
                            let total = context.dataset.data.reduce((a, b) => a + b, 0);
                            let percentage = Math.round((value / total) * 100);
                            
                            return label + ': ' + value.toLocaleString() + ' NPR (' + percentage + '%)';
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