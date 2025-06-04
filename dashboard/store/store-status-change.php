<?php
/**
 * Store Status Change Page
 * 
 * This page processes the activation/deactivation/termination of a store.
 */

// Include required files
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

// Check if user has access to this page
checkPageAccess('store_management');

// Initialize response
$response = [
    'success' => false,
    'message' => '',
    'redirect' => ''
];

// Check if the request is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Redirect to store list if not a POST request
    header('Location: store-list.php');
    exit;
}

// Validate CSRF token
validateCsrfToken($_POST['csrf_token']);

// Get parameters
$storeId = isset($_POST['store_id']) ? intval($_POST['store_id']) : 0;
$action = isset($_POST['action']) ? sanitizeInput($_POST['action']) : '';
$reason = isset($_POST['reason']) ? sanitizeInput($_POST['reason']) : '';
$notes = isset($_POST['notes']) ? sanitizeInput($_POST['notes']) : '';

// Validate parameters
if ($storeId <= 0) {
    $_SESSION['message'] = "잘못된 판매점 ID입니다.";
    $_SESSION['message_type'] = "danger";
    header('Location: store-list.php');
    exit;
}

if (!in_array($action, ['activate', 'deactivate', 'terminate'])) {
    $_SESSION['message'] = "잘못된 작업입니다.";
    $_SESSION['message_type'] = "danger";
    header('Location: store-details.php?id=' . $storeId);
    exit;
}

// Database connection
$db = getDbConnection();

// Get store information
$stmt = $db->prepare("SELECT store_name, store_code, status FROM stores WHERE id = ?");
$stmt->bind_param("i", $storeId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['message'] = "판매점을 찾을 수 없습니다.";
    $_SESSION['message_type'] = "danger";
    header('Location: store-list.php');
    exit;
}

$store = $result->fetch_assoc();
$stmt->close();

// Perform the action
$newStatus = '';
$actionDescription = '';

switch ($action) {
    case 'activate':
        $newStatus = 'active';
        $actionDescription = '활성화';
        break;
    case 'deactivate':
        $newStatus = 'inactive';
        $actionDescription = '비활성화';
        break;
    case 'terminate':
        $newStatus = 'terminated';
        $actionDescription = '계약해지';
        break;
}

// Update the store status
$updateSql = "UPDATE stores SET status = ? WHERE id = ?";
$updateStmt = $db->prepare($updateSql);
$updateStmt->bind_param("si", $newStatus, $storeId);

if ($updateStmt->execute()) {
    // Log the status change
    $logMessage = sprintf(
        "판매점 %s (%s)가 %s 상태로 변경되었습니다. %s", 
        $store['store_name'], 
        $store['store_code'], 
        $actionDescription,
        !empty($reason) ? "사유: " . $reason : (!empty($notes) ? "비고: " . $notes : "")
    );
    
    logActivity(
        'store_status_change',
        $logMessage,
        $storeId,
        'stores'
    );
    
    // If this was a termination, handle any active contracts
    if ($action === 'terminate') {
        // Update any active contracts to terminated status
        $contractSql = "UPDATE store_contracts SET status = 'terminated', termination_date = CURDATE(), termination_reason = ? WHERE store_id = ? AND status = 'active'";
        $contractStmt = $db->prepare($contractSql);
        $contractStmt->bind_param("si", $reason, $storeId);
        $contractStmt->execute();
        $contractStmt->close();
    }
    
    // Set success message
    $_SESSION['message'] = sprintf("판매점이 성공적으로 %s되었습니다.", $actionDescription);
    $_SESSION['message_type'] = "success";
} else {
    // Set error message
    $_SESSION['message'] = sprintf("판매점 %s 처리 중 오류가 발생했습니다: %s", $actionDescription, $db->error);
    $_SESSION['message_type'] = "danger";
    
    // Log error
    logError(
        'store_status_change_error',
        sprintf("Error changing store status: %s", $db->error),
        $storeId,
        'stores'
    );
}

$updateStmt->close();

// Redirect back to the store details page
header('Location: store-details.php?id=' . $storeId);
exit;
