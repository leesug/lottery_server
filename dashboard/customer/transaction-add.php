<?php
/**
 * 거래 내역 추가 페이지
 * 
 * 이 페이지는 고객의 새 거래 내역을 등록하는 기능을 제공합니다.
 */

// 필요한 파일 포함
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

// 페이지 접근 권한 확인
checkPageAccess('customer_management');

// 고객 ID 유효성 검사
$customerId = isset($_GET['customer_id']) ? (int)$_GET['customer_id'] : 0;

if ($customerId <= 0) {
    // 유효하지 않은 ID인 경우 고객 목록 페이지로 리다이렉트
    header('Location: customer-list.php');
    exit;
}

// 데이터베이스 연결
$db = getDbConnection();

// 고객 정보 조회
$sql = "SELECT id, customer_code, first_name, last_name, email, phone FROM customers WHERE id = ?";
$stmt = $db->prepare($sql);
$stmt->bind_param('i', $customerId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // 고객 정보가 없는 경우 고객 목록 페이지로 리다이렉트
    $stmt->close();
    header('Location: customer-list.php');
    exit;
}

$customer = $result->fetch_assoc();
$stmt->close();

// 초기 변수 설정
$success = false;
$errors = [];
$formData = [
    'transaction_type' => 'purchase',
    'amount' => '',
    'transaction_date' => date('Y-m-d H:i:s'),
    'reference_number' => generateReferenceNumber(),
    'status' => 'pending',
    'details' => ''
];

// POST 요청 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF 토큰 검증
    validateCsrfToken($_POST['csrf_token']);
    
    // 폼 데이터 유효성 검사
    $formData = [
        'transaction_type' => sanitizeInput($_POST['transaction_type']),
        'amount' => sanitizeInput($_POST['amount']),
        'transaction_date' => sanitizeInput($_POST['transaction_date']),
        'reference_number' => sanitizeInput($_POST['reference_number']),
        'status' => sanitizeInput($_POST['status']),
        'details' => sanitizeInput($_POST['details'])
    ];
    
    // 필수 필드 검사
    if (empty($formData['transaction_type'])) {
        $errors['transaction_type'] = '거래 유형을 선택해주세요.';
    }
    
    if (empty($formData['amount'])) {
        $errors['amount'] = '금액을 입력해주세요.';
    } else if (!is_numeric($formData['amount']) || $formData['amount'] <= 0) {
        $errors['amount'] = '유효한 금액을 입력해주세요.';
    }
    
    if (empty($formData['transaction_date'])) {
        $errors['transaction_date'] = '거래 날짜를 입력해주세요.';
    }
    
    if (empty($formData['reference_number'])) {
        $errors['reference_number'] = '참조 번호를 입력해주세요.';
    }
    
    // 에러가 없으면 거래 등록 진행
    if (empty($errors)) {
        $db = getDbConnection();
        
        // 거래 내역 등록
        $sql = "INSERT INTO customer_transactions (
                    customer_id, transaction_type, amount, transaction_date, 
                    reference_number, status, details
                ) VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $db->prepare($sql);
        $stmt->bind_param(
            'isdsss',
            $customerId,
            $formData['transaction_type'],
            $formData['amount'],
            $formData['transaction_date'],
            $formData['reference_number'],
            $formData['status'],
            $formData['details']
        );
        
        $result = $stmt->execute();
        
        if ($result) {
            $transactionId = $db->insert_id;
            
            // 성공 메시지 설정
            $success = true;
            
            // 작업 로그 기록
            logAction('transaction_create', '새 거래 등록: ' . $transactionId . ', 고객: ' . $customerId);
            
            // 폼 데이터 초기화
            $formData = [
                'transaction_type' => 'purchase',
                'amount' => '',
                'transaction_date' => date('Y-m-d H:i:s'),
                'reference_number' => generateReferenceNumber(),
                'status' => 'pending',
                'details' => ''
            ];
        } else {
            $errors['general'] = '거래 등록 중 오류가 발생했습니다. 다시 시도해주세요.';
            logError('transaction_create_fail', '거래 등록 실패: ' . $db->error);
        }
        
        $stmt->close();
    }
}

// 참조 번호 생성 함수
function generateReferenceNumber() {
    // 현재 날짜 + 랜덤 숫자로 참조 번호 생성
    $prefix = 'TX';
    $date = date('Ymd');
    $random = mt_rand(1000, 9999);
    
    return $prefix . $date . $random;
}

// 페이지 제목 및 기타 메타 정보
$pageTitle = "새 거래 등록: " . $customer['first_name'] . ' ' . $customer['last_name'];
$pageDescription = "고객 코드: " . $customer['customer_code'] . "의 새 거래를 등록합니다.";
$activeMenu = "customer";
$activeSubMenu = "customer-list";

// 헤더 포함
include '../../templates/header.php';
?>

<div class="content-wrapper">
    <!-- 콘텐츠 헤더 -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0"><?php echo $pageTitle; ?></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="../../dashboard/">대시보드</a></li>
                        <li class="breadcrumb-item">고객 관리</li>
                        <li class="breadcrumb-item"><a href="customer-list.php">고객 목록</a></li>
                        <li class="breadcrumb-item"><a href="customer-details.php?id=<?php echo $customerId; ?>">고객 세부 정보</a></li>
                        <li class="breadcrumb-item"><a href="customer-transactions.php?customer_id=<?php echo $customerId; ?>">거래 내역</a></li>
                        <li class="breadcrumb-item active">새 거래 등록</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- 메인 콘텐츠 -->
    <section class="content">
        <div class="container-fluid">
            <!-- 버튼 행 -->
            <div class="row mb-3">
                <div class="col-md-12">
                    <a href="customer-transactions.php?customer_id=<?php echo $customerId; ?>" class="btn btn-default">
                        <i class="fas fa-arrow-left"></i> 거래 내역 목록으로 돌아가기
                    </a>
                    <a href="customer-details.php?id=<?php echo $customerId; ?>" class="btn btn-info">
                        <i class="fas fa-user"></i> 고객 세부 정보
                    </a>
                </div>
            </div>

            <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                <h5><i class="icon fas fa-check"></i> 성공!</h5>
                거래가 성공적으로 등록되었습니다.
            </div>
            <?php endif; ?>

            <?php if (isset($errors['general'])): ?>
            <div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                <h5><i class="icon fas fa-ban"></i> 오류!</h5>
                <?php echo $errors['general']; ?>
            </div>
            <?php endif; ?>

            <!-- 고객 정보 요약 -->
            <div class="row">
                <div class="col-md-12">
                    <div class="info-box">
                        <span class="info-box-icon bg-info"><i class="fas fa-user"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">고객 정보</span>
                            <span class="info-box-number"><?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></span>
                            <span class="info-box-text">고객 코드: <?php echo htmlspecialchars($customer['customer_code']); ?></span>
                            <span class="info-box-text">이메일: <?php echo htmlspecialchars($customer['email']); ?></span>
                            <span class="info-box-text">전화번호: <?php echo htmlspecialchars($customer['phone']); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 거래 등록 폼 -->
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">거래 정보 입력</h3>
                </div>
                <form method="post" id="transactionForm">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="transaction_type">거래 유형 <span class="text-danger">*</span></label>
                                    <select class="form-control <?php echo isset($errors['transaction_type']) ? 'is-invalid' : ''; ?>" 
                                        id="transaction_type" name="transaction_type" required>
                                        <option value="purchase" <?php echo ($formData['transaction_type'] == 'purchase') ? 'selected' : ''; ?>>구매</option>
                                        <option value="prize_claim" <?php echo ($formData['transaction_type'] == 'prize_claim') ? 'selected' : ''; ?>>당첨금 지급</option>
                                        <option value="refund" <?php echo ($formData['transaction_type'] == 'refund') ? 'selected' : ''; ?>>환불</option>
                                        <option value="deposit" <?php echo ($formData['transaction_type'] == 'deposit') ? 'selected' : ''; ?>>입금</option>
                                        <option value="withdrawal" <?php echo ($formData['transaction_type'] == 'withdrawal') ? 'selected' : ''; ?>>출금</option>
                                        <option value="other" <?php echo ($formData['transaction_type'] == 'other') ? 'selected' : ''; ?>>기타</option>
                                    </select>
                                    <?php if (isset($errors['transaction_type'])): ?>
                                        <span class="error invalid-feedback"><?php echo $errors['transaction_type']; ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="amount">금액 <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" class="form-control <?php echo isset($errors['amount']) ? 'is-invalid' : ''; ?>" 
                                        id="amount" name="amount" placeholder="금액 입력" 
                                        value="<?php echo htmlspecialchars($formData['amount']); ?>" required>
                                    <?php if (isset($errors['amount'])): ?>
                                        <span class="error invalid-feedback"><?php echo $errors['amount']; ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="transaction_date">거래 날짜 <span class="text-danger">*</span></label>
                                    <input type="datetime-local" class="form-control <?php echo isset($errors['transaction_date']) ? 'is-invalid' : ''; ?>" 
                                        id="transaction_date" name="transaction_date" 
                                        value="<?php echo str_replace(' ', 'T', $formData['transaction_date']); ?>" required>
                                    <?php if (isset($errors['transaction_date'])): ?>
                                        <span class="error invalid-feedback"><?php echo $errors['transaction_date']; ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="reference_number">참조 번호 <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control <?php echo isset($errors['reference_number']) ? 'is-invalid' : ''; ?>" 
                                        id="reference_number" name="reference_number" placeholder="참조 번호 입력" 
                                        value="<?php echo htmlspecialchars($formData['reference_number']); ?>" required>
                                    <?php if (isset($errors['reference_number'])): ?>
                                        <span class="error invalid-feedback"><?php echo $errors['reference_number']; ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="status">거래 상태</label>
                            <select class="form-control" id="status" name="status">
                                <option value="pending" <?php echo ($formData['status'] == 'pending') ? 'selected' : ''; ?>>대기중</option>
                                <option value="completed" <?php echo ($formData['status'] == 'completed') ? 'selected' : ''; ?>>완료</option>
                                <option value="failed" <?php echo ($formData['status'] == 'failed') ? 'selected' : ''; ?>>실패</option>
                                <option value="cancelled" <?php echo ($formData['status'] == 'cancelled') ? 'selected' : ''; ?>>취소</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="details">거래 세부 내용</label>
                            <textarea class="form-control" id="details" name="details" rows="4" 
                                      placeholder="거래 세부 내용 입력"><?php echo htmlspecialchars($formData['details']); ?></textarea>
                        </div>
                    </div>
                    
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> 거래 등록
                        </button>
                        <a href="customer-transactions.php?customer_id=<?php echo $customerId; ?>" class="btn btn-default">
                            <i class="fas fa-times"></i> 취소
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </section>
</div>

<script>
// 브라우저 콘솔에 디버깅 정보 출력
console.log('거래 등록 페이지 로드됨');
console.log('고객 ID:', <?php echo json_encode($customerId); ?>);

// 폼 유효성 검사
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('transactionForm');
    
    form.addEventListener('submit', function(e) {
        console.log('폼 제출 시도');
        let isValid = true;
        
        // 거래 유형 유효성 검사
        const transactionType = document.getElementById('transaction_type').value;
        if (!transactionType) {
            isValid = false;
            console.log('거래 유형이 선택되지 않음');
        }
        
        // 금액 유효성 검사
        const amount = document.getElementById('amount').value;
        if (!amount || isNaN(amount) || parseFloat(amount) <= 0) {
            isValid = false;
            console.log('유효하지 않은 금액');
        }
        
        // 거래 날짜 유효성 검사
        const transactionDate = document.getElementById('transaction_date').value;
        if (!transactionDate) {
            isValid = false;
            console.log('거래 날짜가 입력되지 않음');
        }
        
        // 참조 번호 유효성 검사
        const referenceNumber = document.getElementById('reference_number').value.trim();
        if (!referenceNumber) {
            isValid = false;
            console.log('참조 번호가 입력되지 않음');
        }
        
        if (!isValid) {
            e.preventDefault();
            console.log('폼 유효성 검사 실패');
        } else {
            console.log('폼 유효성 검사 통과');
        }
    });
    
    // 거래 유형 변경 이벤트 처리
    document.getElementById('transaction_type').addEventListener('change', function() {
        console.log('거래 유형 변경:', this.value);
        
        // 거래 유형에 따라 금액 입력란 음수 제한 및 스타일 변경
        const amountInput = document.getElementById('amount');
        
        if (this.value === 'purchase' || this.value === 'withdrawal') {
            // 구매, 출금은 양수만 허용
            amountInput.min = 0;
            amountInput.style.color = 'red';
        } else if (this.value === 'prize_claim' || this.value === 'refund' || this.value === 'deposit') {
            // 당첨금, 환불, 입금은 양수만 허용
            amountInput.min = 0;
            amountInput.style.color = 'green';
        } else {
            // 기타는 제한 없음
            amountInput.removeAttribute('min');
            amountInput.style.color = '';
        }
    });
    
    // 거래 유형 초기값에 따라 금액 입력란 스타일 설정
    const initialType = document.getElementById('transaction_type').value;
    const amountInput = document.getElementById('amount');
    
    if (initialType === 'purchase' || initialType === 'withdrawal') {
        amountInput.style.color = 'red';
    } else if (initialType === 'prize_claim' || initialType === 'refund' || initialType === 'deposit') {
        amountInput.style.color = 'green';
    }
});
</script>

<?php
// 푸터 포함
include '../../templates/footer.php';
?>
