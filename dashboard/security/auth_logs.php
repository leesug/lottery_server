<?php
/**
 * 인증 로그 페이지
 * 
 * 이 페이지는 시스템 사용자의 인증 관련 로그를 표시합니다.
 * 로그인 성공/실패, 비밀번호 변경, 계정 잠금 등의 이벤트를 모니터링합니다.
 */

// 공통 파일 포함
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// 세션 시작
session_start();

// 페이지 변수 설정
$pageTitle = "인증 로그";
$currentSection = "security";
$currentPage = basename($_SERVER['PHP_SELF']);

// 데이터베이스 연결
$db = get_db_connection();

// 필터링 설정
$username = isset($_GET['username']) ? $_GET['username'] : '';
$eventType = isset($_GET['event_type']) ? $_GET['event_type'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$fromDate = isset($_GET['from_date']) ? $_GET['from_date'] : date('Y-m-d', strtotime('-7 days'));
$toDate = isset($_GET['to_date']) ? $_GET['to_date'] : date('Y-m-d');

// 쿼리 빌드
$sql = "SELECT al.*, u.username 
        FROM auth_logs al
        LEFT JOIN users u ON al.user_id = u.id
        WHERE 1=1";

// 필터 조건 추가
$params = [];

if (!empty($username)) {
    $sql .= " AND u.username LIKE :username";
    $params[':username'] = "%$username%";
}

if (!empty($eventType)) {
    $sql .= " AND al.event_type = :event_type";
    $params[':event_type'] = $eventType;
}

if (!empty($status)) {
    $sql .= " AND al.status = :status";
    $params[':status'] = $status;
}

if (!empty($fromDate)) {
    $sql .= " AND DATE(al.event_time) >= :from_date";
    $params[':from_date'] = $fromDate;
}

if (!empty($toDate)) {
    $sql .= " AND DATE(al.event_time) <= :to_date";
    $params[':to_date'] = $toDate;
}

// 정렬
$sql .= " ORDER BY al.event_time DESC";

// 페이지네이션
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// 전체 레코드 수 조회
$countSql = str_replace("al.*, u.username", "COUNT(*) as total", $sql);
$countSql = preg_replace('/ORDER BY.*$/i', '', $countSql);

$stmt = $db->prepare($countSql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$totalRows = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
$totalPages = ceil($totalRows / $perPage);

// 데이터 조회
$sql .= " LIMIT :offset, :per_page";
$stmt = $db->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':per_page', $perPage, PDO::PARAM_INT);
$stmt->execute();
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 이벤트 유형 목록 조회
$eventTypesSql = "SELECT DISTINCT event_type FROM auth_logs ORDER BY event_type";
$eventTypesStmt = $db->prepare($eventTypesSql);
$eventTypesStmt->execute();
$eventTypes = $eventTypesStmt->fetchAll(PDO::FETCH_COLUMN);

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
                                <label>사용자명</label>
                                <input type="text" class="form-control" name="username" value="<?php echo htmlspecialchars($username); ?>" placeholder="사용자명">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>이벤트 유형</label>
                                <select class="form-control" name="event_type">
                                    <option value="">전체</option>
                                    <?php foreach ($eventTypes as $type): ?>
                                    <option value="<?php echo htmlspecialchars($type); ?>" <?php echo $eventType == $type ? 'selected' : ''; ?>><?php echo htmlspecialchars($type); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>상태</label>
                                <select class="form-control" name="status">
                                    <option value="">전체</option>
                                    <option value="success" <?php echo $status == 'success' ? 'selected' : ''; ?>>성공</option>
                                    <option value="failure" <?php echo $status == 'failure' ? 'selected' : ''; ?>>실패</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>시작일</label>
                                <input type="date" class="form-control" name="from_date" value="<?php echo htmlspecialchars($fromDate); ?>">
                            </div>
                        </div>
                        <div class="col-md-2">
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
                        $stmt = $db->prepare("SELECT COUNT(*) as count FROM auth_logs WHERE DATE(event_time) = CURDATE()");
                        $stmt->execute();
                        $todayCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                        ?>
                        <h3><?php echo $todayCount; ?></h3>
                        <p>오늘의 인증 기록</p>
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
                        $stmt = $db->prepare("SELECT COUNT(*) as count FROM auth_logs WHERE status = 'success' AND DATE(event_time) = CURDATE()");
                        $stmt->execute();
                        $successCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                        ?>
                        <h3><?php echo $successCount; ?></h3>
                        <p>성공한 인증</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <a href="?status=success&from_date=<?php echo date('Y-m-d'); ?>&to_date=<?php echo date('Y-m-d'); ?>" class="small-box-footer">
                        자세히 보기 <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <?php
                        $stmt = $db->prepare("SELECT COUNT(*) as count FROM auth_logs WHERE status = 'failure' AND DATE(event_time) = CURDATE()");
                        $stmt->execute();
                        $failureCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                        ?>
                        <h3><?php echo $failureCount; ?></h3>
                        <p>실패한 인증</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <a href="?status=failure&from_date=<?php echo date('Y-m-d'); ?>&to_date=<?php echo date('Y-m-d'); ?>" class="small-box-footer">
                        자세히 보기 <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <?php
                        $stmt = $db->prepare("SELECT COUNT(DISTINCT user_id) as count FROM auth_logs 
                                             WHERE status = 'failure' 
                                             AND event_type = 'login' 
                                             AND event_time >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                                             AND user_id IS NOT NULL");
                        $stmt->execute();
                        $lockedAccounts = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                        ?>
                        <h3><?php echo $lockedAccounts; ?></h3>
                        <p>잠재적 계정 잠금</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-lock"></i>
                    </div>
                    <a href="?event_type=login&status=failure&from_date=<?php echo date('Y-m-d', strtotime('-1 day')); ?>&to_date=<?php echo date('Y-m-d'); ?>" class="small-box-footer">
                        자세히 보기 <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
        </div>
        <!-- /통계 개요 -->

        <!-- 로그 목록 -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">인증 로그</h3>
                <div class="card-tools">
                    <span class="badge badge-info">총 <?php echo $totalRows; ?>개의 로그</span>
                </div>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover text-nowrap">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>사용자</th>
                            <th>이벤트 유형</th>
                            <th>상태</th>
                            <th>IP 주소</th>
                            <th>세부 정보</th>
                            <th>이벤트 시간</th>
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
                                <td><?php echo htmlspecialchars($log['username'] ?? '비회원'); ?></td>
                                <td>
                                    <?php 
                                    switch ($log['event_type']) {
                                        case 'login': echo '<span class="badge bg-primary">로그인</span>'; break;
                                        case 'logout': echo '<span class="badge bg-secondary">로그아웃</span>'; break;
                                        case 'password_change': echo '<span class="badge bg-info">비밀번호 변경</span>'; break;
                                        case 'password_reset': echo '<span class="badge bg-warning">비밀번호 재설정</span>'; break;
                                        case 'account_lock': echo '<span class="badge bg-danger">계정 잠금</span>'; break;
                                        case 'account_unlock': echo '<span class="badge bg-success">계정 잠금 해제</span>'; break;
                                        case '2fa_setup': echo '<span class="badge bg-info">2FA 설정</span>'; break;
                                        case '2fa_verification': echo '<span class="badge bg-primary">2FA 인증</span>'; break;
                                        default: echo '<span class="badge bg-secondary">' . htmlspecialchars($log['event_type']) . '</span>'; break;
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php if ($log['status'] === 'success'): ?>
                                        <span class="badge bg-success">성공</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">실패</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($log['ip_address']); ?></td>
                                <td>
                                    <?php 
                                    $details = json_decode($log['details'] ?? '{}', true);
                                    if (!empty($details)) {
                                        echo '<span class="text-muted" data-toggle="tooltip" title="' . htmlspecialchars(json_encode($details)) . '">';
                                        echo mb_substr(json_encode($details), 0, 30) . (strlen(json_encode($details)) > 30 ? '...' : '');
                                        echo '</span>';
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                                <td><?php echo date('Y-m-d H:i:s', strtotime($log['event_time'])); ?></td>
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
    console.log("인증 로그 페이지가 로드되었습니다.");
    
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
            
            fetch(`/api/auth_logs.php?action=get_details&id=${logId}`)
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
                                        <th>사용자</th>
                                        <td>${log.username || '비회원'}</td>
                                    </tr>
                                    <tr>
                                        <th>이벤트 유형</th>
                                        <td>${getEventTypeLabel(log.event_type)}</td>
                                    </tr>
                                    <tr>
                                        <th>상태</th>
                                        <td>${log.status === 'success' ? '<span class="badge bg-success">성공</span>' : '<span class="badge bg-danger">실패</span>'}</td>
                                    </tr>
                                    <tr>
                                        <th>IP 주소</th>
                                        <td>${log.ip_address}</td>
                                    </tr>
                                    <tr>
                                        <th>사용자 에이전트</th>
                                        <td>${log.user_agent || '-'}</td>
                                    </tr>`;
                        
                        // 세부 정보가 있는 경우 표시
                        if (log.details) {
                            const details = JSON.parse(log.details);
                            html += `
                                    <tr>
                                        <th>세부 정보</th>
                                        <td>
                                            <pre class="mb-0" style="max-height: 200px; overflow-y: auto;">${JSON.stringify(details, null, 2)}</pre>
                                        </td>
                                    </tr>`;
                        }
                        
                        html += `
                                    <tr>
                                        <th>이벤트 시간</th>
                                        <td>${log.event_time}</td>
                                    </tr>
                                </table>
                            </div>
                            
                            <h5 class="mt-4">같은 IP의 최근 활동</h5>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>이벤트 유형</th>
                                            <th>사용자</th>
                                            <th>상태</th>
                                            <th>날짜</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                        `;
                        
                        if (log.related_logs && log.related_logs.length > 0) {
                            log.related_logs.forEach(related => {
                                html += `
                                    <tr>
                                        <td>${getEventTypeLabel(related.event_type)}</td>
                                        <td>${related.username || '비회원'}</td>
                                        <td>${related.status === 'success' ? '<span class="badge bg-success">성공</span>' : '<span class="badge bg-danger">실패</span>'}</td>
                                        <td>${related.event_time}</td>
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
                        logDetailsContent.innerHTML = `<div class="alert alert-danger">${data.message || '데이터를 불러오는 중 오류가 발생했습니다.'}</div>`;
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
                    reason: '인증 로그에서 차단'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('IP 주소가 성공적으로 차단되었습니다.');
                    blockIpButton.disabled = true;
                    blockIpButton.textContent = 'IP 차단됨';
                } else {
                    alert(`오류: ${data.message || '알 수 없는 오류가 발생했습니다.'}`);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('IP 차단 중 오류가 발생했습니다.');
            });
        }
    });
    
    // 이벤트 유형 레이블 가져오기
    function getEventTypeLabel(type) {
        switch (type) {
            case 'login': return '<span class="badge bg-primary">로그인</span>';
            case 'logout': return '<span class="badge bg-secondary">로그아웃</span>';
            case 'password_change': return '<span class="badge bg-info">비밀번호 변경</span>';
            case 'password_reset': return '<span class="badge bg-warning">비밀번호 재설정</span>';
            case 'account_lock': return '<span class="badge bg-danger">계정 잠금</span>';
            case 'account_unlock': return '<span class="badge bg-success">계정 잠금 해제</span>';
            case '2fa_setup': return '<span class="badge bg-info">2FA 설정</span>';
            case '2fa_verification': return '<span class="badge bg-primary">2FA 인증</span>';
            default: return `<span class="badge bg-secondary">${type}</span>`;
        }
    }
    
    // 툴팁 초기화
    try {
        $('[data-toggle="tooltip"]').tooltip();
    } catch (e) {
        console.warn('jQuery not loaded, tooltip initialization skipped');
    }
    
    // 날짜 필터 변경 시 자동 폼 제출
    document.querySelectorAll('form#filterForm select, form#filterForm input[type="date"]').forEach(element => {
        element.addEventListener('change', function() {
            document.getElementById('filterForm').submit();
        });
    });
});
</script>

<?php include_once TEMPLATES_PATH . '/dashboard_footer.php'; ?>