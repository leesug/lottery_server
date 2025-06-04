<?php
/**
 * 유지보수 취소 처리
 * 
 * 이 페이지는 유지보수 일정을 취소 처리합니다.
 */

// 세션 및 필수 파일 포함
require_once '../../includes/auth.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// 페이지 접근 권한 확인
// functions.php에 정의된 check_permission 함수 사용
if (function_exists('check_permission')) {
    check_permission('store_management');
} else {
    // 함수가 로드되지 않은 경우 로그 기록
    error_log("check_permission 함수를 찾을 수 없습니다. (equipment-maintenance-cancel.php)");
}

// POST 요청이 아니면 리다이렉트
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_to('/dashboard/store/equipment-list.php');
    exit;
}

// CSRF 토큰 검증
verify_csrf_token();

// 입력 값 검증
$maintenance_id = isset($_POST['maintenance_id']) ? intval($_POST['maintenance_id']) : 0;
$equipment_id = isset($_POST['equipment_id']) ? intval($_POST['equipment_id']) : 0;
$cancellation_reason = isset($_POST['cancellation_reason']) ? sanitize_input($_POST['cancellation_reason']) : '';

// 로그 기록
log_activity('유지보수 취소 처리 시도', '유지보수 ID: ' . $maintenance_id . ', 장비 ID: ' . $equipment_id);

// 필수 매개변수 검증
if ($maintenance_id <= 0 || $equipment_id <= 0) {
    set_flash_message('error', '잘못된 요청입니다. 유효한 ID가 필요합니다.');
    redirect_to('/dashboard/store/equipment-list.php');
    exit;
}

// 취소 사유 검증
if (empty($cancellation_reason)) {
    set_flash_message('error', '취소 사유를 입력해주세요.');
    redirect_to('/dashboard/store/equipment-maintenance.php?equipment_id=' . $equipment_id);
    exit;
}

// 유지보수 정보 가져오기
$maintenance_query = "SELECT * FROM store_equipment_maintenance WHERE id = ?";
$stmt = $conn->prepare($maintenance_query);

if ($stmt) {
    $stmt->bind_param("i", $maintenance_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $maintenance = $result->fetch_assoc();
    $stmt->close();
    
    if (!$maintenance) {
        set_flash_message('error', '해당 유지보수 정보를 찾을 수 없습니다.');
        redirect_to('/dashboard/store/equipment-maintenance.php?equipment_id=' . $equipment_id);
        exit;
    }
    
    // 장비 ID 일치 여부 확인
    if ($maintenance['equipment_id'] != $equipment_id) {
        set_flash_message('error', '유지보수 정보가 지정된 장비와 일치하지 않습니다.');
        redirect_to('/dashboard/store/equipment-maintenance.php?equipment_id=' . $equipment_id);
        exit;
    }
    
    // 이미 완료 또는 취소 상태인지 확인
    if ($maintenance['status'] === 'completed' || $maintenance['status'] === 'cancelled') {
        set_flash_message('error', '이미 완료되거나 취소된 유지보수 일정입니다.');
        redirect_to('/dashboard/store/equipment-maintenance.php?equipment_id=' . $equipment_id);
        exit;
    }
} else {
    log_error("유지보수 정보 조회 실패: " . $conn->error);
    set_flash_message('error', '데이터베이스 오류로 인해 작업을 완료할 수 없습니다.');
    redirect_to('/dashboard/store/equipment-maintenance.php?equipment_id=' . $equipment_id);
    exit;
}

// 트랜잭션 시작
$conn->begin_transaction();

try {
    // 유지보수 정보 업데이트
    $update_query = "
        UPDATE store_equipment_maintenance
        SET 
            status = 'cancelled',
            resolution = CONCAT('취소됨: ', ?),
            updated_at = NOW()
        WHERE id = ?
    ";
    
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("si", $cancellation_reason, $maintenance_id);
    $update_result = $update_stmt->execute();
    $update_stmt->close();
    
    if (!$update_result) {
        throw new Exception("유지보수 정보 업데이트 실패: " . $conn->error);
    }
    
    // 상태 변경 로그 기록
    $status_log_query = "
        INSERT INTO activity_logs (
            user_id, 
            activity_type, 
            description, 
            ip_address, 
            user_agent
        ) VALUES (?, 'maintenance_cancel', ?, ?, ?)
    ";
    
    $admin_id = $_SESSION['admin_id'] ?? 0;
    $log_description = "유지보수 ID: {$maintenance_id} 취소 처리됨, 장비 ID: {$equipment_id}, 사유: " . substr($cancellation_reason, 0, 100);
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    $log_stmt = $conn->prepare($status_log_query);
    $log_stmt->bind_param("isss", $admin_id, $log_description, $ip_address, $user_agent);
    $log_stmt->execute();
    $log_stmt->close();
    
    // 트랜잭션 커밋
    $conn->commit();
    
    // 성공 메시지 설정
    set_flash_message('success', '유지보수 일정이 성공적으로 취소 처리되었습니다.');
    
    // 유지보수 관리 페이지로 리다이렉트
    redirect_to('/dashboard/store/equipment-maintenance.php?equipment_id=' . $equipment_id);
    
} catch (Exception $e) {
    // 트랜잭션 롤백
    $conn->rollback();
    
    // 오류 로그 기록
    log_error("유지보수 취소 처리 실패: " . $e->getMessage());
    
    // 오류 메시지 설정
    set_flash_message('error', '유지보수 취소 처리 중 오류가 발생했습니다: ' . $e->getMessage());
    
    // 유지보수 관리 페이지로 리다이렉트
    redirect_to('/dashboard/store/equipment-maintenance.php?equipment_id=' . $equipment_id);
}
