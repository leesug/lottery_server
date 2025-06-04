<?php
/**
 * 재무 관리 - 기금 상세 정보 페이지
 * 
 * 이 페이지는 특정 기금의 상세 정보와 거래 내역을 보여줍니다.
 */

// 세션 시작 및 필수 파일 포함
session_start();
require_once '../../includes/auth.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// 인증 확인
checkAuth();

// 필요한 권한 확인
$requiredPermissions = ['finance_view', 'finance_funds_view'];
checkPermissions($requiredPermissions);

// 기금 ID 확인
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    setAlert('기금 ID가 유효하지 않습니다.', 'error');
    redirectTo('funds.php');
}

$fundId = intval($_GET['id']);

// 데이터베이스 연결
$conn = getDBConnection();

// 기금 정보 조회
$sql = "SELECT * FROM funds WHERE id = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    logError("Database prepare error: " . $conn->error);
    die("데이터베이스 오류가 발생했습니다.");
}

$stmt->bind_param("i", $fundId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    setAlert('존재하지 않는 기금입니다.', 'error');
    redirectTo('funds.php');
}

$fund = $result->fetch_assoc();

// 기금 거래 내역 조회
$transactionSql = "SELECT * FROM fund_transactions WHERE fund_id = ? ORDER BY transaction_date DESC";
$transactionStmt = $conn->prepare($transactionSql);
$transactionStmt->bind_param("i", $fundId);
$transactionStmt->execute();
$transactionResult = $transactionStmt->get_result();

$transactions = [];
while ($row = $transactionResult->fetch_assoc()) {
    $transactions[] = $row;
}

// 페이지 제목 설정
$pageTitle = "기금 상세 정보: " . $fund['fund_name'];
$currentSection = "finance";
$currentPage = "funds";

// 기금 유형 및 상태 옵션
$fundTypes = [
    'prize' => '당첨금 기금', 
    'charity' => '자선 기금', 
    'development' => '개발 기금', 
    'operational' => '운영 기금', 
    'reserve' => '예비 기금', 
    'other' => '기타 기금'
];

$fundStatuses = [
    'active' => '활성', 
    'inactive' => '비활성', 
    'depleted' => '소진됨'
];

$transactionTypes = [
    'allocation' => '할당', 
    'withdrawal' => '인출', 
    'transfer' => '이체', 
    'adjustment' => '조정'
];

$transactionStatuses = [
    'pending' => '대기 중', 
    'completed' => '완료됨', 
    'cancelled' => '취소됨'
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
                        <li class="breadcrumb-item"><a href="funds.php">기금 관리</a></li>
                        <li class="breadcrumb-item active">기금 상세 정보</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-6">
                    <!-- 기금 정보 카드 -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-money-bill-wave mr-1"></i>
                                기금 기본 정보
                            </h3>
                            <div class="card-tools">
                                <a href="funds.php" class="btn btn-default btn-sm">
                                    <i class="fas fa-arrow-left"></i> 목록으로
                                </a>
                                <?php if (hasPermission('finance_funds_edit')): ?>
                                    <a href="fund-edit.php?id=<?php echo $fundId; ?>" class="btn btn-primary btn-sm">
                                        <i class="fas fa-edit"></i> 수정
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="card-body">
                            <dl class="row">
                                <dt class="col-sm-4">기금 코드</dt>
                                <dd class="col-sm-8"><?php echo htmlspecialchars($fund['fund_code']); ?></dd>
                                
                                <dt class="col-sm-4">기금명</dt>
                                <dd class="col-sm-8"><?php echo htmlspecialchars($fund['fund_name']); ?></dd>
                                
                                <dt class="col-sm-4">기금 유형</dt>
                                <dd class="col-sm-8">
                                    <?php 
                                        $typeClass = 'badge bg-primary';
                                        echo '<span class="' . $typeClass . '">' . ($fundTypes[$fund['fund_type']] ?? $fund['fund_type']) . '</span>';
                                    ?>
                                </dd>
                                
                                <dt class="col-sm-4">상태</dt>
                                <dd class="col-sm-8">
                                    <?php 
                                        $statusClass = '';
                                        switch($fund['status']) {
                                            case 'active':
                                                $statusClass = 'badge bg-success';
                                                break;
                                            case 'inactive':
                                                $statusClass = 'badge bg-warning';
                                                break;
                                            case 'depleted':
                                                $statusClass = 'badge bg-danger';
                                                break;
                                        }
                                        echo '<span class="' . $statusClass . '">' . $fundStatuses[$fund['status']] . '</span>';
                                    ?>
                                </dd>
                                
                                <dt class="col-sm-4">총 할당액</dt>
                                <dd class="col-sm-8"><?php echo number_format($fund['total_allocation'], 2) . ' NPR'; ?></dd>
                                
                                <dt class="col-sm-4">현재 잔액</dt>
                                <dd class="col-sm-8">
                                    <?php 
                                        $balanceClass = '';
                                        if ($fund['current_balance'] <= 0) {
                                            $balanceClass = 'text-danger font-weight-bold';
                                        } elseif ($fund['current_balance'] < ($fund['total_allocation'] * 0.1)) {
                                            $balanceClass = 'text-warning font-weight-bold';
                                        } else {
                                            $balanceClass = 'text-success font-weight-bold';
                                        }
                                        echo '<span class="' . $balanceClass . '">' . number_format($fund['current_balance'], 2) . ' NPR</span>'; 
                                    ?>
                                </dd>
                                
                                <dt class="col-sm-4">할당 비율</dt>
                                <dd class="col-sm-8">
                                    <?php 
                                        if (!empty($fund['allocation_percentage'])) {
                                            echo $fund['allocation_percentage'] . '%';
                                        } else {
                                            echo '지정되지 않음';
                                        }
                                    ?>
                                </dd>
                                
                                <dt class="col-sm-4">생성일</dt>
                                <dd class="col-sm-8"><?php echo date('Y-m-d H:i', strtotime($fund['created_at'])); ?></dd>
                                
                                <dt class="col-sm-4">최종 업데이트</dt>
                                <dd class="col-sm-8"><?php echo date('Y-m-d H:i', strtotime($fund['updated_at'])); ?></dd>
                            </dl>
                            
                            <?php if (!empty($fund['description'])): ?>
                                <div class="mt-4">
                                    <h5>설명</h5>
                                    <p><?php echo nl2br(htmlspecialchars($fund['description'])); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <!-- 기금 잔액 차트 카드 -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-chart-pie mr-1"></i>
                                기금 잔액 현황
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="chart-container" style="position: relative; height:250px;">
                                <canvas id="balanceChart"></canvas>
                            </div>
                            
                            <div class="mt-4">
                                <div class="info-box bg-gradient-success">
                                    <span class="info-box-icon"><i class="fas fa-dollar-sign"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">현재 잔액</span>
                                        <span class="info-box-number"><?php echo number_format($fund['current_balance'], 2) . ' NPR'; ?></span>
                                        <div class="progress">
                                            <?php 
                                                $percentage = 0;
                                                if ($fund['total_allocation'] > 0) {
                                                    $percentage = min(100, ($fund['current_balance'] / $fund['total_allocation']) * 100);
                                                }
                                            ?>
                                            <div class="progress-bar" style="width: <?php echo $percentage; ?>%"></div>
                                        </div>
                                        <span class="progress-description">
                                            총 할당액 대비 <?php echo number_format($percentage, 1); ?>%
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if (hasPermission('finance_funds_transactions')): ?>
                                <div class="text-center mt-3">
                                    <a href="fund-transaction-add.php?fund_id=<?php echo $fundId; ?>" class="btn btn-primary">
                                        <i class="fas fa-plus-circle"></i> 새 거래 추가
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 기금 거래 내역 카드 -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-list mr-1"></i>
                        기금 거래 내역
                    </h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="transactions-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>거래 유형</th>
                                    <th>금액</th>
                                    <th>거래 날짜</th>
                                    <th>설명</th>
                                    <th>참조 정보</th>
                                    <th>상태</th>
                                    <th>승인자</th>
                                    <th>작업</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($transactions) > 0): ?>
                                    <?php foreach ($transactions as $transaction): ?>
                                        <tr>
                                            <td><?php echo $transaction['id']; ?></td>
                                            <td>
                                                <?php 
                                                    $typeClass = '';
                                                    switch($transaction['transaction_type']) {
                                                        case 'allocation':
                                                            $typeClass = 'badge bg-success';
                                                            break;
                                                        case 'withdrawal':
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
                                            </td>
                                            <td class="text-right">
                                                <?php 
                                                    $amountClass = $transaction['transaction_type'] == 'allocation' ? 'text-success' : ($transaction['transaction_type'] == 'withdrawal' ? 'text-danger' : '');
                                                    echo '<span class="' . $amountClass . '">' . ($transaction['transaction_type'] == 'allocation' ? '+' : ($transaction['transaction_type'] == 'withdrawal' ? '-' : '')) . number_format($transaction['amount'], 2) . ' NPR</span>'; 
                                                ?>
                                            </td>
                                            <td><?php echo date('Y-m-d H:i', strtotime($transaction['transaction_date'])); ?></td>
                                            <td><?php echo htmlspecialchars($transaction['description'] ?? ''); ?></td>
                                            <td>
                                                <?php 
                                                    if (!empty($transaction['reference_type']) && !empty($transaction['reference_id'])) {
                                                        echo htmlspecialchars($transaction['reference_type'] . ': ' . $transaction['reference_id']);
                                                    } else {
                                                        echo '-';
                                                    }
                                                ?>
                                            </td>
                                            <td>
                                                <?php 
                                                    $statusClass = '';
                                                    switch($transaction['status']) {
                                                        case 'pending':
                                                            $statusClass = 'badge bg-warning';
                                                            break;
                                                        case 'completed':
                                                            $statusClass = 'badge bg-success';
                                                            break;
                                                        case 'cancelled':
                                                            $statusClass = 'badge bg-secondary';
                                                            break;
                                                    }
                                                    echo '<span class="' . $statusClass . '">' . $transactionStatuses[$transaction['status']] . '</span>';
                                                ?>
                                            </td>
                                            <td>
                                                <?php 
                                                    if (!empty($transaction['approved_by'])) {
                                                        echo getUserName($transaction['approved_by']);
                                                    } else {
                                                        echo '미승인';
                                                    }
                                                ?>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="fund-transaction-details.php?id=<?php echo $transaction['id']; ?>" class="btn btn-info btn-xs" title="상세보기">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <?php if (hasPermission('finance_funds_transactions_approve') && $transaction['status'] == 'pending'): ?>
                                                        <button type="button" class="btn btn-success btn-xs approve-transaction" data-id="<?php echo $transaction['id']; ?>" title="승인">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                    <?php if (hasPermission('finance_funds_transactions_cancel') && in_array($transaction['status'], ['pending'])): ?>
                                                        <button type="button" class="btn btn-danger btn-xs cancel-transaction" data-id="<?php echo $transaction['id']; ?>" title="취소">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="9" class="text-center">거래 내역이 없습니다.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
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
                <h5 class="modal-title" id="approveTransactionModalLabel">기금 거래 승인</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="approveTransactionForm" action="../../api/finance/approve-fund-transaction.php" method="post">
                <div class="modal-body">
                    <p>이 기금 거래를 승인하시겠습니까?</p>
                    <input type="hidden" name="transaction_id" id="approve_transaction_id" value="">
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

<!-- 거래 취소 모달 -->
<div class="modal fade" id="cancelTransactionModal" tabindex="-1" role="dialog" aria-labelledby="cancelTransactionModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cancelTransactionModalLabel">기금 거래 취소</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="cancelTransactionForm" action="../../api/finance/cancel-fund-transaction.php" method="post">
                <div class="modal-body">
                    <p>이 기금 거래를 취소하시겠습니까? 이 작업은 되돌릴 수 없습니다.</p>
                    <input type="hidden" name="transaction_id" id="cancel_transaction_id" value="">
                    <div class="form-group">
                        <label for="cancel_reason">취소 사유 (필수)</label>
                        <textarea class="form-control" id="cancel_reason" name="reason" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">닫기</button>
                    <button type="submit" class="btn btn-danger">취소 처리</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../../templates/footer.php'; ?>

<script>
$(document).ready(function() {
    // 잔액 차트 초기화
    var ctx = document.getElementById('balanceChart').getContext('2d');
    var totalAllocation = <?php echo $fund['total_allocation']; ?>;
    var currentBalance = <?php echo $fund['current_balance']; ?>;
    var usedAmount = totalAllocation - currentBalance;
    
    if (usedAmount < 0) usedAmount = 0;
    
    var balanceChart = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: ['사용됨', '잔액'],
            datasets: [{
                data: [usedAmount, currentBalance],
                backgroundColor: [
                    'rgba(255, 99, 132, 0.7)',
                    'rgba(75, 192, 192, 0.7)'
                ],
                borderColor: [
                    'rgba(255, 99, 132, 1)',
                    'rgba(75, 192, 192, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            legend: {
                position: 'bottom'
            },
            tooltips: {
                callbacks: {
                    label: function(tooltipItem, data) {
                        var dataset = data.datasets[tooltipItem.datasetIndex];
                        var index = tooltipItem.index;
                        var value = dataset.data[index];
                        var label = data.labels[index];
                        var total = dataset.data.reduce(function(previousValue, currentValue) {
                            return previousValue + currentValue;
                        });
                        var percentage = Math.round((value / total) * 100);
                        return label + ': ' + numberWithCommas(value.toFixed(2)) + ' NPR (' + percentage + '%)';
                    }
                }
            }
        }
    });
    
    // 거래 목록 데이터테이블 초기화
    $('#transactions-table').DataTable({
        "paging": true,
        "lengthChange": false,
        "searching": true,
        "ordering": true,
        "info": true,
        "autoWidth": false,
        "responsive": true,
        "language": {
            "emptyTable": "데이터가 없습니다.",
            "info": "총 _TOTAL_개 중 _START_에서 _END_까지 표시",
            "infoEmpty": "0개 중 0에서 0까지 표시",
            "infoFiltered": "(총 _MAX_개 중에서 필터링됨)",
            "lengthMenu": "_MENU_개씩 보기",
            "search": "검색:",
            "zeroRecords": "일치하는 레코드가 없습니다.",
            "paginate": {
                "first": "처음",
                "last": "마지막",
                "next": "다음",
                "previous": "이전"
            }
        }
    });
    
    // 거래 승인 버튼 클릭 이벤트
    $('.approve-transaction').click(function() {
        var transactionId = $(this).data('id');
        $('#approve_transaction_id').val(transactionId);
        $('#approveTransactionModal').modal('show');
    });
    
    // 거래 취소 버튼 클릭 이벤트
    $('.cancel-transaction').click(function() {
        var transactionId = $(this).data('id');
        $('#cancel_transaction_id').val(transactionId);
        $('#cancelTransactionModal').modal('show');
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
    
    // 취소 폼 제출 이벤트
    $('#cancelTransactionForm').submit(function(e) {
        e.preventDefault();
        if (!$('#cancel_reason').val().trim()) {
            Swal.fire({
                icon: 'warning',
                title: '입력 오류',
                text: '취소 사유를 입력해주세요.'
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
                $('#cancelTransactionModal').modal('hide');
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: '오류',
                    text: '서버 요청 중 오류가 발생했습니다.'
                });
                $('#cancelTransactionModal').modal('hide');
            }
        });
    });
    
    // 숫자 포맷 함수
    function numberWithCommas(x) {
        return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }
});
</script>

<?php
// 사용자 이름 가져오기 함수
function getUserName($userId) {
    global $conn;
    
    $sql = "SELECT username FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        return htmlspecialchars($user['username']);
    }
    
    return "미확인";
}

// 연결 종료
$stmt->close();
$transactionStmt->close();
$conn->close();
?>