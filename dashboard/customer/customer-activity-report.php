<?php
/**
 * Customer Activity Report Page
 * 
 * This page generates and displays various reports and analytics
 * about customer activities, including purchase patterns, demographics,
 * and other relevant metrics.
 */

// Include required files
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

// Check if user has access to this page
checkPageAccess('customer_management');

// Page title and metadata
$pageTitle = "고객 활동 보고서";
$pageDescription = "고객 활동에 대한 다양한 보고서 및 분석 정보";
$activeMenu = "customer";
$activeSubMenu = "customer-activity-report";

// Initialize variables
$reportType = isset($_GET['type']) ? sanitizeInput($_GET['type']) : 'purchase_patterns';
$startDate = isset($_GET['start_date']) ? sanitizeInput($_GET['start_date']) : date('Y-m-d', strtotime('-30 days'));
$endDate = isset($_GET['end_date']) ? sanitizeInput($_GET['end_date']) : date('Y-m-d');
$reportData = [];

// Database connection
$db = getDbConnection();

// Generate report based on type
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    switch ($reportType) {
        case 'purchase_patterns':
            // Get purchase patterns by day of week
            $sql = "
                SELECT 
                    DAYNAME(transaction_date) as day_of_week,
                    COUNT(*) as transaction_count,
                    SUM(amount) as total_amount
                FROM customer_transactions
                WHERE 
                    transaction_type = 'purchase'
                    AND transaction_date BETWEEN ? AND ?
                GROUP BY DAYNAME(transaction_date)
                ORDER BY FIELD(
                    DAYNAME(transaction_date), 
                    'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'
                )
            ";
            
            $stmt = $db->prepare($sql);
            $stmt->bind_param("ss", $startDate, $endDate);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $dayOfWeekData = [];
            while ($row = $result->fetch_assoc()) {
                $dayOfWeekData[] = $row;
            }
            $reportData['day_of_week'] = $dayOfWeekData;
            
            // Get purchase patterns by hour of day
            $sql = "
                SELECT 
                    HOUR(transaction_date) as hour_of_day,
                    COUNT(*) as transaction_count,
                    SUM(amount) as total_amount
                FROM customer_transactions
                WHERE 
                    transaction_type = 'purchase'
                    AND transaction_date BETWEEN ? AND ?
                GROUP BY HOUR(transaction_date)
                ORDER BY hour_of_day
            ";
            
            $stmt = $db->prepare($sql);
            $stmt->bind_param("ss", $startDate, $endDate);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $hourOfDayData = [];
            while ($row = $result->fetch_assoc()) {
                $hourOfDayData[] = $row;
            }
            $reportData['hour_of_day'] = $hourOfDayData;
            
            // Get purchase patterns by day of month
            $sql = "
                SELECT 
                    DAY(transaction_date) as day_of_month,
                    COUNT(*) as transaction_count,
                    SUM(amount) as total_amount
                FROM customer_transactions
                WHERE 
                    transaction_type = 'purchase'
                    AND transaction_date BETWEEN ? AND ?
                GROUP BY DAY(transaction_date)
                ORDER BY day_of_month
            ";
            
            $stmt = $db->prepare($sql);
            $stmt->bind_param("ss", $startDate, $endDate);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $dayOfMonthData = [];
            while ($row = $result->fetch_assoc()) {
                $dayOfMonthData[] = $row;
            }
            $reportData['day_of_month'] = $dayOfMonthData;
            break;
            
        case 'customer_demographics':
            // Get customer count by city
            $sql = "
                SELECT 
                    city,
                    COUNT(*) as customer_count
                FROM customers
                WHERE registration_date BETWEEN ? AND ?
                GROUP BY city
                ORDER BY customer_count DESC
                LIMIT 10
            ";
            
            $stmt = $db->prepare($sql);
            $stmt->bind_param("ss", $startDate, $endDate);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $cityData = [];
            while ($row = $result->fetch_assoc()) {
                $cityData[] = $row;
            }
            $reportData['city'] = $cityData;
            
            // Get customer count by registration month
            $sql = "
                SELECT 
                    DATE_FORMAT(registration_date, '%Y-%m') as registration_month,
                    COUNT(*) as customer_count
                FROM customers
                WHERE registration_date BETWEEN ? AND ?
                GROUP BY DATE_FORMAT(registration_date, '%Y-%m')
                ORDER BY registration_month
            ";
            
            $stmt = $db->prepare($sql);
            $stmt->bind_param("ss", $startDate, $endDate);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $registrationMonthData = [];
            while ($row = $result->fetch_assoc()) {
                $registrationMonthData[] = $row;
            }
            $reportData['registration_month'] = $registrationMonthData;
            
            // Get customer verification status
            $sql = "
                SELECT 
                    verification_status,
                    COUNT(*) as customer_count
                FROM customers
                WHERE registration_date BETWEEN ? AND ?
                GROUP BY verification_status
            ";
            
            $stmt = $db->prepare($sql);
            $stmt->bind_param("ss", $startDate, $endDate);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $verificationStatusData = [];
            while ($row = $result->fetch_assoc()) {
                $verificationStatusData[] = $row;
            }
            $reportData['verification_status'] = $verificationStatusData;
            break;
            
        case 'customer_value':
            // Get top 10 customers by purchase amount
            $sql = "
                SELECT 
                    c.id,
                    c.customer_code,
                    c.first_name,
                    c.last_name,
                    COUNT(ct.id) as transaction_count,
                    SUM(ct.amount) as total_amount
                FROM customers c
                JOIN customer_transactions ct ON c.id = ct.customer_id
                WHERE 
                    ct.transaction_type = 'purchase'
                    AND ct.transaction_date BETWEEN ? AND ?
                GROUP BY c.id
                ORDER BY total_amount DESC
                LIMIT 10
            ";
            
            $stmt = $db->prepare($sql);
            $stmt->bind_param("ss", $startDate, $endDate);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $topCustomersData = [];
            while ($row = $result->fetch_assoc()) {
                $topCustomersData[] = $row;
            }
            $reportData['top_customers'] = $topCustomersData;
            
            // Get average purchase amount by customer status
            $sql = "
                SELECT 
                    c.status,
                    COUNT(DISTINCT c.id) as customer_count,
                    COUNT(ct.id) as transaction_count,
                    AVG(ct.amount) as avg_amount,
                    SUM(ct.amount) as total_amount
                FROM customers c
                JOIN customer_transactions ct ON c.id = ct.customer_id
                WHERE 
                    ct.transaction_type = 'purchase'
                    AND ct.transaction_date BETWEEN ? AND ?
                GROUP BY c.status
            ";
            
            $stmt = $db->prepare($sql);
            $stmt->bind_param("ss", $startDate, $endDate);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $statusData = [];
            while ($row = $result->fetch_assoc()) {
                $statusData[] = $row;
            }
            $reportData['customer_status'] = $statusData;
            
            // Get purchase frequency distribution
            $sql = "
                SELECT 
                    purchase_count,
                    COUNT(*) as customer_count
                FROM (
                    SELECT 
                        c.id,
                        COUNT(ct.id) as purchase_count
                    FROM customers c
                    JOIN customer_transactions ct ON c.id = ct.customer_id
                    WHERE 
                        ct.transaction_type = 'purchase'
                        AND ct.transaction_date BETWEEN ? AND ?
                    GROUP BY c.id
                ) as purchase_counts
                GROUP BY purchase_count
                ORDER BY purchase_count
            ";
            
            $stmt = $db->prepare($sql);
            $stmt->bind_param("ss", $startDate, $endDate);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $purchaseFrequencyData = [];
            while ($row = $result->fetch_assoc()) {
                $purchaseFrequencyData[] = $row;
            }
            $reportData['purchase_frequency'] = $purchaseFrequencyData;
            break;
            
        case 'prize_claims':
            // Get prize claims by day of week
            $sql = "
                SELECT 
                    DAYNAME(transaction_date) as day_of_week,
                    COUNT(*) as claim_count,
                    SUM(amount) as total_amount
                FROM customer_transactions
                WHERE 
                    transaction_type = 'prize_claim'
                    AND transaction_date BETWEEN ? AND ?
                GROUP BY DAYNAME(transaction_date)
                ORDER BY FIELD(
                    DAYNAME(transaction_date), 
                    'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'
                )
            ";
            
            $stmt = $db->prepare($sql);
            $stmt->bind_param("ss", $startDate, $endDate);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $claimsByDayData = [];
            while ($row = $result->fetch_assoc()) {
                $claimsByDayData[] = $row;
            }
            $reportData['claims_by_day'] = $claimsByDayData;
            
            // Get top 10 prize claim customers
            $sql = "
                SELECT 
                    c.id,
                    c.customer_code,
                    c.first_name,
                    c.last_name,
                    COUNT(ct.id) as claim_count,
                    SUM(ct.amount) as total_amount
                FROM customers c
                JOIN customer_transactions ct ON c.id = ct.customer_id
                WHERE 
                    ct.transaction_type = 'prize_claim'
                    AND ct.transaction_date BETWEEN ? AND ?
                GROUP BY c.id
                ORDER BY total_amount DESC
                LIMIT 10
            ";
            
            $stmt = $db->prepare($sql);
            $stmt->bind_param("ss", $startDate, $endDate);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $topClaimsData = [];
            while ($row = $result->fetch_assoc()) {
                $topClaimsData[] = $row;
            }
            $reportData['top_claims'] = $topClaimsData;
            
            // Get prize claims by amount range
            $sql = "
                SELECT 
                    CASE
                        WHEN amount <= 10000 THEN '10,000 이하'
                        WHEN amount <= 100000 THEN '10,001 - 100,000'
                        WHEN amount <= 1000000 THEN '100,001 - 1,000,000'
                        WHEN amount <= 10000000 THEN '1,000,001 - 10,000,000'
                        ELSE '10,000,000 초과'
                    END as amount_range,
                    COUNT(*) as claim_count,
                    SUM(amount) as total_amount
                FROM customer_transactions
                WHERE 
                    transaction_type = 'prize_claim'
                    AND transaction_date BETWEEN ? AND ?
                GROUP BY amount_range
                ORDER BY FIELD(
                    amount_range, 
                    '10,000 이하', '10,001 - 100,000', '100,001 - 1,000,000', 
                    '1,000,001 - 10,000,000', '10,000,000 초과'
                )
            ";
            
            $stmt = $db->prepare($sql);
            $stmt->bind_param("ss", $startDate, $endDate);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $claimsByAmountData = [];
            while ($row = $result->fetch_assoc()) {
                $claimsByAmountData[] = $row;
            }
            $reportData['claims_by_amount'] = $claimsByAmountData;
            break;
            
        case 'customer_retention':
            // Get active customer retention rates by month
            $sql = "
                SELECT 
                    DATE_FORMAT(current_month.month_date, '%Y-%m') as month,
                    COUNT(DISTINCT last_month.customer_id) as retained_customers,
                    COUNT(DISTINCT current_month.customer_id) as total_customers,
                    ROUND(COUNT(DISTINCT last_month.customer_id) / COUNT(DISTINCT current_month.customer_id) * 100, 2) as retention_rate
                FROM (
                    SELECT 
                        customer_id,
                        DATE_FORMAT(transaction_date, '%Y-%m-01') as month_date
                    FROM customer_transactions
                    WHERE transaction_date BETWEEN ? AND ?
                    GROUP BY customer_id, DATE_FORMAT(transaction_date, '%Y-%m-01')
                ) as current_month
                LEFT JOIN (
                    SELECT 
                        customer_id,
                        DATE_FORMAT(transaction_date, '%Y-%m-01') as month_date
                    FROM customer_transactions
                    WHERE transaction_date BETWEEN DATE_SUB(?, INTERVAL 1 MONTH) AND DATE_SUB(?, INTERVAL 1 DAY)
                    GROUP BY customer_id, DATE_FORMAT(transaction_date, '%Y-%m-01')
                ) as last_month ON current_month.customer_id = last_month.customer_id
                    AND DATE_FORMAT(current_month.month_date, '%Y-%m') = DATE_FORMAT(DATE_ADD(last_month.month_date, INTERVAL 1 MONTH), '%Y-%m')
                GROUP BY DATE_FORMAT(current_month.month_date, '%Y-%m')
                ORDER BY month
            ";
            
            $stmt = $db->prepare($sql);
            $stmt->bind_param("ssss", $startDate, $endDate, $startDate, $startDate);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $retentionData = [];
            while ($row = $result->fetch_assoc()) {
                $retentionData[] = $row;
            }
            $reportData['retention'] = $retentionData;
            
            // Get customer activity by recency
            $sql = "
                SELECT 
                    recency_group,
                    COUNT(*) as customer_count
                FROM (
                    SELECT 
                        c.id,
                        CASE
                            WHEN DATEDIFF(NOW(), MAX(ct.transaction_date)) <= 7 THEN '최근 7일'
                            WHEN DATEDIFF(NOW(), MAX(ct.transaction_date)) <= 30 THEN '8-30일'
                            WHEN DATEDIFF(NOW(), MAX(ct.transaction_date)) <= 90 THEN '31-90일'
                            WHEN DATEDIFF(NOW(), MAX(ct.transaction_date)) <= 180 THEN '91-180일'
                            ELSE '180일 초과'
                        END as recency_group
                    FROM customers c
                    LEFT JOIN customer_transactions ct ON c.id = ct.customer_id
                    WHERE c.registration_date BETWEEN ? AND ?
                    GROUP BY c.id
                ) as customer_recency
                GROUP BY recency_group
                ORDER BY FIELD(
                    recency_group, 
                    '최근 7일', '8-30일', '31-90일', '91-180일', '180일 초과'
                )
            ";
            
            $stmt = $db->prepare($sql);
            $stmt->bind_param("ss", $startDate, $endDate);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $recencyData = [];
            while ($row = $result->fetch_assoc()) {
                $recencyData[] = $row;
            }
            $reportData['recency'] = $recencyData;
            break;
    }
}

// Include header template
include '../../templates/header.php';
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
        
        <!-- Breadcrumb -->
        <div class="row">
            <div class="col-md-12">
                <ol class="breadcrumb">
                    <li><a href="../../dashboard/"><i class="fa fa-dashboard"></i> 대시보드</a></li>
                    <li><a href="customer-list.php">고객 관리</a></li>
                    <li class="active">고객 활동 보고서</li>
                </ol>
            </div>
        </div>
        
        <!-- Report Filter Form -->
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title">보고서 필터</h3>
                    </div>
                    <div class="panel-body">
                        <form method="get" action="customer-activity-report.php" class="form-horizontal">
                            <div class="form-group">
                                <label class="col-md-2 control-label">보고서 유형:</label>
                                <div class="col-md-4">
                                    <select name="type" class="form-control">
                                        <option value="purchase_patterns" <?php echo ($reportType === 'purchase_patterns') ? 'selected' : ''; ?>>구매 패턴 분석</option>
                                        <option value="customer_demographics" <?php echo ($reportType === 'customer_demographics') ? 'selected' : ''; ?>>고객 인구통계</option>
                                        <option value="customer_value" <?php echo ($reportType === 'customer_value') ? 'selected' : ''; ?>>고객 가치 분석</option>
                                        <option value="prize_claims" <?php echo ($reportType === 'prize_claims') ? 'selected' : ''; ?>>당첨금 수령 분석</option>
                                        <option value="customer_retention" <?php echo ($reportType === 'customer_retention') ? 'selected' : ''; ?>>고객 유지율 분석</option>
                                    </select>
                                </div>
                                
                                <label class="col-md-2 control-label">기간:</label>
                                <div class="col-md-4">
                                    <div class="input-daterange input-group">
                                        <input type="date" class="form-control" name="start_date" value="<?php echo $startDate; ?>">
                                        <span class="input-group-addon">~</span>
                                        <input type="date" class="form-control" name="end_date" value="<?php echo $endDate; ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <div class="col-md-offset-2 col-md-10">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fa fa-search"></i> 보고서 생성
                                    </button>
                                    <a href="customer-activity-report.php" class="btn btn-default">
                                        <i class="fa fa-refresh"></i> 필터 초기화
                                    </a>
                                    <button type="button" class="btn btn-success" id="exportReportBtn">
                                        <i class="fa fa-download"></i> 보고서 내보내기 (Excel)
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if ($reportType === 'purchase_patterns'): ?>
        <!-- Purchase Patterns Report -->
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title">구매 패턴 분석 (<?php echo $startDate; ?> ~ <?php echo $endDate; ?>)</h3>
                    </div>
                    <div class="panel-body">
                        <!-- Day of Week Analysis -->
                        <div class="row">
                            <div class="col-md-6">
                                <h4>요일별 구매 패턴</h4>
                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered">
                                        <thead>
                                            <tr>
                                                <th>요일</th>
                                                <th>거래 수</th>
                                                <th>총 금액</th>
                                                <th>평균 금액</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($reportData['day_of_week'] as $day): ?>
                                            <tr>
                                                <td>
                                                    <?php 
                                                    $dayNames = [
                                                        'Monday' => '월요일', 
                                                        'Tuesday' => '화요일', 
                                                        'Wednesday' => '수요일', 
                                                        'Thursday' => '목요일', 
                                                        'Friday' => '금요일', 
                                                        'Saturday' => '토요일', 
                                                        'Sunday' => '일요일'
                                                    ];
                                                    echo isset($dayNames[$day['day_of_week']]) 
                                                        ? $dayNames[$day['day_of_week']] 
                                                        : $day['day_of_week']; 
                                                    ?>
                                                </td>
                                                <td><?php echo number_format($day['transaction_count']); ?></td>
                                                <td><?php echo number_format($day['total_amount'], 2); ?></td>
                                                <td>
                                                    <?php 
                                                    $avgAmount = $day['transaction_count'] > 0 
                                                        ? $day['total_amount'] / $day['transaction_count'] 
                                                        : 0;
                                                    echo number_format($avgAmount, 2); 
                                                    ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                            <?php if (empty($reportData['day_of_week'])): ?>
                                            <tr>
                                                <td colspan="4" class="text-center">데이터가 없습니다.</td>
                                            </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <canvas id="dayOfWeekChart" width="100%" height="300"></canvas>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <!-- Hour of Day Analysis -->
                        <div class="row">
                            <div class="col-md-6">
                                <h4>시간대별 구매 패턴</h4>
                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered">
                                        <thead>
                                            <tr>
                                                <th>시간대</th>
                                                <th>거래 수</th>
                                                <th>총 금액</th>
                                                <th>평균 금액</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($reportData['hour_of_day'] as $hour): ?>
                                            <tr>
                                                <td><?php echo sprintf('%02d:00 - %02d:59', $hour['hour_of_day'], $hour['hour_of_day']); ?></td>
                                                <td><?php echo number_format($hour['transaction_count']); ?></td>
                                                <td><?php echo number_format($hour['total_amount'], 2); ?></td>
                                                <td>
                                                    <?php 
                                                    $avgAmount = $hour['transaction_count'] > 0 
                                                        ? $hour['total_amount'] / $hour['transaction_count'] 
                                                        : 0;
                                                    echo number_format($avgAmount, 2); 
                                                    ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                            <?php if (empty($reportData['hour_of_day'])): ?>
                                            <tr>
                                                <td colspan="4" class="text-center">데이터가 없습니다.</td>
                                            </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <canvas id="hourOfDayChart" width="100%" height="300"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($reportType === 'customer_demographics'): ?>
        <!-- Customer Demographics Report -->
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title">고객 인구통계 분석 (<?php echo $startDate; ?> ~ <?php echo $endDate; ?>)</h3>
                    </div>
                    <div class="panel-body">
                        <!-- Top Cities -->
                        <div class="row">
                            <div class="col-md-6">
                                <h4>상위 10개 도시별 고객 수</h4>
                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered">
                                        <thead>
                                            <tr>
                                                <th>도시</th>
                                                <th>고객 수</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($reportData['city'] as $city): ?>
                                            <tr>
                                                <td><?php echo !empty($city['city']) ? htmlspecialchars($city['city']) : '미지정'; ?></td>
                                                <td><?php echo number_format($city['customer_count']); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                            <?php if (empty($reportData['city'])): ?>
                                            <tr>
                                                <td colspan="2" class="text-center">데이터가 없습니다.</td>
                                            </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <canvas id="cityChart" width="100%" height="300"></canvas>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <!-- Registration Month -->
                        <div class="row">
                            <div class="col-md-6">
                                <h4>월별 신규 고객 등록 추이</h4>
                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered">
                                        <thead>
                                            <tr>
                                                <th>월</th>
                                                <th>고객 수</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($reportData['registration_month'] as $month): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($month['registration_month']); ?></td>
                                                <td><?php echo number_format($month['customer_count']); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                            <?php if (empty($reportData['registration_month'])): ?>
                                            <tr>
                                                <td colspan="2" class="text-center">데이터가 없습니다.</td>
                                            </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <canvas id="registrationMonthChart" width="100%" height="300"></canvas>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <!-- Verification Status -->
                        <div class="row">
                            <div class="col-md-6">
                                <h4>고객 인증 상태 분포</h4>
                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered">
                                        <thead>
                                            <tr>
                                                <th>인증 상태</th>
                                                <th>고객 수</th>
                                                <th>비율</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $totalCustomers = 0;
                                            foreach ($reportData['verification_status'] as $status) {
                                                $totalCustomers += $status['customer_count'];
                                            }
                                            ?>
                                            
                                            <?php foreach ($reportData['verification_status'] as $status): ?>
                                            <tr>
                                                <td>
                                                    <?php 
                                                    $statusNames = [
                                                        'verified' => '인증됨', 
                                                        'unverified' => '미인증'
                                                    ];
                                                    echo isset($statusNames[$status['verification_status']]) 
                                                        ? $statusNames[$status['verification_status']] 
                                                        : $status['verification_status']; 
                                                    ?>
                                                </td>
                                                <td><?php echo number_format($status['customer_count']); ?></td>
                                                <td>
                                                    <?php 
                                                    $percentage = $totalCustomers > 0 
                                                        ? ($status['customer_count'] / $totalCustomers) * 100 
                                                        : 0;
                                                    echo number_format($percentage, 2) . '%'; 
                                                    ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                            <?php if (empty($reportData['verification_status'])): ?>
                                            <tr>
                                                <td colspan="3" class="text-center">데이터가 없습니다.</td>
                                            </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <canvas id="verificationStatusChart" width="100%" height="300"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($reportType === 'customer_value'): ?>
        <!-- Customer Value Report -->
        <!-- Similar structure for other report types -->
        <?php endif; ?>
        
        <?php if ($reportType === 'prize_claims'): ?>
        <!-- Prize Claims Report -->
        <!-- Similar structure for other report types -->
        <?php endif; ?>
        
        <?php if ($reportType === 'customer_retention'): ?>
        <!-- Customer Retention Report -->
        <!-- Similar structure for other report types -->
        <?php endif; ?>
        
    </div>
</div>

<!-- Chart.js -->
<script src="../../assets/js/chart.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($reportType === 'purchase_patterns' && !empty($reportData['day_of_week'])): ?>
    // Day of Week Chart
    var dayOfWeekCtx = document.getElementById('dayOfWeekChart').getContext('2d');
    var dayOfWeekData = <?php echo json_encode($reportData['day_of_week']); ?>;
    
    var labels = dayOfWeekData.map(function(item) {
        var dayNames = {
            'Monday': '월요일', 
            'Tuesday': '화요일', 
            'Wednesday': '수요일', 
            'Thursday': '목요일', 
            'Friday': '금요일', 
            'Saturday': '토요일', 
            'Sunday': '일요일'
        };
        return dayNames[item.day_of_week] || item.day_of_week;
    });
    
    var transactionCounts = dayOfWeekData.map(function(item) {
        return item.transaction_count;
    });
    
    var totalAmounts = dayOfWeekData.map(function(item) {
        return item.total_amount;
    });
    
    new Chart(dayOfWeekCtx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: '거래 수',
                    data: transactionCounts,
                    backgroundColor: 'rgba(54, 162, 235, 0.7)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1,
                    yAxisID: 'y-axis-1'
                },
                {
                    label: '총 금액',
                    data: totalAmounts,
                    backgroundColor: 'rgba(255, 99, 132, 0.7)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 1,
                    type: 'line',
                    fill: false,
                    yAxisID: 'y-axis-2'
                }
            ]
        },
        options: {
            responsive: true,
            title: {
                display: true,
                text: '요일별 구매 패턴'
            },
            scales: {
                yAxes: [
                    {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        id: 'y-axis-1',
                        ticks: {
                            beginAtZero: true
                        },
                        scaleLabel: {
                            display: true,
                            labelString: '거래 수'
                        }
                    },
                    {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        id: 'y-axis-2',
                        ticks: {
                            beginAtZero: true
                        },
                        scaleLabel: {
                            display: true,
                            labelString: '총 금액'
                        },
                        gridLines: {
                            drawOnChartArea: false
                        }
                    }
                ]
            }
        }
    });
    <?php endif; ?>
    
    <?php if ($reportType === 'purchase_patterns' && !empty($reportData['hour_of_day'])): ?>
    // Hour of Day Chart
    var hourOfDayCtx = document.getElementById('hourOfDayChart').getContext('2d');
    var hourOfDayData = <?php echo json_encode($reportData['hour_of_day']); ?>;
    
    var hourLabels = hourOfDayData.map(function(item) {
        return item.hour_of_day + ':00';
    });
    
    var hourCounts = hourOfDayData.map(function(item) {
        return item.transaction_count;
    });
    
    new Chart(hourOfDayCtx, {
        type: 'line',
        data: {
            labels: hourLabels,
            datasets: [{
                label: '거래 수',
                data: hourCounts,
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 2,
                tension: 0.1,
                fill: true
            }]
        },
        options: {
            responsive: true,
            title: {
                display: true,
                text: '시간대별 구매 패턴'
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: '거래 수'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: '시간'
                    }
                }
            }
        }
    });
    <?php endif; ?>
    
    <?php if ($reportType === 'customer_demographics' && !empty($reportData['city'])): ?>
    // City Chart
    var cityCtx = document.getElementById('cityChart').getContext('2d');
    var cityData = <?php echo json_encode($reportData['city']); ?>;
    
    var cityLabels = cityData.map(function(item) {
        return item.city || '미지정';
    });
    
    var cityCounts = cityData.map(function(item) {
        return item.customer_count;
    });
    
    new Chart(cityCtx, {
        type: 'pie',
        data: {
            labels: cityLabels,
            datasets: [{
                data: cityCounts,
                backgroundColor: [
                    'rgba(255, 99, 132, 0.7)',
                    'rgba(54, 162, 235, 0.7)',
                    'rgba(255, 206, 86, 0.7)',
                    'rgba(75, 192, 192, 0.7)',
                    'rgba(153, 102, 255, 0.7)',
                    'rgba(255, 159, 64, 0.7)',
                    'rgba(255, 99, 132, 0.5)',
                    'rgba(54, 162, 235, 0.5)',
                    'rgba(255, 206, 86, 0.5)',
                    'rgba(75, 192, 192, 0.5)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            title: {
                display: true,
                text: '도시별 고객 분포'
            }
        }
    });
    <?php endif; ?>
    
    <?php if ($reportType === 'customer_demographics' && !empty($reportData['registration_month'])): ?>
    // Registration Month Chart
    var registrationMonthCtx = document.getElementById('registrationMonthChart').getContext('2d');
    var registrationMonthData = <?php echo json_encode($reportData['registration_month']); ?>;
    
    var monthLabels = registrationMonthData.map(function(item) {
        return item.registration_month;
    });
    
    var monthCounts = registrationMonthData.map(function(item) {
        return item.customer_count;
    });
    
    new Chart(registrationMonthCtx, {
        type: 'bar',
        data: {
            labels: monthLabels,
            datasets: [{
                label: '신규 고객 수',
                data: monthCounts,
                backgroundColor: 'rgba(54, 162, 235, 0.7)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            title: {
                display: true,
                text: '월별 신규 고객 등록 추이'
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: '고객 수'
                    }
                }
            }
        }
    });
    <?php endif; ?>
    
    <?php if ($reportType === 'customer_demographics' && !empty($reportData['verification_status'])): ?>
    // Verification Status Chart
    var verificationStatusCtx = document.getElementById('verificationStatusChart').getContext('2d');
    var verificationStatusData = <?php echo json_encode($reportData['verification_status']); ?>;
    
    var statusLabels = verificationStatusData.map(function(item) {
        var statusNames = {
            'verified': '인증됨', 
            'unverified': '미인증'
        };
        return statusNames[item.verification_status] || item.verification_status;
    });
    
    var statusCounts = verificationStatusData.map(function(item) {
        return item.customer_count;
    });
    
    new Chart(verificationStatusCtx, {
        type: 'doughnut',
        data: {
            labels: statusLabels,
            datasets: [{
                data: statusCounts,
                backgroundColor: [
                    'rgba(75, 192, 192, 0.7)',
                    'rgba(255, 206, 86, 0.7)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            title: {
                display: true,
                text: '고객 인증 상태 분포'
            }
        }
    });
    <?php endif; ?>
    
    // Excel Export Button Event
    document.getElementById('exportReportBtn').addEventListener('click', function() {
        window.location.href = 'customer-activity-report-export.php?type=<?php echo $reportType; ?>&start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>';
    });
});
</script>

<?php
// Include footer template
include '../../templates/footer.php';
?>
