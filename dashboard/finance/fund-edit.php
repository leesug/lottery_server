<?php
/**
 * 재무 관리 - 기금 편집 페이지
 * 
 * 이 페이지는 기존 기금 정보를 편집하는 기능을 제공합니다.
 */

// 세션 시작 및 필수 파일 포함
session_start();
require_once '../../includes/auth.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// 인증 확인
checkAuth();

// 필요한 권한 확인
$requiredPermissions = ['finance_funds_edit'];
checkPermissions($requiredPermissions);

// 기금 ID 확인
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    setAlert('기금 ID가 유효하지 않습니다.', 'error');
    redirectTo('funds.php');
}

$fundId = intval($_GET['id']);

// 데이터베이스 연결
$conn = getDBConnection();

// 기금 정보 조회
$sql = "SELECT * FROM funds WHERE id = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    logError("Database prepare error: " . $conn->error);
    die("데이터베이스 오류가 발생했습니다.");
}

$stmt->bind_param("i", $fundId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    setAlert('존재하지 않는 기금입니다.', 'error');
    redirectTo('funds.php');
}

$fund = $result->fetch_assoc();

// 폼 제출 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF 토큰 검증
    verifyCsrfToken($_POST['csrf_token']);
    
    // 폼 데이터 검증
    $fundName = sanitizeInput($_POST['fund_name']);
    $fundType = sanitizeInput($_POST['fund_type']);
    $description = sanitizeInput($_POST['description']);
    $totalAllocation = floatval($_POST['total_allocation']);
    $allocationPercentage = !empty($_POST['allocation_percentage']) ? floatval($_POST['allocation_percentage']) : null;
    $status = sanitizeInput($_POST['status']);
    
    // 유효성 검사
    $errors = [];
    
    if (empty($fundName)) {
        $errors[] = "기금명을 입력해주세요.";
    }
    
    if (empty($fundType)) {
        $errors[] = "기금 유형을 선택해주세요.";
    }
    
    if ($totalAllocation <= 0) {
        $errors[] = "총 할당액은 0보다 커야 합니다.";
    }
    
    if ($allocationPercentage !== null && ($allocationPercentage <= 0 || $allocationPercentage > 100)) {
        $errors[] = "할당 비율은 0보다 크고 100 이하여야 합니다.";
    }
    
    if (empty($status)) {
        $errors[] = "상태를 선택해주세요.";
    }
    
    // 총 할당액이 현재 잔액보다 작은지 확인
    if ($totalAllocation < $fund['current_balance']) {
        $errors[] = "총 할당액은 현재 잔액(" . number_format($fund['current_balance'], 2) . " NPR)보다 작을 수 없습니다.";
    }
    
    // 오류가 없으면 기금 업데이트
    if (empty($errors)) {
        try {
            // 기금 업데이트
            $sql = "UPDATE funds SET 
                    fund_name = ?, 
                    fund_type = ?, 
                    description = ?, 
                    total_allocation = ?, 
                    allocation_percentage = ?, 
                    status = ?, 
                    updated_at = NOW()
                    WHERE id = ?";
            
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("데이터베이스 준비 오류: " . $conn->error);
            }
            
            $stmt->bind_param(
                "sssdssi", 
                $fundName, 
                $fundType, 
                $description, 
                $totalAllocation, 
                $allocationPercentage, 
                $status, 
                $fundId
            );
            
            $result = $stmt->execute();
            if (!$result) {
                throw new Exception("기금 업데이트 중 오류가 발생했습니다: " . $stmt->error);
            }
            
            // 성공 메시지 설정
            setAlert('기금이 성공적으로 업데이트되었습니다.', 'success');
            
            // 로그 기록
            logActivity('finance', 'fund_edit', "기금 편집: {$fundName} ({$fund['fund_code']})");
            
            // 상세 페이지로 리디렉션
            redirectTo("fund-details.php?id={$fundId}");
            
        } catch (Exception $e) {
            // 오류 로깅
            logError("기금 업데이트 오류: " . $e->getMessage());
            
            // 오류 메시지 설정
            setAlert('기금 업데이트 중 오류가 발생했습니다: ' . $e->getMessage(), 'error');
        }
    } else {
        // 폼 오류 메시지 설정
        setAlert('입력 양식에 오류가 있습니다: ' . implode(' ', $errors), 'error');
    }
}

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

// 페이지 제목 설정
$pageTitle = "기금 편집: " . $fund['fund_name'];
$currentSection = "finance";
$currentPage = "funds";

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
                        <li class="breadcrumb-item"><a href="funds.php">기금 관리</a></li>
                        <li class="breadcrumb-item active">기금 편집</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-edit mr-1"></i>
                                기금 정보 편집
                            </h3>
                        </div>
                        <form id="editFundForm" method="post" action="">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                            
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="fund_name">기금명<span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="fund_name" name="fund_name" value="<?php echo htmlspecialchars($fund['fund_name']); ?>" required>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="fund_code">기금 코드</label>
                                            <input type="text" class="form-control" id="fund_code" value="<?php echo htmlspecialchars($fund['fund_code']); ?>" readonly>
                                            <small class="form-text text-muted">기금 코드는 변경할 수 없습니다.</small>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="fund_type">기금 유형<span class="text-danger">*</span></label>
                                            <select class="form-control" id="fund_type" name="fund_type" required>
                                                <option value="">선택하세요</option>
                                                <?php foreach ($fundTypes as $key => $value): ?>
                                                    <option value="<?php echo $key; ?>" <?php echo ($fund['fund_type'] == $key) ? 'selected' : ''; ?>>
                                                        <?php echo $value; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="status">상태<span class="text-danger">*</span></label>
                                            <select class="form-control" id="status" name="status" required>
                                                <option value="">선택하세요</option>
                                                <?php foreach ($fundStatuses as $key => $value): ?>
                                                    <option value="<?php echo $key; ?>" <?php echo ($fund['status'] == $key) ? 'selected' : ''; ?>>
                                                        <?php echo $value; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="current_balance">현재 잔액</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">NPR</span>
                                                </div>
                                                <input type="text" class="form-control" id="current_balance" value="<?php echo number_format($fund['current_balance'], 2); ?>" readonly>
                                            </div>
                                            <small class="form-text text-muted">현재 잔액은 거래를 통해서만 변경할 수 있습니다.</small>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="total_allocation">총 할당액<span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">NPR</span>
                                                </div>
                                                <input type="number" class="form-control" id="total_allocation" name="total_allocation" min="<?php echo $fund['current_balance']; ?>" step="0.01" value="<?php echo $fund['total_allocation']; ?>" required>
                                            </div>
                                            <small class="form-text text-muted">총 할당액은 현재 잔액보다 작을 수 없습니다.</small>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="allocation_percentage">할당 비율 (%)</label>
                                            <div class="input-group">
                                                <input type="number" class="form-control" id="allocation_percentage" name="allocation_percentage" min="0.01" max="100" step="0.01" value="<?php echo $fund['allocation_percentage']; ?>">
                                                <div class="input-group-append">
                                                    <span class="input-group-text">%</span>
                                                </div>
                                            </div>
                                            <small class="form-text text-muted">매출에서 자동 할당될 비율입니다. 입력하지 않으면 수동으로만 할당 가능합니다.</small>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="description">설명</label>
                                    <textarea class="form-control" id="description" name="description" rows="4"><?php echo htmlspecialchars($fund['description']); ?></textarea>
                                </div>
                            </div>
                            
                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> 저장
                                </button>
                                <a href="fund-details.php?id=<?php echo $fundId; ?>" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> 취소
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../templates/footer.php'; ?>

<script>
$(document).ready(function() {
    // 폼 제출 전 검증
    $('#editFundForm').submit(function(e) {
        var fundName = $('#fund_name').val().trim();
        var fundType = $('#fund_type').val();
        var totalAllocation = parseFloat($('#total_allocation').val()) || 0;
        var currentBalance = parseFloat('<?php echo $fund['current_balance']; ?>');
        var status = $('#status').val();
        
        var isValid = true;
        
        // 필수 입력값 확인
        if (!fundName || !fundType || !status) {
            Swal.fire({
                icon: 'error',
                title: '입력 오류',
                text: '모든 필수 항목을 입력해주세요.'
            });
            isValid = false;
        }
        
        // 총 할당액 확인
        if (totalAllocation <= 0) {
            Swal.fire({
                icon: 'error',
                title: '입력 오류',
                text: '총 할당액은 0보다 커야 합니다.'
            });
            isValid = false;
        }
        
        // 총 할당액이 현재 잔액보다 작은지 확인
        if (totalAllocation < currentBalance) {
            Swal.fire({
                icon: 'error',
                title: '입력 오류',
                text: '총 할당액은 현재 잔액(' + currentBalance.toLocaleString() + ' NPR)보다 작을 수 없습니다.'
            });
            isValid = false;
        }
        
        // 할당 비율 확인
        var allocationPercentage = parseFloat($('#allocation_percentage').val()) || 0;
        if (allocationPercentage > 0 && allocationPercentage > 100) {
            Swal.fire({
                icon: 'error',
                title: '입력 오류',
                text: '할당 비율은 100% 이하여야 합니다.'
            });
            isValid = false;
        }
        
        return isValid;
    });
});
</script>

<?php
// 연결 종료
$stmt->close();
$conn->close();
?>