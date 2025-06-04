<?php
/**
 * 재무 관리 - 정산 생성 페이지
 * 
 * 이 페이지는 새로운 정산을 생성하는 기능을 제공합니다.
 */

// 세션 시작 및 필수 파일 포함
session_start();
require_once '../../includes/auth.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// 인증 확인
checkAuth();

// 필요한 권한 확인
$requiredPermissions = ['finance_settlements_add'];
checkPermissions($requiredPermissions);

// 데이터베이스 연결
$conn = getDBConnection();

// 폼 제출 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF 토큰 검증
    verifyCsrfToken($_POST['csrf_token']);
    
    // 폼 데이터 검증
    $settlementType = sanitizeInput($_POST['settlement_type']);
    $entityId = intval($_POST['entity_id']);
    $entityType = sanitizeInput($_POST['entity_type']);
    $startDate = sanitizeInput($_POST['start_date']);
    $endDate = sanitizeInput($_POST['end_date']);
    $totalAmount = floatval($_POST['total_amount']);
    $commissionAmount = floatval($_POST['commission_amount']);
    $taxAmount = floatval($_POST['tax_amount']);
    $netAmount = floatval($_POST['net_amount']);
    $paymentMethod = sanitizeInput($_POST['payment_method']);
    $notes = sanitizeInput($_POST['notes']);
    
    // 정산 항목 처리
    $itemsData = isset($_POST['items']) ? $_POST['items'] : [];
    
    // 유효성 검사
    $errors = [];
    
    if (empty($settlementType)) {
        $errors[] = "정산 유형을 선택해주세요.";
    }
    
    if ($entityId <= 0) {
        $errors[] = "유효한 정산 대상을 선택해주세요.";
    }
    
    if (empty($startDate)) {
        $errors[] = "시작일을 입력해주세요.";
    }
    
    if (empty($endDate)) {
        $errors[] = "종료일을 입력해주세요.";
    }
    
    if ($endDate < $startDate) {
        $errors[] = "종료일은 시작일보다 빠를 수 없습니다.";
    }
    
    if ($totalAmount <= 0) {
        $errors[] = "총 금액은 0보다 커야 합니다.";
    }
    
    if ($netAmount <= 0) {
        $errors[] = "순 지급액은 0보다 커야 합니다.";
    }
    
    if (empty($paymentMethod)) {
        $errors[] = "지불 방법을 선택해주세요.";
    }
    
    if (empty($itemsData)) {
        $errors[] = "최소한 하나 이상의 정산 항목이 필요합니다.";
    }
    
    // 오류가 없으면 정산 생성
    if (empty($errors)) {
        try {
            // 트랜잭션 시작
            $conn->begin_transaction();
            
            // 정산 코드 생성 (YYYY-MM-DD-SETTLE-순번)
            $today = date('Y-m-d');
            $codePrefix = date('Ymd', strtotime($today)) . '-STL-';
            
            $codeSql = "SELECT MAX(settlement_code) as max_code FROM settlements WHERE settlement_code LIKE ?";
            $codeStmt = $conn->prepare($codeSql);
            $searchPattern = $codePrefix . '%';
            $codeStmt->bind_param("s", $searchPattern);
            $codeStmt->execute();
            $codeResult = $codeStmt->get_result();
            $codeRow = $codeResult->fetch_assoc();
            
            $lastCode = $codeRow['max_code'];
            $sequenceNumber = 1;
            
            if ($lastCode) {
                $lastSequence = intval(substr($lastCode, -5));
                $sequenceNumber = $lastSequence + 1;
            }
            
            $settlementCode = $codePrefix . str_pad($sequenceNumber, 5, '0', STR_PAD_LEFT);
            
            // 현재 사용자 ID 가져오기
            $userId = $_SESSION['user_id'];
            
            // 새 정산 추가
            $sql = "INSERT INTO settlements (
                        settlement_code, settlement_type, entity_id, entity_type, 
                        start_date, end_date, total_amount, commission_amount, 
                        tax_amount, net_amount, status, payment_method, 
                        created_by, notes
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?)";
            
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("데이터베이스 준비 오류: " . $conn->error);
            }
            
            $stmt->bind_param(
                "ssissddddssi", 
                $settlementCode, $settlementType, $entityId, $entityType, 
                $startDate, $endDate, $totalAmount, $commissionAmount, 
                $taxAmount, $netAmount, $paymentMethod, $userId, $notes
            );
            
            $result = $stmt->execute();
            if (!$result) {
                throw new Exception("정산 추가 중 오류가 발생했습니다: " . $stmt->error);
            }
            
            $newSettlementId = $stmt->insert_id;
            
            // 정산 항목 추가
            foreach ($itemsData as $item) {
                $itemType = sanitizeInput($item['type']);
                $description = sanitizeInput($item['description']);
                $referenceId = !empty($item['reference_id']) ? sanitizeInput($item['reference_id']) : null;
                $amount = floatval($item['amount']);
                $quantity = intval($item['quantity']);
                $totalItemAmount = floatval($item['total_amount']);
                
                $itemSql = "INSERT INTO settlement_items (
                                settlement_id, item_type, reference_id, description,
                                amount, quantity, total_amount
                            ) VALUES (?, ?, ?, ?, ?, ?, ?)";
                
                $itemStmt = $conn->prepare($itemSql);
                if (!$itemStmt) {
                    throw new Exception("데이터베이스 준비 오류: " . $conn->error);
                }
                
                $itemStmt->bind_param(
                    "isssidi", 
                    $newSettlementId, $itemType, $referenceId, $description,
                    $amount, $quantity, $totalItemAmount
                );
                
                $itemResult = $itemStmt->execute();
                if (!$itemResult) {
                    throw new Exception("정산 항목 추가 중 오류가 발생했습니다: " . $itemStmt->error);
                }
                
                $itemStmt->close();
            }
            
            // 트랜잭션 커밋
            $conn->commit();
            
            // 성공 메시지 설정
            setAlert('정산이 성공적으로 생성되었습니다.', 'success');
            
            // 로그 기록
            logActivity('finance', 'settlement_create', "새 정산 생성: {$settlementCode}");
            
            // 상세 페이지로 리디렉션
            redirectTo("settlement-details.php?id={$newSettlementId}");
            
        } catch (Exception $e) {
            // 트랜잭션 롤백
            $conn->rollback();
            
            // 오류 로깅
            logError("정산 생성 오류: " . $e->getMessage());
            
            // 오류 메시지 설정
            setAlert('정산 생성 중 오류가 발생했습니다: ' . $e->getMessage(), 'error');
        }
    } else {
        // 폼 오류 메시지 설정
        setAlert('입력 양식에 오류가 있습니다: ' . implode(' ', $errors), 'error');
    }
}

// 정산 유형 옵션
$settlementTypes = [
    'store' => '판매점', 
    'vendor' => '공급업체', 
    'employee' => '직원', 
    'tax' => '세금', 
    'other' => '기타'
];

// 항목 유형 옵션
$itemTypes = [
    'sale' => '판매',
    'prize' => '당첨금',
    'commission' => '수수료',
    'deduction' => '공제',
    'tax' => '세금',
    'fee' => '수수료',
    'bonus' => '보너스',
    'other' => '기타'
];

// 지불 방법 옵션
$paymentMethods = [
    'cash' => '현금',
    'bank_transfer' => '계좌이체',
    'check' => '수표',
    'credit' => '신용',
    'adjustment' => '조정'
];

// 판매점 목록 조회
$storesSql = "SELECT id, store_code, store_name FROM stores WHERE status = 'active' ORDER BY store_name";
$storesStmt = $conn->prepare($storesSql);
$storesStmt->execute();
$storesResult = $storesStmt->get_result();

$stores = [];
while ($row = $storesResult->fetch_assoc()) {
    $stores[] = $row;
}

// 페이지 제목 설정
$pageTitle = "정산 생성";
$currentSection = "finance";
$currentPage = "settlement_create";

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
                        <li class="breadcrumb-item"><a href="settlements.php">정산 목록</a></li>
                        <li class="breadcrumb-item active">정산 생성</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">
            <form id="createSettlementForm" method="post" action="">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                <input type="hidden" name="entity_type" id="entity_type" value="store">
                
                <div class="row">
                    <div class="col-md-8">
                        <!-- 기본 정보 카드 -->
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-file-invoice-dollar mr-1"></i>
                                    정산 기본 정보
                                </h3>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="settlement_type">정산 유형<span class="text-danger">*</span></label>
                                            <select class="form-control" id="settlement_type" name="settlement_type" required>
                                                <option value="">선택하세요</option>
                                                <?php foreach ($settlementTypes as $key => $value): ?>
                                                    <option value="<?php echo $key; ?>" <?php echo (isset($_POST['settlement_type']) && $_POST['settlement_type'] == $key) ? 'selected' : ''; ?>>
                                                        <?php echo $value; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="entity_id">정산 대상<span class="text-danger">*</span></label>
                                            <select class="form-control select2" id="entity_id" name="entity_id" required>
                                                <option value="">선택하세요</option>
                                                <?php foreach ($stores as $store): ?>
                                                    <option value="<?php echo $store['id']; ?>" <?php echo (isset($_POST['entity_id']) && $_POST['entity_id'] == $store['id']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($store['store_code'] . ' - ' . $store['store_name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <small class="form-text text-muted">정산 유형에 따라 옵션이 변경됩니다.</small>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="start_date">시작일<span class="text-danger">*</span></label>
                                            <input type="date" class="form-control" id="start_date" name="start_date" 
                                                value="<?php echo isset($_POST['start_date']) ? htmlspecialchars($_POST['start_date']) : date('Y-m-01'); ?>" required>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="end_date">종료일<span class="text-danger">*</span></label>
                                            <input type="date" class="form-control" id="end_date" name="end_date" 
                                                value="<?php echo isset($_POST['end_date']) ? htmlspecialchars($_POST['end_date']) : date('Y-m-t'); ?>" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="payment_method">지불 방법<span class="text-danger">*</span></label>
                                            <select class="form-control" id="payment_method" name="payment_method" required>
                                                <option value="">선택하세요</option>
                                                <?php foreach ($paymentMethods as $key => $value): ?>
                                                    <option value="<?php echo $key; ?>" <?php echo (isset($_POST['payment_method']) && $_POST['payment_method'] == $key) ? 'selected' : ''; ?>>
                                                        <?php echo $value; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="notes">메모</label>
                                            <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo isset($_POST['notes']) ? htmlspecialchars($_POST['notes']) : ''; ?></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- 정산 항목 카드 -->
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-list mr-1"></i>
                                    정산 항목
                                </h3>
                                <div class="card-tools">
                                    <button type="button" class="btn btn-primary btn-sm" id="add_item_btn">
                                        <i class="fas fa-plus"></i> 항목 추가
                                    </button>
                                    <button type="button" class="btn btn-info btn-sm" id="search_items_btn">
                                        <i class="fas fa-search"></i> 항목 검색
                                    </button>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-striped" id="items_table">
                                        <thead>
                                            <tr>
                                                <th style="width: 50px">#</th>
                                                <th>항목 유형</th>
                                                <th>설명</th>
                                                <th>참조 ID</th>
                                                <th style="width: 100px">수량</th>
                                                <th style="width: 150px">단가</th>
                                                <th style="width: 150px">합계</th>
                                                <th style="width: 80px">작업</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr id="no_items_row">
                                                <td colspan="8" class="text-center">항목이 없습니다. 위 버튼을 클릭하여 항목을 추가하세요.</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <!-- 정산 금액 요약 카드 -->
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-calculator mr-1"></i>
                                    정산 금액 요약
                                </h3>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="total_amount">총 금액</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">NPR</span>
                                        </div>
                                        <input type="number" class="form-control text-right" id="total_amount" name="total_amount" step="0.01" value="0.00" readonly>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="commission_amount">수수료 금액</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">NPR</span>
                                        </div>
                                        <input type="number" class="form-control text-right" id="commission_amount" name="commission_amount" step="0.01" min="0" value="0.00">
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="tax_amount">세금 금액</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">NPR</span>
                                        </div>
                                        <input type="number" class="form-control text-right" id="tax_amount" name="tax_amount" step="0.01" min="0" value="0.00">
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="net_amount">순 지급액</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">NPR</span>
                                        </div>
                                        <input type="number" class="form-control text-right font-weight-bold" id="net_amount" name="net_amount" step="0.01" value="0.00" readonly>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-primary btn-block mt-4">
                                    <i class="fas fa-save"></i> 정산 생성
                                </button>
                                <a href="settlements.php" class="btn btn-secondary btn-block">
                                    <i class="fas fa-times"></i> 취소
                                </a>
                            </div>
                        </div>
                        
                        <!-- 도움말 카드 -->
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    도움말
                                </h3>
                            </div>
                            <div class="card-body">
                                <p>정산 생성 시 다음 사항에 유의하세요:</p>
                                <ul>
                                    <li>정산 기간은 일반적으로 월 단위로 설정합니다.</li>
                                    <li>항목 추가 버튼을 클릭하여 정산 항목을 추가하세요.</li>
                                    <li>항목 검색 버튼으로 판매, 당첨금 등의 정보를 검색하여 추가할 수 있습니다.</li>
                                    <li>수수료 및 세금 금액을 입력하면 자동으로 순 지급액이 계산됩니다.</li>
                                    <li>모든 필수 항목을 입력해야 정산을 생성할 수 있습니다.</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 항목 추가 모달 -->
<div class="modal fade" id="addItemModal" tabindex="-1" role="dialog" aria-labelledby="addItemModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addItemModalLabel">정산 항목 추가</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="addItemForm">
                    <div class="form-group">
                        <label for="item_type">항목 유형<span class="text-danger">*</span></label>
                        <select class="form-control" id="item_type" required>
                            <option value="">선택하세요</option>
                            <?php foreach ($itemTypes as $key => $value): ?>
                                <option value="<?php echo $key; ?>"><?php echo $value; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="item_description">설명<span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="item_description" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="item_reference_id">참조 ID</label>
                        <input type="text" class="form-control" id="item_reference_id">
                        <small class="form-text text-muted">판매, 당첨금 등의 참조 식별자</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="item_quantity">수량<span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="item_quantity" min="1" step="1" value="1" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="item_amount">단가 (NPR)<span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="item_amount" min="0.01" step="0.01" value="0.00" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="item_total_amount">합계 (NPR)</label>
                        <input type="number" class="form-control" id="item_total_amount" step="0.01" value="0.00" readonly>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">취소</button>
                <button type="button" class="btn btn-primary" id="save_item_btn">추가</button>
            </div>
        </div>
    </div>
</div>

<!-- 항목 검색 모달 -->
<div class="modal fade" id="searchItemsModal" tabindex="-1" role="dialog" aria-labelledby="searchItemsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="searchItemsModalLabel">정산 항목 검색</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-row mb-3">
                    <div class="col-md-4">
                        <select class="form-control" id="search_type">
                            <option value="sale">판매 항목</option>
                            <option value="prize">당첨금 항목</option>
                            <option value="commission">수수료 항목</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <input type="text" class="form-control" id="search_query" placeholder="검색어 입력">
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-primary btn-block" id="search_items_execute_btn">검색</button>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="search_results_table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>설명</th>
                                <th>날짜</th>
                                <th>금액</th>
                                <th>선택</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="5" class="text-center">검색 결과가 여기에 표시됩니다.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">닫기</button>
            </div>
        </div>
    </div>
</div>

<?php include '../../templates/footer.php'; ?>

<script>
$(document).ready(function() {
    // Select2 초기화
    $('.select2').select2({
        theme: 'bootstrap4'
    });
    
    // 변수 초기화
    let itemCount = 0;
    let items = [];
    
    // 금액 계산 함수
    function calculateAmounts() {
        // 총 금액 계산
        let totalAmount = 0;
        items.forEach(function(item) {
            totalAmount += parseFloat(item.total_amount);
        });
        
        // 수수료 및 세금 금액 가져오기
        let commissionAmount = parseFloat($('#commission_amount').val()) || 0;
        let taxAmount = parseFloat($('#tax_amount').val()) || 0;
        
        // 순 지급액 계산
        let netAmount = totalAmount - commissionAmount - taxAmount;
        
        // 금액 업데이트
        $('#total_amount').val(totalAmount.toFixed(2));
        $('#net_amount').val(netAmount.toFixed(2));
    }
    
    // 항목 유형 변경 시 대상 옵션 변경
    $('#settlement_type').change(function() {
        var settlementType = $(this).val();
        $('#entity_type').val(settlementType);
        
        // 대상 옵션 변경 로직 (실제로는 AJAX로 구현)
        var $entitySelect = $('#entity_id');
        $entitySelect.empty().append('<option value="">선택하세요</option>');
        
        if (settlementType === 'store') {
            <?php foreach ($stores as $store): ?>
                $entitySelect.append('<option value="<?php echo $store['id']; ?>"><?php echo htmlspecialchars($store['store_code'] . ' - ' . $store['store_name']); ?></option>');
            <?php endforeach; ?>
        } else if (settlementType === 'vendor') {
            $entitySelect.append('<option value="1">VEN-001 - 예시 공급업체</option>');
        } else if (settlementType === 'employee') {
            $entitySelect.append('<option value="1">EMP-001 - 홍길동</option>');
        } else if (settlementType === 'tax') {
            $entitySelect.append('<option value="1">TAX-001 - 부가가치세</option>');
        } else if (settlementType === 'other') {
            $entitySelect.append('<option value="1">OTHER-001 - 기타 대상</option>');
        }
        
        // Select2 새로고침
        $entitySelect.trigger('change');
    });
    
    // 단가 또는 수량 변경 시 합계 계산
    $('#item_amount, #item_quantity').on('input', function() {
        var amount = parseFloat($('#item_amount').val()) || 0;
        var quantity = parseInt($('#item_quantity').val()) || 0;
        var totalItemAmount = amount * quantity;
        $('#item_total_amount').val(totalItemAmount.toFixed(2));
    });
    
    // 수수료 또는 세금 금액 변경 시 순 지급액 계산
    $('#commission_amount, #tax_amount').on('input', function() {
        calculateAmounts();
    });
    
    // 항목 추가 버튼 클릭 이벤트
    $('#add_item_btn').click(function() {
        // 모달 초기화
        $('#item_type').val('');
        $('#item_description').val('');
        $('#item_reference_id').val('');
        $('#item_quantity').val('1');
        $('#item_amount').val('0.00');
        $('#item_total_amount').val('0.00');
        
        // 모달 표시
        $('#addItemModal').modal('show');
    });
    
    // 항목 저장 버튼 클릭 이벤트
    $('#save_item_btn').click(function() {
        // 입력값 검증
        var itemType = $('#item_type').val();
        var description = $('#item_description').val();
        var referenceId = $('#item_reference_id').val();
        var quantity = parseInt($('#item_quantity').val()) || 0;
        var amount = parseFloat($('#item_amount').val()) || 0;
        var totalItemAmount = parseFloat($('#item_total_amount').val()) || 0;
        
        if (!itemType || !description || !quantity || !amount) {
            Swal.fire({
                icon: 'error',
                title: '입력 오류',
                text: '모든 필수 항목을 입력해주세요.'
            });
            return;
        }
        
        if (quantity <= 0) {
            Swal.fire({
                icon: 'error',
                title: '입력 오류',
                text: '수량은 1 이상이어야 합니다.'
            });
            return;
        }
        
        if (amount <= 0) {
            Swal.fire({
                icon: 'error',
                title: '입력 오류',
                text: '단가는 0보다 커야 합니다.'
            });
            return;
        }
        
        // 항목 ID 생성
        itemCount++;
        var itemId = 'item_' + itemCount;
        
        // 항목 객체 생성
        var item = {
            id: itemId,
            type: itemType,
            type_name: $('#item_type option:selected').text(),
            description: description,
            reference_id: referenceId,
            quantity: quantity,
            amount: amount,
            total_amount: totalItemAmount
        };
        
        // 항목 배열에 추가
        items.push(item);
        
        // 테이블에 행 추가
        addItemRow(item);
        
        // 항목이 없습니다 행 숨기기
        $('#no_items_row').hide();
        
        // 금액 계산
        calculateAmounts();
        
        // 모달 닫기
        $('#addItemModal').modal('hide');
    });
    
    // 항목 검색 버튼 클릭 이벤트
    $('#search_items_btn').click(function() {
        // 모달 초기화
        $('#search_type').val('sale');
        $('#search_query').val('');
        $('#search_results_table tbody').html('<tr><td colspan="5" class="text-center">검색 결과가 여기에 표시됩니다.</td></tr>');
        
        // 모달 표시
        $('#searchItemsModal').modal('show');
    });
    
    // 항목 검색 실행 버튼 클릭 이벤트
    $('#search_items_execute_btn').click(function() {
        var searchType = $('#search_type').val();
        var searchQuery = $('#search_query').val();
        
        // 로딩 표시
        $('#search_results_table tbody').html('<tr><td colspan="5" class="text-center"><i class="fas fa-spinner fa-spin"></i> 검색 중...</td></tr>');
        
        // AJAX로 항목 검색
        $.ajax({
            url: '../../api/finance/search-settlement-items.php',
            type: 'GET',
            data: {
                type: searchType,
                query: searchQuery,
                entity_id: $('#entity_id').val(),
                start_date: $('#start_date').val(),
                end_date: $('#end_date').val()
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // 검색 결과 표시
                    displaySearchResults(response.data, searchType);
                } else {
                    $('#search_results_table tbody').html('<tr><td colspan="5" class="text-center text-danger">' + response.message + '</td></tr>');
                }
            },
            error: function() {
                $('#search_results_table tbody').html('<tr><td colspan="5" class="text-center text-danger">검색 중 오류가 발생했습니다.</td></tr>');
            }
        });
    });
    
    // 검색 결과 표시 함수
    function displaySearchResults(data, type) {
        var $tbody = $('#search_results_table tbody');
        $tbody.empty();
        
        if (data.length === 0) {
            $tbody.html('<tr><td colspan="5" class="text-center">검색 결과가 없습니다.</td></tr>');
            return;
        }
        
        // 각 결과에 대한 행 추가
        $.each(data, function(i, item) {
            var $row = $('<tr>');
            
            $row.append($('<td>').text(item.id));
            $row.append($('<td>').text(item.description));
            $row.append($('<td>').text(item.date));
            $row.append($('<td>').text(formatCurrency(item.amount)));
            
            var $addBtn = $('<button>')
                .addClass('btn btn-sm btn-primary add-search-item')
                .attr('data-id', item.id)
                .attr('data-type', type)
                .attr('data-description', item.description)
                .attr('data-amount', item.amount)
                .html('<i class="fas fa-plus"></i> 추가');
            
            $row.append($('<td>').append($addBtn));
            
            $tbody.append($row);
        });
        
        // 추가 버튼 이벤트 등록
        $('.add-search-item').click(function() {
            var $btn = $(this);
            var id = $btn.data('id');
            var type = $btn.data('type');
            var description = $btn.data('description');
            var amount = parseFloat($btn.data('amount'));
            
            // 항목 ID 생성
            itemCount++;
            var itemId = 'item_' + itemCount;
            
            // 항목 객체 생성
            var item = {
                id: itemId,
                type: type,
                type_name: getItemTypeName(type),
                description: description,
                reference_id: id,
                quantity: 1,
                amount: amount,
                total_amount: amount
            };
            
            // 항목 배열에 추가
            items.push(item);
            
            // 테이블에 행 추가
            addItemRow(item);
            
            // 항목이 없습니다 행 숨기기
            $('#no_items_row').hide();
            
            // 금액 계산
            calculateAmounts();
            
            // 성공 메시지
            Swal.fire({
                icon: 'success',
                title: '항목 추가',
                text: '항목이 성공적으로 추가되었습니다.',
                showConfirmButton: false,
                timer: 1000
            });
        });
    }
    
    // 항목 유형 이름 조회 함수
    function getItemTypeName(type) {
        var types = {
            'sale': '판매',
            'prize': '당첨금',
            'commission': '수수료',
            'deduction': '공제',
            'tax': '세금',
            'fee': '수수료',
            'bonus': '보너스',
            'other': '기타'
        };
        
        return types[type] || type;
    }
    
    // 금액 형식화 함수
    function formatCurrency(amount) {
        return new Intl.NumberFormat('ko-KR', {
            style: 'decimal',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(amount) + ' NPR';
    }
    
    // 항목 행 추가 함수
    function addItemRow(item) {
        var $row = $('<tr>').attr('id', 'row_' + item.id);
        
        $row.append($('<td>').text(items.length));
        $row.append($('<td>').text(item.type_name));
        $row.append($('<td>').text(item.description));
        $row.append($('<td>').text(item.reference_id || '-'));
        $row.append($('<td>').text(item.quantity).addClass('text-right'));
        $row.append($('<td>').text(formatCurrency(item.amount)).addClass('text-right'));
        $row.append($('<td>').text(formatCurrency(item.total_amount)).addClass('text-right'));
        
        var $actions = $('<td>').addClass('text-center');
        
        var $deleteBtn = $('<button>')
            .addClass('btn btn-sm btn-danger delete-item')
            .attr('data-id', item.id)
            .html('<i class="fas fa-trash"></i>')
            .attr('title', '삭제');
        
        $actions.append($deleteBtn);
        $row.append($actions);
        
        $('#items_table tbody').append($row);
        
        // 삭제 버튼 이벤트 등록
        $deleteBtn.click(function() {
            var itemId = $(this).data('id');
            
            // 항목 배열에서 제거
            items = items.filter(function(item) {
                return item.id !== itemId;
            });
            
            // 행 제거
            $('#row_' + itemId).remove();
            
            // 항목이 없으면 '항목이 없습니다' 행 표시
            if (items.length === 0) {
                $('#no_items_row').show();
            } else {
                // 항목 번호 재조정
                $('#items_table tbody tr').not('#no_items_row').each(function(index) {
                    $(this).find('td:first').text(index + 1);
                });
            }
            
            // 금액 계산
            calculateAmounts();
        });
        
        // 히든 필드 추가
        $('#createSettlementForm').append(
            '<input type="hidden" name="items[' + item.id + '][type]" value="' + item.type + '">' +
            '<input type="hidden" name="items[' + item.id + '][description]" value="' + item.description + '">' +
            '<input type="hidden" name="items[' + item.id + '][reference_id]" value="' + item.reference_id + '">' +
            '<input type="hidden" name="items[' + item.id + '][quantity]" value="' + item.quantity + '">' +
            '<input type="hidden" name="items[' + item.id + '][amount]" value="' + item.amount + '">' +
            '<input type="hidden" name="items[' + item.id + '][total_amount]" value="' + item.total_amount + '">'
        );
    }
    
    // 폼 제출 전 검증
    $('#createSettlementForm').submit(function(e) {
        // 필수 입력값 확인
        var settlementType = $('#settlement_type').val();
        var entityId = $('#entity_id').val();
        var startDate = $('#start_date').val();
        var endDate = $('#end_date').val();
        var paymentMethod = $('#payment_method').val();
        
        if (!settlementType || !entityId || !startDate || !endDate || !paymentMethod) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: '입력 오류',
                text: '모든 필수 항목을 입력해주세요.'
            });
            return false;
        }
        
        // 항목 확인
        if (items.length === 0) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: '입력 오류',
                text: '최소한 하나 이상의 정산 항목이 필요합니다.'
            });
            return false;
        }
        
        // 날짜 범위 확인
        if (endDate < startDate) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: '입력 오류',
                text: '종료일은 시작일보다 빠를 수 없습니다.'
            });
            return false;
        }
        
        // 순 지급액 확인
        var netAmount = parseFloat($('#net_amount').val());
        if (netAmount <= 0) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: '금액 확인',
                text: '순 지급액이 0 이하입니다. 계속 진행하시겠습니까?',
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
        
        return true;
    });
});
</script>

<?php
// 연결 종료
$storesStmt->close();
$conn->close();
?>