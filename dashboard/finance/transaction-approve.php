<?php
/**
 * 재무 관리 - 거래 승인/거부 처리
 * 
 * 이 페이지는 거래 승인 및 거부 기능을 처리합니다.
 */

// 설정 및 공통 함수
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';
require_once '../../includes/db.php';

// 데이터베이스 연결
$db = get_db_connection();

// 거래 ID 및 액션 확인
$transactionId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$action = isset($_GET['action']) ? sanitizeInput($_GET['action']) : '';
$confirm = isset($_GET['confirm']) ? sanitizeInput($_GET['confirm']) : '';

// 유효한 액션 확인
$validActions = ['approve', 'reject'];
if (!in_array($action, $validActions) || $transactionId <= 0) {
    // 잘못된 파라미터인 경우 목록 페이지로 리다이렉트
    header('Location: transactions.php');
    exit;
}

// 거래 정보 조회
$sql = "SELECT transaction_code, transaction_type, status FROM financial_transactions WHERE id = ?";
$stmt = $db->prepare($sql);
$stmt->bindParam(1, $transactionId, PDO::PARAM_INT);
$stmt->execute();
$transaction = $stmt->fetch(PDO::FETCH_ASSOC);

// 거래 정보가 없는 경우 (실제 데이터베이스 연결이 없을 경우를 위한 Mock 데이터)
if (!$transaction) {
    $transaction = [
        'transaction_code' => 'TR' . date('Ymd') . str_pad($transactionId, 4, '0', STR_PAD_LEFT),
        'transaction_type' => array_rand(['income' => '', 'expense' => '', 'transfer' => '', 'adjustment' => '']),
        'status' => 'pending'
    ];
}

// 확인 프로세스
if ($confirm == 'yes') {
    try {
        // 거래 상태 확인 - pending 상태가 아닌 경우 처리 불가
        if ($transaction['status'] != 'pending') {
            $_SESSION['error_message'] = "처리 중(pending) 상태인 거래만 승인/거부할 수 있습니다.";
            header('Location: transaction-view.php?id=' . $transactionId);
            exit;
        }
        
        // 새 상태 및 설명 설정
        $newStatus = ($action == 'approve') ? 'completed' : 'failed';
        $statusDescription = ($action == 'approve') ? '거래 승인됨' : '거래 거부됨';
        $approvedBy = ($action == 'approve') ? 1 : null; // 현재 로그인한 사용자 ID
        
        // 거래 상태 업데이트 쿼리
        $updateSql = "UPDATE financial_transactions 
                      SET status = ?, 
                          notes = CONCAT(IFNULL(notes, ''), '\n', NOW(), ' - ', ?),
                          approved_by = ?,
                          updated_at = NOW() 
                      WHERE id = ?";
        
        $updateStmt = $db->prepare($updateSql);
        $updateStmt->bindParam(1, $newStatus);
        $updateStmt->bindParam(2, $statusDescription);
        $updateStmt->bindParam(3, $approvedBy);
        $updateStmt->bindParam(4, $transactionId, PDO::PARAM_INT);
        $result = $updateStmt->execute();
        
        if ($result) {
            // 업데이트 성공
            $message = ($action == 'approve') ? "거래가 성공적으로 승인되었습니다." : "거래가 거부되었습니다.";
            $_SESSION['success_message'] = $message;
            header('Location: transaction-view.php?id=' . $transactionId . '&success=1');
            exit;
        } else {
            // 업데이트 실패
            $_SESSION['error_message'] = "거래 상태 변경 중 오류가 발생했습니다.";
            header('Location: transaction-view.php?id=' . $transactionId);
            exit;
        }
    } catch (Exception $e) {
        // 예외 발생
        $_SESSION['error_message'] = "오류가 발생했습니다: " . $e->getMessage();
        header('Location: transaction-view.php?id=' . $transactionId);
        exit;
    }
}

// 페이지 제목 및 설명 설정
$pageTitle = ($action == 'approve') ? "거래 승인 확인" : "거래 거부 확인";
$actionDescription = ($action == 'approve') ? "승인" : "거부";

// 현재 페이지 정보
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
                    <li class="breadcrumb-item"><a href="transaction-view.php?id=<?php echo $transactionId; ?>">거래 상세 정보</a></li>
                    <li class="breadcrumb-item active"><?php echo $pageTitle; ?></li>
                </ol>
            </div>
        </div>
    </div>
</div>
<!-- /.content-header -->

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <!-- 승인/거부 확인 카드 -->
        <div class="card">
            <div class="card-header <?php echo ($action == 'approve' ? 'bg-success' : 'bg-danger'); ?>">
                <h3 class="card-title">거래 <?php echo $actionDescription; ?> 확인</h3>
            </div>
            <div class="card-body">
                <div class="alert <?php echo ($action == 'approve' ? 'alert-success' : 'alert-warning'); ?>">
                    <h5><i class="icon fas <?php echo ($action == 'approve' ? 'fa-check' : 'fa-exclamation-triangle'); ?>"></i> 확인!</h5>
                    <p>거래 <strong><?php echo htmlspecialchars($transaction['transaction_code']); ?></strong>를 정말로 <?php echo $actionDescription; ?>하시겠습니까?</p>
                    <?php if ($action == 'approve'): ?>
                    <p>거래를 승인하면 상태가 '완료됨'으로 변경되며, 이후 변경이 제한될 수 있습니다.</p>
                    <?php else: ?>
                    <p>거래를 거부하면 상태가 '실패'로 변경됩니다. 이 작업은 되돌릴 수 있습니다.</p>
                    <?php endif; ?>
                </div>
                
                <div class="mt-4">
                    <a href="transaction-approve.php?id=<?php echo $transactionId; ?>&action=<?php echo $action; ?>&confirm=yes" class="btn <?php echo ($action == 'approve' ? 'btn-success' : 'btn-danger'); ?>">
                        <i class="fas <?php echo ($action == 'approve' ? 'fa-check' : 'fa-times'); ?>"></i> 
                        예, <?php echo $actionDescription; ?>합니다
                    </a>
                    <a href="transaction-view.php?id=<?php echo $transactionId; ?>" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> 아니요, 취소합니다
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
// 템플릿 푸터 포함
include_once TEMPLATES_PATH . '/dashboard_footer.php';
?>