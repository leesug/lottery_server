<?php
/**
 * 판매점 장비 유지보수 관리 페이지
 * 
 * 이 페이지는 모든 장비 또는 특정 장비의 유지보수 기록을 관리합니다.
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
    error_log("check_permission 함수를 찾을 수 없습니다. (equipment-maintenance.php)");
}

// 페이지 정보 설정
$pageTitle = '장비 유지보수 관리';
$currentSection = 'store';
$currentPage = basename($_SERVER['PHP_SELF']);

// 데이터베이스 연결
$db = get_db_connection();

// 장비 ID 가져오기
$equipment_id = isset($_GET['equipment_id']) ? intval($_GET['equipment_id']) : 0;
$store_id = isset($_GET['store_id']) ? intval($_GET['store_id']) : 0;
$display_mode = 'all'; // 기본적으로 모든 장비 표시

// 특정 장비 모드인지 확인
$specific_equipment = false;
$equipment = null;

if ($equipment_id > 0) {
    $display_mode = 'specific';
    $specific_equipment = true;
    
    // 장비 정보 가져오기
    $equipment_query = "
        SELECT e.*, s.store_name, s.store_code
        FROM store_equipment e
        JOIN stores s ON e.store_id = s.id
        WHERE e.id = ?
    ";
    
    $stmt = $db->prepare($equipment_query);
    if ($stmt) {
        $stmt->bindValue(1, $equipment_id, PDO::PARAM_INT);
        $stmt->execute();
        $equipment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$equipment) {
            set_flash_message('error', '해당 장비를 찾을 수 없습니다.');
            redirect_to('/dashboard/store/equipment-list.php');
            exit;
        }
    } else {
        log_error("장비 정보 쿼리 준비 실패: " . $db->errorInfo()[2]);
        set_flash_message('error', '데이터베이스 오류가 발생했습니다.');
        redirect_to('/dashboard/store/equipment-list.php');
        exit;
    }
}

// 유지보수 이력 가져오기
$maintenance_history = [];

if ($specific_equipment) {
    // 특정 장비의 유지보수 이력 가져오기
    $maintenance_query = "
        SELECT m.*
        FROM store_equipment_maintenance m
        WHERE m.equipment_id = ?
        ORDER BY m.maintenance_date DESC, m.id DESC
    ";
    
    $stmt = $db->prepare($maintenance_query);
    if ($stmt) {
        $stmt->bindValue(1, $equipment_id, PDO::PARAM_INT);
        $stmt->execute();
        $maintenance_history = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        log_error("유지보수 이력 쿼리 준비 실패: " . $db->errorInfo()[2]);
    }
} else {
    // 모든 장비 또는 특정 판매점의 장비 유지보수 이력 가져오기
    $where_clause = '';
    $params = [];
    $param_types = '';
    
    if ($store_id > 0) {
        $where_clause = 'WHERE e.store_id = ?';
        $params[] = $store_id;
        $param_types .= 'i';
    }
    
    $maintenance_query = "
        SELECT m.*, e.equipment_code, e.model_name, e.serial_number, e.equipment_type, s.store_name, s.store_code
        FROM store_equipment_maintenance m
        JOIN store_equipment e ON m.equipment_id = e.id
        JOIN stores s ON e.store_id = s.id
        $where_clause
        ORDER BY m.maintenance_date DESC, m.id DESC
        LIMIT 50
    ";
    
    $stmt = $db->prepare($maintenance_query);
    if ($stmt) {
        if (!empty($params)) {
            foreach ($params as $index => $param) {
                $stmt->bindValue($index + 1, $param);
            }
        }
        $stmt->execute();
        $maintenance_history = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        log_error("유지보수 이력 쿼리 준비 실패: " . $db->errorInfo()[2]);
    }
}

// 판매점 목록 가져오기 (필터링용)
$stores_query = "SELECT id, store_name, store_code FROM stores WHERE status = 'active' ORDER BY store_name";
$stores_result = $db->query($stores_query);
$stores = $stores_result->fetchAll(PDO::FETCH_ASSOC);

// 장비 상태 및 유형 정보 도움 함수들
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

function get_equipment_type_label($type) {
    $type_map = [
        'terminal' => '단말기',
        'printer' => '프린터',
        'scanner' => '스캐너',
        'display' => '디스플레이',
        'router' => '라우터',
        'other' => '기타'
    ];
    
    return $type_map[$type] ?? '알 수 없음';
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

function get_maintenance_type_label($type) {
    $type_map = [
        'regular' => '정기 점검',
        'repair' => '고장 수리',
        'upgrade' => '업그레이드',
        'inspection' => '특별 점검',
        'other' => '기타'
    ];
    
    return $type_map[$type] ?? '알 수 없음';
}

function get_maintenance_status_label_and_class($status) {
    $status_map = [
        'scheduled' => ['예정됨', 'info'],
        'in_progress' => ['진행 중', 'warning'],
        'completed' => ['완료됨', 'success'],
        'cancelled' => ['취소됨', 'danger']
    ];
    
    return $status_map[$status] ?? ['알 수 없음', 'dark'];
}

// 아이콘 및 상태 정보 설정
if ($specific_equipment) {
    $equipment_icon = get_equipment_icon($equipment['equipment_type']);
    [$status_label, $status_class] = get_equipment_status_label_and_class($equipment['status']);
}

// 헤더 인클루드
include_once TEMPLATES_PATH . '/dashboard_header.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><?php echo $pageTitle; ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="/dashboard">대시보드</a></li>
        <li class="breadcrumb-item"><a href="/dashboard/store/store-list.php">판매점 관리</a></li>
        <li class="breadcrumb-item"><a href="/dashboard/store/equipment-list.php">장비 관리</a></li>
        <?php if ($specific_equipment): ?>
            <li class="breadcrumb-item"><a href="/dashboard/store/equipment-details.php?id=<?php echo $equipment_id; ?>">장비 상세 정보</a></li>
        <?php endif; ?>
        <li class="breadcrumb-item active">유지보수 관리</li>
    </ol>
    
    <!-- 작업 버튼 -->
    <div class="mb-4">
        <a href="/dashboard/store/equipment-list.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> 장비 목록으로 돌아가기
        </a>
        
        <?php if ($specific_equipment): ?>
            <a href="/dashboard/store/equipment-details.php?id=<?php echo $equipment_id; ?>" class="btn btn-secondary">
                <i class="fas fa-info-circle me-1"></i> 장비 상세 정보
            </a>
            <a href="/dashboard/store/equipment-maintenance-add.php?equipment_id=<?php echo $equipment_id; ?>" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i> 새 유지보수 일정 추가
            </a>
        <?php endif; ?>
    </div>
    
    <?php if (!$specific_equipment): ?>
    <!-- 필터 섹션 -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-filter me-1"></i>
            필터
        </div>
        <div class="card-body">
            <form method="get" action="" class="row g-3 align-items-end">
                <!-- 판매점 필터 -->
                <div class="col-md-3">
                    <label for="store_id" class="form-label">판매점</label>
                    <select name="store_id" id="store_id" class="form-select">
                        <option value="">전체 판매점</option>
                        <?php foreach ($stores as $store): ?>
                            <option value="<?php echo $store['id']; ?>" <?php echo ($store_id == $store['id'] ? 'selected' : ''); ?>>
                                <?php echo htmlspecialchars($store['store_name'] . ' (' . $store['store_code'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- 필터 적용 및 초기화 버튼 -->
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter me-1"></i> 필터 적용
                    </button>
                </div>
                <div class="col-md-2">
                    <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn btn-secondary">
                        <i class="fas fa-redo me-1"></i> 초기화
                    </a>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if ($specific_equipment): ?>
    <!-- 장비 요약 정보 카드 -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="<?php echo $equipment_icon; ?> me-1"></i>
            장비 요약 정보
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
                    <h6 class="text-muted">판매점</h6>
                    <p>
                        <a href="/dashboard/store/store-details.php?id=<?php echo $equipment['store_id']; ?>">
                            <?php echo htmlspecialchars($equipment['store_name'] . ' (' . $equipment['store_code'] . ')'); ?>
                        </a>
                    </p>
                </div>
                <div class="col-md-3 mb-3">
                    <h6 class="text-muted">모델 / 시리얼 번호</h6>
                    <p>
                        <?php echo htmlspecialchars($equipment['model_name'] ?? 'N/A'); ?> / 
                        <span class="font-monospace"><?php echo htmlspecialchars($equipment['serial_number']); ?></span>
                    </p>
                </div>
                <div class="col-md-3 mb-3">
                    <h6 class="text-muted">상태</h6>
                    <p><span class="badge bg-<?php echo $status_class; ?>"><?php echo $status_label; ?></span></p>
                </div>
                <div class="col-md-3 mb-3">
                    <h6 class="text-muted">설치일</h6>
                    <p><?php echo date('Y-m-d', strtotime($equipment['installation_date'])); ?></p>
                </div>
                <div class="col-md-3 mb-3">
                    <h6 class="text-muted">마지막 점검일</h6>
                    <p>
                        <?php 
                        if (!empty($equipment['last_maintenance_date'])) {
                            echo date('Y-m-d', strtotime($equipment['last_maintenance_date']));
                        } else {
                            echo '<span class="text-muted">정보 없음</span>';
                        }
                        ?>
                    </p>
                </div>
                <div class="col-md-3 mb-3">
                    <h6 class="text-muted">다음 점검 예정일</h6>
                    <p>
                        <?php 
                        if (!empty($equipment['next_maintenance_date'])) {
                            echo date('Y-m-d', strtotime($equipment['next_maintenance_date']));
                        } else {
                            echo '<span class="text-muted">정보 없음</span>';
                        }
                        ?>
                    </p>
                </div>
                <div class="col-md-3 mb-3">
                    <h6 class="text-muted">총 유지보수 횟수</h6>
                    <p><?php echo count($maintenance_history); ?>회</p>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- 유지보수 이력 테이블 -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-history me-1"></i>
            <?php echo $specific_equipment ? '유지보수 이력' : '전체 장비 유지보수 이력'; ?>
        </div>
        <div class="card-body">
            <?php if (empty($maintenance_history)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-1"></i> 기록된 유지보수 이력이 없습니다.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>유지보수 코드</th>
                                <?php if (!$specific_equipment): ?>
                                <th>장비 정보</th>
                                <th>판매점</th>
                                <?php endif; ?>
                                <th>유지보수 유형</th>
                                <th>일자 / 시간</th>
                                <th>기술자 정보</th>
                                <th>상태</th>
                                <th>내용</th>
                                <th>관리</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($maintenance_history as $maintenance): ?>
                                <?php 
                                // 유지보수 상태에 따른 클래스 및 레이블
                                [$m_status_label, $m_status_class] = get_maintenance_status_label_and_class($maintenance['status']);
                                ?>
                                <tr>
                                    <td><?php echo $maintenance['id']; ?></td>
                                    <td class="font-monospace"><?php echo htmlspecialchars($maintenance['maintenance_code'] ?? 'N/A'); ?></td>
                                    <?php if (!$specific_equipment): ?>
                                    <td>
                                        <a href="/dashboard/store/equipment-details.php?id=<?php echo $maintenance['equipment_id']; ?>">
                                            <i class="<?php echo get_equipment_icon($maintenance['equipment_type']); ?> me-1"></i>
                                            <?php echo get_equipment_type_label($maintenance['equipment_type']); ?> 
                                            <span class="font-monospace">(<?php echo htmlspecialchars($maintenance['equipment_code'] ?? 'N/A'); ?>)</span>
                                        </a>
                                        <br>
                                        <small class="text-muted">SN: <?php echo htmlspecialchars($maintenance['serial_number']); ?></small>
                                    </td>
                                    <td>
                                        <a href="/dashboard/store/store-details.php?id=<?php echo $maintenance['store_id']; ?>">
                                            <?php echo htmlspecialchars($maintenance['store_name']); ?>
                                        </a>
                                        <br>
                                        <small class="text-muted"><?php echo htmlspecialchars($maintenance['store_code']); ?></small>
                                    </td>
                                    <?php endif; ?>
                                    <td><?php echo get_maintenance_type_label($maintenance['maintenance_type']); ?></td>
                                    <td>
                                        <?php 
                                        echo date('Y-m-d', strtotime($maintenance['maintenance_date']));
                                        if (!empty($maintenance['maintenance_time'])) {
                                            echo ' ' . date('H:i', strtotime($maintenance['maintenance_time']));
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                        if (!empty($maintenance['technician_name'])) {
                                            echo htmlspecialchars($maintenance['technician_name']);
                                            
                                            if (!empty($maintenance['technician_contact'])) {
                                                echo '<br><small>' . htmlspecialchars($maintenance['technician_contact']) . '</small>';
                                            }
                                        } else {
                                            echo '<span class="text-muted">미정</span>';
                                        }
                                        ?>
                                    </td>
                                    <td><span class="badge bg-<?php echo $m_status_class; ?>"><?php echo $m_status_label; ?></span></td>
                                    <td>
                                        <?php
                                        if (!empty($maintenance['issue_description'])) {
                                            $description = htmlspecialchars($maintenance['issue_description']);
                                            echo (strlen($description) > 50) ? substr($description, 0, 50) . '...' : $description;
                                        } else {
                                            echo '<span class="text-muted">내용 없음</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php if ($maintenance['status'] == 'scheduled'): ?>
                                            <a href="/dashboard/store/equipment-maintenance-edit.php?id=<?php echo $maintenance['id']; ?>" class="btn btn-sm btn-primary" title="수정">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="/dashboard/store/equipment-maintenance-complete.php?id=<?php echo $maintenance['id']; ?>" class="btn btn-sm btn-success" title="완료 처리">
                                                <i class="fas fa-check"></i>
                                            </a>
                                            <a href="/dashboard/store/equipment-maintenance-cancel.php?id=<?php echo $maintenance['id']; ?>" class="btn btn-sm btn-danger" title="취소">
                                                <i class="fas fa-times"></i>
                                            </a>
                                        <?php elseif ($maintenance['status'] == 'in_progress'): ?>
                                            <a href="/dashboard/store/equipment-maintenance-edit.php?id=<?php echo $maintenance['id']; ?>" class="btn btn-sm btn-primary" title="수정">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="/dashboard/store/equipment-maintenance-complete.php?id=<?php echo $maintenance['id']; ?>" class="btn btn-sm btn-success" title="완료 처리">
                                                <i class="fas fa-check"></i>
                                            </a>
                                        <?php else: ?>
                                            <a href="#" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#detailModal<?php echo $maintenance['id']; ?>" title="상세 정보">
                                                <i class="fas fa-info-circle"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                
                                <!-- 상세 정보 모달 -->
                                <div class="modal fade" id="detailModal<?php echo $maintenance['id']; ?>" tabindex="-1" aria-labelledby="detailModalLabel<?php echo $maintenance['id']; ?>" aria-hidden="true">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="detailModalLabel<?php echo $maintenance['id']; ?>">유지보수 상세 정보</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="row">
                                                    <div class="col-md-6 mb-3">
                                                        <h6 class="text-muted">유지보수 ID</h6>
                                                        <p><?php echo $maintenance['id']; ?></p>
                                                    </div>
                                                    <div class="col-md-6 mb-3">
                                                        <h6 class="text-muted">유지보수 코드</h6>
                                                        <p class="font-monospace"><?php echo htmlspecialchars($maintenance['maintenance_code'] ?? 'N/A'); ?></p>
                                                    </div>
                                                    <div class="col-md-6 mb-3">
                                                        <h6 class="text-muted">유지보수 유형</h6>
                                                        <p><?php echo get_maintenance_type_label($maintenance['maintenance_type']); ?></p>
                                                    </div>
                                                    <div class="col-md-6 mb-3">
                                                        <h6 class="text-muted">상태</h6>
                                                        <p><span class="badge bg-<?php echo $m_status_class; ?>"><?php echo $m_status_label; ?></span></p>
                                                    </div>
                                                    <div class="col-md-6 mb-3">
                                                        <h6 class="text-muted">일시</h6>
                                                        <p>
                                                            <?php 
                                                            echo date('Y년 m월 d일', strtotime($maintenance['maintenance_date']));
                                                            if (!empty($maintenance['maintenance_time'])) {
                                                                echo ' ' . date('H시 i분', strtotime($maintenance['maintenance_time']));
                                                            }
                                                            ?>
                                                        </p>
                                                    </div>
                                                    <div class="col-md-6 mb-3">
                                                        <h6 class="text-muted">기술자 정보</h6>
                                                        <p>
                                                            <?php 
                                                            if (!empty($maintenance['technician_name'])) {
                                                                echo htmlspecialchars($maintenance['technician_name']);
                                                                
                                                                if (!empty($maintenance['technician_contact'])) {
                                                                    echo '<br>' . htmlspecialchars($maintenance['technician_contact']);
                                                                }
                                                            } else {
                                                                echo '<span class="text-muted">미정</span>';
                                                            }
                                                            ?>
                                                        </p>
                                                    </div>
                                                    <div class="col-12 mb-3">
                                                        <h6 class="text-muted">문제 설명</h6>
                                                        <p>
                                                            <?php 
                                                            if (!empty($maintenance['issue_description'])) {
                                                                echo nl2br(htmlspecialchars($maintenance['issue_description']));
                                                            } else {
                                                                echo '<span class="text-muted">내용 없음</span>';
                                                            }
                                                            ?>
                                                        </p>
                                                    </div>
                                                    <?php if ($maintenance['status'] == 'completed'): ?>
                                                        <div class="col-12 mb-3">
                                                            <h6 class="text-muted">해결책 / 작업 결과</h6>
                                                            <p>
                                                                <?php 
                                                                if (!empty($maintenance['resolution'])) {
                                                                    echo nl2br(htmlspecialchars($maintenance['resolution']));
                                                                } else {
                                                                    echo '<span class="text-muted">내용 없음</span>';
                                                                }
                                                                ?>
                                                            </p>
                                                        </div>
                                                        <?php if (!empty($maintenance['parts_replaced'])): ?>
                                                            <div class="col-12 mb-3">
                                                                <h6 class="text-muted">교체된 부품</h6>
                                                                <p><?php echo nl2br(htmlspecialchars($maintenance['parts_replaced'])); ?></p>
                                                            </div>
                                                        <?php endif; ?>
                                                        <?php if (!empty($maintenance['cost']) && $maintenance['cost'] > 0): ?>
                                                            <div class="col-6 mb-3">
                                                                <h6 class="text-muted">비용</h6>
                                                                <p>₩<?php echo number_format($maintenance['cost'], 2); ?></p>
                                                            </div>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">닫기</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include_once TEMPLATES_PATH . '/dashboard_footer.php'; ?>
