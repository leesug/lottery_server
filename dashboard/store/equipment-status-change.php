<?php
/**
 * 판매점 장비 상태 변경 처리
 * 
 * 이 페이지는 장비의 상태를 변경하고 유지보수 로그를 기록합니다.
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
    error_log("check_permission 함수를 찾을 수 없습니다. (equipment-status-change.php)");
}

// POST 요청이 아니면 리다이렉트
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_to('/dashboard/store/equipment-list.php');
    exit;
}

// CSRF 토큰 검증
verify_csrf_token();

// 입력 값 검증
$equipment_id = isset($_POST['equipment_id']) ? intval($_POST['equipment_id']) : 0;
$new_status = isset($_POST['new_status']) ? sanitize_input($_POST['new_status']) : '';
$notes = isset($_POST['notes']) ? sanitize_input($_POST['notes']) : '';

// 로그 기록
log_activity('장비 상태 변경 시도', '장비 ID: ' . $equipment_id . ', 새 상태: ' . $new_status);

// 장비 ID 및 상태 검증
if ($equipment_id <= 0 || empty($new_status)) {
    set_flash_message('error', '잘못된 요청입니다. 장비 ID와 새 상태가 필요합니다.');
    redirect_to('/dashboard/store/equipment-list.php');
    exit;
}

// 허용된 상태 값 검증
$allowed_statuses = ['operational', 'maintenance', 'faulty', 'replaced', 'retired'];
if (!in_array($new_status, $allowed_statuses)) {
    set_flash_message('error', '허용되지 않은 상태 값입니다.');
    redirect_to('/dashboard/store/equipment-list.php');
    exit;
}

// 현재 장비 정보 및 상태 가져오기
$equipment_query = "SELECT id, store_id, equipment_type, status FROM store_equipment WHERE id = ?";
$stmt = $conn->prepare($equipment_query);

if ($stmt) {
    $stmt->bind_param("i", $equipment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $equipment = $result->fetch_assoc();
    $stmt->close();
    
    if (!$equipment) {
        set_flash_message('error', '장비를 찾을 수 없습니다.');
        redirect_to('/dashboard/store/equipment-list.php');
        exit;
    }
    
    // 이미 같은 상태이면 변경하지 않음
    if ($equipment['status'] === $new_status) {
        set_flash_message('info', '장비가 이미 선택한 상태입니다.');
        redirect_to('/dashboard/store/equipment-list.php');
        exit;
    }
    
    // 트랜잭션 시작
    $conn->begin_transaction();
    
    try {
        // 장비 상태 업데이트
        $update_query = "UPDATE store_equipment SET status = ?, updated_at = NOW() WHERE id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("si", $new_status, $equipment_id);
        $update_result = $update_stmt->execute();
        $update_stmt->close();
        
        if (!$update_result) {
            throw new Exception("장비 상태 업데이트 실패: " . $conn->error);
        }
        
        // 유지보수 기록 생성 (상태가 'maintenance'로 변경될 때)
        if ($new_status === 'maintenance') {
            $maintenance_query = "
                INSERT INTO store_equipment_maintenance (
                    equipment_id, 
                    maintenance_type, 
                    maintenance_date, 
                    issue_description, 
                    status
                ) VALUES (?, 'routine', CURDATE(), ?, 'scheduled')
            ";
            
            $maintenance_stmt = $conn->prepare($maintenance_query);
            $maintenance_stmt->bind_param("is", $equipment_id, $notes);
            $maintenance_result = $maintenance_stmt->execute();
            $maintenance_stmt->close();
            
            if (!$maintenance_result) {
                throw new Exception("유지보수 기록 생성 실패: " . $conn->error);
            }
        }
        
        // 마지막 유지보수 날짜 업데이트 (상태가 'operational'로 변경될 때)
        if ($new_status === 'operational' && ($equipment['status'] === 'maintenance' || $equipment['status'] === 'faulty')) {
            $last_maintenance_query = "
                UPDATE store_equipment 
                SET last_maintenance_date = CURDATE(),
                    next_maintenance_date = DATE_ADD(CURDATE(), INTERVAL 3 MONTH)
                WHERE id = ?
            ";
            
            $last_maintenance_stmt = $conn->prepare($last_maintenance_query);
            $last_maintenance_stmt->bind_param("i", $equipment_id);
            $last_maintenance_result = $last_maintenance_stmt->execute();
            $last_maintenance_stmt->close();
            
            if (!$last_maintenance_result) {
                throw new Exception("마지막 유지보수 날짜 업데이트 실패: " . $conn->error);
            }
            
            // 관련 유지보수 작업 완료 처리
            $complete_maintenance_query = "
                UPDATE store_equipment_maintenance 
                SET status = 'completed', 
                    resolution = ?, 
                    maintenance_date = CURDATE()
                WHERE equipment_id = ? AND status = 'scheduled' OR status = 'in_progress'
                ORDER BY id DESC LIMIT 1
            ";
            
            $resolution = !empty($notes) ? $notes : '정기 유지보수 완료 및 장비 정상화';
            $complete_maintenance_stmt = $conn->prepare($complete_maintenance_query);
            $complete_maintenance_stmt->bind_param("si", $resolution, $equipment_id);
            $complete_maintenance_result = $complete_maintenance_stmt->execute();
            $complete_maintenance_stmt->close();
            
            if (!$complete_maintenance_result) {
                throw new Exception("유지보수 작업 완료 처리 실패: " . $conn->error);
            }
        }
        
        // 로그 기록
        $admin_id = $_SESSION['admin_id'] ?? 0;
        $log_query = "
            INSERT INTO activity_logs (
                user_id, 
                activity_type, 
                description, 
                ip_address, 
                user_agent
            ) VALUES (?, 'equipment_status_change', ?, ?, ?)
        ";
        
        $log_description = "장비 ID: {$equipment_id}, 이전 상태: {$equipment['status']}, 새 상태: {$new_status}" . (!empty($notes) ? ", 비고: {$notes}" : "");
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        $log_stmt = $conn->prepare($log_query);
        $log_stmt->bind_param("isss", $admin_id, $log_description, $ip_address, $user_agent);
        $log_stmt->execute();
        $log_stmt->close();
        
        // 트랜잭션 커밋
        $conn->commit();
        
        // 성공 메시지 설정
        set_flash_message('success', '장비 상태가 성공적으로 변경되었습니다.');
        
        // 장비 목록 페이지로 리다이렉트
        redirect_to('/dashboard/store/equipment-list.php');
        
    } catch (Exception $e) {
        // 트랜잭션 롤백
        $conn->rollback();
        
        // 오류 로그 기록
        log_error("장비 상태 변경 실패: " . $e->getMessage());
        
        // 오류 메시지 설정
        set_flash_message('error', '장비 상태 변경 중 오류가 발생했습니다: ' . $e->getMessage());
        
        // 장비 목록 페이지로 리다이렉트
        redirect_to('/dashboard/store/equipment-list.php');
    }
    
} else {
    // 쿼리 준비 실패
    log_error("장비 상태 변경 쿼리 준비 실패: " . $conn->error);
    set_flash_message('error', '데이터베이스 오류로 인해 작업을 완료할 수 없습니다.');
    redirect_to('/dashboard/store/equipment-list.php');
}
