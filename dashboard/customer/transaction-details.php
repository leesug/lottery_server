<?php
/**
 * 거래 내역 세부 정보 페이지
 * 
 * 이 페이지는 특정 거래의 세부 정보를 표시합니다.
 */

// 필요한 파일 포함
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

// 페이지 접근 권한 확인
checkPageAccess('customer_management');

// 거래 ID 및 고객 ID 유효성 검사
$transactionId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$customerId = isset($_GET['customer_id']) ? (int)$_GET['customer_id'] : 0;

if ($transactionId <= 0 || $customerId <= 0) {
    // 유효하지 않은 ID인 경우 고객 목록 페이지로 리다이렉트
    header('Location: customer-list.php');
    exit;
}

// 데이터베이스 연결
$db = getDbConnection();

// 거래 정보 조회
$sql = "SELECT t.*, c.first_name, c.last_name, c.customer_code, c.email, c.phone
        FROM customer_transactions t
        JOIN customers c ON t.customer_id = c.id
        WHERE t.id = ? AND t.customer_id = ?";
$stmt = $db->prepare($sql);
$stmt->bind_param('ii', $transactionId, $customerId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // 거래 정보가 없는 경우 고객 거래 내역 페이지로 리다이렉트
    $stmt->close();
    header('Location: customer-transactions.php?customer_id=' . $customerId);
    exit;
}

$transaction = $result->fetch_assoc();
$stmt->close();

// 페이지 제목 및 기타 메타 정보
$pageTitle = "거래 내역 세부 정보: " . $transaction['reference_number'];
$pageDescription = "거래 ID: " . $transaction['id'] . "의 세부 정보입니다.";
$activeMenu = "customer";
$activeSubMenu = "customer-list";

// 거래 상태 업데이트 처리
$statusMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    // CSRF 토큰 검증
    validateCsrfToken($_POST['csrf_token']);
    
    $newStatus = sanitizeInput($_POST['new_status']);
    $validStatuses = ['pending', 'completed', 'failed', 'cancelled'];
    
    if (in_array($newStatus, $validStatuses)) {
        $updateSql = "UPDATE customer_transactions SET status = ? WHERE id = ?";
        $updateStmt = $db->prepare($updateSql);
        $updateStmt->bind_param('si', $newStatus, $transactionId);
        $result = $updateStmt->execute();
        $updateStmt->close();
        
        if ($result) {
            $statusMessage = '<div class="alert alert-success">거래 상태가 성공적으로 업데이트되었습니다.</div>';
            
            // 작업 로그 기록
            logAction('transaction_status_update', '거래 상태 업데이트: ' . $transactionId . ', 새 상태: ' . $newStatus);
            
            // 업데이트된 거래 정보 다시 조회
            $sql = "SELECT t.*, c.first_name, c.last_name, c.customer_code, c.email, c.phone
                    FROM customer_transactions t
                    JOIN customers c ON t.customer_id = c.id
                    WHERE t.id = ? AND t.customer_id = ?";
            $stmt = $db->prepare($sql);
            $stmt->bind_param('ii', $transactionId, $customerId);
            $stmt->execute();
            $result = $stmt->get_result();
            $transaction = $result->fetch_assoc();
            $stmt->close();
        } else {
            $statusMessage = '<div class="alert alert-danger">거래 상태 업데이트 중 오류가 발생했습니다.</div>';
            logError('transaction_status_update_fail', '거래 상태 업데이트 실패: ' . $db->error);
        }
    } else {
        $statusMessage = '<div class="alert alert-danger">유효하지 않은 상태 값입니다.</div>';
    }
}

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
                        <li class="breadcrumb-item active">거래 세부 정보</li>
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

            <?php echo $statusMessage; ?>

            <div class="row">
                <!-- 거래 기본 정보 -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">거래 기본 정보</h3>
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered">
                                <tr>
                                    <th style="width: 30%">거래 ID</th>
                                    <td><?php echo $transaction['id']; ?></td>
                                </tr>
                                <tr>
                                    <th>참조 번호</th>
                                    <td><?php echo htmlspecialchars($transaction['reference_number']); ?></td>
                                </tr>
                                <tr>
                                    <th>거래 유형</th>
                                    <td>
                                        <?php
                                        $typeClass = '';
                                        $typeText = '';
                                        
                                        switch ($transaction['transaction_type']) {
                                            case 'purchase':
                                                $typeClass = 'primary';
                                                $typeText = '구매';
                                                break;
                                            case 'prize_claim':
                                                $typeClass = 'success';
                                                $typeText = '당첨금 지급';
                                                break;
                                            case 'refund':
                                                $typeClass = 'warning';
                                                $typeText = '환불';
                                                break;
                                            case 'deposit':
                                                $typeClass = 'info';
                                                $typeText = '입금';
                                                break;
                                            case 'withdrawal':
                                                $typeClass = 'danger';
                                                $typeText = '출금';
                                                break;
                                            default:
                                                $typeClass = 'secondary';
                                                $typeText = '기타';
                                        }
                                        ?>
                                        <span class="badge badge-<?php echo $typeClass; ?>">
                                            <?php echo $typeText; ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>금액</th>
                                    <td><?php echo number_format($transaction['amount'], 2); ?></td>
                                </tr>
                                <tr>
                                    <th>거래 날짜</th>
                                    <td><?php echo date('Y-m-d H:i:s', strtotime($transaction['transaction_date'])); ?></td>
                                </tr>
                                <tr>
                                    <th>현재 상태</th>
                                    <td>
                                        <?php
                                        $statusClass = '';
                                        $statusText = '';
                                        
                                        switch ($transaction['status']) {
                                            case 'pending':
                                                $statusClass = 'warning';
                                                $statusText = '대기중';
                                                break;
                                            case 'completed':
                                                $statusClass = 'success';
                                                $statusText = '완료';
                                                break;
                                            case 'failed':
                                                $statusClass = 'danger';
                                                $statusText = '실패';
                                                break;
                                            case 'cancelled':
                                                $statusClass = 'secondary';
                                                $statusText = '취소';
                                                break;
                                            default:
                                                $statusClass = 'info';
                                                $statusText = '알 수 없음';
                                        }
                                        ?>
                                        <span class="badge badge-<?php echo $statusClass; ?>">
                                            <?php echo $statusText; ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>생성일</th>
                                    <td><?php echo date('Y-m-d H:i:s', strtotime($transaction['created_at'])); ?></td>
                                </tr>
                                <tr>
                                    <th>마지막 업데이트</th>
                                    <td><?php echo date('Y-m-d H:i:s', strtotime($transaction['updated_at'])); ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- 고객 정보 -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">고객 정보</h3>
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered">
                                <tr>
                                    <th style="width: 30%">고객 ID</th>
                                    <td><?php echo $transaction['customer_id']; ?></td>
                                </tr>
                                <tr>
                                    <th>고객 코드</th>
                                    <td><?php echo htmlspecialchars($transaction['customer_code']); ?></td>
                                </tr>
                                <tr>
                                    <th>이름</th>
                                    <td><?php echo htmlspecialchars($transaction['first_name'] . ' ' . $transaction['last_name']); ?></td>
                                </tr>
                                <tr>
                                    <th>이메일</th>
                                    <td><?php echo htmlspecialchars($transaction['email']); ?></td>
                                </tr>
                                <tr>
                                    <th>전화번호</th>
                                    <td><?php echo htmlspecialchars($transaction['phone']); ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- 상태 업데이트 폼 -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">거래 상태 업데이트</h3>
                        </div>
                        <div class="card-body">
                            <form action="" method="POST">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                <div class="form-group">
                                    <label for="new_status">새 상태:</label>
                                    <select class="form-control" id="new_status" name="new_status">
                                        <option value="pending" <?php echo ($transaction['status'] == 'pending') ? 'selected' : ''; ?>>대기중</option>
                                        <option value="completed" <?php echo ($transaction['status'] == 'completed') ? 'selected' : ''; ?>>완료</option>
                                        <option value="failed" <?php echo ($transaction['status'] == 'failed') ? 'selected' : ''; ?>>실패</option>
                                        <option value="cancelled" <?php echo ($transaction['status'] == 'cancelled') ? 'selected' : ''; ?>>취소</option>
                                    </select>
                                </div>
                                <button type="submit" name="update_status" class="btn btn-primary">
                                    <i class="fas fa-save"></i> 상태 업데이트
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 거래 세부 내용 -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">거래 세부 내용</h3>
                </div>
                <div class="card-body">
                    <?php if (!empty($transaction['details'])) : ?>
                        <div class="callout callout-info">
                            <?php echo nl2br(htmlspecialchars($transaction['details'])); ?>
                        </div>
                    <?php else : ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> 거래 세부 내용이 없습니다.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
</div>

<script>
// 브라우저 콘솔에 디버깅 정보 출력
console.log('거래 세부 정보 페이지 로드됨');
console.log('거래 ID:', <?php echo json_encode($transactionId); ?>);
console.log('고객 ID:', <?php echo json_encode($customerId); ?>);
console.log('거래 정보:', <?php echo json_encode($transaction); ?>);

// 페이지 로드 시 이벤트 처리
document.addEventListener('DOMContentLoaded', function() {
    // 상태 변경 이벤트 처리
    document.getElementById('new_status').addEventListener('change', function() {
        console.log('새 상태 선택:', this.value);
    });

    // 상태 업데이트 폼 제출 이벤트 처리
    document.querySelector('form').addEventListener('submit', function(e) {
        console.log('상태 업데이트 폼 제출됨');
    });
});
</script>

<?php
// 푸터 포함
include '../../templates/footer.php';
?>
