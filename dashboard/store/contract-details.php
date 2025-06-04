<?php
/**
 * Contract Details Page
 * 
 * This page displays detailed information about a store contract.
 */

// Include required files
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

// Check if user has access to this page
checkPageAccess('store_management');

// Get contract ID from URL parameter
$contractId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($contractId <= 0) {
    // Redirect to store list if no valid ID provided
    header('Location: store-list.php');
    exit;
}

// Initialize variables
$message = '';
$messageType = '';
$contract = null;
$storeInfo = null;

// Database connection
$db = getDbConnection();

// Get contract information with store details
$stmt = $db->prepare("
    SELECT c.*, 
           s.id as store_id, 
           s.store_name, 
           s.store_code, 
           s.address, 
           s.city, 
           s.state, 
           s.postal_code, 
           s.status as store_status,
           CONCAT(u1.first_name, ' ', u1.last_name) as created_by_name,
           CONCAT(u2.first_name, ' ', u2.last_name) as approved_by_name
    FROM store_contracts c
    JOIN stores s ON c.store_id = s.id
    LEFT JOIN users u1 ON c.created_by = u1.id
    LEFT JOIN users u2 ON c.approved_by = u2.id
    WHERE c.id = ?
");
$stmt->bind_param("i", $contractId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Contract not found, redirect to list
    header('Location: store-list.php');
    exit;
}

$contract = $result->fetch_assoc();
$storeId = $contract['store_id'];
$stmt->close();

// Get previous contract if exists
$prevContract = null;
$stmt = $db->prepare("
    SELECT id, contract_number, start_date, end_date, status
    FROM store_contracts
    WHERE store_id = ? AND end_date < ? AND id != ?
    ORDER BY end_date DESC
    LIMIT 1
");
$stmt->bind_param("isi", $storeId, $contract['start_date'], $contractId);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $prevContract = $result->fetch_assoc();
}
$stmt->close();

// Get next contract if exists
$nextContract = null;
$stmt = $db->prepare("
    SELECT id, contract_number, start_date, end_date, status
    FROM store_contracts
    WHERE store_id = ? AND start_date > ? AND id != ?
    ORDER BY start_date ASC
    LIMIT 1
");
$stmt->bind_param("isi", $storeId, $contract['end_date'], $contractId);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $nextContract = $result->fetch_assoc();
}
$stmt->close();

// Check if there is a message in session
if (isset($_SESSION['message']) && isset($_SESSION['message_type'])) {
    $message = $_SESSION['message'];
    $messageType = $_SESSION['message_type'];
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}

// Page title and metadata
$pageTitle = "계약 상세 정보: " . htmlspecialchars($contract['contract_number']);
$pageDescription = "판매점 계약의 상세 정보를 조회합니다.";
$activeMenu = "store";
$activeSubMenu = "store-list";

// Include header template
include '../../templates/header.php';
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="page-title-div">
                    <h2 class="title"><?php echo $pageTitle; ?></h2>
                    <p class="sub-title"><?php echo $pageDescription; ?></p>
                </div>
            </div>
        </div>
        
        <!-- Breadcrumb -->
        <div class="row">
            <div class="col-md-12">
                <ol class="breadcrumb">
                    <li><a href="../../dashboard/"><i class="fa fa-dashboard"></i> 대시보드</a></li>
                    <li><a href="store-list.php">판매점 관리</a></li>
                    <li><a href="store-details.php?id=<?php echo $storeId; ?>"><?php echo htmlspecialchars($contract['store_name']); ?></a></li>
                    <li><a href="store-contracts.php?store_id=<?php echo $storeId; ?>">계약 관리</a></li>
                    <li class="active">계약 상세</li>
                </ol>
            </div>
        </div>
        
        <!-- Alert Message -->
        <?php if (!empty($message)): ?>
        <div class="row">
            <div class="col-md-12">
                <div class="alert alert-<?php echo $messageType; ?> alert-dismissible" role="alert">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <?php echo $message; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Action Buttons -->
        <div class="row mb-3">
            <div class="col-md-12">
                <div class="btn-group">
                    <a href="store-contracts.php?store_id=<?php echo $storeId; ?>" class="btn btn-default">
                        <i class="fa fa-arrow-left"></i> 계약 목록으로 돌아가기
                    </a>
                    
                    <?php if (in_array($contract['status'], ['draft', 'active', 'renewal_pending'])): ?>
                    <a href="contract-edit.php?id=<?php echo $contractId; ?>" class="btn btn-primary">
                        <i class="fa fa-edit"></i> 계약 수정
                    </a>
                    <?php endif; ?>
                    
                    <?php if ($contract['status'] === 'draft'): ?>
                    <form method="post" action="contract-status-change.php" class="d-inline" id="activateForm" style="display:inline;">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="contract_id" value="<?php echo $contractId; ?>">
                        <input type="hidden" name="action" value="activate">
                        <button type="submit" class="btn btn-success" onclick="return confirm('이 계약을 활성화하시겠습니까? 기존 활성 계약이 있다면 만료됩니다.');">
                            <i class="fa fa-check-circle"></i> 계약 활성화
                        </button>
                    </form>
                    <?php endif; ?>
                    
                    <?php if ($contract['status'] === 'active'): ?>
                    <button type="button" class="btn btn-warning" data-toggle="modal" data-target="#terminateModal">
                        <i class="fa fa-times-circle"></i> 계약 해지
                    </button>
                    
                    <?php 
                    // Calculate days until expiration
                    $endDate = new DateTime($contract['end_date']);
                    $today = new DateTime();
                    $daysLeft = $today->diff($endDate)->days;
                    
                    // Show renewal button if less than 60 days to expiration or already expired
                    if (($daysLeft <= 60 && $endDate > $today) || $endDate <= $today): 
                    ?>
                    <button type="button" class="btn btn-info" data-toggle="modal" data-target="#renewalModal">
                        <i class="fa fa-refresh"></i> 계약 갱신
                    </button>
                    <?php endif; ?>
                    <?php endif; ?>
                    
                    <a href="contract-print.php?id=<?php echo $contractId; ?>" class="btn btn-default" target="_blank">
                        <i class="fa fa-print"></i> 계약서 인쇄
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Contract Details Card -->
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title">계약 상세 정보</h3>
                    </div>
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-12">
                                <h4 class="mb-3">
                                    <span class="text-primary">계약번호: <?php echo htmlspecialchars($contract['contract_number']); ?></span>
                                    <?php 
                                    $statusLabels = [
                                        'draft' => '<span class="label label-default">초안</span>',
                                        'active' => '<span class="label label-success">활성</span>',
                                        'expired' => '<span class="label label-warning">만료</span>',
                                        'terminated' => '<span class="label label-danger">해지</span>',
                                        'renewal_pending' => '<span class="label label-info">갱신 대기</span>'
                                    ];
                                    echo isset($statusLabels[$contract['status']]) 
                                        ? $statusLabels[$contract['status']] 
                                        : htmlspecialchars($contract['status']);
                                    ?>
                                </h4>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <h5 class="text-bold">판매점 정보</h5>
                                <dl class="dl-horizontal">
                                    <dt>판매점명:</dt>
                                    <dd>
                                        <a href="store-details.php?id=<?php echo $storeId; ?>">
                                            <?php echo htmlspecialchars($contract['store_name']); ?>
                                        </a>
                                    </dd>
                                    <dt>판매점 코드:</dt>
                                    <dd><?php echo htmlspecialchars($contract['store_code']); ?></dd>
                                    <dt>주소:</dt>
                                    <dd>
                                        <?php 
                                        $fullAddress = [];
                                        if (!empty($contract['address'])) $fullAddress[] = htmlspecialchars($contract['address']);
                                        if (!empty($contract['city'])) $fullAddress[] = htmlspecialchars($contract['city']);
                                        if (!empty($contract['state'])) $fullAddress[] = htmlspecialchars($contract['state']);
                                        if (!empty($contract['postal_code'])) $fullAddress[] = htmlspecialchars($contract['postal_code']);
                                        echo implode(", ", $fullAddress);
                                        ?>
                                    </dd>
                                    <dt>판매점 상태:</dt>
                                    <dd>
                                        <?php 
                                        $storeStatusLabels = [
                                            'active' => '<span class="label label-success">활성</span>',
                                            'inactive' => '<span class="label label-warning">비활성</span>',
                                            'pending' => '<span class="label label-info">대기중</span>',
                                            'terminated' => '<span class="label label-danger">계약해지</span>'
                                        ];
                                        echo isset($storeStatusLabels[$contract['store_status']]) 
                                            ? $storeStatusLabels[$contract['store_status']] 
                                            : htmlspecialchars($contract['store_status']);
                                        ?>
                                    </dd>
                                </dl>
                            </div>
                            <div class="col-md-6">
                                <h5 class="text-bold">계약 기본 정보</h5>
                                <dl class="dl-horizontal">
                                    <dt>계약 유형:</dt>
                                    <dd>
                                        <?php 
                                        $contractTypeLabels = [
                                            'standard' => '표준 계약',
                                            'premium' => '프리미엄 계약',
                                            'seasonal' => '계절 계약',
                                            'temporary' => '임시 계약',
                                            'custom' => '맞춤 계약'
                                        ];
                                        echo isset($contractTypeLabels[$contract['contract_type']]) 
                                            ? $contractTypeLabels[$contract['contract_type']] 
                                            : htmlspecialchars($contract['contract_type']);
                                        ?>
                                    </dd>
                                    <dt>계약 기간:</dt>
                                    <dd>
                                        <?php 
                                        echo formatDate($contract['start_date']) . ' ~ ' . formatDate($contract['end_date']); 
                                        
                                        // Calculate contract duration
                                        $startDate = new DateTime($contract['start_date']);
                                        $endDate = new DateTime($contract['end_date']);
                                        $interval = $startDate->diff($endDate);
                                        $months = ($interval->y * 12) + $interval->m;
                                        
                                        echo ' (' . $months . '개월)';
                                        
                                        // Show days left if contract is active
                                        if ($contract['status'] === 'active') {
                                            $today = new DateTime();
                                            $daysLeft = $today->diff($endDate)->days;
                                            if ($endDate > $today) {
                                                echo ' <span class="text-primary">' . $daysLeft . '일 남음</span>';
                                            } else {
                                                echo ' <span class="text-danger">만료됨</span>';
                                            }
                                        }
                                        ?>
                                    </dd>
                                    <dt>서명일:</dt>
                                    <dd><?php echo formatDate($contract['signing_date']); ?></dd>
                                    <dt>수수료율:</dt>
                                    <dd><?php echo number_format($contract['commission_rate'], 2); ?>%</dd>
                                </dl>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <h5 class="text-bold">재무 정보</h5>
                                <dl class="dl-horizontal">
                                    <dt>판매 목표:</dt>
                                    <dd><?php echo !empty($contract['sales_target']) ? '₩ ' . number_format($contract['sales_target']) : '-'; ?></dd>
                                    <dt>최소 보장액:</dt>
                                    <dd><?php echo !empty($contract['min_guarantee_amount']) ? '₩ ' . number_format($contract['min_guarantee_amount']) : '-'; ?></dd>
                                    <dt>보증금:</dt>
                                    <dd><?php echo !empty($contract['security_deposit']) ? '₩ ' . number_format($contract['security_deposit']) : '-'; ?></dd>
                                </dl>
                            </div>
                            <div class="col-md-6">
                                <h5 class="text-bold">계약 상태 정보</h5>
                                <dl class="dl-horizontal">
                                    <?php if ($contract['status'] === 'terminated'): ?>
                                    <dt>해지일:</dt>
                                    <dd><?php echo formatDate($contract['termination_date']); ?></dd>
                                    <dt>해지 사유:</dt>
                                    <dd><?php echo !empty($contract['termination_reason']) ? htmlspecialchars($contract['termination_reason']) : '-'; ?></dd>
                                    <?php endif; ?>
                                    <dt>갱신 알림:</dt>
                                    <dd><?php echo $contract['renewal_notification_sent'] ? '발송됨' : '발송되지 않음'; ?></dd>
                                    <dt>계약서 위치:</dt>
                                    <dd>
                                        <?php if (!empty($contract['contract_document_path'])): ?>
                                        <a href="<?php echo htmlspecialchars($contract['contract_document_path']); ?>" target="_blank">
                                            <i class="fa fa-file-pdf-o"></i> 계약서 보기
                                        </a>
                                        <?php else: ?>
                                        <span class="text-muted">계약서 파일 없음</span>
                                        <?php endif; ?>
                                    </dd>
                                </dl>
                            </div>
                        </div>
                        
                        <?php if (!empty($contract['special_terms'])): ?>
                        <div class="row">
                            <div class="col-md-12">
                                <h5 class="text-bold">특별 계약 조건</h5>
                                <div class="well well-sm">
                                    <?php echo nl2br(htmlspecialchars($contract['special_terms'])); ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="row">
                            <div class="col-md-12">
                                <h5 class="text-bold">관리 정보</h5>
                                <dl class="dl-horizontal">
                                    <dt>생성자:</dt>
                                    <dd><?php echo !empty($contract['created_by_name']) ? htmlspecialchars($contract['created_by_name']) : '-'; ?></dd>
                                    <dt>승인자:</dt>
                                    <dd><?php echo !empty($contract['approved_by_name']) ? htmlspecialchars($contract['approved_by_name']) : '-'; ?></dd>
                                    <dt>생성일:</dt>
                                    <dd><?php echo formatDateTime($contract['created_at']); ?></dd>
                                    <dt>최종 수정일:</dt>
                                    <dd><?php echo formatDateTime($contract['updated_at']); ?></dd>
                                </dl>
                            </div>
                        </div>
                        
                        <?php if ($prevContract || $nextContract): ?>
                        <div class="row">
                            <div class="col-md-12">
                                <h5 class="text-bold">관련 계약</h5>
                                <dl class="dl-horizontal">
                                    <?php if ($prevContract): ?>
                                    <dt>이전 계약:</dt>
                                    <dd>
                                        <a href="contract-details.php?id=<?php echo $prevContract['id']; ?>">
                                            <?php 
                                            echo htmlspecialchars($prevContract['contract_number']) . ' (' . 
                                                 formatDate($prevContract['start_date']) . ' ~ ' . 
                                                 formatDate($prevContract['end_date']) . ') - ';
                                            
                                            $prevStatusLabels = [
                                                'draft' => '<span class="label label-default">초안</span>',
                                                'active' => '<span class="label label-success">활성</span>',
                                                'expired' => '<span class="label label-warning">만료</span>',
                                                'terminated' => '<span class="label label-danger">해지</span>',
                                                'renewal_pending' => '<span class="label label-info">갱신 대기</span>'
                                            ];
                                            echo isset($prevStatusLabels[$prevContract['status']]) 
                                                ? $prevStatusLabels[$prevContract['status']] 
                                                : htmlspecialchars($prevContract['status']);
                                            ?>
                                        </a>
                                    </dd>
                                    <?php endif; ?>
                                    <?php if ($nextContract): ?>
                                    <dt>다음 계약:</dt>
                                    <dd>
                                        <a href="contract-details.php?id=<?php echo $nextContract['id']; ?>">
                                            <?php 
                                            echo htmlspecialchars($nextContract['contract_number']) . ' (' . 
                                                 formatDate($nextContract['start_date']) . ' ~ ' . 
                                                 formatDate($nextContract['end_date']) . ') - ';
                                            
                                            $nextStatusLabels = [
                                                'draft' => '<span class="label label-default">초안</span>',
                                                'active' => '<span class="label label-success">활성</span>',
                                                'expired' => '<span class="label label-warning">만료</span>',
                                                'terminated' => '<span class="label label-danger">해지</span>',
                                                'renewal_pending' => '<span class="label label-info">갱신 대기</span>'
                                            ];
                                            echo isset($nextStatusLabels[$nextContract['status']]) 
                                                ? $nextStatusLabels[$nextContract['status']] 
                                                : htmlspecialchars($nextContract['status']);
                                            ?>
                                        </a>
                                    </dd>
                                    <?php endif; ?>
                                </dl>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Terminate Contract Modal -->
<div class="modal fade" id="terminateModal" tabindex="-1" role="dialog" aria-labelledby="terminateModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="post" action="contract-status-change.php">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="contract_id" value="<?php echo $contractId; ?>">
                <input type="hidden" name="action" value="terminate">
                
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title" id="terminateModalLabel">계약 해지</h4>
                </div>
                <div class="modal-body">
                    <p class="text-danger">
                        <i class="fa fa-warning"></i> 경고: 계약을 해지하면 되돌릴 수 없으며, 판매점에서 활성 계약이 없을 경우 판매점 상태가 '비활성'으로 변경됩니다.
                    </p>
                    
                    <div class="form-group">
                        <label for="termination_date">해지일</label>
                        <input type="date" class="form-control" id="termination_date" name="termination_date" 
                               value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="reason">해지 사유 <span class="text-danger">*</span></label>
                        <select class="form-control" id="reason" name="reason" required>
                            <option value="">-- 해지 사유 선택 --</option>
                            <option value="계약 조건 위반">계약 조건 위반</option>
                            <option value="판매 실적 저조">판매 실적 저조</option>
                            <option value="판매점 요청">판매점 요청</option>
                            <option value="운영상 문제">운영상 문제</option>
                            <option value="사업 종료">사업 종료</option>
                            <option value="기타">기타</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="notes">세부 사항</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3" 
                                  placeholder="해지에 대한 상세 내용을 입력하세요."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">취소</button>
                    <button type="submit" class="btn btn-danger">계약 해지</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Renewal Contract Modal -->
<div class="modal fade" id="renewalModal" tabindex="-1" role="dialog" aria-labelledby="renewalModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="post" action="contract-status-change.php">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="contract_id" value="<?php echo $contractId; ?>">
                <input type="hidden" name="action" value="mark_renewal">
                
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title" id="renewalModalLabel">계약 갱신</h4>
                </div>
                <div class="modal-body">
                    <p>
                        이 계약을 갱신 대기 상태로 변경하시겠습니까? 이후 계약 갱신 페이지에서 새로운 계약 조건을 설정할 수 있습니다.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">취소</button>
                    <button type="submit" class="btn btn-primary">갱신 준비</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Custom JS -->
<script>
$(document).ready(function() {
    // Toggle other reason field
    $('#reason').change(function() {
        if ($(this).val() === '기타') {
            $('#notes').attr('required', 'required');
            $('#notes').attr('placeholder', '해지 사유를 상세히 입력하세요.');
        } else {
            $('#notes').removeAttr('required');
            $('#notes').attr('placeholder', '해지에 대한 상세 내용을 입력하세요.');
        }
    });
});
</script>

<?php
// Include footer template
include '../../templates/footer.php';
?>