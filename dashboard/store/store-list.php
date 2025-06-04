<?php
/**
 * 판매점 목록 페이지
 * 
 * 필터링 및 페이지네이션을 통한 모든 복권 판매점 목록 표시
 */

// 설정 및 공통 함수
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';
require_once '../../includes/db.php';

// 데이터베이스 연결
$db = get_db_connection();

// 현재 페이지 정보
$pageTitle = "판매점 목록";
$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir = dirname($_SERVER['PHP_SELF']);
$parts = explode('/', $currentDir);
$currentSection = end($parts);

// Initialize variables
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$status = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';
$category = isset($_GET['category']) ? sanitizeInput($_GET['category']) : '';
$city = isset($_GET['city']) ? sanitizeInput($_GET['city']) : '';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;
$sortBy = isset($_GET['sort']) ? sanitizeInput($_GET['sort']) : 'store_name';
$sortOrder = isset($_GET['order']) && strtolower($_GET['order']) === 'desc' ? 'DESC' : 'ASC';

// Build query conditions
$conditions = [];
$params = [];

if (!empty($search)) {
    $conditions[] = "(store_name LIKE ? OR store_code LIKE ? OR owner_name LIKE ? OR phone LIKE ? OR email LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if (!empty($status)) {
    $conditions[] = "status = ?";
    $params[] = $status;
}

if (!empty($category)) {
    $conditions[] = "store_category = ?";
    $params[] = $category;
}

if (!empty($city)) {
    $conditions[] = "city = ?";
    $params[] = $city;
}

// Combine all conditions
$whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

// Get total count for pagination
$countSql = "SELECT COUNT(*) as total FROM stores $whereClause";

// 전역 쿼리 타입 설정
global $query_type;
$query_type = "store_count";

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

// Validate and adjust pagination
if ($page < 1) $page = 1;
if ($page > $totalPages && $totalPages > 0) $page = $totalPages;

// Get store list with pagination and sorting
$sql = "SELECT s.*, 
        (SELECT COUNT(*) FROM store_equipment WHERE store_id = s.id) as equipment_count
        FROM stores s
        $whereClause
        ORDER BY $sortBy $sortOrder
        LIMIT ?, ?";

// 메인 쿼리 타입 설정
$query_type = "store_list";

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
$stores = $stmt->fetchAll();

// Get list of cities for filter dropdown
$citiesSql = "SELECT DISTINCT city FROM stores ORDER BY city";
$citiesResult = $db->query($citiesSql);
$cities = [];

while ($row = $citiesResult->fetch(PDO::FETCH_ASSOC)) {
    $cities[] = $row['city'];
}

// 메시지 초기화
$message = '';
$message_type = '';

// Include header template
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
        <!-- 알림 메시지 -->
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
                <?php echo $message; ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <!-- 검색 및 필터 -->
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
                <form method="get" action="store-list.php">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="search">검색어</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                                    </div>
                                    <input type="text" class="form-control" id="search" name="search" 
                                           placeholder="판매점명, 코드, 대표자명" value="<?php echo htmlspecialchars($search); ?>">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="status">상태</label>
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
                                <label for="category">카테고리</label>
                                <select class="form-control" id="category" name="category">
                                    <option value="">모든 카테고리</option>
                                    <option value="standard" <?php echo $category === 'standard' ? 'selected' : ''; ?>>일반</option>
                                    <option value="premium" <?php echo $category === 'premium' ? 'selected' : ''; ?>>프리미엄</option>
                                    <option value="exclusive" <?php echo $category === 'exclusive' ? 'selected' : ''; ?>>전용</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="city">도시</label>
                                <select class="form-control" id="city" name="city">
                                    <option value="">모든 도시</option>
                                    <?php foreach ($cities as $cityOption): ?>
                                        <option value="<?php echo htmlspecialchars($cityOption); ?>" <?php echo $city === $cityOption ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cityOption); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label class="d-block">&nbsp;</label>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-filter"></i> 적용
                                </button>
                                <a href="store-list.php" class="btn btn-default">
                                    <i class="fas fa-sync-alt"></i> 초기화
                                </a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- 판매점 목록 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">판매점 목록</h3>
                <div class="card-tools">
                    <a href="store-add.php" class="btn btn-success btn-sm mr-2">
                        <i class="fas fa-plus"></i> 새 판매점 등록
                    </a>
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                    <div class="btn-group">
                        <button type="button" class="btn btn-tool dropdown-toggle" data-toggle="dropdown">
                            <i class="fas fa-download"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-right">
                            <a href="#" class="dropdown-item" id="exportExcel">
                                <i class="fas fa-file-excel mr-2"></i> Excel로 내보내기
                            </a>
                            <a href="#" class="dropdown-item" id="exportCsv">
                                <i class="fas fa-file-csv mr-2"></i> CSV로 내보내기
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if (empty($stores)): ?>
                <div class="alert alert-info m-3">
                    <i class="fas fa-info-circle"></i> 검색 조건에 맞는 판매점이 없습니다.
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>
                                    <a href="?search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&category=<?php echo urlencode($category); ?>&city=<?php echo urlencode($city); ?>&sort=store_code&order=<?php echo $sortBy === 'store_code' && $sortOrder === 'ASC' ? 'DESC' : 'ASC'; ?>">
                                        코드
                                        <?php if ($sortBy === 'store_code'): ?>
                                            <i class="fas fa-sort-<?php echo $sortOrder === 'ASC' ? 'up' : 'down'; ?>"></i>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th>
                                    <a href="?search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&category=<?php echo urlencode($category); ?>&city=<?php echo urlencode($city); ?>&sort=store_name&order=<?php echo $sortBy === 'store_name' && $sortOrder === 'ASC' ? 'DESC' : 'ASC'; ?>">
                                        판매점명
                                        <?php if ($sortBy === 'store_name'): ?>
                                            <i class="fas fa-sort-<?php echo $sortOrder === 'ASC' ? 'up' : 'down'; ?>"></i>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th>
                                    <a href="?search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&category=<?php echo urlencode($category); ?>&city=<?php echo urlencode($city); ?>&sort=owner_name&order=<?php echo $sortBy === 'owner_name' && $sortOrder === 'ASC' ? 'DESC' : 'ASC'; ?>">
                                        대표자명
                                        <?php if ($sortBy === 'owner_name'): ?>
                                            <i class="fas fa-sort-<?php echo $sortOrder === 'ASC' ? 'up' : 'down'; ?>"></i>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th>
                                    <a href="?search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&category=<?php echo urlencode($category); ?>&city=<?php echo urlencode($city); ?>&sort=phone&order=<?php echo $sortBy === 'phone' && $sortOrder === 'ASC' ? 'DESC' : 'ASC'; ?>">
                                        연락처
                                        <?php if ($sortBy === 'phone'): ?>
                                            <i class="fas fa-sort-<?php echo $sortOrder === 'ASC' ? 'up' : 'down'; ?>"></i>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th>
                                    <a href="?search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&category=<?php echo urlencode($category); ?>&city=<?php echo urlencode($city); ?>&sort=city&order=<?php echo $sortBy === 'city' && $sortOrder === 'ASC' ? 'DESC' : 'ASC'; ?>">
                                        지역
                                        <?php if ($sortBy === 'city'): ?>
                                            <i class="fas fa-sort-<?php echo $sortOrder === 'ASC' ? 'up' : 'down'; ?>"></i>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th>
                                    <a href="?search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&category=<?php echo urlencode($category); ?>&city=<?php echo urlencode($city); ?>&sort=registration_date&order=<?php echo $sortBy === 'registration_date' && $sortOrder === 'ASC' ? 'DESC' : 'ASC'; ?>">
                                        등록일
                                        <?php if ($sortBy === 'registration_date'): ?>
                                            <i class="fas fa-sort-<?php echo $sortOrder === 'ASC' ? 'up' : 'down'; ?>"></i>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th>
                                    <a href="?search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&category=<?php echo urlencode($category); ?>&city=<?php echo urlencode($city); ?>&sort=status&order=<?php echo $sortBy === 'status' && $sortOrder === 'ASC' ? 'DESC' : 'ASC'; ?>">
                                        상태
                                        <?php if ($sortBy === 'status'): ?>
                                            <i class="fas fa-sort-<?php echo $sortOrder === 'ASC' ? 'up' : 'down'; ?>"></i>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th>장비</th>
                                <th>작업</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stores as $store): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($store['store_code'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($store['store_name'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($store['owner_name'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($store['phone'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($store['city'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($store['registration_date'] ?? ''); ?></td>
                                <td>
                                    <?php
                                    $statusClass = '';
                                    $statusText = '';
                                    
                                    switch($store['status'] ?? '') {
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
                                        default:
                                            $statusClass = 'secondary';
                                            $statusText = '미정';
                                    }
                                    ?>
                                    <span class="badge badge-<?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                                </td>
                                <td>
                                    <span class="badge badge-primary"><?php echo isset($store['equipment_count']) ? number_format($store['equipment_count']) : '0'; ?></span>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="store-details.php?id=<?php echo $store['id']; ?>" class="btn btn-sm btn-info" title="상세 정보">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="store-edit.php?id=<?php echo $store['id']; ?>" class="btn btn-sm btn-primary" title="수정">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="store-contracts.php?store_id=<?php echo $store['id']; ?>" class="btn btn-sm btn-warning" title="계약 관리">
                                            <i class="fas fa-file-contract"></i>
                                        </a>
                                        <a href="equipment-list.php?store_id=<?php echo $store['id']; ?>" class="btn btn-sm btn-success" title="장비 관리">
                                            <i class="fas fa-desktop"></i>
                                        </a>
                                        <a href="store-performance.php?store_id=<?php echo $store['id']; ?>" class="btn btn-sm btn-default" title="성과 관리">
                                            <i class="fas fa-chart-line"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
            <div class="card-footer clearfix">
                <?php if ($totalPages > 1): ?>
                <div class="float-left">
                    <p>총 <?php echo number_format($totalCount); ?>개의 판매점 중 <?php echo count($stores); ?>개 표시 (페이지 <?php echo $page; ?>/<?php echo $totalPages; ?>)</p>
                </div>
                <ul class="pagination pagination-sm m-0 float-right">
                    <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&category=<?php echo urlencode($category); ?>&city=<?php echo urlencode($city); ?>&sort=<?php echo urlencode($sortBy); ?>&order=<?php echo urlencode($sortOrder); ?>&page=<?php echo $page - 1; ?>">
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
                            <a class="page-link" href="?search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&category=<?php echo urlencode($category); ?>&city=<?php echo urlencode($city); ?>&sort=<?php echo urlencode($sortBy); ?>&order=<?php echo urlencode($sortOrder); ?>&page=<?php echo $i; ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&category=<?php echo urlencode($category); ?>&city=<?php echo urlencode($city); ?>&sort=<?php echo urlencode($sortBy); ?>&order=<?php echo urlencode($sortOrder); ?>&page=<?php echo $page + 1; ?>">
                            &raquo;
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
                <?php else: ?>
                <div class="float-left">
                    <p>총 <?php echo number_format($totalCount); ?>개의 판매점 중 <?php echo count($stores); ?>개 표시</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
<!-- /.content -->

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Excel 내보내기 버튼 클릭 이벤트
    document.getElementById('exportExcel').addEventListener('click', function(e) {
        e.preventDefault();
        
        // 내보내기 URL 구성
        var exportUrl = 'store-export.php?format=excel';
        // 현재 검색 및 필터 조건 추가
        var searchParams = new URLSearchParams(window.location.search);
        if (searchParams.has('search')) exportUrl += '&search=' + encodeURIComponent(searchParams.get('search'));
        if (searchParams.has('status')) exportUrl += '&status=' + encodeURIComponent(searchParams.get('status'));
        if (searchParams.has('category')) exportUrl += '&category=' + encodeURIComponent(searchParams.get('category'));
        if (searchParams.has('city')) exportUrl += '&city=' + encodeURIComponent(searchParams.get('city'));
        if (searchParams.has('sort')) exportUrl += '&sort=' + encodeURIComponent(searchParams.get('sort'));
        if (searchParams.has('order')) exportUrl += '&order=' + encodeURIComponent(searchParams.get('order'));
        
        // 페이지 이동
        window.location.href = exportUrl;
    });
    
    // CSV 내보내기 버튼 클릭 이벤트
    document.getElementById('exportCsv').addEventListener('click', function(e) {
        e.preventDefault();
        
        // 내보내기 URL 구성
        var exportUrl = 'store-export.php?format=csv';
        // 현재 검색 및 필터 조건 추가
        var searchParams = new URLSearchParams(window.location.search);
        if (searchParams.has('search')) exportUrl += '&search=' + encodeURIComponent(searchParams.get('search'));
        if (searchParams.has('status')) exportUrl += '&status=' + encodeURIComponent(searchParams.get('status'));
        if (searchParams.has('category')) exportUrl += '&category=' + encodeURIComponent(searchParams.get('category'));
        if (searchParams.has('city')) exportUrl += '&city=' + encodeURIComponent(searchParams.get('city'));
        if (searchParams.has('sort')) exportUrl += '&sort=' + encodeURIComponent(searchParams.get('sort'));
        if (searchParams.has('order')) exportUrl += '&order=' + encodeURIComponent(searchParams.get('order'));
        
        // 페이지 이동
        window.location.href = exportUrl;
    });
});
</script>

<?php include_once TEMPLATES_PATH . '/dashboard_footer.php'; ?>
