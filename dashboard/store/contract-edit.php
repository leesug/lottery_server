<?php
/**
 * Contract Edit Page
 * 
 * This page allows editing of an existing store contract.
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

// Check if contract is editable (only draft, active, or renewal_pending)
if (!in_array($contract['status'], ['draft', 'active', 'renewal_pending'])) {
    $_SESSION['message'] = "이 계약은 더 이상 수정할 수 없습니다.";
    $_SESSION['message_type'] = "danger";
    header('Location: contract-details.php?id=' . $contractId);
    exit;
}

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
    
    // Check if contract number already exists (except this contract)
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM store_contracts WHERE contract_number = ? AND id != ?");
    $stmt->bind_param("si", $contractNumber, $contractId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    if ($row['count'] > 0) {
        $validationErrors[] = "이미 사용 중인 계약 번호입니다.";
    }
    
    // Check for overlapping contract periods (except this contract)
    $stmt = $db->prepare("
        SELECT COUNT(*) as count 
        FROM store_contracts 
        WHERE store_id = ? 
        AND id != ?
        AND (
            (start_date <= ? AND end_date >= ?) OR
            (start_date <= ? AND end_date >= ?) OR
            (start_date >= ? AND end_date <= ?)
        )
        AND status NOT IN ('expired', 'terminated')
    ");
    $stmt->bind_param("iissssss", $storeId, $contractId, $endDate, $startDate, $startDate, $startDate, $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    if ($row['count'] > 0 && $contract['status'] !== 'active') {
        $validationErrors[] = "이 기간에 이미 활성화된 계약이 있습니다.";
    }
    
    // Process if no validation errors
    if (empty($validationErrors)) {
        try {
            // Begin transaction
            $db->begin_transaction();
            
            // Update contract
            $stmt = $db->prepare("
                UPDATE store_contracts 
                SET 
                    contract_number = ?,
                    contract_type = ?,
                    start_date = ?,
                    end_date = ?,
                    signing_date = ?,
                    commission_rate = ?,
                    sales_target = ?,
                    min_guarantee_amount = ?,
                    security_deposit = ?,
                    special_terms = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->bind_param(
                "sssssddddsi",
                $contractNumber, $contractType, $startDate, $endDate,
                $signingDate, $commissionRate, $salesTarget, $minGuaranteeAmount,
                $securityDeposit, $specialTerms, $contractId
            );
            $stmt->execute();
            $stmt->close();
            
            // Log activity
            logActivity(
                'contract_update',
                sprintf("계약 %s가 수정되었습니다.", $contractNumber),
                $contractId,
                'store_contracts'
            );
            
            // Commit transaction
            $db->commit();
            
            // Set success message
            $_SESSION['message'] = "계약이 성공적으로 수정되었습니다.";
            $_SESSION['message_type'] = "success";
            
            // Redirect to contract details
            header('Location: contract-details.php?id=' . $contractId);
            exit;
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $db->rollback();
            
            // Set error message
            $message = "계약 수정 중 오류가 발생했습니다: " . $e->getMessage();
            $messageType = "danger";
            
            // Log error
            logError(
                'contract_update_error',
                sprintf("Error updating contract: %s", $e->getMessage()),
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
$pageTitle = "계약 수정: " . htmlspecialchars($contract['contract_number']);
$pageDescription = "기존 계약 정보를 수정합니다.";
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
                    <li><a href="contract-details.php?id=<?php echo $contractId; ?>">계약 상세</a></li>
                    <li class="active">계약 수정</li>
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
                                    <dd><?php echo htmlspecialchars($contract['store_name']); ?></dd>
                                </dl>
                            </div>
                            <div class="col-md-4">
                                <dl class="dl-horizontal">
                                    <dt>판매점 코드:</dt>
                                    <dd><?php echo htmlspecialchars($contract['store_code']); ?></dd>
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
                                        echo isset($statusLabels[$contract['store_status']]) 
                                            ? $statusLabels[$contract['store_status']] 
                                            : htmlspecialchars($contract['store_status']);
                                        ?>
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Contract Form -->
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title">계약 정보 수정</h3>
                    </div>
                    <div class="panel-body">
                        <form method="post" action="contract-edit.php?id=<?php echo $contractId; ?>" class="form-horizontal">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            
                            <div class="form-group">
                                <label for="contract_number" class="col-sm-3 control-label">계약 번호 <span class="text-danger">*</span></label>
                                <div class="col-sm-9">
                                    <input type="text" class="form-control" id="contract_number" name="contract_number" 
                                           value="<?php echo $formSubmitted ? $_POST['contract_number'] : htmlspecialchars($contract['contract_number']); ?>" required
                                           <?php echo ($contract['status'] === 'active') ? 'readonly' : ''; ?>
                                           placeholder="계약 번호 입력">
                                    <?php if ($contract['status'] === 'active'): ?>
                                    <p class="help-block">활성 계약의 계약 번호는 수정할 수 없습니다.</p>
                                    <?php endif; ?>
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
                            
                            <div class="form-group">
                                <label for="start_date" class="col-sm-3 control-label">시작일 <span class="text-danger">*</span></label>
                                <div class="col-sm-9">
                                    <input type="date" class="form-control" id="start_date" name="start_date" 
                                           value="<?php echo $formSubmitted ? $_POST['start_date'] : $contract['start_date']; ?>" required
                                           <?php echo ($contract['status'] === 'active') ? 'readonly' : ''; ?>>
                                    <?php if ($contract['status'] === 'active'): ?>
                                    <p class="help-block">활성 계약의 시작일은 수정할 수 없습니다.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="end_date" class="col-sm-3 control-label">종료일 <span class="text-danger">*</span></label>
                                <div class="col-sm-9">
                                    <input type="date" class="form-control" id="end_date" name="end_date" 
                                           value="<?php echo $formSubmitted ? $_POST['end_date'] : $contract['end_date']; ?>" required>
                                    <?php if ($contract['status'] === 'active'): ?>
                                    <div id="duration_info" class="help-block"></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="signing_date" class="col-sm-3 control-label">서명일 <span class="text-danger">*</span></label>
                                <div class="col-sm-9">
                                    <input type="date" class="form-control" id="signing_date" name="signing_date" 
                                           value="<?php echo $formSubmitted ? $_POST['signing_date'] : $contract['signing_date']; ?>" required>
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
                                <label class="col-sm-3 control-label">계약 상태</label>
                                <div class="col-sm-9">
                                    <p class="form-control-static">
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
                                        ?> - 상태 변경은 계약 상세 페이지에서 가능합니다.
                                    </p>
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
                                    <button type="submit" class="btn btn-primary">계약 수정</button>
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
        // Only apply automatic changes if fields haven't been manually edited
        var type = $(this).val();
        
        if ('<?php echo $contract['status']; ?>' !== 'active') {
            switch(type) {
                case 'standard':
                    if ($('#commission_rate').val() == '') {
                        $('#commission_rate').val('5.0');
                    }
                    break;
                case 'premium':
                    if ($('#commission_rate').val() == '') {
                        $('#commission_rate').val('7.5');
                    }
                    break;
                case 'seasonal':
                    if ($('#commission_rate').val() == '') {
                        $('#commission_rate').val('6.0');
                    }
                    break;
                case 'temporary':
                    if ($('#commission_rate').val() == '') {
                        $('#commission_rate').val('8.0');
                    }
                    break;
            }
        }
    });
    
    // Initial calculation of duration
    calculateDuration();
});
</script>

<?php
// Include footer template
include '../../templates/footer.php';
?>