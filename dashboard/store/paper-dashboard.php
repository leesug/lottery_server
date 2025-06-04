<?php
// 용지관리 대시보드
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

// 로그인 체크
checkLogin();

// 페이지 제목
$pageTitle = "용지 관리";
$currentSection = "store";
$currentPage = "paper-dashboard.php";

// 데이터베이스 연결
$conn = get_db_connection();

// 필터 파라미터
$filterStatus = isset($_GET['status']) ? $_GET['status'] : 'all';
$searchKeyword = isset($_GET['search']) ? $_GET['search'] : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// 용지 사용 현황 조회 쿼리 구성
$where = [];
$params = [];

if ($filterStatus != 'all') {
    $where[] = "pu.is_active = ?";
    $params[] = ($filterStatus == 'active') ? 1 : 0;
}

if (!empty($searchKeyword)) {
    $where[] = "(s.store_name LIKE ? OR s.store_code LIKE ? OR pr.roll_code LIKE ?)";
    $params[] = "%$searchKeyword%";
    $params[] = "%$searchKeyword%";
    $params[] = "%$searchKeyword%";
}

$whereClause = empty($where) ? "" : "WHERE " . implode(" AND ", $where);

// 전체 카운트 조회
$countQuery = "
    SELECT COUNT(DISTINCT s.id) as total 
    FROM stores s
    LEFT JOIN paper_usage pu ON s.id = pu.store_id AND pu.is_active = 1
    LEFT JOIN paper_rolls pr ON pu.roll_id = pr.id
    $whereClause
";

$stmt = $conn->prepare($countQuery);
if (!empty($params)) {
    $stmt->execute($params);
} else {
    $stmt->execute();
}
$totalCount = $stmt->fetch()['total'];

// 용지 사용 현황 데이터 조회
$query = "
    SELECT 
        s.id as store_id,
        s.store_name,
        s.store_code,
        s.region_id,
        r.region_name,
        pu.id as usage_id,
        pu.roll_id,
        pu.current_serial,
        pu.estimated_serial,
        pu.printed_length_mm,
        pu.remaining_length_mm,
        pu.serial_difference,
        pu.total_tickets,
        pu.last_updated,
        pr.roll_code,
        pr.start_serial,
        pr.end_serial,
        pr.status as roll_status,
        pb.box_code,
        ROUND((pu.printed_length_mm / pr.length_mm) * 100, 2) as usage_percentage
    FROM stores s
    LEFT JOIN paper_usage pu ON s.id = pu.store_id AND pu.is_active = 1
    LEFT JOIN paper_rolls pr ON pu.roll_id = pr.id
    LEFT JOIN paper_boxes pb ON pr.box_id = pb.id
    LEFT JOIN regions r ON s.region_id = r.id
    $whereClause
    ORDER BY 
        CASE WHEN pu.id IS NULL THEN 1 ELSE 0 END,
        usage_percentage DESC
    LIMIT $offset, $perPage
";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->execute($params);
} else {
    $stmt->execute();
}
$paperUsages = $stmt->fetchAll();

// 통계 데이터 조회
$statsQuery = "
    SELECT 
        COUNT(DISTINCT pb.id) as total_boxes,
        COUNT(DISTINCT pr.id) as total_rolls,
        COUNT(DISTINCT CASE WHEN pr.status = 'active' THEN pr.id END) as active_rolls,
        COUNT(DISTINCT CASE WHEN pr.status = 'registered' THEN pr.id END) as available_rolls,
        COUNT(DISTINCT pu.store_id) as stores_with_paper,
        AVG(CASE WHEN pu.id IS NOT NULL THEN (pu.printed_length_mm / pr.length_mm) * 100 END) as avg_usage
    FROM paper_boxes pb
    LEFT JOIN paper_rolls pr ON pb.id = pr.box_id
    LEFT JOIN paper_usage pu ON pr.id = pu.roll_id AND pu.is_active = 1
";
$stats = $conn->query($statsQuery)->fetch();

// 최근 알림 조회
$alertsQuery = "
    SELECT 
        pa.*,
        s.store_name,
        pr.roll_code
    FROM paper_alerts pa
    INNER JOIN paper_rolls pr ON pa.roll_id = pr.id
    INNER JOIN stores s ON pa.store_id = s.id
    WHERE pa.is_notified = 1 AND pa.acknowledged = 0
    ORDER BY pa.created_at DESC
    LIMIT 5
";
$recentAlerts = $conn->query($alertsQuery)->fetchAll();

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
                    <li class="breadcrumb-item active"><?php echo $pageTitle; ?></li>
                </ol>
            </nav>
        </div>
        <div class="col-md-6 text-end">
            <a href="paper-box-register.php" class="btn btn-primary">
                <i class="fas fa-box"></i> 박스 등록
            </a>
            <a href="paper-roll-register.php" class="btn btn-success">
                <i class="fas fa-scroll"></i> 롤 등록
            </a>
            <a href="paper-stock.php" class="btn btn-info">
                <i class="fas fa-warehouse"></i> 재고 현황
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
                                총 박스</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo number_format($stats['total_boxes'] ?? 0); ?>개
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-box fa-2x text-gray-300"></i>
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
                                사용중 롤</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo number_format($stats['active_rolls'] ?? 0); ?>개
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-scroll fa-2x text-gray-300"></i>
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
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">대기중 롤</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo number_format($stats['available_rolls'] ?? 0); ?>개
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-layer-group fa-2x text-gray-300"></i>
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
                                평균 사용률</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo number_format($stats['avg_usage'] ?? 0, 1); ?>%
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chart-pie fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 최근 알림 -->
    <?php if (!empty($recentAlerts)): ?>
    <div class="alert alert-warning alert-dismissible fade show mb-4" role="alert">
        <h6 class="alert-heading">
            <i class="fas fa-exclamation-triangle"></i> 용지 알림
        </h6>
        <ul class="mb-0">
            <?php foreach ($recentAlerts as $alert): ?>
                <li>
                    <strong><?php echo htmlspecialchars($alert['store_name']); ?></strong> - 
                    <?php echo htmlspecialchars($alert['message']); ?>
                    <small class="text-muted">(<?php echo date('m/d H:i', strtotime($alert['created_at'])); ?>)</small>
                </li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <!-- 필터 및 검색 -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <form method="get" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">상태</label>
                    <select name="status" class="form-select">
                        <option value="all">전체</option>
                        <option value="active" <?php echo $filterStatus == 'active' ? 'selected' : ''; ?>>사용중</option>
                        <option value="inactive" <?php echo $filterStatus == 'inactive' ? 'selected' : ''; ?>>미사용</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">검색</label>
                    <input type="text" name="search" class="form-control" 
                           placeholder="판매점명, 코드, 롤번호로 검색" 
                           value="<?php echo htmlspecialchars($searchKeyword); ?>">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search"></i> 검색
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 용지 사용 현황 목록 -->
    <div class="card shadow">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                용지 사용 현황 (총 <?php echo number_format($totalCount); ?>개 판매점)
            </h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>판매점</th>
                            <th>용지 정보</th>
                            <th>현재 번호</th>
                            <th>추정 번호</th>
                            <th>차이</th>
                            <th>사용량</th>
                            <th>사용률</th>
                            <th>최종 업데이트</th>
                            <th>관리</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($paperUsages as $usage): ?>
                            <?php
                            $hasActiveRoll = !empty($usage['roll_id']);
                            $serialDiffClass = '';
                            if ($hasActiveRoll) {
                                $diff = abs($usage['serial_difference']);
                                if ($diff > 12) {
                                    $serialDiffClass = 'text-danger fw-bold';
                                } elseif ($diff > 8) {
                                    $serialDiffClass = 'text-warning';
                                }
                            }
                            ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($usage['store_name']); ?></strong><br>
                                    <small class="text-muted"><?php echo $usage['store_code']; ?></small>
                                </td>
                                <td>
                                    <?php if ($hasActiveRoll): ?>
                                        <strong>롤: <?php echo $usage['roll_code']; ?></strong><br>
                                        <small class="text-muted">
                                            박스: <?php echo $usage['box_code']; ?><br>
                                            범위: <?php echo $usage['start_serial']; ?> ~ <?php echo $usage['end_serial']; ?>
                                        </small>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php echo $hasActiveRoll ? $usage['current_serial'] : '-'; ?>
                                </td>
                                <td class="text-center">
                                    <?php echo $hasActiveRoll ? $usage['estimated_serial'] : '-'; ?>
                                </td>
                                <td class="text-center <?php echo $serialDiffClass; ?>">
                                    <?php if ($hasActiveRoll): ?>
                                        <?php echo ($usage['serial_difference'] >= 0 ? '+' : '') . $usage['serial_difference']; ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($hasActiveRoll): ?>
                                        <?php echo number_format($usage['printed_length_mm'] / 1000, 1); ?>m<br>
                                        <small class="text-muted"><?php echo number_format($usage['total_tickets']); ?> 티켓</small>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($hasActiveRoll): ?>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar <?php echo getUsageProgressClass($usage['usage_percentage']); ?>" 
                                                 role="progressbar" 
                                                 style="width: <?php echo $usage['usage_percentage']; ?>%">
                                                <?php echo number_format($usage['usage_percentage'], 1); ?>%
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($hasActiveRoll): ?>
                                        <?php echo date('Y-m-d H:i', strtotime($usage['last_updated'])); ?>
                                    <?php else: ?>
                                        <span class="text-muted">미사용</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <?php if ($hasActiveRoll): ?>
                                            <a href="paper-input.php?store_id=<?php echo $usage['store_id']; ?>" 
                                               class="btn btn-primary" title="번호 입력">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="paper-history.php?store_id=<?php echo $usage['store_id']; ?>" 
                                               class="btn btn-info" title="이력 보기">
                                                <i class="fas fa-history"></i>
                                            </a>
                                            <?php if ($usage['usage_percentage'] >= 95): ?>
                                                <button class="btn btn-warning" 
                                                        onclick="alertPaperChange(<?php echo $usage['store_id']; ?>)" 
                                                        title="용지 교체 알림">
                                                    <i class="fas fa-bell"></i>
                                                </button>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <a href="paper-activate.php?store_id=<?php echo $usage['store_id']; ?>" 
                                               class="btn btn-success" title="용지 활성화">
                                                <i class="fas fa-play"></i> 활성화
                                            </a>
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
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&status=<?php echo $filterStatus; ?>&search=<?php echo urlencode($searchKeyword); ?>">
                                이전
                            </a>
                        </li>
                        
                        <?php
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $page + 2);
                        
                        for ($i = $startPage; $i <= $endPage; $i++):
                        ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo $filterStatus; ?>&search=<?php echo urlencode($searchKeyword); ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&status=<?php echo $filterStatus; ?>&search=<?php echo urlencode($searchKeyword); ?>">
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
function alertPaperChange(storeId) {
    if (confirm('이 판매점에 용지 교체 알림을 보내시겠습니까?')) {
        // AJAX로 알림 발송
        fetch('paper-alert-send.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ 
                store_id: storeId,
                alert_type: 'paper_change'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('알림이 발송되었습니다.');
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

// 헬퍼 함수
<?php
function getUsageProgressClass($percentage) {
    if ($percentage >= 98) return 'bg-danger';
    if ($percentage >= 95) return 'bg-warning';
    if ($percentage >= 90) return 'bg-info';
    return 'bg-success';
}
?>
</script>

<?php include_once TEMPLATES_PATH . '/dashboard_footer.php'; ?>
