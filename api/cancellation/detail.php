<?php
/**
 * 취소/환불 상세 정보 API
 * 
 * 취소/환불 이력의 상세 정보를 HTML 형식으로 제공하는 API
 * 
 * @param int id 취소 이력 ID
 * @return string HTML 형식의 취소/환불 상세 정보
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
$cancellation_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($cancellation_id <= 0) {
    http_response_code(400);
    exit(json_encode([
        'status' => 'error',
        'message' => '유효하지 않은 취소 이력 ID입니다.'
    ]));
}

// 데이터베이스 연결
$conn = getDBConnection();

// 취소/환불 상세 정보 가져오기
function getCancellationDetails($conn, $cancellation_id) {
    $query = "
        SELECT 
            tc.id,
            tc.ticket_id,
            tc.cancel_reason,
            tc.cancel_notes,
            tc.cancelled_at,
            u1.username as cancelled_by_name,
            t.ticket_number,
            t.numbers,
            t.price,
            t.created_at as ticket_created_at,
            lp.name as product_name,
            lp.product_code,
            s.name as store_name,
            s.store_code,
            r.name as region_name,
            tm.terminal_code,
            r2.id as refund_id,
            r2.refund_amount,
            r2.refund_method,
            r2.refund_reference,
            r2.refunded_at,
            u2.username as refunded_by_name
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
            regions r ON s.region_id = r.id
        LEFT JOIN 
            users u1 ON tc.cancelled_by = u1.id
        LEFT JOIN 
            refunds r2 ON t.id = r2.ticket_id
        LEFT JOIN 
            users u2 ON r2.refunded_by = u2.id
        WHERE 
            tc.id = ?
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$cancellation_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// 필요한 데이터 가져오기
$cancellation = getCancellationDetails($conn, $cancellation_id);
if (!$cancellation) {
    http_response_code(404);
    exit(json_encode([
        'status' => 'error',
        'message' => '취소/환불 정보를 찾을 수 없습니다.'
    ]));
}

// HTML 응답 출력
header('Content-Type: text/html; charset=UTF-8');
?>

<div id="cancellationDetailPrint">
    <!-- 취소 기본 정보 -->
    <div class="row">
        <div class="col-md-12">
            <h4 class="text-center mb-4">취소/환불 상세 정보</h4>
            
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <h5 class="card-title mb-0">취소된 티켓 정보</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-sm">
                                <tr>
                                    <th style="width: 140px;">티켓 번호:</th>
                                    <td><?php echo $cancellation['ticket_number']; ?></td>
                                </tr>
                                <tr>
                                    <th>복권 상품:</th>
                                    <td><?php echo $cancellation['product_name'] . ' (' . $cancellation['product_code'] . ')'; ?></td>
                                </tr>
                                <tr>
                                    <th>선택 번호:</th>
                                    <td>
                                        <div class="lottery-numbers">
                                            <?php 
                                            $numbers_array = explode(',', $cancellation['numbers']);
                                            foreach ($numbers_array as $number) {
                                                echo '<span class="badge badge-primary">' . trim($number) . '</span> ';
                                            }
                                            ?>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <th>판매점:</th>
                                    <td><?php echo $cancellation['store_name'] . ' (' . $cancellation['store_code'] . ')'; ?></td>
                                </tr>
                                <tr>
                                    <th>지역:</th>
                                    <td><?php echo $cancellation['region_name']; ?></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-sm">
                                <tr>
                                    <th style="width: 140px;">가격:</th>
                                    <td><?php echo number_format($cancellation['price']); ?> NPR</td>
                                </tr>
                                <tr>
                                    <th>판매일시:</th>
                                    <td><?php echo date('Y-m-d H:i:s', strtotime($cancellation['ticket_created_at'])); ?></td>
                                </tr>
                                <tr>
                                    <th>단말기:</th>
                                    <td><?php echo $cancellation['terminal_code']; ?></td>
                                </tr>
                                <tr>
                                    <th>취소일시:</th>
                                    <td><?php echo date('Y-m-d H:i:s', strtotime($cancellation['cancelled_at'])); ?></td>
                                </tr>
                                <tr>
                                    <th>취소자:</th>
                                    <td><?php echo $cancellation['cancelled_by_name']; ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 취소 상세 정보 -->
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="card-title">취소 상세 정보</h5>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tr>
                            <th style="width: 140px; vertical-align: middle;">취소 사유:</th>
                            <td>
                                <?php 
                                $reason_text = '';
                                switch ($cancellation['cancel_reason']) {
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
                        </tr>
                        <tr>
                            <th style="vertical-align: middle;">취소 상세 내용:</th>
                            <td>
                                <?php 
                                if (!empty($cancellation['cancel_notes'])) {
                                    echo nl2br(htmlspecialchars($cancellation['cancel_notes']));
                                } else {
                                    echo '<span class="text-muted">추가 정보 없음</span>';
                                }
                                ?>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <!-- 환불 정보 (있는 경우에만 표시) -->
            <?php if (!empty($cancellation['refund_id'])): ?>
                <div class="card mt-3">
                    <div class="card-header bg-success text-white">
                        <h5 class="card-title mb-0">환불 정보</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-sm">
                                    <tr>
                                        <th style="width: 140px;">환불 금액:</th>
                                        <td><strong><?php echo number_format($cancellation['refund_amount']); ?> NPR</strong></td>
                                    </tr>
                                    <tr>
                                        <th>환불 방법:</th>
                                        <td>
                                            <?php 
                                            $method_text = '';
                                            switch ($cancellation['refund_method']) {
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
                                                    $method_text = $cancellation['refund_method'];
                                            }
                                            echo $method_text;
                                            ?>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-sm">
                                    <tr>
                                        <th style="width: 140px;">환불 참조 번호:</th>
                                        <td>
                                            <?php 
                                            if (!empty($cancellation['refund_reference'])) {
                                                echo $cancellation['refund_reference'];
                                            } else {
                                                echo '<span class="text-muted">참조 번호 없음</span>';
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>환불 처리자:</th>
                                        <td><?php echo $cancellation['refunded_by_name']; ?></td>
                                    </tr>
                                    <tr>
                                        <th>환불 일시:</th>
                                        <td><?php echo date('Y-m-d H:i:s', strtotime($cancellation['refunded_at'])); ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- 바코드 -->
            <div class="card mt-3">
                <div class="card-body text-center">
                    <div class="row">
                        <div class="col-md-6">
                            <h5>티켓 번호</h5>
                            <div class="barcode-container">
                                <img src="https://barcode.tec-it.com/barcode.ashx?data=<?php echo $cancellation['ticket_number']; ?>&code=Code128&dpi=96" alt="바코드">
                            </div>
                            <div class="mt-2">
                                <span class="badge badge-dark"><?php echo $cancellation['ticket_number']; ?></span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h5>취소 참조 번호</h5>
                            <div class="barcode-container">
                                <img src="https://barcode.tec-it.com/barcode.ashx?data=CANCEL-<?php echo $cancellation['id']; ?>&code=Code128&dpi=96" alt="바코드">
                            </div>
                            <div class="mt-2">
                                <span class="badge badge-danger">CANCEL-<?php echo $cancellation['id']; ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer text-center">
                    <small class="text-muted">취소 처리일: <?php echo date('Y-m-d H:i:s', strtotime($cancellation['cancelled_at'])); ?></small>
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
.badge-danger {
    background-color: #dc3545;
}
.barcode-container {
    padding: 10px;
    display: inline-block;
    background-color: white;
    border-radius: 5px;
    border: 1px solid #ddd;
}
@media print {
    #cancellationDetailPrint {
        padding: 15px;
    }
    .card {
        border: 1px solid #ddd !important;
        margin-bottom: 20px !important;
        break-inside: avoid;
    }
    .card-header {
        padding: 10px 15px !important;
    }
    .bg-danger {
        background-color: #dc3545 !important;
        color: white !important;
    }
    .bg-success {
        background-color: #28a745 !important;
        color: white !important;
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
