<?php
/**
 * 재무 관리 - 매출 보고서 페이지
 * 
 * 이 페이지는 기간별, 판매점별, 복권 종류별 매출 데이터 분석 및 보고서를 제공합니다.
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
$groupBy = isset($_GET['group_by']) ? sanitizeInput($_GET['group_by']) : 'day';
$lotteryType = isset($_GET['lottery_type']) ? sanitizeInput($_GET['lottery_type']) : '';
$storeId = isset($_GET['store_id']) ? intval($_GET['store_id']) : 0;

// 날짜 범위가 유효한지 확인
if (strtotime($startDate) > strtotime($endDate)) {
    $temp = $startDate;
    $startDate = $endDate;
    $endDate = $temp;
}

// 유효한 그룹화 옵션인지 확인
$validGroupByOptions = ['day', 'week', 'month', 'quarter', 'year'];
if (!in_array($groupBy, $validGroupByOptions)) {
    $groupBy = 'day';
}

// 복권 종류 목록 조회
$lotteryTypesSql = "SELECT id, lottery_name FROM lottery_types WHERE is_active = 1 ORDER BY lottery_name";
$lotteryTypesResult = $conn->query($lotteryTypesSql);
$lotteryTypes = [];

while ($row = $lotteryTypesResult->fetch_assoc()) {
    $lotteryTypes[] = $row;
}

// 판매점 목록 조회 (매출 기준 상위 50개)
$storesSql = "SELECT s.id, s.store_name, s.store_code
            FROM stores s
            JOIN sales_transactions st ON s.id = st.store_id
            WHERE s.status = 'active'
            GROUP BY s.id
            ORDER BY SUM(st.total_amount) DESC
            LIMIT 50";
$storesResult = $conn->query($storesSql);
$stores = [];

while ($row = $storesResult->fetch_assoc()) {
    $stores[] = $row;
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

// 매출 데이터 조회 쿼리 구성
$salesSql = "SELECT 
            {$dateLabel} as date_group,
            COUNT(*) as transaction_count,
            SUM(ticket_quantity) as ticket_count,
            SUM(total_amount) as total_sales,
            SUM(commission_amount) as total_commission
            FROM sales_transactions
            WHERE transaction_date BETWEEN ? AND ?
            AND status = 'completed'";

$params = [$startDate, $endDate];
$paramTypes = "ss";

// 필터 조건 추가
if (!empty($lotteryType)) {
    $salesSql .= " AND lottery_type_id = ?";
    $params[] = $lotteryType;
    $paramTypes .= "i";
}

if ($storeId > 0) {
    $salesSql .= " AND store_id = ?";
    $params[] = $storeId;
    $paramTypes .= "i";
}

// 그룹화 및 정렬
$salesSql .= " GROUP BY date_group
              ORDER BY MIN(transaction_date)";

// 쿼리 실행
$salesStmt = $conn->prepare($salesSql);
$salesStmt->bind_param($paramTypes, ...$params);
$salesStmt->execute();
$salesResult = $salesStmt->get_result();

// 데이터 포맷팅
$salesData = [];
$labels = [];
$salesValues = [];
$ticketCountValues = [];
$transactionCountValues = [];
$commissionValues = [];

$totalSales = 0;
$totalTickets = 0;
$totalTransactions = 0;
$totalCommission = 0;

while ($row = $salesResult->fetch_assoc()) {
    $salesData[] = $row;
    
    // 차트 데이터 구성
    $labels[] = $row['date_group'];
    $salesValues[] = $row['total_sales'];
    $ticketCountValues[] = $row['ticket_count'];
    $transactionCountValues[] = $row['transaction_count'];
    $commissionValues[] = $row['total_commission'];
    
    // 합계 계산
    $totalSales += $row['total_sales'];
    $totalTickets += $row['ticket_count'];
    $totalTransactions += $row['transaction_count'];
    $totalCommission += $row['total_commission'];
}

// 복권 종류별 매출 비중 조회
$lotteryTypeSalesSql = "SELECT 
                      lt.lottery_name,
                      COUNT(st.id) as transaction_count,
                      SUM(st.ticket_quantity) as ticket_count,
                      SUM(st.total_amount) as total_sales,
                      ROUND((SUM(st.total_amount) / (SELECT SUM(total_amount) FROM sales_transactions 
                                                  WHERE transaction_date BETWEEN ? AND ? 
                                                  AND status = 'completed')) * 100, 2) as percentage
                      FROM sales_transactions st
                      JOIN lottery_types lt ON st.lottery_type_id = lt.id
                      WHERE st.transaction_date BETWEEN ? AND ?
                      AND st.status = 'completed'";

$ltParams = [$startDate, $endDate, $startDate, $endDate];
$ltParamTypes = "ssss";

// 필터 조건 추가
if ($storeId > 0) {
    $lotteryTypeSalesSql .= " AND st.store_id = ?";
    $ltParams[] = $storeId;
    $ltParamTypes .= "i";
}

$lotteryTypeSalesSql .= " GROUP BY lt.id
                        ORDER BY total_sales DESC";

// 쿼리 실행
$lotteryTypeSalesStmt = $conn->prepare($lotteryTypeSalesSql);
$lotteryTypeSalesStmt->bind_param($ltParamTypes, ...$ltParams);
$lotteryTypeSalesStmt->execute();
$lotteryTypeSalesResult = $lotteryTypeSalesStmt->get_result();

// 데이터 포맷팅
$lotteryTypeSalesData = [];
$lotteryLabels = [];
$lotteryValues = [];

while ($row = $lotteryTypeSalesResult->fetch_assoc()) {
    $lotteryTypeSalesData[] = $row;
    
    // 차트 데이터 구성
    $lotteryLabels[] = $row['lottery_name'];
    $lotteryValues[] = $row['total_sales'];
}

// 상위 판매점 매출 조회
$topStoresSql = "SELECT 
               s.store_name,
               s.store_code,
               COUNT(st.id) as transaction_count,
               SUM(st.ticket_quantity) as ticket_count,
               SUM(st.total_amount) as total_sales,
               ROUND((SUM(st.total_amount) / (SELECT SUM(total_amount) FROM sales_transactions 
                                          WHERE transaction_date BETWEEN ? AND ? 
                                          AND status = 'completed')) * 100, 2) as percentage
               FROM sales_transactions st
               JOIN stores s ON st.store_id = s.id
               WHERE st.transaction_date BETWEEN ? AND ?
               AND st.status = 'completed'";

$tsParams = [$startDate, $endDate, $startDate, $endDate];
$tsParamTypes = "ssss";

// 필터 조건 추가
if (!empty($lotteryType)) {
    $topStoresSql .= " AND st.lottery_type_id = ?";
    $tsParams[] = $lotteryType;
    $tsParamTypes .= "i";
}

$topStoresSql .= " GROUP BY s.id
                ORDER BY total_sales DESC
                LIMIT 10";

// 쿼리 실행
$topStoresStmt = $conn->prepare($topStoresSql);
$topStoresStmt->bind_param($tsParamTypes, ...$tsParams);
$topStoresStmt->execute();
$topStoresResult = $topStoresStmt->get_result();

// 데이터 포맷팅
$topStoresData = [];
$storeLabels = [];
$storeValues = [];

while ($row = $topStoresResult->fetch_assoc()) {
    $topStoresData[] = $row;
    
    // 차트 데이터 구성
    $storeLabels[] = $row['store_name'] . ' (' . $row['store_code'] . ')';
    $storeValues[] = $row['total_sales'];
}

// 페이지 제목 설정
$pageTitle = "매출 보고서";
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
                        <li class="breadcrumb-item active">매출 보고서</li>
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
                                            <label for="lottery_type">복권 종류</label>
                                            <select class="form-control" id="lottery_type" name="lottery_type">
                                                <option value="">모든 복권</option>
                                                <?php foreach ($lotteryTypes as $type): ?>
                                                <option value="<?php echo $type['id']; ?>" <?php if ($lotteryType == $type['id']) echo 'selected'; ?>>
                                                    <?php echo htmlspecialchars($type['lottery_name']); ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="store_id">판매점</label>
                                            <select class="form-control" id="store_id" name="store_id">
                                                <option value="0">모든 판매점</option>
                                                <?php foreach ($stores as $store): ?>
                                                <option value="<?php echo $store['id']; ?>" <?php if ($storeId == $store['id']) echo 'selected'; ?>>
                                                    <?php echo htmlspecialchars($store['store_name'] . ' (' . $store['store_code'] . ')'); ?>
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
                                            <a href="reports-sales.php" class="btn btn-default ml-1">
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
            
            <!-- 매출 요약 카드 -->
            <div class="row">
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3><?php echo formatNumber($totalSales, 0); ?> <small>NPR</small></h3>
                            <p>총 매출액</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-success">
                        <div class="inner">
                            <h3><?php echo formatNumber($totalCommission, 0); ?> <small>NPR</small></h3>
                            <p>총 수수료</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-money-bill"></i>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-warning">
                        <div class="inner">
                            <h3><?php echo formatNumber($totalTickets, 0); ?></h3>
                            <p>판매 티켓 수</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-ticket-alt"></i>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-danger">
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
            
            <!-- 매출 추이 차트 -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-chart-line mr-1"></i>
                                매출 추이
                            </h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="chart">
                                <canvas id="salesChart" style="min-height: 300px; height: 300px; max-height: 300px; max-width: 100%;"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 복권 종류별 및 판매점별 차트 -->
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-chart-pie mr-1"></i>
                                복권 종류별 매출 비중
                            </h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if (empty($lotteryTypeSalesData)): ?>
                            <div class="alert alert-info">
                                <i class="icon fas fa-info-circle"></i>
                                선택한 기간에 매출 데이터가 없습니다.
                            </div>
                            <?php else: ?>
                            <div class="chart">
                                <canvas id="lotteryTypePieChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                            </div>
                            <div class="mt-4">
                                <table class="table table-bordered table-sm">
                                    <thead>
                                        <tr>
                                            <th>복권 종류</th>
                                            <th class="text-right">매출액 (NPR)</th>
                                            <th class="text-right">비중 (%)</th>
                                            <th class="text-right">판매 티켓 수</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($lotteryTypeSalesData as $data): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($data['lottery_name']); ?></td>
                                            <td class="text-right"><?php echo formatNumber($data['total_sales'], 2); ?></td>
                                            <td class="text-right"><?php echo formatNumber($data['percentage'], 2); ?>%</td>
                                            <td class="text-right"><?php echo formatNumber($data['ticket_count'], 0); ?></td>
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
                                <i class="fas fa-store mr-1"></i>
                                상위 판매점 매출
                            </h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if (empty($topStoresData)): ?>
                            <div class="alert alert-info">
                                <i class="icon fas fa-info-circle"></i>
                                선택한 기간에 매출 데이터가 없습니다.
                            </div>
                            <?php else: ?>
                            <div class="chart">
                                <canvas id="topStoresChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                            </div>
                            <div class="mt-4">
                                <table class="table table-bordered table-sm">
                                    <thead>
                                        <tr>
                                            <th>판매점</th>
                                            <th class="text-right">매출액 (NPR)</th>
                                            <th class="text-right">비중 (%)</th>
                                            <th class="text-right">거래 건수</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($topStoresData as $data): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($data['store_name'] . ' (' . $data['store_code'] . ')'); ?></td>
                                            <td class="text-right"><?php echo formatNumber($data['total_sales'], 2); ?></td>
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
            
            <!-- 상세 매출 데이터 -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-table mr-1"></i>
                                상세 매출 데이터
                            </h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body table-responsive">
                            <?php if (empty($salesData)): ?>
                            <div class="alert alert-info">
                                <i class="icon fas fa-info-circle"></i>
                                선택한 기간에 매출 데이터가 없습니다.
                            </div>
                            <?php else: ?>
                            <table class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th>기간</th>
                                        <th class="text-right">매출액 (NPR)</th>
                                        <th class="text-right">수수료 (NPR)</th>
                                        <th class="text-right">판매 티켓 수</th>
                                        <th class="text-right">거래 건수</th>
                                        <th class="text-right">티켓당 평균 가격</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($salesData as $data): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($data['date_group']); ?></td>
                                        <td class="text-right"><?php echo formatNumber($data['total_sales'], 2); ?></td>
                                        <td class="text-right"><?php echo formatNumber($data['total_commission'], 2); ?></td>
                                        <td class="text-right"><?php echo formatNumber($data['ticket_count'], 0); ?></td>
                                        <td class="text-right"><?php echo formatNumber($data['transaction_count'], 0); ?></td>
                                        <td class="text-right">
                                            <?php 
                                            $avgTicketPrice = $data['ticket_count'] > 0 ? $data['total_sales'] / $data['ticket_count'] : 0;
                                            echo formatNumber($avgTicketPrice, 2); 
                                            ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="bg-light">
                                        <th>합계</th>
                                        <th class="text-right"><?php echo formatNumber($totalSales, 2); ?></th>
                                        <th class="text-right"><?php echo formatNumber($totalCommission, 2); ?></th>
                                        <th class="text-right"><?php echo formatNumber($totalTickets, 0); ?></th>
                                        <th class="text-right"><?php echo formatNumber($totalTransactions, 0); ?></th>
                                        <th class="text-right">
                                            <?php 
                                            $avgTotalTicketPrice = $totalTickets > 0 ? $totalSales / $totalTickets : 0;
                                            echo formatNumber($avgTotalTicketPrice, 2); 
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
    console.log('매출 보고서 페이지 로드됨');
    
    // 차트 데이터
    const ctx = document.getElementById('salesChart').getContext('2d');
    const salesChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($labels); ?>,
            datasets: [
                {
                    label: '매출액 (NPR)',
                    data: <?php echo json_encode($salesValues); ?>,
                    backgroundColor: 'rgba(60, 141, 188, 0.2)',
                    borderColor: 'rgba(60, 141, 188, 1)',
                    pointRadius: 3,
                    pointBackgroundColor: '#3c8dbc',
                    pointBorderColor: 'rgba(60, 141, 188, 1)',
                    pointHoverRadius: 5,
                    pointHoverBackgroundColor: '#3c8dbc',
                    pointHoverBorderColor: 'rgba(60, 141, 188, 1)',
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
    
    <?php if (!empty($lotteryTypeSalesData)): ?>
    // 복권 종류별 파이 차트
    const pieCtx = document.getElementById('lotteryTypePieChart').getContext('2d');
    const pieChart = new Chart(pieCtx, {
        type: 'pie',
        data: {
            labels: <?php echo json_encode($lotteryLabels); ?>,
            datasets: [
                {
                    data: <?php echo json_encode($lotteryValues); ?>,
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
    
    <?php if (!empty($topStoresData)): ?>
    // 상위 판매점 바 차트
    const storeCtx = document.getElementById('topStoresChart').getContext('2d');
    const storeChart = new Chart(storeCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($storeLabels); ?>,
            datasets: [
                {
                    label: '매출액 (NPR)',
                    data: <?php echo json_encode($storeValues); ?>,
                    backgroundColor: 'rgba(60, 141, 188, 0.7)',
                    borderColor: 'rgba(60, 141, 188, 1)',
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
                legend: {
                    display: false
                },
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
        // 데이터 준비
        let startDate = document.getElementById('start_date').value;
        let endDate = document.getElementById('end_date').value;
        
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