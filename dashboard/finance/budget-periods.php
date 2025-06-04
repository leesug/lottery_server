<?php
/**
 * 재무 관리 - 예산 기간 목록 페이지
 * 
 * 이 페이지는 예산 기간 목록을 표시하고 관리하는 기능을 제공합니다.
 */

// 세션 시작 및 필수 파일 포함
session_start();
require_once '../../includes/auth.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// 인증 확인
checkAuth();

// 필요한 권한 확인
$requiredPermissions = ['finance_budget'];
checkPermissions($requiredPermissions);

// 데이터베이스 연결
$conn = getDBConnection();

// 삭제 요청 처리
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    // 삭제 권한 확인
    if (!hasPermission('finance_budget_delete')) {
        setAlert('예산 기간을 삭제할 권한이 없습니다.', 'error');
        redirectTo('budget-periods.php');
    }
    
    $periodId = intval($_GET['id']);
    
    // CSRF 토큰 검증
    if (!isset($_GET['csrf_token']) || !verifyCsrfToken($_GET['csrf_token'])) {
        setAlert('CSRF 토큰이 유효하지 않습니다.', 'error');
        redirectTo('budget-periods.php');
    }
    
    try {
        // 예산 기간 정보 조회
        $checkSql = "SELECT * FROM budget_periods WHERE id = ?";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param("i", $periodId);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows === 0) {
            throw new Exception("존재하지 않는 예산 기간입니다.");
        }
        
        $periodInfo = $checkResult->fetch_assoc();
        
        // 활성 상태인 기간은 삭제 불가
        if ($periodInfo['status'] === 'active') {
            throw new Exception("활성 상태인 예산 기간은 삭제할 수 없습니다.");
        }
        
        // 할당된 예산이 있는지 확인
        $checkAllocationSql = "SELECT COUNT(*) as count FROM budget_allocations WHERE period_id = ?";
        $checkAllocationStmt = $conn->prepare($checkAllocationSql);
        $checkAllocationStmt->bind_param("i", $periodId);
        $checkAllocationStmt->execute();
        $allocationResult = $checkAllocationStmt->get_result();
        $allocationCount = $allocationResult->fetch_assoc()['count'];
        
        if ($allocationCount > 0) {
            throw new Exception("이 예산 기간에 할당된 예산이 있습니다. 먼저 예산 할당을 삭제해주세요.");
        }
        
        // 예산 기간 삭제
        $deleteSql = "DELETE FROM budget_periods WHERE id = ?";
        $deleteStmt = $conn->prepare($deleteSql);
        $deleteStmt->bind_param("i", $periodId);
        $deleteResult = $deleteStmt->execute();
        
        if (!$deleteResult) {
            throw new Exception("예산 기간 삭제 중 오류가 발생했습니다: " . $deleteStmt->error);
        }
        
        // 성공 메시지 설정
        setAlert("예산 기간이 성공적으로 삭제되었습니다.", 'success');
        
        // 로그 기록
        logActivity('finance', 'budget_period_delete', "예산 기간 삭제: ID {$periodId}, {$periodInfo['period_name']}");
        
    } catch (Exception $e) {
        setAlert($e->getMessage(), 'error');
    }
    
    // 목록 페이지로 리디렉션
    redirectTo('budget-periods.php');
}

// 필터 및 정렬 파라미터
$status = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';
$year = isset($_GET['year']) ? intval($_GET['year']) : 0;
$sortBy = isset($_GET['sort']) ? sanitizeInput($_GET['sort']) : 'start_date';
$sortOrder = isset($_GET['order']) && strtolower($_GET['order']) === 'asc' ? 'ASC' : 'DESC';

// 기간 목록 쿼리
$sql = "SELECT bp.*,
        COUNT(ba.id) as allocation_count,
        SUM(ba.allocated_amount) as total_allocated,
        SUM(ba.utilized_amount) as total_utilized
        FROM budget_periods bp
        LEFT JOIN budget_allocations ba ON bp.id = ba.period_id";

$whereConditions = [];
$params = [];
$paramTypes = "";

if (!empty($status)) {
    $whereConditions[] = "bp.status = ?";
    $params[] = $status;
    $paramTypes .= "s";
}

if ($year > 0) {
    $whereConditions[] = "YEAR(bp.start_date) = ?";
    $params[] = $year;
    $paramTypes .= "i";
}

if (!empty($whereConditions)) {
    $sql .= " WHERE " . implode(" AND ", $whereConditions);
}

$sql .= " GROUP BY bp.id";

// 정렬 설정
$validSortColumns = ['period_name', 'start_date', 'end_date', 'status'];
if (!in_array($sortBy, $validSortColumns)) {
    $sortBy = 'start_date';
}

$sql .= " ORDER BY bp.{$sortBy} {$sortOrder}";

// 쿼리 실행
$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($paramTypes, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$periods = [];

while ($row = $result->fetch_assoc()) {
    $periods[] = $row;
}

// 사용 가능한 연도 목록 가져오기
$yearsSql = "SELECT DISTINCT YEAR(start_date) as year FROM budget_periods ORDER BY year DESC";
$yearsResult = $conn->query($yearsSql);
$years = [];

while ($row = $yearsResult->fetch_assoc()) {
    $years[] = $row['year'];
}

// 페이지 제목 설정
$pageTitle = "예산 기간 관리";
$currentSection = "finance";
$currentPage = "budget";

// 헤더 및 네비게이션 포함
include '../../templates/header.php';
include '../../templates/navbar.php';

// 상태 한글명 및 배지 색상 반환 함수
function getStatusBadge($status) {
    $badges = [
        'planning' => ['label' => '계획 중', 'color' => 'info'],
        'active' => ['label' => '활성', 'color' => 'success'],
        'closed' => ['label' => '종료', 'color' => 'secondary']
    ];
    
    if (!isset($badges[$status])) {
        return ['label' => ucfirst($status), 'color' => 'secondary'];
    }
    
    return $badges[$status];
}
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0"><?php echo $pageTitle; ?></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="../../dashboard/">대시보드</a></li>
                        <li class="breadcrumb-item"><a href="../">재무 관리</a></li>
                        <li class="breadcrumb-item active">예산 기간</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-calendar-alt mr-1"></i>
                                예산 기간 목록
                            </h3>
                            <div class="card-tools">
                                <?php if (hasPermission('finance_budget_create')): ?>
                                <a href="budget-period-add.php" class="btn btn-primary btn-sm">
                                    <i class="fas fa-plus-circle mr-1"></i> 새 예산 기간 추가
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="card-body">
                            <!-- 필터 -->
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <form id="filterForm" method="get" class="form-inline">
                                        <div class="form-group mr-2">
                                            <label for="status" class="mr-1">상태:</label>
                                            <select class="form-control form-control-sm" id="status" name="status">
                                                <option value="">모든 상태</option>
                                                <option value="planning" <?php if ($status === 'planning') echo 'selected'; ?>>계획 중</option>
                                                <option value="active" <?php if ($status === 'active') echo 'selected'; ?>>활성</option>
                                                <option value="closed" <?php if ($status === 'closed') echo 'selected'; ?>>종료</option>
                                            </select>
                                        </div>
                                        <div class="form-group mr-2">
                                            <label for="year" class="mr-1">연도:</label>
                                            <select class="form-control form-control-sm" id="year" name="year">
                                                <option value="0">모든 연도</option>
                                                <?php foreach ($years as $yearOption): ?>
                                                <option value="<?php echo $yearOption; ?>" <?php if ($year === $yearOption) echo 'selected'; ?>>
                                                    <?php echo $yearOption; ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="form-group mr-2">
                                            <label for="sort" class="mr-1">정렬:</label>
                                            <select class="form-control form-control-sm" id="sort" name="sort">
                                                <option value="start_date" <?php if ($sortBy === 'start_date') echo 'selected'; ?>>시작일</option>
                                                <option value="end_date" <?php if ($sortBy === 'end_date') echo 'selected'; ?>>종료일</option>
                                                <option value="period_name" <?php if ($sortBy === 'period_name') echo 'selected'; ?>>기간명</option>
                                                <option value="status" <?php if ($sortBy === 'status') echo 'selected'; ?>>상태</option>
                                            </select>
                                        </div>
                                        <div class="form-group mr-2">
                                            <select class="form-control form-control-sm" id="order" name="order">
                                                <option value="asc" <?php if ($sortOrder === 'ASC') echo 'selected'; ?>>오름차순</option>
                                                <option value="desc" <?php if ($sortOrder === 'DESC') echo 'selected'; ?>>내림차순</option>
                                            </select>
                                        </div>
                                        <button type="submit" class="btn btn-sm btn-info">
                                            <i class="fas fa-filter mr-1"></i> 필터 적용
                                        </button>
                                        <a href="budget-periods.php" class="btn btn-sm btn-default ml-1">
                                            <i class="fas fa-sync-alt mr-1"></i> 필터 초기화
                                        </a>
                                    </form>
                                </div>
                            </div>
                            
                            <!-- 테이블 -->
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>기간명</th>
                                            <th>시작일</th>
                                            <th>종료일</th>
                                            <th>상태</th>
                                            <th>예산 할당 수</th>
                                            <th>총 할당액</th>
                                            <th>총 사용액</th>
                                            <th>사용률</th>
                                            <th>액션</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($periods)): ?>
                                        <tr>
                                            <td colspan="9" class="text-center">예산 기간이 없습니다.</td>
                                        </tr>
                                        <?php else: ?>
                                            <?php foreach ($periods as $period): ?>
                                            <?php 
                                            $statusBadge = getStatusBadge($period['status']); 
                                            $utilizationRate = ($period['total_allocated'] > 0) ? 
                                                ($period['total_utilized'] / $period['total_allocated'] * 100) : 0;
                                            ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($period['period_name']); ?></td>
                                                <td><?php echo date('Y-m-d', strtotime($period['start_date'])); ?></td>
                                                <td><?php echo date('Y-m-d', strtotime($period['end_date'])); ?></td>
                                                <td>
                                                    <span class="badge badge-<?php echo $statusBadge['color']; ?>">
                                                        <?php echo $statusBadge['label']; ?>
                                                    </span>
                                                </td>
                                                <td><?php echo $period['allocation_count']; ?></td>
                                                <td><?php echo number_format($period['total_allocated'] ?? 0, 2); ?> NPR</td>
                                                <td><?php echo number_format($period['total_utilized'] ?? 0, 2); ?> NPR</td>
                                                <td>
                                                    <div class="progress progress-sm">
                                                        <div class="progress-bar bg-green" role="progressbar" 
                                                            aria-valuenow="<?php echo round($utilizationRate); ?>" 
                                                            aria-valuemin="0" 
                                                            aria-valuemax="100" 
                                                            style="width: <?php echo round($utilizationRate); ?>%">
                                                        </div>
                                                    </div>
                                                    <small>
                                                        <?php echo round($utilizationRate, 1); ?>%
                                                    </small>
                                                </td>
                                                <td>
                                                    <div class="btn-group">
                                                        <a href="budget-period-details.php?id=<?php echo $period['id']; ?>" class="btn btn-sm btn-info" title="상세 정보">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        
                                                        <?php if (hasPermission('finance_budget_update') && $period['status'] !== 'closed'): ?>
                                                        <a href="budget-period-edit.php?id=<?php echo $period['id']; ?>" class="btn btn-sm btn-primary" title="편집">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <?php endif; ?>
                                                        
                                                        <?php if (hasPermission('finance_budget_delete') && $period['status'] !== 'active' && $period['allocation_count'] == 0): ?>
                                                        <a href="javascript:void(0);" onclick="confirmDelete(<?php echo $period['id']; ?>, '<?php echo htmlspecialchars($period['period_name']); ?>')" class="btn btn-sm btn-danger" title="삭제">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                        <?php endif; ?>
                                                        
                                                        <?php if (hasPermission('finance_budget_allocations')): ?>
                                                        <a href="budget-allocations.php?period_id=<?php echo $period['id']; ?>" class="btn btn-sm btn-success" title="예산 할당 관리">
                                                            <i class="fas fa-money-bill-wave"></i>
                                                        </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 삭제 확인 모달 -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">예산 기간 삭제 확인</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>다음 예산 기간을 삭제하시겠습니까?</p>
                <p><strong id="deletePeriodName"></strong></p>
                <p>이 작업은 되돌릴 수 없습니다.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">취소</button>
                <a href="#" id="deleteConfirmBtn" class="btn btn-danger">삭제</a>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('예산 기간 목록 페이지 로드됨');
    
    // 필터 폼 변경 시 자동 제출
    document.querySelectorAll('#filterForm select').forEach(function(select) {
        select.addEventListener('change', function() {
            document.getElementById('filterForm').submit();
        });
    });
});

function confirmDelete(id, name) {
    document.getElementById('deletePeriodName').textContent = name;
    document.getElementById('deleteConfirmBtn').href = 'budget-periods.php?action=delete&id=' + id + '&csrf_token=<?php echo generateCsrfToken(); ?>';
    $('#deleteConfirmModal').modal('show');
}
</script>

<?php
// 푸터 포함
include '../../templates/footer.php';
?>