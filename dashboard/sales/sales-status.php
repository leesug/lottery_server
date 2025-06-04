<?php
// 판매현황 대시보드
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

// 로그인 체크
checkLogin();

// 페이지 제목
$pageTitle = "판매현황";
$currentSection = "sales";
$currentPage = "sales-status.php";

// 데이터베이스 연결
$conn = get_db_connection();

// 필터 파라미터
$viewType = isset($_GET['view']) ? $_GET['view'] : 'draw'; // draw(회차별), week(주별), day(일별)
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-7 days'));
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$storeId = isset($_GET['store_id']) ? intval($_GET['store_id']) : 0;
$regionId = isset($_GET['region_id']) ? intval($_GET['region_id']) : 0;

// 판매점 목록 조회
$storesQuery = "SELECT id, store_name, store_code FROM stores WHERE status = 'active' ORDER BY store_name";
$stores = $conn->query($storesQuery)->fetchAll();

// 지역 목록 조회
$regionsQuery = "SELECT id, region_name FROM regions ORDER BY region_name";
$regions = $conn->query($regionsQuery)->fetchAll();

// 조건절 구성
$where = [];
$params = [];

if ($storeId > 0) {
    $where[] = "t.store_id = ?";
    $params[] = $storeId;
}

if ($regionId > 0) {
    $where[] = "s.region_id = ?";
    $params[] = $regionId;
}

// 날짜 조건 (일별, 주별 뷰에서만)
if ($viewType != 'draw') {
    $where[] = "DATE(t.created_at) BETWEEN ? AND ?";
    $params[] = $startDate;
    $params[] = $endDate;
}

$whereClause = empty($where) ? "" : "WHERE " . implode(" AND ", $where);

// 뷰 타입별 쿼리
if ($viewType == 'draw') {
    // 회차별 판매현황
    $query = "
        SELECT 
            d.draw_no,
            d.draw_date,
            d.status as draw_status,
            COUNT(DISTINCT t.id) as ticket_count,
            SUM(t.games_count) as total_games,
            SUM(t.ticket_amount) as total_amount,
            COUNT(DISTINCT t.store_id) as store_count,
            MAX(t.created_at) as last_sale_time
        FROM draws d
        LEFT JOIN tickets t ON d.id = t.draw_id
        LEFT JOIN stores s ON t.store_id = s.id
        $whereClause
        GROUP BY d.id
        ORDER BY d.draw_no DESC
        LIMIT 20
    ";
} elseif ($viewType == 'week') {
    // 주별 판매현황
    $query = "
        SELECT 
            YEARWEEK(t.created_at, 1) as year_week,
            MIN(DATE(t.created_at)) as week_start,
            MAX(DATE(t.created_at)) as week_end,
            COUNT(DISTINCT t.id) as ticket_count,
            SUM(t.games_count) as total_games,
            SUM(t.ticket_amount) as total_amount,
            COUNT(DISTINCT t.store_id) as store_count,
            COUNT(DISTINCT t.draw_id) as draw_count
        FROM tickets t
        LEFT JOIN stores s ON t.store_id = s.id
        $whereClause
        GROUP BY YEARWEEK(t.created_at, 1)
        ORDER BY year_week DESC
        LIMIT 12
    ";
} else {
    // 일별 판매현황
    $query = "
        SELECT 
            DATE(t.created_at) as sale_date,
            DAYNAME(t.created_at) as day_name,
            COUNT(DISTINCT t.id) as ticket_count,
            SUM(t.games_count) as total_games,
            SUM(t.ticket_amount) as total_amount,
            COUNT(DISTINCT t.store_id) as store_count,
            COUNT(DISTINCT t.draw_id) as draw_count,
            GROUP_CONCAT(DISTINCT d.draw_no ORDER BY d.draw_no) as draw_numbers
        FROM tickets t
        LEFT JOIN stores s ON t.store_id = s.id
        LEFT JOIN draws d ON t.draw_id = d.id
        $whereClause
        GROUP BY DATE(t.created_at)
        ORDER BY sale_date DESC
        LIMIT 30
    ";
}

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->execute($params);
} else {
    $stmt->execute();
}
$salesData = $stmt->fetchAll();

// 전체 통계
$statsQuery = "
    SELECT 
        COUNT(DISTINCT t.id) as total_tickets,
        SUM(t.games_count) as total_games,
        SUM(t.ticket_amount) as total_sales,
        AVG(t.ticket_amount) as avg_ticket_amount,
        COUNT(DISTINCT t.store_id) as active_stores,
        COUNT(DISTINCT DATE(t.created_at)) as sale_days
    FROM tickets t
    LEFT JOIN stores s ON t.store_id = s.id
    WHERE DATE(t.created_at) BETWEEN ? AND ?
";
$statsParams = [$startDate, $endDate];
if ($storeId > 0) {
    $statsQuery .= " AND t.store_id = ?";
    $statsParams[] = $storeId;
}
if ($regionId > 0) {
    $statsQuery .= " AND s.region_id = ?";
    $statsParams[] = $regionId;
}

$stmt = $conn->prepare($statsQuery);
$stmt->execute($statsParams);
$stats = $stmt->fetch();

// 상위 판매점
$topStoresQuery = "
    SELECT 
        s.store_name,
        s.store_code,
        COUNT(t.id) as ticket_count,
        SUM(t.ticket_amount) as total_sales
    FROM tickets t
    INNER JOIN stores s ON t.store_id = s.id
    WHERE DATE(t.created_at) BETWEEN ? AND ?
    GROUP BY s.id
    ORDER BY total_sales DESC
    LIMIT 5
";
$stmt = $conn->prepare($topStoresQuery);
$stmt->execute([$startDate, $endDate]);
$topStores = $stmt->fetchAll();

include_once TEMPLATES_PATH . '/dashboard_header.php';
?>

<!-- 컨텐츠 시작 -->
<div class="container-fluid">
    <!-- 페이지 헤더 -->
    <div class="row mb-4">
        <div class="col-md-6">
            <h1 class="h3 mb-0"><?php echo $pageTitle; ?></h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="/dashboard">대시보드</a></li>
                    <li class="breadcrumb-item"><a href="/dashboard/sales">판매관리</a></li>
                    <li class="breadcrumb-item active"><?php echo $pageTitle; ?></li>
                </ol>
            </nav>
        </div>
        <div class="col-md-6 text-end">
            <div class="btn-group" role="group">
                <a href="?view=draw" class="btn btn-<?php echo $viewType == 'draw' ? 'primary' : 'outline-primary'; ?>">
                    회차별
                </a>
                <a href="?view=week" class="btn btn-<?php echo $viewType == 'week' ? 'primary' : 'outline-primary'; ?>">
                    주별
                </a>
                <a href="?view=day" class="btn btn-<?php echo $viewType == 'day' ? 'primary' : 'outline-primary'; ?>">
                    일별
                </a>
            </div>
            <a href="sales-report.php" class="btn btn-secondary ms-2">
                <i class="fas fa-file-excel"></i> 리포트
            </a>
        </div>
    </div>

    <!-- 통계 카드 -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                총 판매액</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                ₩ <?php echo number_format($stats['total_sales'] ?? 0); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-won-sign fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                총 티켓수</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo number_format($stats['total_tickets'] ?? 0); ?>장
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-ticket-alt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                평균 티켓금액</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                ₩ <?php echo number_format($stats['avg_ticket_amount'] ?? 0); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calculator fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                활성 판매점</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo number_format($stats['active_stores'] ?? 0); ?>개
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-store fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 필터 -->
    <?php if ($viewType != 'draw'): ?>
    <div class="card shadow mb-4">
        <div class="card-body">
            <form method="get" class="row g-3">
                <input type="hidden" name="view" value="<?php echo $viewType; ?>">
                
                <div class="col-md-2">
                    <label class="form-label">시작일</label>
                    <input type="date" name="start_date" class="form-control" 
                           value="<?php echo $startDate; ?>" max="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">종료일</label>
                    <input type="date" name="end_date" class="form-control" 
                           value="<?php echo $endDate; ?>" max="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">지역</label>
                    <select name="region_id" class="form-select">
                        <option value="0">전체</option>
                        <?php foreach ($regions as $region): ?>
                            <option value="<?php echo $region['id']; ?>" 
                                    <?php echo $regionId == $region['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($region['region_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">판매점</label>
                    <select name="store_id" class="form-select">
                        <option value="0">전체</option>
                        <?php foreach ($stores as $store): ?>
                            <option value="<?php echo $store['id']; ?>" 
                                    <?php echo $storeId == $store['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($store['store_name']); ?> (<?php echo $store['store_code']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search"></i> 조회
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <div class="row">
        <!-- 판매 데이터 테이블 -->
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <?php
                        switch($viewType) {
                            case 'draw': echo '회차별 판매현황'; break;
                            case 'week': echo '주별 판매현황'; break;
                            case 'day': echo '일별 판매현황'; break;
                        }
                        ?>
                    </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <?php if ($viewType == 'draw'): ?>
                                        <th>회차</th>
                                        <th>추첨일</th>
                                        <th>상태</th>
                                        <th>티켓수</th>
                                        <th>게임수</th>
                                        <th>판매액</th>
                                        <th>판매점수</th>
                                    <?php elseif ($viewType == 'week'): ?>
                                        <th>주차</th>
                                        <th>기간</th>
                                        <th>티켓수</th>
                                        <th>게임수</th>
                                        <th>판매액</th>
                                        <th>판매점수</th>
                                        <th>회차수</th>
                                    <?php else: ?>
                                        <th>날짜</th>
                                        <th>요일</th>
                                        <th>티켓수</th>
                                        <th>게임수</th>
                                        <th>판매액</th>
                                        <th>판매점수</th>
                                        <th>회차</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($salesData as $row): ?>
                                    <tr>
                                        <?php if ($viewType == 'draw'): ?>
                                            <td class="text-center">
                                                <strong><?php echo $row['draw_no']; ?></strong>회
                                            </td>
                                            <td class="text-center">
                                                <?php echo date('Y-m-d', strtotime($row['draw_date'])); ?>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-<?php echo getDrawStatusBadgeClass($row['draw_status']); ?>">
                                                    <?php echo getDrawStatusText($row['draw_status']); ?>
                                                </span>
                                            </td>
                                            <td class="text-end"><?php echo number_format($row['ticket_count']); ?></td>
                                            <td class="text-end"><?php echo number_format($row['total_games']); ?></td>
                                            <td class="text-end">₩ <?php echo number_format($row['total_amount']); ?></td>
                                            <td class="text-center"><?php echo number_format($row['store_count']); ?></td>
                                        <?php elseif ($viewType == 'week'): ?>
                                            <td class="text-center">
                                                <?php 
                                                $year = substr($row['year_week'], 0, 4);
                                                $week = substr($row['year_week'], 4);
                                                echo "{$year}년 {$week}주";
                                                ?>
                                            </td>
                                            <td class="text-center">
                                                <?php echo date('m/d', strtotime($row['week_start'])); ?> ~ 
                                                <?php echo date('m/d', strtotime($row['week_end'])); ?>
                                            </td>
                                            <td class="text-end"><?php echo number_format($row['ticket_count']); ?></td>
                                            <td class="text-end"><?php echo number_format($row['total_games']); ?></td>
                                            <td class="text-end">₩ <?php echo number_format($row['total_amount']); ?></td>
                                            <td class="text-center"><?php echo number_format($row['store_count']); ?></td>
                                            <td class="text-center"><?php echo number_format($row['draw_count']); ?></td>
                                        <?php else: ?>
                                            <td class="text-center">
                                                <?php echo $row['sale_date']; ?>
                                            </td>
                                            <td class="text-center">
                                                <?php echo getDayNameKorean($row['day_name']); ?>
                                            </td>
                                            <td class="text-end"><?php echo number_format($row['ticket_count']); ?></td>
                                            <td class="text-end"><?php echo number_format($row['total_games']); ?></td>
                                            <td class="text-end">₩ <?php echo number_format($row['total_amount']); ?></td>
                                            <td class="text-center"><?php echo number_format($row['store_count']); ?></td>
                                            <td class="text-center">
                                                <small><?php echo $row['draw_numbers'] ?: '-'; ?></small>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- 상위 판매점 -->
        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">상위 판매점 TOP 5</h6>
                </div>
                <div class="card-body">
                    <?php foreach ($topStores as $idx => $store): ?>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong><?php echo $idx + 1; ?>.</strong>
                                    <?php echo htmlspecialchars($store['store_name']); ?>
                                    <small class="text-muted">(<?php echo $store['store_code']; ?>)</small>
                                </div>
                                <div class="text-end">
                                    <strong>₩ <?php echo number_format($store['total_sales']); ?></strong><br>
                                    <small class="text-muted"><?php echo number_format($store['ticket_count']); ?>장</small>
                                </div>
                            </div>
                            <?php if ($idx < count($topStores) - 1): ?>
                                <hr class="my-2">
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- 판매 차트 -->
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">판매 추이</h6>
                </div>
                <div class="card-body">
                    <canvas id="salesChart" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// 판매 차트
const ctx = document.getElementById('salesChart').getContext('2d');
const chartData = <?php echo json_encode(array_slice(array_reverse($salesData), -7)); ?>;

const labels = chartData.map(item => {
    <?php if ($viewType == 'draw'): ?>
        return item.draw_no + '회';
    <?php elseif ($viewType == 'week'): ?>
        return item.year_week.substr(4) + '주';
    <?php else: ?>
        return item.sale_date.substr(5);
    <?php endif; ?>
});

const data = {
    labels: labels,
    datasets: [{
        label: '판매액',
        data: chartData.map(item => item.total_amount),
        borderColor: 'rgb(75, 192, 192)',
        backgroundColor: 'rgba(75, 192, 192, 0.2)',
        tension: 0.1
    }]
};

new Chart(ctx, {
    type: 'line',
    data: data,
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return '₩' + value.toLocaleString();
                    }
                }
            }
        }
    }
});
</script>

<?php
// 헬퍼 함수들
function getDrawStatusBadgeClass($status) {
    switch($status) {
        case 'upcoming': return 'info';
        case 'active': return 'success';
        case 'closed': return 'warning';
        case 'completed': return 'secondary';
        default: return 'secondary';
    }
}

function getDrawStatusText($status) {
    switch($status) {
        case 'upcoming': return '예정';
        case 'active': return '판매중';
        case 'closed': return '마감';
        case 'completed': return '완료';
        default: return $status;
    }
}

function getDayNameKorean($dayName) {
    $days = [
        'Monday' => '월요일',
        'Tuesday' => '화요일',
        'Wednesday' => '수요일',
        'Thursday' => '목요일',
        'Friday' => '금요일',
        'Saturday' => '토요일',
        'Sunday' => '일요일'
    ];
    return $days[$dayName] ?? $dayName;
}
?>

<?php include_once TEMPLATES_PATH . '/dashboard_footer.php'; ?>
