<?php
/**
 * 당첨금 통계 페이지
 * 
 * 이 페이지는 복권 당첨금 관련 통계 및 분석 기능을 제공합니다.
 * - 당첨금 통계 조회
 * - 등수별 당첨 통계
 * - 당첨금 지급 현황
 */

// 필요한 파일 포함
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// 페이지 제목 설정
$pageTitle = "당첨금 통계";
$currentSection = "prize";
$currentPage = "statistics.php";

// 데이터베이스 연결
$conn = getDBConnection();

// 필터링을 위한 기본값 설정
$product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-d', strtotime('-3 months'));
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');

// 복권 상품 목록 가져오기
function getLotteryProducts($conn) {
    $query = "SELECT id, product_code, name FROM lottery_products WHERE status = 'active' ORDER BY name";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// 당첨금 통계 가져오기
function getPrizeStatistics($conn, $product_id = 0, $date_from = '', $date_to = '') {
    // 총 당첨금 통계
    $total_query = "
        SELECT 
            SUM(w.prize_amount) as total_prize_amount,
            COUNT(DISTINCT w.id) as total_winners,
            COUNT(DISTINCT CASE WHEN w.status = 'paid' THEN w.id END) as paid_winners,
            SUM(CASE WHEN w.status = 'paid' THEN w.prize_amount ELSE 0 END) as paid_amount,
            COUNT(DISTINCT CASE WHEN w.status = 'claimed' THEN w.id END) as claimed_winners,
            SUM(CASE WHEN w.status = 'claimed' THEN w.prize_amount ELSE 0 END) as claimed_amount,
            COUNT(DISTINCT CASE WHEN w.status = 'pending' THEN w.id END) as pending_winners,
            SUM(CASE WHEN w.status = 'pending' THEN w.prize_amount ELSE 0 END) as pending_amount
        FROM 
            winnings w
        JOIN 
            tickets t ON w.ticket_id = t.id
        JOIN 
            draws d ON t.product_id = d.product_id
        WHERE 1=1
    ";
    
    $params = [];
    
    if ($product_id > 0) {
        $total_query .= " AND t.product_id = ?";
        $params[] = $product_id;
    }
    
    if (!empty($date_from)) {
        $total_query .= " AND DATE(d.draw_date) >= ?";
        $params[] = $date_from;
    }
    
    if (!empty($date_to)) {
        $total_query .= " AND DATE(d.draw_date) <= ?";
        $params[] = $date_to;
    }
    
    $total_stmt = $conn->prepare($total_query);
    $total_stmt->execute($params);
    $total_stats = $total_stmt->fetch(PDO::FETCH_ASSOC);
    
    // 필요한 필드에 기본값 설정
    if (!$total_stats) {
        $total_stats = [
            'total_prize_amount' => 0,
            'total_winners' => 0,
            'paid_winners' => 0,
            'paid_amount' => 0,
            'claimed_winners' => 0,
            'claimed_amount' => 0,
            'pending_winners' => 0,
            'pending_amount' => 0
        ];
    } else {
        // 필드가 없을 경우 기본값 설정
        $total_stats['total_prize_amount'] = $total_stats['total_prize_amount'] ?? 0;
        $total_stats['total_winners'] = $total_stats['total_winners'] ?? 0;
        $total_stats['paid_winners'] = $total_stats['paid_winners'] ?? 0;
        $total_stats['paid_amount'] = $total_stats['paid_amount'] ?? 0;
        $total_stats['claimed_winners'] = $total_stats['claimed_winners'] ?? 0;
        $total_stats['claimed_amount'] = $total_stats['claimed_amount'] ?? 0;
        $total_stats['pending_winners'] = $total_stats['pending_winners'] ?? 0;
        $total_stats['pending_amount'] = $total_stats['pending_amount'] ?? 0;
    }
    
    // 등수별 당첨 통계
    $tier_query = "
        SELECT 
            w.prize_tier as prize_tier,
            COUNT(w.id) as winners_count,
            SUM(w.prize_amount) as total_amount,
            AVG(w.prize_amount) as avg_amount,
            MAX(w.prize_amount) as max_amount,
            MIN(w.prize_amount) as min_amount
        FROM 
            winnings w
        JOIN 
            tickets t ON w.ticket_id = t.id
        JOIN 
            draws d ON t.product_id = d.product_id
        WHERE 1=1
    ";
    
    $params = [];
    
    if ($product_id > 0) {
        $tier_query .= " AND t.product_id = ?";
        $params[] = $product_id;
    }
    
    if (!empty($date_from)) {
        $tier_query .= " AND DATE(d.draw_date) >= ?";
        $params[] = $date_from;
    }
    
    if (!empty($date_to)) {
        $tier_query .= " AND DATE(d.draw_date) <= ?";
        $params[] = $date_to;
    }
    
    $tier_query .= " GROUP BY w.prize_tier ORDER BY w.prize_tier";
    
    $tier_stmt = $conn->prepare($tier_query);
    $tier_stmt->execute($params);
    $tier_stats = $tier_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 월별 당첨금 통계
    $monthly_query = "
        SELECT 
            DATE_FORMAT(d.draw_date, '%Y-%m') as month,
            COUNT(w.id) as winners_count,
            SUM(w.prize_amount) as total_amount,
            SUM(CASE WHEN w.status = 'paid' THEN w.prize_amount ELSE 0 END) as paid_amount,
            COUNT(DISTINCT d.id) as draws_count
        FROM 
            winnings w
        JOIN 
            tickets t ON w.ticket_id = t.id
        JOIN 
            draws d ON t.product_id = d.product_id
        WHERE 1=1
    ";
    
    $params = [];
    
    if ($product_id > 0) {
        $monthly_query .= " AND t.product_id = ?";
        $params[] = $product_id;
    }
    
    if (!empty($date_from)) {
        $monthly_query .= " AND DATE(d.draw_date) >= ?";
        $params[] = $date_from;
    }
    
    if (!empty($date_to)) {
        $monthly_query .= " AND DATE(d.draw_date) <= ?";
        $params[] = $date_to;
    }
    
    $monthly_query .= " GROUP BY DATE_FORMAT(d.draw_date, '%Y-%m') ORDER BY month";
    
    $monthly_stmt = $conn->prepare($monthly_query);
    $monthly_stmt->execute($params);
    $monthly_stats = $monthly_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 지역별 당첨 통계
    $region_query = "
        SELECT 
            r.region_name as region_name,
            COUNT(w.id) as winners_count,
            SUM(w.prize_amount) as total_amount,
            AVG(w.prize_amount) as avg_amount,
            MAX(w.prize_amount) as max_amount
        FROM 
            winnings w
        JOIN 
            tickets t ON w.ticket_id = t.id
        JOIN 
            terminals tm ON t.terminal_id = tm.id
        JOIN 
            stores s ON tm.store_id = s.id
        JOIN 
            regions r ON s.state = r.region_name
        JOIN 
            draws d ON t.product_id = d.product_id
        WHERE 1=1
    ";
    
    $params = [];
    
    if ($product_id > 0) {
        $region_query .= " AND t.product_id = ?";
        $params[] = $product_id;
    }
    
    if (!empty($date_from)) {
        $region_query .= " AND DATE(d.draw_date) >= ?";
        $params[] = $date_from;
    }
    
    if (!empty($date_to)) {
        $region_query .= " AND DATE(d.draw_date) <= ?";
        $params[] = $date_to;
    }
    
    $region_query .= " GROUP BY r.id, r.region_name ORDER BY total_amount DESC";
    
    $region_stmt = $conn->prepare($region_query);
    $region_stmt->execute($params);
    $region_stats = $region_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 상품별 당첨 통계
    $product_query = "
        SELECT 
            p.id as product_id,
            p.name as product_name,
            p.product_code,
            COUNT(w.id) as winners_count,
            SUM(w.prize_amount) as total_amount,
            AVG(w.prize_amount) as avg_amount,
            COUNT(DISTINCT d.id) as draws_count
        FROM 
            winnings w
        JOIN 
            tickets t ON w.ticket_id = t.id
        JOIN 
            lottery_products p ON t.product_id = p.id
        JOIN 
            draws d ON p.id = d.product_id
        WHERE 1=1
    ";
    
    $params = [];
    
    if ($product_id > 0) {
        $product_query .= " AND t.product_id = ?";
        $params[] = $product_id;
    }
    
    if (!empty($date_from)) {
        $product_query .= " AND DATE(d.draw_date) >= ?";
        $params[] = $date_from;
    }
    
    if (!empty($date_to)) {
        $product_query .= " AND DATE(d.draw_date) <= ?";
        $params[] = $date_to;
    }
    
    $product_query .= " GROUP BY p.id, p.name, p.product_code ORDER BY total_amount DESC";
    
    $product_stmt = $conn->prepare($product_query);
    $product_stmt->execute($params);
    $product_stats = $product_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return [
        'total_stats' => $total_stats,
        'tier_stats' => $tier_stats,
        'monthly_stats' => $monthly_stats,
        'region_stats' => $region_stats,
        'product_stats' => $product_stats
    ];
}

// 고액 당첨자 목록 가져오기
function getTopWinners($conn, $product_id = 0, $date_from = '', $date_to = '', $limit = 10) {
    $query = "
        SELECT 
            w.id,
            w.prize_tier as prize_tier,
            w.prize_amount,
            w.status,
            w.created_at,
            t.ticket_number,
            d.draw_code as draw_number,
            d.draw_date,
            p.name as product_name,
            p.product_code,
            tm.terminal_code,
            s.store_name as store_name,
            r.region_name as region_name
        FROM 
            winnings w
        JOIN 
            tickets t ON w.ticket_id = t.id
        JOIN 
            draws d ON t.product_id = d.product_id
        JOIN 
            lottery_products p ON t.product_id = p.id
        JOIN 
            terminals tm ON t.terminal_id = tm.id
        JOIN 
            stores s ON tm.store_id = s.id
        JOIN 
            regions r ON s.state = r.region_name
        WHERE 1=1
    ";
    
    $params = [];
    
    if ($product_id > 0) {
        $query .= " AND t.product_id = ?";
        $params[] = $product_id;
    }
    
    if (!empty($date_from)) {
        $query .= " AND DATE(d.draw_date) >= ?";
        $params[] = $date_from;
    }
    
    if (!empty($date_to)) {
        $query .= " AND DATE(d.draw_date) <= ?";
        $params[] = $date_to;
    }
    
    $query .= " ORDER BY w.prize_amount DESC LIMIT ?";
    $params[] = $limit;
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// 일별 당첨금 통계
function getDailyPrizeStats($conn, $product_id = 0, $date_from = '', $date_to = '') {
    $query = "
        SELECT 
            DATE(d.draw_date) as draw_date,
            COUNT(w.id) as winners_count,
            SUM(w.prize_amount) as total_amount,
            COUNT(DISTINCT d.id) as draws_count
        FROM 
            winnings w
        JOIN 
            tickets t ON w.ticket_id = t.id
        JOIN 
            draws d ON t.product_id = d.product_id
        WHERE 1=1
    ";
    
    $params = [];
    
    if ($product_id > 0) {
        $query .= " AND t.product_id = ?";
        $params[] = $product_id;
    }
    
    if (!empty($date_from)) {
        $query .= " AND DATE(d.draw_date) >= ?";
        $params[] = $date_from;
    }
    
    if (!empty($date_to)) {
        $query .= " AND DATE(d.draw_date) <= ?";
        $params[] = $date_to;
    }
    
    $query .= " GROUP BY DATE(d.draw_date) ORDER BY draw_date DESC LIMIT 30";
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// 필요한 데이터 가져오기
$lottery_products = getLotteryProducts($conn);
$prize_statistics = getPrizeStatistics($conn, $product_id, $date_from, $date_to);
$top_winners = getTopWinners($conn, $product_id, $date_from, $date_to);
$daily_stats = getDailyPrizeStats($conn, $product_id, $date_from, $date_to);

// JSON 데이터 준비 (차트용)
$monthly_chart_data = [];
foreach ($prize_statistics['monthly_stats'] as $month) {
    $monthly_chart_data[] = [
        'month' => $month['month'],
        'amount' => floatval($month['total_amount']),
        'winners' => intval($month['winners_count'])
    ];
}

$tier_chart_data = [];
foreach ($prize_statistics['tier_stats'] as $tier) {
    $tier_chart_data[] = [
        'tier' => $tier['prize_tier'] . '등',
        'amount' => floatval($tier['total_amount']),
        'winners' => intval($tier['winners_count'])
    ];
}

$region_chart_data = [];
foreach ($prize_statistics['region_stats'] as $region) {
    $region_chart_data[] = [
        'region' => $region['region_name'],
        'amount' => floatval($region['total_amount']),
        'winners' => intval($region['winners_count'])
    ];
}

$daily_chart_data = [];
foreach ($daily_stats as $day) {
    $daily_chart_data[] = [
        'date' => $day['draw_date'],
        'amount' => floatval($day['total_amount']),
        'winners' => intval($day['winners_count'])
    ];
}

// 템플릿 헤더 포함
include_once TEMPLATES_PATH . '/dashboard_header.php';
include_once TEMPLATES_PATH . '/page_header.php';
?>

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <!-- 필터 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">통계 필터</h3>
            </div>
            <div class="card-body">
                <form method="GET" action="" class="form-horizontal">
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label for="product_id">복권 상품</label>
                            <select class="form-control" id="product_id" name="product_id">
                                <option value="0">모든 상품</option>
                                <?php foreach ($lottery_products as $product): ?>
                                    <option value="<?php echo $product['id']; ?>" <?php echo $product_id == $product['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($product['name']); ?> (<?php echo htmlspecialchars($product['product_code']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group col-md-3">
                            <label for="date_from">시작일</label>
                            <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo $date_from; ?>">
                        </div>
                        <div class="form-group col-md-3">
                            <label for="date_to">종료일</label>
                            <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo $date_to; ?>">
                        </div>
                        <div class="form-group col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary btn-block">적용</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- 당첨금 요약 -->
        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3><?php echo number_format($prize_statistics['total_stats']['total_winners']); ?></h3>
                        <p>총 당첨자 수</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-trophy"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3><?php echo number_format($prize_statistics['total_stats']['total_prize_amount']); ?> NPR</h3>
                        <p>총 당첨금액</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3><?php echo number_format($prize_statistics['total_stats']['paid_amount'] ?? 0); ?> NPR</h3>
                        <p>지급된 당첨금 (<?php echo number_format($prize_statistics['total_stats']['paid_winners'] ?? 0); ?>명)</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-hand-holding-usd"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3><?php echo number_format(($prize_statistics['total_stats']['claimed_amount'] ?? 0) + ($prize_statistics['total_stats']['pending_amount'] ?? 0)); ?> NPR</h3>
                        <p>미지급 당첨금 (<?php echo number_format(($prize_statistics['total_stats']['claimed_winners'] ?? 0) + ($prize_statistics['total_stats']['pending_winners'] ?? 0)); ?>명)</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-hourglass-half"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 당첨금 통계 차트 -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">월별 당첨금 통계</h3>
                    </div>
                    <div class="card-body">
                        <div class="chart">
                            <canvas id="monthlyChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">등수별 당첨 통계</h3>
                    </div>
                    <div class="card-body">
                        <div class="chart">
                            <canvas id="tierChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">지역별 당첨 통계</h3>
                    </div>
                    <div class="card-body">
                        <div class="chart">
                            <canvas id="regionChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">일별 당첨금 추이</h3>
                    </div>
                    <div class="card-body">
                        <div class="chart">
                            <canvas id="dailyChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 등수별 당첨 통계 테이블 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">등수별 당첨 통계</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr class="bg-light">
                                <th>등수</th>
                                <th>당첨자 수</th>
                                <th>총 당첨금</th>
                                <th>평균 당첨금</th>
                                <th>최대 당첨금</th>
                                <th>최소 당첨금</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($prize_statistics['tier_stats'] as $tier): ?>
                                <tr>
                                    <td><strong><?php echo $tier['prize_tier']; ?>등</strong></td>
                                    <td><?php echo number_format($tier['winners_count']); ?>명</td>
                                    <td><?php echo number_format($tier['total_amount']); ?> NPR</td>
                                    <td><?php echo number_format($tier['avg_amount']); ?> NPR</td>
                                    <td><?php echo number_format($tier['max_amount']); ?> NPR</td>
                                    <td><?php echo number_format($tier['min_amount']); ?> NPR</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- 상품별 당첨 통계 테이블 -->
        <?php if ($product_id == 0 && !empty($prize_statistics['product_stats'])): ?>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">상품별 당첨 통계</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr class="bg-light">
                                    <th>복권 상품</th>
                                    <th>당첨자 수</th>
                                    <th>총 당첨금</th>
                                    <th>평균 당첨금</th>
                                    <th>추첨 회차 수</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($prize_statistics['product_stats'] as $product): ?>
                                    <tr>
                                        <td>
                                            <a href="?product_id=<?php echo $product['product_id']; ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>">
                                                <?php echo htmlspecialchars($product['product_name']); ?> (<?php echo htmlspecialchars($product['product_code']); ?>)
                                            </a>
                                        </td>
                                        <td><?php echo number_format($product['winners_count']); ?>명</td>
                                        <td><?php echo number_format($product['total_amount']); ?> NPR</td>
                                        <td><?php echo number_format($product['avg_amount']); ?> NPR</td>
                                        <td><?php echo number_format($product['draws_count']); ?>회</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- 고액 당첨자 목록 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">고액 당첨자 TOP 10</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr class="bg-light">
                                <th>티켓 번호</th>
                                <th>복권 상품</th>
                                <th>회차</th>
                                <th>추첨일</th>
                                <th>당첨 등수</th>
                                <th>당첨금</th>
                                <th>판매점</th>
                                <th>지역</th>
                                <th>상태</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($top_winners as $winner): ?>
                                <tr>
                                    <td>
                                        <a href="../prize/payment.php?ticket=<?php echo urlencode($winner['ticket_number']); ?>">
                                            <?php echo htmlspecialchars($winner['ticket_number']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo htmlspecialchars($winner['product_name']); ?></td>
                                    <td><?php echo $winner['draw_number']; ?>회</td>
                                    <td><?php echo date('Y-m-d', strtotime($winner['draw_date'])); ?></td>
                                    <td><?php echo $winner['prize_tier']; ?>등</td>
                                    <td><?php echo number_format($winner['prize_amount']); ?> NPR</td>
                                    <td><?php echo htmlspecialchars($winner['store_name']); ?></td>
                                    <td><?php echo htmlspecialchars($winner['region_name']); ?></td>
                                    <td>
                                        <?php
                                        $status_class = '';
                                        $status_text = '';
                                        
                                        switch ($winner['status']) {
                                            case 'pending':
                                                $status_class = 'badge badge-warning';
                                                $status_text = '미확인';
                                                break;
                                            case 'claimed':
                                                $status_class = 'badge badge-info';
                                                $status_text = '확인됨';
                                                break;
                                            case 'paid':
                                                $status_class = 'badge badge-success';
                                                $status_text = '지급완료';
                                                break;
                                            default:
                                                $status_class = 'badge badge-dark';
                                                $status_text = '알 수 없음';
                                        }
                                        ?>
                                        <span class="<?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- 월별 당첨금 통계 테이블 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">월별 당첨금 통계</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr class="bg-light">
                                <th>월</th>
                                <th>당첨자 수</th>
                                <th>총 당첨금</th>
                                <th>지급 완료 금액</th>
                                <th>추첨 회차 수</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($prize_statistics['monthly_stats'] as $month): ?>
                                <tr>
                                    <td><?php echo $month['month']; ?></td>
                                    <td><?php echo number_format($month['winners_count']); ?>명</td>
                                    <td><?php echo number_format($month['total_amount']); ?> NPR</td>
                                    <td><?php echo number_format($month['paid_amount']); ?> NPR</td>
                                    <td><?php echo number_format($month['draws_count']); ?>회</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- /.content -->

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 차트 색상 설정
    const colors = {
        blue: 'rgba(54, 162, 235, 0.8)',
        green: 'rgba(75, 192, 192, 0.8)',
        red: 'rgba(255, 99, 132, 0.8)',
        orange: 'rgba(255, 159, 64, 0.8)',
        purple: 'rgba(153, 102, 255, 0.8)',
        yellow: 'rgba(255, 206, 86, 0.8)',
        grey: 'rgba(201, 203, 207, 0.8)'
    };
    
    // 막대 차트 공통 옵션
    const barOptions = {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return new Intl.NumberFormat('ko-KR').format(value);
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
                        label += new Intl.NumberFormat('ko-KR').format(context.raw);
                        return label;
                    }
                }
            }
        }
    };
    
    // 월별 당첨금 차트
    const monthlyData = <?php echo json_encode($monthly_chart_data); ?>;
    new Chart(document.getElementById('monthlyChart'), {
        type: 'bar',
        data: {
            labels: monthlyData.map(item => item.month),
            datasets: [
                {
                    label: '당첨금액 (NPR)',
                    data: monthlyData.map(item => item.amount),
                    backgroundColor: colors.blue,
                    borderColor: colors.blue.replace('0.8', '1'),
                    borderWidth: 1
                },
                {
                    label: '당첨자 수',
                    data: monthlyData.map(item => item.winners),
                    backgroundColor: colors.orange,
                    borderColor: colors.orange.replace('0.8', '1'),
                    borderWidth: 1,
                    type: 'line',
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            ...barOptions,
            scales: {
                ...barOptions.scales,
                y1: {
                    position: 'right',
                    beginAtZero: true,
                    grid: {
                        drawOnChartArea: false
                    },
                    ticks: {
                        callback: function(value) {
                            return new Intl.NumberFormat('ko-KR').format(value);
                        }
                    }
                }
            }
        }
    });
    
    // 등수별 당첨 차트
    const tierData = <?php echo json_encode($tier_chart_data); ?>;
    new Chart(document.getElementById('tierChart'), {
        type: 'bar',
        data: {
            labels: tierData.map(item => item.tier),
            datasets: [
                {
                    label: '당첨금액 (NPR)',
                    data: tierData.map(item => item.amount),
                    backgroundColor: colors.green,
                    borderColor: colors.green.replace('0.8', '1'),
                    borderWidth: 1
                },
                {
                    label: '당첨자 수',
                    data: tierData.map(item => item.winners),
                    backgroundColor: colors.purple,
                    borderColor: colors.purple.replace('0.8', '1'),
                    borderWidth: 1,
                    type: 'line',
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            ...barOptions,
            scales: {
                ...barOptions.scales,
                y1: {
                    position: 'right',
                    beginAtZero: true,
                    grid: {
                        drawOnChartArea: false
                    },
                    ticks: {
                        callback: function(value) {
                            return new Intl.NumberFormat('ko-KR').format(value);
                        }
                    }
                }
            }
        }
    });
    
    // 지역별 당첨 차트
    const regionData = <?php echo json_encode($region_chart_data); ?>;
    new Chart(document.getElementById('regionChart'), {
        type: 'pie',
        data: {
            labels: regionData.map(item => item.region),
            datasets: [
                {
                    data: regionData.map(item => item.amount),
                    backgroundColor: Object.values(colors),
                    borderWidth: 1
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
                            let label = context.label || '';
                            if (label) {
                                label += ': ';
                            }
                            label += new Intl.NumberFormat('ko-KR').format(context.raw) + ' NPR';
                            return label;
                        }
                    }
                }
            }
        }
    });
    
    // 일별 당첨금 추이 차트
    const dailyData = <?php echo json_encode($daily_chart_data); ?>;
    new Chart(document.getElementById('dailyChart'), {
        type: 'line',
        data: {
            labels: dailyData.map(item => item.date),
            datasets: [
                {
                    label: '당첨금액 (NPR)',
                    data: dailyData.map(item => item.amount),
                    backgroundColor: colors.red,
                    borderColor: colors.red.replace('0.8', '1'),
                    borderWidth: 2,
                    fill: false,
                    tension: 0.1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return new Intl.NumberFormat('ko-KR').format(value);
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
                            label += new Intl.NumberFormat('ko-KR').format(context.raw) + ' NPR';
                            return label;
                        }
                    }
                }
            }
        }
    });
});
</script>

<?php include_once TEMPLATES_PATH . '/dashboard_footer.php'; ?>