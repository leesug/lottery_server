<?php
/**
 * 판매 취소/환불 페이지
 * 
 * 이 페이지는 로또 판매 취소 및 환불 처리를 관리하는 기능을 제공합니다.
 * - 판매 취소 처리
 * - 환불 처리
 * - 취소/환불 이력
 */

// 설정 및 공통 함수
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';
require_once '../../includes/db.php';

// 데이터베이스 연결
$db = get_db_connection();

// 현재 페이지 정보
$pageTitle = "판매 취소/환불";
$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir = dirname($_SERVER['PHP_SELF']);
$parts = explode('/', $currentDir);
$currentSection = end($parts);

// 메시지 초기화
$message = '';
$message_type = '';

// 데이터베이스 연결
$conn = get_db_connection();

// 취소/환불 처리 메시지
$message = '';
$message_type = '';

// 티켓 취소 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel') {
    try {
        // CSRF 토큰 검증
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            throw new Exception("보안 토큰이 유효하지 않습니다. 페이지를 새로고침한 후 다시 시도해주세요.");
        }
        
        $ticket_id = intval($_POST['ticket_id'] ?? 0);
        $cancel_reason = $_POST['cancel_reason'] ?? '';
        $cancel_notes = $_POST['cancel_notes'] ?? '';
        
        if ($ticket_id <= 0) {
            throw new Exception("유효하지 않은 티켓 ID입니다.");
        }
        
        if (empty($cancel_reason)) {
            throw new Exception("취소 사유를 선택해주세요.");
        }
        
        // 티켓 정보 가져오기
        $query = "SELECT * FROM tickets WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$ticket_id]);
        $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$ticket) {
            throw new Exception("티켓 정보를 찾을 수 없습니다.");
        }
        
        // 티켓 상태 체크 (취소 가능한 상태인지)
        if ($ticket['status'] !== 'active') {
            throw new Exception("이미 처리된 티켓이므로 취소할 수 없습니다.");
        }
        
        // 티켓 판매 시간으로부터 취소 가능 시간 계산 (기본 30분)
        $ticket_time = new DateTime($ticket['created_at']);
        $current_time = new DateTime();
        $diff = $current_time->diff($ticket_time);
        $minutes_passed = $diff->days * 24 * 60 + $diff->h * 60 + $diff->i;
        
        // 취소 정책에 따른 제한 시간 체크 (관리자/판매 관리자는 예외)
        if ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'sales_manager' && $minutes_passed > 30) {
            throw new Exception("판매 후 30분이 경과하여 취소할 수 없습니다.");
        }
        
        // 트랜잭션 시작
        $conn->beginTransaction();
        
        // 티켓 상태 업데이트
        $update_query = "UPDATE tickets SET status = 'cancelled', updated_at = NOW() WHERE id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->execute([$ticket_id]);
        
        // 취소 이력 기록
        $log_query = "
            INSERT INTO ticket_cancellations
                (ticket_id, cancel_reason, cancel_notes, cancelled_by, cancelled_at)
            VALUES
                (?, ?, ?, ?, NOW())
        ";
        $stmt = $conn->prepare($log_query);
        $stmt->execute([$ticket_id, $cancel_reason, $cancel_notes, $_SESSION['user_id']]);
        
        // 환불 처리 (필요한 경우)
        if (isset($_POST['refund_method']) && !empty($_POST['refund_method'])) {
            $refund_method = $_POST['refund_method'];
            $refund_amount = floatval($ticket['price']);
            $refund_reference = $_POST['refund_reference'] ?? '';
            
            $refund_query = "
                INSERT INTO refunds
                    (ticket_id, refund_amount, refund_method, refund_reference, refunded_by, refunded_at)
                VALUES
                    (?, ?, ?, ?, ?, NOW())
            ";
            $stmt = $conn->prepare($refund_query);
            $stmt->execute([$ticket_id, $refund_amount, $refund_method, $refund_reference, $_SESSION['user_id']]);
        }
        
        // 트랜잭션 커밋
        $conn->commit();
        
        $message = "티켓 취소 및 환불이 성공적으로 처리되었습니다.";
        $message_type = "success";
    } catch (Exception $e) {
        if (isset($conn) && $conn->inTransaction()) {
            $conn->rollBack();
        }
        
        $message = "오류: " . $e->getMessage();
        $message_type = "danger";
    }
}

// CSRF 토큰 생성
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// 필터링을 위한 기본값 설정
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : date('Y-m-d', strtotime('-30 days'));
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : date('Y-m-d');
$ticket_number = isset($_GET['ticket_number']) ? trim($_GET['ticket_number']) : '';
$cancel_reason = isset($_GET['cancel_reason']) ? $_GET['cancel_reason'] : '';

// 페이지네이션 설정
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 50;
$offset = ($page - 1) * $per_page;

// 취소/환불 이력 가져오기
function getCancellationHistory($db, $from_date, $to_date, $ticket_number = '', $cancel_reason = '', $limit = 50, $offset = 0) {
    global $query_type;
    $query_type = 'cancellation_history';
    $query = "
        SELECT 
            tc.id,
            t.ticket_number,
            lp.name as product_name,
            t.price,
            s.store_code,
            s.store_code as store_name,
            tm.terminal_code,
            tc.cancel_reason,
            tc.cancel_notes,
            tc.cancelled_at,
            u.username as cancelled_by_name,
            r.refund_amount,
            r.refund_method,
            r.refund_reference,
            r.refunded_at
        FROM 
            ticket_cancellations tc
        JOIN 
            tickets t ON tc.ticket_id = t.id
        JOIN 
            lottery_products lp ON t.product_id = lp.id
        JOIN 
            terminals tm ON t.terminal_id = tm.id
        JOIN 
            stores s ON tm.store_id = s.id
        LEFT JOIN 
            users u ON tc.cancelled_by = u.id
        LEFT JOIN 
            refunds r ON t.id = r.ticket_id
        WHERE 
            DATE(tc.cancelled_at) BETWEEN ? AND ?
    ";
    
    $params = [$from_date, $to_date];
    
    if (!empty($ticket_number)) {
        $query .= " AND t.ticket_number = ?";
        $params[] = $ticket_number;
    }
    
    if (!empty($cancel_reason)) {
        $query .= " AND tc.cancel_reason = ?";
        $params[] = $cancel_reason;
    }
    
    $query .= "
        ORDER BY 
            tc.cancelled_at DESC
        LIMIT ?, ?
    ";
    
    $params[] = $offset;
    $params[] = $limit;
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// 취소/환불 이력 총 개수 가져오기 (페이지네이션용)
function getCancellationHistoryCount($db, $from_date, $to_date, $ticket_number = '', $cancel_reason = '') {
    global $query_type;
    $query_type = 'count';
    $query = "
        SELECT 
            COUNT(*) as total
        FROM 
            ticket_cancellations tc
        JOIN 
            tickets t ON tc.ticket_id = t.id
        WHERE 
            DATE(tc.cancelled_at) BETWEEN ? AND ?
    ";
    
    $params = [$from_date, $to_date];
    
    if (!empty($ticket_number)) {
        $query .= " AND t.ticket_number = ?";
        $params[] = $ticket_number;
    }
    
    if (!empty($cancel_reason)) {
        $query .= " AND tc.cancel_reason = ?";
        $params[] = $cancel_reason;
    }
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return isset($result['total']) ? $result['total'] : 0;
}

// 티켓 정보 가져오기 (취소 처리용)
function getTicketInfo($db, $ticket_number) {
    global $query_type;
    $query_type = 'ticket_info';
    $query = "
        SELECT 
            t.id,
            t.ticket_number,
            t.numbers,
            t.price,
            t.status,
            t.created_at,
            lp.name as product_name,
            lp.product_code,
            s.store_code as store_name,
            s.store_code,
            r.code as region_name,
            tm.terminal_code
        FROM 
            tickets t
        JOIN 
            lottery_products lp ON t.product_id = lp.id
        JOIN 
            terminals tm ON t.terminal_id = tm.id
        JOIN 
            stores s ON tm.store_id = s.id
        JOIN 
            regions r ON s.region_id = r.id
        WHERE 
            t.ticket_number = ?
    ";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$ticket_number]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// 필요한 데이터 가져오기
$cancellation_history = getCancellationHistory($db, $from_date, $to_date, $ticket_number, $cancel_reason, $per_page, $offset);
$total_records = getCancellationHistoryCount($db, $from_date, $to_date, $ticket_number, $cancel_reason);
$total_pages = ceil($total_records / $per_page);

// 티켓 검색 처리
$ticket_info = null;
$search_error = '';

if (isset($_GET['search_ticket']) && !empty($_GET['search_ticket_number'])) {
    $search_ticket_number = trim($_GET['search_ticket_number']);
    $ticket_info = getTicketInfo($db, $search_ticket_number);
    
    if (!$ticket_info) {
        $search_error = "티켓 번호 '{$search_ticket_number}'에 해당하는 정보를 찾을 수 없습니다.";
    } elseif ($ticket_info['status'] !== 'active') {
        $search_error = "해당 티켓은 이미 '{$ticket_info['status']}' 상태이므로 취소할 수 없습니다.";
    }
}

// 헤더 및 사이드바 포함
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
                    <li class="breadcrumb-item">판매 관리</li>
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
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>

            <!-- 취소/환불 요약 -->
            <div class="row">
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3><?php echo $total_records; ?></h3>
                            <p>총 취소 건수</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-undo-alt"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-success">
                        <div class="inner">
                            <h3><?php 
                                $refunded_count = 0;
                                if (!empty($cancellation_history)) {
                                    foreach ($cancellation_history as $item) {
                                        if (!empty($item['refund_amount'])) $refunded_count++;
                                    }
                                }
                                echo $refunded_count;
                            ?></h3>
                            <p>환불 건수</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-warning">
                        <div class="inner">
                            <h3><?php echo $from_date; ?></h3>
                            <p>시작일</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-calendar-minus"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-danger">
                        <div class="inner">
                            <h3><?php echo $to_date; ?></h3>
                            <p>종료일</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-calendar-plus"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 티켓 검색 및 취소 카드 -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">티켓 취소/환불</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <form action="refund.php" method="GET">
                                <div class="input-group mb-3">
                                    <input type="text" class="form-control" name="search_ticket_number" placeholder="티켓 번호 입력" required>
                                    <div class="input-group-append">
                                        <button type="submit" name="search_ticket" value="1" class="btn btn-primary">
                                            <i class="fas fa-search"></i> 티켓 검색
                                        </button>
                                    </div>
                                </div>
                            </form>
                            
                            <?php if (!empty($search_error)): ?>
                                <div class="alert alert-danger mt-3">
                                    <?php echo $search_error; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($ticket_info && $ticket_info['status'] === 'active'): ?>
                            <div class="col-md-6">
                                <div class="card bg-light">
                                    <div class="card-header">
                                        <h3 class="card-title">검색된 티켓 정보</h3>
                                    </div>
                                    <div class="card-body">
                                        <dl class="row">
                                            <dt class="col-sm-4">티켓 번호:</dt>
                                            <dd class="col-sm-8"><?php echo $ticket_info['ticket_number']; ?></dd>
                                            
                                            <dt class="col-sm-4">복권 상품:</dt>
                                            <dd class="col-sm-8"><?php echo $ticket_info['product_name'] . ' (' . $ticket_info['product_code'] . ')'; ?></dd>
                                            
                                            <dt class="col-sm-4">선택 번호:</dt>
                                            <dd class="col-sm-8"><?php echo $ticket_info['numbers']; ?></dd>
                                            
                                            <dt class="col-sm-4">판매 가격:</dt>
                                            <dd class="col-sm-8"><?php echo number_format($ticket_info['price']); ?> NPR</dd>
                                            
                                            <dt class="col-sm-4">판매점:</dt>
                                            <dd class="col-sm-8"><?php echo $ticket_info['store_name'] . ' (' . $ticket_info['store_code'] . ')'; ?></dd>
                                            
                                            <dt class="col-sm-4">판매일시:</dt>
                                            <dd class="col-sm-8"><?php echo date('Y-m-d H:i:s', strtotime($ticket_info['created_at'])); ?></dd>
                                        </dl>
                                        
                                        <button type="button" class="btn btn-danger btn-block" data-toggle="modal" data-target="#cancelModal">
                                            <i class="fas fa-ban"></i> 티켓 취소 및 환불 처리
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- 취소/환불 이력 필터 카드 -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">취소/환불 이력 필터</h3>
                </div>
                <div class="card-body">
                    <form id="refund-history-filter-form" method="GET">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>시작일:</label>
                                    <input type="date" class="form-control" name="from_date" value="<?php echo $from_date; ?>">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>종료일:</label>
                                    <input type="date" class="form-control" name="to_date" value="<?php echo $to_date; ?>">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>티켓 번호:</label>
                                    <input type="text" class="form-control" name="ticket_number" value="<?php echo $ticket_number; ?>">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>취소 사유:</label>
                                    <select class="form-control" name="cancel_reason">
                                        <option value="">전체 사유</option>
                                        <option value="customer_request" <?php echo ($cancel_reason == 'customer_request') ? 'selected' : ''; ?>>고객 요청</option>
                                        <option value="input_error" <?php echo ($cancel_reason == 'input_error') ? 'selected' : ''; ?>>입력 오류</option>
                                        <option value="system_error" <?php echo ($cancel_reason == 'system_error') ? 'selected' : ''; ?>>시스템 오류</option>
                                        <option value="payment_issue" <?php echo ($cancel_reason == 'payment_issue') ? 'selected' : ''; ?>>결제 문제</option>
                                        <option value="other" <?php echo ($cancel_reason == 'other') ? 'selected' : ''; ?>>기타</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12 text-right">
                                <button type="submit" class="btn btn-primary">적용</button>
                                <button type="button" class="btn btn-default" id="reset-filter">초기화</button>
                                <button type="button" class="btn btn-success" id="export-excel">
                                    <i class="fas fa-file-excel"></i> 엑셀 내보내기
                                </button>
                                <button type="button" class="btn btn-danger" id="export-pdf">
                                    <i class="fas fa-file-pdf"></i> PDF 내보내기
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- 취소/환불 이력 테이블 -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">취소/환불 이력</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>티켓 번호</th>
                                    <th>상품</th>
                                    <th>취소 사유</th>
                                    <th>취소일시</th>
                                    <th>취소자</th>
                                    <th>환불 방법</th>
                                    <th>환불 금액</th>
                                    <th>환불일시</th>
                                    <th>상세</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($cancellation_history)): ?>
                                    <tr>
                                        <td colspan="9" class="text-center">검색 조건에 맞는 취소/환불 이력이 없습니다.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($cancellation_history as $record): ?>
                                        <tr>
                                            <td><?php echo $record['ticket_number']; ?></td>
                                            <td><?php echo $record['product_name']; ?></td>
                                            <td>
                                                <?php 
                                                $reason_text = '';
                                                switch ($record['cancel_reason']) {
                                                    case 'customer_request':
                                                        $reason_text = '고객 요청';
                                                        break;
                                                    case 'input_error':
                                                        $reason_text = '입력 오류';
                                                        break;
                                                    case 'system_error':
                                                        $reason_text = '시스템 오류';
                                                        break;
                                                    case 'payment_issue':
                                                        $reason_text = '결제 문제';
                                                        break;
                                                    default:
                                                        $reason_text = '기타';
                                                }
                                                echo $reason_text;
                                                ?>
                                            </td>
                                            <td><?php echo date('Y-m-d H:i:s', strtotime($record['cancelled_at'])); ?></td>
                                            <td><?php echo htmlspecialchars($record['cancelled_by_name']); ?></td>
                                            <td>
                                                <?php 
                                                if (empty($record['refund_method'])) {
                                                    echo '<span class="badge badge-secondary">환불 없음</span>';
                                                } else {
                                                    $method_text = '';
                                                    switch ($record['refund_method']) {
                                                        case 'cash':
                                                            $method_text = '현금';
                                                            break;
                                                        case 'credit_card':
                                                            $method_text = '신용카드';
                                                            break;
                                                        case 'bank_transfer':
                                                            $method_text = '계좌이체';
                                                            break;
                                                        default:
                                                            $method_text = $record['refund_method'];
                                                    }
                                                    echo $method_text;
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <?php 
                                                if (!empty($record['refund_amount'])) {
                                                    echo number_format($record['refund_amount']) . ' NPR';
                                                } else {
                                                    echo '-';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <?php 
                                                if (!empty($record['refunded_at'])) {
                                                    echo date('Y-m-d H:i:s', strtotime($record['refunded_at']));
                                                } else {
                                                    echo '-';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-info view-cancellation-detail" data-id="<?php echo $record['id']; ?>">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer clearfix">
                    <div class="float-left">
                        <p>전체 <?php echo number_format($total_records ?? 0); ?>건 중 <?php echo ($offset + 1); ?>-<?php echo min($offset + $per_page, $total_records ?? 0); ?>건 표시</p>
                    </div>
                    <ul class="pagination pagination-sm m-0 float-right">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=1<?php echo getQueryString(['page']); ?>">&laquo;</a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo ($page - 1); ?><?php echo getQueryString(['page']); ?>">&lsaquo;</a>
                            </li>
                        <?php endif; ?>
                        
                        <?php
                        // 페이지 범위 계산
                        $start_page = max(1, min($page - 2, $total_pages - 4));
                        $end_page = min($total_pages, max(5, $page + 2));
                        
                        for ($i = $start_page; $i <= $end_page; $i++):
                        ?>
                            <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?><?php echo getQueryString(['page']); ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo ($page + 1); ?><?php echo getQueryString(['page']); ?>">&rsaquo;</a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $total_pages; ?><?php echo getQueryString(['page']); ?>">&raquo;</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 티켓 취소 모달 -->
<?php if ($ticket_info && $ticket_info['status'] === 'active'): ?>
    <div class="modal fade" id="cancelModal" tabindex="-1" role="dialog" aria-labelledby="cancelModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="cancelModalLabel">티켓 취소 및 환불 처리</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="refund.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="action" value="cancel">
                    <input type="hidden" name="ticket_id" value="<?php echo $ticket_info['id']; ?>">
                    
                    <div class="modal-body">
                        <p>
                            티켓 번호 <strong><?php echo $ticket_info['ticket_number']; ?></strong>의 취소를 처리합니다.<br>
                            아래 정보를 확인하고 취소 사유와 환불 정보를 입력해주세요.
                        </p>
                        
                        <div class="form-group">
                            <label for="cancel_reason">취소 사유 <span class="text-danger">*</span></label>
                            <select class="form-control" id="cancel_reason" name="cancel_reason" required>
                                <option value="">취소 사유 선택</option>
                                <option value="customer_request">고객 요청</option>
                                <option value="input_error">입력 오류</option>
                                <option value="system_error">시스템 오류</option>
                                <option value="payment_issue">결제 문제</option>
                                <option value="other">기타</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="cancel_notes">취소 상세 내용</label>
                            <textarea class="form-control" id="cancel_notes" name="cancel_notes" rows="2"></textarea>
                        </div>
                        
                        <hr>
                        
                        <div class="form-group">
                            <label>환불 처리</label>
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="refund_check" checked>
                                <label class="custom-control-label" for="refund_check">환불 처리 포함</label>
                            </div>
                        </div>
                        
                        <div id="refund_details">
                            <div class="form-group">
                                <label for="refund_method">환불 방법 <span class="text-danger">*</span></label>
                                <select class="form-control" id="refund_method" name="refund_method" required>
                                    <option value="">환불 방법 선택</option>
                                    <option value="cash">현금</option>
                                    <option value="credit_card">신용카드</option>
                                    <option value="bank_transfer">계좌이체</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="refund_reference">환불 참조 번호</label>
                                <input type="text" class="form-control" id="refund_reference" name="refund_reference" placeholder="결제 취소 번호, 계좌이체 번호 등">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">취소</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-ban"></i> 티켓 취소 처리
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- 취소 상세 모달 -->
<div class="modal fade" id="cancellationDetailModal" tabindex="-1" role="dialog" aria-labelledby="cancellationDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cancellationDetailModalLabel">취소/환불 상세 정보</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                </div>
                <div id="cancellationDetailContent" style="display: none;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">닫기</button>
                <button type="button" class="btn btn-primary" id="print-detail">인쇄</button>
            </div>
        </div>
    </div>
</div>

<?php include_once '../../templates/footer.php'; ?>

<script>
$(function() {
    // 환불 체크박스 처리
    $('#refund_check').change(function() {
        if ($(this).is(':checked')) {
            $('#refund_details').show();
            $('#refund_method').prop('required', true);
        } else {
            $('#refund_details').hide();
            $('#refund_method').prop('required', false);
        }
    });
    
    // 필터 초기화 버튼
    $('#reset-filter').click(function() {
        window.location.href = 'refund.php';
    });
    
    // 취소/환불 상세 모달
    $('.view-cancellation-detail').click(function() {
        const id = $(this).data('id');
        
        // 모달 내용 초기화 및 로딩 표시
        $('#cancellationDetailContent').hide();
        $('.spinner-border').show();
        $('#cancellationDetailModal').modal('show');
        
        // AJAX로 상세 정보 가져오기
        $.ajax({
            url: '/api/cancellation/detail.php',
            type: 'GET',
            data: { id: id },
            success: function(response) {
                $('.spinner-border').hide();
                $('#cancellationDetailContent').html(response).show();
            },
            error: function() {
                $('.spinner-border').hide();
                $('#cancellationDetailContent').html('<div class="alert alert-danger">정보를 가져오는 중 오류가 발생했습니다.</div>').show();
            }
        });
    });
    
    // 상세 정보 인쇄 버튼
    $('#print-detail').click(function() {
        const printContents = document.getElementById('cancellationDetailContent').innerHTML;
        const originalContents = document.body.innerHTML;
        
        document.body.innerHTML = printContents;
        window.print();
        document.body.innerHTML = originalContents;
        
        // 페이지 새로고침
        location.reload();
    });
    
    // 엑셀 내보내기 버튼
    $('#export-excel').click(function() {
        const queryString = $('#refund-history-filter-form').serialize();
        window.location.href = `/api/export/cancellation_history_excel.php?${queryString}`;
    });
    
    // PDF 내보내기 버튼
    $('#export-pdf').click(function() {
        const queryString = $('#refund-history-filter-form').serialize();
        window.location.href = `/api/export/cancellation_history_pdf.php?${queryString}`;
    });
    
    // 테이블에 DataTable 적용
    $('table').DataTable({
        "paging": false,
        "lengthChange": false,
        "searching": true,
        "ordering": true,
        "info": true,
        "autoWidth": false,
        "responsive": true,
        "language": {
            "url": "/assets/js/Korean.json"
        }
    });
});

<?php
// 쿼리 문자열 생성 함수
function getQueryString($excludeParams = []) {
    $params = [];
    foreach ($_GET as $key => $value) {
        if (!in_array($key, $excludeParams) && $value !== '') {
            $params[] = urlencode($key) . '=' . urlencode($value);
        }
    }
    
    return !empty($params) ? '&' . implode('&', $params) : '';
}
?>
</script>

    </div>
</section>
<!-- /.content -->

<?php
// 템플릿 푸터 포함
include_once TEMPLATES_PATH . '/dashboard_footer.php';
?>
