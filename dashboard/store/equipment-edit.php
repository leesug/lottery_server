<?php
/**
 * 판매점 장비 수정 페이지
 * 
 * 이 페이지는 기존 장비 정보를 수정하는 양식을 제공합니다.
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
    error_log("check_permission 함수를 찾을 수 없습니다. (equipment-edit.php)");
}

// 장비 ID 가져오기
$equipment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

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
} else {
    log_error("장비 정보 쿼리 준비 실패: " . $conn->error);
    set_flash_message('error', '데이터베이스 오류로 인해 장비 정보를 가져올 수 없습니다.');
    redirect_to('/dashboard/store/equipment-list.php');
    exit;
}

// 판매점 목록 가져오기
$stores_query = "SELECT id, store_name, store_code FROM stores WHERE status = 'active' ORDER BY store_name";
$stores_result = $conn->query($stores_query);
$stores = [];

if ($stores_result) {
    $stores = $stores_result->fetch_all(MYSQLI_ASSOC);
} else {
    log_error("판매점 목록 쿼리 실패: " . $conn->error);
}

// 페이지 제목 설정
$page_title = '장비 정보 수정';

// POST 요청 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF 토큰 검증
    verify_csrf_token();
    
    // 폼 데이터 검증
    $store_id = isset($_POST['store_id']) ? intval($_POST['store_id']) : 0;
    $equipment_type = isset($_POST['equipment_type']) ? sanitize_input($_POST['equipment_type']) : '';
    $equipment_model = isset($_POST['equipment_model']) ? sanitize_input($_POST['equipment_model']) : '';
    $serial_number = isset($_POST['serial_number']) ? sanitize_input($_POST['serial_number']) : '';
    $asset_tag = isset($_POST['asset_tag']) ? sanitize_input($_POST['asset_tag']) : '';
    $installation_date = isset($_POST['installation_date']) ? sanitize_input($_POST['installation_date']) : '';
    $warranty_end_date = isset($_POST['warranty_end_date']) ? sanitize_input($_POST['warranty_end_date']) : null;
    $status = isset($_POST['status']) ? sanitize_input($_POST['status']) : '';
    $last_maintenance_date = isset($_POST['last_maintenance_date']) ? sanitize_input($_POST['last_maintenance_date']) : null;
    $next_maintenance_date = isset($_POST['next_maintenance_date']) ? sanitize_input($_POST['next_maintenance_date']) : null;
    $maintenance_notes = isset($_POST['maintenance_notes']) ? sanitize_input($_POST['maintenance_notes']) : '';
    
    // 필수 필드 검증
    $errors = [];
    
    if ($store_id <= 0) {
        $errors[] = "판매점을 선택해주세요.";
    }
    
    if (empty($equipment_type)) {
        $errors[] = "장비 유형을 선택해주세요.";
    }
    
    if (empty($equipment_model)) {
        $errors[] = "장비 모델을 입력해주세요.";
    }
    
    if (empty($serial_number)) {
        $errors[] = "시리얼 번호를 입력해주세요.";
    } else {
        // 시리얼 번호 중복 검사 (현재 장비 제외)
        $serial_check_query = "SELECT id FROM store_equipment WHERE serial_number = ? AND id != ?";
        $check_stmt = $conn->prepare($serial_check_query);
        
        if ($check_stmt) {
            $check_stmt->bind_param("si", $serial_number, $equipment_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                $errors[] = "이미 등록된 시리얼 번호입니다. 다른 번호를 입력해주세요.";
            }
            
            $check_stmt->close();
        }
    }
    
    if (empty($installation_date)) {
        $errors[] = "설치 날짜를 입력해주세요.";
    }
    
    if (empty($status)) {
        $errors[] = "상태를 선택해주세요.";
    }
    
    // 에러가 없으면 데이터 저장
    if (empty($errors)) {
        // 트랜잭션 시작
        $conn->begin_transaction();
        
        try {
            // 장비 정보 업데이트
            $update_query = "
                UPDATE store_equipment
                SET 
                    store_id = ?,
                    equipment_type = ?,
                    equipment_model = ?,
                    serial_number = ?,
                    asset_tag = ?,
                    installation_date = ?,
                    warranty_end_date = ?,
                    status = ?,
                    last_maintenance_date = ?,
                    next_maintenance_date = ?,
                    maintenance_notes = ?,
                    updated_at = NOW()
                WHERE id = ?
            ";
            
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param(
                "isssssssssssi",
                $store_id,
                $equipment_type,
                $equipment_model,
                $serial_number,
                $asset_tag,
                $installation_date,
                $warranty_end_date,
                $status,
                $last_maintenance_date,
                $next_maintenance_date,
                $maintenance_notes,
                $equipment_id
            );
            
            $update_result = $update_stmt->execute();
            $update_stmt->close();
            
            if (!$update_result) {
                throw new Exception("장비 정보 업데이트 실패: " . $conn->error);
            }
            
            // 상태 변경이 있을 경우 로그 기록
            if ($equipment['status'] !== $status) {
                $status_log_query = "
                    INSERT INTO activity_logs (
                        user_id, 
                        activity_type, 
                        description, 
                        ip_address, 
                        user_agent
                    ) VALUES (?, 'equipment_status_change', ?, ?, ?)
                ";
                
                $admin_id = $_SESSION['admin_id'] ?? 0;
                $log_description = "장비 ID: {$equipment_id}, 이전 상태: {$equipment['status']}, 새 상태: {$status}";
                $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
                $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
                
                $log_stmt = $conn->prepare($status_log_query);
                $log_stmt->bind_param("isss", $admin_id, $log_description, $ip_address, $user_agent);
                $log_stmt->execute();
                $log_stmt->close();
            }
            
            // 일반 수정 로그 기록
            $log_query = "
                INSERT INTO activity_logs (
                    user_id, 
                    activity_type, 
                    description, 
                    ip_address, 
                    user_agent
                ) VALUES (?, 'equipment_edit', ?, ?, ?)
            ";
            
            $admin_id = $_SESSION['admin_id'] ?? 0;
            $log_description = "장비 ID: {$equipment_id} 정보 수정";
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            
            $log_stmt = $conn->prepare($log_query);
            $log_stmt->bind_param("isss", $admin_id, $log_description, $ip_address, $user_agent);
            $log_stmt->execute();
            $log_stmt->close();
            
            // 트랜잭션 커밋
            $conn->commit();
            
            // 성공 메시지 설정
            set_flash_message('success', '장비 정보가 성공적으로 업데이트되었습니다.');
            
            // 장비 상세 페이지로 리다이렉트
            redirect_to("/dashboard/store/equipment-details.php?id={$equipment_id}");
            exit;
            
        } catch (Exception $e) {
            // 트랜잭션 롤백
            $conn->rollback();
            
            // 오류 로그 기록
            log_error("장비 정보 수정 실패: " . $e->getMessage());
            
            // 오류 메시지 설정
            $errors[] = "장비 정보 수정 중 오류가 발생했습니다: " . $e->getMessage();
        }
    }
}

// 헤더 인클루드
include_once '../../templates/dashboard-header.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><?php echo $page_title; ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="/dashboard">대시보드</a></li>
        <li class="breadcrumb-item"><a href="/dashboard/store/store-list.php">판매점 관리</a></li>
        <li class="breadcrumb-item"><a href="/dashboard/store/equipment-list.php">장비 관리</a></li>
        <li class="breadcrumb-item"><a href="/dashboard/store/equipment-details.php?id=<?php echo $equipment_id; ?>">장비 상세 정보</a></li>
        <li class="breadcrumb-item active">장비 정보 수정</li>
    </ol>
    
    <!-- 작업 버튼 -->
    <div class="mb-4">
        <a href="/dashboard/store/equipment-details.php?id=<?php echo $equipment_id; ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> 상세 정보로 돌아가기
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
    
    <!-- 장비 수정 양식 -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-edit me-1"></i>
            장비 정보 수정
        </div>
        <div class="card-body">
            <form method="post" action="" class="needs-validation" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                
                <!-- 양식 시작 -->
                <div class="row mb-3">
                    <!-- 장비 ID -->
                    <div class="col-md-6">
                        <label for="equipment_id" class="form-label">장비 ID</label>
                        <input type="text" id="equipment_id" class="form-control" value="<?php echo $equipment_id; ?>" disabled>
                    </div>
                    
                    <!-- 판매점 선택 -->
                    <div class="col-md-6">
                        <label for="store_id" class="form-label">판매점 <span class="text-danger">*</span></label>
                        <select name="store_id" id="store_id" class="form-select" required>
                            <option value="">판매점 선택</option>
                            <?php foreach ($stores as $store): ?>
                                <option value="<?php echo $store['id']; ?>" <?php echo ($equipment['store_id'] == $store['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($store['store_name'] . ' (' . $store['store_code'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">
                            판매점을 선택해주세요.
                        </div>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <!-- 장비 유형 -->
                    <div class="col-md-6">
                        <label for="equipment_type" class="form-label">장비 유형 <span class="text-danger">*</span></label>
                        <select name="equipment_type" id="equipment_type" class="form-select" required>
                            <option value="">장비 유형 선택</option>
                            <option value="terminal" <?php echo ($equipment['equipment_type'] == 'terminal') ? 'selected' : ''; ?>>단말기</option>
                            <option value="printer" <?php echo ($equipment['equipment_type'] == 'printer') ? 'selected' : ''; ?>>프린터</option>
                            <option value="scanner" <?php echo ($equipment['equipment_type'] == 'scanner') ? 'selected' : ''; ?>>스캐너</option>
                            <option value="display" <?php echo ($equipment['equipment_type'] == 'display') ? 'selected' : ''; ?>>디스플레이</option>
                            <option value="router" <?php echo ($equipment['equipment_type'] == 'router') ? 'selected' : ''; ?>>라우터</option>
                            <option value="other" <?php echo ($equipment['equipment_type'] == 'other') ? 'selected' : ''; ?>>기타</option>
                        </select>
                        <div class="invalid-feedback">
                            장비 유형을 선택해주세요.
                        </div>
                    </div>
                    
                    <!-- 장비 모델 -->
                    <div class="col-md-6">
                        <label for="equipment_model" class="form-label">장비 모델 <span class="text-danger">*</span></label>
                        <input type="text" name="equipment_model" id="equipment_model" class="form-control" required value="<?php echo htmlspecialchars($equipment['equipment_model']); ?>">
                        <div class="invalid-feedback">
                            장비 모델을 입력해주세요.
                        </div>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <!-- 시리얼 번호 -->
                    <div class="col-md-6">
                        <label for="serial_number" class="form-label">시리얼 번호 <span class="text-danger">*</span></label>
                        <input type="text" name="serial_number" id="serial_number" class="form-control" required value="<?php echo htmlspecialchars($equipment['serial_number']); ?>">
                        <div class="invalid-feedback">
                            시리얼 번호를 입력해주세요.
                        </div>
                        <small class="text-muted">시리얼 번호는 중복될 수 없습니다.</small>
                    </div>
                    
                    <!-- 자산 태그 -->
                    <div class="col-md-6">
                        <label for="asset_tag" class="form-label">자산 태그</label>
                        <input type="text" name="asset_tag" id="asset_tag" class="form-control" value="<?php echo htmlspecialchars($equipment['asset_tag'] ?? ''); ?>">
                        <small class="text-muted">내부 자산 관리를 위한 태그 번호 (선택사항)</small>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <!-- 설치일 -->
                    <div class="col-md-6">
                        <label for="installation_date" class="form-label">설치일 <span class="text-danger">*</span></label>
                        <input type="date" name="installation_date" id="installation_date" class="form-control" required value="<?php echo date('Y-m-d', strtotime($equipment['installation_date'])); ?>">
                        <div class="invalid-feedback">
                            설치일을 입력해주세요.
                        </div>
                    </div>
                    
                    <!-- 보증 만료일 -->
                    <div class="col-md-6">
                        <label for="warranty_end_date" class="form-label">보증 만료일</label>
                        <input type="date" name="warranty_end_date" id="warranty_end_date" class="form-control" value="<?php echo !empty($equipment['warranty_end_date']) ? date('Y-m-d', strtotime($equipment['warranty_end_date'])) : ''; ?>">
                        <small class="text-muted">보증 만료일 (선택사항)</small>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <!-- 상태 -->
                    <div class="col-md-6">
                        <label for="status" class="form-label">상태 <span class="text-danger">*</span></label>
                        <select name="status" id="status" class="form-select" required>
                            <option value="operational" <?php echo ($equipment['status'] == 'operational') ? 'selected' : ''; ?>>정상 작동</option>
                            <option value="maintenance" <?php echo ($equipment['status'] == 'maintenance') ? 'selected' : ''; ?>>유지보수 중</option>
                            <option value="faulty" <?php echo ($equipment['status'] == 'faulty') ? 'selected' : ''; ?>>고장</option>
                            <option value="replaced" <?php echo ($equipment['status'] == 'replaced') ? 'selected' : ''; ?>>교체됨</option>
                            <option value="retired" <?php echo ($equipment['status'] == 'retired') ? 'selected' : ''; ?>>폐기됨</option>
                        </select>
                        <div class="invalid-feedback">
                            상태를 선택해주세요.
                        </div>
                    </div>
                    
                    <!-- 마지막 점검일 -->
                    <div class="col-md-6">
                        <label for="last_maintenance_date" class="form-label">마지막 점검일</label>
                        <input type="date" name="last_maintenance_date" id="last_maintenance_date" class="form-control" value="<?php echo !empty($equipment['last_maintenance_date']) ? date('Y-m-d', strtotime($equipment['last_maintenance_date'])) : ''; ?>">
                    </div>
                </div>
                
                <div class="row mb-3">
                    <!-- 다음 점검 예정일 -->
                    <div class="col-md-6">
                        <label for="next_maintenance_date" class="form-label">다음 점검 예정일</label>
                        <input type="date" name="next_maintenance_date" id="next_maintenance_date" class="form-control" value="<?php echo !empty($equipment['next_maintenance_date']) ? date('Y-m-d', strtotime($equipment['next_maintenance_date'])) : ''; ?>">
                    </div>
                </div>
                
                <div class="row mb-3">
                    <!-- 유지보수 비고 -->
                    <div class="col-md-12">
                        <label for="maintenance_notes" class="form-label">유지보수 비고</label>
                        <textarea name="maintenance_notes" id="maintenance_notes" class="form-control" rows="3"><?php echo htmlspecialchars($equipment['maintenance_notes'] ?? ''); ?></textarea>
                        <small class="text-muted">장비 관리에 필요한 특이사항 (선택사항)</small>
                    </div>
                </div>
                
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <button type="button" class="btn btn-secondary me-md-2" onclick="history.back()">취소</button>
                    <button type="submit" class="btn btn-primary">변경사항 저장</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('장비 수정 페이지 로드됨');
    
    // 폼 유효성 검사
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
    
    // 설치일에 따른 기본 보증 만료일 설정 (1년 후)
    const installationDateInput = document.getElementById('installation_date');
    const warrantyEndDateInput = document.getElementById('warranty_end_date');
    
    installationDateInput.addEventListener('change', function() {
        if (this.value && !warrantyEndDateInput.value) {
            const installDate = new Date(this.value);
            const warrantyDate = new Date(installDate);
            warrantyDate.setFullYear(warrantyDate.getFullYear() + 1);
            
            // 날짜 형식 변환 (YYYY-MM-DD)
            const year = warrantyDate.getFullYear();
            const month = String(warrantyDate.getMonth() + 1).padStart(2, '0');
            const day = String(warrantyDate.getDate()).padStart(2, '0');
            
            warrantyEndDateInput.value = `${year}-${month}-${day}`;
        }
    });
    
    // 마지막 점검일에 따른 다음 점검일 설정 (3개월 후)
    const lastMaintenanceDateInput = document.getElementById('last_maintenance_date');
    const nextMaintenanceDateInput = document.getElementById('next_maintenance_date');
    
    lastMaintenanceDateInput.addEventListener('change', function() {
        if (this.value) {
            const lastDate = new Date(this.value);
            const nextDate = new Date(lastDate);
            nextDate.setMonth(nextDate.getMonth() + 3);
            
            // 날짜 형식 변환 (YYYY-MM-DD)
            const year = nextDate.getFullYear();
            const month = String(nextDate.getMonth() + 1).padStart(2, '0');
            const day = String(nextDate.getDate()).padStart(2, '0');
            
            nextMaintenanceDateInput.value = `${year}-${month}-${day}`;
        }
    });
});
</script>

<?php
// 푸터 인클루드
include_once '../../templates/dashboard-footer.php';
?>