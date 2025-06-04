<?php
// 공통 파일 포함
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// 세션 체크 및 권한 검증 (개발 환경에서는 무시)
session_start();
// if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
//     header('Location: /pages/access-denied.php');
//     exit;
// }

// 페이지 변수 설정
$pageTitle = "외부 접속 감시 대시보드";
$currentSection = "security";
$currentPage = basename($_SERVER['PHP_SELF']);

// 데이터베이스 연결
$db = getDBConnection();

// 필터링 설정
$entityType = isset($_GET['entity_type']) ? $_GET['entity_type'] : '';
$fromDate = isset($_GET['from_date']) ? $_GET['from_date'] : date('Y-m-d', strtotime('-7 days'));
$toDate = isset($_GET['to_date']) ? $_GET['to_date'] : date('Y-m-d');
$activityType = isset($_GET['activity_type']) ? $_GET['activity_type'] : '';

// 쿼리 빌드
$sql = "SELECT eml.*, 
        CASE 
            WHEN eml.entity_type = 'broadcaster' THEN b.name
            WHEN eml.entity_type = 'bank' THEN bk.bank_name
            WHEN eml.entity_type = 'government' THEN ga.agency_name
            WHEN eml.entity_type = 'fund' THEN fd.department_name
            ELSE 'Unknown'
        END as entity_name
        FROM external_monitoring_logs eml
        LEFT JOIN broadcaster b ON eml.entity_type = 'broadcaster' AND eml.entity_id = b.id
        LEFT JOIN banks bk ON eml.entity_type = 'bank' AND eml.entity_id = bk.id
        LEFT JOIN government_agencies ga ON eml.entity_type = 'government' AND eml.entity_id = ga.id
        LEFT JOIN fund_departments fd ON eml.entity_type = 'fund' AND eml.entity_id = fd.id
        WHERE 1=1";

// 필터 조건 추가
if (!empty($entityType)) {
    $sql .= " AND eml.entity_type = :entity_type";
}

if (!empty($fromDate)) {
    $sql .= " AND DATE(eml.log_date) >= :from_date";
}

if (!empty($toDate)) {
    $sql .= " AND DATE(eml.log_date) <= :to_date";
}

if (!empty($activityType)) {
    $sql .= " AND eml.activity_type = :activity_type";
}

// 정렬
$sql .= " ORDER BY eml.log_date DESC";

// 페이지네이션
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

$countSql = str_replace("eml.*, CASE", "COUNT(*) as total", $sql);
$countSql = preg_replace('/ORDER BY.*$/i', '', $countSql);

$stmt = $db->prepare($countSql);

// 파라미터 바인딩
if (!empty($entityType)) {
    $stmt->bindParam(':entity_type', $entityType, PDO::PARAM_STR);
}

if (!empty($fromDate)) {
    $stmt->bindParam(':from_date', $fromDate, PDO::PARAM_STR);
}

if (!empty($toDate)) {
    $stmt->bindParam(':to_date', $toDate, PDO::PARAM_STR);
}

if (!empty($activityType)) {
    $stmt->bindParam(':activity_type', $activityType, PDO::PARAM_STR);
}

$stmt->execute();
$totalRows = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
$totalPages = ceil($totalRows / $perPage);

// 데이터 조회
$sql .= " LIMIT :offset, :per_page";
$stmt = $db->prepare($sql);

// 파라미터 바인딩
if (!empty($entityType)) {
    $stmt->bindParam(':entity_type', $entityType, PDO::PARAM_STR);
}

if (!empty($fromDate)) {
    $stmt->bindParam(':from_date', $fromDate, PDO::PARAM_STR);
}

if (!empty($toDate)) {
    $stmt->bindParam(':to_date', $toDate, PDO::PARAM_STR);
}

if (!empty($activityType)) {
    $stmt->bindParam(':activity_type', $activityType, PDO::PARAM_STR);
}

$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->bindParam(':per_page', $perPage, PDO::PARAM_INT);

$stmt->execute();
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 활동 유형 목록 조회
$activityTypesSql = "SELECT DISTINCT activity_type FROM external_monitoring_logs ORDER BY activity_type";
$activityTypesStmt = $db->prepare($activityTypesSql);
$activityTypesStmt->execute();
$activityTypes = $activityTypesStmt->fetchAll(PDO::FETCH_COLUMN);

// 로그 내보내기 처리
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    // 헤더 설정
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="external_monitoring_logs_'.date('Y-m-d').'.csv"');
    
    // CSV 출력
    $output = fopen('php://output', 'w');
    
    // BOM (UTF-8 인코딩 표시)
    fprintf($output, "\xEF\xBB\xBF");
    
    // 헤더 출력
    fputcsv($output, ['ID', '엔터티 유형', '엔터티 이름', '활동 유형', '설명', 'IP 주소', '사용자 에이전트', '로그 날짜']);
    
    // 쿼리 수정하여 모든 결과 가져오기 (페이지네이션 제거)
    $exportSql = preg_replace('/LIMIT.*$/i', '', $sql);
    $stmt = $db->prepare($exportSql);
    
    // 파라미터 바인딩
    if (!empty($entityType)) {
        $stmt->bindParam(':entity_type', $entityType, PDO::PARAM_STR);
    }
    
    if (!empty($fromDate)) {
        $stmt->bindParam(':from_date', $fromDate, PDO::PARAM_STR);
    }
    
    if (!empty($toDate)) {
        $stmt->bindParam(':to_date', $toDate, PDO::PARAM_STR);
    }
    
    if (!empty($activityType)) {
        $stmt->bindParam(':activity_type', $activityType, PDO::PARAM_STR);
    }
    
    $stmt->execute();
    
    // 데이터 출력
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $entityTypeKorean = '';
        switch ($row['entity_type']) {
            case 'broadcaster': $entityTypeKorean = '방송국'; break;
            case 'bank': $entityTypeKorean = '은행'; break;
            case 'government': $entityTypeKorean = '정부기관'; break;
            case 'fund': $entityTypeKorean = '기금처'; break;
            default: $entityTypeKorean = '알 수 없음'; break;
        }
        
        fputcsv($output, [
            $row['id'],
            $entityTypeKorean,
            $row['entity_name'],
            $row['activity_type'],
            $row['description'],
            $row['ip_address'],
            $row['user_agent'],
            $row['log_date']
        ]);
    }
    
    fclose($output);
    exit;
}

// 템플릿 헤더 포함
include_once TEMPLATES_PATH . '/dashboard_header.php';
?>

<!-- Content Header (Page header) -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><?php echo $pageTitle; ?></h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?php echo SERVER_URL; ?>/dashboard/index.php">홈</a></li>
                    <li class="breadcrumb-item">보안 관리</li>
                    <li class="breadcrumb-item active"><?php echo $pageTitle; ?></li>
                </ol>
            </div>
        </div>
    </div>
</div>
<!-- /.content-header -->

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <!-- 필터 카드 -->
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">로그 필터링</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <form id="filterForm" method="GET" action="">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>엔터티 유형</label>
                                <select class="form-control" name="entity_type">
                                    <option value="">전체</option>
                                    <option value="broadcaster" <?php echo $entityType == 'broadcaster' ? 'selected' : ''; ?>>방송국</option>
                                    <option value="bank" <?php echo $entityType == 'bank' ? 'selected' : ''; ?>>은행</option>
                                    <option value="government" <?php echo $entityType == 'government' ? 'selected' : ''; ?>>정부기관</option>
                                    <option value="fund" <?php echo $entityType == 'fund' ? 'selected' : ''; ?>>기금처</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>활동 유형</label>
                                <select class="form-control" name="activity_type">
                                    <option value="">전체</option>
                                    <?php foreach ($activityTypes as $type): ?>
                                    <option value="<?php echo htmlspecialchars($type); ?>" <?php echo $activityType == $type ? 'selected' : ''; ?>><?php echo htmlspecialchars($type); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>시작일</label>
                                <input type="date" class="form-control" name="from_date" value="<?php echo htmlspecialchars($fromDate); ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>종료일</label>
                                <input type="date" class="form-control" name="to_date" value="<?php echo htmlspecialchars($toDate); ?>">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12 text-right">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> 검색
                            </button>
                            <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn btn-default">
                                <i class="fas fa-redo"></i> 초기화
                            </a>
                            <a href="<?php echo $_SERVER['PHP_SELF'] . '?' . http_build_query(array_merge($_GET, ['export' => 'csv'])); ?>" class="btn btn-success">
                                <i class="fas fa-file-csv"></i> CSV 내보내기
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <!-- /필터 카드 -->

        <!-- 통계 개요 -->
        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <?php
                        $stmt = $db->prepare("SELECT COUNT(*) as count FROM external_monitoring_logs WHERE DATE(log_date) = CURDATE()");
                        $stmt->execute();
                        $todayCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                        ?>
                        <h3><?php echo $todayCount; ?></h3>
                        <p>오늘의 접속 기록</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <a href="?from_date=<?php echo date('Y-m-d'); ?>&to_date=<?php echo date('Y-m-d'); ?>" class="small-box-footer">
                        자세히 보기 <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <?php
                        $stmt = $db->prepare("SELECT COUNT(DISTINCT ip_address) as count FROM external_monitoring_logs WHERE DATE(log_date) = CURDATE()");
                        $stmt->execute();
                        $uniqueIPs = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                        ?>
                        <h3><?php echo $uniqueIPs; ?></h3>
                        <p>고유 IP 주소</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-network-wired"></i>
                    </div>
                    <a href="#" class="small-box-footer">
                        자세히 보기 <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <?php
                        $stmt = $db->prepare("SELECT COUNT(*) as count FROM external_monitoring_logs WHERE log_date >= DATE_SUB(NOW(), INTERVAL 1 HOUR)");
                        $stmt->execute();
                        $lastHourCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                        ?>
                        <h3><?php echo $lastHourCount; ?></h3>
                        <p>최근 1시간 활동</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <a href="#" class="small-box-footer">
                        자세히 보기 <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <?php
                        // 여기서는 로그인 실패 등의 의심스러운 활동을 카운트할 수 있습니다.
                        // 예시로 '로그인 실패'라는 활동 유형이 있다고 가정합니다.
                        $stmt = $db->prepare("SELECT COUNT(*) as count FROM external_monitoring_logs WHERE activity_type = '로그인 실패' AND DATE(log_date) = CURDATE()");
                        $stmt->execute();
                        $suspiciousCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                        ?>
                        <h3><?php echo $suspiciousCount; ?></h3>
                        <p>의심스러운 활동</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <a href="?activity_type=로그인 실패" class="small-box-footer">
                        자세히 보기 <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
        </div>
        <!-- /통계 개요 -->

        <!-- 로그 목록 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">외부 접속 로그</h3>
                <div class="card-tools">
                    <span class="badge badge-info">총 <?php echo $totalRows; ?>개의 로그</span>
                </div>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover text-nowrap">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>엔터티 유형</th>
                            <th>엔터티 이름</th>
                            <th>활동 유형</th>
                            <th>설명</th>
                            <th>IP 주소</th>
                            <th>로그 날짜</th>
                            <th>작업</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="8" class="text-center">검색 조건에 맞는 로그가 없습니다.</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?php echo $log['id']; ?></td>
                                <td>
                                    <?php 
                                    switch ($log['entity_type']) {
                                        case 'broadcaster': echo '<span class="badge bg-primary">방송국</span>'; break;
                                        case 'bank': echo '<span class="badge bg-success">은행</span>'; break;
                                        case 'government': echo '<span class="badge bg-warning">정부기관</span>'; break;
                                        case 'fund': echo '<span class="badge bg-info">기금처</span>'; break;
                                        default: echo '<span class="badge bg-secondary">알 수 없음</span>'; break;
                                    }
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($log['entity_name']); ?></td>
                                <td><?php echo htmlspecialchars($log['activity_type']); ?></td>
                                <td><?php echo htmlspecialchars($log['description']); ?></td>
                                <td><?php echo htmlspecialchars($log['ip_address']); ?></td>
                                <td><?php echo date('Y-m-d H:i:s', strtotime($log['log_date'])); ?></td>
                                <td>
                                    <button type="button" class="btn btn-xs btn-info view-details" data-toggle="modal" data-target="#logDetailsModal" data-log-id="<?php echo $log['id']; ?>">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer clearfix">
                <ul class="pagination pagination-sm m-0 float-right">
                    <?php if ($page > 1): ?>
                    <li class="page-item"><a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>">&laquo;</a></li>
                    <li class="page-item"><a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">&lsaquo;</a></li>
                    <?php endif; ?>
                    
                    <?php
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $page + 2);
                    
                    for ($i = $startPage; $i <= $endPage; $i++):
                    ?>
                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                    </li>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                    <li class="page-item"><a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">&rsaquo;</a></li>
                    <li class="page-item"><a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $totalPages])); ?>">&raquo;</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
        <!-- /로그 목록 -->
    </div>
</section>
<!-- /.content -->

<!-- 로그 상세 정보 모달 -->
<div class="modal fade" id="logDetailsModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">로그 상세 정보</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="logDetailsContent">
                    <p>로딩 중...</p>
                </div>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-default" data-dismiss="modal">닫기</button>
                <button type="button" class="btn btn-danger" id="blockIpButton">IP 차단</button>
            </div>
        </div>
    </div>
</div>
<!-- /로그 상세 정보 모달 -->

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 로그 상세 정보 조회
    const viewButtons = document.querySelectorAll('.view-details');
    const logDetailsContent = document.getElementById('logDetailsContent');
    const blockIpButton = document.getElementById('blockIpButton');
    let currentLogId = null;
    let currentIpAddress = null;

    viewButtons.forEach(button => {
        button.addEventListener('click', function() {
            const logId = this.getAttribute('data-log-id');
            currentLogId = logId;
            
            // 로그 상세 정보 Ajax 요청
            logDetailsContent.innerHTML = '<p class="text-center"><i class="fas fa-spinner fa-spin"></i> 로딩 중...</p>';
            
            fetch(`/api/external_monitoring_logs.php?action=get_details&id=${logId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const log = data.data;
                        currentIpAddress = log.ip_address;
                        
                        // 로그 상세 정보 표시
                        let html = `
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <tr>
                                        <th style="width: 30%">ID</th>
                                        <td>${log.id}</td>
                                    </tr>
                                    <tr>
                                        <th>엔터티 유형</th>
                                        <td>${getEntityTypeLabel(log.entity_type)}</td>
                                    </tr>
                                    <tr>
                                        <th>엔터티 이름</th>
                                        <td>${log.entity_name}</td>
                                    </tr>
                                    <tr>
                                        <th>활동 유형</th>
                                        <td>${log.activity_type}</td>
                                    </tr>
                                    <tr>
                                        <th>설명</th>
                                        <td>${log.description}</td>
                                    </tr>
                                    <tr>
                                        <th>IP 주소</th>
                                        <td>${log.ip_address}</td>
                                    </tr>
                                    <tr>
                                        <th>사용자 에이전트</th>
                                        <td>${log.user_agent || '-'}</td>
                                    </tr>
                                    <tr>
                                        <th>사용자 ID</th>
                                        <td>${log.user_id || '-'}</td>
                                    </tr>
                                    <tr>
                                        <th>로그 날짜</th>
                                        <td>${log.log_date}</td>
                                    </tr>
                                </table>
                            </div>
                            
                            <h5 class="mt-4">같은 IP의 최근 활동</h5>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>활동 유형</th>
                                            <th>엔터티</th>
                                            <th>설명</th>
                                            <th>날짜</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                        `;
                        
                        if (log.related_logs && log.related_logs.length > 0) {
                            log.related_logs.forEach(related => {
                                html += `
                                    <tr>
                                        <td>${related.activity_type}</td>
                                        <td>${getEntityTypeLabel(related.entity_type)} - ${related.entity_name}</td>
                                        <td>${related.description}</td>
                                        <td>${related.log_date}</td>
                                    </tr>
                                `;
                            });
                        } else {
                            html += `<tr><td colspan="4" class="text-center">관련 활동이 없습니다.</td></tr>`;
                        }
                        
                        html += `
                                    </tbody>
                                </table>
                            </div>
                        `;
                        
                        logDetailsContent.innerHTML = html;
                        
                        // IP 차단 버튼 상태 업데이트
                        blockIpButton.disabled = log.ip_is_blocked;
                        blockIpButton.textContent = log.ip_is_blocked ? 'IP 차단됨' : 'IP 차단';
                    } else {
                        logDetailsContent.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    logDetailsContent.innerHTML = '<div class="alert alert-danger">데이터를 불러오는 중 오류가 발생했습니다.</div>';
                });
        });
    });
    
    // IP 차단 버튼 클릭 이벤트
    blockIpButton.addEventListener('click', function() {
        if (!currentIpAddress) return;
        
        if (confirm(`정말로 IP 주소 ${currentIpAddress}를 차단하시겠습니까?`)) {
            // IP 차단 Ajax 요청
            fetch('/api/ip_blocklist.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'block_ip',
                    ip_address: currentIpAddress,
                    log_id: currentLogId,
                    reason: '외부 접속 감시 로그에서 차단'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('IP 주소가 성공적으로 차단되었습니다.');
                    blockIpButton.disabled = true;
                    blockIpButton.textContent = 'IP 차단됨';
                } else {
                    alert(`오류: ${data.message}`);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('IP 차단 중 오류가 발생했습니다.');
            });
        }
    });
    
    // 엔터티 유형 레이블 가져오기
    function getEntityTypeLabel(type) {
        switch (type) {
            case 'broadcaster': return '<span class="badge bg-primary">방송국</span>';
            case 'bank': return '<span class="badge bg-success">은행</span>';
            case 'government': return '<span class="badge bg-warning">정부기관</span>';
            case 'fund': return '<span class="badge bg-info">기금처</span>';
            default: return '<span class="badge bg-secondary">알 수 없음</span>';
        }
    }
    
    // 날짜 필터 변경 시 자동 폼 제출
    document.querySelectorAll('form#filterForm select, form#filterForm input[type="date"]').forEach(element => {
        element.addEventListener('change', function() {
            document.getElementById('filterForm').submit();
        });
    });
    
    // 콘솔에 로딩 완료 로그
    console.log("[외부 접속 감시] 페이지 로딩 완료");
});
</script>

<?php include_once TEMPLATES_PATH . '/dashboard_footer.php'; ?>
