<?php
// 예치금 관리 대시보드
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

// 로그인 체크
checkLogin();

// 페이지 제목
$pageTitle = "예치금 관리";
$currentSection = "store";
$currentPage = "deposit-dashboard.php";

// 데이터베이스 연결
$conn = get_db_connection();

// 필터 파라미터
$filterStatus = isset($_GET['status']) ? $_GET['status'] : 'all';
$filterGrade = isset($_GET['grade']) ? $_GET['grade'] : 'all';
$searchKeyword = isset($_GET['search']) ? $_GET['search'] : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// 예치금 현황 조회 쿼리 구성
$where = [];
$params = [];

if ($filterStatus != 'all') {
    $where[] = "sd.status = ?";
    $params[] = $filterStatus;
}

if ($filterGrade != 'all') {
    $where[] = "sd.store_grade = ?";
    $params[] = $filterGrade;
}

if (!empty($searchKeyword)) {
    $where[] = "(s.store_name LIKE ? OR s.store_code LIKE ?)";
    $params[] = "%$searchKeyword%";
    $params[] = "%$searchKeyword%";
}

$whereClause = empty($where) ? "" : "WHERE " . implode(" AND ", $where);

// 전체 카운트 조회
$countQuery = "
    SELECT COUNT(*) as total 
    FROM store_deposits sd
    INNER JOIN stores s ON sd.store_id = s.id
    $whereClause
";

$stmt = $conn->prepare($countQuery);
if (!empty($params)) {
    $stmt->execute($params);
} else {
    $stmt->execute();
}
$totalCount = $stmt->fetch()['total'];

// 예치금 데이터 조회
$query = "
    SELECT 
        sd.*,
        s.store_name,
        s.store_code,
        sgl.leverage_rate as grade_leverage
    FROM store_deposits sd
    INNER JOIN stores s ON sd.store_id = s.id
    LEFT JOIN store_grade_leverage sgl ON sd.store_grade = sgl.grade
    $whereClause
    ORDER BY sd.usage_percentage DESC, sd.updated_at DESC
    LIMIT $offset, $perPage
";

// 디버깅용 쿼리 출력
error_log("Deposit Dashboard Query: " . $query);

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->execute($params);
} else {
    $stmt->execute();
}
$deposits = $stmt->fetchAll();

// 통계 데이터 조회
$statsQuery = "
    SELECT 
        COUNT(*) as total_stores,
        SUM(total_deposit) as total_deposits,
        SUM(sales_limit) as total_limits,
        SUM(used_limit) as total_used,
        AVG(usage_percentage) as avg_usage,
        COUNT(CASE WHEN usage_percentage >= 90 THEN 1 END) as critical_stores,
        COUNT(CASE WHEN status = 'blocked' THEN 1 END) as blocked_stores
    FROM store_deposits
";
$stats = $conn->query($statsQuery)->fetch();

// 페이지네이션 계산
$totalPages = ceil($totalCount / $perPage);

// 등급별 통계
$gradeStatsQuery = "
    SELECT 
        store_grade,
        COUNT(*) as count,
        AVG(usage_percentage) as avg_usage
    FROM store_deposits
    GROUP BY store_grade
    ORDER BY 
        CASE store_grade 
            WHEN 'S' THEN 1 
            WHEN 'A' THEN 2 
            WHEN 'B' THEN 3 
            WHEN 'C' THEN 4 
            WHEN 'D' THEN 5 
        END
";
$gradeStats = $conn->query($gradeStatsQuery)->fetchAll(PDO::FETCH_KEY_PAIR);

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
                    <li class="breadcrumb-item active"><?php echo $pageTitle; ?></li>
                </ol>
            </nav>
        </div>
        <div class="col-md-6 text-end">
            <a href="deposit-add.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> 예치금 입금
            </a>
            <a href="deposit-report.php" class="btn btn-secondary">
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
                                총 예치금</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                ₩ <?php echo number_format($stats['total_deposits'] ?? 0); ?>
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
                                판매 한도</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                ₩ <?php echo number_format($stats['total_limits'] ?? 0); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chart-line fa-2x text-gray-300"></i>
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
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">평균 사용률</div>
                            <div class="row no-gutters align-items-center">
                                <div class="col-auto">
                                    <div class="h5 mb-0 mr-3 font-weight-bold text-gray-800">
                                        <?php echo number_format($stats['avg_usage'] ?? 0, 1); ?>%
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="progress progress-sm mr-2">
                                        <div class="progress-bar bg-info" role="progressbar"
                                             style="width: <?php echo $stats['avg_usage'] ?? 0; ?>%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
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
                                주의 필요</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo $stats['critical_stores'] ?? 0; ?>개점
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
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
                        <option value="active" <?php echo $filterStatus == 'active' ? 'selected' : ''; ?>>정상</option>
                        <option value="suspended" <?php echo $filterStatus == 'suspended' ? 'selected' : ''; ?>>일시정지</option>
                        <option value="blocked" <?php echo $filterStatus == 'blocked' ? 'selected' : ''; ?>>차단</option>
                        <option value="terminated" <?php echo $filterStatus == 'terminated' ? 'selected' : ''; ?>>해지</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">등급</label>
                    <select name="grade" class="form-select">
                        <option value="all">전체</option>
                        <?php foreach (['S', 'A', 'B', 'C', 'D'] as $grade): ?>
                            <option value="<?php echo $grade; ?>" <?php echo $filterGrade == $grade ? 'selected' : ''; ?>>
                                <?php echo $grade; ?>등급
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">검색</label>
                    <input type="text" name="search" class="form-control" 
                           placeholder="판매점명, 코드로 검색" value="<?php echo htmlspecialchars($searchKeyword); ?>">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search"></i> 검색
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 예치금 목록 -->
    <div class="card shadow">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                예치금 현황 (총 <?php echo number_format($totalCount); ?>개)
            </h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>판매점</th>
                            <th>등급</th>
                            <th>예치금</th>
                            <th>판매한도</th>
                            <th>사용액</th>
                            <th>잔여한도</th>
                            <th>사용률</th>
                            <th>상태</th>
                            <th>관리</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($deposits as $deposit): ?>
                            <?php
                            $usageClass = '';
                            if ($deposit['usage_percentage'] >= 98) {
                                $usageClass = 'text-danger fw-bold';
                            } elseif ($deposit['usage_percentage'] >= 90) {
                                $usageClass = 'text-warning fw-bold';
                            }
                            ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($deposit['store_name']); ?></strong><br>
                                    <small class="text-muted"><?php echo $deposit['store_code']; ?></small>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-<?php echo getGradeBadgeClass($deposit['store_grade']); ?>">
                                        <?php echo $deposit['store_grade']; ?>등급
                                    </span>
                                </td>
                                <td class="text-end">
                                    ₩ <?php echo number_format($deposit['total_deposit']); ?>
                                </td>
                                <td class="text-end">
                                    ₩ <?php echo number_format($deposit['sales_limit']); ?>
                                    <?php if ($deposit['leverage_rate'] > 1): ?>
                                        <br><small class="text-muted">x<?php echo $deposit['leverage_rate']; ?></small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    ₩ <?php echo number_format($deposit['used_limit']); ?>
                                </td>
                                <td class="text-end">
                                    ₩ <?php echo number_format($deposit['remaining_limit']); ?>
                                </td>
                                <td class="text-center">
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar <?php echo getUsageProgressClass($deposit['usage_percentage']); ?>" 
                                             role="progressbar" 
                                             style="width: <?php echo $deposit['usage_percentage']; ?>%">
                                            <span class="<?php echo $usageClass; ?>">
                                                <?php echo number_format($deposit['usage_percentage'], 1); ?>%
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-<?php echo getStatusBadgeClass($deposit['status']); ?>">
                                        <?php echo getStatusText($deposit['status']); ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <a href="deposit-view.php?id=<?php echo $deposit['id']; ?>" 
                                           class="btn btn-info" title="상세보기">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="deposit-transaction.php?store_id=<?php echo $deposit['store_id']; ?>" 
                                           class="btn btn-primary" title="입금/출금">
                                            <i class="fas fa-exchange-alt"></i>
                                        </a>
                                        <?php if ($deposit['usage_percentage'] >= 100): ?>
                                            <button class="btn btn-danger" onclick="resetLimit(<?php echo $deposit['store_id']; ?>)" 
                                                    title="한도 리셋">
                                                <i class="fas fa-redo"></i>
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
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&status=<?php echo $filterStatus; ?>&grade=<?php echo $filterGrade; ?>&search=<?php echo urlencode($searchKeyword); ?>">
                                이전
                            </a>
                        </li>
                        
                        <?php
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $page + 2);
                        
                        for ($i = $startPage; $i <= $endPage; $i++):
                        ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo $filterStatus; ?>&grade=<?php echo $filterGrade; ?>&search=<?php echo urlencode($searchKeyword); ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&status=<?php echo $filterStatus; ?>&grade=<?php echo $filterGrade; ?>&search=<?php echo urlencode($searchKeyword); ?>">
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
function resetLimit(storeId) {
    if (confirm('정말로 이 판매점의 판매한도를 리셋하시겠습니까?')) {
        // AJAX로 한도 리셋 처리
        fetch('deposit-reset-limit.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ store_id: storeId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('판매한도가 리셋되었습니다.');
                location.reload();
            } else {
                alert('오류가 발생했습니다: ' + data.message);
            }
        })
        .catch(error => {
            alert('오류가 발생했습니다.');
            console.error('Error:', error);
        });
    }
}

// 헬퍼 함수들
<?php
function getGradeBadgeClass($grade) {
    switch($grade) {
        case 'S': return 'primary';
        case 'A': return 'success';
        case 'B': return 'info';
        case 'C': return 'warning';
        case 'D': return 'danger';
        default: return 'secondary';
    }
}

function getStatusBadgeClass($status) {
    switch($status) {
        case 'active': return 'success';
        case 'suspended': return 'warning';
        case 'blocked': return 'danger';
        case 'terminated': return 'secondary';
        default: return 'secondary';
    }
}

function getStatusText($status) {
    switch($status) {
        case 'active': return '정상';
        case 'suspended': return '일시정지';
        case 'blocked': return '차단';
        case 'terminated': return '해지';
        default: return $status;
    }
}

function getUsageProgressClass($percentage) {
    if ($percentage >= 98) return 'bg-danger';
    if ($percentage >= 90) return 'bg-warning';
    if ($percentage >= 75) return 'bg-info';
    return 'bg-success';
}
?>
</script>

<?php include_once TEMPLATES_PATH . '/dashboard_footer.php'; ?>
