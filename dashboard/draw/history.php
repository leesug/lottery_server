<?php
/**
 * 추첨 이력 페이지
 * 
 * 이 페이지는 과거 추첨 결과와 이력을 조회하고 검색하는 기능을 제공합니다.
 * - 과거 추첨 결과
 * - 추첨 데이터 분석
 */

// 설정 및 공통 함수
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';
require_once '../../includes/db.php';

// 데이터베이스 연결
$db = getDbConnection();

// 현재 페이지 정보
$pageTitle = "추첨 이력";
$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir = dirname($_SERVER['PHP_SELF']);
$parts = explode('/', $currentDir);
$currentSection = end($parts);

// 필터링을 위한 기본값 설정
$product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
$draw_status = isset($_GET['draw_status']) ? $_GET['draw_status'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-d', strtotime('-3 months'));
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';

// 페이지네이션 설정
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// 복권 상품 목록 가져오기
function getLotteryProducts($db) {
    $query = "SELECT id, product_code, name FROM lottery_products WHERE status = 'active' ORDER BY name";
    $stmt = $db->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

// 추첨 이력 가져오기
function getDrawHistory($db, $product_id = 0, $draw_status = '', $date_from = '', $date_to = '', $search_term = '', $limit = 20, $offset = 0) {
    $query = "
        SELECT 
            d.id,
            d.product_id,
            d.draw_code as draw_number,
            d.draw_date,
            COALESCE(de.draw_method, 'random_generator') as draw_method,
            d.draw_venue as draw_location,
            dr.winning_numbers,
            d.status as draw_status,
            d.is_official as results_published,
            d.updated_at as published_at,
            COUNT(dw.id) as winners_count,
            COALESCE(d.prize_pool_amount, 0) as total_prizes,
            d.created_at,
            lp.name as product_name,
            lp.product_code,
            d.total_sold as tickets_count,
            COALESCE(d.total_sold * lp.price, 0) as total_sales
        FROM 
            draws d
        JOIN 
            lottery_products lp ON d.product_id = lp.id
        LEFT JOIN 
            draw_plans dp ON d.draw_code = dp.draw_code
        LEFT JOIN 
            draw_executions de ON dp.id = de.draw_plan_id
        LEFT JOIN 
            draw_results dr ON de.id = dr.draw_execution_id
        LEFT JOIN 
            draw_winners dw ON d.id = dw.draw_result_id
        WHERE 1=1
    ";
    
    $params = [];
    
    if ($product_id > 0) {
        $query .= " AND d.product_id = ?";
        $params[] = $product_id;
    }
    
    if (!empty($draw_status)) {
        $query .= " AND d.status = ?";
        $params[] = $draw_status;
    }
    
    if (!empty($date_from)) {
        $query .= " AND DATE(d.draw_date) >= ?";
        $params[] = $date_from;
    }
    
    if (!empty($date_to)) {
        $query .= " AND DATE(d.draw_date) <= ?";
        $params[] = $date_to;
    }
    
    if (!empty($search_term)) {
        $query .= " AND (d.draw_code LIKE ? OR lp.name LIKE ? OR d.draw_venue LIKE ? OR dr.winning_numbers LIKE ?)";
        $search_param = "%$search_term%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
    }
    
    $query .= " GROUP BY d.id, dr.winning_numbers";
    $query .= " ORDER BY d.draw_date DESC LIMIT ?, ?";
    $params[] = $offset;
    $params[] = $limit;
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

// 이력 총 개수 가져오기 (페이지네이션용)
function getDrawHistoryCount($db, $product_id = 0, $draw_status = '', $date_from = '', $date_to = '', $search_term = '') {
    $query = "
        SELECT 
            COUNT(DISTINCT d.id) as total
        FROM 
            draws d
        JOIN 
            lottery_products lp ON d.product_id = lp.id
        LEFT JOIN 
            draw_plans dp ON d.draw_code = dp.draw_code
        LEFT JOIN 
            draw_executions de ON dp.id = de.draw_plan_id
        LEFT JOIN 
            draw_results dr ON de.id = dr.draw_execution_id
        WHERE 1=1
    ";
    
    $params = [];
    
    if ($product_id > 0) {
        $query .= " AND d.product_id = ?";
        $params[] = $product_id;
    }
    
    if (!empty($draw_status)) {
        $query .= " AND d.status = ?";
        $params[] = $draw_status;
    }
    
    if (!empty($date_from)) {
        $query .= " AND DATE(d.draw_date) >= ?";
        $params[] = $date_from;
    }
    
    if (!empty($date_to)) {
        $query .= " AND DATE(d.draw_date) <= ?";
        $params[] = $date_to;
    }
    
    if (!empty($search_term)) {
        $query .= " AND (d.draw_code LIKE ? OR lp.name LIKE ? OR d.draw_venue LIKE ? OR dr.winning_numbers LIKE ?)";
        $search_param = "%$search_term%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
    }
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return (is_array($result) && isset($result['total'])) ? $result['total'] : 0;
}

// 통계 데이터 가져오기
function getDrawStatistics($db, $product_id = 0, $date_from = '', $date_to = '') {
    // 상태별 통계
    $status_query = "
        SELECT 
            status as draw_status,
            COUNT(*) as count
        FROM 
            draws
        WHERE 1=1
    ";
    
    $params = [];
    
    if ($product_id > 0) {
        $status_query .= " AND product_id = ?";
        $params[] = $product_id;
    }
    
    if (!empty($date_from)) {
        $status_query .= " AND DATE(draw_date) >= ?";
        $params[] = $date_from;
    }
    
    if (!empty($date_to)) {
        $status_query .= " AND DATE(draw_date) <= ?";
        $params[] = $date_to;
    }
    
    $status_query .= " GROUP BY status";
    
    $status_stmt = $db->prepare($status_query);
    $status_stmt->execute($params);
    $status_stats = $status_stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    
    // 월별 추첨 횟수
    $monthly_query = "
        SELECT 
            DATE_FORMAT(draw_date, '%Y-%m') as month,
            COUNT(*) as count
        FROM 
            draws
        WHERE 1=1
    ";
    
    $params = [];
    
    if ($product_id > 0) {
        $monthly_query .= " AND product_id = ?";
        $params[] = $product_id;
    }
    
    if (!empty($date_from)) {
        $monthly_query .= " AND DATE(draw_date) >= ?";
        $params[] = $date_from;
    }
    
    if (!empty($date_to)) {
        $monthly_query .= " AND DATE(draw_date) <= ?";
        $params[] = $date_to;
    }
    
    $monthly_query .= " GROUP BY DATE_FORMAT(draw_date, '%Y-%m') ORDER BY month";
    
    $monthly_stmt = $db->prepare($monthly_query);
    $monthly_stmt->execute($params);
    $monthly_stats = $monthly_stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    
    // 당첨 등급별 통계 (1등 당첨자 수)
    $tier_query = "
        SELECT 
            dw.rank as prize_tier,
            COUNT(*) as count,
            AVG(dw.prize_amount) as avg_amount,
            MAX(dw.prize_amount) as max_amount
        FROM 
            draw_winners dw
        JOIN 
            draws d ON dw.draw_result_id = d.id
        JOIN 
            draw_plans dp ON d.draw_code = dp.draw_code
        JOIN 
            draw_executions de ON dp.id = de.draw_plan_id
        WHERE 
            dw.rank = 1
    ";
    
    $params = [];
    
    if ($product_id > 0) {
        $tier_query .= " AND d.product_id = ?";
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
    
    $tier_query .= " GROUP BY dw.rank";
    
    $tier_stmt = $db->prepare($tier_query);
    $tier_stmt->execute($params);
    $tier_stats = $tier_stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    
    // 총 판매 및 당첨금 통계
    $total_query = "
        SELECT 
            COUNT(DISTINCT d.id) as total_draws,
            SUM(d.total_sold * lp.price) as total_sales,
            SUM(d.prize_pool_amount) as total_prizes,
            COUNT(DISTINCT dw.id) as total_winners,
            COUNT(DISTINCT CASE WHEN dw.id IS NOT NULL THEN d.id END) as draws_with_winners
        FROM 
            draws d
        JOIN 
            lottery_products lp ON d.product_id = lp.id
        LEFT JOIN 
            draw_winners dw ON d.id = dw.draw_result_id
        WHERE 
            d.status = 'completed'
    ";
    
    $params = [];
    
    if ($product_id > 0) {
        $total_query .= " AND d.product_id = ?";
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
    
    $total_stmt = $db->prepare($total_query);
    $total_stmt->execute($params);
    $total_stats = $total_stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    
    return [
        'status_stats' => $status_stats ?: [],
        'monthly_stats' => $monthly_stats ?: [],
        'tier_stats' => $tier_stats ?: [],
        'total_stats' => $total_stats ?: []
    ];
}

// 필요한 데이터 가져오기
$lottery_products = getLotteryProducts($db);
$draw_history = getDrawHistory($db, $product_id, $draw_status, $date_from, $date_to, $search_term, $per_page, $offset);
$total_count = getDrawHistoryCount($db, $product_id, $draw_status, $date_from, $date_to, $search_term);
$total_pages = ceil($total_count / $per_page);

// 통계 데이터 가져오기
$statistics = getDrawStatistics($db, $product_id, $date_from, $date_to);

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
                    <li class="breadcrumb-item">추첨 관리</li>
                    <li class="breadcrumb-item active"><?php echo $pageTitle; ?></li>
                </ol>
            </div>
        </div>
    </div>
</div>
<!-- /.content-header -->

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <!-- 필터링 폼 -->
        <div class="card card-default">
            <div class="card-header">
                <h3 class="card-title">검색 필터</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <form method="get" action="<?php echo $_SERVER['PHP_SELF']; ?>" id="filterForm">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>복권 상품</label>
                                <select class="form-control" name="product_id">
                                    <option value="0">모든 상품</option>
                                    <?php foreach ($lottery_products as $product): ?>
                                    <option value="<?php echo $product['id']; ?>" <?php echo $product_id == $product['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($product['name']); ?> (<?php echo htmlspecialchars($product['product_code']); ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>추첨 상태</label>
                                <select class="form-control" name="draw_status">
                                    <option value="">모든 상태</option>
                                    <option value="scheduled" <?php echo $draw_status == 'scheduled' ? 'selected' : ''; ?>>예정됨</option>
                                    <option value="in_progress" <?php echo $draw_status == 'in_progress' ? 'selected' : ''; ?>>진행 중</option>
                                    <option value="completed" <?php echo $draw_status == 'completed' ? 'selected' : ''; ?>>완료됨</option>
                                    <option value="cancelled" <?php echo $draw_status == 'cancelled' ? 'selected' : ''; ?>>취소됨</option>
                                    <option value="verified" <?php echo $draw_status == 'verified' ? 'selected' : ''; ?>>검증됨</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>시작일</label>
                                <input type="date" class="form-control" name="date_from" value="<?php echo $date_from; ?>">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>종료일</label>
                                <input type="date" class="form-control" name="date_to" value="<?php echo $date_to; ?>">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>검색어</label>
                                <input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($search_term); ?>" placeholder="검색어 입력...">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12 text-right">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> 검색
                            </button>
                            <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn btn-default">
                                <i class="fas fa-times"></i> 초기화
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- 통계 카드 -->
        <div class="row">
            <div class="col-md-3 col-sm-6 col-12">
                <div class="info-box">
                    <span class="info-box-icon bg-info">
                        <i class="fas fa-calendar-check"></i>
                    </span>
                    <div class="info-box-content">
                        <span class="info-box-text">총 추첨 횟수</span>
                        <span class="info-box-number"><?php echo number_format($statistics['total_stats']['total_draws'] ?? 0); ?></span>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 col-12">
                <div class="info-box">
                    <span class="info-box-icon bg-success">
                        <i class="fas fa-dollar-sign"></i>
                    </span>
                    <div class="info-box-content">
                        <span class="info-box-text">총 판매액</span>
                        <span class="info-box-number">₹ <?php echo number_format($statistics['total_stats']['total_sales'] ?? 0); ?></span>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 col-12">
                <div class="info-box">
                    <span class="info-box-icon bg-warning">
                        <i class="fas fa-trophy"></i>
                    </span>
                    <div class="info-box-content">
                        <span class="info-box-text">총 당첨금액</span>
                        <span class="info-box-number">₹ <?php echo number_format($statistics['total_stats']['total_prizes'] ?? 0); ?></span>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 col-12">
                <div class="info-box">
                    <span class="info-box-icon bg-danger">
                        <i class="fas fa-users"></i>
                    </span>
                    <div class="info-box-content">
                        <span class="info-box-text">총 당첨자 수</span>
                        <span class="info-box-number"><?php echo number_format($statistics['total_stats']['total_winners'] ?? 0); ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 추첨 이력 목록 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">추첨 이력 목록</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped projects">
                        <thead>
                            <tr>
                                <th>추첨 번호</th>
                                <th>복권 상품</th>
                                <th>추첨 일시</th>
                                <th>당첨 번호</th>
                                <th>판매량</th>
                                <th>당첨자 수</th>
                                <th>상태</th>
                                <th>관리</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($draw_history)): ?>
                            <tr>
                                <td colspan="8" class="text-center">검색 조건에 맞는 추첨 이력이 없습니다.</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($draw_history as $draw): ?>
                            <tr>
                                <td>
                                    <a href="results.php?draw_id=<?php echo $draw['id']; ?>">
                                        <?php echo htmlspecialchars($draw['draw_number']); ?>
                                    </a>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($draw['product_name']); ?>
                                    <br>
                                    <small><?php echo htmlspecialchars($draw['product_code']); ?></small>
                                </td>
                                <td>
                                    <?php echo date('Y-m-d H:i', strtotime($draw['draw_date'])); ?>
                                </td>
                                <td>
                                    <?php if ($draw['draw_status'] == 'completed'): ?>
                                    <span class="badge bg-success"><?php echo htmlspecialchars($draw['winning_numbers'] ?? ''); ?></span>
                                    <?php else: ?>
                                    <span class="badge bg-secondary">미정</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo number_format($draw['tickets_count'] ?? 0); ?> 장
                                    <br>
                                    <small>₹ <?php echo number_format($draw['total_sales'] ?? 0); ?></small>
                                </td>
                                <td>
                                    <?php if ($draw['draw_status'] == 'completed'): ?>
                                    <?php echo number_format($draw['winners_count'] ?? 0); ?> 명
                                    <br>
                                    <small>₹ <?php echo number_format($draw['total_prizes'] ?? 0); ?></small>
                                    <?php else: ?>
                                    <span class="badge bg-secondary">미정</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $statusClass = '';
                                    $statusLabel = '';
                                    
                                    switch ($draw['draw_status']) {
                                        case 'scheduled':
                                            $statusClass = 'bg-info';
                                            $statusLabel = '예정됨';
                                            break;
                                        case 'in_progress':
                                            $statusClass = 'bg-warning';
                                            $statusLabel = '진행 중';
                                            break;
                                        case 'completed':
                                            $statusClass = 'bg-success';
                                            $statusLabel = '완료됨';
                                            break;
                                        case 'cancelled':
                                            $statusClass = 'bg-danger';
                                            $statusLabel = '취소됨';
                                            break;
                                        case 'verified':
                                            $statusClass = 'bg-primary';
                                            $statusLabel = '검증됨';
                                            break;
                                        default:
                                            $statusClass = 'bg-secondary';
                                            $statusLabel = '알 수 없음';
                                    }
                                    ?>
                                    <span class="badge <?php echo $statusClass; ?>"><?php echo $statusLabel; ?></span>
                                    <?php if ($draw['results_published']): ?>
                                    <span class="badge bg-info">공개됨</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a class="btn btn-primary btn-sm" href="results.php?draw_id=<?php echo $draw['id']; ?>">
                                        <i class="fas fa-eye"></i> 상세
                                    </a>
                                    <?php if ($draw['draw_status'] == 'completed' && !$draw['results_published']): ?>
                                    <a class="btn btn-info btn-sm" href="results.php?draw_id=<?php echo $draw['id']; ?>&action=publish">
                                        <i class="fas fa-upload"></i> 공개
                                    </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer clearfix">
                <ul class="pagination pagination-sm m-0 float-right">
                    <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=1&product_id=<?php echo $product_id; ?>&draw_status=<?php echo urlencode($draw_status); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>&search=<?php echo urlencode($search_term); ?>">«</a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page - 1; ?>&product_id=<?php echo $product_id; ?>&draw_status=<?php echo urlencode($draw_status); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>&search=<?php echo urlencode($search_term); ?>">‹</a>
                    </li>
                    <?php endif; ?>
                    
                    <?php
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);
                    
                    for ($i = $start_page; $i <= $end_page; $i++):
                    ?>
                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>&product_id=<?php echo $product_id; ?>&draw_status=<?php echo urlencode($draw_status); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>&search=<?php echo urlencode($search_term); ?>"><?php echo $i; ?></a>
                    </li>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?>&product_id=<?php echo $product_id; ?>&draw_status=<?php echo urlencode($draw_status); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>&search=<?php echo urlencode($search_term); ?>">›</a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $total_pages; ?>&product_id=<?php echo $product_id; ?>&draw_status=<?php echo urlencode($draw_status); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>&search=<?php echo urlencode($search_term); ?>">»</a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</section>

<?php
// 템플릿 푸터 포함
include_once TEMPLATES_PATH . '/dashboard_footer.php';
?><?php
// 템플릿 푸터 포함
include_once TEMPLATES_PATH . '/dashboard_footer.php';
?>
