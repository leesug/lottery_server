<?php
/**
 * Contract Renewal Page
 * 
 * This page allows renewal of an existing contract marked for renewal.
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
$validationErrors = [];
$formSubmitted = false;
$userId = $_SESSION['user_id'];

// Database connection
$db = getDbConnection();

// Get contract information with store details
$stmt = $db->prepare("
    SELECT c.*, s.id as store_id, s.store_name, s.store_code, s.status as store_status
    FROM store_contracts c
    JOIN stores s ON c.store_id = s.id
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

// Check if contract is renewable (active or renewal_pending status)
if (!in_array($contract['status'], ['active', 'renewal_pending'])) {
    $_SESSION['message'] = "이 계약은 갱신할 수 없습니다. 계약이 활성 또는 갱신 대기 상태여야 합니다.";
    $_SESSION['message_type'] = "danger";
    header('Location: contract-details.php?id=' . $contractId);
    exit;
}

// Get the latest contract number for auto-generation
$latestContractNumber = '';
$stmt = $db->prepare("
    SELECT contract_number 
    FROM store_contracts 
    ORDER BY id DESC 
    LIMIT 1
");
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $latestContractNumber = $result->fetch_assoc()['contract_number'];
}
$stmt->close();

// Generate a new contract number for renewal
$newContractNumber = generateContractNumber($latestContractNumber, $contract['store_code']);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formSubmitted = true;
    
    // Validate CSRF token
    validateCsrfToken($_POST['csrf_token']);
    
    // Get form data
    $contractNumber = sanitizeInput($_POST['contract_number']);
    $contractType = sanitizeInput($_POST['contract_type']);
    $startDate = sanitizeInput($_POST['start_date']);
    $endDate = sanitizeInput($_POST['end_date']);
    $signingDate = sanitizeInput($_POST['signing_date']);
    $commissionRate = floatval($_POST['commission_rate']);
    $salesTarget = !empty($_POST['sales_target']) ? floatval($_POST['sales_target']) : null;
    $minGuaranteeAmount = !empty($_POST['min_guarantee_amount']) ? floatval($_POST['min_guarantee_amount']) : null;
    $securityDeposit = !empty($_POST['security_deposit']) ? floatval($_POST['security_deposit']) : null;
    $specialTerms = sanitizeInput($_POST['special_terms']);
    
    // Validation
    if (empty($contractNumber)) {
        $validationErrors[] = "계약 번호는 필수 항목입니다.";
    }
    
    if (empty($contractType)) {
        $validationErrors[] = "계약 유형은 필수 항목입니다.";
    }
    
    if (empty($startDate)) {
        $validationErrors[] = "계약 시작일은 필수 항목입니다.";
    }
    
    if (empty($endDate)) {
        $validationErrors[] = "계약 종료일은 필수 항목입니다.";
    } else if ($endDate <= $startDate) {
        $validationErrors[] = "계약 종료일은 시작일 이후여야 합니다.";
    }
    
    if (empty($signingDate)) {
        $validationErrors[] = "계약 서명일은 필수 항목입니다.";
    }
    
    if ($commissionRate <= 0) {
        $validationErrors[] = "수수료율은 0보다 커야 합니다.";
    }
    
    // Check if contract number already exists
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM store_contracts WHERE contract_number = ?");
    $stmt->bind_param("s", $contractNumber);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    if ($row['count'] > 0) {
        $validationErrors[] = "이미 사용 중인 계약 번호입니다.";
    }
    
    // Check for overlapping contract periods (excluding old contract if expired)
    $additionalWhere = "";
    if (isset($_POST['expire_old_contract']) && $_POST['expire_old_contract'] === 'Y') {
        $additionalWhere = " AND id != " . $contractId;
    }
    
    $stmt = $db->prepare("
        SELECT COUNT(*) as count 
        FROM store_contracts 
        WHERE store_id = ? 
        AND (
            (start_date <= ? AND end_date >= ?) OR
            (start_date <= ? AND end_date >= ?) OR
            (start_date >= ? AND end_date <= ?)
        )
        AND status NOT IN ('expired', 'terminated')
        " . $additionalWhere
    );
    $stmt->bind_param("issssss", $storeId, $endDate, $startDate, $startDate, $startDate, $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    if ($row['count'] > 0) {
        $validationErrors[] = "이 기간에 이미 활성화된 계약이 있습니다.";
    }
    
    // Process if no validation errors
    if (empty($validationErrors)) {
        try {
            // Begin transaction
            $db->begin_transaction();
            
            // Insert new contract
            $stmt = $db->prepare("
                INSERT INTO store_contracts (
                    store_id, contract_number, contract_type, start_date, end_date, 
                    signing_date, commission_rate, sales_target, min_guarantee_amount, 
                    security_deposit, status, special_terms, created_by
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', ?, ?
                )
            ");
            $stmt->bind_param(
                "isssssddddsi",
                $storeId, $contractNumber, $contractType, $startDate, $endDate,
                $signingDate, $commissionRate, $salesTarget, $minGuaranteeAmount,
                $securityDeposit, $specialTerms, $userId
            );
            $stmt->execute();
            $newContractId = $db->insert_id;
            $stmt->close();
            
            // Update old contract status
            if (isset($_POST['expire_old_contract']) && $_POST['expire_old_contract'] === 'Y') {
                $stmt = $db->prepare("
                    UPDATE store_contracts 
                    SET status = 'expired', renewal_notification_sent = true
                    WHERE id = ?
                ");
                $stmt->bind_param("i", $contractId);
                $stmt->execute();
                $stmt->close();
            }
            
            // Ensure store is active
            $stmt = $db->prepare("
                UPDATE stores 
                SET status = 'active' 
                WHERE id = ? AND status != 'terminated'
            ");
            $stmt->bind_param("i", $storeId);
            $stmt->execute();
            $stmt->close();
            
            // Log activity
            logActivity(
                'contract_renewal',
                sprintf("계약 %s가 갱신되어 새 계약 %s가 생성되었습니다.", $contract['contract_number'], $contractNumber),
                $newContractId,
                'store_contracts'
            );
            
            // Commit transaction
            $db->commit();
            
            // Set success message
            $_SESSION['message'] = "계약이 성공적으로 갱신되었습니다.";
            $_SESSION['message_type'] = "success";
            
            // Redirect to new contract details
            header('Location: contract-details.php?id=' . $newContractId);
            exit;
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $db->rollback();
            
            // Set error message
            $message = "계약 갱신 중 오류가 발생했습니다: " . $e->getMessage();
            $messageType = "danger";
            
            // Log error
            logError(
                'contract_renewal_error',
                sprintf("Error renewing contract: %s", $e->getMessage()),
                $contractId,
                'store_contracts'
            );
        }
    } else {
        // Set validation error message
        $message = "입력 내용을 확인해주세요.";
        $messageType = "danger";
    }
}

// Page title and metadata
$pageTitle = "계약 갱신: " . htmlspecialchars($contract['contract_number']);
$pageDescription = "기존 계약을 갱신하여 새 계약을 생성합니다.";
$activeMenu = "store";
$activeSubMenu = "store-list";

// Include header template
include '../../templates/header.php';

/**
 * Generate a new contract number based on the last contract number
 * 
 * @param string $lastContractNumber The last contract number
 * @param string $storeCode The store code
 * @return string The new contract number
 */
function generateContractNumber($lastContractNumber, $storeCode) {
    $prefix = "CNT-";
    $year = date('Y');
    $month = date('m');
    
    if (empty($lastContractNumber)) {
        return $prefix . $storeCode . "-" . $year . $month . "-0001";
    }
    
    // Extract sequence number from last contract number
    $parts = explode("-", $lastContractNumber);
    $lastSeq = intval(end($parts));
    $newSeq = $lastSeq + 1;
    
    return $prefix . $storeCode . "-" . $year . $month . "-" . str_pad($newSeq, 4, "0", STR_PAD_LEFT);
}
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
                    <li><a href="contract-details.php?id=<?php echo $contractId; ?>">계약 상세</a></li>
                    <li class="active">계약 갱신</li>
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
                    <?php if (!empty($validationErrors)): ?>
                    <ul>
                        <?php foreach ($validationErrors as $error): ?>
                        <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Original Contract Info -->
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-info">
                    <div class="panel-heading">
                        <h3 class="panel-title">원본 계약 정보</h3>
                    </div>
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-6">
                                <dl class="dl-horizontal">
                                    <dt>판매점:</dt>
                                    <dd><?php echo htmlspecialchars($contract['store_name']); ?></dd>
                                    <dt>계약 번호:</dt>
                                    <dd><?php echo htmlspecialchars($contract['contract_number']); ?></dd>
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
                                        ?>
                                    </dd>
                                </dl>
                            </div>
                            <div class="col-md-6">
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
                                    <dt>수수료율:</dt>
                                    <dd><?php echo number_format($contract['commission_rate'], 2); ?>%</dd>
                                    <dt>계약 상태:</dt>
                                    <dd>
                                        <?php 
                                        $contractStatusLabels = [
                                            'draft' => '<span class="label label-default">초안</span>',
                                            'active' => '<span class="label label-success">활성</span>',
                                            'expired' => '<span class="label label-warning">만료</span>',
                                            'terminated' => '<span class="label label-danger">해지</span>',
                                            'renewal_pending' => '<span class="label label-info">갱신 대기</span>'
                                        ];
                                        echo isset($contractStatusLabels[$contract['status']]) 
                                            ? $contractStatusLabels[$contract['status']] 
                                            : htmlspecialchars($contract['status']);
                                        ?>
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Renewal Form -->
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title">새 계약 정보</h3>
                    </div>
                    <div class="panel-body">
                        <form method="post" action="contract-renew.php?id=<?php echo $contractId; ?>" class="form-horizontal">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            
                            <div class="form-group">
                                <label for="contract_number" class="col-sm-3 control-label">계약 번호 <span class="text-danger">*</span></label>
                                <div class="col-sm-9">
                                    <input type="text" class="form-control" id="contract_number" name="contract_number" 
                                           value="<?php echo $formSubmitted ? $_POST['contract_number'] : htmlspecialchars($newContractNumber); ?>" required
                                           placeholder="자동 생성된 계약 번호">
                                    <p class="help-block">자동 생성된 새 계약 번호입니다. 필요시 수정할 수 있습니다.</p>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="contract_type" class="col-sm-3 control-label">계약 유형 <span class="text-danger">*</span></label>
                                <div class="col-sm-9">
                                    <select class="form-control" id="contract_type" name="contract_type" required>
                                        <option value="">-- 계약 유형 선택 --</option>
                                        <option value="standard" <?php echo ($formSubmitted ? $_POST['contract_type'] === 'standard' : $contract['contract_type'] === 'standard') ? 'selected' : ''; ?>>표준 계약</option>
                                        <option value="premium" <?php echo ($formSubmitted ? $_POST['contract_type'] === 'premium' : $contract['contract_type'] === 'premium') ? 'selected' : ''; ?>>프리미엄 계약</option>
                                        <option value="seasonal" <?php echo ($formSubmitted ? $_POST['contract_type'] === 'seasonal' : $contract['contract_type'] === 'seasonal') ? 'selected' : ''; ?>>계절 계약</option>
                                        <option value="temporary" <?php echo ($formSubmitted ? $_POST['contract_type'] === 'temporary' : $contract['contract_type'] === 'temporary') ? 'selected' : ''; ?>>임시 계약</option>
                                        <option value="custom" <?php echo ($formSubmitted ? $_POST['contract_type'] === 'custom' : $contract['contract_type'] === 'custom') ? 'selected' : ''; ?>>맞춤 계약</option>
                                    </select>
                                </div>
                            </div>
                            
                            <?php
                            // Calculate suggested start date (day after old contract end date or today if already expired)
                            $oldEndDate = new DateTime($contract['end_date']);
                            $today = new DateTime();
                            
                            if ($oldEndDate > $today) {
                                $newStartDate = clone $oldEndDate;
                                $newStartDate->modify('+1 day');
                            } else {
                                $newStartDate = clone $today;
                            }
                            
                            // Calculate suggested end date (1 year after start date)
                            $newEndDate = clone $newStartDate;
                            $newEndDate->modify('+1 year');
                            ?>
                            
                            <div class="form-group">
                                <label for="start_date" class="col-sm-3 control-label">시작일 <span class="text-danger">*</span></label>
                                <div class="col-sm-9">
                                    <input type="date" class="form-control" id="start_date" name="start_date" 
                                           value="<?php echo $formSubmitted ? $_POST['start_date'] : $newStartDate->format('Y-m-d'); ?>" required>
                                    <p class="help-block">기존 계약 종료일 다음 날 또는 오늘 이후로 설정합니다.</p>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="end_date" class="col-sm-3 control-label">종료일 <span class="text-danger">*</span></label>
                                <div class="col-sm-9">
                                    <input type="date" class="form-control" id="end_date" name="end_date" 
                                           value="<?php echo $formSubmitted ? $_POST['end_date'] : $newEndDate->format('Y-m-d'); ?>" required>
                                    <div id="duration_info" class="help-block"></div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="signing_date" class="col-sm-3 control-label">서명일 <span class="text-danger">*</span></label>
                                <div class="col-sm-9">
                                    <input type="date" class="form-control" id="signing_date" name="signing_date" 
                                           value="<?php echo $formSubmitted ? $_POST['signing_date'] : date('Y-m-d'); ?>" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="commission_rate" class="col-sm-3 control-label">수수료율 (%) <span class="text-danger">*</span></label>
                                <div class="col-sm-9">
                                    <input type="number" class="form-control" id="commission_rate" name="commission_rate" 
                                           value="<?php echo $formSubmitted ? $_POST['commission_rate'] : $contract['commission_rate']; ?>" step="0.01" min="0" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="sales_target" class="col-sm-3 control-label">판매 목표 (₩)</label>
                                <div class="col-sm-9">
                                    <input type="number" class="form-control" id="sales_target" name="sales_target" 
                                           value="<?php echo $formSubmitted ? (isset($_POST['sales_target']) ? $_POST['sales_target'] : '') : $contract['sales_target']; ?>" step="1000" min="0">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="min_guarantee_amount" class="col-sm-3 control-label">최소 보장액 (₩)</label>
                                <div class="col-sm-9">
                                    <input type="number" class="form-control" id="min_guarantee_amount" name="min_guarantee_amount" 
                                           value="<?php echo $formSubmitted ? (isset($_POST['min_guarantee_amount']) ? $_POST['min_guarantee_amount'] : '') : $contract['min_guarantee_amount']; ?>" step="1000" min="0">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="security_deposit" class="col-sm-3 control-label">보증금 (₩)</label>
                                <div class="col-sm-9">
                                    <input type="number" class="form-control" id="security_deposit" name="security_deposit" 
                                           value="<?php echo $formSubmitted ? (isset($_POST['security_deposit']) ? $_POST['security_deposit'] : '') : $contract['security_deposit']; ?>" step="1000" min="0">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="special_terms" class="col-sm-3 control-label">특별 계약 조건</label>
                                <div class="col-sm-9">
                                    <textarea class="form-control" id="special_terms" name="special_terms" rows="5"
                                              placeholder="특별 계약 조건이 있는 경우 입력하세요."><?php echo $formSubmitted ? $_POST['special_terms'] : $contract['special_terms']; ?></textarea>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <div class="col-sm-offset-3 col-sm-9">
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" id="expire_old_contract" name="expire_old_contract" value="Y" <?php echo ($formSubmitted && isset($_POST['expire_old_contract']) && $_POST['expire_old_contract'] === 'Y') ? 'checked' : ''; ?> checked>
                                            기존 계약을 만료 처리합니다.
                                        </label>
                                        <p class="help-block">이 옵션을 선택하면 기존 계약이 '만료' 상태로 변경되며, 새 계약이 활성화됩니다.</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <div class="col-sm-offset-3 col-sm-9">
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" id="confirm_agreement" required> 위 정보가 정확함을 확인합니다. <span class="text-danger">*</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <div class="col-sm-offset-3 col-sm-9">
                                    <button type="submit" class="btn btn-primary">계약 갱신</button>
                                    <a href="contract-details.php?id=<?php echo $contractId; ?>" class="btn btn-default">취소</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Custom JS -->
<script>
$(document).ready(function() {
    // Calculate contract duration when start or end date changes
    function calculateDuration() {
        var startDate = $('#start_date').val();
        var endDate = $('#end_date').val();
        
        if (startDate && endDate) {
            var start = new Date(startDate);
            var end = new Date(endDate);
            
            // Check if end date is after start date
            if (end <= start) {
                alert('계약 종료일은 시작일 이후여야 합니다.');
                $('#end_date').val('');
                return;
            }
            
            // Calculate duration in months
            var months = (end.getFullYear() - start.getFullYear()) * 12;
            months -= start.getMonth();
            months += end.getMonth();
            
            if (months === 0) {
                months = 1; // Minimum 1 month
            }
            
            $('#duration_info').text('계약 기간: ' + months + '개월');
        }
    }
    
    $('#start_date, #end_date').change(calculateDuration);
    
    // Set contract type dependent fields
    $('#contract_type').change(function() {
        var type = $(this).val();
        
        switch(type) {
            case 'standard':
                $('#commission_rate').val('5.0');
                break;
            case 'premium':
                $('#commission_rate').val('7.5');
                break;
            case 'seasonal':
                $('#commission_rate').val('6.0');
                // Set a 3 month period for seasonal contracts
                var startDate = new Date($('#start_date').val());
                var endDate = new Date(startDate);
                endDate.setMonth(endDate.getMonth() + 3);
                $('#end_date').val(endDate.toISOString().slice(0, 10));
                break;
            case 'temporary':
                $('#commission_rate').val('8.0');
                // Set a 1 month period for temporary contracts
                var startDate = new Date($('#start_date').val());
                var endDate = new Date(startDate);
                endDate.setMonth(endDate.getMonth() + 1);
                $('#end_date').val(endDate.toISOString().slice(0, 10));
                break;
            case 'custom':
                // Let the user set custom values
                break;
        }
        
        calculateDuration();
    });
    
    // Initial calculation of duration
    calculateDuration();
});
</script>

<?php
// Include footer template
include '../../templates/footer.php';
?>