<?php
// 예치금 거래 처리
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// 로그인 체크
checkLogin();

// 페이지 제목
$pageTitle = "예치금 거래";
$currentSection = "store";
$currentPage = "deposit-transaction.php";

// 데이터베이스 연결
$conn = get_db_connection();

// 판매점 ID 확인
$store_id = isset($_GET['store_id']) ? intval($_GET['store_id']) : 0;

if ($store_id <= 0) {
    header('Location: deposit-dashboard.php');
    exit;
}

// 판매점 정보 조회
$storeQuery = "
    SELECT s.*, sd.*, r.region_name
    FROM stores s
    LEFT JOIN store_deposits sd ON s.id = sd.store_id
    LEFT JOIN regions r ON s.region_id = r.id
    WHERE s.id = ?
";
$stmt = $conn->prepare($storeQuery);
$stmt->execute([$store_id]);
$store = $stmt->fetch();

if (!$store) {
    header('Location: deposit-dashboard.php');
    exit;
}

// 예치금 정보가 없으면 초기화
if (!$store['equipment_deposit']) {
    $initQuery = "
        INSERT INTO store_deposits (store_id, store_grade) 
        VALUES (?, 'C')
    ";
    $conn->prepare($initQuery)->execute([$store_id]);
    
    // 정보 다시 조회
    $stmt->execute([$store_id]);
    $store = $stmt->fetch();
}

// 최근 거래 내역 조회
$transQuery = "
    SELECT dt.*, u.name as created_by_name
    FROM deposit_transactions dt
    LEFT JOIN users u ON dt.created_by = u.id
    WHERE dt.store_id = ?
    ORDER BY dt.created_at DESC
    LIMIT 10
";
$stmt = $conn->prepare($transQuery);
$stmt->execute([$store_id]);
$transactions = $stmt->fetchAll();

// POST 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $transaction_type = $_POST['transaction_type'] ?? '';
    $deposit_type = $_POST['deposit_type'] ?? '';
    $amount = floatval($_POST['amount'] ?? 0);
    $payment_method = $_POST['payment_method'] ?? '';
    $bank_name = $_POST['bank_name'] ?? '';
    $account_number = $_POST['account_number'] ?? '';
    $reference_no = $_POST['reference_no'] ?? '';
    $notes = $_POST['notes'] ?? '';
    
    $errors = [];
    
    // 유효성 검증
    if (empty($transaction_type)) {
        $errors[] = "거래 유형을 선택해주세요.";
    }
    
    if (empty($deposit_type)) {
        $errors[] = "예치금 유형을 선택해주세요.";
    }
    
    if ($amount <= 0) {
        $errors[] = "금액을 올바르게 입력해주세요.";
    }
    
    // 차감 시 잔액 확인
    if (in_array($transaction_type, ['decrease', 'refund'])) {
        $checkAmount = 0;
        if ($deposit_type == 'equipment') {
            $checkAmount = $store['equipment_deposit'];
        } elseif ($deposit_type == 'sales') {
            $checkAmount = $store['sales_deposit'];
        } else {
            $checkAmount = $store['total_deposit'];
        }
        
        if ($amount > $checkAmount) {
            $errors[] = "차감 금액이 현재 잔액보다 큽니다.";
        }
    }
    
    if (empty($errors)) {
        try {
            $conn->beginTransaction();
            
            // 현재 잔액 계산
            $balance_before = $store['total_deposit'];
            $new_equipment_deposit = $store['equipment_deposit'];
            $new_sales_deposit = $store['sales_deposit'];
            
            // 거래 유형에 따른 처리
            if (in_array($transaction_type, ['initial', 'deposit', 'increase'])) {
                // 증액
                if ($deposit_type == 'equipment') {
                    $new_equipment_deposit += $amount;
                } elseif ($deposit_type == 'sales') {
                    $new_sales_deposit += $amount;
                } else {
                    // both - 50:50으로 분배
                    $new_equipment_deposit += $amount / 2;
                    $new_sales_deposit += $amount / 2;
                }
            } else {
                // 감액
                if ($deposit_type == 'equipment') {
                    $new_equipment_deposit -= $amount;
                } elseif ($deposit_type == 'sales') {
                    $new_sales_deposit -= $amount;
                } else {
                    // both - 50:50으로 차감
                    $new_equipment_deposit -= $amount / 2;
                    $new_sales_deposit -= $amount / 2;
                }
            }
            
            $new_total_deposit = $new_equipment_deposit + $new_sales_deposit;
            
            // 판매한도 재계산
            $leverage_rate = $store['leverage_rate'] ?: 1.0;
            $new_sales_limit = $new_sales_deposit * 1.05 * $leverage_rate;
            
            // store_deposits 업데이트
            $updateQuery = "
                UPDATE store_deposits 
                SET equipment_deposit = ?,
                    sales_deposit = ?,
                    total_deposit = ?,
                    sales_limit = ?
                WHERE store_id = ?
            ";
            $stmt = $conn->prepare($updateQuery);
            $stmt->execute([
                $new_equipment_deposit,
                $new_sales_deposit,
                $new_total_deposit,
                $new_sales_limit,
                $store_id
            ]);
            
            // 거래 내역 저장
            $insertQuery = "
                INSERT INTO deposit_transactions (
                    store_id, transaction_type, deposit_type, amount,
                    balance_before, balance_after, reference_no,
                    payment_method, bank_name, account_number,
                    notes, status, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'completed', ?)
            ";
            $stmt = $conn->prepare($insertQuery);
            $stmt->execute([
                $store_id,
                $transaction_type,
                $deposit_type,
                $amount,
                $balance_before,
                $new_total_deposit,
                $reference_no,
                $payment_method,
                $bank_name,
                $account_number,
                $notes,
                $_SESSION['user_id']
            ]);
            
            // 한도 변경 이력 저장
            if ($new_sales_limit != $store['sales_limit']) {
                $historyQuery = "
                    INSERT INTO deposit_limit_history (
                        store_id, change_type, old_deposit, new_deposit,
                        old_limit, new_limit, reason, changed_by
                    ) VALUES (?, 'deposit_change', ?, ?, ?, ?, ?, ?)
                ";
                $stmt = $conn->prepare($historyQuery);
                $stmt->execute([
                    $store_id,
                    $balance_before,
                    $new_total_deposit,
                    $store['sales_limit'],
                    $new_sales_limit,
                    "예치금 거래: $transaction_type",
                    $_SESSION['user_id']
                ]);
            }
            
            $conn->commit();
            
            // 성공 메시지와 함께 리다이렉트
            $_SESSION['success_message'] = "예치금 거래가 성공적으로 처리되었습니다.";
            header("Location: deposit-transaction.php?store_id=$store_id");
            exit;
            
        } catch (Exception $e) {
            $conn->rollBack();
            $errors[] = "처리 중 오류가 발생했습니다: " . $e->getMessage();
        }
    }
}

include_once TEMPLATES_PATH . '/dashboard_header.php';
?>

<!-- 컨텐츠 시작 -->
<div class="container-fluid">
    <!-- 페이지 헤더 -->
    <div class="row mb-4">
        <div class="col-md-6">
            <h1 class="h3 mb-0"><?php echo $pageTitle; ?></h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="/dashboard">대시보드</a></li>
                    <li class="breadcrumb-item"><a href="/dashboard/store">판매점 관리</a></li>
                    <li class="breadcrumb-item"><a href="deposit-dashboard.php">예치금 관리</a></li>
                    <li class="breadcrumb-item active"><?php echo $pageTitle; ?></li>
                </ol>
            </nav>
        </div>
        <div class="col-md-6 text-end">
            <a href="deposit-dashboard.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> 목록으로
            </a>
        </div>
    </div>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php 
            echo $_SESSION['success_message']; 
            unset($_SESSION['success_message']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- 판매점 정보 -->
        <div class="col-md-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">판매점 정보</h6>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <th>판매점명</th>
                            <td><?php echo htmlspecialchars($store['store_name']); ?></td>
                        </tr>
                        <tr>
                            <th>판매점코드</th>
                            <td><?php echo $store['store_code']; ?></td>
                        </tr>
                        <tr>
                            <th>지역</th>
                            <td><?php echo htmlspecialchars($store['region_name']); ?></td>
                        </tr>
                        <tr>
                            <th>등급</th>
                            <td>
                                <span class="badge bg-<?php echo getGradeBadgeClass($store['store_grade']); ?>">
                                    <?php echo $store['store_grade']; ?>등급
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>상태</th>
                            <td>
                                <span class="badge bg-<?php echo getStatusBadgeClass($store['status']); ?>">
                                    <?php echo getStatusText($store['status']); ?>
                                </span>
                            </td>
                        </tr>
                    </table>
                    
                    <hr>
                    
                    <h6 class="font-weight-bold mb-3">예치금 현황</h6>
                    <table class="table table-sm">
                        <tr>
                            <th>기기보증금</th>
                            <td class="text-end">₩ <?php echo number_format($store['equipment_deposit']); ?></td>
                        </tr>
                        <tr>
                            <th>판매보증금</th>
                            <td class="text-end">₩ <?php echo number_format($store['sales_deposit']); ?></td>
                        </tr>
                        <tr class="table-primary">
                            <th>총 예치금</th>
                            <td class="text-end font-weight-bold">₩ <?php echo number_format($store['total_deposit']); ?></td>
                        </tr>
                        <tr>
                            <th>판매한도</th>
                            <td class="text-end">₩ <?php echo number_format($store['sales_limit']); ?></td>
                        </tr>
                        <tr>
                            <th>사용액</th>
                            <td class="text-end">₩ <?php echo number_format($store['used_limit']); ?></td>
                        </tr>
                        <tr>
                            <th>잔여한도</th>
                            <td class="text-end">₩ <?php echo number_format($store['remaining_limit']); ?></td>
                        </tr>
                        <tr>
                            <th>사용률</th>
                            <td class="text-end">
                                <div class="progress" style="height: 20px;">
                                    <div class="progress-bar <?php echo getUsageProgressClass($store['usage_percentage']); ?>" 
                                         style="width: <?php echo $store['usage_percentage']; ?>%">
                                        <?php echo number_format($store['usage_percentage'], 1); ?>%
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- 거래 입력 폼 -->
        <div class="col-md-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">예치금 거래 입력</h6>
                </div>
                <div class="card-body">
                    <form method="post" action="">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="transaction_type" class="form-label">거래 유형 <span class="text-danger">*</span></label>
                                <select name="transaction_type" id="transaction_type" class="form-select" required>
                                    <option value="">선택하세요</option>
                                    <option value="initial">초기입금</option>
                                    <option value="deposit">추가입금</option>
                                    <option value="increase">증액</option>
                                    <option value="decrease">차감</option>
                                    <option value="refund">환불</option>
                                    <option value="adjustment">조정</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="deposit_type" class="form-label">예치금 유형 <span class="text-danger">*</span></label>
                                <select name="deposit_type" id="deposit_type" class="form-select" required>
                                    <option value="">선택하세요</option>
                                    <option value="equipment">기기보증금</option>
                                    <option value="sales">판매보증금</option>
                                    <option value="both">모두</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="amount" class="form-label">금액 <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">₩</span>
                                    <input type="number" name="amount" id="amount" class="form-control" 
                                           min="1" step="1000" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="payment_method" class="form-label">결제방법</label>
                                <select name="payment_method" id="payment_method" class="form-select">
                                    <option value="">선택하세요</option>
                                    <option value="bank_transfer">계좌이체</option>
                                    <option value="cash">현금</option>
                                    <option value="card">카드</option>
                                    <option value="other">기타</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="bank_name" class="form-label">은행명</label>
                                <input type="text" name="bank_name" id="bank_name" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label for="account_number" class="form-label">계좌번호</label>
                                <input type="text" name="account_number" id="account_number" class="form-control">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="reference_no" class="form-label">참조번호</label>
                            <input type="text" name="reference_no" id="reference_no" class="form-control" 
                                   placeholder="입금확인번호, 전표번호 등">
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">비고</label>
                            <textarea name="notes" id="notes" class="form-control" rows="3"></textarea>
                        </div>

                        <div class="text-end">
                            <button type="button" class="btn btn-secondary" onclick="history.back()">취소</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> 거래 저장
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- 최근 거래 내역 -->
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">최근 거래 내역</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead>
                                <tr>
                                    <th>일시</th>
                                    <th>거래유형</th>
                                    <th>예치금유형</th>
                                    <th>금액</th>
                                    <th>잔액</th>
                                    <th>처리자</th>
                                    <th>상태</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($transactions)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center">거래 내역이 없습니다.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($transactions as $trans): ?>
                                        <tr>
                                            <td><?php echo date('Y-m-d H:i', strtotime($trans['created_at'])); ?></td>
                                            <td><?php echo getTransactionTypeText($trans['transaction_type']); ?></td>
                                            <td><?php echo getDepositTypeText($trans['deposit_type']); ?></td>
                                            <td class="text-end">
                                                <?php if (in_array($trans['transaction_type'], ['decrease', 'refund'])): ?>
                                                    <span class="text-danger">-₩ <?php echo number_format($trans['amount']); ?></span>
                                                <?php else: ?>
                                                    <span class="text-success">+₩ <?php echo number_format($trans['amount']); ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end">₩ <?php echo number_format($trans['balance_after']); ?></td>
                                            <td><?php echo htmlspecialchars($trans['created_by_name'] ?: '-'); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo getTransactionStatusBadgeClass($trans['status']); ?>">
                                                    <?php echo getTransactionStatusText($trans['status']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="text-end mt-3">
                        <a href="deposit-history.php?store_id=<?php echo $store_id; ?>" class="btn btn-sm btn-info">
                            전체 내역 보기
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// 헬퍼 함수들
function getGradeBadgeClass($grade) {
    switch($grade) {
        case 'S': return 'primary';
        case 'A': return 'success';
        case 'B': return 'info';
        case 'C': return 'warning';
        case 'D': return 'danger';
        default: return 'secondary';
    }
}

function getStatusBadgeClass($status) {
    switch($status) {
        case 'active': return 'success';
        case 'suspended': return 'warning';
        case 'blocked': return 'danger';
        case 'terminated': return 'secondary';
        default: return 'secondary';
    }
}

function getStatusText($status) {
    switch($status) {
        case 'active': return '정상';
        case 'suspended': return '일시정지';
        case 'blocked': return '차단';
        case 'terminated': return '해지';
        default: return $status;
    }
}

function getUsageProgressClass($percentage) {
    if ($percentage >= 98) return 'bg-danger';
    if ($percentage >= 90) return 'bg-warning';
    if ($percentage >= 75) return 'bg-info';
    return 'bg-success';
}

function getTransactionTypeText($type) {
    switch($type) {
        case 'initial': return '초기입금';
        case 'deposit': return '추가입금';
        case 'increase': return '증액';
        case 'decrease': return '차감';
        case 'refund': return '환불';
        case 'adjustment': return '조정';
        default: return $type;
    }
}

function getDepositTypeText($type) {
    switch($type) {
        case 'equipment': return '기기보증금';
        case 'sales': return '판매보증금';
        case 'both': return '모두';
        default: return $type;
    }
}

function getTransactionStatusBadgeClass($status) {
    switch($status) {
        case 'completed': return 'success';
        case 'pending': return 'warning';
        case 'cancelled': return 'danger';
        default: return 'secondary';
    }
}

function getTransactionStatusText($status) {
    switch($status) {
        case 'completed': return '완료';
        case 'pending': return '대기중';
        case 'cancelled': return '취소됨';
        default: return $status;
    }
}
?>

<?php include_once TEMPLATES_PATH . '/dashboard_footer.php'; ?>
