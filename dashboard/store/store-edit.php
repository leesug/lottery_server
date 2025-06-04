<?php
/**
 * Store Edit Page
 * 
 * This page allows administrators to edit existing lottery retail store information.
 */

// Include required files
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

// Check if user has access to this page
checkPageAccess('store_management');

// Get store ID from URL parameter
$storeId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($storeId <= 0) {
    // Redirect to store list if no valid ID provided
    header('Location: store-list.php');
    exit;
}

// Initialize variables
$message = '';
$messageType = '';
$errors = [];
$formData = [];

// Database connection
$db = getDbConnection();

// Get store information
$stmt = $db->prepare("SELECT * FROM stores WHERE id = ?");
$stmt->bind_param("i", $storeId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Store not found, redirect to list
    header('Location: store-list.php');
    exit;
}

$formData = $result->fetch_assoc();
$stmt->close();

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    validateCsrfToken($_POST['csrf_token']);
    
    // Get form data
    $formData['store_name'] = sanitizeInput($_POST['store_name']);
    $formData['owner_name'] = sanitizeInput($_POST['owner_name']);
    $formData['email'] = sanitizeInput($_POST['email']);
    $formData['phone'] = sanitizeInput($_POST['phone']);
    $formData['address'] = sanitizeInput($_POST['address']);
    $formData['city'] = sanitizeInput($_POST['city']);
    $formData['state'] = sanitizeInput($_POST['state']);
    $formData['postal_code'] = sanitizeInput($_POST['postal_code']);
    $formData['country'] = sanitizeInput($_POST['country']);
    $formData['gps_latitude'] = sanitizeInput($_POST['gps_latitude']);
    $formData['gps_longitude'] = sanitizeInput($_POST['gps_longitude']);
    $formData['business_license_number'] = sanitizeInput($_POST['business_license_number']);
    $formData['tax_id'] = sanitizeInput($_POST['tax_id']);
    $formData['bank_name'] = sanitizeInput($_POST['bank_name']);
    $formData['bank_account_number'] = sanitizeInput($_POST['bank_account_number']);
    $formData['bank_ifsc_code'] = sanitizeInput($_POST['bank_ifsc_code']);
    $formData['status'] = sanitizeInput($_POST['status']);
    $formData['store_category'] = sanitizeInput($_POST['store_category']);
    $formData['store_size'] = sanitizeInput($_POST['store_size']);
    $formData['notes'] = sanitizeInput($_POST['notes']);
    
    // Validate required fields
    $requiredFields = ['store_name', 'owner_name', 'phone', 'address', 'city', 'country'];
    foreach ($requiredFields as $field) {
        if (empty($formData[$field])) {
            $errors[$field] = "이 필드는 필수입니다.";
        }
    }
    
    // Validate email if provided
    if (!empty($formData['email']) && !filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "유효한 이메일 주소를 입력해주세요.";
    }
    
    // Validate GPS coordinates if provided
    if (!empty($formData['gps_latitude']) && 
        (!is_numeric($formData['gps_latitude']) || $formData['gps_latitude'] < -90 || $formData['gps_latitude'] > 90)) {
        $errors['gps_latitude'] = "유효한 위도 값을 입력해주세요 (-90 ~ 90).";
    }
    
    if (!empty($formData['gps_longitude']) && 
        (!is_numeric($formData['gps_longitude']) || $formData['gps_longitude'] < -180 || $formData['gps_longitude'] > 180)) {
        $errors['gps_longitude'] = "유효한 경도 값을 입력해주세요 (-180 ~ 180).";
    }
    
    // If no errors, update the store data
    if (empty($errors)) {
        // Prepare and execute update query
        $sql = "UPDATE stores SET 
                store_name = ?, owner_name = ?, email = ?, phone = ?, address = ?, 
                city = ?, state = ?, postal_code = ?, country = ?, gps_latitude = ?, 
                gps_longitude = ?, business_license_number = ?, tax_id = ?, 
                bank_name = ?, bank_account_number = ?, bank_ifsc_code = ?, 
                status = ?, store_category = ?, store_size = ?, notes = ? 
                WHERE id = ?";
        
        $stmt = $db->prepare($sql);
        
        // Convert empty strings to NULL for optional fields
        $gpsLat = !empty($formData['gps_latitude']) ? $formData['gps_latitude'] : null;
        $gpsLng = !empty($formData['gps_longitude']) ? $formData['gps_longitude'] : null;
        $email = !empty($formData['email']) ? $formData['email'] : null;
        $state = !empty($formData['state']) ? $formData['state'] : null;
        $postalCode = !empty($formData['postal_code']) ? $formData['postal_code'] : null;
        $businessLicense = !empty($formData['business_license_number']) ? $formData['business_license_number'] : null;
        $taxId = !empty($formData['tax_id']) ? $formData['tax_id'] : null;
        $bankName = !empty($formData['bank_name']) ? $formData['bank_name'] : null;
        $bankAccount = !empty($formData['bank_account_number']) ? $formData['bank_account_number'] : null;
        $bankIfsc = !empty($formData['bank_ifsc_code']) ? $formData['bank_ifsc_code'] : null;
        $notes = !empty($formData['notes']) ? $formData['notes'] : null;
        
        $stmt->bind_param(
            "sssssssssddsssssssssi",
            $formData['store_name'],
            $formData['owner_name'],
            $email,
            $formData['phone'],
            $formData['address'],
            $formData['city'],
            $state,
            $postalCode,
            $formData['country'],
            $gpsLat,
            $gpsLng,
            $businessLicense,
            $taxId,
            $bankName,
            $bankAccount,
            $bankIfsc,
            $formData['status'],
            $formData['store_category'],
            $formData['store_size'],
            $notes,
            $storeId
        );
        
        if ($stmt->execute()) {
            // Set success message
            $message = "판매점 정보가 성공적으로 업데이트되었습니다.";
            $messageType = "success";
            
            // Log activity
            logActivity(
                'store_update',
                sprintf("Store updated: %s (%s)", $formData['store_name'], $formData['store_code']),
                $storeId,
                'stores'
            );
        } else {
            $message = "판매점 정보 업데이트 중 오류가 발생했습니다: " . $db->error;
            $messageType = "danger";
            
            // Log error
            logError(
                'store_update_error',
                sprintf("Error updating store: %s", $db->error),
                $storeId,
                'stores'
            );
        }
        
        $stmt->close();
    }
}

// Page title and metadata
$pageTitle = "판매점 정보 수정: " . htmlspecialchars($formData['store_name']);
$pageDescription = "복권 판매점 정보 수정";
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
                    <li><a href="store-details.php?id=<?php echo $storeId; ?>"><?php echo htmlspecialchars($formData['store_name']); ?></a></li>
                    <li class="active">정보 수정</li>
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
        
        <!-- Store Form -->
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title">판매점 정보</h3>
                    </div>
                    <div class="panel-body">
                        <form method="post" action="store-edit.php?id=<?php echo $storeId; ?>" class="form-horizontal">
                            <!-- CSRF Token -->
                            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                            
                            <!-- Basic Information Section -->
                            <fieldset>
                                <legend>기본 정보</legend>
                                
                                <div class="form-group">
                                    <label class="control-label col-md-3">판매점 코드:</label>
                                    <div class="col-md-6">
                                        <p class="form-control-static"><?php echo htmlspecialchars($formData['store_code']); ?></p>
                                    </div>
                                </div>
                                
                                <div class="form-group <?php echo isset($errors['store_name']) ? 'has-error' : ''; ?>">
                                    <label class="control-label col-md-3" for="store_name">판매점명 <span class="text-danger">*</span></label>
                                    <div class="col-md-6">
                                        <input type="text" class="form-control" id="store_name" name="store_name" 
                                            value="<?php echo htmlspecialchars($formData['store_name']); ?>" required>
                                        <?php if (isset($errors['store_name'])): ?>
                                        <span class="help-block"><?php echo $errors['store_name']; ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="form-group <?php echo isset($errors['owner_name']) ? 'has-error' : ''; ?>">
                                    <label class="control-label col-md-3" for="owner_name">대표자명 <span class="text-danger">*</span></label>
                                    <div class="col-md-6">
                                        <input type="text" class="form-control" id="owner_name" name="owner_name" 
                                            value="<?php echo htmlspecialchars($formData['owner_name']); ?>" required>
                                        <?php if (isset($errors['owner_name'])): ?>
                                        <span class="help-block"><?php echo $errors['owner_name']; ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="form-group <?php echo isset($errors['email']) ? 'has-error' : ''; ?>">
                                    <label class="control-label col-md-3" for="email">이메일</label>
                                    <div class="col-md-6">
                                        <input type="email" class="form-control" id="email" name="email" 
                                            value="<?php echo htmlspecialchars($formData['email'] ?? ''); ?>">
                                        <?php if (isset($errors['email'])): ?>
                                        <span class="help-block"><?php echo $errors['email']; ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="form-group <?php echo isset($errors['phone']) ? 'has-error' : ''; ?>">
                                    <label class="control-label col-md-3" for="phone">전화번호 <span class="text-danger">*</span></label>
                                    <div class="col-md-6">
                                        <input type="text" class="form-control" id="phone" name="phone" 
                                            value="<?php echo htmlspecialchars($formData['phone']); ?>" required>
                                        <?php if (isset($errors['phone'])): ?>
                                        <span class="help-block"><?php echo $errors['phone']; ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="control-label col-md-3" for="status">상태</label>
                                    <div class="col-md-6">
                                        <select class="form-control" id="status" name="status">
                                            <option value="pending" <?php echo ($formData['status'] === 'pending') ? 'selected' : ''; ?>>대기중</option>
                                            <option value="active" <?php echo ($formData['status'] === 'active') ? 'selected' : ''; ?>>활성</option>
                                            <option value="inactive" <?php echo ($formData['status'] === 'inactive') ? 'selected' : ''; ?>>비활성</option>
                                            <option value="terminated" <?php echo ($formData['status'] === 'terminated') ? 'selected' : ''; ?>>계약해지</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="control-label col-md-3" for="store_category">판매점 카테고리</label>
                                    <div class="col-md-6">
                                        <select class="form-control" id="store_category" name="store_category">
                                            <option value="standard" <?php echo ($formData['store_category'] === 'standard') ? 'selected' : ''; ?>>일반</option>
                                            <option value="premium" <?php echo ($formData['store_category'] === 'premium') ? 'selected' : ''; ?>>프리미엄</option>
                                            <option value="exclusive" <?php echo ($formData['store_category'] === 'exclusive') ? 'selected' : ''; ?>>전용</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="control-label col-md-3" for="store_size">판매점 규모</label>
                                    <div class="col-md-6">
                                        <select class="form-control" id="store_size" name="store_size">
                                            <option value="small" <?php echo ($formData['store_size'] === 'small') ? 'selected' : ''; ?>>소형</option>
                                            <option value="medium" <?php echo ($formData['store_size'] === 'medium') ? 'selected' : ''; ?>>중형</option>
                                            <option value="large" <?php echo ($formData['store_size'] === 'large') ? 'selected' : ''; ?>>대형</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="control-label col-md-3">등록일:</label>
                                    <div class="col-md-6">
                                        <p class="form-control-static"><?php echo date('Y-m-d', strtotime($formData['registration_date'])); ?></p>
                                    </div>
                                </div>
                            </fieldset>
                            
                            <!-- Address Information Section -->
                            <fieldset>
                                <legend>주소 정보</legend>
                                
                                <div class="form-group <?php echo isset($errors['address']) ? 'has-error' : ''; ?>">
                                    <label class="control-label col-md-3" for="address">주소 <span class="text-danger">*</span></label>
                                    <div class="col-md-6">
                                        <textarea class="form-control" id="address" name="address" rows="3" required><?php echo htmlspecialchars($formData['address']); ?></textarea>
                                        <?php if (isset($errors['address'])): ?>
                                        <span class="help-block"><?php echo $errors['address']; ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="form-group <?php echo isset($errors['city']) ? 'has-error' : ''; ?>">
                                    <label class="control-label col-md-3" for="city">도시 <span class="text-danger">*</span></label>
                                    <div class="col-md-6">
                                        <input type="text" class="form-control" id="city" name="city" 
                                            value="<?php echo htmlspecialchars($formData['city']); ?>" required>
                                        <?php if (isset($errors['city'])): ?>
                                        <span class="help-block"><?php echo $errors['city']; ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="control-label col-md-3" for="state">주/도</label>
                                    <div class="col-md-6">
                                        <input type="text" class="form-control" id="state" name="state" 
                                            value="<?php echo htmlspecialchars($formData['state'] ?? ''); ?>">
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="control-label col-md-3" for="postal_code">우편번호</label>
                                    <div class="col-md-6">
                                        <input type="text" class="form-control" id="postal_code" name="postal_code" 
                                            value="<?php echo htmlspecialchars($formData['postal_code'] ?? ''); ?>">
                                    </div>
                                </div>
                                
                                <div class="form-group <?php echo isset($errors['country']) ? 'has-error' : ''; ?>">
                                    <label class="control-label col-md-3" for="country">국가 <span class="text-danger">*</span></label>
                                    <div class="col-md-6">
                                        <input type="text" class="form-control" id="country" name="country" 
                                            value="<?php echo htmlspecialchars($formData['country']); ?>" required>
                                        <?php if (isset($errors['country'])): ?>
                                        <span class="help-block"><?php echo $errors['country']; ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="form-group <?php echo isset($errors['gps_latitude']) ? 'has-error' : ''; ?>">
                                    <label class="control-label col-md-3" for="gps_latitude">GPS 위도</label>
                                    <div class="col-md-6">
                                        <input type="text" class="form-control" id="gps_latitude" name="gps_latitude" 
                                            value="<?php echo htmlspecialchars($formData['gps_latitude'] ?? ''); ?>" placeholder="예: 27.700769">
                                        <?php if (isset($errors['gps_latitude'])): ?>
                                        <span class="help-block"><?php echo $errors['gps_latitude']; ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="form-group <?php echo isset($errors['gps_longitude']) ? 'has-error' : ''; ?>">
                                    <label class="control-label col-md-3" for="gps_longitude">GPS 경도</label>
                                    <div class="col-md-6">
                                        <input type="text" class="form-control" id="gps_longitude" name="gps_longitude" 
                                            value="<?php echo htmlspecialchars($formData['gps_longitude'] ?? ''); ?>" placeholder="예: 85.300140">
                                        <?php if (isset($errors['gps_longitude'])): ?>
                                        <span class="help-block"><?php echo $errors['gps_longitude']; ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </fieldset>
                            
                            <!-- Business Information Section -->
                            <fieldset>
                                <legend>사업자 정보</legend>
                                
                                <div class="form-group">
                                    <label class="control-label col-md-3" for="business_license_number">사업자 등록번호</label>
                                    <div class="col-md-6">
                                        <input type="text" class="form-control" id="business_license_number" name="business_license_number" 
                                            value="<?php echo htmlspecialchars($formData['business_license_number'] ?? ''); ?>">
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="control-label col-md-3" for="tax_id">세금 ID</label>
                                    <div class="col-md-6">
                                        <input type="text" class="form-control" id="tax_id" name="tax_id" 
                                            value="<?php echo htmlspecialchars($formData['tax_id'] ?? ''); ?>">
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="control-label col-md-3" for="bank_name">은행명</label>
                                    <div class="col-md-6">
                                        <input type="text" class="form-control" id="bank_name" name="bank_name" 
                                            value="<?php echo htmlspecialchars($formData['bank_name'] ?? ''); ?>">
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="control-label col-md-3" for="bank_account_number">계좌번호</label>
                                    <div class="col-md-6">
                                        <input type="text" class="form-control" id="bank_account_number" name="bank_account_number" 
                                            value="<?php echo htmlspecialchars($formData['bank_account_number'] ?? ''); ?>">
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="control-label col-md-3" for="bank_ifsc_code">은행 지점 코드</label>
                                    <div class="col-md-6">
                                        <input type="text" class="form-control" id="bank_ifsc_code" name="bank_ifsc_code" 
                                            value="<?php echo htmlspecialchars($formData['bank_ifsc_code'] ?? ''); ?>">
                                    </div>
                                </div>
                            </fieldset>
                            
                            <!-- Additional Information Section -->
                            <fieldset>
                                <legend>추가 정보</legend>
                                
                                <div class="form-group">
                                    <label class="control-label col-md-3" for="notes">비고</label>
                                    <div class="col-md-6">
                                        <textarea class="form-control" id="notes" name="notes" rows="5"><?php echo htmlspecialchars($formData['notes'] ?? ''); ?></textarea>
                                    </div>
                                </div>
                            </fieldset>
                            
                            <!-- Form Buttons -->
                            <div class="form-group">
                                <div class="col-md-offset-3 col-md-6">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fa fa-save"></i> 변경사항 저장
                                    </button>
                                    <a href="store-details.php?id=<?php echo $storeId; ?>" class="btn btn-default">
                                        <i class="fa fa-arrow-left"></i> 상세 정보로 돌아가기
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Optional: Add custom validation or form handling logic here
    console.log('Store edit form loaded');
});
</script>

<?php
// Include footer template
include '../../templates/footer.php';
?>
