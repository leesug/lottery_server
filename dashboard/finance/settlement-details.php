<?php
/**
 * 재무 관리 - 정산 상세 정보 페이지
 * 
 * 이 페이지는 특정 정산의 상세 정보를 표시합니다.
 */

// 세션 시작 및 필수 파일 포함
session_start();
require_once '../../includes/auth.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// 인증 확인
checkAuth();

// 필요한 권한 확인
$requiredPermissions = ['finance_view', 'finance_settlements_view'];
checkPermissions($requiredPermissions);

// 정산 ID 확인
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    setAlert('정산 ID가 유효하지 않습니다.', 'error');
    redirectTo('settlements.php');
}

$settlementId = intval($_GET['id']);

// 데이터베이스 연결
$conn = getDBConnection();

// 정산 정보 조회
$sql = "SELECT s.*, u1.username as created_by_user, u2.username as approved_by_user
        FROM settlements s 
        LEFT JOIN users u1 ON s.created_by = u1.id 
        LEFT JOIN users u2 ON s.approved_by = u2.id 
        WHERE s.id = ?";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    logError("Database prepare error: " . $conn->error);
    die("데이터베이스 오류가 발생했습니다.");
}

$stmt->bind_param("i", $settlementId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    setAlert('존재하지 않는 정산입니다.', 'error');
    redirectTo('settlements.php');
}

$settlement = $result->fetch_assoc();

// 정산 항목 조회
$itemsSql = "SELECT * FROM settlement_items WHERE settlement_id = ? ORDER BY id";
$itemsStmt = $conn->prepare($itemsSql);
$itemsStmt->bind_param("i", $settlementId);
$itemsStmt->execute();
$itemsResult = $itemsStmt->get_result();

$items = [];
while ($row = $itemsResult->fetch_assoc()) {
    $items[] = $row;
}

// 정산 대상 정보 조회
$entityInfo = getSettlementEntityInfo($settlement['entity_type'], $settlement['entity_id']);

// 페이지 제목 설정
$pageTitle = "정산 상세 정보: " . $settlement['settlement_code'];
$currentSection = "finance";
$currentPage = "settlements";

// 정산 유형 및 상태 옵션
$settlementTypes = [
    'store' => '판매점', 
    'vendor' => '공급업체', 
    'employee' => '직원', 
    'tax' => '세금', 
    'other' => '기타'
];

$settlementStatuses = [
    'pending' => '대기 중', 
    'processing' => '처리 중', 
    'completed' => '완료됨', 
    'failed' => '실패', 
    'cancelled' => '취소됨'
];

$paymentMethods = [
    'cash' => '현금',
    'bank_transfer' => '계좌이체',
    'check' => '수표',
    'credit' => '신용',
    'adjustment' => '조정'
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
                        <li class="breadcrumb-item"><a href="settlements.php">정산 목록</a></li>
                        <li class="breadcrumb-item active">상세 정보</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-8">
                    <!-- 정산 정보 카드 -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-file-invoice-dollar mr-1"></i>
                                정산 기본 정보
                            </h3>
                            <div class="card-tools">
                                <a href="settlements.php" class="btn btn-default btn-sm">
                                    <i class="fas fa-arrow-left"></i> 목록으로
                                </a>
                                <?php if (hasPermission('finance_settlements_print')): ?>
                                    <button type="button" class="btn btn-info btn-sm" id="printBtn">
                                        <i class="fas fa-print"></i> 인쇄
                                    </button>
                                <?php endif; ?>
                                <?php if (hasPermission('finance_settlements_edit') && in_array($settlement['status'], ['pending', 'processing'])): ?>
                                    <a href="settlement-edit.php?id=<?php echo $settlementId; ?>" class="btn btn-primary btn-sm">
                                        <i class="fas fa-edit"></i> 수정
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <dl class="row">
                                        <dt class="col-sm-4">정산 코드</dt>
                                        <dd class="col-sm-8"><?php echo htmlspecialchars($settlement['settlement_code']); ?></dd>
                                        
                                        <dt class="col-sm-4">정산 유형</dt>
                                        <dd class="col-sm-8">
                                            <?php 
                                                $typeClass = 'badge bg-primary';
                                                echo '<span class="' . $typeClass . '">' . ($settlementTypes[$settlement['settlement_type']] ?? $settlement['settlement_type']) . '</span>';
                                            ?>
                                        </dd>
                                        
                                        <dt class="col-sm-4">대상 정보</dt>
                                        <dd class="col-sm-8">
                                            <?php if ($entityInfo && !empty($entityInfo['link'])): ?>
                                                <a href="<?php echo $entityInfo['link']; ?>"><?php echo htmlspecialchars($entityInfo['name']); ?></a>
                                            <?php else: ?>
                                                <?php echo htmlspecialchars($entityInfo['name'] ?? '정보 없음'); ?>
                                            <?php endif; ?>
                                        </dd>
                                        
                                        <dt class="col-sm-4">정산 기간</dt>
                                        <dd class="col-sm-8"><?php echo date('Y-m-d', strtotime($settlement['start_date'])) . ' ~ ' . date('Y-m-d', strtotime($settlement['end_date'])); ?></dd>
                                    </dl>
                                </div>
                                <div class="col-md-6">
                                    <dl class="row">
                                        <dt class="col-sm-4">총 금액</dt>
                                        <dd class="col-sm-8">
                                            <span class="font-weight-bold"><?php echo number_format($settlement['total_amount'], 2) . ' NPR'; ?></span>
                                        </dd>
                                        
                                        <dt class="col-sm-4">수수료</dt>
                                        <dd class="col-sm-8"><?php echo number_format($settlement['commission_amount'], 2) . ' NPR'; ?></dd>
                                        
                                        <dt class="col-sm-4">세금</dt>
                                        <dd class="col-sm-8"><?php echo number_format($settlement['tax_amount'], 2) . ' NPR'; ?></dd>
                                        
                                        <dt class="col-sm-4">순 지급액</dt>
                                        <dd class="col-sm-8">
                                            <span class="font-weight-bold text-success"><?php echo number_format($settlement['net_amount'], 2) . ' NPR'; ?></span>
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                            
                            <div class="row mt-3">
                                <div class="col-12">
                                    <h5>상태 정보</h5>
                                    <dl class="row">
                                        <dt class="col-sm-2">현재 상태</dt>
                                        <dd class="col-sm-4">
                                            <?php 
                                                $statusClass = '';
                                                switch($settlement['status']) {
                                                    case 'pending':
                                                        $statusClass = 'badge bg-warning';
                                                        break;
                                                    case 'processing':
                                                        $statusClass = 'badge bg-info';
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
                                                }
                                                echo '<span class="' . $statusClass . '">' . $settlementStatuses[$settlement['status']] . '</span>';
                                            ?>
                                        </dd>
                                        
                                        <dt class="col-sm-2">지불 방법</dt>
                                        <dd class="col-sm-4"><?php echo $paymentMethods[$settlement['payment_method']] ?? htmlspecialchars($settlement['payment_method']); ?></dd>
                                        
                                        <dt class="col-sm-2">지불 참조</dt>
                                        <dd class="col-sm-4"><?php echo htmlspecialchars($settlement['payment_reference'] ?? '없음'); ?></dd>
                                        
                                        <dt class="col-sm-2">정산 날짜</dt>
                                        <dd class="col-sm-4"><?php echo $settlement['settlement_date'] ? date('Y-m-d H:i', strtotime($settlement['settlement_date'])) : '미처리'; ?></dd>
                                    </dl>
                                </div>
                            </div>
                            
                            <?php if (!empty($settlement['notes'])): ?>
                                <div class="row mt-3">
                                    <div class="col-12">
                                        <h5>메모</h5>
                                        <p><?php echo nl2br(htmlspecialchars($settlement['notes'])); ?></p>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- 정산 항목 카드 -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-list mr-1"></i>
                                정산 항목
                            </h3>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th style="width: 50px">#</th>
                                            <th>항목 유형</th>
                                            <th>설명</th>
                                            <th>수량</th>
                                            <th class="text-right">단가</th>
                                            <th class="text-right">합계</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($items) > 0): ?>
                                            <?php foreach ($items as $index => $item): ?>
                                                <tr>
                                                    <td><?php echo $index + 1; ?></td>
                                                    <td><?php echo getItemTypeName($item['item_type']); ?></td>
                                                    <td>
                                                        <?php echo htmlspecialchars($item['description']); ?>
                                                        <?php if (!empty($item['reference_id'])): ?>
                                                            <small class="d-block text-muted">참조 ID: <?php echo htmlspecialchars($item['reference_id']); ?></small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo number_format($item['quantity']); ?></td>
                                                    <td class="text-right"><?php echo number_format($item['amount'], 2) . ' NPR'; ?></td>
                                                    <td class="text-right"><?php echo number_format($item['total_amount'], 2) . ' NPR'; ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6" class="text-center">정산 항목이 없습니다.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th colspan="5" class="text-right">소계:</th>
                                            <th class="text-right"><?php echo number_format($settlement['total_amount'], 2) . ' NPR'; ?></th>
                                        </tr>
                                        <tr>
                                            <th colspan="5" class="text-right">수수료:</th>
                                            <th class="text-right"><?php echo number_format($settlement['commission_amount'], 2) . ' NPR'; ?></th>
                                        </tr>
                                        <tr>
                                            <th colspan="5" class="text-right">세금:</th>
                                            <th class="text-right"><?php echo number_format($settlement['tax_amount'], 2) . ' NPR'; ?></th>
                                        </tr>
                                        <tr>
                                            <th colspan="5" class="text-right">순 지급액:</th>
                                            <th class="text-right"><?php echo number_format($settlement['net_amount'], 2) . ' NPR'; ?></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
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
                                <dd class="col-sm-7"><?php echo htmlspecialchars($settlement['created_by_user'] ?? '미확인'); ?></dd>
                                
                                <dt class="col-sm-5">등록일</dt>
                                <dd class="col-sm-7"><?php echo date('Y-m-d H:i', strtotime($settlement['created_at'])); ?></dd>
                                
                                <dt class="col-sm-5">승인자</dt>
                                <dd class="col-sm-7"><?php echo htmlspecialchars($settlement['approved_by_user'] ?? '미승인'); ?></dd>
                                
                                <?php if ($settlement['approved_by']): ?>
                                    <dt class="col-sm-5">승인일</dt>
                                    <dd class="col-sm-7"><?php echo date('Y-m-d H:i', strtotime($settlement['updated_at'])); ?></dd>
                                <?php endif; ?>
                            </dl>
                            
                            <?php if (hasPermission('finance_settlements_process') && $settlement['status'] === 'pending'): ?>
                                <button type="button" class="btn btn-primary btn-block mt-3" id="processBtn">
                                    <i class="fas fa-cog"></i> 정산 처리 시작
                                </button>
                            <?php endif; ?>
                            
                            <?php if (hasPermission('finance_settlements_complete') && $settlement['status'] === 'processing'): ?>
                                <button type="button" class="btn btn-success btn-block mt-3" id="completeBtn">
                                    <i class="fas fa-check"></i> 정산 완료 처리
                                </button>
                            <?php endif; ?>
                            
                            <?php if (hasPermission('finance_settlements_cancel') && in_array($settlement['status'], ['pending', 'processing'])): ?>
                                <button type="button" class="btn btn-danger btn-block mt-2" id="cancelBtn">
                                    <i class="fas fa-times"></i> 정산 취소
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- 대상 정보 -->
                    <?php if ($entityInfo && !empty($entityInfo['details'])): ?>
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <?php echo getEntityTypeTitle($settlement['settlement_type']); ?> 정보
                                </h3>
                            </div>
                            <div class="card-body p-0">
                                <table class="table table-striped">
                                    <?php foreach ($entityInfo['details'] as $key => $value): ?>
                                        <tr>
                                            <th style="width:40%"><?php echo htmlspecialchars($key); ?></th>
                                            <td><?php echo htmlspecialchars($value); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </table>
                            </div>
                        </div>
                    <?php endif; ?>
                    
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

<!-- 정산 처리 시작 모달 -->
<div class="modal fade" id="processSettlementModal" tabindex="-1" role="dialog" aria-labelledby="processSettlementModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="processSettlementModalLabel">정산 처리 시작</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="processSettlementForm" action="../../api/finance/process-settlement.php" method="post">
                <div class="modal-body">
                    <p>다음 정산의 처리를 시작하시겠습니까?</p>
                    <p><strong>정산 코드:</strong> <?php echo htmlspecialchars($settlement['settlement_code']); ?></p>
                    <p><strong>금액:</strong> <?php echo number_format($settlement['net_amount'], 2) . ' NPR'; ?></p>
                    
                    <input type="hidden" name="settlement_id" value="<?php echo $settlementId; ?>">
                    <div class="form-group">
                        <label for="process_notes">처리 메모 (선택사항)</label>
                        <textarea class="form-control" id="process_notes" name="notes" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">취소</button>
                    <button type="submit" class="btn btn-primary">처리 시작</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 정산 완료 모달 -->
<div class="modal fade" id="completeSettlementModal" tabindex="-1" role="dialog" aria-labelledby="completeSettlementModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="completeSettlementModalLabel">정산 완료 처리</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="completeSettlementForm" action="../../api/finance/complete-settlement.php" method="post">
                <div class="modal-body">
                    <p>다음 정산을 완료 처리하시겠습니까?</p>
                    <p><strong>정산 코드:</strong> <?php echo htmlspecialchars($settlement['settlement_code']); ?></p>
                    <p><strong>금액:</strong> <?php echo number_format($settlement['net_amount'], 2) . ' NPR'; ?></p>
                    
                    <input type="hidden" name="settlement_id" value="<?php echo $settlementId; ?>">
                    
                    <div class="form-group">
                        <label for="payment_reference">지불 참조 번호</label>
                        <input type="text" class="form-control" id="payment_reference" name="payment_reference" required>
                        <small class="text-muted">거래 확인을 위한 참조 번호를 입력하세요 (예: 송금 번호, 수표 번호)</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="settlement_date">정산 완료일</label>
                        <input type="datetime-local" class="form-control" id="settlement_date" name="settlement_date" value="<?php echo date('Y-m-d\TH:i'); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="complete_notes">완료 메모 (선택사항)</label>
                        <textarea class="form-control" id="complete_notes" name="notes" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">취소</button>
                    <button type="submit" class="btn btn-success">완료 처리</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 정산 취소 모달 -->
<div class="modal fade" id="cancelSettlementModal" tabindex="-1" role="dialog" aria-labelledby="cancelSettlementModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cancelSettlementModalLabel">정산 취소</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="cancelSettlementForm" action="../../api/finance/cancel-settlement.php" method="post">
                <div class="modal-body">
                    <p>다음 정산을 취소하시겠습니까? 이 작업은 되돌릴 수 없습니다.</p>
                    <p><strong>정산 코드:</strong> <?php echo htmlspecialchars($settlement['settlement_code']); ?></p>
                    <p><strong>금액:</strong> <?php echo number_format($settlement['net_amount'], 2) . ' NPR'; ?></p>
                    
                    <input type="hidden" name="settlement_id" value="<?php echo $settlementId; ?>">
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

<!-- 인쇄용 템플릿 -->
<div id="printArea" style="display: none;">
    <div style="padding: 20px;">
        <div style="text-align: center; margin-bottom: 20px;">
            <h1>정산 내역서</h1>
        </div>
        
        <div style="margin-bottom: 30px;">
            <div style="float: left; width: 50%;">
                <h3>KHUSHI LOTTERY</h3>
                <p>123 Main Street, Kathmandu</p>
                <p>Tel: +977-1-234-5678</p>
                <p>Email: info@khushilottery.com</p>
            </div>
            <div style="float: right; width: 50%; text-align: right;">
                <p><strong>정산 코드:</strong> <?php echo htmlspecialchars($settlement['settlement_code']); ?></p>
                <p><strong>정산 기간:</strong> <?php echo date('Y-m-d', strtotime($settlement['start_date'])) . ' ~ ' . date('Y-m-d', strtotime($settlement['end_date'])); ?></p>
                <p><strong>정산 상태:</strong> <?php echo $settlementStatuses[$settlement['status']]; ?></p>
                <p><strong>인쇄 날짜:</strong> <span id="printDate"></span></p>
            </div>
            <div style="clear: both;"></div>
        </div>
        
        <div style="margin-bottom: 20px;">
            <h3>대상 정보</h3>
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
                <tr>
                    <th style="border: 1px solid #ddd; padding: 8px; text-align: left; width: 30%;">유형</th>
                    <td style="border: 1px solid #ddd; padding: 8px;"><?php echo $settlementTypes[$settlement['settlement_type']] ?? $settlement['settlement_type']; ?></td>
                </tr>
                <tr>
                    <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">이름</th>
                    <td style="border: 1px solid #ddd; padding: 8px;"><?php echo htmlspecialchars($entityInfo['name'] ?? '정보 없음'); ?></td>
                </tr>
                <?php if ($entityInfo && !empty($entityInfo['details'])): ?>
                    <?php foreach ($entityInfo['details'] as $key => $value): ?>
                        <tr>
                            <th style="border: 1px solid #ddd; padding: 8px; text-align: left;"><?php echo htmlspecialchars($key); ?></th>
                            <td style="border: 1px solid #ddd; padding: 8px;"><?php echo htmlspecialchars($value); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </table>
        </div>
        
        <div style="margin-bottom: 20px;">
            <h3>정산 항목</h3>
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 10px;">
                <thead>
                    <tr>
                        <th style="border: 1px solid #ddd; padding: 8px; background-color: #f2f2f2;">#</th>
                        <th style="border: 1px solid #ddd; padding: 8px; background-color: #f2f2f2;">항목 유형</th>
                        <th style="border: 1px solid #ddd; padding: 8px; background-color: #f2f2f2;">설명</th>
                        <th style="border: 1px solid #ddd; padding: 8px; background-color: #f2f2f2;">수량</th>
                        <th style="border: 1px solid #ddd; padding: 8px; background-color: #f2f2f2; text-align: right;">단가</th>
                        <th style="border: 1px solid #ddd; padding: 8px; background-color: #f2f2f2; text-align: right;">합계</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($items) > 0): ?>
                        <?php foreach ($items as $index => $item): ?>
                            <tr>
                                <td style="border: 1px solid #ddd; padding: 8px;"><?php echo $index + 1; ?></td>
                                <td style="border: 1px solid #ddd; padding: 8px;"><?php echo getItemTypeName($item['item_type']); ?></td>
                                <td style="border: 1px solid #ddd; padding: 8px;"><?php echo htmlspecialchars($item['description']); ?></td>
                                <td style="border: 1px solid #ddd; padding: 8px;"><?php echo number_format($item['quantity']); ?></td>
                                <td style="border: 1px solid #ddd; padding: 8px; text-align: right;"><?php echo number_format($item['amount'], 2) . ' NPR'; ?></td>
                                <td style="border: 1px solid #ddd; padding: 8px; text-align: right;"><?php echo number_format($item['total_amount'], 2) . ' NPR'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="border: 1px solid #ddd; padding: 8px; text-align: center;">정산 항목이 없습니다.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="5" style="border: 1px solid #ddd; padding: 8px; text-align: right;">소계:</th>
                        <th style="border: 1px solid #ddd; padding: 8px; text-align: right;"><?php echo number_format($settlement['total_amount'], 2) . ' NPR'; ?></th>
                    </tr>
                    <tr>
                        <th colspan="5" style="border: 1px solid #ddd; padding: 8px; text-align: right;">수수료:</th>
                        <th style="border: 1px solid #ddd; padding: 8px; text-align: right;"><?php echo number_format($settlement['commission_amount'], 2) . ' NPR'; ?></th>
                    </tr>
                    <tr>
                        <th colspan="5" style="border: 1px solid #ddd; padding: 8px; text-align: right;">세금:</th>
                        <th style="border: 1px solid #ddd; padding: 8px; text-align: right;"><?php echo number_format($settlement['tax_amount'], 2) . ' NPR'; ?></th>
                    </tr>
                    <tr>
                        <th colspan="5" style="border: 1px solid #ddd; padding: 8px; text-align: right;">순 지급액:</th>
                        <th style="border: 1px solid #ddd; padding: 8px; text-align: right; font-weight: bold;"><?php echo number_format($settlement['net_amount'], 2) . ' NPR'; ?></th>
                    </tr>
                </tfoot>
            </table>
        </div>
        
        <div style="margin-top: 50px;">
            <div style="float: left; width: 45%; border-top: 1px solid #000; padding-top: 10px; text-align: center;">
                담당자 서명
            </div>
            <div style="float: right; width: 45%; border-top: 1px solid #000; padding-top: 10px; text-align: center;">
                수령자 서명
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
    // 정산 처리 버튼 클릭 이벤트
    $('#processBtn').click(function() {
        $('#processSettlementModal').modal('show');
    });

    // 정산 완료 버튼 클릭 이벤트
    $('#completeBtn').click(function() {
        $('#completeSettlementModal').modal('show');
    });

    // 정산 취소 버튼 클릭 이벤트
    $('#cancelBtn').click(function() {
        $('#cancelSettlementModal').modal('show');
    });

    // 정산 처리 폼 제출 이벤트
    $('#processSettlementForm').submit(function(e) {
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
                $('#processSettlementModal').modal('hide');
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: '오류',
                    text: '서버 요청 중 오류가 발생했습니다.'
                });
                $('#processSettlementModal').modal('hide');
            }
        });
    });

    // 정산 완료 폼 제출 이벤트
    $('#completeSettlementForm').submit(function(e) {
        e.preventDefault();
        
        if (!$('#payment_reference').val().trim()) {
            Swal.fire({
                icon: 'warning',
                title: '입력 오류',
                text: '지불 참조 번호를 입력해주세요.'
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
                $('#completeSettlementModal').modal('hide');
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: '오류',
                    text: '서버 요청 중 오류가 발생했습니다.'
                });
                $('#completeSettlementModal').modal('hide');
            }
        });
    });

    // 정산 취소 폼 제출 이벤트
    $('#cancelSettlementForm').submit(function(e) {
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
                $('#cancelSettlementModal').modal('hide');
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: '오류',
                    text: '서버 요청 중 오류가 발생했습니다.'
                });
                $('#cancelSettlementModal').modal('hide');
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
// 정산 대상 정보 조회 함수
function getSettlementEntityInfo($entityType, $entityId) {
    global $conn;
    
    // 대상 정보 기본 구조
    $entityInfo = [
        'name' => '', // 대상 이름
        'details' => [], // 상세 정보
        'link' => '' // 관련 페이지 링크
    ];
    
    switch ($entityType) {
        case 'store':
            // 판매점 정보 조회
            $sql = "SELECT store_code, store_name, owner_name, phone, address, city, state, country 
                    FROM stores WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $entityId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $store = $result->fetch_assoc();
                $entityInfo['name'] = $store['store_name'];
                $entityInfo['details'] = [
                    '판매점 코드' => $store['store_code'],
                    '대표자' => $store['owner_name'],
                    '연락처' => $store['phone'],
                    '주소' => $store['address'] . ', ' . $store['city'] . ', ' . $store['state'] . ', ' . $store['country']
                ];
                $entityInfo['link'] = '../../dashboard/store/store-details.php?id=' . $entityId;
            }
            $stmt->close();
            break;
            
        case 'vendor':
            // 공급업체 정보 조회 (예시)
            $entityInfo['name'] = '공급업체 #' . $entityId;
            $entityInfo['details'] = [
                '업체명' => '예시 공급업체',
                '연락처' => '01-234-5678',
                '계약 번호' => 'CTR-2023-001'
            ];
            $entityInfo['link'] = '#'; // 실제로는 공급업체 상세 페이지 링크
            break;
            
        case 'employee':
            // 직원 정보 조회 (예시)
            $entityInfo['name'] = '직원 #' . $entityId;
            $entityInfo['details'] = [
                '이름' => '홍길동',
                '부서' => '영업부',
                '직급' => '과장'
            ];
            $entityInfo['link'] = '#'; // 실제로는 직원 상세 페이지 링크
            break;
            
        case 'tax':
            // 세금 정보 조회 (예시)
            $entityInfo['name'] = '세금 정산 #' . $entityId;
            $entityInfo['details'] = [
                '세금 유형' => '부가가치세',
                '과세 기간' => '2023년 2분기',
                '세무서' => '중앙 세무서'
            ];
            break;
            
        default:
            // 기타 유형
            $entityInfo['name'] = '정산 대상 #' . $entityId;
            $entityInfo['details'] = [
                '유형' => $entityType,
                'ID' => $entityId
            ];
            break;
    }
    
    return $entityInfo;
}

// 정산 항목 유형 이름 조회 함수
function getItemTypeName($itemType) {
    $types = [
        'sale' => '판매',
        'prize' => '당첨금',
        'commission' => '수수료',
        'deduction' => '공제',
        'tax' => '세금',
        'fee' => '수수료',
        'bonus' => '보너스',
        'other' => '기타'
    ];
    
    return $types[$itemType] ?? $itemType;
}

// 정산 대상 유형 제목 조회 함수
function getEntityTypeTitle($entityType) {
    $titles = [
        'store' => '판매점',
        'vendor' => '공급업체',
        'employee' => '직원',
        'tax' => '세금',
        'other' => '기타'
    ];
    
    return $titles[$entityType] ?? '대상';
}

// 연결 종료
$stmt->close();
$itemsStmt->close();
$conn->close();
?>