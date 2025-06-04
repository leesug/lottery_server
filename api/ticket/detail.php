<?php
/**
 * 티켓 상세 정보 API
 * 
 * 복권 티켓의 상세 정보를 HTML 형식으로 제공하는 API
 * 
 * @param int ticket_id 티켓 ID
 * @return string HTML 형식의 티켓 상세 정보
 */

// 세션 시작 및 로그인 체크
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    http_response_code(401);
    exit(json_encode([
        'status' => 'error',
        'message' => '인증되지 않은 접근입니다.'
    ]));
}

// 필요한 파일 포함
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// 입력 파라미터 검증
$ticket_id = isset($_GET['ticket_id']) ? intval($_GET['ticket_id']) : 0;

if ($ticket_id <= 0) {
    http_response_code(400);
    exit(json_encode([
        'status' => 'error',
        'message' => '유효하지 않은 티켓 ID입니다.'
    ]));
}

// 데이터베이스 연결
$conn = getDBConnection();

// 티켓 상세 정보 가져오기
function getTicketDetails($conn, $ticket_id) {
    $query = "
        SELECT 
            t.id,
            t.ticket_number,
            t.numbers,
            t.price,
            t.status,
            t.created_at,
            lp.id as product_id,
            lp.name as product_name,
            lp.product_code,
            lp.draw_schedule,
            ld.id as draw_id,
            ld.draw_number,
            ld.draw_date,
            ld.draw_status,
            ld.winning_numbers,
            s.id as store_id,
            s.name as store_name,
            s.store_code,
            r.name as region_name,
            tm.id as terminal_id,
            tm.terminal_code,
            c.id as customer_id,
            CONCAT(c.first_name, ' ', c.last_name) as customer_name,
            c.phone as customer_phone,
            c.email as customer_email
        FROM 
            tickets t
        JOIN 
            lottery_products lp ON t.product_id = lp.id
        JOIN 
            lottery_draws ld ON t.draw_id = ld.id
        JOIN 
            terminals tm ON t.terminal_id = tm.id
        JOIN 
            stores s ON tm.store_id = s.id
        LEFT JOIN 
            regions r ON s.region_id = r.id
        LEFT JOIN 
            customers c ON t.customer_id = c.id
        WHERE 
            t.id = ?
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$ticket_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// 티켓 당첨 정보 가져오기
function getTicketWinning($conn, $ticket_id) {
    $query = "
        SELECT 
            w.id,
            w.prize_tier,
            w.prize_amount,
            w.status,
            w.claimed_at,
            w.paid_at,
            w.payment_method,
            w.payment_reference
        FROM 
            winnings w
        WHERE 
            w.ticket_id = ?
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$ticket_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// 필요한 데이터 가져오기
$ticket = getTicketDetails($conn, $ticket_id);
if (!$ticket) {
    http_response_code(404);
    exit(json_encode([
        'status' => 'error',
        'message' => '티켓 정보를 찾을 수 없습니다.'
    ]));
}

$winning = getTicketWinning($conn, $ticket_id);

// HTML 응답 출력
header('Content-Type: text/html; charset=UTF-8');
?>

<div id="ticketDetailPrint">
    <!-- 티켓 기본 정보 -->
    <div class="row">
        <div class="col-md-12">
            <h4 class="text-center mb-4">복권 티켓 상세 정보</h4>
            
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">티켓 정보</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-sm">
                                <tr>
                                    <th style="width: 140px;">티켓 번호:</th>
                                    <td><?php echo $ticket['ticket_number']; ?></td>
                                </tr>
                                <tr>
                                    <th>복권 상품:</th>
                                    <td><?php echo $ticket['product_name'] . ' (' . $ticket['product_code'] . ')'; ?></td>
                                </tr>
                                <tr>
                                    <th>회차 정보:</th>
                                    <td><?php echo $ticket['draw_number'] . '회 (' . date('Y-m-d', strtotime($ticket['draw_date'])) . ')'; ?></td>
                                </tr>
                                <tr>
                                    <th>선택 번호:</th>
                                    <td>
                                        <div class="lottery-numbers">
                                            <?php 
                                            $numbers_array = explode(',', $ticket['numbers']);
                                            foreach ($numbers_array as $number) {
                                                echo '<span class="badge badge-primary">' . trim($number) . '</span> ';
                                            }
                                            ?>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <th>가격:</th>
                                    <td><?php echo number_format($ticket['price']); ?> NPR</td>
                                </tr>
                                <tr>
                                    <th>상태:</th>
                                    <td>
                                        <?php 
                                        $status_class = '';
                                        $status_text = '';
                                        
                                        switch ($ticket['status']) {
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
                                                $status_text = '취소';
                                                break;
                                            default:
                                                $status_class = 'badge badge-dark';
                                                $status_text = '기타';
                                        }
                                        ?>
                                        <span class="<?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-sm">
                                <tr>
                                    <th style="width: 140px;">판매점:</th>
                                    <td><?php echo $ticket['store_name'] . ' (' . $ticket['store_code'] . ')'; ?></td>
                                </tr>
                                <tr>
                                    <th>지역:</th>
                                    <td><?php echo $ticket['region_name']; ?></td>
                                </tr>
                                <tr>
                                    <th>단말기:</th>
                                    <td><?php echo $ticket['terminal_code']; ?></td>
                                </tr>
                                <tr>
                                    <th>판매일시:</th>
                                    <td><?php echo date('Y-m-d H:i:s', strtotime($ticket['created_at'])); ?></td>
                                </tr>
                                <tr>
                                    <th>고객 정보:</th>
                                    <td>
                                        <?php 
                                        if (!empty($ticket['customer_id'])) {
                                            echo $ticket['customer_name'];
                                            if (!empty($ticket['customer_phone'])) {
                                                echo ' (' . $ticket['customer_phone'] . ')';
                                            }
                                        } else {
                                            echo '<span class="text-muted">익명 구매</span>';
                                        }
                                        ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th>당첨 결과:</th>
                                    <td>
                                        <?php 
                                        if ($ticket['status'] === 'won' && !empty($winning)) {
                                            echo '<span class="badge badge-success">' . $winning['prize_tier'] . '등 당첨</span>';
                                            echo ' <strong>' . number_format($winning['prize_amount']) . ' NPR</strong>';
                                        } else if ($ticket['status'] === 'lost') {
                                            echo '<span class="badge badge-secondary">미당첨</span>';
                                        } else if ($ticket['draw_status'] === 'scheduled') {
                                            echo '<span class="badge badge-warning">추첨 전</span>';
                                        } else if ($ticket['status'] === 'cancelled') {
                                            echo '<span class="badge badge-danger">취소됨</span>';
                                        } else {
                                            echo '<span class="badge badge-info">결과 확인 중</span>';
                                        }
                                        ?>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 당첨 정보 (있는 경우에만 표시) -->
            <?php if ($ticket['status'] === 'won' && !empty($winning)): ?>
                <div class="card mt-3">
                    <div class="card-header bg-success text-white">
                        <h5 class="card-title mb-0">당첨 정보</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-sm">
                                    <tr>
                                        <th style="width: 140px;">당첨 등수:</th>
                                        <td><?php echo $winning['prize_tier']; ?>등</td>
                                    </tr>
                                    <tr>
                                        <th>당첨금:</th>
                                        <td><strong><?php echo number_format($winning['prize_amount']); ?> NPR</strong></td>
                                    </tr>
                                    <tr>
                                        <th>당첨 번호:</th>
                                        <td>
                                            <div class="lottery-numbers">
                                                <?php 
                                                if (!empty($ticket['winning_numbers'])) {
                                                    $winning_numbers_array = explode(',', $ticket['winning_numbers']);
                                                    foreach ($winning_numbers_array as $number) {
                                                        echo '<span class="badge badge-success">' . trim($number) . '</span> ';
                                                    }
                                                } else {
                                                    echo '<span class="text-muted">정보 없음</span>';
                                                }
                                                ?>
                                            </div>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-sm">
                                    <tr>
                                        <th style="width: 140px;">지급 상태:</th>
                                        <td>
                                            <?php 
                                            $payout_status_class = '';
                                            $payout_status_text = '';
                                            
                                            switch ($winning['status']) {
                                                case 'pending':
                                                    $payout_status_class = 'badge badge-warning';
                                                    $payout_status_text = '지급 대기';
                                                    break;
                                                case 'claimed':
                                                    $payout_status_class = 'badge badge-info';
                                                    $payout_status_text = '청구됨';
                                                    break;
                                                case 'paid':
                                                    $payout_status_class = 'badge badge-success';
                                                    $payout_status_text = '지급 완료';
                                                    break;
                                                default:
                                                    $payout_status_class = 'badge badge-dark';
                                                    $payout_status_text = '기타';
                                            }
                                            ?>
                                            <span class="<?php echo $payout_status_class; ?>"><?php echo $payout_status_text; ?></span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>청구일시:</th>
                                        <td>
                                            <?php 
                                            if (!empty($winning['claimed_at'])) {
                                                echo date('Y-m-d H:i:s', strtotime($winning['claimed_at']));
                                            } else {
                                                echo '<span class="text-muted">청구 전</span>';
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>지급일시:</th>
                                        <td>
                                            <?php 
                                            if (!empty($winning['paid_at'])) {
                                                echo date('Y-m-d H:i:s', strtotime($winning['paid_at']));
                                            } else {
                                                echo '<span class="text-muted">지급 전</span>';
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>지급 방법:</th>
                                        <td>
                                            <?php 
                                            if (!empty($winning['payment_method'])) {
                                                $payment_method = '';
                                                switch ($winning['payment_method']) {
                                                    case 'cash':
                                                        $payment_method = '현금';
                                                        break;
                                                    case 'bank_transfer':
                                                        $payment_method = '계좌 이체';
                                                        break;
                                                    case 'check':
                                                        $payment_method = '수표';
                                                        break;
                                                    default:
                                                        $payment_method = $winning['payment_method'];
                                                }
                                                echo $payment_method;
                                                
                                                if (!empty($winning['payment_reference'])) {
                                                    echo ' (참조번호: ' . $winning['payment_reference'] . ')';
                                                }
                                            } else {
                                                echo '<span class="text-muted">정보 없음</span>';
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- 티켓 이미지 (QR 코드) -->
            <div class="card mt-3">
                <div class="card-body text-center">
                    <h5 class="card-title">티켓 이미지</h5>
                    <div class="qr-code-container">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?php echo urlencode($ticket['ticket_number']); ?>" 
                             alt="티켓 QR 코드" class="img-fluid">
                    </div>
                    <div class="ticket-number-display mt-2">
                        <span class="badge badge-dark"><?php echo $ticket['ticket_number']; ?></span>
                    </div>
                </div>
                <div class="card-footer text-center">
                    <small class="text-muted">발행일: <?php echo date('Y-m-d H:i:s', strtotime($ticket['created_at'])); ?></small>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.lottery-numbers .badge {
    font-size: 14px;
    padding: 5px 8px;
    margin-right: 4px;
    border-radius: 50%;
    width: 30px;
    height: 30px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
.badge-primary {
    background-color: #007bff;
}
.badge-success {
    background-color: #28a745;
}
.qr-code-container {
    padding: 10px;
    display: inline-block;
    background-color: white;
    border-radius: 5px;
    border: 1px solid #ddd;
}
.ticket-number-display .badge {
    font-size: 18px;
    padding: 8px 12px;
}
@media print {
    #ticketDetailPrint {
        padding: 15px;
    }
    .card {
        border: 1px solid #ddd !important;
        margin-bottom: 20px !important;
        break-inside: avoid;
    }
    .card-header {
        background-color: #f8f9fa !important;
        padding: 10px 15px !important;
    }
    .card-body {
        padding: 15px !important;
    }
    .badge-primary {
        background-color: #007bff !important;
        color: white !important;
    }
    .badge-success {
        background-color: #28a745 !important;
        color: white !important;
    }
    .badge-secondary {
        background-color: #6c757d !important;
        color: white !important;
    }
    .badge-info {
        background-color: #17a2b8 !important;
        color: white !important;
    }
    .badge-warning {
        background-color: #ffc107 !important;
        color: black !important;
    }
    .badge-danger {
        background-color: #dc3545 !important;
        color: white !important;
    }
    .badge-dark {
        background-color: #343a40 !important;
        color: white !important;
    }
}
</style>
