<?php
/**
 * 재무 관리 - 기금 거래 편집 페이지
 * 
 * 이 페이지는 대기 중인 기금 거래의 정보를 수정하는 기능을 제공합니다.
 */

// 세션 시작 및 필수 파일 포함
session_start();
require_once '../../includes/auth.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// 인증 확인
checkAuth();

// 필요한 권한 확인
$requiredPermissions = ['finance_funds_transactions_update'];
checkPermissions($requiredPermissions);

// 거래 ID 확인
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    setAlert('거래 ID가 유효하지 않습니다.', 'error');
    redirectTo('funds.php');
}

$transactionId = intval($_GET['id']);

// 데이터베이스 연결
$conn = getDBConnection();

// 거래 정보 조회 (기금 정보도 함께 조인)
$sql = "SELECT ft.*, f.fund_name, f.fund_code, f.current_balance
        FROM fund_transactions ft
        JOIN funds f ON ft.fund_id = f.id
        WHERE ft.id = ?";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    logError("Database prepare error: " . $conn->error);
    die("데이터베이스 오류가 발생했습니다.");
}

$stmt->bind_param("i", $transactionId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    setAlert('존재하지 않는 거래입니다.', 'error');
    redirectTo('funds.php');
}

$transaction = $result->fetch_assoc();

// 이미 완료되거나 취소된 거래는 편집 불가
if ($transaction['status'] !== 'pending') {
    setAlert('완료되거나 취소된 거래는 편집할 수 없습니다.', 'error');
    redirectTo("fund-transaction-details.php?id={$transactionId}");
}

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
    if ($transactionType === 'withdrawal' && $amount > $transaction['current_balance']) {
        $errors[] = "인출 금액이 현재 잔액(" . number_format($transaction['current_balance'], 2) . " NPR)보다 큽니다.";
    }
    
    // 오류가 없으면 거래 업데이트
    if (empty($errors)) {
        try {
            // 거래 업데이트
            $sql = "UPDATE fund_transactions SET
                    transaction_type = ?,
                    amount = ?,
                    transaction_date = ?,
                    description = ?,
                    reference_type = ?,
                    reference_id = ?,
                    notes = ?,
                    updated_at = NOW()
                    WHERE id = ?";
            
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("데이터베이스 준비 오류: " . $conn->error);
            }
            
            $stmt->bind_param(
                "sdsssssi", 
                $transactionType, 
                $amount, 
                $transactionDate, 
                $description, 
                $referenceType, 
                $referenceId, 
                $notes,
                $transactionId
            );
            
            $result = $stmt->execute();
            if (!$result) {
                throw new Exception("거래 업데이트 중 오류가 발생했습니다: " . $stmt->error);
            }
            
            // 성공 메시지 설정
            setAlert('거래가 성공적으로 업데이트되었습니다.', 'success');
            
            // 로그 기록
            logActivity('finance', 'fund_transaction_update', "기금 거래 업데이트: ID {$transactionId}, 기금: {$transaction['fund_name']} ({$transaction['fund_code']}), 금액: {$amount} NPR");
            
            // 거래 상세 페이지로 리디렉션
            redirectTo("fund-transaction-details.php?id={$transactionId}");
            
        } catch (Exception $e) {
            // 오류 로깅
            logError("기금 거래 업데이트 오류: " . $e->getMessage());
            
            // 오류 메시지 설정
            setAlert('기금 거래 업데이트 중 오류가 발생했습니다: ' . $e->getMessage(), 'error');
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
$pageTitle = "기금 거래 편집";
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
                        <li class="breadcrumb-item"><a href="fund-details.php?id=<?php echo $transaction['fund_id']; ?>"><?php echo $transaction['fund_name']; ?></a></li>
                        <li class="breadcrumb-item"><a href="fund-transaction-details.php?id=<?php echo $transactionId; ?>">거래 상세</a></li>
                        <li class="breadcrumb-item active">거래 편집</li>
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
                                거래 정보 편집
                            </h3>
                            <div class="card-tools">
                                <a href="fund-transaction-details.php?id=<?php echo $transactionId; ?>" class="btn btn-sm btn-default">
                                    <i class="fas fa-arrow-left mr-1"></i> 거래 상세로 돌아가기
                                </a>
                            </div>
                        </div>
                        <form id="editTransactionForm" method="post" action="">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                            
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="fund_code">기금 코드</label>
                                            <input type="text" class="form-control" id="fund_code" value="<?php echo htmlspecialchars($transaction['fund_code']); ?>" readonly>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="fund_name">기금명</label>
                                            <input type="text" class="form-control" id="fund_name" value="<?php echo htmlspecialchars($transaction['fund_name']); ?>" readonly>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="transaction_type">거래 유형 <span class="text-danger">*</span></label>
                                            <select class="form-control" id="transaction_type" name="transaction_type" required>
                                                <?php foreach ($transactionTypes as $value => $label): ?>
                                                <option value="<?php echo $value; ?>" <?php if ($transaction['transaction_type'] === $value) echo 'selected'; ?>>
                                                    <?php echo $label; ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="amount">금액 (NPR) <span class="text-danger">*</span></label>
                                            <input type="number" step="0.01" min="0.01" class="form-control" id="amount" name="amount" value="<?php echo $transaction['amount']; ?>" required>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="transaction_date">거래 날짜 <span class="text-danger">*</span></label>
                                            <input type="datetime-local" class="form-control" id="transaction_date" name="transaction_date" value="<?php echo date('Y-m-d\TH:i', strtotime($transaction['transaction_date'])); ?>" required>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="reference_type">참조 유형</label>
                                            <select class="form-control" id="reference_type" name="reference_type">
                                                <option value="">선택 안함</option>
                                                <?php foreach ($referenceTypes as $value => $label): ?>
                                                <option value="<?php echo $value; ?>" <?php if ($transaction['reference_type'] === $value) echo 'selected'; ?>>
                                                    <?php echo $label; ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="reference_id">참조 ID</label>
                                            <input type="text" class="form-control" id="reference_id" name="reference_id" value="<?php echo htmlspecialchars($transaction['reference_id'] ?? ''); ?>">
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="description">설명</label>
                                            <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($transaction['description'] ?? ''); ?></textarea>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="notes">비고</label>
                                            <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo htmlspecialchars($transaction['notes'] ?? ''); ?></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save mr-1"></i> 저장
                                </button>
                                <a href="fund-transaction-details.php?id=<?php echo $transactionId; ?>" class="btn btn-default">
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
                                기금 정보
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label>현재 잔액</label>
                                <h4 class="mt-2"><?php echo number_format($transaction['current_balance'], 2); ?> NPR</h4>
                            </div>
                            
                            <div class="alert alert-info">
                                <h5><i class="icon fas fa-info"></i> 편집 안내</h5>
                                <ul class="mb-0">
                                    <li>오직 대기 중인 거래만 편집할 수 있습니다.</li>
                                    <li>인출의 경우, 현재 기금 잔액을 초과할 수 없습니다.</li>
                                </ul>
                            </div>
                            
                            <a href="fund-details.php?id=<?php echo $transaction['fund_id']; ?>" class="btn btn-info btn-block mt-3">
                                <i class="fas fa-money-check-alt mr-1"></i> 기금 상세 정보 보기
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// 페이지 로드 시 콘솔에 로그 기록
document.addEventListener('DOMContentLoaded', function() {
    console.log('기금 거래 편집 페이지 로드됨: 거래 ID <?php echo $transactionId; ?>');
    
    // 거래 유형 변경 이벤트
    document.getElementById('transaction_type').addEventListener('change', function() {
        const transactionType = this.value;
        const amount = parseFloat(document.getElementById('amount').value);
        const balance = <?php echo $transaction['current_balance']; ?>;
        
        if (transactionType === 'withdrawal' && amount > balance) {
            alert('경고: 인출 금액이 현재 잔액(' + balance.toLocaleString() + ' NPR)보다 큽니다.');
        }
    });
    
    // 금액 변경 이벤트
    document.getElementById('amount').addEventListener('change', function() {
        const transactionType = document.getElementById('transaction_type').value;
        const amount = parseFloat(this.value);
        const balance = <?php echo $transaction['current_balance']; ?>;
        
        if (transactionType === 'withdrawal' && amount > balance) {
            alert('경고: 인출 금액이 현재 잔액(' + balance.toLocaleString() + ' NPR)보다 큽니다.');
        }
    });
    
    // 폼 제출 전 유효성 검사
    document.getElementById('editTransactionForm').addEventListener('submit', function(e) {
        const transactionType = document.getElementById('transaction_type').value;
        const amount = parseFloat(document.getElementById('amount').value);
        const balance = <?php echo $transaction['current_balance']; ?>;
        
        if (transactionType === 'withdrawal' && amount > balance) {
            e.preventDefault();
            alert('인출 금액이 현재 잔액(' + balance.toLocaleString() + ' NPR)보다 큽니다. 거래를 저장할 수 없습니다.');
            return false;
        }
    });
});
</script>

<?php
// 푸터 포함
include '../../templates/footer.php';
?>