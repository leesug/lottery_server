<?php
/**
 * 재무 관리 - 기금 거래 상세 페이지
 * 
 * 이 페이지는 기금 거래의 상세 정보를 표시하고, 거래 상태 관리 기능을 제공합니다.
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

// 거래 ID 확인
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    setAlert('거래 ID가 유효하지 않습니다.', 'error');
    redirectTo('funds.php');
}

$transactionId = intval($_GET['id']);

// 데이터베이스 연결
$conn = getDBConnection();

// 거래 정보 조회 (기금 정보도 함께 조인)
$sql = "SELECT ft.*, f.fund_name, f.fund_code, f.current_balance, 
        u1.username as created_by_username, u2.username as approved_by_username
        FROM fund_transactions ft
        JOIN funds f ON ft.fund_id = f.id
        LEFT JOIN users u1 ON ft.created_by = u1.id
        LEFT JOIN users u2 ON ft.approved_by = u2.id
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

// 거래 유형 한글명 매핑
$transactionTypeLabels = [
    'allocation' => '할당',
    'withdrawal' => '인출',
    'transfer' => '이체',
    'adjustment' => '조정'
];

// 거래 상태 한글명 및 색상 매핑
$statusLabels = [
    'pending' => ['name' => '대기 중', 'color' => 'warning'],
    'completed' => ['name' => '완료됨', 'color' => 'success'],
    'cancelled' => ['name' => '취소됨', 'color' => 'danger']
];

// 상태 업데이트 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // CSRF 토큰 검증
    verifyCsrfToken($_POST['csrf_token']);
    
    $action = $_POST['action'];
    $newStatus = '';
    $actionDesc = '';
    
    // 승인 권한 확인
    if (!hasPermission('finance_funds_approval')) {
        setAlert('이 작업을 수행할 권한이 없습니다.', 'error');
        redirectTo("fund-transaction-details.php?id={$transactionId}");
    }
    
    // 이미 완료되거나 취소된 거래는 처리하지 않음
    if ($transaction['status'] !== 'pending') {
        setAlert('이미 처리된 거래입니다.', 'error');
        redirectTo("fund-transaction-details.php?id={$transactionId}");
    }
    
    // 작업 종류에 따른 처리
    if ($action === 'approve') {
        $newStatus = 'completed';
        $actionDesc = '승인';
    } elseif ($action === 'cancel') {
        $newStatus = 'cancelled';
        $actionDesc = '취소';
    } else {
        setAlert('유효하지 않은 작업입니다.', 'error');
        redirectTo("fund-transaction-details.php?id={$transactionId}");
    }
    
    try {
        // 트랜잭션 시작
        $conn->begin_transaction();
        
        // 거래 상태 업데이트
        $updateSql = "UPDATE fund_transactions SET 
                     status = ?,
                     approved_by = ?,
                     updated_at = NOW()
                     WHERE id = ?";
                     
        $updateStmt = $conn->prepare($updateSql);
        if (!$updateStmt) {
            throw new Exception("데이터베이스 준비 오류: " . $conn->error);
        }
        
        $updateStmt->bind_param("sii", $newStatus, $_SESSION['user_id'], $transactionId);
        
        $updateResult = $updateStmt->execute();
        if (!$updateResult) {
            throw new Exception("거래 상태 업데이트 중 오류가 발생했습니다: " . $updateStmt->error);
        }
        
        // 승인인 경우 기금 잔액 업데이트
        if ($newStatus === 'completed') {
            $newBalance = $transaction['current_balance'];
            
            if ($transaction['transaction_type'] === 'allocation') {
                $newBalance += $transaction['amount'];
            } elseif ($transaction['transaction_type'] === 'withdrawal') {
                // 잔액 충분한지 확인
                if ($transaction['amount'] > $transaction['current_balance']) {
                    throw new Exception("인출 금액이 현재 잔액보다 큽니다. 거래를 승인할 수 없습니다.");
                }
                $newBalance -= $transaction['amount'];
            } elseif ($transaction['transaction_type'] === 'adjustment') {
                // 조정은 금액을 그대로 반영 (양수는 증가, 음수는 감소)
                $newBalance += $transaction['amount'];
            }
            // transfer는 다른 기금에 대한 처리가 필요하나 여기서는 단순화
            
            // 잔액 업데이트
            $balanceUpdateSql = "UPDATE funds SET 
                                current_balance = ?,
                                updated_at = NOW()
                                WHERE id = ?";
                                
            $balanceUpdateStmt = $conn->prepare($balanceUpdateSql);
            if (!$balanceUpdateStmt) {
                throw new Exception("데이터베이스 준비 오류: " . $conn->error);
            }
            
            $balanceUpdateStmt->bind_param("di", $newBalance, $transaction['fund_id']);
            
            $balanceUpdateResult = $balanceUpdateStmt->execute();
            if (!$balanceUpdateResult) {
                throw new Exception("기금 잔액 업데이트 중 오류가 발생했습니다: " . $balanceUpdateStmt->error);
            }
            
            $balanceUpdateStmt->close();
        }
        
        // 트랜잭션 커밋
        $conn->commit();
        
        // 성공 메시지 설정
        setAlert("거래가 성공적으로 {$actionDesc}되었습니다.", 'success');
        
        // 로그 기록
        $logMessage = "기금 거래 {$actionDesc}: ID {$transactionId}, 기금: {$transaction['fund_name']} ({$transaction['fund_code']}), 금액: {$transaction['amount']} NPR";
        logActivity('finance', "fund_transaction_{$action}", $logMessage);
        
        // 페이지 리로드 (새로운 상태 반영)
        redirectTo("fund-transaction-details.php?id={$transactionId}");
        
    } catch (Exception $e) {
        // 트랜잭션 롤백
        $conn->rollback();
        
        // 오류 로깅
        logError("기금 거래 {$actionDesc} 오류: " . $e->getMessage());
        
        // 오류 메시지 설정
        setAlert("기금 거래 {$actionDesc} 중 오류가 발생했습니다: " . $e->getMessage(), 'error');
        
        // 현재 페이지로 리디렉션
        redirectTo("fund-transaction-details.php?id={$transactionId}");
    }
}

// 페이지 제목 설정
$pageTitle = "기금 거래 상세 정보";
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
                        <li class="breadcrumb-item active">거래 상세 정보</li>
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
                                <i class="fas fa-exchange-alt mr-1"></i>
                                거래 상세 정보
                            </h3>
                            <div class="card-tools">
                                <a href="fund-details.php?id=<?php echo $transaction['fund_id']; ?>" class="btn btn-sm btn-default">
                                    <i class="fas fa-arrow-left mr-1"></i> 기금으로 돌아가기
                                </a>
                                <?php if (hasPermission('finance_funds_transactions_update') && $transaction['status'] === 'pending'): ?>
                                <a href="fund-transaction-edit.php?id=<?php echo $transactionId; ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-edit mr-1"></i> 편집
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h5>거래 상태</h5>
                                        <span class="badge badge-<?php echo $statusLabels[$transaction['status']]['color']; ?> p-2">
                                            <?php echo $statusLabels[$transaction['status']]['name']; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>기금 코드</label>
                                        <p class="form-control-static"><?php echo htmlspecialchars($transaction['fund_code']); ?></p>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>기금명</label>
                                        <p class="form-control-static"><?php echo htmlspecialchars($transaction['fund_name']); ?></p>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>거래 유형</label>
                                        <p class="form-control-static">
                                            <?php echo $transactionTypeLabels[$transaction['transaction_type']]; ?>
                                        </p>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>거래 금액</label>
                                        <p class="form-control-static">
                                            <?php echo number_format($transaction['amount'], 2); ?> NPR
                                        </p>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>거래 날짜</label>
                                        <p class="form-control-static">
                                            <?php echo date('Y-m-d H:i', strtotime($transaction['transaction_date'])); ?>
                                        </p>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <?php if (!empty($transaction['reference_type']) && !empty($transaction['reference_id'])): ?>
                                    <div class="form-group">
                                        <label>참조 정보</label>
                                        <p class="form-control-static">
                                            <?php echo htmlspecialchars($transaction['reference_type']); ?>: 
                                            <?php echo htmlspecialchars($transaction['reference_id']); ?>
                                        </p>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="form-group">
                                        <label>설명</label>
                                        <p class="form-control-static">
                                            <?php echo htmlspecialchars($transaction['description'] ?: '(없음)'); ?>
                                        </p>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>비고</label>
                                        <p class="form-control-static">
                                            <?php echo nl2br(htmlspecialchars($transaction['notes'] ?: '(없음)')); ?>
                                        </p>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>생성 정보</label>
                                        <p class="form-control-static">
                                            <?php echo htmlspecialchars($transaction['created_by_username'] ?: '시스템'); ?>, 
                                            <?php echo date('Y-m-d H:i', strtotime($transaction['created_at'])); ?>
                                        </p>
                                    </div>
                                    
                                    <?php if (!empty($transaction['approved_by'])): ?>
                                    <div class="form-group">
                                        <label>승인 정보</label>
                                        <p class="form-control-static">
                                            <?php echo htmlspecialchars($transaction['approved_by_username']); ?>, 
                                            <?php echo date('Y-m-d H:i', strtotime($transaction['updated_at'])); ?>
                                        </p>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <?php if ($transaction['status'] === 'pending' && hasPermission('finance_funds_approval')): ?>
                        <div class="card-footer">
                            <form id="transactionActionForm" method="post" class="d-inline">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                <input type="hidden" name="action" id="transaction_action" value="">
                                
                                <button type="button" class="btn btn-success" onclick="confirmAction('approve')">
                                    <i class="fas fa-check mr-1"></i> 승인
                                </button>
                                
                                <button type="button" class="btn btn-danger" onclick="confirmAction('cancel')">
                                    <i class="fas fa-times mr-1"></i> 취소
                                </button>
                            </form>
                        </div>
                        <?php endif; ?>
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
                            
                            <?php if ($transaction['status'] === 'pending'): ?>
                            <div class="form-group">
                                <label>거래 후 예상 잔액</label>
                                <?php
                                $projectedBalance = $transaction['current_balance'];
                                if ($transaction['transaction_type'] === 'allocation') {
                                    $projectedBalance += $transaction['amount'];
                                    $balanceClass = 'text-success';
                                } elseif ($transaction['transaction_type'] === 'withdrawal') {
                                    $projectedBalance -= $transaction['amount'];
                                    $balanceClass = 'text-danger';
                                } elseif ($transaction['transaction_type'] === 'adjustment') {
                                    $projectedBalance += $transaction['amount'];
                                    $balanceClass = $transaction['amount'] >= 0 ? 'text-success' : 'text-danger';
                                }
                                ?>
                                <h4 class="mt-2 <?php echo $balanceClass; ?>">
                                    <?php echo number_format($projectedBalance, 2); ?> NPR
                                </h4>
                            </div>
                            <?php endif; ?>
                            
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

<!-- 확인 모달 -->
<div class="modal fade" id="confirmModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmModalTitle">작업 확인</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="confirmModalBody">
                정말 이 작업을 수행하시겠습니까?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">취소</button>
                <button type="button" class="btn btn-primary" id="confirmActionBtn">확인</button>
            </div>
        </div>
    </div>
</div>

<script>
function confirmAction(action) {
    let actionText = '';
    let confirmClass = '';
    
    if (action === 'approve') {
        actionText = '승인';
        confirmClass = 'btn-success';
        
        // 추가 검증 (인출이면서 잔액이 부족한 경우)
        <?php if ($transaction['transaction_type'] === 'withdrawal' && $transaction['amount'] > $transaction['current_balance']): ?>
        alert('인출 금액이 현재 잔액보다 큽니다. 거래를 승인할 수 없습니다.');
        return;
        <?php endif; ?>
    } else if (action === 'cancel') {
        actionText = '취소';
        confirmClass = 'btn-danger';
    }
    
    // 모달 내용 설정
    document.getElementById('confirmModalTitle').textContent = `거래 ${actionText} 확인`;
    document.getElementById('confirmModalBody').textContent = `정말 이 거래를 ${actionText}하시겠습니까?`;
    
    // 확인 버튼 스타일 설정
    const confirmBtn = document.getElementById('confirmActionBtn');
    confirmBtn.className = 'btn ' + confirmClass;
    
    // 확인 버튼 클릭 이벤트
    confirmBtn.onclick = function() {
        document.getElementById('transaction_action').value = action;
        document.getElementById('transactionActionForm').submit();
    };
    
    // 모달 표시
    $('#confirmModal').modal('show');
}

// 페이지 로드 시 콘솔에 로그 기록
document.addEventListener('DOMContentLoaded', function() {
    console.log('기금 거래 상세 페이지 로드됨: 거래 ID <?php echo $transactionId; ?>');
});
</script>

<?php
// 푸터 포함
include '../../templates/footer.php';
?>