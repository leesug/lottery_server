<?php
/**
 * 재무 관리 - 예산 할당 관리 페이지
 * 
 * 이 페이지는 개별 예산 할당을 추가하거나 편집하는 기능을 제공합니다.
 */

// 세션 시작 및 필수 파일 포함
session_start();
require_once '../../includes/auth.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// 인증 확인
checkAuth();

// 필요한 권한 확인
$requiredPermissions = ['finance_budget_allocations'];
checkPermissions($requiredPermissions);

// 데이터베이스 연결
$conn = getDBConnection();

// 편집 모드인지 확인
$isEditMode = isset($_GET['id']) && is_numeric($_GET['id']);

if ($isEditMode) {
    $allocationId = intval($_GET['id']);
    
    // 할당 정보 조회
    $allocationSql = "SELECT ba.*, bp.period_name, bp.start_date, bp.end_date, bp.status as period_status,
                     fc.category_name, fc.category_type
                     FROM budget_allocations ba
                     JOIN budget_periods bp ON ba.period_id = bp.id
                     JOIN financial_categories fc ON ba.category_id = fc.id
                     WHERE ba.id = ?";
    
    $allocationStmt = $conn->prepare($allocationSql);
    $allocationStmt->bind_param("i", $allocationId);
    $allocationStmt->execute();
    $allocationResult = $allocationStmt->get_result();
    
    if ($allocationResult->num_rows === 0) {
        setAlert('존재하지 않는 예산 할당입니다.', 'error');
        redirectTo('budget-periods.php');
    }
    
    $allocation = $allocationResult->fetch_assoc();
    $periodId = $allocation['period_id'];
    
    // 종료된 기간은 편집 불가
    if ($allocation['period_status'] === 'closed') {
        setAlert('종료된 예산 기간은 편집할 수 없습니다.', 'error');
        redirectTo("budget-period-details.php?id={$periodId}");
    }
} else {
    // 신규 모드
    if (!isset($_GET['period_id']) || !is_numeric($_GET['period_id']) || !isset($_GET['category_id']) || !is_numeric($_GET['category_id'])) {
        setAlert('필수 파라미터가 누락되었습니다.', 'error');
        redirectTo('budget-periods.php');
    }
    
    $periodId = intval($_GET['period_id']);
    $categoryId = intval($_GET['category_id']);
    
    // 예산 기간 정보 조회
    $periodSql = "SELECT * FROM budget_periods WHERE id = ?";
    $periodStmt = $conn->prepare($periodSql);
    $periodStmt->bind_param("i", $periodId);
    $periodStmt->execute();
    $periodResult = $periodStmt->get_result();
    
    if ($periodResult->num_rows === 0) {
        setAlert('존재하지 않는 예산 기간입니다.', 'error');
        redirectTo('budget-periods.php');
    }
    
    $period = $periodResult->fetch_assoc();
    
    // 종료된 기간은 편집 불가
    if ($period['status'] === 'closed') {
        setAlert('종료된 예산 기간은 편집할 수 없습니다.', 'error');
        redirectTo("budget-period-details.php?id={$periodId}");
    }
    
    // 카테고리 정보 조회
    $categorySql = "SELECT * FROM financial_categories WHERE id = ?";
    $categoryStmt = $conn->prepare($categorySql);
    $categoryStmt->bind_param("i", $categoryId);
    $categoryStmt->execute();
    $categoryResult = $categoryStmt->get_result();
    
    if ($categoryResult->num_rows === 0) {
        setAlert('존재하지 않는 카테고리입니다.', 'error');
        redirectTo("budget-allocations.php?period_id={$periodId}");
    }
    
    $category = $categoryResult->fetch_assoc();
    
    // 해당 기간에 이미 할당된 카테고리인지 확인
    $checkSql = "SELECT id FROM budget_allocations WHERE period_id = ? AND category_id = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("ii", $periodId, $categoryId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        $existingAllocation = $checkResult->fetch_assoc();
        setAlert('이미 할당된 카테고리입니다. 편집 페이지로 이동합니다.', 'info');
        redirectTo("budget-allocation-manage.php?id={$existingAllocation['id']}");
    }
    
    // 임시 할당 데이터 생성
    $allocation = [
        'period_id' => $periodId,
        'period_name' => $period['period_name'],
        'start_date' => $period['start_date'],
        'end_date' => $period['end_date'],
        'period_status' => $period['status'],
        'category_id' => $categoryId,
        'category_name' => $category['category_name'],
        'category_type' => $category['category_type'],
        'allocated_amount' => 0,
        'utilized_amount' => 0,
        'notes' => ''
    ];
}

// 폼 제출 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF 토큰 검증
    verifyCsrfToken($_POST['csrf_token']);
    
    // 폼 데이터 검증
    $allocatedAmount = floatval($_POST['allocated_amount']);
    $notes = sanitizeInput($_POST['notes']);
    
    // 유효성 검사
    $errors = [];
    
    if ($allocatedAmount < 0) {
        $errors[] = "할당액은 0 이상이어야 합니다.";
    }
    
    // 오류가 없으면 처리
    if (empty($errors)) {
        try {
            if ($isEditMode) {
                // 기존 할당 업데이트
                $updateSql = "UPDATE budget_allocations SET 
                            allocated_amount = ?, 
                            notes = ?, 
                            updated_at = NOW()
                            WHERE id = ?";
                
                $updateStmt = $conn->prepare($updateSql);
                $updateStmt->bind_param("dsi", $allocatedAmount, $notes, $allocationId);
                $updateResult = $updateStmt->execute();
                
                if (!$updateResult) {
                    throw new Exception("예산 할당 업데이트 중 오류가 발생했습니다: " . $updateStmt->error);
                }
                
                // 성공 메시지 설정
                setAlert('예산 할당이 성공적으로 업데이트되었습니다.', 'success');
                
                // 로그 기록
                logActivity('finance', 'budget_allocation_update', "예산 할당 업데이트: ID {$allocationId}, 카테고리: {$allocation['category_name']}, 금액: {$allocatedAmount} NPR");
            } else {
                // 새 할당 추가
                $insertSql = "INSERT INTO budget_allocations (
                            period_id, 
                            category_id, 
                            allocated_amount, 
                            utilized_amount, 
                            notes, 
                            created_at
                        ) VALUES (?, ?, ?, 0, ?, NOW())";
                
                $insertStmt = $conn->prepare($insertSql);
                $insertStmt->bind_param("iids", $periodId, $categoryId, $allocatedAmount, $notes);
                $insertResult = $insertStmt->execute();
                
                if (!$insertResult) {
                    throw new Exception("예산 할당 추가 중 오류가 발생했습니다: " . $insertStmt->error);
                }
                
                // 성공 메시지 설정
                setAlert('예산 할당이 성공적으로 추가되었습니다.', 'success');
                
                // 로그 기록
                logActivity('finance', 'budget_allocation_add', "예산 할당 추가: 기간: {$allocation['period_name']}, 카테고리: {$allocation['category_name']}, 금액: {$allocatedAmount} NPR");
            }
            
            // 예산 할당 페이지로 리디렉션
            redirectTo("budget-allocations.php?period_id={$periodId}");
            
        } catch (Exception $e) {
            // 오류 로깅
            logError("예산 할당 저장 오류: " . $e->getMessage());
            
            // 오류 메시지 설정
            setAlert('예산 할당 저장 중 오류가 발생했습니다: ' . $e->getMessage(), 'error');
        }
    } else {
        // 폼 오류 메시지 설정
        setAlert('입력 양식에 오류가 있습니다: ' . implode(' ', $errors), 'error');
    }
}

// 페이지 제목 설정
$pageTitle = $isEditMode ? "예산 할당 편집" : "예산 할당 추가";
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

// 상태 한글명 반환 함수
function getStatusLabel($status) {
    $labels = [
        'planning' => '계획 중',
        'active' => '활성',
        'closed' => '종료'
    ];
    
    return isset($labels[$status]) ? $labels[$status] : ucfirst($status);
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
                        <li class="breadcrumb-item"><a href="budget-period-details.php?id=<?php echo $allocation['period_id']; ?>"><?php echo htmlspecialchars($allocation['period_name']); ?></a></li>
                        <li class="breadcrumb-item"><a href="budget-allocations.php?period_id=<?php echo $allocation['period_id']; ?>">예산 할당</a></li>
                        <li class="breadcrumb-item active"><?php echo $pageTitle; ?></li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-money-bill-wave mr-1"></i>
                                <?php echo htmlspecialchars($allocation['category_name']); ?> 예산 할당
                            </h3>
                            <div class="card-tools">
                                <a href="budget-allocations.php?period_id=<?php echo $allocation['period_id']; ?>" class="btn btn-default btn-sm">
                                    <i class="fas fa-arrow-left mr-1"></i> 예산 할당 목록으로 돌아가기
                                </a>
                            </div>
                        </div>
                        <form method="post" action="">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                            
                            <div class="card-body">
                                <div class="form-group">
                                    <label>예산 기간</label>
                                    <p class="form-control-static"><?php echo htmlspecialchars($allocation['period_name']); ?></p>
                                </div>
                                
                                <div class="form-group">
                                    <label>기간 범위</label>
                                    <p class="form-control-static">
                                        <?php echo date('Y-m-d', strtotime($allocation['start_date'])); ?> ~ 
                                        <?php echo date('Y-m-d', strtotime($allocation['end_date'])); ?>
                                    </p>
                                </div>
                                
                                <div class="form-group">
                                    <label>카테고리</label>
                                    <p class="form-control-static">
                                        <?php echo htmlspecialchars($allocation['category_name']); ?> 
                                        (<?php echo getCategoryTypeLabel($allocation['category_type']); ?>)
                                    </p>
                                </div>
                                
                                <div class="form-group">
                                    <label for="allocated_amount">
                                        <?php echo $allocation['category_type'] === 'income' ? '예상액' : '할당액'; ?> (NPR) <span class="text-danger">*</span>
                                    </label>
                                    <input type="number" step="0.01" min="0" class="form-control" id="allocated_amount" name="allocated_amount" value="<?php echo $allocation['allocated_amount']; ?>" required>
                                </div>
                                
                                <?php if ($isEditMode): ?>
                                <div class="form-group">
                                    <label>
                                        <?php echo $allocation['category_type'] === 'income' ? '실현액' : '사용액'; ?> (NPR)
                                    </label>
                                    <input type="number" step="0.01" min="0" class="form-control" value="<?php echo $allocation['utilized_amount']; ?>" readonly>
                                    <small class="form-text text-muted">이 값은 자동으로 계산되며 직접 편집할 수 없습니다.</small>
                                </div>
                                
                                <div class="form-group">
                                    <label>사용률</label>
                                    <div class="progress">
                                        <?php 
                                        $utilizationRate = ($allocation['allocated_amount'] > 0) ? 
                                            ($allocation['utilized_amount'] / $allocation['allocated_amount'] * 100) : 0;
                                        ?>
                                        <div class="progress-bar <?php echo $allocation['category_type'] === 'income' ? 'bg-success' : 'bg-danger'; ?>" role="progressbar" 
                                            style="width: <?php echo round($utilizationRate); ?>%">
                                            <?php echo round($utilizationRate, 1); ?>%
                                        </div>
                                    </div>
                                    <small class="form-text text-muted">
                                        <?php echo number_format($allocation['utilized_amount'], 2); ?> / 
                                        <?php echo number_format($allocation['allocated_amount'], 2); ?> NPR
                                    </small>
                                </div>
                                <?php endif; ?>
                                
                                <div class="form-group">
                                    <label for="notes">비고</label>
                                    <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo htmlspecialchars($allocation['notes'] ?? ''); ?></textarea>
                                </div>
                            </div>
                            
                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save mr-1"></i> 저장
                                </button>
                                <a href="budget-allocations.php?period_id=<?php echo $allocation['period_id']; ?>" class="btn btn-default">
                                    <i class="fas fa-times mr-1"></i> 취소
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-info-circle mr-1"></i>
                                카테고리 정보
                            </h3>
                        </div>
                        <div class="card-body">
                            <h5><?php echo htmlspecialchars($allocation['category_name']); ?></h5>
                            <p><strong>유형:</strong> <?php echo getCategoryTypeLabel($allocation['category_type']); ?></p>
                            
                            <?php
                            // 카테고리 설명 조회
                            $descSql = "SELECT description FROM financial_categories WHERE id = ?";
                            $descStmt = $conn->prepare($descSql);
                            $descStmt->bind_param("i", $allocation['category_id']);
                            $descStmt->execute();
                            $descResult = $descStmt->get_result();
                            $categoryDesc = $descResult->fetch_assoc()['description'] ?? '';
                            
                            if (!empty($categoryDesc)):
                            ?>
                            <p><strong>설명:</strong> <?php echo htmlspecialchars($categoryDesc); ?></p>
                            <?php endif; ?>
                            
                            <?php if ($isEditMode): ?>
                            <h5 class="mt-4">최근 거래</h5>
                            <?php
                            // 최근 관련 거래 조회
                            $transactionsSql = "SELECT transaction_type, amount, transaction_date, status
                                              FROM financial_transactions
                                              WHERE category_id = ? AND transaction_date BETWEEN ? AND ?
                                              ORDER BY transaction_date DESC
                                              LIMIT 5";
                            
                            $transactionsStmt = $conn->prepare($transactionsSql);
                            $transactionsStmt->bind_param("iss", $allocation['category_id'], $allocation['start_date'], $allocation['end_date']);
                            $transactionsStmt->execute();
                            $transactionsResult = $transactionsStmt->get_result();
                            
                            if ($transactionsResult->num_rows > 0):
                            ?>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>유형</th>
                                            <th>금액</th>
                                            <th>날짜</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($transaction = $transactionsResult->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $transaction['transaction_type']; ?></td>
                                            <td><?php echo number_format($transaction['amount'], 2); ?></td>
                                            <td><?php echo date('Y-m-d', strtotime($transaction['transaction_date'])); ?></td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php else: ?>
                            <p class="text-muted">이 카테고리의 최근 거래가 없습니다.</p>
                            <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-calendar-alt mr-1"></i>
                                예산 기간 정보
                            </h3>
                        </div>
                        <div class="card-body">
                            <h5><?php echo htmlspecialchars($allocation['period_name']); ?></h5>
                            <p><strong>상태:</strong> <?php echo getStatusLabel($allocation['period_status']); ?></p>
                            <p><strong>기간:</strong> <?php echo date('Y-m-d', strtotime($allocation['start_date'])); ?> ~ <?php echo date('Y-m-d', strtotime($allocation['end_date'])); ?></p>
                            
                            <?php
                            // 기간의 총 예산 조회
                            $totalSql = "SELECT 
                                       SUM(CASE WHEN fc.category_type = 'income' OR fc.category_type = 'both' THEN ba.allocated_amount ELSE 0 END) as total_income,
                                       SUM(CASE WHEN fc.category_type = 'expense' OR fc.category_type = 'both' THEN ba.allocated_amount ELSE 0 END) as total_expense
                                       FROM budget_allocations ba
                                       JOIN financial_categories fc ON ba.category_id = fc.id
                                       WHERE ba.period_id = ?";
                            
                            $totalStmt = $conn->prepare($totalSql);
                            $totalStmt->bind_param("i", $allocation['period_id']);
                            $totalStmt->execute();
                            $totalResult = $totalStmt->get_result();
                            $total = $totalResult->fetch_assoc();
                            ?>
                            
                            <p><strong>총 수입 예산:</strong> <?php echo number_format($total['total_income'] ?? 0, 2); ?> NPR</p>
                            <p><strong>총 지출 예산:</strong> <?php echo number_format($total['total_expense'] ?? 0, 2); ?> NPR</p>
                            <p><strong>예상 잔액:</strong> <?php echo number_format(($total['total_income'] ?? 0) - ($total['total_expense'] ?? 0), 2); ?> NPR</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('예산 할당 <?php echo $isEditMode ? '편집' : '추가'; ?> 페이지 로드됨');
});
</script>

<?php
// 푸터 포함
include '../../templates/footer.php';
?>