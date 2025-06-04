<?php
// 용지박스 목록
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

// 세션 시작
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 기본 사용자 정보 설정 (세션 관리가 비활성화되어 있으므로)
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;  // 기본 관리자 ID
    $_SESSION['username'] = '관리자';
    $_SESSION['role'] = 'admin';
}

// 로그인 체크
checkLogin();

// 페이지 제목
$pageTitle = "용지박스 목록";
$currentSection = "store";
$currentPage = "paper-box-list.php";

// 데이터베이스 연결
$conn = get_db_connection();

// 검색 및 필터링
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// 기본 쿼리
$whereConditions = [];
$params = [];

if (!empty($search)) {
    $whereConditions[] = "(pb.box_code LIKE ? OR pb.qr_code LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($status)) {
    $whereConditions[] = "pb.status = ?";
    $params[] = $status;
}

$whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

// 전체 개수 조회
$countQuery = "
    SELECT COUNT(*) as total
    FROM paper_boxes pb
    $whereClause
";
$stmt = $conn->prepare($countQuery);
if (!empty($params)) {
    $stmt->execute($params);
} else {
    $stmt->execute();
}
$totalCount = $stmt->fetch()['total'];
$totalPages = ceil($totalCount / $perPage);

// 박스 목록 조회
$boxesQuery = "
    SELECT 
        pb.*,
        s.name as store_name,
        s.code as store_code,
        COUNT(pr.id) as roll_count,
        SUM(CASE WHEN pr.status = 'active' THEN 1 ELSE 0 END) as active_rolls,
        u.username as created_by_name
    FROM paper_boxes pb
    LEFT JOIN stores s ON pb.store_id = s.id
    LEFT JOIN paper_rolls pr ON pr.box_id = pb.id
    LEFT JOIN users u ON pb.created_by = u.id
    $whereClause
    GROUP BY pb.id
    ORDER BY pb.created_at DESC
    LIMIT $perPage OFFSET $offset
";

$stmt = $conn->prepare($boxesQuery);
if (!empty($params)) {
    $stmt->execute($params);
} else {
    $stmt->execute();
}
$boxes = $stmt->fetchAll();

// 상태별 통계
$statsQuery = "
    SELECT 
        status,
        COUNT(*) as count
    FROM paper_boxes
    GROUP BY status
";
$stats = $conn->query($statsQuery)->fetchAll(PDO::FETCH_KEY_PAIR);

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
                    <li class="breadcrumb-item"><a href="/dashboard/store">판매점 관리</a></li>
                    <li class="breadcrumb-item"><a href="paper-dashboard.php">용지 관리</a></li>
                    <li class="breadcrumb-item active"><?php echo $pageTitle; ?></li>
                </ol>
            </nav>
        </div>
        <div class="col-md-6 text-end">
            <a href="paper-box-register.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> 박스 등록
            </a>
            <a href="paper-dashboard.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> 대시보드
            </a>
        </div>
    </div>

    <!-- 통계 카드 -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                전체 박스
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo number_format($totalCount); ?>개
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-box fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                등록됨
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo number_format($stats['registered'] ?? 0); ?>개
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                할당됨
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo number_format($stats['assigned'] ?? 0); ?>개
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-truck fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                사용완료
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo number_format($stats['used'] ?? 0); ?>개
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-archive fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 검색 및 필터 -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <form method="get" action="" class="row g-3">
                <div class="col-md-4">
                    <label for="search" class="form-label">검색</label>
                    <input type="text" name="search" id="search" class="form-control" 
                           placeholder="박스 코드 또는 QR 코드" value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-3">
                    <label for="status" class="form-label">상태</label>
                    <select name="status" id="status" class="form-select">
                        <option value="">전체</option>
                        <option value="registered" <?php echo $status === 'registered' ? 'selected' : ''; ?>>등록됨</option>
                        <option value="assigned" <?php echo $status === 'assigned' ? 'selected' : ''; ?>>할당됨</option>
                        <option value="used" <?php echo $status === 'used' ? 'selected' : ''; ?>>사용완료</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> 검색
                    </button>
                    <a href="paper-box-list.php" class="btn btn-secondary ms-2">
                        <i class="fas fa-redo"></i> 초기화
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- 박스 목록 -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">박스 목록</h6>
        </div>
        <div class="card-body">
            <?php if (empty($boxes)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-box fa-3x text-gray-300 mb-3"></i>
                    <p class="text-muted">등록된 박스가 없습니다.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th width="15%">박스 코드</th>
                                <th width="20%">QR 코드</th>
                                <th width="10%" class="text-center">롤 개수</th>
                                <th width="10%" class="text-center">활성 롤</th>
                                <th width="15%">판매점</th>
                                <th width="10%" class="text-center">상태</th>
                                <th width="10%">등록일</th>
                                <th width="10%" class="text-center">작업</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($boxes as $box): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($box['box_code']); ?></strong>
                                    </td>
                                    <td>
                                        <small><?php echo htmlspecialchars($box['qr_code']); ?></small>
                                    </td>
                                    <td class="text-center">
                                        <?php echo number_format($box['roll_count']); ?>개
                                    </td>
                                    <td class="text-center">
                                        <?php if ($box['active_rolls'] > 0): ?>
                                            <span class="badge bg-success">
                                                <?php echo number_format($box['active_rolls']); ?>개
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($box['store_id']): ?>
                                            <a href="store-details.php?id=<?php echo $box['store_id']; ?>">
                                                <?php echo htmlspecialchars($box['store_name']); ?>
                                            </a>
                                            <br>
                                            <small class="text-muted"><?php echo htmlspecialchars($box['store_code']); ?></small>
                                        <?php else: ?>
                                            <span class="text-muted">미할당</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php
                                        $statusClass = [
                                            'registered' => 'info',
                                            'assigned' => 'primary',
                                            'used' => 'secondary'
                                        ];
                                        $statusText = [
                                            'registered' => '등록됨',
                                            'assigned' => '할당됨',
                                            'used' => '사용완료'
                                        ];
                                        ?>
                                        <span class="badge bg-<?php echo $statusClass[$box['status']] ?? 'secondary'; ?>">
                                            <?php echo $statusText[$box['status']] ?? $box['status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo date('Y-m-d', strtotime($box['created_at'])); ?>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-info" 
                                                onclick="viewBoxDetails('<?php echo $box['id']; ?>')"
                                                title="상세보기">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <?php if ($box['status'] === 'registered'): ?>
                                            <button type="button" class="btn btn-sm btn-primary" 
                                                    onclick="assignBox('<?php echo $box['id']; ?>')"
                                                    title="판매점 할당">
                                                <i class="fas fa-truck"></i>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- 페이지네이션 -->
                <?php if ($totalPages > 1): ?>
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>">
                                    이전
                                </a>
                            </li>
                            
                            <?php
                            $startPage = max(1, $page - 2);
                            $endPage = min($totalPages, $page + 2);
                            
                            for ($i = $startPage; $i <= $endPage; $i++):
                            ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>">
                                    다음
                                </a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// 박스 상세보기
function viewBoxDetails(boxId) {
    // 추후 구현: 박스 상세 정보 모달 또는 페이지
    alert('박스 ID: ' + boxId + ' 상세보기 기능은 추후 구현됩니다.');
}

// 박스 할당
function assignBox(boxId) {
    // 추후 구현: 판매점 할당 모달
    alert('박스 ID: ' + boxId + ' 판매점 할당 기능은 추후 구현됩니다.');
}
</script>

<?php include_once TEMPLATES_PATH . '/dashboard_footer.php'; ?>
