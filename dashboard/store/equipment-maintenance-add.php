<?php
/**
 * 유지보수 추가 페이지
 * 
 * 이 페이지는 장비에 대한 새 유지보수 일정을 추가하는 양식을 제공합니다.
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
    error_log("check_permission 함수를 찾을 수 없습니다. (equipment-maintenance-add.php)");
}

// 장비 ID 가져오기
$equipment_id = isset($_GET['equipment_id']) ? intval($_GET['equipment_id']) : 0;

// 유효한 ID가 아니면 목록으로 리다이렉트
if ($equipment_id <= 0) {
    set_flash_message('error', '유효한 장비 ID가 필요합니다.');
    redirect_to('/dashboard/store/equipment-list.php');
    exit;
}

// 장비 정보 가져오기
$equipment_query = "
    SELECT e.*, s.store_name, s.store_code
    FROM store_equipment e
    JOIN stores s ON e.store_id = s.id
    WHERE e.id = ?
";

$equipment = null;
$stmt = $conn->prepare($equipment_query);

if ($stmt) {
    $stmt->bind_param("i", $equipment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $equipment = $result->fetch_assoc();
    $stmt->close();
    
    if (!$equipment) {
        set_flash_message('error', '해당 장비를 찾을 수 없습니다.');
        redirect_to('/dashboard/store/equipment-list.php');
        exit;
    }
    
    // 장비 코드 확인
    if (empty($equipment['equipment_code'])) {
        // 장비 코드가 없는 경우 생성하여 업데이트
        $equipment_code = generate_equipment_code($equipment['equipment_type']);
        $update_code_query = "UPDATE store_equipment SET equipment_code = ? WHERE id = ?";
        $update_code_stmt = $conn->prepare($update_code_query);
        $update_code_stmt->bind_param("si", $equipment_code, $equipment_id);
        $update_code_stmt->execute();
        $update_code_stmt->close();
        
        // 업데이트된 장비 정보 다시 가져오기
        $stmt = $conn->prepare($equipment_query);
        $stmt->bind_param("i", $equipment_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $equipment = $result->fetch_assoc();
        $stmt->close();
    }
} else {
    log_error("장비 정보 쿼리 준비 실패: " . $conn->error);
    set_flash_message('error', '데이터베이스 오류로 인해 장비 정보를 가져올 수 없습니다.');
    redirect_to('/dashboard/store/equipment-list.php');
    exit;
}

// 페이지 제목 설정
$page_title = '새 유지보수 일정 추가';

// 헤더 인클루드
include_once '../../templates/dashboard-header.php';

// 장비 아이콘 및 상태 정보
function get_equipment_icon($type) {
    $icon_map = [
        'terminal' => 'fas fa-desktop',
        'printer' => 'fas fa-print',
        'scanner' => 'fas fa-barcode',
        'display' => 'fas fa-tv',
        'router' => 'fas fa-wifi',
        'other' => 'fas fa-hdd'
    ];
    
    return $icon_map[$type] ?? 'fas fa-question-circle';
}

function get_equipment_status_label_and_class($status) {
    $status_map = [
        'operational' => ['정상 작동', 'success'],
        'maintenance' => ['유지보수 중', 'warning'],
        'faulty' => ['고장', 'danger'],
        'replaced' => ['교체됨', 'info'],
        'retired' => ['폐기됨', 'secondary']
    ];
    
    return $status_map[$status] ?? ['알 수 없음', 'dark'];
}

$equipment_icon = get_equipment_icon($equipment['equipment_type']);
[$status_label, $status_class] = get_equipment_status_label_and_class($equipment['status']);

// POST 요청 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF 토큰 검증
    verify_csrf_token();
    
    // 폼 데이터 검증
    $maintenance_type = isset($_POST['maintenance_type']) ? sanitize_input($_POST['maintenance_type']) : '';
    $maintenance_date = isset($_POST['maintenance_date']) ? sanitize_input($_POST['maintenance_date']) : '';
    $maintenance_time = isset($_POST['maintenance_time']) ? sanitize_input($_POST['maintenance_time']) : null;
    $technician_name = isset($_POST['technician_name']) ? sanitize_input($_POST['technician_name']) : '';
    $technician_contact = isset($_POST['technician_contact']) ? sanitize_input($_POST['technician_contact']) : '';
    $issue_description = isset($_POST['issue_description']) ? sanitize_input($_POST['issue_description']) : '';
    $status = isset($_POST['status']) ? sanitize_input($_POST['status']) : 'scheduled';
    $resolution = isset($_POST['resolution']) ? sanitize_input($_POST['resolution']) : '';
    $parts_replaced = isset($_POST['parts_replaced']) ? sanitize_input($_POST['parts_replaced']) : '';
    $cost = isset($_POST['cost']) ? (float) $_POST['cost'] : 0.00;
    
    // 필수 필드 검증
    $errors = [];
    
    if (empty($maintenance_type)) {
        $errors[] = "유지보수 유형을 선택해주세요.";
    }
    
    if (empty($maintenance_date)) {
        $errors[] = "유지보수 날짜를 입력해주세요.";
    }
    
    // 상태가 완료됨인데 해결책이 없는 경우
    if ($status === 'completed' && empty($resolution)) {
        $errors[] = "완료된 유지보수에는 해결책/작업 결과를 입력해야 합니다.";
    }
    
    // 에러가 없으면 데이터 저장
    if (empty($errors)) {
        // 트랜잭션 시작
        $conn->begin_transaction();
        
        try {
            // 장비 코드 가져오기
            $equipment_code = $equipment['equipment_code'] ?? '';
            
            // 유지보수 코드 생성
            $maintenance_code = generate_maintenance_code($maintenance_type, $equipment_code);
            
            // 유지보수 일정 추가
            $insert_query = "
                INSERT INTO store_equipment_maintenance (
                    equipment_id,
                    maintenance_code,
                    maintenance_type,
                    maintenance_date,
                    maintenance_time,
                    technician_name,
                    technician_contact,
                    issue_description,
                    resolution,
                    parts_replaced,
                    cost,
                    status,
                    created_at,
                    updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ";
            
            $insert_stmt = $conn->prepare($insert_query);
            $insert_stmt->bind_param(
                "isssssssssds",
                $equipment_id,
                $maintenance_code,
                $maintenance_type,
                $maintenance_date,
                $maintenance_time,
                $technician_name,
                $technician_contact,
                $issue_description,
                $resolution,
                $parts_replaced,
                $cost,
                $status
            );
            
            $insert_result = $insert_stmt->execute();
            $maintenance_id = $insert_stmt->insert_id;
            $insert_stmt->close();
            
            if (!$insert_result) {
                throw new Exception("유지보수 일정 추가 실패: " . $conn->error);
            }
            
            // 상태가 'completed'일 경우 장비의 last_maintenance_date 업데이트
            if ($status === 'completed') {
                $update_query = "
                    UPDATE store_equipment
                    SET last_maintenance_date = ?,
                        next_maintenance_date = DATE_ADD(?, INTERVAL 3 MONTH),
                        updated_at = NOW()
                    WHERE id = ?
                ";
                
                $update_stmt = $conn->prepare($update_query);
                $update_stmt->bind_param("ssi", $maintenance_date, $maintenance_date, $equipment_id);
                $update_result = $update_stmt->execute();
                $update_stmt->close();
                
                if (!$update_result) {
                    throw new Exception("장비 유지보수 정보 업데이트 실패: " . $conn->error);
                }
                
                // 장비가 유지보수 중 상태였다면 상태를 정상 작동으로 변경
                if ($equipment['status'] === 'maintenance') {
                    $equipment_status_query = "
                        UPDATE store_equipment
                        SET status = 'operational',
                            updated_at = NOW()
                        WHERE id = ?
                    ";
                    
                    $equipment_status_stmt = $conn->prepare($equipment_status_query);
                    $equipment_status_stmt->bind_param("i", $equipment_id);
                    $equipment_status_stmt->execute();
                    $equipment_status_stmt->close();
                }
            } 
            // 상태가 'scheduled' 또는 'in_progress'이고 장비가 고장 상태면 유지보수 중으로 변경
            else if (($status === 'scheduled' || $status === 'in_progress') && $equipment['status'] === 'faulty') {
                $equipment_status_query = "
                    UPDATE store_equipment
                    SET status = 'maintenance',
                        updated_at = NOW()
                    WHERE id = ?
                ";
                
                $equipment_status_stmt = $conn->prepare($equipment_status_query);
                $equipment_status_stmt->bind_param("i", $equipment_id);
                $equipment_status_stmt->execute();
                $equipment_status_stmt->close();
            }
            
            // 로그 기록
            $log_query = "
                INSERT INTO activity_logs (
                    user_id, 
                    activity_type, 
                    description, 
                    ip_address, 
                    user_agent
                ) VALUES (?, 'maintenance_add', ?, ?, ?)
            ";
            
            $admin_id = $_SESSION['admin_id'] ?? 0;
            $log_description = "장비 ID: {$equipment_id}에 대한 새 유지보수 일정 추가 (유형: {$maintenance_type}, 코드: {$maintenance_code}, 상태: {$status})";
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            
            $log_stmt = $conn->prepare($log_query);
            $log_stmt->bind_param("isss", $admin_id, $log_description, $ip_address, $user_agent);
            $log_stmt->execute();
            $log_stmt->close();
            
            // 트랜잭션 커밋
            $conn->commit();
            
            // 성공 메시지 설정
            set_flash_message('success', "새 유지보수 일정(코드: {$maintenance_code})이 성공적으로 추가되었습니다.");
            
            // 유지보수 관리 페이지로 리다이렉트
            redirect_to("/dashboard/store/equipment-maintenance.php?equipment_id={$equipment_id}");
            exit;
        } catch (Exception $e) {
            // 트랜잭션 롤백
            $conn->rollback();
            
            // 오류 로그 기록
            log_error("유지보수 일정 추가 실패: " . $e->getMessage());
            
            // 오류 메시지 설정
            $errors[] = "유지보수 일정 추가 중 오류가 발생했습니다: " . $e->getMessage();
        }
    }
}
?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><?php echo $page_title; ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="/dashboard/index.php">대시보드</a></li>
        <li class="breadcrumb-item"><a href="/dashboard/store/store-list.php">판매점 관리</a></li>
        <li class="breadcrumb-item"><a href="/dashboard/store/equipment-list.php">장비 관리</a></li>
        <li class="breadcrumb-item"><a href="/dashboard/store/equipment-details.php?id=<?php echo $equipment_id; ?>">장비 상세 정보</a></li>
        <li class="breadcrumb-item active">유지보수 추가</li>
    </ol>
    
    <!-- 작업 버튼 -->
    <div class="mb-4">
        <a href="/dashboard/store/equipment-maintenance.php?equipment_id=<?php echo $equipment_id; ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> 유지보수 목록으로 돌아가기
        </a>
    </div>
    
    <!-- 오류 메시지 출력 -->
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <!-- 장비 정보 카드 -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="<?php echo $equipment_icon; ?> me-1"></i>
            장비 정보
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <h6 class="text-muted">장비 ID</h6>
                    <p class="font-monospace">#<?php echo $equipment['id']; ?></p>
                </div>
                <div class="col-md-3 mb-3">
                    <h6 class="text-muted">장비 코드</h6>
                    <p class="font-monospace"><?php echo htmlspecialchars($equipment['equipment_code'] ?? 'N/A'); ?></p>
                </div>
                <div class="col-md-3 mb-3">
                    <h6 class="text-muted">장비 유형</h6>
                    <p><i class="<?php echo $equipment_icon; ?> me-1"></i> <?php echo get_equipment_type_label($equipment['equipment_type']); ?></p>
                </div>
                <div class="col-md-3 mb-3">
                    <h6 class="text-muted">장비 모델</h6>
                    <p><?php echo htmlspecialchars($equipment['model_name'] ?? 'N/A'); ?></p>
                </div>
                <div class="col-md-3 mb-3">
                    <h6 class="text-muted">시리얼 번호</h6>
                    <p class="font-monospace"><?php echo htmlspecialchars($equipment['serial_number']); ?></p>
                </div>
                <div class="col-md-3 mb-3">
                    <h6 class="text-muted">상태</h6>
                    <p><span class="badge bg-<?php echo $status_class; ?>"><?php echo $status_label; ?></span></p>
                </div>
                <div class="col-md-3 mb-3">
                    <h6 class="text-muted">설치일</h6>
                    <p><?php echo date('Y년 m월 d일', strtotime($equipment['installation_date'])); ?></p>
                </div>
                <div class="col-md-3 mb-3">
                    <h6 class="text-muted">마지막 유지보수</h6>
                    <p>
                        <?php 
                        if (!empty($equipment['last_maintenance_date'])) {
                            echo date('Y년 m월 d일', strtotime($equipment['last_maintenance_date']));
                        } else {
                            echo '<span class="text-muted">없음</span>';
                        }
                        ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 유지보수 추가 양식 -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-tools me-1"></i>
            유지보수 정보 입력
        </div>
        <div class="card-body">
            <form method="post" action="" class="needs-validation" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                
                <div class="row mb-3">
                    <!-- 유지보수 유형 -->
                    <div class="col-md-6">
                        <label for="maintenance_type" class="form-label">유지보수 유형 <span class="text-danger">*</span></label>
                        <select name="maintenance_type" id="maintenance_type" class="form-select" required>
                            <option value="">유형 선택</option>
                            <option value="regular" <?php echo isset($_POST['maintenance_type']) && $_POST['maintenance_type'] === 'regular' ? 'selected' : ''; ?>>정기 점검</option>
                            <option value="repair" <?php echo isset($_POST['maintenance_type']) && $_POST['maintenance_type'] === 'repair' ? 'selected' : ''; ?>>고장 수리</option>
                            <option value="upgrade" <?php echo isset($_POST['maintenance_type']) && $_POST['maintenance_type'] === 'upgrade' ? 'selected' : ''; ?>>업그레이드/교체</option>
                            <option value="inspection" <?php echo isset($_POST['maintenance_type']) && $_POST['maintenance_type'] === 'inspection' ? 'selected' : ''; ?>>특별 점검</option>
                            <option value="other" <?php echo isset($_POST['maintenance_type']) && $_POST['maintenance_type'] === 'other' ? 'selected' : ''; ?>>기타</option>
                        </select>
                        <div class="invalid-feedback">유지보수 유형을 선택해주세요.</div>
                    </div>
                    
                    <!-- 유지보수 상태 -->
                    <div class="col-md-6">
                        <label for="status" class="form-label">상태 <span class="text-danger">*</span></label>
                        <select name="status" id="status" class="form-select" required>
                            <option value="scheduled" <?php echo isset($_POST['status']) && $_POST['status'] === 'scheduled' ? 'selected' : ''; ?>>예정됨</option>
                            <option value="in_progress" <?php echo isset($_POST['status']) && $_POST['status'] === 'in_progress' ? 'selected' : ''; ?>>진행 중</option>
                            <option value="completed" <?php echo isset($_POST['status']) && $_POST['status'] === 'completed' ? 'selected' : ''; ?>>완료됨</option>
                            <option value="cancelled" <?php echo isset($_POST['status']) && $_POST['status'] === 'cancelled' ? 'selected' : ''; ?>>취소됨</option>
                        </select>
                        <div class="invalid-feedback">상태를 선택해주세요.</div>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <!-- 유지보수 날짜 -->
                    <div class="col-md-6">
                        <label for="maintenance_date" class="form-label">유지보수 날짜 <span class="text-danger">*</span></label>
                        <input type="date" name="maintenance_date" id="maintenance_date" class="form-control" required value="<?php echo isset($_POST['maintenance_date']) ? $_POST['maintenance_date'] : date('Y-m-d'); ?>">
                        <div class="invalid-feedback">유지보수 날짜를 입력해주세요.</div>
                    </div>
                    
                    <!-- 유지보수 시간 -->
                    <div class="col-md-6">
                        <label for="maintenance_time" class="form-label">유지보수 시간</label>
                        <input type="time" name="maintenance_time" id="maintenance_time" class="form-control" value="<?php echo isset($_POST['maintenance_time']) ? $_POST['maintenance_time'] : '09:00'; ?>">
                    </div>
                </div>
                
                <div class="row mb-3">
                    <!-- 담당 기술자 -->
                    <div class="col-md-6">
                        <label for="technician_name" class="form-label">담당 기술자</label>
                        <input type="text" name="technician_name" id="technician_name" class="form-control" value="<?php echo isset($_POST['technician_name']) ? htmlspecialchars($_POST['technician_name']) : ''; ?>" placeholder="기술자 이름">
                    </div>
                    
                    <!-- 기술자 연락처 -->
                    <div class="col-md-6">
                        <label for="technician_contact" class="form-label">기술자 연락처</label>
                        <input type="text" name="technician_contact" id="technician_contact" class="form-control" value="<?php echo isset($_POST['technician_contact']) ? htmlspecialchars($_POST['technician_contact']) : ''; ?>" placeholder="전화번호 또는 이메일">
                    </div>
                </div>
                
                <div class="mb-3">
                    <!-- 문제 설명 -->
                    <label for="issue_description" class="form-label">문제 설명 / 유지보수 내용</label>
                    <textarea name="issue_description" id="issue_description" class="form-control" rows="3" placeholder="장비의 문제 또는 유지보수 내용을 설명해주세요."><?php echo isset($_POST['issue_description']) ? htmlspecialchars($_POST['issue_description']) : ''; ?></textarea>
                </div>
                
                <div id="completed-fields" style="display: none;">
                    <div class="mb-3">
                        <!-- 해결책 / 작업 결과 -->
                        <label for="resolution" class="form-label">해결책 / 작업 결과 <span class="text-danger">*</span></label>
                        <textarea name="resolution" id="resolution" class="form-control" rows="3" placeholder="문제 해결 방법 또는 작업 결과를 설명해주세요."><?php echo isset($_POST['resolution']) ? htmlspecialchars($_POST['resolution']) : ''; ?></textarea>
                    </div>
                    
                    <div class="row mb-3">
                        <!-- 교체된 부품 -->
                        <div class="col-md-6">
                            <label for="parts_replaced" class="form-label">교체된 부품</label>
                            <textarea name="parts_replaced" id="parts_replaced" class="form-control" rows="2" placeholder="교체된 부품 목록 (있는 경우)"><?php echo isset($_POST['parts_replaced']) ? htmlspecialchars($_POST['parts_replaced']) : ''; ?></textarea>
                        </div>
                        
                        <!-- 비용 -->
                        <div class="col-md-6">
                            <label for="cost" class="form-label">비용</label>
                            <div class="input-group">
                                <span class="input-group-text">₩</span>
                                <input type="number" name="cost" id="cost" class="form-control" min="0" step="0.01" value="<?php echo isset($_POST['cost']) ? htmlspecialchars($_POST['cost']) : '0.00'; ?>">
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> 유지보수 일정 추가
                    </button>
                    <a href="/dashboard/store/equipment-maintenance.php?equipment_id=<?php echo $equipment_id; ?>" class="btn btn-secondary ms-2">
                        <i class="fas fa-times me-1"></i> 취소
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// 상태에 따라 완료 필드 표시 제어
document.addEventListener('DOMContentLoaded', function() {
    const statusSelect = document.getElementById('status');
    const completedFields = document.getElementById('completed-fields');
    
    function toggleCompletedFields() {
        if (statusSelect.value === 'completed') {
            completedFields.style.display = 'block';
            document.getElementById('resolution').setAttribute('required', 'required');
        } else {
            completedFields.style.display = 'none';
            document.getElementById('resolution').removeAttribute('required');
        }
    }
    
    // 초기 상태 설정
    toggleCompletedFields();
    
    // 상태 변경 이벤트 처리
    statusSelect.addEventListener('change', toggleCompletedFields);
    
    // Bootstrap Validation
    var forms = document.querySelectorAll('.needs-validation');
    Array.prototype.slice.call(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
});
</script>

<?php include_once '../../templates/dashboard-footer.php'; ?>
