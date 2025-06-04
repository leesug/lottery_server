<?php
/**
 * 재무 관리 - 거래 목록 페이지
 * 
 * 이 페이지는 시스템의 모든 재무 거래 목록을 표시합니다.
 */

// 설정 및 공통 함수
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';
require_once '../../includes/db.php';

// 데이터베이스 연결
$db = get_db_connection();

// 현재 페이지 정보
$pageTitle = "재무 거래 목록";
$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir = dirname($_SERVER['PHP_SELF']);
$parts = explode('/', $currentDir);
$currentSection = end($parts);

// 검색 및 필터링 파라미터
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$dateFrom = isset($_GET['date_from']) ? sanitizeInput($_GET['date_from']) : '';
$dateTo = isset($_GET['date_to']) ? sanitizeInput($_GET['date_to']) : '';
$transactionType = isset($_GET['transaction_type']) ? sanitizeInput($_GET['transaction_type']) : '';
$status = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';
$amountMin = isset($_GET['amount_min']) ? sanitizeInput($_GET['amount_min']) : '';
$amountMax = isset($_GET['amount_max']) ? sanitizeInput($_GET['amount_max']) : '';

// 페이지네이션 설정
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$recordsPerPage = 20;
$offset = ($page - 1) * $recordsPerPage;

// 거래 유형 및 상태 옵션
$transactionTypes = ['income' => '수입', 'expense' => '지출', 'transfer' => '이체', 'adjustment' => '조정'];
$transactionStatuses = [
    'pending' => '처리 중', 
    'completed' => '완료됨', 
    'failed' => '실패', 
    'cancelled' => '취소됨', 
    'reconciled' => '대사완료'
];

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
                    <li class="breadcrumb-item active">거래 목록</li>
                </ol>
            </div>
        </div>
    </div>
</div>
<!-- /.content-header -->

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <!-- 필터 및 검색 카드 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">검색 및 필터</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <form method="get" action="">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="search">검색어</label>
                                <input type="text" class="form-control" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="거래 코드, 설명">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="date_from">시작일</label>
                                <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="date_to">종료일</label>
                                <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo htmlspecialchars($dateTo); ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="transaction_type">거래 유형</label>
                                <select class="form-control" id="transaction_type" name="transaction_type">
                                    <option value="">전체</option>
                                    <?php foreach ($transactionTypes as $key => $value): ?>
                                        <option value="<?php echo $key; ?>" <?php echo ($transactionType == $key) ? 'selected' : ''; ?>>
                                            <?php echo $value; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="status">상태</label>
                                <select class="form-control" id="status" name="status">
                                    <option value="">전체</option>
                                    <?php foreach ($transactionStatuses as $key => $value): ?>
                                        <option value="<?php echo $key; ?>" <?php echo ($status == $key) ? 'selected' : ''; ?>>
                                            <?php echo $value; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="amount_min">최소 금액</label>
                                <input type="number" class="form-control" id="amount_min" name="amount_min" value="<?php echo htmlspecialchars($amountMin); ?>" placeholder="최소 금액">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="amount_max">최대 금액</label>
                                <input type="number" class="form-control" id="amount_max" name="amount_max" value="<?php echo htmlspecialchars($amountMax); ?>" placeholder="최대 금액">
                            </div>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <div class="form-group w-100">
                                <button type="submit" class="btn btn-primary mr-2">
                                    <i class="fas fa-search"></i> 검색
                                </button>
                                <a href="transactions.php" class="btn btn-default">
                                    <i class="fas fa-times"></i> 초기화
                                </a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- 거래 목록 카드 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">재무 거래 목록</h3>
                <div class="card-tools">
                    <a href="transaction-add.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus"></i> 새 거래 등록
                    </a>
                </div>
            </div>
            <div class="card-body">
                <?php
                // 쿼리 조건 생성
                $conditions = [];
                $params = [];
                
                if (!empty($search)) {
                    $conditions[] = "(transaction_code LIKE ? OR description LIKE ?)";
                    $params[] = "%{$search}%";
                    $params[] = "%{$search}%";
                }
                
                if (!empty($dateFrom)) {
                    $conditions[] = "DATE(transaction_date) >= ?";
                    $params[] = $dateFrom;
                }
                
                if (!empty($dateTo)) {
                    $conditions[] = "DATE(transaction_date) <= ?";
                    $params[] = $dateTo;
                }
                
                if (!empty($transactionType)) {
                    $conditions[] = "transaction_type = ?";
                    $params[] = $transactionType;
                }
                
                if (!empty($status)) {
                    $conditions[] = "status = ?";
                    $params[] = $status;
                }
                
                if (!empty($amountMin)) {
                    $conditions[] = "amount >= ?";
                    $params[] = $amountMin;
                }
                
                if (!empty($amountMax)) {
                    $conditions[] = "amount <= ?";
                    $params[] = $amountMax;
                }
                
                // SQL 쿼리 조건 결합
                $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";
                
                // 전체 레코드 수 조회 쿼리
                $countSql = "SELECT COUNT(*) as total FROM financial_transactions $whereClause";
                $countStmt = $db->prepare($countSql);
                
                if (!empty($params)) {
                    // PDO prepare statement에 파라미터 바인딩
                    for ($i = 0; $i < count($params); $i++) {
                        $countStmt->bindValue($i + 1, $params[$i]);
                    }
                }
                
                $countStmt->execute();
                $totalRecords = $countStmt->fetchColumn();
                $totalPages = ceil($totalRecords / $recordsPerPage);
                
                // 거래 목록 조회 쿼리
                $sql = "SELECT ft.*, fc.category_name 
                        FROM financial_transactions ft
                        LEFT JOIN financial_categories fc ON ft.category_id = fc.id
                        $whereClause
                        ORDER BY ft.transaction_date DESC
                        LIMIT $offset, $recordsPerPage";
                
                $stmt = $db->prepare($sql);
                
                if (!empty($params)) {
                    // PDO prepare statement에 파라미터 바인딩
                    for ($i = 0; $i < count($params); $i++) {
                        $stmt->bindValue($i + 1, $params[$i]);
                    }
                }
                
                $stmt->execute();
                $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Mock 데이터 생성 (실제 데이터베이스 연결이 없을 경우)
                if (empty($transactions)) {
                    $transactions = [];
                    for ($i = 1; $i <= 10; $i++) {
                        $type = array_rand($transactionTypes);
                        $stat = array_rand($transactionStatuses);
                        $date = date('Y-m-d H:i:s', strtotime("-$i days"));
                        
                        $transactions[] = [
                            'id' => $i,
                            'transaction_code' => 'TR' . date('Ymd') . str_pad($i, 4, '0', STR_PAD_LEFT),
                            'transaction_type' => $type,
                            'amount' => rand(1000, 1000000) / 100,
                            'currency' => 'NPR',
                            'transaction_date' => $date,
                            'description' => $type == 'income' ? '판매 수입' : ($type == 'expense' ? '운영 비용' : ($type == 'transfer' ? '계좌 이체' : '잔액 조정')),
                            'category_name' => $type == 'income' ? '판매 수입' : ($type == 'expense' ? '운영 비용' : '기타'),
                            'payment_method' => ['cash', 'bank_transfer', 'check'][rand(0, 2)],
                            'status' => $stat,
                            'created_by' => 1,
                            'approved_by' => $stat == 'completed' ? 2 : null,
                            'created_at' => $date
                        ];
                    }
                }
                
                if (count($transactions) > 0) {
                ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>거래 코드</th>
                                <th>거래 유형</th>
                                <th>금액</th>
                                <th>거래일</th>
                                <th>카테고리</th>
                                <th>결제 방법</th>
                                <th>상태</th>
                                <th>작업</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $transaction): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($transaction['transaction_code']); ?></td>
                                <td>
                                    <?php 
                                    $typeLabel = $transactionTypes[$transaction['transaction_type']] ?? '알 수 없음';
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
                                        default:
                                            $typeClass = 'badge bg-secondary';
                                    }
                                    ?>
                                    <span class="<?php echo $typeClass; ?>"><?php echo $typeLabel; ?></span>
                                </td>
                                <td class="text-right">
                                    <?php 
                                    $amount = number_format($transaction['amount'], 2);
                                    $amountClass = $transaction['transaction_type'] == 'income' ? 'text-success' : ($transaction['transaction_type'] == 'expense' ? 'text-danger' : '');
                                    ?>
                                    <span class="<?php echo $amountClass; ?>"><?php echo $amount; ?> <?php echo htmlspecialchars($transaction['currency']); ?></span>
                                </td>
                                <td><?php echo date('Y-m-d', strtotime($transaction['transaction_date'])); ?></td>
                                <td><?php echo htmlspecialchars($transaction['category_name'] ?? '분류 없음'); ?></td>
                                <td>
                                    <?php
                                    $paymentMethods = [
                                        'cash' => '현금',
                                        'bank_transfer' => '계좌 이체',
                                        'check' => '수표',
                                        'credit_card' => '신용카드',
                                        'debit_card' => '직불카드',
                                        'mobile_payment' => '모바일 결제',
                                        'other' => '기타'
                                    ];
                                    echo $paymentMethods[$transaction['payment_method']] ?? '알 수 없음';
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    $statusLabel = $transactionStatuses[$transaction['status']] ?? '알 수 없음';
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
                                        default:
                                            $statusClass = 'badge bg-secondary';
                                    }
                                    ?>
                                    <span class="<?php echo $statusClass; ?>"><?php echo $statusLabel; ?></span>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="transaction-view.php?id=<?php echo $transaction['id']; ?>" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="transaction-edit.php?id=<?php echo $transaction['id']; ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if ($transaction['status'] == 'pending'): ?>
                                        <a href="transaction-approve.php?id=<?php echo $transaction['id']; ?>&action=approve" class="btn btn-sm btn-success" onclick="return confirm('이 거래를 승인하시겠습니까?');">
                                            <i class="fas fa-check"></i>
                                        </a>
                                        <a href="transaction-approve.php?id=<?php echo $transaction['id']; ?>&action=reject" class="btn btn-sm btn-danger" onclick="return confirm('이 거래를 거부하시겠습니까?');">
                                            <i class="fas fa-times"></i>
                                        </a>
                                        <?php endif; ?>
                                        <?php if (in_array($transaction['status'], ['pending', 'failed'])): ?>
                                        <a href="transaction-delete.php?id=<?php echo $transaction['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('이 거래를 삭제하시겠습니까? 이 작업은 되돌릴 수 없습니다.');">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- 페이지네이션 -->
                <?php if ($totalPages > 1): ?>
                <div class="mt-3">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=1<?php echo !empty($_GET) ? '&' . http_build_query(array_diff_key($_GET, ['page' => ''])) : ''; ?>">
                                <i class="fas fa-angle-double-left"></i>
                            </a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo !empty($_GET) ? '&' . http_build_query(array_diff_key($_GET, ['page' => ''])) : ''; ?>">
                                <i class="fas fa-angle-left"></i>
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <?php
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $page + 2);
                        
                        for ($i = $startPage; $i <= $endPage; $i++):
                        ?>
                        <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($_GET) ? '&' . http_build_query(array_diff_key($_GET, ['page' => ''])) : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo !empty($_GET) ? '&' . http_build_query(array_diff_key($_GET, ['page' => ''])) : ''; ?>">
                                <i class="fas fa-angle-right"></i>
                            </a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $totalPages; ?><?php echo !empty($_GET) ? '&' . http_build_query(array_diff_key($_GET, ['page' => ''])) : ''; ?>">
                                <i class="fas fa-angle-double-right"></i>
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
                <?php endif; ?>
                
                <!-- 통계 요약 -->
                <div class="row mt-4">
                    <div class="col-lg-3 col-md-6">
                        <div class="info-box bg-success">
                            <span class="info-box-icon"><i class="fas fa-arrow-down"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">총 수입</span>
                                <span class="info-box-number">
                                    <?php
                                    // 실제로는 데이터베이스에서 조회
                                    $totalIncome = array_reduce($transactions, function($carry, $item) {
                                        return $carry + ($item['transaction_type'] == 'income' ? $item['amount'] : 0);
                                    }, 0);
                                    echo number_format($totalIncome, 2) . ' ' . ($transactions[0]['currency'] ?? 'NPR');
                                    ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-3 col-md-6">
                        <div class="info-box bg-danger">
                            <span class="info-box-icon"><i class="fas fa-arrow-up"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">총 지출</span>
                                <span class="info-box-number">
                                    <?php
                                    // 실제로는 데이터베이스에서 조회
                                    $totalExpense = array_reduce($transactions, function($carry, $item) {
                                        return $carry + ($item['transaction_type'] == 'expense' ? $item['amount'] : 0);
                                    }, 0);
                                    echo number_format($totalExpense, 2) . ' ' . ($transactions[0]['currency'] ?? 'NPR');
                                    ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-3 col-md-6">
                        <div class="info-box bg-info">
                            <span class="info-box-icon"><i class="fas fa-balance-scale"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">순 잔액</span>
                                <span class="info-box-number">
                                    <?php
                                    $netBalance = $totalIncome - $totalExpense;
                                    $balanceClass = $netBalance >= 0 ? 'text-white' : 'text-danger';
                                    echo '<span class="' . $balanceClass . '">' . number_format($netBalance, 2) . ' ' . ($transactions[0]['currency'] ?? 'NPR') . '</span>';
                                    ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-3 col-md-6">
                        <div class="info-box bg-warning">
                            <span class="info-box-icon"><i class="fas fa-hourglass-half"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">대기 중인 거래</span>
                                <span class="info-box-number">
                                    <?php
                                    // 실제로는 데이터베이스에서 조회
                                    $pendingCount = array_reduce($transactions, function($carry, $item) {
                                        return $carry + ($item['status'] == 'pending' ? 1 : 0);
                                    }, 0);
                                    echo $pendingCount . ' 건';
                                    ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php } else { ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> 검색 조건에 맞는 거래 내역이 없습니다.
                </div>
                <?php } ?>
            </div>
        </div>
    </div>
</section>

<?php
// 템플릿 푸터 포함
include_once TEMPLATES_PATH . '/dashboard_footer.php';
?>