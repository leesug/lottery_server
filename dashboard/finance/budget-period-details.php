<?php
/**
 * 재무 관리 - 예산 기간 상세 페이지
 * 
 * 이 페이지는 예산 기간의 상세 정보와 할당된 예산을 보여줍니다.
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

// 기간 ID 확인
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    setAlert('기간 ID가 유효하지 않습니다.', 'error');
    redirectTo('budget-periods.php');
}

$periodId = intval($_GET['id']);

// 데이터베이스 연결
$conn = getDBConnection();

// 예산 기간 정보 조회
$sql = "SELECT * FROM budget_periods WHERE id = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    logError("Database prepare error: " . $conn->error);
    die("데이터베이스 오류가 발생했습니다.");
}

$stmt->bind_param("i", $periodId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    setAlert('존재하지 않는 예산 기간입니다.', 'error');
    redirectTo('budget-periods.php');
}

$period = $result->fetch_assoc();

// 상태 변경 요청 처리
if (isset($_GET['action']) && $_GET['action'] === 'change_status' && isset($_GET['status'])) {
    // 권한 확인
    if (!hasPermission('finance_budget_update')) {
        setAlert('예산 기간 상태를 변경할 권한이 없습니다.', 'error');
        redirectTo("budget-period-details.php?id={$periodId}");
    }
    
    // CSRF 토큰 검증
    if (!isset($_GET['csrf_token']) || !verifyCsrfToken($_GET['csrf_token'])) {
        setAlert('CSRF 토큰이 유효하지 않습니다.', 'error');
        redirectTo("budget-period-details.php?id={$periodId}");
    }
    
    $newStatus = sanitizeInput($_GET['status']);
    
    // 유효한 상태인지 확인
    if (!in_array($newStatus, ['planning', 'active', 'closed'])) {
        setAlert('유효하지 않은 상태입니다.', 'error');
        redirectTo("budget-period-details.php?id={$periodId}");
    }
    
    // 이미 같은 상태인 경우
    if ($period['status'] === $newStatus) {
        setAlert('이미 해당 상태입니다.', 'info');
        redirectTo("budget-period-details.php?id={$periodId}");
    }
    
    try {
        // 활성화하려는 경우 다른 활성 기간이 있는지 확인
        if ($newStatus === 'active') {
            $activeCheckSql = "SELECT COUNT(*) as count FROM budget_periods WHERE status = 'active' AND id != ?";
            $activeCheckStmt = $conn->prepare($activeCheckSql);
            $activeCheckStmt->bind_param("i", $periodId);
            $activeCheckStmt->execute();
            $activeCheckResult = $activeCheckStmt->get_result();
            $activeCount = $activeCheckResult->fetch_assoc()['count'];
            
            if ($activeCount > 0) {
                throw new Exception("이미 활성화된 예산 기간이 있습니다. 먼저 기존 활성 기간을 종료해주세요.");
            }
        }
        
        // 상태 업데이트
        $updateSql = "UPDATE budget_periods SET status = ?, updated_at = NOW() WHERE id = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param("si", $newStatus, $periodId);
        $updateResult = $updateStmt->execute();
        
        if (!$updateResult) {
            throw new Exception("예산 기간 상태 업데이트 중 오류가 발생했습니다: " . $updateStmt->error);
        }
        
        // 성공 메시지 설정
        $statusLabels = ['planning' => '계획 중', 'active' => '활성', 'closed' => '종료'];
        setAlert("예산 기간 상태가 '{$statusLabels[$newStatus]}'로 변경되었습니다.", 'success');
        
        // 로그 기록
        logActivity('finance', 'budget_period_status_change', "예산 기간 상태 변경: ID {$periodId}, {$period['period_name']}, 상태: {$period['status']} → {$newStatus}");
        
    } catch (Exception $e) {
        setAlert($e->getMessage(), 'error');
    }
    
    // 페이지 리로드 (상태 반영)
    redirectTo("budget-period-details.php?id={$periodId}");
}

// 예산 할당 조회
$allocationsSql = "SELECT ba.*, fc.category_name, fc.category_type
                 FROM budget_allocations ba
                 JOIN financial_categories fc ON ba.category_id = fc.id
                 WHERE ba.period_id = ?
                 ORDER BY fc.category_type, fc.category_name";

$allocationsStmt = $conn->prepare($allocationsSql);
$allocationsStmt->bind_param("i", $periodId);
$allocationsStmt->execute();
$allocationsResult = $allocationsStmt->get_result();
$allocations = [];

while ($row = $allocationsResult->fetch_assoc()) {
    $allocations[] = $row;
}

// 카테고리별 집계
$expenseTotal = 0;
$incomeTotal = 0;
$expenseUtilized = 0;
$incomeUtilized = 0;

foreach ($allocations as $allocation) {
    if ($allocation['category_type'] === 'expense' || $allocation['category_type'] === 'both') {
        $expenseTotal += $allocation['allocated_amount'];
        $expenseUtilized += $allocation['utilized_amount'];
    }
    
    if ($allocation['category_type'] === 'income' || $allocation['category_type'] === 'both') {
        $incomeTotal += $allocation['allocated_amount'];
        $incomeUtilized += $allocation['utilized_amount'];
    }
}

// 전체 예산 사용률
$totalUtilizationRate = ($expenseTotal > 0) ? ($expenseUtilized / $expenseTotal * 100) : 0;

// 예산 진행 상황 확인 (시작일, 종료일 대비 현재)
$now = time();
$startDate = strtotime($period['start_date']);
$endDate = strtotime($period['end_date']);
$totalDays = ($endDate - $startDate) / (60 * 60 * 24);
$elapsedDays = max(0, min($totalDays, ($now - $startDate) / (60 * 60 * 24)));
$timeProgressRate = ($totalDays > 0) ? ($elapsedDays / $totalDays * 100) : 0;

// 예산 사용 효율성 (시간 진행률 대비 예산 사용률)
$efficiency = ($timeProgressRate > 0) ? ($totalUtilizationRate / $timeProgressRate) : 0;

// 페이지 제목 설정
$pageTitle = "예산 기간 상세 정보";
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

// 카테고리 유형 한글명 반환 함수
function getCategoryTypeLabel($type) {
    $labels = [
        'income' => '수입',
        'expense' => '지출',
        'both' => '수입/지출'
    ];
    
    return isset($labels[$type]) ? $labels[$type] : ucfirst($type);
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
                        <li class="breadcrumb-item"><a href="budget-periods.php">예산 기간</a></li>
                        <li class="breadcrumb-item active">상세 정보</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-4">
                    <!-- 예산 기간 정보 카드 -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-calendar-alt mr-1"></i>
                                예산 기간 정보
                            </h3>
                            <div class="card-tools">
                                <?php if (hasPermission('finance_budget_update') && $period['status'] !== 'closed'): ?>
                                <a href="budget-period-edit.php?id=<?php echo $periodId; ?>" class="btn btn-primary btn-sm">
                                    <i class="fas fa-edit mr-1"></i> 편집
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h4><?php echo htmlspecialchars($period['period_name']); ?></h4>
                                <?php $statusBadge = getStatusBadge($period['status']); ?>
                                <span class="badge badge-<?php echo $statusBadge['color']; ?> p-2">
                                    <?php echo $statusBadge['label']; ?>
                                </span>
                            </div>
                            
                            <div class="row">
                                <div class="col-6">
                                    <div class="form-group">
                                        <label>시작일</label>
                                        <p class="form-control-static"><?php echo date('Y-m-d', strtotime($period['start_date'])); ?></p>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-group">
                                        <label>종료일</label>
                                        <p class="form-control-static"><?php echo date('Y-m-d', strtotime($period['end_date'])); ?></p>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if (!empty($period['notes'])): ?>
                            <div class="form-group">
                                <label>비고</label>
                                <p class="form-control-static"><?php echo nl2br(htmlspecialchars($period['notes'])); ?></p>
                            </div>
                            <?php endif; ?>
                            
                            <div class="form-group">
                                <label>생성일</label>
                                <p class="form-control-static"><?php echo date('Y-m-d H:i', strtotime($period['created_at'])); ?></p>
                            </div>
                            
                            <?php if (!empty($period['updated_at'])): ?>
                            <div class="form-group">
                                <label>최종 수정일</label>
                                <p class="form-control-static"><?php echo date('Y-m-d H:i', strtotime($period['updated_at'])); ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (hasPermission('finance_budget_update') && $period['status'] !== 'closed'): ?>
                        <div class="card-footer">
                            <?php if ($period['status'] === 'planning'): ?>
                            <a href="javascript:void(0);" onclick="confirmStatusChange('active', '이 예산 기간을 활성화하시겠습니까? 다른 활성 기간이 있으면 먼저 종료해야 합니다.')" class="btn btn-success">
                                <i class="fas fa-play mr-1"></i> 활성화
                            </a>
                            <?php elseif ($period['status'] === 'active'): ?>
                            <a href="javascript:void(0);" onclick="confirmStatusChange('closed', '이 예산 기간을 종료하시겠습니까? 이 작업은 되돌릴 수 없습니다.')" class="btn btn-danger">
                                <i class="fas fa-stop mr-1"></i> 종료
                            </a>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- 예산 요약 카드 -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-chart-pie mr-1"></i>
                                예산 요약
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-6">
                                    <div class="info-box bg-info">
                                        <div class="info-box-content text-center">
                                            <span class="info-box-text">수입 예산</span>
                                            <span class="info-box-number"><?php echo number_format($incomeTotal, 2); ?> NPR</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="info-box bg-danger">
                                        <div class="info-box-content text-center">
                                            <span class="info-box-text">지출 예산</span>
                                            <span class="info-box-number"><?php echo number_format($expenseTotal, 2); ?> NPR</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <h5 class="mt-3">예산 진행 상황</h5>
                            
                            <div class="form-group">
                                <label>시간 진행률</label>
                                <div class="progress">
                                    <div class="progress-bar bg-info" role="progressbar" 
                                        style="width: <?php echo round($timeProgressRate); ?>%">
                                        <?php echo round($timeProgressRate, 1); ?>%
                                    </div>
                                </div>
                                <small class="text-muted">
                                    <?php echo round($elapsedDays); ?> / <?php echo round($totalDays); ?> 일
                                </small>
                            </div>
                            
                            <div class="form-group">
                                <label>예산 사용률 (지출)</label>
                                <div class="progress">
                                    <div class="progress-bar bg-danger" role="progressbar" 
                                        style="width: <?php echo round($totalUtilizationRate); ?>%">
                                        <?php echo round($totalUtilizationRate, 1); ?>%
                                    </div>
                                </div>
                                <small class="text-muted">
                                    <?php echo number_format($expenseUtilized, 2); ?> / <?php echo number_format($expenseTotal, 2); ?> NPR
                                </small>
                            </div>
                            
                            <div class="form-group">
                                <label>예산 사용 효율성</label>
                                <?php 
                                $efficiencyText = '';
                                $efficiencyClass = '';
                                
                                if ($efficiency > 1.1) {
                                    $efficiencyText = '예산 초과 사용 중';
                                    $efficiencyClass = 'text-danger';
                                } elseif ($efficiency > 0.9 && $efficiency <= 1.1) {
                                    $efficiencyText = '적정 사용 중';
                                    $efficiencyClass = 'text-success';
                                } elseif ($efficiency > 0) {
                                    $efficiencyText = '예산 여유 있음';
                                    $efficiencyClass = 'text-info';
                                } else {
                                    $efficiencyText = '미사용';
                                    $efficiencyClass = 'text-muted';
                                }
                                ?>
                                <h5 class="<?php echo $efficiencyClass; ?>"><?php echo $efficiencyText; ?></h5>
                                <small class="text-muted">
                                    시간 진행률 대비 예산 사용률: <?php echo round($efficiency, 2); ?>
                                </small>
                            </div>
                        </div>
                        
                        <div class="card-footer">
                            <a href="budget-allocations.php?period_id=<?php echo $periodId; ?>" class="btn btn-primary btn-block">
                                <i class="fas fa-money-bill-wave mr-1"></i> 예산 할당 관리
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-8">
                    <!-- 예산 할당 카드 -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-money-bill-wave mr-1"></i>
                                예산 할당 목록
                            </h3>
                            <div class="card-tools">
                                <?php if (hasPermission('finance_budget_allocations') && $period['status'] !== 'closed'): ?>
                                <a href="budget-allocations.php?period_id=<?php echo $periodId; ?>" class="btn btn-primary btn-sm">
                                    <i class="fas fa-edit mr-1"></i> 예산 할당 관리
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="card-body">
                            <h5>지출 예산</h5>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>카테고리</th>
                                            <th class="text-right">할당액</th>
                                            <th class="text-right">사용액</th>
                                            <th class="text-right">잔액</th>
                                            <th>사용률</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $expenseAllocations = array_filter($allocations, function($a) {
                                            return $a['category_type'] === 'expense' || $a['category_type'] === 'both';
                                        });
                                        
                                        if (empty($expenseAllocations)):
                                        ?>
                                        <tr>
                                            <td colspan="5" class="text-center">지출 카테고리에 할당된 예산이 없습니다.</td>
                                        </tr>
                                        <?php else: ?>
                                            <?php foreach ($expenseAllocations as $allocation): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($allocation['category_name']); ?></td>
                                                <td class="text-right"><?php echo number_format($allocation['allocated_amount'], 2); ?></td>
                                                <td class="text-right"><?php echo number_format($allocation['utilized_amount'], 2); ?></td>
                                                <td class="text-right"><?php echo number_format($allocation['remaining_amount'], 2); ?></td>
                                                <td>
                                                    <div class="progress progress-sm">
                                                        <div class="progress-bar bg-danger" role="progressbar" 
                                                            style="width: <?php echo round($allocation['utilization_percentage']); ?>%">
                                                        </div>
                                                    </div>
                                                    <small>
                                                        <?php echo round($allocation['utilization_percentage'], 1); ?>%
                                                    </small>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                            <tr class="bg-light">
                                                <th>합계</th>
                                                <th class="text-right"><?php echo number_format($expenseTotal, 2); ?></th>
                                                <th class="text-right"><?php echo number_format($expenseUtilized, 2); ?></th>
                                                <th class="text-right"><?php echo number_format($expenseTotal - $expenseUtilized, 2); ?></th>
                                                <th>
                                                    <div class="progress progress-sm">
                                                        <div class="progress-bar bg-danger" role="progressbar" 
                                                            style="width: <?php echo round($totalUtilizationRate); ?>%">
                                                        </div>
                                                    </div>
                                                    <small>
                                                        <?php echo round($totalUtilizationRate, 1); ?>%
                                                    </small>
                                                </th>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <?php if (!empty(array_filter($allocations, function($a) { return $a['category_type'] === 'income' || $a['category_type'] === 'both'; }))): ?>
                            <h5 class="mt-4">수입 예산</h5>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>카테고리</th>
                                            <th class="text-right">예상액</th>
                                            <th class="text-right">실현액</th>
                                            <th class="text-right">차액</th>
                                            <th>달성률</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $incomeAllocations = array_filter($allocations, function($a) {
                                            return $a['category_type'] === 'income' || $a['category_type'] === 'both';
                                        });
                                        ?>
                                        <?php foreach ($incomeAllocations as $allocation): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($allocation['category_name']); ?></td>
                                            <td class="text-right"><?php echo number_format($allocation['allocated_amount'], 2); ?></td>
                                            <td class="text-right"><?php echo number_format($allocation['utilized_amount'], 2); ?></td>
                                            <td class="text-right"><?php echo number_format($allocation['utilized_amount'] - $allocation['allocated_amount'], 2); ?></td>
                                            <td>
                                                <div class="progress progress-sm">
                                                    <div class="progress-bar bg-success" role="progressbar" 
                                                        style="width: <?php echo round($allocation['utilization_percentage']); ?>%">
                                                    </div>
                                                </div>
                                                <small>
                                                    <?php echo round($allocation['utilization_percentage'], 1); ?>%
                                                </small>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                        
                                        <?php
                                        $incomeUtilizationRate = ($incomeTotal > 0) ? 
                                            ($incomeUtilized / $incomeTotal * 100) : 0;
                                        ?>
                                        <tr class="bg-light">
                                            <th>합계</th>
                                            <th class="text-right"><?php echo number_format($incomeTotal, 2); ?></th>
                                            <th class="text-right"><?php echo number_format($incomeUtilized, 2); ?></th>
                                            <th class="text-right"><?php echo number_format($incomeUtilized - $incomeTotal, 2); ?></th>
                                            <th>
                                                <div class="progress progress-sm">
                                                    <div class="progress-bar bg-success" role="progressbar" 
                                                        style="width: <?php echo round($incomeUtilizationRate); ?>%">
                                                    </div>
                                                </div>
                                                <small>
                                                    <?php echo round($incomeUtilizationRate, 1); ?>%
                                                </small>
                                            </th>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (empty($allocations)): ?>
                            <div class="alert alert-warning mt-3">
                                <i class="icon fas fa-exclamation-triangle"></i>
                                이 예산 기간에 할당된 예산이 없습니다.
                                <?php if (hasPermission('finance_budget_allocations') && $period['status'] !== 'closed'): ?>
                                <a href="budget-allocations.php?period_id=<?php echo $periodId; ?>" class="alert-link">예산 할당 관리</a> 페이지로 이동하여 예산을 할당하세요.
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- 월별 예산 차트 -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-chart-line mr-1"></i>
                                월별 예산 사용 추이
                            </h3>
                        </div>
                        <div class="card-body">
                            <?php
                            // 월별 예산 사용 데이터 조회
                            $monthlySql = "SELECT DATE_FORMAT(ft.transaction_date, '%Y-%m') as month,
                                          SUM(CASE WHEN ft.transaction_type = 'expense' THEN ft.amount ELSE 0 END) as expense,
                                          SUM(CASE WHEN ft.transaction_type = 'income' THEN ft.amount ELSE 0 END) as income
                                          FROM financial_transactions ft
                                          WHERE ft.transaction_date BETWEEN ? AND ?
                                          AND ft.status = 'completed'
                                          GROUP BY DATE_FORMAT(ft.transaction_date, '%Y-%m')
                                          ORDER BY month";
                            
                            $monthlyStmt = $conn->prepare($monthlySql);
                            $monthlyStmt->bind_param("ss", $period['start_date'], $period['end_date']);
                            $monthlyStmt->execute();
                            $monthlyResult = $monthlyStmt->get_result();
                            
                            // 월별 데이터가 있는지 확인
                            if ($monthlyResult->num_rows > 0) {
                                $monthlyData = [];
                                $months = [];
                                $expenseData = [];
                                $incomeData = [];
                                
                                while ($row = $monthlyResult->fetch_assoc()) {
                                    $monthlyData[] = $row;
                                    $months[] = date('Y년 n월', strtotime($row['month'] . '-01'));
                                    $expenseData[] = $row['expense'];
                                    $incomeData[] = $row['income'];
                                }
                            ?>
                            <div class="chart">
                                <canvas id="monthlyBudgetChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                            </div>
                            <?php } else { ?>
                            <div class="alert alert-info">
                                <i class="icon fas fa-info-circle"></i>
                                이 예산 기간에 대한 월별 데이터가 아직 없습니다.
                            </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 상태 변경 확인 모달 -->
<div class="modal fade" id="statusChangeModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="statusChangeTitle">상태 변경 확인</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="statusChangeBody">
                상태를 변경하시겠습니까?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">취소</button>
                <a href="#" id="statusChangeConfirmBtn" class="btn btn-primary">확인</a>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('예산 기간 상세 페이지 로드됨: 기간 ID <?php echo $periodId; ?>');
    
    <?php if (isset($monthlyData) && !empty($monthlyData)): ?>
    // 월별 예산 차트
    var monthlyCtx = document.getElementById('monthlyBudgetChart').getContext('2d');
    var monthlyBudgetChart = new Chart(monthlyCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($months); ?>,
            datasets: [
                {
                    label: '지출',
                    backgroundColor: 'rgba(231, 74, 59, 0.2)',
                    borderColor: '#e74a3b',
                    pointBackgroundColor: '#e74a3b',
                    pointBorderColor: '#fff',
                    pointRadius: 4,
                    fill: true,
                    data: <?php echo json_encode($expenseData); ?>
                },
                {
                    label: '수입',
                    backgroundColor: 'rgba(28, 200, 138, 0.2)',
                    borderColor: '#1cc88a',
                    pointBackgroundColor: '#1cc88a',
                    pointBorderColor: '#fff',
                    pointRadius: 4,
                    fill: true,
                    data: <?php echo json_encode($incomeData); ?>
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    grid: {
                        display: false
                    }
                },
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value.toLocaleString() + ' NPR';
                        }
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + context.raw.toLocaleString() + ' NPR';
                        }
                    }
                }
            }
        }
    });
    <?php endif; ?>
});

// 상태 변경 확인 함수
function confirmStatusChange(status, message) {
    document.getElementById('statusChangeBody').textContent = message;
    document.getElementById('statusChangeConfirmBtn').href = 'budget-period-details.php?id=<?php echo $periodId; ?>&action=change_status&status=' + status + '&csrf_token=<?php echo generateCsrfToken(); ?>';
    
    var statusLabel = '';
    var buttonClass = '';
    
    if (status === 'active') {
        statusLabel = '활성화';
        buttonClass = 'btn-success';
    } else if (status === 'closed') {
        statusLabel = '종료';
        buttonClass = 'btn-danger';
    } else if (status === 'planning') {
        statusLabel = '계획 중으로 변경';
        buttonClass = 'btn-info';
    }
    
    document.getElementById('statusChangeTitle').textContent = '예산 기간 ' + statusLabel + ' 확인';
    
    var confirmBtn = document.getElementById('statusChangeConfirmBtn');
    confirmBtn.className = confirmBtn.className.replace(/btn-\w+/, buttonClass);
    confirmBtn.textContent = statusLabel;
    
    $('#statusChangeModal').modal('show');
}
</script>

<?php
// 푸터 포함
include '../../templates/footer.php';
?>