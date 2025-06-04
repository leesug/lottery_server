<?php
/**
 * 재무 관리 - 거래 추가 페이지
 * 
 * 이 페이지는 새로운 재무 거래를 등록하는 기능을 제공합니다.
 */

// 설정 및 공통 함수
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';
require_once '../../includes/db.php';

// 데이터베이스 연결
$db = get_db_connection();

// 거래 유형 및 상태 옵션
$transactionTypes = ['income' => '수입', 'expense' => '지출', 'transfer' => '이체', 'adjustment' => '조정'];
$transactionStatuses = [
    'pending' => '처리 중', 
    'completed' => '완료됨', 
    'failed' => '실패', 
    'cancelled' => '취소됨', 
    'reconciled' => '대사완료'
];
$paymentMethods = [
    'cash' => '현금',
    'bank_transfer' => '계좌 이체',
    'check' => '수표',
    'credit_card' => '신용카드',
    'debit_card' => '직불카드',
    'mobile_payment' => '모바일 결제',
    'other' => '기타'
];

// 카테고리 목록 조회
$categorySql = "SELECT id, category_name, category_type FROM financial_categories WHERE is_active = 1 ORDER BY category_type, category_name";
$categoryStmt = $db->query($categorySql);
$categories = $categoryStmt ? $categoryStmt->fetchAll(PDO::FETCH_ASSOC) : [];

// 카테고리가 없는 경우 (실제 데이터베이스 연결이 없을 경우를 위한 Mock 데이터)
if (empty($categories)) {
    $categories = [
        ['id' => 1, 'category_name' => '판매 수입', 'category_type' => 'income'],
        ['id' => 2, 'category_name' => '기타 수입', 'category_type' => 'income'],
        ['id' => 3, 'category_name' => '운영 비용', 'category_type' => 'expense'],
        ['id' => 4, 'category_name' => '인건비', 'category_type' => 'expense'],
        ['id' => 5, 'category_name' => '장비 구매', 'category_type' => 'expense'],
        ['id' => 6, 'category_name' => '세금', 'category_type' => 'expense'],
        ['id' => 7, 'category_name' => '마케팅 비용', 'category_type' => 'expense'],
        ['id' => 8, 'category_name' => '기타 비용', 'category_type' => 'expense'],
        ['id' => 9, 'category_name' => '계좌 이체', 'category_type' => 'both'],
    ];
}

// 폼 제출 처리
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 폼 데이터 가져오기
    $transactionType = sanitizeInput($_POST['transaction_type'] ?? '');
    $amount = sanitizeInput($_POST['amount'] ?? '');
    $currency = sanitizeInput($_POST['currency'] ?? 'NPR');
    $transactionDate = sanitizeInput($_POST['transaction_date'] ?? '');
    $categoryId = intval($_POST['category_id'] ?? 0);
    $description = sanitizeInput($_POST['description'] ?? '');
    $paymentMethod = sanitizeInput($_POST['payment_method'] ?? '');
    $paymentDetails = sanitizeInput($_POST['payment_details'] ?? '');
    $referenceType = sanitizeInput($_POST['reference_type'] ?? '');
    $referenceId = sanitizeInput($_POST['reference_id'] ?? '');
    $notes = sanitizeInput($_POST['notes'] ?? '');
    $status = 'pending'; // 기본 상태는 '처리 중'
    
    // 거래 코드 생성 (TR + 날짜 + 4자리 시퀀스)
    $transactionCode = 'TR' . date('Ymd') . '0001'; // 실제로는 DB에서 다음 시퀀스 번호를 조회하여 설정
    
    // 데이터 유효성 검사
    $errors = [];
    
    if (empty($transactionType)) {
        $errors[] = "거래 유형은 필수 항목입니다.";
    }
    
    if (empty($amount) || !is_numeric($amount) || $amount <= 0) {
        $errors[] = "유효한 금액을 입력해주세요.";
    }
    
    if (empty($transactionDate)) {
        $errors[] = "거래일은 필수 항목입니다.";
    }
    
    if (empty($paymentMethod)) {
        $errors[] = "결제 방법은 필수 항목입니다.";
    }
    
    if (empty($errors)) {
        try {
            // 데이터베이스에 새 거래 추가
            $sql = "INSERT INTO financial_transactions 
                    (transaction_code, transaction_type, amount, currency, transaction_date,
                     description, category_id, reference_type, reference_id, payment_method,
                     payment_details, status, created_by, notes, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $stmt = $db->prepare($sql);
            $createdBy = 1; // 현재 로그인한 사용자 ID
            
            $stmt->bindParam(1, $transactionCode);
            $stmt->bindParam(2, $transactionType);
            $stmt->bindParam(3, $amount);
            $stmt->bindParam(4, $currency);
            $stmt->bindParam(5, $transactionDate);
            $stmt->bindParam(6, $description);
            $stmt->bindParam(7, $categoryId);
            $stmt->bindParam(8, $referenceType);
            $stmt->bindParam(9, $referenceId);
            $stmt->bindParam(10, $paymentMethod);
            $stmt->bindParam(11, $paymentDetails);
            $stmt->bindParam(12, $status);
            $stmt->bindParam(13, $createdBy);
            $stmt->bindParam(14, $notes);
            
            $result = $stmt->execute();
            
            if ($result) {
                // 가상의 성공 시나리오
                $message = '새 거래가 성공적으로 등록되었습니다.';
                $messageType = 'success';
                
                // 실제로는 마지막으로 삽입된 ID를 가져와서 상세 페이지로 리다이렉트
                $lastId = $db->lastInsertId();
                
                // Mock 환경에서는 ID가 없을 수 있으므로 가상의 ID 생성
                if (!$lastId) $lastId = rand(1, 1000);
                
                header("Location: transaction-view.php?id=" . $lastId . "&success=1");
                exit;
            } else {
                throw new Exception("데이터베이스 오류가 발생했습니다.");
            }
        } catch (Exception $e) {
            $message = '오류가 발생했습니다: ' . $e->getMessage();
            $messageType = 'danger';
        }
    } else {
        $message = '다음 오류를 수정해주세요:<ul><li>' . implode('</li><li>', $errors) . '</li></ul>';
        $messageType = 'danger';
    }
}

// 현재 페이지 정보
$pageTitle = "새 거래 등록";
$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir = dirname($_SERVER['PHP_SELF']);
$parts = explode('/', $currentDir);
$currentSection = end($parts);

// 템플릿 헤더 포함
include_once TEMPLATES_PATH . '/dashboard_header.php';
?>

<!-- Content Header (Page header) -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><?php echo $pageTitle; ?></h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?php echo SERVER_URL; ?>/dashboard/index.php">홈</a></li>
                    <li class="breadcrumb-item">재무 관리</li>
                    <li class="breadcrumb-item"><a href="transactions.php">거래 목록</a></li>
                    <li class="breadcrumb-item active">새 거래 등록</li>
                </ol>
            </div>
        </div>
    </div>
</div>
<!-- /.content-header -->

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <!-- 알림 메시지 표시 -->
        <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
            <?php echo $message; ?>
        </div>
        <?php endif; ?>
        
        <!-- 작업 버튼 -->
        <div class="mb-3">
            <a href="transactions.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> 목록으로 돌아가기
            </a>
        </div>
        
        <!-- 거래 추가 폼 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">거래 정보 입력</h3>
            </div>
            <div class="card-body">
                <form method="post" action="" class="needs-validation" novalidate>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="transaction_type">거래 유형 <span class="text-danger">*</span></label>
                                <select class="form-control" id="transaction_type" name="transaction_type" required>
                                    <option value="">선택하세요</option>
                                    <?php foreach ($transactionTypes as $key => $value): ?>
                                    <option value="<?php echo $key; ?>" <?php echo isset($_POST['transaction_type']) && $_POST['transaction_type'] == $key ? 'selected' : ''; ?>>
                                        <?php echo $value; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">거래 유형을 선택해주세요.</div>
                            </div>
                            
                            <div class="form-group">
                                <label for="amount">금액 <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" step="0.01" min="0.01" class="form-control" id="amount" name="amount" placeholder="금액" value="<?php echo htmlspecialchars($_POST['amount'] ?? ''); ?>" required>
                                    <div class="input-group-append">
                                        <select class="form-control" id="currency" name="currency">
                                            <option value="NPR" selected>NPR</option>
                                            <option value="USD">USD</option>
                                            <option value="EUR">EUR</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="invalid-feedback">유효한 금액을 입력해주세요.</div>
                            </div>
                            
                            <div class="form-group">
                                <label for="transaction_date">거래일 <span class="text-danger">*</span></label>
                                <input type="datetime-local" class="form-control" id="transaction_date" name="transaction_date" value="<?php echo htmlspecialchars($_POST['transaction_date'] ?? date('Y-m-d\TH:i')); ?>" required>
                                <div class="invalid-feedback">거래일을 입력해주세요.</div>
                            </div>
                            
                            <div class="form-group">
                                <label for="category_id">카테고리</label>
                                <select class="form-control" id="category_id" name="category_id">
                                    <option value="">선택하세요</option>
                                    <optgroup label="수입">
                                        <?php foreach ($categories as $category): ?>
                                        <?php if ($category['category_type'] == 'income' || $category['category_type'] == 'both'): ?>
                                        <option value="<?php echo $category['id']; ?>" 
                                                <?php echo isset($_POST['category_id']) && $_POST['category_id'] == $category['id'] ? 'selected' : ''; ?>
                                                data-type="<?php echo $category['category_type']; ?>">
                                            <?php echo htmlspecialchars($category['category_name']); ?>
                                        </option>
                                        <?php endif; ?>
                                        <?php endforeach; ?>
                                    </optgroup>
                                    <optgroup label="지출">
                                        <?php foreach ($categories as $category): ?>
                                        <?php if ($category['category_type'] == 'expense' || $category['category_type'] == 'both'): ?>
                                        <option value="<?php echo $category['id']; ?>" 
                                                <?php echo isset($_POST['category_id']) && $_POST['category_id'] == $category['id'] ? 'selected' : ''; ?>
                                                data-type="<?php echo $category['category_type']; ?>">
                                            <?php echo htmlspecialchars($category['category_name']); ?>
                                        </option>
                                        <?php endif; ?>
                                        <?php endforeach; ?>
                                    </optgroup>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="payment_method">결제 방법 <span class="text-danger">*</span></label>
                                <select class="form-control" id="payment_method" name="payment_method" required>
                                    <option value="">선택하세요</option>
                                    <?php foreach ($paymentMethods as $key => $value): ?>
                                    <option value="<?php echo $key; ?>" <?php echo isset($_POST['payment_method']) && $_POST['payment_method'] == $key ? 'selected' : ''; ?>>
                                        <?php echo $value; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">결제 방법을 선택해주세요.</div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="description">설명</label>
                                <textarea class="form-control" id="description" name="description" rows="3" placeholder="거래에 대한 설명"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="payment_details">결제 상세 정보</label>
                                <textarea class="form-control" id="payment_details" name="payment_details" rows="2" placeholder="결제 관련 상세 정보"><?php echo htmlspecialchars($_POST['payment_details'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="reference_type">참조 유형</label>
                                <select class="form-control" id="reference_type" name="reference_type">
                                    <option value="">없음</option>
                                    <option value="sale" <?php echo isset($_POST['reference_type']) && $_POST['reference_type'] == 'sale' ? 'selected' : ''; ?>>판매</option>
                                    <option value="prize" <?php echo isset($_POST['reference_type']) && $_POST['reference_type'] == 'prize' ? 'selected' : ''; ?>>당첨금</option>
                                    <option value="refund" <?php echo isset($_POST['reference_type']) && $_POST['reference_type'] == 'refund' ? 'selected' : ''; ?>>환불</option>
                                    <option value="commission" <?php echo isset($_POST['reference_type']) && $_POST['reference_type'] == 'commission' ? 'selected' : ''; ?>>수수료</option>
                                    <option value="expense" <?php echo isset($_POST['reference_type']) && $_POST['reference_type'] == 'expense' ? 'selected' : ''; ?>>비용</option>
                                    <option value="other" <?php echo isset($_POST['reference_type']) && $_POST['reference_type'] == 'other' ? 'selected' : ''; ?>>기타</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="reference_id">참조 ID</label>
                                <input type="text" class="form-control" id="reference_id" name="reference_id" placeholder="관련 참조 ID" value="<?php echo htmlspecialchars($_POST['reference_id'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="notes">비고</label>
                                <textarea class="form-control" id="notes" name="notes" rows="2" placeholder="추가 참고사항"><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> 거래 등록
                            </button>
                            <a href="transactions.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> 취소
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 폼 유효성 검사
    var form = document.querySelector('.needs-validation');
    form.addEventListener('submit', function(event) {
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        
        form.classList.add('was-validated');
    }, false);
    
    // 거래 유형에 따라 카테고리 필터링
    var transactionType = document.getElementById('transaction_type');
    var categorySelect = document.getElementById('category_id');
    var categoryOptions = Array.from(categorySelect.querySelectorAll('option[data-type]'));
    
    transactionType.addEventListener('change', function() {
        var selectedType = this.value;
        
        // 옵션 그룹 표시 여부 설정
        var incomeGroup = categorySelect.querySelector('optgroup[label="수입"]');
        var expenseGroup = categorySelect.querySelector('optgroup[label="지출"]');
        
        if (selectedType === 'income') {
            incomeGroup.style.display = '';
            expenseGroup.style.display = 'none';
        } else if (selectedType === 'expense') {
            incomeGroup.style.display = 'none';
            expenseGroup.style.display = '';
        } else {
            incomeGroup.style.display = '';
            expenseGroup.style.display = '';
        }
        
        // 첫 번째 옵션 선택으로 초기화
        categorySelect.selectedIndex = 0;
    });
    
    // 초기 로드 시 실행
    if (transactionType.value) {
        transactionType.dispatchEvent(new Event('change'));
    }
});
</script>

<?php
// 템플릿 푸터 포함
include_once TEMPLATES_PATH . '/dashboard_footer.php';
?>