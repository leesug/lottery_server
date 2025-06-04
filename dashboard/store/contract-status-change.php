<?php
/**
 * Contract Status Change Page
 * 
 * This page processes the activation/termination/renewal of a store contract.
 */

// Include required files
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

// Check if user has access to this page
checkPageAccess('store_management');

// Check if the request is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Redirect to store list if not a POST request
    header('Location: store-list.php');
    exit;
}

// Validate CSRF token
validateCsrfToken($_POST['csrf_token']);

// Get parameters
$contractId = isset($_POST['contract_id']) ? intval($_POST['contract_id']) : 0;
$action = isset($_POST['action']) ? sanitizeInput($_POST['action']) : '';
$reason = isset($_POST['reason']) ? sanitizeInput($_POST['reason']) : '';
$notes = isset($_POST['notes']) ? sanitizeInput($_POST['notes']) : '';
$terminationDate = isset($_POST['termination_date']) ? sanitizeInput($_POST['termination_date']) : date('Y-m-d');

// Validate parameters
if ($contractId <= 0) {
    $_SESSION['message'] = "잘못된 계약 ID입니다.";
    $_SESSION['message_type'] = "danger";
    header('Location: store-list.php');
    exit;
}

if (!in_array($action, ['activate', 'terminate', 'mark_renewal'])) {
    $_SESSION['message'] = "잘못된 작업입니다.";
    $_SESSION['message_type'] = "danger";
    header('Location: store-list.php');
    exit;
}

// Database connection
$db = getDbConnection();

// Get contract information
$stmt = $db->prepare("
    SELECT c.*, s.id as store_id, s.store_name, s.store_code
    FROM store_contracts c
    JOIN stores s ON c.store_id = s.id
    WHERE c.id = ?
");
$stmt->bind_param("i", $contractId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['message'] = "계약을 찾을 수 없습니다.";
    $_SESSION['message_type'] = "danger";
    header('Location: store-list.php');
    exit;
}

$contract = $result->fetch_assoc();
$storeId = $contract['store_id'];
$stmt->close();

// Begin transaction
$db->begin_transaction();

try {
    // Perform the action
    switch ($action) {
        case 'activate':
            // If there's already an active contract, expire it
            $expireStmt = $db->prepare("
                UPDATE store_contracts 
                SET status = 'expired' 
                WHERE store_id = ? AND status = 'active' AND id != ?
            ");
            $expireStmt->bind_param("ii", $storeId, $contractId);
            $expireStmt->execute();
            $expireStmt->close();
            
            // Activate this contract
            $activateStmt = $db->prepare("
                UPDATE store_contracts 
                SET status = 'active' 
                WHERE id = ?
            ");
            $activateStmt->bind_param("i", $contractId);
            $activateStmt->execute();
            $activateStmt->close();
            
            // Ensure store is active
            $storeStmt = $db->prepare("
                UPDATE stores 
                SET status = 'active' 
                WHERE id = ? AND status != 'terminated'
            ");
            $storeStmt->bind_param("i", $storeId);
            $storeStmt->execute();
            $storeStmt->close();
            
            // Set message
            $_SESSION['message'] = "계약이 성공적으로 활성화되었습니다.";
            $_SESSION['message_type'] = "success";
            
            // Log activity
            logActivity(
                'contract_activate',
                sprintf("계약 %s (%s)가 활성화되었습니다.", $contract['contract_number'], $contract['store_name']),
                $contractId,
                'store_contracts'
            );
            break;
            
        case 'terminate':
            // Validate termination reason
            if (empty($reason)) {
                throw new Exception("해지 사유를 입력해야 합니다.");
            }
            
            // Terminate the contract
            $terminateStmt = $db->prepare("
                UPDATE store_contracts 
                SET status = 'terminated', 
                    termination_date = ?, 
                    termination_reason = ? 
                WHERE id = ?
            ");
            $terminateStmt->bind_param("ssi", $terminationDate, $reason, $contractId);
            $terminateStmt->execute();
            $terminateStmt->close();
            
            // Check if this was the only active contract
            $activeContractStmt = $db->prepare("
                SELECT COUNT(*) as active_count 
                FROM store_contracts 
                WHERE store_id = ? AND status = 'active'
            ");
            $activeContractStmt->bind_param("i", $storeId);
            $activeContractStmt->execute();
            $activeContractResult = $activeContractStmt->get_result();
            $activeContractCount = $activeContractResult->fetch_assoc()['active_count'];
            $activeContractStmt->close();
            
            // If no active contracts left, mark store as inactive
            if ($activeContractCount === 0) {
                $storeStmt = $db->prepare("
                    UPDATE stores 
                    SET status = 'inactive' 
                    WHERE id = ?
                ");
                $storeStmt->bind_param("i", $storeId);
                $storeStmt->execute();
                $storeStmt->close();
            }
            
            // Set message
            $_SESSION['message'] = "계약이 성공적으로 해지되었습니다.";
            $_SESSION['message_type'] = "success";
            
            // Log activity
            logActivity(
                'contract_terminate',
                sprintf("계약 %s (%s)가 해지되었습니다. 사유: %s", $contract['contract_number'], $contract['store_name'], $reason),
                $contractId,
                'store_contracts'
            );
            break;
            
        case 'mark_renewal':
            // Mark the contract for renewal
            $renewalStmt = $db->prepare("
                UPDATE store_contracts 
                SET status = 'renewal_pending' 
                WHERE id = ?
            ");
            $renewalStmt->bind_param("i", $contractId);
            $renewalStmt->execute();
            $renewalStmt->close();
            
            // Set message
            $_SESSION['message'] = "계약이 갱신 대기 상태로 변경되었습니다. 계약 갱신 페이지에서 세부 사항을 설정하세요.";
            $_SESSION['message_type'] = "success";
            
            // Log activity
            logActivity(
                'contract_renewal_mark',
                sprintf("계약 %s (%s)가 갱신 대기 상태로 변경되었습니다.", $contract['contract_number'], $contract['store_name']),
                $contractId,
                'store_contracts'
            );
            break;
    }
    
    // Commit transaction
    $db->commit();
    
    // Redirect back to the store contracts page
    header('Location: store-contracts.php?store_id=' . $storeId);
    exit;
} catch (Exception $e) {
    // Rollback transaction on error
    $db->rollback();
    
    // Set error message
    $_SESSION['message'] = "계약 상태 변경 중 오류가 발생했습니다: " . $e->getMessage();
    $_SESSION['message_type'] = "danger";
    
    // Log error
    logError(
        'contract_status_change_error',
        sprintf("Error changing contract status: %s", $e->getMessage()),
        $contractId,
        'store_contracts'
    );
    
    // Redirect back to the store contracts page
    header('Location: store-contracts.php?store_id=' . $storeId);
    exit;
}
