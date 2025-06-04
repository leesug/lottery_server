<?php
/**
 * 재무 관리 - 예산 기간 편집 페이지
 * 
 * 이 페이지는 기존 예산 기간의 정보를 수정하는 기능을 제공합니다.
 */

// 세션 시작 및 필수 파일 포함
session_start();
require_once '../../includes/auth.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// 인증 확인
checkAuth();

// 필요한 권한 확인
$requiredPermissions = ['finance_budget_update'];
checkPermissions($requiredPermissions);

// 기간 ID 확인
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    setAlert('기간 ID가 유효하지 않습니다.', 'error');
    redirectTo('budget-periods.php');
}

$periodId = intval($_GET['id']);

// 데이터베이스 연결
$conn = getDBConnection();

// 기존 예산 기간 정보 조회
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

// 종료된 기간은 편집 불가
if ($period['status'] === 'closed') {
    setAlert('종료된 예산 기간은 편집할 수 없습니다.', 'error');
    redirectTo('budget-periods.php');
}

// 폼 제출 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF 토큰 검증
    verifyCsrfToken($_POST['csrf_token']);
    
    // 폼 데이터 검증
    $periodName = sanitizeInput($_POST['period_name']);
    $startDate = sanitizeInput($_POST['start_date']);
    $endDate = sanitizeInput($_POST['end_date']);
    $status = sanitizeInput($_POST['status']);
    $notes = sanitizeInput($_POST['notes']);
    
    // 유효성 검사
    $errors = [];
    
    if (empty($periodName)) {
        $errors[] = "예산 기간명을 입력해주세요.";
    }
    
    if (empty($startDate)) {
        $errors[] = "시작일을 입력해주세요.";
    }
    
    if (empty($endDate)) {
        $errors[] = "종료일을 입력해주세요.";
    }
    
    if (!empty($startDate) && !empty($endDate) && strtotime($startDate) >= strtotime($endDate)) {
        $errors[] = "종료일은 시작일보다 나중이어야 합니다.";
    }
    
    // 같은 기간에 다른 예산 기간이 있는지 확인 (자기 자신 제외)
    if (!empty($startDate) && !empty($endDate)) {
        $checkSql = "SELECT COUNT(*) as count FROM budget_periods 
                    WHERE id != ? AND (
                        (start_date <= ? AND end_date >= ?) 
                        OR (start_date <= ? AND end_date >= ?) 
                        OR (start_date >= ? AND end_date <= ?)
                    )";
        
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param("issssss", $periodId, $endDate, $startDate, $endDate, $startDate, $startDate, $endDate);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        $overlapCount = $checkResult->fetch_assoc()['count'];
        
        if ($overlapCount > 0) {
            $errors[] = "입력한 기간이 기존 예산 기간과 중복됩니다.";
        }
    }
    
    // 'active' 상태로 변경하려는 경우 다른 활성 기간이 있는지 확인 (자기 자신 제외)
    if ($status === 'active' && $period['status'] !== 'active') {
        $activeCheckSql = "SELECT COUNT(*) as count FROM budget_periods WHERE status = 'active' AND id != ?";
        $activeCheckStmt = $conn->prepare($activeCheckSql);
        $activeCheckStmt->bind_param("i", $periodId);
        $activeCheckStmt->execute();
        $activeCheckResult = $activeCheckStmt->get_result();
        $activeCount = $activeCheckResult->fetch_assoc()['count'];
        
        if ($activeCount > 0) {
            $errors[] = "이미 활성화된 예산 기간이 있습니다. 먼저 기존 활성 기간을 종료해주세요.";
        }
    }
    
    // 오류가 없으면 예산 기간 업데이트
    if (empty($errors)) {
        try {
            $sql = "UPDATE budget_periods SET 
                    period_name = ?, 
                    start_date = ?, 
                    end_date = ?, 
                    status = ?, 
                    notes = ?, 
                    updated_at = NOW()
                    WHERE id = ?";
            
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("데이터베이스 준비 오류: " . $conn->error);
            }
            
            $stmt->bind_param("sssssi", $periodName, $startDate, $endDate, $status, $notes, $periodId);
            
            $result = $stmt->execute();
            if (!$result) {
                throw new Exception("예산 기간 업데이트 중 오류가 발생했습니다: " . $stmt->error);
            }
            
            // 성공 메시지 설정
            setAlert('예산 기간이 성공적으로 업데이트되었습니다.', 'success');
            
            // 로그 기록
            logActivity('finance', 'budget_period_update', "예산 기간 업데이트: ID {$periodId}, {$periodName}, {$startDate} ~ {$endDate}, 상태: {$status}");
            
            // 예산 기간 목록 페이지로 리디렉션
            redirectTo("budget-periods.php");
            
        } catch (Exception $e) {
            // 오류 로깅
            logError("예산 기간 업데이트 오류: " . $e->getMessage());
            
            // 오류 메시지 설정
            setAlert('예산 기간 업데이트 중 오류가 발생했습니다: ' . $e->getMessage(), 'error');
        }
    } else {
        // 폼 오류 메시지 설정
        setAlert('입력 양식에 오류가 있습니다: ' . implode(' ', $errors), 'error');
    }
}

// 페이지 제목 설정
$pageTitle = "예산 기간 편집";
$currentSection = "finance";
$currentPage = "budget";

// 헤더 및 네비게이션 포함
include '../../templates/header.php';
include '../../templates/navbar.php';
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
                        <li class="breadcrumb-item active">예산 기간 편집</li>
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
                                <i class="fas fa-edit mr-1"></i>
                                예산 기간 정보 편집
                            </h3>
                            <div class="card-tools">
                                <a href="budget-periods.php" class="btn btn-default btn-sm">
                                    <i class="fas fa-arrow-left mr-1"></i> 목록으로 돌아가기
                                </a>
                            </div>
                        </div>
                        <form id="editPeriodForm" method="post" action="">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                            
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="period_name">예산 기간명 <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="period_name" name="period_name" value="<?php echo htmlspecialchars($period['period_name']); ?>" required>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="start_date">시작일 <span class="text-danger">*</span></label>
                                            <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo date('Y-m-d', strtotime($period['start_date'])); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="end_date">종료일 <span class="text-danger">*</span></label>
                                            <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo date('Y-m-d', strtotime($period['end_date'])); ?>" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="status">상태 <span class="text-danger">*</span></label>
                                    <select class="form-control" id="status" name="status" required>
                                        <option value="planning" <?php if ($period['status'] === 'planning') echo 'selected'; ?>>계획 중</option>
                                        <option value="active" <?php if ($period['status'] === 'active') echo 'selected'; ?>>활성</option>
                                        <option value="closed" <?php if ($period['status'] === 'closed') echo 'selected'; ?>>종료</option>
                                    </select>
                                    <small class="form-text text-muted">활성 상태는 한 번에 하나만 가능합니다.</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="notes">비고</label>
                                    <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo htmlspecialchars($period['notes'] ?? ''); ?></textarea>
                                </div>
                            </div>
                            
                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save mr-1"></i> 저장
                                </button>
                                <a href="budget-periods.php" class="btn btn-default">
                                    <i class="fas fa-times mr-1"></i> 취소
                                </a>
                                <a href="budget-allocations.php?period_id=<?php echo $periodId; ?>" class="btn btn-success float-right">
                                    <i class="fas fa-money-bill-wave mr-1"></i> 예산 할당 관리
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
                                도움말
                            </h3>
                        </div>
                        <div class="card-body">
                            <h5>편집 제한 사항</h5>
                            <ul>
                                <li>종료된 예산 기간은 편집할 수 없습니다.</li>
                                <li>활성 상태는 한 번에 하나만 가능합니다.</li>
                                <li>예산 기간이 중복되지 않도록 주의하세요.</li>
                            </ul>
                            
                            <h5>상태 변경</h5>
                            <p>상태를 변경하면 다음과 같은 영향이 있습니다:</p>
                            <ul>
                                <li>계획 중 → 활성: 예산이 실행 단계로 전환됩니다.</li>
                                <li>활성 → 종료: 예산 집행이 종료되고 보고서가 생성됩니다.</li>
                                <li>계획 중 → 종료: 예산 기간이 사용되지 않고 종료됩니다.</li>
                            </ul>
                            
                            <div class="alert alert-warning mt-3">
                                <i class="icon fas fa-exclamation-triangle"></i>
                                활성 상태에서 종료 상태로 변경하면 되돌릴 수 없습니다. 주의하세요!
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-chart-pie mr-1"></i>
                                예산 할당 정보
                            </h3>
                        </div>
                        <div class="card-body">
                            <?php
                            // 예산 할당 요약 정보 조회
                            $allocationSql = "SELECT 
                                             COUNT(*) as count,
                                             SUM(allocated_amount) as total_allocated,
                                             SUM(utilized_amount) as total_utilized
                                             FROM budget_allocations 
                                             WHERE period_id = ?";
                            
                            $allocationStmt = $conn->prepare($allocationSql);
                            $allocationStmt->bind_param("i", $periodId);
                            $allocationStmt->execute();
                            $allocationResult = $allocationStmt->get_result();
                            $allocation = $allocationResult->fetch_assoc();
                            
                            $utilizationRate = ($allocation['total_allocated'] > 0) ? 
                                ($allocation['total_utilized'] / $allocation['total_allocated'] * 100) : 0;
                            ?>
                            
                            <div class="info-box bg-gradient-info">
                                <span class="info-box-icon"><i class="fas fa-money-check-alt"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">할당된 카테고리</span>
                                    <span class="info-box-number"><?php echo $allocation['count']; ?></span>
                                </div>
                            </div>
                            
                            <div class="info-box bg-gradient-success">
                                <span class="info-box-icon"><i class="fas fa-dollar-sign"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">총 할당액</span>
                                    <span class="info-box-number"><?php echo number_format($allocation['total_allocated'] ?? 0, 2); ?> NPR</span>
                                </div>
                            </div>
                            
                            <div class="info-box bg-gradient-warning">
                                <span class="info-box-icon"><i class="fas fa-chart-line"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">총 사용액 / 사용률</span>
                                    <span class="info-box-number"><?php echo number_format($allocation['total_utilized'] ?? 0, 2); ?> NPR / <?php echo round($utilizationRate, 1); ?>%</span>
                                    <div class="progress">
                                        <div class="progress-bar" style="width: <?php echo round($utilizationRate); ?>%"></div>
                                    </div>
                                </div>
                            </div>
                            
                            <a href="budget-allocations.php?period_id=<?php echo $periodId; ?>" class="btn btn-primary btn-block mt-3">
                                <i class="fas fa-money-bill-wave mr-1"></i> 예산 할당 관리
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('예산 기간 편집 페이지 로드됨: 기간 ID <?php echo $periodId; ?>');
    
    // 날짜 유효성 검증
    var startDateInput = document.getElementById('start_date');
    var endDateInput = document.getElementById('end_date');
    
    function validateDates() {
        var startDate = new Date(startDateInput.value);
        var endDate = new Date(endDateInput.value);
        
        if (startDate && endDate && startDate >= endDate) {
            endDateInput.setCustomValidity('종료일은 시작일보다 나중이어야 합니다.');
        } else {
            endDateInput.setCustomValidity('');
        }
    }
    
    startDateInput.addEventListener('change', validateDates);
    endDateInput.addEventListener('change', validateDates);
    
    // 상태 변경 시 경고
    var statusSelect = document.getElementById('status');
    var originalStatus = statusSelect.value;
    
    statusSelect.addEventListener('change', function() {
        var newStatus = this.value;
        
        if (newStatus === 'active' && originalStatus !== 'active') {
            alert('활성 상태는 한 번에 하나의 예산 기간만 가능합니다. 다른 활성 기간이 있는 경우 저장되지 않습니다.');
        } else if (newStatus === 'closed' && originalStatus !== 'closed') {
            if (!confirm('예산 기간을 종료 상태로 변경하면 되돌릴 수 없습니다. 계속하시겠습니까?')) {
                this.value = originalStatus;
            }
        }
    });
    
    // 폼 제출 전 최종 검증
    document.getElementById('editPeriodForm').addEventListener('submit', function(e) {
        validateDates();
        
        if (endDateInput.validity.customError) {
            e.preventDefault();
            alert(endDateInput.validationMessage);
        }
    });
});
</script>

<?php
// 푸터 포함
include '../../templates/footer.php';
?>