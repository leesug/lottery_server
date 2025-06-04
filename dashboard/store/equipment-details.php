<?php
/**
 * 판매점 장비 상세 정보 페이지
 * 
 * 이 페이지는 특정 장비의 상세 정보와 유지보수 이력을 보여줍니다.
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
    error_log("check_permission 함수를 찾을 수 없습니다. (equipment-details.php)");
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
    SELECT e.*, s.store_name, s.store_code, s.phone AS store_phone, s.address AS store_address
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

// 유지보수 이력 가져오기
$maintenance_query = "
    SELECT *
    FROM store_equipment_maintenance
    WHERE equipment_id = ?
    ORDER BY maintenance_date DESC, id DESC
";

$maintenance_history = [];
$maintenance_stmt = $conn->prepare($maintenance_query);

if ($maintenance_stmt) {
    $maintenance_stmt->bind_param("i", $equipment_id);
    $maintenance_stmt->execute();
    $maintenance_result = $maintenance_stmt->get_result();
    $maintenance_history = $maintenance_result->fetch_all(MYSQLI_ASSOC);
    $maintenance_stmt->close();
} else {
    log_error("유지보수 이력 쿼리 준비 실패: " . $conn->error);
}

// 페이지 제목 설정
$page_title = '장비 상세 정보';

// 헤더 인클루드
include_once '../../templates/dashboard-header.php';

// 장비 상태 레이블 및 클래스 얻기
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

// 장비 유형 레이블 및 아이콘 얻기
function get_equipment_type_label_and_icon($type) {
    $type_map = [
        'terminal' => ['단말기', 'fas fa-desktop'],
        'printer' => ['프린터', 'fas fa-print'],
        'scanner' => ['스캐너', 'fas fa-barcode'],
        'display' => ['디스플레이', 'fas fa-tv'],
        'router' => ['라우터', 'fas fa-wifi'],
        'other' => ['기타', 'fas fa-hdd']
    ];
    
    return $type_map[$type] ?? ['알 수 없음', 'fas fa-question-circle'];
}

// 유지보수 유형 레이블 얻기
function get_maintenance_type_label($type) {
    $type_map = [
        'routine' => '정기 점검',
        'repair' => '수리',
        'upgrade' => '업그레이드',
        'replacement' => '교체',
        'inspection' => '특별 검사'
    ];
    
    return $type_map[$type] ?? '기타';
}

// 유지보수 상태 레이블 및 클래스 얻기
function get_maintenance_status_label_and_class($status) {
    $status_map = [
        'scheduled' => ['예정됨', 'info'],
        'in_progress' => ['진행 중', 'warning'],
        'completed' => ['완료됨', 'success'],
        'cancelled' => ['취소됨', 'danger']
    ];
    
    return $status_map[$status] ?? ['알 수 없음', 'dark'];
}

// 장비 상태 정보
[$status_label, $status_class] = get_equipment_status_label_and_class($equipment['status']);

// 장비 유형 정보
[$type_label, $type_icon] = get_equipment_type_label_and_icon($equipment['equipment_type']);

// 경과 시간 계산 함수
function time_elapsed($datetime) {
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    
    if ($diff->y > 0) {
        return $diff->y . '년 전';
    } elseif ($diff->m > 0) {
        return $diff->m . '개월 전';
    } elseif ($diff->d > 0) {
        return $diff->d . '일 전';
    } elseif ($diff->h > 0) {
        return $diff->h . '시간 전';
    } elseif ($diff->i > 0) {
        return $diff->i . '분 전';
    } else {
        return '방금 전';
    }
}
?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><?php echo $page_title; ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="/dashboard">대시보드</a></li>
        <li class="breadcrumb-item"><a href="/dashboard/store/store-list.php">판매점 관리</a></li>
        <li class="breadcrumb-item"><a href="/dashboard/store/equipment-list.php">장비 관리</a></li>
        <li class="breadcrumb-item active">장비 상세 정보</li>
    </ol>
    
    <!-- 작업 버튼 -->
    <div class="mb-4">
        <a href="/dashboard/store/equipment-list.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> 목록으로
        </a>
        <a href="/dashboard/store/equipment-edit.php?id=<?php echo $equipment_id; ?>" class="btn btn-primary">
            <i class="fas fa-edit me-1"></i> 수정
        </a>
        <a href="/dashboard/store/equipment-maintenance.php?equipment_id=<?php echo $equipment_id; ?>" class="btn btn-success">
            <i class="fas fa-tools me-1"></i> 유지보수 관리
        </a>
        <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#statusChangeModal">
            <i class="fas fa-exchange-alt me-1"></i> 상태 변경
        </button>
        <a href="/dashboard/store/store-details.php?id=<?php echo $equipment['store_id']; ?>" class="btn btn-info">
            <i class="fas fa-store me-1"></i> 판매점 보기
        </a>
    </div>
    
    <div class="row">
        <!-- 장비 기본 정보 카드 -->
        <div class="col-xl-6">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="<?php echo $type_icon; ?> me-1"></i>
                    장비 기본 정보
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- 장비 ID 및 시리얼 번호 -->
                        <div class="col-md-6 mb-3">
                            <h6 class="text-muted">장비 ID</h6>
                            <p class="font-monospace">#<?php echo $equipment['id']; ?></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <h6 class="text-muted">장비 코드</h6>
                            <p class="font-monospace"><?php echo htmlspecialchars($equipment['equipment_code'] ?? 'N/A'); ?></p>
                        </div>
                        
                        <!-- 시리얼 번호 -->
                        <div class="col-md-6 mb-3">
                            <h6 class="text-muted">시리얼 번호</h6>
                            <p class="font-monospace"><?php echo htmlspecialchars($equipment['serial_number']); ?></p>
                        </div>
                        
                        <!-- 장비 유형 및 모델 -->
                        <div class="col-md-6 mb-3">
                            <h6 class="text-muted">장비 유형</h6>
                            <p><i class="<?php echo $type_icon; ?> me-1"></i> <?php echo $type_label; ?></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <h6 class="text-muted">모델명</h6>
                            <p><?php echo htmlspecialchars($equipment['equipment_model']); ?></p>
                        </div>
                        
                        <!-- 상태 및 자산 태그 -->
                        <div class="col-md-6 mb-3">
                            <h6 class="text-muted">현재 상태</h6>
                            <p><span class="badge bg-<?php echo $status_class; ?>"><?php echo $status_label; ?></span></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <h6 class="text-muted">자산 태그</h6>
                            <p><?php echo !empty($equipment['asset_tag']) ? htmlspecialchars($equipment['asset_tag']) : '<span class="text-muted">미할당</span>'; ?></p>
                        </div>
                        
                        <!-- 설치일 및 보증 만료일 -->
                        <div class="col-md-6 mb-3">
                            <h6 class="text-muted">설치일</h6>
                            <p><?php echo date('Y년 m월 d일', strtotime($equipment['installation_date'])); ?></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <h6 class="text-muted">보증 만료일</h6>
                            <p>
                                <?php if (!empty($equipment['warranty_end_date'])): ?>
                                    <?php
                                    $warranty_date = strtotime($equipment['warranty_end_date']);
                                    $now = time();
                                    $days_left = floor(($warranty_date - $now) / (60 * 60 * 24));
                                    echo date('Y년 m월 d일', $warranty_date);
                                    
                                    if ($days_left > 0) {
                                        echo ' <span class="badge bg-info">D-' . $days_left . '</span>';
                                    } else {
                                        echo ' <span class="badge bg-danger">만료됨</span>';
                                    }
                                    ?>
                                <?php else: ?>
                                    <span class="text-muted">정보 없음</span>
                                <?php endif; ?>
                            </p>
                        </div>
                        
                        <!-- 마지막 점검일 및 다음 점검일 -->
                        <div class="col-md-6 mb-3">
                            <h6 class="text-muted">마지막 점검일</h6>
                            <p>
                                <?php if (!empty($equipment['last_maintenance_date'])): ?>
                                    <?php echo date('Y년 m월 d일', strtotime($equipment['last_maintenance_date'])); ?>
                                    <small class="text-muted">(<?php echo time_elapsed($equipment['last_maintenance_date']); ?>)</small>
                                <?php else: ?>
                                    <span class="text-muted">점검 이력 없음</span>
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <h6 class="text-muted">다음 점검 예정일</h6>
                            <p>
                                <?php if (!empty($equipment['next_maintenance_date'])): ?>
                                    <?php
                                    $next_date = strtotime($equipment['next_maintenance_date']);
                                    $now = time();
                                    $days_diff = floor(($next_date - $now) / (60 * 60 * 24));
                                    
                                    echo date('Y년 m월 d일', $next_date);
                                    
                                    if ($days_diff <= 7 && $days_diff >= 0) {
                                        echo ' <span class="badge bg-warning">D-' . $days_diff . '</span>';
                                    } elseif ($days_diff < 0) {
                                        echo ' <span class="badge bg-danger">점검 필요</span>';
                                    }
                                    ?>
                                <?php else: ?>
                                    <span class="text-muted">예정된 점검 없음</span>
                                <?php endif; ?>
                            </p>
                        </div>
                        
                        <!-- 비고 -->
                        <div class="col-12 mb-3">
                            <h6 class="text-muted">유지보수 비고</h6>
                            <p><?php echo !empty($equipment['maintenance_notes']) ? nl2br(htmlspecialchars($equipment['maintenance_notes'])) : '<span class="text-muted">기록 없음</span>'; ?></p>
                        </div>
                        
                        <!-- 등록 및 수정 정보 -->
                        <div class="col-md-6 mb-3">
                            <h6 class="text-muted">등록 날짜</h6>
                            <p><?php echo date('Y-m-d H:i:s', strtotime($equipment['created_at'])); ?></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <h6 class="text-muted">마지막 수정</h6>
                            <p><?php echo date('Y-m-d H:i:s', strtotime($equipment['updated_at'])); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 판매점 정보 및 상태 카드 -->
        <div class="col-xl-6">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-store me-1"></i>
                    판매점 정보
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- 판매점 명 및 코드 -->
                        <div class="col-md-6 mb-3">
                            <h6 class="text-muted">판매점 명</h6>
                            <p>
                                <a href="/dashboard/store/store-details.php?id=<?php echo $equipment['store_id']; ?>">
                                    <?php echo htmlspecialchars($equipment['store_name']); ?>
                                </a>
                            </p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <h6 class="text-muted">판매점 코드</h6>
                            <p class="font-monospace"><?php echo htmlspecialchars($equipment['store_code']); ?></p>
                        </div>
                        
                        <!-- 연락처 및 주소 -->
                        <div class="col-md-6 mb-3">
                            <h6 class="text-muted">연락처</h6>
                            <p><i class="fas fa-phone me-1"></i> <?php echo htmlspecialchars($equipment['store_phone']); ?></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <h6 class="text-muted">주소</h6>
                            <p><i class="fas fa-map-marker-alt me-1"></i> <?php echo htmlspecialchars($equipment['store_address']); ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 상태 차트 카드 -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-chart-pie me-1"></i>
                    장비 상태 요약
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- 운영 상태 -->
                        <div class="col-md-6 mb-3">
                            <div class="card bg-<?php echo $status_class; ?> text-white mb-4">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h5 class="mb-0"><?php echo $status_label; ?></h5>
                                            <div class="small">현재 장비 상태</div>
                                        </div>
                                        <div>
                                            <i class="<?php echo $type_icon; ?> fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- 장비 나이 -->
                        <div class="col-md-6 mb-3">
                            <?php
                            $installation_date = new DateTime($equipment['installation_date']);
                            $now = new DateTime();
                            $age = $installation_date->diff($now);
                            $age_in_days = $age->days;
                            
                            // 장비 나이에 따른 색상 결정
                            $age_color = 'success'; // 기본 녹색 (신품)
                            
                            if ($age_in_days > 1095) { // 3년 초과 (1095일)
                                $age_color = 'danger'; // 빨강 (노후)
                            } elseif ($age_in_days > 730) { // 2년 초과 (730일)
                                $age_color = 'warning'; // 노랑 (주의)
                            } elseif ($age_in_days > 365) { // 1년 초과 (365일)
                                $age_color = 'info'; // 파랑 (양호)
                            }
                            
                            // 장비 나이 문자열 생성
                            $age_str = '';
                            if ($age->y > 0) {
                                $age_str .= $age->y . '년 ';
                            }
                            if ($age->m > 0) {
                                $age_str .= $age->m . '개월 ';
                            }
                            if ($age->d > 0 && $age->y == 0) {
                                $age_str .= $age->d . '일';
                            }
                            $age_str = trim($age_str);
                            ?>
                            <div class="card bg-<?php echo $age_color; ?> text-white mb-4">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h5 class="mb-0"><?php echo $age_str; ?></h5>
                                            <div class="small">장비 사용 기간</div>
                                        </div>
                                        <div>
                                            <i class="fas fa-calendar-alt fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- 유지보수 카운트 -->
                        <div class="col-md-6 mb-3">
                            <div class="card bg-primary text-white mb-4">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h5 class="mb-0"><?php echo count($maintenance_history); ?>회</h5>
                                            <div class="small">유지보수 횟수</div>
                                        </div>
                                        <div>
                                            <i class="fas fa-tools fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- 보증 상태 -->
                        <div class="col-md-6 mb-3">
                            <?php
                            $warranty_color = 'secondary'; // 기본 회색 (정보 없음)
                            $warranty_label = '정보 없음';
                            $warranty_icon = 'fas fa-question-circle';
                            
                            if (!empty($equipment['warranty_end_date'])) {
                                $warranty_date = new DateTime($equipment['warranty_end_date']);
                                $warranty_days_left = $now->diff($warranty_date)->days;
                                $warranty_expired = $warranty_date < $now;
                                
                                if ($warranty_expired) {
                                    $warranty_color = 'danger';
                                    $warranty_label = '만료됨';
                                    $warranty_icon = 'fas fa-exclamation-circle';
                                } else {
                                    if ($warranty_days_left <= 30) {
                                        $warranty_color = 'warning';
                                    } else {
                                        $warranty_color = 'success';
                                    }
                                    $warranty_label = $warranty_days_left . '일 남음';
                                    $warranty_icon = 'fas fa-shield-alt';
                                }
                            }
                            ?>
                            <div class="card bg-<?php echo $warranty_color; ?> text-white mb-4">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h5 class="mb-0"><?php echo $warranty_label; ?></h5>
                                            <div class="small">보증 상태</div>
                                        </div>
                                        <div>
                                            <i class="<?php echo $warranty_icon; ?> fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 유지보수 이력 카드 -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-history me-1"></i>
            유지보수 이력
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
                                <th>유지보수 유형</th>
                                <th>일자</th>
                                <th>기술자 정보</th>
                                <th>상태</th>
                                <th>내용</th>
                                <th>해결책</th>
                                <th>비용</th>
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
                                        <?php if (!empty($maintenance['technician_name'])): ?>
                                            <?php echo htmlspecialchars($maintenance['technician_name']); ?>
                                            <?php if (!empty($maintenance['technician_contact'])): ?>
                                                <br><small><?php echo htmlspecialchars($maintenance['technician_contact']); ?></small>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted">정보 없음</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="badge bg-<?php echo $m_status_class; ?>"><?php echo $m_status_label; ?></span></td>
                                    <td>
                                        <?php if (!empty($maintenance['issue_description'])): ?>
                                            <?php
                                            $issue_desc = $maintenance['issue_description'];
                                            if (strlen($issue_desc) > 100) {
                                                echo htmlspecialchars(substr($issue_desc, 0, 100)) . '...';
                                                echo '<button type="button" class="btn btn-link btn-sm p-0 mt-1" data-bs-toggle="modal" data-bs-target="#descriptionModal' . $maintenance['id'] . '">더 보기</button>';
                                            } else {
                                                echo htmlspecialchars($issue_desc);
                                            }
                                            ?>
                                        <?php else: ?>
                                            <span class="text-muted">정보 없음</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($maintenance['resolution'])): ?>
                                            <?php
                                            $resolution = $maintenance['resolution'];
                                            if (strlen($resolution) > 100) {
                                                echo htmlspecialchars(substr($resolution, 0, 100)) . '...';
                                                echo '<button type="button" class="btn btn-link btn-sm p-0 mt-1" data-bs-toggle="modal" data-bs-target="#resolutionModal' . $maintenance['id'] . '">더 보기</button>';
                                            } else {
                                                echo htmlspecialchars($resolution);
                                            }
                                            ?>
                                        <?php else: ?>
                                            <span class="text-muted">정보 없음</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <?php if (!empty($maintenance['cost']) && $maintenance['cost'] > 0): ?>
                                            <?php echo number_format($maintenance['cost'], 2); ?> 원
                                        <?php else: ?>
                                            <span class="text-muted">0.00 원</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                
                                <!-- 설명 모달 -->
                                <?php if (!empty($maintenance['issue_description']) && strlen($maintenance['issue_description']) > 100): ?>
                                    <div class="modal fade" id="descriptionModal<?php echo $maintenance['id']; ?>" tabindex="-1" aria-labelledby="descriptionModalLabel<?php echo $maintenance['id']; ?>" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="descriptionModalLabel<?php echo $maintenance['id']; ?>">문제 설명</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <?php echo nl2br(htmlspecialchars($maintenance['issue_description'])); ?>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">닫기</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- 해결책 모달 -->
                                <?php if (!empty($maintenance['resolution']) && strlen($maintenance['resolution']) > 100): ?>
                                    <div class="modal fade" id="resolutionModal<?php echo $maintenance['id']; ?>" tabindex="-1" aria-labelledby="resolutionModalLabel<?php echo $maintenance['id']; ?>" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="resolutionModalLabel<?php echo $maintenance['id']; ?>">해결책</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <?php echo nl2br(htmlspecialchars($maintenance['resolution'])); ?>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">닫기</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
            
            <div class="mt-3">
                <a href="/dashboard/store/equipment-maintenance.php?equipment_id=<?php echo $equipment_id; ?>" class="btn btn-primary">
                    <i class="fas fa-tools me-1"></i> 유지보수 관리
                </a>
                <a href="/dashboard/store/equipment-maintenance-add.php?equipment_id=<?php echo $equipment_id; ?>" class="btn btn-success">
                    <i class="fas fa-plus me-1"></i> 새 유지보수 일정 추가
                </a>
            </div>
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
                    <input type="hidden" name="equipment_id" value="<?php echo $equipment_id; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    
                    <div class="mb-3">
                        <label for="new_status" class="form-label">새 상태</label>
                        <select name="new_status" id="new_status" class="form-select" required>
                            <option value="operational" <?php echo ($equipment['status'] === 'operational') ? 'selected' : ''; ?>>정상 작동</option>
                            <option value="maintenance" <?php echo ($equipment['status'] === 'maintenance') ? 'selected' : ''; ?>>유지보수 중</option>
                            <option value="faulty" <?php echo ($equipment['status'] === 'faulty') ? 'selected' : ''; ?>>고장</option>
                            <option value="replaced" <?php echo ($equipment['status'] === 'replaced') ? 'selected' : ''; ?>>교체됨</option>
                            <option value="retired" <?php echo ($equipment['status'] === 'retired') ? 'selected' : ''; ?>>폐기됨</option>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('장비 상세 정보 페이지 로드됨');
});
</script>

<?php
// 푸터 인클루드
include_once '../../templates/dashboard-footer.php';
?>