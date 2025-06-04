<?php
/**
 * 재무 관리 - 거래 상세 정보 페이지
 * 
 * 이 페이지는 특정 재무 거래의 상세 정보를 표시합니다.
 */

// 세션 시작 및 필수 파일 포함
session_start();
require_once '../../includes/auth.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// 인증 확인
checkAuth();

// 필요한 권한 확인
$requiredPermissions = ['finance_view', 'finance_transactions_view'];
checkPermissions($requiredPermissions);

// 거래 ID 확인
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    setAlert('거래 ID가 유효하지 않습니다.', 'error');
    redirectTo('transactions.php');
}

$transactionId = intval($_GET['id']);

// 데이터베이스 연결
$conn = getDBConnection();

// 거래 정보 조회
$sql = "SELECT t.*, u1.username as created_by_user, u2.username as approved_by_user, 
        fc.category_name, fc.category_type
        FROM financial_transactions t 
        LEFT JOIN users u1 ON t.created_by = u1.id 
        LEFT JOIN users u2 ON t.approved_by = u2.id 
        LEFT JOIN financial_categories fc ON t.category_id = fc.id 
        WHERE t.id = ?";

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
    redirectTo('transactions.php');
}

$transaction = $result->fetch_assoc();

// 연관된 참조 정보 조회
$referenceInfo = null;
if (!empty($transaction['reference_type']) && !empty($transaction['reference_id'])) {
    $referenceInfo = getTransactionReferenceInfo($transaction['reference_type'], $transaction['reference_id']);
}

// 페이지 제목 설정
$pageTitle = "거래 상세 정보: " . $transaction['transaction_code'];
$currentSection = "finance";
$currentPage = "transactions";

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
    'bank_transfer' => '계좌이체',
    'check' => '수표',
    'credit_card' => '신용카드',
    'debit_card' => '직불카드',
    'mobile_payment' => '모바일결제',
    'other' => '기타'
];

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
                        <li class="breadcrumb-item"><a href="transactions.php">거래 목록</a></li>
                        <li class="breadcrumb-item active">상세 정보</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">
            <!-- 거래 정보 카드 -->
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-money-bill-wave mr-1"></i>
                                거래 기본 정보
                            </h3>
                            <div class="card-tools">
                                <a href="transactions.php" class="btn btn-default btn-sm">
                                    <i class="fas fa-arrow-left"></i> 목록으로
                                </a>
                                <?php if (hasPermission('finance_transactions_print')): ?>
                                    <button type="button" class="btn btn-info btn-sm" id="printBtn">
                                        <i class="fas fa-print"></i> 인쇄
                                    </button>
                                <?php endif; ?>
                                <?php if (hasPermission('finance_transactions_edit') && ($transaction['status'] == 'pending' || $transaction['status'] == 'failed')): ?>
                                    <a href="transaction-edit.php?id=<?php echo $transactionId; ?>" class="btn btn-primary btn-sm">
                                        <i class="fas fa-edit"></i> 수정
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <dl class="row">
                                        <dt class="col-sm-4">거래 코드</dt>
                                        <dd class="col-sm-8"><?php echo htmlspecialchars($transaction['transaction_code']); ?></dd>
                                        
                                        <dt class="col-sm-4">거래 유형</dt>
                                        <dd class="col-sm-8">
                                            <?php 
                                                $typeClass = '';
                                                switch($transaction['transaction_type']) {
                                                    case 'income':
                                                        $typeClass = 'badge bg-success';
                                                        break;
                                                    case 'expense':
                                                        $typeClass = 'badge bg-danger';
                                                        break;
                                                    case 'transfer':
                                                        $typeClass = 'badge bg-info';
                                                        break;
                                                    case 'adjustment':
                                                        $typeClass = 'badge bg-warning';
                                                        break;
                                                }
                                                echo '<span class="' . $typeClass . '">' . $transactionTypes[$transaction['transaction_type']] . '</span>';
                                            ?>
                                        </dd>
                                        
                                        <dt class="col-sm-4">금액</dt>
                                        <dd class="col-sm-8">
                                            <?php 
                                                $amountClass = $transaction['transaction_type'] == 'income' ? 'text-success' : ($transaction['transaction_type'] == 'expense' ? 'text-danger' : '');
                                                echo '<span class="' . $amountClass . ' font-weight-bold">' . number_format($transaction['amount'], 2) . ' ' . htmlspecialchars($transaction['currency']) . '</span>'; 
                                            ?>
                                        </dd>
                                        
                                        <dt class="col-sm-4">분류</dt>
                                        <dd class="col-sm-8"><?php echo htmlspecialchars($transaction['category_name'] ?? '분류 없음'); ?></dd>
                                    </dl>
                                </div>
                                <div class="col-md-6">
                                    <dl class="row">
                                        <dt class="col-sm-4">거래 날짜</dt>
                                        <dd class="col-sm-8"><?php echo date('Y-m-d H:i', strtotime($transaction['transaction_date'])); ?></dd>
                                        
                                        <dt class="col-sm-4">지불 방법</dt>
                                        <dd class="col-sm-8"><?php echo $paymentMethods[$transaction['payment_method']] ?? htmlspecialchars($transaction['payment_method']); ?></dd>
                                        
                                        <dt class="col-sm-4">상태</dt>
                                        <dd class="col-sm-8">
                                            <?php 
                                                $statusClass = '';
                                                switch($transaction['status']) {
                                                    case 'pending':
                                                        $statusClass = 'badge bg-warning';
                                                        break;
                                                    case 'completed':
                                                        $statusClass = 'badge bg-success';
                                                        break;
                                                    case 'failed':
                                                        $statusClass = 'badge bg-danger';
                                                        break;
                                                    case 'cancelled':
                                                        $statusClass = 'badge bg-secondary';
                                                        break;
                                                    case 'reconciled':
                                                        $statusClass = 'badge bg-info';
                                                        break;
                                                }
                                                echo '<span class="' . $statusClass . '">' . $transactionStatuses[$transaction['status']] . '</span>';
                                            ?>
                                        </dd>
                                        
                                        <dt class="col-sm-4">등록일</dt>
                                        <dd class="col-sm-8"><?php echo date('Y-m-d H:i', strtotime($transaction['created_at'])); ?></dd>
                                    </dl>
                                </div>
                            </div>
                            
                            <div class="row mt-3">
                                <div class="col-12">
                                    <h5>거래 내용</h5>
                                    <p><?php echo nl2br(htmlspecialchars($transaction['description'] ?? '내용 없음')); ?></p>
                                </div>
                            </div>
                            
                            <?php if (!empty($transaction['payment_details'])): ?>
                                <div class="row mt-3">
                                    <div class="col-12">
                                        <h5>지불 상세 정보</h5>
                                        <p><?php echo nl2br(htmlspecialchars($transaction['payment_details'])); ?></p>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($referenceInfo): ?>
                                <div class="row mt-3">
                                    <div class="col-12">
                                        <h5>참조 정보</h5>
                                        <div class="table-responsive">
                                            <table class="table table-bordered">
                                                <tr>
                                                    <th style="width: 20%">참조 유형</th>
                                                    <td><?php echo htmlspecialchars($referenceInfo['type_name']); ?></td>
                                                </tr>
                                                <tr>
                                                    <th>참조 ID</th>
                                                    <td><?php echo htmlspecialchars($referenceInfo['id']); ?></td>
                                                </tr>
                                                <?php foreach ($referenceInfo['details'] as $key => $value): ?>
                                                    <tr>
                                                        <th><?php echo htmlspecialchars($key); ?></th>
                                                        <td><?php echo htmlspecialchars($value); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                                <?php if (!empty($referenceInfo['link'])): ?>
                                                    <tr>
                                                        <th>링크</th>
                                                        <td><a href="<?php echo $referenceInfo['link']; ?>" target="_blank">상세 정보 보기</a></td>
                                                    </tr>
                                                <?php endif; ?>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <!-- 승인 정보 -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">승인 정보</h3>
                        </div>
                        <div class="card-body">
                            <dl class="row">
                                <dt class="col-sm-5">등록자</dt>
                                <dd class="col-sm-7"><?php echo htmlspecialchars($transaction['created_by_user'] ?? '미확인'); ?></dd>
                                
                                <dt class="col-sm-5">승인자</dt>
                                <dd class="col-sm-7"><?php echo htmlspecialchars($transaction['approved_by_user'] ?? '미승인'); ?></dd>
                                
                                <?php if ($transaction['approved_by']): ?>
                                    <dt class="col-sm-5">승인일</dt>
                                    <dd class="col-sm-7"><?php echo date('Y-m-d H:i', strtotime($transaction['updated_at'])); ?></dd>
                                <?php endif; ?>
                            </dl>
                            
                            <?php if (!empty($transaction['notes'])): ?>
                                <h5 class="mt-3">메모</h5>
                                <p><?php echo nl2br(htmlspecialchars($transaction['notes'])); ?></p>
                            <?php endif; ?>
                            
                            <?php if (hasPermission('finance_transactions_approve') && $transaction['status'] == 'pending'): ?>
                                <button type="button" class="btn btn-success btn-block mt-3" id="approveBtn">
                                    <i class="fas fa-check"></i> 거래 승인
                                </button>
                            <?php endif; ?>
                            
                            <?php if (hasPermission('finance_transactions_reject') && $transaction['status'] == 'pending'): ?>
                                <button type="button" class="btn btn-danger btn-block mt-2" id="rejectBtn">
                                    <i class="fas fa-times"></i> 거래 거부
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- 변경 이력 -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">변경 이력</h3>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>일시</th>
                                            <th>작업자</th>
                                            <th>변경 내용</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        // 변경 이력 조회 (실제로는 별도의 로그 테이블에서 조회해야 함)
                                        $historyLog = [];
                                        
                                        if (empty($historyLog)):
                                        ?>
                                            <tr>
                                                <td colspan="3" class="text-center">변경 이력이 없습니다.</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($historyLog as $log): ?>
                                                <tr>
                                                    <td><?php echo date('Y-m-d H:i', strtotime($log['timestamp'])); ?></td>
                                                    <td><?php echo htmlspecialchars($log['username']); ?></td>
                                                    <td><?php echo htmlspecialchars($log['action']); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 거래 승인 모달 -->
<div class="modal fade" id="approveTransactionModal" tabindex="-1" role="dialog" aria-labelledby="approveTransactionModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="approveTransactionModalLabel">거래 승인</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="approveTransactionForm" action="../../api/finance/approve-transaction.php" method="post">
                <div class="modal-body">
                    <p>다음 거래를 승인하시겠습니까?</p>
                    <p><strong>거래 코드:</strong> <?php echo htmlspecialchars($transaction['transaction_code']); ?></p>
                    <p><strong>금액:</strong> <?php echo number_format($transaction['amount'], 2) . ' ' . htmlspecialchars($transaction['currency']); ?></p>
                    
                    <input type="hidden" name="transaction_id" value="<?php echo $transactionId; ?>">
                    <div class="form-group">
                        <label for="approve_notes">승인 메모 (선택사항)</label>
                        <textarea class="form-control" id="approve_notes" name="notes" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">취소</button>
                    <button type="submit" class="btn btn-success">승인</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 거래 거부 모달 -->
<div class="modal fade" id="rejectTransactionModal" tabindex="-1" role="dialog" aria-labelledby="rejectTransactionModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="rejectTransactionModalLabel">거래 거부</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="rejectTransactionForm" action="../../api/finance/reject-transaction.php" method="post">
                <div class="modal-body">
                    <p>다음 거래를 거부하시겠습니까?</p>
                    <p><strong>거래 코드:</strong> <?php echo htmlspecialchars($transaction['transaction_code']); ?></p>
                    <p><strong>금액:</strong> <?php echo number_format($transaction['amount'], 2) . ' ' . htmlspecialchars($transaction['currency']); ?></p>
                    
                    <input type="hidden" name="transaction_id" value="<?php echo $transactionId; ?>">
                    <div class="form-group">
                        <label for="reject_reason">거부 사유 (필수)</label>
                        <textarea class="form-control" id="reject_reason" name="reason" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">취소</button>
                    <button type="submit" class="btn btn-danger">거부</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 인쇄용 템플릿 -->
<div id="printArea" style="display: none;">
    <div style="padding: 20px;">
        <div style="text-align: center; margin-bottom: 20px;">
            <h1>거래 내역서</h1>
        </div>
        
        <div style="margin-bottom: 30px;">
            <div style="float: left; width: 50%;">
                <h3>KHUSHI LOTTERY</h3>
                <p>123 Main Street, Kathmandu</p>
                <p>Tel: +977-1-234-5678</p>
                <p>Email: info@khushilottery.com</p>
            </div>
            <div style="float: right; width: 50%; text-align: right;">
                <p><strong>거래 코드:</strong> <?php echo htmlspecialchars($transaction['transaction_code']); ?></p>
                <p><strong>거래 날짜:</strong> <?php echo date('Y-m-d H:i', strtotime($transaction['transaction_date'])); ?></p>
                <p><strong>인쇄 날짜:</strong> <span id="printDate"></span></p>
            </div>
            <div style="clear: both;"></div>
        </div>
        
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 30px;">
            <tr>
                <th style="border: 1px solid #ddd; padding: 8px; text-align: left; width: 30%;">거래 유형</th>
                <td style="border: 1px solid #ddd; padding: 8px;"><?php echo $transactionTypes[$transaction['transaction_type']]; ?></td>
            </tr>
            <tr>
                <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">금액</th>
                <td style="border: 1px solid #ddd; padding: 8px; font-weight: bold;"><?php echo number_format($transaction['amount'], 2) . ' ' . htmlspecialchars($transaction['currency']); ?></td>
            </tr>
            <tr>
                <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">분류</th>
                <td style="border: 1px solid #ddd; padding: 8px;"><?php echo htmlspecialchars($transaction['category_name'] ?? '분류 없음'); ?></td>
            </tr>
            <tr>
                <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">지불 방법</th>
                <td style="border: 1px solid #ddd; padding: 8px;"><?php echo $paymentMethods[$transaction['payment_method']] ?? htmlspecialchars($transaction['payment_method']); ?></td>
            </tr>
            <tr>
                <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">상태</th>
                <td style="border: 1px solid #ddd; padding: 8px;"><?php echo $transactionStatuses[$transaction['status']]; ?></td>
            </tr>
        </table>
        
        <div style="margin-bottom: 30px;">
            <h4>거래 내용</h4>
            <p><?php echo nl2br(htmlspecialchars($transaction['description'] ?? '내용 없음')); ?></p>
        </div>
        
        <?php if (!empty($transaction['payment_details'])): ?>
            <div style="margin-bottom: 30px;">
                <h4>지불 상세 정보</h4>
                <p><?php echo nl2br(htmlspecialchars($transaction['payment_details'])); ?></p>
            </div>
        <?php endif; ?>
        
        <div style="margin-top: 50px;">
            <div style="float: left; width: 45%; border-top: 1px solid #000; padding-top: 10px; text-align: center;">
                담당자 서명
            </div>
            <div style="float: right; width: 45%; border-top: 1px solid #000; padding-top: 10px; text-align: center;">
                승인자 서명
            </div>
            <div style="clear: both;"></div>
        </div>
        
        <div style="margin-top: 50px; font-size: 0.8em; text-align: center;">
            이 문서는 컴퓨터로 생성된 것으로 서명 없이도 유효합니다.
        </div>
    </div>
</div>

<?php include '../../templates/footer.php'; ?>

<script>
$(document).ready(function() {
    // 승인 버튼 클릭 이벤트
    $('#approveBtn').click(function() {
        $('#approveTransactionModal').modal('show');
    });

    // 거부 버튼 클릭 이벤트
    $('#rejectBtn').click(function() {
        $('#rejectTransactionModal').modal('show');
    });

    // 승인 폼 제출 이벤트
    $('#approveTransactionForm').submit(function(e) {
        e.preventDefault();
        $.ajax({
            type: "POST",
            url: $(this).attr('action'),
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '성공',
                        text: response.message,
                        showConfirmButton: false,
                        timer: 1500
                    }).then(function() {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: '오류',
                        text: response.message
                    });
                }
                $('#approveTransactionModal').modal('hide');
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: '오류',
                    text: '서버 요청 중 오류가 발생했습니다.'
                });
                $('#approveTransactionModal').modal('hide');
            }
        });
    });

    // 거부 폼 제출 이벤트
    $('#rejectTransactionForm').submit(function(e) {
        e.preventDefault();
        if (!$('#reject_reason').val().trim()) {
            Swal.fire({
                icon: 'warning',
                title: '입력 오류',
                text: '거부 사유를 입력해주세요.'
            });
            return false;
        }
        
        $.ajax({
            type: "POST",
            url: $(this).attr('action'),
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '성공',
                        text: response.message,
                        showConfirmButton: false,
                        timer: 1500
                    }).then(function() {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: '오류',
                        text: response.message
                    });
                }
                $('#rejectTransactionModal').modal('hide');
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: '오류',
                    text: '서버 요청 중 오류가 발생했습니다.'
                });
                $('#rejectTransactionModal').modal('hide');
            }
        });
    });

    // 인쇄 버튼 클릭 이벤트
    $('#printBtn').click(function() {
        var currentDate = new Date();
        var formattedDate = currentDate.getFullYear() + '-' + 
                            ('0' + (currentDate.getMonth() + 1)).slice(-2) + '-' + 
                            ('0' + currentDate.getDate()).slice(-2) + ' ' + 
                            ('0' + currentDate.getHours()).slice(-2) + ':' + 
                            ('0' + currentDate.getMinutes()).slice(-2);
        
        $('#printDate').text(formattedDate);
        
        var printContents = document.getElementById('printArea').innerHTML;
        var originalContents = document.body.innerHTML;
        
        document.body.innerHTML = printContents;
        window.print();
        document.body.innerHTML = originalContents;
        
        // 페이지 새로고침 대신 이벤트 핸들러 다시 등록
        $(document).ready(function() {
            $('#printBtn').click(function() {
                // 인쇄 이벤트 재등록
            });
        });
    });
});
</script>

<?php
// 거래 참조 정보 조회 함수
function getTransactionReferenceInfo($referenceType, $referenceId) {
    // 실제 구현에서는 DB 쿼리 등을 통해 참조 정보를 조회
    // 여기서는 간단히 예시만 제공
    
    // 참조 정보 기본 구조
    $referenceInfo = [
        'type_name' => '', // 참조 유형 이름
        'id' => $referenceId, // 참조 ID
        'details' => [], // 상세 정보
        'link' => '' // 관련 페이지 링크
    ];
    
    switch ($referenceType) {
        case 'sale':
            $referenceInfo['type_name'] = '판매';
            // 실제로는 판매 테이블에서 관련 정보 조회
            $referenceInfo['details'] = [
                '판매 날짜' => '2023-10-15',
                '판매점' => '예시 판매점',
                '판매 금액' => '5,000 NPR'
            ];
            $referenceInfo['link'] = '../../dashboard/sales/sale-details.php?id=' . $referenceId;
            break;
            
        case 'prize':
            $referenceInfo['type_name'] = '당첨금';
            // 실제로는 당첨금 테이블에서 관련 정보 조회
            $referenceInfo['details'] = [
                '당첨 날짜' => '2023-10-20',
                '당첨 유형' => '일반 당첨',
                '당첨 금액' => '1,000,000 NPR'
            ];
            $referenceInfo['link'] = '../../dashboard/prize/prize-details.php?id=' . $referenceId;
            break;
            
        case 'refund':
            $referenceInfo['type_name'] = '환불';
            // 실제로는 환불 테이블에서 관련 정보 조회
            $referenceInfo['details'] = [
                '환불 날짜' => '2023-10-18',
                '환불 사유' => '고객 요청',
                '환불 금액' => '2,000 NPR'
            ];
            $referenceInfo['link'] = '../../dashboard/sales/refund-details.php?id=' . $referenceId;
            break;
            
        case 'commission':
            $referenceInfo['type_name'] = '수수료';
            // 실제로는 수수료 테이블에서 관련 정보 조회
            $referenceInfo['details'] = [
                '정산 기간' => '2023-10-01 ~ 2023-10-15',
                '판매점' => '예시 판매점',
                '수수료율' => '5%',
                '수수료 금액' => '25,000 NPR'
            ];
            $referenceInfo['link'] = '../../dashboard/sales/commission-details.php?id=' . $referenceId;
            break;
            
        default:
            // 알 수 없는 참조 유형
            $referenceInfo['type_name'] = $referenceType;
            $referenceInfo['details'] = [
                '정보' => '상세 정보를 사용할 수 없습니다.'
            ];
            break;
    }
    
    return $referenceInfo;
}

// 연결 종료
$stmt->close();
$conn->close();
?>