<?php
/**
 * 고객 거래 내역 목록 페이지
 * 
 * 이 페이지는 특정 고객의 모든 거래 내역을 표시합니다.
 * 거래 유형, 날짜 등으로 필터링할 수 있습니다.
 */

// 필요한 파일 포함
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

// 페이지 접근 권한 확인
// functions.php에 정의된 checkPageAccess 함수 사용
if (function_exists('checkPageAccess')) {
    checkPageAccess('customer_management');
} else {
    // 함수가 로드되지 않은 경우 로그 기록
    error_log("checkPageAccess 함수를 찾을 수 없습니다.");
}

// 현재 페이지 정보
$pageTitle = "고객 거래 내역";
$currentSection = "customer";
$currentPage = basename($_SERVER['PHP_SELF']);

// 데이터베이스 연결
$conn = getDBConnection();

// 메시지 초기화
$message = '';
$message_type = '';

// 페이지네이션 설정
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
$offset = ($page - 1) * $limit;

// 고객 ID 유효성 검사
$customerId = isset($_GET['customer_id']) ? (int)$_GET['customer_id'] : 0;
$allCustomers = empty($customerId);

// 검색 및 필터링 파라미터
$startDate = isset($_GET['start_date']) ? sanitizeInput($_GET['start_date']) : '';
$endDate = isset($_GET['end_date']) ? sanitizeInput($_GET['end_date']) : '';
$transactionType = isset($_GET['transaction_type']) ? sanitizeInput($_GET['transaction_type']) : '';
$sortBy = isset($_GET['sort']) ? sanitizeInput($_GET['sort']) : 'transaction_date';
$sortOrder = isset($_GET['order']) ? sanitizeInput($_GET['order']) : 'DESC';

// 고객 정보 가져오기 함수
function getCustomerInfo($conn, $customerId) {
    // 실제 환경에서는 데이터베이스에서 가져옴
    // 여기서는 더미 데이터 반환
    if ($customerId === 1) {
        return [
            'id' => 1,
            'customer_code' => 'CUST00001',
            'full_name' => '홍길동',
            'email' => 'hong@example.com',
            'phone' => '010-1234-5678'
        ];
    } elseif ($customerId === 2) {
        return [
            'id' => 2,
            'customer_code' => 'CUST00002',
            'full_name' => '김철수',
            'email' => 'kim@example.com',
            'phone' => '010-2345-6789'
        ];
    } elseif ($customerId === 3) {
        return [
            'id' => 3,
            'customer_code' => 'CUST00003',
            'full_name' => '이영희',
            'email' => 'lee@example.com',
            'phone' => '010-3456-7890'
        ];
    } else {
        return null;
    }
}

// 거래 내역 가져오기 함수
function getTransactions($conn, $customerId = null, $startDate = '', $endDate = '', $transactionType = '', $sortBy = 'transaction_date', $sortOrder = 'DESC', $limit = 20, $offset = 0) {
    // 실제 환경에서는 데이터베이스에서 가져옴
    // 여기서는 더미 데이터 반환
    $transactions = [
        [
            'id' => 1,
            'customer_id' => 1,
            'customer_name' => '홍길동',
            'transaction_code' => 'TX00001',
            'transaction_date' => '2023-05-01 10:30:00',
            'transaction_type' => 'purchase',
            'amount' => 50000,
            'payment_method' => 'card',
            'status' => 'completed',
            'product' => '일반 복권',
            'ticket_count' => 5
        ],
        [
            'id' => 2,
            'customer_id' => 1,
            'customer_name' => '홍길동',
            'transaction_code' => 'TX00002',
            'transaction_date' => '2023-05-03 14:15:00',
            'transaction_type' => 'purchase',
            'amount' => 30000,
            'payment_method' => 'cash',
            'status' => 'completed',
            'product' => '연금 복권',
            'ticket_count' => 3
        ],
        [
            'id' => 3,
            'customer_id' => 1,
            'customer_name' => '홍길동',
            'transaction_code' => 'TX00003',
            'transaction_date' => '2023-05-05 16:45:00',
            'transaction_type' => 'prize_payout',
            'amount' => 100000,
            'payment_method' => 'bank_transfer',
            'status' => 'completed',
            'product' => '일반 복권',
            'ticket_count' => null
        ],
        [
            'id' => 4,
            'customer_id' => 2,
            'customer_name' => '김철수',
            'transaction_code' => 'TX00004',
            'transaction_date' => '2023-05-02 09:20:00',
            'transaction_type' => 'purchase',
            'amount' => 20000,
            'payment_method' => 'card',
            'status' => 'completed',
            'product' => '로또',
            'ticket_count' => 2
        ],
        [
            'id' => 5,
            'customer_id' => 2,
            'customer_name' => '김철수',
            'transaction_code' => 'TX00005',
            'transaction_date' => '2023-05-04 11:10:00',
            'transaction_type' => 'refund',
            'amount' => 10000,
            'payment_method' => 'card',
            'status' => 'completed',
            'product' => '로또',
            'ticket_count' => 1
        ]
    ];
    
    // 필터링
    $filteredTransactions = [];
    foreach ($transactions as $transaction) {
        // 고객 ID 필터
        if ($customerId !== null && $transaction['customer_id'] != $customerId) {
            continue;
        }
        
        // 시작 날짜 필터
        if (!empty($startDate) && strtotime($transaction['transaction_date']) < strtotime($startDate . ' 00:00:00')) {
            continue;
        }
        
        // 종료 날짜 필터
        if (!empty($endDate) && strtotime($transaction['transaction_date']) > strtotime($endDate . ' 23:59:59')) {
            continue;
        }
        
        // 거래 유형 필터
        if (!empty($transactionType) && $transaction['transaction_type'] != $transactionType) {
            continue;
        }
        
        $filteredTransactions[] = $transaction;
    }
    
    // 정렬
    usort($filteredTransactions, function($a, $b) use ($sortBy, $sortOrder) {
        if ($sortOrder === 'ASC') {
            return $a[$sortBy] <=> $b[$sortBy];
        } else {
            return $b[$sortBy] <=> $a[$sortBy];
        }
    });
    
    // 페이지네이션
    $paginatedTransactions = array_slice($filteredTransactions, $offset, $limit);
    
    return [
        'transactions' => $paginatedTransactions,
        'total' => count($filteredTransactions)
    ];
}

// 거래 내역 가져오기
$transactionData = getTransactions($conn, $allCustomers ? null : $customerId, $startDate, $endDate, $transactionType, $sortBy, $sortOrder, $limit, $offset);
$transactions = $transactionData['transactions'];
$totalTransactions = $transactionData['total'];
$totalPages = ceil($totalTransactions / $limit);

// 특정 고객의 정보 가져오기
$customerInfo = $allCustomers ? null : getCustomerInfo($conn, $customerId);

// 템플릿 헤더 포함
include_once TEMPLATES_PATH . '/dashboard_header.php';
?>

<!-- Content Header (Page header) -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">
                    <?php if ($customerInfo): ?>
                        <?php echo escape($customerInfo['full_name']); ?>님의 거래 내역
                    <?php else: ?>
                        <?php echo $pageTitle; ?>
                    <?php endif; ?>
                </h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?php echo SERVER_URL; ?>/dashboard/index.php">홈</a></li>
                    <li class="breadcrumb-item">고객 관리</li>
                    <?php if ($customerInfo): ?>
                        <li class="breadcrumb-item"><a href="<?php echo SERVER_URL; ?>/dashboard/customer/customer-list.php">고객 목록</a></li>
                        <li class="breadcrumb-item active"><?php echo escape($customerInfo['full_name']); ?>님의 거래 내역</li>
                    <?php else: ?>
                        <li class="breadcrumb-item active"><?php echo $pageTitle; ?></li>
                    <?php endif; ?>
                </ol>
            </div>
        </div>
    </div>
</div>
<!-- /.content-header -->

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <!-- 알림 메시지 -->
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
                <?php echo $message; ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <?php if ($customerInfo): ?>
        <!-- 고객 정보 -->
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">고객 정보</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <strong>고객 코드:</strong> <?php echo escape($customerInfo['customer_code']); ?>
                    </div>
                    <div class="col-md-3">
                        <strong>이름:</strong> <?php echo escape($customerInfo['full_name']); ?>
                    </div>
                    <div class="col-md-3">
                        <strong>이메일:</strong> <?php echo escape($customerInfo['email']); ?>
                    </div>
                    <div class="col-md-3">
                        <strong>전화번호:</strong> <?php echo escape($customerInfo['phone']); ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- 검색 및 필터 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">검색 및 필터</h3>
            </div>
            <div class="card-body">
                <form method="get" class="form-horizontal">
                    <?php if ($customerId): ?>
                        <input type="hidden" name="customer_id" value="<?php echo $customerId; ?>">
                    <?php endif; ?>
                    
                    <div class="form-row">
                        <div class="form-group col-md-3">
                            <label for="start_date">시작 날짜</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo escape($startDate); ?>">
                        </div>
                        <div class="form-group col-md-3">
                            <label for="end_date">종료 날짜</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo escape($endDate); ?>">
                        </div>
                        <div class="form-group col-md-3">
                            <label for="transaction_type">거래 유형</label>
                            <select class="form-control" id="transaction_type" name="transaction_type">
                                <option value="">모든 유형</option>
                                <option value="purchase" <?php echo $transactionType === 'purchase' ? 'selected' : ''; ?>>구매</option>
                                <option value="prize_payout" <?php echo $transactionType === 'prize_payout' ? 'selected' : ''; ?>>당첨금 지급</option>
                                <option value="refund" <?php echo $transactionType === 'refund' ? 'selected' : ''; ?>>환불</option>
                                <option value="deposit" <?php echo $transactionType === 'deposit' ? 'selected' : ''; ?>>입금</option>
                                <option value="withdrawal" <?php echo $transactionType === 'withdrawal' ? 'selected' : ''; ?>>출금</option>
                            </select>
                        </div>
                        <div class="form-group col-md-3">
                            <label for="limit">표시 개수</label>
                            <select class="form-control" id="limit" name="limit">
                                <option value="10" <?php echo $limit === 10 ? 'selected' : ''; ?>>10개</option>
                                <option value="20" <?php echo $limit === 20 ? 'selected' : ''; ?>>20개</option>
                                <option value="50" <?php echo $limit === 50 ? 'selected' : ''; ?>>50개</option>
                                <option value="100" <?php echo $limit === 100 ? 'selected' : ''; ?>>100개</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group col-md-3">
                            <label for="sort">정렬 기준</label>
                            <select class="form-control" id="sort" name="sort">
                                <option value="transaction_date" <?php echo $sortBy === 'transaction_date' ? 'selected' : ''; ?>>거래 날짜</option>
                                <option value="amount" <?php echo $sortBy === 'amount' ? 'selected' : ''; ?>>금액</option>
                                <option value="transaction_type" <?php echo $sortBy === 'transaction_type' ? 'selected' : ''; ?>>거래 유형</option>
                                <option value="status" <?php echo $sortBy === 'status' ? 'selected' : ''; ?>>상태</option>
                            </select>
                        </div>
                        <div class="form-group col-md-3">
                            <label for="order">정렬 순서</label>
                            <select class="form-control" id="order" name="order">
                                <option value="DESC" <?php echo $sortOrder === 'DESC' ? 'selected' : ''; ?>>내림차순</option>
                                <option value="ASC" <?php echo $sortOrder === 'ASC' ? 'selected' : ''; ?>>오름차순</option>
                            </select>
                        </div>
                        <div class="form-group col-md-6 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary">검색</button>
                            <a href="<?php echo SERVER_URL; ?>/dashboard/customer/customer-transactions.php<?php echo $customerId ? '?customer_id=' . $customerId : ''; ?>" class="btn btn-secondary ml-2">초기화</a>
                            <?php if (!$allCustomers): ?>
                                <a href="<?php echo SERVER_URL; ?>/dashboard/customer/customer-list.php" class="btn btn-info ml-auto">고객 목록으로 돌아가기</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- 거래 내역 목록 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">거래 내역</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" id="exportButton">
                        <i class="fas fa-file-export"></i> 내보내기
                    </button>
                </div>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover text-nowrap">
                    <thead>
                        <tr>
                            <th>거래 코드</th>
                            <?php if ($allCustomers): ?>
                                <th>고객명</th>
                            <?php endif; ?>
                            <th>날짜/시간</th>
                            <th>거래 유형</th>
                            <th>금액</th>
                            <th>결제 방법</th>
                            <th>상품</th>
                            <th>티켓 수</th>
                            <th>상태</th>
                            <th>작업</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($transactions)): ?>
                            <tr>
                                <td colspan="<?php echo $allCustomers ? 10 : 9; ?>" class="text-center">거래 내역이 없습니다.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($transactions as $transaction): ?>
                                <tr>
                                    <td>
                                        <a href="#" class="transaction-details" data-toggle="modal" data-target="#transactionModal" data-id="<?php echo $transaction['id']; ?>">
                                            <?php echo escape($transaction['transaction_code']); ?>
                                        </a>
                                    </td>
                                    <?php if ($allCustomers): ?>
                                        <td>
                                            <a href="<?php echo SERVER_URL; ?>/dashboard/customer/customer-transactions.php?customer_id=<?php echo $transaction['customer_id']; ?>">
                                                <?php echo escape($transaction['customer_name']); ?>
                                            </a>
                                        </td>
                                    <?php endif; ?>
                                    <td><?php echo formatDate($transaction['transaction_date'], 'Y-m-d H:i'); ?></td>
                                    <td>
                                        <?php
                                        switch ($transaction['transaction_type']) {
                                            case 'purchase':
                                                echo '<span class="badge badge-info">구매</span>';
                                                break;
                                            case 'prize_payout':
                                                echo '<span class="badge badge-success">당첨금 지급</span>';
                                                break;
                                            case 'refund':
                                                echo '<span class="badge badge-warning">환불</span>';
                                                break;
                                            case 'deposit':
                                                echo '<span class="badge badge-primary">입금</span>';
                                                break;
                                            case 'withdrawal':
                                                echo '<span class="badge badge-secondary">출금</span>';
                                                break;
                                            default:
                                                echo '<span class="badge badge-dark">' . escape($transaction['transaction_type']) . '</span>';
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo number_format($transaction['amount']); ?>원</td>
                                    <td>
                                        <?php
                                        switch ($transaction['payment_method']) {
                                            case 'card':
                                                echo '카드';
                                                break;
                                            case 'cash':
                                                echo '현금';
                                                break;
                                            case 'bank_transfer':
                                                echo '계좌이체';
                                                break;
                                            default:
                                                echo escape($transaction['payment_method']);
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo escape($transaction['product']); ?></td>
                                    <td><?php echo $transaction['ticket_count'] !== null ? number_format($transaction['ticket_count']) : '-'; ?></td>
                                    <td>
                                        <?php
                                        switch ($transaction['status']) {
                                            case 'completed':
                                                echo '<span class="badge badge-success">완료</span>';
                                                break;
                                            case 'pending':
                                                echo '<span class="badge badge-warning">대기 중</span>';
                                                break;
                                            case 'cancelled':
                                                echo '<span class="badge badge-danger">취소됨</span>';
                                                break;
                                            default:
                                                echo '<span class="badge badge-secondary">' . escape($transaction['status']) . '</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-info btn-sm transaction-details" data-toggle="modal" data-target="#transactionModal" data-id="<?php echo $transaction['id']; ?>" title="상세 정보">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <a href="<?php echo SERVER_URL; ?>/dashboard/finance/transaction-print.php?id=<?php echo $transaction['id']; ?>" target="_blank" class="btn btn-default btn-sm" title="영수증 인쇄">
                                                <i class="fas fa-print"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer clearfix">
                <div class="float-left">
                    전체 <?php echo $totalTransactions; ?>건 중 <?php echo count($transactions); ?>건 표시
                </div>
                <ul class="pagination pagination-sm m-0 float-right">
                    <?php
                    $queryParams = http_build_query([
                        'customer_id' => $customerId ?: '',
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'transaction_type' => $transactionType,
                        'sort' => $sortBy,
                        'order' => $sortOrder,
                        'limit' => $limit
                    ]);
                    ?>
                    
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=1&<?php echo $queryParams; ?>">&laquo;</a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&<?php echo $queryParams; ?>">&lsaquo;</a>
                        </li>
                    <?php endif; ?>
                    
                    <?php
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $page + 2);
                    
                    for ($i = $startPage; $i <= $endPage; $i++):
                    ?>
                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&<?php echo $queryParams; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&<?php echo $queryParams; ?>">&rsaquo;</a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $totalPages; ?>&<?php echo $queryParams; ?>">&raquo;</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</section>
<!-- /.content -->

<!-- 거래 상세 모달 -->
<div class="modal fade" id="transactionModal" tabindex="-1" aria-labelledby="transactionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="transactionModalLabel">거래 상세 정보</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-bordered">
                            <tr>
                                <th>거래 코드</th>
                                <td id="modal_transaction_code"></td>
                            </tr>
                            <tr>
                                <th>거래 날짜/시간</th>
                                <td id="modal_transaction_date"></td>
                            </tr>
                            <tr>
                                <th>고객명</th>
                                <td id="modal_customer_name"></td>
                            </tr>
                            <tr>
                                <th>거래 유형</th>
                                <td id="modal_transaction_type"></td>
                            </tr>
                            <tr>
                                <th>상태</th>
                                <td id="modal_status"></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-bordered">
                            <tr>
                                <th>금액</th>
                                <td id="modal_amount"></td>
                            </tr>
                            <tr>
                                <th>결제 방법</th>
                                <td id="modal_payment_method"></td>
                            </tr>
                            <tr>
                                <th>상품</th>
                                <td id="modal_product"></td>
                            </tr>
                            <tr>
                                <th>티켓 수</th>
                                <td id="modal_ticket_count"></td>
                            </tr>
                            <tr>
                                <th>처리자</th>
                                <td>관리자</td>
                            </tr>
                        </table>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-12">
                        <h5>티켓 목록</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>티켓 번호</th>
                                        <th>선택 번호</th>
                                        <th>추첨 날짜</th>
                                        <th>당첨 여부</th>
                                        <th>당첨금</th>
                                    </tr>
                                </thead>
                                <tbody id="modal_tickets">
                                    <!-- 티켓 목록은 JavaScript로 채워집니다 -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <a href="#" id="modal_print_receipt" class="btn btn-default" target="_blank">
                    <i class="fas fa-print"></i> 영수증 인쇄
                </a>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">닫기</button>
            </div>
        </div>
    </div>
</div>

<script>
// 거래 상세 정보 모달 이벤트
$('.transaction-details').click(function() {
    var transactionId = $(this).data('id');
    
    // 실제 환경에서는 AJAX로 데이터 요청
    // 여기서는 트랜잭션 목록에서 ID로 찾기
    var transaction = <?php echo json_encode($transactions); ?>.find(function(t) {
        return t.id == transactionId;
    });
    
    if (transaction) {
        $('#modal_transaction_code').text(transaction.transaction_code);
        $('#modal_transaction_date').text(formatDate(transaction.transaction_date));
        $('#modal_customer_name').text(transaction.customer_name);
        
        var transactionType = '';
        switch (transaction.transaction_type) {
            case 'purchase':
                transactionType = '<span class="badge badge-info">구매</span>';
                break;
            case 'prize_payout':
                transactionType = '<span class="badge badge-success">당첨금 지급</span>';
                break;
            case 'refund':
                transactionType = '<span class="badge badge-warning">환불</span>';
                break;
            case 'deposit':
                transactionType = '<span class="badge badge-primary">입금</span>';
                break;
            case 'withdrawal':
                transactionType = '<span class="badge badge-secondary">출금</span>';
                break;
            default:
                transactionType = '<span class="badge badge-dark">' + transaction.transaction_type + '</span>';
        }
        $('#modal_transaction_type').html(transactionType);
        
        var status = '';
        switch (transaction.status) {
            case 'completed':
                status = '<span class="badge badge-success">완료</span>';
                break;
            case 'pending':
                status = '<span class="badge badge-warning">대기 중</span>';
                break;
            case 'cancelled':
                status = '<span class="badge badge-danger">취소됨</span>';
                break;
            default:
                status = '<span class="badge badge-secondary">' + transaction.status + '</span>';
        }
        $('#modal_status').html(status);
        
        $('#modal_amount').text(numberFormat(transaction.amount) + '원');
        
        var paymentMethod = '';
        switch (transaction.payment_method) {
            case 'card':
                paymentMethod = '카드';
                break;
            case 'cash':
                paymentMethod = '현금';
                break;
            case 'bank_transfer':
                paymentMethod = '계좌이체';
                break;
            default:
                paymentMethod = transaction.payment_method;
        }
        $('#modal_payment_method').text(paymentMethod);
        
        $('#modal_product').text(transaction.product);
        $('#modal_ticket_count').text(transaction.ticket_count !== null ? numberFormat(transaction.ticket_count) : '-');
        
        // 티켓 목록 (더미 데이터)
        var ticketsHtml = '';
        if (transaction.transaction_type === 'purchase') {
            for (var i = 0; i < (transaction.ticket_count || 0); i++) {
                var ticketNumber = 'T' + transaction.transaction_code.substr(2) + '-' + (i + 1).toString().padStart(3, '0');
                var numbers = generateRandomNumbers();
                var drawDate = new Date(new Date(transaction.transaction_date).getTime() + (3 * 24 * 60 * 60 * 1000)); // 3일 후 추첨
                var isWinner = Math.random() < 0.2; // 20% 확률로 당첨
                var prize = isWinner ? Math.floor(Math.random() * 5 + 1) * 10000 : 0;
                
                ticketsHtml += '<tr>' +
                    '<td>' + ticketNumber + '</td>' +
                    '<td>' + numbers + '</td>' +
                    '<td>' + formatDate(drawDate.toISOString()) + '</td>' +
                    '<td>' + (isWinner ? '<span class="badge badge-success">당첨</span>' : '<span class="badge badge-secondary">미당첨</span>') + '</td>' +
                    '<td>' + (isWinner ? numberFormat(prize) + '원' : '-') + '</td>' +
                    '</tr>';
            }
        } else {
            ticketsHtml = '<tr><td colspan="5" class="text-center">티켓 정보가 없습니다.</td></tr>';
        }
        $('#modal_tickets').html(ticketsHtml);
        
        // 영수증 인쇄 링크 업데이트
        $('#modal_print_receipt').attr('href', '<?php echo SERVER_URL; ?>/dashboard/finance/transaction-print.php?id=' + transactionId);
    }
});

// 내보내기 버튼 이벤트
$('#exportButton').click(function() {
    alert('거래 내역 내보내기 기능은 구현 중입니다.');
});

// 날짜 형식 변환 함수
function formatDate(dateStr) {
    var date = new Date(dateStr);
    var year = date.getFullYear();
    var month = (date.getMonth() + 1).toString().padStart(2, '0');
    var day = date.getDate().toString().padStart(2, '0');
    var hours = date.getHours().toString().padStart(2, '0');
    var minutes = date.getMinutes().toString().padStart(2, '0');
    
    return `${year}-${month}-${day} ${hours}:${minutes}`;
}

// 숫자 형식 변환 함수
function numberFormat(number) {
    return new Intl.NumberFormat('ko-KR').format(number);
}

// 랜덤 번호 생성 함수 (더미 데이터용)
function generateRandomNumbers() {
    var numbers = [];
    for (var i = 0; i < 6; i++) {
        numbers.push(Math.floor(Math.random() * 45) + 1);
    }
    return numbers.sort((a, b) => a - b).join(', ');
}
</script>

<?php include_once TEMPLATES_PATH . '/dashboard_footer.php'; ?>
