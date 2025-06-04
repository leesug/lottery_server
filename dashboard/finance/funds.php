<?php
/**
 * 재무 관리 - 기금 관리 페이지
 * 
 * 이 페이지는 기금 목록을 표시하고 관리하는 기능을 제공합니다.
 */

// 세션 시작 및 필수 파일 포함
session_start();
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// 인증 확인
checkAuth();

// 필요한 권한 확인
$requiredPermissions = ['finance_view', 'finance_funds_view'];
checkPermissions($requiredPermissions);

// 페이지 제목 설정
$pageTitle = "기금 관리";
$currentSection = "finance";
$currentPage = "funds";

// 데이터베이스 연결
$conn = getDBConnection();

// 검색 및 필터링 파라미터
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$fundType = isset($_GET['fund_type']) ? sanitizeInput($_GET['fund_type']) : '';
$status = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';

// 페이지네이션 설정
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$recordsPerPage = 10;
$offset = ($page - 1) * $recordsPerPage;

// SQL 쿼리 기본 구조
$sql = "SELECT * FROM funds WHERE 1=1";
$countSql = "SELECT COUNT(*) FROM funds WHERE 1=1";
$params = [];

// 검색 조건 추가
if (!empty($search)) {
    $sql .= " AND (fund_name LIKE ? OR fund_code LIKE ? OR description LIKE ?)";
    $countSql .= " AND (fund_name LIKE ? OR fund_code LIKE ? OR description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($fundType)) {
    $sql .= " AND fund_type = ?";
    $countSql .= " AND fund_type = ?";
    $params[] = $fundType;
}

if (!empty($status)) {
    $sql .= " AND status = ?";
    $countSql .= " AND status = ?";
    $params[] = $status;
}

// 정렬 추가
$sql .= " ORDER BY fund_name ASC";

// 페이지네이션 추가
$sql .= " LIMIT ?, ?";
$countParams = $params;
$params[] = $offset;
$params[] = $recordsPerPage;

// 쿼리 실행
$stmt = $conn->prepare($sql);
if (!$stmt) {
    logError("Database prepare error: " . $conn->error);
    die("데이터베이스 오류가 발생했습니다.");
}

// PDO 방식으로 파라미터 바인딩
if (count($params) > 0) {
    for ($i = 0; $i < count($params); $i++) {
        $stmt->bindValue($i + 1, $params[$i]);
    }
}

$stmt->execute();
// PDO에서는 fetch/fetchAll 메소드를 사용하여 결과 가져오기
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 전체 레코드 수 쿼리
$countStmt = $conn->prepare($countSql);
// PDO 방식으로 카운트 파라미터 바인딩
if (count($countParams) > 0) {
    for ($i = 0; $i < count($countParams); $i++) {
        $countStmt->bindValue($i + 1, $countParams[$i]);
    }
}
$countStmt->execute();
// PDO에서는 fetch 메소드를 사용하여 결과 가져오기
$totalRecords = $countStmt->fetchColumn();
$totalPages = ceil($totalRecords / $recordsPerPage);

// 통계 정보 조회
$statsSql = "SELECT 
              COUNT(*) as total_funds,
              SUM(current_balance) as total_balance,
              SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_funds,
              SUM(CASE WHEN status = 'active' THEN current_balance ELSE 0 END) as active_balance
              FROM funds";
$statsStmt = $conn->prepare($statsSql);
$statsStmt->execute();
// PDO에서는 fetch 메소드를 사용하여 결과 가져오기
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

// 기본값 설정
$stats = array_merge([
    'total_funds' => 0,
    'total_balance' => 0,
    'active_funds' => 0,
    'active_balance' => 0
], $stats ?? []);

// 기금 유형 및 상태 옵션
$fundTypes = [
    'prize' => '당첨금 기금', 
    'charity' => '자선 기금', 
    'development' => '개발 기금', 
    'operational' => '운영 기금', 
    'reserve' => '예비 기금', 
    'other' => '기타 기금'
];

$fundStatuses = [
    'active' => '활성', 
    'inactive' => '비활성', 
    'depleted' => '소진됨'
];

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
                    <li class="breadcrumb-item">재무 관리</li>
                    <li class="breadcrumb-item active">기금 관리</li>
                </ol>
            </div>
        </div>
    </div>
</div>
<!-- /.content-header -->

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <!-- 통계 카드 -->
        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3><?php echo number_format($stats['total_funds']); ?></h3>
                        <p>전체 기금 수</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3><?php echo number_format($stats['active_funds'] ?? 0); ?></h3>
                        <p>활성 기금 수</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3><?php echo number_format($stats['total_balance'] ?? 0, 2); ?></h3>
                        <p>전체 기금 잔액 (NPR)</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3><?php echo number_format($stats['active_balance'] ?? 0, 2); ?></h3>
                        <p>활성 기금 잔액 (NPR)</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-chart-pie"></i>
                    </div>
                </div>
            </div>
        </div>
        
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
                <form method="get" action="">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="search">검색어</label>
                                <input type="text" class="form-control" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="기금명, 코드, 설명">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="fund_type">기금 유형</label>
                                <select class="form-control" id="fund_type" name="fund_type">
                                    <option value="">전체</option>
                                    <?php foreach ($fundTypes as $key => $value): ?>
                                        <option value="<?php echo $key; ?>" <?php echo ($fundType == $key) ? 'selected' : ''; ?>>
                                            <?php echo $value; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="status">상태</label>
                                <select class="form-control" id="status" name="status">
                                    <option value="">전체</option>
                                    <?php foreach ($fundStatuses as $key => $value): ?>
                                        <option value="<?php echo $key; ?>" <?php echo ($status == $key) ? 'selected' : ''; ?>>
                                            <?php echo $value; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group pt-4 mt-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> 검색
                                </button>
                                <a href="funds.php" class="btn btn-secondary">
                                    <i class="fas fa-redo"></i> 초기화
                                </a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- 기금 목록 카드 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-money-bill-wave mr-1"></i>
                    기금 목록
                </h3>
                <div class="card-tools">
                    <?php if (hasPermission('finance_funds_add')): ?>
                        <a href="fund-add.php" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> 새 기금 추가
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>기금 코드</th>
                                <th>기금명</th>
                                <th>유형</th>
                                <th>총 할당액</th>
                                <th>현재 잔액</th>
                                <th>할당 비율</th>
                                <th>상태</th>
                                <th>작업</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($result) > 0): ?>
                                <?php foreach ($result as $row): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['fund_code']); ?></td>
                                        <td><?php echo htmlspecialchars($row['fund_name']); ?></td>
                                        <td><?php echo $fundTypes[$row['fund_type']] ?? $row['fund_type']; ?></td>
                                        <td class="text-right"><?php echo number_format($row['total_allocation'], 2) . ' NPR'; ?></td>
                                        <td class="text-right">
                                            <?php 
                                                $balanceClass = '';
                                                if ($row['current_balance'] <= 0) {
                                                    $balanceClass = 'text-danger';
                                                } elseif ($row['current_balance'] < ($row['total_allocation'] * 0.1)) {
                                                    $balanceClass = 'text-warning';
                                                } else {
                                                    $balanceClass = 'text-success';
                                                }
                                                echo '<span class="' . $balanceClass . '">' . number_format($row['current_balance'], 2) . ' NPR</span>'; 
                                            ?>
                                        </td>
                                        <td class="text-center">
                                            <?php 
                                                if (!empty($row['allocation_percentage'])) {
                                                    echo $row['allocation_percentage'] . '%';
                                                } else {
                                                    echo '-';
                                                }
                                            ?>
                                        </td>
                                        <td>
                                            <?php 
                                                $statusClass = '';
                                                switch($row['status']) {
                                                    case 'active':
                                                        $statusClass = 'badge bg-success';
                                                        break;
                                                    case 'inactive':
                                                        $statusClass = 'badge bg-warning';
                                                        break;
                                                    case 'depleted':
                                                        $statusClass = 'badge bg-danger';
                                                        break;
                                                }
                                                echo '<span class="' . $statusClass . '">' . $fundStatuses[$row['status']] . '</span>';
                                            ?>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="fund-details.php?id=<?php echo $row['id']; ?>" class="btn btn-info btn-xs" title="상세보기">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <?php if (hasPermission('finance_funds_edit')): ?>
                                                    <a href="fund-edit.php?id=<?php echo $row['id']; ?>" class="btn btn-primary btn-xs" title="수정">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <?php if (hasPermission('finance_funds_transactions')): ?>
                                                    <a href="fund-transaction-add.php?fund_id=<?php echo $row['id']; ?>" class="btn btn-success btn-xs" title="거래 추가">
                                                        <i class="fas fa-plus-circle"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center">검색 결과가 없습니다.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- 페이지네이션 -->
            <?php if ($totalPages > 1): ?>
                <div class="card-footer clearfix">
                    <ul class="pagination pagination-sm m-0 float-right">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=1<?php echo getQueryParams(['page']); ?>">&laquo;</a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo getQueryParams(['page']); ?>">&lsaquo;</a>
                            </li>
                        <?php endif; ?>

                        <?php
                        // 페이지 범위 계산
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $page + 2);

                        for ($i = $startPage; $i <= $endPage; $i++): 
                        ?>
                            <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?><?php echo getQueryParams(['page']); ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo getQueryParams(['page']); ?>">&rsaquo;</a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $totalPages; ?><?php echo getQueryParams(['page']); ?>">&raquo;</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
<!-- /.content -->

<?php include_once TEMPLATES_PATH . '/dashboard_footer.php'; ?>

<script>
$(document).ready(function() {
    // 데이터 테이블 초기화
    $('.table').DataTable({
        "paging": false,
        "lengthChange": false,
        "searching": false,
        "ordering": true,
        "info": false,
        "autoWidth": false,
        "responsive": true,
        "language": {
            "emptyTable": "데이터가 없습니다.",
            "info": "총 _TOTAL_개 중 _START_에서 _END_까지 표시",
            "infoEmpty": "0개 중 0에서 0까지 표시",
            "infoFiltered": "(총 _MAX_개 중에서 필터링됨)",
            "lengthMenu": "_MENU_개씩 보기",
            "search": "검색:",
            "zeroRecords": "일치하는 레코드가 없습니다.",
            "paginate": {
                "first": "처음",
                "last": "마지막",
                "next": "다음",
                "previous": "이전"
            }
        }
    });
});
</script>
