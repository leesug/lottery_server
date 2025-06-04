<?php
/**
 * Contract Add Page
 * 
 * This page allows the creation of a new contract for a store.
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
$validationErrors = [];
$formSubmitted = false;
$userId = $_SESSION['user_id'];

// Database connection
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

// Check if store is eligible for new contract
if ($storeInfo['status'] === 'terminated') {
    $_SESSION['message'] = "해지된 판매점에는 새 계약을 추가할 수 없습니다.";
    $_SESSION['message_type'] = "danger";
    header('Location: store-list.php');
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

// Generate a new contract number
$newContractNumber = generateContractNumber($latestContractNumber, $storeInfo['store_code']);

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
    
    // Check for overlapping contract periods
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
    ");
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
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'draft', ?, ?
                )
            ");
            $stmt->bind_param(
                "isssssddddsi",
                $storeId, $contractNumber, $contractType, $startDate, $endDate,
                $signingDate, $commissionRate, $salesTarget, $minGuaranteeAmount,
                $securityDeposit, $specialTerms, $userId
            );
            $stmt->execute();
            $contractId = $db->insert_id;
            $stmt->close();
            
            // Log activity
            logActivity(
                'contract_create',
                sprintf("새 계약 %s가 판매점 %s에 추가되었습니다.", $contractNumber, $storeInfo['store_name']),
                $contractId,
                'store_contracts'
            );
            
            // Commit transaction
            $db->commit();
            
            // Set success message
            $_SESSION['message'] = "계약이 성공적으로 추가되었습니다.";
            $_SESSION['message_type'] = "success";
            
            // Redirect to contract details
            header('Location: contract-details.php?id=' . $contractId);
            exit;
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $db->rollback();
            
            // Set error message
            $message = "계약 추가 중 오류가 발생했습니다: " . $e->getMessage();
            $messageType = "danger";
            
            // Log error
            logError(
                'contract_create_error',
                sprintf("Error creating contract: %s", $e->getMessage()),
                $storeId,
                'stores'
            );
        }
    } else {
        // Set validation error message
        $message = "입력 내용을 확인해주세요.";
        $messageType = "danger";
    }
}

// Page title and metadata
$pageTitle = "새 계약 추가: " . htmlspecialchars($storeInfo['store_name']);
$pageDescription = "판매점에 새로운 계약을 추가합니다.";
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
                    <li><a href="store-details.php?id=<?php echo $storeId; ?>"><?php echo htmlspecialchars($storeInfo['store_name']); ?></a></li>
                    <li><a href="store-contracts.php?store_id=<?php echo $storeId; ?>">계약 관리</a></li>
                    <li class="active">새 계약 추가</li>
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
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Contract Form -->
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title">새 계약 정보</h3>
                    </div>
                    <div class="panel-body">
                        <form method="post" action="contract-add.php?store_id=<?php echo $storeId; ?>" class="form-horizontal">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            
                            <div class="form-group">
                                <label for="contract_number" class="col-sm-3 control-label">계약 번호 <span class="text-danger">*</span></label>
                                <div class="col-sm-9">
                                    <input type="text" class="form-control" id="contract_number" name="contract_number" 
                                           value="<?php echo htmlspecialchars($newContractNumber); ?>" required
                                           placeholder="자동 생성된 계약 번호">
                                    <p class="help-block">자동 생성된 계약 번호입니다. 필요시 수정할 수 있습니다.</p>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="contract_type" class="col-sm-3 control-label">계약 유형 <span class="text-danger">*</span></label>
                                <div class="col-sm-9">
                                    <select class="form-control" id="contract_type" name="contract_type" required>
                                        <option value="">-- 계약 유형 선택 --</option>
                                        <option value="standard" <?php echo ($formSubmitted && $_POST['contract_type'] === 'standard') ? 'selected' : ''; ?>>표준 계약</option>
                                        <option value="premium" <?php echo ($formSubmitted && $_POST['contract_type'] === 'premium') ? 'selected' : ''; ?>>프리미엄 계약</option>
                                        <option value="seasonal" <?php echo ($formSubmitted && $_POST['contract_type'] === 'seasonal') ? 'selected' : ''; ?>>계절 계약</option>
                                        <option value="temporary" <?php echo ($formSubmitted && $_POST['contract_type'] === 'temporary') ? 'selected' : ''; ?>>임시 계약</option>
                                        <option value="custom" <?php echo ($formSubmitted && $_POST['contract_type'] === 'custom') ? 'selected' : ''; ?>>맞춤 계약</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="start_date" class="col-sm-3 control-label">시작일 <span class="text-danger">*</span></label>
                                <div class="col-sm-9">
                                    <input type="date" class="form-control" id="start_date" name="start_date" 
                                           value="<?php echo $formSubmitted ? $_POST['start_date'] : date('Y-m-d'); ?>" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="end_date" class="col-sm-3 control-label">종료일 <span class="text-danger">*</span></label>
                                <div class="col-sm-9">
                                    <input type="date" class="form-control" id="end_date" name="end_date" 
                                           value="<?php echo $formSubmitted ? $_POST['end_date'] : date('Y-m-d', strtotime('+1 year')); ?>" required>
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
                                           value="<?php echo $formSubmitted ? $_POST['commission_rate'] : '5.0'; ?>" step="0.01" min="0" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="sales_target" class="col-sm-3 control-label">판매 목표 (₩)</label>
                                <div class="col-sm-9">
                                    <input type="number" class="form-control" id="sales_target" name="sales_target" 
                                           value="<?php echo $formSubmitted && isset($_POST['sales_target']) ? $_POST['sales_target'] : ''; ?>" step="1000" min="0">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="min_guarantee_amount" class="col-sm-3 control-label">최소 보장액 (₩)</label>
                                <div class="col-sm-9">
                                    <input type="number" class="form-control" id="min_guarantee_amount" name="min_guarantee_amount" 
                                           value="<?php echo $formSubmitted && isset($_POST['min_guarantee_amount']) ? $_POST['min_guarantee_amount'] : ''; ?>" step="1000" min="0">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="security_deposit" class="col-sm-3 control-label">보증금 (₩)</label>
                                <div class="col-sm-9">
                                    <input type="number" class="form-control" id="security_deposit" name="security_deposit" 
                                           value="<?php echo $formSubmitted && isset($_POST['security_deposit']) ? $_POST['security_deposit'] : ''; ?>" step="1000" min="0">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="special_terms" class="col-sm-3 control-label">특별 계약 조건</label>
                                <div class="col-sm-9">
                                    <textarea class="form-control" id="special_terms" name="special_terms" rows="5"
                                              placeholder="특별 계약 조건이 있는 경우 입력하세요."><?php echo $formSubmitted ? $_POST['special_terms'] : ''; ?></textarea>
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
                                    <button type="submit" class="btn btn-primary">계약 추가</button>
                                    <a href="store-contracts.php?store_id=<?php echo $storeId; ?>" class="btn btn-default">취소</a>
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
            
            $('#duration_info').text(months + '개월');
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