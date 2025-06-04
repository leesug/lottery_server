<?php
/**
 * Customer Preferences Management Page
 * 
 * This page allows administrators to view and manage customer preferences
 * including communication preferences, language settings, and marketing consent.
 */

// Include required files
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

// Check if user has access to this page
checkPageAccess('customer_management');

// Page title and metadata
$pageTitle = "고객 설정 관리";
$pageDescription = "고객의 언어 설정, 알림 설정, 마케팅 동의 등 설정 관리";
$activeMenu = "customer";
$activeSubMenu = "customer-preferences";

// Include header template
include '../../templates/header.php';

// Get customer ID from URL parameter
$customerId = isset($_GET['customer_id']) ? intval($_GET['customer_id']) : 0;

// Initialize variables
$customer = null;
$preferences = null;
$message = '';
$messageType = '';

// Database connection
$db = getDbConnection();

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    validateCsrfToken($_POST['csrf_token']);
    
    // Get form data
    $language = sanitizeInput($_POST['language']);
    $notificationEmail = isset($_POST['notification_email']) ? 1 : 0;
    $notificationSms = isset($_POST['notification_sms']) ? 1 : 0;
    $notificationPush = isset($_POST['notification_push']) ? 1 : 0;
    $marketingConsent = isset($_POST['marketing_consent']) ? 1 : 0;
    
    // Update customer preferences
    $stmt = $db->prepare("INSERT INTO customer_preferences 
        (customer_id, language, notification_email, notification_sms, notification_push, marketing_consent) 
        VALUES (?, ?, ?, ?, ?, ?) 
        ON DUPLICATE KEY UPDATE 
        language = ?, notification_email = ?, notification_sms = ?, notification_push = ?, marketing_consent = ?");
    
    $stmt->bind_param(
        "isiiisiii", 
        $customerId, 
        $language, 
        $notificationEmail, 
        $notificationSms, 
        $notificationPush, 
        $marketingConsent,
        $language, 
        $notificationEmail, 
        $notificationSms, 
        $notificationPush, 
        $marketingConsent
    );
    
    if ($stmt->execute()) {
        $message = "고객 설정이 성공적으로 업데이트되었습니다.";
        $messageType = "success";
        
        // Log activity
        logActivity(
            'customer_preferences_update', 
            sprintf("Customer ID %d preferences updated", $customerId),
            $customerId,
            'customers'
        );
    } else {
        $message = "고객 설정 업데이트 중 오류가 발생했습니다: " . $db->error;
        $messageType = "danger";
        
        // Log error
        logError(
            'customer_preferences_update_error',
            sprintf("Error updating preferences for customer ID %d: %s", $customerId, $db->error),
            $customerId,
            'customers'
        );
    }
    
    $stmt->close();
}

// Get customer information
if ($customerId > 0) {
    $stmt = $db->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->bind_param("i", $customerId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $customer = $result->fetch_assoc();
    }
    
    $stmt->close();
    
    // Get customer preferences
    $stmt = $db->prepare("SELECT * FROM customer_preferences WHERE customer_id = ?");
    $stmt->bind_param("i", $customerId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $preferences = $result->fetch_assoc();
    } else {
        // Default preferences if not set
        $preferences = [
            'language' => 'en',
            'notification_email' => 1,
            'notification_sms' => 1,
            'notification_push' => 1,
            'marketing_consent' => 0
        ];
    }
    
    $stmt->close();
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
                    <li><a href="customer-list.php">고객 관리</a></li>
                    <?php if ($customer): ?>
                    <li><a href="customer-details.php?id=<?php echo $customerId; ?>"><?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></a></li>
                    <?php endif; ?>
                    <li class="active">고객 설정 관리</li>
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
        
        <?php if ($customer): ?>
        <!-- Customer Preferences Form -->
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?>님의 설정 관리
                    </div>
                    <div class="panel-body">
                        <form method="post" action="customer-preferences.php?customer_id=<?php echo $customerId; ?>" class="form-horizontal">
                            <!-- CSRF Token -->
                            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                            
                            <!-- Language Preference -->
                            <div class="form-group">
                                <label class="control-label col-md-3">언어 설정:</label>
                                <div class="col-md-6">
                                    <select name="language" class="form-control">
                                        <option value="en" <?php echo ($preferences['language'] === 'en') ? 'selected' : ''; ?>>영어 (English)</option>
                                        <option value="ne" <?php echo ($preferences['language'] === 'ne') ? 'selected' : ''; ?>>네팔어 (नेपाली)</option>
                                        <option value="ko" <?php echo ($preferences['language'] === 'ko') ? 'selected' : ''; ?>>한국어 (Korean)</option>
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Notification Preferences -->
                            <div class="form-group">
                                <label class="control-label col-md-3">알림 설정:</label>
                                <div class="col-md-6">
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" name="notification_email" <?php echo ($preferences['notification_email']) ? 'checked' : ''; ?>>
                                            이메일 알림 수신
                                        </label>
                                    </div>
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" name="notification_sms" <?php echo ($preferences['notification_sms']) ? 'checked' : ''; ?>>
                                            SMS 알림 수신
                                        </label>
                                    </div>
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" name="notification_push" <?php echo ($preferences['notification_push']) ? 'checked' : ''; ?>>
                                            앱 푸시 알림 수신
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Marketing Consent -->
                            <div class="form-group">
                                <label class="control-label col-md-3">마케팅 동의:</label>
                                <div class="col-md-6">
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" name="marketing_consent" <?php echo ($preferences['marketing_consent']) ? 'checked' : ''; ?>>
                                            마케팅 정보 수신에 동의합니다
                                        </label>
                                    </div>
                                    <span class="help-block">프로모션, 이벤트, 신규 복권 정보 등 마케팅 정보를 수신합니다.</span>
                                </div>
                            </div>
                            
                            <!-- Submit Button -->
                            <div class="form-group">
                                <div class="col-md-offset-3 col-md-6">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fa fa-save"></i> 설정 저장
                                    </button>
                                    <a href="customer-details.php?id=<?php echo $customerId; ?>" class="btn btn-default">
                                        <i class="fa fa-arrow-left"></i> 고객 정보로 돌아가기
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <?php else: ?>
        <!-- No Customer Selected -->
        <div class="row">
            <div class="col-md-12">
                <div class="alert alert-warning" role="alert">
                    <i class="fa fa-exclamation-triangle"></i> 고객을 선택해주세요.
                    <a href="customer-list.php" class="btn btn-sm btn-warning">고객 목록으로 이동</a>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php
// Include footer template
include '../../templates/footer.php';
?>
