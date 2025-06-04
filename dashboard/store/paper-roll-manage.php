<?php
// 용지롤 관리
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

// 로그인 체크
checkLogin();

// 페이지 제목
$pageTitle = "용지롤 관리";
$currentSection = "store";
$currentPage = "paper-roll-manage.php";

// 데이터베이스 연결
$conn = get_db_connection();

// 삭제 처리
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $roll_id = intval($_GET['id']);
    
    try {
        // 박스에 할당되지 않은 롤인지 확인
        $checkQuery = "SELECT roll_code, box_id FROM paper_rolls WHERE id = ?";
        $stmt = $conn->prepare($checkQuery);
        $stmt->execute([$roll_id]);
        $roll = $stmt->fetch();
        
        if (!$roll) {
            $_SESSION['error_message'] = "해당 롤을 찾을 수 없습니다.";
        } elseif ($roll['box_id'] != null) {
            $_SESSION['error_message'] = "박스에 할당된 롤은 삭제할 수 없습니다.";
        } else {
            // 삭제 실행
            $deleteQuery = "DELETE FROM paper_rolls WHERE id = ? AND box_id IS NULL";
            $stmt = $conn->prepare($deleteQuery);
            $stmt->execute([$roll_id]);
            
            if ($stmt->rowCount() > 0) {
                $_SESSION['success_message'] = "롤 '{$roll['roll_code']}'이(가) 삭제되었습니다.";
            } else {
                $_SESSION['error_message'] = "롤 삭제에 실패했습니다.";
            }
        }
    } catch (Exception $e) {
        $_SESSION['error_message'] = "삭제 중 오류가 발생했습니다: " . $e->getMessage();
    }
    
    header('Location: paper-roll-manage.php');
    exit;
}

// 필터 파라미터
$filterStatus = isset($_GET['status']) ? $_GET['status'] : 'all';
$filterBox = isset($_GET['box']) ? $_GET['box'] : 'all';
$searchKeyword = isset($_GET['search']) ? $_GET['search'] : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// 조건 구성
$where = [];
$params = [];

if ($filterStatus != 'all') {
    $where[] = "pr.status = ?";
    $params[] = $filterStatus;
}

if ($filterBox == 'assigned') {
    $where[] = "pr.box_id IS NOT NULL";
} elseif ($filterBox == 'unassigned') {
    $where[] = "pr.box_id IS NULL";
}

if (!empty($searchKeyword)) {
    $where[] = "(pr.roll_code LIKE ? OR pr.qr_code LIKE ? OR pb.box_code LIKE ?)";
    $params[] = "%$searchKeyword%";
    $params[] = "%$searchKeyword%";
    $params[] = "%$searchKeyword%";
}

$whereClause = empty($where) ? "" : "WHERE " . implode(" AND ", $where);

// 전체 카운트 조회
$countQuery = "
    SELECT COUNT(*) as total 
    FROM paper_rolls pr
    LEFT JOIN paper_boxes pb ON pr.box_id = pb.id
    $whereClause
";

$stmt = $conn->prepare($countQuery);
if (!empty($params)) {
    $stmt->execute($params);
} else {
    $stmt->execute();
}
$totalCount = $stmt->fetch()['total'];

// 롤 목록 조회
$query = "
    SELECT 
        pr.*,
        pb.box_code,
        pb.status as box_status
    FROM paper_rolls pr
    LEFT JOIN paper_boxes pb ON pr.box_id = pb.id
    $whereClause
    ORDER BY pr.created_at DESC
    LIMIT $offset, $perPage
";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->execute($params);
} else {
    $stmt->execute();
}
$rolls = $stmt->fetchAll();

// 통계 데이터
$statsQuery = "
    SELECT 
        COUNT(*) as total_rolls,
        COUNT(CASE WHEN box_id IS NULL THEN 1 END) as unassigned_rolls,
        COUNT(CASE WHEN box_id IS NOT NULL THEN 1 END) as assigned_rolls,
        COUNT(CASE WHEN status = 'registered' THEN 1 END) as registered_rolls,
        COUNT(CASE WHEN status = 'active' THEN 1 END) as active_rolls,
        COUNT(CASE WHEN status = 'used' THEN 1 END) as used_rolls
    FROM paper_rolls
";
$stats = $conn->query($statsQuery)->fetch();

// 페이지네이션 계산
$totalPages = ceil($totalCount / $perPage);

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
            <a href="paper-roll-register.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> 롤 등록
            </a>
            <a href="paper-dashboard.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> 목록으로
            </a>
        </div>
    </div>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success">
            <?php 
            echo $_SESSION['success_message']; 
            unset($_SESSION['success_message']);
            ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger">
            <?php 
            echo $_SESSION['error_message']; 
            unset($_SESSION['error_message']);
            ?>
        </div>
    <?php endif; ?>

    <!-- 통계 카드 -->
    <div class="row mb-4">
        <div class="col-xl-2 col-md-4 mb-3">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                전체 롤</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo number_format($stats['total_rolls']); ?>개
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-scroll fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 mb-3">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                미할당</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo number_format($stats['unassigned_rolls']); ?>개
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-layer-group fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 mb-3">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                할당됨</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo number_format($stats['assigned_rolls']); ?>개
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-box fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 mb-3">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                사용중</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo number_format($stats['active_rolls']); ?>개
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-play-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 mb-3">
            <div class="card border-left-secondary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">
                                사용완료</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo number_format($stats['used_rolls']); ?>개
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 필터 및 검색 -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <form method="get" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">상태</label>
                    <select name="status" class="form-select">
                        <option value="all">전체</option>
                        <option value="registered" <?php echo $filterStatus == 'registered' ? 'selected' : ''; ?>>등록</option>
                        <option value="active" <?php echo $filterStatus == 'active' ? 'selected' : ''; ?>>사용중</option>
                        <option value="used" <?php echo $filterStatus == 'used' ? 'selected' : ''; ?>>사용완료</option>
                        <option value="expired" <?php echo $filterStatus == 'expired' ? 'selected' : ''; ?>>만료</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">할당 상태</label>
                    <select name="box" class="form-select">
                        <option value="all">전체</option>
                        <option value="unassigned" <?php echo $filterBox == 'unassigned' ? 'selected' : ''; ?>>미할당</option>
                        <option value="assigned" <?php echo $filterBox == 'assigned' ? 'selected' : ''; ?>>할당됨</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">검색</label>
                    <input type="text" name="search" class="form-control" 
                           placeholder="롤 코드, QR 코드, 박스 코드로 검색" 
                           value="<?php echo htmlspecialchars($searchKeyword); ?>">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search"></i> 검색
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 롤 목록 -->
    <div class="card shadow">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                롤 목록 (총 <?php echo number_format($totalCount); ?>개)
            </h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>롤 코드</th>
                            <th>QR 코드</th>
                            <th>시작 번호</th>
                            <th>종료 번호</th>
                            <th>개수</th>
                            <th>박스</th>
                            <th>상태</th>
                            <th>등록일</th>
                            <th>관리</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rolls as $roll): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($roll['roll_code']); ?></td>
                                <td><?php echo htmlspecialchars($roll['qr_code']); ?></td>
                                <td><?php echo $roll['start_serial']; ?></td>
                                <td><?php echo $roll['end_serial']; ?></td>
                                <td class="text-end"><?php echo number_format($roll['serial_count']); ?></td>
                                <td>
                                    <?php if ($roll['box_id']): ?>
                                        <span class="badge bg-info">
                                            <?php echo htmlspecialchars($roll['box_code']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-<?php echo getRollStatusClass($roll['status']); ?>">
                                        <?php echo getRollStatusText($roll['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('Y-m-d H:i', strtotime($roll['created_at'])); ?></td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <?php if (!$roll['box_id']): ?>
                                            <button type="button" class="btn btn-danger" 
                                                    onclick="deleteRoll(<?php echo $roll['id']; ?>, '<?php echo htmlspecialchars($roll['roll_code']); ?>')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php else: ?>
                                            <button type="button" class="btn btn-secondary" disabled 
                                                    title="박스에 할당된 롤은 삭제할 수 없습니다">
                                                <i class="fas fa-lock"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
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
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&status=<?php echo $filterStatus; ?>&box=<?php echo $filterBox; ?>&search=<?php echo urlencode($searchKeyword); ?>">
                                이전
                            </a>
                        </li>
                        
                        <?php
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $page + 2);
                        
                        for ($i = $startPage; $i <= $endPage; $i++):
                        ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo $filterStatus; ?>&box=<?php echo $filterBox; ?>&search=<?php echo urlencode($searchKeyword); ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&status=<?php echo $filterStatus; ?>&box=<?php echo $filterBox; ?>&search=<?php echo urlencode($searchKeyword); ?>">
                                다음
                            </a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function deleteRoll(rollId, rollCode) {
    if (confirm(`정말로 롤 '${rollCode}'을(를) 삭제하시겠습니까?\n\n이 작업은 되돌릴 수 없습니다.`)) {
        window.location.href = 'paper-roll-manage.php?action=delete&id=' + rollId;
    }
}
</script>

<?php
// 헬퍼 함수들
function getRollStatusClass($status) {
    switch($status) {
        case 'registered': return 'primary';
        case 'active': return 'success';
        case 'used': return 'secondary';
        case 'expired': return 'danger';
        case 'damaged': return 'warning';
        default: return 'secondary';
    }
}

function getRollStatusText($status) {
    switch($status) {
        case 'registered': return '등록';
        case 'active': return '사용중';
        case 'used': return '사용완료';
        case 'expired': return '만료';
        case 'damaged': return '손상';
        default: return $status;
    }
}
?>

<?php include_once TEMPLATES_PATH . '/dashboard_footer.php'; ?>
