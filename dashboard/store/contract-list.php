<?php
/**
 * 판매점 계약 목록 페이지
 * 
 * 이 페이지는 판매점과의 계약 목록을 보여줍니다.
 */

// 필요한 파일 포함
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

// 페이지 접근 권한 확인
checkPageAccess('store_management');

// 데이터베이스 연결
$db = get_db_connection();

// 검색 조건 초기화
$filterStore = isset($_GET['store_id']) ? (int)$_GET['store_id'] : 0;
$filterStatus = isset($_GET['status']) ? trim($_GET['status']) : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// 알림 메시지
$message = '';
$message_type = '';
if (isset($_GET['updated']) && $_GET['updated'] == 1) {
    $message = "계약 정보가 성공적으로 수정되었습니다.";
    $message_type = "success";
} elseif (isset($_GET['created']) && $_GET['created'] == 1) {
    $message = "새 계약이 성공적으로 등록되었습니다.";
    $message_type = "success";
} elseif (isset($_GET['status_changed']) && $_GET['status_changed'] == 1) {
    $message = "계약 상태가 변경되었습니다.";
    $message_type = "success";
} elseif (isset($_GET['renewed']) && $_GET['renewed'] == 1) {
    $message = "계약이 성공적으로 갱신되었습니다.";
    $message_type = "success";
}

// 계약 목록 쿼리 생성
$query = "
    SELECT c.*, s.store_name, s.owner_name 
    FROM contracts c
    JOIN stores s ON c.store_id = s.id
    WHERE 1=1
";
$params = [];

// 검색 필터 적용
if ($filterStore > 0) {
    $query .= " AND c.store_id = ?";
    $params[] = $filterStore;
}

if (!empty($filterStatus)) {
    $query .= " AND c.status = ?";
    $params[] = $filterStatus;
}

if (!empty($search)) {
    $query .= " AND (s.store_name LIKE ? OR s.owner_name LIKE ? OR c.contract_code LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

// 전체 행 수 조회 (페이징용)
$countQuery = preg_replace('/SELECT.*?FROM/is', 'SELECT COUNT(*) as total FROM', $query);
$countStmt = $db->prepare($countQuery);

// MockPDO 타입 설정 (필요한 경우)
if (method_exists($countStmt, 'setQueryType')) {
    $countStmt->setQueryType('count_contracts');
}

for ($i = 0; $i < count($params); $i++) {
    $countStmt->bindParam($i+1, $params[$i]);
}
$countStmt->execute();
$totalRows = $countStmt->fetchColumn();
$totalPages = ceil($totalRows / $limit);

// 정렬 및 페이지네이션 적용
$query .= " ORDER BY c.created_at DESC LIMIT $offset, $limit";

// 계약 목록 가져오기
$stmt = $db->prepare($query);

// MockPDO 타입 설정 (필요한 경우)
if (method_exists($stmt, 'setQueryType')) {
    $stmt->setQueryType('contracts_list');
}

for ($i = 0; $i < count($params); $i++) {
    $stmt->bindParam($i+1, $params[$i]);
}
$stmt->execute();
$contracts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 판매점 목록 가져오기 (필터용)
$storeQuery = "SELECT id, store_name FROM stores ORDER BY store_name";
$storeStmt = $db->prepare($storeQuery);

// MockPDO 타입 설정 (필요한 경우)
if (method_exists($storeStmt, 'setQueryType')) {
    $storeStmt->setQueryType('stores_list');
}

$storeStmt->execute();
$stores = $storeStmt->fetchAll(PDO::FETCH_ASSOC);

// 페이지 정보 설정
$pageTitle = "판매점 계약 목록";
$currentSection = "store";
$currentPage = basename($_SERVER['PHP_SELF']);

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
        <!-- 알림 메시지 -->
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
                <?php echo $message; ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <!-- 필터 및 검색 카드 -->
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
                <form method="get" id="filterForm" class="mb-0">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="store_id">판매점 선택</label>
                                <select class="form-control" id="store_id" name="store_id">
                                    <option value="0">모든 판매점</option>
                                    <?php foreach ($stores as $store): ?>
                                        <option value="<?php echo $store['id']; ?>" <?php echo $filterStore == $store['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($store['store_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="status">계약 상태</label>
                                <select class="form-control" id="status" name="status">
                                    <option value="">모든 상태</option>
                                    <option value="active" <?php echo $filterStatus === 'active' ? 'selected' : ''; ?>>활성</option>
                                    <option value="pending" <?php echo $filterStatus === 'pending' ? 'selected' : ''; ?>>대기중</option>
                                    <option value="expired" <?php echo $filterStatus === 'expired' ? 'selected' : ''; ?>>만료됨</option>
                                    <option value="terminated" <?php echo $filterStatus === 'terminated' ? 'selected' : ''; ?>>해지됨</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="search">검색어</label>
                                <input type="text" class="form-control" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="판매점명, 대표자명, 계약번호...">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search"></i> 검색
                                    </button>
                                    <a href="<?php echo SERVER_URL; ?>/dashboard/store/contract-list.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-sync-alt"></i> 초기화
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- 계약 목록 카드 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">판매점 계약 목록 (<?php echo $totalRows; ?>건)</h3>
                <div class="card-tools">
                    <a href="<?php echo SERVER_URL; ?>/dashboard/store/contract-add.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus"></i> 새 계약 등록
                    </a>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>계약 코드</th>
                                <th>판매점명</th>
                                <th>계약 시작일</th>
                                <th>계약 만료일</th>
                                <th>상태</th>
                                <th>계약 유형</th>
                                <th>커미션 비율</th>
                                <th class="text-center">관리</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($contracts) > 0): ?>
                                <?php foreach ($contracts as $contract): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($contract['contract_code']); ?></td>
                                        <td><?php echo htmlspecialchars($contract['store_name']); ?></td>
                                        <td><?php echo date('Y-m-d', strtotime($contract['start_date'])); ?></td>
                                        <td><?php echo date('Y-m-d', strtotime($contract['end_date'])); ?></td>
                                        <td>
                                            <?php
                                            $statusBadge = '';
                                            switch ($contract['status']) {
                                                case 'active':
                                                    $statusBadge = '<span class="badge badge-success">활성</span>';
                                                    break;
                                                case 'pending':
                                                    $statusBadge = '<span class="badge badge-warning">대기중</span>';
                                                    break;
                                                case 'expired':
                                                    $statusBadge = '<span class="badge badge-danger">만료됨</span>';
                                                    break;
                                                case 'terminated':
                                                    $statusBadge = '<span class="badge badge-dark">해지됨</span>';
                                                    break;
                                                default:
                                                    $statusBadge = '<span class="badge badge-secondary">기타</span>';
                                            }
                                            echo $statusBadge;
                                            ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($contract['contract_type']); ?></td>
                                        <td><?php echo number_format($contract['commission_rate'], 2); ?>%</td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm">
                                                <a href="<?php echo SERVER_URL; ?>/dashboard/store/contract-details.php?id=<?php echo $contract['id']; ?>" class="btn btn-info" title="상세보기">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="<?php echo SERVER_URL; ?>/dashboard/store/contract-edit.php?id=<?php echo $contract['id']; ?>" class="btn btn-primary" title="편집">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="<?php echo SERVER_URL; ?>/dashboard/store/contract-print.php?id=<?php echo $contract['id']; ?>" class="btn btn-secondary" title="인쇄" target="_blank">
                                                    <i class="fas fa-print"></i>
                                                </a>
                                                <?php if ($contract['status'] === 'active'): ?>
                                                    <a href="<?php echo SERVER_URL; ?>/dashboard/store/contract-renew.php?id=<?php echo $contract['id']; ?>" class="btn btn-success" title="계약 갱신">
                                                        <i class="fas fa-sync-alt"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="fas fa-info-circle mr-1"></i> 검색 조건에 맞는 계약 정보가 없습니다.
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer clearfix">
                <?php if ($totalPages > 1): ?>
                    <ul class="pagination pagination-sm m-0 float-right">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=1&store_id=<?php echo $filterStore; ?>&status=<?php echo $filterStatus; ?>&search=<?php echo urlencode($search); ?>">«</a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>&store_id=<?php echo $filterStore; ?>&status=<?php echo $filterStatus; ?>&search=<?php echo urlencode($search); ?>">‹</a>
                            </li>
                        <?php endif; ?>
                        
                        <?php
                        // 페이지 범위 계산
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $page + 2);
                        
                        for ($i = $startPage; $i <= $endPage; $i++):
                        ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&store_id=<?php echo $filterStore; ?>&status=<?php echo $filterStatus; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>&store_id=<?php echo $filterStore; ?>&status=<?php echo $filterStatus; ?>&search=<?php echo urlencode($search); ?>">›</a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $totalPages; ?>&store_id=<?php echo $filterStore; ?>&status=<?php echo $filterStatus; ?>&search=<?php echo urlencode($search); ?>">»</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
<!-- /.content -->

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('Contract list page loaded');
    // 필터 적용 시 자동 변경
    document.getElementById('store_id').addEventListener('change', function() {
        console.log('Store filter changed');
        document.getElementById('filterForm').submit();
    });
    
    document.getElementById('status').addEventListener('change', function() {
        console.log('Status filter changed');
        document.getElementById('filterForm').submit();
    });
});
</script>

<?php include_once TEMPLATES_PATH . '/dashboard_footer.php'; ?>
