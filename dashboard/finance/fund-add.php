<?php
/**
 * 재무 관리 - 기금 추가 페이지
 * 
 * 이 페이지는 새로운 기금을 추가하는 기능을 제공합니다.
 */

// 세션 시작 및 필수 파일 포함
session_start();
require_once '../../includes/auth.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// 인증 확인
checkAuth();

// 필요한 권한 확인
$requiredPermissions = ['finance_funds_add'];
checkPermissions($requiredPermissions);

// 데이터베이스 연결
$conn = getDBConnection();

// 폼 제출 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF 토큰 검증
    verifyCsrfToken($_POST['csrf_token']);
    
    // 폼 데이터 검증
    $fundName = sanitizeInput($_POST['fund_name']);
    $fundCode = sanitizeInput($_POST['fund_code']);
    $fundType = sanitizeInput($_POST['fund_type']);
    $description = sanitizeInput($_POST['description']);
    $totalAllocation = floatval($_POST['total_allocation']);
    $initialBalance = floatval($_POST['initial_balance']);
    $allocationPercentage = !empty($_POST['allocation_percentage']) ? floatval($_POST['allocation_percentage']) : null;
    $status = sanitizeInput($_POST['status']);
    
    // 유효성 검사
    $errors = [];
    
    if (empty($fundName)) {
        $errors[] = "기금명을 입력해주세요.";
    }
    
    if (empty($fundCode)) {
        $errors[] = "기금 코드를 입력해주세요.";
    } else {
        // 중복 코드 검사
        $checkSql = "SELECT id FROM funds WHERE fund_code = ?";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param("s", $fundCode);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows > 0) {
            $errors[] = "이미 사용 중인 기금 코드입니다.";
        }
        $checkStmt->close();
    }
    
    if (empty($fundType)) {
        $errors[] = "기금 유형을 선택해주세요.";
    }
    
    if ($totalAllocation <= 0) {
        $errors[] = "총 할당액은 0보다 커야 합니다.";
    }
    
    if ($initialBalance < 0) {
        $errors[] = "초기 잔액은 0 이상이어야 합니다.";
    }
    
    if ($allocationPercentage !== null && ($allocationPercentage <= 0 || $allocationPercentage > 100)) {
        $errors[] = "할당 비율은 0보다 크고 100 이하여야 합니다.";
    }
    
    if (empty($status)) {
        $errors[] = "상태를 선택해주세요.";
    }
    
    // 오류가 없으면 기금 추가
    if (empty($errors)) {
        try {
            // 트랜잭션 시작
            $conn->begin_transaction();
            
            // 새 기금 추가
            $sql = "INSERT INTO funds (
                        fund_name, 
                        fund_code, 
                        fund_type, 
                        description, 
                        total_allocation, 
                        current_balance, 
                        allocation_percentage, 
                        status
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("데이터베이스 준비 오류: " . $conn->error);
            }
            
            $stmt->bind_param(
                "ssssddss", 
                $fundName, 
                $fundCode, 
                $fundType, 
                $description, 
                $totalAllocation, 
                $initialBalance, 
                $allocationPercentage, 
                $status
            );
            
            $result = $stmt->execute();
            if (!$result) {
                throw new Exception("기금 추가 중 오류가 발생했습니다: " . $stmt->error);
            }
            
            $newFundId = $stmt->insert_id;
            
            // 초기 잔액이 있으면 거래 추가
            if ($initialBalance > 0) {
                $transactionSql = "INSERT INTO fund_transactions (
                                    fund_id, 
                                    transaction_type, 
                                    amount, 
                                    transaction_date, 
                                    description, 
                                    status
                                ) VALUES (?, 'allocation', ?, NOW(), '초기 할당', 'completed')";
                
                $transactionStmt = $conn->prepare($transactionSql);
                if (!$transactionStmt) {
                    throw new Exception("데이터베이스 준비 오류: " . $conn->error);
                }
                
                $transactionStmt->bind_param(
                    "id", 
                    $newFundId, 
                    $initialBalance
                );
                
                $transactionResult = $transactionStmt->execute();
                if (!$transactionResult) {
                    throw new Exception("초기 거래 추가 중 오류가 발생했습니다: " . $transactionStmt->error);
                }
                
                $transactionStmt->close();
            }
            
            // 트랜잭션 커밋
            $conn->commit();
            
            // 성공 메시지 설정
            setAlert('기금이 성공적으로 추가되었습니다.', 'success');
            
            // 로그 기록
            logActivity('finance', 'fund_add', "새 기금 추가: {$fundName} ({$fundCode})");
            
            // 목록 페이지로 리디렉션
            redirectTo("funds.php");
            
        } catch (Exception $e) {
            // 트랜잭션 롤백
            $conn->rollback();
            
            // 오류 로깅
            logError("기금 추가 오류: " . $e->getMessage());
            
            // 오류 메시지 설정
            setAlert('기금 추가 중 오류가 발생했습니다: ' . $e->getMessage(), 'error');
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
$pageTitle = "새 기금 추가";
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
                        <li class="breadcrumb-item active">새 기금 추가</li>
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
                                <i class="fas fa-plus mr-1"></i>
                                새 기금 정보 입력
                            </h3>
                        </div>
                        <form id="addFundForm" method="post" action="">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                            
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="fund_name">기금명<span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="fund_name" name="fund_name" value="<?php echo isset($_POST['fund_name']) ? htmlspecialchars($_POST['fund_name']) : ''; ?>" required>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="fund_code">기금 코드<span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="fund_code" name="fund_code" value="<?php echo isset($_POST['fund_code']) ? htmlspecialchars($_POST['fund_code']) : generateFundCode(); ?>" required>
                                            <small class="form-text text-muted">예: PRIZE-001, CHARITY-001 등</small>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="fund_type">기금 유형<span class="text-danger">*</span></label>
                                            <select class="form-control" id="fund_type" name="fund_type" required>
                                                <option value="">선택하세요</option>
                                                <?php foreach ($fundTypes as $key => $value): ?>
                                                    <option value="<?php echo $key; ?>" <?php echo (isset($_POST['fund_type']) && $_POST['fund_type'] == $key) ? 'selected' : ''; ?>>
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
                                                    <option value="<?php echo $key; ?>" <?php echo (isset($_POST['status']) && $_POST['status'] == $key) ? 'selected' : ((!isset($_POST['status']) && $key == 'active') ? 'selected' : ''); ?>>
                                                        <?php echo $value; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="total_allocation">총 할당액<span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">NPR</span>
                                                </div>
                                                <input type="number" class="form-control" id="total_allocation" name="total_allocation" min="0.01" step="0.01" value="<?php echo isset($_POST['total_allocation']) ? htmlspecialchars($_POST['total_allocation']) : '0.00'; ?>" required>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="initial_balance">초기 잔액</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">NPR</span>
                                                </div>
                                                <input type="number" class="form-control" id="initial_balance" name="initial_balance" min="0" step="0.01" value="<?php echo isset($_POST['initial_balance']) ? htmlspecialchars($_POST['initial_balance']) : '0.00'; ?>">
                                            </div>
                                            <small class="form-text text-muted">초기 잔액이 0보다 크면 자동으로 초기 할당 거래가 생성됩니다.</small>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="allocation_percentage">할당 비율 (%)</label>
                                            <div class="input-group">
                                                <input type="number" class="form-control" id="allocation_percentage" name="allocation_percentage" min="0.01" max="100" step="0.01" value="<?php echo isset($_POST['allocation_percentage']) ? htmlspecialchars($_POST['allocation_percentage']) : ''; ?>">
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
                                    <textarea class="form-control" id="description" name="description" rows="4"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                                </div>
                            </div>
                            
                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> 저장
                                </button>
                                <a href="funds.php" class="btn btn-secondary">
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
    // 기금 유형 변경 시 자동으로 코드 패턴 생성
    $('#fund_type').change(function() {
        var fundType = $(this).val();
        if (fundType && $('#fund_code').val() === '') {
            generateCode(fundType);
        }
    });
    
    // 코드 생성 함수
    function generateCode(type) {
        $.ajax({
            url: '../../api/finance/generate-fund-code.php',
            type: 'GET',
            data: {
                type: type
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#fund_code').val(response.code);
                }
            }
        });
    }
    
    // 폼 제출 전 검증
    $('#addFundForm').submit(function(e) {
        var fundName = $('#fund_name').val().trim();
        var fundCode = $('#fund_code').val().trim();
        var fundType = $('#fund_type').val();
        var totalAllocation = parseFloat($('#total_allocation').val()) || 0;
        var initialBalance = parseFloat($('#initial_balance').val()) || 0;
        var status = $('#status').val();
        
        var isValid = true;
        
        // 필수 입력값 확인
        if (!fundName || !fundCode || !fundType || !status) {
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
        
        // 초기 잔액 확인
        if (initialBalance < 0) {
            Swal.fire({
                icon: 'error',
                title: '입력 오류',
                text: '초기 잔액은 0 이상이어야 합니다.'
            });
            isValid = false;
        }
        
        // 초기 잔액이 총 할당액보다 큰 경우
        if (initialBalance > totalAllocation) {
            Swal.fire({
                icon: 'warning',
                title: '확인',
                text: '초기 잔액이 총 할당액보다 큽니다. 계속하시겠습니까?',
                showCancelButton: true,
                confirmButtonText: '계속',
                cancelButtonText: '취소'
            }).then((result) => {
                if (result.isConfirmed) {
                    // 폼 제출
                    $(this).off('submit').submit();
                }
            });
            return false;
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
/**
 * 새 기금 코드 생성 함수
 * 
 * @return string 생성된 기금 코드
 */
function generateFundCode() {
    // 기본적으로 FUND-날짜-001 형식으로 생성
    $prefix = 'FUND-' . date('Ymd');
    $suffix = '001';
    
    return $prefix . '-' . $suffix;
}

// 연결 종료
$conn->close();
?>