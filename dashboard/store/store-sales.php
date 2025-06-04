<?php
/**
 * 판매점 판매 현황 페이지
 * 
 * 이 페이지는 각 판매점의 판매 현황을 표시합니다.
 */

// 필수 파일 포함
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

// 페이지 접근 권한 확인
checkPageAccess('store_management');

// 현재 페이지 정보
$pageTitle = "판매점 판매 현황";
$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir = dirname($_SERVER['PHP_SELF']);
$parts = explode('/', $currentDir);
$currentSection = end($parts);

// 변수 초기화
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$status = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';
$productType = isset($_GET['product_type']) ? sanitizeInput($_GET['product_type']) : '';
$dateRange = isset($_GET['date_range']) ? sanitizeInput($_GET['date_range']) : 'this_month';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$perPage = 15;
$offset = ($page - 1) * $perPage;
$sortBy = isset($_GET['sort']) ? sanitizeInput($_GET['sort']) : 'sales_amount';
$sortOrder = isset($_GET['order']) && strtolower($_GET['order']) === 'asc' ? 'ASC' : 'DESC';

// 데이터베이스 연결
$db = get_db_connection();

// 날짜 범위 설정
$dateFrom = '';
$dateTo = '';
switch($dateRange) {
    case 'today':
        $dateFrom = date('Y-m-d');
        $dateTo = date('Y-m-d');
        break;
    case 'yesterday':
        $dateFrom = date('Y-m-d', strtotime('-1 day'));
        $dateTo = date('Y-m-d', strtotime('-1 day'));
        break;
    case 'this_week':
        $dateFrom = date('Y-m-d', strtotime('monday this week'));
        $dateTo = date('Y-m-d');
        break;
    case 'last_week':
        $dateFrom = date('Y-m-d', strtotime('monday last week'));
        $dateTo = date('Y-m-d', strtotime('sunday last week'));
        break;
    case 'this_month':
        $dateFrom = date('Y-m-01');
        $dateTo = date('Y-m-d');
        break;
    case 'last_month':
        $dateFrom = date('Y-m-01', strtotime('first day of last month'));
        $dateTo = date('Y-m-t', strtotime('last day of last month'));
        break;
    case 'last_3_months':
        $dateFrom = date('Y-m-01', strtotime('-2 months'));
        $dateTo = date('Y-m-d');
        break;
    case 'custom':
        $dateFrom = isset($_GET['date_from']) ? sanitizeInput($_GET['date_from']) : date('Y-m-01');
        $dateTo = isset($_GET['date_to']) ? sanitizeInput($_GET['date_to']) : date('Y-m-d');
        break;
    default:
        $dateFrom = date('Y-m-01');
        $dateTo = date('Y-m-d');
        break;
}

// 전역 쿼리 타입 설정
global $query_type;
$query_type = "store_sales";

// 쿼리 조건 빌드
$conditions = ["(t.created_at BETWEEN ? AND ?)"];
$params = [$dateFrom, $dateTo];
$types = 'ss';

if (!empty($search)) {
    $conditions[] = "(s.store_name LIKE ? OR s.store_code LIKE ? OR s.owner_name LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= 'sss';
}

if (!empty($status)) {
    $conditions[] = "s.status = ?";
    $params[] = $status;
    $types .= 's';
}

if (!empty($productType)) {
    $conditions[] = "p.name = ?";
    $params[] = $productType;
    $types .= 's';
}

// 조건 결합
$whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

// 판매점별 판매 현황 조회를 위한 쿼리
$sql = "SELECT 
            s.id, 
            s.store_name, 
            s.store_code, 
            s.city,
            s.status,
            s.owner_name,
            COUNT(t.id) as total_sales,
            SUM(t.price) as sales_amount,
            COUNT(DISTINCT t.terminal_id) as unique_customers
        FROM 
            stores s
        LEFT JOIN 
            terminals tm ON s.id = tm.store_id
        LEFT JOIN 
            tickets t ON tm.id = t.terminal_id
        LEFT JOIN 
            lottery_products p ON t.product_id = p.id
        $whereClause
        GROUP BY 
            s.id
        ORDER BY 
            $sortBy $sortOrder
        LIMIT ?, ?";

// 페이지네이션을 위한 총 레코드 수 조회
$countSql = "SELECT 
                COUNT(DISTINCT s.id) as total 
             FROM 
                stores s
             LEFT JOIN 
                terminals tm ON s.id = tm.store_id
             LEFT JOIN 
                tickets t ON tm.id = t.terminal_id
             LEFT JOIN 
                lottery_products p ON t.product_id = p.id
             $whereClause";

$countStmt = $db->prepare($countSql);

if (!empty($params)) {
    foreach ($params as $index => $param) {
        $countStmt->bindValue($index + 1, $param);
    }
}

$countStmt->execute();
$result = $countStmt->fetch(PDO::FETCH_ASSOC);
$totalCount = isset($result['total']) ? $result['total'] : 0;
$totalPages = ceil($totalCount / $perPage);

// 판매점 판매 데이터 조회
$stmt = $db->prepare($sql);

if (!empty($params)) {
    $params[] = $offset;
    $params[] = $perPage;
    foreach ($params as $index => $param) {
        $stmt->bindValue($index + 1, $param);
    }
} else {
    $stmt->bindValue(1, $offset, PDO::PARAM_INT);
    $stmt->bindValue(2, $perPage, PDO::PARAM_INT);
}

$stmt->execute();
$storeSales = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 제품 유형 목록 조회
$productTypesSql = "SELECT DISTINCT name FROM lottery_products ORDER BY name";
$productTypesResult = $db->query($productTypesSql);
$productTypes = [];

while ($row = $productTypesResult->fetch(PDO::FETCH_ASSOC)) {
    $productTypes[] = $row['name'];
}

// 총 판매액 계산
$totalSalesAmount = 0;
$totalSalesCount = 0;
foreach ($storeSales as $store) {
    $totalSalesAmount += $store['sales_amount'];
    $totalSalesCount += $store['total_sales'];
}

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
                    <li class="breadcrumb-item">판매점 관리</li>
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
        <!-- 통계 요약 -->
        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3><?php echo number_format($totalSalesCount); ?></h3>
                        <p>총 판매 횟수</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3>₹ <?php echo number_format($totalSalesAmount); ?></h3>
                        <p>총 판매 금액</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3><?php echo $totalCount; ?></h3>
                        <p>활성 판매점</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-store"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3>
                            <?php 
                                echo ($totalSalesCount > 0 && $totalCount > 0) ? 
                                     number_format($totalSalesCount / $totalCount, 1) : 
                                     '0.0'; 
                            ?>
                        </h3>
                        <p>판매점당 평균 판매수</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 필터 및 검색 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">검색 및 필터</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <form method="get" action="store-sales.php" class="form">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="search">판매점 검색</label>
                                <input type="text" class="form-control" id="search" name="search" 
                                    placeholder="판매점명, 코드, 대표자명" value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="status">판매점 상태</label>
                                <select class="form-control" id="status" name="status">
                                    <option value="">모든 상태</option>
                                    <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>활성</option>
                                    <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>비활성</option>
                                    <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>대기중</option>
                                    <option value="terminated" <?php echo $status === 'terminated' ? 'selected' : ''; ?>>계약해지</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="product_type">제품 유형</label>
                                <select class="form-control" id="product_type" name="product_type">
                                    <option value="">모든 제품</option>
                                    <?php foreach ($productTypes as $type): ?>
                                    <option value="<?php echo htmlspecialchars($type); ?>" 
                                        <?php echo $productType === $type ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($type); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="date_range">날짜 범위</label>
                                <select class="form-control" id="date_range" name="date_range">
                                    <option value="today" <?php echo $dateRange === 'today' ? 'selected' : ''; ?>>오늘</option>
                                    <option value="yesterday" <?php echo $dateRange === 'yesterday' ? 'selected' : ''; ?>>어제</option>
                                    <option value="this_week" <?php echo $dateRange === 'this_week' ? 'selected' : ''; ?>>이번 주</option>
                                    <option value="last_week" <?php echo $dateRange === 'last_week' ? 'selected' : ''; ?>>지난 주</option>
                                    <option value="this_month" <?php echo $dateRange === 'this_month' ? 'selected' : ''; ?>>이번 달</option>
                                    <option value="last_month" <?php echo $dateRange === 'last_month' ? 'selected' : ''; ?>>지난 달</option>
                                    <option value="last_3_months" <?php echo $dateRange === 'last_3_months' ? 'selected' : ''; ?>>최근 3개월</option>
                                    <option value="custom" <?php echo $dateRange === 'custom' ? 'selected' : ''; ?>>직접 지정</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <button type="submit" class="btn btn-primary btn-block">
                                    <i class="fas fa-search"></i> 검색
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 커스텀 날짜 선택 (기본적으로 숨겨짐) -->
                    <div class="row custom-date-range" style="display: <?php echo $dateRange === 'custom' ? 'flex' : 'none'; ?>;">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="date_from">시작일</label>
                                <input type="date" class="form-control" id="date_from" name="date_from" 
                                       value="<?php echo htmlspecialchars($dateFrom); ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="date_to">종료일</label>
                                <input type="date" class="form-control" id="date_to" name="date_to" 
                                       value="<?php echo htmlspecialchars($dateTo); ?>">
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- 판매점 판매 현황 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">판매점 판매 현황</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                    <div class="btn-group">
                        <button type="button" class="btn btn-tool dropdown-toggle" data-toggle="dropdown">
                            <i class="fas fa-download"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-right">
                            <a href="<?php echo SERVER_URL; ?>/api/export/store-sales-export.php?format=excel&date_from=<?php echo urlencode($dateFrom); ?>&date_to=<?php echo urlencode($dateTo); ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&product_type=<?php echo urlencode($productType); ?>" class="dropdown-item">
                                <i class="fas fa-file-excel mr-2"></i> Excel 다운로드
                            </a>
                            <a href="<?php echo SERVER_URL; ?>/api/export/store-sales-export.php?format=csv&date_from=<?php echo urlencode($dateFrom); ?>&date_to=<?php echo urlencode($dateTo); ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&product_type=<?php echo urlencode($productType); ?>" class="dropdown-item">
                                <i class="fas fa-file-csv mr-2"></i> CSV 다운로드
                            </a>
                            <a href="<?php echo SERVER_URL; ?>/api/export/store-sales-export.php?format=pdf&date_from=<?php echo urlencode($dateFrom); ?>&date_to=<?php echo urlencode($dateTo); ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&product_type=<?php echo urlencode($productType); ?>" class="dropdown-item">
                                <i class="fas fa-file-pdf mr-2"></i> PDF 다운로드
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover text-nowrap">
                    <thead>
                        <tr>
                            <th>
                                <a href="?search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&product_type=<?php echo urlencode($productType); ?>&date_range=<?php echo urlencode($dateRange); ?>&date_from=<?php echo urlencode($dateFrom); ?>&date_to=<?php echo urlencode($dateTo); ?>&sort=store_name&order=<?php echo $sortBy === 'store_name' && $sortOrder === 'ASC' ? 'DESC' : 'ASC'; ?>">
                                    판매점명
                                    <?php if ($sortBy === 'store_name'): ?>
                                        <i class="fas fa-sort-<?php echo $sortOrder === 'ASC' ? 'up' : 'down'; ?>"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th>판매점 코드</th>
                            <th>대표자명</th>
                            <th>지역</th>
                            <th>상태</th>
                            <th>
                                <a href="?search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&product_type=<?php echo urlencode($productType); ?>&date_range=<?php echo urlencode($dateRange); ?>&date_from=<?php echo urlencode($dateFrom); ?>&date_to=<?php echo urlencode($dateTo); ?>&sort=total_sales&order=<?php echo $sortBy === 'total_sales' && $sortOrder === 'ASC' ? 'DESC' : 'ASC'; ?>">
                                    판매 횟수
                                    <?php if ($sortBy === 'total_sales'): ?>
                                        <i class="fas fa-sort-<?php echo $sortOrder === 'ASC' ? 'up' : 'down'; ?>"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th>
                                <a href="?search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&product_type=<?php echo urlencode($productType); ?>&date_range=<?php echo urlencode($dateRange); ?>&date_from=<?php echo urlencode($dateFrom); ?>&date_to=<?php echo urlencode($dateTo); ?>&sort=sales_amount&order=<?php echo $sortBy === 'sales_amount' && $sortOrder === 'ASC' ? 'DESC' : 'ASC'; ?>">
                                    판매 금액
                                    <?php if ($sortBy === 'sales_amount'): ?>
                                        <i class="fas fa-sort-<?php echo $sortOrder === 'ASC' ? 'up' : 'down'; ?>"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th>
                                <a href="?search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&product_type=<?php echo urlencode($productType); ?>&date_range=<?php echo urlencode($dateRange); ?>&date_from=<?php echo urlencode($dateFrom); ?>&date_to=<?php echo urlencode($dateTo); ?>&sort=unique_customers&order=<?php echo $sortBy === 'unique_customers' && $sortOrder === 'ASC' ? 'DESC' : 'ASC'; ?>">
                                    고유 고객수
                                    <?php if ($sortBy === 'unique_customers'): ?>
                                        <i class="fas fa-sort-<?php echo $sortOrder === 'ASC' ? 'up' : 'down'; ?>"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th>작업</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($storeSales) > 0): ?>
                            <?php foreach ($storeSales as $store): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($store['store_name']); ?></td>
                                    <td><?php echo htmlspecialchars($store['store_code']); ?></td>
                                    <td><?php echo htmlspecialchars($store['owner_name']); ?></td>
                                    <td><?php echo htmlspecialchars($store['city']); ?></td>
                                    <td>
                                        <?php
                                        $statusClass = 'secondary';
                                        $statusText = '알 수 없음';
                                        
                                        switch($store['status']) {
                                            case 'active':
                                                $statusClass = 'success';
                                                $statusText = '활성';
                                                break;
                                            case 'inactive':
                                                $statusClass = 'warning';
                                                $statusText = '비활성';
                                                break;
                                            case 'pending':
                                                $statusClass = 'info';
                                                $statusText = '대기중';
                                                break;
                                            case 'terminated':
                                                $statusClass = 'danger';
                                                $statusText = '계약해지';
                                                break;
                                        }
                                        ?>
                                        <span class="badge badge-<?php echo $statusClass; ?>">
                                            <?php echo $statusText; ?>
                                        </span>
                                    </td>
                                    <td><?php echo number_format($store['total_sales']); ?></td>
                                    <td>₹ <?php echo number_format($store['sales_amount']); ?></td>
                                    <td><?php echo number_format($store['unique_customers']); ?></td>
                                    <td>
                                        <a href="store-performance.php?store_id=<?php echo $store['id']; ?>" class="btn btn-sm btn-info">
                                            <i class="fas fa-chart-bar"></i> 상세 성과
                                        </a>
                                        <a href="store-details.php?id=<?php echo $store['id']; ?>" class="btn btn-sm btn-default">
                                            <i class="fas fa-info-circle"></i> 상세 정보
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center">판매 데이터가 없습니다.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer clearfix">
                <div class="float-left">
                    <p>총 <?php echo number_format($totalCount); ?>개의 판매점 중 <?php echo count($storeSales); ?>개 표시 (페이지 <?php echo $page; ?>/<?php echo $totalPages; ?>)</p>
                </div>
                <ul class="pagination pagination-sm m-0 float-right">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&product_type=<?php echo urlencode($productType); ?>&date_range=<?php echo urlencode($dateRange); ?>&date_from=<?php echo urlencode($dateFrom); ?>&date_to=<?php echo urlencode($dateTo); ?>&sort=<?php echo urlencode($sortBy); ?>&order=<?php echo urlencode($sortOrder); ?>&page=<?php echo $page - 1; ?>">
                                &laquo;
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <?php
                    $startPage = max(1, $page - 2);
                    $endPage = min($startPage + 4, $totalPages);
                    
                    if ($endPage - $startPage < 4 && $startPage > 1) {
                        $startPage = max(1, $endPage - 4);
                    }
                    
                    for ($i = $startPage; $i <= $endPage; $i++):
                    ?>
                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&product_type=<?php echo urlencode($productType); ?>&date_range=<?php echo urlencode($dateRange); ?>&date_from=<?php echo urlencode($dateFrom); ?>&date_to=<?php echo urlencode($dateTo); ?>&sort=<?php echo urlencode($sortBy); ?>&order=<?php echo urlencode($sortOrder); ?>&page=<?php echo $i; ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&product_type=<?php echo urlencode($productType); ?>&date_range=<?php echo urlencode($dateRange); ?>&date_from=<?php echo urlencode($dateFrom); ?>&date_to=<?php echo urlencode($dateTo); ?>&sort=<?php echo urlencode($sortBy); ?>&order=<?php echo urlencode($sortOrder); ?>&page=<?php echo $page + 1; ?>">
                                &raquo;
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</section>
<!-- /.content -->

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 날짜 범위 선택 이벤트
    document.getElementById('date_range').addEventListener('change', function() {
        const customDateRange = document.querySelector('.custom-date-range');
        if (this.value === 'custom') {
            customDateRange.style.display = 'flex';
        } else {
            customDateRange.style.display = 'none';
        }
    });
});
</script>

<?php include_once TEMPLATES_PATH . '/dashboard_footer.php'; ?>
