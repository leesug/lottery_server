<?php
/**
 * 재무 관리 - 예산 활용 현황 페이지
 * 
 * 이 페이지는 예산 기간별 활용 현황 및 분석 정보를 제공합니다.
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

// 필터 파라미터
$periodId = isset($_GET['period_id']) ? intval($_GET['period_id']) : 0;
$categoryType = isset($_GET['category_type']) ? sanitizeInput($_GET['category_type']) : '';
$sortBy = isset($_GET['sort']) ? sanitizeInput($_GET['sort']) : 'utilization_percentage';
$sortOrder = isset($_GET['order']) && strtolower($_GET['order']) === 'asc' ? 'ASC' : 'DESC';

// 활성 예산 기간 조회 (기본값)
if ($periodId === 0) {
    $activePeriodSql = "SELECT id FROM budget_periods WHERE status = 'active' ORDER BY end_date DESC LIMIT 1";
    $activePeriodResult = $conn->query($activePeriodSql);
    
    if ($activePeriodResult->num_rows > 0) {
        $periodId = $activePeriodResult->fetch_assoc()['id'];
    } else {
        // 활성 기간이 없으면 가장 최근 기간 사용
        $latestPeriodSql = "SELECT id FROM budget_periods ORDER BY end_date DESC LIMIT 1";
        $latestPeriodResult = $conn->query($latestPeriodSql);
        
        if ($latestPeriodResult->num_rows > 0) {
            $periodId = $latestPeriodResult->fetch_assoc()['id'];
        }
    }
}

// 예산 기간 목록 조회
$periodsSql = "SELECT id, period_name, start_date, end_date, status FROM budget_periods ORDER BY end_date DESC";
$periodsResult = $conn->query($periodsSql);
$periods = [];

while ($row = $periodsResult->fetch_assoc()) {
    $periods[] = $row;
}

// 선택된 기간 정보 조회
$periodSql = "SELECT * FROM budget_periods WHERE id = ?";
$periodStmt = $conn->prepare($periodSql);
if ($periodStmt) {
    $periodStmt->bind_param("i", $periodId);
    $periodStmt->execute();
    $periodResult = $periodStmt->get_result();
    $period = $periodResult->num_rows > 0 ? $periodResult->fetch_assoc() : null;
}

// 예산 활용 현황 조회
$utilizationSql = "SELECT ba.*, fc.category_name, fc.category_type,
                  bp.period_name, bp.start_date, bp.end_date, bp.status as period_status
                  FROM budget_allocations ba
                  JOIN financial_categories fc ON ba.category_id = fc.id
                  JOIN budget_periods bp ON ba.period_id = bp.id
                  WHERE ba.period_id = ?";

$params = [$periodId];
$paramTypes = "i";

if (!empty($categoryType)) {
    if ($categoryType === 'income') {
        $utilizationSql .= " AND (fc.category_type = 'income' OR fc.category_type = 'both')";
    } elseif ($categoryType === 'expense') {
        $utilizationSql .= " AND (fc.category_type = 'expense' OR fc.category_type = 'both')";
    }
}

// 정렬 설정
$validSortColumns = ['category_name', 'allocated_amount', 'utilized_amount', 'remaining_amount', 'utilization_percentage'];
if (!in_array($sortBy, $validSortColumns)) {
    $sortBy = 'utilization_percentage';
}

$utilizationSql .= " ORDER BY {$sortBy} {$sortOrder}";

$utilizationStmt = $conn->prepare($utilizationSql);
if ($utilizationStmt) {
    $utilizationStmt->bind_param($paramTypes, ...$params);
    $utilizationStmt->execute();
    $utilizationResult = $utilizationStmt->get_result();
    $allocations = [];
    
    while ($row = $utilizationResult->fetch_assoc()) {
        $allocations[] = $row;
    }
}

// 예산 진행 상황 계산
$totalIncomeBudget = 0;
$totalIncomeUtilized = 0;
$totalExpenseBudget = 0;
$totalExpenseUtilized = 0;

foreach ($allocations as $allocation) {
    if ($allocation['category_type'] === 'income' || $allocation['category_type'] === 'both') {
        $totalIncomeBudget += $allocation['allocated_amount'];
        $totalIncomeUtilized += $allocation['utilized_amount'];
    }
    
    if ($allocation['category_type'] === 'expense' || $allocation['category_type'] === 'both') {
        $totalExpenseBudget += $allocation['allocated_amount'];
        $totalExpenseUtilized += $allocation['utilized_amount'];
    }
}

// 시간 진행률 계산
$timeProgressRate = 0;
if ($period) {
    $now = time();
    $startDate = strtotime($period['start_date']);
    $endDate = strtotime($period['end_date']);
    $totalDays = ($endDate - $startDate) / (60 * 60 * 24);
    $elapsedDays = max(0, min($totalDays, ($now - $startDate) / (60 * 60 * 24)));
    $timeProgressRate = ($totalDays > 0) ? ($elapsedDays / $totalDays * 100) : 0;
}

// 지출 예산 사용률
$expenseUtilizationRate = ($totalExpenseBudget > 0) ? ($totalExpenseUtilized / $totalExpenseBudget * 100) : 0;

// 수입 예산 달성률
$incomeUtilizationRate = ($totalIncomeBudget > 0) ? ($totalIncomeUtilized / $totalIncomeBudget * 100) : 0;

// 예산 효율성 평가
$timeEfficiency = 0;
$efficiencyStatus = '';

if ($timeProgressRate > 0) {
    $timeEfficiency = $expenseUtilizationRate / $timeProgressRate;
    
    if ($timeEfficiency > 1.1) {
        $efficiencyStatus = 'over'; // 초과 사용 중
    } elseif ($timeEfficiency >= 0.9 && $timeEfficiency <= 1.1) {
        $efficiencyStatus = 'optimal'; // 적정 사용 중
    } else {
        $efficiencyStatus = 'under'; // 예산 여유 있음
    }
}

// 페이지 제목 설정
$pageTitle = "예산 활용 현황";
$currentSection = "finance";
$currentPage = "budget";

// 헤더 및 네비게이션 포함
include '../../templates/header.php';
include '../../templates/navbar.php';

// 카테고리 유형 한글명 반환 함수
function getCategoryTypeLabel($type) {
    $labels = [
        'income' => '수입',
        'expense' => '지출',
        'both' => '수입/지출'
    ];
    
    return isset($labels[$type]) ? $labels[$type] : ucfirst($type);
}

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
                        <li class="breadcrumb-item"><a href="budget-periods.php">예산 기간</a></li>
                        <li class="breadcrumb-item active">예산 활용 현황</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">
            <!-- 기간 선택 및 필터 -->
            <div class="row mb-3">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body">
                            <form id="filterForm" method="get" class="form-inline">
                                <div class="form-group mr-2">
                                    <label for="period_id" class="mr-1">예산 기간:</label>
                                    <select class="form-control form-control-sm" id="period_id" name="period_id">
                                        <?php foreach ($periods as $p): ?>
                                        <option value="<?php echo $p['id']; ?>" <?php if ($p['id'] == $periodId) echo 'selected'; ?>>
                                            <?php echo htmlspecialchars($p['period_name']); ?>
                                            <?php 
                                            $statusBadge = getStatusBadge($p['status']);
                                            echo " <span class=\"badge badge-{$statusBadge['color']}\">{$statusBadge['label']}</span>";
                                            ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group mr-2">
                                    <label for="category_type" class="mr-1">카테고리 유형:</label>
                                    <select class="form-control form-control-sm" id="category_type" name="category_type">
                                        <option value="">모든 유형</option>
                                        <option value="income" <?php if ($categoryType === 'income') echo 'selected'; ?>>수입</option>
                                        <option value="expense" <?php if ($categoryType === 'expense') echo 'selected'; ?>>지출</option>
                                    </select>
                                </div>
                                
                                <div class="form-group mr-2">
                                    <label for="sort" class="mr-1">정렬:</label>
                                    <select class="form-control form-control-sm" id="sort" name="sort">
                                        <option value="category_name" <?php if ($sortBy === 'category_name') echo 'selected'; ?>>카테고리명</option>
                                        <option value="allocated_amount" <?php if ($sortBy === 'allocated_amount') echo 'selected'; ?>>할당액</option>
                                        <option value="utilized_amount" <?php if ($sortBy === 'utilized_amount') echo 'selected'; ?>>사용액</option>
                                        <option value="remaining_amount" <?php if ($sortBy === 'remaining_amount') echo 'selected'; ?>>잔액</option>
                                        <option value="utilization_percentage" <?php if ($sortBy === 'utilization_percentage') echo 'selected'; ?>>사용률</option>
                                    </select>
                                </div>
                                
                                <div class="form-group mr-2">
                                    <select class="form-control form-control-sm" id="order" name="order">
                                        <option value="desc" <?php if ($sortOrder === 'DESC') echo 'selected'; ?>>내림차순</option>
                                        <option value="asc" <?php if ($sortOrder === 'ASC') echo 'selected'; ?>>오름차순</option>
                                    </select>
                                </div>
                                
                                <button type="submit" class="btn btn-sm btn-info">
                                    <i class="fas fa-filter mr-1"></i> 필터 적용
                                </button>
                                
                                <a href="budget-utilization.php" class="btn btn-sm btn-default ml-1">
                                    <i class="fas fa-sync-alt mr-1"></i> 필터 초기화
                                </a>
                                
                                <?php if ($period): ?>
                                <a href="budget-period-details.php?id=<?php echo $periodId; ?>" class="btn btn-sm btn-primary ml-1">
                                    <i class="fas fa-info-circle mr-1"></i> 예산 기간 상세 정보
                                </a>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php if ($period): ?>
            <!-- 예산 진행 상황 요약 -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-chart-pie mr-1"></i>
                                <?php echo htmlspecialchars($period['period_name']); ?> 예산 진행 상황
                            </h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="info-box bg-gradient-info">
                                        <span class="info-box-icon"><i class="fas fa-clock"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">시간 진행률</span>
                                            <span class="info-box-number"><?php echo round($timeProgressRate, 1); ?>%</span>
                                            <div class="progress">
                                                <div class="progress-bar" style="width: <?php echo round($timeProgressRate); ?>%"></div>
                                            </div>
                                            <span class="progress-description">
                                                <?php 
                                                echo date('Y-m-d', strtotime($period['start_date'])); ?> ~ 
                                                <?php echo date('Y-m-d', strtotime($period['end_date'])); 
                                                ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <div class="info-box bg-gradient-danger">
                                        <span class="info-box-icon"><i class="fas fa-dollar-sign"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">지출 예산 사용률</span>
                                            <span class="info-box-number"><?php echo round($expenseUtilizationRate, 1); ?>%</span>
                                            <div class="progress">
                                                <div class="progress-bar" style="width: <?php echo round($expenseUtilizationRate); ?>%"></div>
                                            </div>
                                            <span class="progress-description">
                                                <?php echo number_format($totalExpenseUtilized, 2); ?> / 
                                                <?php echo number_format($totalExpenseBudget, 2); ?> NPR
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <div class="info-box bg-gradient-success">
                                        <span class="info-box-icon"><i class="fas fa-piggy-bank"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">수입 예산 달성률</span>
                                            <span class="info-box-number"><?php echo round($incomeUtilizationRate, 1); ?>%</span>
                                            <div class="progress">
                                                <div class="progress-bar" style="width: <?php echo round($incomeUtilizationRate); ?>%"></div>
                                            </div>
                                            <span class="progress-description">
                                                <?php echo number_format($totalIncomeUtilized, 2); ?> / 
                                                <?php echo number_format($totalIncomeBudget, 2); ?> NPR
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header bg-light">
                                            <h3 class="card-title">예산 효율성 평가</h3>
                                        </div>
                                        <div class="card-body">
                                            <?php if ($timeProgressRate > 0): ?>
                                            <h4 class="
                                                <?php
                                                if ($efficiencyStatus === 'over') echo 'text-danger';
                                                elseif ($efficiencyStatus === 'optimal') echo 'text-success';
                                                else echo 'text-info';
                                                ?>
                                            ">
                                                <?php
                                                if ($efficiencyStatus === 'over') echo '초과 사용 중';
                                                elseif ($efficiencyStatus === 'optimal') echo '적정 사용 중';
                                                else echo '예산 여유 있음';
                                                ?>
                                            </h4>
                                            <p>시간 진행률 대비 예산 사용률: <?php echo round($timeEfficiency, 2); ?></p>
                                            
                                            <?php if ($efficiencyStatus === 'over'): ?>
                                            <div class="alert alert-danger">
                                                <i class="icon fas fa-exclamation-triangle"></i>
                                                예산 사용률이 시간 진행률보다 높습니다. 지출을 줄이거나 예산을 조정하는 것이 좋습니다.
                                            </div>
                                            <?php elseif ($efficiencyStatus === 'optimal'): ?>
                                            <div class="alert alert-success">
                                                <i class="icon fas fa-check"></i>
                                                예산이 계획대로 잘 사용되고 있습니다.
                                            </div>
                                            <?php else: ?>
                                            <div class="alert alert-info">
                                                <i class="icon fas fa-info-circle"></i>
                                                예산 사용률이 시간 진행률보다 낮습니다. 필요한 경우 예산 조정을 고려해볼 수 있습니다.
                                            </div>
                                            <?php endif; ?>
                                            <?php else: ?>
                                            <div class="alert alert-warning">
                                                <i class="icon fas fa-exclamation-triangle"></i>
                                                아직 기간이 시작되지 않았거나 데이터가 충분하지 않습니다.
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header bg-light">
                                            <h3 class="card-title">예산 요약</h3>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="info-box bg-light">
                                                        <div class="info-box-content">
                                                            <span class="info-box-text">총 수입 예산</span>
                                                            <span class="info-box-number"><?php echo number_format($totalIncomeBudget, 2); ?> NPR</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="info-box bg-light">
                                                        <div class="info-box-content">
                                                            <span class="info-box-text">총 지출 예산</span>
                                                            <span class="info-box-number"><?php echo number_format($totalExpenseBudget, 2); ?> NPR</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="mt-3">
                                                <h5>예상 예산 잔액: <?php echo number_format($totalIncomeBudget - $totalExpenseBudget, 2); ?> NPR</h5>
                                                <h5>현재 실제 잔액: <?php echo number_format($totalIncomeUtilized - $totalExpenseUtilized, 2); ?> NPR</h5>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 카테고리별 예산 활용 현황 -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-list mr-1"></i>
                                카테고리별 예산 활용 현황
                            </h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if (empty($allocations)): ?>
                            <div class="alert alert-warning">
                                <i class="icon fas fa-exclamation-triangle"></i>
                                이 예산 기간에 할당된 예산이 없습니다.
                                <?php if (hasPermission('finance_budget_allocations') && $period['status'] !== 'closed'): ?>
                                <a href="budget-allocations.php?period_id=<?php echo $periodId; ?>" class="alert-link">예산 할당 관리</a> 페이지로 이동하여 예산을 할당하세요.
                                <?php endif; ?>
                            </div>
                            <?php else: ?>
                                <?php if (empty($categoryType) || $categoryType === 'expense'): ?>
                                <!-- 지출 카테고리 -->
                                <h4 class="mt-3">지출 카테고리</h4>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover">
                                        <thead>
                                            <tr>
                                                <th>카테고리</th>
                                                <th class="text-right">할당액 (NPR)</th>
                                                <th class="text-right">사용액 (NPR)</th>
                                                <th class="text-right">잔액 (NPR)</th>
                                                <th>사용률</th>
                                                <th>상태</th>
                                                <th>조치</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $expenseCategories = array_filter($allocations, function($a) {
                                                return $a['category_type'] === 'expense' || $a['category_type'] === 'both';
                                            });
                                            
                                            foreach ($expenseCategories as $allocation):
                                                $utilizationRate = ($allocation['allocated_amount'] > 0) ? 
                                                    ($allocation['utilized_amount'] / $allocation['allocated_amount'] * 100) : 0;
                                                
                                                // 상태 평가
                                                $statusClass = '';
                                                $statusText = '';
                                                $recommendationText = '';
                                                
                                                if ($timeProgressRate > 0) {
                                                    $efficiency = $utilizationRate / $timeProgressRate;
                                                    
                                                    if ($efficiency > 1.25) {
                                                        $statusClass = 'danger';
                                                        $statusText = '초과 사용';
                                                        $recommendationText = '지출 감소 필요';
                                                    } elseif ($efficiency > 1.1) {
                                                        $statusClass = 'warning';
                                                        $statusText = '과다 사용';
                                                        $recommendationText = '주의 필요';
                                                    } elseif ($efficiency >= 0.9) {
                                                        $statusClass = 'success';
                                                        $statusText = '적정 사용';
                                                        $recommendationText = '정상';
                                                    } elseif ($efficiency >= 0.5) {
                                                        $statusClass = 'info';
                                                        $statusText = '저조 사용';
                                                        $recommendationText = '활용도 개선 필요';
                                                    } else {
                                                        $statusClass = 'secondary';
                                                        $statusText = '미사용';
                                                        $recommendationText = '예산 재할당 고려';
                                                    }
                                                } else {
                                                    $statusClass = 'secondary';
                                                    $statusText = '미평가';
                                                    $recommendationText = '-';
                                                }
                                            ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($allocation['category_name']); ?></td>
                                                <td class="text-right"><?php echo number_format($allocation['allocated_amount'], 2); ?></td>
                                                <td class="text-right"><?php echo number_format($allocation['utilized_amount'], 2); ?></td>
                                                <td class="text-right"><?php echo number_format($allocation['remaining_amount'], 2); ?></td>
                                                <td>
                                                    <div class="progress progress-sm">
                                                        <div class="progress-bar bg-danger" role="progressbar" 
                                                            style="width: <?php echo round($utilizationRate); ?>%">
                                                        </div>
                                                    </div>
                                                    <small>
                                                        <?php echo round($utilizationRate, 1); ?>%
                                                    </small>
                                                </td>
                                                <td>
                                                    <span class="badge badge-<?php echo $statusClass; ?>">
                                                        <?php echo $statusText; ?>
                                                    </span>
                                                    <small class="d-block"><?php echo $recommendationText; ?></small>
                                                </td>
                                                <td>
                                                    <a href="budget-allocation-manage.php?id=<?php echo $allocation['id']; ?>" class="btn btn-xs btn-info">
                                                        <i class="fas fa-eye"></i> 상세
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (empty($categoryType) || $categoryType === 'income'): ?>
                                <!-- 수입 카테고리 -->
                                <h4 class="mt-4">수입 카테고리</h4>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover">
                                        <thead>
                                            <tr>
                                                <th>카테고리</th>
                                                <th class="text-right">예상액 (NPR)</th>
                                                <th class="text-right">실현액 (NPR)</th>
                                                <th class="text-right">차액 (NPR)</th>
                                                <th>달성률</th>
                                                <th>상태</th>
                                                <th>조치</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $incomeCategories = array_filter($allocations, function($a) {
                                                return $a['category_type'] === 'income' || $a['category_type'] === 'both';
                                            });
                                            
                                            foreach ($incomeCategories as $allocation):
                                                $utilizationRate = ($allocation['allocated_amount'] > 0) ? 
                                                    ($allocation['utilized_amount'] / $allocation['allocated_amount'] * 100) : 0;
                                                
                                                // 상태 평가
                                                $statusClass = '';
                                                $statusText = '';
                                                $recommendationText = '';
                                                
                                                if ($timeProgressRate > 0) {
                                                    $efficiency = $utilizationRate / $timeProgressRate;
                                                    
                                                    if ($efficiency > 1.25) {
                                                        $statusClass = 'success';
                                                        $statusText = '초과 달성';
                                                        $recommendationText = '우수';
                                                    } elseif ($efficiency > 1.1) {
                                                        $statusClass = 'info';
                                                        $statusText = '초과 달성';
                                                        $recommendationText = '양호';
                                                    } elseif ($efficiency >= 0.9) {
                                                        $statusClass = 'success';
                                                        $statusText = '정상 달성';
                                                        $recommendationText = '정상';
                                                    } elseif ($efficiency >= 0.5) {
                                                        $statusClass = 'warning';
                                                        $statusText = '저조 달성';
                                                        $recommendationText = '개선 필요';
                                                    } else {
                                                        $statusClass = 'danger';
                                                        $statusText = '미달성';
                                                        $recommendationText = '대책 필요';
                                                    }
                                                } else {
                                                    $statusClass = 'secondary';
                                                    $statusText = '미평가';
                                                    $recommendationText = '-';
                                                }
                                            ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($allocation['category_name']); ?></td>
                                                <td class="text-right"><?php echo number_format($allocation['allocated_amount'], 2); ?></td>
                                                <td class="text-right"><?php echo number_format($allocation['utilized_amount'], 2); ?></td>
                                                <td class="text-right"><?php echo number_format($allocation['utilized_amount'] - $allocation['allocated_amount'], 2); ?></td>
                                                <td>
                                                    <div class="progress progress-sm">
                                                        <div class="progress-bar bg-success" role="progressbar" 
                                                            style="width: <?php echo round($utilizationRate); ?>%">
                                                        </div>
                                                    </div>
                                                    <small>
                                                        <?php echo round($utilizationRate, 1); ?>%
                                                    </small>
                                                </td>
                                                <td>
                                                    <span class="badge badge-<?php echo $statusClass; ?>">
                                                        <?php echo $statusText; ?>
                                                    </span>
                                                    <small class="d-block"><?php echo $recommendationText; ?></small>
                                                </td>
                                                <td>
                                                    <a href="budget-allocation-manage.php?id=<?php echo $allocation['id']; ?>" class="btn btn-xs btn-info">
                                                        <i class="fas fa-eye"></i> 상세
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="alert alert-warning">
                <i class="icon fas fa-exclamation-triangle"></i>
                예산 기간을 선택해주세요.
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('예산 활용 현황 페이지 로드됨');
    
    // 필터 폼 변경 시 자동 제출
    document.querySelectorAll('#filterForm select').forEach(function(select) {
        select.addEventListener('change', function() {
            document.getElementById('filterForm').submit();
        });
    });
});
</script>

<?php
// 푸터 포함
include '../../templates/footer.php';
?>