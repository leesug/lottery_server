<?php
/**
 * Store Contracts Page
 * 
 * This page displays and manages contracts for a lottery retail store.
 */

// Include required files
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

// Check if user has access to this page
checkPageAccess('store_management');

// Get store ID from URL parameter
$storeId = isset($_GET['store_id']) ? intval($_GET['store_id']) : 0;

if ($storeId <= 0) {
    // Redirect to store list if no valid ID provided
    header('Location: store-list.php');
    exit;
}

// Initialize variables
$message = '';
$messageType = '';
$storeInfo = null;
$contracts = [];
$sortBy = isset($_GET['sort']) ? sanitizeInput($_GET['sort']) : 'end_date';
$sortOrder = isset($_GET['order']) && strtolower($_GET['order']) === 'asc' ? 'ASC' : 'DESC';
$filterStatus = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';

// SIMULATING DATABASE CONNECTION FOR TESTING
// Temporary test data - In production, this would come from the database
$storeInfo = [
    'id' => $storeId,
    'store_name' => '테스트 판매점',
    'store_code' => 'ST001',
    'status' => 'active'
];

// Simulated contracts data
$contracts = [
    [
        'id' => 1,
        'contract_number' => 'CNT-ST001-202505-0001',
        'contract_type' => 'standard',
        'start_date' => '2025-05-01',
        'end_date' => '2026-04-30',
        'signing_date' => '2025-04-25',
        'commission_rate' => 5.00,
        'sales_target' => 1000000,
        'min_guarantee_amount' => 50000,
        'security_deposit' => 100000,
        'status' => 'active',
        'created_at' => '2025-04-25 10:30:00',
        'updated_at' => '2025-04-25 10:30:00'
    ],
    [
        'id' => 2,
        'contract_number' => 'CNT-ST001-202404-0001',
        'contract_type' => 'standard',
        'start_date' => '2024-04-01',
        'end_date' => '2025-03-31',
        'signing_date' => '2024-03-25',
        'commission_rate' => 5.00,
        'sales_target' => 900000,
        'min_guarantee_amount' => 45000,
        'security_deposit' => 90000,
        'status' => 'expired',
        'created_at' => '2024-03-25 09:15:00',
        'updated_at' => '2025-04-01 00:00:00'
    ]
];

// Apply status filter if set
if (!empty($filterStatus)) {
    $filteredContracts = [];
    foreach ($contracts as $contract) {
        if ($contract['status'] === $filterStatus) {
            $filteredContracts[] = $contract;
        }
    }
    $contracts = $filteredContracts;
}

// Check if there is a message in session
if (isset($_SESSION['message']) && isset($_SESSION['message_type'])) {
    $message = $_SESSION['message'];
    $messageType = $_SESSION['message_type'];
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}

// Database connection - Commented out for testing
/*
$db = getDbConnection();

// Get store information
$stmt = $db->prepare("SELECT id, store_name, store_code, status FROM stores WHERE id = ?");
$stmt->bind_param("i", $storeId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Store not found, redirect to list
    header('Location: store-list.php');
    exit;
}

$storeInfo = $result->fetch_assoc();
$stmt->close();

// Check if there is a message in session
if (isset($_SESSION['message']) && isset($_SESSION['message_type'])) {
    $message = $_SESSION['message'];
    $messageType = $_SESSION['message_type'];
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}

// Build query
$sql = "SELECT * FROM store_contracts WHERE store_id = ?";
$params = [$storeId];
$types = "i";

if (!empty($filterStatus)) {
    $sql .= " AND status = ?";
    $params[] = $filterStatus;
    $types .= "s";
}

$sql .= " ORDER BY $sortBy $sortOrder";

// Get contracts
$stmt = $db->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $contracts[] = $row;
}
$stmt->close();
*/

// Page title and metadata
$pageTitle = "판매점 계약 관리: " . htmlspecialchars($storeInfo['store_name']);
$pageDescription = "판매점의 계약 이력 및 관리";
$activeMenu = "store";
$activeSubMenu = "store-list";

// Add CSRF token for testing
$_SESSION['csrf_token'] = md5(uniqid(mt_rand(), true));

// Include header template
include 'test-header.php';
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
                    <li><a href="store-details.php?id=<?php echo $storeId; ?>"><?php echo htmlspecialchars($storeInfo['store_name']); ?></a></li>
                    <li class="active">계약 관리</li>
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
        
        <!-- Store Info Card -->
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title">판매점 정보</h3>
                    </div>
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-4">
                                <dl class="dl-horizontal">
                                    <dt>판매점명:</dt>
                                    <dd><?php echo htmlspecialchars($storeInfo['store_name']); ?></dd>
                                </dl>
                            </div>
                            <div class="col-md-4">
                                <dl class="dl-horizontal">
                                    <dt>판매점 코드:</dt>
                                    <dd><?php echo htmlspecialchars($storeInfo['store_code']); ?></dd>
                                </dl>
                            </div>
                            <div class="col-md-4">
                                <dl class="dl-horizontal">
                                    <dt>판매점 상태:</dt>
                                    <dd>
                                        <?php 
                                        $statusLabels = [
                                            'active' => '<span class="label label-success">활성</span>',
                                            'inactive' => '<span class="label label-warning">비활성</span>',
                                            'pending' => '<span class="label label-info">대기중</span>',
                                            'terminated' => '<span class="label label-danger">계약해지</span>'
                                        ];
                                        echo isset($statusLabels[$storeInfo['status']]) 
                                            ? $statusLabels[$storeInfo['status']] 
                                            : htmlspecialchars($storeInfo['status']);
                                        ?>
                                    </dd>
                                </dl>
                            </div>
                        </div>
                        <div class="text-right">
                            <a href="store-details.php?id=<?php echo $storeId; ?>" class="btn btn-info btn-sm">
                                <i class="fa fa-eye"></i> 판매점 상세 정보
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Contract Management -->
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <div class="row">
                            <div class="col-md-6">
                                <h3 class="panel-title">계약 목록</h3>
                            </div>
                            <div class="col-md-6 text-right">
                                <div class="btn-group">
                                    <a href="contract-add.php?store_id=<?php echo $storeId; ?>" class="btn btn-success btn-sm">
                                        <i class="fa fa-plus"></i> 새 계약 추가
                                    </a>
                                    <a href="#" class="btn btn-info btn-sm" id="exportBtn">
                                        <i class="fa fa-download"></i> 내보내기 (Excel)
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="panel-body">
                        <!-- Filter Form -->
                        <div class="row">
                            <div class="col-md-12">
                                <form method="get" action="store-contracts.php" class="form-inline mb-3">
                                    <input type="hidden" name="store_id" value="<?php echo $storeId; ?>">
                                    
                                    <div class="form-group">
                                        <label for="status" class="sr-only">상태 필터</label>
                                        <select class="form-control" id="status" name="status">
                                            <option value="">모든 상태</option>
                                            <option value="draft" <?php echo ($filterStatus === 'draft') ? 'selected' : ''; ?>>초안</option>
                                            <option value="active" <?php echo ($filterStatus === 'active') ? 'selected' : ''; ?>>활성</option>
                                            <option value="expired" <?php echo ($filterStatus === 'expired') ? 'selected' : ''; ?>>만료</option>
                                            <option value="terminated" <?php echo ($filterStatus === 'terminated') ? 'selected' : ''; ?>>해지</option>
                                            <option value="renewal_pending" <?php echo ($filterStatus === 'renewal_pending') ? 'selected' : ''; ?>>갱신 대기</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="sort" class="sr-only">정렬 기준</label>
                                        <select class="form-control" id="sort" name="sort">
                                            <option value="end_date" <?php echo ($sortBy === 'end_date') ? 'selected' : ''; ?>>계약 종료일</option>
                                            <option value="start_date" <?php echo ($sortBy === 'start_date') ? 'selected' : ''; ?>>계약 시작일</option>
                                            <option value="signing_date" <?php echo ($sortBy === 'signing_date') ? 'selected' : ''; ?>>계약 체결일</option>
                                            <option value="commission_rate" <?php echo ($sortBy === 'commission_rate') ? 'selected' : ''; ?>>수수료율</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="order" class="sr-only">정렬 순서</label>
                                        <select class="form-control" id="order" name="order">
                                            <option value="desc" <?php echo ($sortOrder === 'DESC') ? 'selected' : ''; ?>>내림차순</option>
                                            <option value="asc" <?php echo ($sortOrder === 'ASC') ? 'selected' : ''; ?>>오름차순</option>
                                        </select>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fa fa-filter"></i> 적용
                                    </button>
                                    
                                    <a href="store-contracts.php?store_id=<?php echo $storeId; ?>" class="btn btn-default">
                                        <i class="fa fa-refresh"></i> 초기화
                                    </a>
                                </form>
                            </div>
                        </div>
                        
                        <!-- Contracts Table -->
                        <?php if (empty($contracts)): ?>
                        <div class="alert alert-info">
                            <i class="fa fa-info-circle"></i> 계약 정보가 없습니다.
                        </div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th>계약 번호</th>
                                        <th>계약 유형</th>
                                        <th>계약 기간</th>
                                        <th>수수료율</th>
                                        <th>판매 목표액</th>
                                        <th>상태</th>
                                        <th>체결일</th>
                                        <th>관리</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($contracts as $contract): ?>
                                    <tr class="<?php echo getContractRowClass($contract); ?>">
                                        <td><?php echo htmlspecialchars($contract['contract_number']); ?></td>
                                        <td>
                                            <?php 
                                            $contractTypeLabels = [
                                                'standard' => '표준',
                                                'premium' => '프리미엄',
                                                'seasonal' => '시즌',
                                                'temporary' => '임시',
                                                'custom' => '맞춤'
                                            ];
                                            echo isset($contractTypeLabels[$contract['contract_type']]) 
                                                ? $contractTypeLabels[$contract['contract_type']] 
                                                : htmlspecialchars($contract['contract_type']);
                                            ?>
                                        </td>
                                        <td>
                                            <?php echo date('Y-m-d', strtotime($contract['start_date'])); ?> ~ 
                                            <?php echo date('Y-m-d', strtotime($contract['end_date'])); ?>
                                            <br>
                                            <small class="text-muted">
                                                <?php echo getContractDuration($contract); ?>
                                            </small>
                                        </td>
                                        <td><?php echo $contract['commission_rate']; ?>%</td>
                                        <td>
                                            <?php echo !empty($contract['sales_target']) 
                                                ? number_format($contract['sales_target'], 2) 
                                                : '<span class="text-muted">미설정</span>'; 
                                            ?>
                                        </td>
                                        <td>
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
                                        </td>
                                        <td><?php echo date('Y-m-d', strtotime($contract['signing_date'])); ?></td>
                                        <td>
                                            <div class="btn-group btn-group-xs">
                                                <a href="contract-details.php?id=<?php echo $contract['id']; ?>" class="btn btn-info" title="상세 정보">
                                                    <i class="fa fa-eye"></i>
                                                </a>
                                                
                                                <?php if ($contract['status'] === 'draft'): ?>
                                                <a href="contract-edit.php?id=<?php echo $contract['id']; ?>" class="btn btn-primary" title="수정">
                                                    <i class="fa fa-edit"></i>
                                                </a>
                                                <a href="#" class="btn btn-success activateContract" 
                                                    data-id="<?php echo $contract['id']; ?>" 
                                                    data-number="<?php echo htmlspecialchars($contract['contract_number']); ?>" 
                                                    title="활성화">
                                                    <i class="fa fa-check"></i>
                                                </a>
                                                <?php elseif ($contract['status'] === 'active'): ?>
                                                <a href="#" class="btn btn-warning renewContract" 
                                                    data-id="<?php echo $contract['id']; ?>" 
                                                    data-number="<?php echo htmlspecialchars($contract['contract_number']); ?>" 
                                                    title="갱신">
                                                    <i class="fa fa-refresh"></i>
                                                </a>
                                                <a href="#" class="btn btn-danger terminateContract" 
                                                    data-id="<?php echo $contract['id']; ?>" 
                                                    data-number="<?php echo htmlspecialchars($contract['contract_number']); ?>" 
                                                    title="해지">
                                                    <i class="fa fa-ban"></i>
                                                </a>
                                                <?php elseif ($contract['status'] === 'renewal_pending'): ?>
                                                <a href="contract-renew.php?id=<?php echo $contract['id']; ?>" class="btn btn-success" title="갱신 처리">
                                                    <i class="fa fa-refresh"></i>
                                                </a>
                                                <?php endif; ?>
                                                
                                                <a href="contract-print.php?id=<?php echo $contract['id']; ?>" class="btn btn-default" title="인쇄">
                                                    <i class="fa fa-print"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Activate Contract Modal -->
<div class="modal fade" id="activateContractModal" tabindex="-1" role="dialog" aria-labelledby="activateContractModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="activateContractModalLabel">계약 활성화</h4>
            </div>
            <form id="activateContractForm" method="post" action="contract-status-change.php">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    <input type="hidden" name="contract_id" id="activateContractId" value="">
                    <input type="hidden" name="action" value="activate">
                    
                    <p>선택한 계약 (<span id="activateContractNumber"></span>)을 활성화하시겠습니까?</p>
                    <p class="text-info">이 작업은 계약을 활성 상태로 변경하며, 가장 최근의 활성 계약은 자동으로 만료 상태로 변경됩니다.</p>
                    
                    <div class="form-group">
                        <label for="activateNotes">비고:</label>
                        <textarea class="form-control" id="activateNotes" name="notes" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">취소</button>
                    <button type="submit" class="btn btn-success">활성화</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Renew Contract Modal -->
<div class="modal fade" id="renewContractModal" tabindex="-1" role="dialog" aria-labelledby="renewContractModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="renewContractModalLabel">계약 갱신</h4>
            </div>
            <form id="renewContractForm" method="post" action="contract-status-change.php">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    <input type="hidden" name="contract_id" id="renewContractId" value="">
                    <input type="hidden" name="action" value="mark_renewal">
                    
                    <p>선택한 계약 (<span id="renewContractNumber"></span>)의 갱신을 진행하시겠습니까?</p>
                    <p class="text-info">이 작업은 계약 상태를 '갱신 대기'로 변경하며, 이후 갱신 처리 페이지에서 세부 사항을 설정할 수 있습니다.</p>
                    
                    <div class="form-group">
                        <label for="renewNotes">비고:</label>
                        <textarea class="form-control" id="renewNotes" name="notes" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">취소</button>
                    <button type="submit" class="btn btn-warning">갱신 대기로 변경</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Terminate Contract Modal -->
<div class="modal fade" id="terminateContractModal" tabindex="-1" role="dialog" aria-labelledby="terminateContractModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="terminateContractModalLabel">계약 해지</h4>
            </div>
            <form id="terminateContractForm" method="post" action="contract-status-change.php">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    <input type="hidden" name="contract_id" id="terminateContractId" value="">
                    <input type="hidden" name="action" value="terminate">
                    
                    <p class="text-danger">선택한 계약 (<span id="terminateContractNumber"></span>)을 해지하시겠습니까?</p>
                    <p class="text-danger">이 작업은 계약을 해지 상태로 변경하며, 판매점이 더 이상 이 계약에 따른 서비스를 이용할 수 없게 됩니다.</p>
                    
                    <div class="form-group">
                        <label for="terminateReason">해지 사유: <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="terminateReason" name="reason" rows="3" required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="terminationDate">해지 일자: <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="terminationDate" name="termination_date" 
                            value="<?php echo date('Y-m-d'); ?>" required>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Activate Contract
    document.querySelectorAll('.activateContract').forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            var contractId = this.getAttribute('data-id');
            var contractNumber = this.getAttribute('data-number');
            
            document.getElementById('activateContractId').value = contractId;
            document.getElementById('activateContractNumber').textContent = contractNumber;
            
            $('#activateContractModal').modal('show');
        });
    });
    
    // Renew Contract
    document.querySelectorAll('.renewContract').forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            var contractId = this.getAttribute('data-id');
            var contractNumber = this.getAttribute('data-number');
            
            document.getElementById('renewContractId').value = contractId;
            document.getElementById('renewContractNumber').textContent = contractNumber;
            
            $('#renewContractModal').modal('show');
        });
    });
    
    // Terminate Contract
    document.querySelectorAll('.terminateContract').forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            var contractId = this.getAttribute('data-id');
            var contractNumber = this.getAttribute('data-number');
            
            document.getElementById('terminateContractId').value = contractId;
            document.getElementById('terminateContractNumber').textContent = contractNumber;
            
            $('#terminateContractModal').modal('show');
        });
    });
    
    // Export button click handler
    document.getElementById('exportBtn').addEventListener('click', function(e) {
        e.preventDefault();
        
        var exportUrl = 'contract-export.php?format=excel&store_id=<?php echo $storeId; ?>';
        
        if ('<?php echo $filterStatus; ?>') {
            exportUrl += '&status=<?php echo $filterStatus; ?>';
        }
        
        exportUrl += '&sort=<?php echo $sortBy; ?>&order=<?php echo $sortOrder; ?>';
        
        window.location.href = exportUrl;
    });
});
</script>

<?php
// Include footer template
include 'test-footer.php';

/**
 * Get the CSS class for a contract row based on its status and dates
 * 
 * @param array $contract The contract data
 * @return string The CSS class
 */
function getContractRowClass($contract) {
    $class = '';
    
    switch ($contract['status']) {
        case 'active':
            // Check if contract is ending soon (within 30 days)
            $endDate = strtotime($contract['end_date']);
            $now = time();
            $daysToEnd = ($endDate - $now) / (60 * 60 * 24);
            
            if ($daysToEnd <= 0) {
                $class = 'danger';  // Contract has expired but still marked as active
            } elseif ($daysToEnd <= 30) {
                $class = 'warning'; // Contract ending soon
            }
            break;
            
        case 'draft':
            $class = 'active';
            break;
            
        case 'expired':
            $class = 'warning';
            break;
            
        case 'terminated':
            $class = 'danger';
            break;
            
        case 'renewal_pending':
            $class = 'info';
            break;
    }
    
    return $class;
}

/**
 * Get a readable duration string for a contract
 * 
 * @param array $contract The contract data
 * @return string The duration string
 */
function getContractDuration($contract) {
    $startDate = new DateTime($contract['start_date']);
    $endDate = new DateTime($contract['end_date']);
    $interval = $startDate->diff($endDate);
    
    $parts = [];
    
    if ($interval->y > 0) {
        $parts[] = $interval->y . '년';
    }
    
    if ($interval->m > 0) {
        $parts[] = $interval->m . '개월';
    }
    
    if (empty($parts) && $interval->d > 0) {
        $parts[] = $interval->d . '일';
    }
    
    return count($parts) > 0 ? implode(' ', $parts) : '1일';
}
?>
