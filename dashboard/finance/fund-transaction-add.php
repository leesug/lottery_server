<?php
/**
 * 재무 관리 - 기금 거래 추가 페이지
 * 
 * 이 페이지는 기금의 새로운 거래를 추가하는 기능을 제공합니다.
 */

// 세션 시작 및 필수 파일 포함
session_start();
require_once '../../includes/auth.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// 인증 확인
checkAuth();

// 필요한 권한 확인
$requiredPermissions = ['finance_funds_transactions'];
checkPermissions($requiredPermissions);

// 기금 ID 확인
if (!isset($_GET['fund_id']) || !is_numeric($_GET['fund_id'])) {
    setAlert('기금 ID가 유효하지 않습니다.', 'error');
    redirectTo('funds.php');
}

$fundId = intval($_GET['fund_id']);

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
    $transactionType = sanitizeInput($_POST['transaction_type']);
    $amount = floatval($_POST['amount']);
    $transactionDate = sanitizeInput($_POST['transaction_date']);
    $description = sanitizeInput($_POST['description']);
    $referenceType = !empty($_POST['reference_type']) ? sanitizeInput($_POST['reference_type']) : null;
    $referenceId = !empty($_POST['reference_id']) ? sanitizeInput($_POST['reference_id']) : null;
    $notes = sanitizeInput($_POST['notes']);
    $autoApprove = isset($_POST['auto_approve']) ? true : false;
    
    // 유효성 검사
    $errors = [];
    
    if (empty($transactionType)) {
        $errors[] = "거래 유형을 선택해주세요.";
    }
    
    if ($amount <= 0) {
        $errors[] = "금액은 0보다 커야 합니다.";
    }
    
    if (empty($transactionDate)) {
        $errors[] = "거래 날짜를 입력해주세요.";
    }
    
    // 인출 시 잔액 확인
    if ($transactionType === 'withdrawal' && $amount > $fund['current_balance']) {
        $errors[] = "인출 금액이 현재 잔액(" . number_format($fund['current_balance'], 2) . " NPR)보다 큽니다.";
    }
    
    // 오류가 없으면 거래 추가
    if (empty($errors)) {
        try {
            // 트랜잭션 시작
            $conn->begin_transaction();
            
            // 자동 승인 여부에 따른 상태 설정
            $status = $autoApprove ? 'completed' : 'pending';
            $approvedBy = $autoApprove ? $_SESSION['user_id'] : null;
            
            // 새 거래 추가
            $sql = "INSERT INTO fund_transactions (
                        fund_id, 
                        transaction_type, 
                        amount, 
                        transaction_date, 
                        description, 
                        reference_type, 
                        reference_id, 
                        status, 
                        approved_by, 
                        notes
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("데이터베이스 준비 오류: " . $conn->error);
            }
            
            $stmt->bind_param(
                "isdsssssis", 
                $fundId, 
                $transactionType, 
                $amount, 
                $transactionDate, 
                $description, 
                $referenceType, 
                $referenceId, 
                $status, 
                $approvedBy, 
                $notes
            );
            
            $result = $stmt->execute();
            if (!$result) {
                throw new Exception("거래 추가 중 오류가 발생했습니다: " . $stmt->error);
            }
            
            $newTransactionId = $stmt->insert_id;
            
            // 자동 승인 시 기금 잔액 업데이트
            if ($autoApprove) {
                $newBalance = $fund['current_balance'];
                
                if ($transactionType === 'allocation') {
                    $newBalance += $amount;
                } elseif ($transactionType === 'withdrawal') {
                    $newBalance -= $amount;
                }
                
                // 잔액 업데이트
                $updateSql = "UPDATE funds SET 
                              current_balance = ?, 
                              updated_at = NOW()
                              WHERE id = ?";
                
                $updateStmt = $conn->prepare($updateSql);
                if (!$updateStmt) {
                    throw new Exception("데이터베이스 준비 오류: " . $conn->error);
                }
                
                $updateStmt->bind_param("di", $newBalance, $fundId);
                
                $updateResult = $updateStmt->execute();
                if (!$updateResult) {
                    throw new Exception("기금 잔액 업데이트 중 오류가 발생했습니다: " . $updateStmt->error);
                }
                
                $updateStmt->close();
            }
            
            // 트랜잭션 커밋
            $conn->commit();
            
            // 성공 메시지 설정
            $successMessage = $autoApprove ? 
                '거래가 성공적으로 추가되고 승인되었습니다.' :
                '거래가 성공적으로 추가되었습니다. 승인 대기 중입니다.';
            
            setAlert($successMessage, 'success');
            
            // 로그 기록
            logActivity('finance', 'fund_transaction_add', "기금 거래 추가: {$fund['fund_name']} ({$fund['fund_code']}), 금액: {$amount} NPR, 유형: {$transactionType}");
            
            // 기금 상세 페이지로 리디렉션
            redirectTo("fund-details.php?id={$fundId}");
            
        } catch (Exception $e) {
            // 트랜잭션 롤백
            $conn->rollback();
            
            // 오류 로깅
            logError("기금 거래 추가 오류: " . $e->getMessage());
            
            // 오류 메시지 설정
            setAlert('기금 거래 추가 중 오류가 발생했습니다: ' . $e->getMessage(), 'error');
        }
    } else {
        // 폼 오류 메시지 설정
        setAlert('입력 양식에 오류가 있습니다: ' . implode(' ', $errors), 'error');
    }
}

// 거래 유형 옵션
$transactionTypes = [
    'allocation' => '할당', 
    'withdrawal' => '인출', 
    'transfer' => '이체', 
    'adjustment' => '조정'
];

// 참조 유형 옵션
$referenceTypes = [
    'lottery' => '복권',
    'sale' => '판매',
    'prize' => '당첨금',
    'expense' => '지출',
    'manual' => '수동',
    'other' => '기타'
];

// 페이지 제목 설정
$pageTitle = "기금 거래 추가: " . $fund['fund_name'];
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
                        <li class="breadcrumb-item"><a href="fund-details.php?id=<?php echo $fundId; ?>"><?php echo $fund['fund_name']; ?></a></li>
                        <li class="breadcrumb-item active">거래 추가</li>
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
                                <i class="fas fa-plus-circle mr-1"></i>
                                새 거래 정보 입력
                            </h3>
                        </div>
                        <form id="addTransactionForm" method="post" action="">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                            
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="fund_code">기금 코드</label>
                                            <input type="text" class="form-control" id="fund_code" value="<?php echo htmlspecialchars($fund['fund_code']); ?>" readonly>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="fund_name">기금명</label>
                                            <input type="text" class="form-control" id="fund_name" value="<?php echo htmlspecialchars($fund['fund_name']); ?>" readonly>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="current_balance">현재 잔액</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">NPR</span>
                                                </div>
                                                <input type="text" class="form-control text-right" id="current_balance" value="<?php echo number_format($fund['current_balance'], 2); ?>" readonly>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="transaction_type">거래 유형<span class="text-danger">*</span></label>
                                            <select class="form-control" id="transaction_type" name="transaction_type" required>
                                                <option value="">선택하세요</option>
                                                <?php foreach ($transactionTypes as $key => $value): ?>
                                                    <option value="<?php echo $key; ?>" <?php echo (isset($_POST['transaction_type']) && $_POST['transaction_type'] == $key) ? 'selected' : ''; ?>>
                                                        <?php echo $value; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="amount">금액<span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">NPR</span>
                                                </div>
                                                <input type="number" class="form-control text-right" id="amount" name="amount" min="0.01" step="0.01" value="<?php echo isset($_POST['amount']) ? htmlspecialchars($_POST['amount']) : '0.00'; ?>" required>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="transaction_date">거래 날짜<span class="text-danger">*</span></label>
                                            <input type="datetime-local" class="form-control" id="transaction_date" name="transaction_date" value="<?php echo isset($_POST['transaction_date']) ? htmlspecialchars($_POST['transaction_date']) : date('Y-m-d\TH:i'); ?>" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="description">설명<span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="description" name="description" value="<?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?>" required>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="reference_type">참조 유형</label>
                                            <select class="form-control" id="reference_type" name="reference_type">
                                                <option value="">선택하세요</option>
                                                <?php foreach ($referenceTypes as $key => $value): ?>
                                                    <option value="<?php echo $key; ?>" <?php echo (isset($_POST['reference_type']) && $_POST['reference_type'] == $key) ? 'selected' : ''; ?>>
                                                        <?php echo $value; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="reference_id">참조 ID</label>
                                            <input type="text" class="form-control" id="reference_id" name="reference_id" value="<?php echo isset($_POST['reference_id']) ? htmlspecialchars($_POST['reference_id']) : ''; ?>">
                                            <small class="form-text text-muted">참조하는 항목의 ID를 입력하세요.</small>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="notes">메모</label>
                                    <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo isset($_POST['notes']) ? htmlspecialchars($_POST['notes']) : ''; ?></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="auto_approve" name="auto_approve" <?php echo (isset($_POST['auto_approve']) && $_POST['auto_approve']) ? 'checked' : ''; ?>>
                                        <label class="custom-control-label" for="auto_approve">즉시 승인</label>
                                    </div>
                                    <small class="form-text text-muted">체크하면 거래가 즉시 승인되고 기금 잔액이 업데이트됩니다.</small>
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
                
                <div class="col-md-4">
                    <!-- 도움말 카드 -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-info-circle mr-1"></i>
                                도움말
                            </h3>
                        </div>
                        <div class="card-body">
                            <p>거래 추가 시 다음 사항에 유의하세요:</p>
                            <ul>
                                <li><strong>할당(allocation)</strong>: 기금에 금액을 추가합니다. 잔액이 증가합니다.</li>
                                <li><strong>인출(withdrawal)</strong>: 기금에서 금액을 인출합니다. 잔액이 감소합니다.</li>
                                <li><strong>이체(transfer)</strong>: 기금 간 금액을 이동합니다. 이체 대상 기금의 거래를 별도로 생성해야 합니다.</li>
                                <li><strong>조정(adjustment)</strong>: 잔액을 조정합니다. 할당 또는 인출과 같은 효과를 가집니다.</li>
                            </ul>
                            <p><strong>참조 유형과 ID</strong>는 이 거래가 다른 항목(예: 복권 판매, 당첨금 지급 등)과 관련이 있을 경우 해당 정보를 기록합니다.</p>
                            <p><strong>즉시 승인</strong> 옵션을 선택하면 거래가 자동으로 승인되고 기금 잔액이 즉시 업데이트됩니다. 선택하지 않으면 승인 권한이 있는 사용자의 승인을 기다립니다.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../templates/footer.php'; ?>

<script>
$(document).ready(function() {
    // 거래 유형 변경 시 금액 처리 방식 표시
    $('#transaction_type').change(function() {
        var transactionType = $(this).val();
        var amountHelp = '';
        
        if (transactionType === 'allocation') {
            amountHelp = '할당 금액입니다. 기금 잔액이 증가합니다.';
        } else if (transactionType === 'withdrawal') {
            amountHelp = '인출 금액입니다. 기금 잔액이 감소합니다.';
        } else if (transactionType === 'transfer') {
            amountHelp = '이체 금액입니다. 이체 대상 기금의 거래를 별도로 생성해야 합니다.';
        } else if (transactionType === 'adjustment') {
            amountHelp = '조정 금액입니다. 양수이면 잔액이 증가하고, 음수이면 감소합니다.';
        }
        
        // 금액 입력 필드 도움말 업데이트
        $('#amount').siblings('.text-muted').remove();
        if (amountHelp) {
            $('#amount').parent().after('<small class="text-muted">' + amountHelp + '</small>');
        }
    });
    
    // 폼 제출 전 검증
    $('#addTransactionForm').submit(function(e) {
        var transactionType = $('#transaction_type').val();
        var amount = parseFloat($('#amount').val()) || 0;
        var currentBalance = parseFloat('<?php echo $fund['current_balance']; ?>');
        var transactionDate = $('#transaction_date').val();
        var description = $('#description').val().trim();
        
        var isValid = true;
        
        // 필수 입력값 확인
        if (!transactionType || amount <= 0 || !transactionDate || !description) {
            Swal.fire({
                icon: 'error',
                title: '입력 오류',
                text: '모든 필수 항목을 입력해주세요.'
            });
            isValid = false;
        }
        
        // 인출 시 잔액 확인
        if (transactionType === 'withdrawal' && amount > currentBalance) {
            Swal.fire({
                icon: 'error',
                title: '잔액 부족',
                text: '인출 금액이 현재 잔액(' + currentBalance.toLocaleString() + ' NPR)보다 큽니다.'
            });
            isValid = false;
        }
        
        // 참조 정보 확인
        var referenceType = $('#reference_type').val();
        var referenceId = $('#reference_id').val().trim();
        
        if ((referenceType && !referenceId) || (!referenceType && referenceId)) {
            Swal.fire({
                icon: 'warning',
                title: '참조 정보 확인',
                text: '참조 유형과 참조 ID를 모두 입력하거나 모두 비워두세요.'
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