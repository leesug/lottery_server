<?php
/**
 * 재무 관리 - 거래 삭제 처리
 * 
 * 이 페이지는 거래 삭제 기능을 처리합니다.
 */

// 설정 및 공통 함수
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';
require_once '../../includes/db.php';

// 데이터베이스 연결
$db = get_db_connection();

// 거래 ID 및 확인 토큰 확인
$transactionId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$confirm = isset($_GET['confirm']) ? sanitizeInput($_GET['confirm']) : '';

if ($transactionId <= 0) {
    // 잘못된 ID인 경우 목록 페이지로 리다이렉트
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
        // 거래 상태 확인 - 완료 또는 대사완료 상태인 경우 삭제 불가
        if (in_array($transaction['status'], ['completed', 'reconciled'])) {
            $_SESSION['error_message'] = "완료 또는 대사완료 상태인 거래는 삭제할 수 없습니다.";
            header('Location: transaction-view.php?id=' . $transactionId);
            exit;
        }
        
        // 거래 삭제 쿼리
        $deleteSql = "DELETE FROM financial_transactions WHERE id = ?";
        $deleteStmt = $db->prepare($deleteSql);
        $deleteStmt->bindParam(1, $transactionId, PDO::PARAM_INT);
        $result = $deleteStmt->execute();
        
        if ($result) {
            // 삭제 성공
            $_SESSION['success_message'] = "거래가 성공적으로 삭제되었습니다.";
            header('Location: transactions.php');
            exit;
        } else {
            // 삭제 실패
            $_SESSION['error_message'] = "거래 삭제 중 오류가 발생했습니다.";
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

// 현재 페이지 정보
$pageTitle = "거래 삭제 확인";
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
                    <li class="breadcrumb-item active">거래 삭제 확인</li>
                </ol>
            </div>
        </div>
    </div>
</div>
<!-- /.content-header -->

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <!-- 삭제 확인 카드 -->
        <div class="card">
            <div class="card-header bg-danger">
                <h3 class="card-title">거래 삭제 확인</h3>
            </div>
            <div class="card-body">
                <div class="alert alert-warning">
                    <h5><i class="icon fas fa-exclamation-triangle"></i> 주의!</h5>
                    <p>거래 <strong><?php echo htmlspecialchars($transaction['transaction_code']); ?></strong>를 정말로 삭제하시겠습니까?</p>
                    <p>이 작업은 되돌릴 수 없으며, 모든 관련 데이터가 영구적으로 삭제됩니다.</p>
                </div>
                
                <div class="mt-4">
                    <a href="transaction-delete.php?id=<?php echo $transactionId; ?>&confirm=yes" class="btn btn-danger">
                        <i class="fas fa-trash"></i> 예, 삭제합니다
                    </a>
                    <a href="transaction-view.php?id=<?php echo $transactionId; ?>" class="btn btn-secondary">
                        <i class="fas fa-times"></i> 아니요, 취소합니다
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