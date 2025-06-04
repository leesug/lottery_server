<?php
/**
 * 판매점 장비 목록 페이지
 * 
 * 이 페이지는 판매점별 또는 전체 판매점에 대한 장비 목록을 보여줍니다.
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
    error_log("check_permission 함수를 찾을 수 없습니다. (equipment-list.php)");
}

// 페이지 제목 설정
$pageTitle = '판매점 장비 관리';
$currentSection = 'store';
$currentPage = basename($_SERVER['PHP_SELF']);

// 데이터베이스 연결
$db = get_db_connection();

// 헤더 인클루드
include_once TEMPLATES_PATH . '/dashboard_header.php';

// 페이지 매개변수 처리
$store_id = isset($_GET['store_id']) ? intval($_GET['store_id']) : 0;
$equipment_type = isset($_GET['type']) ? sanitize_input($_GET['type']) : '';
$status = isset($_GET['status']) ? sanitize_input($_GET['status']) : '';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
$offset = ($page - 1) * $limit;

// 필터링을 위한 기본 쿼리 조건
$where_conditions = [];
$params = [];

// 특정 판매점 필터링
if ($store_id > 0) {
    $where_conditions[] = "e.store_id = ?";
    $params[] = $store_id;
}

// 장비 타입 필터링
if (!empty($equipment_type)) {
    $where_conditions[] = "e.equipment_type = ?";
    $params[] = $equipment_type;
}

// 상태 필터링
if (!empty($status)) {
    $where_conditions[] = "e.status = ?";
    $params[] = $status;
}

// 최종 WHERE 절 구성
$where_clause = !empty($where_conditions) ? "WHERE " . implode(' AND ', $where_conditions) : "";

// 장비 목록 쿼리
$sql = "
    SELECT e.*, s.store_name, s.store_code
    FROM store_equipment e
    JOIN stores s ON e.store_id = s.id
    $where_clause
    ORDER BY e.id DESC
    LIMIT ?, ?
";

// 파라미터에 페이지네이션 매개변수 추가
$params[] = $offset;
$params[] = $limit;

// 쿼리 실행
$stmt = $db->prepare($sql);
if ($stmt) {
    // 파라미터 바인딩
    if (!empty($params)) {
        foreach ($params as $index => $param) {
            $stmt->bindValue($index + 1, $param);
        }
    }
    
    $stmt->execute();
    $equipment_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $equipment_list = [];
    log_error("장비 목록 쿼리 실패: " . $db->errorInfo()[2]);
}

// 총 장비 수 쿼리
$count_sql = "
    SELECT COUNT(*) as total
    FROM store_equipment e
    JOIN stores s ON e.store_id = s.id
    $where_clause
";

$total_equipment = 0;
$count_stmt = $db->prepare($count_sql);

if ($count_stmt) {
    // 파라미터 바인딩 (페이지네이션 매개변수는 제외)
    if (!empty($where_conditions)) {
        $count_params = array_slice($params, 0, count($params) - 2);
        foreach ($count_params as $index => $param) {
            $count_stmt->bindValue($index + 1, $param);
        }
    }
    
    $count_stmt->execute();
    $count_result = $count_stmt->fetch(PDO::FETCH_ASSOC);
    $total_equipment = isset($count_result['total']) ? $count_result['total'] : 0;
}

// 페이지네이션 계산
$total_pages = ceil($total_equipment / $limit);

// 판매점 목록 가져오기 (필터링용)
$store_query = "SELECT id, store_name, store_code FROM stores ORDER BY store_name";
$store_result = $db->query($store_query);
$stores = $store_result->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><?php echo $pageTitle; ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="/dashboard">대시보드</a></li>
        <li class="breadcrumb-item"><a href="/dashboard/store/store-list.php">판매점 관리</a></li>
        <li class="breadcrumb-item active">장비 관리</li>
    </ol>
    
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
                
                <!-- 장비 유형 필터 -->
                <div class="col-md-2">
                    <label for="type" class="form-label">장비 유형</label>
                    <select name="type" id="type" class="form-select">
                        <option value="">전체 유형</option>
                        <option value="terminal" <?php echo ($equipment_type == 'terminal' ? 'selected' : ''); ?>>단말기</option>
                        <option value="printer" <?php echo ($equipment_type == 'printer' ? 'selected' : ''); ?>>프린터</option>
                        <option value="scanner" <?php echo ($equipment_type == 'scanner' ? 'selected' : ''); ?>>스캐너</option>
                        <option value="display" <?php echo ($equipment_type == 'display' ? 'selected' : ''); ?>>디스플레이</option>
                        <option value="router" <?php echo ($equipment_type == 'router' ? 'selected' : ''); ?>>라우터</option>
                        <option value="other" <?php echo ($equipment_type == 'other' ? 'selected' : ''); ?>>기타</option>
                    </select>
                </div>
                
                <!-- 상태 필터 -->
                <div class="col-md-2">
                    <label for="status" class="form-label">상태</label>
                    <select name="status" id="status" class="form-select">
                        <option value="">전체 상태</option>
                        <option value="operational" <?php echo ($status == 'operational' ? 'selected' : ''); ?>>정상 작동</option>
                        <option value="maintenance" <?php echo ($status == 'maintenance' ? 'selected' : ''); ?>>유지보수 중</option>
                        <option value="faulty" <?php echo ($status == 'faulty' ? 'selected' : ''); ?>>고장</option>
                        <option value="replaced" <?php echo ($status == 'replaced' ? 'selected' : ''); ?>>교체됨</option>
                        <option value="retired" <?php echo ($status == 'retired' ? 'selected' : ''); ?>>폐기됨</option>
                    </select>
                </div>
                
                <!-- 페이지당 항목 수 -->
                <div class="col-md-2">
                    <label for="limit" class="form-label">표시 개수</label>
                    <select name="limit" id="limit" class="form-select">
                        <option value="10" <?php echo ($limit == 10 ? 'selected' : ''); ?>>10개</option>
                        <option value="20" <?php echo ($limit == 20 ? 'selected' : ''); ?>>20개</option>
                        <option value="50" <?php echo ($limit == 50 ? 'selected' : ''); ?>>50개</option>
                        <option value="100" <?php echo ($limit == 100 ? 'selected' : ''); ?>>100개</option>
                    </select>
                </div>
                
                <!-- 필터 적용 및 초기화 버튼 -->
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary me-2">필터 적용</button>
                    <a href="equipment-list.php" class="btn btn-secondary">초기화</a>
                    <?php if ($store_id > 0): ?>
                        <a href="equipment-add.php?store_id=<?php echo $store_id; ?>" class="btn btn-success ms-2">
                            <i class="fas fa-plus"></i> 장비 추가
                        </a>
                    <?php else: ?>
                        <a href="equipment-add.php" class="btn btn-success ms-2">
                            <i class="fas fa-plus"></i> 장비 추가
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
    
    <!-- 장비 목록 테이블 -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-hdd me-1"></i>
            장비 목록
        </div>
        <div class="card-body">
            <?php if (empty($equipment_list)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-1"></i> 등록된 장비가 없습니다.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>장비 코드</th>
                                <th>판매점</th>
                                <th>장비 유형</th>
                                <th>모델명</th>
                                <th>시리얼 번호</th>
                                <th>설치일</th>
                                <th>상태</th>
                                <th>마지막 점검일</th>
                                <th>다음 점검일</th>
                                <th>관리</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($equipment_list as $equipment): ?>
                                <tr>
                                    <td><?php echo $equipment['id']; ?></td>
                                    <td><?php echo htmlspecialchars($equipment['equipment_code'] ?? 'N/A'); ?></td>
                                    <td>
                                        <a href="store-details.php?id=<?php echo $equipment['store_id']; ?>">
                                            <?php echo htmlspecialchars($equipment['store_name'] . ' (' . $equipment['store_code'] . ')'); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <?php
                                        // 장비 유형에 따른 아이콘 및 레이블 표시
                                        $equipment_icon = '';
                                        $equipment_label = '';
                                        
                                        switch ($equipment['equipment_type']) {
                                            case 'terminal':
                                                $equipment_icon = 'fas fa-desktop';
                                                $equipment_label = '단말기';
                                                break;
                                            case 'printer':
                                                $equipment_icon = 'fas fa-print';
                                                $equipment_label = '프린터';
                                                break;
                                            case 'scanner':
                                                $equipment_icon = 'fas fa-barcode';
                                                $equipment_label = '스캐너';
                                                break;
                                            case 'display':
                                                $equipment_icon = 'fas fa-tv';
                                                $equipment_label = '디스플레이';
                                                break;
                                            case 'router':
                                                $equipment_icon = 'fas fa-wifi';
                                                $equipment_label = '라우터';
                                                break;
                                            default:
                                                $equipment_icon = 'fas fa-hdd';
                                                $equipment_label = '기타';
                                                break;
                                        }
                                        ?>
                                        <i class="<?php echo $equipment_icon; ?> me-1"></i>
                                        <?php echo $equipment_label; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($equipment['equipment_model']); ?></td>
                                    <td><?php echo htmlspecialchars($equipment['serial_number']); ?></td>
                                    <td><?php echo date('Y-m-d', strtotime($equipment['installation_date'])); ?></td>
                                    <td>
                                        <?php
                                        // 상태에 따른 뱃지 색상 결정
                                        $status_class = '';
                                        $status_label = '';
                                        
                                        switch ($equipment['status']) {
                                            case 'operational':
                                                $status_class = 'success';
                                                $status_label = '정상 작동';
                                                break;
                                            case 'maintenance':
                                                $status_class = 'warning';
                                                $status_label = '유지보수 중';
                                                break;
                                            case 'faulty':
                                                $status_class = 'danger';
                                                $status_label = '고장';
                                                break;
                                            case 'replaced':
                                                $status_class = 'info';
                                                $status_label = '교체됨';
                                                break;
                                            case 'retired':
                                                $status_class = 'secondary';
                                                $status_label = '폐기됨';
                                                break;
                                        }
                                        ?>
                                        <span class="badge bg-<?php echo $status_class; ?>">
                                            <?php echo $status_label; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo !empty($equipment['last_maintenance_date']) ? date('Y-m-d', strtotime($equipment['last_maintenance_date'])) : '-'; ?>
                                    </td>
                                    <td>
                                        <?php 
                                        if (!empty($equipment['next_maintenance_date'])) {
                                            $next_date = strtotime($equipment['next_maintenance_date']);
                                            $now = time();
                                            $days_diff = floor(($next_date - $now) / (60 * 60 * 24));
                                            
                                            echo date('Y-m-d', $next_date);
                                            
                                            // 점검일이 가까우면 경고 표시
                                            if ($days_diff <= 7 && $days_diff >= 0) {
                                                echo ' <span class="badge bg-warning">D-' . $days_diff . '</span>';
                                            } elseif ($days_diff < 0) {
                                                echo ' <span class="badge bg-danger">점검 필요</span>';
                                            }
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group" role="group">
                                            <!-- 장비 상세보기 -->
                                            <a href="equipment-details.php?id=<?php echo $equipment['id']; ?>" class="btn btn-primary btn-sm" title="상세 정보">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            
                                            <!-- 장비 수정 -->
                                            <a href="equipment-edit.php?id=<?php echo $equipment['id']; ?>" class="btn btn-success btn-sm" title="수정">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            
                                            <!-- 유지보수 기록 -->
                                            <a href="equipment-maintenance.php?equipment_id=<?php echo $equipment['id']; ?>" class="btn btn-info btn-sm" title="유지보수 기록">
                                                <i class="fas fa-tools"></i>
                                            </a>
                                            
                                            <!-- 상태 변경 -->
                                            <button type="button" class="btn btn-warning btn-sm" title="상태 변경" 
                                                    data-bs-toggle="modal" data-bs-target="#statusChangeModal" 
                                                    data-equipment-id="<?php echo $equipment['id']; ?>"
                                                    data-current-status="<?php echo $equipment['status']; ?>">
                                                <i class="fas fa-exchange-alt"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- 페이지네이션 -->
                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center mt-4">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=1<?php echo !empty($store_id) ? '&store_id=' . $store_id : ''; ?><?php echo !empty($equipment_type) ? '&type=' . $equipment_type : ''; ?><?php echo !empty($status) ? '&status=' . $status : ''; ?>&limit=<?php echo $limit; ?>" aria-label="First">
                                        <span aria-hidden="true">&laquo;&laquo;</span>
                                    </a>
                                </li>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo !empty($store_id) ? '&store_id=' . $store_id : ''; ?><?php echo !empty($equipment_type) ? '&type=' . $equipment_type : ''; ?><?php echo !empty($status) ? '&status=' . $status : ''; ?>&limit=<?php echo $limit; ?>" aria-label="Previous">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php
                            // 페이지 링크 표시 (현재 페이지 주변 5개 페이지)
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);
                            
                            for ($i = $start_page; $i <= $end_page; $i++): 
                            ?>
                                <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($store_id) ? '&store_id=' . $store_id : ''; ?><?php echo !empty($equipment_type) ? '&type=' . $equipment_type : ''; ?><?php echo !empty($status) ? '&status=' . $status : ''; ?>&limit=<?php echo $limit; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo !empty($store_id) ? '&store_id=' . $store_id : ''; ?><?php echo !empty($equipment_type) ? '&type=' . $equipment_type : ''; ?><?php echo !empty($status) ? '&status=' . $status : ''; ?>&limit=<?php echo $limit; ?>" aria-label="Next">
                                        <span aria-hidden="true">&raquo;</span>
                                    </a>
                                </li>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $total_pages; ?><?php echo !empty($store_id) ? '&store_id=' . $store_id : ''; ?><?php echo !empty($equipment_type) ? '&type=' . $equipment_type : ''; ?><?php echo !empty($status) ? '&status=' . $status : ''; ?>&limit=<?php echo $limit; ?>" aria-label="Last">
                                        <span aria-hidden="true">&raquo;&raquo;</span>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
                
                <!-- 총 아이템 수 표시 -->
                <div class="text-center mt-2">
                    <p>전체 <?php echo $total_equipment; ?>개 중 <?php echo count($equipment_list); ?>개 표시</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- 상태 변경 모달 -->
<div class="modal fade" id="statusChangeModal" tabindex="-1" aria-labelledby="statusChangeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="statusChangeModalLabel">장비 상태 변경</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="equipment-status-change.php" method="post">
                <div class="modal-body">
                    <input type="hidden" name="equipment_id" id="modal_equipment_id" value="">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    
                    <div class="mb-3">
                        <label for="new_status" class="form-label">새 상태</label>
                        <select name="new_status" id="new_status" class="form-select" required>
                            <option value="operational">정상 작동</option>
                            <option value="maintenance">유지보수 중</option>
                            <option value="faulty">고장</option>
                            <option value="replaced">교체됨</option>
                            <option value="retired">폐기됨</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label">비고</label>
                        <textarea name="notes" id="notes" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                    <button type="submit" class="btn btn-primary">상태 변경</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 모달 관련 스크립트 -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 상태 변경 모달 데이터 설정
    const statusChangeModal = document.getElementById('statusChangeModal');
    if (statusChangeModal) {
        statusChangeModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const equipmentId = button.getAttribute('data-equipment-id');
            const currentStatus = button.getAttribute('data-current-status');
            
            // 모달에 데이터 설정
            document.getElementById('modal_equipment_id').value = equipmentId;
            
            // 현재 상태를 기본 선택
            const statusSelect = document.getElementById('new_status');
            if (statusSelect && currentStatus) {
                for (let i = 0; i < statusSelect.options.length; i++) {
                    if (statusSelect.options[i].value === currentStatus) {
                        statusSelect.selectedIndex = i;
                        break;
                    }
                }
            }
        });
    }
    
    // 콘솔에 디버깅 정보 기록
    console.log('장비 목록 페이지 로드됨');
});
</script>

<?php
// 푸터 인클루드
include_once '../../templates/dashboard-footer.php';
?>