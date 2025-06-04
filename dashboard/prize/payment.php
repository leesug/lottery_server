<?php
/**
 * 당첨금 지급 페이지
 * 
 * 이 페이지는 복권 당첨금 지급 및 관리 기능을 제공합니다.
 * - 당첨자 조회
 * - 당첨금 지급 처리
 * - 당첨금 지급 이력 관리
 */

// 필요한 파일 포함
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// 페이지 제목 설정
$pageTitle = "당첨금 지급";
$currentSection = "prize";
$currentPage = "payment.php";

// 데이터베이스 연결
$conn = getDBConnection();

// 메시지 처리
$message = '';
$message_type = '';

// 티켓 번호 검색 처리
$search_ticket = '';
$ticket_info = null;
if (isset($_GET['ticket']) && !empty($_GET['ticket'])) {
    $search_ticket = trim($_GET['ticket']);
    $ticket_info = getTicketInfo($conn, $search_ticket);
    
    if (!$ticket_info) {
        $message = "티켓 번호 '{$search_ticket}'을(를) 찾을 수 없습니다.";
        $message_type = "warning";
    }
}

// 당첨금 지급 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        // CSRF 토큰 검증
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            throw new Exception("보안 토큰이 유효하지 않습니다. 페이지를 새로고침한 후 다시 시도해주세요.");
        }
        
        // 당첨금 지급 처리
        if ($_POST['action'] === 'process_payment') {
            $ticket_number = $_POST['ticket_number'];
            $winning_id = intval($_POST['winning_id']);
            $payment_method = $_POST['payment_method'];
            $payment_reference = $_POST['payment_reference'];
            $notes = $_POST['notes'];
            
            // 티켓 정보 확인
            $ticket_info = getTicketInfo($conn, $ticket_number);
            
            if (!$ticket_info) {
                throw new Exception("유효하지 않은 티켓 번호입니다.");
            }
            
            // 당첨 정보 확인
            $winning_info = getWinningInfo($conn, $winning_id);
            
            if (!$winning_info) {
                throw new Exception("유효하지 않은 당첨 정보입니다.");
            }
            
            // 이미 지급된 경우
            if ($winning_info['status'] === 'paid') {
                throw new Exception("이 당첨금은 이미 지급되었습니다.");
            }
            
            // 지급 가능 상태가 아닌 경우
            if ($winning_info['status'] !== 'claimed') {
                throw new Exception("당첨금 지급을 진행할 수 없습니다. 당첨 확인 절차가 필요합니다.");
            }
            
            // 트랜잭션 시작
            $conn->beginTransaction();
            
            // 당첨금 지급 상태 업데이트
            $update_query = "
                UPDATE winnings
                SET
                    status = 'paid',
                    payment_method = ?,
                    payment_reference = ?,
                    notes = ?,
                    paid_at = NOW(),
                    paid_by = ?,
                    updated_at = NOW()
                WHERE
                    id = ?
            ";
            
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->execute([
                $payment_method,
                $payment_reference,
                $notes,
                $_SESSION['user_id'],
                $winning_id
            ]);
            
            // 트랜잭션 커밋
            $conn->commit();
            
            $message = "당첨금 지급이 성공적으로 처리되었습니다.";
            $message_type = "success";
            
            // 페이지 새로고침
            header("Location: payment.php?ticket={$ticket_number}&paid=1");
            exit;
        }
        // 당첨 확인 처리
        else if ($_POST['action'] === 'confirm_winning') {
            $ticket_number = $_POST['ticket_number'];
            $winning_id = intval($_POST['winning_id']);
            $customer_name = $_POST['customer_name'];
            $customer_contact = $_POST['customer_contact'];
            $customer_id_type = $_POST['customer_id_type'];
            $customer_id_number = $_POST['customer_id_number'];
            
            // 티켓 정보 확인
            $ticket_info = getTicketInfo($conn, $ticket_number);
            
            if (!$ticket_info) {
                throw new Exception("유효하지 않은 티켓 번호입니다.");
            }
            
            // 당첨 정보 확인
            $winning_info = getWinningInfo($conn, $winning_id);
            
            if (!$winning_info) {
                throw new Exception("유효하지 않은 당첨 정보입니다.");
            }
            
            // 이미 확인된 경우
            if ($winning_info['status'] !== 'pending') {
                throw new Exception("이 당첨금은 이미 확인 처리되었습니다.");
            }
            
            // 트랜잭션 시작
            $conn->beginTransaction();
            
            // 고객 정보 저장/업데이트
            if ($ticket_info['customer_id']) {
                // 기존 고객 정보 업데이트
                $customer_query = "
                    UPDATE customers
                    SET
                        first_name = ?,
                        phone = ?,
                        id_type = ?,
                        id_number = ?,
                        updated_at = NOW()
                    WHERE
                        id = ?
                ";
                
                $customer_stmt = $conn->prepare($customer_query);
                $customer_stmt->execute([
                    $customer_name,
                    $customer_contact,
                    $customer_id_type,
                    $customer_id_number,
                    $ticket_info['customer_id']
                ]);
                
                $customer_id = $ticket_info['customer_id'];
            } else {
                // 새 고객 정보 저장
                $customer_query = "
                    INSERT INTO customers
                        (first_name, phone, id_type, id_number, created_at)
                    VALUES
                        (?, ?, ?, ?, NOW())
                ";
                
                $customer_stmt = $conn->prepare($customer_query);
                $customer_stmt->execute([
                    $customer_name,
                    $customer_contact,
                    $customer_id_type,
                    $customer_id_number
                ]);
                
                $customer_id = $conn->lastInsertId();
                
                // 티켓에 고객 정보 연결
                $ticket_update_query = "
                    UPDATE tickets
                    SET
                        customer_id = ?
                    WHERE
                        id = ?
                ";
                
                $ticket_update_stmt = $conn->prepare($ticket_update_query);
                $ticket_update_stmt->execute([
                    $customer_id,
                    $ticket_info['id']
                ]);
            }
            
            // 당첨금 확인 상태 업데이트
            $update_query = "
                UPDATE winnings
                SET
                    status = 'claimed',
                    claimed_at = NOW(),
                    claimed_by = ?,
                    customer_info = ?,
                    updated_at = NOW()
                WHERE
                    id = ?
            ";
            
            $customer_info = json_encode([
                'name' => $customer_name,
                'contact' => $customer_contact,
                'id_type' => $customer_id_type,
                'id_number' => $customer_id_number
            ]);
            
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->execute([
                $_SESSION['user_id'],
                $customer_info,
                $winning_id
            ]);
            
            // 트랜잭션 커밋
            $conn->commit();
            
            $message = "당첨 확인이 성공적으로 처리되었습니다.";
            $message_type = "success";
            
            // 페이지 새로고침
            header("Location: payment.php?ticket={$ticket_number}&claimed=1");
            exit;
        }
    } catch (Exception $e) {
        if (isset($conn) && $conn->inTransaction()) {
            $conn->rollBack();
        }
        
        $message = "오류: " . $e->getMessage();
        $message_type = "danger";
    }
}

// URL 파라미터에 따른 메시지 처리
if (isset($_GET['claimed']) && $_GET['claimed'] == 1) {
    $message = "당첨 확인이 성공적으로 처리되었습니다.";
    $message_type = "success";
} else if (isset($_GET['paid']) && $_GET['paid'] == 1) {
    $message = "당첨금 지급이 성공적으로 처리되었습니다.";
    $message_type = "success";
}

// CSRF 토큰 생성
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// 티켓 정보 가져오기
function getTicketInfo($conn, $ticket_number) {
    $query = "
        SELECT 
            t.*,
            p.name as product_name,
            p.product_code,
            d.draw_code as draw_number,
            d.draw_date,
            d.winning_numbers
        FROM 
            tickets t
        JOIN 
            lottery_products p ON t.product_id = p.id
        JOIN 
            draws d ON p.id = d.product_id
        WHERE 
            t.ticket_number = ?
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$ticket_number]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// 당첨 정보 가져오기
function getWinningInfo($conn, $winning_id) {
    $query = "
        SELECT 
            w.*,
            u1.username as claimed_by_name,
            u2.username as paid_by_name
        FROM 
            winnings w
        LEFT JOIN 
            users u1 ON w.claimed_by = u1.id
        LEFT JOIN 
            users u2 ON w.paid_by = u2.id
        WHERE 
            w.id = ?
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$winning_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// 티켓의 당첨 정보 가져오기
function getTicketWinnings($conn, $ticket_id) {
    $query = "
        SELECT 
            w.*,
            u1.username as claimed_by_name,
            u2.username as paid_by_name
        FROM 
            winnings w
        LEFT JOIN 
            users u1 ON w.claimed_by = u1.id
        LEFT JOIN 
            users u2 ON w.paid_by = u2.id
        WHERE 
            w.ticket_id = ?
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$ticket_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// 당첨금 지급 이력 가져오기
function getPaymentHistory($conn, $limit = 10) {
    $query = "
        SELECT 
            w.*,
            t.ticket_number,
            p.name as product_name,
            d.draw_code as draw_number,
            u1.username as claimed_by_name,
            u2.username as paid_by_name
        FROM 
            winnings w
        JOIN 
            tickets t ON w.ticket_id = t.id
        JOIN 
            lottery_products p ON t.product_id = p.id
        JOIN 
            draws d ON p.id = d.product_id 
        LEFT JOIN 
            users u1 ON w.claimed_by = u1.id
        LEFT JOIN 
            users u2 ON w.paid_by = u2.id
        WHERE
            w.status = 'paid'
        ORDER BY 
            w.paid_at DESC
        LIMIT ?
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$limit]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// 미지급 당첨금 목록 가져오기
function getPendingPayments($conn, $limit = 10) {
    $query = "
        SELECT 
            w.*,
            t.ticket_number,
            p.name as product_name,
            d.draw_code as draw_number,
            d.draw_date
        FROM 
            winnings w
        JOIN 
            tickets t ON w.ticket_id = t.id
        JOIN 
            lottery_products p ON t.product_id = p.id
        JOIN 
            draws d ON p.id = d.product_id
        WHERE 
            w.status = 'claimed'
        ORDER BY 
            w.claimed_at ASC
        LIMIT ?
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$limit]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// 필요한 데이터 가져오기
$payment_history = getPaymentHistory($conn);
$pending_payments = getPendingPayments($conn);

// 티켓 당첨 정보
$ticket_winnings = $ticket_info ? getTicketWinnings($conn, $ticket_info['id']) : [];

// 템플릿 헤더 포함
include_once TEMPLATES_PATH . '/dashboard_header.php';
include_once TEMPLATES_PATH . '/page_header.php';
?>

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
            
            <!-- 티켓 검색 -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">당첨 티켓 검색</h3>
                </div>
                <div class="card-body">
                    <form method="GET" action="">
                        <div class="input-group">
                            <input type="text" class="form-control" name="ticket" placeholder="티켓 번호 입력" value="<?php echo htmlspecialchars($search_ticket); ?>" required>
                            <div class="input-group-append">
                                <button class="btn btn-primary" type="submit">검색</button>
                            </div>
                        </div>
                        <small class="form-text text-muted">정확한 티켓 번호를 입력하세요.</small>
                    </form>
                </div>
            </div>
            
            <!-- 티켓 정보 -->
            <?php if ($ticket_info): ?>
                <div class="card">
                    <div class="card-header bg-info">
                        <h3 class="card-title">티켓 정보</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-bordered">
                                    <tr>
                                        <th>티켓 번호</th>
                                        <td><?php echo htmlspecialchars($ticket_info['ticket_number']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>복권 상품</th>
                                        <td><?php echo htmlspecialchars($ticket_info['product_name']); ?> (<?php echo htmlspecialchars($ticket_info['product_code']); ?>)</td>
                                    </tr>
                                    <tr>
                                        <th>회차</th>
                                        <td><?php echo htmlspecialchars($ticket_info['draw_number']); ?>회 (<?php echo date('Y-m-d H:i', strtotime($ticket_info['draw_date'])); ?>)</td>
                                    </tr>
                                    <tr>
                                        <th>선택 번호</th>
                                        <td><strong><?php echo htmlspecialchars($ticket_info['numbers']); ?></strong></td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-bordered">
                                    <tr>
                                        <th>당첨 번호</th>
                                        <td><strong><?php echo htmlspecialchars($ticket_info['winning_numbers']); ?></strong></td>
                                    </tr>
                                    <tr>
                                        <th>티켓 상태</th>
                                        <td>
                                            <?php
                                            $status_class = '';
                                            $status_text = '';
                                            
                                            switch ($ticket_info['status']) {
                                                case 'active':
                                                    $status_class = 'badge badge-info';
                                                    $status_text = '활성';
                                                    break;
                                                case 'won':
                                                    $status_class = 'badge badge-success';
                                                    $status_text = '당첨';
                                                    break;
                                                case 'lost':
                                                    $status_class = 'badge badge-secondary';
                                                    $status_text = '미당첨';
                                                    break;
                                                case 'cancelled':
                                                    $status_class = 'badge badge-danger';
                                                    $status_text = '취소됨';
                                                    break;
                                                default:
                                                    $status_class = 'badge badge-dark';
                                                    $status_text = '알 수 없음';
                                            }
                                            ?>
                                            <span class="<?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>구매 금액</th>
                                        <td><?php echo number_format($ticket_info['price']); ?> NPR</td>
                                    </tr>
                                    <tr>
                                        <th>구매 일시</th>
                                        <td><?php echo date('Y-m-d H:i:s', strtotime($ticket_info['created_at'])); ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        
                        <?php if (!empty($ticket_winnings)): ?>
                            <h4 class="mt-4">당첨 내역</h4>
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr class="bg-light">
                                        <th>등수</th>
                                        <th>당첨금</th>
                                        <th>상태</th>
                                        <th>확인 일시</th>
                                        <th>지급 일시</th>
                                        <th>처리</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($ticket_winnings as $winning): ?>
                                        <tr>
                                            <td><?php echo $winning['prize_tier']; ?>등</td>
                                            <td><?php echo number_format($winning['prize_amount']); ?> NPR</td>
                                            <td>
                                                <?php
                                                $winning_status_class = '';
                                                $winning_status_text = '';
                                                
                                                switch ($winning['status']) {
                                                    case 'pending':
                                                        $winning_status_class = 'badge badge-warning';
                                                        $winning_status_text = '미확인';
                                                        break;
                                                    case 'claimed':
                                                        $winning_status_class = 'badge badge-info';
                                                        $winning_status_text = '확인됨';
                                                        break;
                                                    case 'paid':
                                                        $winning_status_class = 'badge badge-success';
                                                        $winning_status_text = '지급완료';
                                                        break;
                                                    default:
                                                        $winning_status_class = 'badge badge-dark';
                                                        $winning_status_text = '알 수 없음';
                                                }
                                                ?>
                                                <span class="<?php echo $winning_status_class; ?>"><?php echo $winning_status_text; ?></span>
                                            </td>
                                            <td>
                                                <?php if ($winning['claimed_at']): ?>
                                                    <?php echo date('Y-m-d H:i:s', strtotime($winning['claimed_at'])); ?><br>
                                                    <small>처리자: <?php echo htmlspecialchars($winning['claimed_by_name']); ?></small>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($winning['paid_at']): ?>
                                                    <?php echo date('Y-m-d H:i:s', strtotime($winning['paid_at'])); ?><br>
                                                    <small>처리자: <?php echo htmlspecialchars($winning['paid_by_name']); ?></small>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($winning['status'] === 'pending'): ?>
                                                    <button type="button" class="btn btn-sm btn-info" data-toggle="modal" data-target="#confirmWinningModal" 
                                                            data-winning-id="<?php echo $winning['id']; ?>" 
                                                            data-ticket-number="<?php echo $ticket_info['ticket_number']; ?>"
                                                            data-prize-tier="<?php echo $winning['prize_tier']; ?>"
                                                            data-prize-amount="<?php echo number_format($winning['prize_amount']); ?>">
                                                        당첨 확인
                                                    </button>
                                                <?php elseif ($winning['status'] === 'claimed'): ?>
                                                    <button type="button" class="btn btn-sm btn-success" data-toggle="modal" data-target="#processPaymentModal"
                                                            data-winning-id="<?php echo $winning['id']; ?>"
                                                            data-ticket-number="<?php echo $ticket_info['ticket_number']; ?>"
                                                            data-prize-tier="<?php echo $winning['prize_tier']; ?>"
                                                            data-prize-amount="<?php echo number_format($winning['prize_amount']); ?>">
                                                        지급 처리
                                                    </button>
                                                <?php elseif ($winning['status'] === 'paid'): ?>
                                                    <button type="button" class="btn btn-sm btn-secondary" data-toggle="modal" data-target="#paymentInfoModal"
                                                            data-winning-id="<?php echo $winning['id']; ?>"
                                                            data-ticket-number="<?php echo $ticket_info['ticket_number']; ?>"
                                                            data-prize-tier="<?php echo $winning['prize_tier']; ?>"
                                                            data-prize-amount="<?php echo number_format($winning['prize_amount']); ?>"
                                                            data-payment-method="<?php echo htmlspecialchars($winning['payment_method']); ?>"
                                                            data-payment-reference="<?php echo htmlspecialchars($winning['payment_reference']); ?>"
                                                            data-paid-at="<?php echo date('Y-m-d H:i:s', strtotime($winning['paid_at'])); ?>"
                                                            data-paid-by="<?php echo htmlspecialchars($winning['paid_by_name']); ?>"
                                                            data-notes="<?php echo htmlspecialchars($winning['notes']); ?>">
                                                        지급 정보
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <?php if ($ticket_info['status'] === 'lost'): ?>
                                <div class="alert alert-warning mt-3">
                                    이 티켓은 당첨되지 않았습니다.
                                </div>
                            <?php elseif ($ticket_info['status'] === 'active'): ?>
                                <div class="alert alert-info mt-3">
                                    이 티켓은 아직 추첨되지 않았습니다.
                                </div>
                            <?php elseif ($ticket_info['status'] === 'cancelled'): ?>
                                <div class="alert alert-danger mt-3">
                                    이 티켓은 취소되었습니다.
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- 미지급 당첨금 목록 -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">미지급 당첨금 목록</h3>
                </div>
                <div class="card-body">
                    <?php if (!empty($pending_payments)): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr class="bg-light">
                                        <th>티켓 번호</th>
                                        <th>복권 상품</th>
                                        <th>회차</th>
                                        <th>등수</th>
                                        <th>당첨금</th>
                                        <th>확인 일시</th>
                                        <th>처리</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pending_payments as $payment): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($payment['ticket_number']); ?></td>
                                            <td><?php echo htmlspecialchars($payment['product_name']); ?></td>
                                            <td><?php echo $payment['draw_number']; ?>회</td>
                                            <td><?php echo $payment['prize_tier']; ?>등</td>
                                            <td><?php echo number_format($payment['prize_amount']); ?> NPR</td>
                                            <td><?php echo date('Y-m-d H:i:s', strtotime($payment['claimed_at'])); ?></td>
                                            <td>
                                                <a href="payment.php?ticket=<?php echo urlencode($payment['ticket_number']); ?>" class="btn btn-sm btn-primary">
                                                    처리하기
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            현재 미지급 상태인 당첨금이 없습니다.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- 최근 지급 이력 -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">최근 지급 이력</h3>
                </div>
                <div class="card-body">
                    <?php if (!empty($payment_history)): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr class="bg-light">
                                        <th>지급 일시</th>
                                        <th>티켓 번호</th>
                                        <th>복권 상품</th>
                                        <th>회차</th>
                                        <th>등수</th>
                                        <th>지급 금액</th>
                                        <th>지급 방법</th>
                                        <th>처리자</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($payment_history as $history): ?>
                                        <tr>
                                            <td><?php echo date('Y-m-d H:i:s', strtotime($history['paid_at'])); ?></td>
                                            <td><?php echo htmlspecialchars($history['ticket_number']); ?></td>
                                            <td><?php echo htmlspecialchars($history['product_name']); ?></td>
                                            <td><?php echo $history['draw_number']; ?>회</td>
                                            <td><?php echo $history['prize_tier']; ?>등</td>
                                            <td><?php echo number_format($history['prize_amount']); ?> NPR</td>
                                            <td><?php echo htmlspecialchars($history['payment_method']); ?></td>
                                            <td><?php echo htmlspecialchars($history['paid_by_name']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            아직 지급 이력이 없습니다.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 당첨 확인 모달 -->
<div class="modal fade" id="confirmWinningModal" tabindex="-1" role="dialog" aria-labelledby="confirmWinningModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info">
                <h5 class="modal-title" id="confirmWinningModalLabel">당첨 확인</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="action" value="confirm_winning">
                <input type="hidden" name="winning_id" id="confirm_winning_id">
                <input type="hidden" name="ticket_number" id="confirm_ticket_number">
                
                <div class="modal-body">
                    <div class="alert alert-info">
                        <span id="confirm_prize_info"></span>
                    </div>
                    
                    <div class="form-group">
                        <label for="customer_name">당첨자 이름</label>
                        <input type="text" class="form-control" id="customer_name" name="customer_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="customer_contact">연락처</label>
                        <input type="text" class="form-control" id="customer_contact" name="customer_contact" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="customer_id_type">신분증 종류</label>
                        <select class="form-control" id="customer_id_type" name="customer_id_type" required>
                            <option value="citizenship">시민권(Citizenship)</option>
                            <option value="passport">여권(Passport)</option>
                            <option value="driving_license">운전면허증(Driving License)</option>
                            <option value="voter_id">유권자 ID(Voter ID)</option>
                            <option value="other">기타</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="customer_id_number">신분증 번호</label>
                        <input type="text" class="form-control" id="customer_id_number" name="customer_id_number" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">취소</button>
                    <button type="submit" class="btn btn-info">당첨 확인 완료</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 당첨금 지급 모달 -->
<div class="modal fade" id="processPaymentModal" tabindex="-1" role="dialog" aria-labelledby="processPaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success">
                <h5 class="modal-title" id="processPaymentModalLabel">당첨금 지급 처리</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="action" value="process_payment">
                <input type="hidden" name="winning_id" id="payment_winning_id">
                <input type="hidden" name="ticket_number" id="payment_ticket_number">
                
                <div class="modal-body">
                    <div class="alert alert-success">
                        <span id="payment_prize_info"></span>
                    </div>
                    
                    <div class="form-group">
                        <label for="payment_method">지급 방법</label>
                        <select class="form-control" id="payment_method" name="payment_method" required>
                            <option value="cash">현금</option>
                            <option value="bank_transfer">계좌이체</option>
                            <option value="cheque">수표</option>
                            <option value="mobile_wallet">모바일 지갑</option>
                            <option value="other">기타</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="payment_reference">지급 참조번호</label>
                        <input type="text" class="form-control" id="payment_reference" name="payment_reference" placeholder="계좌이체 번호, 수표 번호 등">
                    </div>
                    
                    <div class="form-group">
                        <label for="notes">메모</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">취소</button>
                    <button type="submit" class="btn btn-success">지급 처리 완료</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 지급 정보 모달 -->
<div class="modal fade" id="paymentInfoModal" tabindex="-1" role="dialog" aria-labelledby="paymentInfoModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-secondary">
                <h5 class="modal-title" id="paymentInfoModalLabel">당첨금 지급 정보</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <table class="table table-bordered">
                    <tr>
                        <th>티켓 번호</th>
                        <td id="info_ticket_number"></td>
                    </tr>
                    <tr>
                        <th>당첨 등수</th>
                        <td id="info_prize_tier"></td>
                    </tr>
                    <tr>
                        <th>당첨금</th>
                        <td id="info_prize_amount"></td>
                    </tr>
                    <tr>
                        <th>지급 방법</th>
                        <td id="info_payment_method"></td>
                    </tr>
                    <tr>
                        <th>지급 참조번호</th>
                        <td id="info_payment_reference"></td>
                    </tr>
                    <tr>
                        <th>지급 일시</th>
                        <td id="info_paid_at"></td>
                    </tr>
                    <tr>
                        <th>처리자</th>
                        <td id="info_paid_by"></td>
                    </tr>
                    <tr>
                        <th>메모</th>
                        <td id="info_notes"></td>
                    </tr>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">닫기</button>
                <button type="button" class="btn btn-primary" id="btnPrintPayment">인쇄</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 당첨 확인 모달 데이터 설정
    $('#confirmWinningModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var winningId = button.data('winning-id');
        var ticketNumber = button.data('ticket-number');
        var prizeTier = button.data('prize-tier');
        var prizeAmount = button.data('prize-amount');
        
        var modal = $(this);
        modal.find('#confirm_winning_id').val(winningId);
        modal.find('#confirm_ticket_number').val(ticketNumber);
        modal.find('#confirm_prize_info').html(`티켓 번호 <strong>${ticketNumber}</strong>의 <strong>${prizeTier}등</strong> 당첨금 <strong>${prizeAmount} NPR</strong>에 대한 당첨자 정보를 입력하세요.`);
    });
    
    // 당첨금 지급 모달 데이터 설정
    $('#processPaymentModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var winningId = button.data('winning-id');
        var ticketNumber = button.data('ticket-number');
        var prizeTier = button.data('prize-tier');
        var prizeAmount = button.data('prize-amount');
        
        var modal = $(this);
        modal.find('#payment_winning_id').val(winningId);
        modal.find('#payment_ticket_number').val(ticketNumber);
        modal.find('#payment_prize_info').html(`티켓 번호 <strong>${ticketNumber}</strong>의 <strong>${prizeTier}등</strong> 당첨금 <strong>${prizeAmount} NPR</strong>에 대한 지급 정보를 입력하세요.`);
    });
    
    // 지급 정보 모달 데이터 설정
    $('#paymentInfoModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var ticketNumber = button.data('ticket-number');
        var prizeTier = button.data('prize-tier');
        var prizeAmount = button.data('prize-amount');
        var paymentMethod = button.data('payment-method');
        var paymentReference = button.data('payment-reference');
        var paidAt = button.data('paid-at');
        var paidBy = button.data('paid-by');
        var notes = button.data('notes');
        
        var modal = $(this);
        modal.find('#info_ticket_number').text(ticketNumber);
        modal.find('#info_prize_tier').text(prizeTier + '등');
        modal.find('#info_prize_amount').text(prizeAmount + ' NPR');
        modal.find('#info_payment_method').text(paymentMethod);
        modal.find('#info_payment_reference').text(paymentReference || '-');
        modal.find('#info_paid_at').text(paidAt);
        modal.find('#info_paid_by').text(paidBy);
        modal.find('#info_notes').text(notes || '-');
    });
    
    // 인쇄 버튼 클릭 이벤트
    $('#btnPrintPayment').on('click', function() {
        window.print();
    });
});
</script>

    </div>
</section>
<!-- /.content -->

<?php include_once TEMPLATES_PATH . '/dashboard_footer.php'; ?>
